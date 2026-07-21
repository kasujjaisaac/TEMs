<?php

namespace App\Services\Planning;

use App\Models\AuditLog;
use App\Models\Finance\FinanceBudgetLine;
use App\Models\HR\HrDepartment;
use App\Models\HR\HrPosition;
use App\Models\Planning\WorkplanCorrectiveAction;
use App\Models\Planning\WorkplanEvidence;
use App\Models\Planning\WorkplanEvidenceReview;
use App\Models\Planning\PlanningYear;
use App\Models\Planning\StrategicObjective;
use App\Models\Planning\StrategicPillar;
use App\Models\Planning\TargetAllocation;
use App\Models\Planning\Workplan;
use App\Models\Planning\WorkplanAssignment;
use App\Models\Planning\WorkplanItem;
use App\Models\Planning\PlanningDailyTask;
use App\Models\Planning\PlanningWorkplanImport;
use App\Models\User;
use App\Services\Enterprise\DomainEventService;
use App\Services\Enterprise\NotificationService;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class PlanningPerformanceService
{
    public const WORKPLAN_IMPORT_HEADERS = [
        'workplan_code', 'workplan_title', 'workplan_level', 'workplan_description',
        'objective_code', 'target_reference', 'target_title', 'target_type', 'kpi',
        'target_value', 'actual_value', 'unit', 'priority', 'weight', 'starts_on',
        'due_on', 'department_code', 'position_code', 'employee_email',
        'assignment_role', 'required_evidence_type', 'quality_standard', 'description',
    ];

    public function bootstrapTenant(int $tenantId): PlanningYear
    {
        $year = $this->currentPlanningYear($tenantId);
        $this->ensureStrategy($tenantId, $year);
        $this->ensureWorkplans($tenantId, $year);

        return $year;
    }

    public function currentPlanningYear(int $tenantId): PlanningYear
    {
        $now = now();
        $startYear = $now->month >= 7 ? $now->year : $now->year - 1;
        $name = $startYear . '/' . ($startYear + 1);

        PlanningYear::where('tenant_id', $tenantId)->where('is_current', true)->update(['is_current' => false]);

        return PlanningYear::updateOrCreate(
            ['tenant_id' => $tenantId, 'name' => $name],
            [
                'starts_on' => Carbon::create($startYear, 7, 1)->toDateString(),
                'ends_on' => Carbon::create($startYear + 1, 6, 30)->toDateString(),
                'annual_theme' => 'Disciplined growth through verified execution',
                'status' => 'Open',
                'is_current' => true,
                'scoring_rules' => [
                    'achievement' => 40,
                    'timeliness' => 20,
                    'quality_evidence' => 15,
                    'strategic_importance' => 10,
                    'budget_discipline' => 5,
                    'collaboration_reporting' => 10,
                ],
            ]
        );
    }

    public function dashboard(int $tenantId): array
    {
        $year = $this->bootstrapTenant($tenantId);
        $items = WorkplanItem::with(['workplan.department', 'objective.pillar', 'assignments.department', 'assignments.position', 'budgetLine'])
            ->where('tenant_id', $tenantId)
            ->latest('updated_at')
            ->get();

        $snapshots = $items->map(fn (WorkplanItem $item): array => $this->itemSnapshot($item));
        $healthCounts = $snapshots->countBy('health');
        $weightedTarget = max(1, $items->sum(fn (WorkplanItem $item): int => max(1, (int) $item->weight)));
        $weightedAchievement = $items->sum(fn (WorkplanItem $item): float => min(100, $item->achievement_percentage) * max(1, (int) $item->weight));

        return [
            'planningYear' => $year,
            'metrics' => [
                'strategic_pillars' => StrategicPillar::where('tenant_id', $tenantId)->count(),
                'strategic_objectives' => StrategicObjective::where('tenant_id', $tenantId)->count(),
                'workplans' => Workplan::where('tenant_id', $tenantId)->count(),
                'targets' => $items->count(),
                'monthly_allocations' => TargetAllocation::where('tenant_id', $tenantId)->where('period_type', 'Monthly')->count(),
                'weekly_allocations' => TargetAllocation::where('tenant_id', $tenantId)->where('period_type', 'Weekly')->count(),
                'company_achievement' => $items->isEmpty() ? 0 : (int) round($weightedAchievement / $weightedTarget),
                'at_risk_or_behind' => ($healthCounts['At Risk'] ?? 0) + ($healthCounts['Behind'] ?? 0),
                'evidence_submitted' => WorkplanEvidence::where('tenant_id', $tenantId)->count(),
                'evidence_awaiting_review' => WorkplanEvidence::where('tenant_id', $tenantId)->where('status', 'Submitted')->count(),
                'open_corrective_actions' => WorkplanCorrectiveAction::where('tenant_id', $tenantId)->whereIn('status', ['Open', 'In Progress'])->count(),
                'daily_tasks_today' => PlanningDailyTask::where('tenant_id', $tenantId)->whereDate('task_date', now()->toDateString())->count(),
                'daily_tasks_blocked' => PlanningDailyTask::where('tenant_id', $tenantId)->where('status', 'Blocked')->count(),
                'daily_tasks_submitted' => PlanningDailyTask::where('tenant_id', $tenantId)->where('status', 'Submitted')->count(),
            ],
            'healthCounts' => $healthCounts,
            'recentItems' => $snapshots->take(10),
            'workplans' => Workplan::withCount('items')->with(['department', 'planningYear'])
                ->where('tenant_id', $tenantId)
                ->orderBy('level')
                ->orderBy('title')
                ->limit(8)
                ->get(),
            'objectives' => StrategicObjective::with('pillar')
                ->where('tenant_id', $tenantId)
                ->orderBy('code')
                ->limit(8)
                ->get(),
            'alerts' => $this->alerts($snapshots),
        ];
    }

    public function itemSnapshot(WorkplanItem $item): array
    {
        $timeElapsed = $this->timeElapsedPercentage($item->starts_on, $item->due_on);
        $achievement = $item->achievement_percentage;
        $expected = round(((float) $item->target_value * $timeElapsed) / 100, 2);
        $gap = round((float) $item->actual_value - $expected, 2);
        $pace = $timeElapsed > 0 ? (int) round(($achievement / $timeElapsed) * 100) : ($achievement > 0 ? 100 : 0);
        $forecast = $timeElapsed > 0 ? round(((float) $item->actual_value / $timeElapsed) * 100, 2) : (float) $item->actual_value;
        $health = $this->healthStatus($item, $pace, $achievement);

        return [
            'item' => $item,
            'achievement' => $achievement,
            'time_elapsed' => $timeElapsed,
            'expected' => $expected,
            'gap' => $gap,
            'pace' => $pace,
            'forecast' => $forecast,
            'health' => $health,
        ];
    }

    public function createAllocations(WorkplanItem $item): void
    {
        if (! $item->starts_on || ! $item->due_on || (float) $item->target_value <= 0) {
            return;
        }

        $months = collect(CarbonPeriod::create($item->starts_on->copy()->startOfMonth(), '1 month', $item->due_on->copy()->startOfMonth()));
        $monthlyTarget = $months->isEmpty() ? (float) $item->target_value : round((float) $item->target_value / $months->count(), 2);
        $months->each(function (Carbon $month) use ($item, $monthlyTarget): void {
            TargetAllocation::updateOrCreate(
                ['tenant_id' => $item->tenant_id, 'workplan_item_id' => $item->id, 'period_type' => 'Monthly', 'period_start' => $month->copy()->startOfMonth()->toDateString()],
                ['period_end' => $month->copy()->endOfMonth()->toDateString(), 'target_value' => $monthlyTarget, 'status' => 'Planned']
            );
        });

        $weeks = collect(CarbonPeriod::create($item->starts_on->copy()->startOfWeek(), '1 week', $item->due_on->copy()->startOfWeek()));
        $weeklyTarget = $weeks->isEmpty() ? (float) $item->target_value : round((float) $item->target_value / $weeks->count(), 2);
        $weeks->each(function (Carbon $week) use ($item, $weeklyTarget): void {
            TargetAllocation::updateOrCreate(
                ['tenant_id' => $item->tenant_id, 'workplan_item_id' => $item->id, 'period_type' => 'Weekly', 'period_start' => $week->copy()->startOfWeek()->toDateString()],
                ['period_end' => $week->copy()->endOfWeek()->toDateString(), 'target_value' => $weeklyTarget, 'status' => 'Planned']
            );
        });
    }

    public function importWorkplanCsv(int $tenantId, User $user, UploadedFile $file): PlanningWorkplanImport
    {
        $year = $this->bootstrapTenant($tenantId);
        [$rows, $errors] = $this->parseWorkplanCsv($file);

        if ($errors !== []) {
            PlanningWorkplanImport::create([
                'tenant_id' => $tenantId,
                'uploaded_by' => $user->id,
                'planning_year_id' => $year->id,
                'original_filename' => $file->getClientOriginalName(),
                'status' => 'Failed',
                'rows_read' => count($rows),
                'errors' => $errors,
                'imported_at' => now(),
            ]);

            throw ValidationException::withMessages(['workplan_file' => implode(' ', array_slice($errors, 0, 3))]);
        }

        return DB::transaction(function () use ($tenantId, $user, $file, $year, $rows): PlanningWorkplanImport {
            $createdWorkplans = [];
            $importedTargets = 0;

            foreach ($rows as $index => $row) {
                $line = $index + 2;
                $department = $this->departmentByCode($tenantId, $row['department_code'] ?? null);
                $position = $this->positionByCode($tenantId, $row['position_code'] ?? null);
                $employee = $this->employeeByEmail($tenantId, $row['employee_email'] ?? null);
                $objective = $this->objectiveByCode($tenantId, $row['objective_code'] ?? null);

                $missing = [];
                if (! empty($row['department_code']) && ! $department) {
                    $missing[] = 'department_code';
                }
                if (! empty($row['position_code']) && ! $position) {
                    $missing[] = 'position_code';
                }
                if (! empty($row['employee_email']) && ! $employee) {
                    $missing[] = 'employee_email';
                }
                if (! empty($row['objective_code']) && ! $objective) {
                    $missing[] = 'objective_code';
                }
                if ($missing !== []) {
                    throw ValidationException::withMessages(['workplan_file' => 'Row ' . $line . ' references unknown ' . implode(', ', $missing) . '.']);
                }

                $workplanCode = strtoupper(trim((string) $row['workplan_code']));
                $existing = Workplan::where('tenant_id', $tenantId)->where('code', $workplanCode)->first();
                $workplan = Workplan::updateOrCreate(
                    ['tenant_id' => $tenantId, 'code' => $workplanCode],
                    [
                        'planning_year_id' => $year->id,
                        'department_id' => $department?->id,
                        'position_id' => $position?->id,
                        'employee_id' => $employee?->id,
                        'title' => $row['workplan_title'],
                        'level' => $row['workplan_level'] ?: ($employee ? 'Individual' : ($position ? 'Position' : ($department ? 'Department' : 'Corporate'))),
                        'description' => $row['workplan_description'] ?: null,
                        'owner_name' => $department?->name ?: $position?->title ?: $employee?->name ?: null,
                        'approval_status' => 'Draft',
                        'health_status' => 'Not Started',
                    ]
                );

                if (! $existing) {
                    $createdWorkplans[$workplan->id] = true;
                }

                $item = WorkplanItem::updateOrCreate(
                    ['tenant_id' => $tenantId, 'reference' => strtoupper(trim((string) $row['target_reference']))],
                    [
                        'workplan_id' => $workplan->id,
                        'strategic_objective_id' => $objective?->id,
                        'title' => $row['target_title'],
                        'description' => $row['description'] ?: null,
                        'target_type' => $row['target_type'] ?: 'Numeric',
                        'kpi' => $row['kpi'] ?: $row['target_title'],
                        'target_value' => (float) $row['target_value'],
                        'actual_value' => (float) ($row['actual_value'] ?: 0),
                        'unit' => $row['unit'] ?: null,
                        'priority' => $row['priority'] ?: 'Medium',
                        'weight' => (int) ($row['weight'] ?: 10),
                        'starts_on' => $row['starts_on'] ?: $year->starts_on,
                        'due_on' => $row['due_on'] ?: $year->ends_on,
                        'required_evidence_type' => $row['required_evidence_type'] ?: null,
                        'quality_standard' => $row['quality_standard'] ?: null,
                        'approval_status' => 'Draft',
                        'health_status' => 'Not Started',
                        'created_by' => $user->id,
                        'updated_by' => $user->id,
                    ]
                );

                WorkplanAssignment::updateOrCreate(
                    ['tenant_id' => $tenantId, 'workplan_item_id' => $item->id, 'assignment_role' => $row['assignment_role'] ?: 'Accountable'],
                    [
                        'department_id' => $department?->id ?? $workplan->department_id,
                        'position_id' => $position?->id,
                        'employee_id' => $employee?->id,
                        'supervisor_id' => null,
                        'contribution_weight' => 100,
                        'status' => 'Active',
                    ]
                );

                $this->createAllocations($item);
                $importedTargets++;
            }

            $import = PlanningWorkplanImport::create([
                'tenant_id' => $tenantId,
                'uploaded_by' => $user->id,
                'planning_year_id' => $year->id,
                'original_filename' => $file->getClientOriginalName(),
                'status' => 'Imported',
                'rows_read' => count($rows),
                'workplans_created' => count($createdWorkplans),
                'targets_imported' => $importedTargets,
                'metadata' => ['template_headers' => self::WORKPLAN_IMPORT_HEADERS],
                'imported_at' => now(),
            ]);

            app(DomainEventService::class)->record('workplan.imported', 'Planning and Performance', $import, [
                'rows_read' => count($rows),
                'workplans_created' => count($createdWorkplans),
                'targets_imported' => $importedTargets,
            ], $tenantId, $user);

            return $import;
        });
    }

    private function parseWorkplanCsv(UploadedFile $file): array
    {
        $handle = fopen($file->getRealPath(), 'rb');
        if (! $handle) {
            return [[], ['Unable to read the uploaded workplan file.']];
        }

        $headers = fgetcsv($handle);
        if ($headers === false) {
            fclose($handle);
            return [[], ['The uploaded workplan file is empty.']];
        }

        $headers = array_map(fn ($header): string => strtolower(trim((string) $header)), $headers);
        $missing = array_diff(['workplan_code', 'workplan_title', 'target_reference', 'target_title', 'target_value'], $headers);
        if ($missing !== []) {
            fclose($handle);
            return [[], ['Missing required columns: ' . implode(', ', $missing) . '.']];
        }

        $rows = [];
        $errors = [];
        $line = 1;
        while (($data = fgetcsv($handle)) !== false) {
            $line++;
            if (count(array_filter($data, fn ($value): bool => trim((string) $value) !== '')) === 0) {
                continue;
            }

            $row = array_fill_keys(self::WORKPLAN_IMPORT_HEADERS, '');
            foreach ($headers as $index => $header) {
                if (array_key_exists($header, $row)) {
                    $row[$header] = trim((string) ($data[$index] ?? ''));
                }
            }

            foreach (['workplan_code', 'workplan_title', 'target_reference', 'target_title', 'target_value'] as $required) {
                if ($row[$required] === '') {
                    $errors[] = 'Row ' . $line . ' is missing ' . $required . '.';
                }
            }
            if ($row['target_value'] !== '' && ! is_numeric($row['target_value'])) {
                $errors[] = 'Row ' . $line . ' target_value must be numeric.';
            }
            if ($row['actual_value'] !== '' && ! is_numeric($row['actual_value'])) {
                $errors[] = 'Row ' . $line . ' actual_value must be numeric.';
            }
            if ($row['weight'] !== '' && (! is_numeric($row['weight']) || (int) $row['weight'] < 0 || (int) $row['weight'] > 100)) {
                $errors[] = 'Row ' . $line . ' weight must be between 0 and 100.';
            }
            foreach (['starts_on', 'due_on'] as $dateField) {
                if ($row[$dateField] !== '' && ! strtotime($row[$dateField])) {
                    $errors[] = 'Row ' . $line . ' ' . $dateField . ' must be a valid date.';
                }
            }

            $rows[] = $row;
        }
        fclose($handle);

        if ($rows === []) {
            $errors[] = 'The uploaded workplan file has no target rows.';
        }

        return [$rows, $errors];
    }

    private function departmentByCode(int $tenantId, ?string $code): ?HrDepartment
    {
        return $code ? HrDepartment::where('tenant_id', $tenantId)->where('code', strtoupper(trim($code)))->first() : null;
    }

    private function positionByCode(int $tenantId, ?string $code): ?HrPosition
    {
        return $code ? HrPosition::where('tenant_id', $tenantId)->where('code', strtoupper(trim($code)))->first() : null;
    }

    private function employeeByEmail(int $tenantId, ?string $email): ?User
    {
        return $email ? User::where('tenant_id', $tenantId)->whereRaw('LOWER(email) = ?', [strtolower(trim($email))])->first() : null;
    }

    private function objectiveByCode(int $tenantId, ?string $code): ?StrategicObjective
    {
        return $code ? StrategicObjective::where('tenant_id', $tenantId)->where('code', strtoupper(trim($code)))->first() : null;
    }

    public function submitEvidence(WorkplanItem $item, User $submitter, array $data, ?Request $request = null): WorkplanEvidence
    {
        return DB::transaction(function () use ($item, $submitter, $data, $request): WorkplanEvidence {
            $evidence = WorkplanEvidence::create([
                'tenant_id' => $item->tenant_id,
                'workplan_item_id' => $item->id,
                'submitted_by' => $submitter->id,
                'title' => $data['title'],
                'evidence_type' => $data['evidence_type'],
                'description' => $data['description'] ?? null,
                'source_module' => $data['source_module'] ?? null,
                'source_reference' => $data['source_reference'] ?? null,
                'claimed_value' => $data['claimed_value'] ?? 0,
                'status' => 'Submitted',
                'submitted_at' => now(),
                'metadata' => [
                    'required_evidence_type' => $item->required_evidence_type,
                    'quality_standard' => $item->quality_standard,
                ],
            ]);

            app(DomainEventService::class)->record('evidence.submitted', 'Planning and Performance', $evidence, [
                'workplan_item_id' => $item->id,
                'reference' => $item->reference,
                'claimed_value' => $evidence->claimed_value,
            ], (int) $item->tenant_id, $submitter);

            app(NotificationService::class)->notify(
                null,
                (int) $item->tenant_id,
                'Evidence awaiting verification',
                $item->reference . ' has new evidence submitted for review.',
                ['source_module' => 'Planning and Performance', 'type' => 'evidence', 'severity' => 'Info', 'action_url' => route('planning.workplans.show', $item->workplan_id)]
            );

            $this->audit($item, $submitter, 'submitted', 'Submitted evidence ' . $evidence->title, $request, ['evidence_id' => $evidence->id]);

            return $evidence;
        });
    }

    public function reviewEvidence(WorkplanEvidence $evidence, User $reviewer, string $decision, float $verifiedValue, ?string $notes = null, ?Request $request = null): WorkplanEvidence
    {
        return DB::transaction(function () use ($evidence, $reviewer, $decision, $verifiedValue, $notes, $request): WorkplanEvidence {
            $status = $decision === 'Approved' ? 'Verified' : 'Rejected';
            $verifiedValue = $status === 'Verified' ? $verifiedValue : 0;

            $evidence->forceFill([
                'status' => $status,
                'verified_value' => $verifiedValue,
                'reviewed_by' => $reviewer->id,
                'reviewed_at' => now(),
                'review_notes' => $notes,
            ])->save();

            WorkplanEvidenceReview::create([
                'tenant_id' => $evidence->tenant_id,
                'workplan_evidence_id' => $evidence->id,
                'reviewed_by' => $reviewer->id,
                'decision' => $decision,
                'verified_value' => $verifiedValue,
                'notes' => $notes,
            ]);

            $item = $evidence->item()->lockForUpdate()->firstOrFail();
            if ($status === 'Verified') {
                $verifiedTotal = WorkplanEvidence::where('tenant_id', $item->tenant_id)
                    ->where('workplan_item_id', $item->id)
                    ->where('status', 'Verified')
                    ->sum('verified_value');

                $item->forceFill([
                    'actual_value' => $verifiedTotal,
                    'health_status' => $this->itemSnapshot($item->forceFill(['actual_value' => $verifiedTotal]))['health'],
                    'updated_by' => $reviewer->id,
                ])->save();
            }

            app(DomainEventService::class)->record($status === 'Verified' ? 'evidence.verified' : 'evidence.rejected', 'Planning and Performance', $evidence, [
                'workplan_item_id' => $item->id,
                'reference' => $item->reference,
                'verified_value' => $verifiedValue,
            ], (int) $item->tenant_id, $reviewer);

            app(NotificationService::class)->notify(
                $evidence->submitter,
                (int) $evidence->tenant_id,
                'Evidence ' . strtolower($status),
                $evidence->title . ' was ' . strtolower($status) . '.',
                ['source_module' => 'Planning and Performance', 'type' => 'evidence', 'severity' => $status === 'Verified' ? 'Success' : 'Warning', 'action_url' => route('planning.workplans.show', $item->workplan_id)]
            );

            $this->audit($item, $reviewer, strtolower($status), $status . ' evidence ' . $evidence->title, $request, ['evidence_id' => $evidence->id, 'verified_value' => $verifiedValue]);

            return $evidence->fresh(['item', 'submitter']);
        });
    }

    public function createCorrectiveAction(WorkplanItem $item, User $creator, array $data, ?Request $request = null): WorkplanCorrectiveAction
    {
        return DB::transaction(function () use ($item, $creator, $data, $request): WorkplanCorrectiveAction {
            $action = WorkplanCorrectiveAction::create([
                'tenant_id' => $item->tenant_id,
                'workplan_item_id' => $item->id,
                'owner_id' => $data['owner_id'] ?? null,
                'created_by' => $creator->id,
                'title' => $data['title'],
                'root_cause' => $data['root_cause'] ?? null,
                'recovery_plan' => $data['recovery_plan'],
                'due_on' => $data['due_on'] ?? null,
                'status' => $data['status'] ?? 'Open',
                'severity' => $data['severity'] ?? 'Medium',
            ]);

            $item->forceFill([
                'risk_summary' => $data['root_cause'] ?? $item->risk_summary,
                'health_status' => 'Recovery',
                'updated_by' => $creator->id,
            ])->save();

            app(DomainEventService::class)->record('corrective_action.created', 'Planning and Performance', $action, [
                'workplan_item_id' => $item->id,
                'reference' => $item->reference,
                'severity' => $action->severity,
            ], (int) $item->tenant_id, $creator);

            app(NotificationService::class)->notify(
                $action->owner,
                (int) $item->tenant_id,
                'Corrective action assigned',
                $item->reference . ' has a recovery action: ' . $action->title,
                ['source_module' => 'Planning and Performance', 'type' => 'recovery', 'severity' => $action->severity, 'action_url' => route('planning.workplans.show', $item->workplan_id)]
            );

            $this->audit($item, $creator, 'created', 'Created corrective action ' . $action->title, $request, ['corrective_action_id' => $action->id]);

            return $action;
        });
    }

    private function ensureStrategy(int $tenantId, PlanningYear $year): void
    {
        $pillars = [
            ['GROWTH', 'Commercial Growth', 'Acquire, convert, and retain profitable customers.', 30],
            ['DELIVERY', 'Product & Delivery Excellence', 'Deliver reliable products, implementations, support, and uptime.', 25],
            ['FINCTRL', 'Financial Control', 'Protect cash, budget discipline, profitability, and audit readiness.', 20],
            ['PEOPLE', 'People & Organization', 'Build accountable roles, capacity, culture, and performance.', 15],
            ['GOV', 'Governance & Compliance', 'Maintain legal, security, policy, and board discipline.', 10],
        ];

        $pillarModels = [];
        foreach ($pillars as [$code, $name, $description, $weight]) {
            $pillarModels[$code] = StrategicPillar::updateOrCreate(
                ['tenant_id' => $tenantId, 'code' => $code],
                ['planning_year_id' => $year->id, 'name' => $name, 'description' => $description, 'weight' => $weight, 'status' => 'Active']
            );
        }

        foreach ([
            ['OBJ-001', 'GROWTH', 'Acquire active customers', 'Acquire and activate paying customers from Commercial and Sales.', 'Active clients', 85, 'clients', 30],
            ['OBJ-002', 'GROWTH', 'Convert pipeline into revenue', 'Move qualified opportunities into quotations, invoices and payments.', 'Revenue achieved', 250000000, 'UGX', 25],
            ['OBJ-003', 'DELIVERY', 'Deliver reliable implementations', 'Complete product, project and support milestones with verified acceptance.', 'Verified milestones', 40, 'milestones', 20],
            ['OBJ-004', 'FINCTRL', 'Maintain budget and cash discipline', 'Keep budgets, receivables, payables and evidence under management control.', 'Budget compliant lines', 90, '%', 15],
            ['OBJ-005', 'PEOPLE', 'Strengthen accountable execution', 'Ensure positions, workplans, evidence and reviews guide every employee.', 'Roles with workplans', 100, '%', 10],
        ] as [$code, $pillar, $title, $description, $kpi, $target, $unit, $weight]) {
            StrategicObjective::updateOrCreate(
                ['tenant_id' => $tenantId, 'code' => $code],
                [
                    'planning_year_id' => $year->id,
                    'strategic_pillar_id' => $pillarModels[$pillar]->id,
                    'title' => $title,
                    'description' => $description,
                    'kpi' => $kpi,
                    'target_value' => $target,
                    'unit' => $unit,
                    'weight' => $weight,
                    'status' => 'Approved',
                ]
            );
        }
    }

    private function ensureWorkplans(int $tenantId, PlanningYear $year): void
    {
        Workplan::updateOrCreate(
            ['tenant_id' => $tenantId, 'code' => 'CORP-' . str_replace('/', '-', $year->name)],
            [
                'planning_year_id' => $year->id,
                'title' => 'Corporate Workplan ' . $year->name,
                'level' => 'Corporate',
                'description' => 'Company strategy translated into annual, monthly and weekly execution targets.',
                'owner_name' => 'Managing Director',
                'approval_status' => 'Approved',
                'approved_at' => now(),
                'health_status' => 'On Track',
            ]
        );

        HrDepartment::where('tenant_id', $tenantId)->where('status', 'Active')->get()->each(function (HrDepartment $department) use ($tenantId, $year): void {
            Workplan::updateOrCreate(
                ['tenant_id' => $tenantId, 'code' => 'DEPT-' . $department->code . '-' . str_replace('/', '-', $year->name)],
                [
                    'planning_year_id' => $year->id,
                    'department_id' => $department->id,
                    'title' => $department->name . ' Workplan',
                    'level' => 'Department',
                    'description' => $department->mandate,
                    'owner_name' => $department->headPosition?->title ?: $department->short_name,
                    'approval_status' => 'Draft',
                    'health_status' => 'Not Started',
                ]
            );
        });
    }

    private function timeElapsedPercentage(?Carbon $start, ?Carbon $due): int
    {
        if (! $start || ! $due) {
            return 0;
        }

        $today = now()->startOfDay();
        if ($today->lessThanOrEqualTo($start)) {
            return 0;
        }

        $total = max(1, $start->diffInDays($due) + 1);
        $elapsed = min($total, $start->diffInDays($today) + 1);

        return (int) min(100, round(($elapsed / $total) * 100));
    }

    private function healthStatus(WorkplanItem $item, int $pace, int $achievement): string
    {
        if ($achievement >= 100 && $item->approval_status === 'Approved') {
            return 'Completed';
        }
        if ($item->due_on && now()->startOfDay()->greaterThan($item->due_on) && $achievement < 100) {
            return 'Missed';
        }
        if ($pace >= 110) {
            return 'Ahead';
        }
        if ($pace >= 90) {
            return 'On Track';
        }
        if ($pace >= 70) {
            return 'At Risk';
        }

        return $achievement > 0 ? 'Behind' : 'Not Started';
    }

    private function alerts(Collection $snapshots): array
    {
        $alerts = [];
        $behind = $snapshots->whereIn('health', ['Behind', 'Missed'])->count();
        $risk = $snapshots->where('health', 'At Risk')->count();
        if ($behind > 0) {
            $alerts[] = ['severity' => 'High', 'title' => 'Behind targets', 'message' => $behind . ' workplan targets require recovery or escalation.'];
        }
        if ($risk > 0) {
            $alerts[] = ['severity' => 'Medium', 'title' => 'At-risk targets', 'message' => $risk . ' targets are below expected pace and need supervisor attention.'];
        }
        if ($snapshots->filter(fn (array $row): bool => $row['item']->assignments->isEmpty())->count() > 0) {
            $alerts[] = ['severity' => 'Medium', 'title' => 'Unassigned work', 'message' => 'Some approved targets do not yet have accountable departments, positions or employees.'];
        }
        if ($snapshots->isEmpty()) {
            $alerts[] = ['severity' => 'Medium', 'title' => 'Planning baseline required', 'message' => 'Create workplan targets and allocations to activate performance intelligence.'];
        }

        return $alerts;
    }

    private function audit(WorkplanItem $item, User $user, string $action, string $description, ?Request $request = null, array $metadata = []): void
    {
        AuditLog::create([
            'tenant_id' => $item->tenant_id,
            'user_id' => $user->id,
            'action' => $action,
            'module' => 'planning',
            'subject_type' => WorkplanItem::class,
            'subject_id' => $item->id,
            'description' => $description,
            'metadata' => $metadata,
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
        ]);
    }
}
