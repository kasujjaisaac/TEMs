<?php

namespace App\Http\Controllers\Commercial;

use App\Http\Requests\Commercial\StoreOpportunityRequest;
use App\Models\Commercial\CommercialOpportunity;
use App\Models\Commercial\CommercialOpportunityStageHistory;
use App\Models\Commercial\CommercialOrganization;
use App\Models\Commercial\CommercialPipelineStage;
use App\Models\Commercial\CommercialStakeholder;
use App\Models\User;
use App\Services\Commercial\CommercialAuditService;
use App\Services\Commercial\CommercialNumberingService;
use App\Services\Commercial\CommercialSalesHandoffService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class OpportunityController extends CommercialController
{
    public function index(Request $request): View
    {
        $this->authorizeCommercial('commercial.opportunities.view');

        return view('commercial.opportunities.index', [
            'page_title' => 'Commercial Opportunities | Texaro Technologies Limited',
            'opportunities' => CommercialOpportunity::with(['organization', 'assignedEmployee'])
                ->where('tenant_id', $this->tenantId())
                ->when($request->string('stage')->toString(), fn ($query, $stage) => $query->where('current_stage', $stage))
                ->latest()
                ->paginate(15)
                ->withQueryString(),
            'stages' => CommercialPipelineStage::where('tenant_id', $this->tenantId())->orderBy('display_order')->get(),
        ]);
    }

    public function create(): View
    {
        $this->authorizeCommercial('commercial.opportunities.create');

        return view('commercial.opportunities.form', [
            'page_title' => 'Create Opportunity | Texaro Technologies Limited',
            'opportunity' => new CommercialOpportunity(['current_stage' => 'Qualified', 'probability' => 20, 'currency' => config('app.currency', 'UGX')]),
            'organizations' => CommercialOrganization::where('tenant_id', $this->tenantId())->orderBy('legal_name')->get(),
            'stakeholders' => CommercialStakeholder::where('tenant_id', $this->tenantId())->orderBy('full_name')->get(),
            'employees' => User::where('tenant_id', $this->tenantId())->orderBy('name')->get(),
            'stages' => CommercialPipelineStage::where('tenant_id', $this->tenantId())->orderBy('display_order')->get(),
        ]);
    }

    public function store(StoreOpportunityRequest $request, CommercialNumberingService $numbering, CommercialAuditService $audit): RedirectResponse
    {
        $data = $request->validated();
        CommercialOrganization::where('tenant_id', $this->tenantId())->findOrFail($data['organization_id']);

        $opportunity = CommercialOpportunity::create($data + [
            'tenant_id' => $this->tenantId(),
            'reference' => $numbering->next($this->tenantId(), 'opportunity'),
            'created_by' => Auth::id(),
            'updated_by' => Auth::id(),
        ]);

        CommercialOpportunityStageHistory::create([
            'tenant_id' => $this->tenantId(),
            'opportunity_id' => $opportunity->id,
            'new_stage' => $opportunity->current_stage,
            'changed_by' => Auth::id(),
            'reason' => 'Opportunity created',
        ]);

        $audit->record($request, 'created', $opportunity, 'Created opportunity ' . $opportunity->reference);

        return redirect()->route('commercial.opportunities.show', $opportunity)->with('success', 'Opportunity created successfully.');
    }

    public function show(CommercialOpportunity $opportunity): View
    {
        $this->authorizeCommercial('commercial.opportunities.view');
        $this->ensureTenant($opportunity);

        return view('commercial.opportunities.show', [
            'page_title' => $opportunity->reference . ' | Commercial Opportunity',
            'opportunity' => $opportunity->load(['organization', 'primaryStakeholder', 'assignedEmployee', 'stageHistory', 'latestSalesHandoff']),
            'stages' => CommercialPipelineStage::where('tenant_id', $this->tenantId())->where('is_active', true)->orderBy('display_order')->get(),
        ]);
    }

    public function handoffToSales(Request $request, CommercialOpportunity $opportunity, CommercialSalesHandoffService $handoff, CommercialAuditService $audit): RedirectResponse
    {
        $this->authorizeCommercial('commercial.opportunities.handoff_to_sales');
        $this->ensureTenant($opportunity);

        $data = $request->validate([
            'handoff_summary' => ['nullable', 'string', 'max:3000'],
            'sales_instructions' => ['nullable', 'string', 'max:3000'],
            'quotation_notes' => ['nullable', 'string', 'max:3000'],
        ]);

        $previousStage = $opportunity->current_stage;
        $result = $handoff->handoffToSales($opportunity, Auth::user(), $data);
        $opportunity->refresh();

        CommercialOpportunityStageHistory::create([
            'tenant_id' => $this->tenantId(),
            'opportunity_id' => $opportunity->id,
            'previous_stage' => $previousStage,
            'new_stage' => $opportunity->current_stage,
            'changed_by' => Auth::id(),
            'reason' => 'Commercial opportunity handed to Sales',
            'notes' => 'Quotation ID ' . $result['quotation_id'] . ' created for Sales follow-up.',
        ]);

        $audit->record($request, 'sales_handoff_created', $opportunity, 'Handed opportunity ' . $opportunity->reference . ' to Sales', [
            'customer_id' => $result['customer_id'],
            'quotation_id' => $result['quotation_id'],
            'created_customer' => $result['created_customer'],
            'created_quotation' => $result['created_quotation'],
        ]);

        return redirect()
            ->route('commercial.opportunities.show', $opportunity)
            ->with('success', 'Sales handoff completed. A quotation is now available in Sales.');
    }

    public function updateStage(Request $request, CommercialOpportunity $opportunity, CommercialAuditService $audit): RedirectResponse
    {
        $this->authorizeCommercial('commercial.opportunities.change_stage');
        $this->ensureTenant($opportunity);

        $data = $request->validate([
            'stage_id' => ['required', 'integer', 'exists:commercial_pipeline_stages,id'],
            'reason' => ['nullable', 'string', 'max:1000'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $stage = CommercialPipelineStage::where('tenant_id', $this->tenantId())
            ->where('is_active', true)
            ->findOrFail($data['stage_id']);

        $previousStage = $opportunity->current_stage;
        $opportunity->forceFill([
            'pipeline_stage_id' => $stage->id,
            'current_stage' => $stage->name,
            'probability' => $stage->default_probability,
            'updated_by' => Auth::id(),
            'won_at' => $stage->name === 'Won' ? now() : $opportunity->won_at,
            'lost_at' => $stage->name === 'Lost' ? now() : $opportunity->lost_at,
        ])->save();

        CommercialOpportunityStageHistory::create([
            'tenant_id' => $this->tenantId(),
            'opportunity_id' => $opportunity->id,
            'previous_stage' => $previousStage,
            'new_stage' => $stage->name,
            'changed_by' => Auth::id(),
            'reason' => $data['reason'] ?? 'Stage updated',
            'notes' => $data['notes'] ?? null,
        ]);

        $audit->record($request, 'stage_changed', $opportunity, 'Changed opportunity stage for ' . $opportunity->reference, [
            'previous_stage' => $previousStage,
            'new_stage' => $stage->name,
        ]);

        return redirect()->route('commercial.opportunities.show', $opportunity)->with('success', 'Opportunity stage updated successfully.');
    }
}
