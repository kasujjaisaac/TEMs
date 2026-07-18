@extends('layouts.app')

@section('content')
@include('settings.partials.style')

<section class="access-page users-management-page">
    <header class="access-header access-header-compact">
        <div class="access-title">
            <div class="access-title-icon"><i class="fa-solid fa-users-gear"></i></div>
            <div>
                <h1>Users</h1>
                <p>Manage staff accounts, role assignment, account status, and recent login visibility.</p>
            </div>
        </div>
        <a class="access-button" href="{{ route('settings.users.create') }}"><i class="fa-solid fa-user-plus"></i> Add User</a>
    </header>

    <section class="access-kpis access-kpis-compact">
        <div class="access-kpi"><span>Total Users</span><strong>{{ $users->count() }}</strong></div>
        <div class="access-kpi"><span>Active Users</span><strong>{{ $users->where('is_active', true)->count() }}</strong></div>
        <div class="access-kpi"><span>Disabled Users</span><strong>{{ $users->where('is_active', false)->count() }}</strong></div>
        <div class="access-kpi"><span>Available Roles</span><strong>{{ $roles->count() }}</strong></div>
    </section>

    @if(session('success'))
        <div class="access-alert success">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="access-alert error">{{ $errors->first() }}</div>
    @endif

    <section class="access-panel">
        <div class="access-panel-head">
            <h2>User Register</h2>
            <span class="access-muted">Role changes are controlled from each user row</span>
        </div>
        @foreach($users as $user)
            <form id="user-update-{{ $user->id }}" method="POST" action="{{ route('settings.users.update', $user) }}">
                @csrf
                @method('PUT')
            </form>
        @endforeach
        <div class="access-table-wrap">
            <table class="access-table access-table-square user-register-table">
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Contact</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Last Login</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($users as $user)
                        <tr>
                            <td>
                                <strong class="access-table-title">{{ $user->name }}</strong>
                                <span class="access-muted">{{ $user->department ?: 'No department' }}</span>
                            </td>
                            <td>
                                <strong class="access-table-title">{{ $user->email }}</strong>
                                <span class="access-muted">{{ $user->phone ?: 'No phone' }}</span>
                            </td>
                            <td>
                                <div class="access-field compact">
                                    <select form="user-update-{{ $user->id }}" name="role_id" required>
                                        @foreach($rolesByTenant->get($user->tenant_id, collect()) as $role)
                                            <option value="{{ $role->id }}" @selected((int) $user->role_id === (int) $role->id)>{{ $role->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </td>
                            <td>
                                <input form="user-update-{{ $user->id }}" type="hidden" name="name" value="{{ $user->name }}">
                                <input form="user-update-{{ $user->id }}" type="hidden" name="email" value="{{ $user->email }}">
                                <input form="user-update-{{ $user->id }}" type="hidden" name="phone" value="{{ $user->phone }}">
                                <input form="user-update-{{ $user->id }}" type="hidden" name="department" value="{{ $user->department }}">
                                <input form="user-update-{{ $user->id }}" type="hidden" name="is_active" value="0">
                                <label class="access-check">
                                    <input form="user-update-{{ $user->id }}" type="checkbox" name="is_active" value="1" @checked($user->is_active)>
                                    <span class="access-badge flat {{ $user->is_active ? 'success' : 'warning' }}">{{ $user->is_active ? 'Active' : 'Disabled' }}</span>
                                </label>
                            </td>
                            <td><span class="access-muted">{{ $user->last_login_at?->format('M d, Y H:i') ?? 'Never' }}</span></td>
                            <td>
                                <div class="access-actions role-actions">
                                    <button form="user-update-{{ $user->id }}" class="access-icon-button" type="submit" title="Save user" aria-label="Save {{ $user->name }}">
                                        <i class="fa-solid fa-floppy-disk"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6">No users found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</section>
@endsection
