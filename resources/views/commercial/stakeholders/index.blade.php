@extends('layouts.app')

@section('content')
@include('commercial.partials.style')

<section class="commercial-page">
    <header class="commercial-header">
        <div class="commercial-title">
            <div class="commercial-title-icon"><i class="fa-solid fa-address-book"></i></div>
            <div>
                <h1>Stakeholders</h1>
                <div class="commercial-muted">Decision makers, influencers, and commercial contacts across organizations.</div>
            </div>
        </div>
        <a class="commercial-button" href="{{ route('commercial.stakeholders.create') }}"><i class="fa-solid fa-plus"></i> Create Stakeholder</a>
    </header>
    <section class="commercial-panel">
        @include('commercial.partials.table', [
            'headers' => ['Stakeholder', 'Organization', 'Role', 'Influence', 'Contact'],
            'rows' => $stakeholders->map(fn ($stakeholder) => [
                e($stakeholder->full_name),
                e($stakeholder->organization?->legal_name ?: '-'),
                e($stakeholder->decision_role ?: '-'),
                e($stakeholder->influence_level ?: '-'),
                e($stakeholder->telephone ?: $stakeholder->email ?: '-'),
            ])->all()
        ])
        <div style="margin-top: 12px">{{ $stakeholders->links() }}</div>
    </section>
</section>
@endsection
