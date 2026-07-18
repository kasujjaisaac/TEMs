@extends('layouts.app')

@section('content')
@include('commercial.partials.style')

<section class="commercial-page">
    <header class="commercial-header">
        <div class="commercial-title">
            <div class="commercial-title-icon"><i class="fa-solid fa-handshake-angle"></i></div>
            <div>
                <h1>Opportunities</h1>
                <div class="commercial-muted">Phase 1 pipeline records created directly or from lead conversion.</div>
            </div>
        </div>
        <a class="commercial-button" href="{{ route('commercial.opportunities.create') }}"><i class="fa-solid fa-plus"></i> Create Opportunity</a>
    </header>

    @if(session('success')) <div class="commercial-alert success">{{ session('success') }}</div> @endif

    <section class="commercial-panel">
        <div class="commercial-panel-head">
            <h2>Pipeline Register</h2>
            <form class="commercial-filters" method="GET">
                <div class="commercial-field">
                    <select name="stage">
                        <option value="">All stages</option>
                        @foreach($stages as $stage)
                            <option value="{{ $stage->name }}" @selected(request('stage') === $stage->name)>{{ $stage->name }}</option>
                        @endforeach
                    </select>
                </div>
                <button class="commercial-button secondary" type="submit"><i class="fa-solid fa-filter"></i> Filter</button>
            </form>
        </div>
        @include('commercial.partials.table', [
            'headers' => ['Opportunity', 'Organization', 'Stage', 'Probability', 'Value', 'Weighted', 'Actions'],
            'rows' => $opportunities->map(fn ($opportunity) => [
                '<strong class="commercial-table-title">' . e($opportunity->reference) . '</strong><span class="commercial-muted">' . e($opportunity->title) . '</span>',
                e($opportunity->organization?->legal_name ?: '-'),
                '<span class="commercial-badge">' . e($opportunity->current_stage) . '</span>',
                e($opportunity->probability) . '%',
                e($opportunity->currency) . ' ' . number_format((float) $opportunity->estimated_value, 2),
                e($opportunity->currency) . ' ' . number_format($opportunity->weighted_value, 2),
                '<div class="commercial-actions"><a class="commercial-icon-button" href="' . route('commercial.opportunities.show', $opportunity) . '" title="View opportunity"><i class="fa-solid fa-eye"></i></a><a class="commercial-icon-button" href="' . route('commercial.activities.create', ['related_type' => App\Models\Commercial\CommercialOpportunity::class, 'related_id' => $opportunity->id]) . '" title="Record activity"><i class="fa-solid fa-list-check"></i></a></div>',
            ])->all()
        ])
        <div style="margin-top: 12px">{{ $opportunities->links() }}</div>
    </section>
</section>
@endsection
