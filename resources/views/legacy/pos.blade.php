<?php

$pdo = onyx_db();
$tenant_id = (int) onyx_tenant_id();

function pos_h(mixed $value): string
{
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
}

function pos_redirect(string $message = '', bool $success = true, ?int $receiptId = null): void
{
    $params = [];
    if ($message !== '') {
        $params[$success ? 'success' : 'error'] = $message;
    }
    if ($receiptId !== null) {
        $params['receipt'] = $receiptId;
    }
    header('Location: pos.php' . ($params === [] ? '' : '?' . http_build_query($params)));
    exit();
}

function pos_columns(PDO $pdo, string $table): array
{
    try {
        return array_map(static fn (array $row): string => $row['Field'], $pdo->query('SHOW COLUMNS FROM `' . str_replace('`', '``', $table) . '`')->fetchAll(PDO::FETCH_ASSOC));
    } catch (Throwable) {
        return [];
    }
}

function pos_ensure_column(PDO $pdo, string $table, string $column, string $definition): void
{
    if (! in_array($column, pos_columns($pdo, $table), true)) {
        $pdo->exec('ALTER TABLE `' . str_replace('`', '``', $table) . '` ADD COLUMN `' . str_replace('`', '``', $column) . '` ' . $definition);
    }
}

function pos_ensure_sales_tables(PDO $pdo): void
{
    $pdo->exec("CREATE TABLE IF NOT EXISTS invoices (
        id BIGINT(20) NOT NULL AUTO_INCREMENT,
        tenant_id BIGINT(20) NOT NULL,
        invoice_number VARCHAR(100) NOT NULL,
        invoice_type ENUM('invoice','return','quotation','delivery_note','credit_note') NOT NULL DEFAULT 'invoice',
        customer_id BIGINT(20) DEFAULT NULL,
        invoice_date DATE NOT NULL,
        due_date DATE DEFAULT NULL,
        notes TEXT DEFAULT NULL,
        subtotal DECIMAL(15,2) NOT NULL DEFAULT 0.00,
        tax DECIMAL(15,2) NOT NULL DEFAULT 0.00,
        total DECIMAL(15,2) NOT NULL DEFAULT 0.00,
        status ENUM('draft','approved','sent','partial','paid','overdue','cancelled') NOT NULL DEFAULT 'draft',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY idx_invoice_tenant (tenant_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    $pdo->exec("CREATE TABLE IF NOT EXISTS invoice_lines (
        id BIGINT(20) NOT NULL AUTO_INCREMENT,
        tenant_id BIGINT(20) NOT NULL,
        invoice_id BIGINT(20) NOT NULL,
        product_id BIGINT(20) DEFAULT NULL,
        description TEXT DEFAULT NULL,
        unit_price DECIMAL(15,2) NOT NULL DEFAULT 0.00,
        quantity INT(11) NOT NULL DEFAULT 1,
        tax_rate DECIMAL(5,2) NOT NULL DEFAULT 0.00,
        line_total DECIMAL(15,2) NOT NULL DEFAULT 0.00,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY idx_invoice_line_invoice (invoice_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    $pdo->exec("CREATE TABLE IF NOT EXISTS invoice_payments (
        id BIGINT(20) NOT NULL AUTO_INCREMENT,
        tenant_id BIGINT(20) NOT NULL,
        invoice_id BIGINT(20) NOT NULL,
        payment_date DATE NOT NULL,
        amount DECIMAL(15,2) NOT NULL DEFAULT 0.00,
        method VARCHAR(100) NOT NULL DEFAULT 'cash',
        reference VARCHAR(255) DEFAULT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY idx_invoice_payment_invoice (invoice_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    $pdo->exec("ALTER TABLE invoices MODIFY status ENUM('draft','approved','sent','partial','paid','overdue','cancelled') NOT NULL DEFAULT 'draft'");
    pos_ensure_column($pdo, 'invoices', 'terms', "VARCHAR(80) DEFAULT 'POS'");
    pos_ensure_column($pdo, 'invoices', 'salesperson', 'VARCHAR(155) DEFAULT NULL');
    pos_ensure_column($pdo, 'invoices', 'branch_name', 'VARCHAR(155) DEFAULT NULL');
    pos_ensure_column($pdo, 'invoices', 'customer_reference', 'VARCHAR(155) DEFAULT NULL');
    pos_ensure_column($pdo, 'invoices', 'discount', 'DECIMAL(15,2) NOT NULL DEFAULT 0.00');
    pos_ensure_column($pdo, 'invoices', 'delivery_charge', 'DECIMAL(15,2) NOT NULL DEFAULT 0.00');
    pos_ensure_column($pdo, 'invoices', 'source_invoice_id', 'BIGINT(20) DEFAULT NULL');
    pos_ensure_column($pdo, 'invoices', 'stock_posted', 'TINYINT(1) NOT NULL DEFAULT 0');
    pos_ensure_column($pdo, 'invoices', 'accounting_posted', 'TINYINT(1) NOT NULL DEFAULT 0');
    pos_ensure_column($pdo, 'invoice_lines', 'tenant_id', 'BIGINT(20) NOT NULL DEFAULT 0');
    pos_ensure_column($pdo, 'invoice_lines', 'description', 'TEXT DEFAULT NULL');
    pos_ensure_column($pdo, 'invoice_lines', 'tax_rate', 'DECIMAL(5,2) NOT NULL DEFAULT 0.00');
    pos_ensure_column($pdo, 'invoice_lines', 'line_total', 'DECIMAL(15,2) NOT NULL DEFAULT 0.00');
}

function pos_invoice_number(string $prefix = 'POS'): string
{
    return $prefix . '-' . date('ymd') . '-' . strtoupper(substr(uniqid(), -6));
}

function pos_money(mixed $amount, string $currency): string
{
    return number_format((float) ($amount ?? 0), 2) . ' ' . $currency;
}

function pos_badge(string $label, string $class = ''): string
{
    return '<span class="pos-badge ' . pos_h($class) . '">' . pos_h($label) . '</span>';
}

function pos_checkout(PDO $pdo, int $tenantId): void
{
    $cart = json_decode((string) ($_POST['cart_payload'] ?? '[]'), true);
    if (! is_array($cart) || $cart === []) {
        pos_redirect('Add at least one product to the cart.', false);
    }

    $customerId = (int) ($_POST['customer_id'] ?? 0) ?: null;
    $paymentMethod = trim($_POST['payment_method'] ?? 'cash') ?: 'cash';
    $reference = trim($_POST['payment_reference'] ?? '');
    $discount = max(0, (float) ($_POST['discount'] ?? 0));
    $amountPaid = max(0, (float) ($_POST['amount_paid'] ?? 0));
    $cashier = session('user_name', 'Operator');

    if ($paymentMethod === 'credit' && ! $customerId) {
        pos_redirect('Credit sale requires a selected customer.', false);
    }

    $items = [];
    $subtotal = 0.0;
    $tax = 0.0;

    foreach ($cart as $cartItem) {
        $productId = (int) ($cartItem['id'] ?? 0);
        $qty = max(0, (int) ($cartItem['qty'] ?? 0));
        if ($productId <= 0 || $qty <= 0) {
            continue;
        }

        $product = onyx_row(
            'SELECT id, name, sku, selling_price, vat_rate, current_stock FROM products WHERE id = :id AND tenant_id = :tenant_id',
            ['id' => $productId, 'tenant_id' => $tenantId]
        );
        if (! $product) {
            pos_redirect('A selected product could not be found.', false);
        }
        if ((int) $product['current_stock'] < $qty) {
            pos_redirect($product['name'] . ' has only ' . $product['current_stock'] . ' unit(s) in stock.', false);
        }

        $unitPrice = (float) $product['selling_price'];
        $taxRate = (float) ($product['vat_rate'] ?? 0);
        $lineSubtotal = $unitPrice * $qty;
        $lineTax = $lineSubtotal * ($taxRate / 100);
        $lineTotal = $lineSubtotal + $lineTax;
        $subtotal += $lineSubtotal;
        $tax += $lineTax;
        $items[] = [
            'product_id' => (int) $product['id'],
            'name' => $product['name'],
            'unit_price' => $unitPrice,
            'quantity' => $qty,
            'tax_rate' => $taxRate,
            'line_total' => $lineTotal,
        ];
    }

    if ($items === []) {
        pos_redirect('Add at least one valid product to the cart.', false);
    }

    $total = max(0, $subtotal + $tax - $discount);
    if ($paymentMethod !== 'credit' && $amountPaid + 0.001 < $total) {
        pos_redirect('Amount paid cannot be less than the sale total.', false);
    }

    $status = $paymentMethod === 'credit' ? ($amountPaid > 0 ? 'partial' : 'sent') : 'paid';
    $pdo->beginTransaction();
    try {
        $invoiceNumber = pos_invoice_number();
        $invoice = $pdo->prepare('INSERT INTO invoices (tenant_id, invoice_number, invoice_type, customer_id, invoice_date, due_date, notes, terms, salesperson, customer_reference, discount, delivery_charge, subtotal, tax, total, status, stock_posted, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, NOW(), NOW())');
        $invoice->execute([
            $tenantId,
            $invoiceNumber,
            'invoice',
            $customerId,
            date('Y-m-d'),
            $paymentMethod === 'credit' ? date('Y-m-d', strtotime('+7 days')) : null,
            'POS sale',
            'POS',
            $cashier,
            'POS Checkout',
            $discount,
            0,
            $subtotal,
            $tax,
            $total,
            $status,
        ]);
        $invoiceId = (int) $pdo->lastInsertId();

        $line = $pdo->prepare('INSERT INTO invoice_lines (tenant_id, invoice_id, product_id, description, unit_price, quantity, tax_rate, line_total, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())');
        $stock = $pdo->prepare('UPDATE products SET current_stock = current_stock - ?, updated_at = NOW() WHERE id = ? AND tenant_id = ?');
        foreach ($items as $item) {
            $line->execute([$tenantId, $invoiceId, $item['product_id'], $item['name'], $item['unit_price'], $item['quantity'], $item['tax_rate'], $item['line_total']]);
            $stock->execute([$item['quantity'], $item['product_id'], $tenantId]);
            try {
                $pdo->prepare('INSERT INTO inventory_transactions (tenant_id, product_id, transaction_type, quantity, reference, notes, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())')
                    ->execute([$tenantId, $item['product_id'], 'sold', $item['quantity'], $invoiceNumber, 'Posted from POS']);
            } catch (Throwable) {
                // Inventory transaction table is optional in some local databases.
            }
        }

        if ($amountPaid > 0) {
            $payment = $pdo->prepare('INSERT INTO invoice_payments (tenant_id, invoice_id, payment_date, amount, method, reference, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())');
            $payment->execute([$tenantId, $invoiceId, date('Y-m-d'), min($amountPaid, $total), $paymentMethod, $reference]);
        }

        $pdo->commit();
        pos_redirect('POS sale completed.', true, $invoiceId);
    } catch (Throwable $e) {
        $pdo->rollBack();
        pos_redirect('Unable to complete POS sale: ' . $e->getMessage(), false);
    }
}

pos_ensure_sales_tables($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'checkout') {
    pos_checkout($pdo, $tenant_id);
}

$context = onyx_page_start('POS', 'Fast product search, cashier cart, payments, receipts, and daily sales tracking.');
$currency = $context['currency'];

$products = onyx_rows(
    'SELECT p.id, p.name, p.sku, p.barcode, p.selling_price, p.vat_rate, p.current_stock, p.min_stock, p.image_url, pc.name AS category_name
     FROM products p
     LEFT JOIN product_categories pc ON pc.id = p.product_category_id AND pc.tenant_id = p.tenant_id
     WHERE p.tenant_id = :tenant_id
     ORDER BY p.name ASC',
    ['tenant_id' => $tenant_id]
);
$customers = onyx_rows(
    'SELECT id, name, phone, email, credit_status, credit_limit, credit_balance
     FROM customers
     WHERE tenant_id = :tenant_id AND is_active = 1
     ORDER BY name ASC',
    ['tenant_id' => $tenant_id]
);
$categories = array_values(array_unique(array_filter(array_map(static fn (array $product): string => (string) ($product['category_name'] ?? ''), $products))));
$todaySales = (float) onyx_scalar('SELECT COALESCE(SUM(total), 0) FROM invoices WHERE tenant_id = :tenant_id AND invoice_type = "invoice" AND invoice_date = :today AND customer_reference = "POS Checkout"', ['tenant_id' => $tenant_id, 'today' => date('Y-m-d')], 0);
$todayCash = (float) onyx_scalar(
    'SELECT COALESCE(SUM(p.amount), 0)
     FROM invoice_payments p
     JOIN invoices i ON i.id = p.invoice_id AND i.tenant_id = p.tenant_id
     WHERE p.tenant_id = :tenant_id AND p.payment_date = :today AND i.customer_reference = "POS Checkout"',
    ['tenant_id' => $tenant_id, 'today' => date('Y-m-d')],
    0
);
$todayReceipts = (int) onyx_scalar('SELECT COUNT(*) FROM invoices WHERE tenant_id = :tenant_id AND invoice_type = "invoice" AND invoice_date = :today AND customer_reference = "POS Checkout"', ['tenant_id' => $tenant_id, 'today' => date('Y-m-d')], 0);
$recentReceipts = onyx_rows(
    'SELECT i.id, i.invoice_number, i.invoice_date, i.total, i.status, c.name AS customer_name, COALESCE(SUM(p.amount), 0) AS paid_amount
     FROM invoices i
     LEFT JOIN customers c ON c.id = i.customer_id AND c.tenant_id = i.tenant_id
     LEFT JOIN invoice_payments p ON p.invoice_id = i.id AND p.tenant_id = i.tenant_id
     WHERE i.tenant_id = :tenant_id AND i.invoice_type = "invoice" AND i.customer_reference = "POS Checkout"
     GROUP BY i.id, i.invoice_number, i.invoice_date, i.total, i.status, c.name
     ORDER BY i.id DESC
     LIMIT 12',
    ['tenant_id' => $tenant_id]
);
$receipt = null;
$receiptLines = [];
$receiptPayments = [];
if (isset($_GET['receipt'])) {
    $receiptId = (int) $_GET['receipt'];
    $receipt = onyx_row(
        'SELECT i.*, c.name AS customer_name, c.phone AS customer_phone
         FROM invoices i
         LEFT JOIN customers c ON c.id = i.customer_id AND c.tenant_id = i.tenant_id
         WHERE i.id = :id AND i.tenant_id = :tenant_id',
        ['id' => $receiptId, 'tenant_id' => $tenant_id]
    );
    if ($receipt) {
        $receiptLines = onyx_rows('SELECT * FROM invoice_lines WHERE tenant_id = :tenant_id AND invoice_id = :invoice_id ORDER BY id ASC', ['tenant_id' => $tenant_id, 'invoice_id' => $receiptId]);
        $receiptPayments = onyx_rows('SELECT * FROM invoice_payments WHERE tenant_id = :tenant_id AND invoice_id = :invoice_id ORDER BY id ASC', ['tenant_id' => $tenant_id, 'invoice_id' => $receiptId]);
    }
}
$productPayload = array_map(static fn (array $product): array => [
    'id' => (int) $product['id'],
    'name' => $product['name'],
    'sku' => $product['sku'],
    'barcode' => $product['barcode'],
    'category' => $product['category_name'] ?: 'Uncategorized',
    'price' => (float) $product['selling_price'],
    'tax' => (float) ($product['vat_rate'] ?? 0),
    'stock' => (int) ($product['current_stock'] ?? 0),
    'minStock' => (int) ($product['min_stock'] ?? 0),
    'image' => $product['image_url'] ?: '',
], $products);
$customerPayload = array_map(static fn (array $customer): array => [
    'id' => (int) $customer['id'],
    'name' => $customer['name'],
    'phone' => $customer['phone'] ?: '',
    'email' => $customer['email'] ?: '',
    'creditStatus' => $customer['credit_status'] ?: 'good',
    'creditLimit' => (float) ($customer['credit_limit'] ?? 0),
    'creditBalance' => (float) ($customer['credit_balance'] ?? 0),
], $customers);
?>

<style>
    .pos-page,.pos-page *{border-radius:0!important}
    .pos-page{display:grid;gap:16px}
    .pos-shell{display:grid;gap:16px;grid-template-columns:1fr}
    .pos-panel{background:var(--onyx-surface);border:1px solid var(--onyx-border);overflow:hidden;padding:16px}
    .pos-title{align-items:center;color:#fff;display:flex;font-size:10px;font-weight:800;gap:9px;margin-bottom:12px;text-transform:uppercase}
    .pos-status-grid{display:grid;gap:10px;grid-template-columns:repeat(3,minmax(150px,1fr))}
    .pos-stat{border:1px solid rgba(255,255,255,.08);display:grid;gap:5px;padding:12px}.pos-stat span{color:var(--onyx-muted);font-size:9px;font-weight:800;text-transform:uppercase}.pos-stat strong{color:#fff;font-size:16px}.pos-stat small{color:var(--onyx-muted);font-size:10px}
    .pos-filterbar{align-items:end;display:grid;gap:10px;grid-template-columns:1fr 1fr 1fr auto}.pos-field{display:grid;gap:5px;min-width:0}.pos-field label{color:var(--onyx-muted);font-size:9px;font-weight:800;text-transform:uppercase}
    .pos-scan-field{background:rgba(0,0,0,.12);border:1px solid rgba(255,255,255,.08);display:grid;gap:7px;margin-bottom:10px;padding:12px}.pos-scan-field label{align-items:center;color:#fff;display:flex;font-size:10px;font-weight:800;gap:8px;text-transform:uppercase}.pos-scan-field input{background:rgba(0,0,0,.18);border:1px solid rgba(255,255,255,.1);color:#fff;font-family:Poppins,system-ui,sans-serif;font-size:12px;font-weight:700;min-height:40px;outline:0;padding:9px 10px;width:100%}.pos-scan-help{display:none}
    .pos-field input,.pos-field select,.pos-cart-select,.pos-cart-input{background:rgba(0,0,0,.18);border:1px solid rgba(255,255,255,.1);color:#fff;font-family:Poppins,system-ui,sans-serif;font-size:10px;min-height:36px;outline:0;padding:8px 10px;width:100%}
    .pos-field select option,.pos-cart-select option{background:#050506;color:#fff}
    .pos-btn{align-items:center;background:rgba(255,255,255,.035);border:1px solid rgba(255,255,255,.1);color:#fff;cursor:pointer;display:inline-flex;font:inherit;font-size:10px;font-weight:800;gap:8px;justify-content:center;min-height:36px;padding:0 11px;text-decoration:none;text-transform:uppercase}.pos-btn.primary{background:#fff;color:#050506}.pos-btn.danger{color:#ff8a8a}.pos-btn:disabled{cursor:not-allowed;opacity:.45}
    .pos-chips{display:flex;flex-wrap:wrap;gap:7px;margin-top:10px}.pos-chip{background:rgba(255,255,255,.035);border:1px solid rgba(255,255,255,.09);color:#d8d8de;cursor:pointer;font:inherit;font-size:9px;font-weight:800;min-height:28px;padding:0 9px;text-transform:uppercase}.pos-chip.active{background:#fff;color:#050506}
    .pos-products{display:grid;gap:10px;grid-template-columns:repeat(auto-fill,minmax(150px,1fr));margin-top:12px}.pos-product{background:rgba(255,255,255,.025);border:1px solid rgba(255,255,255,.08);cursor:pointer;display:grid;gap:8px;min-height:132px;padding:11px;text-align:left}.pos-product:hover{background:rgba(255,255,255,.04);border-color:rgba(255,255,255,.2)}.pos-product.disabled{cursor:not-allowed;opacity:.48}
    .pos-fast-row{display:none}
    .pos-product-media{align-items:center;background:rgba(0,0,0,.18);border:1px solid rgba(255,255,255,.06);display:flex;height:38px;justify-content:center;overflow:hidden}.pos-product-media img{height:100%;object-fit:cover;width:100%}.pos-product-media span{font-size:12px;font-weight:800}
    .pos-product strong{color:#fff;font-size:11px;line-height:1.25;min-height:28px}.pos-product small{color:var(--onyx-muted);font-size:9px;line-height:1.25}.pos-product-price{color:#fff;font-size:12px;font-weight:800}
    .pos-badge{border:1px solid rgba(255,255,255,.12);color:#d8d8de;display:inline-flex;font-size:8px;font-weight:800;padding:4px 6px;text-transform:uppercase;white-space:nowrap}.pos-badge.ok{color:#8ff0c3}.pos-badge.warn{color:#ffd27a}.pos-badge.danger{color:#ff8a8a}.pos-badge.info{color:#9fd7ff}
    .pos-cart{position:static}.pos-cart-grid{display:grid;gap:14px;grid-template-columns:minmax(240px,.8fr) minmax(0,1.35fr) minmax(260px,.85fr)}.pos-cart-lines{display:grid;gap:8px;max-height:325px;overflow:auto;padding-right:4px}.pos-cart-line{border:1px solid rgba(255,255,255,.08);display:grid;gap:8px;padding:10px}.pos-cart-line-head{align-items:start;display:flex;gap:8px;justify-content:space-between}.pos-cart-line strong{color:#fff;font-size:11px}.pos-cart-line span{color:var(--onyx-muted);font-size:10px}.pos-qty{align-items:center;display:flex;gap:6px}.pos-qty button{background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.1);color:#fff;cursor:pointer;height:28px;width:28px}.pos-qty input{background:rgba(0,0,0,.18);border:1px solid rgba(255,255,255,.1);color:#fff;font-size:10px;height:28px;text-align:center;width:48px}
    .pos-total-list{display:grid;gap:8px;margin-top:0}.pos-total-list div{align-items:center;border-bottom:1px solid rgba(255,255,255,.06);display:flex;font-size:11px;justify-content:space-between;padding-bottom:7px}.pos-total-list span{color:var(--onyx-muted)}.pos-total-list strong{color:#fff}.pos-total-list .grand strong{font-size:18px}
    .pos-payment-grid{display:grid;gap:8px;grid-template-columns:repeat(3,1fr);margin-top:10px}.pos-payment-grid .pos-btn.active{background:#fff;color:#050506}
    .pos-customer-card{border:1px solid rgba(255,255,255,.08);display:grid;gap:5px;margin-top:8px;padding:10px}.pos-customer-card strong{color:#fff;font-size:11px}.pos-customer-card span{color:var(--onyx-muted);font-size:10px}.pos-payment-reference.is-hidden{display:none}
    .pos-tender{border:1px solid rgba(255,255,255,.08);margin-top:10px;padding:10px}.pos-tender-title{color:var(--onyx-muted);font-size:9px;font-weight:800;margin-bottom:8px;text-transform:uppercase}
    .pos-actions{display:grid;gap:8px;grid-template-columns:repeat(2,1fr);margin-top:12px}.pos-actions .wide{grid-column:span 2}
    .pos-bottom-grid{display:grid;gap:16px;grid-template-columns:1fr}.pos-table-wrap{overflow:auto}.pos-table{border-collapse:collapse;min-width:620px;width:100%}.pos-table th,.pos-table td{border-bottom:1px solid rgba(255,255,255,.06);font-size:10px;padding:9px;text-align:left}.pos-table th{color:var(--onyx-muted);font-size:9px;font-weight:800;text-transform:uppercase}
    .pos-receipt{background:#f7f8fa;color:#171b22;font-family:Arial,sans-serif;padding:16px}.pos-receipt h3{font-size:15px;margin:0}.pos-receipt small{color:#697281}.pos-receipt table{border-collapse:collapse;margin-top:10px;width:100%}.pos-receipt td,.pos-receipt th{border-bottom:1px solid #dde2ea;font-size:11px;padding:7px;text-align:left}.pos-receipt .right{text-align:right}.pos-receipt-total{font-weight:800}
    .pos-empty{border:1px solid rgba(255,255,255,.08);color:var(--onyx-muted);font-size:11px;padding:14px}.pos-alert{border:1px solid rgba(143,240,195,.24);color:#8ff0c3;font-size:11px;font-weight:700;padding:11px 12px}.pos-alert.error{border-color:rgba(255,138,138,.28);color:#ff8a8a}
    @media(max-width:1200px){.pos-cart-grid{grid-template-columns:1fr}.pos-filterbar{grid-template-columns:1fr 1fr}.pos-status-grid{grid-template-columns:repeat(2,1fr)}}
    @media(max-width:640px){.pos-filterbar,.pos-status-grid,.pos-payment-grid,.pos-actions{grid-template-columns:1fr}.pos-actions .wide{grid-column:auto}}
    @media print{body *{visibility:hidden}.pos-receipt,.pos-receipt *{visibility:visible}.pos-receipt{left:0;position:absolute;top:0;width:100%}.no-print{display:none!important}}
</style>

<div class="pos-page">
    <?php if (isset($_GET['success'])): ?><div class="pos-alert"><?= pos_h($_GET['success']) ?></div><?php endif; ?>
    <?php if (isset($_GET['error'])): ?><div class="pos-alert error"><?= pos_h($_GET['error']) ?></div><?php endif; ?>

    <section class="pos-status-grid">
        <div class="pos-stat"><span>Receipts Today</span><strong><?= pos_h($todayReceipts) ?></strong><small>Completed POS sales</small></div>
        <div class="pos-stat"><span>POS Sales</span><strong><?= pos_h(pos_money($todaySales, $currency)) ?></strong><small>Total checkout value</small></div>
        <div class="pos-stat"><span>Collections</span><strong><?= pos_h(pos_money($todayCash, $currency)) ?></strong><small>Paid through POS</small></div>
    </section>

    <div class="pos-shell">
        <main class="pos-panel">
            <div class="pos-title"><i class="fa-solid fa-boxes-stacked"></i> Products</div>
            <div class="pos-scan-field">
                <label for="pos-search"><i class="fa-solid fa-magnifying-glass"></i> Product Search</label>
                <input id="pos-search" placeholder="Scan barcode, type SKU, or search product name" autofocus autocomplete="off">
            </div>
            <div class="pos-filterbar">
                <div class="pos-field"><label>Category</label><select id="pos-category"><option value="">All categories</option><?php foreach ($categories as $category): ?><option value="<?= pos_h($category) ?>"><?= pos_h($category) ?></option><?php endforeach; ?></select></div>
                <div class="pos-field"><label>Stock</label><select id="pos-stock"><option value="">All stock</option><option value="in">In stock</option><option value="low">Low stock</option><option value="out">Out of stock</option></select></div>
                <div class="pos-field"><label>Sort</label><select id="pos-sort"><option value="name">Name</option><option value="price_asc">Price low</option><option value="price_desc">Price high</option><option value="stock">Stock</option></select></div>
                <button class="pos-btn" type="button" id="pos-clear"><i class="fa-solid fa-xmark"></i> Clear</button>
            </div>
            <div class="pos-chips">
                <button class="pos-chip active" type="button" data-category="">All</button>
                <?php foreach (array_slice($categories, 0, 10) as $category): ?><button class="pos-chip" type="button" data-category="<?= pos_h($category) ?>"><?= pos_h($category) ?></button><?php endforeach; ?>
            </div>
            <div class="pos-fast-row" id="pos-fast-row"></div>
            <div class="pos-products" id="pos-products"></div>
        </main>

        <aside class="pos-panel pos-cart">
            <div class="pos-title"><i class="fa-solid fa-cart-shopping"></i> Cart & Checkout</div>
            <div class="pos-cart-grid">
                <div>
                    <div class="pos-field">
                        <label>Customer</label>
                        <select class="pos-cart-select" id="pos-customer" name="customer_id" form="pos-checkout-form">
                            <option value="">Walk-in Customer</option>
                            <?php foreach ($customers as $customer): ?>
                                <option value="<?= pos_h($customer['id']) ?>"><?= pos_h($customer['name']) ?><?= $customer['phone'] ? ' · ' . pos_h($customer['phone']) : '' ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="pos-customer-card" id="pos-customer-card">
                        <strong>Walk-in Customer</strong>
                        <span>No account selected · credit disabled</span>
                    </div>
                </div>
                <div class="pos-cart-lines" id="pos-cart-lines">
                    <div class="pos-empty">Cart is empty. Select products from the grid.</div>
                </div>
                <div>
                    <div class="pos-total-list">
                        <div><span>Items</span><strong id="pos-items">0</strong></div>
                        <div><span>Subtotal</span><strong id="pos-subtotal">0.00</strong></div>
                        <div><span>Tax</span><strong id="pos-tax">0.00</strong></div>
                        <div><span>Discount</span><input class="pos-cart-input" id="pos-discount" name="discount" form="pos-checkout-form" type="number" step="0.01" value="0.00" style="max-width:120px;"></div>
                        <div class="grand"><span>Total</span><strong id="pos-total">0.00</strong></div>
                        <div><span>Paid</span><input class="pos-cart-input" id="pos-paid" name="amount_paid" form="pos-checkout-form" type="number" step="0.01" value="0.00" style="max-width:120px;"></div>
                        <div><span>Change</span><strong id="pos-change">0.00</strong></div>
                    </div>
                    <div class="pos-tender">
                        <div class="pos-tender-title">Tender</div>
                        <div class="pos-payment-grid">
                            <?php foreach (['cash' => 'Cash', 'mobile_money' => 'Mobile', 'card' => 'Card', 'bank_transfer' => 'Bank', 'credit' => 'Credit', 'other' => 'Other'] as $method => $label): ?>
                                <button class="pos-btn <?= $method === 'cash' ? 'active' : '' ?>" type="button" data-payment="<?= pos_h($method) ?>"><?= pos_h($label) ?></button>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
            <form method="POST" action="pos.php" id="pos-checkout-form">
                <input type="hidden" name="action" value="checkout">
                <input type="hidden" name="cart_payload" id="pos-cart-payload">
                <input type="hidden" name="payment_method" id="pos-payment-method" value="cash">
                <div class="pos-field pos-payment-reference is-hidden" style="margin-top:10px;"><label>Payment Reference</label><input class="pos-cart-input" name="payment_reference" placeholder="Mobile money ref, card ref, cheque no."></div>
                <div class="pos-actions">
                    <button class="pos-btn" type="button" id="pos-hold"><i class="fa-solid fa-pause"></i> Hold</button>
                    <button class="pos-btn danger" type="button" id="pos-void"><i class="fa-solid fa-trash"></i> Void</button>
                    <button class="pos-btn primary wide" type="submit" id="pos-checkout"><i class="fa-solid fa-receipt"></i> Complete Sale</button>
                </div>
            </form>
        </aside>
    </div>

    <section class="pos-bottom-grid">
        <div class="pos-panel">
            <div class="pos-title"><i class="fa-solid fa-clock-rotate-left"></i> Held Sales</div>
            <div id="pos-held-list" class="pos-table-wrap"><div class="pos-empty">No held sales on this terminal.</div></div>
        </div>
        <div class="pos-panel">
            <div class="pos-title"><i class="fa-solid fa-file-invoice-dollar"></i> Recent Receipts</div>
            <div class="pos-table-wrap">
                <table class="pos-table">
                    <thead><tr><th>Receipt</th><th>Customer</th><th>Total</th><th>Status</th><th>Action</th></tr></thead>
                    <tbody>
                    <?php if ($recentReceipts === []): ?>
                        <tr><td colspan="5" class="pos-empty">No POS receipts yet.</td></tr>
                    <?php else: ?>
                        <?php foreach ($recentReceipts as $row): ?>
                            <tr>
                                <td><?= pos_h($row['invoice_number']) ?><br><span class="muted"><?= pos_h($row['invoice_date']) ?></span></td>
                                <td><?= pos_h($row['customer_name'] ?: 'Walk-in') ?></td>
                                <td><?= pos_h(pos_money($row['total'], $currency)) ?></td>
                                <td><?= pos_badge(ucwords($row['status']), $row['status'] === 'paid' ? 'ok' : 'warn') ?></td>
                                <td><a class="pos-btn" href="<?= pos_h(onyx_legacy_url('pos.php?receipt=' . (int) $row['id'])) ?>">View</a></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </section>

    <?php if ($receipt): ?>
        <section class="pos-panel">
            <div class="pos-title no-print"><i class="fa-solid fa-receipt"></i> Receipt Preview</div>
            <div class="pos-receipt" id="pos-receipt">
                <div style="display:flex;justify-content:space-between;gap:16px;">
                    <div><h3><?= pos_h(session('company_name', 'Onyx BCS')) ?></h3><small>POS Receipt</small></div>
                    <div class="right"><strong><?= pos_h($receipt['invoice_number']) ?></strong><br><small><?= pos_h($receipt['invoice_date']) ?></small></div>
                </div>
                <div style="margin-top:10px;"><small>Customer: <?= pos_h($receipt['customer_name'] ?: 'Walk-in Customer') ?></small></div>
                <table>
                    <thead><tr><th>Item</th><th class="right">Qty</th><th class="right">Amount</th></tr></thead>
                    <tbody>
                    <?php foreach ($receiptLines as $line): ?>
                        <tr><td><?= pos_h($line['description']) ?></td><td class="right"><?= pos_h($line['quantity']) ?></td><td class="right"><?= pos_h(number_format((float) $line['line_total'], 2)) ?></td></tr>
                    <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr><td colspan="2">Subtotal</td><td class="right"><?= pos_h(number_format((float) $receipt['subtotal'], 2)) ?></td></tr>
                        <tr><td colspan="2">Tax</td><td class="right"><?= pos_h(number_format((float) $receipt['tax'], 2)) ?></td></tr>
                        <tr><td colspan="2">Discount</td><td class="right"><?= pos_h(number_format((float) ($receipt['discount'] ?? 0), 2)) ?></td></tr>
                        <tr class="pos-receipt-total"><td colspan="2">Total</td><td class="right"><?= pos_h(pos_money($receipt['total'], $currency)) ?></td></tr>
                    </tfoot>
                </table>
                <?php if ($receiptPayments !== []): ?><small>Payment: <?= pos_h($receiptPayments[0]['method']) ?> · <?= pos_h(number_format((float) $receiptPayments[0]['amount'], 2)) ?></small><?php endif; ?>
            </div>
            <div class="pos-actions no-print" style="max-width:360px;">
                <button class="pos-btn primary" type="button" onclick="window.print()"><i class="fa-solid fa-print"></i> Print Receipt</button>
                <a class="pos-btn" href="<?= pos_h(onyx_legacy_url('sales_action.php?action=view_invoice&id=' . (int) $receipt['id'])) ?>"><i class="fa-solid fa-eye"></i> Open Invoice</a>
            </div>
        </section>
    <?php endif; ?>
</div>

<script>
    const POS_PRODUCTS = <?= json_encode($productPayload, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
    const POS_CUSTOMERS = <?= json_encode($customerPayload, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
    const POS_CURRENCY = <?= json_encode($currency) ?>;
    const posCart = new Map();
    const productGrid = document.querySelector('#pos-products');
    const fastRow = document.querySelector('#pos-fast-row');
    const cartLines = document.querySelector('#pos-cart-lines');
    const searchInput = document.querySelector('#pos-search');
    const categorySelect = document.querySelector('#pos-category');
    const stockSelect = document.querySelector('#pos-stock');
    const sortSelect = document.querySelector('#pos-sort');
    const customerSelect = document.querySelector('#pos-customer');
    const customerCard = document.querySelector('#pos-customer-card');
    const discountInput = document.querySelector('#pos-discount');
    const paidInput = document.querySelector('#pos-paid');
    const cartPayload = document.querySelector('#pos-cart-payload');
    const paymentMethodInput = document.querySelector('#pos-payment-method');
    const paymentReference = document.querySelector('.pos-payment-reference');

    function money(value) {
        return `${Number(value || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })} ${POS_CURRENCY}`;
    }

    function escapeHtml(value) {
        return String(value ?? '').replace(/[&<>"']/g, (char) => ({
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        }[char]));
    }

    function initials(name) {
        return String(name || 'P').split(/\s+/).slice(0, 2).map((part) => part[0] || '').join('').toUpperCase();
    }

    function filteredProducts() {
        const query = searchInput.value.trim().toLowerCase();
        const category = categorySelect.value;
        const stock = stockSelect.value;
        const sort = sortSelect.value;
        return POS_PRODUCTS.filter((product) => {
            const haystack = `${product.name} ${product.sku || ''} ${product.barcode || ''}`.toLowerCase();
            if (query && !haystack.includes(query)) return false;
            if (category && product.category !== category) return false;
            if (stock === 'in' && product.stock <= 0) return false;
            if (stock === 'out' && product.stock > 0) return false;
            if (stock === 'low' && !(product.stock > 0 && product.stock <= product.minStock)) return false;
            return true;
        }).sort((a, b) => {
            if (sort === 'price_asc') return a.price - b.price;
            if (sort === 'price_desc') return b.price - a.price;
            if (sort === 'stock') return b.stock - a.stock;
            return a.name.localeCompare(b.name);
        });
    }

    function renderProducts() {
        const products = filteredProducts();
        if (!products.length) {
            productGrid.innerHTML = '<div class="pos-empty">No products match the current filters.</div>';
            return;
        }
        productGrid.innerHTML = products.map((product) => {
            const stockClass = product.stock <= 0 ? 'danger' : (product.stock <= product.minStock ? 'warn' : 'ok');
            const disabled = product.stock <= 0 ? ' disabled' : '';
            const media = product.image ? `<img src="${escapeHtml(product.image)}" alt="">` : `<span>${escapeHtml(initials(product.name))}</span>`;
            return `<button class="pos-product${disabled}" type="button" data-id="${product.id}" ${product.stock <= 0 ? 'disabled' : ''}>
                <div class="pos-product-media">${media}</div>
                <strong>${escapeHtml(product.name)}</strong>
                <small>${escapeHtml(product.sku || 'No SKU')} · ${escapeHtml(product.category)}</small>
                <div class="pos-product-price">${money(product.price)}</div>
                <div>${product.tax > 0 ? `<span class="pos-badge info">VAT ${product.tax}%</span>` : ''} <span class="pos-badge ${stockClass}">Stock ${product.stock}</span></div>
            </button>`;
        }).join('');
    }

    function fastMoverProducts() {
        return [...POS_PRODUCTS]
            .filter((product) => product.stock > 0)
            .sort((a, b) => {
                const aScore = (a.stock <= a.minStock ? 2 : 0) + (a.price > 0 ? 1 : 0);
                const bScore = (b.stock <= b.minStock ? 2 : 0) + (b.price > 0 ? 1 : 0);
                return bScore - aScore || a.name.localeCompare(b.name);
            })
            .slice(0, 8);
    }

    function renderFastMovers() {
        const products = fastMoverProducts();
        if (!products.length) {
            fastRow.innerHTML = '';
            return;
        }
        fastRow.innerHTML = products.map((product) => `
            <button class="pos-fast-card" type="button" data-fast-id="${product.id}">
                <strong>${escapeHtml(product.name)}</strong>
                <span>${money(product.price)}</span>
                <span>Stock ${product.stock}</span>
            </button>
        `).join('');
    }

    function addToCart(id) {
        const product = POS_PRODUCTS.find((item) => item.id === Number(id));
        if (!product || product.stock <= 0) return;
        const current = posCart.get(product.id) || { ...product, qty: 0 };
        if (current.qty >= product.stock) return;
        current.qty += 1;
        posCart.set(product.id, current);
        renderCart();
    }

    function setQty(id, qty) {
        const item = posCart.get(Number(id));
        if (!item) return;
        const next = Math.max(0, Math.min(item.stock, Number(qty) || 0));
        if (next <= 0) posCart.delete(Number(id));
        else posCart.set(Number(id), { ...item, qty: next });
        renderCart();
    }

    function totals() {
        let items = 0, subtotal = 0, tax = 0;
        posCart.forEach((item) => {
            items += item.qty;
            const lineSubtotal = item.price * item.qty;
            subtotal += lineSubtotal;
            tax += lineSubtotal * (item.tax / 100);
        });
        const discount = Math.max(0, Number(discountInput.value || 0));
        const total = Math.max(0, subtotal + tax - discount);
        const paid = Math.max(0, Number(paidInput.value || 0));
        return { items, subtotal, tax, discount, total, paid, change: Math.max(0, paid - total) };
    }

    function renderCart() {
        if (!posCart.size) {
            cartLines.innerHTML = '<div class="pos-empty">Cart is empty. Select products from the grid.</div>';
        } else {
            cartLines.innerHTML = Array.from(posCart.values()).map((item) => `
                <div class="pos-cart-line">
                    <div class="pos-cart-line-head">
                        <div><strong>${escapeHtml(item.name)}</strong><br><span>${money(item.price)} · VAT ${item.tax}%</span></div>
                        <button class="pos-btn danger" type="button" data-remove="${item.id}">Remove</button>
                    </div>
                    <div class="pos-cart-line-head">
                        <div class="pos-qty">
                            <button type="button" data-dec="${item.id}">-</button>
                            <input value="${item.qty}" data-qty="${item.id}" inputmode="numeric">
                            <button type="button" data-inc="${item.id}">+</button>
                        </div>
                        <strong>${money((item.price * item.qty) * (1 + item.tax / 100))}</strong>
                    </div>
                </div>
            `).join('');
        }
        const current = totals();
        document.querySelector('#pos-items').textContent = current.items;
        document.querySelector('#pos-subtotal').textContent = money(current.subtotal);
        document.querySelector('#pos-tax').textContent = money(current.tax);
        document.querySelector('#pos-total').textContent = money(current.total);
        document.querySelector('#pos-change').textContent = money(current.change);
        cartPayload.value = JSON.stringify(Array.from(posCart.values()).map((item) => ({ id: item.id, qty: item.qty })));
    }

    function renderCustomerCard() {
        const customer = POS_CUSTOMERS.find((item) => item.id === Number(customerSelect.value));
        if (!customer) {
            customerCard.innerHTML = '<strong>Walk-in Customer</strong><span>No account selected · credit disabled</span>';
            return;
        }
        const balanceClass = customer.creditBalance > customer.creditLimit && customer.creditLimit > 0 ? 'danger' : (customer.creditBalance > 0 ? 'warn' : 'ok');
        customerCard.innerHTML = `
            <strong>${escapeHtml(customer.name)}</strong>
            <span>${escapeHtml(customer.phone || customer.email || 'No contact saved')}</span>
            <span>${customer.creditStatus ? escapeHtml(customer.creditStatus.replace(/_/g, ' ')) : 'good'} · Balance <span class="pos-badge ${balanceClass}">${money(customer.creditBalance)}</span> · Limit ${money(customer.creditLimit)}</span>
        `;
    }

    function setPaymentMethod(method) {
        paymentMethodInput.value = method;
        document.querySelectorAll('[data-payment]').forEach((item) => item.classList.toggle('active', item.dataset.payment === method));
        paymentReference.classList.toggle('is-hidden', ['cash', 'credit'].includes(method));
        const current = totals();
        if (method !== 'credit') paidInput.value = current.total.toFixed(2);
        if (method === 'credit') paidInput.value = '0.00';
        renderCart();
    }

    function addBestSearchMatch() {
        const query = searchInput.value.trim().toLowerCase();
        if (!query) return false;
        const exact = POS_PRODUCTS.find((product) => [product.barcode, product.sku].filter(Boolean).some((value) => String(value).toLowerCase() === query));
        const first = exact || filteredProducts()[0];
        if (first && first.stock > 0) {
            addToCart(first.id);
            searchInput.value = '';
            renderProducts();
            searchInput.focus();
            return true;
        }
        return false;
    }

    function heldSales() {
        try { return JSON.parse(localStorage.getItem('onyx_pos_held_sales') || '[]'); } catch { return []; }
    }

    function saveHeldSales(items) {
        localStorage.setItem('onyx_pos_held_sales', JSON.stringify(items));
        renderHeldSales();
    }

    function renderHeldSales() {
        const target = document.querySelector('#pos-held-list');
        const held = heldSales();
        if (!held.length) {
            target.innerHTML = '<div class="pos-empty">No held sales on this terminal.</div>';
            return;
        }
        target.innerHTML = `<table class="pos-table"><thead><tr><th>Time</th><th>Items</th><th>Total</th><th>Action</th></tr></thead><tbody>${held.map((sale, index) => `
            <tr><td>${sale.time}</td><td>${sale.items}</td><td>${money(sale.total)}</td><td><button class="pos-btn" type="button" data-resume="${index}">Resume</button> <button class="pos-btn danger" type="button" data-drop-held="${index}">Drop</button></td></tr>
        `).join('')}</tbody></table>`;
    }

    productGrid.addEventListener('click', (event) => {
        const product = event.target.closest('[data-id]');
        if (product) addToCart(product.dataset.id);
    });
    fastRow.addEventListener('click', (event) => {
        const product = event.target.closest('[data-fast-id]');
        if (product) addToCart(product.dataset.fastId);
    });
    cartLines.addEventListener('click', (event) => {
        const inc = event.target.closest('[data-inc]');
        const dec = event.target.closest('[data-dec]');
        const remove = event.target.closest('[data-remove]');
        if (inc) setQty(inc.dataset.inc, (posCart.get(Number(inc.dataset.inc))?.qty || 0) + 1);
        if (dec) setQty(dec.dataset.dec, (posCart.get(Number(dec.dataset.dec))?.qty || 0) - 1);
        if (remove) setQty(remove.dataset.remove, 0);
    });
    cartLines.addEventListener('input', (event) => {
        if (event.target.matches('[data-qty]')) setQty(event.target.dataset.qty, event.target.value);
    });
    [searchInput, categorySelect, stockSelect, sortSelect].forEach((input) => input.addEventListener('input', renderProducts));
    searchInput.addEventListener('keydown', (event) => {
        if (event.key === 'Enter') {
            event.preventDefault();
            addBestSearchMatch();
        }
    });
    customerSelect.addEventListener('change', renderCustomerCard);
    [discountInput, paidInput].forEach((input) => input.addEventListener('input', renderCart));
    document.querySelector('#pos-clear').addEventListener('click', () => {
        searchInput.value = ''; categorySelect.value = ''; stockSelect.value = ''; sortSelect.value = 'name';
        document.querySelectorAll('.pos-chip').forEach((chip) => chip.classList.toggle('active', chip.dataset.category === ''));
        renderProducts();
    });
    document.querySelectorAll('[data-category]').forEach((chip) => {
        chip.addEventListener('click', () => {
            categorySelect.value = chip.dataset.category;
            document.querySelectorAll('.pos-chip').forEach((item) => item.classList.toggle('active', item === chip));
            renderProducts();
        });
    });
    document.querySelectorAll('[data-payment]').forEach((button) => {
        button.addEventListener('click', () => setPaymentMethod(button.dataset.payment));
    });
    document.querySelector('#pos-hold').addEventListener('click', () => {
        if (!posCart.size) return;
        const current = totals();
        const held = heldSales();
        held.splice(0, 0, { time: new Date().toLocaleString(), items: current.items, total: current.total, cart: Array.from(posCart.values()).map((item) => ({ id: item.id, qty: item.qty })) });
        saveHeldSales(held.slice(0, 10));
        posCart.clear();
        renderCart();
    });
    document.querySelector('#pos-void').addEventListener('click', () => {
        posCart.clear();
        discountInput.value = '0.00';
        paidInput.value = '0.00';
        renderCart();
    });
    document.querySelector('#pos-held-list').addEventListener('click', (event) => {
        const resume = event.target.closest('[data-resume]');
        const drop = event.target.closest('[data-drop-held]');
        const held = heldSales();
        if (resume) {
            const sale = held.splice(Number(resume.dataset.resume), 1)[0];
            posCart.clear();
            (sale.cart || []).forEach((line) => {
                const product = POS_PRODUCTS.find((item) => item.id === Number(line.id));
                if (product) posCart.set(product.id, { ...product, qty: Math.min(Number(line.qty) || 1, product.stock) });
            });
            saveHeldSales(held);
            renderCart();
        }
        if (drop) {
            held.splice(Number(drop.dataset.dropHeld), 1);
            saveHeldSales(held);
        }
    });
    document.querySelector('#pos-checkout-form').addEventListener('submit', (event) => {
        if (!posCart.size) {
            event.preventDefault();
            alert('Add products to the cart first.');
            return;
        }
        const current = totals();
        if (paymentMethodInput.value !== 'credit' && current.paid + 0.001 < current.total) {
            event.preventDefault();
            alert('Amount paid cannot be less than the total.');
        }
    });
    document.addEventListener('keydown', (event) => {
        if (event.target && ['INPUT', 'SELECT', 'TEXTAREA'].includes(event.target.tagName) && event.key !== 'F8' && event.key !== 'F2' && event.key !== 'F4' && event.key !== 'Escape') {
            return;
        }
        if (event.key === '/') {
            event.preventDefault();
            searchInput.focus();
        }
        if (event.key === 'F2') {
            event.preventDefault();
            document.querySelector('#pos-hold').click();
        }
        if (event.key === 'F4') {
            event.preventDefault();
            setPaymentMethod('cash');
        }
        if (event.key === 'F8') {
            event.preventDefault();
            document.querySelector('#pos-checkout').click();
        }
        if (event.key === 'Escape') {
            searchInput.value = '';
            renderProducts();
            searchInput.focus();
        }
    });

    renderProducts();
    renderFastMovers();
    renderCart();
    renderCustomerCard();
    renderHeldSales();
</script>

<?php onyx_page_end(); ?>
