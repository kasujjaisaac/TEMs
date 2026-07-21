<?php

namespace App\Http\Controllers\Enterprise;

use App\Http\Controllers\Controller;
use App\Services\Enterprise\MissingModuleFoundationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class EngineeringController extends Controller
{
    public function index(MissingModuleFoundationService $service): View
    {
        abort_unless(Auth::user()?->hasPermission('engineering.view'), 403);
        $tenantId = (int) Auth::user()->tenant_id;
        $service->bootstrap($tenantId);

        return view('enterprise.engineering', [
            'page_title' => 'Engineering and Software Development | Texaro Technologies Limited',
            'metrics' => $service->metrics($tenantId),
            'backlog' => DB::table('engineering_backlog_items')->where('tenant_id', $tenantId)->latest()->limit(12)->get(),
            'defects' => DB::table('engineering_quality_defects')->where('tenant_id', $tenantId)->latest()->limit(12)->get(),
            'releases' => DB::table('engineering_releases')->where('tenant_id', $tenantId)->latest()->limit(12)->get(),
        ]);
    }

    public function storeBacklogItem(Request $request, MissingModuleFoundationService $service): RedirectResponse
    {
        abort_unless(Auth::user()?->hasPermission('engineering.manage'), 403);
        $tenantId = (int) Auth::user()->tenant_id;
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'item_type' => ['required', 'string', 'max:80'],
            'priority' => ['required', 'string', 'max:40'],
            'release_target' => ['nullable', 'string', 'max:120'],
        ]);

        DB::table('engineering_backlog_items')->insert($data + [
            'tenant_id' => $tenantId,
            'reference' => $service->nextReference($tenantId, 'engineering_backlog_items', 'ENG'),
            'owner_id' => Auth::id(),
            'status' => 'Backlog',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return back()->with('success', 'Engineering backlog item created.');
    }
}
