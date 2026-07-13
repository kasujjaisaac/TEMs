<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
    <title>Login | Onyx Business Control System</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        :root {
            --bg: #050506;
            --panel: #0a0a0c;
            --panel-2: #101014;
            --line: rgba(255,255,255,.1);
            --line-strong: rgba(255,255,255,.22);
            --text: #fff;
            --muted: #858590;
            --soft: #d8d8de;
            --danger: #ffaaaa;
        }

        * { box-sizing: border-box; }
        html, body { min-height: 100%; }

        body {
            align-items: center;
            background:
                linear-gradient(rgba(255,255,255,.032) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255,255,255,.032) 1px, transparent 1px),
                radial-gradient(circle at 50% 18%, rgba(255,255,255,.08), transparent 28%),
                var(--bg);
            background-size: 40px 40px, 40px 40px, auto, auto;
            color: var(--text);
            display: flex;
            font-family: "Inter", system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
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
            box-shadow: 0 34px 90px rgba(0,0,0,.55);
            display: grid;
            grid-template-columns: minmax(280px, 0.9fr) minmax(360px, 1.1fr);
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
            align-items: center;
            background:
                linear-gradient(145deg, rgba(255,255,255,.08), transparent 38%),
                #09090b;
            border-right: 1px solid var(--line);
            display: flex;
            justify-content: center;
            padding: 30px;
            position: relative;
        }

        .brand-lockup {
            align-items: center;
            display: flex;
            justify-content: center;
            position: relative;
            z-index: 3;
        }

        .brand-mark {
            align-items: center;
            background: #fff;
            display: flex;
            height: 150px;
            justify-content: center;
            padding: 16px;
            width: 150px;
        }

        .brand-mark img {
            display: block;
            height: 100%;
            object-fit: contain;
            width: 100%;
        }

        .form-side {
            align-content: center;
            background:
                linear-gradient(180deg, rgba(255,255,255,.035), transparent 34%),
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
            background: #fff;
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
            background: #050506;
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
            border-color: #fff;
            box-shadow: 0 0 0 3px rgba(255,255,255,.08);
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

        .primary-button {
            align-items: center;
            background: #fff;
            border: 1px solid #fff;
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
            background: transparent;
            color: #fff;
        }

        .login-foot {
            align-items: center;
            border-top: 1px solid var(--line);
            display: flex;
            gap: 12px;
            justify-content: space-between;
            margin-top: 26px;
            padding-top: 16px;
        }

        .login-foot span {
            color: var(--muted);
            font-size: 12px;
            font-weight: 700;
        }

        .login-foot a {
            border: 1px solid var(--line);
            color: #fff;
            font-size: 11px;
            font-weight: 900;
            min-height: 34px;
            padding: 9px 11px;
            text-decoration: none;
            text-transform: uppercase;
        }

        .login-foot a:hover {
            background: #fff;
            color: #050506;
        }

        @media (max-width: 820px) {
            body {
                padding: 16px;
            }

            .auth-shell {
                align-items: flex-start;
                min-height: calc(100vh - 32px);
                padding-top: 54px;
            }

            .login-frame {
                grid-template-columns: 1fr;
                min-height: auto;
            }

            .brand-side {
                border-bottom: 1px solid var(--line);
                border-right: 0;
                padding: 24px;
            }

            .form-side {
                padding: 28px 22px;
            }
        }

        @media (max-width: 460px) {
            .login-title {
                font-size: 24px;
            }

            .login-foot {
                align-items: stretch;
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <main class="auth-shell" aria-labelledby="login-title">
        <a class="back-link" href="{{ url('/') }}"><i class="fa-solid fa-arrow-left"></i> Back</a>

        <section class="login-frame">
            <aside class="brand-side" aria-label="Onyx logo">
                <div class="brand-lockup">
                    <div class="brand-mark" aria-label="Onyx Business Control System">
                        <img src="{{ asset('assets/onxy logo.jpeg') }}" alt="">
                    </div>
                </div>
            </aside>

            <section class="form-side">
                <div class="login-card">
                    <div class="login-eyebrow">Authorized access</div>
                    <h1 class="login-title" id="login-title">Sign in to your workspace</h1>
                    <p class="login-subtitle">Use your company workspace and administrator credentials to continue.</p>

                    <form class="login-form" method="POST" action="{{ route('login') }}">
                        @csrf

                        @if($errors->any())
                            <div class="error-box">{{ $errors->first() }}</div>
                        @endif

                        <div class="field-group">
                            <label for="workspace">Workspace</label>
                            <div class="input-wrap">
                                <i class="fa-solid fa-building"></i>
                                <input id="workspace" name="workspace" type="text" class="input" value="{{ old('workspace') }}" placeholder="onyx-tech" autocomplete="organization">
                            </div>
                        </div>

                        <div class="field-group">
                            <label for="email">Email Address</label>
                            <div class="input-wrap">
                                <i class="fa-solid fa-envelope"></i>
                                <input id="email" name="email" type="email" class="input" value="{{ old('email') }}" placeholder="admin@clinic.test" autocomplete="email" required autofocus>
                            </div>
                        </div>

                        <div class="field-group">
                            <label for="password">Password</label>
                            <div class="input-wrap">
                                <i class="fa-solid fa-lock"></i>
                                <input id="password" name="password" type="password" class="input" placeholder="Enter password" autocomplete="current-password" required>
                            </div>
                        </div>

                        <button class="primary-button" type="submit">
                            <i class="fa-solid fa-arrow-right-to-bracket"></i>
                            Sign In
                        </button>
                    </form>

                    <footer class="login-foot">
                        <span>New workspace?</span>
                        <a href="{{ route('register') }}">Register</a>
                    </footer>
                </div>
            </section>
        </section>
    </main>
</body>
</html>
