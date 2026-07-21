@extends('layouts.app')

@section('content')
@include('commercial.partials.style')
<section class="commercial-page">
    <header class="commercial-header">
        <div class="commercial-title"><div class="commercial-title-icon"><i class="fa-solid fa-chess-king"></i></div><div><h1>Executive Strategy</h1><div class="commercial-muted">Directives, executive decisions, risks, and company-level accountability.</div></div></div>
    </header>
    @if(session('success')) <div class="commercial-alert success">{{ session('success') }}</div> @endif
    <section class="commercial-grid">
        <div class="commercial-card"><span>Open Directives</span><strong>{{ $metrics['open_directives'] }}</strong></div>
        <div class="commercial-card"><span>Corporate Risks</span><strong>{{ $metrics['corporate_risks'] }}</strong></div>
        <div class="commercial-card"><span>Reports</span><strong>{{ $metrics['reports'] }}</strong></div>
        <div class="commercial-card"><span>Backlog Items</span><strong>{{ $metrics['backlog_items'] }}</strong></div>
    </section>
    <section class="commercial-split">
        <div class="commercial-panel">
            <div class="commercial-panel-head"><h2>New Directive</h2></div>
            <form class="commercial-form" method="POST" action="{{ route('strategy.directives.store') }}">
                @csrf
                <div class="commercial-field double"><label>Title</label><input name="title" required></div>
                <div class="commercial-field"><label>Priority</label><select name="priority"><option>Normal</option><option>High</option><option>Critical</option><option>Low</option></select></div>
                <div class="commercial-field"><label>Due</label><input name="due_on" type="date"></div>
                <div class="commercial-field full"><label>Directive</label><textarea name="directive"></textarea></div>
                <div class="commercial-field full"><button class="commercial-button" type="submit">Create Directive</button></div>
            </form>
        </div>
        <div class="commercial-panel">
            <div class="commercial-panel-head"><h2>Corporate Risks</h2></div>
            @include('commercial.partials.table', ['headers' => ['Risk','Level','Review','Status'], 'rows' => $risks->map(fn($r) => ['<strong class="commercial-table-title">'.e($r->reference).'</strong><span class="commercial-muted">'.e($r->title).'</span>', e($r->risk_level), e($r->review_due_on ?: '-'), '<span class="commercial-badge warning">'.e($r->status).'</span>'])->all()])
        </div>
    </section>
    <section class="commercial-panel">
        <div class="commercial-panel-head"><h2>Executive Directives</h2></div>
        @include('commercial.partials.table', ['headers' => ['Directive','Priority','Due','Status'], 'rows' => $directives->map(fn($d) => ['<strong class="commercial-table-title">'.e($d->reference).'</strong><span class="commercial-muted">'.e($d->title).'</span>', e($d->priority), e($d->due_on ?: '-'), '<span class="commercial-badge">'.e($d->status).'</span>'])->all()])
    </section>
    <section class="commercial-panel">
        <div class="commercial-panel-head"><h2>Executive Decisions</h2></div>
        @include('commercial.partials.table', ['headers' => ['Decision','Type','Date','Status'], 'rows' => $decisions->map(fn($d) => ['<strong class="commercial-table-title">'.e($d->reference).'</strong><span class="commercial-muted">'.e($d->title).'</span>', e($d->decision_type ?: '-'), e($d->decided_on ?: '-'), '<span class="commercial-badge success">'.e($d->status).'</span>'])->all()])
    </section>
</section>
@endsection
