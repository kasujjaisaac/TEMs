@extends('layouts.app')

@section('content')
@include('commercial.partials.style')

<section class="commercial-page">
    <header class="commercial-header">
        <div class="commercial-title">
            <div class="commercial-title-icon"><i class="fa-solid fa-list-check"></i></div>
            <div>
                <h1>Record Activity</h1>
                <div class="commercial-muted">Attach activity to a lead, organization, opportunity, or stakeholder record.</div>
            </div>
        </div>
        <a class="commercial-button secondary" href="{{ route('commercial.activities.index') }}"><i class="fa-solid fa-arrow-left"></i> Back</a>
    </header>
    @if($errors->any()) <div class="commercial-alert error">{{ $errors->first() }}</div> @endif
    <form class="commercial-panel commercial-form" method="POST" action="{{ route('commercial.activities.store') }}">
        @csrf
        <div class="commercial-field"><label>Activity Type</label><select name="activity_type">@foreach(['Call','Email','Note','Follow-up','Task','Internal Review'] as $type)<option value="{{ $type }}">{{ $type }}</option>@endforeach</select></div>
        <div class="commercial-field"><label>Related Type</label><select name="related_type"><option value="App\Models\Commercial\CommercialLead" @selected(old('related_type', request('related_type')) === App\Models\Commercial\CommercialLead::class)>Lead</option><option value="App\Models\Commercial\CommercialOrganization" @selected(old('related_type', request('related_type')) === App\Models\Commercial\CommercialOrganization::class)>Organization</option><option value="App\Models\Commercial\CommercialOpportunity" @selected(old('related_type', request('related_type')) === App\Models\Commercial\CommercialOpportunity::class)>Opportunity</option><option value="App\Models\Commercial\CommercialStakeholder" @selected(old('related_type', request('related_type')) === App\Models\Commercial\CommercialStakeholder::class)>Stakeholder</option></select></div>
        <div class="commercial-field"><label>Related ID</label><input type="number" name="related_id" value="{{ old('related_id', request('related_id')) }}" required></div>
        <div class="commercial-field"><label>Activity Date</label><input type="date" name="activity_date" value="{{ old('activity_date', now()->toDateString()) }}"></div>
        <div class="commercial-field"><label>Activity Time</label><input type="time" name="activity_time" value="{{ old('activity_time') }}"></div>
        <div class="commercial-field"><label>Status</label><select name="completion_status"><option>Open</option><option>Completed</option><option>Deferred</option></select></div>
        <div class="commercial-field full"><label>Description</label><textarea name="description" required>{{ old('description') }}</textarea></div>
        <div class="commercial-field full"><label>Outcome</label><textarea name="outcome">{{ old('outcome') }}</textarea></div>
        <div class="commercial-field"><label>Next Action</label><input name="next_action" value="{{ old('next_action') }}"></div>
        <div class="commercial-field"><label>Next Action Date</label><input type="date" name="next_action_date" value="{{ old('next_action_date') }}"></div>
        <div class="commercial-field full"><button class="commercial-button" type="submit"><i class="fa-solid fa-check"></i> Save Activity</button></div>
    </form>
</section>
@endsection
