<?php

namespace App\Http\Controllers\Commercial;

use App\Http\Requests\Commercial\StoreOrganizationRequest;
use App\Models\Commercial\CommercialOrganization;
use App\Models\User;
use App\Services\Commercial\CommercialAuditService;
use App\Services\Commercial\CommercialLegacyCustomerBridgeService;
use App\Services\Commercial\CommercialNumberingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class OrganizationController extends CommercialController
{
    public function index(Request $request): View
    {
        $this->authorizeCommercial('commercial.organizations.view');

        return view('commercial.organizations.index', [
            'page_title' => 'Commercial Organizations | Texaro Technologies Limited',
            'organizations' => CommercialOrganization::with('accountManager')
                ->where('tenant_id', $this->tenantId())
                ->when($request->string('search')->toString(), function ($query, $search): void {
                    $query->where(function ($nested) use ($search): void {
                        $nested->where('legal_name', 'like', '%' . $search . '%')
                            ->orWhere('reference', 'like', '%' . $search . '%');
                    });
                })
                ->latest()
                ->paginate(15)
                ->withQueryString(),
        ]);
    }

    public function create(): View
    {
        $this->authorizeCommercial('commercial.organizations.create');

        return view('commercial.organizations.form', [
            'page_title' => 'Create Organization | Texaro Technologies Limited',
            'organization' => new CommercialOrganization(['customer_status' => 'Prospect']),
            'employees' => User::where('tenant_id', $this->tenantId())->orderBy('name')->get(),
        ]);
    }

    public function store(StoreOrganizationRequest $request, CommercialNumberingService $numbering, CommercialAuditService $audit, CommercialLegacyCustomerBridgeService $legacyBridge): RedirectResponse
    {
        $organization = CommercialOrganization::create($request->validated() + [
            'tenant_id' => $this->tenantId(),
            'reference' => $numbering->next($this->tenantId(), 'organization'),
            'created_by' => Auth::id(),
            'updated_by' => Auth::id(),
        ]);

        $audit->record($request, 'created', $organization, 'Created commercial organization ' . $organization->reference);
        if (str_contains(strtolower($organization->customer_status), 'customer')) {
            $legacyBridge->syncOrganization($organization, $request->user());
        }

        return redirect()->route('commercial.organizations.show', $organization)->with('success', 'Organization created successfully.');
    }

    public function show(CommercialOrganization $organization): View
    {
        $this->authorizeCommercial('commercial.organizations.view');
        $this->ensureTenant($organization);

        return view('commercial.organizations.show', [
            'page_title' => $organization->legal_name . ' | Commercial Organization',
            'organization' => $organization->load(['accountManager', 'stakeholders', 'opportunities']),
        ]);
    }

    public function edit(CommercialOrganization $organization): View
    {
        $this->authorizeCommercial('commercial.organizations.update');
        $this->ensureTenant($organization);

        return view('commercial.organizations.form', [
            'page_title' => 'Edit ' . $organization->legal_name . ' | Texaro Technologies Limited',
            'organization' => $organization,
            'employees' => User::where('tenant_id', $this->tenantId())->orderBy('name')->get(),
        ]);
    }

    public function update(StoreOrganizationRequest $request, CommercialOrganization $organization, CommercialAuditService $audit, CommercialLegacyCustomerBridgeService $legacyBridge): RedirectResponse
    {
        $this->authorizeCommercial('commercial.organizations.update');
        $this->ensureTenant($organization);

        $before = $organization->only(['legacy_customer_id', 'legal_name', 'customer_status', 'account_manager_id', 'relationship_score', 'credit_status']);
        $organization->fill($request->validated() + ['updated_by' => Auth::id()]);
        $organization->save();

        $audit->record($request, 'updated', $organization, 'Updated commercial organization ' . $organization->reference, [
            'before' => $before,
            'after' => $organization->only(array_keys($before)),
        ]);
        if (! $organization->legacy_customer_id && str_contains(strtolower($organization->customer_status), 'customer')) {
            $legacyBridge->syncOrganization($organization, $request->user());
        }

        return redirect()->route('commercial.organizations.show', $organization)->with('success', 'Organization updated successfully.');
    }

    public function syncCustomer(Request $request, CommercialOrganization $organization, CommercialAuditService $audit, CommercialLegacyCustomerBridgeService $legacyBridge): RedirectResponse
    {
        $this->authorizeCommercial('commercial.organizations.update');
        $this->ensureTenant($organization);

        $customerId = $legacyBridge->syncOrganization($organization, $request->user());
        $audit->record($request, 'legacy_customer_synced', $organization->refresh(), 'Synced commercial organization ' . $organization->reference . ' to legacy customer register', [
            'customer_id' => $customerId,
        ]);

        return redirect()->route('commercial.organizations.show', $organization)->with('success', 'Legacy customer register synced.');
    }
}
