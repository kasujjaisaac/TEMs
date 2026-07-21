<?php

namespace App\Http\Controllers\Enterprise;

use App\Http\Controllers\Controller;
use App\Services\Enterprise\MissingModuleFoundationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class KnowledgeController extends Controller
{
    public function index(MissingModuleFoundationService $service): View
    {
        abort_unless(Auth::user()?->hasPermission('knowledge.view'), 403);
        $tenantId = (int) Auth::user()->tenant_id;
        $service->bootstrap($tenantId);

        return view('enterprise.knowledge', [
            'page_title' => 'Knowledge and Documents | Texaro Technologies Limited',
            'metrics' => $service->metrics($tenantId),
            'articles' => DB::table('knowledge_articles')->where('tenant_id', $tenantId)->latest()->limit(12)->get(),
            'documents' => DB::table('document_records')->where('tenant_id', $tenantId)->latest()->limit(12)->get(),
        ]);
    }

    public function storeArticle(Request $request, MissingModuleFoundationService $service): RedirectResponse
    {
        abort_unless(Auth::user()?->hasPermission('knowledge.manage'), 403);
        $tenantId = (int) Auth::user()->tenant_id;
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:120'],
            'summary' => ['nullable', 'string'],
            'review_due_on' => ['nullable', 'date'],
        ]);

        DB::table('knowledge_articles')->insert($data + [
            'tenant_id' => $tenantId,
            'reference' => $service->nextReference($tenantId, 'knowledge_articles', 'KB'),
            'review_status' => 'Draft',
            'owner_id' => Auth::id(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return back()->with('success', 'Knowledge article created.');
    }
}
