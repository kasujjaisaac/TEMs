@extends('layouts.app')

@section('content')
@include('hr.partials.style')

<section class="hr-core">
    <header class="hr-core-header">
        <div class="hr-core-title">
            <div class="hr-core-icon"><i class="fa-solid fa-chart-pie"></i></div>
            <div>
                <h1>HR Command Centre</h1>
                <div class="hr-core-muted">Organization structure, positions, establishment, vacancies, and employee capacity.</div>
            </div>
        </div>
        <div class="hr-core-actions">
            <a class="hr-core-button" href="{{ route('hr.departments.create') }}"><i class="fa-solid fa-plus"></i> Department</a>
            <a class="hr-core-button secondary" href="{{ route('hr.positions.create') }}"><i class="fa-solid fa-id-badge"></i> Position</a>
        </div>
    </header>

    <section class="hr-core-grid">
        <div class="hr-core-card"><span>Employees</span><strong>{{ $metrics['employees'] }}</strong></div>
        <div class="hr-core-card"><span>Active Employees</span><strong>{{ $metrics['active_employees'] }}</strong></div>
        <div class="hr-core-card"><span>Departments</span><strong>{{ $metrics['departments'] }}</strong></div>
        <div class="hr-core-card"><span>Active Departments</span><strong>{{ $metrics['active_departments'] }}</strong></div>
        <div class="hr-core-card"><span>Positions</span><strong>{{ $metrics['positions'] }}</strong></div>
        <div class="hr-core-card"><span>Approved Headcount</span><strong>{{ $metrics['approved_headcount'] }}</strong></div>
        <div class="hr-core-card"><span>Filled Headcount</span><strong>{{ $metrics['filled_headcount'] }}</strong></div>
        <div class="hr-core-card"><span>Vacancies</span><strong>{{ $metrics['vacancies'] }}</strong></div>
    </section>

    <section class="hr-core-split">
        <div class="hr-core-panel">
            <div class="hr-core-panel-head">
                <h2>Departments & Teams</h2>
                <a class="hr-core-button secondary" href="{{ route('hr.departments.index') }}">View All</a>
            </div>
            @include('hr.partials.table', [
                'headers' => ['Department', 'Type', 'Status', 'Positions'],
                'rows' => $departments->map(fn ($department) => [
                    '<strong class="hr-core-table-title"><a href="' . route('hr.departments.show', $department) . '">' . e($department->code) . '</a></strong><span class="hr-core-muted">' . e($department->name) . '</span>',
                    e($department->type),
                    '<span class="hr-core-badge ' . ($department->status === 'Active' ? 'success' : 'warning') . '">' . e($department->status) . '</span>',
                    e($department->positions_count),
                ])->all()
            ])
        </div>

        <div class="hr-core-panel">
            <div class="hr-core-panel-head">
                <h2>Position Architecture</h2>
                <a class="hr-core-button secondary" href="{{ route('hr.positions.index') }}">View All</a>
            </div>
            @include('hr.partials.table', [
                'headers' => ['Position', 'Department', 'Status', 'Vacancies'],
                'rows' => $positions->map(fn ($position) => [
                    '<strong class="hr-core-table-title"><a href="' . route('hr.positions.show', $position) . '">' . e($position->code) . '</a></strong><span class="hr-core-muted">' . e($position->title) . '</span>',
                    e($position->department?->name ?: '-'),
                    '<span class="hr-core-badge ' . ($position->position_status === 'Occupied' ? 'success' : 'warning') . '">' . e($position->position_status) . '</span>',
                    e($position->vacancy_count),
                ])->all()
            ])
        </div>
    </section>
</section>
@endsection
