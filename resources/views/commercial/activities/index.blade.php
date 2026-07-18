@extends('layouts.app')

@section('content')
@include('commercial.partials.style')

<section class="commercial-page">
    <header class="commercial-header">
        <div class="commercial-title">
            <div class="commercial-title-icon"><i class="fa-solid fa-list-check"></i></div>
            <div>
                <h1>Commercial Activities</h1>
                <div class="commercial-muted">Calls, follow-ups, notes, and other customer-facing activity records.</div>
            </div>
        </div>
        <a class="commercial-button" href="{{ route('commercial.activities.create') }}"><i class="fa-solid fa-plus"></i> Record Activity</a>
    </header>
    @if(session('success')) <div class="commercial-alert success">{{ session('success') }}</div> @endif
    <section class="commercial-panel">
        @include('commercial.partials.table', [
            'headers' => ['Type', 'Related Record', 'Date', 'Status', 'Description'],
            'rows' => $activities->map(fn ($activity) => [
                e($activity->activity_type),
                e(class_basename($activity->related_type) . ' #' . $activity->related_id),
                e($activity->activity_date?->format('M d, Y') ?: '-'),
                '<span class="commercial-badge">' . e($activity->completion_status) . '</span>',
                e($activity->description),
            ])->all()
        ])
        <div style="margin-top: 12px">{{ $activities->links() }}</div>
    </section>
</section>
@endsection
