<?php
/**
 * Shared ONYX Accounting System ERP layout helpers.
 */

function onyx_session_name_from_cookie(): ?string
{
    foreach ($_COOKIE as $cookieName => $cookieValue) {
        if (preg_match('/^Onyx_\d+$/', $cookieName)) {
            return $cookieName;
        }
    }

    return null;
}

function onyx_start_session(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        $tenant_id = null;

        if (isset($_GET['tenant_id']) && ctype_digit((string)$_GET['tenant_id'])) {
            $tenant_id = $_GET['tenant_id'];
        } elseif (isset($_POST['tenant_id']) && ctype_digit((string)$_POST['tenant_id'])) {
            $tenant_id = $_POST['tenant_id'];
        }

        if ($tenant_id !== null) {
            session_name('Onyx_' . $tenant_id);
        } elseif (($cookieName = onyx_session_name_from_cookie()) !== null) {
            session_name($cookieName);
        }

        session_start();
    }

    if (!isset($_SESSION['user_id']) || !isset($_SESSION['tenant_id'])) {
        header('Location: login.php?error=' . urlencode('Session expired or unauthorized access context.'));
        exit();
    }
}

function onyx_context(): array
{
    return [
        'company_name' => $_SESSION['company_name'] ?? 'Isolated Workspace',
        'user_name' => $_SESSION['user_name'] ?? 'Operator',
        'currency' => $_SESSION['currency'] ?? 'UGX',
    ];
}

function onyx_db(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    if (!defined('ONYX_SKIP_AUTO_CONNECT')) {
        define('ONYX_SKIP_AUTO_CONNECT', true);
    }
    require_once __DIR__ . '/../config.php';

    $pdo = onyx_create_pdo();
    return $pdo;
}

function onyx_tenant_id(): int
{
    onyx_start_session();
    return (int) $_SESSION['tenant_id'];
}

function onyx_money(float $amount, string $currency): string
{
    return number_format($amount, 2) . ' ' . $currency;
}

function onyx_scalar(string $sql, array $params = [], mixed $default = 0): mixed
{
    $stmt = onyx_db()->prepare($sql);
    $stmt->execute($params);
    $value = $stmt->fetchColumn();

    return $value === false || $value === null ? $default : $value;
}

function onyx_rows(string $sql, array $params = []): array
{
    $stmt = onyx_db()->prepare($sql);
    $stmt->execute($params);

    return $stmt->fetchAll();
}

function onyx_row(string $sql, array $params = []): array|false
{
    $stmt = onyx_db()->prepare($sql);
    $stmt->execute($params);

    return $stmt->fetch();
}

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

function onyx_page_start(string $title, string $subtitle = ''): array
{
    onyx_start_session();
    $context = onyx_context();
    $current_page = basename($_SERVER['PHP_SELF']);
    $nav_query = '?tenant_id=' . urlencode((string) $_SESSION['tenant_id']);
    $currency = $context['currency'];
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title) ?> - ONYX ACCOUNTING SYSTEM</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --onyx-bg: #12121a;
            --onyx-surface: #1c1c27;
            --onyx-accent: #ff6b00;
            --onyx-border: #2a2a3b;
            --onyx-text: #ffffff;
            --onyx-muted: #84849a;
        }
        * {
            box-sizing: border-box;
        }
        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--onyx-bg);
            color: var(--onyx-text);
            margin: 0;
            overflow-x: hidden;
        }
        a {
            color: inherit;
            text-decoration: none;
        }
        .sidebar {
            width: 260px;
            background-color: var(--onyx-surface);
            border-right: 1px solid var(--onyx-border);
            height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            display: flex;
            flex-direction: column;
            z-index: 1000;
        }
        .sidebar-header {
            padding: 20px 15px;
            border-bottom: 1px solid var(--onyx-border);
        }
        .brand-title {
            font-size: 1.05rem;
            font-weight: 800;
            letter-spacing: 0.5px;
            line-height: 1.35;
            margin: 0 0 8px;
        }
        .accent-text {
            color: var(--onyx-accent);
        }
        .sidebar-meta,
        .muted {
            color: var(--onyx-muted);
        }
        .sidebar-menu-wrapper {
            overflow-y: auto;
            flex-grow: 1;
            padding: 10px 0;
        }
        .sidebar-menu-wrapper::-webkit-scrollbar {
            width: 4px;
        }
        .sidebar-menu-wrapper::-webkit-scrollbar-track {
            background: var(--onyx-surface);
        }
        .sidebar-menu-wrapper::-webkit-scrollbar-thumb {
            background: var(--onyx-border);
            border-radius: 4px;
        }
        .sidebar-footer {
            padding: 15px;
            border-top: 1px solid var(--onyx-border);
            background-color: rgba(0, 0, 0, 0.1);
        }
        .nav-link-onyx {
            color: var(--onyx-muted);
            padding: 11px 20px;
            display: flex;
            align-items: center;
            gap: 12px;
            font-weight: 400;
            font-size: 0.88rem;
            transition: all 0.2s ease;
            border-left: 3px solid transparent;
            width: 100%;
            background: transparent;
            border: none;
            text-align: left;
            cursor: pointer;
        }
        .nav-link-onyx i {
            width: 20px;
            text-align: center;
            font-size: 1rem;
        }
        .nav-link-onyx:hover,
        .nav-link-onyx.active,
        .nav-dropdown-toggle.active {
            color: var(--onyx-text);
            background-color: rgba(255, 107, 0, 0.05);
            border-left-color: var(--onyx-accent);
        }
        .nav-dropdown {
            display: flex;
            flex-direction: column;
        }
        .nav-dropdown-toggle {
            justify-content: space-between;
        }
        .nav-dropdown-caret {
            margin-left: auto;
            font-size: 0.8rem;
            transition: transform 0.2s ease;
        }
        .nav-dropdown-caret.open {
            transform: rotate(180deg);
        }
        .nav-dropdown-menu {
            display: none;
            flex-direction: column;
            padding-left: 20px;
            padding-bottom: 6px;
        }
        .nav-dropdown-menu.open {
            display: flex;
        }
        .nav-dropdown-menu .nav-link-onyx {
            padding: 8px 16px;
            font-size: 0.82rem;
            border-left-color: transparent;
        }
        .nav-dropdown-menu .nav-link-onyx:hover,
        .nav-dropdown-menu .nav-link-onyx.active {
            border-left-color: var(--onyx-accent);
        }
        .logout-btn {
            border: 1px solid rgba(220, 53, 69, 0.7);
            color: #ff7b86;
            border-radius: 8px;
            display: block;
            font-size: 0.78rem;
            font-weight: 600;
            padding: 8px 10px;
            text-align: center;
        }
        .main-content {
            margin-left: 260px;
            padding: 34px;
            min-height: 100vh;
        }
        .page-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 20px;
            margin-bottom: 26px;
        }
        h1,
        h2,
        h3,
        p {
            margin-top: 0;
        }
        .page-header h1 {
            font-size: 1.75rem;
            margin-bottom: 6px;
        }
        .page-subtitle {
            color: var(--onyx-muted);
            font-size: 0.88rem;
            margin-bottom: 0;
        }
        .currency-badge {
            border: 1px solid var(--onyx-border);
            border-radius: 8px;
            color: var(--onyx-accent);
            font-family: monospace;
            font-size: 0.78rem;
            letter-spacing: 0.6px;
            padding: 10px 12px;
            text-transform: uppercase;
            white-space: nowrap;
        }
        .stat-grid {
            display: grid;
            grid-template-columns: repeat(5, minmax(150px, 1fr));
            gap: 16px;
            margin-bottom: 22px;
        }
        .module-grid {
            display: grid;
            grid-template-columns: repeat(12, 1fr);
            gap: 18px;
            margin-bottom: 22px;
        }
        .span-3 {
            grid-column: span 3;
        }
        .span-4 {
            grid-column: span 4;
        }
        .span-5 {
            grid-column: span 5;
        }
        .span-6 {
            grid-column: span 6;
        }
        .span-7 {
            grid-column: span 7;
        }
        .span-8 {
            grid-column: span 8;
        }
        .span-12 {
            grid-column: span 12;
        }
        .stat-card,
        .panel {
            background-color: var(--onyx-surface);
            border: 1px solid var(--onyx-border);
            border-radius: 8px;
        }
        .stat-card {
            min-height: 118px;
            padding: 20px;
        }
        .stat-card .label,
        .panel-title,
        .table th {
            color: var(--onyx-muted);
            font-size: 0.72rem;
            font-weight: 700;
            letter-spacing: 0.8px;
            text-transform: uppercase;
        }
        .stat-card .value {
            color: var(--onyx-text);
            font-size: 1.45rem;
            font-weight: 800;
            line-height: 1.2;
            margin-top: 12px;
        }
        .stat-card .note {
            color: var(--onyx-muted);
            font-size: 0.76rem;
            margin-top: 8px;
        }
        .panel {
            padding: 20px;
        }
        .panel-title {
            display: flex;
            align-items: center;
            gap: 9px;
            margin-bottom: 16px;
        }
        .panel-title i {
            color: var(--onyx-accent);
        }
        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 10px;
        }
        .action-btn {
            align-items: center;
            background: rgba(255, 107, 0, 0.06);
            border: 1px solid var(--onyx-border);
            border-radius: 8px;
            color: var(--onyx-text);
            display: flex;
            font-size: 0.84rem;
            font-weight: 600;
            gap: 10px;
            padding: 12px;
        }
        .action-btn i {
            color: var(--onyx-accent);
            width: 18px;
        }
        .chart-shell {
            min-height: 230px;
            display: flex;
            align-items: center;
            justify-content: center;
            padding-top: 10px;
        }
        .chart-svg {
            width: 100%;
            max-width: 100%;
            height: 220px;
        }
        .chart-list,
        .clean-list {
            display: grid;
            gap: 12px;
            list-style: none;
            margin: 0;
            padding: 0;
        }
        .chart-list li,
        .clean-list li {
            align-items: center;
            border-bottom: 1px solid rgba(255, 255, 255, 0.04);
            display: flex;
            justify-content: space-between;
            gap: 14px;
            padding-bottom: 10px;
        }
        .chart-list li:last-child,
        .clean-list li:last-child {
            border-bottom: 0;
            padding-bottom: 0;
        }
        .badge {
            border: 1px solid var(--onyx-border);
            border-radius: 999px;
            color: var(--onyx-accent);
            display: inline-flex;
            font-size: 0.7rem;
            font-weight: 700;
            padding: 5px 9px;
            white-space: nowrap;
        }
        .table-wrap {
            overflow-x: auto;
        }
        .table {
            border-collapse: collapse;
            min-width: 760px;
            width: 100%;
        }
        .table th,
        .table td {
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            padding: 13px 12px;
            text-align: left;
        }
        .table td {
            color: var(--onyx-text);
            font-size: 0.84rem;
        }
        .profile-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 12px;
        }
        .profile-card {
            border: 1px solid var(--onyx-border);
            border-radius: 8px;
            padding: 14px;
        }
        .profile-card strong {
            display: block;
            font-size: 0.88rem;
            margin-bottom: 5px;
        }
        .profile-card span {
            color: var(--onyx-muted);
            font-size: 0.78rem;
        }
        .pos-layout {
            display: grid;
            grid-template-columns: minmax(0, 1fr) 360px;
            gap: 18px;
        }
        .product-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(130px, 1fr));
            gap: 12px;
        }
        .product-tile {
            border: 1px solid var(--onyx-border);
            border-radius: 8px;
            min-height: 105px;
            padding: 14px;
        }
        .input-like {
            background: rgba(0, 0, 0, 0.14);
            border: 1px solid var(--onyx-border);
            border-radius: 8px;
            color: var(--onyx-muted);
            padding: 12px 14px;
            width: 100%;
        }
        @media (max-width: 1180px) {
            .stat-grid {
                grid-template-columns: repeat(2, minmax(150px, 1fr));
            }
            .span-3,
            .span-4,
            .span-5,
            .span-6,
            .span-7,
            .span-8 {
                grid-column: span 12;
            }
            .pos-layout {
                grid-template-columns: 1fr;
            }
        }
        @media (max-width: 760px) {
            .sidebar {
                position: static;
                width: 100%;
                height: auto;
            }
            .sidebar-menu-wrapper {
                max-height: none;
            }
            .main-content {
                margin-left: 0;
                padding: 20px;
            }
            .page-header {
                flex-direction: column;
            }
            .stat-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <aside class="sidebar">
        <div class="sidebar-header">
            <h4 class="brand-title">ONYX <span class="accent-text">ACCOUNTING SYSTEM</span></h4>
            <div class="sidebar-meta" title="<?= htmlspecialchars($context['company_name']) ?>">
                <i class="fa-solid fa-building"></i> <?= htmlspecialchars($context['company_name']) ?>
            </div>
        </div>
        <div class="sidebar-menu-wrapper">
            <nav>
                <?php foreach (onyx_nav_items() as $item): ?>
                    <?php if (($item['file'] ?? '') === 'settings.php'): ?>
                        <div class="nav-dropdown">
                            <button class="nav-link-onyx nav-dropdown-toggle <?= $current_page === 'settings.php' ? 'active' : '' ?>" type="button" data-target="settings-submenu">
                                <span><i class="fa-solid <?= htmlspecialchars($item['icon']) ?>"></i> <?= htmlspecialchars($item['label']) ?></span>
                                <i class="fa-solid fa-chevron-down nav-dropdown-caret"></i>
                            </button>
                            <div class="nav-dropdown-menu" id="settings-submenu">
                                <a class="nav-link-onyx" href="settings.php<?= htmlspecialchars($nav_query) ?>#company"><span><i class="fa-solid fa-building"></i> Company</span></a>
                                <a class="nav-link-onyx" href="settings.php<?= htmlspecialchars($nav_query) ?>#accounting"><span><i class="fa-solid fa-calculator"></i> Accounting</span></a>
                                <a class="nav-link-onyx" href="settings.php<?= htmlspecialchars($nav_query) ?>#tax"><span><i class="fa-solid fa-percent"></i> Tax</span></a>
                                <a class="nav-link-onyx" href="settings.php<?= htmlspecialchars($nav_query) ?>#users_roles"><span><i class="fa-solid fa-users-cog"></i> Users & Roles</span></a>
                                <a class="nav-link-onyx" href="settings.php<?= htmlspecialchars($nav_query) ?>#branches"><span><i class="fa-solid fa-code-branch"></i> Branches</span></a>
                                <a class="nav-link-onyx" href="settings.php<?= htmlspecialchars($nav_query) ?>#warehouses"><span><i class="fa-solid fa-warehouse"></i> Warehouses</span></a>
                                <a class="nav-link-onyx" href="settings.php<?= htmlspecialchars($nav_query) ?>#products"><span><i class="fa-solid fa-box-open"></i> Products</span></a>
                                <a class="nav-link-onyx" href="settings.php<?= htmlspecialchars($nav_query) ?>#payment_methods"><span><i class="fa-solid fa-credit-card"></i> Payment Methods</span></a>
                                <a class="nav-link-onyx" href="settings.php<?= htmlspecialchars($nav_query) ?>#invoice_settings"><span><i class="fa-solid fa-file-invoice-dollar"></i> Invoice Settings</span></a>
                                <a class="nav-link-onyx" href="settings.php<?= htmlspecialchars($nav_query) ?>#receipt_settings"><span><i class="fa-solid fa-receipt"></i> Receipt Settings</span></a>
                                <a class="nav-link-onyx" href="settings.php<?= htmlspecialchars($nav_query) ?>#quotation_settings"><span><i class="fa-solid fa-file-signature"></i> Quotation Settings</span></a>
                                <a class="nav-link-onyx" href="settings.php<?= htmlspecialchars($nav_query) ?>#notifications"><span><i class="fa-solid fa-bell"></i> Notifications</span></a>
                                <a class="nav-link-onyx" href="settings.php<?= htmlspecialchars($nav_query) ?>#email_smtp"><span><i class="fa-solid fa-envelope"></i> Email (SMTP)</span></a>
                                <a class="nav-link-onyx" href="settings.php<?= htmlspecialchars($nav_query) ?>#security"><span><i class="fa-solid fa-shield-alt"></i> Security</span></a>
                                <a class="nav-link-onyx" href="settings.php<?= htmlspecialchars($nav_query) ?>#audit_logs"><span><i class="fa-solid fa-clipboard-list"></i> Audit Logs</span></a>
                                <a class="nav-link-onyx" href="settings.php<?= htmlspecialchars($nav_query) ?>#financial_periods"><span><i class="fa-solid fa-calendar-check"></i> Financial Periods</span></a>
                                <a class="nav-link-onyx" href="settings.php<?= htmlspecialchars($nav_query) ?>#backup_restore"><span><i class="fa-solid fa-database"></i> Backup & Restore</span></a>
                                <a class="nav-link-onyx" href="settings.php<?= htmlspecialchars($nav_query) ?>#import_export"><span><i class="fa-solid fa-file-import"></i> Import & Export</span></a>
                                <a class="nav-link-onyx" href="settings.php<?= htmlspecialchars($nav_query) ?>#system_info"><span><i class="fa-solid fa-info-circle"></i> System Information</span></a>
                            </div>
                        </div>
                    <?php else: ?>
                        <a class="nav-link-onyx <?= $current_page === $item['file'] ? 'active' : '' ?>" href="<?= htmlspecialchars($item['file'] . $nav_query) ?>">
                            <i class="fa-solid <?= htmlspecialchars($item['icon']) ?>"></i>
                            <span><?= htmlspecialchars($item['label']) ?></span>
                        </a>
                    <?php endif; ?>
                <?php endforeach; ?>
            </nav>
        </div>
        <div class="sidebar-footer">
            <div class="sidebar-meta" style="font-size: 0.8rem; margin-bottom: 10px;">
                <i class="fa-solid fa-user"></i> <?= htmlspecialchars($context['user_name']) ?>
            </div>
            <a href="logout.php" class="logout-btn">Secure Logout</a>
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
                    // Only close other menus inside the same sidebar
                    sidebar.querySelectorAll('.nav-dropdown-menu.open').forEach(function (openMenu) {
                        if (openMenu !== menu) {
                            openMenu.classList.remove('open');
                            const relatedButton = sidebar.querySelector('[data-target="' + openMenu.id + '"]');
                            if (relatedButton) {
                                const rbCaret = relatedButton.querySelector('.nav-dropdown-caret');
                                if (rbCaret) rbCaret.classList.remove('open');
                            }
                        }
                    });

                    menu.classList.toggle('open', !isOpen);
                    if (caret) caret.classList.toggle('open', !isOpen);
                });
            });
        });
    </script>

    <main class="main-content">
        <div class="page-header">
            <div>
                <h1><?= htmlspecialchars($title) ?></h1>
                <?php if ($subtitle !== ''): ?>
                    <p class="page-subtitle"><?= htmlspecialchars($subtitle) ?></p>
                <?php endif; ?>
            </div>
            <div class="currency-badge">Base Currency: <?= htmlspecialchars($currency) ?></div>
        </div>
    <?php
    return $context;
}

function onyx_page_end(): void
{
    ?>
    </main>
</body>
</html>
    <?php
}

function onyx_stat_card(string $label, string $value, string $note = ''): void
{
    ?>
    <div class="stat-card">
        <div class="label"><?= htmlspecialchars($label) ?></div>
        <div class="value"><?= htmlspecialchars($value) ?></div>
        <?php if ($note !== ''): ?>
            <div class="note"><?= htmlspecialchars($note) ?></div>
        <?php endif; ?>
    </div>
    <?php
}

function onyx_panel_start(string $title, string $icon = 'fa-circle-dot', string $span = 'span-6'): void
{
    ?>
    <section class="panel <?= htmlspecialchars($span) ?>">
        <div class="panel-title"><i class="fa-solid <?= htmlspecialchars($icon) ?>"></i> <?= htmlspecialchars($title) ?></div>
    <?php
}

function onyx_panel_end(): void
{
    echo '</section>';
}

function onyx_table(array $headers, array $rows): void
{
    ?>
    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr>
                    <?php foreach ($headers as $header): ?>
                        <th><?= htmlspecialchars($header) ?></th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php if ($rows === []): ?>
                    <tr>
                        <td colspan="<?= count($headers) ?>" class="muted">No database records found for this workspace.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($rows as $row): ?>
                        <tr>
                            <?php foreach ($row as $cell): ?>
                                <td><?= htmlspecialchars((string) $cell) ?></td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php
}

function onyx_table_html(array $headers, array $rows): void
{
    ?>
    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr>
                    <?php foreach ($headers as $header): ?>
                        <th><?= htmlspecialchars($header) ?></th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php if ($rows === []): ?>
                    <tr>
                        <td colspan="<?= count($headers) ?>" class="muted">No database records found for this workspace.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($rows as $row): ?>
                        <tr>
                            <?php foreach ($row as $cell): ?>
                                <?php if (is_array($cell) && isset($cell['raw']) && $cell['raw'] === true): ?>
                                    <td><?= $cell['value'] ?></td>
                                <?php else: ?>
                                    <td><?= htmlspecialchars((string) $cell) ?></td>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php
}

function onyx_action_grid(array $actions): void
{
    ?>
    <div class="quick-actions">
        <?php foreach ($actions as $action): ?>
            <a class="action-btn" href="<?= htmlspecialchars($action['href'] ?? '#') ?>">
                <i class="fa-solid <?= htmlspecialchars($action['icon'] ?? 'fa-circle-dot') ?>"></i>
                <span><?= htmlspecialchars($action['label']) ?></span>
            </a>
        <?php endforeach; ?>
    </div>
    <?php
}

function onyx_clean_list(array $items): void
{
    ?>
    <ul class="clean-list">
        <?php foreach ($items as $item): ?>
            <li>
                <span><?= htmlspecialchars($item[0]) ?></span>
                <span class="badge"><?= htmlspecialchars($item[1]) ?></span>
            </li>
        <?php endforeach; ?>
    </ul>
    <?php
}
?>
