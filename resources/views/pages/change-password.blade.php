<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
    <title>Change Password | Onyx Business Control System</title>
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

        .password-card {
            background: linear-gradient(180deg, rgba(255,255,255,.035), transparent 34%), var(--panel);
            border: 1px solid var(--line-strong);
            box-shadow: 0 34px 90px rgba(0,0,0,.62);
            display: grid;
            gap: 22px;
            max-width: 440px;
            padding: 36px;
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
            font-size: 28px;
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

        .error-box {
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
            grid-template-columns: 18px 1fr 32px;
            min-height: 44px;
            padding: 0 12px;
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
            background: transparent;
            border: 0;
            color: var(--muted);
            cursor: pointer;
            display: inline-flex;
            font: inherit;
            height: 32px;
            justify-content: center;
            padding: 0;
            text-transform: none;
            width: 32px;
        }

        .password-toggle:hover,
        .password-toggle:focus {
            background: transparent;
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
            margin-top: 6px;
        }

        button {
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
            text-transform: uppercase;
            width: 100%;
        }

        button:hover {
            background: #0b141e;
            color: #fff;
        }

        .logout {
            background: transparent;
            border-color: var(--line);
            color: #fff;
        }

        @media (max-width: 520px) {
            body { padding: 16px; }
            .password-card { padding: 28px 22px; }
            h1 { font-size: 24px; }
        }
    </style>
</head>
<body>
    <main class="password-card" aria-labelledby="change-password-title">
        <header>
            <div class="eyebrow">Password required</div>
            <h1 id="change-password-title">Change your temporary password</h1>
            <p>Your account was created with a temporary password. Set a private password before entering the workspace.</p>
        </header>

        <form method="POST" action="{{ route('password.update') }}">
            @csrf

            @if($errors->any())
                <div class="error-box">{{ $errors->first() }}</div>
            @endif

            <div class="field">
                <label for="current_password">Current Password</label>
                <div class="input-wrap">
                    <i class="fa-solid fa-key"></i>
                    <input id="current_password" name="current_password" type="password" autocomplete="current-password" required>
                    <button class="password-toggle" type="button" data-target="current_password" aria-label="Show current password" aria-pressed="false">
                        <i class="fa-solid fa-eye" aria-hidden="true"></i>
                    </button>
                </div>
            </div>

            <div class="field">
                <label for="password">New Password</label>
                <div class="input-wrap">
                    <i class="fa-solid fa-lock"></i>
                    <input id="password" name="password" type="password" autocomplete="new-password" required>
                    <button class="password-toggle" type="button" data-target="password" aria-label="Show new password" aria-pressed="false">
                        <i class="fa-solid fa-eye" aria-hidden="true"></i>
                    </button>
                </div>
            </div>

            <div class="field">
                <label for="password_confirmation">Confirm New Password</label>
                <div class="input-wrap">
                    <i class="fa-solid fa-shield-halved"></i>
                    <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required>
                    <button class="password-toggle" type="button" data-target="password_confirmation" aria-label="Show password confirmation" aria-pressed="false">
                        <i class="fa-solid fa-eye" aria-hidden="true"></i>
                    </button>
                </div>
            </div>

            <div class="note">Use at least 10 characters with uppercase, lowercase, number, and symbol.</div>

            <div class="actions">
                <button type="submit"><i class="fa-solid fa-check"></i> Change Password</button>
            </div>
        </form>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button class="logout" type="submit"><i class="fa-solid fa-arrow-right-from-bracket"></i> Logout</button>
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
