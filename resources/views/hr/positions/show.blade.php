@extends('layouts.app')

@section('content')
@include('hr.partials.style')

<section class="hr-core">
    <header class="hr-core-header">
        <div class="hr-core-title">
            <div class="hr-core-icon"><i class="fa-solid fa-id-badge"></i></div>
            <div>
                <h1>{{ $position->title }}</h1>
                <div class="hr-core-muted">{{ $position->code }} / {{ $position->department?->name }} / {{ $position->position_status }}</div>
            </div>
        </div>
        <div class="hr-core-actions">
            <a class="hr-core-button secondary" href="{{ route('hr.positions.index') }}"><i class="fa-solid fa-arrow-left"></i> Back</a>
            <a class="hr-core-button secondary" href="{{ route('hr.positions.edit', $position) }}"><i class="fa-solid fa-pen-to-square"></i> Edit</a>
        </div>
    </header>

    @if(session('success')) <div class="hr-core-alert success">{{ session('success') }}</div> @endif

    <section class="hr-core-grid">
        <div class="hr-core-card"><span>Status</span><strong>{{ $position->position_status }}</strong></div>
        <div class="hr-core-card"><span>Reports To</span><strong>{{ $position->reportsTo?->title ?: '-' }}</strong></div>
        <div class="hr-core-card"><span>Headcount</span><strong>{{ $position->filled_headcount }} / {{ $position->approved_headcount }}</strong></div>
        <div class="hr-core-card"><span>Vacancies</span><strong>{{ $position->vacancy_count }}</strong></div>
    </section>

    <section class="hr-core-split">
        <div class="hr-core-panel">
            <div class="hr-core-panel-head"><h2>Role Definition</h2></div>
            @include('hr.partials.table', [
                'headers' => ['Field', 'Value'],
                'rows' => [
                    ['Job Family', e($position->job_family ?: '-')],
                    ['Grade / Level', e(($position->grade ?: '-') . ' / ' . ($position->level ?: '-'))],
                    ['Employment Type', e($position->employment_type)],
                    ['Work Location', e($position->work_location ?: '-')],
                    ['Approval Limit', e($position->approval_limit ? number_format((float) $position->approval_limit, 2) : '-')],
                    ['Effective Dates', e(($position->effective_from?->format('M d, Y') ?: '-') . ' to ' . ($position->effective_to?->format('M d, Y') ?: 'Open'))],
                ],
            ])
        </div>
        <div class="hr-core-panel">
            <div class="hr-core-panel-head"><h2>Direct Reports</h2></div>
            @include('hr.partials.table', [
                'headers' => ['Position', 'Status'],
                'rows' => $position->directReports->map(fn ($report) => [
                    '<a href="' . route('hr.positions.show', $report) . '">' . e($report->title) . '</a>',
                    '<span class="hr-core-badge">' . e($report->position_status) . '</span>',
                ])->all(),
            ])
        </div>
    </section>

    <section class="hr-core-panel">
        <div class="hr-core-panel-head"><h2>Accountability Profile</h2></div>
        @include('hr.partials.table', [
            'headers' => ['Area', 'Definition'],
            'rows' => [
                ['Job Purpose', e($position->job_purpose ?: '-')],
                ['Key Responsibilities', e($position->key_responsibilities ?: '-')],
                ['Standard KPIs', e($position->standard_kpis ?: '-')],
                ['Competencies', e($position->competencies ?: '-')],
                ['Decision Rights', e($position->decision_rights ?: '-')],
            ],
        ])
    </section>
</section>
@endsection
