@extends('layouts.app')

@section('content')
@include('planning.partials.style')

<section class="planning-page">
    <header class="planning-header">
        <div class="planning-title"><div class="planning-icon"><i class="fa-solid fa-bullseye"></i></div><div><h1>Planning & Performance Engine</h1><div class="planning-muted">{{ $planningYear->name }} - {{ $planningYear->annual_theme }}</div></div></div>
        <div class="planning-actions">
            <a class="planning-button secondary" href="{{ route('planning.objectives.index') }}"><i class="fa-solid fa-compass"></i> Objectives</a>
            <a class="planning-button" href="{{ route('planning.workplans.index') }}"><i class="fa-solid fa-list-check"></i> Workplans</a>
        </div>
    </header>

    @if(session('success')) <div class="planning-alert success">{{ session('success') }}</div> @endif

    <section class="planning-grid">
        <div class="planning-card"><span>Company Achievement</span><strong>{{ $metrics['company_achievement'] }}%</strong></div>
        <div class="planning-card"><span>Strategic Objectives</span><strong>{{ $metrics['strategic_objectives'] }}</strong></div>
        <div class="planning-card"><span>Workplan Targets</span><strong>{{ $metrics['targets'] }}</strong></div>
        <div class="planning-card"><span>At Risk / Behind</span><strong>{{ $metrics['at_risk_or_behind'] }}</strong></div>
        <div class="planning-card"><span>Workplans</span><strong>{{ $metrics['workplans'] }}</strong></div>
        <div class="planning-card"><span>Monthly Allocations</span><strong>{{ $metrics['monthly_allocations'] }}</strong></div>
        <div class="planning-card"><span>Weekly Allocations</span><strong>{{ $metrics['weekly_allocations'] }}</strong></div>
        <div class="planning-card"><span>Strategic Pillars</span><strong>{{ $metrics['strategic_pillars'] }}</strong></div>
    </section>

    <section class="planning-split">
        <div class="planning-panel">
            <div class="planning-panel-head"><h2>Live Target Pace</h2><span class="planning-muted">System-calculated progress from target and actual values.</span></div>
            @include('planning.partials.table', [
                'headers' => ['Target', 'Objective', 'Progress', 'Pace', 'Forecast', 'Health'],
                'rows' => $recentItems->map(fn ($row) => [
                    '<strong class="planning-table-title"><a href="' . route('planning.workplans.show', $row['item']->workplan) . '">' . e($row['item']->reference) . '</a></strong><span class="planning-muted">' . e($row['item']->title) . '</span>',
                    e($row['item']->objective?->code ?: '-'),
                    '<div class="planning-progress"><span style="width:' . e(min(100, $row['achievement'])) . '%"></span></div><span class="planning-muted">' . e($row['achievement']) . '% achieved / ' . e($row['time_elapsed']) . '% elapsed</span>',
                    e($row['pace']) . '%',
                    e(number_format((float) $row['forecast'], 2)) . ' ' . e($row['item']->unit ?: ''),
                    '<span class="planning-badge ' . (in_array($row['health'], ['Ahead','On Track','Completed'], true) ? 'success' : (in_array($row['health'], ['At Risk','Not Started'], true) ? 'warning' : 'danger')) . '">' . e($row['health']) . '</span>',
                ])->all()
            ])
        </div>
        <div class="planning-panel">
            <div class="planning-panel-head"><h2>Performance Intelligence</h2></div>
            @forelse($alerts as $alert)
                <div class="planning-alert {{ $alert['severity'] === 'High' ? 'danger' : 'warning' }}"><strong>{{ $alert['title'] }}</strong><br>{{ $alert['message'] }}</div>
            @empty
                <div class="planning-alert success">All live targets are inside acceptable planning thresholds.</div>
            @endforelse
        </div>
    </section>

    <section class="planning-panel">
        <div class="planning-panel-head"><h2>Workplan Coverage</h2><a class="planning-button secondary" href="{{ route('planning.workplans.index') }}">View All</a></div>
        @include('planning.partials.table', [
            'headers' => ['Workplan', 'Level', 'Owner', 'Status', 'Targets'],
            'rows' => $workplans->map(fn ($workplan) => [
                '<strong class="planning-table-title"><a href="' . route('planning.workplans.show', $workplan) . '">' . e($workplan->code) . '</a></strong><span class="planning-muted">' . e($workplan->title) . '</span>',
                e($workplan->level),
                e($workplan->department?->name ?: $workplan->owner_name ?: '-'),
                '<span class="planning-badge ' . ($workplan->approval_status === 'Approved' ? 'success' : 'warning') . '">' . e($workplan->approval_status) . '</span>',
                e($workplan->items_count),
            ])->all()
        ])
    </section>
</section>
@endsection
