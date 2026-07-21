<?php

namespace App\Http\Controllers\Commercial;

use App\Models\Commercial\CommercialCampaign;
use App\Models\User;
use App\Services\Commercial\CommercialAuditService;
use App\Services\Commercial\CommercialNumberingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class CampaignController extends CommercialController
{
    public function index(): View
    {
        $this->authorizeCommercial('commercial.campaigns.view');

        return view('commercial.campaigns.index', [
            'page_title' => 'Commercial Campaigns | Texaro Technologies Limited',
            'campaigns' => CommercialCampaign::withCount(['leads', 'opportunities'])
                ->where('tenant_id', $this->tenantId())
                ->latest()
                ->paginate(15),
            'employees' => User::where('tenant_id', $this->tenantId())->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request, CommercialNumberingService $numbering, CommercialAuditService $audit): RedirectResponse
    {
        $this->authorizeCommercial('commercial.campaigns.manage');

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'campaign_type' => ['required', 'string', 'max:80'],
            'channel' => ['nullable', 'string', 'max:80'],
            'objective' => ['nullable', 'string'],
            'target_audience' => ['nullable', 'string', 'max:255'],
            'budget' => ['nullable', 'numeric', 'min:0'],
            'actual_spend' => ['nullable', 'numeric', 'min:0'],
            'starts_on' => ['nullable', 'date'],
            'ends_on' => ['nullable', 'date', 'after_or_equal:starts_on'],
            'status' => ['required', 'string', 'max:40'],
            'owner_id' => ['nullable', 'integer'],
        ]);

        if (! empty($data['owner_id'])) {
            User::where('tenant_id', $this->tenantId())->findOrFail($data['owner_id']);
        }

        $campaign = CommercialCampaign::create($data + [
            'tenant_id' => $this->tenantId(),
            'reference' => $numbering->next($this->tenantId(), 'campaign'),
            'created_by' => Auth::id(),
        ]);

        $audit->record($request, 'created', $campaign, 'Created campaign ' . $campaign->reference);

        return redirect()->route('commercial.campaigns.index')->with('success', 'Campaign created successfully.');
    }
}
