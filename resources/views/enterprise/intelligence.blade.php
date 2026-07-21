@extends('layouts.app')

@section('content')
@include('commercial.partials.style')
<section class="commercial-page">
    <header class="commercial-header"><div class="commercial-title"><div class="commercial-title-icon"><i class="fa-solid fa-brain"></i></div><div><h1>Enterprise Intelligence</h1><div class="commercial-muted">Metric snapshots, risk signals and decision recommendations generated from connected TEMS modules.</div></div></div><form method="POST" action="{{ route('intelligence.refresh') }}">@csrf<button class="commercial-button" type="submit">Refresh</button></form></header>
    @if(session('success')) <div class="commercial-alert success">{{ session('success') }}</div> @endif
    <section class="commercial-grid">
        <div class="commercial-card"><span>Critical Signals</span><strong>{{ $metrics['critical_signals'] }}</strong></div>
        <div class="commercial-card"><span>Recommendations</span><strong>{{ $metrics['recommendations'] }}</strong></div>
        <div class="commercial-card"><span>Active Projects</span><strong>{{ $metrics['active_projects'] }}</strong></div>
        <div class="commercial-card"><span>Customer Risks</span><strong>{{ $metrics['customer_risks'] }}</strong></div>
    </section>
    <section class="commercial-split">
        <div class="commercial-panel"><div class="commercial-panel-head"><h2>Signals</h2></div>@include('commercial.partials.table', ['headers' => ['Signal','Severity','Source','Status'], 'rows' => $signals->map(fn($s) => ['<strong class="commercial-table-title">'.e($s->title).'</strong><span class="commercial-muted">'.e($s->message).'</span>', e($s->severity), e($s->source_module), e($s->status)])->all()])</div>
        <div class="commercial-panel"><div class="commercial-panel-head"><h2>Recommendations</h2></div>@include('commercial.partials.table', ['headers' => ['Recommendation','Priority','Source','Status'], 'rows' => $recommendations->map(fn($r) => ['<strong class="commercial-table-title">'.e($r->title).'</strong><span class="commercial-muted">'.e($r->recommendation).'</span>', e($r->priority), e($r->source_module), e($r->status)])->all()])</div>
    </section>
    <section class="commercial-panel"><div class="commercial-panel-head"><h2>Metric Snapshots</h2></div>@include('commercial.partials.table', ['headers' => ['Metric','Value','Source','Captured'], 'rows' => $snapshots->map(fn($m) => [e($m->metric_name), e(number_format((float)$m->metric_value, 2).' '.($m->unit ?: '')), e($m->source_module), e($m->captured_at)])->all()])</section>
</section>
@endsection
