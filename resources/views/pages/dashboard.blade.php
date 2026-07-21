@extends('layouts.app')

@section('content')
<section class="tems-command">
    <header class="tems-command-header">
        <div class="tems-command-title">
            <div class="tems-command-icon"><i class="fa-solid fa-chart-line"></i></div>
            <div>
                <span class="tems-command-eyebrow">Executive command</span>
                <h1>Business Overview</h1>
                <div class="tems-command-subtitle">Inventory value, customer health, supplier exposure, field work, and operational exceptions in one control view.</div>
            </div>
        </div>
        <div class="tems-command-actions">
            <a class="tems-button secondary" href="{{ route('crm.dashboard') }}"><i class="fa-solid fa-address-book"></i> CRM</a>
            <a class="tems-button secondary" href="{{ route('commercial.dashboard') }}"><i class="fa-solid fa-briefcase"></i> Commercial</a>
            <a class="tems-button" href="{{ route('finance.dashboard') }}"><i class="fa-solid fa-scale-balanced"></i> Finance</a>
        </div>
    </header>

    <section class="tems-kpi-grid">
        <article class="tems-kpi-card">
            <span>Inventory Capital</span>
            <strong>{{ number_format($inventory_value, 2) }} {{ $currency }}</strong>
            <small>{{ $product_count }} catalog items under stock control.</small>
        </article>
        <article class="tems-kpi-card">
            <span>Stock Exceptions</span>
            <strong>{{ $low_stock_count }}</strong>
            <small>{{ $near_reorder }} items are close to reorder pressure.</small>
        </article>
        <article class="tems-kpi-card">
            <span>Active Relationships</span>
            <strong>{{ $customer_count }}</strong>
            <small>{{ $credit_customer_count }} customer accounts have payment follow-up exposure.</small>
        </article>
        <article class="tems-kpi-card">
            <span>Supplier Network</span>
            <strong>{{ $supplier_count }}</strong>
            <small>{{ $credit_supplier_count }} supplier accounts have payable follow-up exposure.</small>
        </article>
        <article class="tems-kpi-card">
            <span>Today Installations</span>
            <strong>{{ $today_installations }}</strong>
            <small>Same-day field execution requiring customer readiness.</small>
        </article>
        <article class="tems-kpi-card">
            <span>Maintenance Queue</span>
            <strong>{{ $upcoming_maintenance }}</strong>
            <small>{{ $maintenance_due }} jobs are due or overdue.</small>
        </article>
        <article class="tems-kpi-card">
            <span>Warranty Watch</span>
            <strong>{{ $warranty_alerts }}</strong>
            <small>Assets expiring within the next 30 days.</small>
        </article>
        <article class="tems-kpi-card">
            <span>Operational Readiness</span>
            <strong>{{ max(0, 100 - ($low_stock_count + $maintenance_due)) }}%</strong>
            <small>Computed from current stock and maintenance exceptions.</small>
        </article>
    </section>

    <section class="tems-dashboard-grid">
        <article class="tems-panel">
            <div class="tems-panel-head">
                <div>
                    <span class="tems-panel-kicker">Capital trend</span>
                    <h2>Inventory Value Movement</h2>
                </div>
                <span class="tems-status">6 point view</span>
            </div>
            <div class="chart-shell">{!! $chartSvg !!}</div>
        </article>

        <aside class="tems-panel">
            <div class="tems-panel-head">
                <div>
                    <span class="tems-panel-kicker">Command queue</span>
                    <h2>Priority Exceptions</h2>
                </div>
            </div>
            <div class="tems-work-list">
                <div class="tems-work-card">
                    <div><span class="tems-list-label">Stock control</span><small class="tems-muted">Low stock items needing replenishment.</small></div>
                    <strong>{{ $low_stock_count }}</strong>
                </div>
                <div class="tems-work-card">
                    <div><span class="tems-list-label">Customer credit</span><small class="tems-muted">Accounts requiring collection follow-up.</small></div>
                    <strong>{{ $credit_customer_count }}</strong>
                </div>
                <div class="tems-work-card">
                    <div><span class="tems-list-label">Supplier credit</span><small class="tems-muted">Supplier balances needing payment discipline.</small></div>
                    <strong>{{ $credit_supplier_count }}</strong>
                </div>
                <div class="tems-work-card">
                    <div><span class="tems-list-label">Field service</span><small class="tems-muted">Maintenance due today or earlier.</small></div>
                    <strong>{{ $maintenance_due }}</strong>
                </div>
            </div>
        </aside>
    </section>

    <section class="tems-two-column">
        <article class="tems-panel">
            <div class="tems-panel-head">
                <div>
                    <span class="tems-panel-kicker">Work launchpad</span>
                    <h2>Fast Module Actions</h2>
                </div>
            </div>
            <div class="tems-mini-grid">
                <a class="tems-insight-card" href="{{ route('crm.accounts.index') }}">
                    <span class="tems-list-label">CRM</span>
                    <strong>Accounts</strong>
                    <small class="tems-muted">Review customer ownership and account health.</small>
                </a>
                <a class="tems-insight-card" href="{{ route('commercial.leads.index') }}">
                    <span class="tems-list-label">Commercial</span>
                    <strong>Leads</strong>
                    <small class="tems-muted">Qualify active demand and next actions.</small>
                </a>
                <a class="tems-insight-card" href="{{ route('finance.transactions.index') }}">
                    <span class="tems-list-label">Finance</span>
                    <strong>Ledger</strong>
                    <small class="tems-muted">Check transaction control and evidence status.</small>
                </a>
            </div>
        </article>

        <article class="tems-panel">
            <div class="tems-panel-head">
                <div>
                    <span class="tems-panel-kicker">Operating summary</span>
                    <h2>System Health</h2>
                </div>
            </div>
            <div class="tems-table-wrap">
                <table class="tems-table">
                    <tbody>
                        <tr><th>Area</th><th>Live Count</th><th>Signal</th></tr>
                        <tr><td>Customers</td><td>{{ $customer_count }}</td><td>Active account base</td></tr>
                        <tr><td>Suppliers</td><td>{{ $supplier_count }}</td><td>Procurement network</td></tr>
                        <tr><td>Products</td><td>{{ $product_count }}</td><td>Catalog breadth</td></tr>
                        <tr><td>Installations</td><td>{{ $today_installations }}</td><td>Today field execution</td></tr>
                        <tr><td>Maintenance</td><td>{{ $upcoming_maintenance }}</td><td>Upcoming service demand</td></tr>
                    </tbody>
                </table>
            </div>
        </article>
    </section>
</section>
@endsection
