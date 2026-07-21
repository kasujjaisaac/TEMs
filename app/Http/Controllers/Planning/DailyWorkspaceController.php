<?php

namespace App\Http\Controllers\Planning;

use App\Models\HR\HrDepartment;
use App\Models\HR\HrPosition;
use App\Models\Planning\PlanningDailyTask;
use App\Models\Planning\WorkplanItem;
use App\Models\User;
use App\Services\Planning\DailyWorkspaceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DailyWorkspaceController extends PlanningController
{
    public function index(DailyWorkspaceService $daily): View
    {
        $this->authorizePlanning('planning.daily.view');

        $workspace = $daily->workspace($this->tenantId(), auth()->user());

        return view('planning.daily.index', [
            'page_title' => 'My Daily Workspace | Planning',
            ...$workspace,
            'canSupervise' => $daily->canSupervise(auth()->user()),
            'employees' => User::where('tenant_id', $this->tenantId())->where('is_active', true)->orderBy('name')->get(),
            'departments' => HrDepartment::where('tenant_id', $this->tenantId())->orderBy('name')->get(),
            'positions' => HrPosition::where('tenant_id', $this->tenantId())->orderBy('title')->get(),
            'workplanItems' => WorkplanItem::where('tenant_id', $this->tenantId())->orderBy('reference')->limit(80)->get(),
        ]);
    }

    public function store(Request $request, DailyWorkspaceService $daily): RedirectResponse
    {
        $this->authorizePlanning('planning.daily.assign');

        $data = $request->validate([
            'employee_id' => ['required', 'integer'],
            'supervisor_id' => ['nullable', 'integer'],
            'department_id' => ['nullable', 'integer'],
            'position_id' => ['nullable', 'integer'],
            'workplan_item_id' => ['nullable', 'integer'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'expected_output' => ['nullable', 'string', 'max:255'],
            'priority' => ['required', 'in:Low,Medium,High,Critical'],
            'task_date' => ['required', 'date'],
            'due_at' => ['nullable', 'date'],
            'source_module' => ['nullable', 'string', 'max:120'],
            'source_reference' => ['nullable', 'string', 'max:160'],
            'evidence_required' => ['nullable', 'boolean'],
        ]);

        User::where('tenant_id', $this->tenantId())->findOrFail($data['employee_id']);
        if (! empty($data['supervisor_id'])) {
            User::where('tenant_id', $this->tenantId())->findOrFail($data['supervisor_id']);
        }
        if (! empty($data['department_id'])) {
            HrDepartment::where('tenant_id', $this->tenantId())->findOrFail($data['department_id']);
        }
        if (! empty($data['position_id'])) {
            HrPosition::where('tenant_id', $this->tenantId())->findOrFail($data['position_id']);
        }

        $daily->storeManualTask($this->tenantId(), $request->user(), $data + ['evidence_required' => (bool) ($data['evidence_required'] ?? false)]);

        return redirect()->route('planning.daily.index')->with('success', 'Daily task assigned.');
    }

    public function update(Request $request, PlanningDailyTask $task, DailyWorkspaceService $daily): RedirectResponse
    {
        $this->authorizePlanning('planning.daily.update');
        $this->ensureTenant($task);
        abort_unless($task->employee_id === $request->user()->id || $daily->canSupervise($request->user()), 403);

        $data = $request->validate([
            'status' => ['required', 'in:Not Started,In Progress,Blocked,Awaiting Evidence,Completed,Cancelled'],
            'progress_percent' => ['required', 'integer', 'min:0', 'max:100'],
            'blocker_summary' => ['nullable', 'string', 'max:255'],
            'completion_notes' => ['nullable', 'string'],
        ]);

        $daily->updateTask($task, $request->user(), $data);

        return redirect()->route('planning.daily.index')->with('success', 'Daily task updated.');
    }

    public function submitEvidence(Request $request, PlanningDailyTask $task, DailyWorkspaceService $daily): RedirectResponse
    {
        $this->authorizePlanning('planning.daily.update');
        $this->ensureTenant($task);
        abort_unless($task->employee_id === $request->user()->id || $daily->canSupervise($request->user()), 403);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'evidence_type' => ['required', 'string', 'max:120'],
            'claimed_value' => ['required', 'numeric', 'min:0'],
            'source_module' => ['nullable', 'string', 'max:120'],
            'source_reference' => ['nullable', 'string', 'max:120'],
            'description' => ['nullable', 'string'],
        ]);

        $daily->submitEvidence($task, $request->user(), $data);

        return redirect()->route('planning.daily.index')->with('success', 'Task evidence submitted for supervisor verification.');
    }

    public function review(Request $request, PlanningDailyTask $task, DailyWorkspaceService $daily): RedirectResponse
    {
        $this->authorizePlanning('planning.daily.review');
        $this->ensureTenant($task);
        abort_unless($daily->canSupervise($request->user()), 403);

        $data = $request->validate([
            'decision' => ['required', 'in:Approved,Returned'],
            'verified_value' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
        ]);

        $daily->reviewTask($task, $request->user(), $data);

        return redirect()->route('planning.daily.index')->with('success', 'Daily task review recorded.');
    }
}
