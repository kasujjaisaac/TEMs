@extends('layouts.app')

@section('content')
@include('planning.partials.style')

<section class="planning-page">
    <header class="planning-header">
        <div class="planning-title"><div class="planning-icon"><i class="fa-solid fa-list-check"></i></div><div><h1>{{ $workplan->title }}</h1><div class="planning-muted">{{ $workplan->code }} - {{ $workplan->level }} - {{ $workplan->planningYear?->name }}</div></div></div>
        <div class="planning-actions">
            <a class="planning-button secondary" href="{{ route('planning.workplans.index') }}"><i class="fa-solid fa-arrow-left"></i> Workplans</a>
            @if($workplan->approval_status !== 'Approved')
                <form method="POST" action="{{ route('planning.workplans.approve', $workplan) }}">@csrf<button class="planning-button" type="submit"><i class="fa-solid fa-lock"></i> Approve Baseline</button></form>
            @endif
        </div>
    </header>

    @if(session('success')) <div class="planning-alert success">{{ session('success') }}</div> @endif

    <section class="planning-grid">
        <div class="planning-card"><span>Approval</span><strong>{{ $workplan->approval_status }}</strong></div>
        <div class="planning-card"><span>Health</span><strong>{{ $workplan->health_status }}</strong></div>
        <div class="planning-card"><span>Targets</span><strong>{{ $items->count() }}</strong></div>
        <div class="planning-card"><span>Owner</span><strong>{{ $workplan->department?->short_name ?: $workplan->owner_name ?: '-' }}</strong></div>
    </section>

    <section class="planning-panel">
        <div class="planning-panel-head"><h2>Create Workplan Target</h2><span class="planning-muted">Phase 1 generates monthly and weekly allocations from dates and target value.</span></div>
        <form class="planning-form" method="POST" action="{{ route('planning.workplans.items.store', $workplan) }}">
            @csrf
            <div class="planning-field"><label>Reference</label><input name="reference" required placeholder="WP-001"></div>
            <div class="planning-field"><label>Target Type</label><select name="target_type"><option>Numeric</option><option>Financial</option><option>Milestone</option><option>Percentage</option><option>Quality</option><option>Binary Compliance</option><option>Composite</option></select></div>
            <div class="planning-field"><label>Target Value</label><input name="target_value" type="number" min="0" step="0.01" required value="1"></div>
            <div class="planning-field"><label>Actual Value</label><input name="actual_value" type="number" min="0" step="0.01" value="0"></div>
            <div class="planning-field double"><label>Title</label><input name="title" required></div>
            <div class="planning-field"><label>KPI</label><input name="kpi"></div>
            <div class="planning-field"><label>Unit</label><input name="unit" placeholder="clients, visits, UGX"></div>
            <div class="planning-field"><label>Priority</label><select name="priority"><option>Critical</option><option>High</option><option selected>Medium</option><option>Low</option></select></div>
            <div class="planning-field"><label>Weight</label><input name="weight" type="number" min="0" max="100" value="10"></div>
            <div class="planning-field"><label>Starts On</label><input name="starts_on" type="date" value="{{ now()->toDateString() }}"></div>
            <div class="planning-field"><label>Due On</label><input name="due_on" type="date" value="{{ now()->addMonth()->toDateString() }}"></div>
            <div class="planning-field"><label>Objective</label><select name="strategic_objective_id"><option value="">None</option>@foreach($objectives as $objective)<option value="{{ $objective->id }}">{{ $objective->code }} - {{ $objective->title }}</option>@endforeach</select></div>
            <div class="planning-field"><label>Budget Line</label><select name="budget_line_id"><option value="">None</option>@foreach($budgetLines as $line)<option value="{{ $line->id }}">{{ $line->reference }}</option>@endforeach</select></div>
            <div class="planning-field"><label>Department</label><select name="department_id"><option value="">Use workplan owner</option>@foreach($departments as $department)<option value="{{ $department->id }}">{{ $department->name }}</option>@endforeach</select></div>
            <div class="planning-field"><label>Position</label><select name="position_id"><option value="">No position</option>@foreach($positions as $position)<option value="{{ $position->id }}">{{ $position->title }}</option>@endforeach</select></div>
            <div class="planning-field"><label>Employee</label><select name="employee_id"><option value="">No employee</option>@foreach($employees as $employee)<option value="{{ $employee->id }}">{{ $employee->name }}</option>@endforeach</select></div>
            <div class="planning-field"><label>Assignment Role</label><select name="assignment_role"><option>Accountable</option><option>Contributor</option><option>Approver</option><option>Consulted</option><option>Informed</option></select></div>
            <div class="planning-field double"><label>Required Evidence</label><input name="required_evidence_type" placeholder="Visit report, invoice, acceptance record"></div>
            <div class="planning-field double"><label>Quality Standard</label><input name="quality_standard"></div>
            <div class="planning-field full"><label>Description</label><textarea name="description"></textarea></div>
            <div class="planning-field full"><button class="planning-button" type="submit"><i class="fa-solid fa-save"></i> Save Target & Generate Allocations</button></div>
        </form>
    </section>

    <section class="planning-panel">
        <div class="planning-panel-head"><h2>Targets & Allocations</h2></div>
        @include('planning.partials.table', [
            'headers' => ['Target', 'Owner', 'Target / Actual', 'Expected Gap', 'Pace', 'Health', 'Evidence'],
            'rows' => $items->map(function ($item) use ($snapshots) {
                $row = $snapshots[$item->id];
                return [
                    '<strong class="planning-table-title">' . e($item->reference) . '</strong><span class="planning-muted">' . e($item->title) . '</span>',
                    e($item->assignments->first()?->department?->name ?: $item->assignments->first()?->position?->title ?: $item->assignments->first()?->employee?->name ?: '-'),
                    e(number_format((float) $item->actual_value, 2) . ' / ' . number_format((float) $item->target_value, 2) . ' ' . ($item->unit ?: '')),
                    e(number_format((float) $row['gap'], 2)),
                    e($row['pace']) . '%',
                    '<span class="planning-badge ' . (in_array($row['health'], ['Ahead','On Track','Completed'], true) ? 'success' : (in_array($row['health'], ['At Risk','Not Started'], true) ? 'warning' : 'danger')) . '">' . e($row['health']) . '</span>',
                    e($item->required_evidence_type ?: '-'),
                ];
            })->all()
        ])
    </section>
</section>
@endsection
