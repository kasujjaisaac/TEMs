@extends('layouts.app')

@section('content')
@include('settings.partials.style')

<div class="access-page">
    <section class="access-header">
        <div class="access-title">
            <div class="access-title-icon"><i class="fa-solid fa-diagram-project"></i></div>
            <div>
                <h1>Enterprise Foundation</h1>
                <p>Company configuration, approvals, notifications, documents, audit and event traceability for TEMS.</p>
            </div>
        </div>
        <div class="access-actions">
            <a class="access-button secondary" href="{{ route('settings.roles') }}"><i class="fa-solid fa-shield-halved"></i> Roles</a>
            <a class="access-button secondary" href="{{ route('settings.audit_logs') }}"><i class="fa-solid fa-clock-rotate-left"></i> Audit</a>
        </div>
    </section>

    @if(session('success'))
        <div class="access-alert success">{{ session('success') }}</div>
    @endif

    @if($errors->any())
        <div class="access-alert error">{{ $errors->first() }}</div>
    @endif

    <section class="access-kpis">
        <div class="access-kpi"><span>Pending Approvals</span><strong>{{ $metrics['pending_approvals'] }}</strong></div>
        <div class="access-kpi"><span>Unread Notifications</span><strong>{{ $metrics['unread_notifications'] }}</strong></div>
        <div class="access-kpi"><span>Events Today</span><strong>{{ $metrics['events_today'] }}</strong></div>
        <div class="access-kpi"><span>Document Records</span><strong>{{ $metrics['document_records'] }}</strong></div>
    </section>

    <section class="access-grid">
        <div class="access-panel">
            <div class="access-panel-head">
                <h2>Company Configuration</h2>
            </div>
            <form class="access-form" method="POST" action="{{ route('foundation.company.update') }}">
                @csrf
                @method('PUT')
                <div class="access-field"><label>Company Name</label><input name="company_name" value="{{ old('company_name', $settings['company_name']) }}" required></div>
                <div class="access-field"><label>Email</label><input type="email" name="company_email" value="{{ old('company_email', $settings['company_email']) }}"></div>
                <div class="access-field"><label>Phone</label><input name="company_phone" value="{{ old('company_phone', $settings['company_phone']) }}"></div>
                <div class="access-field"><label>Country</label><input name="company_country" value="{{ old('company_country', $settings['company_country']) }}"></div>
                <div class="access-field"><label>Currency</label><input name="currency" value="{{ old('currency', $settings['currency']) }}" required></div>
                <div class="access-field"><label>Fiscal Year Start</label><input type="date" name="fiscal_year_start" value="{{ old('fiscal_year_start', $settings['fiscal_year_start']) }}"></div>
                <div class="access-field"><label>Website</label><input name="company_website" value="{{ old('company_website', $settings['company_website']) }}"></div>
                <div class="access-field"><label>Logo Path</label><input name="company_logo" value="{{ old('company_logo', $settings['company_logo']) }}"></div>
                <div class="access-field"><label>Address</label><textarea name="company_address">{{ old('company_address', $settings['company_address']) }}</textarea></div>
                <div class="access-field"><label>Approval Policy</label><textarea name="approval_policy">{{ old('approval_policy', $settings['approval_policy']) }}</textarea></div>
                <div class="access-field"><label>Notification Policy</label><textarea name="notification_policy">{{ old('notification_policy', $settings['notification_policy']) }}</textarea></div>
                <div class="access-field"><label>Document Policy</label><textarea name="document_policy">{{ old('document_policy', $settings['document_policy']) }}</textarea></div>
                <div class="access-footer-actions"><button class="access-button" type="submit"><i class="fa-solid fa-floppy-disk"></i> Save Foundation</button></div>
            </form>
        </div>

        <div class="access-panel">
            <div class="access-panel-head">
                <h2>Approval Register</h2>
            </div>
            <form class="access-form" method="POST" action="{{ route('foundation.approvals.store') }}">
                @csrf
                <div class="security-grid">
                    <div class="access-field"><label>Module</label><input name="module" value="{{ old('module', 'Enterprise Foundation') }}" required></div>
                    <div class="access-field"><label>Request Type</label><input name="request_type" value="{{ old('request_type', 'Foundation Control') }}" required></div>
                    <div class="access-field"><label>Priority</label><select name="priority"><option>Normal</option><option>High</option><option>Critical</option><option>Low</option></select></div>
                    <div class="access-field"><label>Title</label><input name="title" value="{{ old('title') }}" required></div>
                </div>
                <div class="access-field"><label>Summary</label><textarea name="summary">{{ old('summary') }}</textarea></div>
                <div class="access-footer-actions"><button class="access-button secondary" type="submit"><i class="fa-solid fa-check-to-slot"></i> Request Approval</button></div>
            </form>

            <div class="access-table-wrap" style="margin-top:16px">
                <table class="access-table access-table-square">
                    <thead><tr><th>Request</th><th>Status</th><th>Owner</th><th>Decision</th></tr></thead>
                    <tbody>
                    @forelse($approvals as $approval)
                        <tr>
                            <td><span class="access-table-title">{{ $approval->title }}</span><span class="access-muted">{{ $approval->module }} / {{ $approval->request_type }}</span></td>
                            <td><span class="access-badge {{ $approval->status === 'Approved' ? 'success' : ($approval->status === 'Rejected' ? 'danger' : 'warning') }}">{{ $approval->status }}</span></td>
                            <td>{{ $approval->requester?->name ?? 'System' }}</td>
                            <td>
                                @if($approval->status === 'Pending')
                                    <form method="POST" action="{{ route('foundation.approvals.decision', $approval) }}" class="access-actions">
                                        @csrf
                                        <input type="hidden" name="decision" value="Approved">
                                        <button class="access-icon-button" title="Approve" aria-label="Approve"><i class="fa-solid fa-check"></i></button>
                                    </form>
                                    <form method="POST" action="{{ route('foundation.approvals.decision', $approval) }}" class="access-actions" style="margin-top:6px">
                                        @csrf
                                        <input type="hidden" name="decision" value="Rejected">
                                        <button class="access-icon-button" title="Reject" aria-label="Reject"><i class="fa-solid fa-xmark"></i></button>
                                    </form>
                                @else
                                    <span class="access-muted">{{ $approval->reviewer?->name ?? 'Reviewed' }}</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4">No approval requests recorded.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </section>

    <section class="access-grid">
        <div class="access-panel">
            <div class="access-panel-head"><h2>Notifications</h2></div>
            <div class="access-table-wrap">
                <table class="access-table access-table-square">
                    <thead><tr><th>Notification</th><th>Severity</th><th>User</th><th>Status</th></tr></thead>
                    <tbody>
                    @forelse($notifications as $notification)
                        <tr>
                            <td><span class="access-table-title">{{ $notification->title }}</span><span class="access-muted">{{ $notification->message }}</span></td>
                            <td><span class="access-badge">{{ $notification->severity }}</span></td>
                            <td>{{ $notification->user?->name ?? 'All users' }}</td>
                            <td>
                                @if($notification->read_at)
                                    <span class="access-badge success">Read</span>
                                @else
                                    <form method="POST" action="{{ route('foundation.notifications.read', $notification) }}">
                                        @csrf
                                        <button class="access-button secondary" type="submit">Mark Read</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4">No notifications recorded.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="access-panel">
            <div class="access-panel-head"><h2>Domain Event Ledger</h2></div>
            <div class="access-table-wrap">
                <table class="access-table access-table-square">
                    <thead><tr><th>Event</th><th>Module</th><th>Actor</th><th>Time</th></tr></thead>
                    <tbody>
                    @forelse($events as $event)
                        <tr>
                            <td><span class="access-table-title">{{ $event->event_name }}</span><span class="access-muted">{{ $event->status }}</span></td>
                            <td>{{ $event->source_module }}</td>
                            <td>{{ $event->actor?->name ?? 'System' }}</td>
                            <td>{{ $event->occurred_at?->format('Y-m-d H:i') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4">No domain events recorded.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </section>

    <section class="access-panel">
        <div class="access-panel-head"><h2>Document Foundation</h2></div>
        <div class="access-table-wrap">
            <table class="access-table access-table-square">
                <thead><tr><th>Document</th><th>Module</th><th>Status</th><th>Owner</th></tr></thead>
                <tbody>
                @forelse($documents as $document)
                    <tr>
                        <td><span class="access-table-title">{{ $document->title }}</span><span class="access-muted">{{ $document->reference ?: $document->document_type }}</span></td>
                        <td>{{ $document->module }}</td>
                        <td><span class="access-badge">{{ $document->status }}</span></td>
                        <td>{{ $document->owner?->name ?? 'Unassigned' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4">No document records registered yet.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>
@endsection
