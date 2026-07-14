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
            'purchases', 'accounting', 'banking', 'budgets', 'assets', 'human_resources',
            'hr_profiles', 'hr_employee', 'hr_contracts', 'hr_attendance', 'hr_leave', 'hr_advances',
            'hr_documents', 'hr_performance', 'hr_payroll_readiness', 'payroll',
            'reports', 'notifications', 'settings', 'document_templates', 'mobile_app',
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

        $tenantId = Auth::user()?->tenant_id ?: session('tenant_id');
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
            'role' => Auth::user()->role ?? session('role', 'user'),
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

if (! function_exists('onyx_hr_ensure_schema')) {
    function onyx_hr_ensure_schema(PDO $pdo): void
    {
        $pdo->exec("CREATE TABLE IF NOT EXISTS hr_employees (
            id BIGINT(20) NOT NULL AUTO_INCREMENT,
            tenant_id BIGINT(20) NOT NULL,
            employee_code VARCHAR(50) NOT NULL,
            full_name VARCHAR(180) NOT NULL,
            gender VARCHAR(40) DEFAULT NULL,
            date_of_birth DATE DEFAULT NULL,
            department VARCHAR(120) DEFAULT NULL,
            job_title VARCHAR(120) DEFAULT NULL,
            employment_type VARCHAR(60) DEFAULT 'Full time',
            phone VARCHAR(80) DEFAULT NULL,
            email VARCHAR(180) DEFAULT NULL,
            national_id VARCHAR(120) DEFAULT NULL,
            bank_wallet VARCHAR(180) DEFAULT NULL,
            address TEXT DEFAULT NULL,
            next_of_kin VARCHAR(180) DEFAULT NULL,
            kin_phone VARCHAR(80) DEFAULT NULL,
            basic_pay DECIMAL(15,2) NOT NULL DEFAULT 0.00,
            status VARCHAR(50) NOT NULL DEFAULT 'Active',
            notes TEXT DEFAULT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uq_hr_employee_code (tenant_id, employee_code)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

        $pdo->exec("CREATE TABLE IF NOT EXISTS hr_employee_documents (
            id BIGINT(20) NOT NULL AUTO_INCREMENT,
            tenant_id BIGINT(20) NOT NULL,
            employee_id BIGINT(20) NOT NULL,
            document_type VARCHAR(120) NOT NULL,
            title VARCHAR(180) NOT NULL,
            document_ref VARCHAR(160) DEFAULT NULL,
            issued_by VARCHAR(180) DEFAULT NULL,
            issue_date DATE DEFAULT NULL,
            expiry_date DATE DEFAULT NULL,
            file_path VARCHAR(255) DEFAULT NULL,
            original_name VARCHAR(255) DEFAULT NULL,
            mime_type VARCHAR(120) DEFAULT NULL,
            file_size BIGINT(20) DEFAULT NULL,
            status VARCHAR(50) NOT NULL DEFAULT 'Filed',
            notes TEXT DEFAULT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_hr_doc_employee (tenant_id, employee_id),
            KEY idx_hr_doc_expiry (tenant_id, expiry_date)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

        $pdo->exec("CREATE TABLE IF NOT EXISTS hr_employee_contracts (
            id BIGINT(20) NOT NULL AUTO_INCREMENT,
            tenant_id BIGINT(20) NOT NULL,
            employee_id BIGINT(20) NOT NULL,
            contract_type VARCHAR(80) NOT NULL DEFAULT 'Full time',
            department VARCHAR(120) DEFAULT NULL,
            job_title VARCHAR(120) DEFAULT NULL,
            supervisor VARCHAR(180) DEFAULT NULL,
            start_date DATE DEFAULT NULL,
            end_date DATE DEFAULT NULL,
            probation_end DATE DEFAULT NULL,
            status VARCHAR(50) NOT NULL DEFAULT 'Active',
            role_summary TEXT DEFAULT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_hr_contract_employee (tenant_id, employee_id),
            KEY idx_hr_contract_expiry (tenant_id, end_date)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    }
}

if (! function_exists('onyx_hr_seed_employees')) {
    function onyx_hr_seed_employees(PDO $pdo, int $tenantId): void
    {
        $count = (int) onyx_scalar('SELECT COUNT(*) FROM hr_employees WHERE tenant_id = :tenant_id', ['tenant_id' => $tenantId], 0);
        if ($count > 0) {
            return;
        }

        $stmt = $pdo->prepare('INSERT INTO hr_employees (tenant_id, employee_code, full_name, gender, department, job_title, employment_type, phone, email, national_id, bank_wallet, address, next_of_kin, kin_phone, basic_pay, status, notes, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())');
        foreach ([
            ['EMP-001', 'Super Admin', 'Prefer not to say', 'Administration', 'Super Admin', 'Full time', '+256 700 000 001', 'superadmin@clinic.test', '', '', 'Kampala', '', '', 0, 'Active', 'System administration profile.'],
            ['EMP-002', 'Cashier', 'Female', 'Sales', 'Cashier', 'Full time', '+256 700 000 002', 'cashier@clinic.test', '', '', 'Kampala', 'Jane Cashier', '+256 701 111 222', 0, 'Active', 'Bank details pending review.'],
            ['EMP-003', 'Store Manager', 'Male', 'Inventory', 'Store Manager', 'Full time', '+256 700 000 003', 'store@clinic.test', '', '', 'Kampala', 'Michael Store', '+256 702 222 333', 0, 'Onboarding', 'Next of kin pending confirmation.'],
        ] as $row) {
            $stmt->execute(array_merge([$tenantId], $row));
        }
    }
}

if (! function_exists('has_permission')) {
    function has_permission(string $permission): bool
    {
        onyx_start_session();

        $mappedPermission = [
            'view_reports' => 'reports.view',
            'manage_purchases' => 'purchases.manage',
            'manage_products' => 'products.manage',
            'manage_hr' => 'hr.manage',
            'view_hr' => 'hr.view',
        ][$permission] ?? $permission;

        if (Auth::check()) {
            return Auth::user()->hasPermission($mappedPermission);
        }

        $permissions = session('permissions', []);
        if (is_array($permissions) && in_array($mappedPermission, $permissions, true)) {
            return true;
        }

        return false;
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
            ['file' => 'document_templates.php', 'icon' => 'fa-file-lines', 'label' => 'Document Templates'],
            ['file' => 'pos.php', 'icon' => 'fa-cash-register', 'label' => 'POS'],
            ['file' => 'purchases.php', 'icon' => 'fa-shopping-cart', 'label' => 'Purchases'],
            ['file' => 'accounting.php', 'icon' => 'fa-calculator', 'label' => 'Accounting'],
            ['file' => 'banking.php', 'icon' => 'fa-university', 'label' => 'Banking'],
            ['file' => 'budgets.php', 'icon' => 'fa-wallet', 'label' => 'Budgets'],
            ['file' => 'assets.php', 'icon' => 'fa-boxes', 'label' => 'Assets'],
            ['file' => 'human_resources.php', 'icon' => 'fa-users-gear', 'label' => 'Employees'],
            ['file' => 'hr_contracts.php', 'icon' => 'fa-file-signature', 'label' => 'Contracts'],
            ['file' => 'hr_attendance.php', 'icon' => 'fa-clock', 'label' => 'Attendance'],
            ['file' => 'hr_leave.php', 'icon' => 'fa-calendar-check', 'label' => 'Leave'],
            ['file' => 'hr_advances.php', 'icon' => 'fa-hand-holding-dollar', 'label' => 'Advances'],
            ['file' => 'hr_documents.php', 'icon' => 'fa-folder-open', 'label' => 'Documents'],
            ['file' => 'hr_performance.php', 'icon' => 'fa-chart-line', 'label' => 'Performance'],
            ['file' => 'hr_payroll_readiness.php', 'icon' => 'fa-clipboard-check', 'label' => 'Payroll Readiness'],
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
                    ['file' => 'document_templates.php', 'icon' => 'fa-file-lines', 'label' => 'Document Templates'],
                    ['file' => 'banking.php', 'icon' => 'fa-building-columns', 'label' => 'Banking'],
                    ['file' => 'budgets.php', 'icon' => 'fa-wallet', 'label' => 'Budgets'],
                    ['file' => 'assets.php', 'icon' => 'fa-laptop-file', 'label' => 'Assets'],
                    ['file' => 'reports.php', 'icon' => 'fa-chart-column', 'label' => 'Reports'],
                ],
            ],
            [
                'label' => 'Human Resource',
                'icon' => 'fa-users-gear',
                'items' => [
                    ['file' => 'human_resources.php', 'icon' => 'fa-users', 'label' => 'Employees'],
                    ['file' => 'hr_contracts.php', 'icon' => 'fa-file-signature', 'label' => 'Contracts & Roles'],
                    ['file' => 'hr_attendance.php', 'icon' => 'fa-clock', 'label' => 'Attendance'],
                    ['file' => 'hr_leave.php', 'icon' => 'fa-calendar-check', 'label' => 'Leave'],
                    ['file' => 'hr_advances.php', 'icon' => 'fa-hand-holding-dollar', 'label' => 'Advances & Loans'],
                    ['file' => 'hr_documents.php', 'icon' => 'fa-folder-open', 'label' => 'Documents'],
                    ['file' => 'hr_performance.php', 'icon' => 'fa-chart-line', 'label' => 'Performance'],
                    ['file' => 'hr_payroll_readiness.php', 'icon' => 'fa-clipboard-check', 'label' => 'Payroll Readiness'],
                    ['file' => 'payroll.php', 'icon' => 'fa-money-check-dollar', 'label' => 'Payroll'],
                ],
            ],
            [
                'label' => 'Operations',
                'icon' => 'fa-briefcase',
                'items' => [
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
                'label' => 'Users & Roles Management',
                'icon' => 'fa-users-gear',
                'items' => [
                    ['file' => 'users.php', 'href' => route('settings.users'), 'icon' => 'fa-users-gear', 'label' => 'Users'],
                    ['file' => 'roles.php', 'href' => route('settings.roles'), 'icon' => 'fa-shield-halved', 'label' => 'Roles & Permissions'],
                    ['file' => 'security.php', 'href' => route('settings.security'), 'icon' => 'fa-lock', 'label' => 'Security Settings'],
                    ['file' => 'audit-logs.php', 'href' => route('settings.audit_logs'), 'icon' => 'fa-clock-rotate-left', 'label' => 'Audit Logs'],
                ],
            ],
            [
                'label' => 'Settings',
                'icon' => 'fa-sliders',
                'items' => [
                    ['file' => 'settings.php', 'icon' => 'fa-gear', 'label' => 'Overview'],
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
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= asset('assets/css/style.css') ?>?v=<?= $cssVersion ?>">
    <style>
        :root{--onyx-bg:#07111a;--onyx-surface:#101923;--onyx-surface-2:#131d28;--onyx-accent:#ff6a00;--onyx-accent-2:#ff8a1d;--onyx-border:#263241;--onyx-text:#f5f7fa;--onyx-muted:#8d99a8}
        .onyx-erp-body{background:var(--onyx-bg);color:var(--onyx-text);font-family:Poppins,system-ui,sans-serif;font-size:12px;margin:0;overflow-x:hidden}
        .onyx-erp-body a{color:inherit;text-decoration:none}
        .onyx-erp-body .sidebar{background:radial-gradient(circle at 10% 0,rgba(255,255,255,.07),transparent 24%),linear-gradient(180deg,#0b0b0d 0%,#050506 100%);border-right:1px solid rgba(255,255,255,.08);display:flex;flex-direction:column;height:calc(100vh - 82px);left:0;position:fixed;top:82px;transition:transform .18s ease;width:272px;z-index:1000}.onyx-erp-body.sidebar-collapsed .sidebar{transform:translateX(-290px)}
        .sidebar-header{display:none}.sidebar-logo-plate{align-items:center;background:#fff;border:1px solid rgba(255,255,255,.18);display:flex;flex:0 0 58px;height:58px;justify-content:center;padding:6px;width:58px}.sidebar-logo-plate img{display:block;height:100%;object-fit:contain;width:100%}.brand-title{font-size:.82rem;font-weight:800;line-height:1.25;margin:0}.brand-title small{color:#777783;display:block;font-size:.62rem;font-weight:700;margin-top:4px;text-transform:uppercase}.accent-text,.action-btn i,.panel-title i,.currency-badge,.badge{color:var(--onyx-accent)}
        .sidebar-meta,.muted{color:var(--onyx-muted)}.sidebar-menu-wrapper{flex:1;overflow-y:auto;padding:14px 10px}.sidebar-menu-wrapper::-webkit-scrollbar{width:6px}.sidebar-menu-wrapper::-webkit-scrollbar-thumb{background:rgba(255,255,255,.12)}
        .nav-group{margin-bottom:5px}.nav-dropdown-toggle,.nav-link-onyx{align-items:center;background:transparent;border:0;border-left:2px solid transparent;color:#a8a8b2;cursor:pointer;display:flex;font-family:Poppins,system-ui,sans-serif;font-size:11px;font-weight:700;gap:10px;letter-spacing:0;min-height:34px;padding:0 10px;text-align:left;width:100%}.nav-link-onyx{font-family:Poppins,system-ui,sans-serif;font-size:11px;font-weight:600;min-height:28px}.nav-direct-link{font-size:11px;font-weight:700;min-height:34px}.nav-dropdown-menu .nav-link-onyx{font-family:Poppins,system-ui,sans-serif;font-size:11px;font-weight:600;min-height:28px}.nav-dropdown-toggle{justify-content:space-between}.nav-dropdown-label{align-items:center;display:flex;gap:10px}.nav-link-onyx i,.nav-dropdown-label i{color:#777783;flex:0 0 17px;font-size:11px;text-align:center}.nav-direct-link i{font-size:11px}.nav-dropdown-caret{color:#555560;font-size:9px;transition:transform .16s ease}.nav-dropdown-caret.open{transform:rotate(180deg)}.nav-dropdown-toggle.active,.nav-dropdown-toggle:hover{background:rgba(255,255,255,.045);border-left-color:#fff;color:#fff}.nav-dropdown-toggle.active i,.nav-dropdown-toggle:hover i{color:#fff}.nav-link-onyx:hover{background:rgba(255,255,255,.04);color:#fff}.nav-link-onyx.active{background:#fff;border-left-color:#fff;color:#050506;font-weight:800}.nav-link-onyx.active i{color:#050506}
        .nav-dropdown-menu{display:none;flex-direction:column;padding:4px 0 5px 19px}.nav-dropdown-menu.open{display:flex}.nav-dropdown-menu .nav-link-onyx{border-left-color:rgba(255,255,255,.06);margin:1px 0}.sidebar-footer{border-top:1px solid rgba(255,255,255,.08);padding:14px}.sidebar-user{align-items:center;display:flex;gap:10px;margin-bottom:12px;min-width:0}.sidebar-user-avatar{align-items:center;background:#fff;color:#050506;display:flex;flex:0 0 32px;font-size:12px;font-weight:800;height:32px;justify-content:center;width:32px}.sidebar-user-copy{min-width:0}.sidebar-user-copy strong,.sidebar-user-copy span{display:block;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}.sidebar-user-copy strong{color:#fff;font-size:12px;font-weight:800}.sidebar-user-copy span{color:#777783;font-size:10px;font-weight:700;margin-top:2px}.logout-btn{align-items:center;background:rgba(255,255,255,.035);border:1px solid rgba(255,255,255,.08);border-radius:0;color:#d8d8de;cursor:pointer;display:flex;font:inherit;font-size:12px;font-weight:700;gap:8px;justify-content:center;min-height:34px;padding:0 12px;width:100%}.logout-btn:hover{background:rgba(239,68,68,.12);border-color:rgba(239,68,68,.38);color:#fff}
        .main-content{margin-left:272px;min-height:100vh;padding:24px 34px 34px;transition:margin-left .18s ease}.onyx-erp-body.sidebar-collapsed .main-content{margin-left:0}.main-topbar{align-items:center;background:#050506;border:0;border-bottom:1px solid rgba(255,255,255,.08);box-shadow:0 10px 24px rgba(0,0,0,.35);display:flex;gap:12px;justify-content:space-between;margin:-24px -34px 24px -306px;min-height:58px;padding:10px 12px;position:sticky;top:0;transition:margin .18s ease,width .18s ease;width:calc(100% + 340px);z-index:1200}.onyx-erp-body.sidebar-collapsed .main-topbar{margin:-24px -34px 24px -34px;width:calc(100% + 68px)}.topbar-left,.topbar-right{align-items:center;display:flex;gap:10px;min-width:0}.topbar-left{flex:1}.topbar-brand{align-items:center;display:flex;flex:0 0 272px;gap:10px;min-width:0;padding:0 10px;text-decoration:none}.onyx-erp-body.sidebar-collapsed .topbar-brand{flex:0 1 auto;min-width:190px}.topbar-brand img{background:#fff;height:38px;object-fit:contain;padding:4px;width:38px}.topbar-brand strong,.topbar-brand small{display:block;line-height:1.1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}.topbar-brand strong{color:#fff;font-size:13px;font-weight:800}.topbar-brand small{color:#777783;font-size:10px;font-weight:800;margin-top:4px;text-transform:uppercase}.topbar-right{flex-wrap:wrap;justify-content:flex-end}.topbar-search{align-items:center;background:rgba(0,0,0,.18);border:1px solid rgba(255,255,255,.08);display:flex;gap:8px;min-height:38px;padding:0 12px;width:min(360px,100%)}.topbar-search i{color:#777783;font-size:11px}.topbar-search input{background:transparent;border:0;color:#fff;font-family:Poppins,system-ui,sans-serif;font-size:12px;outline:0;padding:0;width:100%}.topbar-search input::placeholder{color:#777783}.topbar-sidebar-toggle,.topbar-action,.topbar-icon,.topbar-chip,.topbar-user{align-items:center;border:1px solid rgba(255,255,255,.08);display:inline-flex;min-height:38px}.topbar-sidebar-toggle{background:rgba(255,255,255,.035);color:#d8d8de;cursor:pointer;flex:0 0 38px;font:inherit;justify-content:center;padding:0;width:38px}.topbar-sidebar-toggle:hover,.topbar-sidebar-toggle.active{background:#fff;border-color:#fff;color:#050506}.topbar-action{background:transparent;color:#fff;font-size:12px;font-weight:800;gap:8px;padding:0 13px;text-decoration:none}.topbar-action:hover{background:rgba(255,255,255,.06);text-decoration:none}.topbar-icon{background:rgba(255,255,255,.035);color:#d8d8de;justify-content:center;position:relative;width:38px}.topbar-icon-dot{background:#ef4444;height:7px;position:absolute;right:9px;top:9px;width:7px}.topbar-chip{background:rgba(255,255,255,.035);color:#d8d8de;font-size:11px;font-weight:700;gap:7px;padding:0 11px;white-space:nowrap}.topbar-chip i{color:#8d8d98;font-size:11px}.topbar-user{background:rgba(255,255,255,.035);gap:9px;padding:4px 10px 4px 4px}.topbar-user-avatar{align-items:center;background:#fff;color:#050506;display:flex;font-size:12px;font-weight:800;height:30px;justify-content:center;width:30px}.topbar-user strong,.topbar-user span{display:block;line-height:1.1;max-width:130px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}.topbar-user strong{color:#fff;font-size:11px;font-weight:800}.topbar-user span{color:#777783;font-size:10px;font-weight:700;margin-top:3px}.page-header{align-items:flex-start;display:flex;gap:20px;justify-content:space-between;margin-bottom:26px}.page-header h1{font-size:1.75rem;margin:0 0 6px}.page-subtitle{color:var(--onyx-muted);font-size:.88rem;margin:0}
        .topbar-sidebar-toggle{background:#fff!important;box-shadow:0 6px 14px rgba(0,0,0,.22);color:#050506!important;flex:0 0 22px;height:22px;left:242px;min-height:22px!important;position:fixed;top:101px;transition:left .18s ease,transform .18s ease,background .16s ease,color .16s ease;width:22px;z-index:1400}.topbar-sidebar-toggle i{font-size:9px;transition:transform .18s ease}.onyx-erp-body.sidebar-collapsed .topbar-sidebar-toggle{left:12px}.onyx-erp-body.sidebar-collapsed .topbar-sidebar-toggle i{transform:rotate(180deg)}
        .currency-badge,.panel,.onyx-erp-body .stat-card,.profile-card,.product-tile{border:1px solid var(--onyx-border);border-radius:8px}.currency-badge{font-family:monospace;font-size:.78rem;padding:10px 12px;white-space:nowrap}
        .stat-grid,.module-grid{display:grid;gap:18px;margin-bottom:22px}.stat-grid{grid-template-columns:repeat(5,minmax(150px,1fr))}.module-grid{grid-template-columns:repeat(12,1fr)}.span-3{grid-column:span 3}.span-4{grid-column:span 4}.span-5{grid-column:span 5}.span-6{grid-column:span 6}.span-7{grid-column:span 7}.span-8{grid-column:span 8}.span-12{grid-column:span 12}
        .panel,.onyx-erp-body .stat-card{background:var(--onyx-surface);padding:20px}.panel-title,.stat-card .label,.onyx-erp-body .table th{color:var(--onyx-muted);font-size:.72rem;font-weight:700;letter-spacing:.8px;text-transform:uppercase}.stat-card .value{font-size:1.45rem;font-weight:800;margin-top:12px}.stat-card .note{color:var(--onyx-muted);font-size:.76rem;margin-top:8px}
        .quick-actions,.profile-grid,.product-grid{display:grid;gap:12px;grid-template-columns:repeat(auto-fit,minmax(150px,1fr))}.action-btn{align-items:center;background:rgba(255,255,255,.035);border:1px solid var(--onyx-border);border-radius:8px;color:var(--onyx-text);display:inline-flex;font-weight:600;gap:10px;padding:12px}
        .ops-board,.ops-board *{border-radius:0!important}.ops-board{display:grid;gap:18px}.ops-strip{display:grid;gap:10px;grid-template-columns:repeat(4,minmax(148px,1fr))}.ops-card,.ops-action,.ops-check,.ops-report-card{background:var(--onyx-surface);border:1px solid var(--onyx-border);min-width:0}.ops-card{padding:14px}.ops-card span,.ops-field label,.ops-section-title,.ops-check span,.ops-report-card span{color:var(--onyx-muted);display:block;font-size:.58rem;font-weight:800;letter-spacing:.8px;text-transform:uppercase}.ops-card strong{color:#fff;display:block;font-size:1.08rem;font-weight:900;line-height:1.2;margin-top:8px;word-break:break-word}.ops-form{display:grid;gap:12px;grid-template-columns:repeat(4,1fr)}.ops-field{display:grid;gap:6px;min-width:0}.ops-field.wide{grid-column:span 2}.ops-field.full{grid-column:1/-1}.ops-field input,.ops-field select,.ops-field textarea,.ops-filters input,.ops-filters select{background:#101016;border:1px solid rgba(255,255,255,.12);color:#fff;font:inherit;font-size:.78rem;min-height:38px;padding:9px;width:100%}.ops-field select option,.ops-filters select option{background:#050506;color:#fff}.ops-btn,.ops-action{align-items:center;display:inline-flex;font-size:.68rem;font-weight:800;gap:8px;justify-content:center;min-height:38px;padding:0 12px;text-decoration:none;text-transform:uppercase;white-space:nowrap}.ops-btn{background:#fff;border:1px solid #fff;color:#050506}.ops-btn.ghost{background:transparent;color:#fff}.ops-action{background:rgba(255,255,255,.035);color:#fff}.ops-action.primary{background:#fff;color:#050506}.ops-actions,.ops-tags{display:flex;flex-wrap:wrap;gap:8px}.ops-action i,.ops-report-card i{color:#fff}.ops-tags{align-items:flex-start}.ops-tag,.ops-tags span{border:1px solid rgba(255,255,255,.12);color:#d8d8de;font-size:.68rem;font-weight:800;padding:7px 9px}.ops-checks{display:grid;gap:8px}.ops-check{padding:9px}.ops-check strong,.ops-report-card strong{color:#fff;display:block;font-size:.72rem;font-weight:900}.ops-check span{font-size:.62rem;letter-spacing:0;line-height:1.45;margin-top:4px;text-transform:none}.ops-report-grid{display:grid;gap:10px;grid-template-columns:repeat(4,1fr)}.ops-report-card{padding:14px}.ops-report-card strong{font-size:.82rem;margin-top:10px}.ops-report-card span{font-size:.62rem;letter-spacing:0;line-height:1.45;margin-top:6px;text-transform:none}.ops-filters{display:grid;gap:12px;grid-template-columns:repeat(5,1fr)}@media(max-width:1000px){.ops-strip,.ops-form,.ops-report-grid,.ops-filters{grid-template-columns:1fr}.ops-field.wide{grid-column:auto}.ops-actions{display:grid;grid-template-columns:1fr}.ops-action{width:100%}}
        .table-wrap{overflow-x:auto}.onyx-erp-body .table{border-collapse:collapse;min-width:760px;width:100%}.onyx-erp-body .table td,.onyx-erp-body .table th{border-bottom:1px solid rgba(255,255,255,.05);padding:13px 12px;text-align:left}
        .onyx-erp-body,.onyx-erp-body button,.onyx-erp-body input,.onyx-erp-body select,.onyx-erp-body textarea{font-family:Poppins,system-ui,sans-serif;font-size:12px}.onyx-erp-body *{border-radius:0!important}.page-header h1{font-size:20px;font-weight:900;letter-spacing:0}.page-subtitle{font-size:12px;line-height:1.5}.panel{padding:16px}.panel-title,.stat-card .label,.onyx-erp-body .table th{font-size:11px;font-weight:800;letter-spacing:0;text-transform:uppercase}.onyx-erp-body .table td{font-size:11px;line-height:1.45}.stat-card .value{font-size:18px}.stat-card .note{font-size:12px}.action-btn{font-size:12px;font-weight:800;min-height:38px}.nav-link-onyx,.nav-direct-link,.nav-dropdown-toggle,.nav-dropdown-menu .nav-link-onyx{font-family:Poppins,system-ui,sans-serif;font-size:11px}.ops-board{gap:14px}.ops-strip{gap:10px}.ops-card{padding:12px}.ops-card span,.ops-field label,.ops-section-title,.ops-check span,.ops-report-card span{font-size:11px;letter-spacing:0}.ops-card strong{font-size:16px}.ops-field input,.ops-field select,.ops-field textarea,.ops-filters input,.ops-filters select{font-size:12px;min-height:38px}.ops-btn,.ops-action{font-size:11px}.ops-tag,.ops-tags span{font-size:11px}.ops-check strong,.ops-report-card strong{font-size:11px}.ops-check span,.ops-report-card span{font-size:11px}.ops-report-card{padding:12px}
        .onyx-erp-body{background:linear-gradient(rgba(255,106,0,.026) 1px,transparent 1px),linear-gradient(90deg,rgba(255,106,0,.018) 1px,transparent 1px),radial-gradient(circle at 82% 0,rgba(255,106,0,.13),transparent 30%),linear-gradient(180deg,#07111a 0%,#050a10 100%)!important;background-size:42px 42px,42px 42px,auto,auto!important}.onyx-erp-body .sidebar{background:linear-gradient(180deg,rgba(255,106,0,.075),transparent 22%),linear-gradient(180deg,#07111a 0%,#050a10 100%)!important;border-right-color:rgba(255,106,0,.2)!important}.main-topbar{background:linear-gradient(90deg,rgba(255,106,0,.13),transparent 32%),#07111a!important;border-bottom-color:rgba(255,106,0,.22)!important}.sidebar-logo-plate,.topbar-brand img,.topbar-user-avatar,.sidebar-user-avatar,.topbar-sidebar-toggle{background:var(--onyx-accent)!important;border-color:var(--onyx-accent)!important;color:#050506!important}.nav-dropdown-toggle.active,.nav-dropdown-toggle:hover,.nav-link-onyx:hover,.nav-link-onyx.active{background:rgba(255,106,0,.1)!important;border-left-color:var(--onyx-accent)!important;color:#fff!important}.nav-link-onyx.active i,.nav-dropdown-toggle.active i,.nav-dropdown-toggle:hover i,.nav-link-onyx:hover i{color:var(--onyx-accent)!important}.topbar-search,.topbar-action,.topbar-icon,.topbar-chip,.topbar-user,.logout-btn{background:rgba(16,25,35,.82)!important;border-color:rgba(255,106,0,.18)!important}.panel,.onyx-erp-body .stat-card,.ops-card,.ops-action,.ops-check,.ops-report-card{background:linear-gradient(180deg,rgba(255,255,255,.035),rgba(255,255,255,.012)),var(--onyx-surface)!important;border-color:rgba(255,106,0,.18)!important;box-shadow:0 10px 28px rgba(0,0,0,.24)!important}.ops-btn,.ops-action.primary,.action-btn:hover{background:var(--onyx-accent)!important;border-color:var(--onyx-accent)!important;color:#050506!important}.ops-btn.ghost,.ops-action,.action-btn{background:rgba(255,255,255,.025)!important;border-color:rgba(255,106,0,.2)!important;color:#f5f7fa!important}.ops-field input,.ops-field select,.ops-field textarea,.ops-filters input,.ops-filters select{background:#0b141e!important;border-color:rgba(255,106,0,.18)!important;color:#fff!important}.ops-field input:focus,.ops-field select:focus,.ops-field textarea:focus{border-color:var(--onyx-accent)!important;box-shadow:0 0 0 3px rgba(255,106,0,.13)!important;outline:0}.onyx-erp-body .table tbody tr:hover{background:rgba(255,106,0,.055)!important}.accent-text,.action-btn i,.panel-title i,.currency-badge,.badge,.topbar-chip i,.topbar-action i{color:var(--onyx-accent)!important}@media(max-width:1180px){.stat-grid{grid-template-columns:repeat(2,minmax(150px,1fr))}.span-3,.span-4,.span-5,.span-6,.span-7,.span-8{grid-column:span 12}.main-topbar{align-items:stretch;flex-direction:column}.topbar-left,.topbar-right{width:100%}.topbar-brand{flex-basis:auto;width:100%}.topbar-search{width:100%}}@media(max-width:760px){.onyx-erp-body .sidebar{height:auto;position:static;width:100%}.main-content{margin-left:0;padding:20px}.main-topbar{margin:-20px -20px 24px;width:calc(100% + 40px)}.page-header{flex-direction:column}.stat-grid{grid-template-columns:1fr}.topbar-right{justify-content:flex-start}}
    </style>
</head>
<body class="onyx-erp-body">
    <div class="mobile-appbar">
        <a class="mobile-brand" href="<?= url('dashboard') ?>" aria-label="Onyx dashboard">
            <img src="<?= asset('assets/onxy logo.jpeg') ?>" alt="">
            <span>
                <strong>Onyx BCS</strong>
                <small><?= htmlspecialchars($context['company_name']) ?></small>
            </span>
        </a>
        <div class="mobile-appbar-actions">
            <a href="<?= url('notifications.php') ?>" aria-label="Notifications"><i class="fa-solid fa-bell"></i></a>
            <button type="button" data-mobile-more aria-label="Open menu"><i class="fa-solid fa-grip"></i></button>
        </div>
    </div>

    <div class="mobile-more-panel" data-mobile-more-panel aria-hidden="true">
        <div class="mobile-more-head">
            <strong>Modules</strong>
            <button type="button" data-mobile-more-close aria-label="Close menu"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <div class="mobile-more-grid">
            <a href="<?= url('customers.php') ?>"><i class="fa-solid fa-users"></i><span>Customers</span></a>
            <a href="<?= url('suppliers.php') ?>"><i class="fa-solid fa-truck"></i><span>Suppliers</span></a>
            <a href="<?= url('purchases.php') ?>"><i class="fa-solid fa-cart-shopping"></i><span>Purchases</span></a>
            <a href="<?= url('reports.php') ?>"><i class="fa-solid fa-chart-column"></i><span>Reports</span></a>
            <a href="<?= url('settings.php') ?>"><i class="fa-solid fa-sliders"></i><span>Settings</span></a>
            <a href="<?= route('settings.users') ?>"><i class="fa-solid fa-users-gear"></i><span>Users</span></a>
            <a href="<?= url('document_templates.php') ?>"><i class="fa-solid fa-file-lines"></i><span>Documents</span></a>
            <a href="<?= url('payroll.php') ?>"><i class="fa-solid fa-money-check-dollar"></i><span>Payroll</span></a>
        </div>
    </div>

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
                        if (($group['label'] ?? '') === 'Users & Roles Management') {
                            $groupActive = request()->is('settings/users*')
                                || request()->is('settings/roles*')
                                || request()->is('settings/security*')
                                || request()->is('settings/audit-logs*');
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
                                <?php
                                    $itemHref = $item['href'] ?? url($item['file']);
                                    $itemActive = $current_page === $item['file'];
                                    if (($group['label'] ?? '') === 'Users & Roles Management') {
                                        $itemActive = match ($item['file']) {
                                            'users.php' => request()->is('settings/users*'),
                                            'roles.php' => request()->is('settings/roles*'),
                                            'security.php' => request()->is('settings/security*'),
                                            'audit-logs.php' => request()->is('settings/audit-logs*'),
                                            default => false,
                                        };
                                    }
                                ?>
                                <a class="nav-link-onyx <?= $itemActive ? 'active' : '' ?>" href="<?= htmlspecialchars($itemHref) ?>" data-nav-link>
                                    <i class="fa-solid <?= htmlspecialchars($item['icon']) ?>"></i>
                                    <span><?= htmlspecialchars($item['label']) ?></span>
                                </a>
                                <?php if (($item['file'] ?? '') === 'settings.php'): ?>
                                <?php foreach ([
                                    'company' => ['label' => 'Company', 'icon' => 'fa-building'],
                                    'finance' => ['label' => 'Finance', 'icon' => 'fa-coins'],
                                    'organization' => ['label' => 'Organization', 'icon' => 'fa-users-gear'],
                                    'operations' => ['label' => 'Operations', 'icon' => 'fa-diagram-project'],
                                    'documents' => ['label' => 'Documents', 'icon' => 'fa-file-invoice'],
                                    'communications' => ['label' => 'Communications', 'icon' => 'fa-envelope-open-text'],
                                    'security_data' => ['label' => 'Security & Data', 'icon' => 'fa-shield-halved'],
                                    'system' => ['label' => 'System', 'icon' => 'fa-server'],
                                ] as $anchor => $section): ?>
                                    <a class="nav-link-onyx" href="<?= url('settings.php') ?>?section=<?= htmlspecialchars($anchor) ?>" data-nav-link>
                                        <i class="fa-solid <?= htmlspecialchars($section['icon']) ?>"></i>
                                        <span><?= htmlspecialchars($section['label']) ?></span>
                                    </a>
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
            const csrfToken = '<?= csrf_token() ?>';
            const collapseKey = 'onyx_sidebar_collapsed';
            const collapseButton = document.querySelector('[data-sidebar-collapse]');

            function syncSidebarCollapse(collapsed) {
                document.body.classList.toggle('sidebar-collapsed', collapsed);
                if (collapseButton) {
                    collapseButton.classList.toggle('active', collapsed);
                    collapseButton.setAttribute('aria-pressed', collapsed ? 'true' : 'false');
                    collapseButton.setAttribute('aria-label', collapsed ? 'Open sidebar' : 'Collapse sidebar');
                    collapseButton.setAttribute('title', collapsed ? 'Open sidebar' : 'Collapse sidebar');
                }
            }

            syncSidebarCollapse(localStorage.getItem(collapseKey) === '1');

            if (collapseButton) {
                collapseButton.addEventListener('click', function () {
                    const collapsed = !document.body.classList.contains('sidebar-collapsed');
                    localStorage.setItem(collapseKey, collapsed ? '1' : '0');
                    syncSidebarCollapse(collapsed);
                });
            }

            document.querySelectorAll('form').forEach(function (form) {
                const method = (form.getAttribute('method') || 'GET').toUpperCase();
                if (method === 'POST' && !form.querySelector('input[name="_token"]')) {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = '_token';
                    input.value = csrfToken;
                    form.prepend(input);
                }
            });

            if (sidebar) {
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
        });
    </script>
    <main class="main-content">
        <div class="main-topbar">
            <div class="topbar-left">
                <button class="topbar-sidebar-toggle" type="button" data-sidebar-collapse aria-label="Toggle sidebar" aria-pressed="false" title="Toggle sidebar">
                    <i class="fa-solid fa-chevron-left"></i>
                </button>
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
        ?>
        </main>
        <nav class="mobile-bottom-nav" aria-label="Primary mobile navigation">
            <a href="<?= url('dashboard.php') ?>" class="<?= onyx_current_page() === 'dashboard.php' ? 'active' : '' ?>"><i class="fa-solid fa-gauge-high"></i><span>Home</span></a>
            <a href="<?= url('sales.php') ?>" class="<?= in_array(onyx_current_page(), ['sales.php', 'sales_action.php', 'pos.php'], true) ? 'active' : '' ?>"><i class="fa-solid fa-cash-register"></i><span>Sales</span></a>
            <a href="<?= url('products.php') ?>" class="<?= in_array(onyx_current_page(), ['products.php', 'products_action.php', 'inventory.php'], true) ? 'active' : '' ?>"><i class="fa-solid fa-boxes-stacked"></i><span>Stock</span></a>
            <a href="<?= url('accounting.php') ?>" class="<?= in_array(onyx_current_page(), ['accounting.php', 'banking.php', 'budgets.php'], true) ? 'active' : '' ?>"><i class="fa-solid fa-scale-balanced"></i><span>Finance</span></a>
            <a href="<?= url('human_resources.php') ?>" class="<?= str_starts_with(onyx_current_page(), 'hr_') || onyx_current_page() === 'human_resources.php' ? 'active' : '' ?>"><i class="fa-solid fa-users-gear"></i><span>HR</span></a>
            <button type="button" data-mobile-more><i class="fa-solid fa-ellipsis"></i><span>More</span></button>
        </nav>
        <a class="mobile-fab" href="<?= url('pos.php') ?>" aria-label="New sale"><i class="fa-solid fa-plus"></i><span>Sale</span></a>
        </body></html>
        <?php
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
