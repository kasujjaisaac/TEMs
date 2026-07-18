<?php

namespace App\Http\Controllers\Commercial;

use App\Http\Requests\Commercial\StoreSiteVisitRequest;
use App\Models\Commercial\CommercialSiteVisit;
use App\Services\Commercial\CommercialAuditService;
use App\Services\Commercial\CommercialNumberingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class SiteVisitController extends CommercialController
{
    public function index(): View
    {
        $this->authorizeCommercial('commercial.site_visits.view');

        return view('commercial.site_visits.index', [
            'page_title' => 'Commercial Site Visits | Texaro Technologies Limited',
            'siteVisits' => CommercialSiteVisit::where('tenant_id', $this->tenantId())->latest('visit_date')->paginate(15),
        ]);
    }

    public function create(): View
    {
        $this->authorizeCommercial('commercial.site_visits.create');

        return view('commercial.site_visits.form', [
            'page_title' => 'Record Site Visit | Texaro Technologies Limited',
            'siteVisit' => new CommercialSiteVisit(['report_status' => 'Draft']),
        ]);
    }

    public function store(StoreSiteVisitRequest $request, CommercialNumberingService $numbering, CommercialAuditService $audit): RedirectResponse
    {
        $siteVisit = CommercialSiteVisit::create($request->validated() + [
            'tenant_id' => $this->tenantId(),
            'reference' => $numbering->next($this->tenantId(), 'site_visit'),
            'recorded_by' => Auth::id(),
        ]);

        $audit->record($request, 'created', $siteVisit, 'Created site visit ' . $siteVisit->reference);

        return redirect()->route('commercial.site_visits.index')->with('success', 'Site visit recorded successfully.');
    }
}
