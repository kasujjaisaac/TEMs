@extends('layouts.app')

@section('content')
@include('commercial.partials.style')

<section class="commercial-page">
    <header class="commercial-header">
        <div class="commercial-title">
            <div class="commercial-title-icon"><i class="fa-solid fa-circle-nodes"></i></div>
            <div>
                <h1>{{ $account->company_name ?: $account->name }}</h1>
                <div class="commercial-muted">{{ $account->customer_code ?: 'Customer account' }} · Customer 360</div>
            </div>
        </div>
        <div class="commercial-actions">
            <a class="commercial-button secondary" href="{{ route('crm.accounts.index') }}"><i class="fa-solid fa-arrow-left"></i> Accounts</a>
            @if($commercialOrganization)
                <a class="commercial-button" href="{{ route('commercial.organizations.show', $commercialOrganization->id) }}"><i class="fa-solid fa-building"></i> Commercial Record</a>
            @endif
        </div>
    </header>

    <section class="commercial-grid">
        <div class="commercial-card"><span>Status</span><strong>{{ $account->is_active ? 'Active' : 'Inactive' }}</strong></div>
        <div class="commercial-card"><span>Account Group</span><strong>{{ $account->customer_group ?: '-' }}</strong></div>
        <div class="commercial-card"><span>Payment Terms</span><strong>{{ $account->payment_terms ?: '-' }}</strong></div>
        <div class="commercial-card"><span>Credit Status</span><strong>{{ $account->credit_status ?: '-' }}</strong></div>
        <div class="commercial-card"><span>Opportunities</span><strong>{{ $opportunities->count() }}</strong></div>
        <div class="commercial-card"><span>Invoices</span><strong>{{ $invoices->count() }}</strong></div>
    </section>

    <section class="commercial-split">
        <div class="commercial-panel">
            <div class="commercial-panel-head">
                <h2>Account Profile</h2>
                <span class="commercial-muted">CRM source of truth</span>
            </div>
            @include('commercial.partials.table', [
                'headers' => ['Field', 'Value'],
                'rows' => [
                    ['Legal / Display Name', e(($account->company_name ?: '-') . ' / ' . ($account->name ?: '-'))],
                    ['Primary Contact', e($account->contact_person ?: '-')],
                    ['Email / Phone', e(($account->email ?: '-') . ' / ' . ($account->phone ?: '-'))],
                    ['Address', e($account->billing_address ?: $account->address ?: '-')],
                    ['TIN / Type', e(($account->tin_number ?: '-') . ' / ' . ($account->customer_type ?: '-'))],
                    ['Account Manager', e($account->account_manager ?: '-')],
                    ['Customer Source', e($account->customer_source ?: '-')],
                    ['Source of Truth', e(($account->source_of_truth ?? null) ?: 'CRM Customer Account')],
                    ['Identity Status', e(($account->enterprise_identity_status ?? null) ?: 'Canonical')],
                ],
            ])
        </div>

        <div class="commercial-panel">
            <div class="commercial-panel-head">
                <h2>Commercial Link</h2>
                <span class="commercial-muted">Prospect and deal context</span>
            </div>
            @include('commercial.partials.table', [
                'headers' => ['Field', 'Value'],
                'rows' => [
                    ['Commercial Reference', e(($account->commercial_reference ?? null) ?: ($commercialOrganization->reference ?? '-'))],
                    ['Commercial Organization', $commercialOrganization ? '<a href="' . route('commercial.organizations.show', $commercialOrganization->id) . '">' . e($commercialOrganization->legal_name) . '</a>' : '-'],
                    ['Relationship Score', e($commercialOrganization->relationship_score ?? '-')],
                    ['Sync Status', e(($account->commercial_sync_status ?? null) ?: '-')],
                    ['Last Synced', e(($account->commercial_synced_at ?? null) ?: '-')],
                ],
            ])
        </div>
    </section>

    <section class="commercial-panel">
        <div class="commercial-panel-head">
            <h2>Enterprise Identity Links</h2>
            <span class="commercial-muted">One customer account, many verified source records</span>
        </div>
        @include('commercial.partials.table', [
            'headers' => ['Source', 'Reference', 'Match', 'Status'],
            'rows' => $identityLinks->map(fn ($link) => [
                e($link->source_table . ' #' . $link->source_id),
                e($link->source_reference ?: '-'),
                e($link->match_method . ' / ' . $link->confidence . '%'),
                '<span class="commercial-badge success">' . e($link->status) . '</span>',
            ])->all()
        ])
    </section>

    <section class="commercial-panel">
        <div class="commercial-panel-head">
            <h2>Linked Commercial Opportunities</h2>
            <span class="commercial-muted">Revenue motion remains owned by Commercial Operations</span>
        </div>
        @include('commercial.partials.table', [
            'headers' => ['Opportunity', 'Stage', 'Value', 'Weighted', 'Close Date'],
            'rows' => $opportunities->map(fn ($opportunity) => [
                '<strong class="commercial-table-title"><a href="' . route('commercial.opportunities.show', $opportunity->id) . '">' . e($opportunity->reference) . '</a></strong><span class="commercial-muted">' . e($opportunity->title) . '</span>',
                '<span class="commercial-badge">' . e($opportunity->current_stage) . '</span>',
                e($opportunity->currency ?: 'UGX') . ' ' . number_format((float) $opportunity->estimated_value, 2),
                e($opportunity->currency ?: 'UGX') . ' ' . number_format((float) $opportunity->weighted_value, 2),
                e($opportunity->expected_close_date ?: '-'),
            ])->all()
        ])
    </section>

    <section class="commercial-split">
        <div class="commercial-panel">
            <div class="commercial-panel-head"><h2>Invoices</h2><span class="commercial-muted">Finance owns billing records</span></div>
            @include('commercial.partials.table', [
                'headers' => ['Invoice', 'Type', 'Date', 'Status', 'Total'],
                'rows' => $invoices->map(fn ($invoice) => [
                    e($invoice->invoice_number),
                    e($invoice->invoice_type),
                    e($invoice->invoice_date ?: '-'),
                    '<span class="commercial-badge">' . e($invoice->status) . '</span>',
                    number_format((float) $invoice->total, 2),
                ])->all()
            ])
        </div>

        <div class="commercial-panel">
            <div class="commercial-panel-head"><h2>Relationship Timeline</h2><span class="commercial-muted">CRM leads and payments</span></div>
            @include('commercial.partials.table', [
                'headers' => ['Record', 'Status', 'Value / Amount'],
                'rows' => $crmLeads->map(fn ($lead) => [
                    '<strong class="commercial-table-title">' . e($lead->contact_name) . '</strong><span class="commercial-muted">' . e($lead->source) . '</span>',
                    '<span class="commercial-badge">' . e($lead->status) . '</span>',
                    number_format((float) $lead->estimated_value, 2),
                ])->concat($payments->map(fn ($payment) => [
                    '<strong class="commercial-table-title">' . e($payment->invoice_number) . '</strong><span class="commercial-muted">' . e($payment->payment_date ?: '-') . '</span>',
                    e($payment->method ?: 'Payment'),
                    number_format((float) $payment->amount, 2),
                ]))->all()
            ])
        </div>
    </section>
</section>
@endsection
