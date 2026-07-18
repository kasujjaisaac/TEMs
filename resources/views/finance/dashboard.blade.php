@extends('layouts.app')

@section('content')
@include('finance.partials.style')

<section class="finance-page">
    <header class="finance-header">
        <div class="finance-title"><div class="finance-icon"><i class="fa-solid fa-scale-balanced"></i></div><div><h1>Finance Control Centre</h1><div class="finance-muted">Budget, revenue, expense, cash, receivables, payables and control exceptions from connected modules.</div></div></div>
        <div class="finance-actions">
            <form method="POST" action="{{ route('finance.sync') }}">@csrf<button class="finance-button secondary" type="submit"><i class="fa-solid fa-arrows-rotate"></i> Sync Modules</button></form>
            <a class="finance-button" href="{{ route('finance.transactions.index') }}"><i class="fa-solid fa-list-check"></i> Transaction Register</a>
        </div>
    </header>

    @if(session('success')) <div class="finance-alert success">{{ session('success') }}</div> @endif

    <section class="finance-grid">
        <div class="finance-card"><span>Revenue This Month</span><strong>UGX {{ number_format((float) $metrics['revenue_month'], 2) }}</strong></div>
        <div class="finance-card"><span>Expenses This Month</span><strong>UGX {{ number_format((float) $metrics['expense_month'], 2) }}</strong></div>
        <div class="finance-card"><span>Receivables</span><strong>UGX {{ number_format((float) $metrics['receivables'], 2) }}</strong></div>
        <div class="finance-card"><span>Payables</span><strong>UGX {{ number_format((float) $metrics['payables'], 2) }}</strong></div>
    </section>

    <section class="finance-split">
        <div class="finance-panel">
            <div class="finance-panel-head"><h2>Recent Financial Transactions</h2><span class="finance-muted">Synced from Sales, Commercial, Purchases and HR.</span></div>
            @include('finance.partials.table', [
                'headers' => ['Reference', 'Source', 'Counterparty', 'Direction', 'Amount', 'Status', 'Control'],
                'rows' => $recentTransactions->map(fn ($transaction) => [
                    '<a class="finance-table-title" href="' . route('finance.transactions.show', $transaction) . '">' . e($transaction->reference) . '</a><span class="finance-muted">' . e($transaction->transaction_date?->format('M d, Y')) . '</span>',
                    e($transaction->source_module . ' / ' . $transaction->source_type),
                    e($transaction->counterparty_name ?: '-'),
                    '<span class="finance-badge ' . ($transaction->direction === 'Inflow' ? 'success' : 'warning') . '">' . e($transaction->direction) . '</span>',
                    e($transaction->currency . ' ' . number_format((float) $transaction->amount, 2)),
                    e($transaction->status),
                    e($transaction->evidence_status),
                ])->all()
            ])
        </div>
        <div class="finance-panel">
            <div class="finance-panel-head"><h2>Financial Alerts</h2></div>
            @forelse($alerts as $alert)
                <div class="finance-alert {{ $alert['severity'] === 'High' ? 'danger' : 'warning' }}"><strong>{{ $alert['title'] }}</strong><br>{{ $alert['message'] }}</div>
            @empty
                <div class="finance-alert success">No critical financial exceptions detected from current synced data.</div>
            @endforelse
        </div>
    </section>

    <section class="finance-panel">
        <div class="finance-panel-head"><h2>Budget Control Lines</h2><a class="finance-button secondary" href="{{ route('finance.budgets.index') }}">Manage Budget</a></div>
        @include('finance.partials.table', [
            'headers' => ['Line', 'Account', 'Cost Centre', 'Budget', 'Committed', 'Actual', 'Available', 'Utilization'],
            'rows' => $budgetLines->map(fn ($line) => [
                '<strong class="finance-table-title">' . e($line->reference) . '</strong><span class="finance-muted">' . e($line->description) . '</span>',
                e($line->account?->name ?: '-'),
                e($line->costCentre?->name ?: '-'),
                e(number_format((float) $line->annual_budget, 2)),
                e(number_format((float) $line->committed_amount, 2)),
                e(number_format((float) $line->actual_spent, 2)),
                e(number_format($line->available_balance, 2)),
                '<span class="finance-badge ' . ($line->utilization_percentage >= 80 ? 'warning' : 'success') . '">' . e($line->utilization_percentage) . '%</span>',
            ])->all()
        ])
    </section>
</section>
@endsection
