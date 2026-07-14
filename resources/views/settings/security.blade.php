@extends('layouts.app')

@section('content')
@include('settings.partials.style')

<section class="access-page">
    <header class="access-header">
        <div class="access-title">
            <div class="access-title-icon"><i class="fa-solid fa-lock"></i></div>
            <div>
                <h1>Security Settings</h1>
                <p>Configure password rules, login protection, session controls, and administrator approval rules.</p>
            </div>
        </div>
    </header>

    <section class="access-kpis">
        <div class="access-kpi"><span>Password Length</span><strong>{{ $settings['password_min_length'] }}</strong></div>
        <div class="access-kpi"><span>Login Attempts</span><strong>{{ $settings['login_attempt_limit'] }}</strong></div>
        <div class="access-kpi"><span>Lockout Minutes</span><strong>{{ $settings['account_lockout_minutes'] }}</strong></div>
        <div class="access-kpi"><span>Session Minutes</span><strong>{{ $settings['session_timeout_minutes'] }}</strong></div>
    </section>

    @if(session('success'))
        <div class="access-alert success">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="access-alert error">{{ $errors->first() }}</div>
    @endif

    <form class="access-form" method="POST" action="{{ route('settings.security.update') }}">
        @csrf
        @method('PUT')

        <div class="security-grid">
            <section class="access-panel">
                <div class="access-panel-head">
                    <h2>Password Policy</h2>
                    <span class="access-badge">Rules</span>
                </div>
                <div class="access-form">
                    <div class="access-field">
                        <label for="password_min_length">Minimum Length</label>
                        <input id="password_min_length" name="password_min_length" type="number" min="8" max="64" value="{{ $settings['password_min_length'] }}">
                    </div>
                    <label class="access-check"><input type="checkbox" name="password_require_uppercase" value="1" @checked($settings['password_require_uppercase'])> Require uppercase</label>
                    <label class="access-check"><input type="checkbox" name="password_require_lowercase" value="1" @checked($settings['password_require_lowercase'])> Require lowercase</label>
                    <label class="access-check"><input type="checkbox" name="password_require_number" value="1" @checked($settings['password_require_number'])> Require number</label>
                    <label class="access-check"><input type="checkbox" name="password_require_symbol" value="1" @checked($settings['password_require_symbol'])> Require symbol</label>
                    <div class="access-field">
                        <label for="password_expiry_days">Password Expiry Days</label>
                        <input id="password_expiry_days" name="password_expiry_days" type="number" min="0" max="365" value="{{ $settings['password_expiry_days'] }}">
                    </div>
                </div>
            </section>

            <section class="access-panel">
                <div class="access-panel-head">
                    <h2>Login Protection</h2>
                    <span class="access-badge">Lockout</span>
                </div>
                <div class="access-form">
                    <div class="access-field">
                        <label for="login_attempt_limit">Login Attempt Limit</label>
                        <input id="login_attempt_limit" name="login_attempt_limit" type="number" min="1" max="20" value="{{ $settings['login_attempt_limit'] }}">
                    </div>
                    <div class="access-field">
                        <label for="account_lockout_minutes">Lockout Minutes</label>
                        <input id="account_lockout_minutes" name="account_lockout_minutes" type="number" min="1" max="1440" value="{{ $settings['account_lockout_minutes'] }}">
                    </div>
                    <label class="access-check"><input type="checkbox" name="require_email_verification" value="1" @checked($settings['require_email_verification'])> Require email verification</label>
                    <label class="access-check"><input type="checkbox" name="require_two_factor" value="1" @checked($settings['require_two_factor'])> Require two-factor authentication</label>
                </div>
            </section>

            <section class="access-panel">
                <div class="access-panel-head">
                    <h2>Session Control</h2>
                    <span class="access-badge">Access</span>
                </div>
                <div class="access-form">
                    <div class="access-field">
                        <label for="session_timeout_minutes">Session Timeout Minutes</label>
                        <input id="session_timeout_minutes" name="session_timeout_minutes" type="number" min="5" max="1440" value="{{ $settings['session_timeout_minutes'] }}">
                    </div>
                    <label class="access-check"><input type="checkbox" name="allow_multiple_sessions" value="1" @checked($settings['allow_multiple_sessions'])> Allow multiple sessions</label>
                    <label class="access-check"><input type="checkbox" name="force_password_change_first_login" value="1" @checked($settings['force_password_change_first_login'])> Force password change on first login</label>
                    <label class="access-check"><input type="checkbox" name="admin_approval_required" value="1" @checked($settings['admin_approval_required'])> Admin approval required for new users</label>
                </div>
            </section>
        </div>

        <button class="access-button" type="submit" style="width: fit-content;"><i class="fa-solid fa-lock"></i> Save Security Settings</button>
    </form>
</section>
@endsection
