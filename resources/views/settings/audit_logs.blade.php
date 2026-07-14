@extends('layouts.app')

@section('content')
@include('settings.partials.style')

<section class="access-page">
    <header class="access-header">
        <div class="access-title">
            <div class="access-title-icon"><i class="fa-solid fa-clock-rotate-left"></i></div>
            <div>
                <h1>Audit Logs</h1>
                <p>Review recent access-control and security changes made inside this workspace.</p>
            </div>
        </div>
    </header>

    <section class="access-kpis">
        <div class="access-kpi"><span>Events Loaded</span><strong>{{ $logs->count() }}</strong></div>
        <div class="access-kpi"><span>User Changes</span><strong>{{ $logs->where('module', 'users')->count() }}</strong></div>
        <div class="access-kpi"><span>Role Changes</span><strong>{{ $logs->where('module', 'roles')->count() }}</strong></div>
        <div class="access-kpi"><span>Security Changes</span><strong>{{ $logs->where('module', 'security')->count() }}</strong></div>
    </section>

    <section class="access-panel">
        <div class="access-panel-head">
            <h2>Recent Activity</h2>
            <span class="access-muted">Latest 150 events</span>
        </div>
        <div class="access-table-wrap">
            <table class="access-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>User</th>
                        <th>Action</th>
                        <th>Module</th>
                        <th>Description</th>
                        <th>IP</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($logs as $log)
                        <tr>
                            <td>{{ $log->created_at?->format('M d, Y H:i') }}</td>
                            <td>{{ $log->user?->name ?? 'System' }}</td>
                            <td><span class="access-badge">{{ $log->action }}</span></td>
                            <td>{{ ucfirst($log->module) }}</td>
                            <td>{{ $log->description }}</td>
                            <td class="access-muted">{{ $log->ip_address ?: 'Unknown' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6">No audit events recorded yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</section>
@endsection
