@extends('layouts.app')

@section('content')
@include('commercial.partials.style')
<section class="commercial-page">
    <header class="commercial-header"><div class="commercial-title"><div class="commercial-title-icon"><i class="fa-solid fa-headset"></i></div><div><h1>Customer Success</h1><div class="commercial-muted">Customer health, onboarding, support tickets, SLA risk and renewals.</div></div></div></header>
    @if(session('success')) <div class="commercial-alert success">{{ session('success') }}</div> @endif
    @if($errors->any()) <div class="commercial-alert error">{{ $errors->first() }}</div> @endif
    <section class="commercial-grid">
        <div class="commercial-card"><span>Open Tickets</span><strong>{{ $metrics['open_tickets'] }}</strong></div>
        <div class="commercial-card"><span>Customer Risks</span><strong>{{ $metrics['customer_risks'] }}</strong></div>
        <div class="commercial-card"><span>Products</span><strong>{{ $metrics['products'] }}</strong></div>
        <div class="commercial-card"><span>Recommendations</span><strong>{{ $metrics['recommendations'] }}</strong></div>
    </section>
    <section class="commercial-panel">
        <div class="commercial-panel-head"><h2>Create Support Ticket</h2></div>
        <form class="commercial-form" method="POST" action="{{ route('customer_success.tickets.store') }}">
            @csrf
            <div class="commercial-field"><label>Organization</label><select name="organization_id"><option value="">None</option>@foreach($organizations as $org)<option value="{{ $org->id }}">{{ $org->legal_name }}</option>@endforeach</select></div>
            <div class="commercial-field"><label>Product</label><select name="product_id"><option value="">None</option>@foreach($products as $product)<option value="{{ $product->id }}">{{ $product->name }}</option>@endforeach</select></div>
            <div class="commercial-field"><label>Priority</label><select name="priority"><option>Medium</option><option>High</option><option>Critical</option><option>Low</option></select></div>
            <div class="commercial-field full"><label>Subject</label><input name="subject" required></div>
            <div class="commercial-field full"><label>Description</label><textarea name="description"></textarea></div>
            <div class="commercial-field full"><button class="commercial-button" type="submit">Create Ticket</button></div>
        </form>
    </section>
    <section class="commercial-panel">
        <div class="commercial-panel-head"><h2>Support Tickets</h2></div>
        @include('commercial.partials.table', ['headers' => ['Ticket','Priority','SLA Due','Assigned','Status'], 'rows' => $tickets->map(fn($t) => ['<strong class="commercial-table-title">'.e($t->reference).'</strong><span class="commercial-muted">'.e($t->subject).'</span>', e($t->priority), e($t->sla_due_at ?: '-'), e($t->assigned_to ?: '-'), '<span class="commercial-badge warning">'.e($t->status).'</span>'])->all()])
    </section>
</section>
@endsection
