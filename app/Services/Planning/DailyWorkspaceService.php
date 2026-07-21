<?php

namespace App\Services\Planning;

use App\Models\Planning\PlanningDailyTask;
use App\Models\Planning\WorkplanCorrectiveAction;
use App\Models\Planning\WorkplanItem;
use App\Models\User;
use App\Services\Enterprise\DomainEventService;
use App\Services\Enterprise\NotificationService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DailyWorkspaceService
{
    public function __construct(private readonly PlanningPerformanceService $planning)
    {
    }

    public function workspace(int $tenantId, User $user): array
    {
        $this->generateForUser($tenantId, $user);
        $today = now()->toDateString();

        $base = PlanningDailyTask::with(['item.workplan', 'item.objective', 'department', 'position', 'employee', 'supervisor', 'evidence'])
            ->where('tenant_id', $tenantId);

        $myTasks = (clone $base)
            ->where('employee_id', $user->id)
            ->where(function ($query) use ($today): void {
                $query->whereDate('task_date', '<=', $today)
                    ->orWhereIn('status', ['Blocked', 'In Progress', 'Awaiting Evidence', 'Submitted']);
            })
            ->orderByRaw("CASE priority WHEN 'Critical' THEN 0 WHEN 'High' THEN 1 WHEN 'Medium' THEN 2 WHEN 'Low' THEN 3 ELSE 4 END")
            ->orderBy('due_at')
            ->orderBy('task_date')
            ->get();

        $supervisorTasks = collect();
        if ($this->canSupervise($user)) {
            $supervisorTasks = (clone $base)
                ->where(function ($query) use ($user): void {
                    $query->where('supervisor_id', $user->id)
                        ->orWhereIn('status', ['Submitted', 'Blocked', 'Awaiting Review']);
                })
                ->latest('updated_at')
                ->limit(30)
                ->get();
        }

        return [
            'myTasks' => $myTasks,
            'todayTasks' => $myTasks->where('task_date', $today),
            'overdueTasks' => $myTasks->filter(fn (PlanningDailyTask $task): bool => $task->due_at && $task->due_at->isPast() && ! in_array($task->status, ['Completed', 'Verified', 'Cancelled'], true)),
            'supervisorTasks' => $supervisorTasks,
            'metrics' => $this->metrics($myTasks, $supervisorTasks),
            'scorecard' => $this->scorecard($tenantId, $user),
        ];
    }

    public function generateForUser(int $tenantId, User $user): void
    {
        $today = now()->toDateString();

        WorkplanItem::with(['assignments', 'workplan'])
            ->where('tenant_id', $tenantId)
            ->where('approval_status', 'Approved')
            ->where(function ($query) use ($today): void {
                $query->whereNull('starts_on')->orWhereDate('starts_on', '<=', $today);
            })
            ->where(function ($query) use ($today): void {
                $query->whereNull('due_on')->orWhereDate('due_on', '>=', $today);
            })
            ->whereHas('assignments', fn ($query) => $query->where('employee_id', $user->id)->where('status', 'Active'))
            ->get()
            ->each(function (WorkplanItem $item) use ($tenantId, $user, $today): void {
                $assignment = $item->assignments->firstWhere('employee_id', $user->id);
                PlanningDailyTask::updateOrCreate(
                    [
                        'tenant_id' => $tenantId,
                        'source_type' => WorkplanItem::class,
                        'source_id' => $item->id,
                        'employee_id' => $user->id,
                        'task_date' => $today,
                    ],
                    [
                        'workplan_item_id' => $item->id,
                        'department_id' => $assignment?->department_id ?? $item->workplan?->department_id,
                        'position_id' => $assignment?->position_id ?? $item->workplan?->position_id,
                        'supervisor_id' => $assignment?->supervisor_id,
                        'source_module' => 'Planning and Performance',
                        'source_reference' => $item->reference,
                        'title' => $item->title,
                        'description' => $item->description,
                        'expected_output' => $item->required_evidence_type ?: $item->kpi,
                        'priority' => $item->priority,
                        'due_at' => $item->due_on?->endOfDay(),
                        'evidence_status' => $item->required_evidence_type ? 'Required' : 'Optional',
                        'updated_by' => $user->id,
                    ]
                );
            });

        WorkplanCorrectiveAction::with('item')
            ->where('tenant_id', $tenantId)
            ->where('owner_id', $user->id)
            ->whereIn('status', ['Open', 'In Progress'])
            ->get()
            ->each(function (WorkplanCorrectiveAction $action) use ($tenantId, $user, $today): void {
                PlanningDailyTask::updateOrCreate(
                    [
                        'tenant_id' => $tenantId,
                        'source_type' => WorkplanCorrectiveAction::class,
                        'source_id' => $action->id,
                        'employee_id' => $user->id,
                        'task_date' => $today,
                    ],
                    [
                        'workplan_item_id' => $action->workplan_item_id,
                        'corrective_action_id' => $action->id,
                        'source_module' => 'Planning and Performance',
                        'source_reference' => $action->item?->reference,
                        'title' => $action->title,
                        'description' => $action->root_cause,
                        'expected_output' => $action->recovery_plan,
                        'priority' => $action->severity === 'Critical' ? 'Critical' : 'High',
                        'due_at' => $action->due_on?->endOfDay(),
                        'evidence_status' => 'Required',
                        'updated_by' => $user->id,
                    ]
                );
            });
    }

    public function storeManualTask(int $tenantId, User $actor, array $data): PlanningDailyTask
    {
        return DB::transaction(function () use ($tenantId, $actor, $data): PlanningDailyTask {
            $item = ! empty($data['workplan_item_id'])
                ? WorkplanItem::where('tenant_id', $tenantId)->findOrFail($data['workplan_item_id'])
                : null;

            $task = PlanningDailyTask::create([
                'tenant_id' => $tenantId,
                'workplan_item_id' => $item?->id,
                'department_id' => $data['department_id'] ?? null,
                'position_id' => $data['position_id'] ?? null,
                'employee_id' => $data['employee_id'],
                'supervisor_id' => $data['supervisor_id'] ?? $actor->id,
                'source_module' => $data['source_module'] ?? 'Manual',
                'source_type' => 'manual',
                'source_reference' => $data['source_reference'] ?? null,
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'expected_output' => $data['expected_output'] ?? null,
                'priority' => $data['priority'],
                'task_date' => $data['task_date'],
                'due_at' => $data['due_at'] ?? null,
                'evidence_status' => $data['evidence_required'] ? 'Required' : 'Optional',
                'created_by' => $actor->id,
                'updated_by' => $actor->id,
            ]);

            app(DomainEventService::class)->record('daily_task.created', 'Planning and Performance', $task, [
                'title' => $task->title,
                'employee_id' => $task->employee_id,
            ], $tenantId, $actor);

            app(NotificationService::class)->notify($task->employee, $tenantId, 'New daily task assigned', $task->title, [
                'source_module' => 'Planning and Performance',
                'type' => 'daily_task',
                'severity' => 'Info',
                'action_url' => route('planning.daily.index'),
            ]);

            return $task;
        });
    }

    public function updateTask(PlanningDailyTask $task, User $actor, array $data): PlanningDailyTask
    {
        $status = $data['status'];
        $updates = [
            'status' => $status,
            'progress_percent' => $data['progress_percent'],
            'blocker_summary' => $status === 'Blocked' ? ($data['blocker_summary'] ?? $task->blocker_summary) : ($data['blocker_summary'] ?? null),
            'completion_notes' => $data['completion_notes'] ?? $task->completion_notes,
            'updated_by' => $actor->id,
        ];

        if ($status === 'Completed') {
            $updates['completed_at'] = now();
            $updates['progress_percent'] = 100;
        }

        $task->forceFill($updates)->save();

        app(DomainEventService::class)->record('daily_task.updated', 'Planning and Performance', $task, [
            'status' => $task->status,
            'progress_percent' => $task->progress_percent,
        ], (int) $task->tenant_id, $actor);

        return $task;
    }

    public function submitEvidence(PlanningDailyTask $task, User $actor, array $data): PlanningDailyTask
    {
        return DB::transaction(function () use ($task, $actor, $data): PlanningDailyTask {
            if (! $task->workplan_item_id) {
                throw ValidationException::withMessages([
                    'workplan_item_id' => 'Evidence can only be submitted for a daily task linked to a workplan target.',
                ]);
            }

            $evidence = $this->planning->submitEvidence($task->item, $actor, [
                'title' => $data['title'],
                'evidence_type' => $data['evidence_type'],
                'description' => $data['description'] ?? null,
                'source_module' => $data['source_module'] ?? ($task->source_module ?: 'Daily Workspace'),
                'source_reference' => $data['source_reference'] ?? ($task->source_reference ?: 'TASK-' . $task->id),
                'claimed_value' => $data['claimed_value'],
            ]);

            $task->forceFill([
                'workplan_evidence_id' => $evidence->id,
                'claimed_value' => $data['claimed_value'],
                'status' => 'Submitted',
                'evidence_status' => 'Submitted',
                'submitted_at' => now(),
                'completion_notes' => $data['description'] ?? $task->completion_notes,
                'updated_by' => $actor->id,
            ])->save();

            app(DomainEventService::class)->record('daily_task.evidence_submitted', 'Planning and Performance', $task, [
                'evidence_id' => $evidence->id,
                'claimed_value' => $data['claimed_value'],
            ], (int) $task->tenant_id, $actor);

            return $task;
        });
    }

    public function reviewTask(PlanningDailyTask $task, User $reviewer, array $data): PlanningDailyTask
    {
        return DB::transaction(function () use ($task, $reviewer, $data): PlanningDailyTask {
            $approved = $data['decision'] === 'Approved';

            if ($approved && $task->evidence && $task->evidence->status === 'Submitted' && $task->workplan_item_id) {
                $this->planning->reviewEvidence($task->evidence, $reviewer, 'Approved', (float) ($data['verified_value'] ?? $task->claimed_value), $data['notes'] ?? null);
            } elseif (! $approved && $task->evidence && $task->evidence->status === 'Submitted' && $task->workplan_item_id) {
                $this->planning->reviewEvidence($task->evidence, $reviewer, 'Rejected', 0, $data['notes'] ?? null);
            }

            $task->forceFill([
                'status' => $approved ? 'Verified' : 'Returned',
                'evidence_status' => $approved ? 'Verified' : 'Rejected',
                'review_decision' => $data['decision'],
                'review_notes' => $data['notes'] ?? null,
                'reviewed_by' => $reviewer->id,
                'reviewed_at' => now(),
                'completed_at' => $approved ? now() : $task->completed_at,
                'progress_percent' => $approved ? 100 : $task->progress_percent,
                'updated_by' => $reviewer->id,
            ])->save();

            app(DomainEventService::class)->record('daily_task.reviewed', 'Planning and Performance', $task, [
                'decision' => $data['decision'],
            ], (int) $task->tenant_id, $reviewer);

            return $task;
        });
    }

    public function canSupervise(User $user): bool
    {
        return $user->hasPermission('planning.workplans.manage') || $user->hasPermission('planning.approvals.manage');
    }

    private function metrics(Collection $myTasks, Collection $supervisorTasks): array
    {
        $closed = ['Completed', 'Verified', 'Cancelled'];

        return [
            'today' => $myTasks->where('task_date', now()->toDateString())->count(),
            'open' => $myTasks->whereNotIn('status', $closed)->count(),
            'blocked' => $myTasks->where('status', 'Blocked')->count(),
            'submitted' => $myTasks->where('status', 'Submitted')->count(),
            'verified' => $myTasks->where('status', 'Verified')->count(),
            'supervisor_queue' => $supervisorTasks->whereIn('status', ['Submitted', 'Blocked', 'Awaiting Review'])->count(),
        ];
    }

    private function scorecard(int $tenantId, User $user): array
    {
        $tasks = PlanningDailyTask::where('tenant_id', $tenantId)->where('employee_id', $user->id)->get();
        $total = max(1, $tasks->count());
        $verified = $tasks->where('status', 'Verified')->count();
        $completed = $tasks->whereIn('status', ['Completed', 'Verified'])->count();
        $blocked = $tasks->where('status', 'Blocked')->count();

        return [
            'execution' => (int) round(($completed / $total) * 100),
            'verified' => (int) round(($verified / $total) * 100),
            'blocked' => $blocked,
            'total' => $tasks->count(),
        ];
    }
}
