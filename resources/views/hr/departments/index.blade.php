@extends('layouts.app')

@section('content')
@include('hr.partials.style')

<section class="hr-core">
    <header class="hr-core-header">
        <div class="hr-core-title">
            <div class="hr-core-icon"><i class="fa-solid fa-sitemap"></i></div>
            <div>
                <h1>Departments & Teams</h1>
                <div class="hr-core-muted">Departments define responsibility, ownership, reporting visibility, and workforce structure.</div>
            </div>
        </div>
        <a class="hr-core-button" href="{{ route('hr.departments.create') }}"><i class="fa-solid fa-plus"></i> Create Department</a>
    </header>

    @if(session('success')) <div class="hr-core-alert success">{{ session('success') }}</div> @endif

    <section class="hr-core-panel">
        <div class="hr-core-panel-head">
            <h2>Structure Register</h2>
            <form class="hr-core-filters" method="GET">
                <div class="hr-core-field">
                    <select name="status">
                        <option value="">All statuses</option>
                        @foreach(['Proposed','Reviewed','Approved','Active','Restructured','Inactive','Archived'] as $status)
                            <option value="{{ $status }}" @selected(request('status') === $status)>{{ $status }}</option>
                        @endforeach
                    </select>
                </div>
                <button class="hr-core-button secondary" type="submit"><i class="fa-solid fa-filter"></i> Filter</button>
            </form>
        </div>

        @include('hr.partials.table', [
            'headers' => ['Department', 'Parent', 'Type', 'Status', 'Cost Centre', 'Positions', 'Actions'],
            'rows' => $departments->map(fn ($department) => [
                '<strong class="hr-core-table-title">' . e($department->code) . '</strong><span class="hr-core-muted">' . e($department->name) . '</span>',
                e($department->parent?->name ?: '-'),
                e($department->type),
                '<span class="hr-core-badge ' . ($department->status === 'Active' ? 'success' : 'warning') . '">' . e($department->status) . '</span>',
                e($department->cost_centre ?: '-'),
                e($department->positions->count()),
                '<div class="hr-core-actions"><a class="hr-core-icon-button" href="' . route('hr.departments.show', $department) . '" title="View"><i class="fa-solid fa-eye"></i></a><a class="hr-core-icon-button" href="' . route('hr.departments.edit', $department) . '" title="Edit"><i class="fa-solid fa-pen-to-square"></i></a></div>',
            ])->all()
        ])

        <div style="margin-top: 12px">{{ $departments->links() }}</div>
    </section>
</section>
@endsection
