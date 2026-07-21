<?php

namespace App\Http\Controllers\Planning;

use App\Models\Finance\FinanceBudgetLine;
use App\Models\HR\HrDepartment;
use App\Models\HR\HrPosition;
use App\Models\Planning\StrategicObjective;
use App\Models\Planning\Workplan;
use App\Models\Planning\WorkplanAssignment;
use App\Models\Planning\WorkplanEvidence;
use App\Models\Planning\WorkplanItem;
use App\Models\Planning\PlanningWorkplanImport;
use App\Models\User;
use App\Services\Planning\AnnualWorkplanSpreadsheetService;
use App\Services\Planning\PlanningPerformanceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
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
            'imports' => PlanningWorkplanImport::with('uploader')
                ->where('tenant_id', $this->tenantId())
                ->latest()
                ->limit(6)
                ->get(),
        ]);
    }

    public function template(AnnualWorkplanSpreadsheetService $spreadsheet): StreamedResponse
    {
        $this->authorizePlanning('planning.workplans.manage');

        return response()->streamDownload(function () use ($spreadsheet): void {
            echo $spreadsheet->templateBinary();
        }, 'tems_annual_workplan_template_2026_2027.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public function import(Request $request, PlanningPerformanceService $planning): RedirectResponse
    {
        $this->authorizePlanning('planning.workplans.manage');

        $data = $request->validate([
            'workplan_file' => ['required', 'file', 'mimes:csv,txt,xlsx', 'max:8192'],
        ]);

        $import = $planning->importWorkplanFile($this->tenantId(), $request->user(), $data['workplan_file']);

        return redirect()->route('planning.workplans.index')->with('success', 'Workplan upload imported: ' . $import->targets_imported . ' target(s), ' . $import->workplans_created . ' new workplan(s).');
    }

    public function show(Workplan $workplan, PlanningPerformanceService $planning): View
    {
        $this->authorizePlanning('planning.workplans.view');
        $this->ensureTenant($workplan);

        $items = $workplan->items()
            ->with([
                'objective.pillar', 'assignments.department', 'assignments.position', 'assignments.employee',
                'budgetLine', 'evidence.submitter', 'evidence.reviewer', 'correctiveActions.owner',
            ])
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

    public function submitEvidence(Request $request, WorkplanItem $item, PlanningPerformanceService $planning): RedirectResponse
    {
        $this->authorizePlanning('planning.workplans.manage');
        $this->ensureTenant($item);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'evidence_type' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string'],
            'source_module' => ['nullable', 'string', 'max:120'],
            'source_reference' => ['nullable', 'string', 'max:120'],
            'claimed_value' => ['required', 'numeric', 'min:0'],
        ]);

        $planning->submitEvidence($item, $request->user(), $data, $request);

        return redirect()->route('planning.workplans.show', $item->workplan_id)->with('success', 'Evidence submitted for verification.');
    }

    public function reviewEvidence(Request $request, WorkplanEvidence $evidence, PlanningPerformanceService $planning): RedirectResponse
    {
        $this->authorizePlanning('planning.approvals.manage');
        $this->ensureTenant($evidence);

        $data = $request->validate([
            'decision' => ['required', 'in:Approved,Rejected'],
            'verified_value' => ['required_if:decision,Approved', 'nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
        ]);

        $planning->reviewEvidence($evidence, $request->user(), $data['decision'], (float) ($data['verified_value'] ?? 0), $data['notes'] ?? null, $request);

        return redirect()->route('planning.workplans.show', $evidence->item->workplan_id)->with('success', 'Evidence review recorded and target progress recalculated.');
    }

    public function storeCorrectiveAction(Request $request, WorkplanItem $item, PlanningPerformanceService $planning): RedirectResponse
    {
        $this->authorizePlanning('planning.workplans.manage');
        $this->ensureTenant($item);

        $data = $request->validate([
            'owner_id' => ['nullable', 'integer'],
            'title' => ['required', 'string', 'max:255'],
            'root_cause' => ['nullable', 'string'],
            'recovery_plan' => ['required', 'string'],
            'due_on' => ['nullable', 'date'],
            'severity' => ['required', 'in:Low,Medium,High,Critical'],
            'status' => ['required', 'in:Open,In Progress,Completed,Cancelled'],
        ]);

        if (! empty($data['owner_id'])) {
            User::where('tenant_id', $this->tenantId())->findOrFail($data['owner_id']);
        }

        $planning->createCorrectiveAction($item, $request->user(), $data, $request);

        return redirect()->route('planning.workplans.show', $item->workplan_id)->with('success', 'Corrective action created for the target.');
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
