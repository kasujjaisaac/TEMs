@extends('layouts.app')

@section('content')
@include('commercial.partials.style')

<section class="commercial-page">
    <header class="commercial-header">
        <div class="commercial-title">
            <div class="commercial-title-icon"><i class="fa-solid fa-briefcase"></i></div>
            <div>
                <h1>Commercial Dashboard</h1>
                <div class="commercial-muted">Pipeline, lead flow, meetings, and site visits from live Commercial Operations data.</div>
            </div>
        </div>
        <div class="commercial-actions">
            <a class="commercial-button" href="{{ route('commercial.leads.create') }}"><i class="fa-solid fa-plus"></i> Lead</a>
            <a class="commercial-button secondary" href="{{ route('commercial.opportunities.create') }}"><i class="fa-solid fa-handshake-angle"></i> Opportunity</a>
        </div>
    </header>

    <section class="commercial-grid">
        <div class="commercial-card"><span>Active Leads</span><strong>{{ $metrics['active_leads'] }}</strong></div>
        <div class="commercial-card"><span>New This Month</span><strong>{{ $metrics['new_leads_month'] }}</strong></div>
        <div class="commercial-card"><span>Qualified Leads</span><strong>{{ $metrics['qualified_leads'] }}</strong></div>
        <div class="commercial-card"><span>Organizations</span><strong>{{ $metrics['organizations'] }}</strong></div>
        <div class="commercial-card"><span>Active Opportunities</span><strong>{{ $metrics['active_opportunities'] }}</strong></div>
        <div class="commercial-card"><span>Pipeline Value</span><strong>{{ number_format((float) $metrics['pipeline_value'], 2) }}</strong></div>
        <div class="commercial-card"><span>Weighted Pipeline</span><strong>{{ number_format((float) $metrics['weighted_pipeline_value'], 2) }}</strong></div>
        <div class="commercial-card"><span>Upcoming Meetings</span><strong>{{ $metrics['upcoming_meetings'] }}</strong></div>
    </section>

    @if(session('success'))
        <div class="commercial-alert success">{{ session('success') }}</div>
    @endif

    <section class="commercial-split">
        <div class="commercial-panel">
            <div class="commercial-panel-head">
                <h2>Recent Leads</h2>
                <a class="commercial-button secondary" href="{{ route('commercial.leads.index') }}">View All</a>
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
        </div>

        <div class="commercial-panel">
            <div class="commercial-panel-head">
                <h2>Upcoming Field Work</h2>
                <span class="commercial-muted">{{ $metrics['upcoming_site_visits'] }} site visits</span>
            </div>
            <div class="commercial-table-wrap">
                <table class="commercial-table">
                    <tbody>
                        @forelse($meetings as $meeting)
                            <tr><td data-label="Meeting"><strong class="commercial-table-title">{{ $meeting->title }}</strong><span class="commercial-muted">{{ $meeting->meeting_date?->format('M d, Y') }} · {{ $meeting->meeting_type }}</span></td></tr>
                        @empty
                            <tr><td class="commercial-muted" data-label="Meeting">No upcoming meetings.</td></tr>
                        @endforelse
                        @forelse($siteVisits as $visit)
                            <tr><td data-label="Site Visit"><strong class="commercial-table-title">{{ $visit->reference }}</strong><span class="commercial-muted">{{ $visit->visit_date?->format('M d, Y') }} · {{ $visit->site_location }}</span></td></tr>
                        @empty
                            <tr><td class="commercial-muted" data-label="Site Visit">No upcoming site visits.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </section>

    <section class="commercial-panel">
        <div class="commercial-panel-head">
            <h2>Active Opportunities</h2>
            <a class="commercial-button secondary" href="{{ route('commercial.opportunities.index') }}">Pipeline</a>
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
    </section>
</section>
@endsection
