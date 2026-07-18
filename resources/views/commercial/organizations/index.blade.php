@extends('layouts.app')

@section('content')
@include('commercial.partials.style')

<section class="commercial-page">
    <header class="commercial-header">
        <div class="commercial-title">
            <div class="commercial-title-icon"><i class="fa-solid fa-building"></i></div>
            <div>
                <h1>Organizations</h1>
                <div class="commercial-muted">Professional commercial organization records with optional links to legacy customers.</div>
            </div>
        </div>
        <a class="commercial-button" href="{{ route('commercial.organizations.create') }}"><i class="fa-solid fa-plus"></i> Create Organization</a>
    </header>

    @if(session('success')) <div class="commercial-alert success">{{ session('success') }}</div> @endif

    <section class="commercial-panel">
        <div class="commercial-panel-head">
            <h2>Organization Register</h2>
            <form class="commercial-filters" method="GET">
                <div class="commercial-field"><input type="search" name="search" value="{{ request('search') }}" placeholder="Search organizations"></div>
                <button class="commercial-button secondary" type="submit"><i class="fa-solid fa-magnifying-glass"></i> Search</button>
            </form>
        </div>
        @include('commercial.partials.table', [
            'headers' => ['Organization', 'Status', 'Industry', 'Contact', 'Manager', 'Actions'],
            'rows' => $organizations->map(fn ($organization) => [
                '<strong class="commercial-table-title">' . e($organization->reference) . '</strong><span class="commercial-muted">' . e($organization->legal_name) . '</span>',
                '<span class="commercial-badge">' . e($organization->customer_status) . '</span>',
                e($organization->industry ?: '-'),
                e($organization->primary_telephone ?: $organization->primary_email ?: '-'),
                e($organization->accountManager?->name ?: '-'),
                '<div class="commercial-actions"><a class="commercial-icon-button" href="' . route('commercial.organizations.show', $organization) . '" title="View organization"><i class="fa-solid fa-eye"></i></a><a class="commercial-icon-button" href="' . route('commercial.organizations.edit', $organization) . '" title="Edit organization"><i class="fa-solid fa-pen-to-square"></i></a></div>',
            ])->all()
        ])
        <div style="margin-top: 12px">{{ $organizations->links() }}</div>
    </section>
</section>
@endsection
