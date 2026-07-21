<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
    <title>Texaro Technologies Limited</title>
    <meta name="description" content="Texaro brings accounting, inventory, sales, purchases, customers, suppliers, POS, and reporting into one business control workspace.">

    <link rel="manifest" href="/manifest.json">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <link rel="apple-touch-icon" href="/icons/icon-192.png">
    <meta name="theme-color" content="#211b85">

    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">

    <style>
        :root {
            --navy: #211b85;
            --navy-deep: #120f58;
            --cyan: #35c6ee;
            --ink: #172033;
            --muted: #667085;
            --line: #e3e8f0;
        }

        * {
            box-sizing: border-box;
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            color: var(--ink);
            font-family: "Manrope", system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            letter-spacing: 0;
            margin: 0;
        }

        .square-shadow {
            box-shadow: 0 28px 60px rgba(18, 15, 88, 0.16);
        }

        .hero-shell {
            background-color: var(--navy);
            background-image:
                linear-gradient(rgba(255,255,255,0.045) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255,255,255,0.045) 1px, transparent 1px),
                radial-gradient(circle at 74% 30%, rgba(53,198,238,0.34) 0%, rgba(53,198,238,0) 30%),
                radial-gradient(circle at 92% 72%, rgba(210,31,60,0.20) 0%, rgba(210,31,60,0) 24%),
                linear-gradient(135deg, rgba(18, 15, 88, 0.98) 0%, rgba(33, 27, 133, 0.96) 58%, rgba(25, 93, 179, 0.92) 100%);
            background-position: top left, top left, center, center, center;
            background-size: 40px 40px, 40px 40px, cover, cover, cover;
            min-height: 430px;
            overflow: hidden;
            position: relative;
        }

        .hero-shell::before {
            border: 1px solid rgba(255,255,255,0.18);
            content: "";
            height: 104px;
            pointer-events: none;
            position: absolute;
            right: 14%;
            top: 34%;
            transform: rotate(-28deg);
            width: 360px;
        }

        .hero-shell::after {
            background: var(--cyan);
            content: "";
            height: 10px;
            left: 48px;
            pointer-events: none;
            position: absolute;
            top: 62%;
            width: 54px;
        }

        .hero-inner {
            align-items: center;
            display: grid;
            isolation: isolate;
            min-height: 430px;
            position: relative;
            z-index: 1;
        }

        .hero-copy {
            max-width: 760px;
            padding-top: 58px;
        }

        .hero-eyebrow {
            color: #64daf5;
            display: inline-flex;
            font-size: 12px;
            font-weight: 800;
            letter-spacing: 0.34em;
            margin-bottom: 16px;
            text-transform: uppercase;
        }

        .hero-title {
            color: #ffffff;
            font-size: clamp(36px, 4.2vw, 58px);
            font-weight: 800;
            line-height: 0.98;
            margin: 0;
            max-width: 760px;
        }

        .hero-text {
            color: rgba(255,255,255,0.86);
            font-size: 17px;
            font-weight: 600;
            line-height: 1.62;
            margin: 18px 0 0;
            max-width: 660px;
        }

        .hero-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 14px;
            margin-top: 26px;
        }

        .hero-button {
            align-items: center;
            display: inline-flex;
            font-size: 14px;
            font-weight: 800;
            justify-content: center;
            min-width: 188px;
            padding: 15px 26px;
            text-align: center;
            text-decoration: none;
        }

        .btn-cyan {
            background: var(--cyan);
            border: 1px solid var(--cyan);
            color: #0f1554;
        }

        .btn-cyan:hover {
            background: #60d9f5;
        }

        .btn-outline-light {
            border: 1px solid rgba(255,255,255,0.78);
            color: #ffffff;
        }

        .btn-outline-light:hover {
            background: rgba(255,255,255,0.08);
        }

        .service-card {
            border: 1px solid var(--line);
            background: #ffffff;
            transition: transform 180ms ease, box-shadow 180ms ease;
        }

        .service-card:hover {
            box-shadow: 0 24px 50px rgba(33, 27, 133, 0.16);
            transform: translateY(-6px);
        }

        .icon-box {
            background: linear-gradient(135deg, #211b85, #35c6ee);
            color: #ffffff;
            height: 54px;
            width: 54px;
        }

        .lower-visual {
            background:
                linear-gradient(90deg, rgba(248,250,252,0.95), rgba(248,250,252,0.72)),
                url("{{ asset('assets/hero-business-tech.png') }}");
            background-position: 65% center;
            background-size: cover;
        }

        .site-header {
            left: 0;
            position: absolute;
            right: 0;
            top: 0;
            z-index: 50;
        }

        .site-header-inner {
            align-items: center;
            display: flex;
            justify-content: space-between;
            margin: 0 auto;
            max-width: 1280px;
            padding: 30px 32px;
        }

        .site-logo {
            background: #ffffff;
            height: 70px;
            object-fit: contain;
            width: 70px;
        }

        .site-nav {
            align-items: center;
            display: flex;
            gap: 34px;
        }

        .site-nav a,
        .site-actions a {
            color: #ffffff;
            font-size: 14px;
            font-weight: 800;
            text-decoration: none;
        }

        .site-nav a:hover {
            color: #b8f1ff;
        }

        .site-actions {
            align-items: center;
            display: flex;
            gap: 12px;
        }

        .header-login {
            border: 1px solid rgba(255,255,255,0.55);
            padding: 14px 22px;
        }

        .header-login:hover {
            background: rgba(255,255,255,0.1);
        }

        .header-cta {
            background: var(--cyan);
            color: #10175a !important;
            padding: 15px 28px;
        }

        .mobile-trigger {
            display: none;
        }

        .mobile-menu {
            display: none;
        }

        @media (max-width: 1024px) {
            .site-nav,
            .site-actions {
                display: none;
            }

            .mobile-trigger {
                background: transparent;
                border: 1px solid rgba(255,255,255,0.6);
                color: #ffffff;
                display: inline-flex;
                padding: 12px 14px;
            }

            .mobile-menu {
                background: #120f58;
                border: 1px solid rgba(255,255,255,0.18);
                margin: 0 24px;
                padding: 14px;
            }

            .mobile-menu:not(.hidden) {
                display: grid;
                gap: 10px;
            }

            .mobile-menu a {
                border: 1px solid rgba(255,255,255,0.18);
                color: #ffffff;
                font-size: 14px;
                font-weight: 800;
                padding: 13px 14px;
                text-decoration: none;
            }
        }

        @media (max-width: 900px) {
            .hero-shell {
                background-image:
                    linear-gradient(rgba(255,255,255,0.04) 1px, transparent 1px),
                    linear-gradient(90deg, rgba(255,255,255,0.04) 1px, transparent 1px),
                    radial-gradient(circle at 78% 28%, rgba(53,198,238,0.30) 0%, rgba(53,198,238,0) 34%),
                    linear-gradient(135deg, rgba(18, 15, 88, 0.98) 0%, rgba(33, 27, 133, 0.96) 60%, rgba(25, 93, 179, 0.92) 100%);
                background-position: top left, top left, center, center;
                background-size: 40px 40px, 40px 40px, cover, cover;
                min-height: 500px;
            }

            .hero-inner {
                align-items: start;
                min-height: 500px;
                padding-top: 126px;
            }

        }

        @media (max-width: 640px) {
            .site-header-inner {
                padding: 22px 20px;
            }

            .site-logo {
                height: 58px;
                width: 58px;
            }

            .hero-copy {
                padding-top: 16px;
            }

            .hero-title {
                font-size: 36px;
            }

            .hero-text {
                font-size: 16px;
            }

            .hero-button {
                width: 100%;
            }
        }
    </style>
</head>
<body class="bg-white">
    <header class="site-header">
        <div class="site-header-inner">
            <a href="{{ url('/') }}" aria-label="Texaro home">
                <img src="{{ asset('assets/texaro-logo.png') }}" alt="Texaro logo" class="site-logo">
            </a>

            <nav class="site-nav">
                <a href="#platform">Home</a>
                <a href="#about">About us</a>
                <a href="#services">Services</a>
                <a href="#pricing">Pricing</a>
                <a href="mailto:sales@onyxtechpay.com">Contact us</a>
            </nav>

            <div class="site-actions">
                <a href="{{ route('login') }}" class="header-login">Login</a>
                <a href="mailto:sales@onyxtechpay.com" class="header-cta">Contact Sales</a>
            </div>

            <button class="mobile-trigger" type="button" onclick="document.getElementById('mobile-menu').classList.toggle('hidden')" aria-label="Open menu">
                <i class="fa-solid fa-bars"></i>
            </button>
        </div>

        <div id="mobile-menu" class="mobile-menu hidden">
            <a href="#platform">Home</a>
            <a href="#about">About us</a>
            <a href="#services">Services</a>
            <a href="#pricing">Pricing</a>
            <a href="{{ route('login') }}">Login</a>
            <a href="mailto:sales@onyxtechpay.com">Contact Sales</a>
        </div>
    </header>

    <main>
        <section class="hero-shell" id="platform">
            <div class="hero-inner mx-auto max-w-7xl px-5 lg:px-8">
                <div class="hero-copy">
                    <p class="hero-eyebrow">Welcome to Texaro</p>
                    <h1 class="hero-title">
                        We solve business problems with one operating workspace.
                    </h1>
                    <p class="hero-text">
                        Accounting, inventory, sales, purchases, POS, customers, suppliers, and reports work together so your team can run faster with cleaner numbers.
                    </p>

                    <div class="hero-actions">
                        <a href="mailto:sales@onyxtechpay.com" class="hero-button btn-cyan">
                            Contact Sales
                        </a>
                        <a href="#services" class="hero-button btn-outline-light">
                            View Services
                        </a>
                    </div>

                </div>
            </div>
        </section>

        <section class="-mt-24 px-5 lg:px-8" id="services">
            <div class="mx-auto grid max-w-7xl gap-0 md:grid-cols-3">
                @foreach ([
                    ['icon' => 'fa-code', 'title' => 'Business System', 'copy' => 'One workspace for accounting, sales, inventory, POS, and operational reporting.'],
                    ['icon' => 'fa-cloud', 'title' => 'Cloud Workspace', 'copy' => 'Tenant-ready records, secure access, and clean company separation for growing teams.'],
                    ['icon' => 'fa-shield-halved', 'title' => 'Financial Control', 'copy' => 'Invoices, payments, supplier balances, stock valuation, and performance signals.'],
                ] as $index => $service)
                    <div class="service-card square-shadow {{ $index === 1 ? 'relative z-10 md:-mt-8' : '' }} p-9 text-center">
                        <div class="icon-box mx-auto flex items-center justify-center text-xl">
                            <i class="fa-solid {{ $service['icon'] }}"></i>
                        </div>
                        <h2 class="mt-6 text-lg font-extrabold text-slate-900">{{ $service['title'] }}</h2>
                        <p class="mx-auto mt-4 max-w-xs text-sm font-medium leading-7 text-slate-500">{{ $service['copy'] }}</p>
                        <a href="#services" class="mt-7 inline-flex bg-[#211b85] px-6 py-3 text-sm font-extrabold text-white hover:bg-[#120f58]">
                            Learn more
                        </a>
                    </div>
                @endforeach
            </div>
        </section>

        <section class="bg-white px-5 py-20 lg:px-8">
            <div class="mx-auto max-w-7xl text-center">
                <h2 class="text-2xl font-extrabold text-slate-800">Trusted modules for serious business operators</h2>
                <div class="mt-9 grid gap-5 text-xs font-extrabold uppercase tracking-[0.22em] text-slate-400 sm:grid-cols-2 lg:grid-cols-6">
                    <div class="border border-slate-200 px-4 py-4"><i class="fa-solid fa-file-invoice mr-2"></i>Invoices</div>
                    <div class="border border-slate-200 px-4 py-4"><i class="fa-solid fa-boxes-stacked mr-2"></i>Inventory</div>
                    <div class="border border-slate-200 px-4 py-4"><i class="fa-solid fa-cash-register mr-2"></i>POS</div>
                    <div class="border border-slate-200 px-4 py-4"><i class="fa-solid fa-users mr-2"></i>CRM</div>
                    <div class="border border-slate-200 px-4 py-4"><i class="fa-solid fa-truck mr-2"></i>Suppliers</div>
                    <div class="border border-slate-200 px-4 py-4"><i class="fa-solid fa-chart-line mr-2"></i>Reports</div>
                </div>
            </div>
        </section>

        <section class="lower-visual border-y border-slate-200 px-5 py-20 lg:px-8" id="about">
            <div class="mx-auto grid max-w-7xl items-center gap-12 lg:grid-cols-[0.82fr_1fr]">
                <div class="bg-white p-8 square-shadow">
                    <h2 class="text-4xl font-extrabold leading-tight text-slate-900 md:text-5xl">
                        Let Texaro handle the workflow, so you can focus on what matters.
                    </h2>
                    <p class="mt-6 text-base font-medium leading-8 text-slate-600">
                        Replace disconnected spreadsheets and manual follow-ups with one connected system for quotes, sales, purchases, inventory, customer records, supplier balances, and reporting.
                    </p>
                    <a href="#services" class="mt-8 inline-flex bg-[#211b85] px-7 py-4 text-sm font-extrabold text-white hover:bg-[#120f58]">
                        Learn more
                    </a>
                </div>

                <div class="grid gap-5">
                    @foreach ([
                        ['Remote Business Office', 'Monitor sales, stock, and reports from one workspace.'],
                        ['Virtual Operations Desk', 'Keep customers, suppliers, and payments connected.'],
                        ['Terminal Server Ready', 'Run structured workflows for multi-user teams.'],
                    ] as $item)
                        <div class="ml-auto max-w-md border border-slate-200 bg-white p-5 shadow-lg">
                            <div class="flex gap-4">
                                <div class="flex h-11 w-11 shrink-0 items-center justify-center border border-cyan-300 text-cyan-500">
                                    <i class="fa-solid fa-check"></i>
                                </div>
                                <div>
                                    <h3 class="font-extrabold text-slate-900">{{ $item[0] }}</h3>
                                    <p class="mt-1 text-sm font-medium leading-6 text-slate-500">{{ $item[1] }}</p>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        <section class="bg-white px-5 py-20 lg:px-8" id="pricing">
            <div class="mx-auto max-w-7xl">
                <div class="mx-auto max-w-3xl text-center">
                    <p class="text-xs font-extrabold uppercase tracking-[0.28em] text-[#211b85]">Simple pricing</p>
                    <h2 class="mt-4 text-4xl font-extrabold text-slate-900 md:text-5xl">Start lean. Scale with control.</h2>
                    <p class="mt-5 text-base font-medium leading-8 text-slate-600">Every account starts with the core workspace and expands into a complete business operating system.</p>
                </div>

                <div class="mx-auto mt-12 grid max-w-5xl gap-6 md:grid-cols-2">
                    <div class="border border-slate-200 bg-white p-8 square-shadow">
                        <h3 class="text-2xl font-extrabold text-slate-900">Growth</h3>
                        <p class="mt-2 text-sm font-semibold text-slate-500">For owners and growing businesses</p>
                        <div class="mt-7 flex items-end gap-2">
                            <span class="text-5xl font-extrabold text-slate-900">100k</span>
                            <span class="pb-2 text-sm font-bold text-slate-500">UGX / month</span>
                        </div>
                        <ul class="mt-7 grid gap-3 text-sm font-semibold text-slate-700">
                            <li><i class="fa-solid fa-check mr-2 text-cyan-500"></i>Invoices, estimates, and payments</li>
                            <li><i class="fa-solid fa-check mr-2 text-cyan-500"></i>Inventory and product catalog</li>
                            <li><i class="fa-solid fa-check mr-2 text-cyan-500"></i>Customer and supplier records</li>
                            <li><i class="fa-solid fa-check mr-2 text-cyan-500"></i>Dashboard and reports</li>
                        </ul>
                        <a href="mailto:sales@onyxtechpay.com" class="mt-8 block bg-[#211b85] px-6 py-4 text-center text-sm font-extrabold text-white hover:bg-[#120f58]">Contact Sales</a>
                    </div>

                    <div class="border border-[#211b85] bg-[#211b85] p-8 text-white square-shadow">
                        <h3 class="text-2xl font-extrabold">Enterprise</h3>
                        <p class="mt-2 text-sm font-semibold text-white/70">For complex teams and larger operations</p>
                        <div class="mt-7 text-5xl font-extrabold">Custom</div>
                        <ul class="mt-7 grid gap-3 text-sm font-semibold text-white/90">
                            <li><i class="fa-solid fa-check mr-2 text-cyan-300"></i>Everything in Growth</li>
                            <li><i class="fa-solid fa-check mr-2 text-cyan-300"></i>Advanced roles and permissions</li>
                            <li><i class="fa-solid fa-check mr-2 text-cyan-300"></i>Data exports and setup support</li>
                            <li><i class="fa-solid fa-check mr-2 text-cyan-300"></i>Priority workspace configuration</li>
                        </ul>
                        <a href="mailto:sales@onyxtechpay.com" class="mt-8 block border border-white bg-white px-6 py-4 text-center text-sm font-extrabold text-[#211b85] hover:bg-cyan-50">Contact Sales</a>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <footer class="bg-[#120f58] px-5 py-12 text-white/70 lg:px-8">
        <div class="mx-auto grid max-w-7xl gap-10 border-b border-white/10 pb-10 md:grid-cols-[1.2fr_0.8fr_0.8fr]">
            <div>
                <img src="{{ asset('assets/texaro-logo.png') }}" alt="Texaro logo" class="h-14 w-14 bg-white object-contain">
                <p class="mt-5 max-w-sm text-sm font-medium leading-7">
                    Multi-tenant business management and accounting workspace developed by Texaro Technologies Limited.
                </p>
            </div>
            <div>
                <p class="font-extrabold uppercase tracking-wide text-white">Platform</p>
                <ul class="mt-4 grid gap-3 text-sm font-semibold">
                    <li><a href="#services" class="hover:text-white">Services</a></li>
                    <li><a href="#about" class="hover:text-white">About us</a></li>
                    <li><a href="#pricing" class="hover:text-white">Pricing</a></li>
                    <li><a href="{{ route('login') }}" class="hover:text-white">Login Workspace</a></li>
                </ul>
            </div>
            <div>
                <p class="font-extrabold uppercase tracking-wide text-white">Contact</p>
                <ul class="mt-4 grid gap-3 text-sm font-semibold">
                    <li><a href="mailto:sales@onyxtechpay.com" class="hover:text-white">sales@onyxtechpay.com</a></li>
                    <li><a href="mailto:support@onyxtechpay.com" class="hover:text-white">support@onyxtechpay.com</a></li>
                    <li><a href="{{ route('login') }}" class="hover:text-white">Login</a></li>
                </ul>
            </div>
        </div>
        <div class="mx-auto mt-6 flex max-w-7xl flex-col justify-between gap-4 text-sm font-semibold md:flex-row">
            <p>&copy; 2026 Texaro Technologies Limited. All rights reserved.</p>
            <div class="flex gap-6">
                <a href="mailto:support@onyxtechpay.com?subject=Privacy%20Policy" class="hover:text-white">Privacy Policy</a>
                <a href="mailto:support@onyxtechpay.com?subject=Terms%20of%20Service" class="hover:text-white">Terms of Service</a>
            </div>
        </div>
    </footer>
@include('layouts.design-lock')
</body>
</html>
