@extends('layouts.app')

@section('content')
@include('finance.partials.style')
<section class="finance-page">
    <header class="finance-header">
        <div class="finance-title"><div class="finance-icon"><i class="fa-solid fa-receipt"></i></div><div><h1>{{ $transaction->reference }}</h1><div class="finance-muted">{{ $transaction->source_module }} / {{ $transaction->source_type }} / {{ $transaction->status }}</div></div></div>
        <a class="finance-button secondary" href="{{ route('finance.transactions.index') }}"><i class="fa-solid fa-arrow-left"></i> Back</a>
    </header>
    <section class="finance-grid">
        <div class="finance-card"><span>Direction</span><strong>{{ $transaction->direction }}</strong></div>
        <div class="finance-card"><span>Amount</span><strong>{{ $transaction->currency }} {{ number_format((float) $transaction->amount, 2) }}</strong></div>
        <div class="finance-card"><span>Approval</span><strong>{{ $transaction->approval_status }}</strong></div>
        <div class="finance-card"><span>Evidence</span><strong>{{ $transaction->evidence_status }}</strong></div>
    </section>
    <section class="finance-panel">
        <div class="finance-panel-head"><h2>Universal Transaction Panel</h2></div>
        @include('finance.partials.table', [
            'headers' => ['Field', 'Value'],
            'rows' => [
                ['Financial Account', e($transaction->account?->name ?: 'Unclassified')],
                ['Budget Line', e($transaction->budgetLine?->reference ?: 'Not linked')],
                ['Cost Centre', e($transaction->costCentre?->name ?: 'Not linked')],
                ['Counterparty', e(($transaction->counterparty_type ?: '-') . ' / ' . ($transaction->counterparty_name ?: '-'))],
                ['Transaction Date', e($transaction->transaction_date?->format('M d, Y') ?: '-')],
                ['Due Date', e($transaction->due_date?->format('M d, Y') ?: '-')],
                ['Description', e($transaction->description ?: '-')],
            ]
        ])
    </section>
</section>
@endsection
