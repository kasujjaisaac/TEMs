@extends('layouts.app')

@section('content')
@include('settings.partials.style')

<section class="access-page users-management-page user-create-page">
    <header class="access-header access-header-compact">
        <div class="access-title">
            <div class="access-title-icon"><i class="fa-solid fa-user-plus"></i></div>
            <div>
                <h1>Add User</h1>
                <p>Create a staff account and assign one of the roles configured under Roles & Permissions.</p>
            </div>
        </div>
        <div class="access-actions">
            <a class="access-button secondary" href="{{ route('settings.roles') }}"><i class="fa-solid fa-shield-halved"></i> Roles</a>
            <a class="access-button secondary" href="{{ route('settings.users') }}"><i class="fa-solid fa-table-list"></i> Users</a>
        </div>
    </header>

    @if($errors->any())
        <div class="access-alert error">{{ $errors->first() }}</div>
    @endif

    @php
        $currentTenant = $tenants->firstWhere('id', $currentTenantId) ?? $tenants->first();
        $selectedWorkspace = old('workspace_slug', $currentTenant->slug ?? '');
        $selectedTenant = $tenants->firstWhere('slug', $selectedWorkspace) ?? $currentTenant;
        $selectedRoles = $selectedTenant ? $rolesByTenant->get($selectedTenant->id, collect()) : collect();
        $roleOptionsByWorkspace = $tenants->mapWithKeys(function ($tenant) use ($rolesByTenant) {
            return [
                $tenant->slug => $rolesByTenant->get($tenant->id, collect())->map(fn ($role) => [
                    'id' => $role->id,
                    'name' => $role->name,
                ])->values(),
            ];
        });
    @endphp

    <form class="access-form" method="POST" action="{{ route('settings.users.store') }}">
        @csrf
        <section class="access-panel">
            <div class="access-panel-head">
                <h2>Account Details</h2>
                <span class="access-badge">New User</span>
            </div>
            <div class="security-grid">
                <div class="access-field">
                    <label for="workspace_slug">Workspace</label>
                    <select id="workspace_slug" name="workspace_slug" required @disabled(! $canManageAllWorkspaces)>
                        @foreach($tenants as $tenant)
                            <option value="{{ $tenant->slug }}" @selected($selectedWorkspace === $tenant->slug)>
                                {{ $tenant->company_name }} ({{ $tenant->slug }})
                            </option>
                        @endforeach
                    </select>
                    @unless($canManageAllWorkspaces)
                        <input type="hidden" name="workspace_slug" value="{{ $selectedWorkspace }}">
                    @endunless
                </div>
                <div class="access-field">
                    <label for="name">Full Name</label>
                    <input id="name" name="name" value="{{ old('name') }}" required>
                </div>
                <div class="access-field">
                    <label for="email">Email</label>
                    <input id="email" name="email" type="email" value="{{ old('email') }}" required>
                </div>
                <div class="access-field">
                    <label for="phone">Phone</label>
                    <input id="phone" name="phone" value="{{ old('phone') }}">
                </div>
                <div class="access-field">
                    <label for="department">Department</label>
                    <input id="department" name="department" value="{{ old('department') }}">
                </div>
                <div class="access-field">
                    <label for="role_id">Role</label>
                    <select id="role_id" name="role_id" required>
                        @foreach($selectedRoles as $role)
                            <option value="{{ $role->id }}" @selected((string) old('role_id') === (string) $role->id)>{{ $role->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="access-field">
                    <label for="password">Temporary Password</label>
                    <input id="password" name="password" type="text" value="{{ old('password', '123') }}" required>
                </div>
            </div>
            <label class="access-check access-check-box">
                <input type="hidden" name="is_active" value="0">
                <input type="checkbox" name="is_active" value="1" checked>
                Active user
            </label>
        </section>

        <div class="access-footer-actions">
            <a class="access-button secondary" href="{{ route('settings.users') }}"><i class="fa-solid fa-arrow-left"></i> Back</a>
            <button class="access-button" type="submit"><i class="fa-solid fa-user-plus"></i> Add User</button>
        </div>
    </form>
</section>

<script>
    (() => {
        const rolesByWorkspace = @json($roleOptionsByWorkspace);
        const workspace = document.getElementById('workspace_slug');
        const role = document.getElementById('role_id');
        const selectedRole = @json((string) old('role_id'));

        if (!workspace || !role) {
            return;
        }

        const renderRoles = () => {
            const options = rolesByWorkspace[workspace.value] || [];
            role.innerHTML = '';

            options.forEach((item) => {
                const option = document.createElement('option');
                option.value = item.id;
                option.textContent = item.name;

                if (selectedRole && selectedRole === String(item.id)) {
                    option.selected = true;
                }

                role.appendChild(option);
            });
        };

        workspace.addEventListener('change', renderRoles);
        renderRoles();
    })();
</script>
@endsection
