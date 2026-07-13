<?php

$pdo = onyx_db();
$tenant_id = (int) (onyx_tenant_id() ?? 0);

if (! has_permission('manage_purchases') && ! has_permission('view_reports')) {
    require_permission('manage_purchases');
}

function purchase_h(mixed $value): string
{
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
}

function purchase_redirect(string $message = '', bool $success = true, ?int $purchaseId = null): void
{
    $params = [];
    if ($message !== '') {
        $params[$success ? 'success' : 'error'] = $message;
    }
    if ($purchaseId !== null) {
        $params['purchase'] = $purchaseId;
    }
    header('Location: purchases.php' . ($params === [] ? '' : '?' . http_build_query($params)));
    exit();
}

function purchase_columns(PDO $pdo, string $table): array
{
    try {
        return array_map(static fn (array $row): string => $row['Field'], $pdo->query('SHOW COLUMNS FROM `' . str_replace('`', '``', $table) . '`')->fetchAll(PDO::FETCH_ASSOC));
    } catch (Throwable) {
        return [];
    }
}

function purchase_ensure_column(PDO $pdo, string $table, string $column, string $definition): void
{
    if (! in_array($column, purchase_columns($pdo, $table), true)) {
        $pdo->exec('ALTER TABLE `' . str_replace('`', '``', $table) . '` ADD COLUMN `' . str_replace('`', '``', $column) . '` ' . $definition);
    }
}

function purchase_ensure_schema(PDO $pdo): void
{
    $pdo->exec("CREATE TABLE IF NOT EXISTS suppliers (
        id BIGINT(20) NOT NULL AUTO_INCREMENT,
        tenant_id BIGINT(20) NOT NULL,
        supplier_code VARCHAR(50) DEFAULT NULL,
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

    $pdo->exec("CREATE TABLE IF NOT EXISTS purchases (
        id BIGINT(20) NOT NULL AUTO_INCREMENT,
        tenant_id BIGINT(20) NOT NULL,
        supplier VARCHAR(255) DEFAULT '',
        purchase_date DATE DEFAULT NULL,
        total_amount DECIMAL(15,2) DEFAULT 0.00,
        notes TEXT DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY idx_purchase_tenant (tenant_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    foreach ([
        'payment_terms' => "VARCHAR(50) DEFAULT 'net_30'",
        'status' => "VARCHAR(30) DEFAULT 'active'",
        'updated_at' => 'DATETIME DEFAULT NULL',
    ] as $column => $definition) {
        purchase_ensure_column($pdo, 'suppliers', $column, $definition);
    }

    $productTable = $pdo->query("SHOW TABLES LIKE 'products'");
    if ($productTable && $productTable->rowCount() > 0) {
        purchase_ensure_column($pdo, 'products', 'vat_rate', 'DECIMAL(5,2) NOT NULL DEFAULT 0.00');
    }

    foreach ([
        'purchase_number' => 'VARCHAR(100) DEFAULT NULL',
        'supplier_id' => 'BIGINT(20) DEFAULT NULL',
        'invoice_number' => 'VARCHAR(155) DEFAULT NULL',
        'due_date' => 'DATE DEFAULT NULL',
        'status' => "VARCHAR(40) DEFAULT 'draft'",
        'payment_status' => "VARCHAR(40) DEFAULT 'unpaid'",
        'subtotal' => 'DECIMAL(15,2) NOT NULL DEFAULT 0.00',
        'tax' => 'DECIMAL(15,2) NOT NULL DEFAULT 0.00',
        'discount' => 'DECIMAL(15,2) NOT NULL DEFAULT 0.00',
        'shipping' => 'DECIMAL(15,2) NOT NULL DEFAULT 0.00',
        'stock_posted' => 'TINYINT(1) NOT NULL DEFAULT 0',
        'created_by' => 'VARCHAR(155) DEFAULT NULL',
        'updated_at' => 'DATETIME DEFAULT NULL',
    ] as $column => $definition) {
        purchase_ensure_column($pdo, 'purchases', $column, $definition);
    }

    $pdo->exec("CREATE TABLE IF NOT EXISTS purchase_lines (
        id BIGINT(20) NOT NULL AUTO_INCREMENT,
        tenant_id BIGINT(20) NOT NULL,
        purchase_id BIGINT(20) NOT NULL,
        product_id BIGINT(20) DEFAULT NULL,
        description TEXT DEFAULT NULL,
        quantity INT(11) NOT NULL DEFAULT 1,
        unit_cost DECIMAL(15,2) NOT NULL DEFAULT 0.00,
        tax_rate DECIMAL(5,2) NOT NULL DEFAULT 0.00,
        line_total DECIMAL(15,2) NOT NULL DEFAULT 0.00,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY idx_purchase_line_purchase (purchase_id),
        KEY idx_purchase_line_tenant (tenant_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    $pdo->exec("CREATE TABLE IF NOT EXISTS purchase_payments (
        id BIGINT(20) NOT NULL AUTO_INCREMENT,
        tenant_id BIGINT(20) NOT NULL,
        purchase_id BIGINT(20) NOT NULL,
        supplier_id BIGINT(20) DEFAULT NULL,
        payment_date DATE NOT NULL,
        amount DECIMAL(15,2) NOT NULL DEFAULT 0.00,
        method VARCHAR(80) DEFAULT NULL,
        reference VARCHAR(155) DEFAULT NULL,
        notes TEXT DEFAULT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY idx_purchase_payment_purchase (purchase_id),
        KEY idx_purchase_payment_tenant (tenant_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    $pdo->exec("CREATE TABLE IF NOT EXISTS inventory_transactions (
        id BIGINT(20) NOT NULL AUTO_INCREMENT,
        tenant_id BIGINT(20) NOT NULL,
        product_id BIGINT(20) NOT NULL,
        transaction_type VARCHAR(30) NOT NULL,
        quantity INT(11) NOT NULL DEFAULT 0,
        from_warehouse_id BIGINT(20) DEFAULT NULL,
        to_warehouse_id BIGINT(20) DEFAULT NULL,
        reference VARCHAR(100) DEFAULT NULL,
        notes TEXT DEFAULT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY idx_inventory_transaction_tenant (tenant_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
}

function purchase_number(): string
{
    return 'PO-' . date('ymd') . '-' . strtoupper(substr(uniqid(), -6));
}

function purchase_money(mixed $amount, string $currency): string
{
    return number_format((float) ($amount ?? 0), 2) . ' ' . $currency;
}

function purchase_status_badge(string $status): string
{
    $class = in_array($status, ['received', 'paid'], true) ? 'ok' : (in_array($status, ['cancelled', 'overdue'], true) ? 'danger' : 'warn');
    return '<span class="purchase-badge ' . purchase_h($class) . '">' . purchase_h($status) . '</span>';
}

function purchase_paid_amount(int $purchaseId, int $tenantId): float
{
    return (float) onyx_scalar('SELECT COALESCE(SUM(amount), 0) FROM purchase_payments WHERE purchase_id = :purchase_id AND tenant_id = :tenant_id', ['purchase_id' => $purchaseId, 'tenant_id' => $tenantId], 0);
}

function purchase_update_payment_status(PDO $pdo, int $purchaseId, int $tenantId): void
{
    $purchase = onyx_row('SELECT total_amount FROM purchases WHERE id = :id AND tenant_id = :tenant_id', ['id' => $purchaseId, 'tenant_id' => $tenantId]);
    if (! $purchase) {
        return;
    }
    $paid = purchase_paid_amount($purchaseId, $tenantId);
    $total = (float) $purchase['total_amount'];
    $status = $paid <= 0 ? 'unpaid' : ($paid + 0.001 >= $total ? 'paid' : 'partial');
    $pdo->prepare('UPDATE purchases SET payment_status = ?, updated_at = NOW() WHERE id = ? AND tenant_id = ?')->execute([$status, $purchaseId, $tenantId]);
}

purchase_ensure_schema($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_permission('manage_purchases');
    $action = $_POST['action'] ?? '';

    if ($action === 'create_purchase') {
        $supplierId = (int) ($_POST['supplier_id'] ?? 0) ?: null;
        $supplierName = trim($_POST['supplier_name'] ?? '');
        if ($supplierId) {
            $supplier = onyx_row('SELECT company_name FROM suppliers WHERE id = :id AND tenant_id = :tenant_id', ['id' => $supplierId, 'tenant_id' => $tenant_id]);
            $supplierName = $supplier['company_name'] ?? $supplierName;
        }
        if ($supplierName === '') {
            purchase_redirect('Choose or enter a supplier.', false);
        }

        $productIds = $_POST['line_product_id'] ?? [];
        $descriptions = $_POST['line_description'] ?? [];
        $quantities = $_POST['line_quantity'] ?? [];
        $unitCosts = $_POST['line_unit_cost'] ?? [];
        $taxRates = $_POST['line_tax_rate'] ?? [];
        $lines = [];
        $subtotal = 0.0;
        $tax = 0.0;

        foreach ($descriptions as $index => $description) {
            $productId = (int) ($productIds[$index] ?? 0) ?: null;
            $description = trim((string) $description);
            if ($productId && $description === '') {
                $product = onyx_row('SELECT name FROM products WHERE id = :id AND tenant_id = :tenant_id', ['id' => $productId, 'tenant_id' => $tenant_id]);
                $description = $product['name'] ?? '';
            }
            $qty = max(0, (int) ($quantities[$index] ?? 0));
            $cost = max(0, (float) ($unitCosts[$index] ?? 0));
            $rate = max(0, (float) ($taxRates[$index] ?? 0));
            if ($description === '' && ! $productId) {
                continue;
            }
            if ($qty <= 0) {
                continue;
            }
            $lineSubtotal = $qty * $cost;
            $lineTax = $lineSubtotal * ($rate / 100);
            $subtotal += $lineSubtotal;
            $tax += $lineTax;
            $lines[] = compact('productId', 'description', 'qty', 'cost', 'rate') + ['lineTotal' => $lineSubtotal + $lineTax];
        }

        if ($lines === []) {
            purchase_redirect('Add at least one purchase line.', false);
        }

        $discount = max(0, (float) ($_POST['discount'] ?? 0));
        $shipping = max(0, (float) ($_POST['shipping'] ?? 0));
        $total = max(0, $subtotal + $tax + $shipping - $discount);
        $status = $_POST['status'] ?? 'ordered';
        $postStock = isset($_POST['post_stock']) && in_array($status, ['received', 'closed'], true);
        $purchaseNumber = trim($_POST['purchase_number'] ?? '') ?: purchase_number();
        $purchaseDate = trim($_POST['purchase_date'] ?? '') ?: date('Y-m-d');

        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare('INSERT INTO purchases (tenant_id, purchase_number, supplier_id, supplier, invoice_number, purchase_date, due_date, status, payment_status, subtotal, tax, discount, shipping, total_amount, notes, stock_posted, created_by, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())');
            $stmt->execute([$tenant_id, $purchaseNumber, $supplierId, $supplierName, trim($_POST['invoice_number'] ?? ''), $purchaseDate, trim($_POST['due_date'] ?? '') ?: null, $status, 'unpaid', $subtotal, $tax, $discount, $shipping, $total, trim($_POST['notes'] ?? ''), $postStock ? 1 : 0, session('user_name', 'Operator')]);
            $purchaseId = (int) $pdo->lastInsertId();
            $lineStmt = $pdo->prepare('INSERT INTO purchase_lines (tenant_id, purchase_id, product_id, description, quantity, unit_cost, tax_rate, line_total, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())');
            $stockStmt = $pdo->prepare('UPDATE products SET current_stock = current_stock + ?, buying_price = CASE WHEN ? > 0 THEN ? ELSE buying_price END, updated_at = NOW() WHERE id = ? AND tenant_id = ?');
            $txStmt = $pdo->prepare('INSERT INTO inventory_transactions (tenant_id, product_id, transaction_type, quantity, reference, notes, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())');
            foreach ($lines as $line) {
                $lineStmt->execute([$tenant_id, $purchaseId, $line['productId'], $line['description'], $line['qty'], $line['cost'], $line['rate'], $line['lineTotal']]);
                if ($postStock && $line['productId']) {
                    $stockStmt->execute([$line['qty'], $line['cost'], $line['cost'], $line['productId'], $tenant_id]);
                    $txStmt->execute([$tenant_id, $line['productId'], 'received', $line['qty'], $purchaseNumber, 'Posted from purchase']);
                }
            }
            if ($supplierId) {
                $pdo->prepare('UPDATE suppliers SET credit_balance = credit_balance + ?, updated_at = NOW() WHERE id = ? AND tenant_id = ?')->execute([$total, $supplierId, $tenant_id]);
            }
            $pdo->commit();
            purchase_redirect('Purchase created successfully.', true, $purchaseId);
        } catch (Throwable $e) {
            $pdo->rollBack();
            purchase_redirect('Purchase failed: ' . $e->getMessage(), false);
        }
    }

    if ($action === 'record_payment') {
        $purchaseId = (int) ($_POST['purchase_id'] ?? 0);
        $amount = max(0, (float) ($_POST['amount'] ?? 0));
        if ($purchaseId <= 0 || $amount <= 0) {
            purchase_redirect('Choose a purchase and valid payment amount.', false);
        }
        $purchase = onyx_row('SELECT id, supplier_id FROM purchases WHERE id = :id AND tenant_id = :tenant_id', ['id' => $purchaseId, 'tenant_id' => $tenant_id]);
        if (! $purchase) {
            purchase_redirect('Purchase not found.', false);
        }
        $pdo->beginTransaction();
        try {
            $pdo->prepare('INSERT INTO purchase_payments (tenant_id, purchase_id, supplier_id, payment_date, amount, method, reference, notes, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())')->execute([$tenant_id, $purchaseId, $purchase['supplier_id'], trim($_POST['payment_date'] ?? '') ?: date('Y-m-d'), $amount, trim($_POST['method'] ?? 'bank'), trim($_POST['reference'] ?? ''), trim($_POST['notes'] ?? '')]);
            if ($purchase['supplier_id']) {
                $pdo->prepare('UPDATE suppliers SET credit_balance = credit_balance - ?, updated_at = NOW() WHERE id = ? AND tenant_id = ?')->execute([$amount, $purchase['supplier_id'], $tenant_id]);
            }
            purchase_update_payment_status($pdo, $purchaseId, $tenant_id);
            $pdo->commit();
            purchase_redirect('Purchase payment recorded successfully.', true, $purchaseId);
        } catch (Throwable $e) {
            $pdo->rollBack();
            purchase_redirect('Payment failed: ' . $e->getMessage(), false);
        }
    }

    if ($action === 'update_status') {
        $purchaseId = (int) ($_POST['purchase_id'] ?? 0);
        $status = $_POST['status'] ?? 'ordered';
        $purchase = onyx_row('SELECT id, purchase_number, stock_posted FROM purchases WHERE id = :id AND tenant_id = :tenant_id', ['id' => $purchaseId, 'tenant_id' => $tenant_id]);
        if (! $purchase) {
            purchase_redirect('Purchase not found.', false);
        }
        $pdo->prepare('UPDATE purchases SET status = ?, updated_at = NOW() WHERE id = ? AND tenant_id = ?')->execute([$status, $purchaseId, $tenant_id]);
        if (isset($_POST['post_stock']) && ! (int) $purchase['stock_posted']) {
            $lines = onyx_rows('SELECT product_id, quantity, unit_cost FROM purchase_lines WHERE purchase_id = :purchase_id AND tenant_id = :tenant_id AND product_id IS NOT NULL', ['purchase_id' => $purchaseId, 'tenant_id' => $tenant_id]);
            foreach ($lines as $line) {
                $pdo->prepare('UPDATE products SET current_stock = current_stock + ?, buying_price = CASE WHEN ? > 0 THEN ? ELSE buying_price END, updated_at = NOW() WHERE id = ? AND tenant_id = ?')->execute([(int) $line['quantity'], (float) $line['unit_cost'], (float) $line['unit_cost'], $line['product_id'], $tenant_id]);
                $pdo->prepare('INSERT INTO inventory_transactions (tenant_id, product_id, transaction_type, quantity, reference, notes, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())')->execute([$tenant_id, $line['product_id'], 'received', (int) $line['quantity'], $purchase['purchase_number'], 'Posted from purchase status update']);
            }
            $pdo->prepare('UPDATE purchases SET stock_posted = 1 WHERE id = ? AND tenant_id = ?')->execute([$purchaseId, $tenant_id]);
        }
        purchase_redirect('Purchase status updated.', true, $purchaseId);
    }
}

$context = onyx_page_start('Purchases', 'Purchase orders, supplier invoices, goods received, supplier balances, and payment tracking.');
$currency = $context['currency'];
$can_manage = has_permission('manage_purchases');

$suppliers = onyx_rows('SELECT id, supplier_code, company_name, payment_terms FROM suppliers WHERE tenant_id = :tenant_id ORDER BY company_name ASC', ['tenant_id' => $tenant_id]);
$products = onyx_rows('SELECT id, name, sku, buying_price, vat_rate, current_stock FROM products WHERE tenant_id = :tenant_id ORDER BY name ASC', ['tenant_id' => $tenant_id]);
$purchases = onyx_rows(
    'SELECT p.*, s.company_name,
            COALESCE(pay.amount_paid, 0) AS amount_paid,
            COALESCE(lines.line_count, 0) AS line_count
     FROM purchases p
     LEFT JOIN suppliers s ON s.id = p.supplier_id AND s.tenant_id = p.tenant_id
     LEFT JOIN (
        SELECT purchase_id, tenant_id, SUM(amount) AS amount_paid
        FROM purchase_payments
        WHERE tenant_id = :tenant_id_payments
        GROUP BY purchase_id, tenant_id
     ) pay ON pay.purchase_id = p.id AND pay.tenant_id = p.tenant_id
     LEFT JOIN (
        SELECT purchase_id, tenant_id, COUNT(*) AS line_count
        FROM purchase_lines
        WHERE tenant_id = :tenant_id_lines
        GROUP BY purchase_id, tenant_id
     ) lines ON lines.purchase_id = p.id AND lines.tenant_id = p.tenant_id
     WHERE p.tenant_id = :tenant_id
     ORDER BY p.created_at DESC, p.id DESC',
    ['tenant_id_payments' => $tenant_id, 'tenant_id_lines' => $tenant_id, 'tenant_id' => $tenant_id]
);
$recentPayments = onyx_rows(
    'SELECT pp.*, p.purchase_number, p.supplier
     FROM purchase_payments pp
     JOIN purchases p ON p.id = pp.purchase_id AND p.tenant_id = pp.tenant_id
     WHERE pp.tenant_id = :tenant_id
     ORDER BY pp.payment_date DESC, pp.id DESC
     LIMIT 20',
    ['tenant_id' => $tenant_id]
);
$selectedPurchaseId = (int) ($_GET['purchase'] ?? 0);
$selectedPurchase = null;
$selectedLines = [];
if ($selectedPurchaseId > 0) {
    $selectedPurchase = onyx_row('SELECT * FROM purchases WHERE id = :id AND tenant_id = :tenant_id', ['id' => $selectedPurchaseId, 'tenant_id' => $tenant_id]);
    $selectedLines = onyx_rows('SELECT pl.*, pr.sku FROM purchase_lines pl LEFT JOIN products pr ON pr.id = pl.product_id AND pr.tenant_id = pl.tenant_id WHERE pl.purchase_id = :purchase_id AND pl.tenant_id = :tenant_id ORDER BY pl.id ASC', ['purchase_id' => $selectedPurchaseId, 'tenant_id' => $tenant_id]);
}

$totalPurchases = 0.0;
$openBalance = 0.0;
$receivedCount = 0;
$orderedCount = 0;
foreach ($purchases as $purchase) {
    $totalPurchases += (float) $purchase['total_amount'];
    $openBalance += max(0, (float) $purchase['total_amount'] - (float) $purchase['amount_paid']);
    if (($purchase['status'] ?? '') === 'received') $receivedCount++;
    if (in_array(($purchase['status'] ?? ''), ['draft', 'ordered', 'approved'], true)) $orderedCount++;
}
$message = $_GET['success'] ?? $_GET['error'] ?? '';
$message_type = isset($_GET['error']) ? 'error' : 'success';
$productJson = json_encode(array_map(static fn (array $product): array => [
    'id' => (int) $product['id'],
    'label' => $product['name'] . ' (' . $product['sku'] . ')',
    'cost' => (float) $product['buying_price'],
    'tax' => (float) ($product['vat_rate'] ?? 0),
], $products), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);
?>

<style>
    .purchase-page,.purchase-page *{border-radius:0!important}.purchase-page{display:grid;gap:18px}.purchase-panel{background:var(--onyx-surface);border:1px solid var(--onyx-border);padding:18px;overflow:hidden}.purchase-title{color:var(--onyx-muted);font-size:11px;font-weight:900;letter-spacing:.8px;text-transform:uppercase}.purchase-muted{color:var(--onyx-muted);display:block;font-size:10px;margin-top:4px}.purchase-kpis{display:grid;gap:10px;grid-template-columns:repeat(5,minmax(0,1fr))}.purchase-kpi{background:var(--onyx-surface);border:1px solid var(--onyx-border);padding:14px}.purchase-kpi span{color:var(--onyx-muted);display:block;font-size:9px;font-weight:900;text-transform:uppercase}.purchase-kpi strong{color:#fff;display:block;font-size:16px;margin-top:8px;word-break:break-word}.purchase-grid{display:grid;gap:12px;grid-template-columns:repeat(12,minmax(0,1fr));margin-top:14px}.purchase-field{display:grid;gap:6px;grid-column:span 3;min-width:0}.purchase-field.wide{grid-column:span 6}.purchase-field.full{grid-column:span 12}.purchase-field label{color:var(--onyx-muted);font-size:10px;font-weight:900;text-transform:uppercase}.purchase-field input,.purchase-field select,.purchase-field textarea{background:#101016;border:1px solid rgba(255,255,255,.12);color:#fff;font-family:Poppins,system-ui,sans-serif;font-size:10px;min-height:38px;padding:8px 10px;width:100%}.purchase-field textarea{min-height:78px;resize:vertical}.purchase-field select option{background:#050506;color:#fff}.purchase-actions,.purchase-toolbar,.purchase-tabs{align-items:center;display:flex;flex-wrap:wrap;gap:8px}.purchase-toolbar{justify-content:space-between}.purchase-actions{justify-content:flex-end}.purchase-btn,.purchase-tab{align-items:center;background:rgba(255,255,255,.035);border:1px solid rgba(255,255,255,.1);color:#fff;cursor:pointer;display:inline-flex;font:inherit;font-size:10px;font-weight:900;gap:8px;min-height:38px;padding:0 12px;text-decoration:none;text-transform:uppercase}.purchase-btn.primary,.purchase-tab.active{background:#fff;color:#050506}.purchase-btn.danger{color:#ff8a8a}.purchase-alert{border:1px solid rgba(143,240,195,.24);color:#8ff0c3;font-size:11px;font-weight:800;padding:11px 12px}.purchase-alert.error{border-color:rgba(255,138,138,.28);color:#ff8a8a}.purchase-tab-panel{display:none}.purchase-tab-panel.active{display:block}.purchase-lines{display:grid;gap:8px;margin-top:12px}.purchase-line{border:1px solid rgba(255,255,255,.08);display:grid;gap:8px;grid-template-columns:minmax(180px,1.4fr) minmax(150px,1fr) 80px 110px 90px 120px 40px;padding:10px}.purchase-line button{background:transparent;border:1px solid rgba(255,255,255,.1);color:#ff8a8a;cursor:pointer}.purchase-total-box{border:1px solid rgba(255,255,255,.08);display:grid;gap:8px;margin-left:auto;margin-top:14px;max-width:360px;padding:12px}.purchase-total-box div{display:flex;justify-content:space-between}.purchase-total-box strong{color:#fff}.purchase-table-wrap{margin-top:14px;max-width:calc(100vw - 340px);overflow-x:auto;padding-bottom:14px}.purchase-table{border-collapse:collapse;table-layout:fixed;width:1420px}.purchase-table.compact{width:980px}.purchase-table th,.purchase-table td{border-bottom:1px solid rgba(255,255,255,.06);font-size:10px;padding:9px;text-align:left;vertical-align:top}.purchase-table th{color:var(--onyx-muted);font-size:9px;font-weight:900;text-transform:uppercase}.purchase-name strong{color:#fff;display:block;font-size:11px}.purchase-name span{color:var(--onyx-muted);display:block;font-size:9px;margin-top:3px}.purchase-badge{border:1px solid rgba(255,255,255,.12);display:inline-flex;font-size:9px;font-weight:900;padding:4px 7px;text-transform:uppercase}.purchase-badge.ok{color:#8ff0c3}.purchase-badge.warn{color:#ffd27a}.purchase-badge.danger{color:#ff8a8a}.purchase-empty{border:1px solid rgba(255,255,255,.08);color:var(--onyx-muted);padding:14px}@media(max-width:1180px){.purchase-kpis{grid-template-columns:repeat(2,1fr)}.purchase-field,.purchase-field.wide{grid-column:span 6}.purchase-table-wrap{max-width:calc(100vw - 36px)}.purchase-line{grid-template-columns:1fr 1fr 80px 100px 80px 100px 40px}}@media(max-width:760px){.purchase-kpis{grid-template-columns:1fr}.purchase-field,.purchase-field.wide{grid-column:span 12}.purchase-line{grid-template-columns:1fr}.purchase-actions{justify-content:stretch}.purchase-btn{justify-content:center;width:100%}}
</style>

<div class="purchase-page">
    <?php if ($message !== ''): ?><section class="purchase-panel"><div class="purchase-alert <?= $message_type === 'error' ? 'error' : '' ?>"><?= purchase_h($message) ?></div></section><?php endif; ?>

    <section class="purchase-panel">
        <div class="purchase-toolbar">
            <div><div class="purchase-title">Purchasing Workspace</div><span class="purchase-muted">Create supplier orders, receive stock, track invoices, post supplier balances, and record payments.</span></div>
            <div class="purchase-actions">
                <a class="purchase-btn" href="<?= purchase_h(onyx_legacy_url('suppliers.php')) ?>"><i class="fa-solid fa-truck-field"></i> Suppliers</a>
                <a class="purchase-btn" href="<?= purchase_h(onyx_legacy_url('inventory.php')) ?>"><i class="fa-solid fa-warehouse"></i> Inventory</a>
                <a class="purchase-btn" href="<?= purchase_h(onyx_legacy_url('products.php')) ?>"><i class="fa-solid fa-box"></i> Products</a>
            </div>
        </div>
    </section>

    <section class="purchase-kpis">
        <div class="purchase-kpi"><span>Total Purchases</span><strong><?= purchase_h(purchase_money($totalPurchases, $currency)) ?></strong></div>
        <div class="purchase-kpi"><span>Open Payables</span><strong><?= purchase_h(purchase_money($openBalance, $currency)) ?></strong></div>
        <div class="purchase-kpi"><span>Purchase Records</span><strong><?= purchase_h(count($purchases)) ?></strong></div>
        <div class="purchase-kpi"><span>Awaiting Receipt</span><strong><?= purchase_h($orderedCount) ?></strong></div>
        <div class="purchase-kpi"><span>Received</span><strong><?= purchase_h($receivedCount) ?></strong></div>
    </section>

    <?php if ($can_manage): ?>
    <section class="purchase-panel">
        <div class="purchase-tabs">
            <button class="purchase-tab active" type="button" data-purchase-tab="new">New Purchase</button>
            <button class="purchase-tab" type="button" data-purchase-tab="payment">Record Payment</button>
            <button class="purchase-tab" type="button" data-purchase-tab="status">Update Status / Receive</button>
        </div>
    </section>

    <section class="purchase-panel purchase-tab-panel active" data-purchase-panel="new">
        <div class="purchase-title">New Purchase / Supplier Invoice</div>
        <form method="POST" action="purchases.php" id="purchase-form">
            <input type="hidden" name="action" value="create_purchase">
            <div class="purchase-grid">
                <div class="purchase-field"><label>Purchase No.</label><input name="purchase_number" placeholder="Auto-generated"></div>
                <div class="purchase-field"><label>Supplier</label><select name="supplier_id"><option value="">Choose supplier</option><?php foreach ($suppliers as $supplier): ?><option value="<?= purchase_h($supplier['id']) ?>"><?= purchase_h($supplier['company_name']) ?></option><?php endforeach; ?></select></div>
                <div class="purchase-field"><label>Or Supplier Name</label><input name="supplier_name" placeholder="For once-off supplier"></div>
                <div class="purchase-field"><label>Invoice No.</label><input name="invoice_number"></div>
                <div class="purchase-field"><label>Purchase Date</label><input type="date" name="purchase_date" value="<?= date('Y-m-d') ?>"></div>
                <div class="purchase-field"><label>Due Date</label><input type="date" name="due_date"></div>
                <div class="purchase-field"><label>Status</label><select name="status"><option value="ordered">Ordered</option><option value="approved">Approved</option><option value="received">Received</option><option value="closed">Closed</option><option value="draft">Draft</option></select></div>
                <div class="purchase-field"><label>Post Stock</label><select name="post_stock"><option value="">No</option><option value="1">Yes, receive stock</option></select></div>
            </div>

            <div class="purchase-title" style="margin-top:18px;">Purchase Lines</div>
            <div class="purchase-lines" id="purchase-lines"></div>
            <button class="purchase-btn" type="button" id="add-purchase-line" style="margin-top:10px;"><i class="fa-solid fa-plus"></i> Add Line</button>

            <div class="purchase-grid">
                <div class="purchase-field"><label>Discount</label><input id="purchase-discount" type="number" step="0.01" min="0" name="discount" value="0.00"></div>
                <div class="purchase-field"><label>Shipping / Delivery</label><input id="purchase-shipping" type="number" step="0.01" min="0" name="shipping" value="0.00"></div>
                <div class="purchase-field full"><label>Notes</label><textarea name="notes"></textarea></div>
            </div>
            <div class="purchase-total-box">
                <div><span>Subtotal</span><strong id="purchase-subtotal">0.00</strong></div>
                <div><span>Tax</span><strong id="purchase-tax">0.00</strong></div>
                <div><span>Total</span><strong id="purchase-total">0.00</strong></div>
            </div>
            <div class="purchase-actions" style="margin-top:14px;"><button class="purchase-btn primary" type="submit"><i class="fa-solid fa-floppy-disk"></i> Save Purchase</button></div>
        </form>
    </section>

    <section class="purchase-panel purchase-tab-panel" data-purchase-panel="payment">
        <div class="purchase-title">Record Supplier Payment</div>
        <form method="POST" action="purchases.php">
            <input type="hidden" name="action" value="record_payment">
            <div class="purchase-grid">
                <div class="purchase-field wide"><label>Purchase</label><select name="purchase_id" required><option value="">Choose purchase</option><?php foreach ($purchases as $purchase): ?><option value="<?= purchase_h($purchase['id']) ?>"><?= purchase_h(($purchase['purchase_number'] ?: '#' . $purchase['id']) . ' - ' . ($purchase['supplier'] ?: $purchase['company_name']) . ' - ' . purchase_money($purchase['total_amount'], $currency)) ?></option><?php endforeach; ?></select></div>
                <div class="purchase-field"><label>Payment Date</label><input type="date" name="payment_date" value="<?= date('Y-m-d') ?>"></div>
                <div class="purchase-field"><label>Amount</label><input type="number" step="0.01" min="0" name="amount" required></div>
                <div class="purchase-field"><label>Method</label><select name="method"><option value="bank">Bank</option><option value="mobile_money">Mobile Money</option><option value="cash">Cash</option><option value="cheque">Cheque</option></select></div>
                <div class="purchase-field wide"><label>Reference</label><input name="reference"></div>
                <div class="purchase-field full"><label>Notes</label><textarea name="notes"></textarea></div>
            </div>
            <div class="purchase-actions" style="margin-top:14px;"><button class="purchase-btn primary" type="submit">Record Payment</button></div>
        </form>
    </section>

    <section class="purchase-panel purchase-tab-panel" data-purchase-panel="status">
        <div class="purchase-title">Update Status / Receive Stock</div>
        <form method="POST" action="purchases.php">
            <input type="hidden" name="action" value="update_status">
            <div class="purchase-grid">
                <div class="purchase-field wide"><label>Purchase</label><select name="purchase_id" required><option value="">Choose purchase</option><?php foreach ($purchases as $purchase): ?><option value="<?= purchase_h($purchase['id']) ?>"><?= purchase_h(($purchase['purchase_number'] ?: '#' . $purchase['id']) . ' - ' . ($purchase['supplier'] ?: $purchase['company_name'])) ?></option><?php endforeach; ?></select></div>
                <div class="purchase-field"><label>Status</label><select name="status"><option value="ordered">Ordered</option><option value="approved">Approved</option><option value="received">Received</option><option value="closed">Closed</option><option value="cancelled">Cancelled</option></select></div>
                <div class="purchase-field"><label>Post Stock</label><select name="post_stock"><option value="">Do not post</option><option value="1">Post stock now</option></select></div>
            </div>
            <div class="purchase-actions" style="margin-top:14px;"><button class="purchase-btn primary" type="submit">Update Purchase</button></div>
        </form>
    </section>
    <?php endif; ?>

    <section class="purchase-panel">
        <div class="purchase-title">Purchase Register</div>
        <div class="purchase-table-wrap">
            <table class="purchase-table">
                <colgroup><col style="width:150px"><col style="width:190px"><col style="width:110px"><col style="width:110px"><col style="width:120px"><col style="width:120px"><col style="width:120px"><col style="width:110px"><col style="width:120px"><col style="width:270px"></colgroup>
                <thead><tr><th>Purchase</th><th>Supplier</th><th>Date</th><th>Due</th><th>Status</th><th>Payment</th><th>Total</th><th>Paid</th><th>Lines</th><th>Actions</th></tr></thead>
                <tbody>
                    <?php if ($purchases === []): ?><tr><td colspan="10"><div class="purchase-empty">No purchases recorded yet.</div></td></tr><?php endif; ?>
                    <?php foreach ($purchases as $purchase): ?>
                        <tr>
                            <td><div class="purchase-name"><strong><?= purchase_h($purchase['purchase_number'] ?: '#' . $purchase['id']) ?></strong><span><?= purchase_h($purchase['invoice_number'] ?: 'No invoice') ?></span></div></td>
                            <td><?= purchase_h($purchase['supplier'] ?: $purchase['company_name'] ?: '-') ?></td>
                            <td><?= purchase_h($purchase['purchase_date'] ?: '-') ?></td>
                            <td><?= purchase_h($purchase['due_date'] ?: '-') ?></td>
                            <td><?= purchase_status_badge((string) ($purchase['status'] ?? 'draft')) ?><span class="purchase-muted"><?= ((int) $purchase['stock_posted']) ? 'Stock posted' : 'Stock pending' ?></span></td>
                            <td><?= purchase_status_badge((string) ($purchase['payment_status'] ?? 'unpaid')) ?></td>
                            <td><?= purchase_h(purchase_money($purchase['total_amount'], $currency)) ?></td>
                            <td><?= purchase_h(purchase_money($purchase['amount_paid'], $currency)) ?></td>
                            <td><?= purchase_h($purchase['line_count']) ?></td>
                            <td><div class="purchase-actions"><a class="purchase-btn" href="<?= purchase_h(onyx_legacy_url('purchases.php?purchase=' . (int) $purchase['id'])) ?>">View</a><?php if ($purchase['supplier_id']): ?><a class="purchase-btn" href="<?= purchase_h(onyx_legacy_url('suppliers_action.php?action=statement&id=' . (int) $purchase['supplier_id'])) ?>">Supplier</a><?php endif; ?></div></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>

    <?php if ($selectedPurchase): ?>
        <section class="purchase-panel">
            <div class="purchase-title">Selected Purchase Lines / <?= purchase_h($selectedPurchase['purchase_number'] ?: '#' . $selectedPurchase['id']) ?></div>
            <div class="purchase-table-wrap">
                <table class="purchase-table compact">
                    <thead><tr><th>Product / Description</th><th>SKU</th><th>Qty</th><th>Unit Cost</th><th>Tax</th><th>Total</th></tr></thead>
                    <tbody><?php foreach ($selectedLines as $line): ?><tr><td><?= purchase_h($line['description']) ?></td><td><?= purchase_h($line['sku'] ?: '-') ?></td><td><?= purchase_h($line['quantity']) ?></td><td><?= purchase_h(purchase_money($line['unit_cost'], $currency)) ?></td><td><?= purchase_h(number_format((float) $line['tax_rate'], 2)) ?>%</td><td><?= purchase_h(purchase_money($line['line_total'], $currency)) ?></td></tr><?php endforeach; ?></tbody>
                </table>
            </div>
        </section>
    <?php endif; ?>

    <section class="purchase-panel">
        <div class="purchase-title">Recent Purchase Payments</div>
        <div class="purchase-table-wrap">
            <table class="purchase-table compact">
                <thead><tr><th>Date</th><th>Purchase</th><th>Supplier</th><th>Amount</th><th>Method</th><th>Reference</th></tr></thead>
                <tbody>
                    <?php if ($recentPayments === []): ?><tr><td colspan="6"><div class="purchase-empty">No purchase payments recorded yet.</div></td></tr><?php endif; ?>
                    <?php foreach ($recentPayments as $payment): ?><tr><td><?= purchase_h($payment['payment_date']) ?></td><td><?= purchase_h($payment['purchase_number']) ?></td><td><?= purchase_h($payment['supplier']) ?></td><td><?= purchase_h(purchase_money($payment['amount'], $currency)) ?></td><td><?= purchase_h($payment['method']) ?></td><td><?= purchase_h($payment['reference']) ?></td></tr><?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const products = <?= $productJson ?: '[]' ?>;
    const tabs = Array.from(document.querySelectorAll('[data-purchase-tab]'));
    const panels = Array.from(document.querySelectorAll('[data-purchase-panel]'));
    tabs.forEach((tab) => tab.addEventListener('click', () => {
        tabs.forEach((item) => item.classList.toggle('active', item === tab));
        panels.forEach((panel) => panel.classList.toggle('active', panel.dataset.purchasePanel === tab.dataset.purchaseTab));
    }));

    const lineWrap = document.getElementById('purchase-lines');
    const addLine = document.getElementById('add-purchase-line');
    const discount = document.getElementById('purchase-discount');
    const shipping = document.getElementById('purchase-shipping');
    const subtotalEl = document.getElementById('purchase-subtotal');
    const taxEl = document.getElementById('purchase-tax');
    const totalEl = document.getElementById('purchase-total');

    function money(value) {
        return (Number(value) || 0).toFixed(2);
    }

    function productOptions() {
        return '<option value="">Manual line</option>' + products.map((product) => `<option value="${product.id}" data-cost="${product.cost}" data-tax="${product.tax}">${product.label}</option>`).join('');
    }

    function recalc() {
        let subtotal = 0;
        let tax = 0;
        Array.from(lineWrap.querySelectorAll('.purchase-line')).forEach((line) => {
            const qty = Number(line.querySelector('[name="line_quantity[]"]').value) || 0;
            const cost = Number(line.querySelector('[name="line_unit_cost[]"]').value) || 0;
            const rate = Number(line.querySelector('[name="line_tax_rate[]"]').value) || 0;
            const lineSubtotal = qty * cost;
            const lineTax = lineSubtotal * (rate / 100);
            subtotal += lineSubtotal;
            tax += lineTax;
            line.querySelector('[data-line-total]').textContent = money(lineSubtotal + lineTax);
        });
        const total = Math.max(0, subtotal + tax + (Number(shipping.value) || 0) - (Number(discount.value) || 0));
        subtotalEl.textContent = money(subtotal);
        taxEl.textContent = money(tax);
        totalEl.textContent = money(total);
    }

    function addPurchaseLine() {
        const line = document.createElement('div');
        line.className = 'purchase-line';
        line.innerHTML = `
            <select name="line_product_id[]">${productOptions()}</select>
            <input name="line_description[]" placeholder="Description">
            <input type="number" min="1" step="1" name="line_quantity[]" value="1">
            <input type="number" min="0" step="0.01" name="line_unit_cost[]" value="0.00">
            <input type="number" min="0" step="0.01" name="line_tax_rate[]" value="0.00">
            <strong data-line-total>0.00</strong>
            <button type="button" aria-label="Remove line">X</button>
        `;
        const select = line.querySelector('select');
        select.addEventListener('change', function () {
            const option = select.selectedOptions[0];
            const product = products.find((item) => String(item.id) === select.value);
            if (product) {
                line.querySelector('[name="line_description[]"]').value = product.label;
                line.querySelector('[name="line_unit_cost[]"]').value = money(option.dataset.cost || 0);
                line.querySelector('[name="line_tax_rate[]"]').value = money(option.dataset.tax || 0);
            }
            recalc();
        });
        line.querySelectorAll('input').forEach((input) => input.addEventListener('input', recalc));
        line.querySelector('button').addEventListener('click', function () {
            line.remove();
            recalc();
        });
        lineWrap.appendChild(line);
        recalc();
    }

    if (lineWrap && addLine) {
        addLine.addEventListener('click', addPurchaseLine);
        [discount, shipping].forEach((input) => input.addEventListener('input', recalc));
        addPurchaseLine();
    }
});
</script>

<?php onyx_page_end(); ?>
