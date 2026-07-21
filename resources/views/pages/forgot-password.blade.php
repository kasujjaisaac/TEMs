<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
    <title>Forgot Password | Texaro Technologies Limited</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        :root {
            --bg: #07111a;
            --panel: #101923;
            --accent: #ffffff;
            --line: rgba(255,255,255,.18);
            --line-strong: rgba(255,255,255,.42);
            --text: #fff;
            --muted: #8d99a8;
            --soft: #dce3ec;
            --danger: #ff7b64;
            --success: #8ff0c3;
        }

        * { box-sizing: border-box; }
        html, body { min-height: 100%; }

        body {
            align-items: center;
            background:
                linear-gradient(rgba(255,255,255,.04) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255,255,255,.028) 1px, transparent 1px),
                radial-gradient(circle at 80% 18%, rgba(255,255,255,.2), transparent 28%),
                var(--bg);
            background-size: 40px 40px, 40px 40px, auto, auto;
            color: var(--text);
            display: flex;
            font-family: "Poppins", system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            justify-content: center;
            margin: 0;
            padding: 24px;
        }

        .auth-card {
            background: linear-gradient(180deg, rgba(255,255,255,.035), transparent 34%), var(--panel);
            border: 1px solid var(--line-strong);
            box-shadow: 0 34px 90px rgba(0,0,0,.62);
            display: grid;
            gap: 20px;
            max-width: 420px;
            padding: 34px;
            width: 100%;
        }

        .eyebrow {
            align-items: center;
            color: var(--muted);
            display: flex;
            font-size: 11px;
            font-weight: 900;
            gap: 8px;
            text-transform: uppercase;
        }

        .eyebrow::before {
            background: var(--accent);
            content: "";
            height: 1px;
            width: 34px;
        }

        h1 {
            font-size: 27px;
            font-weight: 900;
            line-height: 1.08;
            margin: 0 0 8px;
        }

        p {
            color: var(--muted);
            font-size: 12px;
            font-weight: 700;
            line-height: 1.55;
            margin: 0;
        }

        form {
            display: grid;
            gap: 13px;
        }

        .alert {
            background: rgba(255,255,255,.05);
            border: 1px solid rgba(255,170,170,.5);
            color: var(--danger);
            font-size: 12px;
            font-weight: 800;
            line-height: 1.5;
            padding: 11px 12px;
        }

        .alert.success {
            border-color: rgba(143,240,195,.35);
            color: var(--success);
        }

        .field {
            display: grid;
            gap: 7px;
        }

        label {
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

        .input-wrap:focus-within {
            border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(255,255,255,.12);
        }

        .input-wrap i {
            color: var(--muted);
            font-size: 12px;
            text-align: center;
        }

        input {
            background: transparent;
            border: 0;
            color: #fff;
            font: inherit;
            font-size: 13px;
            font-weight: 700;
            height: 42px;
            min-width: 0;
            outline: 0;
            width: 100%;
        }

        .actions {
            display: grid;
            gap: 10px;
            margin-top: 4px;
        }

        button, a.button {
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
            text-decoration: none;
            text-transform: uppercase;
            width: 100%;
        }

        button:hover, a.button:hover {
            background: #0b141e;
            color: #fff;
        }

        .secondary {
            background: transparent;
            border-color: var(--line);
            color: #fff;
        }

        @media (max-width: 520px) {
            body { padding: 16px; }
            .auth-card { padding: 28px 22px; }
            h1 { font-size: 24px; }
        }
    </style>
</head>
<body>
    <main class="auth-card" aria-labelledby="forgot-password-title">
        <header>
            <div class="eyebrow">Password reset</div>
            <h1 id="forgot-password-title">Forgot your password?</h1>
            <p>Enter your email address. If the account exists, we will send a secure reset link.</p>
        </header>

        <form method="POST" action="{{ route('password.email') }}">
            @csrf

            @if($errors->any())
                <div class="alert">{{ $errors->first() }}</div>
            @endif
            @if(session('success'))
                <div class="alert success">{{ session('success') }}</div>
            @endif
            @if(session('password_reset_test_url'))
                <div class="alert success">Local reset link: {{ session('password_reset_test_url') }}</div>
            @endif

            <div class="field">
                <label for="email">Email Address</label>
                <div class="input-wrap">
                    <i class="fa-solid fa-envelope"></i>
                    <input id="email" name="email" type="email" value="{{ old('email') }}" placeholder="admin@company.test" autocomplete="email" required autofocus>
                </div>
            </div>

            <div class="actions">
                <button type="submit"><i class="fa-solid fa-paper-plane"></i> Send Reset Link</button>
                <a class="button secondary" href="{{ route('login') }}"><i class="fa-solid fa-arrow-left"></i> Back to Login</a>
            </div>
        </form>
    </main>
</body>
</html>
