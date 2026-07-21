@extends('layouts.app')

@section('content')
@include('planning.partials.style')

<section class="planning-page">
    <header class="planning-header">
        <div class="planning-title"><div class="planning-icon"><i class="fa-solid fa-list-check"></i></div><div><h1>Corporate Workplans</h1><div class="planning-muted">Corporate, department, position and individual planning baselines.</div></div></div>
        <div class="planning-actions">
            <a class="planning-button secondary" href="{{ route('planning.dashboard') }}"><i class="fa-solid fa-arrow-left"></i> Dashboard</a>
            <a class="planning-button secondary" href="{{ route('planning.workplans.template') }}"><i class="fa-solid fa-file-csv"></i> Template</a>
        </div>
    </header>

    @if(session('success')) <div class="planning-alert success">{{ session('success') }}</div> @endif
    @if($errors->any()) <div class="planning-alert danger">{{ $errors->first() }}</div> @endif

    <section class="planning-split">
        <div class="planning-panel">
            <div class="planning-panel-head">
                <h2>Upload Workplan</h2>
                <span class="planning-muted">CSV uploads become workplans, targets, assignments, allocations and auditable baselines.</span>
            </div>
            <form class="planning-form" method="POST" action="{{ route('planning.workplans.import') }}" enctype="multipart/form-data">
                @csrf
                <div class="planning-field double"><label>Completed Workplan CSV</label><input name="workplan_file" type="file" accept=".csv,text/csv,text/plain" required></div>
                <div class="planning-field double"><label>Required Columns</label><input readonly value="workplan_code, target_reference, target_title, target_value"></div>
                <div class="planning-field full"><button class="planning-button" type="submit"><i class="fa-solid fa-upload"></i> Upload & Convert To Targets</button></div>
            </form>
        </div>

        <div class="planning-panel">
            <div class="planning-panel-head"><h2>Recent Imports</h2></div>
            @include('planning.partials.table', [
                'headers' => ['File', 'Status', 'Rows', 'Targets', 'Uploaded By'],
                'rows' => $imports->map(fn ($import) => [
                    '<strong class="planning-table-title">' . e($import->original_filename) . '</strong><span class="planning-muted">' . e($import->imported_at?->toDayDateTimeString() ?: '-') . '</span>',
                    '<span class="planning-badge ' . ($import->status === 'Imported' ? 'success' : 'danger') . '">' . e($import->status) . '</span>',
                    e($import->rows_read),
                    e($import->targets_imported),
                    e($import->uploader?->name ?: '-'),
                ])->all()
            ])
        </div>
    </section>

    <section class="planning-panel">
        <div class="planning-panel-head"><h2>Workplan Register</h2></div>
        @include('planning.partials.table', [
            'headers' => ['Workplan', 'Level', 'Planning Year', 'Owner', 'Approval', 'Health', 'Targets'],
            'rows' => $workplans->map(fn ($workplan) => [
                '<strong class="planning-table-title"><a href="' . route('planning.workplans.show', $workplan) . '">' . e($workplan->code) . '</a></strong><span class="planning-muted">' . e($workplan->title) . '</span>',
                e($workplan->level),
                e($workplan->planningYear?->name ?: '-'),
                e($workplan->department?->name ?: $workplan->position?->title ?: $workplan->employee?->name ?: $workplan->owner_name ?: '-'),
                '<span class="planning-badge ' . ($workplan->approval_status === 'Approved' ? 'success' : 'warning') . '">' . e($workplan->approval_status) . '</span>',
                e($workplan->health_status),
                e($workplan->items_count),
            ])->all()
        ])
        {{ $workplans->links() }}
    </section>
</section>
@endsection
