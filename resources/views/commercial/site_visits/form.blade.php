@extends('layouts.app')

@section('content')
@include('commercial.partials.style')

<section class="commercial-page">
    <header class="commercial-header">
        <div class="commercial-title">
            <div class="commercial-title-icon"><i class="fa-solid fa-location-crosshairs"></i></div>
            <div>
                <h1>Record Site Visit</h1>
                <div class="commercial-muted">Capture operational context, technical environment, risks, and recommendations.</div>
            </div>
        </div>
        <a class="commercial-button secondary" href="{{ route('commercial.site_visits.index') }}"><i class="fa-solid fa-arrow-left"></i> Back</a>
    </header>
    @if($errors->any()) <div class="commercial-alert error">{{ $errors->first() }}</div> @endif
    <form class="commercial-panel commercial-form" method="POST" action="{{ route('commercial.site_visits.store') }}">
        @csrf
        <div class="commercial-field"><label>Organization ID</label><input type="number" name="organization_id" value="{{ old('organization_id') }}"></div>
        <div class="commercial-field"><label>Opportunity ID</label><input type="number" name="opportunity_id" value="{{ old('opportunity_id') }}"></div>
        <div class="commercial-field"><label>Visit Date</label><input type="date" name="visit_date" value="{{ old('visit_date', now()->toDateString()) }}" required></div>
        <div class="commercial-field double"><label>Site Location</label><input name="site_location" value="{{ old('site_location') }}" required></div>
        <div class="commercial-field"><label>Purpose</label><input name="visit_purpose" value="{{ old('visit_purpose') }}"></div>
        <div class="commercial-field"><label>Internet Availability</label><input name="internet_availability" value="{{ old('internet_availability') }}"></div>
        <div class="commercial-field"><label>Users</label><input type="number" name="number_of_users" value="{{ old('number_of_users') }}"></div>
        <div class="commercial-field"><label>Branches</label><input type="number" name="number_of_branches" value="{{ old('number_of_branches') }}"></div>
        <div class="commercial-field"><label>Report Status</label><select name="report_status"><option>Draft</option><option>Internal Review</option><option>Completed</option></select></div>
        <div class="commercial-field full"><label>Current Environment</label><textarea name="current_environment">{{ old('current_environment') }}</textarea></div>
        <div class="commercial-field full"><label>Existing Systems</label><textarea name="existing_systems">{{ old('existing_systems') }}</textarea></div>
        <div class="commercial-field full"><label>Technical Infrastructure</label><textarea name="technical_infrastructure">{{ old('technical_infrastructure') }}</textarea></div>
        <div class="commercial-field full"><label>Customer Challenges</label><textarea name="customer_challenges">{{ old('customer_challenges') }}</textarea></div>
        <div class="commercial-field full"><label>Functional Requirements</label><textarea name="functional_requirements">{{ old('functional_requirements') }}</textarea></div>
        <div class="commercial-field full"><label>Technical Requirements</label><textarea name="technical_requirements">{{ old('technical_requirements') }}</textarea></div>
        <div class="commercial-field full"><label>Risks</label><textarea name="risks">{{ old('risks') }}</textarea></div>
        <div class="commercial-field full"><label>Recommendations</label><textarea name="recommendations">{{ old('recommendations') }}</textarea></div>
        <div class="commercial-field full"><button class="commercial-button" type="submit"><i class="fa-solid fa-check"></i> Save Site Visit</button></div>
    </form>
</section>
@endsection
