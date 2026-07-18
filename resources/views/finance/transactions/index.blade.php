@extends('layouts.app')

@section('content')
@include('finance.partials.style')
<section class="finance-page">
    <header class="finance-header">
        <div class="finance-title"><div class="finance-icon"><i class="fa-solid fa-list-check"></i></div><div><h1>Financial Transaction Register</h1><div class="finance-muted">Universal transaction panel across Sales, Commercial, Procurement, HR and Finance.</div></div></div>
        <a class="finance-button secondary" href="{{ route('finance.dashboard') }}"><i class="fa-solid fa-arrow-left"></i> Back</a>
    </header>
    <section class="finance-panel">
        <div class="finance-panel-head">
            <h2>Transactions</h2>
            <form class="finance-filters" method="GET">
                <select name="direction"><option value="">All directions</option><option value="Inflow" @selected(request('direction') === 'Inflow')>Inflow</option><option value="Outflow" @selected(request('direction') === 'Outflow')>Outflow</option></select>
                <select name="source_module"><option value="">All modules</option>@foreach(['Sales','Commercial','Procurement','HR'] as $module)<option value="{{ $module }}" @selected(request('source_module') === $module)>{{ $module }}</option>@endforeach</select>
                <button class="finance-button secondary" type="submit">Filter</button>
            </form>
        </div>
        @include('finance.partials.table', [
            'headers' => ['Reference', 'Source', 'Account', 'Counterparty', 'Direction', 'Amount', 'Date', 'Evidence'],
            'rows' => $transactions->map(fn ($transaction) => [
                '<a class="finance-table-title" href="' . route('finance.transactions.show', $transaction) . '">' . e($transaction->reference) . '</a><span class="finance-muted">' . e($transaction->status) . '</span>',
                e($transaction->source_module . ' / ' . $transaction->source_type),
                e($transaction->account?->name ?: 'Unclassified'),
                e($transaction->counterparty_name ?: '-'),
                '<span class="finance-badge ' . ($transaction->direction === 'Inflow' ? 'success' : 'warning') . '">' . e($transaction->direction) . '</span>',
                e($transaction->currency . ' ' . number_format((float) $transaction->amount, 2)),
                e($transaction->transaction_date?->format('M d, Y') ?: '-'),
                e($transaction->evidence_status),
            ])->all()
        ])
        <div style="margin-top:12px">{{ $transactions->links() }}</div>
    </section>
</section>
@endsection
