@extends('layouts.app')

@section('content')
@include('commercial.partials.style')

<section class="commercial-page tems-command">
    <header class="tems-command-header">
        <div class="tems-command-title">
            <div class="tems-command-icon"><i class="fa-solid fa-address-book"></i></div>
            <div>
                <span class="tems-command-eyebrow">CRM & customer accounts</span>
                <h1>Customer Health Command</h1>
                <div class="tems-command-subtitle">Account ownership, relationship status, commercial linkage, opportunity context, and support follow-up in one customer operating view.</div>
            </div>
        </div>
        <div class="tems-command-actions">
            <a class="tems-button" href="{{ route('crm.accounts.index') }}"><i class="fa-solid fa-users"></i> Accounts</a>
            <a class="tems-button secondary" href="{{ route('commercial.organizations.index') }}"><i class="fa-solid fa-building"></i> Organizations</a>
        </div>
    </header>

    <section class="tems-kpi-grid">
        <article class="tems-kpi-card"><span>Customer Accounts</span><strong>{{ $metrics['customer_accounts'] }}</strong><small>Total relationship records owned by CRM.</small></article>
        <article class="tems-kpi-card"><span>Active Accounts</span><strong>{{ $metrics['active_accounts'] }}</strong><small>Customers currently available for operational work.</small></article>
        <article class="tems-kpi-card"><span>Commercial Linked</span><strong>{{ $metrics['linked_commercial_accounts'] }}</strong><small>Accounts connected to commercial organizations.</small></article>
        <article class="tems-kpi-card"><span>Open Opportunities</span><strong>{{ $metrics['open_opportunities'] }}</strong><small>Live commercial value connected to customer relationships.</small></article>
        <article class="tems-kpi-card"><span>Open CRM Leads</span><strong>{{ $metrics['open_crm_leads'] }}</strong><small>Demand records still requiring CRM attention.</small></article>
        <article class="tems-kpi-card"><span>Support Follow-ups</span><strong>{{ $metrics['open_support_tickets'] }}</strong><small>Customer service items that may affect retention.</small></article>
    </section>

    <section class="tems-dashboard-grid">
        <article class="tems-panel">
            <div class="tems-panel-head">
                <div>
                    <span class="tems-panel-kicker">Relationship desk</span>
                    <h2>Recent Customer Accounts</h2>
                </div>
                <a class="tems-button secondary" href="{{ route('crm.accounts.index') }}">View All</a>
            </div>
            @include('commercial.partials.table', [
                'headers' => ['Account', 'Contact', 'Terms', 'Status'],
                'rows' => $recentAccounts->map(fn ($account) => [
                    '<strong class="commercial-table-title"><a href="' . route('crm.accounts.show', $account->id) . '">' . e($account->company_name ?: $account->name) . '</a></strong><span class="commercial-muted">' . e($account->customer_code ?: 'No account code') . '</span>',
                    e($account->contact_person ?: '-') . '<br><span class="commercial-muted">' . e($account->phone ?: $account->email ?: '-') . '</span>',
                    e(($account->payment_terms ?: '-') . ' / ' . ($account->credit_status ?: '-')),
                    '<span class="commercial-badge ' . ($account->is_active ? 'success' : 'warning') . '">' . ($account->is_active ? 'Active' : 'Inactive') . '</span>',
                ])->all()
            ])
        </article>

        <aside class="tems-panel">
            <div class="tems-panel-head">
                <div>
                    <span class="tems-panel-kicker">Health signals</span>
                    <h2>Account Attention Queue</h2>
                </div>
            </div>
            <div class="tems-work-list">
                <div class="tems-work-card">
                    <div><span class="tems-list-label">Commercial linkage</span><small class="tems-muted">Accounts connected to organization records.</small></div>
                    <strong>{{ $metrics['linked_commercial_accounts'] }}</strong>
                </div>
                <div class="tems-work-card">
                    <div><span class="tems-list-label">Open CRM leads</span><small class="tems-muted">Leads that can become customer records.</small></div>
                    <strong>{{ $metrics['open_crm_leads'] }}</strong>
                </div>
                <div class="tems-work-card">
                    <div><span class="tems-list-label">Support follow-ups</span><small class="tems-muted">Service records that may affect account health.</small></div>
                    <strong>{{ $metrics['open_support_tickets'] }}</strong>
                </div>
                <div class="tems-work-card">
                    <div><span class="tems-list-label">Opportunity context</span><small class="tems-muted">Commercial work visible from customer records.</small></div>
                    <strong>{{ $metrics['open_opportunities'] }}</strong>
                </div>
            </div>
        </aside>
    </section>

    <section class="tems-two-column">
        <article class="tems-panel">
            <div class="tems-panel-head">
                <div>
                    <span class="tems-panel-kicker">Commercial context</span>
                    <h2>Recent Opportunities</h2>
                </div>
                <span class="tems-status">Source owned by Commercial</span>
            </div>
            @include('commercial.partials.table', [
                'headers' => ['Opportunity', 'Organization', 'Stage', 'Value'],
                'rows' => $recentOpportunities->map(fn ($opportunity) => [
                    '<strong class="commercial-table-title"><a href="' . route('commercial.opportunities.show', $opportunity->id) . '">' . e($opportunity->reference) . '</a></strong><span class="commercial-muted">' . e($opportunity->title) . '</span>',
                    e($opportunity->organization_name ?: '-'),
                    '<span class="commercial-badge">' . e($opportunity->current_stage) . '</span>',
                    e($opportunity->currency ?: 'UGX') . ' ' . number_format((float) $opportunity->estimated_value, 2),
                ])->all()
            ])
        </article>

        <article class="tems-panel">
            <div class="tems-panel-head">
                <div>
                    <span class="tems-panel-kicker">Operating model</span>
                    <h2>Module Responsibilities</h2>
                </div>
                <span class="tems-status">No duplicated ownership</span>
            </div>
            @include('commercial.partials.table', [
                'headers' => ['Area', 'Owns', 'Hands Off'],
                'rows' => [
                    ['Commercial Operations', 'Campaigns, leads, opportunities, proposals, quotations, contracts, sales handoff', 'Won or active customer relationship is linked into CRM & Customer Accounts'],
                    ['CRM & Customer Accounts', 'Customer profile, contacts, account history, communication trail, account health, credit/payment relationship', 'Invoices, payments, and accounting controls remain in Finance'],
                    ['Customer 360', 'One account view that displays linked commercial, sales, finance, and support history', 'Each source module keeps its operational records'],
                ],
            ])
        </article>
    </section>
</section>
@endsection
