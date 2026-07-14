@include('layouts.header')

<main>
    <div class="app-topbar">
        <div class="topbar-left">
            <button class="topbar-sidebar-toggle" type="button" data-sidebar-collapse aria-label="Toggle sidebar" aria-pressed="false" title="Toggle sidebar">
                <i class="fa-solid fa-chevron-left"></i>
            </button>
            <a class="topbar-brand" href="{{ url('dashboard') }}" aria-label="Onyx dashboard">
                <img src="{{ asset('assets/onxy logo.jpeg') }}" alt="Onyx logo">
                <span>
                    <strong>Onyx BCS</strong>
                    <small>{{ config('app.name', 'Onyx Hub') }}</small>
                </span>
            </a>
            <label class="topbar-search" for="topbar-module-search">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input id="topbar-module-search" type="search" placeholder="Search modules..." autocomplete="off">
            </label>
        </div>
        <div class="topbar-right">
            <a class="topbar-action" href="{{ url('pos') }}" style="background: transparent !important; color: #ffffff !important;">
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
                    <span>{{ auth()->user()->email ?? 'admin@onyx.local' }}</span>
                </div>
            </div>
        </div>
    </div>

    <div class="app-layout">
        @yield('content')
    </div>
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
    })();
</script>

@include('layouts.footer')
