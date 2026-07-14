<nav class="access-tabs" aria-label="Security settings navigation">
    <a href="{{ route('settings.users') }}" class="{{ request()->routeIs('settings.users*') ? 'active' : '' }}"><i class="fa-solid fa-users-gear"></i> Users</a>
    <a href="{{ route('settings.roles') }}" class="{{ request()->routeIs('settings.roles*') ? 'active' : '' }}"><i class="fa-solid fa-shield-halved"></i> Roles</a>
    <a href="{{ route('settings.security') }}" class="{{ request()->routeIs('settings.security*') ? 'active' : '' }}"><i class="fa-solid fa-lock"></i> Security</a>
    <a href="{{ route('settings.audit_logs') }}" class="{{ request()->routeIs('settings.audit_logs') ? 'active' : '' }}"><i class="fa-solid fa-clock-rotate-left"></i> Audit Logs</a>
</nav>
