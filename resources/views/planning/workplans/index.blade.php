@extends('layouts.app')

@section('content')
@include('planning.partials.style')

<section class="planning-page">
    <header class="planning-header">
        <div class="planning-title"><div class="planning-icon"><i class="fa-solid fa-list-check"></i></div><div><h1>Corporate Workplans</h1><div class="planning-muted">Corporate, department, position and individual planning baselines.</div></div></div>
        <div class="planning-actions"><a class="planning-button secondary" href="{{ route('planning.dashboard') }}"><i class="fa-solid fa-arrow-left"></i> Dashboard</a></div>
    </header>

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
