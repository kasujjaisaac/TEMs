<?php

namespace App\Http\Controllers\Enterprise;

use App\Http\Controllers\Controller;
use App\Services\Enterprise\MissingModuleFoundationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class MarketingController extends Controller
{
    public function index(MissingModuleFoundationService $service): View
    {
        abort_unless(Auth::user()?->hasPermission('marketing.view'), 403);
        $tenantId = (int) Auth::user()->tenant_id;
        $service->bootstrap($tenantId);

        return view('enterprise.marketing', [
            'page_title' => 'Marketing and Communications | Texaro Technologies Limited',
            'metrics' => $service->metrics($tenantId),
            'plans' => DB::table('marketing_communication_plans')->where('tenant_id', $tenantId)->latest()->limit(12)->get(),
            'contentItems' => DB::table('marketing_content_items')->where('tenant_id', $tenantId)->latest()->limit(12)->get(),
        ]);
    }

    public function storePlan(Request $request, MissingModuleFoundationService $service): RedirectResponse
    {
        abort_unless(Auth::user()?->hasPermission('marketing.manage'), 403);
        $tenantId = (int) Auth::user()->tenant_id;
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'channel' => ['nullable', 'string', 'max:80'],
            'audience' => ['nullable', 'string', 'max:160'],
            'starts_on' => ['nullable', 'date'],
            'ends_on' => ['nullable', 'date'],
            'budget' => ['nullable', 'numeric', 'min:0'],
        ]);

        DB::table('marketing_communication_plans')->insert($data + [
            'tenant_id' => $tenantId,
            'reference' => $service->nextReference($tenantId, 'marketing_communication_plans', 'MKT'),
            'status' => 'Planned',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return back()->with('success', 'Marketing communication plan created.');
    }
}
