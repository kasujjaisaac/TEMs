@extends('layouts.app')

@section('content')
@include('commercial.partials.style')

<section class="commercial-page">
    <header class="commercial-header">
        <div class="commercial-title">
            <div class="commercial-title-icon"><i class="fa-solid fa-calendar-plus"></i></div>
            <div>
                <h1>Schedule Meeting</h1>
                <div class="commercial-muted">Record agenda, attendees, decisions, and follow-up actions.</div>
            </div>
        </div>
        <a class="commercial-button secondary" href="{{ route('commercial.meetings.index') }}"><i class="fa-solid fa-arrow-left"></i> Back</a>
    </header>
    @if($errors->any()) <div class="commercial-alert error">{{ $errors->first() }}</div> @endif
    <form class="commercial-panel commercial-form" method="POST" action="{{ route('commercial.meetings.store') }}">
        @csrf
        <div class="commercial-field double"><label>Title</label><input name="title" value="{{ old('title') }}" required></div>
        <div class="commercial-field"><label>Type</label><select name="meeting_type">@foreach(['Discovery','Introductory','Product Demonstration','Negotiation','Pricing Discussion','Contract Discussion','Review','Renewal','Partnership','Internal Commercial Review','Other'] as $type)<option value="{{ $type }}">{{ $type }}</option>@endforeach</select></div>
        <div class="commercial-field"><label>Date</label><input type="date" name="meeting_date" value="{{ old('meeting_date', now()->toDateString()) }}" required></div>
        <div class="commercial-field"><label>Start Time</label><input type="time" name="start_time" value="{{ old('start_time') }}"></div>
        <div class="commercial-field"><label>End Time</label><input type="time" name="end_time" value="{{ old('end_time') }}"></div>
        <div class="commercial-field"><label>Location</label><input name="location" value="{{ old('location') }}"></div>
        <div class="commercial-field"><label>Meeting Link</label><input name="meeting_link" value="{{ old('meeting_link') }}"></div>
        <div class="commercial-field"><label>Lead ID</label><input type="number" name="lead_id" value="{{ old('lead_id') }}"></div>
        <div class="commercial-field"><label>Opportunity ID</label><input type="number" name="opportunity_id" value="{{ old('opportunity_id') }}"></div>
        <div class="commercial-field full"><label>Agenda</label><textarea name="agenda">{{ old('agenda') }}</textarea></div>
        <div class="commercial-field full"><label>Discussion Notes</label><textarea name="discussion_notes">{{ old('discussion_notes') }}</textarea></div>
        <div class="commercial-field full"><label>Action Items</label><textarea name="action_items">{{ old('action_items') }}</textarea></div>
        <div class="commercial-field"><label>Next Meeting Date</label><input type="date" name="next_meeting_date" value="{{ old('next_meeting_date') }}"></div>
        <div class="commercial-field full"><button class="commercial-button" type="submit"><i class="fa-solid fa-check"></i> Save Meeting</button></div>
    </form>
</section>
@endsection
