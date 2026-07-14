<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rules\Password;
use App\Models\Role;
use App\Models\SecuritySetting;
use App\Models\User;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('pages.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        $credentials['email'] = Str::lower($credentials['email']);
        $credentials['is_active'] = true;
        $candidate = User::where('email', $credentials['email'])->first();
        $security = SecuritySetting::forTenant($candidate?->tenant_id);
        $throttleKey = 'login:' . $credentials['email'] . '|' . $request->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, (int) $security['login_attempt_limit'])) {
            $seconds = RateLimiter::availableIn($throttleKey);

            throw ValidationException::withMessages([
                'email' => 'Too many login attempts. Try again in ' . ceil($seconds / 60) . ' minute(s).',
            ]);
        }

        if (Auth::attempt($credentials)) {
            RateLimiter::clear($throttleKey);
            $request->session()->regenerate();
            $request->user()->forceFill(['last_login_at' => now()])->save();
            $this->hydrateLegacySession($request, Auth::user());

            return redirect()->intended('/dashboard');
        }

        RateLimiter::hit($throttleKey, max(60, (int) $security['account_lockout_minutes'] * 60));

        return back()->withErrors(['email' => 'Invalid credentials.'])->onlyInput('email');
    }

    public function showRegister()
    {
        return view('pages.register');
    }

    public function register(Request $request)
    {
        $data = $request->validate([
            'company_name' => ['required', 'string', 'min:2', 'max:255'],
            'name' => ['required', 'string', 'min:2', 'max:255'],
            'email' => ['required', 'email:rfc', 'max:255', 'unique:users,email'],
            'password' => [
                'required',
                'confirmed',
                Password::min(10)->mixedCase()->numbers()->symbols(),
            ],
        ]);

        $data['email'] = Str::lower($data['email']);

        $user = DB::transaction(function () use ($data): User {
            $tenantId = DB::table('tenants')->insertGetId([
                'company_name' => $data['company_name'],
                'slug' => $this->uniqueTenantSlug($data['company_name']),
                'currency' => 'UGX',
                'fiscal_year_start' => now()->startOfYear()->toDateString(),
                'status' => 'trial',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            Role::ensureDefaultsForTenant($tenantId);
            $role = Role::where('tenant_id', $tenantId)->where('slug', 'super_admin')->first();

            return User::create([
                'tenant_id' => $tenantId,
                'role_id' => $role?->id,
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'role' => 'super_admin',
                'is_active' => true,
                'password_changed_at' => now(),
            ]);
        });

        Auth::login($user);
        $request->session()->regenerate();
        $this->hydrateLegacySession($request, $user);

        return redirect('/dashboard');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/');
    }

    private function hydrateLegacySession(Request $request, User $user): void
    {
        $tenant = DB::table('tenants')->where('id', $user->tenant_id)->first();

        $request->session()->put([
            'tenant_id' => $user->tenant_id ?: ($tenant->id ?? 1),
            'user_id' => $user->id,
            'user_name' => $user->name,
            'company_name' => $tenant->company_name ?? config('app.name', 'Onyx Hub'),
            'currency' => $tenant->currency ?? 'UGX',
            'role' => $user->role ?: 'super_admin',
        ]);
    }

    private function uniqueTenantSlug(string $companyName): string
    {
        $base = Str::slug($companyName) ?: 'workspace';
        $slug = $base;
        $counter = 2;

        while (DB::table('tenants')->where('slug', $slug)->exists()) {
            $slug = $base . '-' . $counter;
            $counter++;
        }

        return $slug;
    }
}
