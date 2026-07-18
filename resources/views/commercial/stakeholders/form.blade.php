@extends('layouts.app')

@section('content')
@include('commercial.partials.style')

<section class="commercial-page">
    <header class="commercial-header">
        <div class="commercial-title">
            <div class="commercial-title-icon"><i class="fa-solid fa-user-plus"></i></div>
            <div>
                <h1>{{ $stakeholder->exists ? 'Edit Stakeholder' : 'Create Stakeholder' }}</h1>
                <div class="commercial-muted">Add a customer-side decision participant or contact.</div>
            </div>
        </div>
        <a class="commercial-button secondary" href="{{ route('commercial.stakeholders.index') }}"><i class="fa-solid fa-arrow-left"></i> Back</a>
    </header>

    @if($errors->any()) <div class="commercial-alert error">{{ $errors->first() }}</div> @endif

    <form class="commercial-panel commercial-form" method="POST" action="{{ $stakeholder->exists ? route('commercial.stakeholders.update', $stakeholder) : route('commercial.stakeholders.store') }}">
        @csrf
        @if($stakeholder->exists) @method('PUT') @endif
        <div class="commercial-field"><label>Organization</label><select name="organization_id" required>@foreach($organizations as $organization)<option value="{{ $organization->id }}" @selected((string) request('organization_id', old('organization_id', $stakeholder->organization_id)) === (string) $organization->id)>{{ $organization->legal_name }}</option>@endforeach</select></div>
        <div class="commercial-field"><label>Full Name</label><input name="full_name" value="{{ old('full_name', $stakeholder->full_name) }}" required></div>
        <div class="commercial-field"><label>Position</label><input name="position_title" value="{{ old('position_title', $stakeholder->position_title) }}"></div>
        <div class="commercial-field"><label>Department</label><input name="department" value="{{ old('department', $stakeholder->department) }}"></div>
        <div class="commercial-field"><label>Email</label><input type="email" name="email" value="{{ old('email', $stakeholder->email) }}"></div>
        <div class="commercial-field"><label>Telephone</label><input name="telephone" value="{{ old('telephone', $stakeholder->telephone) }}"></div>
        <div class="commercial-field"><label>Decision Role</label><select name="decision_role">@foreach(['Decision Maker','Influencer','Champion','Gatekeeper','Technical Evaluator','Finance Contact','Procurement Contact','Legal Contact','User','Contract Signatory','Other'] as $role)<option value="{{ $role }}" @selected(old('decision_role', $stakeholder->decision_role) === $role)>{{ $role }}</option>@endforeach</select></div>
        <div class="commercial-field"><label>Influence</label><select name="influence_level"><option></option>@foreach(['Low','Medium','High','Critical'] as $level)<option value="{{ $level }}" @selected(old('influence_level', $stakeholder->influence_level) === $level)>{{ $level }}</option>@endforeach</select></div>
        <div class="commercial-field"><label>Interest</label><select name="interest_level"><option></option>@foreach(['Low','Medium','High'] as $level)<option value="{{ $level }}" @selected(old('interest_level', $stakeholder->interest_level) === $level)>{{ $level }}</option>@endforeach</select></div>
        <div class="commercial-field"><label>Primary Contact</label><select name="is_primary_contact"><option value="0" @selected(! old('is_primary_contact', $stakeholder->is_primary_contact))>No</option><option value="1" @selected(old('is_primary_contact', $stakeholder->is_primary_contact))>Yes</option></select></div>
        <div class="commercial-field"><label>Billing Contact</label><select name="is_billing_contact"><option value="0" @selected(! old('is_billing_contact', $stakeholder->is_billing_contact))>No</option><option value="1" @selected(old('is_billing_contact', $stakeholder->is_billing_contact))>Yes</option></select></div>
        <div class="commercial-field"><label>Signatory</label><select name="is_contract_signatory"><option value="0" @selected(! old('is_contract_signatory', $stakeholder->is_contract_signatory))>No</option><option value="1" @selected(old('is_contract_signatory', $stakeholder->is_contract_signatory))>Yes</option></select></div>
        <div class="commercial-field full"><label>Notes</label><textarea name="notes">{{ old('notes', $stakeholder->notes) }}</textarea></div>
        <div class="commercial-field full"><button class="commercial-button" type="submit"><i class="fa-solid fa-check"></i> {{ $stakeholder->exists ? 'Update Stakeholder' : 'Save Stakeholder' }}</button></div>
    </form>
</section>
@endsection
