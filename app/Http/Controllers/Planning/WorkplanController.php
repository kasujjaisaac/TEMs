<?php

namespace App\Http\Controllers\Planning;

use App\Models\Finance\FinanceBudgetLine;
use App\Models\HR\HrDepartment;
use App\Models\HR\HrPosition;
use App\Models\Planning\StrategicObjective;
use App\Models\Planning\Workplan;
use App\Models\Planning\WorkplanAssignment;
use App\Models\Planning\WorkplanItem;
use App\Models\User;
use App\Services\Planning\PlanningPerformanceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class WorkplanController extends PlanningController
{
    public function index(PlanningPerformanceService $planning): View
    {
        $this->authorizePlanning('planning.workplans.view');
        $planning->bootstrapTenant($this->tenantId());

        return view('planning.workplans.index', [
            'page_title' => 'Corporate Workplans | Texaro Technologies Limited',
            'workplans' => Workplan::withCount('items')->with(['planningYear', 'department', 'position', 'employee'])
                ->where('tenant_id', $this->tenantId())
                ->orderBy('level')
                ->orderBy('title')
                ->paginate(15),
        ]);
    }

    public function show(Workplan $workplan, PlanningPerformanceService $planning): View
    {
        $this->authorizePlanning('planning.workplans.view');
        $this->ensureTenant($workplan);

        $items = $workplan->items()
            ->with(['objective.pillar', 'assignments.department', 'assignments.position', 'assignments.employee', 'budgetLine'])
            ->orderBy('priority')
            ->orderBy('due_on')
            ->get();

        return view('planning.workplans.show', [
            'page_title' => $workplan->title . ' | Workplan',
            'workplan' => $workplan->load(['planningYear', 'department', 'position', 'employee']),
            'items' => $items,
            'snapshots' => $items->mapWithKeys(fn (WorkplanItem $item): array => [$item->id => $planning->itemSnapshot($item)]),
            'objectives' => StrategicObjective::where('tenant_id', $this->tenantId())->orderBy('code')->get(),
            'departments' => HrDepartment::where('tenant_id', $this->tenantId())->orderBy('name')->get(),
            'positions' => HrPosition::with('department')->where('tenant_id', $this->tenantId())->orderBy('title')->get(),
            'employees' => User::where('tenant_id', $this->tenantId())->orderBy('name')->get(),
            'budgetLines' => FinanceBudgetLine::where('tenant_id', $this->tenantId())->orderBy('reference')->get(),
        ]);
    }

    public function storeItem(Request $request, Workplan $workplan, PlanningPerformanceService $planning): RedirectResponse
    {
        $this->authorizePlanning('planning.workplans.manage');
        $this->ensureTenant($workplan);

        $data = $request->validate([
            'strategic_objective_id' => ['nullable', 'integer'],
            'budget_line_id' => ['nullable', 'integer'],
            'reference' => ['required', 'string', 'max:80'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'target_type' => ['required', 'string', 'max:40'],
            'kpi' => ['nullable', 'string', 'max:255'],
            'target_value' => ['required', 'numeric', 'min:0'],
            'actual_value' => ['nullable', 'numeric', 'min:0'],
            'unit' => ['nullable', 'string', 'max:80'],
            'priority' => ['required', 'string', 'max:40'],
            'weight' => ['required', 'integer', 'min:0', 'max:100'],
            'starts_on' => ['nullable', 'date'],
            'due_on' => ['nullable', 'date', 'after_or_equal:starts_on'],
            'required_evidence_type' => ['nullable', 'string', 'max:255'],
            'quality_standard' => ['nullable', 'string', 'max:255'],
            'department_id' => ['nullable', 'integer'],
            'position_id' => ['nullable', 'integer'],
            'employee_id' => ['nullable', 'integer'],
            'assignment_role' => ['required', 'string', 'max:40'],
        ]);

        foreach (['strategic_objective_id' => StrategicObjective::class, 'budget_line_id' => FinanceBudgetLine::class, 'department_id' => HrDepartment::class, 'position_id' => HrPosition::class, 'employee_id' => User::class] as $field => $model) {
            if (! empty($data[$field])) {
                $model::where('tenant_id', $this->tenantId())->findOrFail($data[$field]);
            }
        }

        $item = WorkplanItem::updateOrCreate(
            ['tenant_id' => $this->tenantId(), 'reference' => $data['reference']],
            [
                ...collect($data)->except(['department_id', 'position_id', 'employee_id', 'assignment_role'])->all(),
                'workplan_id' => $workplan->id,
                'approval_status' => 'Draft',
                'created_by' => Auth::id(),
                'updated_by' => Auth::id(),
            ]
        );

        WorkplanAssignment::updateOrCreate(
            ['tenant_id' => $this->tenantId(), 'workplan_item_id' => $item->id, 'assignment_role' => $data['assignment_role']],
            [
                'department_id' => $data['department_id'] ?? $workplan->department_id,
                'position_id' => $data['position_id'] ?? null,
                'employee_id' => $data['employee_id'] ?? null,
                'contribution_weight' => 100,
                'status' => 'Active',
            ]
        );

        $planning->createAllocations($item);

        return redirect()->route('planning.workplans.show', $workplan)->with('success', 'Workplan target saved and allocations generated.');
    }

    public function approve(Workplan $workplan): RedirectResponse
    {
        $this->authorizePlanning('planning.approvals.manage');
        $this->ensureTenant($workplan);

        $workplan->forceFill([
            'approval_status' => 'Approved',
            'approved_by' => Auth::id(),
            'approved_at' => now(),
            'health_status' => 'On Track',
        ])->save();

        $workplan->items()->update(['approval_status' => 'Approved']);

        return redirect()->route('planning.workplans.show', $workplan)->with('success', 'Workplan approved and baseline locked for Phase 1 tracking.');
    }
}
