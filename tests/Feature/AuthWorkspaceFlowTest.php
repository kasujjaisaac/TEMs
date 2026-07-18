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

    public function test_public_registration_is_currently_disabled(): void
    {
        $response = $this->post('/register', [
            'company_name' => 'Texaro Technologies Limited',
            'name' => 'Admin User',
            'email' => 'ADMIN@EXAMPLE.TEST',
            'password' => 'Password#12345',
            'password_confirmation' => 'Password#12345',
        ]);

        $response->assertRedirect(route('login'));
        $this->assertGuest();
        $this->assertDatabaseMissing('users', ['email' => 'admin@example.test']);
    }

    public function test_user_can_reset_password_from_email_link(): void
    {
        $user = $this->createCompanyUser();

        $this->from(route('password.request'))->post(route('password.email'), [
            'email' => 'admin@example.test',
        ])->assertRedirect(route('password.request'))->assertSessionHas('success');

        $resetUrl = session('password_reset_test_url');
        $this->assertNotEmpty($resetUrl);

        $path = parse_url($resetUrl, PHP_URL_PATH);
        parse_str((string) parse_url($resetUrl, PHP_URL_QUERY), $query);
        $token = basename((string) $path);

        $this->post(route('password.reset.update'), [
            'email' => $query['email'],
            'token' => $token,
            'password' => 'NewPassword#12345',
            'password_confirmation' => 'NewPassword#12345',
        ])->assertRedirect(route('login'))->assertSessionHas('success');

        $user->refresh();
        $this->assertTrue(Hash::check('NewPassword#12345', $user->password));
        $this->assertDatabaseMissing('password_reset_tokens', ['email' => 'admin@example.test']);
    }

    public function test_login_requires_email_and_password(): void
    {
        $this->createCompanyUser();

        $this->post('/login', [
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
        $this->createCompanyUser();

        $this->post('/login', [
            'email' => 'admin@example.test',
            'password' => 'Password#12345',
        ])->assertRedirect(route('login.otp'));

        $this->post(route('login.otp.verify'), [
            'otp' => '000000',
        ])->assertSessionHasErrors('otp');

        $this->assertGuest();
    }

    public function test_login_reports_invalid_email_and_password_separately(): void
    {
        $this->createCompanyUser();

        $this->from('/login')->post('/login', [
            'email' => 'missing@example.test',
            'password' => 'Password#12345',
        ])->assertRedirect('/login')->assertSessionHasErrors('email');

        $this->from('/login')->post('/login', [
            'email' => 'admin@example.test',
            'password' => 'WrongPassword#12345',
        ])->assertRedirect('/login')->assertSessionHasErrors('password');

        $this->assertGuest();
    }

    private function createCompanyUser(array $overrides = []): User
    {
        $tenantId = DB::table('tenants')->insertGetId([
            'company_name' => 'Texaro Technologies Limited',
            'slug' => 'texaro-technologies',
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
