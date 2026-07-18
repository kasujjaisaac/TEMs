@extends('layouts.app')

@section('content')
@include('commercial.partials.style')

<section class="commercial-page">
    <header class="commercial-header">
        <div class="commercial-title">
            <div class="commercial-title-icon"><i class="fa-solid fa-plus"></i></div>
            <div>
                <h1>Create Opportunity</h1>
                <div class="commercial-muted">Open a pipeline record linked to a Commercial Organization.</div>
            </div>
        </div>
        <a class="commercial-button secondary" href="{{ route('commercial.opportunities.index') }}"><i class="fa-solid fa-arrow-left"></i> Back</a>
    </header>

    @if($errors->any()) <div class="commercial-alert error">{{ $errors->first() }}</div> @endif

    <form class="commercial-panel commercial-form" method="POST" action="{{ route('commercial.opportunities.store') }}">
        @csrf
        <div class="commercial-field"><label>Organization</label><select name="organization_id" required>@foreach($organizations as $organization)<option value="{{ $organization->id }}" @selected((string) old('organization_id') === (string) $organization->id)>{{ $organization->legal_name }}</option>@endforeach</select></div>
        <div class="commercial-field"><label>Primary Stakeholder</label><select name="primary_stakeholder_id"><option value="">None</option>@foreach($stakeholders as $stakeholder)<option value="{{ $stakeholder->id }}" @selected((string) old('primary_stakeholder_id') === (string) $stakeholder->id)>{{ $stakeholder->full_name }}</option>@endforeach</select></div>
        <div class="commercial-field"><label>Assigned Employee</label><select name="assigned_employee_id"><option value="">Unassigned</option>@foreach($employees as $employee)<option value="{{ $employee->id }}" @selected((string) old('assigned_employee_id') === (string) $employee->id)>{{ $employee->name }}</option>@endforeach</select></div>
        <div class="commercial-field double"><label>Title</label><input name="title" value="{{ old('title') }}" required></div>
        <div class="commercial-field"><label>Product / Service</label><input name="product_or_service" value="{{ old('product_or_service') }}"></div>
        <div class="commercial-field"><label>Opportunity Type</label><select name="opportunity_type">@foreach(['New Business','Renewal','Upgrade','Cross-sell','Upsell','Expansion','Partnership','Tender','Retainer','Support Contract'] as $type)<option value="{{ $type }}" @selected(old('opportunity_type', 'New Business') === $type)>{{ $type }}</option>@endforeach</select></div>
        <div class="commercial-field"><label>Stage</label><select name="current_stage">@forelse($stages as $stage)<option value="{{ $stage->name }}" @selected(old('current_stage', 'Qualified') === $stage->name)>{{ $stage->name }}</option>@empty<option value="Qualified">Qualified</option>@endforelse</select></div>
        <div class="commercial-field"><label>Probability</label><input type="number" min="0" max="100" name="probability" value="{{ old('probability', 20) }}" required></div>
        <div class="commercial-field"><label>Estimated Value</label><input type="number" step="0.01" min="0.01" name="estimated_value" value="{{ old('estimated_value') }}" required></div>
        <div class="commercial-field"><label>Currency</label><input name="currency" value="{{ old('currency', config('app.currency', 'UGX')) }}" required></div>
        <div class="commercial-field"><label>Expected Close</label><input type="date" name="expected_close_date" value="{{ old('expected_close_date') }}"></div>
        <div class="commercial-field"><label>Revenue Type</label><select name="revenue_type"><option value=""></option>@foreach(['One-time','Recurring','Subscription','Licence','Implementation','Support','Maintenance','Consultancy','Training','Mixed'] as $type)<option value="{{ $type }}" @selected(old('revenue_type') === $type)>{{ $type }}</option>@endforeach</select></div>
        <div class="commercial-field"><label>Next Action</label><input name="next_action" value="{{ old('next_action') }}"></div>
        <div class="commercial-field full"><label>Customer Need</label><textarea name="customer_need">{{ old('customer_need') }}</textarea></div>
        <div class="commercial-field full"><label>Problem Statement</label><textarea name="problem_statement">{{ old('problem_statement') }}</textarea></div>
        <div class="commercial-field full"><label>Proposed Solution</label><textarea name="proposed_solution">{{ old('proposed_solution') }}</textarea></div>
        <div class="commercial-field full"><button class="commercial-button" type="submit"><i class="fa-solid fa-check"></i> Save Opportunity</button></div>
    </form>
</section>
@endsection
