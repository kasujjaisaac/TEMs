<?php

namespace App\Http\Controllers\Commercial;

use App\Http\Requests\Commercial\StoreStakeholderRequest;
use App\Models\Commercial\CommercialOrganization;
use App\Models\Commercial\CommercialStakeholder;
use App\Services\Commercial\CommercialAuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class StakeholderController extends CommercialController
{
    public function index(): View
    {
        $this->authorizeCommercial('commercial.stakeholders.view');

        return view('commercial.stakeholders.index', [
            'page_title' => 'Commercial Stakeholders | Texaro Technologies Limited',
            'stakeholders' => CommercialStakeholder::with('organization')->where('tenant_id', $this->tenantId())->latest()->paginate(15),
        ]);
    }

    public function create(): View
    {
        $this->authorizeCommercial('commercial.stakeholders.create');

        return view('commercial.stakeholders.form', [
            'page_title' => 'Create Stakeholder | Texaro Technologies Limited',
            'stakeholder' => new CommercialStakeholder(),
            'organizations' => CommercialOrganization::where('tenant_id', $this->tenantId())->orderBy('legal_name')->get(),
        ]);
    }

    public function store(StoreStakeholderRequest $request, CommercialAuditService $audit): RedirectResponse
    {
        $organization = CommercialOrganization::where('tenant_id', $this->tenantId())->findOrFail($request->integer('organization_id'));
        $stakeholder = CommercialStakeholder::create($request->validated() + [
            'tenant_id' => $this->tenantId(),
            'organization_id' => $organization->id,
            'created_by' => Auth::id(),
        ]);

        $audit->record($request, 'created', $stakeholder, 'Created stakeholder ' . $stakeholder->full_name);

        return redirect()->route('commercial.organizations.show', $organization)->with('success', 'Stakeholder created successfully.');
    }

    public function edit(CommercialStakeholder $stakeholder): View
    {
        $this->authorizeCommercial('commercial.stakeholders.update');
        $this->ensureTenant($stakeholder);

        return view('commercial.stakeholders.form', [
            'page_title' => 'Edit Stakeholder | Texaro Technologies Limited',
            'stakeholder' => $stakeholder,
            'organizations' => CommercialOrganization::where('tenant_id', $this->tenantId())->orderBy('legal_name')->get(),
        ]);
    }

    public function update(StoreStakeholderRequest $request, CommercialStakeholder $stakeholder, CommercialAuditService $audit): RedirectResponse
    {
        $this->authorizeCommercial('commercial.stakeholders.update');
        $this->ensureTenant($stakeholder);
        $organization = CommercialOrganization::where('tenant_id', $this->tenantId())->findOrFail($request->integer('organization_id'));

        $before = $stakeholder->only(['organization_id', 'full_name', 'decision_role', 'influence_level', 'is_primary_contact']);
        $stakeholder->fill($request->validated() + ['organization_id' => $organization->id]);
        $stakeholder->save();

        $audit->record($request, 'updated', $stakeholder, 'Updated stakeholder ' . $stakeholder->full_name, [
            'before' => $before,
            'after' => $stakeholder->only(array_keys($before)),
        ]);

        return redirect()->route('commercial.organizations.show', $organization)->with('success', 'Stakeholder updated successfully.');
    }
}
