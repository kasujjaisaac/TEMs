@extends('layouts.app')

@section('content')
@include('hr.partials.style')

<section class="hr-core">
    <header class="hr-core-header">
        <div class="hr-core-title">
            <div class="hr-core-icon"><i class="fa-solid fa-sitemap"></i></div>
            <div>
                <h1>{{ $department->name }}</h1>
                <div class="hr-core-muted">{{ $department->code }} / {{ $department->type }} / {{ $department->status }}</div>
            </div>
        </div>
        <div class="hr-core-actions">
            <a class="hr-core-button secondary" href="{{ route('hr.departments.index') }}"><i class="fa-solid fa-arrow-left"></i> Back</a>
            <a class="hr-core-button secondary" href="{{ route('hr.departments.edit', $department) }}"><i class="fa-solid fa-pen-to-square"></i> Edit</a>
            <a class="hr-core-button" href="{{ route('hr.positions.create', ['department_id' => $department->id]) }}"><i class="fa-solid fa-id-badge"></i> Position</a>
        </div>
    </header>

    @if(session('success')) <div class="hr-core-alert success">{{ session('success') }}</div> @endif

    <section class="hr-core-grid">
        <div class="hr-core-card"><span>Status</span><strong>{{ $department->status }}</strong></div>
        <div class="hr-core-card"><span>Parent</span><strong>{{ $department->parent?->short_name ?: $department->parent?->name ?: '-' }}</strong></div>
        <div class="hr-core-card"><span>Child Units</span><strong>{{ $department->children->count() }}</strong></div>
        <div class="hr-core-card"><span>Positions</span><strong>{{ $department->positions->count() }}</strong></div>
    </section>

    <section class="hr-core-split">
        <div class="hr-core-panel">
            <div class="hr-core-panel-head"><h2>Mandate</h2></div>
            <p class="hr-core-muted">{{ $department->mandate ?: 'No mandate recorded yet.' }}</p>
            <div style="height: 12px"></div>
            <h3>Responsibilities</h3>
            <p class="hr-core-muted">{{ $department->responsibilities ?: 'No responsibilities recorded yet.' }}</p>
        </div>
        <div class="hr-core-panel">
            <div class="hr-core-panel-head"><h2>Structure Details</h2></div>
            @include('hr.partials.table', [
                'headers' => ['Field', 'Value'],
                'rows' => [
                    ['Cost Centre', e($department->cost_centre ?: '-')],
                    ['Effective From', e($department->effective_from?->format('M d, Y') ?: '-')],
                    ['Review Date', e($department->review_date?->format('M d, Y') ?: '-')],
                    ['Description', e($department->description ?: '-')],
                ],
            ])
        </div>
    </section>

    <section class="hr-core-panel">
        <div class="hr-core-panel-head"><h2>Positions</h2></div>
        @include('hr.partials.table', [
            'headers' => ['Position', 'Reports To', 'Status', 'Approved', 'Filled', 'Vacant'],
            'rows' => $department->positions->map(fn ($position) => [
                '<strong class="hr-core-table-title"><a href="' . route('hr.positions.show', $position) . '">' . e($position->code) . '</a></strong><span class="hr-core-muted">' . e($position->title) . '</span>',
                e($position->reportsTo?->title ?: '-'),
                '<span class="hr-core-badge ' . ($position->position_status === 'Occupied' ? 'success' : 'warning') . '">' . e($position->position_status) . '</span>',
                e($position->approved_headcount),
                e($position->filled_headcount),
                e($position->vacancy_count),
            ])->all(),
        ])
    </section>
</section>
@endsection
