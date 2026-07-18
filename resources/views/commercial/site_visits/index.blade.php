@extends('layouts.app')

@section('content')
@include('commercial.partials.style')

<section class="commercial-page">
    <header class="commercial-header">
        <div class="commercial-title">
            <div class="commercial-title-icon"><i class="fa-solid fa-location-dot"></i></div>
            <div>
                <h1>Site Visits</h1>
                <div class="commercial-muted">Structured customer site assessment and implementation discovery records.</div>
            </div>
        </div>
        <a class="commercial-button" href="{{ route('commercial.site_visits.create') }}"><i class="fa-solid fa-plus"></i> Record Site Visit</a>
    </header>
    @if(session('success')) <div class="commercial-alert success">{{ session('success') }}</div> @endif
    <section class="commercial-panel">
        @include('commercial.partials.table', [
            'headers' => ['Visit', 'Location', 'Date', 'Purpose', 'Status'],
            'rows' => $siteVisits->map(fn ($visit) => [
                e($visit->reference),
                e($visit->site_location),
                e($visit->visit_date?->format('M d, Y') ?: '-'),
                e($visit->visit_purpose ?: '-'),
                '<span class="commercial-badge">' . e($visit->report_status) . '</span>',
            ])->all()
        ])
        <div style="margin-top: 12px">{{ $siteVisits->links() }}</div>
    </section>
</section>
@endsection
