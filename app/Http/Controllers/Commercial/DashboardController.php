<?php

namespace App\Http\Controllers\Commercial;

use App\Models\Commercial\CommercialLead;
use App\Models\Commercial\CommercialMeeting;
use App\Models\Commercial\CommercialOpportunity;
use App\Models\Commercial\CommercialSiteVisit;
use App\Services\Commercial\CommercialDashboardService;
use Illuminate\View\View;

class DashboardController extends CommercialController
{
    public function __invoke(CommercialDashboardService $dashboard): View
    {
        $this->authorizeCommercial('commercial.dashboard.view');
        $tenantId = $this->tenantId();

        return view('commercial.dashboard', [
            'page_title' => 'Commercial Dashboard | Texaro Technologies Limited',
            'metrics' => $dashboard->metrics($tenantId),
            'recentLeads' => CommercialLead::where('tenant_id', $tenantId)->latest()->limit(5)->get(),
            'opportunities' => CommercialOpportunity::with('organization')->where('tenant_id', $tenantId)->latest()->limit(6)->get(),
            'meetings' => CommercialMeeting::where('tenant_id', $tenantId)->whereDate('meeting_date', '>=', now()->toDateString())->orderBy('meeting_date')->limit(5)->get(),
            'siteVisits' => CommercialSiteVisit::where('tenant_id', $tenantId)->whereDate('visit_date', '>=', now()->toDateString())->orderBy('visit_date')->limit(5)->get(),
        ]);
    }
}
