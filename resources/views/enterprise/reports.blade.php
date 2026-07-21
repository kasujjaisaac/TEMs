@extends('layouts.app')

@section('content')
@include('commercial.partials.style')
<section class="commercial-page">
    <header class="commercial-header">
        <div class="commercial-title"><div class="commercial-title-icon"><i class="fa-solid fa-chart-simple"></i></div><div><h1>Reports & Analytics</h1><div class="commercial-muted">Report definitions, intelligence snapshots, operating metrics, and executive reporting coverage.</div></div></div>
    </header>
    @if(session('success')) <div class="commercial-alert success">{{ session('success') }}</div> @endif
    <section class="commercial-grid">
        <div class="commercial-card"><span>Active Reports</span><strong>{{ $metrics['reports'] }}</strong></div>
        <div class="commercial-card"><span>Open Directives</span><strong>{{ $metrics['open_directives'] }}</strong></div>
        <div class="commercial-card"><span>Backlog Items</span><strong>{{ $metrics['backlog_items'] }}</strong></div>
        <div class="commercial-card"><span>Open Defects</span><strong>{{ $metrics['open_defects'] }}</strong></div>
    </section>
    <section class="commercial-panel">
        <div class="commercial-panel-head"><h2>New Report Definition</h2></div>
        <form class="commercial-form" method="POST" action="{{ route('analytics.reports.store') }}">
            @csrf
            <div class="commercial-field double"><label>Name</label><input name="name" required></div>
            <div class="commercial-field"><label>Module</label><input name="module" required></div>
            <div class="commercial-field"><label>Frequency</label><select name="frequency"><option>Weekly</option><option selected>Monthly</option><option>Quarterly</option><option>Annual</option></select></div>
            <div class="commercial-field"><label>Visibility</label><select name="visibility"><option>Management</option><option>Executive</option><option>Board</option><option>Department</option></select></div>
            <div class="commercial-field"><button class="commercial-button" type="submit">Create Report</button></div>
        </form>
    </section>
    <section class="commercial-split">
        <div class="commercial-panel"><div class="commercial-panel-head"><h2>Report Definitions</h2></div>@include('commercial.partials.table', ['headers' => ['Report','Module','Frequency','Status'], 'rows' => $reports->map(fn($r) => ['<strong class="commercial-table-title">'.e($r->reference).'</strong><span class="commercial-muted">'.e($r->name).'</span>', e($r->module), e($r->frequency), '<span class="commercial-badge success">'.e($r->status).'</span>'])->all()])</div>
        <div class="commercial-panel"><div class="commercial-panel-head"><h2>Metric Snapshots</h2></div>@include('commercial.partials.table', ['headers' => ['Metric','Value','Source','Captured'], 'rows' => $snapshots->map(fn($s) => ['<strong class="commercial-table-title">'.e($s->metric_name).'</strong><span class="commercial-muted">'.e($s->metric_key).'</span>', e($s->metric_value).' '.e($s->unit), e($s->source_module), e($s->captured_at ?: '-')])->all()])</div>
    </section>
</section>
@endsection
