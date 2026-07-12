<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
    <title>Login | Onyx Business Control System</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --page: #11111a;
            --card: #1b1b25;
            --card-deep: #15151e;
            --field-dark: #11111a;
            --field-light: #edf3ff;
            --line: rgba(255, 255, 255, 0.09);
            --line-strong: rgba(255, 255, 255, 0.16);
            --text: #f7f8ff;
            --muted: #a7a3ad;
            --soft: #d9dcec;
            --accent: #ff6512;
            --accent-deep: #e85305;
            --danger: #ff7171;
        }

        * {
            box-sizing: border-box;
        }

        html,
        body {
            min-height: 100%;
        }

        body {
            align-items: center;
            background:
                radial-gradient(circle at 18% 12%, rgba(255, 255, 255, 0.035), transparent 26%),
                linear-gradient(180deg, #11111a 0%, #0f0f17 100%);
            color: var(--text);
            display: flex;
            font-family: "Inter", system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            justify-content: center;
            letter-spacing: 0;
            margin: 0;
            padding: 0;
        }

        .login-panel {
            align-items: center;
            display: flex;
            justify-content: center;
            min-height: 100vh;
            padding: 40px 22px;
            position: relative;
            width: 100%;
        }

        .back-link {
            align-items: center;
            background: rgba(255, 255, 255, 0.035);
            border: 1px solid var(--line);
            border-radius: 8px;
            color: var(--muted);
            display: inline-flex;
            font-size: 0.76rem;
            font-weight: 700;
            gap: 7px;
            left: 22px;
            min-height: 32px;
            padding: 0 12px;
            position: absolute;
            text-decoration: none;
            top: 22px;
            z-index: 2;
        }

        .back-link span {
            font-size: 1.08rem;
            line-height: 1;
        }

        .login-content {
            align-items: center;
            background:
                linear-gradient(180deg, rgba(255, 255, 255, 0.018), transparent 36%),
                var(--card);
            border: 1px solid var(--line-strong);
            border-radius: 8px;
            box-shadow: 0 28px 80px rgba(0, 0, 0, 0.42);
            display: flex;
            flex-direction: column;
            max-width: 380px;
            padding: 38px 36px 40px;
            position: relative;
            text-align: center;
            width: 100%;
            z-index: 1;
        }

        .brand-plate {
            align-items: center;
            background: transparent;
            border: 0;
            border-radius: 0;
            box-shadow: none;
            display: flex;
            height: 44px;
            justify-content: center;
            margin-bottom: 10px;
            padding: 0;
            position: relative;
            width: 120px;
        }

        .brand-plate img {
            border-radius: 0;
            height: 100%;
            object-fit: contain;
            width: 100%;
        }

        .brand-kicker {
            color: var(--text);
            font-size: 1.6rem;
            font-weight: 800;
            letter-spacing: 0;
            line-height: 1;
            margin: 0;
            text-transform: none;
        }

        .brand-kicker span {
            color: var(--accent);
        }

        .login-title {
            color: rgba(255, 255, 255, 0.12);
            font-size: 0.76rem;
            font-weight: 700;
            line-height: 1.4;
            margin: 12px 0 30px;
        }

        .login-copy {
            color: var(--muted);
            font-size: 0.8rem;
            font-weight: 700;
            margin: 22px 0 0;
            text-align: right;
            width: 100%;
        }

        .login-copy a {
            color: #d7a94f;
            font-weight: 800;
            text-decoration: none;
        }

        .login-form {
            max-width: 304px;
            width: 100%;
        }

        .error-box {
            background: rgba(255, 113, 113, 0.1);
            border: 1px solid rgba(255, 113, 113, 0.28);
            border-radius: 6px;
            color: #ffb3b3;
            font-size: 0.76rem;
            font-weight: 700;
            margin-bottom: 16px;
            padding: 10px 12px;
            text-align: left;
        }

        .field-group {
            margin-bottom: 16px;
            text-align: left;
        }

        .field-group label {
            color: var(--muted);
            display: block;
            font-size: 0.78rem;
            font-weight: 600;
            margin: 0 0 8px;
        }

        .input {
            background: var(--field-light);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 5px;
            color: #10121b;
            display: block;
            font: inherit;
            font-size: 0.86rem;
            height: 34px;
            padding: 0 13px;
            transition: border-color 160ms ease, box-shadow 160ms ease, background 160ms ease;
            width: 100%;
        }

        .input.workspace {
            background: var(--field-dark);
            border-color: rgba(255, 255, 255, 0.08);
            color: #f4f4f8;
        }

        .input::placeholder {
            color: rgba(20, 20, 28, 0.35);
        }

        .input.workspace::placeholder {
            color: rgba(255, 255, 255, 0.08);
        }

        .input:focus {
            border-color: rgba(255, 101, 18, 0.78);
            box-shadow: 0 0 0 3px rgba(255, 101, 18, 0.16);
            outline: none;
        }

        .primary-button,
        .secondary-button {
            align-items: center;
            border-radius: 5px;
            cursor: pointer;
            display: inline-flex;
            font: inherit;
            font-size: 0.8rem;
            font-weight: 800;
            height: 36px;
            justify-content: center;
            text-decoration: none;
            transition: transform 160ms ease, filter 160ms ease, border-color 160ms ease;
            width: 100%;
        }

        .primary-button {
            background: var(--accent);
            border: 0;
            box-shadow: 0 12px 26px rgba(255, 101, 18, 0.18);
            color: #ffffff;
            margin-top: 6px;
        }

        .primary-button:hover,
        .secondary-button:hover {
            filter: brightness(1.04);
            transform: translateY(-1px);
        }

        .divider {
            background: rgba(255, 255, 255, 0.18);
            height: 1px;
            margin: 20px 0 0;
            max-width: 304px;
            width: 100%;
        }

        @media (max-width: 740px) {
            body {
                padding: 0;
            }

            .login-panel {
                border-radius: 0;
                min-height: 100vh;
                padding: 76px 18px 52px;
            }

            .login-content {
                padding: 34px 28px 36px;
            }

            .back-link {
                left: 18px;
                top: 18px;
            }
        }

        @media (max-width: 420px) {
            .brand-plate {
                width: 104px;
            }
        }
    </style>
</head>
<body>
    <main class="login-panel" aria-labelledby="login-title">
        <a class="back-link" href="{{ url('/') }}"><span>&lsaquo;</span> Back</a>

        <section class="login-content">
            <div class="brand-plate" aria-label="Onyx Technology Solutions Limited">
                <img src="{{ asset('assets/onxy logo.jpeg') }}" alt="">
            </div>

            <p class="brand-kicker">ONYX <span>ERP</span></p>
            <h1 class="login-title" id="login-title">Access your business workspace</h1>

            <form class="login-form" method="POST" action="{{ route('login') }}">
                @csrf

                @if($errors->any())
                    <div class="error-box">{{ $errors->first() }}</div>
                @endif

                <div class="field-group">
                    <label for="workspace">Company Workspace Handle</label>
                    <input id="workspace" name="workspace" type="text" class="input workspace" value="{{ old('workspace') }}" placeholder="e.g onyx-tech" autocomplete="organization">
                </div>

                <div class="field-group">
                    <label for="email">Enterprise Email Address</label>
                    <input id="email" name="email" type="email" class="input" value="{{ old('email') }}" placeholder="onyx21@mru.ac.ug" autocomplete="email" required autofocus>
                </div>

                <div class="field-group">
                    <label for="password">Secure Passphrase</label>
                    <input id="password" name="password" type="password" class="input" placeholder="....." autocomplete="current-password" required>
                </div>

                <button class="primary-button" type="submit">UNLOCK WORKSPACE</button>
            </form>

            <div class="divider"></div>
            <p class="login-copy">New workspace? <a href="{{ route('register') }}">Register Here</a></p>
        </section>
    </main>
</body>
</html>
