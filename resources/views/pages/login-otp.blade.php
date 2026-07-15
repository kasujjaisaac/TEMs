<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
    <title>Login OTP | Onyx Business Control System</title>
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
            --success: #8ff0c3;
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

        .otp-card {
            background: linear-gradient(180deg, rgba(255,255,255,.035), transparent 34%), var(--panel);
            border: 1px solid var(--line-strong);
            box-shadow: 0 34px 90px rgba(0,0,0,.62);
            display: grid;
            gap: 16px;
            max-width: 380px;
            padding: 26px;
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
            font-size: 23px;
            font-weight: 900;
            line-height: 1.08;
            margin: 0 0 7px;
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
            gap: 11px;
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
            min-height: 40px;
            padding: 0 10px;
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
            font-size: 16px;
            font-weight: 900;
            height: 38px;
            min-width: 0;
            outline: 0;
            width: 100%;
        }

        .actions {
            align-items: center;
            display: grid;
            gap: 8px;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            margin-top: 2px;
        }

        button, a.button {
            align-items: center;
            background: var(--accent);
            border: 1px solid var(--accent);
            color: #050506;
            cursor: pointer;
            display: inline-flex;
            font: inherit;
            font-size: 10px;
            font-weight: 900;
            gap: 7px;
            height: 34px;
            justify-content: center;
            padding: 0 10px;
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
            .otp-card { padding: 22px 18px; }
            h1 { font-size: 22px; }
            .actions { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <main class="otp-card" aria-labelledby="otp-title">
        <header>
            <div class="eyebrow">Email verification</div>
            <h1 id="otp-title">Enter your login OTP</h1>
            <p>We sent a 6-digit code to {{ session('login_otp.email') }}. The code expires in 10 minutes.</p>
        </header>

        @if($errors->any())
            <div class="alert">{{ $errors->first() }}</div>
        @endif
        @if(session('success'))
            <div class="alert success">{{ session('success') }}</div>
        @endif
        @if(session('login_otp_test_code'))
            <div class="alert success">Local OTP: {{ session('login_otp_test_code') }}</div>
        @endif

        <form id="otp-verify-form" method="POST" action="{{ route('login.otp.verify') }}">
            @csrf
            <div class="field">
                <label for="otp">OTP Code</label>
                <div class="input-wrap">
                    <i class="fa-solid fa-shield-halved"></i>
                    <input id="otp" name="otp" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" autocomplete="one-time-code" required autofocus>
                </div>
            </div>
        </form>

        <form id="otp-resend-form" method="POST" action="{{ route('login.otp.resend') }}">
            @csrf
        </form>

        <div class="actions">
            <button form="otp-verify-form" type="submit"><i class="fa-solid fa-check"></i> Verify</button>
            <button form="otp-resend-form" class="secondary" type="submit"><i class="fa-solid fa-rotate-right"></i> Resend</button>
            <a class="button secondary" href="{{ route('login') }}"><i class="fa-solid fa-arrow-left"></i> Back</a>
        </div>
    </main>
</body>
</html>
