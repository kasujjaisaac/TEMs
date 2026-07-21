@extends('layouts.app')

@section('content')
@include('commercial.partials.style')
<section class="commercial-page">
    <header class="commercial-header"><div class="commercial-title"><div class="commercial-title-icon"><i class="fa-solid fa-landmark"></i></div><div><h1>Governance & Compliance</h1><div class="commercial-muted">Compliance obligations, board actions, governance follow-up and enterprise control.</div></div></div></header>
    @if(session('success')) <div class="commercial-alert success">{{ session('success') }}</div> @endif
    @if($errors->any()) <div class="commercial-alert error">{{ $errors->first() }}</div> @endif
    <section class="commercial-grid">
        <div class="commercial-card"><span>Open Compliance</span><strong>{{ $metrics['compliance_open'] }}</strong></div>
        <div class="commercial-card"><span>Board Actions</span><strong>{{ $metrics['board_actions_open'] }}</strong></div>
        <div class="commercial-card"><span>Critical Signals</span><strong>{{ $metrics['critical_signals'] }}</strong></div>
        <div class="commercial-card"><span>Recommendations</span><strong>{{ $metrics['recommendations'] }}</strong></div>
    </section>
    <section class="commercial-panel">
        <div class="commercial-panel-head"><h2>Create Compliance Obligation</h2></div>
        <form class="commercial-form" method="POST" action="{{ route('governance.obligations.store') }}">
            @csrf
            <div class="commercial-field double"><label>Title</label><input name="title" required></div>
            <div class="commercial-field"><label>Category</label><input name="category"></div>
            <div class="commercial-field"><label>Due On</label><input name="due_on" type="date"></div>
            <div class="commercial-field"><label>Risk</label><select name="risk_level"><option>Medium</option><option>High</option><option>Critical</option><option>Low</option></select></div>
            <div class="commercial-field full"><label>Notes</label><textarea name="notes"></textarea></div>
            <div class="commercial-field full"><button class="commercial-button" type="submit">Save Obligation</button></div>
        </form>
    </section>
    <section class="commercial-split">
        <div class="commercial-panel"><div class="commercial-panel-head"><h2>Compliance</h2></div>@include('commercial.partials.table', ['headers' => ['Obligation','Due','Risk','Status'], 'rows' => $obligations->map(fn($o) => ['<strong class="commercial-table-title">'.e($o->reference).'</strong><span class="commercial-muted">'.e($o->title).'</span>', e($o->due_on ?: '-'), e($o->risk_level), e($o->status)])->all()])</div>
        <div class="commercial-panel"><div class="commercial-panel-head"><h2>Board Actions</h2></div>@include('commercial.partials.table', ['headers' => ['Action','Meeting','Due','Status'], 'rows' => $actions->map(fn($a) => ['<strong class="commercial-table-title">'.e($a->reference).'</strong><span class="commercial-muted">'.e($a->title).'</span>', e($a->source_meeting ?: '-'), e($a->due_on ?: '-'), e($a->status)])->all()])</div>
    </section>
</section>
@endsection
