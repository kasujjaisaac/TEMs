<?php

$action = $_GET['action'] ?? $_POST['action'] ?? null;
$tenant_id = (int) (onyx_tenant_id() ?? 0);
$pdo = onyx_db();

function ensure_customer_columns(PDO $pdo): void
{
    $columns = [];
    foreach ($pdo->query('SHOW COLUMNS FROM customers')->fetchAll(PDO::FETCH_ASSOC) as $column) {
        $columns[] = $column['Field'];
    }

    if (!in_array('company_name', $columns, true)) {
        $pdo->exec("ALTER TABLE customers ADD COLUMN company_name VARCHAR(255) DEFAULT NULL");
    }
    if (!in_array('contact_person', $columns, true)) {
        $pdo->exec("ALTER TABLE customers ADD COLUMN contact_person VARCHAR(255) DEFAULT NULL");
    }
    if (!in_array('customer_group', $columns, true)) {
        $pdo->exec("ALTER TABLE customers ADD COLUMN customer_group VARCHAR(50) DEFAULT 'retail'");
    }
    if (!in_array('customer_type', $columns, true)) {
        $pdo->exec("ALTER TABLE customers ADD COLUMN customer_type VARCHAR(50) DEFAULT 'company'");
    }
    if (!in_array('billing_address', $columns, true)) {
        $pdo->exec("ALTER TABLE customers ADD COLUMN billing_address TEXT DEFAULT NULL");
    }
    if (!in_array('service_address', $columns, true)) {
        $pdo->exec("ALTER TABLE customers ADD COLUMN service_address TEXT DEFAULT NULL");
    }
    if (!in_array('payment_terms', $columns, true)) {
        $pdo->exec("ALTER TABLE customers ADD COLUMN payment_terms VARCHAR(50) DEFAULT 'cash'");
    }
    if (!in_array('preferred_payment_method', $columns, true)) {
        $pdo->exec("ALTER TABLE customers ADD COLUMN preferred_payment_method VARCHAR(50) DEFAULT NULL");
    }
    if (!in_array('credit_status', $columns, true)) {
        $pdo->exec("ALTER TABLE customers ADD COLUMN credit_status VARCHAR(30) DEFAULT 'good'");
    }
    if (!in_array('account_manager', $columns, true)) {
        $pdo->exec("ALTER TABLE customers ADD COLUMN account_manager VARCHAR(155) DEFAULT NULL");
    }
    if (!in_array('customer_source', $columns, true)) {
        $pdo->exec("ALTER TABLE customers ADD COLUMN customer_source VARCHAR(80) DEFAULT NULL");
    }
    if (!in_array('internal_notes', $columns, true)) {
        $pdo->exec("ALTER TABLE customers ADD COLUMN internal_notes TEXT DEFAULT NULL");
    }
}

function redirect_back($msg = '', $success = true) {
    $q = '';
    if ($msg !== '') $q = ($success ? '?success=' : '?error=') . urlencode($msg);
    header('Location: customers.php' . $q);
    exit();
}

function redirect_customer_profile(int $customerId, string $msg = '', bool $success = true): void
{
    $q = 'action=view&id=' . $customerId;
    if ($msg !== '') {
        $q .= ($success ? '&success=' : '&error=') . urlencode($msg);
    }

    header('Location: customers_action.php?' . $q);
    exit();
}

function customer_h(mixed $value): string
{
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
}

function customer_post_string(string $key, string $default = ''): string
{
    return trim((string) ($_POST[$key] ?? $default));
}

function customer_post_nullable_date(string $key): ?string
{
    $value = customer_post_string($key);

    return $value === '' ? null : $value;
}

function customer_exists(PDO $pdo, int $tenantId, int $customerId): bool
{
    if ($customerId <= 0) {
        return false;
    }

    $stmt = $pdo->prepare('SELECT COUNT(*) FROM customers WHERE id = ? AND tenant_id = ?');
    $stmt->execute([$customerId, $tenantId]);

    return (int) $stmt->fetchColumn() > 0;
}

function customer_money(float|int|string|null $amount, string $currency): string
{
    return number_format((float) ($amount ?? 0), 2) . ' ' . $currency;
}

function customer_count_rows(string $sql, array $params = []): int
{
    return (int) onyx_scalar($sql, $params, 0);
}

function customer_related_delete_form(string $type, int $itemId, int $customerId): array
{
    $html = '<form method="POST" action="customers_action.php" onsubmit="return confirm(\'Remove this customer record?\');">'
        . '<input type="hidden" name="action" value="delete_related">'
        . '<input type="hidden" name="related_type" value="' . customer_h($type) . '">'
        . '<input type="hidden" name="item_id" value="' . customer_h($itemId) . '">'
        . '<input type="hidden" name="customer_id" value="' . customer_h($customerId) . '">'
        . '<button class="customer-mini-action danger" type="submit">Remove</button>'
        . '</form>';

    return ['raw' => true, 'value' => $html];
}

function customer_maintenance_actions(array $item, int $customerId): array
{
    $id = (int) $item['id'];
    $status = (string) ($item['status'] ?? 'scheduled');
    $statusOptions = ['scheduled' => 'Scheduled', 'in_progress' => 'In Progress', 'completed' => 'Completed'];
    $html = '<form method="POST" action="customers_action.php" class="customer-inline-form">'
        . '<input type="hidden" name="action" value="update_maintenance_status">'
        . '<input type="hidden" name="customer_id" value="' . customer_h($customerId) . '">'
        . '<input type="hidden" name="maintenance_id" value="' . customer_h($id) . '">'
        . '<select name="status">';
    foreach ($statusOptions as $value => $label) {
        $html .= '<option value="' . customer_h($value) . '"' . ($status === $value ? ' selected' : '') . '>' . customer_h($label) . '</option>';
    }
    $html .= '</select><button class="customer-mini-action" type="submit">Update</button></form>';
    $html .= '<form method="POST" action="customers_action.php" onsubmit="return confirm(\'Remove this maintenance item?\');">'
        . '<input type="hidden" name="action" value="delete_related">'
        . '<input type="hidden" name="related_type" value="maintenance">'
        . '<input type="hidden" name="item_id" value="' . customer_h($id) . '">'
        . '<input type="hidden" name="customer_id" value="' . customer_h($customerId) . '">'
        . '<button class="customer-mini-action danger" type="submit">Remove</button></form>';

    return ['raw' => true, 'value' => '<div class="customer-row-actions">' . $html . '</div>'];
}

function customer_form_styles(): void
{
    ?>
    <style>
        .customer-form-page,.customer-form-page *,.customer-profile-page,.customer-profile-page *{border-radius:0!important}
        .customer-form-page,.customer-profile-page{display:grid;gap:16px;max-width:1180px}
        .customer-form-panel,.customer-profile-panel{background:var(--onyx-surface);border:1px solid var(--onyx-border);padding:18px}
        .customer-form-title,.customer-profile-title{align-items:center;color:#fff;display:flex;font-size:10px;font-weight:800;gap:9px;margin-bottom:14px;text-transform:uppercase}
        .customer-form-grid,.customer-profile-grid{display:grid;gap:12px;grid-template-columns:repeat(12,minmax(0,1fr))}
        .customer-field{display:grid;gap:6px;grid-column:span 4;min-width:0}
        .customer-field.wide{grid-column:span 8}.customer-field.full{grid-column:span 12}
        .customer-field label,.customer-mini-label{color:var(--onyx-muted);font-size:10px;font-weight:800;letter-spacing:.7px;text-transform:uppercase}
        .customer-field input,.customer-field select,.customer-field textarea{background:rgba(0,0,0,.18);border:1px solid rgba(255,255,255,.1);color:#fff;font-family:Poppins,system-ui,sans-serif;font-size:10px;min-height:38px;outline:0;padding:8px 10px;width:100%}
        .customer-field textarea{min-height:82px;resize:vertical}
        .customer-field input:focus,.customer-field select:focus,.customer-field textarea:focus{border-color:rgba(255,255,255,.42);box-shadow:0 0 0 2px rgba(255,255,255,.08)}
        .customer-form-actions,.customer-tabs,.customer-action-row{align-items:center;display:flex;flex-wrap:wrap;gap:10px}
        .customer-form-actions{justify-content:flex-end}
        .customer-form-btn,.customer-tab{align-items:center;background:rgba(255,255,255,.035);border:1px solid rgba(255,255,255,.1);color:#fff;cursor:pointer;display:inline-flex;font:inherit;font-size:10px;font-weight:800;gap:8px;min-height:40px;padding:0 14px;text-decoration:none;text-transform:uppercase}
        .customer-form-btn.primary,.customer-tab.active{background:#fff;color:#050506}
        .customer-form-btn.danger{border-color:rgba(255,138,138,.35);color:#ff8a8a}
        .customer-profile-card{border:1px solid rgba(255,255,255,.08);grid-column:span 3;padding:14px}
        .customer-profile-card strong{color:#fff;display:block;font-size:14px}.customer-profile-card span{color:var(--onyx-muted);display:block;font-size:10px;margin-top:6px}
        .customer-table-wrap{overflow-x:auto}.customer-table{border-collapse:collapse;min-width:720px;width:100%}
        .customer-table th,.customer-table td{border-bottom:1px solid rgba(255,255,255,.06);font-size:10px;padding:10px;text-align:left;vertical-align:top}
        .customer-table th{color:var(--onyx-muted);font-weight:800;text-transform:uppercase}
        .customer-alert{border:1px solid rgba(143,240,195,.24);color:#8ff0c3;font-size:11px;font-weight:700;padding:11px 12px}.customer-alert.error{border-color:rgba(255,138,138,.28);color:#ff8a8a}
        .customer-hero{align-items:stretch;display:grid;gap:14px;grid-template-columns:minmax(0,1.4fr) minmax(260px,.6fr)}
        .customer-identity{border:1px solid rgba(255,255,255,.08);display:grid;gap:12px;padding:18px}.customer-kicker{color:var(--onyx-muted);font-size:10px;font-weight:800;letter-spacing:.8px;text-transform:uppercase}.customer-identity h2{font-size:24px;line-height:1.15;margin:0}.customer-identity-meta{color:var(--onyx-muted);display:flex;flex-wrap:wrap;font-size:11px;font-weight:700;gap:9px}.customer-status-stack{display:grid;gap:10px}
        .customer-status-pill{align-items:center;border:1px solid rgba(255,255,255,.1);display:flex;gap:9px;justify-content:space-between;min-height:42px;padding:0 12px}.customer-status-pill strong{font-size:10px;text-transform:uppercase}.customer-status-pill span{color:var(--onyx-muted);font-size:10px}
        .customer-status-pill.ok strong{color:#8ff0c3}.customer-status-pill.warn strong{color:#ffd27a}.customer-status-pill.danger strong{color:#ff8a8a}
        .customer-tabs{background:rgba(0,0,0,.12);border:1px solid rgba(255,255,255,.08);padding:8px;position:sticky;top:70px;z-index:10}.customer-tab-panel{display:none}.customer-tab-panel.active{display:grid;gap:16px}
        .customer-row-actions{align-items:center;display:flex;flex-wrap:wrap;gap:6px}.customer-inline-form{align-items:center;display:inline-flex;gap:6px}.customer-inline-form select{background:rgba(0,0,0,.18);border:1px solid rgba(255,255,255,.1);color:#fff;font-size:10px;min-height:30px;padding:4px 6px}
        .customer-mini-action{background:rgba(255,255,255,.035);border:1px solid rgba(255,255,255,.12);color:#fff;cursor:pointer;font:inherit;font-size:9px;font-weight:800;min-height:30px;padding:0 9px;text-transform:uppercase}.customer-mini-action.danger{color:#ff8a8a}
        .customer-timeline{display:grid;gap:8px}.customer-timeline-item{border:1px solid rgba(255,255,255,.08);display:grid;gap:4px;padding:11px}.customer-timeline-item strong{color:#fff;font-size:11px}.customer-timeline-item span{color:var(--onyx-muted);font-size:10px}
        .customer-print-body{background:#f4f6f8;color:#172033;font-family:Arial,sans-serif;margin:0}.customer-print-sheet{background:#fff;margin:24px auto;max-width:960px;padding:34px}.customer-print-head{align-items:flex-start;border-bottom:2px solid #172033;display:flex;justify-content:space-between;padding-bottom:18px}.customer-print-brand h1{font-size:24px;margin:0}.customer-print-brand span,.customer-print-meta{color:#5f6b7a;font-size:12px}.customer-print-title{font-size:18px;font-weight:800;text-align:right;text-transform:uppercase}.customer-print-grid{display:grid;gap:14px;grid-template-columns:repeat(4,1fr);margin:22px 0}.customer-print-card{border:1px solid #d7dde5;padding:12px}.customer-print-card strong{display:block;font-size:12px;text-transform:uppercase}.customer-print-card span{display:block;font-size:13px;margin-top:6px}.customer-print-table{border-collapse:collapse;width:100%}.customer-print-table th,.customer-print-table td{border-bottom:1px solid #e3e7ed;font-size:12px;padding:9px;text-align:left}.customer-print-table th{background:#172033;color:#fff;text-transform:uppercase}.customer-print-total{margin-left:auto;margin-top:16px;max-width:340px}.customer-print-total div{display:flex;justify-content:space-between;padding:7px 0}.customer-print-total .due{border-top:2px solid #172033;font-size:16px;font-weight:800}@media print{.customer-print-sheet{margin:0;max-width:none}.no-print{display:none}}
        @media(max-width:980px){.customer-field,.customer-field.wide,.customer-profile-card{grid-column:span 6}}
        @media(max-width:760px){.customer-hero{grid-template-columns:1fr}.customer-tabs{position:static}}
        @media(max-width:640px){.customer-field,.customer-field.wide,.customer-profile-card{grid-column:span 12}.customer-form-actions{justify-content:stretch}.customer-form-btn{justify-content:center;width:100%}}
    </style>
    <?php
}

function customer_option(string $value, string $label, ?string $current): void
{
    echo '<option value="' . customer_h($value) . '"' . ($current === $value ? ' selected' : '') . '>' . customer_h($label) . '</option>';
}

function customer_render_form(string $mode, array $row = []): void
{
    $isEdit = $mode === 'edit';
    $action = $isEdit ? 'edit' : 'add';
    $title = $isEdit ? 'Edit Customer' : 'Add Customer';
    customer_form_styles();
    ?>
    <form class="customer-form-page" method="POST" action="customers_action.php">
        <input type="hidden" name="action" value="<?= customer_h($action) ?>">
        <?php if ($isEdit): ?><input type="hidden" name="id" value="<?= customer_h($row['id'] ?? '') ?>"><?php endif; ?>

        <section class="customer-form-panel">
            <div class="customer-form-title"><i class="fa-solid fa-id-card"></i> <?= customer_h($title) ?> / Account Details</div>
            <div class="customer-form-grid">
                <div class="customer-field"><label for="customer_code">Customer Code</label><input id="customer_code" name="customer_code" value="<?= customer_h($row['customer_code'] ?? '') ?>" placeholder="Auto-generated if blank"></div>
                <div class="customer-field"><label for="customer_type">Customer Type</label><select id="customer_type" name="customer_type"><?php foreach (['individual'=>'Individual','company'=>'Company','government'=>'Government','ngo'=>'NGO','contractor'=>'Contractor'] as $v=>$l) customer_option($v,$l,$row['customer_type'] ?? 'company'); ?></select></div>
                <div class="customer-field"><label for="customer_group">Customer Group</label><select id="customer_group" name="customer_group"><?php foreach (['retail'=>'Retail','wholesale'=>'Wholesale','corporate'=>'Corporate','individual'=>'Individual'] as $v=>$l) customer_option($v,$l,$row['customer_group'] ?? 'retail'); ?></select></div>
                <div class="customer-field"><label for="name">Customer Name *</label><input id="name" name="name" required value="<?= customer_h($row['name'] ?? '') ?>" placeholder="Customer or account name"></div>
                <div class="customer-field"><label for="company_name">Company Name</label><input id="company_name" name="company_name" value="<?= customer_h($row['company_name'] ?? '') ?>" placeholder="Business or organization"></div>
                <div class="customer-field"><label for="contact_person">Primary Contact</label><input id="contact_person" name="contact_person" value="<?= customer_h($row['contact_person'] ?? '') ?>" placeholder="Main contact person"></div>
                <div class="customer-field"><label for="account_manager">Account Manager</label><input id="account_manager" name="account_manager" value="<?= customer_h($row['account_manager'] ?? '') ?>" placeholder="Assigned staff"></div>
                <div class="customer-field"><label for="customer_source">Customer Source</label><select id="customer_source" name="customer_source"><?php foreach ([''=>'Not specified','walk_in'=>'Walk-in','referral'=>'Referral','campaign'=>'Campaign','website'=>'Website','existing_client'=>'Existing client','field_sales'=>'Field sales'] as $v=>$l) customer_option($v,$l,$row['customer_source'] ?? ''); ?></select></div>
                <div class="customer-field"><label for="is_active">Status</label><select id="is_active" name="is_active"><?php customer_option('1','Active',(string)($row['is_active'] ?? '1')); customer_option('0','Inactive',(string)($row['is_active'] ?? '1')); ?></select></div>
            </div>
        </section>

        <section class="customer-form-panel">
            <div class="customer-form-title"><i class="fa-solid fa-address-book"></i> Contact, Tax & Sites</div>
            <div class="customer-form-grid">
                <div class="customer-field"><label for="phone">Phone</label><input id="phone" name="phone" value="<?= customer_h($row['phone'] ?? '') ?>" placeholder="+256..."></div>
                <div class="customer-field"><label for="email">Email</label><input id="email" type="email" name="email" value="<?= customer_h($row['email'] ?? '') ?>" placeholder="customer@example.com"></div>
                <div class="customer-field"><label for="tin_number">TIN</label><input id="tin_number" name="tin_number" value="<?= customer_h($row['tin_number'] ?? '') ?>" placeholder="Tax identification number"></div>
                <div class="customer-field"><label for="city">City</label><input id="city" name="city" value="<?= customer_h($row['city'] ?? '') ?>"></div>
                <div class="customer-field"><label for="country">Country</label><input id="country" name="country" value="<?= customer_h($row['country'] ?? 'Uganda') ?>"></div>
                <div class="customer-field wide"><label for="address">Physical Address</label><textarea id="address" name="address"><?= customer_h($row['address'] ?? '') ?></textarea></div>
                <div class="customer-field wide"><label for="billing_address">Billing Address</label><textarea id="billing_address" name="billing_address" placeholder="Leave blank to use physical address"><?= customer_h($row['billing_address'] ?? '') ?></textarea></div>
                <div class="customer-field wide"><label for="service_address">Installation / Service Address</label><textarea id="service_address" name="service_address" placeholder="Site, branch, or service location"><?= customer_h($row['service_address'] ?? '') ?></textarea></div>
            </div>
        </section>

        <section class="customer-form-panel">
            <div class="customer-form-title"><i class="fa-solid fa-scale-balanced"></i> Financial & Control Settings</div>
            <div class="customer-form-grid">
                <div class="customer-field"><label for="payment_terms">Payment Terms</label><select id="payment_terms" name="payment_terms"><?php foreach (['cash'=>'Cash','net_7'=>'Net 7','net_15'=>'Net 15','net_30'=>'Net 30'] as $v=>$l) customer_option($v,$l,$row['payment_terms'] ?? 'cash'); ?></select></div>
                <div class="customer-field"><label for="preferred_payment_method">Preferred Payment</label><select id="preferred_payment_method" name="preferred_payment_method"><?php foreach ([''=>'Not specified','cash'=>'Cash','bank'=>'Bank','mobile_money'=>'Mobile Money','card'=>'Card','cheque'=>'Cheque'] as $v=>$l) customer_option($v,$l,$row['preferred_payment_method'] ?? ''); ?></select></div>
                <div class="customer-field"><label for="credit_status">Credit Status</label><select id="credit_status" name="credit_status"><?php foreach (['good'=>'Good','watchlist'=>'Watchlist','on_hold'=>'On Hold'] as $v=>$l) customer_option($v,$l,$row['credit_status'] ?? 'good'); ?></select></div>
                <div class="customer-field"><label for="credit_limit">Credit Limit</label><input id="credit_limit" type="number" step="0.01" name="credit_limit" value="<?= customer_h($row['credit_limit'] ?? '0.00') ?>"></div>
                <div class="customer-field"><label for="credit_balance">Opening / Current Balance</label><input id="credit_balance" type="number" step="0.01" name="credit_balance" value="<?= customer_h($row['credit_balance'] ?? '0.00') ?>"></div>
                <div class="customer-field full"><label for="internal_notes">Internal Notes</label><textarea id="internal_notes" name="internal_notes" placeholder="Account risks, preferences, collection notes, service expectations"><?= customer_h($row['internal_notes'] ?? '') ?></textarea></div>
            </div>
        </section>

        <section class="customer-form-panel">
            <div class="customer-form-actions">
                <a class="customer-form-btn" href="<?= customer_h(onyx_legacy_url('customers.php')) ?>"><i class="fa-solid fa-arrow-left"></i> Back</a>
                <button class="customer-form-btn primary" type="submit"><i class="fa-solid fa-floppy-disk"></i> <?= $isEdit ? 'Update Customer' : 'Save Customer' ?></button>
            </div>
        </section>
    </form>
    <?php
}

function ensure_customer_tables(PDO $pdo): void
{
    $pdo->exec("CREATE TABLE IF NOT EXISTS customer_equipment (
        id BIGINT(20) NOT NULL AUTO_INCREMENT,
        tenant_id BIGINT(20) NOT NULL,
        customer_id BIGINT(20) NOT NULL,
        equipment_type VARCHAR(155) NOT NULL,
        model VARCHAR(155) DEFAULT NULL,
        serial_number VARCHAR(100) DEFAULT NULL,
        installation_date DATE DEFAULT NULL,
        warranty_expiry DATE DEFAULT NULL,
        assigned_technician VARCHAR(155) DEFAULT NULL,
        notes TEXT DEFAULT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY idx_customer_equipment_tenant (tenant_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    $pdo->exec("CREATE TABLE IF NOT EXISTS customer_maintenance (
        id BIGINT(20) NOT NULL AUTO_INCREMENT,
        tenant_id BIGINT(20) NOT NULL,
        customer_id BIGINT(20) NOT NULL,
        title VARCHAR(155) NOT NULL,
        scheduled_on DATE DEFAULT NULL,
        status VARCHAR(30) DEFAULT 'scheduled',
        notes TEXT DEFAULT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY idx_customer_maintenance_tenant (tenant_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    $pdo->exec("CREATE TABLE IF NOT EXISTS customer_contacts (
        id BIGINT(20) NOT NULL AUTO_INCREMENT,
        tenant_id BIGINT(20) NOT NULL,
        customer_id BIGINT(20) NOT NULL,
        name VARCHAR(155) NOT NULL,
        role VARCHAR(100) DEFAULT NULL,
        phone VARCHAR(80) DEFAULT NULL,
        email VARCHAR(155) DEFAULT NULL,
        is_primary TINYINT(1) DEFAULT 0,
        notes TEXT DEFAULT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY idx_customer_contacts_tenant (tenant_id, customer_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    $pdo->exec("CREATE TABLE IF NOT EXISTS customer_sites (
        id BIGINT(20) NOT NULL AUTO_INCREMENT,
        tenant_id BIGINT(20) NOT NULL,
        customer_id BIGINT(20) NOT NULL,
        site_name VARCHAR(155) NOT NULL,
        address TEXT DEFAULT NULL,
        contact_person VARCHAR(155) DEFAULT NULL,
        phone VARCHAR(80) DEFAULT NULL,
        notes TEXT DEFAULT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY idx_customer_sites_tenant (tenant_id, customer_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    $pdo->exec("CREATE TABLE IF NOT EXISTS customer_notes (
        id BIGINT(20) NOT NULL AUTO_INCREMENT,
        tenant_id BIGINT(20) NOT NULL,
        customer_id BIGINT(20) NOT NULL,
        note_type VARCHAR(50) DEFAULT 'general',
        note TEXT NOT NULL,
        created_by VARCHAR(155) DEFAULT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY idx_customer_notes_tenant (tenant_id, customer_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
}

ensure_customer_columns($pdo);
ensure_customer_tables($pdo);

if (!$action) {
    redirect_back('No action specified.', false);
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if ($action === 'add') {
        $context = onyx_page_start('Add Customer', 'Capture a complete customer profile for sales, service, statements, and account follow-up.');
        customer_render_form('add');
        onyx_page_end();
        exit();
    }

    if ($action === 'edit') {
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        if ($id <= 0) redirect_back('Missing customer id for edit.', false);
        $stmt = $pdo->prepare('SELECT * FROM customers WHERE id = ? AND tenant_id = ? LIMIT 1');
        $stmt->execute([$id, $tenant_id]);
        $row = $stmt->fetch();
        if (!$row) redirect_back('Customer not found.', false);
        $context = onyx_page_start('Edit Customer', 'Update customer profile, account settings, billing, service, and credit controls.');
        customer_render_form('edit', $row);
        onyx_page_end();
        exit();
    }

    if ($action === 'delete') {
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        if ($id <= 0) redirect_back('Missing customer id for delete.', false);
        $context = onyx_page_start('Delete Customer', 'Confirm deletion');
        ?>
        <div class="panel span-6">
            <div class="panel-title"><i class="fa-solid fa-trash"></i> Confirm Delete</div>
            <p>Are you sure you want to delete this customer?</p>
            <form method="POST" action="customers_action.php">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="<?= htmlspecialchars((string) $id) ?>">
                <button class="action-btn" type="submit">Confirm Delete</button>
            </form>
        </div>
        <?php
        onyx_page_end();
        exit();
    }

    if ($action === 'view') {
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        if ($id <= 0) redirect_back('Missing customer id for view.', false);
        $stmt = $pdo->prepare('SELECT * FROM customers WHERE id = ? AND tenant_id = ? LIMIT 1');
        $stmt->execute([$id, $tenant_id]);
        $row = $stmt->fetch();
        if (!$row) redirect_back('Customer not found.', false);
        $equipment = onyx_rows('SELECT * FROM customer_equipment WHERE tenant_id = :tenant_id AND customer_id = :customer_id ORDER BY installation_date DESC, equipment_type ASC', ['tenant_id' => $tenant_id, 'customer_id' => $id]);
        $maintenance = onyx_rows('SELECT * FROM customer_maintenance WHERE tenant_id = :tenant_id AND customer_id = :customer_id ORDER BY scheduled_on DESC, created_at DESC', ['tenant_id' => $tenant_id, 'customer_id' => $id]);
        $contacts = onyx_rows('SELECT * FROM customer_contacts WHERE tenant_id = :tenant_id AND customer_id = :customer_id ORDER BY is_primary DESC, name ASC', ['tenant_id' => $tenant_id, 'customer_id' => $id]);
        $sites = onyx_rows('SELECT * FROM customer_sites WHERE tenant_id = :tenant_id AND customer_id = :customer_id ORDER BY site_name ASC', ['tenant_id' => $tenant_id, 'customer_id' => $id]);
        $notes = onyx_rows('SELECT * FROM customer_notes WHERE tenant_id = :tenant_id AND customer_id = :customer_id ORDER BY created_at DESC', ['tenant_id' => $tenant_id, 'customer_id' => $id]);
        $invoices = onyx_rows(
            'SELECT i.id, i.invoice_number, i.invoice_type, i.invoice_date, i.due_date, i.total, i.status, COALESCE(SUM(p.amount), 0) AS paid_amount
             FROM invoices i
             LEFT JOIN invoice_payments p ON p.invoice_id = i.id AND p.tenant_id = i.tenant_id
             WHERE i.tenant_id = :tenant_id AND i.customer_id = :customer_id
             GROUP BY i.id, i.invoice_number, i.invoice_type, i.invoice_date, i.due_date, i.total, i.status
             ORDER BY i.invoice_date DESC, i.id DESC',
            ['tenant_id' => $tenant_id, 'customer_id' => $id]
        );
        $payments = onyx_rows(
            'SELECT p.payment_date, p.amount, p.method, p.reference, i.invoice_number
             FROM invoice_payments p
             INNER JOIN invoices i ON i.id = p.invoice_id AND i.tenant_id = p.tenant_id
             WHERE p.tenant_id = :tenant_id AND i.customer_id = :customer_id
             ORDER BY p.payment_date DESC, p.id DESC',
            ['tenant_id' => $tenant_id, 'customer_id' => $id]
        );
        $invoiceTotal = array_sum(array_map(static fn (array $invoice): float => (float) ($invoice['total'] ?? 0), $invoices));
        $paidTotal = array_sum(array_map(static fn (array $invoice): float => (float) ($invoice['paid_amount'] ?? 0), $invoices));
        $accountBalance = $invoiceTotal - $paidTotal;
        $openInvoices = count(array_filter($invoices, static fn (array $invoice): bool => (float) ($invoice['total'] ?? 0) > (float) ($invoice['paid_amount'] ?? 0)));
        $nextMaintenance = $maintenance[0]['scheduled_on'] ?? null;
        $context = onyx_page_start('Customer Account', 'Account profile, financial info, and installed equipment for ' . $row['name']);
        customer_form_styles();
        ?>
        <div class="customer-profile-page">
            <?php if (isset($_GET['success']) || isset($_GET['error'])): ?>
                <div class="customer-alert <?= isset($_GET['error']) ? 'error' : '' ?>">
                    <?= customer_h($_GET['success'] ?? $_GET['error'] ?? '') ?>
                </div>
            <?php endif; ?>

            <section class="customer-profile-panel">
                <div class="customer-form-actions" style="justify-content:space-between;margin-bottom:14px;">
                    <a class="customer-form-btn" href="<?= customer_h(onyx_legacy_url('customers.php')) ?>"><i class="fa-solid fa-arrow-left"></i> Back</a>
                    <span class="customer-action-row">
                        <a class="customer-form-btn primary" href="<?= customer_h(onyx_legacy_url('sales_action.php?action=create_invoice&invoice_type=quotation')) ?>"><i class="fa-solid fa-file-invoice"></i> Quotation</a>
                        <a class="customer-form-btn" href="<?= customer_h(onyx_legacy_url('customers_action.php?action=edit&id=' . $id)) ?>"><i class="fa-solid fa-pen-to-square"></i> Edit</a>
                        <a class="customer-form-btn" target="_blank" href="<?= customer_h(onyx_legacy_url('customers_action.php?action=print&id=' . $id)) ?>"><i class="fa-solid fa-print"></i> Statement</a>
                        <form method="POST" action="customers_action.php" style="display:inline-flex;">
                            <input type="hidden" name="action" value="toggle_status">
                            <input type="hidden" name="customer_id" value="<?= customer_h($id) ?>">
                            <button class="customer-form-btn <?= ((int) ($row['is_active'] ?? 1)) === 1 ? 'danger' : 'primary' ?>" type="submit">
                                <i class="fa-solid fa-circle-power-off"></i>
                                <?= ((int) ($row['is_active'] ?? 1)) === 1 ? 'Archive' : 'Reactivate' ?>
                            </button>
                        </form>
                    </span>
                </div>
                <div class="customer-hero">
                    <div class="customer-identity">
                        <div class="customer-kicker"><?= customer_h($row['customer_code'] ?: 'Uncoded customer') ?> / <?= customer_h($row['customer_group'] ?? 'retail') ?></div>
                        <h2><?= customer_h($row['name']) ?></h2>
                        <div class="customer-identity-meta">
                            <span><i class="fa-solid fa-building"></i> <?= customer_h($row['company_name'] ?: 'No company') ?></span>
                            <span><i class="fa-solid fa-user"></i> <?= customer_h($row['contact_person'] ?: 'No primary contact') ?></span>
                            <span><i class="fa-solid fa-phone"></i> <?= customer_h($row['phone'] ?: 'No phone') ?></span>
                            <span><i class="fa-solid fa-envelope"></i> <?= customer_h($row['email'] ?: 'No email') ?></span>
                        </div>
                    </div>
                    <div class="customer-status-stack">
                        <div class="customer-status-pill <?= ((int) ($row['is_active'] ?? 1)) === 1 ? 'ok' : 'danger' ?>"><span>Lifecycle</span><strong><?= ((int) ($row['is_active'] ?? 1)) === 1 ? 'Active' : 'Archived' ?></strong></div>
                        <div class="customer-status-pill <?= $accountBalance > 0 ? 'warn' : 'ok' ?>"><span>Sales Balance</span><strong><?= customer_h(customer_money($accountBalance, $currency)) ?></strong></div>
                        <div class="customer-status-pill <?= ((float) ($row['credit_limit'] ?? 0) > 0 && $accountBalance > (float) $row['credit_limit']) ? 'danger' : 'ok' ?>"><span>Credit Limit</span><strong><?= customer_h(customer_money($row['credit_limit'] ?? 0, $currency)) ?></strong></div>
                        <div class="customer-status-pill <?= $nextMaintenance ? 'warn' : 'ok' ?>"><span>Next Maintenance</span><strong><?= customer_h($nextMaintenance ?: 'None') ?></strong></div>
                    </div>
                </div>
            </section>

            <div class="customer-tabs" data-customer-tabs>
                <button class="customer-tab active" type="button" data-tab="overview">Overview</button>
                <button class="customer-tab" type="button" data-tab="finance">Finance</button>
                <button class="customer-tab" type="button" data-tab="operations">Operations</button>
                <button class="customer-tab" type="button" data-tab="contacts">Contacts & Sites</button>
                <button class="customer-tab" type="button" data-tab="timeline">Timeline</button>
            </div>

            <div class="customer-tab-panel active" data-tab-panel="overview">
                <section class="customer-profile-panel">
                    <div class="customer-profile-title"><i class="fa-solid fa-id-badge"></i> Account Overview</div>
                    <div class="customer-profile-grid">
                        <div class="customer-profile-card"><strong>Profile</strong><span><?= customer_h($row['company_name'] ?: '-') ?><br><?= customer_h($row['contact_person'] ?: '-') ?><br><?= customer_h($row['phone'] ?: '-') ?></span></div>
                        <div class="customer-profile-card"><strong>Financial</strong><span>Credit Limit: <?= customer_h(customer_money($row['credit_limit'] ?? 0, $currency)) ?><br>Sales Balance: <?= customer_h(customer_money($accountBalance, $currency)) ?><br>Credit: <?= customer_h($row['credit_status'] ?? 'good') ?></span></div>
                        <div class="customer-profile-card"><strong>Account</strong><span>Code: <?= customer_h($row['customer_code'] ?: '-') ?><br>Group: <?= customer_h($row['customer_group'] ?? 'retail') ?><br>Type: <?= customer_h($row['customer_type'] ?? 'company') ?></span></div>
                        <div class="customer-profile-card"><strong>Control</strong><span>Status: <?= ((int) ($row['is_active'] ?? 1)) === 1 ? 'Active' : 'Archived' ?><br>Manager: <?= customer_h($row['account_manager'] ?: '-') ?><br>Source: <?= customer_h($row['customer_source'] ?: '-') ?></span></div>
                        <div class="customer-profile-card"><strong>Billing</strong><span>Terms: <?= customer_h($row['payment_terms'] ?? 'cash') ?><br>Payment: <?= customer_h($row['preferred_payment_method'] ?: '-') ?><br>TIN: <?= customer_h($row['tin_number'] ?: '-') ?></span></div>
                        <div class="customer-profile-card"><strong>Address</strong><span><?= customer_h($row['city'] ?: '-') ?>, <?= customer_h($row['country'] ?: '-') ?><br><?= customer_h($row['address'] ?: '-') ?></span></div>
                        <div class="customer-profile-card"><strong>Billing Site</strong><span><?= customer_h($row['billing_address'] ?: ($row['address'] ?: '-')) ?></span></div>
                        <div class="customer-profile-card"><strong>Service Site</strong><span><?= customer_h($row['service_address'] ?: '-') ?></span></div>
                    </div>
                    <?php if (($row['internal_notes'] ?? '') !== ''): ?>
                        <div class="customer-table-wrap" style="margin-top:14px;"><table class="customer-table"><tbody><tr><th>Internal Notes</th><td><?= customer_h($row['internal_notes']) ?></td></tr></tbody></table></div>
                    <?php endif; ?>
                </section>
            </div>

            <div class="customer-tab-panel" data-tab-panel="finance">
                <section class="customer-profile-panel">
                    <div class="customer-profile-title"><i class="fa-solid fa-scale-balanced"></i> Financial Lifecycle</div>
                    <div class="customer-profile-grid">
                        <div class="customer-profile-card"><strong>Total Billed</strong><span><?= customer_h(customer_money($invoiceTotal, $currency)) ?><br><?= count($invoices) ?> documents</span></div>
                        <div class="customer-profile-card"><strong>Total Paid</strong><span><?= customer_h(customer_money($paidTotal, $currency)) ?><br><?= count($payments) ?> payments</span></div>
                        <div class="customer-profile-card"><strong>Open Balance</strong><span><?= customer_h(customer_money($accountBalance, $currency)) ?><br><?= $openInvoices ?> open invoice(s)</span></div>
                        <div class="customer-profile-card"><strong>Credit Control</strong><span><?= customer_h($row['credit_status'] ?? 'good') ?><br>Limit <?= customer_h(customer_money($row['credit_limit'] ?? 0, $currency)) ?></span></div>
                    </div>
                </section>

                <section class="customer-profile-panel">
                    <div class="customer-profile-title"><i class="fa-solid fa-file-invoice-dollar"></i> Invoices & Quotations</div>
                    <?php onyx_table_html(['Number', 'Type', 'Date', 'Due', 'Total', 'Paid', 'Balance', 'Status'], array_map(static fn (array $invoice): array => [
                        $invoice['invoice_number'],
                        $invoice['invoice_type'],
                        $invoice['invoice_date'] ?: '-',
                        $invoice['due_date'] ?: '-',
                        customer_money($invoice['total'], $currency),
                        customer_money($invoice['paid_amount'], $currency),
                        customer_money((float) $invoice['total'] - (float) $invoice['paid_amount'], $currency),
                        $invoice['status'] ?: '-',
                    ], $invoices)); ?>
                </section>

                <section class="customer-profile-panel">
                    <div class="customer-profile-title"><i class="fa-solid fa-money-bill-transfer"></i> Payment History</div>
                    <?php onyx_table(['Date', 'Invoice', 'Method', 'Amount', 'Reference'], array_map(static fn (array $payment): array => [
                        $payment['payment_date'] ?: '-',
                        $payment['invoice_number'] ?: '-',
                        $payment['method'] ?: '-',
                        customer_money($payment['amount'], $currency),
                        $payment['reference'] ?: '-',
                    ], $payments)); ?>
                </section>
            </div>

            <div class="customer-tab-panel" data-tab-panel="operations">
            <section class="customer-profile-panel">
                <div class="customer-profile-title"><i class="fa-solid fa-tools"></i> Add Installed Equipment</div>
                <form class="customer-form-grid" method="POST" action="customers_action.php">
                <input type="hidden" name="action" value="add_equipment">
                <input type="hidden" name="customer_id" value="<?= customer_h($id) ?>">
                <div class="customer-field"><label>Equipment Type</label><input type="text" name="equipment_type" required></div>
                <div class="customer-field"><label>Model</label><input type="text" name="model"></div>
                <div class="customer-field"><label>Serial Number</label><input type="text" name="serial_number"></div>
                <div class="customer-field"><label>Installation Date</label><input type="date" name="installation_date"></div>
                <div class="customer-field"><label>Warranty Expiry</label><input type="date" name="warranty_expiry"></div>
                <div class="customer-field"><label>Assigned Technician</label><input type="text" name="assigned_technician"></div>
                <div class="customer-field full"><label>Notes</label><textarea name="notes"></textarea></div>
                <div class="customer-field full"><button class="customer-form-btn primary" type="submit"><i class="fa-solid fa-floppy-disk"></i> Save Equipment</button></div>
                </form>
            </section>

            <section class="customer-profile-panel">
                <div class="customer-profile-title"><i class="fa-solid fa-calendar-check"></i> Schedule Maintenance</div>
                <form class="customer-form-grid" method="POST" action="customers_action.php">
                <input type="hidden" name="action" value="add_maintenance">
                <input type="hidden" name="customer_id" value="<?= customer_h($id) ?>">
                <div class="customer-field wide"><label>Title</label><input type="text" name="title" required></div>
                <div class="customer-field"><label>Scheduled On</label><input type="date" name="scheduled_on"></div>
                <div class="customer-field"><label>Status</label><select name="status"><?php foreach (['scheduled'=>'Scheduled','in_progress'=>'In Progress','completed'=>'Completed'] as $v=>$l) customer_option($v,$l,'scheduled'); ?></select></div>
                <div class="customer-field full"><label>Notes</label><textarea name="notes"></textarea></div>
                <div class="customer-field full"><button class="customer-form-btn primary" type="submit"><i class="fa-solid fa-floppy-disk"></i> Save Maintenance</button></div>
                </form>
            </section>

            <section class="customer-profile-panel">
                <div class="customer-profile-title"><i class="fa-solid fa-satellite-dish"></i> Installed Equipment</div>
                <?php onyx_table_html(['Equipment', 'Model', 'Serial', 'Installed On', 'Warranty', 'Technician', 'Action'], array_map(static fn (array $item): array => [
                    $item['equipment_type'],
                    $item['model'] ?: '-',
                    $item['serial_number'] ?: '-',
                    $item['installation_date'] ?: '-',
                    $item['warranty_expiry'] ?: '-',
                    $item['assigned_technician'] ?: '-',
                    customer_related_delete_form('equipment', (int) $item['id'], $id),
                ], $equipment)); ?>
            </section>

            <section class="customer-profile-panel">
                <div class="customer-profile-title"><i class="fa-solid fa-calendar-alt"></i> Maintenance Schedule</div>
                <?php onyx_table_html(['Title', 'Scheduled On', 'Status', 'Notes', 'Actions'], array_map(static fn (array $item): array => [
                    $item['title'],
                    $item['scheduled_on'] ?: '-',
                    $item['status'] ?: '-',
                    $item['notes'] ?: '-',
                    customer_maintenance_actions($item, $id),
                ], $maintenance)); ?>
            </section>
            </div>

            <div class="customer-tab-panel" data-tab-panel="contacts">
            <section class="customer-profile-panel">
                <div class="customer-profile-title"><i class="fa-solid fa-address-book"></i> Contacts, Sites & Notes</div>
                <div class="customer-form-grid">
                    <form class="customer-field wide" method="POST" action="customers_action.php">
                        <input type="hidden" name="action" value="add_contact">
                        <input type="hidden" name="customer_id" value="<?= customer_h($id) ?>">
                        <label>Contact</label><input name="name" required placeholder="Name">
                        <input name="role" placeholder="Role" style="margin-top:8px;">
                        <input name="phone" placeholder="Phone" style="margin-top:8px;">
                        <input type="email" name="email" placeholder="Email" style="margin-top:8px;">
                        <label style="margin-top:8px;"><input type="checkbox" name="is_primary" value="1" style="min-height:auto;width:auto;"> Primary contact</label>
                        <textarea name="notes" placeholder="Notes" style="margin-top:8px;"></textarea>
                        <button class="customer-form-btn primary" type="submit" style="margin-top:8px;"><i class="fa-solid fa-user-plus"></i> Save Contact</button>
                    </form>
                    <form class="customer-field wide" method="POST" action="customers_action.php">
                        <input type="hidden" name="action" value="add_site">
                        <input type="hidden" name="customer_id" value="<?= customer_h($id) ?>">
                        <label>Site</label><input name="site_name" required placeholder="Site name">
                        <textarea name="address" placeholder="Address" style="margin-top:8px;"></textarea>
                        <input name="contact_person" placeholder="Contact person" style="margin-top:8px;">
                        <input name="phone" placeholder="Phone" style="margin-top:8px;">
                        <textarea name="notes" placeholder="Notes" style="margin-top:8px;"></textarea>
                        <button class="customer-form-btn primary" type="submit" style="margin-top:8px;"><i class="fa-solid fa-location-dot"></i> Save Site</button>
                    </form>
                    <form class="customer-field full" method="POST" action="customers_action.php">
                        <input type="hidden" name="action" value="add_note">
                        <input type="hidden" name="customer_id" value="<?= customer_h($id) ?>">
                        <label>Account Note</label>
                        <select name="note_type" style="margin-bottom:8px;"><?php foreach (['general'=>'General','sales'=>'Sales','support'=>'Support','collections'=>'Collections'] as $v=>$l) customer_option($v,$l,'general'); ?></select>
                        <textarea name="note" required placeholder="Add an account note"></textarea>
                        <button class="customer-form-btn primary" type="submit" style="margin-top:8px;"><i class="fa-solid fa-note-sticky"></i> Save Note</button>
                    </form>
                </div>
            </section>

            <section class="customer-profile-panel">
                <div class="customer-profile-title"><i class="fa-solid fa-address-book"></i> Contacts</div>
                <?php onyx_table_html(['Name', 'Role', 'Phone', 'Email', 'Primary', 'Notes', 'Action'], array_map(static fn (array $item): array => [
                    $item['name'],
                    $item['role'] ?: '-',
                    $item['phone'] ?: '-',
                    $item['email'] ?: '-',
                    ((int) $item['is_primary']) === 1 ? 'Yes' : 'No',
                    $item['notes'] ?: '-',
                    customer_related_delete_form('contact', (int) $item['id'], $id),
                ], $contacts)); ?>
            </section>

            <section class="customer-profile-panel">
                <div class="customer-profile-title"><i class="fa-solid fa-location-dot"></i> Sites</div>
                <?php onyx_table_html(['Site', 'Address', 'Contact', 'Phone', 'Notes', 'Action'], array_map(static fn (array $item): array => [
                    $item['site_name'],
                    $item['address'] ?: '-',
                    $item['contact_person'] ?: '-',
                    $item['phone'] ?: '-',
                    $item['notes'] ?: '-',
                    customer_related_delete_form('site', (int) $item['id'], $id),
                ], $sites)); ?>
            </section>

            <section class="customer-profile-panel">
                <div class="customer-profile-title"><i class="fa-solid fa-note-sticky"></i> Account Notes</div>
                <?php onyx_table_html(['Type', 'Note', 'By', 'Created', 'Action'], array_map(static fn (array $item): array => [
                    $item['note_type'] ?: 'general',
                    $item['note'],
                    $item['created_by'] ?: '-',
                    $item['created_at'] ?: '-',
                    customer_related_delete_form('note', (int) $item['id'], $id),
                ], $notes)); ?>
            </section>
            </div>

            <div class="customer-tab-panel" data-tab-panel="timeline">
                <section class="customer-profile-panel">
                    <div class="customer-profile-title"><i class="fa-solid fa-clock-rotate-left"></i> Customer Timeline</div>
                    <div class="customer-timeline">
                        <div class="customer-timeline-item"><strong>Account Created</strong><span><?= customer_h($row['created_at'] ?? '-') ?></span></div>
                        <div class="customer-timeline-item"><strong>Last Profile Update</strong><span><?= customer_h($row['updated_at'] ?? '-') ?></span></div>
                        <div class="customer-timeline-item"><strong>Invoices / Open Balance</strong><span><?= count($invoices) ?> documents / <?= customer_h(customer_money($accountBalance, $currency)) ?></span></div>
                        <div class="customer-timeline-item"><strong>Installed Equipment</strong><span><?= count($equipment) ?> item(s)</span></div>
                        <div class="customer-timeline-item"><strong>Maintenance Jobs</strong><span><?= count($maintenance) ?> item(s)</span></div>
                        <div class="customer-timeline-item"><strong>Contacts & Sites</strong><span><?= count($contacts) ?> contact(s), <?= count($sites) ?> site(s)</span></div>
                    </div>
                </section>
            </div>

            <script>
                document.querySelectorAll('[data-customer-tabs] .customer-tab').forEach(function (button) {
                    button.addEventListener('click', function () {
                        const tab = button.getAttribute('data-tab');
                        document.querySelectorAll('[data-customer-tabs] .customer-tab').forEach(function (item) {
                            item.classList.toggle('active', item === button);
                        });
                        document.querySelectorAll('[data-tab-panel]').forEach(function (panel) {
                            panel.classList.toggle('active', panel.getAttribute('data-tab-panel') === tab);
                        });
                    });
                });
            </script>
        </div>
        <?php
        onyx_page_end();
        exit();
    }

    if ($action === 'print') {
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        if ($id <= 0) redirect_back('Missing customer id for print.', false);
        $stmt = $pdo->prepare('SELECT * FROM customers WHERE id = ? AND tenant_id = ? LIMIT 1');
        $stmt->execute([$id, $tenant_id]);
        $row = $stmt->fetch();
        if (!$row) redirect_back('Customer not found.', false);
        $context = onyx_context();
        $currency = $context['currency'];
        $statementInvoices = onyx_rows(
            'SELECT i.invoice_number, i.invoice_type, i.invoice_date, i.due_date, i.total, i.status, COALESCE(SUM(p.amount), 0) AS paid_amount
             FROM invoices i
             LEFT JOIN invoice_payments p ON p.invoice_id = i.id AND p.tenant_id = i.tenant_id
             WHERE i.tenant_id = :tenant_id AND i.customer_id = :customer_id
             GROUP BY i.id, i.invoice_number, i.invoice_type, i.invoice_date, i.due_date, i.total, i.status
             ORDER BY i.invoice_date ASC, i.id ASC',
            ['tenant_id' => $tenant_id, 'customer_id' => $id]
        );
        $statementPayments = onyx_rows(
            'SELECT p.payment_date, p.amount, p.method, p.reference, i.invoice_number
             FROM invoice_payments p
             INNER JOIN invoices i ON i.id = p.invoice_id AND i.tenant_id = p.tenant_id
             WHERE p.tenant_id = :tenant_id AND i.customer_id = :customer_id
             ORDER BY p.payment_date ASC, p.id ASC',
            ['tenant_id' => $tenant_id, 'customer_id' => $id]
        );
        $statementTotal = array_sum(array_map(static fn (array $invoice): float => (float) ($invoice['total'] ?? 0), $statementInvoices));
        $statementPaid = array_sum(array_map(static fn (array $invoice): float => (float) ($invoice['paid_amount'] ?? 0), $statementInvoices));
        $statementDue = $statementTotal - $statementPaid;
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <title>Statement - <?= customer_h($row['name']) ?></title>
            <?php customer_form_styles(); ?>
        </head>
        <body class="customer-print-body">
            <div class="customer-print-sheet">
                <div class="customer-print-head">
                    <div class="customer-print-brand">
                        <h1><?= customer_h($context['company_name']) ?></h1>
                        <span>Customer account statement</span>
                    </div>
                    <div>
                        <div class="customer-print-title">Statement</div>
                        <div class="customer-print-meta">Generated <?= customer_h(date('M d, Y')) ?></div>
                    </div>
                </div>

                <div class="customer-print-grid">
                    <div class="customer-print-card"><strong>Customer</strong><span><?= customer_h($row['name']) ?><br><?= customer_h($row['customer_code'] ?: '-') ?></span></div>
                    <div class="customer-print-card"><strong>Contact</strong><span><?= customer_h($row['contact_person'] ?: '-') ?><br><?= customer_h($row['phone'] ?: '-') ?></span></div>
                    <div class="customer-print-card"><strong>Billing</strong><span><?= customer_h($row['payment_terms'] ?? 'cash') ?><br>Limit <?= customer_h(customer_money($row['credit_limit'] ?? 0, $currency)) ?></span></div>
                    <div class="customer-print-card"><strong>Balance</strong><span><?= customer_h(customer_money($statementDue, $currency)) ?><br><?= customer_h($row['credit_status'] ?? 'good') ?></span></div>
                </div>

                <table class="customer-print-table">
                    <thead><tr><th>Document</th><th>Type</th><th>Date</th><th>Due</th><th>Total</th><th>Paid</th><th>Balance</th><th>Status</th></tr></thead>
                    <tbody>
                        <?php if ($statementInvoices === []): ?>
                            <tr><td colspan="8">No sales documents found for this customer.</td></tr>
                        <?php else: ?>
                            <?php foreach ($statementInvoices as $invoice): ?>
                                <?php $invoiceBalance = (float) $invoice['total'] - (float) $invoice['paid_amount']; ?>
                                <tr>
                                    <td><?= customer_h($invoice['invoice_number']) ?></td>
                                    <td><?= customer_h($invoice['invoice_type']) ?></td>
                                    <td><?= customer_h($invoice['invoice_date'] ?: '-') ?></td>
                                    <td><?= customer_h($invoice['due_date'] ?: '-') ?></td>
                                    <td><?= customer_h(customer_money($invoice['total'], $currency)) ?></td>
                                    <td><?= customer_h(customer_money($invoice['paid_amount'], $currency)) ?></td>
                                    <td><?= customer_h(customer_money($invoiceBalance, $currency)) ?></td>
                                    <td><?= customer_h($invoice['status'] ?: '-') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>

                <?php if ($statementPayments !== []): ?>
                    <h3>Payments</h3>
                    <table class="customer-print-table">
                        <thead><tr><th>Date</th><th>Invoice</th><th>Method</th><th>Amount</th><th>Reference</th></tr></thead>
                        <tbody>
                            <?php foreach ($statementPayments as $payment): ?>
                                <tr>
                                    <td><?= customer_h($payment['payment_date'] ?: '-') ?></td>
                                    <td><?= customer_h($payment['invoice_number'] ?: '-') ?></td>
                                    <td><?= customer_h($payment['method'] ?: '-') ?></td>
                                    <td><?= customer_h(customer_money($payment['amount'], $currency)) ?></td>
                                    <td><?= customer_h($payment['reference'] ?: '-') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>

                <div class="customer-print-total">
                    <div><span>Total billed</span><strong><?= customer_h(customer_money($statementTotal, $currency)) ?></strong></div>
                    <div><span>Total paid</span><strong><?= customer_h(customer_money($statementPaid, $currency)) ?></strong></div>
                    <div class="due"><span>Outstanding</span><strong><?= customer_h(customer_money($statementDue, $currency)) ?></strong></div>
                </div>

                <button class="no-print" onclick="window.print()" style="margin-top:24px;padding:10px 14px;">Print Statement</button>
            </div>
            <script>window.print();</script>
        </body>
        </html>
        <?php
        exit();
    }

    if ($action === 'maintenance') {
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        if ($id <= 0) {
            $customers = onyx_rows(
                'SELECT id, name FROM customers WHERE tenant_id = :tenant_id ORDER BY name ASC',
                ['tenant_id' => $tenant_id]
            );
            $context = onyx_page_start('Schedule Maintenance', 'Choose a customer before planning maintenance.');
            customer_form_styles();
            ?>
            <form class="customer-form-page" method="GET" action="customers_action.php">
                <input type="hidden" name="action" value="maintenance">
                <section class="customer-form-panel">
                    <div class="customer-form-title"><i class="fa-solid fa-calendar-check"></i> Select Customer</div>
                    <div class="customer-form-grid">
                        <div class="customer-field wide">
                            <label for="maintenance_customer_id">Customer</label>
                            <select id="maintenance_customer_id" name="id" required>
                                <option value="">Choose customer</option>
                                <?php foreach ($customers as $customer): ?>
                                    <option value="<?= customer_h($customer['id']) ?>"><?= customer_h($customer['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="customer-field full">
                            <button class="customer-form-btn primary" type="submit"><i class="fa-solid fa-arrow-right"></i> Continue</button>
                        </div>
                    </div>
                </section>
            </form>
            <?php
            onyx_page_end();
            exit();
        }
        $stmt = $pdo->prepare('SELECT id, name FROM customers WHERE id = ? AND tenant_id = ? LIMIT 1');
        $stmt->execute([$id, $tenant_id]);
        $row = $stmt->fetch();
        if (!$row) redirect_back('Customer not found.', false);
        $context = onyx_page_start('Schedule Maintenance', 'Plan customer maintenance.');
        customer_form_styles();
        ?>
        <section class="customer-form-panel">
            <div class="customer-form-title"><i class="fa-solid fa-calendar-check"></i> Schedule Maintenance for <?= customer_h($row['name']) ?></div>
            <form class="customer-form-grid" method="POST" action="customers_action.php">
                <input type="hidden" name="action" value="add_maintenance">
                <input type="hidden" name="customer_id" value="<?= customer_h($id) ?>">
                <div class="customer-field wide"><label>Title</label><input type="text" name="title" required></div>
                <div class="customer-field"><label>Scheduled On</label><input type="date" name="scheduled_on"></div>
                <div class="customer-field"><label>Status</label><select name="status"><?php foreach (['scheduled'=>'Scheduled','in_progress'=>'In Progress','completed'=>'Completed'] as $v=>$l) customer_option($v,$l,'scheduled'); ?></select></div>
                <div class="customer-field full"><label>Notes</label><textarea name="notes"></textarea></div>
                <div class="customer-field full"><button class="customer-form-btn primary" type="submit"><i class="fa-solid fa-floppy-disk"></i> Save Maintenance</button></div>
            </form>
        </section>
        <?php
        onyx_page_end();
        exit();
    }

    redirect_back('Unsupported view action: ' . $action, false);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'add') {
        $name = customer_post_string('name');
        $email = customer_post_string('email');
        $phone = customer_post_string('phone');
        $company_name = customer_post_string('company_name');
        $contact_person = customer_post_string('contact_person');
        $address = customer_post_string('address');
        $city = customer_post_string('city');
        $country = customer_post_string('country', 'Uganda');
        $tin_number = customer_post_string('tin_number');
        $customer_group = customer_post_string('customer_group', 'retail');
        $customer_type = customer_post_string('customer_type', 'company');
        $billing_address = customer_post_string('billing_address');
        $service_address = customer_post_string('service_address');
        $payment_terms = customer_post_string('payment_terms', 'cash');
        $preferred_payment_method = customer_post_string('preferred_payment_method');
        $credit_status = customer_post_string('credit_status', 'good');
        $account_manager = customer_post_string('account_manager');
        $customer_source = customer_post_string('customer_source');
        $internal_notes = customer_post_string('internal_notes');
        if ($name === '') redirect_back('Name is required.', false);
        $code = customer_post_string('customer_code');
        if ($code === '') $code = 'CUST-' . strtoupper(substr(uniqid(), -6));
        $credit_limit = (float)($_POST['credit_limit'] ?? 0);
        $credit_balance = (float)($_POST['credit_balance'] ?? 0);
        $is_active = isset($_POST['is_active']) ? (int)$_POST['is_active'] : 1;
        $stmt = $pdo->prepare('INSERT INTO customers (tenant_id, customer_code, name, company_name, contact_person, customer_type, email, phone, address, billing_address, service_address, city, country, tin_number, customer_group, payment_terms, preferred_payment_method, credit_status, account_manager, customer_source, internal_notes, credit_limit, credit_balance, is_active, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())');
        $stmt->execute([$tenant_id, $code, $name, $company_name, $contact_person, $customer_type, $email, $phone, $address, $billing_address, $service_address, $city, $country, $tin_number, $customer_group, $payment_terms, $preferred_payment_method, $credit_status, $account_manager, $customer_source, $internal_notes, $credit_limit, $credit_balance, $is_active]);
        redirect_customer_profile((int) $pdo->lastInsertId(), 'Customer added successfully.');
    }

    if ($action === 'edit') {
        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        if ($id <= 0) redirect_back('Missing customer id.', false);
        if (! customer_exists($pdo, $tenant_id, $id)) redirect_back('Customer not found.', false);
        $name = customer_post_string('name');
        $email = customer_post_string('email');
        $phone = customer_post_string('phone');
        $company_name = customer_post_string('company_name');
        $contact_person = customer_post_string('contact_person');
        $address = customer_post_string('address');
        $city = customer_post_string('city');
        $country = customer_post_string('country', 'Uganda');
        $tin_number = customer_post_string('tin_number');
        $customer_group = customer_post_string('customer_group', 'retail');
        $customer_type = customer_post_string('customer_type', 'company');
        $billing_address = customer_post_string('billing_address');
        $service_address = customer_post_string('service_address');
        $payment_terms = customer_post_string('payment_terms', 'cash');
        $preferred_payment_method = customer_post_string('preferred_payment_method');
        $credit_status = customer_post_string('credit_status', 'good');
        $account_manager = customer_post_string('account_manager');
        $customer_source = customer_post_string('customer_source');
        $internal_notes = customer_post_string('internal_notes');
        if ($name === '') redirect_back('Name is required.', false);
        $code = customer_post_string('customer_code');
        if ($code === '') $code = 'CUST-' . strtoupper(substr(uniqid(), -6));
        $credit_limit = (float)($_POST['credit_limit'] ?? 0);
        $credit_balance = (float)($_POST['credit_balance'] ?? 0);
        $is_active = isset($_POST['is_active']) ? (int)$_POST['is_active'] : 1;
        $stmt = $pdo->prepare('UPDATE customers SET customer_code = ?, name = ?, company_name = ?, contact_person = ?, customer_type = ?, email = ?, phone = ?, address = ?, billing_address = ?, service_address = ?, city = ?, country = ?, tin_number = ?, customer_group = ?, payment_terms = ?, preferred_payment_method = ?, credit_status = ?, account_manager = ?, customer_source = ?, internal_notes = ?, credit_limit = ?, credit_balance = ?, is_active = ?, updated_at = NOW() WHERE id = ? AND tenant_id = ?');
        $stmt->execute([$code, $name, $company_name, $contact_person, $customer_type, $email, $phone, $address, $billing_address, $service_address, $city, $country, $tin_number, $customer_group, $payment_terms, $preferred_payment_method, $credit_status, $account_manager, $customer_source, $internal_notes, $credit_limit, $credit_balance, $is_active, $id, $tenant_id]);
        redirect_customer_profile($id, 'Customer updated successfully.');
    }

    if ($action === 'delete') {
        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        if ($id <= 0) redirect_back('Missing customer id.', false);
        if (! customer_exists($pdo, $tenant_id, $id)) redirect_back('Customer not found.', false);
        $invoiceCount = customer_count_rows(
            'SELECT COUNT(*) FROM invoices WHERE tenant_id = :tenant_id AND customer_id = :customer_id',
            ['tenant_id' => $tenant_id, 'customer_id' => $id]
        );
        if ($invoiceCount > 0) {
            $stmt = $pdo->prepare('UPDATE customers SET is_active = 0, updated_at = NOW() WHERE id = ? AND tenant_id = ?');
            $stmt->execute([$id, $tenant_id]);
            redirect_customer_profile($id, 'Customer has sales history, so the account was archived instead of deleted.');
        }
        $stmt = $pdo->prepare('DELETE FROM customer_equipment WHERE tenant_id = ? AND customer_id = ?');
        $stmt->execute([$tenant_id, $id]);
        $stmt = $pdo->prepare('DELETE FROM customer_maintenance WHERE tenant_id = ? AND customer_id = ?');
        $stmt->execute([$tenant_id, $id]);
        $stmt = $pdo->prepare('DELETE FROM customer_contacts WHERE tenant_id = ? AND customer_id = ?');
        $stmt->execute([$tenant_id, $id]);
        $stmt = $pdo->prepare('DELETE FROM customer_sites WHERE tenant_id = ? AND customer_id = ?');
        $stmt->execute([$tenant_id, $id]);
        $stmt = $pdo->prepare('DELETE FROM customer_notes WHERE tenant_id = ? AND customer_id = ?');
        $stmt->execute([$tenant_id, $id]);
        $stmt = $pdo->prepare('DELETE FROM customers WHERE id = ? AND tenant_id = ?');
        $stmt->execute([$id, $tenant_id]);
        redirect_back('Customer deleted successfully.');
    }

    if ($action === 'toggle_status') {
        $customer_id = isset($_POST['customer_id']) ? (int) $_POST['customer_id'] : 0;
        if (! customer_exists($pdo, $tenant_id, $customer_id)) redirect_back('Customer not found.', false);
        $current = (int) onyx_scalar(
            'SELECT is_active FROM customers WHERE id = :id AND tenant_id = :tenant_id',
            ['id' => $customer_id, 'tenant_id' => $tenant_id],
            1
        );
        $next = $current === 1 ? 0 : 1;
        $stmt = $pdo->prepare('UPDATE customers SET is_active = ?, updated_at = NOW() WHERE id = ? AND tenant_id = ?');
        $stmt->execute([$next, $customer_id, $tenant_id]);
        redirect_customer_profile($customer_id, $next === 1 ? 'Customer reactivated successfully.' : 'Customer archived successfully.');
    }

    if ($action === 'add_equipment') {
        $customer_id = isset($_POST['customer_id']) ? (int)$_POST['customer_id'] : 0;
        if ($customer_id <= 0) redirect_back('Customer is required.', false);
        if (! customer_exists($pdo, $tenant_id, $customer_id)) redirect_back('Customer not found.', false);
        $equipment_type = customer_post_string('equipment_type');
        if ($equipment_type === '') redirect_back('Equipment type is required.', false);
        $stmt = $pdo->prepare('INSERT INTO customer_equipment (tenant_id, customer_id, equipment_type, model, serial_number, installation_date, warranty_expiry, assigned_technician, notes, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())');
        $stmt->execute([$tenant_id, $customer_id, $equipment_type, customer_post_string('model'), customer_post_string('serial_number'), customer_post_nullable_date('installation_date'), customer_post_nullable_date('warranty_expiry'), customer_post_string('assigned_technician'), customer_post_string('notes')]);
        redirect_customer_profile($customer_id, 'Equipment added successfully.');
    }

    if ($action === 'add_maintenance') {
        $customer_id = isset($_POST['customer_id']) ? (int)$_POST['customer_id'] : 0;
        if ($customer_id <= 0) redirect_back('Customer is required.', false);
        if (! customer_exists($pdo, $tenant_id, $customer_id)) redirect_back('Customer not found.', false);
        $title = customer_post_string('title');
        if ($title === '') redirect_back('Maintenance title is required.', false);
        $stmt = $pdo->prepare('INSERT INTO customer_maintenance (tenant_id, customer_id, title, scheduled_on, status, notes, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())');
        $stmt->execute([$tenant_id, $customer_id, $title, customer_post_nullable_date('scheduled_on'), customer_post_string('status', 'scheduled'), customer_post_string('notes')]);
        redirect_customer_profile($customer_id, 'Maintenance scheduled successfully.');
    }

    if ($action === 'add_contact') {
        $customer_id = isset($_POST['customer_id']) ? (int)$_POST['customer_id'] : 0;
        if (! customer_exists($pdo, $tenant_id, $customer_id)) redirect_back('Customer not found.', false);
        $name = customer_post_string('name');
        if ($name === '') redirect_back('Contact name is required.', false);
        $stmt = $pdo->prepare('INSERT INTO customer_contacts (tenant_id, customer_id, name, role, phone, email, is_primary, notes, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())');
        $stmt->execute([$tenant_id, $customer_id, $name, customer_post_string('role'), customer_post_string('phone'), customer_post_string('email'), isset($_POST['is_primary']) ? 1 : 0, customer_post_string('notes')]);
        redirect_customer_profile($customer_id, 'Contact added successfully.');
    }

    if ($action === 'add_site') {
        $customer_id = isset($_POST['customer_id']) ? (int)$_POST['customer_id'] : 0;
        if (! customer_exists($pdo, $tenant_id, $customer_id)) redirect_back('Customer not found.', false);
        $site_name = customer_post_string('site_name');
        if ($site_name === '') redirect_back('Site name is required.', false);
        $stmt = $pdo->prepare('INSERT INTO customer_sites (tenant_id, customer_id, site_name, address, contact_person, phone, notes, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())');
        $stmt->execute([$tenant_id, $customer_id, $site_name, customer_post_string('address'), customer_post_string('contact_person'), customer_post_string('phone'), customer_post_string('notes')]);
        redirect_customer_profile($customer_id, 'Site added successfully.');
    }

    if ($action === 'add_note') {
        $customer_id = isset($_POST['customer_id']) ? (int)$_POST['customer_id'] : 0;
        if (! customer_exists($pdo, $tenant_id, $customer_id)) redirect_back('Customer not found.', false);
        $note = customer_post_string('note');
        if ($note === '') redirect_back('Note is required.', false);
        $stmt = $pdo->prepare('INSERT INTO customer_notes (tenant_id, customer_id, note_type, note, created_by, created_at) VALUES (?, ?, ?, ?, ?, NOW())');
        $stmt->execute([$tenant_id, $customer_id, customer_post_string('note_type', 'general'), $note, session('user_name', 'Operator')]);
        redirect_customer_profile($customer_id, 'Note added successfully.');
    }

    if ($action === 'update_maintenance_status') {
        $customer_id = isset($_POST['customer_id']) ? (int) $_POST['customer_id'] : 0;
        $maintenance_id = isset($_POST['maintenance_id']) ? (int) $_POST['maintenance_id'] : 0;
        $status = customer_post_string('status', 'scheduled');
        if (! in_array($status, ['scheduled', 'in_progress', 'completed'], true)) {
            redirect_customer_profile($customer_id, 'Unsupported maintenance status.', false);
        }
        if (! customer_exists($pdo, $tenant_id, $customer_id)) redirect_back('Customer not found.', false);
        $stmt = $pdo->prepare('UPDATE customer_maintenance SET status = ? WHERE id = ? AND customer_id = ? AND tenant_id = ?');
        $stmt->execute([$status, $maintenance_id, $customer_id, $tenant_id]);
        redirect_customer_profile($customer_id, 'Maintenance status updated successfully.');
    }

    if ($action === 'delete_related') {
        $customer_id = isset($_POST['customer_id']) ? (int) $_POST['customer_id'] : 0;
        $item_id = isset($_POST['item_id']) ? (int) $_POST['item_id'] : 0;
        $type = customer_post_string('related_type');
        if (! customer_exists($pdo, $tenant_id, $customer_id)) redirect_back('Customer not found.', false);
        $tables = [
            'equipment' => 'customer_equipment',
            'maintenance' => 'customer_maintenance',
            'contact' => 'customer_contacts',
            'site' => 'customer_sites',
            'note' => 'customer_notes',
        ];
        if (! isset($tables[$type]) || $item_id <= 0) {
            redirect_customer_profile($customer_id, 'Unsupported customer record.', false);
        }
        $stmt = $pdo->prepare('DELETE FROM ' . $tables[$type] . ' WHERE id = ? AND customer_id = ? AND tenant_id = ?');
        $stmt->execute([$item_id, $customer_id, $tenant_id]);
        redirect_customer_profile($customer_id, 'Customer record removed successfully.');
    }

    redirect_back('Unsupported action.', false);
}
