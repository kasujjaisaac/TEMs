@extends('layouts.app')

@section('content')
@include('commercial.partials.style')
<section class="commercial-page">
    <header class="commercial-header">
        <div class="commercial-title"><div class="commercial-title-icon"><i class="fa-solid fa-folder-tree"></i></div><div><h1>Knowledge & Documents</h1><div class="commercial-muted">Policies, working knowledge, document records, review status, and organizational memory.</div></div></div>
    </header>
    @if(session('success')) <div class="commercial-alert success">{{ session('success') }}</div> @endif
    <section class="commercial-grid">
        <div class="commercial-card"><span>Knowledge Articles</span><strong>{{ $metrics['knowledge_articles'] }}</strong></div>
        <div class="commercial-card"><span>Reports</span><strong>{{ $metrics['reports'] }}</strong></div>
        <div class="commercial-card"><span>Open Directives</span><strong>{{ $metrics['open_directives'] }}</strong></div>
        <div class="commercial-card"><span>Corporate Risks</span><strong>{{ $metrics['corporate_risks'] }}</strong></div>
    </section>
    <section class="commercial-panel">
        <div class="commercial-panel-head"><h2>New Knowledge Article</h2></div>
        <form class="commercial-form" method="POST" action="{{ route('knowledge.articles.store') }}">
            @csrf
            <div class="commercial-field double"><label>Title</label><input name="title" required></div>
            <div class="commercial-field"><label>Category</label><input name="category"></div>
            <div class="commercial-field"><label>Review Due</label><input name="review_due_on" type="date"></div>
            <div class="commercial-field full"><label>Summary</label><textarea name="summary"></textarea></div>
            <div class="commercial-field full"><button class="commercial-button" type="submit">Create Article</button></div>
        </form>
    </section>
    <section class="commercial-split">
        <div class="commercial-panel"><div class="commercial-panel-head"><h2>Knowledge Base</h2></div>@include('commercial.partials.table', ['headers' => ['Article','Category','Review','Status'], 'rows' => $articles->map(fn($a) => ['<strong class="commercial-table-title">'.e($a->reference).'</strong><span class="commercial-muted">'.e($a->title).'</span>', e($a->category ?: '-'), e($a->review_due_on ?: '-'), '<span class="commercial-badge warning">'.e($a->review_status).'</span>'])->all()])</div>
        <div class="commercial-panel"><div class="commercial-panel-head"><h2>Document Records</h2></div>@include('commercial.partials.table', ['headers' => ['Document','Type','Status','Owner'], 'rows' => $documents->map(fn($d) => ['<strong class="commercial-table-title">'.e($d->reference).'</strong><span class="commercial-muted">'.e($d->title).'</span>', e($d->document_type ?: '-'), '<span class="commercial-badge">'.e($d->status).'</span>', e($d->owner_id ?: '-')])->all()])</div>
    </section>
</section>
@endsection
