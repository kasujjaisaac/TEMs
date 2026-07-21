@extends('layouts.app')

@section('content')
@include('commercial.partials.style')

<section class="commercial-page">
    <header class="commercial-header">
        <div class="commercial-title">
            <div class="commercial-title-icon"><i class="fa-solid fa-address-book"></i></div>
            <div>
                <h1>CRM & Customer Accounts</h1>
                <div class="commercial-muted">Account ownership, customer history, commercial links, and relationship health in one customer record.</div>
            </div>
        </div>
        <div class="commercial-actions">
            <a class="commercial-button" href="{{ route('crm.accounts.index') }}"><i class="fa-solid fa-users"></i> Accounts</a>
            <a class="commercial-button secondary" href="{{ route('commercial.organizations.index') }}"><i class="fa-solid fa-building"></i> Commercial Organizations</a>
        </div>
    </header>

    <section class="commercial-grid">
        <div class="commercial-card"><span>Customer Accounts</span><strong>{{ $metrics['customer_accounts'] }}</strong></div>
        <div class="commercial-card"><span>Active Accounts</span><strong>{{ $metrics['active_accounts'] }}</strong></div>
        <div class="commercial-card"><span>Commercial Linked</span><strong>{{ $metrics['linked_commercial_accounts'] }}</strong></div>
        <div class="commercial-card"><span>Open Opportunities</span><strong>{{ $metrics['open_opportunities'] }}</strong></div>
        <div class="commercial-card"><span>Open CRM Leads</span><strong>{{ $metrics['open_crm_leads'] }}</strong></div>
        <div class="commercial-card"><span>Support Follow-ups</span><strong>{{ $metrics['open_support_tickets'] }}</strong></div>
    </section>

    <section class="commercial-split">
        <div class="commercial-panel">
            <div class="commercial-panel-head">
                <h2>Recent Customer Accounts</h2>
                <a class="commercial-button secondary" href="{{ route('crm.accounts.index') }}">View All</a>
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
        </div>

        <div class="commercial-panel">
            <div class="commercial-panel-head">
                <h2>Commercial Context</h2>
                <span class="commercial-muted">Deal records stay in Commercial Operations</span>
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
        </div>
    </section>

    <section class="commercial-panel">
        <div class="commercial-panel-head">
            <h2>Module Responsibilities</h2>
            <span class="commercial-muted">No duplicated customer ownership</span>
        </div>
        @include('commercial.partials.table', [
            'headers' => ['Area', 'Owns', 'Hands Off'],
            'rows' => [
                ['Commercial Operations', 'Campaigns, leads, opportunities, proposals, quotations, contracts, sales handoff', 'Won or active customer relationship is linked into CRM & Customer Accounts'],
                ['CRM & Customer Accounts', 'Customer profile, contacts, account history, communication trail, account health, credit/payment relationship', 'Invoices, payments, and accounting controls remain in Finance'],
                ['Customer 360', 'One account view that displays linked commercial, sales, finance, and support history', 'Each source module keeps its operational records'],
            ],
        ])
    </section>
</section>
@endsection
