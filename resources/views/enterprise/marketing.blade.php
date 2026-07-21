@extends('layouts.app')

@section('content')
@include('commercial.partials.style')
<section class="commercial-page">
    <header class="commercial-header">
        <div class="commercial-title"><div class="commercial-title-icon"><i class="fa-solid fa-bullhorn"></i></div><div><h1>Marketing & Communications</h1><div class="commercial-muted">Campaign plans, content approvals, audiences, channels, and communication execution.</div></div></div>
    </header>
    @if(session('success')) <div class="commercial-alert success">{{ session('success') }}</div> @endif
    <section class="commercial-grid">
        <div class="commercial-card"><span>Marketing Plans</span><strong>{{ $metrics['marketing_plans'] }}</strong></div>
        <div class="commercial-card"><span>Content Review</span><strong>{{ $metrics['content_awaiting_approval'] }}</strong></div>
        <div class="commercial-card"><span>Active Leads</span><strong>{{ $metrics['open_directives'] }}</strong></div>
        <div class="commercial-card"><span>Reports</span><strong>{{ $metrics['reports'] }}</strong></div>
    </section>
    <section class="commercial-panel">
        <div class="commercial-panel-head"><h2>New Communication Plan</h2></div>
        <form class="commercial-form" method="POST" action="{{ route('marketing.plans.store') }}">
            @csrf
            <div class="commercial-field double"><label>Title</label><input name="title" required></div>
            <div class="commercial-field"><label>Channel</label><input name="channel"></div>
            <div class="commercial-field"><label>Audience</label><input name="audience"></div>
            <div class="commercial-field"><label>Start</label><input name="starts_on" type="date"></div>
            <div class="commercial-field"><label>End</label><input name="ends_on" type="date"></div>
            <div class="commercial-field"><label>Budget</label><input name="budget" type="number" min="0" step="0.01" value="0"></div>
            <div class="commercial-field"><button class="commercial-button" type="submit">Create Plan</button></div>
        </form>
    </section>
    <section class="commercial-split">
        <div class="commercial-panel"><div class="commercial-panel-head"><h2>Plans</h2></div>@include('commercial.partials.table', ['headers' => ['Plan','Channel','Audience','Status'], 'rows' => $plans->map(fn($p) => ['<strong class="commercial-table-title">'.e($p->reference).'</strong><span class="commercial-muted">'.e($p->title).'</span>', e($p->channel ?: '-'), e($p->audience ?: '-'), '<span class="commercial-badge">'.e($p->status).'</span>'])->all()])</div>
        <div class="commercial-panel"><div class="commercial-panel-head"><h2>Content Pipeline</h2></div>@include('commercial.partials.table', ['headers' => ['Content','Type','Publish','Approval'], 'rows' => $contentItems->map(fn($c) => ['<strong class="commercial-table-title">'.e($c->reference).'</strong><span class="commercial-muted">'.e($c->title).'</span>', e($c->content_type ?: '-'), e($c->publish_on ?: '-'), '<span class="commercial-badge warning">'.e($c->approval_status).'</span>'])->all()])</div>
    </section>
</section>
@endsection
