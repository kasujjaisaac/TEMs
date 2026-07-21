<?php

namespace App\Http\Controllers\Enterprise;

use App\Http\Controllers\Controller;
use App\Services\Enterprise\EnterpriseExpansionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class GovernanceController extends Controller
{
    public function index(EnterpriseExpansionService $service): View
    {
        abort_unless(Auth::user()?->hasPermission('governance.view'), 403);
        $tenantId = (int) Auth::user()->tenant_id;
        $service->bootstrap($tenantId);

        return view('enterprise.governance', [
            'page_title' => 'Governance and Compliance | Texaro Technologies Limited',
            'metrics' => $service->metrics($tenantId),
            'obligations' => DB::table('compliance_obligations')->where('tenant_id', $tenantId)->latest()->limit(12)->get(),
            'actions' => DB::table('board_governance_actions')->where('tenant_id', $tenantId)->latest()->limit(12)->get(),
        ]);
    }

    public function storeObligation(Request $request, EnterpriseExpansionService $service): RedirectResponse
    {
        abort_unless(Auth::user()?->hasPermission('governance.manage'), 403);
        $tenantId = (int) Auth::user()->tenant_id;
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:120'],
            'due_on' => ['nullable', 'date'],
            'risk_level' => ['required', 'string', 'max:40'],
            'notes' => ['nullable', 'string'],
        ]);

        DB::table('compliance_obligations')->insert($data + [
            'tenant_id' => $tenantId,
            'reference' => $service->nextReference($tenantId, 'compliance_obligations', 'CMP'),
            'status' => 'Open',
            'owner_id' => Auth::id(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return back()->with('success', 'Compliance obligation created.');
    }
}
