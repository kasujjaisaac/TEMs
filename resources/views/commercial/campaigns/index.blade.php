@extends('layouts.app')

@section('content')
@include('commercial.partials.style')

<section class="commercial-page">
    <header class="commercial-header">
        <div class="commercial-title">
            <div class="commercial-title-icon"><i class="fa-solid fa-bullhorn"></i></div>
            <div>
                <h1>Campaigns</h1>
                <div class="commercial-muted">Demand generation, lead attribution, campaign spend and conversion tracking.</div>
            </div>
        </div>
        <a class="commercial-button secondary" href="{{ route('commercial.dashboard') }}"><i class="fa-solid fa-arrow-left"></i> Dashboard</a>
    </header>

    @if(session('success')) <div class="commercial-alert success">{{ session('success') }}</div> @endif
    @if($errors->any()) <div class="commercial-alert error">{{ $errors->first() }}</div> @endif

    @if(auth()->user()?->hasPermission('commercial.campaigns.manage'))
        <section class="commercial-panel">
            <div class="commercial-panel-head"><h2>Create Campaign</h2></div>
            <form class="commercial-form" method="POST" action="{{ route('commercial.campaigns.store') }}">
                @csrf
                <div class="commercial-field double"><label>Name</label><input name="name" required></div>
                <div class="commercial-field"><label>Type</label><select name="campaign_type"><option>Demand Generation</option><option>Event</option><option>Partnership</option><option>Digital</option><option>Tender</option><option>Retention</option></select></div>
                <div class="commercial-field"><label>Channel</label><input name="channel" placeholder="Email, social, field, event"></div>
                <div class="commercial-field"><label>Budget</label><input name="budget" type="number" min="0" step="0.01" value="0"></div>
                <div class="commercial-field"><label>Actual Spend</label><input name="actual_spend" type="number" min="0" step="0.01" value="0"></div>
                <div class="commercial-field"><label>Starts On</label><input name="starts_on" type="date"></div>
                <div class="commercial-field"><label>Ends On</label><input name="ends_on" type="date"></div>
                <div class="commercial-field"><label>Status</label><select name="status"><option>Planned</option><option>Active</option><option>Running</option><option>Completed</option><option>Cancelled</option></select></div>
                <div class="commercial-field"><label>Owner</label><select name="owner_id"><option value="">Unassigned</option>@foreach($employees as $employee)<option value="{{ $employee->id }}">{{ $employee->name }}</option>@endforeach</select></div>
                <div class="commercial-field full"><label>Target Audience</label><input name="target_audience"></div>
                <div class="commercial-field full"><label>Objective</label><textarea name="objective"></textarea></div>
                <div class="commercial-field full"><button class="commercial-button" type="submit"><i class="fa-solid fa-save"></i> Save Campaign</button></div>
            </form>
        </section>
    @endif

    <section class="commercial-panel">
        <div class="commercial-panel-head"><h2>Campaign Register</h2></div>
        @include('commercial.partials.table', [
            'headers' => ['Campaign', 'Channel', 'Budget / Spend', 'Leads', 'Opportunities', 'Status'],
            'rows' => $campaigns->map(fn ($campaign) => [
                '<strong class="commercial-table-title">' . e($campaign->reference) . '</strong><span class="commercial-muted">' . e($campaign->name) . '</span>',
                e($campaign->channel ?: $campaign->campaign_type),
                e(number_format((float) $campaign->budget, 2) . ' / ' . number_format((float) $campaign->actual_spend, 2)),
                e($campaign->leads_count),
                e($campaign->opportunities_count),
                '<span class="commercial-badge ' . (in_array($campaign->status, ['Active','Running','Completed'], true) ? 'success' : 'warning') . '">' . e($campaign->status) . '</span>',
            ])->all()
        ])
        {{ $campaigns->links() }}
    </section>
</section>
@endsection
