@extends('layouts.app')

@section('content')
@include('hr.partials.style')

<section class="hr-core">
    <header class="hr-core-header">
        <div class="hr-core-title">
            <div class="hr-core-icon"><i class="fa-solid fa-sitemap"></i></div>
            <div>
                <h1>{{ $department->exists ? 'Edit Department' : 'Create Department' }}</h1>
                <div class="hr-core-muted">Define the mandate, lifecycle status, and reporting placement for this structure node.</div>
            </div>
        </div>
        <a class="hr-core-button secondary" href="{{ route('hr.departments.index') }}"><i class="fa-solid fa-arrow-left"></i> Back</a>
    </header>

    @if($errors->any()) <div class="hr-core-alert error">{{ $errors->first() }}</div> @endif

    <form class="hr-core-panel hr-core-form" method="POST" action="{{ $department->exists ? route('hr.departments.update', $department) : route('hr.departments.store') }}">
        @csrf
        @if($department->exists) @method('PUT') @endif
        <div class="hr-core-field"><label>Code</label><input name="code" value="{{ old('code', $department->code) }}" required></div>
        <div class="hr-core-field"><label>Name</label><input name="name" value="{{ old('name', $department->name) }}" required></div>
        <div class="hr-core-field"><label>Short Name</label><input name="short_name" value="{{ old('short_name', $department->short_name) }}"></div>
        <div class="hr-core-field"><label>Type</label><select name="type">@foreach(['Department','Unit','Team','Governance'] as $type)<option value="{{ $type }}" @selected(old('type', $department->type ?: 'Department') === $type)>{{ $type }}</option>@endforeach</select></div>
        <div class="hr-core-field"><label>Parent</label><select name="parent_id"><option value="">None</option>@foreach($parents as $parent)<option value="{{ $parent->id }}" @selected((string) old('parent_id', $department->parent_id) === (string) $parent->id)>{{ $parent->name }}</option>@endforeach</select></div>
        <div class="hr-core-field"><label>Status</label><select name="status">@foreach(['Proposed','Reviewed','Approved','Active','Restructured','Inactive','Archived'] as $status)<option value="{{ $status }}" @selected(old('status', $department->status ?: 'Proposed') === $status)>{{ $status }}</option>@endforeach</select></div>
        <div class="hr-core-field"><label>Cost Centre</label><input name="cost_centre" value="{{ old('cost_centre', $department->cost_centre) }}"></div>
        <div class="hr-core-field"><label>Effective From</label><input type="date" name="effective_from" value="{{ old('effective_from', $department->effective_from?->format('Y-m-d')) }}"></div>
        <div class="hr-core-field"><label>Review Date</label><input type="date" name="review_date" value="{{ old('review_date', $department->review_date?->format('Y-m-d')) }}"></div>
        <div class="hr-core-field full"><label>Description</label><textarea name="description">{{ old('description', $department->description) }}</textarea></div>
        <div class="hr-core-field full"><label>Mandate</label><textarea name="mandate">{{ old('mandate', $department->mandate) }}</textarea></div>
        <div class="hr-core-field full"><label>Responsibilities</label><textarea name="responsibilities">{{ old('responsibilities', $department->responsibilities) }}</textarea></div>
        <div class="hr-core-field full"><button class="hr-core-button" type="submit"><i class="fa-solid fa-check"></i> {{ $department->exists ? 'Update Department' : 'Save Department' }}</button></div>
    </form>
</section>
@endsection
