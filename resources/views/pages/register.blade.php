<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
    <title>Register | Onyx Business Control System</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        :root {
            --bg: #07111a;
            --panel: #101923;
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

        .register-frame {
            background: transparent;
            border: 1px solid var(--line-strong);
            box-shadow: 0 34px 90px rgba(0,0,0,.62), 0 0 0 1px rgba(255,106,0,.08);
            display: grid;
            grid-template-columns: minmax(360px, 420px) minmax(390px, 1fr);
            min-height: 620px;
            overflow: hidden;
            position: relative;
            width: min(980px, 100%);
        }

        .register-frame::before {
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
            position: relative;
            width: 54px;
            z-index: 3;
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

        .brand-metrics {
            border-top: 1px solid rgba(255,255,255,.13);
            display: grid;
            gap: 10px;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            padding-top: 13px;
        }

        .metric {
            min-width: 0;
        }

        .metric strong {
            display: block;
            font-size: 14px;
            font-weight: 900;
            line-height: 1;
        }

        .metric span {
            color: rgba(255,255,255,.52);
            display: block;
            font-size: 9px;
            font-weight: 800;
            line-height: 1.3;
            margin-top: 5px;
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

        .register-card {
            margin: 0 auto;
            max-width: 440px;
            position: relative;
            width: 100%;
            z-index: 3;
        }

        .eyebrow {
            align-items: center;
            color: var(--muted);
            display: flex;
            font-size: 11px;
            font-weight: 900;
            gap: 8px;
            margin-bottom: 16px;
            text-transform: uppercase;
        }

        .eyebrow::before {
            background: var(--accent);
            content: "";
            height: 1px;
            width: 34px;
        }

        .title {
            font-size: 28px;
            font-weight: 900;
            line-height: 1.08;
            margin: 0 0 9px;
        }

        .subtitle {
            color: var(--muted);
            font-size: 12px;
            font-weight: 700;
            line-height: 1.55;
            margin: 0 0 24px;
        }

        .register-form {
            display: grid;
            gap: 13px;
        }

        .error-box {
            background: rgba(255,255,255,.05);
            border: 1px solid rgba(255,170,170,.5);
            color: var(--danger);
            font-size: 12px;
            font-weight: 800;
            line-height: 1.5;
            padding: 11px 12px;
        }

        .field-grid {
            display: grid;
            gap: 12px;
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .field-group {
            display: grid;
            gap: 7px;
        }

        .field-group.full {
            grid-column: 1 / -1;
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

        .field-error {
            color: var(--danger);
            font-size: 11px;
            font-weight: 800;
            line-height: 1.4;
        }

        .password-note {
            color: var(--muted);
            font-size: 11px;
            font-weight: 700;
            line-height: 1.45;
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
            margin-top: 5px;
            text-transform: uppercase;
            width: 100%;
        }

        .primary-button:hover {
            background: #0b141e;
            color: #fff;
        }

        .register-foot {
            align-items: center;
            border-top: 1px solid var(--line);
            display: flex;
            gap: 12px;
            justify-content: space-between;
            margin-top: 24px;
            padding-top: 16px;
        }

        .register-foot span {
            color: var(--muted);
            font-size: 12px;
            font-weight: 700;
        }

        .register-foot a {
            border: 1px solid var(--line);
            color: #fff;
            font-size: 11px;
            font-weight: 900;
            min-height: 34px;
            padding: 9px 11px;
            text-decoration: none;
            text-transform: uppercase;
        }

        .register-foot a:hover {
            background: var(--accent);
            color: #050506;
        }

        @media (max-width: 860px) {
            body { padding: 16px; }
            .auth-shell {
                align-items: flex-start;
                min-height: calc(100vh - 32px);
                padding-top: 54px;
            }
            .register-frame {
                grid-template-columns: 1fr;
                min-height: auto;
            }
            .brand-side {
                border-bottom: 1px solid var(--line);
                border-right: 0;
                padding: 24px;
            }
            .brand-panel { gap: 20px; }
            .brand-copy h2 { font-size: 22px; }
            .feature-list { grid-template-columns: 1fr; }
            .form-side { padding: 28px 22px; }
        }

        @media (max-width: 560px) {
            .field-grid { grid-template-columns: 1fr; }
            .title { font-size: 24px; }
            .brand-side { padding: 22px 18px; }
            .brand-lockup { align-items: flex-start; }
            .brand-copy h2 { font-size: 20px; }
            .brand-metrics { grid-template-columns: 1fr; }
            .register-foot {
                align-items: stretch;
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <main class="auth-shell" aria-labelledby="register-title">
        <a class="back-link" href="{{ route('login') }}"><i class="fa-solid fa-arrow-left"></i> Sign In</a>

        <section class="register-frame">
            <aside class="brand-side" aria-label="Onyx system overview">
                <div class="brand-panel">
                    <div class="brand-heading">
                        <div class="brand-lockup">
                            <div class="brand-mark" aria-label="Onyx Business Control System">
                                <img src="{{ asset('assets/onxy logo.jpeg') }}" alt="">
                            </div>
                            <div class="brand-name">
                                <strong>Onyx Business Control System</strong>
                                <span>Secure company workspace</span>
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
                                <span>Organize customer records and keep workspace access under administrator control.</span>
                            </div>
                        </div>
                    </div>

                    <div class="brand-metrics" aria-label="System highlights">
                        <div class="metric">
                            <strong>24/7</strong>
                            <span>Workspace access</span>
                        </div>
                        <div class="metric">
                            <strong>Multi</strong>
                            <span>Module operations</span>
                        </div>
                        <div class="metric">
                            <strong>Admin</strong>
                            <span>Controlled login</span>
                        </div>
                    </div>
                </div>
            </aside>

            <section class="form-side">
                <div class="register-card">
                    <div class="eyebrow">Workspace registration</div>
                    <h1 class="title" id="register-title">Create an admin workspace</h1>
                    <p class="subtitle">Your account will be created as the workspace administrator.</p>

                    <form class="register-form" method="POST" action="{{ route('register') }}">
                        @csrf

                        @if($errors->any())
                            <div class="error-box">{{ $errors->first() }}</div>
                        @endif

                        <div class="field-grid">
                            <div class="field-group full">
                                <label for="company_name">Company Name</label>
                                <div class="input-wrap">
                                    <i class="fa-solid fa-building"></i>
                                    <input id="company_name" name="company_name" type="text" class="input" value="{{ old('company_name') }}" placeholder="Onyx Technologies" autocomplete="organization" required>
                                </div>
                                @error('company_name')<div class="field-error">{{ $message }}</div>@enderror
                            </div>

                            <div class="field-group full">
                                <label for="workspace">Workspace</label>
                                <div class="input-wrap">
                                    <i class="fa-solid fa-fingerprint"></i>
                                    <input id="workspace" name="workspace" type="text" class="input" value="{{ old('workspace') }}" placeholder="onyx-tech" autocomplete="organization-title" required>
                                </div>
                                @error('workspace')<div class="field-error">{{ $message }}</div>@enderror
                            </div>

                            <div class="field-group">
                                <label for="name">Admin Name</label>
                                <div class="input-wrap">
                                    <i class="fa-solid fa-user-shield"></i>
                                    <input id="name" name="name" type="text" class="input" value="{{ old('name') }}" placeholder="Admin User" autocomplete="name" required>
                                </div>
                                @error('name')<div class="field-error">{{ $message }}</div>@enderror
                            </div>

                            <div class="field-group">
                                <label for="email">Admin Email</label>
                                <div class="input-wrap">
                                    <i class="fa-solid fa-envelope"></i>
                                    <input id="email" name="email" type="email" class="input" value="{{ old('email') }}" placeholder="admin@company.com" autocomplete="email" required>
                                </div>
                                @error('email')<div class="field-error">{{ $message }}</div>@enderror
                            </div>

                            <div class="field-group">
                                <label for="password">Password</label>
                                <div class="input-wrap">
                                    <i class="fa-solid fa-lock"></i>
                                    <input id="password" name="password" type="password" class="input" placeholder="Create password" autocomplete="new-password" required>
                                </div>
                                @error('password')<div class="field-error">{{ $message }}</div>@enderror
                            </div>

                            <div class="field-group">
                                <label for="password_confirmation">Confirm Password</label>
                                <div class="input-wrap">
                                    <i class="fa-solid fa-key"></i>
                                    <input id="password_confirmation" name="password_confirmation" type="password" class="input" placeholder="Confirm password" autocomplete="new-password" required>
                                </div>
                            </div>
                        </div>

                        <div class="password-note">Use at least 10 characters with uppercase, lowercase, number, and symbol.</div>

                        <button class="primary-button" type="submit">
                            <i class="fa-solid fa-user-plus"></i>
                            Create Account
                        </button>
                    </form>

                    <footer class="register-foot">
                        <span>Already registered?</span>
                        <a href="{{ route('login') }}">Sign In</a>
                    </footer>
                </div>
            </section>
        </section>
    </main>
</body>
</html>
