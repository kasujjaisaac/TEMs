<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
    <title>Login | Texaro Technologies Limited</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        :root {
            --bg: #07111a;
            --panel: #101923;
            --panel-2: #131d28;
            --accent: #ff6a00;
            --accent-2: #ff8a1d;
            --line: rgba(255,106,0,.18);
            --line-strong: rgba(255,106,0,.42);
            --text: #fff;
            --muted: #8d99a8;
            --soft: #dce3ec;
            --danger: #ff7b64;
        }

        * { box-sizing: border-box; }
        html, body { min-height: 100%; }

        body {
            align-items: center;
            background:
                linear-gradient(rgba(255,106,0,.04) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255,106,0,.028) 1px, transparent 1px),
                radial-gradient(circle at 82% 14%, rgba(255,106,0,.24), transparent 30%),
                radial-gradient(circle at 18% 82%, rgba(255,138,29,.12), transparent 28%),
                var(--bg);
            background-size: 40px 40px, 40px 40px, auto, auto, auto;
            color: var(--text);
            display: flex;
            font-family: "Poppins", system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            justify-content: center;
            margin: 0;
            padding: 28px;
        }

        .auth-shell {
            align-items: center;
            display: flex;
            justify-content: center;
            min-height: calc(100vh - 56px);
            position: relative;
            width: 100%;
        }

        .back-link {
            align-items: center;
            background: rgba(0,0,0,.34);
            border: 1px solid var(--line);
            color: var(--soft);
            display: inline-flex;
            font-size: 12px;
            font-weight: 900;
            gap: 8px;
            left: 0;
            min-height: 36px;
            padding: 0 12px;
            position: absolute;
            text-decoration: none;
            top: 0;
        }

        .back-link:hover {
            background: #fff;
            color: #050506;
        }

        .login-frame {
            background: transparent;
            border: 1px solid var(--line-strong);
            box-shadow: 0 34px 90px rgba(0,0,0,.62), 0 0 0 1px rgba(255,106,0,.08);
            display: grid;
            grid-template-columns: minmax(360px, 420px) minmax(360px, 1fr);
            min-height: 560px;
            overflow: hidden;
            position: relative;
            width: min(940px, 100%);
        }

        .login-frame::before {
            border: 1px solid rgba(255,255,255,.055);
            content: "";
            inset: 10px;
            pointer-events: none;
            position: absolute;
            z-index: 2;
        }

        .brand-side {
            align-items: stretch;
            background:
                linear-gradient(145deg, rgba(255,106,0,.28), transparent 35%),
                linear-gradient(315deg, rgba(255,138,29,.2), transparent 40%),
                linear-gradient(180deg, rgba(255,255,255,.065), transparent 60%),
                #07111a;
            border-right: 1px solid var(--line);
            display: flex;
            min-width: 0;
            padding: 28px;
            position: relative;
        }

        .brand-side::before {
            background:
                linear-gradient(rgba(255,106,0,.055) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255,106,0,.04) 1px, transparent 1px);
            background-size: 34px 34px;
            content: "";
            inset: 0;
            opacity: .35;
            pointer-events: none;
            position: absolute;
        }

        .brand-panel {
            align-content: start;
            display: grid;
            gap: 20px;
            min-width: 0;
            position: relative;
            width: 100%;
            z-index: 3;
        }

        .brand-heading {
            display: grid;
            gap: 14px;
        }

        .brand-lockup {
            align-items: center;
            display: flex;
            gap: 14px;
            min-width: 0;
        }

        .brand-mark {
            align-items: center;
            background: var(--accent);
            display: flex;
            flex: 0 0 auto;
            height: 54px;
            justify-content: center;
            padding: 8px;
            width: 54px;
        }

        .brand-mark img {
            display: block;
            height: 100%;
            object-fit: contain;
            width: 100%;
        }

        .brand-name {
            min-width: 0;
        }

        .brand-name strong {
            display: block;
            font-size: 14px;
            font-weight: 900;
            line-height: 1.1;
        }

        .brand-name span {
            color: rgba(255,255,255,.62);
            display: block;
            font-size: 9px;
            font-weight: 900;
            letter-spacing: 0;
            margin-top: 5px;
            text-transform: uppercase;
        }

        .brand-copy {
            display: grid;
            gap: 9px;
        }

        .brand-copy h2 {
            font-size: 24px;
            font-weight: 900;
            line-height: 1.04;
            margin: 0;
        }

        .brand-copy p {
            color: rgba(255,255,255,.68);
            font-size: 11px;
            font-weight: 700;
            line-height: 1.6;
            margin: 0;
        }

        .feature-list {
            display: grid;
            gap: 8px;
        }

        .feature-item {
            align-items: center;
            background: rgba(5,5,6,.42);
            border: 1px solid rgba(255,255,255,.11);
            display: grid;
            gap: 12px;
            grid-template-columns: 30px 1fr;
            min-height: 54px;
            padding: 9px;
        }

        .feature-icon {
            align-items: center;
            background: rgba(255,255,255,.08);
            border: 1px solid rgba(255,255,255,.13);
            color: #fff;
            display: inline-flex;
            height: 30px;
            justify-content: center;
            width: 30px;
        }

        .feature-icon i {
            font-size: 12px;
        }

        .feature-icon.inventory {
            color: var(--accent);
        }

        .feature-icon.finance {
            color: #ffb347;
        }

        .feature-icon.team {
            color: var(--accent-2);
        }

        .feature-item strong {
            display: block;
            font-size: 11px;
            font-weight: 900;
            line-height: 1.25;
        }

        .feature-item span {
            color: rgba(255,255,255,.56);
            display: block;
            font-size: 10px;
            font-weight: 700;
            line-height: 1.45;
            margin-top: 2px;
        }

        .form-side {
            align-content: center;
            background:
                linear-gradient(180deg, rgba(255,255,255,.035), transparent 34%),
                linear-gradient(135deg, rgba(255,106,0,.055), transparent 35%),
                var(--panel);
            display: grid;
            padding: 42px;
            position: relative;
        }

        .login-card {
            margin: 0 auto;
            max-width: 390px;
            position: relative;
            width: 100%;
            z-index: 3;
        }

        .login-eyebrow {
            align-items: center;
            color: var(--muted);
            display: flex;
            font-size: 11px;
            font-weight: 900;
            gap: 8px;
            margin-bottom: 16px;
            text-transform: uppercase;
        }

        .login-eyebrow::before {
            background: var(--accent);
            content: "";
            height: 1px;
            width: 34px;
        }

        .login-title {
            font-size: 28px;
            font-weight: 900;
            line-height: 1.08;
            margin: 0 0 9px;
        }

        .login-subtitle {
            color: var(--muted);
            font-size: 12px;
            font-weight: 700;
            line-height: 1.55;
            margin: 0 0 26px;
        }

        .login-form {
            display: grid;
            gap: 13px;
        }

        .error-box {
            background: rgba(255,255,255,.05);
            border: 1px solid rgba(255,170,170,.5);
            color: var(--danger);
            font-size: 12px;
            font-weight: 800;
            padding: 11px 12px;
        }

        .error-box.success {
            border-color: rgba(143,240,195,.35);
            color: #8ff0c3;
        }

        .field-group {
            display: grid;
            gap: 7px;
        }

        .field-group label {
            color: var(--soft);
            font-size: 11px;
            font-weight: 900;
            text-transform: uppercase;
        }

        .input-wrap {
            align-items: center;
            background: #0b141e;
            border: 1px solid var(--line);
            display: grid;
            gap: 11px;
            grid-template-columns: 18px 1fr;
            min-height: 44px;
            padding: 0 12px;
        }

        .input-wrap.has-password-toggle {
            grid-template-columns: 18px 1fr 32px;
        }

        .input-wrap i {
            color: var(--muted);
            font-size: 12px;
            text-align: center;
        }

        .input-wrap:focus-within {
            border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(255,106,0,.12);
        }

        .input {
            background: transparent;
            border: 0;
            color: #fff;
            font: inherit;
            font-size: 13px;
            font-weight: 700;
            height: 42px;
            min-width: 0;
            outline: 0;
            padding: 0;
            width: 100%;
        }

        .input::placeholder {
            color: rgba(255,255,255,.24);
        }

        .input:-webkit-autofill,
        .input:-webkit-autofill:hover,
        .input:-webkit-autofill:focus,
        .input:-webkit-autofill:active {
            -webkit-box-shadow: 0 0 0 1000px #0a0a0c inset;
            -webkit-text-fill-color: #ffffff;
            caret-color: #ffffff;
            transition: background-color 9999s ease-out;
        }

        .password-toggle {
            align-items: center;
            background: transparent;
            border: 0;
            color: var(--muted);
            cursor: pointer;
            display: inline-flex;
            font: inherit;
            height: 32px;
            justify-content: center;
            padding: 0;
            width: 32px;
        }

        .password-toggle:hover,
        .password-toggle:focus {
            color: #fff;
            outline: 0;
        }

        .password-toggle:hover i,
        .password-toggle:focus i {
            color: #fff;
        }

        .password-toggle:focus-visible {
            box-shadow: 0 0 0 3px rgba(255,106,0,.18);
        }

        .form-link-row {
            display: flex;
            justify-content: flex-end;
            margin-top: -2px;
        }

        .form-link-row a {
            color: var(--soft);
            font-size: 11px;
            font-weight: 900;
            text-decoration: none;
            text-transform: uppercase;
        }

        .form-link-row a:hover {
            color: var(--accent);
        }

        .primary-button {
            align-items: center;
            background: var(--accent);
            border: 1px solid var(--accent);
            color: #050506;
            cursor: pointer;
            display: inline-flex;
            font: inherit;
            font-size: 12px;
            font-weight: 900;
            gap: 9px;
            height: 46px;
            justify-content: center;
            margin-top: 8px;
            text-transform: uppercase;
            width: 100%;
        }

        .primary-button:hover {
            background: #0b141e;
            color: #fff;
        }

        @media (max-width: 820px) {
            body {
                padding: 16px;
            }

            .auth-shell {
                align-items: center;
                min-height: calc(100vh - 32px);
                padding-top: 0;
            }

            .login-frame {
                grid-template-columns: 1fr;
                min-height: auto;
            }

            .brand-side {
                border-bottom: 1px solid var(--line);
                border-right: 0;
                justify-content: center;
                padding: 18px 24px;
            }

            .brand-panel {
                display: flex;
                justify-content: center;
            }

            .brand-heading {
                display: block;
            }

            .brand-lockup {
                justify-content: center;
            }

            .brand-name,
            .brand-copy,
            .feature-list {
                display: none;
            }

            .form-side {
                padding: 28px 22px;
            }
        }

        @media (max-width: 460px) {
            .login-title {
                font-size: 24px;
            }

            .brand-side {
                padding: 16px 18px;
            }

            .brand-lockup {
                align-items: center;
            }

        }
    </style>
</head>
<body>
    <main class="auth-shell" aria-labelledby="login-title">
        <a class="back-link" href="{{ url('/') }}"><i class="fa-solid fa-arrow-left"></i> Back</a>

        <section class="login-frame">
            <aside class="brand-side" aria-label="Texaro system overview">
                <div class="brand-panel">
                    <div class="brand-heading">
                        <div class="brand-lockup">
                            <div class="brand-mark" aria-label="Texaro Technologies Limited">
                                <img src="{{ asset('assets/texaro-logo.png') }}" alt="">
                            </div>
                            <div class="brand-name">
                                <strong>Texaro Technologies Limited</strong>
                                <span>Secure company access</span>
                            </div>
                        </div>

                        <div class="brand-copy">
                            <h2>One place to run daily operations with clarity.</h2>
                            <p>Manage sales, stock, customers, suppliers, purchases, reports, and financial workflows from a single controlled dashboard.</p>
                        </div>
                    </div>

                    <div class="feature-list" aria-label="Key system features">
                        <div class="feature-item">
                            <div class="feature-icon inventory"><i class="fa-solid fa-boxes-stacked"></i></div>
                            <div>
                                <strong>Inventory and product control</strong>
                                <span>Track stock movement, product records, purchases, and supplier activity.</span>
                            </div>
                        </div>

                        <div class="feature-item">
                            <div class="feature-icon finance"><i class="fa-solid fa-chart-line"></i></div>
                            <div>
                                <strong>Sales, reporting, and finance</strong>
                                <span>Monitor performance, review transactions, and keep business decisions data-led.</span>
                            </div>
                        </div>

                        <div class="feature-item">
                            <div class="feature-icon team"><i class="fa-solid fa-users-gear"></i></div>
                            <div>
                                <strong>Customers, teams, and access</strong>
                                <span>Organize customer records and keep system access under administrator control.</span>
                            </div>
                        </div>
                    </div>

                </div>
            </aside>

            <section class="form-side">
                <div class="login-card">
                    <div class="login-eyebrow">Authorized access</div>
                    <h1 class="login-title" id="login-title">Sign in to your account</h1>
                    <p class="login-subtitle">Use your email address and password to continue.</p>

                    <form class="login-form" method="POST" action="{{ route('login') }}">
                        @csrf

                        @if($errors->any())
                            <div class="error-box">{{ $errors->first() }}</div>
                        @endif
                        @if(session('success'))
                            <div class="error-box success">{{ session('success') }}</div>
                        @endif

                        <div class="field-group">
                            <label for="email">Email Address</label>
                            <div class="input-wrap">
                                <i class="fa-solid fa-envelope"></i>
                                <input id="email" name="email" type="email" class="input" value="{{ old('email') }}" placeholder="superadmin@texaro.local" autocomplete="email" required>
                            </div>
                        </div>

                        <div class="field-group">
                            <label for="password">Password</label>
                            <div class="input-wrap has-password-toggle">
                                <i class="fa-solid fa-lock"></i>
                                <input id="password" name="password" type="password" class="input" placeholder="Enter password" autocomplete="current-password" required>
                                <button class="password-toggle" type="button" data-target="password" aria-label="Show password" aria-pressed="false">
                                    <i class="fa-solid fa-eye" aria-hidden="true"></i>
                                </button>
                            </div>
                        </div>

                        <div class="form-link-row">
                            <a href="{{ route('password.request') }}">Forgot password?</a>
                        </div>

                        <button class="primary-button" type="submit">
                            <i class="fa-solid fa-arrow-right-to-bracket"></i>
                            Sign In
                        </button>
                    </form>
                </div>
            </section>
        </section>
    </main>

    <script>
        document.querySelectorAll('.password-toggle').forEach((toggle) => {
            toggle.addEventListener('click', () => {
                const input = document.getElementById(toggle.dataset.target);
                const icon = toggle.querySelector('i');
                const isVisible = input.type === 'text';

                input.type = isVisible ? 'password' : 'text';
                toggle.setAttribute('aria-pressed', String(! isVisible));
                toggle.setAttribute('aria-label', isVisible ? 'Show password' : 'Hide password');
                icon.classList.toggle('fa-eye', isVisible);
                icon.classList.toggle('fa-eye-slash', ! isVisible);
            });
        });
    </script>
</body>
</html>
