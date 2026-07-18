@extends('layouts.app')

@section('content')
@include('commercial.partials.style')

<section class="commercial-page">
    <header class="commercial-header">
        <div class="commercial-title">
            <div class="commercial-title-icon"><i class="fa-solid fa-calendar-days"></i></div>
            <div>
                <h1>Meetings</h1>
                <div class="commercial-muted">Discovery, negotiation, review, and renewal meeting records.</div>
            </div>
        </div>
        <a class="commercial-button" href="{{ route('commercial.meetings.create') }}"><i class="fa-solid fa-plus"></i> Schedule Meeting</a>
    </header>
    @if(session('success')) <div class="commercial-alert success">{{ session('success') }}</div> @endif
    <section class="commercial-panel">
        @include('commercial.partials.table', [
            'headers' => ['Meeting', 'Type', 'Date', 'Location', 'Next Meeting'],
            'rows' => $meetings->map(fn ($meeting) => [
                e($meeting->title),
                e($meeting->meeting_type),
                e($meeting->meeting_date?->format('M d, Y') ?: '-'),
                e($meeting->location ?: $meeting->meeting_link ?: '-'),
                e($meeting->next_meeting_date?->format('M d, Y') ?: '-'),
            ])->all()
        ])
        <div style="margin-top: 12px">{{ $meetings->links() }}</div>
    </section>
</section>
@endsection
