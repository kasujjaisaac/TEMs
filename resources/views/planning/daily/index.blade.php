@extends('layouts.app')

@section('content')
@include('planning.partials.style')

@php
    $badge = fn (string $status): string => in_array($status, ['Completed', 'Verified', 'Approved'], true) ? 'success' : (in_array($status, ['Blocked', 'Returned', 'Rejected'], true) ? 'danger' : 'warning');
@endphp

<section class="planning-page">
    <header class="planning-header">
        <div class="planning-title">
            <div class="planning-icon"><i class="fa-solid fa-calendar-day"></i></div>
            <div>
                <h1>My Daily Workspace</h1>
                <div class="planning-muted">Assigned tasks, evidence, blockers, supervisor review, and employee scorecard.</div>
            </div>
        </div>
        <div class="planning-actions">
            <a class="planning-button secondary" href="{{ route('planning.dashboard') }}"><i class="fa-solid fa-chart-line"></i> Planning</a>
            <a class="planning-button secondary" href="{{ route('planning.workplans.index') }}"><i class="fa-solid fa-list-check"></i> Workplans</a>
        </div>
    </header>

    @if(session('success')) <div class="planning-alert success">{{ session('success') }}</div> @endif
    @if($errors->any()) <div class="planning-alert danger">{{ $errors->first() }}</div> @endif

    <section class="planning-grid">
        <div class="planning-card"><span>Today</span><strong>{{ $metrics['today'] }}</strong></div>
        <div class="planning-card"><span>Open</span><strong>{{ $metrics['open'] }}</strong></div>
        <div class="planning-card"><span>Blocked</span><strong>{{ $metrics['blocked'] }}</strong></div>
        <div class="planning-card"><span>Submitted</span><strong>{{ $metrics['submitted'] }}</strong></div>
        <div class="planning-card"><span>Execution</span><strong>{{ $scorecard['execution'] }}%</strong></div>
        <div class="planning-card"><span>Verified</span><strong>{{ $scorecard['verified'] }}%</strong></div>
        <div class="planning-card"><span>Total Tasks</span><strong>{{ $scorecard['total'] }}</strong></div>
        <div class="planning-card"><span>Review Queue</span><strong>{{ $metrics['supervisor_queue'] }}</strong></div>
    </section>

    <section class="planning-panel">
        <div class="planning-panel-head"><h2>Today & Open Tasks</h2><span class="planning-muted">Update status during the day; submit evidence when work is ready for verification.</span></div>
        @include('planning.partials.table', [
            'headers' => ['Task', 'Target', 'Due', 'Status', 'Progress', 'Update', 'Evidence'],
            'rows' => $myTasks->map(function ($task) use ($badge) {
                $target = $task->item ? '<strong class="planning-table-title">' . e($task->item->reference) . '</strong><span class="planning-muted">' . e($task->item->kpi ?: $task->item->unit ?: '-') . '</span>' : '<span class="planning-muted">Manual task</span>';
                $update = '<form class="planning-inline-form" method="POST" action="' . route('planning.daily.tasks.update', $task) . '">' . csrf_field() . method_field('PATCH')
                    . '<select name="status"><option' . ($task->status === 'Not Started' ? ' selected' : '') . '>Not Started</option><option' . ($task->status === 'In Progress' ? ' selected' : '') . '>In Progress</option><option' . ($task->status === 'Blocked' ? ' selected' : '') . '>Blocked</option><option' . ($task->status === 'Awaiting Evidence' ? ' selected' : '') . '>Awaiting Evidence</option><option' . ($task->status === 'Completed' ? ' selected' : '') . '>Completed</option><option' . ($task->status === 'Cancelled' ? ' selected' : '') . '>Cancelled</option></select>'
                    . '<input name="progress_percent" type="number" min="0" max="100" value="' . e($task->progress_percent) . '">'
                    . '<input name="blocker_summary" placeholder="Blocker" value="' . e($task->blocker_summary ?: '') . '">'
                    . '<button class="planning-button" type="submit">Save</button></form>';
                $evidence = $task->workplan_item_id
                    ? '<form class="planning-inline-form" method="POST" action="' . route('planning.daily.tasks.evidence.store', $task) . '">' . csrf_field()
                        . '<input name="title" required placeholder="Evidence title">'
                        . '<input name="evidence_type" required placeholder="' . e($task->expected_output ?: 'Evidence') . '">'
                        . '<input name="claimed_value" type="number" min="0" step="0.01" value="' . e($task->claimed_value ?: 1) . '">'
                        . '<input name="source_reference" placeholder="Reference">'
                        . '<button class="planning-button secondary" type="submit">Submit</button></form>'
                    : '<span class="planning-muted">Link to target for evidence.</span>';
                return [
                    '<strong class="planning-table-title">' . e($task->title) . '</strong><span class="planning-muted">' . e($task->description ?: $task->expected_output ?: $task->source_module ?: '-') . '</span>',
                    $target,
                    e($task->due_at?->format('M d, H:i') ?: $task->task_date?->format('M d')),
                    '<span class="planning-badge ' . $badge($task->status) . '">' . e($task->status) . '</span>',
                    '<div class="planning-progress"><span style="width:' . e($task->progress_percent) . '%"></span></div><span class="planning-muted">' . e($task->progress_percent) . '%</span>',
                    $update,
                    $evidence,
                ];
            })->values()->all()
        ])
    </section>

    <section class="planning-split">
        <div class="planning-panel">
            <div class="planning-panel-head"><h2>Overdue / Blocked</h2></div>
            @forelse($overdueTasks as $task)
                <div class="planning-alert {{ $task->status === 'Blocked' ? 'danger' : 'warning' }}"><strong>{{ $task->title }}</strong><br>{{ $task->blocker_summary ?: 'Due ' . ($task->due_at?->toDayDateTimeString() ?: $task->task_date?->toFormattedDateString()) }}</div>
            @empty
                <div class="planning-alert success">No overdue or blocked daily tasks.</div>
            @endforelse
        </div>

        <div class="planning-panel">
            <div class="planning-panel-head"><h2>Assign Daily Task</h2><span class="planning-muted">Managers can assign work directly into an employee day.</span></div>
            @if($canSupervise)
                <form class="planning-form" method="POST" action="{{ route('planning.daily.tasks.store') }}">
                    @csrf
                    <div class="planning-field double"><label>Title</label><input name="title" required></div>
                    <div class="planning-field"><label>Employee</label><select name="employee_id" required>@foreach($employees as $employee)<option value="{{ $employee->id }}">{{ $employee->name }}</option>@endforeach</select></div>
                    <div class="planning-field"><label>Supervisor</label><select name="supervisor_id"><option value="">Me</option>@foreach($employees as $employee)<option value="{{ $employee->id }}">{{ $employee->name }}</option>@endforeach</select></div>
                    <div class="planning-field"><label>Task Date</label><input name="task_date" type="date" value="{{ now()->toDateString() }}" required></div>
                    <div class="planning-field"><label>Due At</label><input name="due_at" type="datetime-local"></div>
                    <div class="planning-field"><label>Priority</label><select name="priority"><option>Critical</option><option>High</option><option selected>Medium</option><option>Low</option></select></div>
                    <div class="planning-field"><label>Evidence</label><select name="evidence_required"><option value="1">Required</option><option value="0">Optional</option></select></div>
                    <div class="planning-field double"><label>Workplan Target</label><select name="workplan_item_id"><option value="">Manual / no target</option>@foreach($workplanItems as $item)<option value="{{ $item->id }}">{{ $item->reference }} - {{ $item->title }}</option>@endforeach</select></div>
                    <div class="planning-field"><label>Department</label><select name="department_id"><option value="">None</option>@foreach($departments as $department)<option value="{{ $department->id }}">{{ $department->name }}</option>@endforeach</select></div>
                    <div class="planning-field"><label>Position</label><select name="position_id"><option value="">None</option>@foreach($positions as $position)<option value="{{ $position->id }}">{{ $position->title }}</option>@endforeach</select></div>
                    <div class="planning-field double"><label>Expected Output</label><input name="expected_output" placeholder="Evidence, customer update, report, milestone"></div>
                    <div class="planning-field double"><label>Source Reference</label><input name="source_reference" placeholder="Opportunity, customer, project, ticket"></div>
                    <div class="planning-field full"><label>Description</label><textarea name="description"></textarea></div>
                    <div class="planning-field full"><button class="planning-button" type="submit"><i class="fa-solid fa-plus"></i> Assign Task</button></div>
                </form>
            @else
                <div class="planning-alert warning">Only supervisors and planning managers can assign tasks.</div>
            @endif
        </div>
    </section>

    @if($canSupervise)
        <section class="planning-panel">
            <div class="planning-panel-head"><h2>Supervisor Review Queue</h2><span class="planning-muted">Approve verified work or return it for correction.</span></div>
            @include('planning.partials.table', [
                'headers' => ['Employee', 'Task', 'Evidence', 'Status', 'Review'],
                'rows' => $supervisorTasks->map(function ($task) use ($badge) {
                    $review = '<form class="planning-inline-form" method="POST" action="' . route('planning.daily.tasks.review', $task) . '">' . csrf_field()
                        . '<select name="decision"><option>Approved</option><option>Returned</option></select>'
                        . '<input name="verified_value" type="number" min="0" step="0.01" value="' . e($task->claimed_value ?: 0) . '">'
                        . '<input name="notes" placeholder="Review notes">'
                        . '<button class="planning-button" type="submit">Review</button></form>';
                    return [
                        e($task->employee?->name ?: '-'),
                        '<strong class="planning-table-title">' . e($task->title) . '</strong><span class="planning-muted">' . e($task->source_reference ?: $task->source_module ?: '-') . '</span>',
                        e($task->evidence?->title ?: $task->evidence_status),
                        '<span class="planning-badge ' . $badge($task->status) . '">' . e($task->status) . '</span>',
                        $review,
                    ];
                })->values()->all()
            ])
        </section>
    @endif
</section>
@endsection
