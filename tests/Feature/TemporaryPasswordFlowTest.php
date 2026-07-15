<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class TemporaryPasswordFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_created_user_must_change_temporary_password_before_accessing_workspace(): void
    {
        [$tenantId, $admin] = $this->createTenantAdmin();
        $role = Role::where('tenant_id', $tenantId)->where('slug', 'admin')->firstOrFail();

        $this->actingAs($admin)->post(route('settings.users.store'), [
            'name' => 'Cashier User',
            'email' => 'cashier@example.test',
            'phone' => '',
            'department' => 'Sales',
            'role_id' => $role->id,
            'password' => '123',
            'is_active' => '1',
        ])->assertRedirect(route('settings.users'));

        $created = User::where('email', 'cashier@example.test')->firstOrFail();
        $this->assertNull($created->password_changed_at);
        $this->assertTrue(Hash::check('123', $created->password));

        $this->post('/logout');

        $this->post('/login', [
            'workspace' => 'smoke-test-company',
            'email' => 'cashier@example.test',
            'password' => '123',
        ])->assertRedirect(route('login.otp'));

        $this->post(route('login.otp.verify'), [
            'otp' => session('login_otp_test_code'),
        ])->assertRedirect(route('password.change'));

        $this->get('/dashboard')->assertRedirect(route('password.change'));

        $this->post(route('password.update'), [
            'current_password' => '123',
            'password' => 'NewPassword#12345',
            'password_confirmation' => 'NewPassword#12345',
        ])->assertRedirect('/dashboard');

        $created->refresh();
        $this->assertNotNull($created->password_changed_at);
        $this->assertTrue(Hash::check('NewPassword#12345', $created->password));

        $this->get('/dashboard')->assertOk();
    }

    public function test_temporary_password_cannot_be_reused_as_new_password(): void
    {
        [$tenantId] = $this->createTenantAdmin();
        Role::ensureDefaultsForTenant($tenantId);
        $role = Role::where('tenant_id', $tenantId)->where('slug', 'admin')->firstOrFail();

        $user = User::create([
            'tenant_id' => $tenantId,
            'role_id' => $role->id,
            'name' => 'Temp User',
            'email' => 'temp@example.test',
            'password' => Hash::make('123'),
            'role' => $role->slug,
            'is_active' => true,
            'password_changed_at' => null,
        ]);

        $this->actingAs($user)->post(route('password.update'), [
            'current_password' => '123',
            'password' => '123',
            'password_confirmation' => '123',
        ])->assertSessionHasErrors('password');
    }

    private function createTenantAdmin(): array
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

        return [$tenantId, $admin];
    }
}
