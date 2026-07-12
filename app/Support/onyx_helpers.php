<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

if (! function_exists('onyx_legacy_pages')) {
    function onyx_legacy_pages(): array
    {
        return [
            'dashboard', 'crm', 'customers', 'customers_action', 'suppliers', 'suppliers_action',
            'products', 'products_action', 'inventory', 'sales', 'sales_action', 'pos',
            'purchases', 'accounting', 'banking', 'budgets', 'assets', 'payroll',
            'reports', 'notifications', 'settings', 'mobile_app',
        ];
    }
}

if (! function_exists('onyx_legacy_url')) {
    function onyx_legacy_url(string $href): string
    {
        if ($href === '' || str_starts_with($href, '#') || preg_match('/^[a-z]+:/i', $href)) {
            return $href;
        }

        if (str_ends_with($href, '.php')) {
            return url($href);
        }

        $parts = parse_url($href);
        $path = $parts['path'] ?? $href;
        $query = isset($parts['query']) ? '?' . $parts['query'] : '';
        $fragment = isset($parts['fragment']) ? '#' . $parts['fragment'] : '';

        return str_ends_with($path, '.php') ? url($path) . $query . $fragment : $href;
    }
}

if (! function_exists('onyx_start_session')) {
    function onyx_start_session(): void
    {
        if (session_status() === PHP_SESSION_NONE && ! app()->bound('session')) {
            session_start();
        }

        $tenantId = request()->integer('tenant_id') ?: session('tenant_id');
        if (! $tenantId) {
            try {
                $tenantId = (int) (DB::table('tenants')->value('id') ?: 1);
            } catch (Throwable) {
                $tenantId = 1;
            }
        }

        session([
            'tenant_id' => $tenantId,
            'user_id' => Auth::id() ?: session('user_id', 1),
            'user_name' => Auth::user()->name ?? session('user_name', 'Operator'),
            'company_name' => session('company_name', config('app.name', 'Onyx Hub')),
            'currency' => session('currency', config('app.currency', 'UGX')),
            'role' => session('role', 'super_admin'),
        ]);

        $_SESSION['tenant_id'] = session('tenant_id');
        $_SESSION['user_id'] = session('user_id');
        $_SESSION['user_name'] = session('user_name');
        $_SESSION['company_name'] = session('company_name');
        $_SESSION['currency'] = session('currency');
        $_SESSION['role'] = session('role');
    }
}

if (! function_exists('onyx_context')) {
    function onyx_context(): array
    {
        onyx_start_session();

        return [
            'company_name' => session('company_name', 'Onyx Hub'),
            'user_name' => session('user_name', 'Operator'),
            'currency' => session('currency', config('app.currency', 'UGX')),
        ];
    }
}

if (! function_exists('onyx_db')) {
    function onyx_db(): PDO
    {
        $pdo = DB::connection()->getPdo();
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

        return $pdo;
    }
}

if (! function_exists('onyx_tenant_id')) {
    function onyx_tenant_id(): int
    {
        onyx_start_session();

        return (int) session('tenant_id', 1);
    }
}

if (! function_exists('onyx_money')) {
    function onyx_money(float $amount, string $currency): string
    {
        return number_format($amount, 2) . ' ' . $currency;
    }
}

if (! function_exists('onyx_scalar')) {
    function onyx_scalar(string $sql, array $params = [], mixed $default = 0): mixed
    {
        try {
            $stmt = onyx_db()->prepare($sql);
            $stmt->execute($params);
            $value = $stmt->fetchColumn();

            return $value === false || $value === null ? $default : $value;
        } catch (Throwable) {
            return $default;
        }
    }
}

if (! function_exists('onyx_rows')) {
    function onyx_rows(string $sql, array $params = []): array
    {
        try {
            $stmt = onyx_db()->prepare($sql);
            $stmt->execute($params);

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Throwable) {
            return [];
        }
    }
}

if (! function_exists('onyx_row')) {
    function onyx_row(string $sql, array $params = []): array|false
    {
        try {
            $stmt = onyx_db()->prepare($sql);
            $stmt->execute($params);

            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Throwable) {
            return false;
        }
    }
}

if (! function_exists('has_permission')) {
    function has_permission(string $permission): bool
    {
        onyx_start_session();

        if (session('role') === 'super_admin') {
            return true;
        }

        $permissions = session('permissions', []);
        if (is_array($permissions) && in_array($permission, $permissions, true)) {
            return true;
        }

        $roleMap = [
            'accountant' => ['view_reports', 'manage_purchases', 'manage_products'],
            'sales' => ['manage_purchases', 'manage_products'],
            'user' => ['view_reports'],
        ];

        return in_array($permission, $roleMap[session('role', 'user')] ?? [], true);
    }
}

if (! function_exists('require_permission')) {
    function require_permission(string $permission): void
    {
        if (! has_permission($permission)) {
            abort(403, 'You do not have permission to access this page.');
        }
    }
}

if (! function_exists('onyx_nav_items')) {
    function onyx_nav_items(): array
    {
        return [
            ['file' => 'dashboard.php', 'icon' => 'fa-chart-pie', 'label' => 'Dashboard'],
            ['file' => 'crm.php', 'icon' => 'fa-handshake', 'label' => 'CRM'],
            ['file' => 'customers.php', 'icon' => 'fa-users', 'label' => 'Customers'],
            ['file' => 'suppliers.php', 'icon' => 'fa-industry', 'label' => 'Suppliers'],
            ['file' => 'products.php', 'icon' => 'fa-boxes-stacked', 'label' => 'Products'],
            ['file' => 'inventory.php', 'icon' => 'fa-warehouse', 'label' => 'Inventory'],
            ['file' => 'sales.php', 'icon' => 'fa-file-invoice-dollar', 'label' => 'Sales'],
            ['file' => 'pos.php', 'icon' => 'fa-cash-register', 'label' => 'POS'],
            ['file' => 'purchases.php', 'icon' => 'fa-shopping-cart', 'label' => 'Purchases'],
            ['file' => 'accounting.php', 'icon' => 'fa-calculator', 'label' => 'Accounting'],
            ['file' => 'banking.php', 'icon' => 'fa-university', 'label' => 'Banking'],
            ['file' => 'budgets.php', 'icon' => 'fa-wallet', 'label' => 'Budgets'],
            ['file' => 'assets.php', 'icon' => 'fa-boxes', 'label' => 'Assets'],
            ['file' => 'payroll.php', 'icon' => 'fa-money-bill-wave', 'label' => 'Payroll'],
            ['file' => 'reports.php', 'icon' => 'fa-chart-line', 'label' => 'Reports'],
            ['file' => 'notifications.php', 'icon' => 'fa-bell', 'label' => 'Notifications'],
            ['file' => 'settings.php', 'icon' => 'fa-cog', 'label' => 'Settings'],
            ['file' => 'mobile_app.php', 'icon' => 'fa-mobile-alt', 'label' => 'Mobile App'],
        ];
    }
}

if (! function_exists('onyx_nav_groups')) {
    function onyx_nav_groups(): array
    {
        return [
            [
                'label' => 'Dashboard',
                'icon' => 'fa-gauge-high',
                'items' => [
                    ['file' => 'dashboard.php', 'icon' => 'fa-chart-pie', 'label' => 'Dashboard'],
                ],
            ],
            [
                'label' => 'Sales',
                'icon' => 'fa-cash-register',
                'items' => [
                    ['file' => 'pos.php', 'icon' => 'fa-store', 'label' => 'POS'],
                    ['file' => 'sales.php', 'icon' => 'fa-file-invoice-dollar', 'label' => 'Sales'],
                    ['file' => 'customers.php', 'icon' => 'fa-users', 'label' => 'Customers'],
                    ['file' => 'crm.php', 'icon' => 'fa-handshake', 'label' => 'CRM'],
                ],
            ],
            [
                'label' => 'Inventory',
                'icon' => 'fa-boxes-stacked',
                'items' => [
                    ['file' => 'products.php', 'icon' => 'fa-box', 'label' => 'Products'],
                    ['file' => 'inventory.php', 'icon' => 'fa-warehouse', 'label' => 'Inventory'],
                    ['file' => 'purchases.php', 'icon' => 'fa-cart-shopping', 'label' => 'Purchases'],
                    ['file' => 'suppliers.php', 'icon' => 'fa-truck', 'label' => 'Suppliers'],
                ],
            ],
            [
                'label' => 'Finance',
                'icon' => 'fa-scale-balanced',
                'items' => [
                    ['file' => 'accounting.php', 'icon' => 'fa-calculator', 'label' => 'Accounting'],
                    ['file' => 'banking.php', 'icon' => 'fa-building-columns', 'label' => 'Banking'],
                    ['file' => 'payroll.php', 'icon' => 'fa-money-check-dollar', 'label' => 'Payroll'],
                    ['file' => 'budgets.php', 'icon' => 'fa-wallet', 'label' => 'Budgets'],
                ],
            ],
            [
                'label' => 'Operations',
                'icon' => 'fa-briefcase',
                'items' => [
                    ['file' => 'assets.php', 'icon' => 'fa-laptop-file', 'label' => 'Assets'],
                    ['file' => 'mobile_app.php', 'icon' => 'fa-mobile-screen-button', 'label' => 'Mobile App'],
                    ['file' => 'notifications.php', 'icon' => 'fa-bell', 'label' => 'Notifications'],
                ],
            ],
            [
                'label' => 'Reports',
                'icon' => 'fa-file-lines',
                'items' => [
                    ['file' => 'reports.php', 'icon' => 'fa-chart-column', 'label' => 'Reports'],
                ],
            ],
            [
                'label' => 'Administration',
                'icon' => 'fa-sliders',
                'items' => [
                    ['file' => 'settings.php', 'icon' => 'fa-gear', 'label' => 'Settings'],
                ],
            ],
        ];
    }
}

if (! function_exists('onyx_current_page')) {
    function onyx_current_page(): string
    {
        $path = request()->path();
        if ($path === '/') {
            return 'dashboard.php';
        }

        $page = basename($path);

        return str_ends_with($page, '.php') ? $page : $page . '.php';
    }
}

if (! function_exists('onyx_page_start')) {
    function onyx_page_start(string $title, string $subtitle = ''): array
    {
        onyx_start_session();
        $context = onyx_context();
        $current_page = onyx_current_page();
        $currency = $context['currency'];
        $cssVersion = @filemtime(public_path('assets/css/style.css')) ?: time();
        ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title) ?> - ONYX ACCOUNTING SYSTEM</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= asset('assets/css/style.css') ?>?v=<?= $cssVersion ?>">
    <style>
        :root{--onyx-bg:#12121a;--onyx-surface:#1c1c27;--onyx-accent:#fff;--onyx-border:#2a2a3b;--onyx-text:#fff;--onyx-muted:#84849a}
        .onyx-erp-body{background:var(--onyx-bg);color:var(--onyx-text);font-family:Poppins,system-ui,sans-serif;margin:0;overflow-x:hidden}
        .onyx-erp-body a{color:inherit;text-decoration:none}
        .onyx-erp-body .sidebar{background:radial-gradient(circle at 10% 0,rgba(255,255,255,.07),transparent 24%),linear-gradient(180deg,#0b0b0d 0%,#050506 100%);border-right:1px solid rgba(255,255,255,.08);display:flex;flex-direction:column;height:calc(100vh - 82px);left:0;position:fixed;top:82px;width:272px;z-index:1000}
        .sidebar-header{display:none}.sidebar-logo-plate{align-items:center;background:#fff;border:1px solid rgba(255,255,255,.18);display:flex;flex:0 0 58px;height:58px;justify-content:center;padding:6px;width:58px}.sidebar-logo-plate img{display:block;height:100%;object-fit:contain;width:100%}.brand-title{font-size:.82rem;font-weight:800;line-height:1.25;margin:0}.brand-title small{color:#777783;display:block;font-size:.62rem;font-weight:700;margin-top:4px;text-transform:uppercase}.accent-text,.action-btn i,.panel-title i,.currency-badge,.badge{color:var(--onyx-accent)}
        .sidebar-meta,.muted{color:var(--onyx-muted)}.sidebar-menu-wrapper{flex:1;overflow-y:auto;padding:14px 10px}.sidebar-menu-wrapper::-webkit-scrollbar{width:6px}.sidebar-menu-wrapper::-webkit-scrollbar-thumb{background:rgba(255,255,255,.12)}
        .nav-group{margin-bottom:5px}.nav-dropdown-toggle,.nav-link-onyx{align-items:center;background:transparent;border:0;border-left:2px solid transparent;color:#a8a8b2;cursor:pointer;display:flex;font-family:Poppins,system-ui,sans-serif;font-size:12px;font-weight:700;gap:10px;letter-spacing:0;min-height:34px;padding:0 10px;text-align:left;width:100%}.nav-link-onyx{font-family:Poppins,system-ui,sans-serif;font-size:11px;font-weight:600;min-height:28px}.nav-direct-link{font-size:12px;font-weight:700;min-height:34px}.nav-dropdown-menu .nav-link-onyx{font-family:Poppins,system-ui,sans-serif;font-size:11px;font-weight:600;min-height:28px}.nav-dropdown-toggle{justify-content:space-between}.nav-dropdown-label{align-items:center;display:flex;gap:10px}.nav-link-onyx i,.nav-dropdown-label i{color:#777783;flex:0 0 17px;font-size:11px;text-align:center}.nav-direct-link i{font-size:12px}.nav-dropdown-caret{color:#555560;font-size:9px;transition:transform .16s ease}.nav-dropdown-caret.open{transform:rotate(180deg)}.nav-dropdown-toggle.active,.nav-dropdown-toggle:hover{background:rgba(255,255,255,.045);border-left-color:#fff;color:#fff}.nav-dropdown-toggle.active i,.nav-dropdown-toggle:hover i{color:#fff}.nav-link-onyx:hover{background:rgba(255,255,255,.04);color:#fff}.nav-link-onyx.active{background:#fff;border-left-color:#fff;color:#050506;font-weight:800}.nav-link-onyx.active i{color:#050506}
        .nav-dropdown-menu{display:none;flex-direction:column;padding:4px 0 5px 19px}.nav-dropdown-menu.open{display:flex}.nav-dropdown-menu .nav-link-onyx{border-left-color:rgba(255,255,255,.06);margin:1px 0}.sidebar-footer{border-top:1px solid rgba(255,255,255,.08);padding:14px}.sidebar-user{align-items:center;display:flex;gap:10px;margin-bottom:12px;min-width:0}.sidebar-user-avatar{align-items:center;background:#fff;color:#050506;display:flex;flex:0 0 32px;font-size:12px;font-weight:800;height:32px;justify-content:center;width:32px}.sidebar-user-copy{min-width:0}.sidebar-user-copy strong,.sidebar-user-copy span{display:block;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}.sidebar-user-copy strong{color:#fff;font-size:12px;font-weight:800}.sidebar-user-copy span{color:#777783;font-size:10px;font-weight:700;margin-top:2px}.logout-btn{align-items:center;background:rgba(255,255,255,.035);border:1px solid rgba(255,255,255,.08);border-radius:0;color:#d8d8de;cursor:pointer;display:flex;font:inherit;font-size:12px;font-weight:700;gap:8px;justify-content:center;min-height:34px;padding:0 12px;width:100%}.logout-btn:hover{background:rgba(239,68,68,.12);border-color:rgba(239,68,68,.38);color:#fff}
        .main-content{margin-left:272px;min-height:100vh;padding:24px 34px 34px}.main-topbar{align-items:center;background:linear-gradient(180deg,rgba(255,255,255,.045),rgba(255,255,255,.018));border:1px solid rgba(255,255,255,.08);display:flex;gap:12px;justify-content:space-between;margin:-24px -34px 24px -306px;min-height:58px;padding:10px 12px;position:sticky;top:0;width:calc(100% + 340px);z-index:1200}.topbar-left,.topbar-right{align-items:center;display:flex;gap:10px;min-width:0}.topbar-left{flex:1}.topbar-brand{align-items:center;display:flex;flex:0 0 272px;gap:10px;min-width:0;padding:0 10px;text-decoration:none}.topbar-brand img{background:#fff;height:38px;object-fit:contain;padding:4px;width:38px}.topbar-brand strong,.topbar-brand small{display:block;line-height:1.1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}.topbar-brand strong{color:#fff;font-size:13px;font-weight:800}.topbar-brand small{color:#777783;font-size:10px;font-weight:800;margin-top:4px;text-transform:uppercase}.topbar-right{flex-wrap:wrap;justify-content:flex-end}.topbar-search{align-items:center;background:rgba(0,0,0,.18);border:1px solid rgba(255,255,255,.08);display:flex;gap:8px;min-height:38px;padding:0 12px;width:min(360px,100%)}.topbar-search i{color:#777783;font-size:11px}.topbar-search input{background:transparent;border:0;color:#fff;font-family:Poppins,system-ui,sans-serif;font-size:12px;outline:0;padding:0;width:100%}.topbar-search input::placeholder{color:#777783}.topbar-action,.topbar-icon,.topbar-chip,.topbar-user{align-items:center;border:1px solid rgba(255,255,255,.08);display:inline-flex;min-height:38px}.topbar-action{background:#fff;color:#050506;font-size:12px;font-weight:800;gap:8px;padding:0 13px;text-decoration:none}.topbar-icon{background:rgba(255,255,255,.035);color:#d8d8de;justify-content:center;position:relative;width:38px}.topbar-icon-dot{background:#ef4444;height:7px;position:absolute;right:9px;top:9px;width:7px}.topbar-chip{background:rgba(255,255,255,.035);color:#d8d8de;font-size:11px;font-weight:700;gap:7px;padding:0 11px;white-space:nowrap}.topbar-chip i{color:#8d8d98;font-size:11px}.topbar-user{background:rgba(255,255,255,.035);gap:9px;padding:4px 10px 4px 4px}.topbar-user-avatar{align-items:center;background:#fff;color:#050506;display:flex;font-size:12px;font-weight:800;height:30px;justify-content:center;width:30px}.topbar-user strong,.topbar-user span{display:block;line-height:1.1;max-width:130px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}.topbar-user strong{color:#fff;font-size:11px;font-weight:800}.topbar-user span{color:#777783;font-size:10px;font-weight:700;margin-top:3px}.page-header{align-items:flex-start;display:flex;gap:20px;justify-content:space-between;margin-bottom:26px}.page-header h1{font-size:1.75rem;margin:0 0 6px}.page-subtitle{color:var(--onyx-muted);font-size:.88rem;margin:0}
        .currency-badge,.panel,.onyx-erp-body .stat-card,.profile-card,.product-tile{border:1px solid var(--onyx-border);border-radius:8px}.currency-badge{font-family:monospace;font-size:.78rem;padding:10px 12px;white-space:nowrap}
        .stat-grid,.module-grid{display:grid;gap:18px;margin-bottom:22px}.stat-grid{grid-template-columns:repeat(5,minmax(150px,1fr))}.module-grid{grid-template-columns:repeat(12,1fr)}.span-3{grid-column:span 3}.span-4{grid-column:span 4}.span-5{grid-column:span 5}.span-6{grid-column:span 6}.span-7{grid-column:span 7}.span-8{grid-column:span 8}.span-12{grid-column:span 12}
        .panel,.onyx-erp-body .stat-card{background:var(--onyx-surface);padding:20px}.panel-title,.stat-card .label,.onyx-erp-body .table th{color:var(--onyx-muted);font-size:.72rem;font-weight:700;letter-spacing:.8px;text-transform:uppercase}.stat-card .value{font-size:1.45rem;font-weight:800;margin-top:12px}.stat-card .note{color:var(--onyx-muted);font-size:.76rem;margin-top:8px}
        .quick-actions,.profile-grid,.product-grid{display:grid;gap:12px;grid-template-columns:repeat(auto-fit,minmax(150px,1fr))}.action-btn{align-items:center;background:rgba(255,107,0,.06);border:1px solid var(--onyx-border);border-radius:8px;color:var(--onyx-text);display:inline-flex;font-weight:600;gap:10px;padding:12px}
        .table-wrap{overflow-x:auto}.onyx-erp-body .table{border-collapse:collapse;min-width:760px;width:100%}.onyx-erp-body .table td,.onyx-erp-body .table th{border-bottom:1px solid rgba(255,255,255,.05);padding:13px 12px;text-align:left}
        @media(max-width:1180px){.stat-grid{grid-template-columns:repeat(2,minmax(150px,1fr))}.span-3,.span-4,.span-5,.span-6,.span-7,.span-8{grid-column:span 12}.main-topbar{align-items:stretch;flex-direction:column}.topbar-left,.topbar-right{width:100%}.topbar-brand{flex-basis:auto;width:100%}.topbar-search{width:100%}}@media(max-width:760px){.onyx-erp-body .sidebar{height:auto;position:static;width:100%}.main-content{margin-left:0;padding:20px}.main-topbar{margin:-20px -20px 24px;width:calc(100% + 40px)}.page-header{flex-direction:column}.stat-grid{grid-template-columns:1fr}.topbar-right{justify-content:flex-start}}
    </style>
</head>
<body class="onyx-erp-body">
    <aside class="sidebar">
        <div class="sidebar-header">
            <a class="sidebar-logo-plate" href="<?= url('dashboard') ?>" aria-label="Onyx dashboard">
                <img src="<?= asset('assets/onxy logo.jpeg') ?>" alt="Onyx logo">
            </a>
            <div title="<?= htmlspecialchars($context['company_name']) ?>">
                <h4 class="brand-title">Onyx BCS <small><?= htmlspecialchars($context['company_name']) ?></small></h4>
            </div>
        </div>
        <div class="sidebar-menu-wrapper">
            <nav>
                <?php foreach (onyx_nav_groups() as $groupIndex => $group): ?>
                    <?php
                        $groupActive = false;
                        foreach ($group['items'] as $item) {
                            if ($current_page === $item['file']) {
                                $groupActive = true;
                                break;
                            }
                        }
                        $groupId = 'nav-group-' . $groupIndex;
                    ?>
                    <div class="nav-group" data-nav-group>
                        <?php if (($group['label'] ?? '') === 'Dashboard'): ?>
                            <?php $dashboardItem = $group['items'][0]; ?>
                            <a class="nav-link-onyx nav-direct-link <?= $groupActive ? 'active' : '' ?>" href="<?= url($dashboardItem['file']) ?>" data-nav-link>
                                <i class="fa-solid <?= htmlspecialchars($group['icon']) ?>"></i>
                                <span><?= htmlspecialchars($group['label']) ?></span>
                            </a>
                        <?php else: ?>
                        <button class="nav-dropdown-toggle <?= $groupActive ? 'active' : '' ?>" type="button" data-target="<?= htmlspecialchars($groupId) ?>" aria-expanded="<?= $groupActive ? 'true' : 'false' ?>">
                            <span class="nav-dropdown-label">
                                <i class="fa-solid <?= htmlspecialchars($group['icon']) ?>"></i>
                                <span><?= htmlspecialchars($group['label']) ?></span>
                            </span>
                            <i class="fa-solid fa-chevron-down nav-dropdown-caret <?= $groupActive ? 'open' : '' ?>"></i>
                        </button>
                        <div class="nav-dropdown-menu <?= $groupActive ? 'open' : '' ?>" id="<?= htmlspecialchars($groupId) ?>">
                            <?php foreach ($group['items'] as $item): ?>
                                <a class="nav-link-onyx <?= $current_page === $item['file'] ? 'active' : '' ?>" href="<?= url($item['file']) ?>" data-nav-link>
                                    <i class="fa-solid <?= htmlspecialchars($item['icon']) ?>"></i>
                                    <span><?= htmlspecialchars($item['label']) ?></span>
                                </a>
                                <?php if (($item['file'] ?? '') === 'settings.php'): ?>
                                <?php foreach (['company' => 'Company', 'accounting' => 'Accounting', 'tax' => 'Tax', 'users_roles' => 'Users & Roles', 'branches' => 'Branches', 'warehouses' => 'Warehouses', 'products' => 'Products', 'payment_methods' => 'Payment Methods', 'invoice_settings' => 'Invoice Settings', 'receipt_settings' => 'Receipt Settings', 'quotation_settings' => 'Quotation Settings', 'notifications' => 'Notifications', 'email_smtp' => 'Email (SMTP)', 'security' => 'Security', 'audit_logs' => 'Audit Logs', 'financial_periods' => 'Financial Periods', 'backup_restore' => 'Backup & Restore', 'import_export' => 'Import & Export', 'system_info' => 'System Information'] as $anchor => $label): ?>
                                    <a class="nav-link-onyx" href="<?= url('settings.php') ?>#<?= htmlspecialchars($anchor) ?>" data-nav-link><span><?= htmlspecialchars($label) ?></span></a>
                                <?php endforeach; ?>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </nav>
        </div>
        <div class="sidebar-footer">
            <form method="POST" action="<?= route('logout') ?>">
                <input type="hidden" name="_token" value="<?= csrf_token() ?>">
                <button type="submit" class="logout-btn"><i class="fa-solid fa-arrow-right-from-bracket"></i> Secure Logout</button>
            </form>
        </div>
    </aside>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const sidebar = document.querySelector('.sidebar');
            if (!sidebar) return;
            sidebar.querySelectorAll('.nav-dropdown-toggle').forEach(function (button) {
                button.addEventListener('click', function () {
                    const targetId = this.getAttribute('data-target');
                    const menu = sidebar.querySelector('#' + (window.CSS && CSS.escape ? CSS.escape(targetId) : targetId));
                    const caret = this.querySelector('.nav-dropdown-caret');
                    if (!menu) return;
                    const isOpen = menu.classList.contains('open');
                    menu.classList.toggle('open', !isOpen);
                    this.classList.toggle('active', !isOpen);
                    this.setAttribute('aria-expanded', String(!isOpen));
                    if (caret) caret.classList.toggle('open', !isOpen);
                });
            });

            const search = document.querySelector('#erp-topbar-search');
            if (search) {
                search.addEventListener('input', function () {
                    const query = this.value.trim().toLowerCase();
                    sidebar.querySelectorAll('[data-nav-group]').forEach(function (group) {
                        let hasMatch = false;
                        group.querySelectorAll('[data-nav-link]').forEach(function (link) {
                            const matches = link.textContent.toLowerCase().includes(query);
                            link.hidden = query.length > 0 && !matches;
                            hasMatch = hasMatch || matches;
                        });
                        group.hidden = query.length > 0 && !hasMatch;
                        if (query.length > 0 && hasMatch) {
                            const menu = group.querySelector('.nav-dropdown-menu');
                            const toggle = group.querySelector('.nav-dropdown-toggle');
                            const caret = group.querySelector('.nav-dropdown-caret');
                            if (menu) menu.classList.add('open');
                            if (toggle) {
                                toggle.classList.add('active');
                                toggle.setAttribute('aria-expanded', 'true');
                            }
                            if (caret) caret.classList.add('open');
                        }
                    });
                });
            }
        });
    </script>
    <main class="main-content">
        <div class="main-topbar">
            <div class="topbar-left">
                <a class="topbar-brand" href="<?= url('dashboard') ?>" aria-label="Onyx dashboard">
                    <img src="<?= asset('assets/onxy logo.jpeg') ?>" alt="Onyx logo">
                    <span>
                        <strong>Onyx BCS</strong>
                        <small><?= htmlspecialchars($context['company_name']) ?></small>
                    </span>
                </a>
                <label class="topbar-search" for="erp-topbar-search">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <input id="erp-topbar-search" type="search" placeholder="Search modules..." autocomplete="off">
                </label>
            </div>
            <div class="topbar-right">
                <a class="topbar-action" href="<?= url('pos.php') ?>">
                    <i class="fa-solid fa-plus"></i>
                    <span>New Sale</span>
                </a>
                <span class="topbar-chip">
                    <i class="fa-solid fa-circle-check"></i>
                    System Online
                </span>
                <span class="topbar-chip">
                    <i class="fa-solid fa-calendar-day"></i>
                    <?= date('M d, Y') ?>
                </span>
                <span class="topbar-chip">
                    <i class="fa-solid fa-coins"></i>
                    <?= htmlspecialchars($currency) ?>
                </span>
                <a class="topbar-icon" href="<?= url('notifications.php') ?>" aria-label="Notifications">
                    <i class="fa-solid fa-bell"></i>
                    <span class="topbar-icon-dot"></span>
                </a>
                <div class="topbar-user">
                    <div class="topbar-user-avatar"><?= htmlspecialchars(strtoupper(substr($context['user_name'], 0, 1))) ?></div>
                    <div>
                        <strong><?= htmlspecialchars($context['user_name']) ?></strong>
                        <span><?= htmlspecialchars(session('role', 'super_admin')) ?></span>
                    </div>
                </div>
            </div>
        </div>
        <div class="page-header">
            <div>
                <h1><?= htmlspecialchars($title) ?></h1>
                <?php if ($subtitle !== ''): ?>
                    <p class="page-subtitle"><?= htmlspecialchars($subtitle) ?></p>
                <?php endif; ?>
            </div>
        </div>
        <?php
        return $context;
    }
}

if (! function_exists('onyx_page_end')) {
    function onyx_page_end(): void
    {
        echo '</main></body></html>';
    }
}

if (! function_exists('onyx_stat_card')) {
    function onyx_stat_card(string $label, string $value, string $note = ''): void
    {
        ?>
        <div class="stat-card">
            <div class="label"><?= htmlspecialchars($label) ?></div>
            <div class="value"><?= htmlspecialchars($value) ?></div>
            <?php if ($note !== ''): ?><div class="note"><?= htmlspecialchars($note) ?></div><?php endif; ?>
        </div>
        <?php
    }
}

if (! function_exists('onyx_panel_start')) {
    function onyx_panel_start(string $title, string $icon = 'fa-circle-dot', string $span = 'span-6'): void
    {
        echo '<section class="panel ' . htmlspecialchars($span) . '"><div class="panel-title"><i class="fa-solid ' . htmlspecialchars($icon) . '"></i> ' . htmlspecialchars($title) . '</div>';
    }
}

if (! function_exists('onyx_panel_end')) {
    function onyx_panel_end(): void
    {
        echo '</section>';
    }
}

if (! function_exists('onyx_table')) {
    function onyx_table(array $headers, array $rows): void
    {
        ?>
        <div class="table-wrap">
            <table class="table">
                <thead><tr><?php foreach ($headers as $header): ?><th><?= htmlspecialchars($header) ?></th><?php endforeach; ?></tr></thead>
                <tbody>
                    <?php if ($rows === []): ?>
                        <tr><td colspan="<?= count($headers) ?>" class="muted">No database records found for this workspace.</td></tr>
                    <?php else: ?>
                        <?php foreach ($rows as $row): ?>
                            <tr><?php foreach ($row as $cell): ?><td><?= htmlspecialchars((string) $cell) ?></td><?php endforeach; ?></tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }
}

if (! function_exists('onyx_table_html')) {
    function onyx_table_html(array $headers, array $rows): void
    {
        ?>
        <div class="table-wrap">
            <table class="table">
                <thead><tr><?php foreach ($headers as $header): ?><th><?= htmlspecialchars($header) ?></th><?php endforeach; ?></tr></thead>
                <tbody>
                    <?php if ($rows === []): ?>
                        <tr><td colspan="<?= count($headers) ?>" class="muted">No database records found for this workspace.</td></tr>
                    <?php else: ?>
                        <?php foreach ($rows as $row): ?>
                            <tr>
                                <?php foreach ($row as $cell): ?>
                                    <td><?= is_array($cell) && ($cell['raw'] ?? false) ? $cell['value'] : htmlspecialchars((string) $cell) ?></td>
                                <?php endforeach; ?>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }
}

if (! function_exists('onyx_action_grid')) {
    function onyx_action_grid(array $actions): void
    {
        echo '<div class="quick-actions">';
        foreach ($actions as $action) {
            echo '<a class="action-btn" href="' . htmlspecialchars(onyx_legacy_url($action['href'] ?? '#')) . '">';
            echo '<i class="fa-solid ' . htmlspecialchars($action['icon'] ?? 'fa-circle-dot') . '"></i>';
            echo '<span>' . htmlspecialchars($action['label']) . '</span></a>';
        }
        echo '</div>';
    }
}

if (! function_exists('onyx_clean_list')) {
    function onyx_clean_list(array $items): void
    {
        echo '<ul class="clean-list">';
        foreach ($items as $item) {
            echo '<li><span>' . htmlspecialchars($item[0]) . '</span><span class="badge">' . htmlspecialchars($item[1]) . '</span></li>';
        }
        echo '</ul>';
    }
}
