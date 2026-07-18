<?php

namespace App\Http\Controllers\Commercial;

use App\Http\Requests\Commercial\StoreActivityRequest;
use App\Models\Commercial\CommercialActivity;
use App\Services\Commercial\CommercialAuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ActivityController extends CommercialController
{
    public function index(): View
    {
        $this->authorizeCommercial('commercial.activities.view');

        return view('commercial.activities.index', [
            'page_title' => 'Commercial Activities | Texaro Technologies Limited',
            'activities' => CommercialActivity::where('tenant_id', $this->tenantId())->latest()->paginate(20),
        ]);
    }

    public function create(): View
    {
        $this->authorizeCommercial('commercial.activities.create');

        return view('commercial.activities.form', [
            'page_title' => 'Create Commercial Activity | Texaro Technologies Limited',
            'activity' => new CommercialActivity(['completion_status' => 'Open']),
        ]);
    }

    public function store(StoreActivityRequest $request, CommercialAuditService $audit): RedirectResponse
    {
        $activity = CommercialActivity::create($request->validated() + [
            'tenant_id' => $this->tenantId(),
            'owner_id' => Auth::id(),
            'created_by' => Auth::id(),
        ]);

        $audit->record($request, 'created', $activity, 'Created commercial activity');

        return redirect()->route('commercial.activities.index')->with('success', 'Activity recorded successfully.');
    }
}
