<?php

namespace App\Http\Controllers\Commercial;

use App\Http\Requests\Commercial\StoreOpportunityRequest;
use App\Models\Commercial\CommercialOpportunity;
use App\Models\Commercial\CommercialOpportunityStageHistory;
use App\Models\Commercial\CommercialOrganization;
use App\Models\Commercial\CommercialPipelineStage;
use App\Models\Commercial\CommercialProposal;
use App\Models\Commercial\CommercialQuotation;
use App\Models\Commercial\CommercialContract;
use App\Models\Commercial\CommercialCampaign;
use App\Models\Commercial\CommercialStakeholder;
use App\Models\User;
use App\Services\Commercial\CommercialAuditService;
use App\Services\Commercial\CommercialCompletionService;
use App\Services\Commercial\CommercialLegacyCustomerBridgeService;
use App\Services\Commercial\CommercialNumberingService;
use App\Services\Commercial\CommercialRevenueLifecycleService;
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
            'campaigns' => CommercialCampaign::where('tenant_id', $this->tenantId())->orderBy('name')->get(),
        ]);
    }

    public function store(StoreOpportunityRequest $request, CommercialNumberingService $numbering, CommercialAuditService $audit, CommercialLegacyCustomerBridgeService $legacyBridge): RedirectResponse
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
        $legacyBridge->syncOpportunity($opportunity, $request->user());

        return redirect()->route('commercial.opportunities.show', $opportunity)->with('success', 'Opportunity created successfully.');
    }

    public function show(CommercialOpportunity $opportunity): View
    {
        $this->authorizeCommercial('commercial.opportunities.view');
        $this->ensureTenant($opportunity);

        return view('commercial.opportunities.show', [
            'page_title' => $opportunity->reference . ' | Commercial Opportunity',
            'opportunity' => $opportunity->load([
                'organization', 'campaign', 'primaryStakeholder', 'assignedEmployee', 'stageHistory', 'latestSalesHandoff',
                'proposals', 'quotations', 'contracts', 'billingRequests',
            ]),
            'stages' => CommercialPipelineStage::where('tenant_id', $this->tenantId())->where('is_active', true)->orderBy('display_order')->get(),
            'stageControls' => \DB::table('commercial_stage_controls')->where('tenant_id', $this->tenantId())->where('opportunity_id', $opportunity->id)->latest()->get(),
            'negotiations' => \DB::table('commercial_negotiations')->where('tenant_id', $this->tenantId())->where('opportunity_id', $opportunity->id)->latest()->get(),
            'renewals' => \DB::table('commercial_renewals')->where('tenant_id', $this->tenantId())->where('organization_id', $opportunity->organization_id)->latest('renewal_due_on')->get(),
            'expansions' => \DB::table('commercial_expansion_opportunities')->where('tenant_id', $this->tenantId())->where('source_opportunity_id', $opportunity->id)->latest()->get(),
            'lostAnalysis' => \DB::table('commercial_lost_opportunity_analyses')->where('tenant_id', $this->tenantId())->where('opportunity_id', $opportunity->id)->first(),
        ]);
    }

    public function verifyStageControls(CommercialOpportunity $opportunity, CommercialCompletionService $completion): RedirectResponse
    {
        $this->authorizeCommercial('commercial.opportunities.change_stage');
        $this->ensureTenant($opportunity);

        $result = $completion->verifyStageControls($opportunity, Auth::user());

        return back()->with($result['passed'] ? 'success' : 'warning', $result['passed'] ? 'Stage controls passed.' : 'Some stage controls are still incomplete.');
    }

    public function storeNegotiation(Request $request, CommercialOpportunity $opportunity, CommercialCompletionService $completion): RedirectResponse
    {
        $this->authorizeCommercial('commercial.opportunities.update');
        $this->ensureTenant($opportunity);
        $data = $request->validate([
            'stakeholder_id' => ['nullable', 'integer'],
            'topic' => ['required', 'string', 'max:160'],
            'customer_position' => ['nullable', 'string', 'max:3000'],
            'texaro_position' => ['nullable', 'string', 'max:3000'],
            'proposed_value' => ['nullable', 'numeric', 'min:0'],
            'agreed_value' => ['nullable', 'numeric', 'min:0'],
            'status' => ['nullable', 'string', 'max:60'],
            'next_follow_up_on' => ['nullable', 'date'],
        ]);

        $completion->recordNegotiation($opportunity, $request->user(), $data);

        return back()->with('success', 'Negotiation recorded.');
    }

    public function storeRenewal(Request $request, CommercialOpportunity $opportunity, CommercialCompletionService $completion): RedirectResponse
    {
        $this->authorizeCommercial('commercial.opportunities.update');
        $this->ensureTenant($opportunity);
        $data = $request->validate([
            'contract_id' => ['nullable', 'integer'],
            'renewal_due_on' => ['required', 'date'],
            'renewal_value' => ['nullable', 'numeric', 'min:0'],
            'status' => ['nullable', 'string', 'max:60'],
            'retention_plan' => ['nullable', 'string', 'max:3000'],
        ]);

        $completion->scheduleRenewal($opportunity, $request->user(), $data);

        return back()->with('success', 'Renewal scheduled.');
    }

    public function storeExpansion(Request $request, CommercialOpportunity $opportunity, CommercialCompletionService $completion): RedirectResponse
    {
        $this->authorizeCommercial('commercial.opportunities.update');
        $this->ensureTenant($opportunity);
        $data = $request->validate([
            'expansion_type' => ['nullable', 'string', 'max:80'],
            'title' => ['required', 'string', 'max:255'],
            'estimated_value' => ['nullable', 'numeric', 'min:0'],
            'status' => ['nullable', 'string', 'max:60'],
            'rationale' => ['nullable', 'string', 'max:3000'],
        ]);

        $completion->identifyExpansion($opportunity, $request->user(), $data);

        return back()->with('success', 'Expansion opportunity identified.');
    }

    public function storeLostAnalysis(Request $request, CommercialOpportunity $opportunity, CommercialCompletionService $completion): RedirectResponse
    {
        $this->authorizeCommercial('commercial.opportunities.close');
        $this->ensureTenant($opportunity);
        $data = $request->validate([
            'primary_reason' => ['required', 'string', 'max:160'],
            'competitor_name' => ['nullable', 'string', 'max:160'],
            'lessons_learned' => ['nullable', 'string', 'max:3000'],
            'recovery_action' => ['nullable', 'string', 'max:3000'],
        ]);

        $completion->recordLostAnalysis($opportunity, $request->user(), $data);

        return back()->with('success', 'Lost opportunity analysis recorded.');
    }

    public function storeProposal(Request $request, CommercialOpportunity $opportunity, CommercialRevenueLifecycleService $revenue, CommercialAuditService $audit): RedirectResponse
    {
        $this->authorizeCommercial('commercial.opportunities.update');
        $this->ensureTenant($opportunity);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'scope_summary' => ['nullable', 'string'],
            'value_proposition' => ['nullable', 'string'],
            'version' => ['nullable', 'string', 'max:20'],
            'proposed_value' => ['nullable', 'numeric', 'min:0'],
        ]);

        $proposal = $revenue->createProposal($opportunity, $request->user(), $data);
        $audit->record($request, 'proposal_created', $proposal, 'Created proposal ' . $proposal->reference);

        return redirect()->route('commercial.opportunities.show', $opportunity)->with('success', 'Proposal created.');
    }

    public function approveProposal(Request $request, CommercialProposal $proposal, CommercialRevenueLifecycleService $revenue, CommercialAuditService $audit): RedirectResponse
    {
        $this->authorizeCommercial('commercial.opportunities.change_stage');
        $this->ensureTenant($proposal);

        $revenue->approveProposal($proposal, $request->user());
        $audit->record($request, 'proposal_approved', $proposal, 'Approved proposal ' . $proposal->reference);

        return redirect()->route('commercial.opportunities.show', $proposal->opportunity_id)->with('success', 'Proposal approved.');
    }

    public function storeQuotation(Request $request, CommercialOpportunity $opportunity, CommercialRevenueLifecycleService $revenue, CommercialAuditService $audit): RedirectResponse
    {
        $this->authorizeCommercial('commercial.opportunities.update');
        $this->ensureTenant($opportunity);

        $data = $request->validate([
            'proposal_id' => ['nullable', 'integer'],
            'quotation_date' => ['nullable', 'date'],
            'valid_until' => ['nullable', 'date', 'after_or_equal:quotation_date'],
            'subtotal' => ['nullable', 'numeric', 'min:0'],
            'discount_amount' => ['nullable', 'numeric', 'min:0'],
            'tax_amount' => ['nullable', 'numeric', 'min:0'],
            'terms' => ['nullable', 'string'],
        ]);

        if (! empty($data['proposal_id'])) {
            CommercialProposal::where('tenant_id', $this->tenantId())->where('opportunity_id', $opportunity->id)->findOrFail($data['proposal_id']);
        }

        $quotation = $revenue->createQuotation($opportunity, $request->user(), $data);
        $audit->record($request, 'quotation_created', $quotation, 'Created quotation ' . $quotation->reference);

        return redirect()->route('commercial.opportunities.show', $opportunity)->with('success', 'Quotation created.');
    }

    public function decideQuotation(Request $request, CommercialQuotation $quotation, CommercialRevenueLifecycleService $revenue, CommercialAuditService $audit): RedirectResponse
    {
        $this->authorizeCommercial('commercial.opportunities.change_stage');
        $this->ensureTenant($quotation);

        $data = $request->validate(['decision' => ['required', 'in:Approved,Accepted']]);
        $data['decision'] === 'Approved'
            ? $revenue->approveQuotation($quotation, $request->user())
            : $revenue->acceptQuotation($quotation, $request->user());

        $audit->record($request, 'quotation_' . strtolower($data['decision']), $quotation, $data['decision'] . ' quotation ' . $quotation->reference);

        return redirect()->route('commercial.opportunities.show', $quotation->opportunity_id)->with('success', 'Quotation decision recorded.');
    }

    public function storeContract(Request $request, CommercialOpportunity $opportunity, CommercialRevenueLifecycleService $revenue, CommercialAuditService $audit): RedirectResponse
    {
        $this->authorizeCommercial('commercial.opportunities.update');
        $this->ensureTenant($opportunity);

        $data = $request->validate([
            'quotation_id' => ['nullable', 'integer'],
            'contract_title' => ['required', 'string', 'max:255'],
            'contract_value' => ['nullable', 'numeric', 'min:0'],
            'starts_on' => ['nullable', 'date'],
            'ends_on' => ['nullable', 'date', 'after_or_equal:starts_on'],
            'payment_terms' => ['nullable', 'string', 'max:255'],
        ]);

        if (! empty($data['quotation_id'])) {
            CommercialQuotation::where('tenant_id', $this->tenantId())->where('opportunity_id', $opportunity->id)->findOrFail($data['quotation_id']);
        }

        $contract = $revenue->createContract($opportunity, $request->user(), $data);
        $audit->record($request, 'contract_created', $contract, 'Created contract ' . $contract->reference);

        return redirect()->route('commercial.opportunities.show', $opportunity)->with('success', 'Contract created.');
    }

    public function signContract(Request $request, CommercialContract $contract, CommercialRevenueLifecycleService $revenue, CommercialAuditService $audit): RedirectResponse
    {
        $this->authorizeCommercial('commercial.opportunities.change_stage');
        $this->ensureTenant($contract);

        $revenue->signContract($contract, $request->user());
        $audit->record($request, 'contract_signed', $contract, 'Signed contract ' . $contract->reference);

        return redirect()->route('commercial.opportunities.show', $contract->opportunity_id)->with('success', 'Contract signed and opportunity marked won.');
    }

    public function storeBillingRequest(Request $request, CommercialOpportunity $opportunity, CommercialRevenueLifecycleService $revenue, CommercialAuditService $audit): RedirectResponse
    {
        $this->authorizeCommercial('commercial.opportunities.handoff_to_sales');
        $this->ensureTenant($opportunity);

        $data = $request->validate([
            'contract_id' => ['nullable', 'integer'],
            'quotation_id' => ['nullable', 'integer'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'requested_invoice_date' => ['nullable', 'date'],
            'billing_terms' => ['nullable', 'string', 'max:255'],
            'instructions' => ['nullable', 'string'],
        ]);

        if (! empty($data['contract_id'])) {
            CommercialContract::where('tenant_id', $this->tenantId())->where('opportunity_id', $opportunity->id)->findOrFail($data['contract_id']);
        }
        if (! empty($data['quotation_id'])) {
            CommercialQuotation::where('tenant_id', $this->tenantId())->where('opportunity_id', $opportunity->id)->findOrFail($data['quotation_id']);
        }

        $billing = $revenue->createBillingRequest($opportunity, $request->user(), $data);
        $audit->record($request, 'billing_requested', $billing, 'Created billing request ' . $billing->reference);

        return redirect()->route('commercial.opportunities.show', $opportunity)->with('success', 'Billing request created for Finance.');
    }

    public function handoffToSales(Request $request, CommercialOpportunity $opportunity, CommercialSalesHandoffService $handoff, CommercialAuditService $audit, CommercialLegacyCustomerBridgeService $legacyBridge): RedirectResponse
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
        $legacyBridge->syncOpportunity($opportunity, $request->user());

        return redirect()
            ->route('commercial.opportunities.show', $opportunity)
            ->with('success', 'Sales handoff completed. A quotation is now available in Sales.');
    }

    public function updateStage(Request $request, CommercialOpportunity $opportunity, CommercialAuditService $audit, CommercialLegacyCustomerBridgeService $legacyBridge): RedirectResponse
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
        $legacyBridge->syncOpportunity($opportunity, $request->user());

        return redirect()->route('commercial.opportunities.show', $opportunity)->with('success', 'Opportunity stage updated successfully.');
    }
}
