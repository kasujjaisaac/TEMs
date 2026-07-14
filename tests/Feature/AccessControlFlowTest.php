<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AccessControlFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_auth_pages_render(): void
    {
        $this->get('/login')->assertOk()->assertSee('Secure company workspace');
        $this->get('/register')->assertOk()->assertSee('Secure company workspace');
    }

    public function test_admin_can_open_security_pages_and_manage_role_flow(): void
    {
        $tenantId = DB::table('tenants')->insertGetId([
            'company_name' => 'Smoke Test Company',
            'slug' => 'smoke-test-company',
            'currency' => 'UGX',
            'fiscal_year_start' => '2026-01-01',
            'status' => 'trial',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Role::ensureDefaultsForTenant($tenantId);
        $adminRole = Role::where('tenant_id', $tenantId)->where('slug', 'super_admin')->firstOrFail();

        $admin = User::create([
            'tenant_id' => $tenantId,
            'role_id' => $adminRole->id,
            'name' => 'System Admin',
            'email' => 'admin@example.test',
            'password' => Hash::make('Password#12345'),
            'role' => 'super_admin',
            'is_active' => true,
            'password_changed_at' => now(),
        ]);

        $this->actingAs($admin);

        $this->get('/settings/users')->assertOk()->assertSee('Users');
        $this->get('/settings/users/create')->assertOk()->assertSee('Add User');
        $this->get('/settings/roles')->assertOk()->assertSee('Role Register');
        $this->get('/settings/roles/create')->assertOk()->assertSee('Create Role');
        $this->get('/settings/security')->assertOk()->assertSee('Security Settings');
        $this->get('/settings/audit-logs')->assertOk()->assertSee('Audit Logs');

        $response = $this->post('/settings/roles', [
            'name' => 'Branch Supervisor',
            'description' => 'Supervises branch activity.',
            'is_active' => '1',
            'permissions' => ['dashboard.view', 'sales.view', 'customers.view'],
        ]);

        $role = Role::where('tenant_id', $tenantId)->where('slug', 'branch-supervisor')->firstOrFail();
        $response->assertRedirect(route('settings.roles.edit', $role));

        $this->get(route('settings.roles.show', $role))->assertOk()->assertSee('Branch Supervisor');
        $this->get(route('settings.roles.edit', $role))->assertOk()->assertSee('Permission Configuration');

        $this->put(route('settings.roles.update', $role), [
            'name' => 'Branch Supervisor',
            'description' => 'Supervises branch activity and sales reporting.',
            'is_active' => '1',
            'permissions' => ['dashboard.view', 'sales.view', 'reports.view'],
        ])->assertRedirect(route('settings.roles.show', $role));

        $this->assertDatabaseHas('roles', [
            'id' => $role->id,
            'description' => 'Supervises branch activity and sales reporting.',
        ]);

        $this->assertSame(2, AuditLog::where('tenant_id', $tenantId)->where('module', 'roles')->count());
    }
}
