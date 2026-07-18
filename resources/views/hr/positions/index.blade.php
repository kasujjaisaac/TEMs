@extends('layouts.app')

@section('content')
@include('hr.partials.style')

<section class="hr-core">
    <header class="hr-core-header">
        <div class="hr-core-title">
            <div class="hr-core-icon"><i class="fa-solid fa-id-badge"></i></div>
            <div>
                <h1>Positions & Job Architecture</h1>
                <div class="hr-core-muted">Positions define accountability, authority, reporting, KPIs, competencies, and establishment.</div>
            </div>
        </div>
        <a class="hr-core-button" href="{{ route('hr.positions.create') }}"><i class="fa-solid fa-plus"></i> Create Position</a>
    </header>

    @if(session('success')) <div class="hr-core-alert success">{{ session('success') }}</div> @endif

    <section class="hr-core-panel">
        <div class="hr-core-panel-head">
            <h2>Position Register</h2>
            <form class="hr-core-filters" method="GET">
                <div class="hr-core-field">
                    <select name="status">
                        <option value="">All statuses</option>
                        @foreach(['Planned','Approved','Occupied','Vacant','Frozen','Acting','Inactive','Archived'] as $status)
                            <option value="{{ $status }}" @selected(request('status') === $status)>{{ $status }}</option>
                        @endforeach
                    </select>
                </div>
                <button class="hr-core-button secondary" type="submit"><i class="fa-solid fa-filter"></i> Filter</button>
            </form>
        </div>

        @include('hr.partials.table', [
            'headers' => ['Position', 'Department', 'Reports To', 'Status', 'Headcount', 'Vacant', 'Actions'],
            'rows' => $positions->map(fn ($position) => [
                '<strong class="hr-core-table-title">' . e($position->code) . '</strong><span class="hr-core-muted">' . e($position->title) . '</span>',
                e($position->department?->name ?: '-'),
                e($position->reportsTo?->title ?: '-'),
                '<span class="hr-core-badge ' . ($position->position_status === 'Occupied' ? 'success' : 'warning') . '">' . e($position->position_status) . '</span>',
                e($position->filled_headcount . ' / ' . $position->approved_headcount),
                e($position->vacancy_count),
                '<div class="hr-core-actions"><a class="hr-core-icon-button" href="' . route('hr.positions.show', $position) . '" title="View"><i class="fa-solid fa-eye"></i></a><a class="hr-core-icon-button" href="' . route('hr.positions.edit', $position) . '" title="Edit"><i class="fa-solid fa-pen-to-square"></i></a></div>',
            ])->all()
        ])

        <div style="margin-top: 12px">{{ $positions->links() }}</div>
    </section>
</section>
@endsection
