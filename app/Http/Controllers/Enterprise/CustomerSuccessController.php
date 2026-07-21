<?php

namespace App\Http\Controllers\Enterprise;

use App\Http\Controllers\Controller;
use App\Services\Enterprise\EnterpriseExpansionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class CustomerSuccessController extends Controller
{
    public function index(EnterpriseExpansionService $service): View
    {
        abort_unless(Auth::user()?->hasPermission('customer_success.view'), 403);
        $tenantId = (int) Auth::user()->tenant_id;
        $service->bootstrap($tenantId);

        return view('enterprise.customer_success', [
            'page_title' => 'Customer Success | Texaro Technologies Limited',
            'metrics' => $service->metrics($tenantId),
            'accounts' => DB::table('customer_success_accounts')->where('tenant_id', $tenantId)->latest()->limit(12)->get(),
            'tickets' => DB::table('support_tickets')->where('tenant_id', $tenantId)->latest()->limit(12)->get(),
            'organizations' => DB::table('commercial_organizations')->where('tenant_id', $tenantId)->orderBy('legal_name')->get(),
            'products' => DB::table('products_portfolio')->where('tenant_id', $tenantId)->orderBy('name')->get(),
        ]);
    }

    public function storeTicket(Request $request, EnterpriseExpansionService $service): RedirectResponse
    {
        abort_unless(Auth::user()?->hasPermission('customer_success.manage'), 403);
        $tenantId = (int) Auth::user()->tenant_id;
        $data = $request->validate([
            'organization_id' => ['nullable', 'integer'],
            'product_id' => ['nullable', 'integer'],
            'subject' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'priority' => ['required', 'string', 'max:40'],
        ]);

        DB::table('support_tickets')->insert($data + [
            'tenant_id' => $tenantId,
            'reference' => $service->nextReference($tenantId, 'support_tickets', 'SUP'),
            'status' => 'Open',
            'sla_due_at' => now()->addDays($data['priority'] === 'Critical' ? 1 : 3),
            'assigned_to' => Auth::id(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return back()->with('success', 'Support ticket created.');
    }
}
