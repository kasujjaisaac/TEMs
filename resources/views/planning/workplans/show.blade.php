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
    @if($errors->any()) <div class="planning-alert danger">{{ $errors->first() }}</div> @endif

    <section class="planning-grid">
        <div class="planning-card"><span>Approval</span><strong>{{ $workplan->approval_status }}</strong></div>
        <div class="planning-card"><span>Health</span><strong>{{ $workplan->health_status }}</strong></div>
        <div class="planning-card"><span>Targets</span><strong>{{ $items->count() }}</strong></div>
        <div class="planning-card"><span>Owner</span><strong>{{ $workplan->department?->short_name ?: $workplan->owner_name ?: '-' }}</strong></div>
    </section>

    <section class="planning-panel">
        <div class="planning-panel-head"><h2>Create Workplan Target</h2><span class="planning-muted">Phase 2 links each target to verified evidence and recovery actions.</span></div>
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
            'headers' => ['Target', 'Owner', 'Target / Verified', 'Expected Gap', 'Pace', 'Health', 'Evidence'],
            'rows' => $items->map(function ($item) use ($snapshots) {
                $row = $snapshots[$item->id];
                $submitted = $item->evidence->where('status', 'Submitted')->count();
                $verified = $item->evidence->where('status', 'Verified')->count();
                return [
                    '<strong class="planning-table-title">' . e($item->reference) . '</strong><span class="planning-muted">' . e($item->title) . '</span>',
                    e($item->assignments->first()?->department?->name ?: $item->assignments->first()?->position?->title ?: $item->assignments->first()?->employee?->name ?: '-'),
                    e(number_format((float) $item->actual_value, 2) . ' / ' . number_format((float) $item->target_value, 2) . ' ' . ($item->unit ?: '')),
                    e(number_format((float) $row['gap'], 2)),
                    e($row['pace']) . '%',
                    '<span class="planning-badge ' . (in_array($row['health'], ['Ahead','On Track','Completed'], true) ? 'success' : (in_array($row['health'], ['At Risk','Not Started'], true) ? 'warning' : 'danger')) . '">' . e($row['health']) . '</span>',
                    '<span class="planning-badge success">' . e($verified) . ' verified</span> <span class="planning-badge warning">' . e($submitted) . ' pending</span>',
                ];
            })->all()
        ])
    </section>

    <section class="planning-split">
        <div class="planning-panel">
            <div class="planning-panel-head"><h2>Submit Evidence</h2><span class="planning-muted">Official progress is recalculated from verified values.</span></div>
            <form class="planning-form" method="POST" action="{{ $items->first() ? route('planning.workplan_items.evidence.store', $items->first()) : '#' }}" id="evidence-form">
                @csrf
                <div class="planning-field double"><label>Target</label><select name="_target" onchange="document.getElementById('evidence-form').action=this.value" required>@foreach($items as $item)<option value="{{ route('planning.workplan_items.evidence.store', $item) }}">{{ $item->reference }} - {{ $item->title }}</option>@endforeach</select></div>
                <div class="planning-field"><label>Evidence Type</label><input name="evidence_type" required placeholder="Visit report, invoice, acceptance"></div>
                <div class="planning-field"><label>Claimed Value</label><input name="claimed_value" type="number" min="0" step="0.01" required value="1"></div>
                <div class="planning-field double"><label>Title</label><input name="title" required></div>
                <div class="planning-field"><label>Source Module</label><input name="source_module" placeholder="Commercial, Finance, Projects"></div>
                <div class="planning-field"><label>Source Reference</label><input name="source_reference" placeholder="Invoice, visit, milestone no."></div>
                <div class="planning-field full"><label>Description</label><textarea name="description"></textarea></div>
                <div class="planning-field full"><button class="planning-button" type="submit" @disabled($items->isEmpty())><i class="fa-solid fa-file-circle-check"></i> Submit Evidence</button></div>
            </form>
        </div>

        <div class="planning-panel">
            <div class="planning-panel-head"><h2>Create Recovery Action</h2><span class="planning-muted">Use for at-risk, behind, blocked or missed targets.</span></div>
            <form class="planning-form" method="POST" action="{{ $items->first() ? route('planning.workplan_items.corrective_actions.store', $items->first()) : '#' }}" id="recovery-form">
                @csrf
                <div class="planning-field double"><label>Target</label><select name="_target" onchange="document.getElementById('recovery-form').action=this.value" required>@foreach($items as $item)<option value="{{ route('planning.workplan_items.corrective_actions.store', $item) }}">{{ $item->reference }} - {{ $item->title }}</option>@endforeach</select></div>
                <div class="planning-field"><label>Owner</label><select name="owner_id"><option value="">Unassigned</option>@foreach($employees as $employee)<option value="{{ $employee->id }}">{{ $employee->name }}</option>@endforeach</select></div>
                <div class="planning-field"><label>Severity</label><select name="severity"><option>Medium</option><option>High</option><option>Critical</option><option>Low</option></select></div>
                <div class="planning-field double"><label>Title</label><input name="title" required></div>
                <div class="planning-field"><label>Status</label><select name="status"><option>Open</option><option>In Progress</option><option>Completed</option><option>Cancelled</option></select></div>
                <div class="planning-field"><label>Due On</label><input name="due_on" type="date" value="{{ now()->addWeek()->toDateString() }}"></div>
                <div class="planning-field full"><label>Root Cause</label><textarea name="root_cause"></textarea></div>
                <div class="planning-field full"><label>Recovery Plan</label><textarea name="recovery_plan" required></textarea></div>
                <div class="planning-field full"><button class="planning-button" type="submit" @disabled($items->isEmpty())><i class="fa-solid fa-route"></i> Save Recovery Action</button></div>
            </form>
        </div>
    </section>

    <section class="planning-panel">
        <div class="planning-panel-head"><h2>Evidence Verification Queue</h2></div>
        @include('planning.partials.table', [
            'headers' => ['Evidence', 'Target', 'Claimed', 'Status', 'Submitted By', 'Review'],
            'rows' => $items->flatMap->evidence->sortByDesc('created_at')->map(function ($evidence) {
                return [
                    '<strong class="planning-table-title">' . e($evidence->title) . '</strong><span class="planning-muted">' . e($evidence->evidence_type) . '</span>',
                    e($evidence->item?->reference ?: '-'),
                    e(number_format((float) $evidence->claimed_value, 2)),
                    '<span class="planning-badge ' . ($evidence->status === 'Verified' ? 'success' : ($evidence->status === 'Rejected' ? 'danger' : 'warning')) . '">' . e($evidence->status) . '</span>',
                    e($evidence->submitter?->name ?: '-'),
                    $evidence->status === 'Submitted'
                        ? '<form class="planning-inline-form" method="POST" action="' . route('planning.evidence.review', $evidence) . '">' . csrf_field() . '<input type="hidden" name="decision" value="Approved"><input name="verified_value" type="number" min="0" step="0.01" value="' . e($evidence->claimed_value) . '"><input name="notes" placeholder="Review notes"><button class="planning-button" type="submit">Verify</button></form><form class="planning-inline-form" method="POST" action="' . route('planning.evidence.review', $evidence) . '">' . csrf_field() . '<input type="hidden" name="decision" value="Rejected"><input type="hidden" name="verified_value" value="0"><input name="notes" placeholder="Reason"><button class="planning-button secondary" type="submit">Reject</button></form>'
                        : e($evidence->review_notes ?: '-'),
                ];
            })->values()->all()
        ])
    </section>

    <section class="planning-panel">
        <div class="planning-panel-head"><h2>Corrective Actions</h2></div>
        @include('planning.partials.table', [
            'headers' => ['Action', 'Target', 'Owner', 'Due', 'Severity', 'Status'],
            'rows' => $items->flatMap->correctiveActions->sortByDesc('created_at')->map(fn ($action) => [
                '<strong class="planning-table-title">' . e($action->title) . '</strong><span class="planning-muted">' . e($action->recovery_plan) . '</span>',
                e($action->item?->reference ?: '-'),
                e($action->owner?->name ?: '-'),
                e($action->due_on?->toFormattedDateString() ?: '-'),
                '<span class="planning-badge ' . (in_array($action->severity, ['Critical','High'], true) ? 'danger' : 'warning') . '">' . e($action->severity) . '</span>',
                '<span class="planning-badge ' . ($action->status === 'Completed' ? 'success' : 'warning') . '">' . e($action->status) . '</span>',
            ])->values()->all()
        ])
    </section>
</section>
@endsection
