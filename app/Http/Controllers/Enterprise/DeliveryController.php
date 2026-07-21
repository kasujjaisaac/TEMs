<?php

namespace App\Http\Controllers\Enterprise;

use App\Http\Controllers\Controller;
use App\Services\Enterprise\EnterpriseExpansionService;
use App\Services\Enterprise\EnterpriseOperatingControlService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DeliveryController extends Controller
{
    public function index(EnterpriseExpansionService $service): View
    {
        abort_unless(Auth::user()?->hasPermission('delivery.view'), 403);
        $tenantId = (int) Auth::user()->tenant_id;
        $service->bootstrap($tenantId);

        return view('enterprise.delivery', [
            'page_title' => 'Products and Delivery | Texaro Technologies Limited',
            'metrics' => $service->metrics($tenantId),
            'products' => DB::table('products_portfolio')->where('tenant_id', $tenantId)->latest()->limit(12)->get(),
            'projects' => DB::table('implementation_projects')->where('tenant_id', $tenantId)->latest()->limit(12)->get(),
            'releases' => DB::table('engineering_releases')->where('tenant_id', $tenantId)->latest()->limit(12)->get(),
            'opportunities' => DB::table('commercial_opportunities')->where('tenant_id', $tenantId)->where('current_stage', 'Won')->latest()->limit(12)->get(),
        ]);
    }

    public function storeProduct(Request $request, EnterpriseExpansionService $service): RedirectResponse
    {
        abort_unless(Auth::user()?->hasPermission('delivery.manage'), 403);
        $tenantId = (int) Auth::user()->tenant_id;
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:120'],
            'lifecycle_stage' => ['required', 'string', 'max:80'],
            'description' => ['nullable', 'string'],
            'target_revenue' => ['nullable', 'numeric', 'min:0'],
        ]);

        DB::table('products_portfolio')->insert($data + [
            'tenant_id' => $tenantId,
            'reference' => $service->nextReference($tenantId, 'products_portfolio', 'PROD'),
            'owner_id' => Auth::id(),
            'health_score' => 50,
            'status' => 'Active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return back()->with('success', 'Product added to portfolio.');
    }

    public function createProjectFromOpportunity(Request $request, EnterpriseExpansionService $service): RedirectResponse
    {
        abort_unless(Auth::user()?->hasPermission('delivery.manage'), 403);
        $data = $request->validate(['opportunity_id' => ['required', 'integer']]);
        $service->createProjectFromOpportunity((int) Auth::user()->tenant_id, (int) $data['opportunity_id'], Auth::id());

        return back()->with('success', 'Implementation project created from won opportunity.');
    }

    public function completeMilestone(Request $request, EnterpriseOperatingControlService $controls): RedirectResponse
    {
        abort_unless(Auth::user()?->hasPermission('delivery.manage'), 403);
        $data = $request->validate([
            'project_id' => ['required', 'integer'],
            'milestone_id' => ['required', 'integer'],
            'evidence_summary' => ['required', 'string', 'max:2000'],
        ]);

        $controls->completeProjectGate((int) Auth::user()->tenant_id, (int) $data['project_id'], (int) $data['milestone_id'], $request->user(), $data['evidence_summary']);

        return back()->with('success', 'Project milestone accepted and delivery gate verified.');
    }

    public function handoverToCustomerSuccess(Request $request, EnterpriseOperatingControlService $controls): RedirectResponse
    {
        abort_unless(Auth::user()?->hasPermission('delivery.manage'), 403);
        $data = $request->validate([
            'project_id' => ['required', 'integer'],
            'handover_notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $controls->handoverProjectToCustomerSuccess((int) Auth::user()->tenant_id, (int) $data['project_id'], $request->user(), $data['handover_notes'] ?? null);

        return back()->with('success', 'Project handed to Customer Success.');
    }
}
