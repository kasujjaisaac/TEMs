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
            'email' => Str::lower((string) $request->input('email', '')),
        ]);

        $data = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required'],
        ]);

        $email = Str::lower(trim($data['email']));
        $user = User::where('email', $email)->first();
        $security = SecuritySetting::forTenant($user?->tenant_id);
        $throttleKey = 'login:' . $email . '|' . $request->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, (int) $security['login_attempt_limit'])) {
            $seconds = RateLimiter::availableIn($throttleKey);

            throw ValidationException::withMessages([
                'email' => 'Too many login attempts. Try again in ' . ceil($seconds / 60) . ' minute(s).',
            ]);
        }

        if (! $user) {
            RateLimiter::hit($throttleKey, max(60, (int) $security['account_lockout_minutes'] * 60));

            return back()
                ->withErrors(['email' => 'Email address is not registered.'])
                ->onlyInput('email');
        }

        if (! $user->is_active) {
            RateLimiter::hit($throttleKey, max(60, (int) $security['account_lockout_minutes'] * 60));

            return back()
                ->withErrors(['email' => 'This user account is inactive. Contact the administrator.'])
                ->onlyInput('email');
        }

        if (! Hash::check($data['password'], $user->password)) {
            RateLimiter::hit($throttleKey, max(60, (int) $security['account_lockout_minutes'] * 60));

            return back()
                ->withErrors(['password' => 'Password is incorrect for this account.'])
                ->onlyInput('email');
        }

        RateLimiter::clear($throttleKey);
        $this->sendLoginOtp($request, $user);

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
        if (! is_array($pending) || empty($pending['user_id'])) {
            return redirect()->route('login');
        }

        $user = User::find($pending['user_id']);
        if (! $user || ! $user->is_active) {
            $request->session()->forget(['login_otp', 'login_otp_test_code']);

            return redirect()->route('login')->withErrors(['email' => 'This login can no longer be completed.']);
        }

        $this->sendLoginOtp($request, $user);

        return back()->with('success', 'A new OTP has been sent to your email.');
    }

    public function showForgotPassword()
    {
        return view('pages.forgot-password');
    }

    public function sendPasswordResetLink(Request $request)
    {
        $request->merge([
            'email' => Str::lower((string) $request->input('email', '')),
        ]);

        $data = $request->validate([
            'email' => ['required', 'email', 'max:255'],
        ]);

        $email = Str::lower(trim($data['email']));
        $user = User::where('email', $email)->where('is_active', true)->first();

        if ($user) {
            $this->sendPasswordResetEmail($request, $user);
        }

        return back()
            ->with('success', 'If that account exists, a password reset link has been sent.')
            ->onlyInput('email');
    }

    public function showResetPassword(Request $request, string $token)
    {
        return view('pages.reset-password', [
            'token' => $token,
            'email' => $request->query('email', ''),
        ]);
    }

    public function resetPassword(Request $request)
    {
        $request->merge([
            'email' => Str::lower((string) $request->input('email', '')),
        ]);

        $data = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'token' => ['required', 'string'],
            'password' => ['required', 'confirmed'],
        ]);

        $email = Str::lower(trim($data['email']));
        $user = User::where('email', $email)->where('is_active', true)->first();
        $tokenRecord = DB::table('password_reset_tokens')->where('email', $email)->first();

        if (! $user || ! $tokenRecord || ! Hash::check($data['token'], $tokenRecord->token)) {
            return back()->withErrors(['email' => 'This password reset link is invalid.'])->withInput($request->only('email'));
        }

        if (! $tokenRecord->created_at || now()->diffInMinutes($tokenRecord->created_at) > 60) {
            DB::table('password_reset_tokens')->where('email', $email)->delete();

            return back()->withErrors(['email' => 'This password reset link has expired.'])->withInput($request->only('email'));
        }

        $request->validate([
            'password' => ['required', 'confirmed', $this->passwordRule($user->tenant_id)],
        ]);

        $user->forceFill([
            'password' => Hash::make($data['password']),
            'password_changed_at' => now(),
        ])->save();

        DB::table('password_reset_tokens')->where('email', $email)->delete();

        return redirect()->route('login')->with('success', 'Password reset successfully. Sign in with your new password.');
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
        return redirect()->route('login');
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
            'company_name' => $tenant->company_name ?? config('app.name', 'Texaro Technologies Limited'),
            'currency' => $tenant->currency ?? 'UGX',
            'role' => $user->role ?: 'super_admin',
        ]);
    }

    private function sendLoginOtp(Request $request, User $user): void
    {
        $otp = (string) random_int(100000, 999999);

        $request->session()->put('login_otp', [
            'user_id' => $user->id,
            'email' => $user->email,
            'hash' => Hash::make($otp),
            'attempts' => 0,
            'expires_at' => now()->addMinutes(10),
        ]);

        if (app()->runningUnitTests() || app()->environment('local') || config('mail.default') === 'log') {
            $request->session()->put('login_otp_test_code', $otp);
        }

        Mail::raw(
            "Your Texaro login OTP is {$otp}. It expires in 10 minutes.",
            fn ($message) => $message
                ->to($user->email)
                ->subject('Your Texaro login OTP')
        );
    }

    private function sendPasswordResetEmail(Request $request, User $user): void
    {
        $token = Str::random(64);

        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $user->email],
            [
                'token' => Hash::make($token),
                'created_at' => now(),
            ]
        );

        $resetUrl = route('password.reset', [
            'token' => $token,
            'email' => $user->email,
        ]);

        if (app()->runningUnitTests() || app()->environment('local') || config('mail.default') === 'log') {
            $request->session()->put('password_reset_test_url', $resetUrl);
        }

        Mail::raw(
            "Use this link to reset Your Texaro password: {$resetUrl}\n\nThis link expires in 60 minutes.",
            fn ($message) => $message
                ->to($user->email)
                ->subject('Reset Your Texaro password')
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
