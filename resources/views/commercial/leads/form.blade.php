@extends('layouts.app')

@section('content')
@include('commercial.partials.style')

<section class="commercial-page">
    <header class="commercial-header">
        <div class="commercial-title">
            <div class="commercial-title-icon"><i class="fa-solid fa-plus"></i></div>
            <div>
                <h1>{{ $lead->exists ? 'Edit Lead' : 'Create Lead' }}</h1>
                <div class="commercial-muted">Record the first commercial signal with enough context for qualification and conversion.</div>
            </div>
        </div>
        <a class="commercial-button secondary" href="{{ route('commercial.leads.index') }}"><i class="fa-solid fa-arrow-left"></i> Back</a>
    </header>

    @if($errors->any()) <div class="commercial-alert error">{{ $errors->first() }}</div> @endif

    <form class="commercial-panel commercial-form" method="POST" action="{{ $lead->exists ? route('commercial.leads.update', $lead) : route('commercial.leads.store') }}">
        @csrf
        @if($lead->exists) @method('PUT') @endif
        <div class="commercial-field"><label>Organization</label><input name="organization_name" value="{{ old('organization_name', $lead->organization_name) }}" required></div>
        <div class="commercial-field"><label>Contact Person</label><input name="contact_person" value="{{ old('contact_person', $lead->contact_person) }}"></div>
        <div class="commercial-field"><label>Telephone</label><input name="telephone" value="{{ old('telephone', $lead->telephone) }}"></div>
        <div class="commercial-field"><label>Email</label><input type="email" name="email" value="{{ old('email', $lead->email) }}"></div>
        <div class="commercial-field"><label>Location</label><input name="location" value="{{ old('location', $lead->location) }}"></div>
        <div class="commercial-field"><label>District</label><input name="district" value="{{ old('district', $lead->district) }}"></div>
        <div class="commercial-field"><label>Country</label><input name="country" value="{{ old('country', $lead->country ?: 'Uganda') }}"></div>
        <div class="commercial-field"><label>Industry</label><input name="industry" value="{{ old('industry', $lead->industry) }}"></div>
        <div class="commercial-field"><label>Sector</label><input name="sector" value="{{ old('sector', $lead->sector) }}"></div>
        <div class="commercial-field"><label>Campaign</label><select name="campaign_id"><option value="">None</option>@foreach($campaigns as $campaign)<option value="{{ $campaign->id }}" @selected((string) old('campaign_id', $lead->campaign_id) === (string) $campaign->id)>{{ $campaign->reference }} - {{ $campaign->name }}</option>@endforeach</select></div>
        <div class="commercial-field"><label>Lead Source</label><select name="lead_source">@foreach(['Website','Referral','Social Media','Campaign','Walk-in','Telephone','Email','Event','Partnership','Existing Customer','Direct Prospecting','Tender','Other'] as $source)<option value="{{ $source }}" @selected(old('lead_source', $lead->lead_source) === $source)>{{ $source }}</option>@endforeach</select></div>
        <div class="commercial-field"><label>Interested Product</label><input name="interested_product" value="{{ old('interested_product', $lead->interested_product) }}"></div>
        <div class="commercial-field"><label>Interested Service</label><input name="interested_service" value="{{ old('interested_service', $lead->interested_service) }}"></div>
        <div class="commercial-field"><label>Estimated Budget</label><input type="number" step="0.01" min="0" name="estimated_budget" value="{{ old('estimated_budget', $lead->estimated_budget) }}"></div>
        <div class="commercial-field"><label>Expected Decision Date</label><input type="date" name="expected_decision_date" value="{{ old('expected_decision_date', $lead->expected_decision_date?->format('Y-m-d')) }}"></div>
        <div class="commercial-field"><label>Assigned Employee</label><select name="assigned_employee_id"><option value="">Unassigned</option>@foreach($employees as $employee)<option value="{{ $employee->id }}" @selected((string) old('assigned_employee_id', $lead->assigned_employee_id) === (string) $employee->id)>{{ $employee->name }}</option>@endforeach</select></div>
        <div class="commercial-field"><label>Temperature</label><select name="temperature">@foreach(['Cold','Warm','Hot'] as $temperature)<option value="{{ $temperature }}" @selected(old('temperature', $lead->temperature ?: 'Warm') === $temperature)>{{ $temperature }}</option>@endforeach</select></div>
        <div class="commercial-field"><label>Lead Score</label><input type="number" min="0" max="100" name="lead_score" value="{{ old('lead_score', $lead->lead_score ?? 0) }}"></div>
        <div class="commercial-field"><label>Status</label><select name="status">@foreach(['New','Contacted','Engaged','Qualified','Unqualified','Nurturing','Converted','Lost','Archived'] as $status)<option value="{{ $status }}" @selected(old('status', $lead->status ?: 'New') === $status)>{{ $status }}</option>@endforeach</select></div>
        <div class="commercial-field"><label>Next Action</label><input name="next_action" value="{{ old('next_action', $lead->next_action) }}"></div>
        <div class="commercial-field"><label>Next Follow-up</label><input type="date" name="next_follow_up_date" value="{{ old('next_follow_up_date', $lead->next_follow_up_date?->format('Y-m-d')) }}"></div>
        <div class="commercial-field full"><label>Description</label><textarea name="description">{{ old('description', $lead->description) }}</textarea></div>
        <div class="commercial-field full"><label>Pain Points</label><textarea name="pain_points">{{ old('pain_points', $lead->pain_points) }}</textarea></div>
        <div class="commercial-field full"><label>Requirements Summary</label><textarea name="requirements_summary">{{ old('requirements_summary', $lead->requirements_summary) }}</textarea></div>
        <div class="commercial-field full"><button class="commercial-button" type="submit"><i class="fa-solid fa-check"></i> {{ $lead->exists ? 'Update Lead' : 'Save Lead' }}</button></div>
    </form>
</section>
@endsection
