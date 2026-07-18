<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Role;
use App\Models\SecuritySetting;
use App\Models\User;
use App\Support\PermissionCatalog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class AccessControlController extends Controller
{
    public function users(): View
    {
        $this->authorizeAdmin('users.manage');
        $tenantId = $this->tenantId();
        $tenants = $this->accessibleTenants();
        $tenantIds = $tenants->pluck('id')->map(fn ($id) => (int) $id)->all();

        foreach ($tenantIds as $availableTenantId) {
            Role::ensureDefaultsForTenant($availableTenantId);
        }

        $roles = Role::whereIn('tenant_id', $tenantIds)->where('is_active', true)->orderBy('name')->get();

        return view('settings.users', [
            'page_title' => 'Users | Texaro Technologies Limited',
            'users' => User::with('assignedRole')
                ->where('tenant_id', $tenantId)
                ->orderBy('name')
                ->get(),
            'roles' => $roles,
            'rolesByTenant' => $roles->groupBy('tenant_id'),
            'tenantsById' => $tenants->keyBy('id'),
        ]);
    }

    public function createUser(): View
    {
        $this->authorizeAdmin('users.manage');
        $tenantId = $this->tenantId();
        $tenants = $this->accessibleTenants();

        foreach ($tenants as $tenant) {
            Role::ensureDefaultsForTenant((int) $tenant->id);
        }

        $roles = Role::whereIn('tenant_id', $tenants->pluck('id')->map(fn ($id) => (int) $id)->all())
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('settings.users_create', [
            'page_title' => 'Add User | Texaro Technologies Limited',
            'roles' => $roles,
            'rolesByTenant' => $roles->groupBy('tenant_id'),
            'tenants' => $tenants,
            'currentTenantId' => $tenantId,
        ]);
    }

    public function storeUser(Request $request): RedirectResponse
    {
        $this->authorizeAdmin('users.manage');

        $data = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:255'],
            'email' => ['required', 'email:rfc', 'max:255', Rule::unique('users', 'email')],
            'phone' => ['nullable', 'string', 'max:40'],
            'department' => ['nullable', 'string', 'max:120'],
            'role_id' => ['required', 'integer'],
            'password' => ['nullable', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $tenantId = $this->tenantId();
        Role::ensureDefaultsForTenant($tenantId);

        $request->validate([
            'role_id' => ['required', Rule::exists('roles', 'id')->where('tenant_id', $tenantId)],
        ]);

        $role = Role::where('tenant_id', $tenantId)->findOrFail($data['role_id']);
        $temporaryPassword = $data['password'] ?: '123';
        $user = User::create([
            'tenant_id' => $tenantId,
            'role_id' => $role->id,
            'name' => $data['name'],
            'email' => strtolower($data['email']),
            'phone' => $data['phone'] ?? null,
            'department' => $data['department'] ?? null,
            'password' => Hash::make($temporaryPassword),
            'role' => $role->slug,
            'is_active' => (bool) ($data['is_active'] ?? true),
            'password_changed_at' => null,
        ]);

        $this->audit($request, 'created', 'users', $user, 'Created user ' . $user->email, [], $tenantId);

        return redirect()->route('settings.users')->with('success', 'User created successfully.');
    }

    public function updateUser(Request $request, User $user): RedirectResponse
    {
        $this->authorizeAdmin('users.manage');
        $this->ensureTenantRecord($user);
        $tenantId = $this->tenantId();

        $data = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:255'],
            'email' => ['required', 'email:rfc', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => ['nullable', 'string', 'max:40'],
            'department' => ['nullable', 'string', 'max:120'],
            'role_id' => ['required', Rule::exists('roles', 'id')->where('tenant_id', $tenantId)],
            'password' => ['nullable', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $role = Role::where('tenant_id', $tenantId)->findOrFail($data['role_id']);
        $user->fill([
            'role_id' => $role->id,
            'name' => $data['name'],
            'email' => strtolower($data['email']),
            'phone' => $data['phone'] ?? null,
            'department' => $data['department'] ?? null,
            'role' => $role->slug,
            'is_active' => (bool) ($data['is_active'] ?? false),
        ]);

        if (! empty($data['password'])) {
            $user->password = Hash::make($data['password']);
            $user->password_changed_at = null;
        }

        $user->save();

        $this->audit($request, 'updated', 'users', $user, 'Updated user ' . $user->email);

        return back()->with('success', 'User updated successfully.');
    }

    public function roles(): View
    {
        $this->authorizeAdmin('roles.manage');
        $tenantId = $this->tenantId();
        Role::ensureDefaultsForTenant($tenantId);

        return view('settings.roles', [
            'page_title' => 'Roles & Permissions | Texaro Technologies Limited',
            'roles' => Role::withCount('users')->where('tenant_id', $tenantId)->orderBy('is_system', 'desc')->orderBy('name')->get(),
            'permissionGroups' => PermissionCatalog::groups(),
        ]);
    }

    public function createRole(): View
    {
        $this->authorizeAdmin('roles.manage');

        return view('settings.roles_create', [
            'page_title' => 'Create Role | Texaro Technologies Limited',
            'permissionGroups' => PermissionCatalog::groups(),
        ]);
    }

    public function showRole(Role $role): View
    {
        $this->authorizeAdmin('roles.manage');
        $this->ensureTenantRecord($role);

        return view('settings.roles_show', [
            'page_title' => $role->name . ' | Role Permissions',
            'role' => $role->loadCount('users'),
            'permissionGroups' => PermissionCatalog::groups(),
        ]);
    }

    public function editRole(Role $role): View
    {
        $this->authorizeAdmin('roles.manage');
        $this->ensureTenantRecord($role);

        return view('settings.roles_edit', [
            'page_title' => 'Edit ' . $role->name . ' | Texaro Technologies Limited',
            'role' => $role->loadCount('users'),
            'permissionGroups' => PermissionCatalog::groups(),
        ]);
    }

    public function storeRole(Request $request): RedirectResponse
    {
        $this->authorizeAdmin('roles.manage');
        $tenantId = $this->tenantId();

        $data = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:120'],
            'description' => ['nullable', 'string', 'max:1000'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', Rule::in(PermissionCatalog::allKeys())],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $role = Role::create([
            'tenant_id' => $tenantId,
            'name' => $data['name'],
            'slug' => Role::uniqueSlug($tenantId, $data['name']),
            'description' => $data['description'] ?? null,
            'permissions' => array_values($data['permissions'] ?? []),
            'is_system' => false,
            'is_active' => (bool) ($data['is_active'] ?? true),
        ]);

        $this->audit($request, 'created', 'roles', $role, 'Created role ' . $role->name);

        return redirect()->route('settings.roles.edit', $role)->with('success', 'Role created successfully.');
    }

    public function updateRole(Request $request, Role $role): RedirectResponse
    {
        $this->authorizeAdmin('roles.manage');
        $this->ensureTenantRecord($role);

        $data = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:120'],
            'description' => ['nullable', 'string', 'max:1000'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', Rule::in(PermissionCatalog::allKeys())],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $role->fill([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'permissions' => array_values($data['permissions'] ?? []),
            'is_active' => (bool) ($data['is_active'] ?? false),
        ]);

        if (! $role->is_system) {
            $role->slug = Role::uniqueSlug($role->tenant_id, $data['name'], $role->id);
        }

        $role->save();
        User::where('role_id', $role->id)->update(['role' => $role->slug]);

        $this->audit($request, 'updated', 'roles', $role, 'Updated role ' . $role->name);

        return redirect()->route('settings.roles.show', $role)->with('success', 'Role updated successfully.');
    }

    public function security(): View
    {
        $this->authorizeAdmin('security.manage');

        return view('settings.security', [
            'page_title' => 'Security Settings | Texaro Technologies Limited',
            'settings' => SecuritySetting::forTenant($this->tenantId()),
        ]);
    }

    public function updateSecurity(Request $request): RedirectResponse
    {
        $this->authorizeAdmin('security.manage');

        $data = $request->validate([
            'password_min_length' => ['required', 'integer', 'min:8', 'max:64'],
            'password_require_uppercase' => ['nullable', 'boolean'],
            'password_require_lowercase' => ['nullable', 'boolean'],
            'password_require_number' => ['nullable', 'boolean'],
            'password_require_symbol' => ['nullable', 'boolean'],
            'login_attempt_limit' => ['required', 'integer', 'min:1', 'max:20'],
            'account_lockout_minutes' => ['required', 'integer', 'min:1', 'max:1440'],
            'session_timeout_minutes' => ['required', 'integer', 'min:5', 'max:1440'],
            'require_email_verification' => ['nullable', 'boolean'],
            'require_two_factor' => ['nullable', 'boolean'],
            'allow_multiple_sessions' => ['nullable', 'boolean'],
            'force_password_change_first_login' => ['nullable', 'boolean'],
            'password_expiry_days' => ['required', 'integer', 'min:0', 'max:365'],
            'admin_approval_required' => ['nullable', 'boolean'],
        ]);

        $tenantId = $this->tenantId();
        foreach (SecuritySetting::defaults() as $key => $default) {
            SecuritySetting::updateOrCreate(
                ['tenant_id' => $tenantId, 'key' => $key],
                ['value' => (string) ($data[$key] ?? '0')]
            );
        }

        $this->audit($request, 'updated', 'security', null, 'Updated security settings', $data);

        return back()->with('success', 'Security settings saved successfully.');
    }

    public function auditLogs(): View
    {
        $this->authorizeAdmin('audit.view');

        return view('settings.audit_logs', [
            'page_title' => 'Audit Logs | Texaro Technologies Limited',
            'logs' => AuditLog::with('user')->where('tenant_id', $this->tenantId())->latest()->limit(150)->get(),
        ]);
    }

    private function authorizeAdmin(string $permission): void
    {
        $user = Auth::user();

        abort_unless($user && $user->hasPermission($permission), 403);
    }

    private function canManageAllWorkspaces(): bool
    {
        return false;
    }

    private function accessibleTenants()
    {
        return DB::table('tenants')
            ->where('id', $this->tenantId())
            ->orderBy('company_name')
            ->get();
    }

    private function selectedTenantId(?string $workspaceSlug): ?int
    {
        return $this->tenantId();
    }

    private function tenantId(): ?int
    {
        return Auth::user()?->tenant_id;
    }

    private function ensureTenantRecord(object $record): void
    {
        abort_unless($this->canManageAllWorkspaces() || (int) $record->tenant_id === (int) $this->tenantId(), 404);
    }

    private function audit(Request $request, string $action, string $module, ?object $subject, string $description, array $metadata = [], ?int $tenantId = null): void
    {
        AuditLog::create([
            'tenant_id' => $tenantId ?? $this->tenantId(),
            'user_id' => Auth::id(),
            'action' => $action,
            'module' => $module,
            'subject_type' => $subject ? $subject::class : null,
            'subject_id' => $subject->id ?? null,
            'description' => $description,
            'metadata' => $metadata,
            'ip_address' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 1000),
        ]);
    }

    private function passwordRule(): Password
    {
        $settings = SecuritySetting::forTenant($this->tenantId());
        $rule = Password::min((int) $settings['password_min_length']);

        if ((bool) $settings['password_require_uppercase'] || (bool) $settings['password_require_lowercase']) {
            $rule->mixedCase();
        }

        if ((bool) $settings['password_require_number']) {
            $rule->numbers();
        }

        if ((bool) $settings['password_require_symbol']) {
            $rule->symbols();
        }

        return $rule;
    }
}
