@extends('layouts.app')

@section('content')
@include('commercial.partials.style')

<section class="commercial-page">
    <header class="commercial-header">
        <div class="commercial-title">
            <div class="commercial-title-icon"><i class="fa-solid fa-filter-circle-dollar"></i></div>
            <div>
                <h1>{{ $lead->reference }}</h1>
                <div class="commercial-muted">{{ $lead->organization_name }} · {{ $lead->status }}</div>
            </div>
        </div>
        <div class="commercial-actions">
            <a class="commercial-button secondary" href="{{ route('commercial.leads.index') }}"><i class="fa-solid fa-arrow-left"></i> Back</a>
            @if(auth()->user()?->hasPermission('commercial.leads.update'))
                <a class="commercial-button secondary" href="{{ route('commercial.leads.edit', $lead) }}"><i class="fa-solid fa-pen-to-square"></i> Edit</a>
            @endif
            <a class="commercial-button secondary" href="{{ route('commercial.activities.create', ['related_type' => App\Models\Commercial\CommercialLead::class, 'related_id' => $lead->id]) }}"><i class="fa-solid fa-list-check"></i> Activity</a>
            @if(!$lead->converted_at && auth()->user()?->hasPermission('commercial.leads.convert'))
                <form method="POST" action="{{ route('commercial.leads.convert', $lead) }}">
                    @csrf
                    <button class="commercial-button" type="submit"><i class="fa-solid fa-shuffle"></i> Convert</button>
                </form>
            @endif
        </div>
    </header>

    @if(session('success')) <div class="commercial-alert success">{{ session('success') }}</div> @endif
    @if($errors->any()) <div class="commercial-alert error">{{ $errors->first() }}</div> @endif

    <section class="commercial-grid">
        <div class="commercial-card"><span>Temperature</span><strong>{{ $lead->temperature }}</strong></div>
        <div class="commercial-card"><span>Lead Score</span><strong>{{ $lead->lead_score }}</strong></div>
        <div class="commercial-card"><span>Budget</span><strong>{{ $lead->estimated_budget ? number_format((float) $lead->estimated_budget, 2) : '-' }}</strong></div>
        <div class="commercial-card"><span>Assigned</span><strong>{{ $lead->assignedEmployee?->name ?: '-' }}</strong></div>
    </section>

    <section class="commercial-split">
        <div class="commercial-panel">
            <div class="commercial-panel-head"><h2>Lead Details</h2></div>
            @include('commercial.partials.table', [
                'headers' => ['Field', 'Value'],
                'rows' => [
                    ['Organization', e($lead->organization_name)],
                    ['Contact', e($lead->contact_person ?: '-')],
                    ['Telephone / Email', e(($lead->telephone ?: '-') . ' / ' . ($lead->email ?: '-'))],
                    ['Location', e($lead->location ?: '-')],
                    ['Industry / Sector', e(($lead->industry ?: '-') . ' / ' . ($lead->sector ?: '-'))],
                    ['Source', e($lead->lead_source ?: '-')],
                    ['Need', e($lead->requirements_summary ?: '-')],
                    ['Pain Points', e($lead->pain_points ?: '-')],
                ]
            ])
        </div>

        <div class="commercial-panel">
            <div class="commercial-panel-head"><h2>Conversion Links</h2></div>
            <p class="commercial-muted">Converted leads keep their original record and link forward to the resulting commercial records.</p>
            @if($lead->organization)
                <p><a href="{{ route('commercial.organizations.show', $lead->organization) }}">{{ $lead->organization->reference }} · {{ $lead->organization->legal_name }}</a></p>
            @endif
            @if($lead->opportunity)
                <p><a href="{{ route('commercial.opportunities.show', $lead->opportunity) }}">{{ $lead->opportunity->reference }} · {{ $lead->opportunity->title }}</a></p>
            @endif
            @unless($lead->converted_at)
                <span class="commercial-badge warning">Awaiting Conversion</span>
            @endunless
        </div>
    </section>
</section>
@endsection
