@extends('layouts.app')

@section('content')
@include('commercial.partials.style')

<section class="commercial-page">
    <header class="commercial-header">
        <div class="commercial-title">
            <div class="commercial-title-icon"><i class="fa-solid fa-building"></i></div>
            <div>
                <h1>{{ $organization->legal_name }}</h1>
                <div class="commercial-muted">{{ $organization->reference }} · {{ $organization->customer_status }}</div>
            </div>
        </div>
        <div class="commercial-actions">
            <a class="commercial-button secondary" href="{{ route('commercial.organizations.index') }}"><i class="fa-solid fa-arrow-left"></i> Back</a>
            @if(auth()->user()?->hasPermission('commercial.organizations.update'))
                <a class="commercial-button secondary" href="{{ route('commercial.organizations.edit', $organization) }}"><i class="fa-solid fa-pen-to-square"></i> Edit</a>
                <form method="POST" action="{{ route('commercial.organizations.sync_customer', $organization) }}" style="display:inline-flex;">
                    @csrf
                    <button class="commercial-button secondary" type="submit"><i class="fa-solid fa-rotate"></i> Sync Customer</button>
                </form>
            @endif
            <a class="commercial-button secondary" href="{{ route('commercial.activities.create', ['related_type' => App\Models\Commercial\CommercialOrganization::class, 'related_id' => $organization->id]) }}"><i class="fa-solid fa-list-check"></i> Activity</a>
            <a class="commercial-button" href="{{ route('commercial.stakeholders.create', ['organization_id' => $organization->id]) }}"><i class="fa-solid fa-user-plus"></i> Stakeholder</a>
        </div>
    </header>

    @if(session('success')) <div class="commercial-alert success">{{ session('success') }}</div> @endif

    <section class="commercial-grid">
        <div class="commercial-card"><span>Status</span><strong>{{ $organization->customer_status }}</strong></div>
        <div class="commercial-card"><span>Account Manager</span><strong>{{ $organization->accountManager?->name ?: '-' }}</strong></div>
        <div class="commercial-card"><span>Stakeholders</span><strong>{{ $organization->stakeholders->count() }}</strong></div>
        <div class="commercial-card"><span>Opportunities</span><strong>{{ $organization->opportunities->count() }}</strong></div>
        <div class="commercial-card"><span>Legacy Customer</span><strong>{{ $organization->legacy_customer_id ?: 'Not synced' }}</strong></div>
    </section>

    <section class="commercial-split">
        <div class="commercial-panel">
            <div class="commercial-panel-head"><h2>Overview</h2><span class="commercial-muted">Customer 360 foundation</span></div>
            @include('commercial.partials.table', [
                'headers' => ['Field', 'Value'],
                'rows' => [
                    ['Trading Name', e($organization->trading_name ?: '-')],
                    ['Legacy Customer Link', e($organization->legacy_customer_id ?: '-')],
                    ['Industry / Sector', e(($organization->industry ?: '-') . ' / ' . ($organization->sector ?: '-'))],
                    ['Email / Phone', e(($organization->primary_email ?: '-') . ' / ' . ($organization->primary_telephone ?: '-'))],
                    ['Location', e(trim(($organization->city ?: '') . ' ' . ($organization->district ?: '') . ' ' . ($organization->country ?: '')) ?: '-')],
                    ['Credit / Payment Terms', e(($organization->credit_status ?: '-') . ' / ' . ($organization->payment_terms ?: '-'))],
                ]
            ])
        </div>
        <div class="commercial-panel">
            <div class="commercial-panel-head"><h2>Stakeholders</h2><a class="commercial-button secondary" href="{{ route('commercial.stakeholders.create', ['organization_id' => $organization->id]) }}">Add</a></div>
            @include('commercial.partials.table', [
                'headers' => ['Name', 'Role', 'Contact'],
                'rows' => $organization->stakeholders->map(fn ($stakeholder) => [
                    '<a href="' . route('commercial.stakeholders.edit', $stakeholder) . '">' . e($stakeholder->full_name) . '</a>',
                    e($stakeholder->decision_role ?: '-'),
                    e($stakeholder->telephone ?: $stakeholder->email ?: '-'),
                ])->all()
            ])
        </div>
    </section>

    <section class="commercial-panel">
        <div class="commercial-panel-head"><h2>Opportunities</h2></div>
        @include('commercial.partials.table', [
            'headers' => ['Opportunity', 'Stage', 'Value', 'Weighted'],
            'rows' => $organization->opportunities->map(fn ($opportunity) => [
                '<a href="' . route('commercial.opportunities.show', $opportunity) . '">' . e($opportunity->reference) . '</a><br><span class="commercial-muted">' . e($opportunity->title) . '</span>',
                '<span class="commercial-badge">' . e($opportunity->current_stage) . '</span>',
                e($opportunity->currency) . ' ' . number_format((float) $opportunity->estimated_value, 2),
                e($opportunity->currency) . ' ' . number_format($opportunity->weighted_value, 2),
            ])->all()
        ])
    </section>

    <section class="commercial-panel">
        <div class="commercial-panel-head"><h2>Financial Bridge</h2></div>
        <p class="commercial-muted">Legacy customer ID links this professional commercial organization to existing CRM, Sales, Invoice, POS, Payment, and Customer records without merging schemas prematurely.</p>
        <span class="commercial-badge {{ $organization->legacy_customer_id ? 'success' : 'warning' }}">
            {{ $organization->legacy_customer_id ? 'Linked to legacy customer #' . $organization->legacy_customer_id : 'Not linked yet' }}
        </span>
        @if($organization->legacy_customer_id)
            <a class="commercial-button secondary" href="{{ url('customers_action.php?action=view&id=' . $organization->legacy_customer_id) }}" style="margin-left:8px;">Open Legacy Profile</a>
        @endif
    </section>
</section>
@endsection
