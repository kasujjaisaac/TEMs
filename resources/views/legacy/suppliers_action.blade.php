<?php

$action = $_GET['action'] ?? $_POST['action'] ?? null;
$tenant_id = (int) (onyx_tenant_id() ?? 0);
$pdo = onyx_db();

function supplier_h(mixed $value): string
{
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
}

function supplier_redirect(string $msg = '', bool $success = true): void
{
    $q = $msg !== '' ? ($success ? '?success=' : '?error=') . urlencode($msg) : '';
    header('Location: suppliers.php' . $q);
    exit();
}

function supplier_columns(PDO $pdo, string $table): array
{
    try {
        return array_map(static fn (array $row): string => $row['Field'], $pdo->query('SHOW COLUMNS FROM `' . str_replace('`', '``', $table) . '`')->fetchAll(PDO::FETCH_ASSOC));
    } catch (Throwable) {
        return [];
    }
}

function supplier_ensure_column(PDO $pdo, string $table, string $column, string $definition): void
{
    if (! in_array($column, supplier_columns($pdo, $table), true)) {
        $pdo->exec('ALTER TABLE `' . str_replace('`', '``', $table) . '` ADD COLUMN `' . str_replace('`', '``', $column) . '` ' . $definition);
    }
}

function supplier_ensure_schema(PDO $pdo): void
{
    $pdo->exec("CREATE TABLE IF NOT EXISTS suppliers (
        id BIGINT(20) NOT NULL AUTO_INCREMENT,
        tenant_id BIGINT(20) NOT NULL,
        supplier_code VARCHAR(50) NOT NULL,
        company_name VARCHAR(255) NOT NULL,
        contact_person VARCHAR(155) DEFAULT NULL,
        phone VARCHAR(80) DEFAULT NULL,
        email VARCHAR(155) DEFAULT NULL,
        address TEXT DEFAULT NULL,
        tin_number VARCHAR(100) DEFAULT NULL,
        credit_limit DECIMAL(15,2) NOT NULL DEFAULT 0.00,
        credit_balance DECIMAL(15,2) NOT NULL DEFAULT 0.00,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY idx_supplier_tenant (tenant_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    foreach ([
        'supplier_type' => "VARCHAR(50) DEFAULT 'goods'",
        'status' => "VARCHAR(30) DEFAULT 'active'",
        'payment_terms' => "VARCHAR(50) DEFAULT 'net_30'",
        'preferred_payment_method' => 'VARCHAR(50) DEFAULT NULL',
        'lead_time_days' => 'INT(11) NOT NULL DEFAULT 0',
        'rating' => "VARCHAR(30) DEFAULT 'approved'",
        'account_manager' => 'VARCHAR(155) DEFAULT NULL',
        'bank_name' => 'VARCHAR(155) DEFAULT NULL',
        'bank_account_name' => 'VARCHAR(155) DEFAULT NULL',
        'bank_account_number' => 'VARCHAR(100) DEFAULT NULL',
        'mobile_money_number' => 'VARCHAR(100) DEFAULT NULL',
        'website' => 'VARCHAR(255) DEFAULT NULL',
        'notes' => 'TEXT DEFAULT NULL',
    ] as $column => $definition) {
        supplier_ensure_column($pdo, 'suppliers', $column, $definition);
    }

    $pdo->exec("CREATE TABLE IF NOT EXISTS supplier_payments (
        id BIGINT(20) NOT NULL AUTO_INCREMENT,
        tenant_id BIGINT(20) NOT NULL,
        supplier_id BIGINT(20) NOT NULL,
        payment_date DATE NOT NULL,
        amount DECIMAL(15,2) NOT NULL DEFAULT 0.00,
        method VARCHAR(80) DEFAULT NULL,
        reference VARCHAR(155) DEFAULT NULL,
        notes TEXT DEFAULT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY idx_supplier_payment_tenant (tenant_id),
        KEY idx_supplier_payment_supplier (supplier_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
}

function supplier_post(string $key, string $default = ''): string
{
    return trim((string) ($_POST[$key] ?? $default));
}

function supplier_option(string $value, string $label, mixed $current = null): void
{
    echo '<option value="' . supplier_h($value) . '"' . ((string) $current === (string) $value ? ' selected' : '') . '>' . supplier_h($label) . '</option>';
}

function supplier_money(mixed $amount, string $currency): string
{
    return number_format((float) ($amount ?? 0), 2) . ' ' . $currency;
}

function supplier_styles(): void
{
    ?>
    <style>
        .supplier-page,.supplier-page *{border-radius:0!important}.supplier-page{display:grid;gap:16px;max-width:1220px}.supplier-panel{background:var(--onyx-surface);border:1px solid var(--onyx-border);padding:18px;overflow:hidden}.supplier-title{color:#fff;font-size:11px;font-weight:900;letter-spacing:.7px;text-transform:uppercase}.supplier-muted{color:var(--onyx-muted);display:block;font-size:10px;line-height:1.6;margin-top:5px}.supplier-grid{display:grid;gap:12px;grid-template-columns:repeat(12,minmax(0,1fr));margin-top:14px}.supplier-field{display:grid;gap:6px;grid-column:span 4}.supplier-field.wide{grid-column:span 8}.supplier-field.full{grid-column:span 12}.supplier-field label{color:var(--onyx-muted);font-size:10px;font-weight:900;text-transform:uppercase}.supplier-field input,.supplier-field select,.supplier-field textarea{background:#101016;border:1px solid rgba(255,255,255,.12);color:#fff;font-family:Poppins,system-ui,sans-serif;font-size:10px;min-height:38px;padding:8px 10px;width:100%}.supplier-field textarea{min-height:82px;resize:vertical}.supplier-field select option{background:#050506;color:#fff}.supplier-actions,.supplier-tools{align-items:center;display:flex;flex-wrap:wrap;gap:8px}.supplier-actions{justify-content:flex-end}.supplier-btn{align-items:center;background:rgba(255,255,255,.035);border:1px solid rgba(255,255,255,.1);color:#fff;cursor:pointer;display:inline-flex;font:inherit;font-size:10px;font-weight:900;gap:8px;min-height:38px;padding:0 12px;text-decoration:none;text-transform:uppercase}.supplier-btn.primary{background:#fff;color:#050506}.supplier-btn.danger{border-color:rgba(255,138,138,.35);color:#ff8a8a}.supplier-kpis{display:grid;gap:10px;grid-template-columns:repeat(4,minmax(0,1fr));margin-top:14px}.supplier-kpi{border:1px solid rgba(255,255,255,.08);padding:13px}.supplier-kpi span{color:var(--onyx-muted);display:block;font-size:9px;font-weight:900;text-transform:uppercase}.supplier-kpi strong{color:#fff;display:block;font-size:16px;margin-top:7px}.supplier-table-wrap{margin-top:14px;overflow-x:auto}.supplier-table{border-collapse:collapse;min-width:900px;width:100%}.supplier-table th,.supplier-table td{border-bottom:1px solid rgba(255,255,255,.06);font-size:10px;padding:9px;text-align:left}.supplier-table th{color:var(--onyx-muted);font-weight:900;text-transform:uppercase}.supplier-empty{border:1px solid rgba(255,255,255,.08);color:var(--onyx-muted);padding:14px}@media(max-width:900px){.supplier-field,.supplier-field.wide{grid-column:span 6}.supplier-kpis{grid-template-columns:repeat(2,1fr)}}@media(max-width:640px){.supplier-field,.supplier-field.wide{grid-column:span 12}.supplier-kpis{grid-template-columns:1fr}.supplier-actions{justify-content:stretch}.supplier-btn{justify-content:center;width:100%}}
    </style>
    <?php
}

function supplier_render_form(string $mode, array $row = []): void
{
    $isEdit = $mode === 'edit';
    supplier_styles();
    ?>
    <form class="supplier-page" method="POST" action="suppliers_action.php">
        <input type="hidden" name="action" value="<?= $isEdit ? 'edit' : 'add' ?>">
        <?php if ($isEdit): ?><input type="hidden" name="id" value="<?= supplier_h($row['id'] ?? '') ?>"><?php endif; ?>
        <section class="supplier-panel">
            <div class="supplier-tools" style="justify-content:space-between;">
                <div><div class="supplier-title"><i class="fa-solid fa-truck-field"></i> <?= $isEdit ? 'Edit Supplier' : 'Add Supplier' ?> / Commercial Profile</div><span class="supplier-muted">Capture contacts, payment terms, lead time, approval status, and settlement channels.</span></div>
                <a class="supplier-btn" href="<?= supplier_h(onyx_legacy_url('suppliers.php')) ?>"><i class="fa-solid fa-arrow-left"></i> Back</a>
            </div>
            <div class="supplier-grid">
                <div class="supplier-field"><label>Supplier Code</label><input name="supplier_code" value="<?= supplier_h($row['supplier_code'] ?? '') ?>" placeholder="Auto-generated if blank"></div>
                <div class="supplier-field"><label>Supplier Type</label><select name="supplier_type"><?php foreach (['goods'=>'Goods','services'=>'Services','contractor'=>'Contractor','utilities'=>'Utilities','logistics'=>'Logistics'] as $v=>$l) supplier_option($v,$l,$row['supplier_type'] ?? 'goods'); ?></select></div>
                <div class="supplier-field"><label>Status</label><select name="status"><?php foreach (['active'=>'Active','preferred'=>'Preferred','watchlist'=>'Watchlist','blocked'=>'Blocked'] as $v=>$l) supplier_option($v,$l,$row['status'] ?? 'active'); ?></select></div>
                <div class="supplier-field wide"><label>Company Name *</label><input name="company_name" required value="<?= supplier_h($row['company_name'] ?? '') ?>"></div>
                <div class="supplier-field"><label>Contact Person</label><input name="contact_person" value="<?= supplier_h($row['contact_person'] ?? '') ?>"></div>
                <div class="supplier-field"><label>Phone</label><input name="phone" value="<?= supplier_h($row['phone'] ?? '') ?>"></div>
                <div class="supplier-field"><label>Email</label><input type="email" name="email" value="<?= supplier_h($row['email'] ?? '') ?>"></div>
                <div class="supplier-field"><label>TIN / Tax Number</label><input name="tin_number" value="<?= supplier_h($row['tin_number'] ?? '') ?>"></div>
                <div class="supplier-field"><label>Website</label><input name="website" value="<?= supplier_h($row['website'] ?? '') ?>"></div>
                <div class="supplier-field wide"><label>Address</label><textarea name="address"><?= supplier_h($row['address'] ?? '') ?></textarea></div>
            </div>
        </section>
        <section class="supplier-panel">
            <div class="supplier-title"><i class="fa-solid fa-scale-balanced"></i> Terms, Risk & Settlement</div>
            <div class="supplier-grid">
                <div class="supplier-field"><label>Payment Terms</label><select name="payment_terms"><?php foreach (['cash'=>'Cash','net_7'=>'Net 7','net_15'=>'Net 15','net_30'=>'Net 30','net_60'=>'Net 60'] as $v=>$l) supplier_option($v,$l,$row['payment_terms'] ?? 'net_30'); ?></select></div>
                <div class="supplier-field"><label>Preferred Payment</label><select name="preferred_payment_method"><?php foreach ([''=>'Not specified','cash'=>'Cash','bank'=>'Bank','mobile_money'=>'Mobile Money','cheque'=>'Cheque'] as $v=>$l) supplier_option($v,$l,$row['preferred_payment_method'] ?? ''); ?></select></div>
                <div class="supplier-field"><label>Lead Time Days</label><input type="number" min="0" step="1" name="lead_time_days" value="<?= supplier_h($row['lead_time_days'] ?? '0') ?>"></div>
                <div class="supplier-field"><label>Rating</label><select name="rating"><?php foreach (['approved'=>'Approved','excellent'=>'Excellent','average'=>'Average','risk'=>'Risk','blocked'=>'Blocked'] as $v=>$l) supplier_option($v,$l,$row['rating'] ?? 'approved'); ?></select></div>
                <div class="supplier-field"><label>Credit Limit</label><input type="number" step="0.01" name="credit_limit" value="<?= supplier_h($row['credit_limit'] ?? '0.00') ?>"></div>
                <div class="supplier-field"><label>Outstanding Balance</label><input type="number" step="0.01" name="credit_balance" value="<?= supplier_h($row['credit_balance'] ?? '0.00') ?>"></div>
                <div class="supplier-field"><label>Account Manager</label><input name="account_manager" value="<?= supplier_h($row['account_manager'] ?? '') ?>"></div>
                <div class="supplier-field"><label>Bank Name</label><input name="bank_name" value="<?= supplier_h($row['bank_name'] ?? '') ?>"></div>
                <div class="supplier-field"><label>Bank Account Name</label><input name="bank_account_name" value="<?= supplier_h($row['bank_account_name'] ?? '') ?>"></div>
                <div class="supplier-field"><label>Bank Account Number</label><input name="bank_account_number" value="<?= supplier_h($row['bank_account_number'] ?? '') ?>"></div>
                <div class="supplier-field"><label>Mobile Money Number</label><input name="mobile_money_number" value="<?= supplier_h($row['mobile_money_number'] ?? '') ?>"></div>
                <div class="supplier-field full"><label>Internal Notes</label><textarea name="notes"><?= supplier_h($row['notes'] ?? '') ?></textarea></div>
            </div>
        </section>
        <section class="supplier-panel"><div class="supplier-actions"><a class="supplier-btn" href="<?= supplier_h(onyx_legacy_url('suppliers.php')) ?>">Cancel</a><button class="supplier-btn primary" type="submit"><i class="fa-solid fa-floppy-disk"></i> <?= $isEdit ? 'Update Supplier' : 'Save Supplier' ?></button></div></section>
    </form>
    <?php
}

function supplier_get(PDO $pdo, int $tenantId, int $id): ?array
{
    $stmt = $pdo->prepare('SELECT * FROM suppliers WHERE id = ? AND tenant_id = ? LIMIT 1');
    $stmt->execute([$id, $tenantId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
}

supplier_ensure_schema($pdo);

if (! $action) supplier_redirect('No action specified.', false);

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if ($action === 'add') {
        onyx_page_start('Add Supplier', 'Create a complete supplier account.');
        supplier_render_form('add');
        onyx_page_end();
        exit();
    }
    if ($action === 'edit') {
        $row = supplier_get($pdo, $tenant_id, (int) ($_GET['id'] ?? 0));
        if (! $row) supplier_redirect('Supplier not found.', false);
        onyx_page_start('Edit Supplier', 'Update supplier commercial and payment details.');
        supplier_render_form('edit', $row);
        onyx_page_end();
        exit();
    }
    if ($action === 'delete') {
        $row = supplier_get($pdo, $tenant_id, (int) ($_GET['id'] ?? 0));
        if (! $row) supplier_redirect('Supplier not found.', false);
        onyx_page_start('Delete Supplier', 'Confirm supplier removal.');
        supplier_styles();
        ?>
        <form class="supplier-page" method="POST" action="suppliers_action.php" onsubmit="return confirm('Delete this supplier?');">
            <input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= supplier_h($row['id']) ?>">
            <section class="supplier-panel"><div class="supplier-title">Delete Supplier</div><span class="supplier-muted">You are deleting <?= supplier_h($row['company_name']) ?>. Use this only for records created in error.</span></section>
            <section class="supplier-panel"><div class="supplier-actions"><a class="supplier-btn" href="<?= supplier_h(onyx_legacy_url('suppliers.php')) ?>">Cancel</a><button class="supplier-btn danger" type="submit">Confirm Delete</button></div></section>
        </form>
        <?php
        onyx_page_end();
        exit();
    }
    if ($action === 'payments') {
        $suppliers = onyx_rows('SELECT id, company_name FROM suppliers WHERE tenant_id = :tenant_id ORDER BY company_name ASC', ['tenant_id' => $tenant_id]);
        $selected = supplier_get($pdo, $tenant_id, (int) ($_GET['id'] ?? 0));
        onyx_page_start('Supplier Payment', 'Record payments made to suppliers.');
        supplier_styles();
        ?>
        <form class="supplier-page" method="POST" action="suppliers_action.php">
            <input type="hidden" name="action" value="payment">
            <section class="supplier-panel">
                <div class="supplier-title"><i class="fa-solid fa-money-check-dollar"></i> Supplier Payment</div>
                <div class="supplier-grid">
                    <div class="supplier-field wide"><label>Supplier</label><select name="supplier_id" required><option value="">Choose supplier</option><?php foreach ($suppliers as $s) supplier_option((string) $s['id'], $s['company_name'], $selected['id'] ?? ''); ?></select></div>
                    <div class="supplier-field"><label>Payment Date</label><input type="date" name="payment_date" value="<?= date('Y-m-d') ?>"></div>
                    <div class="supplier-field"><label>Amount</label><input type="number" step="0.01" min="0" name="amount" required></div>
                    <div class="supplier-field"><label>Method</label><select name="method"><option value="bank">Bank</option><option value="mobile_money">Mobile Money</option><option value="cash">Cash</option><option value="cheque">Cheque</option></select></div>
                    <div class="supplier-field wide"><label>Reference</label><input name="reference"></div>
                    <div class="supplier-field full"><label>Notes</label><textarea name="notes"></textarea></div>
                </div>
            </section>
            <section class="supplier-panel"><div class="supplier-actions"><a class="supplier-btn" href="<?= supplier_h(onyx_legacy_url('suppliers.php')) ?>">Cancel</a><button class="supplier-btn primary" type="submit">Record Payment</button></div></section>
        </form>
        <?php
        onyx_page_end();
        exit();
    }
    if ($action === 'view' || $action === 'statement') {
        $id = (int) ($_GET['id'] ?? 0);
        if ($id <= 0) {
            $suppliers = onyx_rows('SELECT id, company_name FROM suppliers WHERE tenant_id = :tenant_id ORDER BY company_name ASC', ['tenant_id' => $tenant_id]);
            onyx_page_start('Supplier Statement', 'Select a supplier.');
            supplier_styles();
            ?><form class="supplier-page" method="GET" action="suppliers_action.php"><input type="hidden" name="action" value="statement"><section class="supplier-panel"><div class="supplier-field wide"><label>Supplier</label><select name="id"><?php foreach ($suppliers as $s) supplier_option((string) $s['id'], $s['company_name']); ?></select></div><div class="supplier-actions" style="margin-top:14px;"><button class="supplier-btn primary" type="submit">View Statement</button></div></section></form><?php
            onyx_page_end();
            exit();
        }
        $row = supplier_get($pdo, $tenant_id, $id);
        if (! $row) supplier_redirect('Supplier not found.', false);
        $context = onyx_page_start($action === 'statement' ? 'Supplier Statement' : 'Supplier Details', $row['company_name']);
        $payments = onyx_rows('SELECT * FROM supplier_payments WHERE tenant_id = :tenant_id AND supplier_id = :supplier_id ORDER BY payment_date DESC, id DESC', ['tenant_id' => $tenant_id, 'supplier_id' => $id]);
        supplier_styles();
        ?>
        <div class="supplier-page">
            <section class="supplier-panel">
                <div class="supplier-tools" style="justify-content:space-between;"><div><div class="supplier-title"><?= supplier_h($row['company_name']) ?></div><span class="supplier-muted"><?= supplier_h(($row['supplier_code'] ?? '-') . ' / ' . ($row['status'] ?? 'active') . ' / ' . ($row['rating'] ?? 'approved')) ?></span></div><div class="supplier-tools"><a class="supplier-btn" href="<?= supplier_h(onyx_legacy_url('suppliers.php')) ?>">Back</a><a class="supplier-btn primary" href="<?= supplier_h(onyx_legacy_url('suppliers_action.php?action=edit&id=' . $id)) ?>">Edit</a><a class="supplier-btn" href="<?= supplier_h(onyx_legacy_url('suppliers_action.php?action=payments&id=' . $id)) ?>">Payment</a></div></div>
                <div class="supplier-kpis"><div class="supplier-kpi"><span>Balance</span><strong><?= supplier_h(supplier_money($row['credit_balance'], $context['currency'])) ?></strong></div><div class="supplier-kpi"><span>Credit Limit</span><strong><?= supplier_h(supplier_money($row['credit_limit'], $context['currency'])) ?></strong></div><div class="supplier-kpi"><span>Terms</span><strong><?= supplier_h($row['payment_terms']) ?></strong></div><div class="supplier-kpi"><span>Lead Time</span><strong><?= supplier_h($row['lead_time_days']) ?> days</strong></div></div>
                <div class="supplier-grid"><div class="supplier-field"><label>Contact</label><input readonly value="<?= supplier_h($row['contact_person']) ?>"></div><div class="supplier-field"><label>Phone</label><input readonly value="<?= supplier_h($row['phone']) ?>"></div><div class="supplier-field"><label>Email</label><input readonly value="<?= supplier_h($row['email']) ?>"></div><div class="supplier-field"><label>TIN</label><input readonly value="<?= supplier_h($row['tin_number']) ?>"></div><div class="supplier-field wide"><label>Address</label><textarea readonly><?= supplier_h($row['address']) ?></textarea></div><div class="supplier-field full"><label>Notes</label><textarea readonly><?= supplier_h($row['notes']) ?></textarea></div></div>
            </section>
            <section class="supplier-panel"><div class="supplier-title">Payment Ledger</div><div class="supplier-table-wrap"><table class="supplier-table"><thead><tr><th>Date</th><th>Amount</th><th>Method</th><th>Reference</th><th>Notes</th></tr></thead><tbody><?php if ($payments === []): ?><tr><td colspan="5"><div class="supplier-empty">No payments recorded for this supplier.</div></td></tr><?php endif; ?><?php foreach ($payments as $p): ?><tr><td><?= supplier_h($p['payment_date']) ?></td><td><?= supplier_h(supplier_money($p['amount'], $context['currency'])) ?></td><td><?= supplier_h($p['method']) ?></td><td><?= supplier_h($p['reference']) ?></td><td><?= supplier_h($p['notes']) ?></td></tr><?php endforeach; ?></tbody></table></div></section>
        </div>
        <?php
        onyx_page_end();
        exit();
    }
    supplier_redirect('Unsupported view action: ' . $action, false);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'add' || $action === 'edit') {
        $id = (int) ($_POST['id'] ?? 0);
        $company = supplier_post('company_name');
        if ($company === '') supplier_redirect('Company name is required.', false);
        $code = supplier_post('supplier_code') ?: ('SUP-' . strtoupper(substr(uniqid(), -6)));
        $data = [$code, $company, supplier_post('contact_person'), supplier_post('phone'), supplier_post('email'), supplier_post('address'), supplier_post('tin_number'), max(0, (float) ($_POST['credit_limit'] ?? 0)), (float) ($_POST['credit_balance'] ?? 0), supplier_post('supplier_type', 'goods'), supplier_post('status', 'active'), supplier_post('payment_terms', 'net_30'), supplier_post('preferred_payment_method'), max(0, (int) ($_POST['lead_time_days'] ?? 0)), supplier_post('rating', 'approved'), supplier_post('account_manager'), supplier_post('bank_name'), supplier_post('bank_account_name'), supplier_post('bank_account_number'), supplier_post('mobile_money_number'), supplier_post('website'), supplier_post('notes')];
        if ($action === 'add') {
            $stmt = $pdo->prepare('INSERT INTO suppliers (tenant_id, supplier_code, company_name, contact_person, phone, email, address, tin_number, credit_limit, credit_balance, supplier_type, status, payment_terms, preferred_payment_method, lead_time_days, rating, account_manager, bank_name, bank_account_name, bank_account_number, mobile_money_number, website, notes, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())');
            $stmt->execute(array_merge([$tenant_id], $data));
            supplier_redirect('Supplier added successfully.');
        }
        if ($id <= 0) supplier_redirect('Missing supplier id.', false);
        $stmt = $pdo->prepare('UPDATE suppliers SET supplier_code = ?, company_name = ?, contact_person = ?, phone = ?, email = ?, address = ?, tin_number = ?, credit_limit = ?, credit_balance = ?, supplier_type = ?, status = ?, payment_terms = ?, preferred_payment_method = ?, lead_time_days = ?, rating = ?, account_manager = ?, bank_name = ?, bank_account_name = ?, bank_account_number = ?, mobile_money_number = ?, website = ?, notes = ?, updated_at = NOW() WHERE id = ? AND tenant_id = ?');
        $stmt->execute(array_merge($data, [$id, $tenant_id]));
        supplier_redirect('Supplier updated successfully.');
    }
    if ($action === 'payment') {
        $supplierId = (int) ($_POST['supplier_id'] ?? 0);
        $amount = max(0, (float) ($_POST['amount'] ?? 0));
        if ($supplierId <= 0 || $amount <= 0) supplier_redirect('Select a supplier and valid payment amount.', false);
        $pdo->beginTransaction();
        try {
            $pdo->prepare('INSERT INTO supplier_payments (tenant_id, supplier_id, payment_date, amount, method, reference, notes, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())')->execute([$tenant_id, $supplierId, supplier_post('payment_date', date('Y-m-d')), $amount, supplier_post('method'), supplier_post('reference'), supplier_post('notes')]);
            $pdo->prepare('UPDATE suppliers SET credit_balance = credit_balance - ?, updated_at = NOW() WHERE id = ? AND tenant_id = ?')->execute([$amount, $supplierId, $tenant_id]);
            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();
            supplier_redirect('Supplier payment failed: ' . $e->getMessage(), false);
        }
        supplier_redirect('Supplier payment recorded successfully.');
    }
    if ($action === 'delete') {
        $id = (int) ($_POST['id'] ?? 0);
        if ($id <= 0) supplier_redirect('Missing supplier id.', false);
        $pdo->prepare('DELETE FROM suppliers WHERE id = ? AND tenant_id = ?')->execute([$id, $tenant_id]);
        supplier_redirect('Supplier deleted successfully.');
    }
    supplier_redirect('Unsupported action.', false);
}
