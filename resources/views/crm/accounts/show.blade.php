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

    <section class="commercial-split">
        <div class="commercial-panel">
            <div class="commercial-panel-head"><h2>Account Plan</h2><span class="commercial-muted">Ownership, growth, retention, and review discipline</span></div>
            @if($accountPlan)
                @include('commercial.partials.table', [
                    'headers' => ['Field', 'Value'],
                    'rows' => [
                        ['Relationship Stage', e($accountPlan->relationship_stage)],
                        ['Health / Risk', e($accountPlan->health_status . ' / ' . $accountPlan->risk_level)],
                        ['Objectives', e($accountPlan->objectives ?: '-')],
                        ['Growth Strategy', e($accountPlan->growth_strategy ?: '-')],
                        ['Retention Strategy', e($accountPlan->retention_strategy ?: '-')],
                        ['Next Review', e($accountPlan->next_review_on ?: '-')],
                    ],
                ])
            @endif
            @if(auth()->user()?->hasPermission('crm.accounts.manage') || auth()->user()?->hasPermission('customers.manage'))
                <form class="commercial-form" method="POST" action="{{ route('crm.accounts.account_plan.store', $account->id) }}">
                    @csrf
                    <div class="commercial-field"><label>Relationship Stage</label><input name="relationship_stage" value="{{ $accountPlan->relationship_stage ?? 'Active' }}"></div>
                    <div class="commercial-field"><label>Risk Level</label><select name="risk_level"><option>Low</option><option @selected(($accountPlan->risk_level ?? '') === 'Medium')>Medium</option><option @selected(($accountPlan->risk_level ?? '') === 'High')>High</option></select></div>
                    <div class="commercial-field full"><label>Objectives</label><textarea name="objectives">{{ $accountPlan->objectives ?? '' }}</textarea></div>
                    <div class="commercial-field full"><label>Growth Strategy</label><textarea name="growth_strategy">{{ $accountPlan->growth_strategy ?? '' }}</textarea></div>
                    <div class="commercial-field full"><label>Retention Strategy</label><textarea name="retention_strategy">{{ $accountPlan->retention_strategy ?? '' }}</textarea></div>
                    <div class="commercial-field"><label>Next Review</label><input name="next_review_on" type="date" value="{{ $accountPlan->next_review_on ?? now()->addMonth()->toDateString() }}"></div>
                    <div class="commercial-field full"><button class="commercial-button" type="submit">Save Account Plan</button></div>
                </form>
            @endif
        </div>
        <div class="commercial-panel">
            <div class="commercial-panel-head"><h2>Customer Health</h2><span class="commercial-muted">Pipeline, revenue, tickets, and risk</span></div>
            @if(auth()->user()?->hasPermission('crm.health.view') || auth()->user()?->hasPermission('crm.accounts.manage'))
                <form method="POST" action="{{ route('crm.accounts.health.capture', $account->id) }}">@csrf<button class="commercial-button" type="submit">Capture Health</button></form>
            @endif
            @include('commercial.partials.table', [
                'headers' => ['Date', 'Score', 'Status', 'Risk', 'Pipeline', 'Revenue'],
                'rows' => $healthSnapshots->map(fn ($snapshot) => [
                    e($snapshot->snapshot_date),
                    e($snapshot->health_score . '%'),
                    '<span class="commercial-badge success">' . e($snapshot->health_status) . '</span>',
                    e($snapshot->risk_level),
                    number_format((float) $snapshot->open_pipeline_value, 2),
                    number_format((float) $snapshot->lifetime_revenue, 2),
                ])->all()
            ])
        </div>
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
                'rows' => $timeline->map(fn ($event) => [
                    '<strong class="commercial-table-title">' . e($event->title) . '</strong><span class="commercial-muted">' . e($event->event_type . ' / ' . $event->source_module) . '</span>',
                    e($event->occurred_at ?: '-'),
                    e($event->description ?: '-'),
                ])->concat($crmLeads->map(fn ($lead) => [
                    '<strong class="commercial-table-title">' . e($lead->contact_name) . '</strong><span class="commercial-muted">' . e($lead->source) . '</span>',
                    '<span class="commercial-badge">' . e($lead->status) . '</span>',
                    number_format((float) $lead->estimated_value, 2),
                ]))->concat($payments->map(fn ($payment) => [
                    '<strong class="commercial-table-title">' . e($payment->invoice_number) . '</strong><span class="commercial-muted">' . e($payment->payment_date ?: '-') . '</span>',
                    e($payment->method ?: 'Payment'),
                    number_format((float) $payment->amount, 2),
                ]))->all()
            ])
        </div>
    </section>

    <section class="commercial-split">
        <div class="commercial-panel">
            <div class="commercial-panel-head"><h2>Renewals</h2><span class="commercial-muted">Retention opportunities</span></div>
            @include('commercial.partials.table', [
                'headers' => ['Reference', 'Due', 'Value', 'Status'],
                'rows' => $renewals->map(fn ($renewal) => [e($renewal->reference), e($renewal->renewal_due_on), e($renewal->currency . ' ' . number_format((float) $renewal->renewal_value, 2)), e($renewal->status)])->all()
            ])
        </div>
        <div class="commercial-panel">
            <div class="commercial-panel-head"><h2>Expansion</h2><span class="commercial-muted">Upsell and cross-sell pipeline</span></div>
            @include('commercial.partials.table', [
                'headers' => ['Reference', 'Type', 'Title', 'Value', 'Status'],
                'rows' => $expansions->map(fn ($expansion) => [e($expansion->reference), e($expansion->expansion_type), e($expansion->title), e($expansion->currency . ' ' . number_format((float) $expansion->estimated_value, 2)), e($expansion->status)])->all()
            ])
        </div>
    </section>

    <section class="commercial-split">
        <div class="commercial-panel">
            <div class="commercial-panel-head"><h2>Branches</h2><span class="commercial-muted">Customer locations and contacts</span></div>
            @include('commercial.partials.table', [
                'headers' => ['Name', 'City', 'Contact', 'Status'],
                'rows' => $branches->map(fn ($branch) => [e($branch->name), e($branch->city ?: '-'), e(($branch->contact_person ?: '-') . ' / ' . ($branch->phone ?: '-')), e($branch->status)])->all()
            ])
            @if(auth()->user()?->hasPermission('crm.accounts.manage') || auth()->user()?->hasPermission('customers.manage'))
                <form class="commercial-form" method="POST" action="{{ route('crm.accounts.branches.store', $account->id) }}">
                    @csrf
                    <div class="commercial-field"><label>Name</label><input name="name" required></div>
                    <div class="commercial-field"><label>City</label><input name="city"></div>
                    <div class="commercial-field"><label>Phone</label><input name="phone"></div>
                    <div class="commercial-field full"><label>Address</label><textarea name="address"></textarea></div>
                    <div class="commercial-field full"><button class="commercial-button" type="submit">Add Branch</button></div>
                </form>
            @endif
        </div>
        <div class="commercial-panel">
            <div class="commercial-panel-head"><h2>Documents & Subscriptions</h2><span class="commercial-muted">Customer proof and product ownership</span></div>
            @include('commercial.partials.table', [
                'headers' => ['Record', 'Type / Plan', 'Status', 'Date'],
                'rows' => $customerDocuments->map(fn ($document) => [e($document->title), e($document->document_type), e($document->status), e($document->expires_on ?: '-')])
                    ->concat($subscriptions->map(fn ($subscription) => [e($subscription->product_name), e($subscription->plan_name ?: $subscription->billing_frequency ?: '-'), e($subscription->status), e($subscription->renews_on ?: '-')]))
                    ->all()
            ])
            @if(auth()->user()?->hasPermission('crm.accounts.manage') || auth()->user()?->hasPermission('customers.manage'))
                <form class="commercial-form" method="POST" action="{{ route('crm.accounts.documents.store', $account->id) }}">
                    @csrf
                    <div class="commercial-field"><label>Document Type</label><input name="document_type" required></div>
                    <div class="commercial-field double"><label>Title</label><input name="title" required></div>
                    <div class="commercial-field"><label>Reference</label><input name="reference"></div>
                    <div class="commercial-field full"><button class="commercial-button secondary" type="submit">Register Document</button></div>
                </form>
                <form class="commercial-form" method="POST" action="{{ route('crm.accounts.subscriptions.store', $account->id) }}">
                    @csrf
                    <div class="commercial-field"><label>Product</label><input name="product_name" required></div>
                    <div class="commercial-field"><label>Plan</label><input name="plan_name"></div>
                    <div class="commercial-field"><label>Renews On</label><input name="renews_on" type="date"></div>
                    <div class="commercial-field"><label>Amount</label><input name="recurring_amount" type="number" min="0" step="0.01"></div>
                    <div class="commercial-field full"><button class="commercial-button" type="submit">Add Subscription</button></div>
                </form>
            @endif
        </div>
    </section>
</section>
@endsection
