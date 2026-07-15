<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthWorkspaceFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_requires_and_stores_workspace(): void
    {
        $response = $this->post('/register', [
            'company_name' => 'Onyx Technologies',
            'workspace' => 'Onyx Tech',
            'name' => 'Admin User',
            'email' => 'ADMIN@EXAMPLE.TEST',
            'password' => 'Password#12345',
            'password_confirmation' => 'Password#12345',
        ]);

        $response->assertRedirect(route('login.otp'));
        $this->assertGuest();
        $this->assertDatabaseHas('tenants', [
            'company_name' => 'Onyx Technologies',
            'slug' => 'onyx-tech',
        ]);
        $this->assertDatabaseHas('users', [
            'email' => 'admin@example.test',
        ]);
    }

    public function test_login_requires_matching_workspace_email_and_password(): void
    {
        $this->createWorkspaceUser();

        $this->post('/login', [
            'workspace' => 'onyx-tech',
            'email' => 'admin@example.test',
            'password' => 'Password#12345',
        ])->assertRedirect(route('login.otp'));

        $this->assertGuest();

        $this->post(route('login.otp.verify'), [
            'otp' => session('login_otp_test_code'),
        ])->assertRedirect('/dashboard');

        $this->assertAuthenticated();
    }

    public function test_login_rejects_incorrect_otp(): void
    {
        $this->createWorkspaceUser();

        $this->post('/login', [
            'workspace' => 'onyx-tech',
            'email' => 'admin@example.test',
            'password' => 'Password#12345',
        ])->assertRedirect(route('login.otp'));

        $this->post(route('login.otp.verify'), [
            'otp' => '000000',
        ])->assertSessionHasErrors('otp');

        $this->assertGuest();
    }

    public function test_login_reports_invalid_workspace_email_and_password_separately(): void
    {
        $this->createWorkspaceUser();

        $this->from('/login')->post('/login', [
            'workspace' => 'unknown-workspace',
            'email' => 'admin@example.test',
            'password' => 'Password#12345',
        ])->assertRedirect('/login')->assertSessionHasErrors('workspace');

        $this->from('/login')->post('/login', [
            'workspace' => 'onyx-tech',
            'email' => 'missing@example.test',
            'password' => 'Password#12345',
        ])->assertRedirect('/login')->assertSessionHasErrors('email');

        $this->from('/login')->post('/login', [
            'workspace' => 'onyx-tech',
            'email' => 'admin@example.test',
            'password' => 'WrongPassword#12345',
        ])->assertRedirect('/login')->assertSessionHasErrors('password');

        $this->assertGuest();
    }

    private function createWorkspaceUser(array $overrides = []): User
    {
        $tenantId = DB::table('tenants')->insertGetId([
            'company_name' => 'Onyx Technologies',
            'slug' => 'onyx-tech',
            'currency' => 'UGX',
            'fiscal_year_start' => '2026-01-01',
            'status' => 'trial',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Role::ensureDefaultsForTenant($tenantId);
        $role = Role::where('tenant_id', $tenantId)->where('slug', 'super_admin')->first();

        return User::create(array_merge([
            'tenant_id' => $tenantId,
            'role_id' => $role?->id,
            'name' => 'Admin User',
            'email' => 'admin@example.test',
            'password' => Hash::make('Password#12345'),
            'role' => 'super_admin',
            'is_active' => true,
            'password_changed_at' => now(),
        ], $overrides));
    }
}
