@extends('layouts.app')

@section('content')
@include('settings.partials.style')

<section class="access-page">
    <header class="access-header">
        <div class="access-title">
            <div class="access-title-icon"><i class="fa-solid fa-shield-halved"></i></div>
            <div>
                <h1>Roles & Permissions</h1>
                <p>Review workspace roles, user coverage, status, and permission scope from one clean register.</p>
            </div>
        </div>
        <a class="access-button" href="{{ route('settings.roles.create') }}"><i class="fa-solid fa-plus"></i> Create Role</a>
    </header>

    <section class="access-kpis">
        <div class="access-kpi"><span>Total Roles</span><strong>{{ $roles->count() }}</strong></div>
        <div class="access-kpi"><span>Active Roles</span><strong>{{ $roles->where('is_active', true)->count() }}</strong></div>
        <div class="access-kpi"><span>System Roles</span><strong>{{ $roles->where('is_system', true)->count() }}</strong></div>
        <div class="access-kpi"><span>Permission Groups</span><strong>{{ count($permissionGroups) }}</strong></div>
    </section>

    @if(session('success'))
        <div class="access-alert success">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="access-alert error">{{ $errors->first() }}</div>
    @endif

    <section class="access-panel">
        <div class="access-panel-head">
            <h2>Role Register</h2>
            <span class="access-muted">Use actions to view or configure permissions</span>
        </div>
        <div class="access-table-wrap">
            <table class="access-table access-table-square role-register-table">
                <thead>
                    <tr>
                        <th>Role</th>
                        <th>Type</th>
                        <th>Status</th>
                        <th>Users</th>
                        <th>Permissions</th>
                        <th>Updated</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($roles as $role)
                        <tr>
                            <td>
                                <strong class="access-table-title">{{ $role->name }}</strong>
                                <span class="access-muted">{{ $role->description ?: 'No description added.' }}</span>
                            </td>
                            <td>
                                <span class="access-badge flat">{{ $role->is_system ? 'System' : 'Custom' }}</span>
                            </td>
                            <td>
                                <span class="access-badge flat {{ $role->is_active ? 'success' : 'warning' }}">{{ $role->is_active ? 'Active' : 'Inactive' }}</span>
                            </td>
                            <td>{{ $role->users_count }}</td>
                            <td>{{ count($role->permissions ?? []) }} assigned</td>
                            <td><span class="access-muted">{{ $role->updated_at?->format('M d, Y') ?? '-' }}</span></td>
                            <td>
                                <div class="access-actions role-actions">
                                    <a class="access-icon-button" href="{{ route('settings.roles.show', $role) }}" title="View role" aria-label="View {{ $role->name }}">
                                        <i class="fa-solid fa-eye"></i>
                                    </a>
                                    <a class="access-icon-button" href="{{ route('settings.roles.edit', $role) }}" title="Edit role" aria-label="Edit {{ $role->name }}">
                                        <i class="fa-solid fa-pen-to-square"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7">No roles found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</section>
@endsection
