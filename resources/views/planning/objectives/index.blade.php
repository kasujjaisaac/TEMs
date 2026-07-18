@extends('layouts.app')

@section('content')
@include('planning.partials.style')

<section class="planning-page">
    <header class="planning-header">
        <div class="planning-title"><div class="planning-icon"><i class="fa-solid fa-compass"></i></div><div><h1>Strategic Objectives</h1><div class="planning-muted">Planning year {{ $planningYear->name }} strategy, pillars, KPIs and enterprise targets.</div></div></div>
        <div class="planning-actions"><a class="planning-button secondary" href="{{ route('planning.dashboard') }}"><i class="fa-solid fa-arrow-left"></i> Dashboard</a></div>
    </header>

    @if(session('success')) <div class="planning-alert success">{{ session('success') }}</div> @endif

    <section class="planning-split">
        <div class="planning-panel">
            <div class="planning-panel-head"><h2>Strategic Pillars</h2></div>
            @include('planning.partials.table', [
                'headers' => ['Pillar', 'Weight', 'Status', 'Objectives'],
                'rows' => $pillars->map(fn ($pillar) => [
                    '<strong class="planning-table-title">' . e($pillar->code) . '</strong><span class="planning-muted">' . e($pillar->name) . '</span>',
                    e($pillar->weight) . '%',
                    '<span class="planning-badge success">' . e($pillar->status) . '</span>',
                    e($pillar->objectives_count),
                ])->all()
            ])
        </div>

        <div class="planning-panel">
            <div class="planning-panel-head"><h2>Create Objective</h2></div>
            <form class="planning-form" method="POST" action="{{ route('planning.objectives.store') }}">
                @csrf
                <div class="planning-field"><label>Code</label><input name="code" required placeholder="OBJ-006"></div>
                <div class="planning-field"><label>Pillar</label><select name="strategic_pillar_id"><option value="">None</option>@foreach($pillars as $pillar)<option value="{{ $pillar->id }}">{{ $pillar->code }} - {{ $pillar->name }}</option>@endforeach</select></div>
                <div class="planning-field"><label>Target Value</label><input name="target_value" type="number" min="0" step="0.01" value="0"></div>
                <div class="planning-field"><label>Status</label><select name="status"><option>Draft</option><option>Approved</option><option>Active</option></select></div>
                <div class="planning-field double"><label>Title</label><input name="title" required></div>
                <div class="planning-field"><label>KPI</label><input name="kpi"></div>
                <div class="planning-field"><label>Unit</label><input name="unit" placeholder="clients, UGX, %"></div>
                <div class="planning-field"><label>Weight</label><input name="weight" type="number" min="0" max="100" value="10"></div>
                <div class="planning-field full"><label>Description</label><textarea name="description"></textarea></div>
                <div class="planning-field full"><button class="planning-button" type="submit"><i class="fa-solid fa-save"></i> Save Objective</button></div>
            </form>
        </div>
    </section>

    <section class="planning-panel">
        <div class="planning-panel-head"><h2>Objective Register</h2></div>
        @include('planning.partials.table', [
            'headers' => ['Objective', 'Pillar', 'KPI', 'Target', 'Weight', 'Status'],
            'rows' => $objectives->map(fn ($objective) => [
                '<strong class="planning-table-title">' . e($objective->code) . '</strong><span class="planning-muted">' . e($objective->title) . '</span>',
                e($objective->pillar?->name ?: '-'),
                e($objective->kpi ?: '-'),
                e(number_format((float) $objective->target_value, 2) . ' ' . ($objective->unit ?: '')),
                e($objective->weight) . '%',
                '<span class="planning-badge ' . ($objective->status === 'Approved' ? 'success' : 'warning') . '">' . e($objective->status) . '</span>',
            ])->all()
        ])
        {{ $objectives->links() }}
    </section>
</section>
@endsection
