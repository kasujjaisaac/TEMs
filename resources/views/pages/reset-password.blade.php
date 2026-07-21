<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
    <title>Reset Password | Texaro Technologies Limited</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        :root {
            --bg: #07111a;
            --panel: #101923;
            --accent: #ff6a00;
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
                radial-gradient(circle at 80% 18%, rgba(255,106,0,.2), transparent 28%),
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
            max-width: 440px;
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

        .input-wrap.has-password-toggle {
            grid-template-columns: 18px 1fr 32px;
        }

        .input-wrap:focus-within {
            border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(255,106,0,.12);
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

        .note {
            color: var(--muted);
            font-size: 11px;
            font-weight: 700;
            line-height: 1.45;
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
    <main class="auth-card" aria-labelledby="reset-password-title">
        <header>
            <div class="eyebrow">Password reset</div>
            <h1 id="reset-password-title">Create a new password</h1>
            <p>Set a private password for your account. This reset link can only be used once.</p>
        </header>

        <form method="POST" action="{{ route('password.reset.update') }}">
            @csrf

            @if($errors->any())
                <div class="alert">{{ $errors->first() }}</div>
            @endif

            <input type="hidden" name="token" value="{{ $token }}">

            <div class="field">
                <label for="email">Email Address</label>
                <div class="input-wrap">
                    <i class="fa-solid fa-envelope"></i>
                    <input id="email" name="email" type="email" value="{{ old('email', $email) }}" placeholder="admin@company.test" autocomplete="email" required>
                </div>
            </div>

            <div class="field">
                <label for="password">New Password</label>
                <div class="input-wrap has-password-toggle">
                    <i class="fa-solid fa-lock"></i>
                    <input id="password" name="password" type="password" autocomplete="new-password" required>
                    <button class="password-toggle" type="button" data-target="password" aria-label="Show new password" aria-pressed="false">
                        <i class="fa-solid fa-eye" aria-hidden="true"></i>
                    </button>
                </div>
            </div>

            <div class="field">
                <label for="password_confirmation">Confirm New Password</label>
                <div class="input-wrap has-password-toggle">
                    <i class="fa-solid fa-shield-halved"></i>
                    <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required>
                    <button class="password-toggle" type="button" data-target="password_confirmation" aria-label="Show password confirmation" aria-pressed="false">
                        <i class="fa-solid fa-eye" aria-hidden="true"></i>
                    </button>
                </div>
            </div>

            <div class="note">Use at least 10 characters with uppercase, lowercase, number, and symbol.</div>

            <div class="actions">
                <button type="submit"><i class="fa-solid fa-check"></i> Reset Password</button>
                <a class="button secondary" href="{{ route('login') }}"><i class="fa-solid fa-arrow-left"></i> Back to Login</a>
            </div>
        </form>
    </main>

    <script>
        document.querySelectorAll('.password-toggle').forEach((toggle) => {
            toggle.addEventListener('click', () => {
                const input = document.getElementById(toggle.dataset.target);
                const icon = toggle.querySelector('i');
                const isVisible = input.type === 'text';

                input.type = isVisible ? 'password' : 'text';
                toggle.setAttribute('aria-pressed', String(! isVisible));
                toggle.setAttribute('aria-label', `${isVisible ? 'Show' : 'Hide'} ${input.labels[0].textContent.toLowerCase()}`);
                icon.classList.toggle('fa-eye', isVisible);
                icon.classList.toggle('fa-eye-slash', ! isVisible);
            });
        });
    </script>
</body>
</html>
