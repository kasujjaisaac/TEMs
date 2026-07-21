<?php

namespace App\Http\Controllers\Enterprise;

use App\Http\Controllers\Controller;
use App\Services\Enterprise\MissingModuleFoundationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class StrategyController extends Controller
{
    public function index(MissingModuleFoundationService $service): View
    {
        abort_unless(Auth::user()?->hasPermission('strategy.view'), 403);
        $tenantId = (int) Auth::user()->tenant_id;
        $service->bootstrap($tenantId);

        return view('enterprise.strategy', [
            'page_title' => 'Executive Strategy | Texaro Technologies Limited',
            'metrics' => $service->metrics($tenantId),
            'directives' => DB::table('executive_directives')->where('tenant_id', $tenantId)->latest()->limit(12)->get(),
            'decisions' => DB::table('executive_decisions')->where('tenant_id', $tenantId)->latest()->limit(12)->get(),
            'risks' => DB::table('corporate_risks')->where('tenant_id', $tenantId)->latest()->limit(12)->get(),
        ]);
    }

    public function storeDirective(Request $request, MissingModuleFoundationService $service): RedirectResponse
    {
        abort_unless(Auth::user()?->hasPermission('strategy.manage'), 403);
        $tenantId = (int) Auth::user()->tenant_id;
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'directive' => ['nullable', 'string'],
            'priority' => ['required', 'string', 'max:40'],
            'due_on' => ['nullable', 'date'],
        ]);

        DB::table('executive_directives')->insert($data + [
            'tenant_id' => $tenantId,
            'reference' => $service->nextReference($tenantId, 'executive_directives', 'DIR'),
            'owner_id' => Auth::id(),
            'status' => 'Open',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return back()->with('success', 'Executive directive created.');
    }
}
