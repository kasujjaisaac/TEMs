<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
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
        $request->merge([
            'workspace' => $this->normalizeWorkspace((string) $request->input('workspace', '')),
            'email' => Str::lower((string) $request->input('email', '')),
        ]);

        $data = $request->validate([
            'workspace' => ['required', 'string', 'max:80'],
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required'],
        ]);

        $workspace = $this->normalizeWorkspace($data['workspace']);
        $email = Str::lower(trim($data['email']));
        $tenant = DB::table('tenants')->where('slug', $workspace)->first();
        $security = SecuritySetting::forTenant($tenant?->id);
        $throttleKey = 'login:' . $workspace . '|' . $email . '|' . $request->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, (int) $security['login_attempt_limit'])) {
            $seconds = RateLimiter::availableIn($throttleKey);

            throw ValidationException::withMessages([
                'email' => 'Too many login attempts. Try again in ' . ceil($seconds / 60) . ' minute(s).',
            ]);
        }

        if (! $tenant) {
            RateLimiter::hit($throttleKey, max(60, (int) $security['account_lockout_minutes'] * 60));

            return back()
                ->withErrors(['workspace' => 'Workspace not found. Check the workspace code from registration.'])
                ->onlyInput('workspace', 'email');
        }

        $user = User::where('tenant_id', $tenant->id)->where('email', $email)->first();

        if (! $user) {
            RateLimiter::hit($throttleKey, max(60, (int) $security['account_lockout_minutes'] * 60));

            return back()
                ->withErrors(['email' => 'Email address is not registered in this workspace.'])
                ->onlyInput('workspace', 'email');
        }

        if (! $user->is_active) {
            RateLimiter::hit($throttleKey, max(60, (int) $security['account_lockout_minutes'] * 60));

            return back()
                ->withErrors(['email' => 'This user account is inactive. Contact the workspace administrator.'])
                ->onlyInput('workspace', 'email');
        }

        if (! Hash::check($data['password'], $user->password)) {
            RateLimiter::hit($throttleKey, max(60, (int) $security['account_lockout_minutes'] * 60));

            return back()
                ->withErrors(['password' => 'Password is incorrect for this workspace account.'])
                ->onlyInput('workspace', 'email');
        }

        RateLimiter::clear($throttleKey);
        $this->sendLoginOtp($request, $user, $workspace);

        return redirect()->route('login.otp');
    }

    public function showOtp()
    {
        if (! session()->has('login_otp.user_id')) {
            return redirect()->route('login');
        }

        return view('pages.login-otp');
    }

    public function verifyOtp(Request $request)
    {
        $data = $request->validate([
            'otp' => ['required', 'digits:6'],
        ]);

        $pending = $request->session()->get('login_otp');
        if (! is_array($pending) || empty($pending['user_id']) || empty($pending['hash'])) {
            return redirect()->route('login')->withErrors(['email' => 'Login session expired. Sign in again.']);
        }

        if (now()->greaterThan($pending['expires_at'])) {
            $request->session()->forget(['login_otp', 'login_otp_test_code']);

            return redirect()->route('login')->withErrors(['email' => 'OTP expired. Sign in again.']);
        }

        if ((int) ($pending['attempts'] ?? 0) >= 5) {
            $request->session()->forget(['login_otp', 'login_otp_test_code']);

            return redirect()->route('login')->withErrors(['email' => 'Too many OTP attempts. Sign in again.']);
        }

        if (! Hash::check($data['otp'], $pending['hash'])) {
            $pending['attempts'] = (int) ($pending['attempts'] ?? 0) + 1;
            $request->session()->put('login_otp', $pending);

            return back()->withErrors(['otp' => 'OTP code is incorrect.']);
        }

        $user = User::find($pending['user_id']);
        if (! $user || ! $user->is_active) {
            $request->session()->forget(['login_otp', 'login_otp_test_code']);

            return redirect()->route('login')->withErrors(['email' => 'This login can no longer be completed.']);
        }

        $request->session()->forget(['login_otp', 'login_otp_test_code']);
        Auth::login($user);
        $request->session()->regenerate();
        $user->forceFill(['last_login_at' => now()])->save();
        $this->hydrateLegacySession($request, $user);

        if ($user->mustChangePassword()) {
            return redirect()->route('password.change');
        }

        return redirect()->intended('/dashboard');
    }

    public function resendOtp(Request $request)
    {
        $pending = $request->session()->get('login_otp');
        if (! is_array($pending) || empty($pending['user_id']) || empty($pending['workspace'])) {
            return redirect()->route('login');
        }

        $user = User::find($pending['user_id']);
        if (! $user || ! $user->is_active) {
            $request->session()->forget(['login_otp', 'login_otp_test_code']);

            return redirect()->route('login')->withErrors(['email' => 'This login can no longer be completed.']);
        }

        $this->sendLoginOtp($request, $user, (string) $pending['workspace']);

        return back()->with('success', 'A new OTP has been sent to your email.');
    }

    public function showRegister()
    {
        return view('pages.register');
    }

    public function showChangePassword()
    {
        return view('pages.change-password');
    }

    public function changePassword(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'confirmed', $this->passwordRule($user?->tenant_id)],
        ]);

        if (! $user || ! Hash::check($data['current_password'], $user->password)) {
            return back()->withErrors(['current_password' => 'Current password is incorrect.']);
        }

        if (Hash::check($data['password'], $user->password)) {
            return back()->withErrors(['password' => 'New password must be different from the temporary password.']);
        }

        $user->forceFill([
            'password' => Hash::make($data['password']),
            'password_changed_at' => now(),
        ])->save();

        $request->session()->regenerate();

        return redirect('/dashboard')->with('success', 'Password changed successfully.');
    }

    public function register(Request $request)
    {
        $request->merge([
            'workspace' => $this->normalizeWorkspace((string) $request->input('workspace', '')),
            'email' => Str::lower((string) $request->input('email', '')),
        ]);

        $data = $request->validate(
            [
                'company_name' => ['required', 'string', 'min:2', 'max:255'],
                'workspace' => ['required', 'string', 'min:3', 'max:80', 'regex:/^[a-z0-9][a-z0-9-]*[a-z0-9]$/', 'unique:tenants,slug'],
                'name' => ['required', 'string', 'min:2', 'max:255'],
                'email' => ['required', 'email:rfc', 'max:255', 'unique:users,email'],
                'password' => [
                    'required',
                    'confirmed',
                    Password::min(10)->mixedCase()->numbers()->symbols(),
                ],
            ],
            [
                'workspace.regex' => 'Workspace must use lowercase letters, numbers, and hyphens, and must start and end with a letter or number.',
                'workspace.unique' => 'This workspace is already registered.',
            ]
        );

        $user = DB::transaction(function () use ($data): User {
            $tenantId = DB::table('tenants')->insertGetId([
                'company_name' => $data['company_name'],
                'slug' => $data['workspace'],
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

        $this->sendLoginOtp($request, $user, $data['workspace']);

        return redirect()->route('login.otp');
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

    private function normalizeWorkspace(string $workspace): string
    {
        return Str::slug(Str::lower(trim($workspace)));
    }

    private function sendLoginOtp(Request $request, User $user, string $workspace): void
    {
        $otp = (string) random_int(100000, 999999);

        $request->session()->put('login_otp', [
            'user_id' => $user->id,
            'workspace' => $workspace,
            'email' => $user->email,
            'hash' => Hash::make($otp),
            'attempts' => 0,
            'expires_at' => now()->addMinutes(10),
        ]);

        if (app()->runningUnitTests() || app()->environment('local') || config('mail.default') === 'log') {
            $request->session()->put('login_otp_test_code', $otp);
        }

        Mail::raw(
            "Your Onyx login OTP is {$otp}. It expires in 10 minutes.",
            fn ($message) => $message
                ->to($user->email)
                ->subject('Your Onyx login OTP')
        );
    }

    private function passwordRule(?int $tenantId): Password
    {
        $settings = SecuritySetting::forTenant($tenantId);
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
