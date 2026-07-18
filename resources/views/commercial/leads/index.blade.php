@extends('layouts.app')

@section('content')
@include('commercial.partials.style')

<section class="commercial-page">
    <header class="commercial-header">
        <div class="commercial-title">
            <div class="commercial-title-icon"><i class="fa-solid fa-filter-circle-dollar"></i></div>
            <div>
                <h1>Leads</h1>
                <div class="commercial-muted">Capture, qualify, and convert commercial demand into managed opportunities.</div>
            </div>
        </div>
        <a class="commercial-button" href="{{ route('commercial.leads.create') }}"><i class="fa-solid fa-plus"></i> Create Lead</a>
    </header>

    @if(session('success')) <div class="commercial-alert success">{{ session('success') }}</div> @endif

    <section class="commercial-panel">
        <div class="commercial-panel-head">
            <h2>Lead Register</h2>
            <form class="commercial-filters" method="GET">
                <div class="commercial-field"><input type="search" name="search" value="{{ request('search') }}" placeholder="Search leads"></div>
                <div class="commercial-field">
                    <select name="status">
                        <option value="">All statuses</option>
                        @foreach(['New','Contacted','Engaged','Qualified','Unqualified','Nurturing','Converted','Lost','Archived'] as $status)
                            <option value="{{ $status }}" @selected(request('status') === $status)>{{ $status }}</option>
                        @endforeach
                    </select>
                </div>
                <button class="commercial-button secondary" type="submit"><i class="fa-solid fa-magnifying-glass"></i> Filter</button>
            </form>
        </div>

        @include('commercial.partials.table', [
            'headers' => ['Lead', 'Contact', 'Temperature', 'Status', 'Budget', 'Owner', 'Actions'],
            'rows' => $leads->map(fn ($lead) => [
                '<strong class="commercial-table-title">' . e($lead->reference) . '</strong><span class="commercial-muted">' . e($lead->organization_name) . '</span>',
                e($lead->contact_person ?: '-') . '<br><span class="commercial-muted">' . e($lead->telephone ?: $lead->email ?: '-') . '</span>',
                '<span class="commercial-badge ' . ($lead->temperature === 'Hot' ? 'warning' : '') . '">' . e($lead->temperature) . '</span>',
                '<span class="commercial-badge ' . ($lead->status === 'Converted' ? 'success' : '') . '">' . e($lead->status) . '</span>',
                $lead->estimated_budget ? number_format((float) $lead->estimated_budget, 2) : '-',
                e($lead->assignedEmployee?->name ?: '-'),
                '<div class="commercial-actions"><a class="commercial-icon-button" href="' . route('commercial.leads.show', $lead) . '" title="View lead"><i class="fa-solid fa-eye"></i></a><a class="commercial-icon-button" href="' . route('commercial.leads.edit', $lead) . '" title="Edit lead"><i class="fa-solid fa-pen-to-square"></i></a></div>',
            ])->all()
        ])

        <div style="margin-top: 12px">{{ $leads->links() }}</div>
    </section>
</section>
@endsection
