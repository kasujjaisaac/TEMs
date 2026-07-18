@extends('layouts.app')

@section('content')
@include('hr.partials.style')

<section class="hr-core">
    <header class="hr-core-header">
        <div class="hr-core-title">
            <div class="hr-core-icon"><i class="fa-solid fa-id-badge"></i></div>
            <div>
                <h1>{{ $position->exists ? 'Edit Position' : 'Create Position' }}</h1>
                <div class="hr-core-muted">Define reporting, authority, responsibilities, KPIs, competencies, and headcount.</div>
            </div>
        </div>
        <a class="hr-core-button secondary" href="{{ route('hr.positions.index') }}"><i class="fa-solid fa-arrow-left"></i> Back</a>
    </header>

    @if($errors->any()) <div class="hr-core-alert error">{{ $errors->first() }}</div> @endif

    <form class="hr-core-panel hr-core-form" method="POST" action="{{ $position->exists ? route('hr.positions.update', $position) : route('hr.positions.store') }}">
        @csrf
        @if($position->exists) @method('PUT') @endif
        <div class="hr-core-field"><label>Code</label><input name="code" value="{{ old('code', $position->code) }}" required></div>
        <div class="hr-core-field double"><label>Title</label><input name="title" value="{{ old('title', $position->title) }}" required></div>
        <div class="hr-core-field"><label>Department</label><select name="department_id" required>@foreach($departments as $department)<option value="{{ $department->id }}" @selected((string) request('department_id', old('department_id', $position->department_id)) === (string) $department->id)>{{ $department->name }}</option>@endforeach</select></div>
        <div class="hr-core-field"><label>Reports To</label><select name="reports_to_position_id"><option value="">No position</option>@foreach($positions as $reportingPosition)<option value="{{ $reportingPosition->id }}" @selected((string) old('reports_to_position_id', $position->reports_to_position_id) === (string) $reportingPosition->id)>{{ $reportingPosition->title }}</option>@endforeach</select></div>
        <div class="hr-core-field"><label>Status</label><select name="position_status">@foreach(['Planned','Approved','Occupied','Vacant','Frozen','Acting','Inactive','Archived'] as $status)<option value="{{ $status }}" @selected(old('position_status', $position->position_status ?: 'Planned') === $status)>{{ $status }}</option>@endforeach</select></div>
        <div class="hr-core-field"><label>Job Family</label><input name="job_family" value="{{ old('job_family', $position->job_family) }}"></div>
        <div class="hr-core-field"><label>Grade</label><input name="grade" value="{{ old('grade', $position->grade) }}"></div>
        <div class="hr-core-field"><label>Level</label><input name="level" value="{{ old('level', $position->level) }}"></div>
        <div class="hr-core-field"><label>Employment Type</label><select name="employment_type">@foreach(['Full time','Fixed-term','Part time','Consultant','Intern','Volunteer'] as $type)<option value="{{ $type }}" @selected(old('employment_type', $position->employment_type ?: 'Full time') === $type)>{{ $type }}</option>@endforeach</select></div>
        <div class="hr-core-field"><label>Work Location</label><input name="work_location" value="{{ old('work_location', $position->work_location) }}"></div>
        <div class="hr-core-field"><label>Approval Limit</label><input type="number" step="0.01" min="0" name="approval_limit" value="{{ old('approval_limit', $position->approval_limit) }}"></div>
        <div class="hr-core-field"><label>Approved Headcount</label><input type="number" min="0" name="approved_headcount" value="{{ old('approved_headcount', $position->approved_headcount ?? 1) }}" required></div>
        <div class="hr-core-field"><label>Filled Headcount</label><input type="number" min="0" name="filled_headcount" value="{{ old('filled_headcount', $position->filled_headcount ?? 0) }}" required></div>
        <div class="hr-core-field"><label>Effective From</label><input type="date" name="effective_from" value="{{ old('effective_from', $position->effective_from?->format('Y-m-d')) }}"></div>
        <div class="hr-core-field"><label>Effective To</label><input type="date" name="effective_to" value="{{ old('effective_to', $position->effective_to?->format('Y-m-d')) }}"></div>
        <div class="hr-core-field full"><label>Job Purpose</label><textarea name="job_purpose">{{ old('job_purpose', $position->job_purpose) }}</textarea></div>
        <div class="hr-core-field full"><label>Key Responsibilities</label><textarea name="key_responsibilities">{{ old('key_responsibilities', $position->key_responsibilities) }}</textarea></div>
        <div class="hr-core-field full"><label>Standard KPIs</label><textarea name="standard_kpis">{{ old('standard_kpis', $position->standard_kpis) }}</textarea></div>
        <div class="hr-core-field full"><label>Competencies</label><textarea name="competencies">{{ old('competencies', $position->competencies) }}</textarea></div>
        <div class="hr-core-field full"><label>Decision Rights</label><textarea name="decision_rights">{{ old('decision_rights', $position->decision_rights) }}</textarea></div>
        <div class="hr-core-field full"><button class="hr-core-button" type="submit"><i class="fa-solid fa-check"></i> {{ $position->exists ? 'Update Position' : 'Save Position' }}</button></div>
    </form>
</section>
@endsection
