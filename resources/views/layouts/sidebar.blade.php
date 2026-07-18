@php
    use App\Support\Navigation;

    $navGroups = Navigation::visibleGroups(auth()->user());
@endphp

<aside class="sidebar" aria-label="Primary navigation">
    <div class="sidebar-brand">
        <a class="sidebar-brand-mark" href="{{ url('dashboard') }}" aria-label="Texaro dashboard">
            <img src="{{ asset('assets/texaro-logo.png') }}" alt="Texaro logo">
        </a>
        <div class="sidebar-brand-copy">
            <strong>Texaro Technologies Limited</strong>
            <span>Company workspace</span>
        </div>
    </div>

    <nav class="sidebar-nav" aria-label="ERP modules">
        @foreach ($navGroups as $groupIndex => $group)
            @php
                $groupId = 'sidebar-group-' . $groupIndex;
                $groupActive = collect($group['items'])->contains(fn ($item) => Navigation::isItemActive($item));
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
                                class="sidebar-link {{ Navigation::isItemActive($item) ? 'active' : '' }}"
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
