<?php

namespace App\Http\Controllers\Enterprise;

use App\Http\Controllers\Controller;
use App\Services\Enterprise\EnterpriseExpansionService;
use App\Services\Enterprise\EnterpriseOperatingControlService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class IntelligenceController extends Controller
{
    public function index(EnterpriseExpansionService $service, EnterpriseOperatingControlService $controls): View
    {
        abort_unless(Auth::user()?->hasPermission('intelligence.view'), 403);
        $tenantId = (int) Auth::user()->tenant_id;
        $service->refreshIntelligence($tenantId);
        $controls->captureCompanyScorecard($tenantId);

        return view('enterprise.intelligence', [
            'page_title' => 'Enterprise Intelligence | Texaro Technologies Limited',
            'metrics' => $service->metrics($tenantId),
            'snapshots' => DB::table('intelligence_metric_snapshots')->where('tenant_id', $tenantId)->latest('captured_at')->limit(16)->get(),
            'scorecards' => DB::table('enterprise_scorecard_snapshots')->where('tenant_id', $tenantId)->latest('scorecard_date')->limit(12)->get(),
            'signals' => DB::table('intelligence_signals')->where('tenant_id', $tenantId)->latest()->limit(12)->get(),
            'recommendations' => DB::table('intelligence_recommendations')->where('tenant_id', $tenantId)->latest()->limit(12)->get(),
        ]);
    }

    public function refresh(EnterpriseExpansionService $service, EnterpriseOperatingControlService $controls): RedirectResponse
    {
        abort_unless(Auth::user()?->hasPermission('intelligence.manage'), 403);
        $tenantId = (int) Auth::user()->tenant_id;
        $service->refreshIntelligence($tenantId);
        $controls->captureCompanyScorecard($tenantId);

        return back()->with('success', 'Enterprise intelligence refreshed.');
    }
}
