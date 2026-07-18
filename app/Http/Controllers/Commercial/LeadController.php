<?php

namespace App\Http\Controllers\Commercial;

use App\Http\Requests\Commercial\StoreLeadRequest;
use App\Models\Commercial\CommercialLead;
use App\Models\User;
use App\Services\Commercial\CommercialAuditService;
use App\Services\Commercial\CommercialNumberingService;
use App\Services\Commercial\LeadConversionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class LeadController extends CommercialController
{
    public function index(Request $request): View
    {
        $this->authorizeCommercial('commercial.leads.view');

        $leads = CommercialLead::with('assignedEmployee')
            ->where('tenant_id', $this->tenantId())
            ->when($request->string('status')->toString(), fn ($query, $status) => $query->where('status', $status))
            ->when($request->string('search')->toString(), function ($query, $search): void {
                $query->where(function ($nested) use ($search): void {
                    $nested->where('organization_name', 'like', '%' . $search . '%')
                        ->orWhere('reference', 'like', '%' . $search . '%')
                        ->orWhere('contact_person', 'like', '%' . $search . '%');
                });
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('commercial.leads.index', [
            'page_title' => 'Commercial Leads | Texaro Technologies Limited',
            'leads' => $leads,
        ]);
    }

    public function create(): View
    {
        $this->authorizeCommercial('commercial.leads.create');

        return view('commercial.leads.form', [
            'page_title' => 'Create Lead | Texaro Technologies Limited',
            'lead' => new CommercialLead(['status' => 'New', 'temperature' => 'Warm']),
            'employees' => User::where('tenant_id', $this->tenantId())->orderBy('name')->get(),
        ]);
    }

    public function store(StoreLeadRequest $request, CommercialNumberingService $numbering, CommercialAuditService $audit): RedirectResponse
    {
        $data = $request->validated();
        $lead = CommercialLead::create($data + [
            'tenant_id' => $this->tenantId(),
            'reference' => $numbering->next($this->tenantId(), 'lead'),
            'created_by' => Auth::id(),
            'updated_by' => Auth::id(),
        ]);

        $audit->record($request, 'created', $lead, 'Created commercial lead ' . $lead->reference);

        return redirect()->route('commercial.leads.show', $lead)->with('success', 'Lead created successfully.');
    }

    public function show(CommercialLead $lead): View
    {
        $this->authorizeCommercial('commercial.leads.view');
        $this->ensureTenant($lead);

        return view('commercial.leads.show', [
            'page_title' => $lead->reference . ' | Commercial Lead',
            'lead' => $lead->load(['assignedEmployee', 'organization', 'stakeholder', 'opportunity']),
        ]);
    }

    public function edit(CommercialLead $lead): View
    {
        $this->authorizeCommercial('commercial.leads.update');
        $this->ensureTenant($lead);

        return view('commercial.leads.form', [
            'page_title' => 'Edit ' . $lead->reference . ' | Texaro Technologies Limited',
            'lead' => $lead,
            'employees' => User::where('tenant_id', $this->tenantId())->orderBy('name')->get(),
        ]);
    }

    public function update(StoreLeadRequest $request, CommercialLead $lead, CommercialAuditService $audit): RedirectResponse
    {
        $this->authorizeCommercial('commercial.leads.update');
        $this->ensureTenant($lead);

        $before = $lead->only(['status', 'assigned_employee_id', 'temperature', 'lead_score', 'next_action', 'next_follow_up_date']);
        $lead->fill($request->validated() + ['updated_by' => Auth::id()]);
        $lead->save();

        $audit->record($request, 'updated', $lead, 'Updated commercial lead ' . $lead->reference, [
            'before' => $before,
            'after' => $lead->only(array_keys($before)),
        ]);

        return redirect()->route('commercial.leads.show', $lead)->with('success', 'Lead updated successfully.');
    }

    public function convert(Request $request, CommercialLead $lead, LeadConversionService $converter, CommercialAuditService $audit): RedirectResponse
    {
        $this->authorizeCommercial('commercial.leads.convert');
        $this->ensureTenant($lead);

        $data = $request->validate([
            'legal_name' => ['nullable', 'string', 'max:180'],
            'trading_name' => ['nullable', 'string', 'max:180'],
            'opportunity_title' => ['nullable', 'string', 'max:180'],
            'opportunity_type' => ['nullable', 'string', 'max:80'],
        ]);

        $result = $converter->convert($lead, Auth::user(), $data);
        $audit->record($request, 'converted', $lead->refresh(), 'Converted lead ' . $lead->reference, [
            'organization_id' => $result['organization']->id,
            'stakeholder_id' => $result['stakeholder']?->id,
            'opportunity_id' => $result['opportunity']->id,
        ]);

        return redirect()->route('commercial.opportunities.show', $result['opportunity'])->with('success', 'Lead converted into organization, stakeholder, and opportunity.');
    }
}
