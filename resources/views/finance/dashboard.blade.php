@extends('layouts.app')

@section('content')
@include('finance.partials.style')

<section class="finance-page tems-command">
    <header class="tems-command-header">
        <div class="tems-command-title">
            <div class="tems-command-icon"><i class="fa-solid fa-scale-balanced"></i></div>
            <div>
                <span class="tems-command-eyebrow">Finance control</span>
                <h1>Financial Command Centre</h1>
                <div class="tems-command-subtitle">Revenue, expense, cash discipline, receivables, payables, budget pressure, transaction evidence, and synced control exceptions.</div>
            </div>
        </div>
        <div class="tems-command-actions">
            <form method="POST" action="{{ route('finance.sync') }}">
                @csrf
                <button class="tems-button secondary" type="submit"><i class="fa-solid fa-arrows-rotate"></i> Sync Modules</button>
            </form>
            <a class="tems-button" href="{{ route('finance.transactions.index') }}"><i class="fa-solid fa-list-check"></i> Transaction Register</a>
        </div>
    </header>

    @if(session('success'))
        <div class="finance-alert success">{{ session('success') }}</div>
    @endif

    <section class="tems-kpi-grid">
        <article class="tems-kpi-card"><span>Revenue This Month</span><strong>UGX {{ number_format((float) $metrics['revenue_month'], 2) }}</strong><small>Recognized inflow from connected modules.</small></article>
        <article class="tems-kpi-card"><span>Expenses This Month</span><strong>UGX {{ number_format((float) $metrics['expense_month'], 2) }}</strong><small>Current month outflow under review.</small></article>
        <article class="tems-kpi-card"><span>Receivables</span><strong>UGX {{ number_format((float) $metrics['receivables'], 2) }}</strong><small>Customer money due to the business.</small></article>
        <article class="tems-kpi-card"><span>Payables</span><strong>UGX {{ number_format((float) $metrics['payables'], 2) }}</strong><small>Supplier and operational obligations pending.</small></article>
    </section>

    <section class="tems-dashboard-grid">
        <article class="tems-panel">
            <div class="tems-panel-head">
                <div>
                    <span class="tems-panel-kicker">Ledger activity</span>
                    <h2>Recent Financial Transactions</h2>
                    <div class="tems-muted">Synced from Sales, Commercial, Purchases, and HR.</div>
                </div>
                <a class="tems-button secondary" href="{{ route('finance.transactions.index') }}">Open Ledger</a>
            </div>
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
        </article>

        <aside class="tems-panel">
            <div class="tems-panel-head">
                <div>
                    <span class="tems-panel-kicker">Control exceptions</span>
                    <h2>Financial Alerts</h2>
                </div>
            </div>
            <div class="tems-work-list">
                @forelse($alerts as $alert)
                    <div class="tems-work-card">
                        <div>
                            <span class="tems-list-label">{{ $alert['severity'] }}</span>
                            <strong class="tems-panel-title">{{ $alert['title'] }}</strong>
                            <small class="tems-muted">{{ $alert['message'] }}</small>
                        </div>
                        <span class="tems-status {{ $alert['severity'] === 'High' ? 'danger' : 'warning' }}">Review</span>
                    </div>
                @empty
                    <div class="tems-work-card">
                        <div>
                            <span class="tems-list-label">Control state</span>
                            <strong class="tems-panel-title">No critical exceptions</strong>
                            <small class="tems-muted">Current synced data has no high-priority finance alerts.</small>
                        </div>
                        <span class="tems-status success">Clear</span>
                    </div>
                @endforelse
            </div>
        </aside>
    </section>

    <section class="tems-panel">
        <div class="tems-panel-head">
            <div>
                <span class="tems-panel-kicker">Budget governance</span>
                <h2>Budget Control Lines</h2>
            </div>
            <a class="tems-button secondary" href="{{ route('finance.budgets.index') }}">Manage Budget</a>
        </div>
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
