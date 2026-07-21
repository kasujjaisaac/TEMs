@extends('layouts.app')

@section('content')
@include('commercial.partials.style')

<section class="commercial-page tems-command">
    <header class="tems-command-header">
        <div class="tems-command-title">
            <div class="tems-command-icon"><i class="fa-solid fa-briefcase"></i></div>
            <div>
                <span class="tems-command-eyebrow">Commercial operations</span>
                <h1>Pipeline Command</h1>
                <div class="tems-command-subtitle">Lead generation, qualification, opportunity value, site activity, and billing handoff readiness from live commercial data.</div>
            </div>
        </div>
        <div class="tems-command-actions">
            <a class="tems-button" href="{{ route('commercial.leads.create') }}"><i class="fa-solid fa-plus"></i> Lead</a>
            <a class="tems-button secondary" href="{{ route('commercial.opportunities.create') }}"><i class="fa-solid fa-handshake-angle"></i> Opportunity</a>
        </div>
    </header>

    @if(session('success'))
        <div class="commercial-alert success">{{ session('success') }}</div>
    @endif

    <section class="tems-kpi-grid">
        <article class="tems-kpi-card"><span>Active Leads</span><strong>{{ $metrics['active_leads'] }}</strong><small>Open demand that still needs ownership and next action.</small></article>
        <article class="tems-kpi-card"><span>New This Month</span><strong>{{ $metrics['new_leads_month'] }}</strong><small>Fresh pipeline entering Commercial Operations.</small></article>
        <article class="tems-kpi-card"><span>Qualified Leads</span><strong>{{ $metrics['qualified_leads'] }}</strong><small>Leads ready for opportunity shaping.</small></article>
        <article class="tems-kpi-card"><span>Active Campaigns</span><strong>{{ $metrics['active_campaigns'] }}</strong><small>Market motions currently feeding lead flow.</small></article>
        <article class="tems-kpi-card"><span>Active Opportunities</span><strong>{{ $metrics['active_opportunities'] }}</strong><small>Deals that should have stage, value, and close plan.</small></article>
        <article class="tems-kpi-card"><span>Pipeline Value</span><strong>{{ number_format((float) $metrics['pipeline_value'], 2) }}</strong><small>Total open estimated opportunity value.</small></article>
        <article class="tems-kpi-card"><span>Weighted Pipeline</span><strong>{{ number_format((float) $metrics['weighted_pipeline_value'], 2) }}</strong><small>Probability-adjusted commercial exposure.</small></article>
        <article class="tems-kpi-card"><span>Billing Requests</span><strong>{{ $metrics['billing_requests'] }}</strong><small>Commercial records waiting for finance handoff.</small></article>
    </section>

    <section class="tems-dashboard-grid">
        <article class="tems-panel">
            <div class="tems-panel-head">
                <div>
                    <span class="tems-panel-kicker">Lead desk</span>
                    <h2>Recent Leads</h2>
                </div>
                <a class="tems-button secondary" href="{{ route('commercial.leads.index') }}">View All</a>
            </div>
            @include('commercial.partials.table', [
                'headers' => ['Lead', 'Contact', 'Status', 'Next Action'],
                'rows' => $recentLeads->map(fn ($lead) => [
                    '<strong class="commercial-table-title"><a href="' . route('commercial.leads.show', $lead) . '">' . e($lead->reference) . '</a></strong><span class="commercial-muted">' . e($lead->organization_name) . '</span>',
                    e($lead->contact_person ?: '-') . '<br><span class="commercial-muted">' . e($lead->telephone ?: $lead->email ?: '-') . '</span>',
                    '<span class="commercial-badge">' . e($lead->status) . '</span>',
                    e($lead->next_action ?: '-'),
                ])->all()
            ])
        </article>

        <aside class="tems-panel">
            <div class="tems-panel-head">
                <div>
                    <span class="tems-panel-kicker">Movement</span>
                    <h2>Field & Handoff Queue</h2>
                </div>
                <span class="tems-status">{{ $metrics['upcoming_site_visits'] }} site visits</span>
            </div>
            <div class="tems-work-list">
                <div class="tems-work-card">
                    <div><span class="tems-list-label">Qualified demand</span><small class="tems-muted">Leads that should convert or be disqualified.</small></div>
                    <strong>{{ $metrics['qualified_leads'] }}</strong>
                </div>
                <div class="tems-work-card">
                    <div><span class="tems-list-label">Field visits</span><small class="tems-muted">Upcoming site validation and customer meetings.</small></div>
                    <strong>{{ $metrics['upcoming_site_visits'] }}</strong>
                </div>
                <div class="tems-work-card">
                    <div><span class="tems-list-label">Billing handoff</span><small class="tems-muted">Requests that must stay clean for finance.</small></div>
                    <strong>{{ $metrics['billing_requests'] }}</strong>
                </div>
            </div>
        </aside>
    </section>

    <section class="tems-two-column">
        <article class="tems-panel">
            <div class="tems-panel-head">
                <div>
                    <span class="tems-panel-kicker">Calendar pressure</span>
                    <h2>Upcoming Field Work</h2>
                </div>
            </div>
            <div class="tems-table-wrap">
                <table class="tems-table">
                    <tbody>
                        @forelse($meetings as $meeting)
                            <tr><td><strong>{{ $meeting->title }}</strong><span class="tems-muted">{{ $meeting->meeting_date?->format('M d, Y') }} &middot; {{ $meeting->meeting_type }}</span></td></tr>
                        @empty
                            <tr><td class="tems-muted">No upcoming meetings.</td></tr>
                        @endforelse
                        @forelse($siteVisits as $visit)
                            <tr><td><strong>{{ $visit->reference }}</strong><span class="tems-muted">{{ $visit->visit_date?->format('M d, Y') }} &middot; {{ $visit->site_location }}</span></td></tr>
                        @empty
                            <tr><td class="tems-muted">No upcoming site visits.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </article>

        <article class="tems-panel">
            <div class="tems-panel-head">
                <div>
                    <span class="tems-panel-kicker">Revenue shaping</span>
                    <h2>Active Opportunities</h2>
                </div>
                <a class="tems-button secondary" href="{{ route('commercial.opportunities.index') }}">Pipeline</a>
            </div>
            @include('commercial.partials.table', [
                'headers' => ['Opportunity', 'Organization', 'Stage', 'Value', 'Weighted'],
                'rows' => $opportunities->map(fn ($opportunity) => [
                    '<strong class="commercial-table-title"><a href="' . route('commercial.opportunities.show', $opportunity) . '">' . e($opportunity->reference) . '</a></strong><span class="commercial-muted">' . e($opportunity->title) . '</span>',
                    e($opportunity->organization?->legal_name ?: '-'),
                    '<span class="commercial-badge">' . e($opportunity->current_stage) . '</span>',
                    e($opportunity->currency) . ' ' . number_format((float) $opportunity->estimated_value, 2),
                    e($opportunity->currency) . ' ' . number_format($opportunity->weighted_value, 2),
                ])->all()
            ])
        </article>
    </section>
</section>
@endsection
