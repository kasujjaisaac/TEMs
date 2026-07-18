@include('layouts.header')

<main>
    <div class="mobile-appbar">
        <a class="mobile-brand" href="{{ url('dashboard') }}" aria-label="Texaro dashboard">
            <img src="{{ asset('assets/texaro-logo.png') }}" alt="">
            <span>
                <strong>Texaro Technologies Limited</strong>
                <small>{{ config('app.name', 'Texaro Technologies Limited') }}</small>
            </span>
        </a>
        <div class="mobile-appbar-actions">
            <a href="{{ url('notifications') }}" aria-label="Notifications"><i class="fa-solid fa-bell"></i></a>
            <button type="button" data-mobile-more aria-label="Open menu"><i class="fa-solid fa-grip"></i></button>
        </div>
    </div>

    <div class="mobile-more-panel" data-mobile-more-panel aria-hidden="true">
        <div class="mobile-more-head">
            <strong>Modules</strong>
            <button type="button" data-mobile-more-close aria-label="Close menu"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <div class="mobile-more-grid">
            <a href="{{ url('customers') }}"><i class="fa-solid fa-users"></i><span>Customers</span></a>
            <a href="{{ url('suppliers') }}"><i class="fa-solid fa-truck"></i><span>Suppliers</span></a>
            <a href="{{ url('purchases') }}"><i class="fa-solid fa-cart-shopping"></i><span>Purchases</span></a>
            <a href="{{ url('reports') }}"><i class="fa-solid fa-chart-column"></i><span>Reports</span></a>
            <a href="{{ url('settings') }}"><i class="fa-solid fa-sliders"></i><span>Settings</span></a>
            <a href="{{ route('settings.users') }}"><i class="fa-solid fa-users-gear"></i><span>Users</span></a>
            <a href="{{ url('document_templates') }}"><i class="fa-solid fa-file-lines"></i><span>Documents</span></a>
            <a href="{{ url('payroll') }}"><i class="fa-solid fa-money-check-dollar"></i><span>Payroll</span></a>
        </div>
    </div>

    <div class="app-topbar">
        <div class="topbar-left">
            <button class="topbar-sidebar-toggle" type="button" data-sidebar-collapse aria-label="Toggle sidebar" aria-pressed="false" title="Toggle sidebar">
                <i class="fa-solid fa-chevron-left"></i>
            </button>
            <a class="topbar-brand" href="{{ url('dashboard') }}" aria-label="Texaro dashboard">
                <img src="{{ asset('assets/texaro-logo.png') }}" alt="Texaro logo">
                <span>
                    <strong>Texaro Technologies Limited</strong>
                    <small>{{ config('app.name', 'Texaro Technologies Limited') }}</small>
                </span>
            </a>
            <label class="topbar-search" for="topbar-module-search">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input id="topbar-module-search" type="search" placeholder="Search modules..." autocomplete="off">
            </label>
        </div>
        <div class="topbar-right">
            <a class="topbar-action" href="{{ url('pos') }}">
                <i class="fa-solid fa-plus"></i>
                <span>New Sale</span>
            </a>
            <span class="topbar-chip">
                <i class="fa-solid fa-circle-check"></i>
                System Online
            </span>
            <span class="topbar-chip">
                <i class="fa-solid fa-calendar-day"></i>
                {{ now()->format('M d, Y') }}
            </span>
            <a class="topbar-icon" href="{{ url('notifications') }}" aria-label="Notifications">
                <i class="fa-solid fa-bell"></i>
                <span class="topbar-icon-dot"></span>
            </a>
            <div class="topbar-user">
                <div class="topbar-user-avatar">{{ strtoupper(substr(auth()->user()->name ?? 'A', 0, 1)) }}</div>
                <div>
                    <strong>{{ auth()->user()->name ?? 'Admin User' }}</strong>
                    <span>{{ auth()->user()->email ?? 'admin@texaro.local' }}</span>
                </div>
            </div>
        </div>
    </div>

    <div class="app-layout">
        @yield('content')
    </div>

    <nav class="mobile-bottom-nav" aria-label="Primary mobile navigation">
        <a href="{{ url('dashboard') }}" class="{{ request()->is('dashboard*') ? 'active' : '' }}"><i class="fa-solid fa-gauge-high"></i><span>Home</span></a>
        <a href="{{ url('sales') }}" class="{{ request()->is('sales*') || request()->is('pos*') ? 'active' : '' }}"><i class="fa-solid fa-cash-register"></i><span>Sales</span></a>
        <a href="{{ url('products') }}" class="{{ request()->is('products*') || request()->is('inventory*') ? 'active' : '' }}"><i class="fa-solid fa-boxes-stacked"></i><span>Stock</span></a>
        <a href="{{ url('accounting') }}" class="{{ request()->is('accounting*') || request()->is('banking*') ? 'active' : '' }}"><i class="fa-solid fa-scale-balanced"></i><span>Finance</span></a>
        <a href="{{ url('human_resources') }}" class="{{ request()->is('human_resources*') || request()->is('hr_*') ? 'active' : '' }}"><i class="fa-solid fa-users-gear"></i><span>HR</span></a>
        <button type="button" data-mobile-more><i class="fa-solid fa-ellipsis"></i><span>More</span></button>
    </nav>

    <a class="mobile-fab" href="{{ url('pos') }}" aria-label="New sale"><i class="fa-solid fa-plus"></i><span>Sale</span></a>
</main>

<script>
    (function () {
        const key = 'onyx_sidebar_collapsed';
        const root = document.body;
        const button = document.querySelector('[data-sidebar-collapse]');

        function sync(collapsed) {
            root.classList.toggle('sidebar-collapsed', collapsed);
            if (button) {
                button.setAttribute('aria-pressed', collapsed ? 'true' : 'false');
                button.setAttribute('aria-label', collapsed ? 'Open sidebar' : 'Collapse sidebar');
                button.setAttribute('title', collapsed ? 'Open sidebar' : 'Collapse sidebar');
                button.classList.toggle('active', collapsed);
            }
        }

        sync(localStorage.getItem(key) === '1');

        if (button) {
            button.addEventListener('click', function () {
                const collapsed = !root.classList.contains('sidebar-collapsed');
                localStorage.setItem(key, collapsed ? '1' : '0');
                sync(collapsed);
            });
        }

        function enhanceMobileTables() {
            document.querySelectorAll('table').forEach(function (table) {
                if (table.dataset.mobileEnhanced === '1') return;
                const headers = Array.from(table.querySelectorAll('thead th')).map(function (th) {
                    return th.textContent.trim();
                });
                table.querySelectorAll('tbody tr').forEach(function (row) {
                    Array.from(row.children).forEach(function (cell, index) {
                        if (headers[index]) cell.setAttribute('data-label', headers[index]);
                    });
                });
                table.dataset.mobileEnhanced = '1';
            });
        }

        const morePanel = document.querySelector('[data-mobile-more-panel]');
        document.querySelectorAll('[data-mobile-more]').forEach(function (trigger) {
            trigger.addEventListener('click', function () {
                if (!morePanel) return;
                morePanel.classList.add('open');
                morePanel.setAttribute('aria-hidden', 'false');
            });
        });
        document.querySelectorAll('[data-mobile-more-close]').forEach(function (trigger) {
            trigger.addEventListener('click', function () {
                if (!morePanel) return;
                morePanel.classList.remove('open');
                morePanel.setAttribute('aria-hidden', 'true');
            });
        });

        enhanceMobileTables();
    })();
</script>

@include('layouts.footer')
