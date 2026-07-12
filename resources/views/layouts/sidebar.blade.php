@php
    use Illuminate\Support\Facades\Auth;
    use Illuminate\Support\Facades\Request;

    $user = Auth::user();

    $navGroups = [
        [
            'label' => 'Dashboard',
            'icon' => 'fa-gauge-high',
            'items' => [
                ['label' => 'Dashboard', 'icon' => 'fa-chart-pie', 'url' => url('dashboard'), 'patterns' => ['dashboard*']],
            ],
        ],
        [
            'label' => 'Sales',
            'icon' => 'fa-cash-register',
            'items' => [
                ['label' => 'POS', 'icon' => 'fa-store', 'url' => url('pos'), 'patterns' => ['pos*']],
                ['label' => 'Sales', 'icon' => 'fa-receipt', 'url' => url('sales'), 'patterns' => ['sales*', 'sales_action*']],
                ['label' => 'Customers', 'icon' => 'fa-users', 'url' => url('customers'), 'patterns' => ['customers*', 'customers_action*']],
                ['label' => 'CRM', 'icon' => 'fa-handshake', 'url' => url('crm'), 'patterns' => ['crm*']],
            ],
        ],
        [
            'label' => 'Inventory',
            'icon' => 'fa-boxes-stacked',
            'items' => [
                ['label' => 'Products', 'icon' => 'fa-box', 'url' => url('products'), 'patterns' => ['products*', 'products_action*']],
                ['label' => 'Inventory', 'icon' => 'fa-warehouse', 'url' => url('inventory'), 'patterns' => ['inventory*']],
                ['label' => 'Purchases', 'icon' => 'fa-cart-shopping', 'url' => url('purchases'), 'patterns' => ['purchases*']],
                ['label' => 'Suppliers', 'icon' => 'fa-truck', 'url' => url('suppliers'), 'patterns' => ['suppliers*', 'suppliers_action*']],
            ],
        ],
        [
            'label' => 'Finance',
            'icon' => 'fa-scale-balanced',
            'items' => [
                ['label' => 'Accounting', 'icon' => 'fa-calculator', 'url' => url('accounting'), 'patterns' => ['accounting*']],
                ['label' => 'Banking', 'icon' => 'fa-building-columns', 'url' => url('banking'), 'patterns' => ['banking*']],
                ['label' => 'Payroll', 'icon' => 'fa-money-check-dollar', 'url' => url('payroll'), 'patterns' => ['payroll*']],
                ['label' => 'Budgets', 'icon' => 'fa-chart-line', 'url' => url('budgets'), 'patterns' => ['budgets*']],
            ],
        ],
        [
            'label' => 'Operations',
            'icon' => 'fa-briefcase',
            'items' => [
                ['label' => 'Assets', 'icon' => 'fa-laptop-file', 'url' => url('assets.php'), 'patterns' => ['assets*']],
                ['label' => 'Mobile App', 'icon' => 'fa-mobile-screen-button', 'url' => url('mobile_app'), 'patterns' => ['mobile_app*']],
                ['label' => 'Notifications', 'icon' => 'fa-bell', 'url' => url('notifications'), 'patterns' => ['notifications*']],
            ],
        ],
        [
            'label' => 'Reports',
            'icon' => 'fa-file-lines',
            'items' => [
                ['label' => 'Reports', 'icon' => 'fa-chart-column', 'url' => url('reports'), 'patterns' => ['reports*']],
            ],
        ],
        [
            'label' => 'Administration',
            'icon' => 'fa-sliders',
            'items' => [
                ['label' => 'Settings', 'icon' => 'fa-gear', 'url' => url('settings'), 'patterns' => ['settings*']],
                ['label' => 'Users & Roles', 'icon' => 'fa-user-shield', 'url' => url('settings') . '#users_roles', 'patterns' => ['settings*']],
            ],
        ],
    ];

    $isItemActive = function (array $item): bool {
        foreach ($item['patterns'] as $pattern) {
            if (Request::is($pattern)) {
                return true;
            }
        }

        return false;
    };
@endphp

<aside class="sidebar" aria-label="Primary navigation">
    <div class="sidebar-brand">
        <a class="sidebar-brand-mark" href="{{ url('dashboard') }}" aria-label="Onyx dashboard">
            <img src="{{ asset('assets/onxy logo.jpeg') }}" alt="Onyx logo">
        </a>
        <div class="sidebar-brand-copy">
            <strong>Onyx BCS</strong>
            <span>Business workspace</span>
        </div>
    </div>

    <nav class="sidebar-nav" aria-label="ERP modules">
        @foreach ($navGroups as $groupIndex => $group)
            @php
                $groupId = 'sidebar-group-' . $groupIndex;
                $groupActive = collect($group['items'])->contains(fn ($item) => $isItemActive($item));
            @endphp

            <section class="sidebar-group" data-sidebar-group>
                @if ($group['label'] === 'Dashboard')
                    @php $dashboardItem = $group['items'][0]; @endphp
                    <a
                        href="{{ $dashboardItem['url'] }}"
                        class="sidebar-link sidebar-direct-link {{ $groupActive ? 'active' : '' }}"
                        data-sidebar-link
                    >
                        <i class="fa-solid {{ $group['icon'] }}"></i>
                        <span>{{ $group['label'] }}</span>
                    </a>
                @else
                    <button
                        class="sidebar-group-toggle {{ $groupActive ? 'active' : '' }}"
                        type="button"
                        data-toggle="{{ $groupId }}"
                        aria-controls="{{ $groupId }}"
                        aria-expanded="{{ $groupActive ? 'true' : 'false' }}"
                    >
                        <span class="sidebar-group-label">
                            <i class="fa-solid {{ $group['icon'] }}"></i>
                            <span>{{ $group['label'] }}</span>
                        </span>
                        <i class="fa-solid fa-chevron-down sidebar-chevron"></i>
                    </button>

                    <div id="{{ $groupId }}" class="sidebar-submenu {{ $groupActive ? 'show' : '' }}">
                        @foreach ($group['items'] as $item)
                            <a
                                href="{{ $item['url'] }}"
                                class="sidebar-link {{ $isItemActive($item) ? 'active' : '' }}"
                                data-sidebar-link
                            >
                                <i class="fa-solid {{ $item['icon'] }}"></i>
                                <span>{{ $item['label'] }}</span>
                            </a>
                        @endforeach
                    </div>
                @endif
            </section>
        @endforeach
    </nav>

    <div class="sidebar-footer">
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button class="sidebar-logout" type="submit">
                <i class="fa-solid fa-arrow-right-from-bracket"></i>
                <span>Logout</span>
            </button>
        </form>
    </div>
</aside>

<script>
    (function() {
        const sidebar = document.querySelector('.sidebar');
        if (!sidebar) return;

        sidebar.querySelectorAll('.sidebar-group-toggle').forEach(button => {
            button.addEventListener('click', function() {
                const targetId = this.getAttribute('data-toggle');
                const submenu = sidebar.querySelector('#' + targetId);
                const expanded = this.getAttribute('aria-expanded') === 'true';

                this.classList.toggle('active', !expanded);
                this.setAttribute('aria-expanded', String(!expanded));

                if (submenu) {
                    submenu.classList.toggle('show', !expanded);
                }
            });
        });

        const searchInput = document.querySelector('#topbar-module-search');
        if (searchInput) {
            searchInput.addEventListener('input', function() {
                const query = this.value.trim().toLowerCase();

                sidebar.querySelectorAll('[data-sidebar-group]').forEach(group => {
                    let hasMatch = false;

                    group.querySelectorAll('[data-sidebar-link]').forEach(link => {
                        const matches = link.textContent.toLowerCase().includes(query);
                        link.hidden = query.length > 0 && !matches;
                        hasMatch = hasMatch || matches;
                    });

                    group.hidden = query.length > 0 && !hasMatch;
                    const submenu = group.querySelector('.sidebar-submenu');
                    const toggle = group.querySelector('.sidebar-group-toggle');
                    if (query.length > 0 && hasMatch) {
                        submenu?.classList.add('show');
                        toggle?.classList.add('active');
                        toggle?.setAttribute('aria-expanded', 'true');
                    }
                });
            });
        }
    })();
</script>
