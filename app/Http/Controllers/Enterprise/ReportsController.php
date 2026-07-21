<?php

namespace App\Http\Controllers\Enterprise;

use App\Http\Controllers\Controller;
use App\Services\Enterprise\MissingModuleFoundationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ReportsController extends Controller
{
    public function index(MissingModuleFoundationService $service): View
    {
        abort_unless(Auth::user()?->hasPermission('analytics.view'), 403);
        $tenantId = (int) Auth::user()->tenant_id;
        $service->bootstrap($tenantId);

        return view('enterprise.reports', [
            'page_title' => 'Reports and Analytics | Texaro Technologies Limited',
            'metrics' => $service->metrics($tenantId),
            'reports' => DB::table('report_definitions')->where('tenant_id', $tenantId)->latest()->limit(12)->get(),
            'snapshots' => DB::table('intelligence_metric_snapshots')->where('tenant_id', $tenantId)->latest('captured_at')->limit(12)->get(),
        ]);
    }

    public function storeReport(Request $request, MissingModuleFoundationService $service): RedirectResponse
    {
        abort_unless(Auth::user()?->hasPermission('analytics.manage'), 403);
        $tenantId = (int) Auth::user()->tenant_id;
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'module' => ['required', 'string', 'max:120'],
            'frequency' => ['required', 'string', 'max:60'],
            'visibility' => ['required', 'string', 'max:60'],
        ]);

        DB::table('report_definitions')->insert($data + [
            'tenant_id' => $tenantId,
            'reference' => $service->nextReference($tenantId, 'report_definitions', 'RPT'),
            'metrics' => json_encode([]),
            'owner_id' => Auth::id(),
            'status' => 'Active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return back()->with('success', 'Report definition created.');
    }
}
