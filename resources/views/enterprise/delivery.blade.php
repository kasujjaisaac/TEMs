@extends('layouts.app')

@section('content')
@include('commercial.partials.style')
<section class="commercial-page">
    <header class="commercial-header">
        <div class="commercial-title"><div class="commercial-title-icon"><i class="fa-solid fa-diagram-project"></i></div><div><h1>Products & Delivery</h1><div class="commercial-muted">Product portfolio, engineering releases, and implementation projects.</div></div></div>
    </header>
    @if(session('success')) <div class="commercial-alert success">{{ session('success') }}</div> @endif
    @if($errors->any()) <div class="commercial-alert error">{{ $errors->first() }}</div> @endif
    <section class="commercial-grid">
        <div class="commercial-card"><span>Products</span><strong>{{ $metrics['products'] }}</strong></div>
        <div class="commercial-card"><span>Active Projects</span><strong>{{ $metrics['active_projects'] }}</strong></div>
        <div class="commercial-card"><span>Open Tickets</span><strong>{{ $metrics['open_tickets'] }}</strong></div>
        <div class="commercial-card"><span>Critical Signals</span><strong>{{ $metrics['critical_signals'] }}</strong></div>
    </section>
    <section class="commercial-split">
        <div class="commercial-panel">
            <div class="commercial-panel-head"><h2>Add Product</h2></div>
            <form class="commercial-form" method="POST" action="{{ route('delivery.products.store') }}">
                @csrf
                <div class="commercial-field double"><label>Name</label><input name="name" required></div>
                <div class="commercial-field"><label>Category</label><input name="category"></div>
                <div class="commercial-field"><label>Lifecycle</label><select name="lifecycle_stage"><option>Idea</option><option>Validation</option><option>In Development</option><option>Active</option><option>Maintenance</option><option>Retired</option></select></div>
                <div class="commercial-field"><label>Target Revenue</label><input name="target_revenue" type="number" min="0" step="0.01" value="0"></div>
                <div class="commercial-field full"><label>Description</label><textarea name="description"></textarea></div>
                <div class="commercial-field full"><button class="commercial-button" type="submit">Save Product</button></div>
            </form>
        </div>
        <div class="commercial-panel">
            <div class="commercial-panel-head"><h2>Create Project From Won Opportunity</h2></div>
            <form class="commercial-form" method="POST" action="{{ route('delivery.projects.from_opportunity') }}">
                @csrf
                <div class="commercial-field full"><label>Opportunity</label><select name="opportunity_id" required>@foreach($opportunities as $opportunity)<option value="{{ $opportunity->id }}">{{ $opportunity->reference }} - {{ $opportunity->title }}</option>@endforeach</select></div>
                <div class="commercial-field full"><button class="commercial-button" type="submit">Create Project</button></div>
            </form>
        </div>
    </section>
    <section class="commercial-panel">
        <div class="commercial-panel-head"><h2>Product Portfolio</h2></div>
        @include('commercial.partials.table', ['headers' => ['Product','Stage','Target Revenue','Health','Status'], 'rows' => $products->map(fn($p) => ['<strong class="commercial-table-title">'.e($p->reference).'</strong><span class="commercial-muted">'.e($p->name).'</span>', e($p->lifecycle_stage), e(number_format((float)$p->target_revenue, 2)), e($p->health_score).'%', '<span class="commercial-badge success">'.e($p->status).'</span>'])->all()])
    </section>
    <section class="commercial-panel">
        <div class="commercial-panel-head"><h2>Implementation Projects</h2></div>
        @include('commercial.partials.table', ['headers' => ['Project','Progress','Budget','Health','Status'], 'rows' => $projects->map(fn($p) => ['<strong class="commercial-table-title">'.e($p->reference).'</strong><span class="commercial-muted">'.e($p->name).'</span>', e($p->progress).'%', e(number_format((float)$p->budget, 2)), e($p->health_status), '<span class="commercial-badge warning">'.e($p->status).'</span>'])->all()])
    </section>
</section>
@endsection
