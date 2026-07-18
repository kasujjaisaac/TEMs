<?php

namespace App\Http\Controllers\Commercial;

use App\Http\Requests\Commercial\StoreMeetingRequest;
use App\Models\Commercial\CommercialMeeting;
use App\Services\Commercial\CommercialAuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class MeetingController extends CommercialController
{
    public function index(): View
    {
        $this->authorizeCommercial('commercial.meetings.view');

        return view('commercial.meetings.index', [
            'page_title' => 'Commercial Meetings | Texaro Technologies Limited',
            'meetings' => CommercialMeeting::where('tenant_id', $this->tenantId())->latest('meeting_date')->paginate(15),
        ]);
    }

    public function create(): View
    {
        $this->authorizeCommercial('commercial.meetings.create');

        return view('commercial.meetings.form', [
            'page_title' => 'Schedule Meeting | Texaro Technologies Limited',
            'meeting' => new CommercialMeeting(['meeting_type' => 'Discovery']),
        ]);
    }

    public function store(StoreMeetingRequest $request, CommercialAuditService $audit): RedirectResponse
    {
        $meeting = CommercialMeeting::create($request->validated() + [
            'tenant_id' => $this->tenantId(),
            'recorded_by' => Auth::id(),
        ]);

        $audit->record($request, 'created', $meeting, 'Created commercial meeting ' . $meeting->title);

        return redirect()->route('commercial.meetings.index')->with('success', 'Meeting scheduled successfully.');
    }
}
