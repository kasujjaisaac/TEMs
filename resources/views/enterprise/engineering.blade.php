@extends('layouts.app')

@section('content')
@include('commercial.partials.style')
<section class="commercial-page">
    <header class="commercial-header">
        <div class="commercial-title"><div class="commercial-title-icon"><i class="fa-solid fa-code-branch"></i></div><div><h1>Engineering</h1><div class="commercial-muted">Software backlog, quality defects, releases, and technical delivery evidence.</div></div></div>
    </header>
    @if(session('success')) <div class="commercial-alert success">{{ session('success') }}</div> @endif
    <section class="commercial-grid">
        <div class="commercial-card"><span>Backlog Items</span><strong>{{ $metrics['backlog_items'] }}</strong></div>
        <div class="commercial-card"><span>Open Defects</span><strong>{{ $metrics['open_defects'] }}</strong></div>
        <div class="commercial-card"><span>Knowledge Articles</span><strong>{{ $metrics['knowledge_articles'] }}</strong></div>
        <div class="commercial-card"><span>Critical Signals</span><strong>{{ $metrics['corporate_risks'] }}</strong></div>
    </section>
    <section class="commercial-panel">
        <div class="commercial-panel-head"><h2>New Backlog Item</h2></div>
        <form class="commercial-form" method="POST" action="{{ route('engineering.backlog.store') }}">
            @csrf
            <div class="commercial-field double"><label>Title</label><input name="title" required></div>
            <div class="commercial-field"><label>Type</label><select name="item_type"><option>Feature</option><option>Bug</option><option>Task</option><option>Improvement</option></select></div>
            <div class="commercial-field"><label>Priority</label><select name="priority"><option>Medium</option><option>High</option><option>Critical</option><option>Low</option></select></div>
            <div class="commercial-field"><label>Release Target</label><input name="release_target"></div>
            <div class="commercial-field"><button class="commercial-button" type="submit">Add Item</button></div>
        </form>
    </section>
    <section class="commercial-split">
        <div class="commercial-panel"><div class="commercial-panel-head"><h2>Backlog</h2></div>@include('commercial.partials.table', ['headers' => ['Item','Type','Priority','Status'], 'rows' => $backlog->map(fn($b) => ['<strong class="commercial-table-title">'.e($b->reference).'</strong><span class="commercial-muted">'.e($b->title).'</span>', e($b->item_type), e($b->priority), '<span class="commercial-badge">'.e($b->status).'</span>'])->all()])</div>
        <div class="commercial-panel"><div class="commercial-panel-head"><h2>Quality Defects</h2></div>@include('commercial.partials.table', ['headers' => ['Defect','Severity','Environment','Status'], 'rows' => $defects->map(fn($d) => ['<strong class="commercial-table-title">'.e($d->reference).'</strong><span class="commercial-muted">'.e($d->title).'</span>', e($d->severity), e($d->environment ?: '-'), '<span class="commercial-badge warning">'.e($d->status).'</span>'])->all()])</div>
    </section>
</section>
@endsection
