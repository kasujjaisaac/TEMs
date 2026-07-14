@extends('layouts.app')

@section('content')
@include('settings.partials.style')

<section class="access-page">
    <header class="access-header">
        <div class="access-title">
            <div class="access-title-icon"><i class="fa-solid fa-eye"></i></div>
            <div>
                <h1>{{ $role->name }}</h1>
                <p>{{ $role->description ?: 'Role permissions and account assignment summary.' }}</p>
            </div>
        </div>
        <div class="access-actions">
            <a class="access-button" href="{{ route('settings.roles.edit', $role) }}"><i class="fa-solid fa-pen-to-square"></i> Edit Role</a>
            <a class="access-button secondary" href="{{ route('settings.roles') }}"><i class="fa-solid fa-table-list"></i> Roles</a>
        </div>
    </header>

    <section class="access-kpis">
        <div class="access-kpi"><span>Status</span><strong>{{ $role->is_active ? 'Active' : 'Inactive' }}</strong></div>
        <div class="access-kpi"><span>Role Type</span><strong>{{ $role->is_system ? 'System' : 'Custom' }}</strong></div>
        <div class="access-kpi"><span>Assigned Users</span><strong>{{ $role->users_count }}</strong></div>
        <div class="access-kpi"><span>Permissions</span><strong>{{ count($role->permissions ?? []) }}</strong></div>
    </section>

    @if(session('success'))
        <div class="access-alert success">{{ session('success') }}</div>
    @endif

    <section class="access-panel">
        <div class="access-panel-head">
            <h2>Permission Coverage</h2>
            <span class="access-muted">Read-only view of configured access</span>
        </div>
        <div class="access-table-wrap">
            <table class="access-table access-table-square">
                <thead>
                    <tr>
                        <th>Module</th>
                        <th>Allowed Permissions</th>
                        <th>Coverage</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($permissionGroups as $group => $permissions)
                        @php
                            $allowed = collect($permissions)->filter(fn ($label, $key) => in_array($key, $role->permissions ?? [], true));
                        @endphp
                        <tr>
                            <td><strong class="access-table-title">{{ $group }}</strong></td>
                            <td>
                                @if($allowed->isNotEmpty())
                                    <div class="access-chip-row">
                                        @foreach($allowed as $label)
                                            <span class="access-chip">{{ $label }}</span>
                                        @endforeach
                                    </div>
                                @else
                                    <span class="access-muted">No permissions enabled</span>
                                @endif
                            </td>
                            <td>{{ $allowed->count() }} / {{ count($permissions) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>
</section>
@endsection
