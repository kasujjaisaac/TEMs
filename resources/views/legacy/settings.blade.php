<?php

$tenant_id = (int) onyx_tenant_id();
$pdo = onyx_db();

function admin_h(mixed $value): string
{
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
}

function admin_redirect(string $message = '', bool $success = true, string $anchor = 'overview'): void
{
    $params = [];
    $params['section'] = $anchor;
    if ($message !== '') {
        $params[$success ? 'success' : 'error'] = $message;
    }
    header('Location: settings.php?' . http_build_query($params));
    exit();
}

function admin_url(string $section): string
{
    return onyx_legacy_url('settings.php?section=' . rawurlencode($section));
}

function admin_columns(PDO $pdo, string $table): array
{
    try {
        return array_map(static fn (array $row): string => $row['Field'], $pdo->query('SHOW COLUMNS FROM `' . str_replace('`', '``', $table) . '`')->fetchAll(PDO::FETCH_ASSOC));
    } catch (Throwable) {
        return [];
    }
}

function admin_ensure_column(PDO $pdo, string $table, string $column, string $definition): void
{
    if (! in_array($column, admin_columns($pdo, $table), true)) {
        $pdo->exec('ALTER TABLE `' . str_replace('`', '``', $table) . '` ADD COLUMN `' . str_replace('`', '``', $column) . '` ' . $definition);
    }
}

function admin_ensure_schema(PDO $pdo, int $tenantId): void
{
    $pdo->exec("CREATE TABLE IF NOT EXISTS tenants (
        id BIGINT(20) NOT NULL AUTO_INCREMENT,
        company_name VARCHAR(255) NOT NULL DEFAULT 'Company',
        slug VARCHAR(155) DEFAULT NULL,
        currency VARCHAR(10) DEFAULT 'UGX',
        tin_number VARCHAR(100) DEFAULT NULL,
        address TEXT DEFAULT NULL,
        phone VARCHAR(80) DEFAULT NULL,
        email VARCHAR(155) DEFAULT NULL,
        fiscal_year_start DATE DEFAULT NULL,
        status VARCHAR(40) DEFAULT 'active',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    foreach ([
        'slug' => 'VARCHAR(155) DEFAULT NULL',
        'currency' => "VARCHAR(10) DEFAULT 'UGX'",
        'tin_number' => 'VARCHAR(100) DEFAULT NULL',
        'address' => 'TEXT DEFAULT NULL',
        'phone' => 'VARCHAR(80) DEFAULT NULL',
        'email' => 'VARCHAR(155) DEFAULT NULL',
        'fiscal_year_start' => 'DATE DEFAULT NULL',
        'status' => "VARCHAR(40) DEFAULT 'active'",
        'updated_at' => 'DATETIME DEFAULT NULL',
    ] as $column => $definition) {
        admin_ensure_column($pdo, 'tenants', $column, $definition);
    }

    $exists = (int) onyx_scalar('SELECT COUNT(*) FROM tenants WHERE id = :id', ['id' => $tenantId], 0);
    if ($exists === 0) {
        $pdo->prepare('INSERT INTO tenants (id, company_name, slug, currency, status, created_at, updated_at) VALUES (?, ?, ?, ?, ?, NOW(), NOW())')
            ->execute([$tenantId, 'Onyx BCS', 'onyx-bcs', 'UGX', 'active']);
    }

    $pdo->exec("CREATE TABLE IF NOT EXISTS tenant_settings (
        tenant_id BIGINT(20) NOT NULL,
        setting_key VARCHAR(120) NOT NULL,
        setting_value TEXT DEFAULT NULL,
        PRIMARY KEY (tenant_id, setting_key)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    $pdo->exec("CREATE TABLE IF NOT EXISTS company_branches (
        id BIGINT(20) NOT NULL AUTO_INCREMENT,
        tenant_id BIGINT(20) NOT NULL,
        name VARCHAR(155) NOT NULL,
        code VARCHAR(50) DEFAULT NULL,
        manager VARCHAR(155) DEFAULT NULL,
        phone VARCHAR(80) DEFAULT NULL,
        email VARCHAR(155) DEFAULT NULL,
        address TEXT DEFAULT NULL,
        is_active TINYINT(1) NOT NULL DEFAULT 1,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY idx_company_branch_tenant (tenant_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    $pdo->exec("CREATE TABLE IF NOT EXISTS inventory_warehouses (
        id BIGINT(20) NOT NULL AUTO_INCREMENT,
        tenant_id BIGINT(20) NOT NULL,
        name VARCHAR(155) NOT NULL,
        location VARCHAR(255) DEFAULT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY idx_inventory_warehouse_tenant (tenant_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    admin_ensure_column($pdo, 'inventory_warehouses', 'manager', 'VARCHAR(155) DEFAULT NULL');
    admin_ensure_column($pdo, 'inventory_warehouses', 'is_active', 'TINYINT(1) NOT NULL DEFAULT 1');
    admin_ensure_column($pdo, 'inventory_warehouses', 'updated_at', 'DATETIME DEFAULT NULL');

    $pdo->exec("CREATE TABLE IF NOT EXISTS payment_methods (
        id BIGINT(20) NOT NULL AUTO_INCREMENT,
        tenant_id BIGINT(20) NOT NULL,
        name VARCHAR(155) NOT NULL,
        method_type VARCHAR(50) DEFAULT 'cash',
        account_name VARCHAR(155) DEFAULT NULL,
        account_number VARCHAR(155) DEFAULT NULL,
        is_active TINYINT(1) NOT NULL DEFAULT 1,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY idx_payment_method_tenant (tenant_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    $pdo->exec("CREATE TABLE IF NOT EXISTS admin_audit_logs (
        id BIGINT(20) NOT NULL AUTO_INCREMENT,
        tenant_id BIGINT(20) NOT NULL,
        user_name VARCHAR(155) DEFAULT NULL,
        action VARCHAR(155) NOT NULL,
        details TEXT DEFAULT NULL,
        ip_address VARCHAR(60) DEFAULT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY idx_admin_audit_tenant (tenant_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    foreach ([
        'tenant_id' => 'BIGINT(20) DEFAULT NULL',
        'role' => "VARCHAR(50) DEFAULT 'user'",
        'is_active' => 'TINYINT(1) NOT NULL DEFAULT 1',
    ] as $column => $definition) {
        admin_ensure_column($pdo, 'users', $column, $definition);
    }

    $warehouseCount = (int) onyx_scalar('SELECT COUNT(*) FROM inventory_warehouses WHERE tenant_id = :tenant_id', ['tenant_id' => $tenantId], 0);
    if ($warehouseCount === 0) {
        $pdo->prepare('INSERT INTO inventory_warehouses (tenant_id, name, location, manager, is_active, created_at, updated_at) VALUES (?, ?, ?, ?, 1, NOW(), NOW())')
            ->execute([$tenantId, 'Main Warehouse', 'Head Office', 'Operations']);
    }

    $methodCount = (int) onyx_scalar('SELECT COUNT(*) FROM payment_methods WHERE tenant_id = :tenant_id', ['tenant_id' => $tenantId], 0);
    if ($methodCount === 0) {
        $insert = $pdo->prepare('INSERT INTO payment_methods (tenant_id, name, method_type, is_active, created_at, updated_at) VALUES (?, ?, ?, 1, NOW(), NOW())');
        foreach ([['Cash', 'cash'], ['Bank Transfer', 'bank'], ['Mobile Money', 'mobile_money'], ['Card', 'card']] as $method) {
            $insert->execute([$tenantId, $method[0], $method[1]]);
        }
    }
}

function admin_settings(PDO $pdo, int $tenantId): array
{
    $rows = onyx_rows('SELECT setting_key, setting_value FROM tenant_settings WHERE tenant_id = :tenant_id', ['tenant_id' => $tenantId]);
    return array_column($rows, 'setting_value', 'setting_key');
}

function admin_save_settings(PDO $pdo, int $tenantId, array $settings): void
{
    $stmt = $pdo->prepare('INSERT INTO tenant_settings (tenant_id, setting_key, setting_value) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)');
    foreach ($settings as $key => $value) {
        $stmt->execute([$tenantId, $key, is_array($value) ? json_encode($value) : (string) $value]);
    }
}

function admin_setting(array $settings, string $key, string $default = ''): string
{
    return (string) ($settings[$key] ?? $default);
}

function admin_audit(PDO $pdo, int $tenantId, string $action, string $details = ''): void
{
    $pdo->prepare('INSERT INTO admin_audit_logs (tenant_id, user_name, action, details, ip_address, created_at) VALUES (?, ?, ?, ?, ?, NOW())')
        ->execute([$tenantId, session('user_name', 'Operator'), $action, $details, request()->ip()]);
}

admin_ensure_schema($pdo, $tenant_id);
$currentTenant = onyx_row('SELECT * FROM tenants WHERE id = :id LIMIT 1', ['id' => $tenant_id]) ?: [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_permission('manage_products');
    $action = $_POST['action'] ?? '';

    if ($action === 'save_administration') {
        $section = trim($_POST['section'] ?? 'overview') ?: 'overview';
        $companyName = trim($_POST['company_name'] ?? ($currentTenant['company_name'] ?? ''));
        if ($companyName === '') {
            admin_redirect('Company name is required.', false, 'company');
        }
        $currency = strtoupper(trim($_POST['currency'] ?? ($currentTenant['currency'] ?? 'UGX'))) ?: 'UGX';
        $pdo->prepare('UPDATE tenants SET company_name = ?, slug = ?, currency = ?, tin_number = ?, address = ?, phone = ?, email = ?, fiscal_year_start = ?, status = ?, updated_at = NOW() WHERE id = ?')
            ->execute([
                $companyName,
                trim($_POST['company_slug'] ?? ($currentTenant['slug'] ?? '')),
                $currency,
                trim($_POST['tin_number'] ?? ($currentTenant['tin_number'] ?? '')),
                trim($_POST['company_address'] ?? ($currentTenant['address'] ?? '')),
                trim($_POST['company_phone'] ?? ($currentTenant['phone'] ?? '')),
                trim($_POST['company_email'] ?? ($currentTenant['email'] ?? '')),
                trim($_POST['fiscal_year_start'] ?? ($currentTenant['fiscal_year_start'] ?? '')) ?: null,
                trim($_POST['tenant_status'] ?? ($currentTenant['status'] ?? 'active')),
                $tenant_id,
            ]);

        $settingKeys = [
            'company_logo', 'company_tagline', 'company_profile', 'company_mission', 'company_vision', 'company_website', 'company_country',
            'business_registration_number', 'vat_registration_number', 'financial_year_label', 'timezone', 'date_format', 'time_format',
            'default_vat_rate', 'vat_inclusive_pricing', 'withholding_tax_rate', 'invoice_tax_label', 'default_income_account', 'default_expense_account',
            'cash_account', 'bank_account', 'accounts_receivable_account', 'accounts_payable_account',
            'invoice_prefix', 'receipt_prefix', 'quotation_prefix', 'purchase_prefix', 'next_invoice_number', 'next_receipt_number', 'next_quotation_number', 'next_purchase_number',
            'invoice_footer', 'receipt_footer', 'quotation_terms', 'purchase_terms', 'payment_due_days',
            'invoice_accent_color', 'quotation_accent_color', 'receipt_accent_color', 'document_logo_size', 'document_show_signature', 'document_show_discount',
            'document_payment_account_name', 'document_payment_account_number', 'document_payment_bank_name', 'document_payment_mobile_money', 'document_payment_methods_note',
            'stock_negative_sales', 'low_stock_alerts', 'default_reorder_level', 'default_warehouse_policy',
            'email_from_name', 'email_from_address', 'smtp_host', 'smtp_port', 'smtp_username', 'smtp_encryption',
            'notify_low_stock', 'notify_overdue_invoices', 'notify_new_sale', 'notify_purchase_received',
            'session_lifetime_minutes', 'password_min_length', 'require_2fa', 'login_attempt_limit', 'audit_retention_days',
            'backup_frequency', 'backup_retention_days', 'import_duplicate_policy', 'export_format',
        ];
        $settings = [];
        foreach ($settingKeys as $key) {
            if (array_key_exists($key, $_POST)) {
                $settings[$key] = trim((string) $_POST[$key]);
            }
        }
        admin_save_settings($pdo, $tenant_id, $settings);

        session(['company_name' => $companyName, 'currency' => $currency]);
        $_SESSION['company_name'] = $companyName;
        $_SESSION['currency'] = $currency;
        admin_audit($pdo, $tenant_id, 'Settings updated', 'Settings preferences saved.');
        admin_redirect('Settings saved successfully.', true, $section);
    }

    if ($action === 'add_branch') {
        $name = trim($_POST['name'] ?? '');
        if ($name === '') admin_redirect('Branch name is required.', false, 'branches');
        $pdo->prepare('INSERT INTO company_branches (tenant_id, name, code, manager, phone, email, address, is_active, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())')
            ->execute([$tenant_id, $name, trim($_POST['code'] ?? ''), trim($_POST['manager'] ?? ''), trim($_POST['phone'] ?? ''), trim($_POST['email'] ?? ''), trim($_POST['address'] ?? ''), (int) ($_POST['is_active'] ?? 1)]);
        admin_audit($pdo, $tenant_id, 'Branch added', $name);
        admin_redirect('Branch added successfully.', true, 'organization');
    }

    if ($action === 'add_warehouse') {
        $name = trim($_POST['name'] ?? '');
        if ($name === '') admin_redirect('Warehouse name is required.', false, 'warehouses');
        $pdo->prepare('INSERT INTO inventory_warehouses (tenant_id, name, location, manager, is_active, created_at, updated_at) VALUES (?, ?, ?, ?, ?, NOW(), NOW())')
            ->execute([$tenant_id, $name, trim($_POST['location'] ?? ''), trim($_POST['manager'] ?? ''), (int) ($_POST['is_active'] ?? 1)]);
        admin_audit($pdo, $tenant_id, 'Warehouse added', $name);
        admin_redirect('Warehouse added successfully.', true, 'organization');
    }

    if ($action === 'add_payment_method') {
        $name = trim($_POST['name'] ?? '');
        if ($name === '') admin_redirect('Payment method name is required.', false, 'payment_methods');
        $pdo->prepare('INSERT INTO payment_methods (tenant_id, name, method_type, account_name, account_number, is_active, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())')
            ->execute([$tenant_id, $name, trim($_POST['method_type'] ?? 'cash'), trim($_POST['account_name'] ?? ''), trim($_POST['account_number'] ?? ''), (int) ($_POST['is_active'] ?? 1)]);
        admin_audit($pdo, $tenant_id, 'Payment method added', $name);
        admin_redirect('Payment method added successfully.', true, 'operations');
    }

}

$tenant = onyx_row('SELECT * FROM tenants WHERE id = :id LIMIT 1', ['id' => $tenant_id]) ?: [];
$settings = admin_settings($pdo, $tenant_id);
$branches = onyx_rows('SELECT * FROM company_branches WHERE tenant_id = :tenant_id ORDER BY name ASC', ['tenant_id' => $tenant_id]);
$warehouses = onyx_rows('SELECT * FROM inventory_warehouses WHERE tenant_id = :tenant_id ORDER BY name ASC', ['tenant_id' => $tenant_id]);
$paymentMethods = onyx_rows('SELECT * FROM payment_methods WHERE tenant_id = :tenant_id ORDER BY name ASC', ['tenant_id' => $tenant_id]);
$users = onyx_rows('SELECT id, name, email, role, is_active, created_at FROM users WHERE tenant_id = :tenant_id OR tenant_id IS NULL ORDER BY name ASC', ['tenant_id' => $tenant_id]);
$auditLogs = onyx_rows('SELECT * FROM admin_audit_logs WHERE tenant_id = :tenant_id ORDER BY created_at DESC, id DESC LIMIT 30', ['tenant_id' => $tenant_id]);
$moduleCounts = [
    'products' => (int) onyx_scalar('SELECT COUNT(*) FROM products WHERE tenant_id = :tenant_id', ['tenant_id' => $tenant_id], 0),
    'customers' => (int) onyx_scalar('SELECT COUNT(*) FROM customers WHERE tenant_id = :tenant_id', ['tenant_id' => $tenant_id], 0),
    'suppliers' => (int) onyx_scalar('SELECT COUNT(*) FROM suppliers WHERE tenant_id = :tenant_id', ['tenant_id' => $tenant_id], 0),
    'invoices' => (int) onyx_scalar('SELECT COUNT(*) FROM invoices WHERE tenant_id = :tenant_id', ['tenant_id' => $tenant_id], 0),
];
$message = $_GET['success'] ?? $_GET['error'] ?? '';
$messageType = isset($_GET['error']) ? 'error' : 'success';
$context = onyx_page_start('Settings', 'Company settings, users, roles, branches, fiscal controls, documents, notifications, and system governance.');
$currency = $context['currency'];
$navSections = [
    'overview' => 'Overview',
    'company' => 'Company',
    'finance' => 'Finance',
    'organization' => 'Organization',
    'operations' => 'Operations',
    'documents' => 'Documents',
    'communications' => 'Communications',
    'security_data' => 'Security and Data',
    'system' => 'System',
];
$activeSection = $_GET['section'] ?? 'overview';
if (! array_key_exists($activeSection, $navSections)) {
    $activeSection = 'overview';
}
?>

<style>
    .admin-page,.admin-page *{border-radius:0!important}.admin-page{display:grid;gap:18px}.admin-panel{background:var(--onyx-surface);border:1px solid var(--onyx-border);padding:18px;overflow:hidden}.admin-hero{align-items:flex-start;display:flex;flex-wrap:wrap;gap:14px;justify-content:space-between}.admin-title{color:#fff;font-size:16px;font-weight:900;letter-spacing:.2px}.admin-subtitle,.admin-muted{color:var(--onyx-muted);display:block;font-size:10px;line-height:1.6;margin-top:5px}.admin-kpis{display:grid;gap:10px;grid-template-columns:repeat(5,minmax(0,1fr))}.admin-kpi{background:var(--onyx-surface);border:1px solid var(--onyx-border);padding:14px}.admin-kpi span,.admin-section-title{color:var(--onyx-muted);display:block;font-size:9px;font-weight:900;letter-spacing:.8px;text-transform:uppercase}.admin-kpi strong{color:#fff;display:block;font-size:16px;margin-top:8px;word-break:break-word}.admin-layout{display:grid;gap:18px}.admin-nav{background:var(--onyx-surface);border:1px solid var(--onyx-border);display:flex;flex-wrap:wrap;gap:8px;padding:10px}.admin-nav a{border:1px solid rgba(255,255,255,.08);color:#fff;font-size:10px;font-weight:800;min-height:36px;padding:10px 12px;text-decoration:none;text-transform:uppercase}.admin-nav a:hover,.admin-nav a.active{background:#fff;color:#050506}.admin-form{display:grid;gap:18px}.admin-grid{display:grid;gap:12px;grid-template-columns:repeat(12,minmax(0,1fr));margin-top:14px}.admin-field{display:grid;gap:6px;grid-column:span 3;min-width:0}.admin-field.wide{grid-column:span 6}.admin-field.full{grid-column:span 12}.admin-field label{color:var(--onyx-muted);font-size:10px;font-weight:900;text-transform:uppercase}.admin-field input,.admin-field select,.admin-field textarea{background:#101016;border:1px solid rgba(255,255,255,.12);color:#fff;font-family:Poppins,system-ui,sans-serif;font-size:10px;min-height:38px;padding:8px 10px;width:100%}.admin-field textarea{min-height:80px;resize:vertical}.admin-field select option{background:#050506;color:#fff}.admin-actions,.admin-inline{align-items:center;display:flex;flex-wrap:wrap;gap:8px}.admin-actions{justify-content:flex-end}.admin-btn{align-items:center;background:rgba(255,255,255,.035);border:1px solid rgba(255,255,255,.1);color:#fff;cursor:pointer;display:inline-flex;font:inherit;font-size:10px;font-weight:900;gap:8px;min-height:38px;padding:0 12px;text-decoration:none;text-transform:uppercase}.admin-btn.primary{background:#fff;color:#050506}.admin-alert{border:1px solid rgba(143,240,195,.24);color:#8ff0c3;font-size:11px;font-weight:800;padding:11px 12px}.admin-alert.error{border-color:rgba(255,138,138,.28);color:#ff8a8a}.admin-table-wrap{margin-top:14px;overflow-x:auto}.admin-table{border-collapse:collapse;min-width:820px;width:100%}.admin-table th,.admin-table td{border-bottom:1px solid rgba(255,255,255,.06);font-size:10px;padding:9px;text-align:left;vertical-align:top}.admin-table th{color:var(--onyx-muted);font-size:9px;font-weight:900;text-transform:uppercase}.admin-badge{border:1px solid rgba(255,255,255,.12);display:inline-flex;font-size:9px;font-weight:900;padding:4px 7px;text-transform:uppercase}.admin-badge.ok{color:#8ff0c3}.admin-badge.warn{color:#ffd27a}.admin-badge.danger{color:#ff8a8a}.admin-card-grid{display:grid;gap:10px;grid-template-columns:repeat(4,minmax(0,1fr));margin-top:14px}.admin-card{border:1px solid rgba(255,255,255,.08);padding:12px}.admin-card strong{color:#fff;display:block;font-size:12px}.admin-card span{color:var(--onyx-muted);display:block;font-size:10px;line-height:1.5;margin-top:6px}@media(max-width:1180px){.admin-kpis,.admin-card-grid{grid-template-columns:repeat(2,1fr)}.admin-field,.admin-field.wide{grid-column:span 6}}@media(max-width:700px){.admin-kpis,.admin-card-grid{grid-template-columns:1fr}.admin-field,.admin-field.wide{grid-column:span 12}.admin-actions{justify-content:stretch}.admin-btn{justify-content:center;width:100%}}
</style>

<div class="admin-page">
    <?php if ($message !== ''): ?>
        <section class="admin-panel"><div class="admin-alert <?= $messageType === 'error' ? 'error' : '' ?>"><?= admin_h($message) ?></div></section>
    <?php endif; ?>

    <section class="admin-panel">
        <div class="admin-hero">
            <div>
                <div class="admin-title"><?= admin_h($tenant['company_name'] ?? 'Company') ?> Settings</div>
                <span class="admin-subtitle">Control company identity, fiscal behavior, documents, users, security, branches, warehouses, notifications, and audit readiness.</span>
            </div>
            <div class="admin-inline">
                <a class="admin-btn" href="<?= admin_h(onyx_legacy_url('dashboard.php')) ?>"><i class="fa-solid fa-gauge-high"></i> Dashboard</a>
                <a class="admin-btn" href="<?= admin_h(onyx_legacy_url('reports.php')) ?>"><i class="fa-solid fa-chart-column"></i> Reports</a>
            </div>
        </div>
    </section>

    <section class="admin-kpis">
        <div class="admin-kpi"><span>Currency</span><strong><?= admin_h($tenant['currency'] ?? $currency) ?></strong></div>
        <div class="admin-kpi"><span>Users</span><strong><?= admin_h(count($users)) ?></strong></div>
        <div class="admin-kpi"><span>Branches</span><strong><?= admin_h(count($branches)) ?></strong></div>
        <div class="admin-kpi"><span>Warehouses</span><strong><?= admin_h(count($warehouses)) ?></strong></div>
        <div class="admin-kpi"><span>Audit Events</span><strong><?= admin_h(count($auditLogs)) ?></strong></div>
    </section>

    <div class="admin-layout">
        <div class="admin-form">
            <?php if ($activeSection === 'overview'): ?>
                <section class="admin-panel">
                    <div class="admin-section-title">Settings Overview</div>
                    <div class="admin-card-grid">
                        <div class="admin-card"><strong>Grouped Settings</strong><span>Company, Finance, Organization, Operations, Documents, Communications, Security and System now group related settings.</span></div>
                        <div class="admin-card"><strong>Still Needed</strong><span>Role permission matrix, editable records, SMTP test send, backup execution, import wizard, and document template designer.</span></div>
                        <div class="admin-card"><strong>Connected Data</strong><span>Company, branches, warehouses, payment methods, users, audit logs, and tenant preferences are wired to database records.</span></div>
                        <div class="admin-card"><strong>Configuration Coverage</strong><span>Fiscal, tax, document numbering, inventory, notification, security, backup, and import/export preferences are covered.</span></div>
                    </div>
                </section>
            <?php endif; ?>

            <?php if (in_array($activeSection, ['company','finance','operations','documents','communications','security_data'], true)): ?>
            <form method="POST" action="<?= admin_h(admin_url($activeSection)) ?>">
                <input type="hidden" name="action" value="save_administration">
                <input type="hidden" name="section" value="<?= admin_h($activeSection) ?>">

                <?php if ($activeSection === 'company'): ?>
                <section class="admin-panel" id="company">
                    <div class="admin-section-title">Company Profile</div>
                    <div class="admin-grid">
                        <div class="admin-field wide"><label>Company Name</label><input name="company_name" required value="<?= admin_h($tenant['company_name'] ?? '') ?>"></div>
                        <div class="admin-field"><label>Slug</label><input name="company_slug" value="<?= admin_h($tenant['slug'] ?? '') ?>"></div>
                        <div class="admin-field"><label>Status</label><select name="tenant_status"><?php foreach (['active'=>'Active','trial'=>'Trial','suspended'=>'Suspended'] as $v=>$l): ?><option value="<?= admin_h($v) ?>" <?= ($tenant['status'] ?? 'active') === $v ? 'selected' : '' ?>><?= admin_h($l) ?></option><?php endforeach; ?></select></div>
                        <div class="admin-field"><label>Logo URL</label><input name="company_logo" value="<?= admin_h(admin_setting($settings, 'company_logo')) ?>"></div>
                        <div class="admin-field"><label>Website</label><input name="company_website" value="<?= admin_h(admin_setting($settings, 'company_website')) ?>"></div>
                        <div class="admin-field"><label>Email</label><input type="email" name="company_email" value="<?= admin_h($tenant['email'] ?? '') ?>"></div>
                        <div class="admin-field"><label>Phone</label><input name="company_phone" value="<?= admin_h($tenant['phone'] ?? '') ?>"></div>
                        <div class="admin-field"><label>Country</label><input name="company_country" value="<?= admin_h(admin_setting($settings, 'company_country', 'Uganda')) ?>"></div>
                        <div class="admin-field full"><label>Address</label><textarea name="company_address"><?= admin_h($tenant['address'] ?? '') ?></textarea></div>
                        <div class="admin-field full"><label>Tagline</label><input name="company_tagline" value="<?= admin_h(admin_setting($settings, 'company_tagline')) ?>"></div>
                        <div class="admin-field full"><label>Company Profile</label><textarea name="company_profile"><?= admin_h(admin_setting($settings, 'company_profile')) ?></textarea></div>
                        <div class="admin-field wide"><label>Mission</label><textarea name="company_mission"><?= admin_h(admin_setting($settings, 'company_mission')) ?></textarea></div>
                        <div class="admin-field wide"><label>Vision</label><textarea name="company_vision"><?= admin_h(admin_setting($settings, 'company_vision')) ?></textarea></div>
                    </div>
                </section>
                <?php endif; ?>

                <?php if ($activeSection === 'finance'): ?>
                <section class="admin-panel" id="accounting">
                    <div class="admin-section-title">Accounting Defaults</div>
                    <div class="admin-grid">
                        <div class="admin-field"><label>Currency</label><input name="currency" value="<?= admin_h($tenant['currency'] ?? 'UGX') ?>"></div>
                        <div class="admin-field"><label>Cash Account</label><input name="cash_account" value="<?= admin_h(admin_setting($settings, 'cash_account', 'Cash on Hand')) ?>"></div>
                        <div class="admin-field"><label>Bank Account</label><input name="bank_account" value="<?= admin_h(admin_setting($settings, 'bank_account', 'Main Bank')) ?>"></div>
                        <div class="admin-field"><label>Accounts Receivable</label><input name="accounts_receivable_account" value="<?= admin_h(admin_setting($settings, 'accounts_receivable_account', 'Accounts Receivable')) ?>"></div>
                        <div class="admin-field"><label>Accounts Payable</label><input name="accounts_payable_account" value="<?= admin_h(admin_setting($settings, 'accounts_payable_account', 'Accounts Payable')) ?>"></div>
                        <div class="admin-field"><label>Default Income</label><input name="default_income_account" value="<?= admin_h(admin_setting($settings, 'default_income_account', 'Sales Income')) ?>"></div>
                        <div class="admin-field"><label>Default Expense</label><input name="default_expense_account" value="<?= admin_h(admin_setting($settings, 'default_expense_account', 'Cost of Goods Sold')) ?>"></div>
                        <div class="admin-field"><label>Payment Due Days</label><input type="number" name="payment_due_days" value="<?= admin_h(admin_setting($settings, 'payment_due_days', '7')) ?>"></div>
                    </div>
                </section>
                <?php endif; ?>

                <?php if ($activeSection === 'finance'): ?>
                <section class="admin-panel" id="tax">
                    <div class="admin-section-title">Tax and Fiscal Identity</div>
                    <div class="admin-grid">
                        <div class="admin-field"><label>TIN</label><input name="tin_number" value="<?= admin_h($tenant['tin_number'] ?? '') ?>"></div>
                        <div class="admin-field"><label>Business Registration</label><input name="business_registration_number" value="<?= admin_h(admin_setting($settings, 'business_registration_number')) ?>"></div>
                        <div class="admin-field"><label>VAT Registration</label><input name="vat_registration_number" value="<?= admin_h(admin_setting($settings, 'vat_registration_number')) ?>"></div>
                        <div class="admin-field"><label>Default VAT %</label><input type="number" step="0.01" name="default_vat_rate" value="<?= admin_h(admin_setting($settings, 'default_vat_rate', '0.00')) ?>"></div>
                        <div class="admin-field"><label>VAT Inclusive</label><select name="vat_inclusive_pricing"><option value="no" <?= admin_setting($settings, 'vat_inclusive_pricing', 'no') === 'no' ? 'selected' : '' ?>>No</option><option value="yes" <?= admin_setting($settings, 'vat_inclusive_pricing') === 'yes' ? 'selected' : '' ?>>Yes</option></select></div>
                        <div class="admin-field"><label>Withholding Tax %</label><input type="number" step="0.01" name="withholding_tax_rate" value="<?= admin_h(admin_setting($settings, 'withholding_tax_rate', '0.00')) ?>"></div>
                        <div class="admin-field"><label>Invoice Tax Label</label><input name="invoice_tax_label" value="<?= admin_h(admin_setting($settings, 'invoice_tax_label', 'VAT')) ?>"></div>
                    </div>
                </section>
                <?php endif; ?>

                <?php if ($activeSection === 'documents'): ?>
                <section class="admin-panel" id="document_branding">
                    <div class="admin-section-title">Document Branding and Controls</div>
                    <div class="admin-grid">
                        <div class="admin-field"><label>Invoice Color</label><input type="color" name="invoice_accent_color" value="<?= admin_h(admin_setting($settings, 'invoice_accent_color', '#51439a')) ?>"></div>
                        <div class="admin-field"><label>Quotation Color</label><input type="color" name="quotation_accent_color" value="<?= admin_h(admin_setting($settings, 'quotation_accent_color', '#55734f')) ?>"></div>
                        <div class="admin-field"><label>Receipt Bar Color</label><input type="color" name="receipt_accent_color" value="<?= admin_h(admin_setting($settings, 'receipt_accent_color', '#111111')) ?>"></div>
                        <div class="admin-field"><label>Logo Size</label><select name="document_logo_size"><option value="small" <?= admin_setting($settings, 'document_logo_size', 'medium') === 'small' ? 'selected' : '' ?>>Small</option><option value="medium" <?= admin_setting($settings, 'document_logo_size', 'medium') === 'medium' ? 'selected' : '' ?>>Medium</option><option value="large" <?= admin_setting($settings, 'document_logo_size', 'medium') === 'large' ? 'selected' : '' ?>>Large</option></select></div>
                        <div class="admin-field"><label>Signature Line</label><select name="document_show_signature"><option value="yes" <?= admin_setting($settings, 'document_show_signature', 'yes') === 'yes' ? 'selected' : '' ?>>Show</option><option value="no" <?= admin_setting($settings, 'document_show_signature') === 'no' ? 'selected' : '' ?>>Hide</option></select></div>
                        <div class="admin-field"><label>Discount Row</label><select name="document_show_discount"><option value="yes" <?= admin_setting($settings, 'document_show_discount', 'yes') === 'yes' ? 'selected' : '' ?>>Show</option><option value="no" <?= admin_setting($settings, 'document_show_discount') === 'no' ? 'selected' : '' ?>>Hide</option></select></div>
                    </div>
                </section>
                <?php endif; ?>

                <?php if ($activeSection === 'documents'): ?>
                <section class="admin-panel" id="document_payments">
                    <div class="admin-section-title">Payment Details</div>
                    <div class="admin-grid">
                        <div class="admin-field"><label>Account Name</label><input name="document_payment_account_name" value="<?= admin_h(admin_setting($settings, 'document_payment_account_name', $tenant['company_name'] ?? 'Company Collections')) ?>"></div>
                        <div class="admin-field"><label>Account Number</label><input name="document_payment_account_number" value="<?= admin_h(admin_setting($settings, 'document_payment_account_number', '')) ?>"></div>
                        <div class="admin-field"><label>Bank Name</label><input name="document_payment_bank_name" value="<?= admin_h(admin_setting($settings, 'document_payment_bank_name', admin_setting($settings, 'bank_account', 'Main Bank'))) ?>"></div>
                        <div class="admin-field"><label>Mobile Money</label><input name="document_payment_mobile_money" value="<?= admin_h(admin_setting($settings, 'document_payment_mobile_money', $tenant['phone'] ?? '')) ?>"></div>
                        <div class="admin-field full"><label>Payment Methods Note</label><textarea name="document_payment_methods_note"><?= admin_h(admin_setting($settings, 'document_payment_methods_note', 'Cash, mobile money, bank transfer, cheque.')) ?></textarea></div>
                    </div>
                </section>
                <?php endif; ?>

                <?php if ($activeSection === 'documents'): ?>
                <section class="admin-panel" id="invoice_settings">
                    <div class="admin-section-title">Invoice Settings</div>
                    <div class="admin-grid">
                        <div class="admin-field"><label>Invoice Prefix</label><input name="invoice_prefix" value="<?= admin_h(admin_setting($settings, 'invoice_prefix', 'INV')) ?>"></div>
                        <div class="admin-field"><label>Next Invoice No.</label><input name="next_invoice_number" value="<?= admin_h(admin_setting($settings, 'next_invoice_number', '0001')) ?>"></div>
                        <div class="admin-field"><label>Purchase Prefix</label><input name="purchase_prefix" value="<?= admin_h(admin_setting($settings, 'purchase_prefix', 'PO')) ?>"></div>
                        <div class="admin-field"><label>Next Purchase No.</label><input name="next_purchase_number" value="<?= admin_h(admin_setting($settings, 'next_purchase_number', '0001')) ?>"></div>
                        <div class="admin-field full"><label>Invoice Footer</label><textarea name="invoice_footer"><?= admin_h(admin_setting($settings, 'invoice_footer', 'Thank you for your business.')) ?></textarea></div>
                    </div>
                </section>
                <?php endif; ?>

                <?php if ($activeSection === 'documents'): ?>
                <section class="admin-panel" id="receipt_settings">
                    <div class="admin-section-title">Receipt Settings</div>
                    <div class="admin-grid">
                        <div class="admin-field"><label>Receipt Prefix</label><input name="receipt_prefix" value="<?= admin_h(admin_setting($settings, 'receipt_prefix', 'RCT')) ?>"></div>
                        <div class="admin-field"><label>Next Receipt No.</label><input name="next_receipt_number" value="<?= admin_h(admin_setting($settings, 'next_receipt_number', '0001')) ?>"></div>
                        <div class="admin-field full"><label>Receipt Footer</label><textarea name="receipt_footer"><?= admin_h(admin_setting($settings, 'receipt_footer', 'Goods sold are subject to company policy.')) ?></textarea></div>
                    </div>
                </section>
                <?php endif; ?>

                <?php if ($activeSection === 'documents'): ?>
                <section class="admin-panel" id="quotation_settings">
                    <div class="admin-section-title">Quotation Settings</div>
                    <div class="admin-grid">
                        <div class="admin-field"><label>Quotation Prefix</label><input name="quotation_prefix" value="<?= admin_h(admin_setting($settings, 'quotation_prefix', 'QT')) ?>"></div>
                        <div class="admin-field"><label>Next Quotation No.</label><input name="next_quotation_number" value="<?= admin_h(admin_setting($settings, 'next_quotation_number', '0001')) ?>"></div>
                        <div class="admin-field full"><label>Quotation Terms</label><textarea name="quotation_terms"><?= admin_h(admin_setting($settings, 'quotation_terms', 'This quotation is valid for 7 days.')) ?></textarea></div>
                        <div class="admin-field full"><label>Purchase Terms</label><textarea name="purchase_terms"><?= admin_h(admin_setting($settings, 'purchase_terms', 'Purchase subject to supplier invoice and goods received confirmation.')) ?></textarea></div>
                    </div>
                </section>
                <?php endif; ?>

                <?php if ($activeSection === 'operations'): ?>
                <section class="admin-panel" id="products">
                    <div class="admin-section-title">Product and Inventory Preferences</div>
                    <div class="admin-grid">
                        <div class="admin-field"><label>Negative Sales</label><select name="stock_negative_sales"><option value="block" <?= admin_setting($settings, 'stock_negative_sales', 'block') === 'block' ? 'selected' : '' ?>>Block</option><option value="allow" <?= admin_setting($settings, 'stock_negative_sales') === 'allow' ? 'selected' : '' ?>>Allow</option></select></div>
                        <div class="admin-field"><label>Low Stock Alerts</label><select name="low_stock_alerts"><option value="yes" <?= admin_setting($settings, 'low_stock_alerts', 'yes') === 'yes' ? 'selected' : '' ?>>Yes</option><option value="no" <?= admin_setting($settings, 'low_stock_alerts') === 'no' ? 'selected' : '' ?>>No</option></select></div>
                        <div class="admin-field"><label>Default Reorder Level</label><input type="number" name="default_reorder_level" value="<?= admin_h(admin_setting($settings, 'default_reorder_level', '5')) ?>"></div>
                        <div class="admin-field"><label>Warehouse Policy</label><select name="default_warehouse_policy"><option value="main_first" <?= admin_setting($settings, 'default_warehouse_policy', 'main_first') === 'main_first' ? 'selected' : '' ?>>Main first</option><option value="user_choice" <?= admin_setting($settings, 'default_warehouse_policy') === 'user_choice' ? 'selected' : '' ?>>User choice</option></select></div>
                    </div>
                </section>
                <?php endif; ?>

                <?php if ($activeSection === 'communications'): ?>
                <section class="admin-panel" id="notifications">
                    <div class="admin-section-title">Notification Preferences</div>
                    <div class="admin-grid">
                        <?php foreach (['notify_low_stock' => 'Low Stock', 'notify_overdue_invoices' => 'Overdue Invoices', 'notify_new_sale' => 'New Sale', 'notify_purchase_received' => 'Purchase Received'] as $key => $label): ?>
                            <div class="admin-field"><label><?= admin_h($label) ?></label><select name="<?= admin_h($key) ?>"><option value="yes" <?= admin_setting($settings, $key, 'yes') === 'yes' ? 'selected' : '' ?>>Yes</option><option value="no" <?= admin_setting($settings, $key) === 'no' ? 'selected' : '' ?>>No</option></select></div>
                        <?php endforeach; ?>
                    </div>
                </section>
                <?php endif; ?>

                <?php if ($activeSection === 'communications'): ?>
                <section class="admin-panel" id="email_smtp">
                    <div class="admin-section-title">Email SMTP</div>
                    <div class="admin-grid">
                        <div class="admin-field"><label>From Name</label><input name="email_from_name" value="<?= admin_h(admin_setting($settings, 'email_from_name', $tenant['company_name'] ?? 'Company')) ?>"></div>
                        <div class="admin-field"><label>From Address</label><input name="email_from_address" value="<?= admin_h(admin_setting($settings, 'email_from_address')) ?>"></div>
                        <div class="admin-field"><label>SMTP Host</label><input name="smtp_host" value="<?= admin_h(admin_setting($settings, 'smtp_host')) ?>"></div>
                        <div class="admin-field"><label>SMTP Port</label><input name="smtp_port" value="<?= admin_h(admin_setting($settings, 'smtp_port', '587')) ?>"></div>
                        <div class="admin-field"><label>SMTP Username</label><input name="smtp_username" value="<?= admin_h(admin_setting($settings, 'smtp_username')) ?>"></div>
                        <div class="admin-field"><label>Encryption</label><select name="smtp_encryption"><option value="tls" <?= admin_setting($settings, 'smtp_encryption', 'tls') === 'tls' ? 'selected' : '' ?>>TLS</option><option value="ssl" <?= admin_setting($settings, 'smtp_encryption') === 'ssl' ? 'selected' : '' ?>>SSL</option><option value="" <?= admin_setting($settings, 'smtp_encryption') === '' ? 'selected' : '' ?>>None</option></select></div>
                    </div>
                </section>
                <?php endif; ?>

                <?php if ($activeSection === 'security_data'): ?>
                <section class="admin-panel" id="security">
                    <div class="admin-section-title">Security</div>
                    <div class="admin-grid">
                        <div class="admin-field"><label>Session Lifetime Minutes</label><input type="number" name="session_lifetime_minutes" value="<?= admin_h(admin_setting($settings, 'session_lifetime_minutes', '120')) ?>"></div>
                        <div class="admin-field"><label>Password Min Length</label><input type="number" name="password_min_length" value="<?= admin_h(admin_setting($settings, 'password_min_length', '8')) ?>"></div>
                        <div class="admin-field"><label>Require 2FA</label><select name="require_2fa"><option value="no" <?= admin_setting($settings, 'require_2fa', 'no') === 'no' ? 'selected' : '' ?>>No</option><option value="yes" <?= admin_setting($settings, 'require_2fa') === 'yes' ? 'selected' : '' ?>>Yes</option></select></div>
                        <div class="admin-field"><label>Login Attempt Limit</label><input type="number" name="login_attempt_limit" value="<?= admin_h(admin_setting($settings, 'login_attempt_limit', '5')) ?>"></div>
                        <div class="admin-field"><label>Audit Retention Days</label><input type="number" name="audit_retention_days" value="<?= admin_h(admin_setting($settings, 'audit_retention_days', '365')) ?>"></div>
                    </div>
                </section>
                <?php endif; ?>

                <?php if ($activeSection === 'finance'): ?>
                <section class="admin-panel" id="financial_periods">
                    <div class="admin-section-title">Financial Periods</div>
                    <div class="admin-grid">
                        <div class="admin-field"><label>Financial Year Label</label><input name="financial_year_label" value="<?= admin_h(admin_setting($settings, 'financial_year_label', date('Y'))) ?>"></div>
                        <div class="admin-field"><label>Fiscal Year Start</label><input type="date" name="fiscal_year_start" value="<?= admin_h($tenant['fiscal_year_start'] ?? '') ?>"></div>
                        <div class="admin-field"><label>Timezone</label><input name="timezone" value="<?= admin_h(admin_setting($settings, 'timezone', 'Africa/Kampala')) ?>"></div>
                        <div class="admin-field"><label>Date Format</label><select name="date_format"><option value="Y-m-d" <?= admin_setting($settings, 'date_format', 'Y-m-d') === 'Y-m-d' ? 'selected' : '' ?>>YYYY-MM-DD</option><option value="d/m/Y" <?= admin_setting($settings, 'date_format') === 'd/m/Y' ? 'selected' : '' ?>>DD/MM/YYYY</option><option value="m/d/Y" <?= admin_setting($settings, 'date_format') === 'm/d/Y' ? 'selected' : '' ?>>MM/DD/YYYY</option></select></div>
                        <div class="admin-field"><label>Time Format</label><select name="time_format"><option value="H:i" <?= admin_setting($settings, 'time_format', 'H:i') === 'H:i' ? 'selected' : '' ?>>24 hour</option><option value="h:i A" <?= admin_setting($settings, 'time_format') === 'h:i A' ? 'selected' : '' ?>>12 hour</option></select></div>
                    </div>
                </section>
                <?php endif; ?>

                <?php if ($activeSection === 'security_data'): ?>
                <section class="admin-panel" id="backup_restore">
                    <div class="admin-section-title">Backup and Restore</div>
                    <div class="admin-grid">
                        <div class="admin-field"><label>Backup Frequency</label><select name="backup_frequency"><option value="daily" <?= admin_setting($settings, 'backup_frequency', 'daily') === 'daily' ? 'selected' : '' ?>>Daily</option><option value="weekly" <?= admin_setting($settings, 'backup_frequency') === 'weekly' ? 'selected' : '' ?>>Weekly</option><option value="manual" <?= admin_setting($settings, 'backup_frequency') === 'manual' ? 'selected' : '' ?>>Manual</option></select></div>
                        <div class="admin-field"><label>Retention Days</label><input type="number" name="backup_retention_days" value="<?= admin_h(admin_setting($settings, 'backup_retention_days', '30')) ?>"></div>
                    </div>
                </section>
                <?php endif; ?>

                <?php if ($activeSection === 'security_data'): ?>
                <section class="admin-panel" id="import_export">
                    <div class="admin-section-title">Import and Export</div>
                    <div class="admin-grid">
                        <div class="admin-field"><label>Duplicate Policy</label><select name="import_duplicate_policy"><option value="skip" <?= admin_setting($settings, 'import_duplicate_policy', 'skip') === 'skip' ? 'selected' : '' ?>>Skip</option><option value="update" <?= admin_setting($settings, 'import_duplicate_policy') === 'update' ? 'selected' : '' ?>>Update existing</option></select></div>
                        <div class="admin-field"><label>Export Format</label><select name="export_format"><option value="csv" <?= admin_setting($settings, 'export_format', 'csv') === 'csv' ? 'selected' : '' ?>>CSV</option><option value="xlsx" <?= admin_setting($settings, 'export_format') === 'xlsx' ? 'selected' : '' ?>>XLSX</option></select></div>
                    </div>
                </section>
                <?php endif; ?>

                <section class="admin-panel">
                    <div class="admin-actions"><button class="admin-btn primary" type="submit"><i class="fa-solid fa-floppy-disk"></i> Save Settings</button></div>
                </section>
            </form>
            <?php endif; ?>

            <?php if ($activeSection === 'organization'): ?>
            <section class="admin-panel" id="branches">
                <div class="admin-section-title">Branches</div>
                <form method="POST" action="<?= admin_h(admin_url('organization')) ?>"><input type="hidden" name="action" value="add_branch"><div class="admin-grid"><div class="admin-field"><label>Name</label><input name="name" required></div><div class="admin-field"><label>Code</label><input name="code"></div><div class="admin-field"><label>Manager</label><input name="manager"></div><div class="admin-field"><label>Phone</label><input name="phone"></div><div class="admin-field"><label>Email</label><input name="email"></div><div class="admin-field"><label>Status</label><select name="is_active"><option value="1">Active</option><option value="0">Inactive</option></select></div><div class="admin-field full"><label>Address</label><textarea name="address"></textarea></div></div><div class="admin-actions" style="margin-top:14px;"><button class="admin-btn primary" type="submit">Add Branch</button></div></form>
                <div class="admin-table-wrap"><table class="admin-table"><thead><tr><th>Name</th><th>Code</th><th>Manager</th><th>Phone</th><th>Email</th><th>Status</th></tr></thead><tbody><?php foreach ($branches as $branch): ?><tr><td><?= admin_h($branch['name']) ?></td><td><?= admin_h($branch['code']) ?></td><td><?= admin_h($branch['manager']) ?></td><td><?= admin_h($branch['phone']) ?></td><td><?= admin_h($branch['email']) ?></td><td><span class="admin-badge <?= (int)$branch['is_active'] ? 'ok' : 'danger' ?>"><?= (int)$branch['is_active'] ? 'Active' : 'Inactive' ?></span></td></tr><?php endforeach; ?></tbody></table></div>
            </section>
            <?php endif; ?>

            <?php if ($activeSection === 'organization'): ?>
            <section class="admin-panel" id="warehouses">
                <div class="admin-section-title">Warehouses</div>
                <form method="POST" action="<?= admin_h(admin_url('organization')) ?>"><input type="hidden" name="action" value="add_warehouse"><div class="admin-grid"><div class="admin-field"><label>Name</label><input name="name" required></div><div class="admin-field wide"><label>Location</label><input name="location"></div><div class="admin-field"><label>Manager</label><input name="manager"></div><div class="admin-field"><label>Status</label><select name="is_active"><option value="1">Active</option><option value="0">Inactive</option></select></div></div><div class="admin-actions" style="margin-top:14px;"><button class="admin-btn primary" type="submit">Add Warehouse</button></div></form>
                <div class="admin-table-wrap"><table class="admin-table"><thead><tr><th>Name</th><th>Location</th><th>Manager</th><th>Status</th></tr></thead><tbody><?php foreach ($warehouses as $warehouse): ?><tr><td><?= admin_h($warehouse['name']) ?></td><td><?= admin_h($warehouse['location']) ?></td><td><?= admin_h($warehouse['manager'] ?? '') ?></td><td><span class="admin-badge <?= (int)($warehouse['is_active'] ?? 1) ? 'ok' : 'danger' ?>"><?= (int)($warehouse['is_active'] ?? 1) ? 'Active' : 'Inactive' ?></span></td></tr><?php endforeach; ?></tbody></table></div>
            </section>
            <?php endif; ?>

            <?php if ($activeSection === 'operations'): ?>
            <section class="admin-panel" id="payment_methods">
                <div class="admin-section-title">Payment Methods</div>
                <form method="POST" action="<?= admin_h(admin_url('operations')) ?>"><input type="hidden" name="action" value="add_payment_method"><div class="admin-grid"><div class="admin-field"><label>Name</label><input name="name" required></div><div class="admin-field"><label>Type</label><select name="method_type"><option value="cash">Cash</option><option value="bank">Bank</option><option value="mobile_money">Mobile Money</option><option value="card">Card</option><option value="cheque">Cheque</option></select></div><div class="admin-field"><label>Account Name</label><input name="account_name"></div><div class="admin-field"><label>Account Number</label><input name="account_number"></div><div class="admin-field"><label>Status</label><select name="is_active"><option value="1">Active</option><option value="0">Inactive</option></select></div></div><div class="admin-actions" style="margin-top:14px;"><button class="admin-btn primary" type="submit">Add Payment Method</button></div></form>
                <div class="admin-table-wrap"><table class="admin-table"><thead><tr><th>Name</th><th>Type</th><th>Account</th><th>Status</th></tr></thead><tbody><?php foreach ($paymentMethods as $method): ?><tr><td><?= admin_h($method['name']) ?></td><td><?= admin_h($method['method_type']) ?></td><td><?= admin_h(trim(($method['account_name'] ?? '') . ' ' . ($method['account_number'] ?? ''))) ?></td><td><span class="admin-badge <?= (int)$method['is_active'] ? 'ok' : 'danger' ?>"><?= (int)$method['is_active'] ? 'Active' : 'Inactive' ?></span></td></tr><?php endforeach; ?></tbody></table></div>
            </section>
            <?php endif; ?>

            <?php if ($activeSection === 'security_data'): ?>
            <section class="admin-panel" id="audit_logs">
                <div class="admin-section-title">Audit Logs</div>
                <div class="admin-table-wrap"><table class="admin-table"><thead><tr><th>Date</th><th>User</th><th>Action</th><th>Details</th><th>IP</th></tr></thead><tbody><?php if ($auditLogs === []): ?><tr><td colspan="5">No audit events recorded yet.</td></tr><?php endif; ?><?php foreach ($auditLogs as $log): ?><tr><td><?= admin_h($log['created_at']) ?></td><td><?= admin_h($log['user_name']) ?></td><td><?= admin_h($log['action']) ?></td><td><?= admin_h($log['details']) ?></td><td><?= admin_h($log['ip_address']) ?></td></tr><?php endforeach; ?></tbody></table></div>
            </section>
            <?php endif; ?>

            <?php if ($activeSection === 'system'): ?>
            <section class="admin-panel" id="system_info">
                <div class="admin-section-title">System Information</div>
                <div class="admin-card-grid">
                    <div class="admin-card"><strong>Laravel</strong><span><?= admin_h(app()->version()) ?></span></div>
                    <div class="admin-card"><strong>PHP</strong><span><?= admin_h(PHP_VERSION) ?></span></div>
                    <div class="admin-card"><strong>Tenant ID</strong><span><?= admin_h($tenant_id) ?></span></div>
                    <div class="admin-card"><strong>Database</strong><span><?= admin_h(config('database.connections.mysql.database')) ?></span></div>
                    <?php foreach ($moduleCounts as $label => $count): ?><div class="admin-card"><strong><?= admin_h(ucfirst($label)) ?></strong><span><?= admin_h($count) ?> record(s)</span></div><?php endforeach; ?>
                </div>
            </section>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php onyx_page_end(); ?>
