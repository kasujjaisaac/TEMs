@extends('layouts.app')

@section('content')
@include('commercial.partials.style')

<section class="commercial-page">
    <header class="commercial-header">
        <div class="commercial-title">
            <div class="commercial-title-icon"><i class="fa-solid fa-building-circle-check"></i></div>
            <div>
                <h1>{{ $organization->exists ? 'Edit Organization' : 'Create Organization' }}</h1>
                <div class="commercial-muted">Create a controlled commercial customer/prospect profile.</div>
            </div>
        </div>
        <a class="commercial-button secondary" href="{{ route('commercial.organizations.index') }}"><i class="fa-solid fa-arrow-left"></i> Back</a>
    </header>

    @if($errors->any()) <div class="commercial-alert error">{{ $errors->first() }}</div> @endif

    <form class="commercial-panel commercial-form" method="POST" action="{{ $organization->exists ? route('commercial.organizations.update', $organization) : route('commercial.organizations.store') }}">
        @csrf
        @if($organization->exists) @method('PUT') @endif
        <div class="commercial-field"><label>Legal Name</label><input name="legal_name" value="{{ old('legal_name', $organization->legal_name) }}" required></div>
        <div class="commercial-field"><label>Trading Name</label><input name="trading_name" value="{{ old('trading_name', $organization->trading_name) }}"></div>
        <div class="commercial-field"><label>Legacy Customer ID</label><input type="number" name="legacy_customer_id" value="{{ old('legacy_customer_id', $organization->legacy_customer_id) }}"></div>
        <div class="commercial-field"><label>Organization Type</label><input name="organization_type" value="{{ old('organization_type', $organization->organization_type) }}"></div>
        <div class="commercial-field"><label>Customer Category</label><input name="customer_category" value="{{ old('customer_category', $organization->customer_category) }}"></div>
        <div class="commercial-field"><label>Status</label><select name="customer_status">@foreach(['Prospect','Qualified Prospect','Active Customer','Inactive Customer','Suspended','Former Customer','Partner','Vendor'] as $status)<option value="{{ $status }}" @selected(old('customer_status', $organization->customer_status ?: 'Prospect') === $status)>{{ $status }}</option>@endforeach</select></div>
        <div class="commercial-field"><label>Industry</label><input name="industry" value="{{ old('industry', $organization->industry) }}"></div>
        <div class="commercial-field"><label>Sector</label><input name="sector" value="{{ old('sector', $organization->sector) }}"></div>
        <div class="commercial-field"><label>TIN</label><input name="tin" value="{{ old('tin', $organization->tin) }}"></div>
        <div class="commercial-field"><label>Primary Email</label><input type="email" name="primary_email" value="{{ old('primary_email', $organization->primary_email) }}"></div>
        <div class="commercial-field"><label>Primary Telephone</label><input name="primary_telephone" value="{{ old('primary_telephone', $organization->primary_telephone) }}"></div>
        <div class="commercial-field"><label>Account Manager</label><select name="account_manager_id"><option value="">Unassigned</option>@foreach($employees as $employee)<option value="{{ $employee->id }}" @selected((string) old('account_manager_id', $organization->account_manager_id) === (string) $employee->id)>{{ $employee->name }}</option>@endforeach</select></div>
        <div class="commercial-field"><label>Country</label><input name="country" value="{{ old('country', $organization->country ?: 'Uganda') }}"></div>
        <div class="commercial-field"><label>District</label><input name="district" value="{{ old('district', $organization->district) }}"></div>
        <div class="commercial-field"><label>City</label><input name="city" value="{{ old('city', $organization->city) }}"></div>
        <div class="commercial-field"><label>Payment Terms</label><input name="payment_terms" value="{{ old('payment_terms', $organization->payment_terms) }}"></div>
        <div class="commercial-field"><label>Credit Status</label><input name="credit_status" value="{{ old('credit_status', $organization->credit_status) }}"></div>
        <div class="commercial-field"><label>Relationship Score</label><input type="number" min="0" max="100" name="relationship_score" value="{{ old('relationship_score', $organization->relationship_score) }}"></div>
        <div class="commercial-field full"><label>Physical Address</label><textarea name="physical_address">{{ old('physical_address', $organization->physical_address) }}</textarea></div>
        <div class="commercial-field full"><label>Notes</label><textarea name="notes">{{ old('notes', $organization->notes) }}</textarea></div>
        <div class="commercial-field full"><button class="commercial-button" type="submit"><i class="fa-solid fa-check"></i> {{ $organization->exists ? 'Update Organization' : 'Save Organization' }}</button></div>
    </form>
</section>
@endsection
