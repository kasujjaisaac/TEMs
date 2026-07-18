<?php

$action = $_GET['action'] ?? $_POST['action'] ?? null;
$tenant_id = onyx_tenant_id();
$pdo = onyx_db();

function redirect_back(string $msg = '', bool $success = true): void
{
    $query = $msg !== '' ? ($success ? '?success=' : '?error=') . urlencode($msg) : '';
    header('Location: sales.php' . $query);
    exit();
}

function ensure_column(PDO $pdo, string $table, string $column, string $definition): void
{
    $stmt = $pdo->query("SHOW COLUMNS FROM `{$table}` LIKE '{$column}'");
    if ($stmt && $stmt->rowCount() === 0) {
        $pdo->exec("ALTER TABLE `{$table}` ADD COLUMN `{$column}` {$definition}");
    }
}

function ensure_sales_tables(PDO $pdo): void
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
        terms VARCHAR(80) DEFAULT 'Net 30',
        salesperson VARCHAR(155) DEFAULT NULL,
        branch_name VARCHAR(155) DEFAULT NULL,
        customer_reference VARCHAR(155) DEFAULT NULL,
        discount DECIMAL(15,2) NOT NULL DEFAULT 0.00,
        delivery_charge DECIMAL(15,2) NOT NULL DEFAULT 0.00,
        source_invoice_id BIGINT(20) DEFAULT NULL,
        stock_posted TINYINT(1) NOT NULL DEFAULT 0,
        accounting_posted TINYINT(1) NOT NULL DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY uq_tenant_invoice_number (tenant_id, invoice_number),
        KEY idx_invoice_tenant_date (tenant_id, invoice_date)
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
        method VARCHAR(100) DEFAULT 'cash',
        reference VARCHAR(255) DEFAULT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY idx_invoice_payment_invoice (invoice_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    ensure_column($pdo, 'invoices', 'notes', 'TEXT DEFAULT NULL');
    ensure_column($pdo, 'invoices', 'subtotal', 'DECIMAL(15,2) NOT NULL DEFAULT 0.00');
    ensure_column($pdo, 'invoices', 'tax', 'DECIMAL(15,2) NOT NULL DEFAULT 0.00');
    ensure_column($pdo, 'invoices', 'total', 'DECIMAL(15,2) NOT NULL DEFAULT 0.00');
    $pdo->exec("ALTER TABLE invoices MODIFY status ENUM('draft','approved','sent','partial','paid','overdue','cancelled') NOT NULL DEFAULT 'draft'");
    ensure_column($pdo, 'invoices', 'terms', "VARCHAR(80) DEFAULT 'Net 30'");
    ensure_column($pdo, 'invoices', 'salesperson', 'VARCHAR(155) DEFAULT NULL');
    ensure_column($pdo, 'invoices', 'branch_name', 'VARCHAR(155) DEFAULT NULL');
    ensure_column($pdo, 'invoices', 'customer_reference', 'VARCHAR(155) DEFAULT NULL');
    ensure_column($pdo, 'invoices', 'discount', 'DECIMAL(15,2) NOT NULL DEFAULT 0.00');
    ensure_column($pdo, 'invoices', 'delivery_charge', 'DECIMAL(15,2) NOT NULL DEFAULT 0.00');
    ensure_column($pdo, 'invoices', 'source_invoice_id', 'BIGINT(20) DEFAULT NULL');
    ensure_column($pdo, 'invoices', 'commercial_opportunity_id', 'BIGINT(20) DEFAULT NULL');
    ensure_column($pdo, 'invoices', 'commercial_handoff_id', 'BIGINT(20) DEFAULT NULL');
    ensure_column($pdo, 'invoices', 'stock_posted', 'TINYINT(1) NOT NULL DEFAULT 0');
    ensure_column($pdo, 'invoices', 'accounting_posted', 'TINYINT(1) NOT NULL DEFAULT 0');
    ensure_column($pdo, 'invoice_lines', 'tenant_id', 'BIGINT(20) NOT NULL DEFAULT 0');
    ensure_column($pdo, 'invoice_lines', 'description', 'TEXT DEFAULT NULL');
    ensure_column($pdo, 'invoice_lines', 'tax_rate', 'DECIMAL(5,2) NOT NULL DEFAULT 0.00');
    ensure_column($pdo, 'invoice_lines', 'line_total', 'DECIMAL(15,2) NOT NULL DEFAULT 0.00');
}

ensure_sales_tables($pdo);

function invoice_type_label(string $type): string
{
    return match ($type) {
        'return' => 'Sales Return',
        'quotation' => 'Quotation',
        'delivery_note' => 'Delivery Note',
        'credit_note' => 'Credit Note',
        default => 'Invoice',
    };
}

function invoice_number_prefix(string $type): string
{
    return match ($type) {
        'return' => 'SR',
        'quotation' => 'QT',
        'delivery_note' => 'DN',
        'credit_note' => 'CN',
        default => 'INV',
    };
}

$allowedTypes = ['invoice', 'return', 'quotation', 'delivery_note', 'credit_note'];

function sales_action_start(string $title, string $subtitle = ''): void
{
    onyx_page_start($title, $subtitle);
    ?>
    <style>
        .sales-form-page,.sales-form-page *,.sales-select-page,.sales-select-page *,.sales-preview-page,.sales-preview-page *{border-radius:0!important}
        .sales-form-page,.sales-select-page,.sales-preview-page{display:grid;gap:16px;max-width:1240px}
        .sales-panel{background:var(--onyx-surface);border:1px solid var(--onyx-border);padding:18px;overflow:hidden}
        .sales-title{align-items:center;color:#fff;display:flex;font-size:10px;font-weight:800;gap:9px;margin-bottom:14px;text-transform:uppercase}
        .sales-grid{display:grid;gap:12px;grid-template-columns:repeat(12,minmax(0,1fr))}
        .sales-field{display:grid;gap:6px;grid-column:span 3;min-width:0}.sales-field.wide{grid-column:span 6}.sales-field.full{grid-column:span 12}
        .sales-field label{color:var(--onyx-muted);font-size:10px;font-weight:800;letter-spacing:.7px;text-transform:uppercase}
        .sales-field input,.sales-field select,.sales-field textarea{background:rgba(0,0,0,.18);border:1px solid rgba(255,255,255,.1);color:#fff;font-family:Poppins,system-ui,sans-serif;font-size:10px;min-height:38px;outline:0;padding:8px 10px;width:100%}
        .sales-field textarea{min-height:82px;resize:vertical}
        .sales-actions,.sales-row-actions{align-items:center;display:flex;flex-wrap:wrap;gap:8px}.sales-actions{justify-content:flex-end}
        .sales-btn{align-items:center;background:rgba(255,255,255,.035);border:1px solid rgba(255,255,255,.1);color:#fff;cursor:pointer;display:inline-flex;font:inherit;font-size:10px;font-weight:800;gap:8px;min-height:38px;padding:0 12px;text-decoration:none;text-transform:uppercase}
        .sales-btn.primary{background:#fff;color:#050506}.sales-btn.danger{border-color:rgba(255,138,138,.35);color:#ff8a8a}
        .sales-form-page .action-btn,.sales-select-page .action-btn,.sales-preview-page .action-btn{align-items:center;background:#fff;border:1px solid #fff;color:#050506;cursor:pointer;display:inline-flex;font:inherit;font-size:10px;font-weight:800;gap:8px;min-height:38px;padding:0 12px;text-decoration:none;text-transform:uppercase}
        .sales-form-page .panel,.sales-select-page .panel,.sales-preview-page .panel{background:var(--onyx-surface);border:1px solid var(--onyx-border);padding:18px}
        .sales-table-wrap{overflow-x:auto;padding-bottom:12px}.sales-table{border-collapse:collapse;min-width:920px;width:100%}
        .sales-table th,.sales-table td{border-bottom:1px solid rgba(255,255,255,.06);font-size:10px;padding:10px;text-align:left;vertical-align:middle}
        .sales-table th{color:var(--onyx-muted);font-weight:800;text-transform:uppercase}
        .sales-badge{border:1px solid rgba(255,255,255,.12);color:#d8d8de;display:inline-flex;font-size:9px;font-weight:800;padding:5px 8px;text-transform:uppercase;white-space:nowrap}.sales-badge.ok{color:#8ff0c3}.sales-badge.warn{color:#ffd27a}.sales-badge.danger{color:#ff8a8a}
        .sales-total-grid{display:grid;gap:10px;grid-template-columns:repeat(4,minmax(160px,1fr))}.sales-total-grid .sales-field{grid-column:auto}
        @media(max-width:900px){.sales-field,.sales-field.wide{grid-column:span 6}.sales-total-grid{grid-template-columns:repeat(2,1fr)}}
        @media(max-width:640px){.sales-field,.sales-field.wide{grid-column:span 12}.sales-total-grid{grid-template-columns:1fr}.sales-actions{justify-content:stretch}.sales-btn{justify-content:center;width:100%}}
    </style>
    <?php
}

function sales_status_badge(string $status): string
{
    $class = match ($status) {
        'paid', 'approved' => 'ok',
        'partial', 'sent', 'draft' => 'warn',
        'overdue', 'cancelled' => 'danger',
        default => '',
    };

    return '<span class="sales-badge ' . htmlspecialchars($class) . '">' . htmlspecialchars(ucwords(str_replace('_', ' ', $status))) . '</span>';
}

function sales_collect_lines(array $post): array
{
    $product_ids = $post['product_id'] ?? [];
    $descriptions = $post['description'] ?? [];
    $unit_prices = $post['unit_price'] ?? [];
    $quantities = $post['quantity'] ?? [];
    $tax_rates = $post['tax_rate'] ?? [];
    $lineCount = max(count($product_ids), count($descriptions), count($unit_prices), count($quantities), count($tax_rates));
    $totals = ['subtotal' => 0.0, 'tax' => 0.0, 'total' => 0.0, 'lines' => []];

    for ($i = 0; $i < $lineCount; $i++) {
        $unit_price = isset($unit_prices[$i]) ? (float) $unit_prices[$i] : 0.0;
        $quantity = isset($quantities[$i]) ? (int) $quantities[$i] : 0;
        if ($quantity <= 0 || $unit_price <= 0) {
            continue;
        }

        $tax_rate = isset($tax_rates[$i]) ? (float) $tax_rates[$i] : 0.0;
        $lineSubtotal = $unit_price * $quantity;
        $lineTax = $lineSubtotal * ($tax_rate / 100);
        $lineTotal = $lineSubtotal + $lineTax;
        $totals['subtotal'] += $lineSubtotal;
        $totals['tax'] += $lineTax;
        $totals['total'] += $lineTotal;
        $totals['lines'][] = [
            'product_id' => isset($product_ids[$i]) && (int) $product_ids[$i] > 0 ? (int) $product_ids[$i] : null,
            'description' => trim($descriptions[$i] ?? ''),
            'unit_price' => $unit_price,
            'quantity' => $quantity,
            'tax_rate' => $tax_rate,
            'line_total' => $lineTotal,
        ];
    }

    $discount = max(0, (float) ($post['discount'] ?? 0));
    $delivery = max(0, (float) ($post['delivery_charge'] ?? 0));
    $totals['total'] = max(0, $totals['total'] - $discount + $delivery);
    $totals['discount'] = $discount;
    $totals['delivery_charge'] = $delivery;

    return $totals;
}

function sales_paid_amount(int $invoiceId, int $tenantId): float
{
    return (float) onyx_scalar(
        'SELECT COALESCE(SUM(amount), 0) FROM invoice_payments WHERE invoice_id = :invoice_id AND tenant_id = :tenant_id',
        ['invoice_id' => $invoiceId, 'tenant_id' => $tenantId]
    );
}

function sales_refresh_payment_status(PDO $pdo, int $invoiceId, int $tenantId): void
{
    $invoice = onyx_row('SELECT total, status FROM invoices WHERE id = :id AND tenant_id = :tenant_id', ['id' => $invoiceId, 'tenant_id' => $tenantId]);
    if (! $invoice || in_array($invoice['status'], ['cancelled'], true)) {
        return;
    }
    $paid = sales_paid_amount($invoiceId, $tenantId);
    $status = $paid <= 0 ? 'sent' : ($paid + 0.001 >= (float) $invoice['total'] ? 'paid' : 'partial');
    $pdo->prepare('UPDATE invoices SET status = ?, updated_at = NOW() WHERE id = ? AND tenant_id = ?')->execute([$status, $invoiceId, $tenantId]);
}

function sales_stock_multiplier(string $invoiceType): int
{
    return in_array($invoiceType, ['return', 'credit_note'], true) ? 1 : -1;
}

function sales_post_stock(PDO $pdo, int $invoiceId, int $tenantId, bool $force = false): void
{
    $invoice = onyx_row('SELECT id, invoice_number, invoice_type, stock_posted FROM invoices WHERE id = :id AND tenant_id = :tenant_id', ['id' => $invoiceId, 'tenant_id' => $tenantId]);
    if (! $invoice || ($invoice['stock_posted'] && ! $force) || $invoice['invoice_type'] === 'quotation') {
        return;
    }
    $lines = onyx_rows('SELECT product_id, quantity FROM invoice_lines WHERE invoice_id = :invoice_id AND tenant_id = :tenant_id', ['invoice_id' => $invoiceId, 'tenant_id' => $tenantId]);
    $multiplier = sales_stock_multiplier($invoice['invoice_type']);
    foreach ($lines as $line) {
        if (! $line['product_id']) {
            continue;
        }
        $qty = (int) $line['quantity'] * $multiplier;
        $pdo->prepare('UPDATE products SET current_stock = current_stock + ?, updated_at = NOW() WHERE id = ? AND tenant_id = ?')->execute([$qty, $line['product_id'], $tenantId]);
        try {
            $pdo->prepare('INSERT INTO inventory_transactions (tenant_id, product_id, transaction_type, quantity, reference, notes, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())')
                ->execute([$tenantId, $line['product_id'], $qty < 0 ? 'sold' : 'returned', abs($qty), $invoice['invoice_number'], 'Posted from Sales module']);
        } catch (Throwable) {
            // Inventory transaction logging is optional if the inventory module has not initialized its table yet.
        }
    }
    $pdo->prepare('UPDATE invoices SET stock_posted = 1 WHERE id = ? AND tenant_id = ?')->execute([$invoiceId, $tenantId]);
}

function sales_reverse_stock(PDO $pdo, int $invoiceId, int $tenantId): void
{
    $invoice = onyx_row('SELECT id, invoice_type, stock_posted FROM invoices WHERE id = :id AND tenant_id = :tenant_id', ['id' => $invoiceId, 'tenant_id' => $tenantId]);
    if (! $invoice || ! $invoice['stock_posted'] || $invoice['invoice_type'] === 'quotation') {
        return;
    }
    $lines = onyx_rows('SELECT product_id, quantity FROM invoice_lines WHERE invoice_id = :invoice_id AND tenant_id = :tenant_id', ['invoice_id' => $invoiceId, 'tenant_id' => $tenantId]);
    $multiplier = sales_stock_multiplier($invoice['invoice_type']) * -1;
    foreach ($lines as $line) {
        if ($line['product_id']) {
            $pdo->prepare('UPDATE products SET current_stock = current_stock + ?, updated_at = NOW() WHERE id = ? AND tenant_id = ?')->execute([(int) $line['quantity'] * $multiplier, $line['product_id'], $tenantId]);
        }
    }
    $pdo->prepare('UPDATE invoices SET stock_posted = 0 WHERE id = ? AND tenant_id = ?')->execute([$invoiceId, $tenantId]);
}

function sales_account_id(PDO $pdo, int $tenantId, string $code, string $name, string $type): int
{
    $id = (int) onyx_scalar('SELECT id FROM accounts WHERE tenant_id = :tenant_id AND code = :code LIMIT 1', ['tenant_id' => $tenantId, 'code' => $code], 0);
    if ($id > 0) {
        return $id;
    }
    $pdo->prepare('INSERT INTO accounts (tenant_id, code, name, type, created_at) VALUES (?, ?, ?, ?, NOW())')->execute([$tenantId, $code, $name, $type]);
    return (int) $pdo->lastInsertId();
}

function sales_post_invoice_accounting(PDO $pdo, int $invoiceId, int $tenantId): void
{
    $invoice = onyx_row('SELECT * FROM invoices WHERE id = :id AND tenant_id = :tenant_id', ['id' => $invoiceId, 'tenant_id' => $tenantId]);
    if (! $invoice || $invoice['accounting_posted'] || $invoice['invoice_type'] === 'quotation') {
        return;
    }
    try {
        $ar = sales_account_id($pdo, $tenantId, '1100', 'Accounts Receivable', 'asset');
        $sales = sales_account_id($pdo, $tenantId, '4000', 'Sales Revenue', 'revenue');
        $vat = sales_account_id($pdo, $tenantId, '2100', 'VAT Payable', 'liability');
        $userId = (int) session('user_id', 1);
        $pdo->prepare('INSERT INTO journal_entries (tenant_id, user_id, entry_date, reference_number, narration, source_module, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())')
            ->execute([$tenantId, $userId, $invoice['invoice_date'], $invoice['invoice_number'], 'Sales document posted', 'sales']);
        $entryId = (int) $pdo->lastInsertId();
        $pdo->prepare('INSERT INTO journal_entry_lines (journal_entry_id, account_id, debit, credit, created_at) VALUES (?, ?, ?, ?, NOW())')->execute([$entryId, $ar, $invoice['total'], 0]);
        $pdo->prepare('INSERT INTO journal_entry_lines (journal_entry_id, account_id, debit, credit, created_at) VALUES (?, ?, ?, ?, NOW())')->execute([$entryId, $sales, 0, max(0, (float) $invoice['subtotal'] - (float) $invoice['discount'] + (float) $invoice['delivery_charge'])]);
        if ((float) $invoice['tax'] > 0) {
            $pdo->prepare('INSERT INTO journal_entry_lines (journal_entry_id, account_id, debit, credit, created_at) VALUES (?, ?, ?, ?, NOW())')->execute([$entryId, $vat, 0, $invoice['tax']]);
        }
        $pdo->prepare('UPDATE invoices SET accounting_posted = 1 WHERE id = ? AND tenant_id = ?')->execute([$invoiceId, $tenantId]);
    } catch (Throwable) {
        // Accounting tables may not be initialized in every local database; sales remains usable.
    }
}

function sales_post_payment_accounting(PDO $pdo, int $paymentId, int $tenantId): void
{
    try {
        $payment = onyx_row('SELECT p.*, i.invoice_number FROM invoice_payments p JOIN invoices i ON i.id = p.invoice_id AND i.tenant_id = p.tenant_id WHERE p.id = :id AND p.tenant_id = :tenant_id', ['id' => $paymentId, 'tenant_id' => $tenantId]);
        if (! $payment) {
            return;
        }
        $cash = sales_account_id($pdo, $tenantId, '1000', 'Cash and Bank', 'asset');
        $ar = sales_account_id($pdo, $tenantId, '1100', 'Accounts Receivable', 'asset');
        $userId = (int) session('user_id', 1);
        $pdo->prepare('INSERT INTO journal_entries (tenant_id, user_id, entry_date, reference_number, narration, source_module, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())')
            ->execute([$tenantId, $userId, $payment['payment_date'], $payment['invoice_number'], 'Sales payment received', 'sales']);
        $entryId = (int) $pdo->lastInsertId();
        $pdo->prepare('INSERT INTO journal_entry_lines (journal_entry_id, account_id, debit, credit, created_at) VALUES (?, ?, ?, ?, NOW())')->execute([$entryId, $cash, $payment['amount'], 0]);
        $pdo->prepare('INSERT INTO journal_entry_lines (journal_entry_id, account_id, debit, credit, created_at) VALUES (?, ?, ?, ?, NOW())')->execute([$entryId, $ar, 0, $payment['amount']]);
    } catch (Throwable) {
        // Optional accounting hook.
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if ($action === 'create_invoice') {
        $invoice_type = $_GET['invoice_type'] ?? 'invoice';
        if (!in_array($invoice_type, $allowedTypes, true)) {
            $invoice_type = 'invoice';
        }

        $customers = onyx_rows('SELECT id, name FROM customers WHERE tenant_id = :tenant_id ORDER BY name ASC', ['tenant_id' => $tenant_id]);
        $products = onyx_rows('SELECT id, name, sku, selling_price, vat_rate FROM products WHERE tenant_id = :tenant_id ORDER BY name ASC', ['tenant_id' => $tenant_id]);

        $title = invoice_type_label($invoice_type);
        sales_action_start($title, 'Create a new ' . $title . '.');
        ?>
        <form class="sales-form-page" method="POST" action="sales_action.php">
            <input type="hidden" name="action" value="create_invoice">
            <input type="hidden" name="invoice_type" value="<?= htmlspecialchars($invoice_type) ?>">
            <section class="sales-panel">
                <div class="sales-title"><i class="fa-solid fa-file-circle-plus"></i> <?= htmlspecialchars($title) ?> Details</div>
                <div class="sales-grid">
                    <div class="sales-field wide"><label>Customer</label>
                    <select name="customer_id" required>
                        <option value="">-- Select customer --</option>
                        <?php foreach ($customers as $customer): ?>
                            <option value="<?= htmlspecialchars($customer['id']) ?>"><?= htmlspecialchars($customer['name']) ?></option>
                        <?php endforeach; ?>
                    </select></div>
                    <div class="sales-field"><label>Document Date</label><input type="date" name="invoice_date" value="<?= date('Y-m-d') ?>" required></div>
                    <div class="sales-field"><label>Due Date</label><input type="date" name="due_date"></div>
                    <div class="sales-field"><label>Terms</label><select name="terms"><option>Cash</option><option selected>Net 30</option><option>Net 15</option><option>Net 7</option></select></div>
                    <div class="sales-field"><label>Salesperson</label><input name="salesperson" value="<?= htmlspecialchars(session('user_name', 'Operator')) ?>"></div>
                    <div class="sales-field"><label>Branch</label><input name="branch_name" placeholder="Main branch"></div>
                    <div class="sales-field wide"><label>Customer Ref / PO</label><input name="customer_reference" placeholder="Customer PO, LPO, or reference"></div>
                    <div class="sales-field full"><label>Notes</label><textarea name="notes" placeholder="Optional invoice notes"></textarea></div>
                </div>
            </section>
            <section class="sales-panel">
                <div class="sales-title"><i class="fa-solid fa-list"></i> Line Items</div>
                <div class="sales-table-wrap">
                    <table class="table" id="invoice-lines" style="width:100%;border-collapse:collapse;">
                        <thead>
                            <tr>
                                <th style="padding:8px;border-bottom:1px solid #444;">Product</th>
                                <th style="padding:8px;border-bottom:1px solid #444;">Description</th>
                                <th style="padding:8px;border-bottom:1px solid #444;">Unit Price</th>
                                <th style="padding:8px;border-bottom:1px solid #444;">Qty</th>
                                <th style="padding:8px;border-bottom:1px solid #444;">Tax %</th>
                                <th style="padding:8px;border-bottom:1px solid #444;">Line Total</th>
                                <th style="padding:8px;border-bottom:1px solid #444;"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>
                                    <select name="product_id[]" class="line-product" style="width:100%;padding:8px;">
                                        <option value="">-- choose product --</option>
                                        <?php foreach ($products as $product): ?>
                                            <option value="<?= htmlspecialchars($product['id']) ?>" data-price="<?= htmlspecialchars($product['selling_price']) ?>" data-tax="<?= htmlspecialchars($product['vat_rate']) ?>"><?= htmlspecialchars($product['name']) ?> (<?= htmlspecialchars($product['sku']) ?>)</option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                                <td><input name="description[]" style="width:100%;padding:8px;" placeholder="Description"></td>
                                <td><input type="number" step="0.01" name="unit_price[]" value="0.00" class="line-price" style="width:100%;padding:8px;"></td>
                                <td><input type="number" step="1" name="quantity[]" value="1" class="line-quantity" style="width:100%;padding:8px;"></td>
                                <td><input type="number" step="0.01" name="tax_rate[]" value="0.00" class="line-tax" style="width:100%;padding:8px;"></td>
                                <td><input type="text" name="line_total[]" class="line-total" value="0.00" readonly style="width:100%;padding:8px;background:#1c1c27;border:1px solid #333;color:#fff;"></td>
                                <td><button type="button" class="remove-line" style="padding:8px 12px;">Remove</button></td>
                            </tr>
                        </tbody>
                    </table>
                    <button type="button" id="add-line" class="sales-btn" style="margin-top:12px;">Add Line</button>
                </div>
            </section>
            <section class="sales-panel">
                <div class="sales-title"><i class="fa-solid fa-calculator"></i> Totals</div>
                <div class="sales-total-grid">
                    <div class="sales-field">
                        <label>Subtotal</label>
                        <input type="text" id="invoice-subtotal" readonly value="0.00">
                    </div>
                    <div class="sales-field">
                        <label>Total Tax</label>
                        <input type="text" id="invoice-tax" readonly value="0.00">
                    </div>
                    <div class="sales-field">
                        <label>Discount</label>
                        <input type="number" step="0.01" id="invoice-discount" name="discount" value="0.00">
                    </div>
                    <div class="sales-field">
                        <label>Delivery Charge</label>
                        <input type="number" step="0.01" id="invoice-delivery" name="delivery_charge" value="0.00">
                    </div>
                    <div class="sales-field">
                        <label>Total Amount</label>
                        <input type="text" id="invoice-total" readonly value="0.00">
                    </div>
                </div>
                <div class="sales-actions" style="margin-top:16px;">
                    <a class="sales-btn" href="sales.php"><i class="fa-solid fa-arrow-left"></i> Back</a>
                    <button class="sales-btn primary" type="submit"><i class="fa-solid fa-floppy-disk"></i> Save <?= htmlspecialchars($title) ?></button>
                </div>
            </section>
        </form>
        <script>
            const lineTable = document.querySelector('#invoice-lines tbody');
            const addLineButton = document.querySelector('#add-line');
            const subtotalInput = document.querySelector('#invoice-subtotal');
            const taxInput = document.querySelector('#invoice-tax');
            const totalInput = document.querySelector('#invoice-total');
            const discountInput = document.querySelector('#invoice-discount');
            const deliveryInput = document.querySelector('#invoice-delivery');
            const discountInput = document.querySelector('#invoice-discount');
            const deliveryInput = document.querySelector('#invoice-delivery');

            function recalcRow(row) {
                const price = parseFloat(row.querySelector('.line-price').value) || 0;
                const qty = parseFloat(row.querySelector('.line-quantity').value) || 0;
                const tax = parseFloat(row.querySelector('.line-tax').value) || 0;
                const lineTotal = price * qty * (1 + tax / 100);
                row.querySelector('.line-total').value = lineTotal.toFixed(2);
                recalcInvoice();
            }

            function recalcInvoice() {
                let subtotal = 0;
                let taxTotal = 0;
                lineTable.querySelectorAll('tr').forEach((row) => {
                    const price = parseFloat(row.querySelector('.line-price').value) || 0;
                    const qty = parseFloat(row.querySelector('.line-quantity').value) || 0;
                    const tax = parseFloat(row.querySelector('.line-tax').value) || 0;
                    const lineSubtotal = price * qty;
                    subtotal += lineSubtotal;
                    taxTotal += lineSubtotal * (tax / 100);
                });
                subtotalInput.value = subtotal.toFixed(2);
                taxInput.value = taxTotal.toFixed(2);
                const discount = parseFloat(discountInput?.value || '0') || 0;
                const delivery = parseFloat(deliveryInput?.value || '0') || 0;
                totalInput.value = Math.max(0, subtotal + taxTotal - discount + delivery).toFixed(2);
            }

            function createLine() {
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td><select name="product_id[]" class="line-product" style="width:100%;padding:8px;"><option value="">-- choose product --</option><?php foreach ($products as $product): ?><option value="<?= htmlspecialchars($product['id']) ?>" data-price="<?= htmlspecialchars($product['selling_price']) ?>" data-tax="<?= htmlspecialchars($product['vat_rate']) ?>"><?= htmlspecialchars($product['name']) ?> (<?= htmlspecialchars($product['sku']) ?>)</option><?php endforeach; ?></select></td>
                    <td><input name="description[]" style="width:100%;padding:8px;" placeholder="Description"></td>
                    <td><input type="number" step="0.01" name="unit_price[]" value="0.00" class="line-price" style="width:100%;padding:8px;"></td>
                    <td><input type="number" step="1" name="quantity[]" value="1" class="line-quantity" style="width:100%;padding:8px;"></td>
                    <td><input type="number" step="0.01" name="tax_rate[]" value="0.00" class="line-tax" style="width:100%;padding:8px;"></td>
                    <td><input type="text" name="line_total[]" class="line-total" value="0.00" readonly style="width:100%;padding:8px;background:#1c1c27;border:1px solid #333;color:#fff;"></td>
                    <td><button type="button" class="remove-line" style="padding:8px 12px;">Remove</button></td>
                `;
                bindRowEvents(row);
                lineTable.appendChild(row);
            }

            function bindRowEvents(row) {
                const productSelect = row.querySelector('.line-product');
                const priceInput = row.querySelector('.line-price');
                const qtyInput = row.querySelector('.line-quantity');
                const taxInput = row.querySelector('.line-tax');
                const removeButton = row.querySelector('.remove-line');

                productSelect.addEventListener('change', () => {
                    const selected = productSelect.selectedOptions[0];
                    if (selected && selected.dataset.price) {
                        priceInput.value = parseFloat(selected.dataset.price).toFixed(2);
                    }
                    if (selected && selected.dataset.tax) {
                        taxInput.value = parseFloat(selected.dataset.tax).toFixed(2);
                    }
                    recalcRow(row);
                });

                [priceInput, qtyInput, taxInput].forEach((input) => {
                    input.addEventListener('input', () => recalcRow(row));
                });

                removeButton.addEventListener('click', () => {
                    row.remove();
                    recalcInvoice();
                });
            }

            lineTable.querySelectorAll('tr').forEach(bindRowEvents);
            addLineButton.addEventListener('click', createLine);
            [discountInput, deliveryInput].forEach((input) => {
                if (input) input.addEventListener('input', recalcInvoice);
            });
            recalcInvoice();
        </script>
        <?php
        onyx_page_end();
        exit();
    }

    if ($action === 'select_quotation') {
        $task = $_GET['task'] ?? 'view';
        $invoice_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

        if ($invoice_id > 0) {
            if ($task === 'print') {
                header('Location: sales_action.php?action=view_invoice&id=' . $invoice_id . '&print=1');
                exit();
            }
            if ($task === 'edit') {
                header('Location: sales_action.php?action=edit_invoice&id=' . $invoice_id);
                exit();
            }
            if ($task === 'approve') {
                header('Location: sales_action.php?action=approve_quotation&id=' . $invoice_id);
                exit();
            }
            if ($task === 'convert') {
                header('Location: sales_action.php?action=convert_quotation&id=' . $invoice_id);
                exit();
            }
            if ($task === 'email') {
                header('Location: sales_action.php?action=email_quotation&id=' . $invoice_id);
                exit();
            }
            redirect_back('Unsupported quotation task.', false);
        }

        $quotations = onyx_rows(
            'SELECT i.id, i.invoice_number, c.name AS customer_name, i.invoice_date, i.total, i.status FROM invoices i LEFT JOIN customers c ON c.id = i.customer_id AND c.tenant_id = i.tenant_id WHERE i.tenant_id = :tenant_id AND i.invoice_type = :type ORDER BY i.invoice_date DESC, i.id DESC',
            ['tenant_id' => $tenant_id, 'type' => 'quotation']
        );
        sales_action_start('Select Quotation', 'Choose a quotation to perform the requested action.');
        $rows = array_map(static fn (array $row): array => [
            $row['invoice_number'],
            $row['customer_name'] ?: '-',
            $row['invoice_date'] ?: '-',
            number_format((float) ($row['total'] ?? 0), 2),
            ['raw' => true, 'value' => sales_status_badge($row['status'] ?: 'draft')],
            ['raw' => true, 'value' => '<a class="sales-btn primary" href="sales_action.php?action=select_quotation&task=' . urlencode($task) . '&id=' . htmlspecialchars($row['id']) . '">Select</a>'],
        ], $quotations);
        echo '<div class="sales-select-page"><section class="sales-panel"><div class="sales-title"><i class="fa-solid fa-file-signature"></i> Available Quotations</div>';
        onyx_table_html(['Quotation No', 'Customer', 'Date', 'Total', 'Status', 'Select'], $rows);
        echo '</section></div>';
        onyx_page_end();
        exit();
    }

    if ($action === 'select_invoice') {
        $task = $_GET['task'] ?? 'payment';
        $invoice_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

        if ($invoice_id > 0) {
            if ($task === 'payment') {
                header('Location: sales_action.php?action=capture_payment&id=' . $invoice_id);
                exit();
            }
            redirect_back('Unsupported invoice task.', false);
        }

        $invoices = onyx_rows(
            'SELECT i.id, i.invoice_number, c.name AS customer_name, i.invoice_date, i.total, i.status FROM invoices i LEFT JOIN customers c ON c.id = i.customer_id AND c.tenant_id = i.tenant_id WHERE i.tenant_id = :tenant_id AND i.invoice_type = :type ORDER BY i.invoice_date DESC, i.id DESC',
            ['tenant_id' => $tenant_id, 'type' => 'invoice']
        );
        sales_action_start('Select Invoice', 'Choose an invoice to capture payment for.');
        $rows = array_map(static fn (array $row): array => [
            $row['invoice_number'],
            $row['customer_name'] ?: '-',
            $row['invoice_date'] ?: '-',
            number_format((float) ($row['total'] ?? 0), 2),
            ['raw' => true, 'value' => sales_status_badge($row['status'] ?: 'draft')],
            ['raw' => true, 'value' => '<a class="sales-btn primary" href="sales_action.php?action=select_invoice&task=' . urlencode($task) . '&id=' . htmlspecialchars($row['id']) . '">Pay</a>'],
        ], $invoices);
        echo '<div class="sales-select-page"><section class="sales-panel"><div class="sales-title"><i class="fa-solid fa-file-invoice"></i> Available Invoices</div>';
        onyx_table_html(['Invoice No', 'Customer', 'Date', 'Total', 'Status', 'Pay'], $rows);
        echo '</section></div>';
        onyx_page_end();
        exit();
    }

    if ($action === 'edit_invoice') {
        $invoice_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        if ($invoice_id <= 0) {
            redirect_back('Invoice not specified for edit.', false);
        }

        $invoice = onyx_row('SELECT * FROM invoices WHERE id = :id AND tenant_id = :tenant_id', ['id' => $invoice_id, 'tenant_id' => $tenant_id]);
        if (!$invoice) {
            redirect_back('Invoice not found.', false);
        }

        $lines = onyx_rows('SELECT l.* FROM invoice_lines l JOIN invoices i ON i.id = l.invoice_id AND i.tenant_id = :tenant_id WHERE l.invoice_id = :invoice_id', ['invoice_id' => $invoice_id, 'tenant_id' => $tenant_id]);
        $customers = onyx_rows('SELECT id, name FROM customers WHERE tenant_id = :tenant_id ORDER BY name ASC', ['tenant_id' => $tenant_id]);
        $products = onyx_rows('SELECT id, name, sku, selling_price, vat_rate FROM products WHERE tenant_id = :tenant_id ORDER BY name ASC', ['tenant_id' => $tenant_id]);
        $title = invoice_type_label($invoice['invoice_type']);
        sales_action_start('Edit ' . $title, 'Edit the selected quotation or invoice.');
        ?>
        <form class="sales-form-page" method="POST" action="sales_action.php">
            <input type="hidden" name="action" value="edit_invoice">
            <input type="hidden" name="invoice_id" value="<?= htmlspecialchars($invoice_id) ?>">
            <input type="hidden" name="invoice_type" value="<?= htmlspecialchars($invoice['invoice_type']) ?>">
            <section class="sales-panel">
                <div class="sales-title"><i class="fa-solid fa-pen-to-square"></i> Edit <?= htmlspecialchars($title) ?> Details</div>
                <div class="sales-grid">
                    <div class="sales-field wide"><label>Customer</label>
                    <select name="customer_id" required>
                        <?php foreach ($customers as $customer): ?>
                            <option value="<?= htmlspecialchars($customer['id']) ?>" <?= $customer['id'] == $invoice['customer_id'] ? 'selected' : '' ?>><?= htmlspecialchars($customer['name']) ?></option>
                        <?php endforeach; ?>
                    </select></div>
                    <div class="sales-field"><label>Document Date</label><input type="date" name="invoice_date" value="<?= htmlspecialchars($invoice['invoice_date']) ?>" required></div>
                    <div class="sales-field"><label>Due Date</label><input type="date" name="due_date" value="<?= htmlspecialchars($invoice['due_date'] ?? '') ?>"></div>
                    <div class="sales-field"><label>Terms</label><input name="terms" value="<?= htmlspecialchars($invoice['terms'] ?? 'Net 30') ?>"></div>
                    <div class="sales-field"><label>Salesperson</label><input name="salesperson" value="<?= htmlspecialchars($invoice['salesperson'] ?? '') ?>"></div>
                    <div class="sales-field"><label>Branch</label><input name="branch_name" value="<?= htmlspecialchars($invoice['branch_name'] ?? '') ?>"></div>
                    <div class="sales-field wide"><label>Customer Ref / PO</label><input name="customer_reference" value="<?= htmlspecialchars($invoice['customer_reference'] ?? '') ?>"></div>
                    <div class="sales-field full"><label>Notes</label><textarea name="notes" placeholder="Optional invoice notes"><?= htmlspecialchars($invoice['notes'] ?? '') ?></textarea></div>
                </div>
            </section>
            <section class="sales-panel">
                <div class="sales-title"><i class="fa-solid fa-list"></i> Line Items</div>
                <div class="sales-table-wrap">
                    <table class="table" id="invoice-lines" style="width:100%;border-collapse:collapse;">
                        <thead>
                            <tr>
                                <th style="padding:8px;border-bottom:1px solid #444;">Product</th>
                                <th style="padding:8px;border-bottom:1px solid #444;">Description</th>
                                <th style="padding:8px;border-bottom:1px solid #444;">Unit Price</th>
                                <th style="padding:8px;border-bottom:1px solid #444;">Qty</th>
                                <th style="padding:8px;border-bottom:1px solid #444;">Tax %</th>
                                <th style="padding:8px;border-bottom:1px solid #444;">Line Total</th>
                                <th style="padding:8px;border-bottom:1px solid #444;"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($lines as $line): ?>
                                <tr>
                                    <td>
                                        <select name="product_id[]" class="line-product" style="width:100%;padding:8px;">
                                            <option value="">-- choose product --</option>
                                            <?php foreach ($products as $product): ?>
                                                <option value="<?= htmlspecialchars($product['id']) ?>" data-price="<?= htmlspecialchars($product['selling_price']) ?>" data-tax="<?= htmlspecialchars($product['vat_rate']) ?>" <?= $product['id'] == $line['product_id'] ? 'selected' : '' ?>><?= htmlspecialchars($product['name']) ?> (<?= htmlspecialchars($product['sku']) ?>)</option>
                                            <?php endforeach; ?>
                                        </select>
                                    </td>
                                    <td><input name="description[]" value="<?= htmlspecialchars($line['description']) ?>" style="width:100%;padding:8px;" placeholder="Description"></td>
                                    <td><input type="number" step="0.01" name="unit_price[]" value="<?= htmlspecialchars($line['unit_price']) ?>" class="line-price" style="width:100%;padding:8px;"></td>
                                    <td><input type="number" step="1" name="quantity[]" value="<?= htmlspecialchars($line['quantity']) ?>" class="line-quantity" style="width:100%;padding:8px;"></td>
                                    <td><input type="number" step="0.01" name="tax_rate[]" value="<?= htmlspecialchars($line['tax_rate']) ?>" class="line-tax" style="width:100%;padding:8px;"></td>
                                    <td><input type="text" name="line_total[]" class="line-total" value="<?= htmlspecialchars($line['line_total']) ?>" readonly style="width:100%;padding:8px;background:#1c1c27;border:1px solid #333;color:#fff;"></td>
                                    <td><button type="button" class="remove-line" style="padding:8px 12px;">Remove</button></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <button type="button" id="add-line" class="sales-btn" style="margin-top:12px;">Add Line</button>
                </div>
            </section>
            <section class="sales-panel">
                <div class="sales-title"><i class="fa-solid fa-calculator"></i> Totals</div>
                <div class="sales-total-grid">
                    <div class="sales-field">
                        <label>Subtotal</label>
                        <input type="text" id="invoice-subtotal" readonly value="<?= htmlspecialchars(number_format((float)$invoice['subtotal'], 2)) ?>">
                    </div>
                    <div class="sales-field">
                        <label>Total Tax</label>
                        <input type="text" id="invoice-tax" readonly value="<?= htmlspecialchars(number_format((float)$invoice['tax'], 2)) ?>">
                    </div>
                    <div class="sales-field">
                        <label>Discount</label>
                        <input type="number" step="0.01" id="invoice-discount" name="discount" value="<?= htmlspecialchars(number_format((float)($invoice['discount'] ?? 0), 2, '.', '')) ?>">
                    </div>
                    <div class="sales-field">
                        <label>Delivery Charge</label>
                        <input type="number" step="0.01" id="invoice-delivery" name="delivery_charge" value="<?= htmlspecialchars(number_format((float)($invoice['delivery_charge'] ?? 0), 2, '.', '')) ?>">
                    </div>
                    <div class="sales-field">
                        <label>Total Amount</label>
                        <input type="text" id="invoice-total" readonly value="<?= htmlspecialchars(number_format((float)$invoice['total'], 2)) ?>">
                    </div>
                </div>
                <div class="sales-actions" style="margin-top:16px;">
                    <a class="sales-btn" href="sales.php"><i class="fa-solid fa-arrow-left"></i> Back</a>
                    <button class="sales-btn primary" type="submit"><i class="fa-solid fa-floppy-disk"></i> Save <?= htmlspecialchars($title) ?></button>
                </div>
            </section>
        </form>
        <script>
            const lineTable = document.querySelector('#invoice-lines tbody');
            const addLineButton = document.querySelector('#add-line');
            const subtotalInput = document.querySelector('#invoice-subtotal');
            const taxInput = document.querySelector('#invoice-tax');
            const totalInput = document.querySelector('#invoice-total');

            function recalcRow(row) {
                const price = parseFloat(row.querySelector('.line-price').value) || 0;
                const qty = parseFloat(row.querySelector('.line-quantity').value) || 0;
                const tax = parseFloat(row.querySelector('.line-tax').value) || 0;
                const lineTotal = price * qty * (1 + tax / 100);
                row.querySelector('.line-total').value = lineTotal.toFixed(2);
                recalcInvoice();
            }

            function recalcInvoice() {
                let subtotal = 0;
                let taxTotal = 0;
                lineTable.querySelectorAll('tr').forEach((row) => {
                    const price = parseFloat(row.querySelector('.line-price').value) || 0;
                    const qty = parseFloat(row.querySelector('.line-quantity').value) || 0;
                    const tax = parseFloat(row.querySelector('.line-tax').value) || 0;
                    const lineSubtotal = price * qty;
                    subtotal += lineSubtotal;
                    taxTotal += lineSubtotal * (tax / 100);
                });
                subtotalInput.value = subtotal.toFixed(2);
                taxInput.value = taxTotal.toFixed(2);
                const discount = parseFloat(discountInput?.value || '0') || 0;
                const delivery = parseFloat(deliveryInput?.value || '0') || 0;
                totalInput.value = Math.max(0, subtotal + taxTotal - discount + delivery).toFixed(2);
            }

            function createLine() {
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td><select name="product_id[]" class="line-product" style="width:100%;padding:8px;"><option value="">-- choose product --</option><?php foreach ($products as $product): ?><option value="<?= htmlspecialchars($product['id']) ?>" data-price="<?= htmlspecialchars($product['selling_price']) ?>" data-tax="<?= htmlspecialchars($product['vat_rate']) ?>"><?= htmlspecialchars($product['name']) ?> (<?= htmlspecialchars($product['sku']) ?>)</option><?php endforeach; ?></select></td>
                    <td><input name="description[]" style="width:100%;padding:8px;" placeholder="Description"></td>
                    <td><input type="number" step="0.01" name="unit_price[]" value="0.00" class="line-price" style="width:100%;padding:8px;"></td>
                    <td><input type="number" step="1" name="quantity[]" value="1" class="line-quantity" style="width:100%;padding:8px;"></td>
                    <td><input type="number" step="0.01" name="tax_rate[]" value="0.00" class="line-tax" style="width:100%;padding:8px;"></td>
                    <td><input type="text" name="line_total[]" class="line-total" value="0.00" readonly style="width:100%;padding:8px;background:#1c1c27;border:1px solid #333;color:#fff;"></td>
                    <td><button type="button" class="remove-line" style="padding:8px 12px;">Remove</button></td>
                `;
                bindRowEvents(row);
                lineTable.appendChild(row);
            }

            function bindRowEvents(row) {
                const productSelect = row.querySelector('.line-product');
                const priceInput = row.querySelector('.line-price');
                const qtyInput = row.querySelector('.line-quantity');
                const taxInput = row.querySelector('.line-tax');
                const removeButton = row.querySelector('.remove-line');

                productSelect.addEventListener('change', () => {
                    const selected = productSelect.selectedOptions[0];
                    if (selected && selected.dataset.price) {
                        priceInput.value = parseFloat(selected.dataset.price).toFixed(2);
                    }
                    if (selected && selected.dataset.tax) {
                        taxInput.value = parseFloat(selected.dataset.tax).toFixed(2);
                    }
                    recalcRow(row);
                });

                [priceInput, qtyInput, taxInput].forEach((input) => {
                    input.addEventListener('input', () => recalcRow(row));
                });

                removeButton.addEventListener('click', () => {
                    row.remove();
                    recalcInvoice();
                });
            }

            lineTable.querySelectorAll('tr').forEach(bindRowEvents);
            addLineButton.addEventListener('click', createLine);
            [discountInput, deliveryInput].forEach((input) => {
                if (input) input.addEventListener('input', recalcInvoice);
            });
            recalcInvoice();
        </script>
        <?php
        onyx_page_end();
        exit();
    }

    if ($action === 'approve_quotation') {
        $invoice_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        if ($invoice_id <= 0) {
            redirect_back('Quotation not specified for approval.', false);
        }
        $stmt = $pdo->prepare('UPDATE invoices SET status = ?, updated_at = NOW() WHERE id = ? AND tenant_id = ? AND invoice_type = ?');
        $stmt->execute(['approved', $invoice_id, $tenant_id, 'quotation']);
        redirect_back('Quotation approved.');
    }

    if ($action === 'convert_quotation') {
        $invoice_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        if ($invoice_id <= 0) {
            redirect_back('Quotation not specified for conversion.', false);
        }
        $newNumber = invoice_number_prefix('invoice') . '-' . strtoupper(uniqid());
        $stmt = $pdo->prepare('UPDATE invoices SET invoice_type = ?, invoice_number = ?, status = ?, updated_at = NOW() WHERE id = ? AND tenant_id = ? AND invoice_type = ?');
        $stmt->execute(['invoice', $newNumber, 'draft', $invoice_id, $tenant_id, 'quotation']);
        try {
            $invoice = onyx_row('SELECT commercial_opportunity_id, commercial_handoff_id FROM invoices WHERE id = :id AND tenant_id = :tenant_id', ['id' => $invoice_id, 'tenant_id' => $tenant_id]);
            if ($invoice && (int) ($invoice['commercial_opportunity_id'] ?? 0) > 0) {
                $pdo->prepare('UPDATE commercial_opportunities SET legacy_invoice_id = ?, sales_handoff_status = ?, updated_at = NOW() WHERE id = ? AND tenant_id = ?')
                    ->execute([$invoice_id, 'Invoice Drafted', (int) $invoice['commercial_opportunity_id'], $tenant_id]);
            }
            if ($invoice && (int) ($invoice['commercial_handoff_id'] ?? 0) > 0) {
                $pdo->prepare('UPDATE commercial_sales_handoffs SET invoice_id = ?, status = ?, updated_at = NOW() WHERE id = ? AND tenant_id = ?')
                    ->execute([$invoice_id, 'Invoice Drafted', (int) $invoice['commercial_handoff_id'], $tenant_id]);
            }
        } catch (Throwable) {
            // Commercial bridge updates are skipped if the Commercial module has not been migrated yet.
        }
        sales_post_stock($pdo, $invoice_id, $tenant_id);
        redirect_back('Quotation converted to invoice.');
    }

    if ($action === 'email_quotation') {
        $invoice_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        if ($invoice_id <= 0) {
            redirect_back('Quotation not specified for email.', false);
        }
        $invoice = onyx_row('SELECT i.*, c.name AS customer_name, c.email AS customer_email FROM invoices i LEFT JOIN customers c ON c.id = i.customer_id AND c.tenant_id = i.tenant_id WHERE i.id = :id AND i.tenant_id = :tenant_id', ['id' => $invoice_id, 'tenant_id' => $tenant_id]);
        if (!$invoice) {
            redirect_back('Quotation not found.', false);
        }
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $to = $invoice['customer_email'] ?: '';
            $subject = 'Quotation ' . $invoice['invoice_number'];
            $message = 'Please find your quotation at: ' . (isset($_SERVER['HTTP_HOST']) ? 'https://' . $_SERVER['HTTP_HOST'] : '') . dirname($_SERVER['REQUEST_URI']) . '/sales_action.php?action=view_invoice&id=' . $invoice_id . "\n\n";
            $message .= 'Thank you.';
            $headers = 'From: no-reply@' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . "\r\n";
            if ($to && mail($to, $subject, $message, $headers)) {
                redirect_back('Quotation sent to ' . htmlspecialchars($to) . '.', true);
            }
            redirect_back('Unable to send email. Please ensure mail is configured.', false);
        }
        $title = 'Email Quotation';
        sales_action_start($title, 'Send the quotation to the customer by email.');
        ?>
        <div class="sales-form-page">
        <section class="sales-panel">
            <div class="sales-title"><i class="fa-solid fa-envelope"></i> <?= htmlspecialchars($invoice['invoice_number']) ?></div>
            <p><strong>Customer:</strong> <?= htmlspecialchars($invoice['customer_name'] ?: '-') ?></p>
            <p><strong>Recipient Email:</strong> <?= htmlspecialchars($invoice['customer_email'] ?: 'Not provided') ?></p>
            <form method="POST" action="sales_action.php?action=email_quotation&id=<?= htmlspecialchars($invoice_id) ?>">
                <button class="sales-btn primary" type="submit" style="margin-top:16px;">Send Email</button>
            </form>
        </section>
        </div>
        <?php
        onyx_page_end();
        exit();
    }

    if ($action === 'capture_payment') {
        $invoice_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        if ($invoice_id <= 0) {
            redirect_back('Invoice not specified for payment capture.', false);
        }

        $invoice = onyx_row('SELECT i.*, c.name AS customer_name, c.email AS customer_email FROM invoices i LEFT JOIN customers c ON c.id = i.customer_id AND c.tenant_id = i.tenant_id WHERE i.id = :id AND i.tenant_id = :tenant_id', ['id' => $invoice_id, 'tenant_id' => $tenant_id]);
        if (!$invoice) {
            redirect_back('Invoice not found.', false);
        }

        $paidAmount = sales_paid_amount($invoice_id, $tenant_id);
        $balance = (float)$invoice['total'] - (float)$paidAmount;

        $title = 'Receive Payment for ' . htmlspecialchars($invoice['invoice_number']);
        sales_action_start($title, 'Capture payment against the selected invoice.');
        ?>
        <form class="sales-form-page" method="POST" action="sales_action.php">
            <section class="sales-panel">
                <div class="sales-title"><i class="fa-solid fa-money-bill-transfer"></i> <?= htmlspecialchars($invoice['invoice_number']) ?></div>
                <div class="sales-total-grid">
                    <div class="sales-field"><label>Customer</label><input readonly value="<?= htmlspecialchars($invoice['customer_name'] ?: '-') ?>"></div>
                    <div class="sales-field"><label>Invoice Date</label><input readonly value="<?= htmlspecialchars($invoice['invoice_date']) ?>"></div>
                    <div class="sales-field"><label>Total</label><input readonly value="<?= htmlspecialchars(number_format((float)$invoice['total'], 2)) ?>"></div>
                    <div class="sales-field"><label>Paid</label><input readonly value="<?= htmlspecialchars(number_format((float)$paidAmount, 2)) ?>"></div>
                    <div class="sales-field"><label>Balance</label><input readonly value="<?= htmlspecialchars(number_format($balance, 2)) ?>"></div>
                </div>
            </section>
            <section class="sales-panel">
                <div class="sales-title"><i class="fa-solid fa-receipt"></i> Payment Details</div>
                <input type="hidden" name="action" value="capture_payment">
                <input type="hidden" name="invoice_id" value="<?= htmlspecialchars($invoice_id) ?>">
                <div class="sales-grid">
                    <div class="sales-field"><label>Payment Date</label><input type="date" name="payment_date" value="<?= date('Y-m-d') ?>" required></div>
                    <div class="sales-field"><label>Amount</label><input type="number" step="0.01" max="<?= htmlspecialchars(number_format(max(0, $balance), 2, '.', '')) ?>" name="amount" value="<?= htmlspecialchars(number_format(max(0, $balance), 2, '.', '')) ?>" required></div>
                    <div class="sales-field"><label>Payment Method</label><select name="method"><option value="cash">Cash</option><option value="bank_transfer">Bank Transfer</option><option value="mobile_money">Mobile Money</option><option value="card">Card</option><option value="cheque">Cheque</option><option value="other">Other</option></select></div>
                    <div class="sales-field wide"><label>Reference</label><input type="text" name="reference" placeholder="Payment reference (optional)"></div>
                </div>
                <div class="sales-actions" style="margin-top:16px;">
                    <a class="sales-btn" href="sales.php"><i class="fa-solid fa-arrow-left"></i> Back</a>
                    <button class="sales-btn primary" type="submit"><i class="fa-solid fa-check"></i> Record Payment</button>
                </div>
            </section>
        </form>
        <?php
        onyx_page_end();
        exit();
    }

    if ($action === 'view_invoice') {
        $invoice_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        if ($invoice_id <= 0) {
            redirect_back('Invoice not specified for viewing.', false);
        }

        $invoice = onyx_row('SELECT i.*, c.name AS customer_name, c.address AS customer_address FROM invoices i LEFT JOIN customers c ON c.id = i.customer_id AND c.tenant_id = i.tenant_id WHERE i.id = :id AND i.tenant_id = :tenant_id', ['id' => $invoice_id, 'tenant_id' => $tenant_id]);
        if (!$invoice) {
            redirect_back('Invoice not found.', false);
        }

        $lines = onyx_rows('SELECT l.*, p.name AS product_name FROM invoice_lines l JOIN invoices i ON i.id = l.invoice_id AND i.tenant_id = :tenant_id LEFT JOIN products p ON p.id = l.product_id AND p.tenant_id = i.tenant_id WHERE l.invoice_id = :invoice_id', ['invoice_id' => $invoice_id, 'tenant_id' => $tenant_id]);
        $payments = onyx_rows('SELECT * FROM invoice_payments WHERE invoice_id = :invoice_id AND tenant_id = :tenant_id ORDER BY payment_date DESC', ['invoice_id' => $invoice_id, 'tenant_id' => $tenant_id]);
        $paidAmount = sales_paid_amount($invoice_id, $tenant_id);
        $balance = (float)$invoice['total'] - (float)$paidAmount;

                // If print=1 is present, render a full-page printable invoice/quotation
                if (isset($_GET['print']) && $_GET['print']) {
                        // Determine company context
                        $ctx = onyx_context();
                        $settingsRows = onyx_rows('SELECT setting_key, setting_value FROM tenant_settings WHERE tenant_id = :tenant_id', ['tenant_id' => $tenant_id]);
                        $settings = [];
                        foreach ($settingsRows as $settingRow) {
                            $settings[$settingRow['setting_key']] = $settingRow['setting_value'];
                        }
                        $company_name = $ctx['company_name'] ?? 'Company';
                        $company_logo = trim((string) ($settings['company_logo'] ?? '')) ?: asset('assets/texaro-logo.png');
                        $company_email = $settings['email_address'] ?? ($_SESSION['email_address'] ?? '');
                        $company_phone = $settings['phone_number'] ?? ($_SESSION['phone_number'] ?? '');
                        $company_address = $settings['physical_address'] ?? ($_SESSION['physical_address'] ?? '');
                        $currency = $ctx['currency'] ?? 'UGX';
                        $documentTitle = invoice_type_label($invoice['invoice_type']);
                        $documentFooter = $invoice['invoice_type'] === 'quotation'
                            ? ($settings['quotation_terms'] ?? 'This quotation is valid for 7 days.')
                            : ($settings['invoice_footer'] ?? 'Thank you for your business.');

                        // Prepare totals
                        $subtotal = number_format((float)$invoice['subtotal'], 2);
                        $tax = number_format((float)$invoice['tax'], 2);
                        $total = number_format((float)$invoice['total'], 2);

                        // Render printable HTML
                        header('Content-Type: text/html; charset=utf-8');
                        ?>
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title><?= htmlspecialchars($documentTitle) ?> <?= htmlspecialchars($invoice['invoice_number']) ?></title>
    <style>
        body { background:#eef1f5; font-family: Arial, Helvetica, sans-serif; color: #172033; margin: 0; }
        .page { width: 820px; margin: 20px auto; background: #fff; padding: 34px; box-shadow: 0 14px 40px rgba(15,23,42,.12); }
        .header { border-bottom:3px solid #172033; display:flex; justify-content:space-between; align-items:flex-start; padding-bottom:18px; }
        .company { max-width:60%; }
        .brand-row { align-items:center; display:flex; gap:14px; }
        .company h2 { margin:0; font-size:18px; color:#111827; }
        .company .meta { margin-top:8px; color:#64748b; font-size:12px; line-height:1.5 }
        .logo { background:#fff; border:1px solid #e5e7eb; width:64px; height:64px; display:flex; align-items:center; justify-content:center; }
        .invoice-badge { background:#172033; color:#fff; padding:14px 18px; text-align:right; }
        .invoice-badge h3 { margin:0; font-size:20px; }
        .meta-rows { margin-top:12px; display:flex; gap:20px; }
        .meta-rows .col { background:#f8fafc; border:1px solid #e5e7eb; padding:12px 14px; }
        table.items { width:100%; border-collapse:collapse; margin-top:18px; background:#fff; }
        table.items thead th { background:#172033; color:#fff; padding:10px; text-align:left; }
        table.items td { padding:10px; border-bottom:1px solid #e5e7eb; }
        .right { text-align:right; }
        .summary { margin-top:12px; display:flex; justify-content:flex-end; gap:12px; }
        .summary .box { background:#f8fafc; border:1px solid #e5e7eb; padding:10px 14px; min-width:200px; }
        .total-due { background:#172033; color:#fff; padding:12px 16px; font-weight:700; }
        .tax-summary { margin-top:18px; background:#f8fafc; border:1px solid #e5e7eb; padding:12px; }
        .footer-note{border-top:1px solid #e5e7eb;color:#64748b;font-size:11px;line-height:1.5;margin-top:22px;padding-top:12px}
        @media print { body { background: #fff; } .page { box-shadow:none; margin:0; width:auto; } }
    </style>
</head>
<body>
    <div class="page">
        <div class="header">
            <div class="company">
                <div class="brand-row">
                    <?php if ($company_logo): ?>
                        <div class="logo"><img src="<?= htmlspecialchars($company_logo) ?>" alt="logo" style="max-width:54px;max-height:54px;"></div>
                    <?php endif; ?>
                    <div>
                        <h2><?= htmlspecialchars($company_name) ?></h2>
                        <div class="meta">
                            <?= nl2br(htmlspecialchars($company_address)) ?><br>
                            <?= htmlspecialchars($company_phone) ?> <?= $company_email ? ' | ' . htmlspecialchars($company_email) : '' ?>
                        </div>
                    </div>
                </div>
            </div>
            <div style="text-align:right;">
                <div class="invoice-badge">
                    <div style="font-size:12px;"><?= htmlspecialchars($documentTitle) ?></div>
                    <h3><?= htmlspecialchars($invoice['invoice_number']) ?></h3>
                </div>
            </div>
        </div>

        <div class="meta-rows">
            <div class="col">
                <strong>BILL TO</strong><br>
                <?= htmlspecialchars($invoice['customer_name'] ?: '-') ?><br>
                <?= nl2br(htmlspecialchars($invoice['customer_address'] ?? '')) ?>
            </div>
            <div class="col">
                <strong>DATE</strong><br>
                <?= htmlspecialchars($invoice['invoice_date']) ?><br>
                <strong>TERMS</strong><br>
                <?= htmlspecialchars($invoice['terms'] ?? 'Net 30') ?><br>
                <strong>DUE DATE</strong><br>
                <?= htmlspecialchars($invoice['due_date'] ?? '') ?>
            </div>
            <div style="flex:1"></div>
        </div>

        <table class="items">
            <thead>
                <tr>
                    <th style="width:120px;">DATE</th>
                    <th>ACTIVITY</th>
                    <th>DESCRIPTION</th>
                    <th style="width:80px;" class="right">QTY</th>
                    <th style="width:120px;" class="right">RATE</th>
                    <th style="width:120px;" class="right">AMOUNT</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($lines as $ln): ?>
                    <tr>
                        <td><?= htmlspecialchars($invoice['invoice_date']) ?></td>
                        <td><?= htmlspecialchars($ln['product_name'] ?: 'Item') ?></td>
                        <td><?= htmlspecialchars($ln['description'] ?? '') ?></td>
                        <td class="right"><?= (int)$ln['quantity'] ?></td>
                        <td class="right"><?= htmlspecialchars(number_format((float)$ln['unit_price'], 2)) ?></td>
                        <td class="right"><?= htmlspecialchars(number_format((float)$ln['line_total'], 2)) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="summary">
            <div class="box">
                <div>SUBTOTAL<br><strong><?= $subtotal ?></strong></div>
                <div>TAX<br><strong><?= $tax ?></strong></div>
                <div>TOTAL<br><strong><?= $total ?></strong></div>
            </div>
            <div class="total-due">TOTAL DUE<br><?= htmlspecialchars($currency . ' ' . $total) ?></div>
        </div>

        <div class="tax-summary">
            <strong>TAX SUMMARY</strong>
            <table style="width:100%; margin-top:8px; border-collapse:collapse;">
                <tr>
                    <td>RATE</td>
                    <td class="right">TAX</td>
                    <td class="right">NET</td>
                </tr>
                <tr>
                    <td>VAT</td>
                    <td class="right"><?= $tax ?></td>
                    <td class="right"><?= $subtotal ?></td>
                </tr>
            </table>
        </div>
        <div class="footer-note">
            <?= nl2br(htmlspecialchars($documentFooter)) ?><br>
            Authorized signature: ______________________________
        </div>
    </div>
</body>
</html>
                        <script>
                            window.onload = function(){
                                setTimeout(function(){ window.print(); }, 250);
                            };
                        </script>
                        <?php
                        exit();
                }

                sales_action_start('Invoice Preview', 'Preview and print invoice details.');
                ?>
                <div class="sales-preview-page">
                <section class="sales-panel">
                    <div style="display:flex;justify-content:space-between;align-items:center;">
                        <div class="sales-title" style="margin-bottom:0;"><i class="fa-solid fa-file-invoice"></i> <?= htmlspecialchars($invoice['invoice_number']) ?></div>
                        <div>
                            <a class="sales-btn primary" href="sales_action.php?action=view_invoice&id=<?= htmlspecialchars($invoice_id) ?>&print=1" target="_blank" style="margin-left:8px;">Print</a>
                        </div>
                    </div>
            <div style="margin-top:12px;">
                <p><strong>Customer:</strong> <?= htmlspecialchars($invoice['customer_name'] ?: '-') ?></p>
                <p><strong>Type:</strong> <?= htmlspecialchars(invoice_type_label($invoice['invoice_type'])) ?></p>
                <p><strong>Date:</strong> <?= htmlspecialchars($invoice['invoice_date']) ?></p>
                <p><strong>Status:</strong> <?= sales_status_badge($invoice['status'] ?: 'draft') ?></p>
                <p><strong>Notes:</strong> <?= nl2br(htmlspecialchars($invoice['notes'] ?? '-')) ?></p>
            </div>
            <div class="sales-table-wrap">
            <table class="sales-table" style="margin-top:16px;">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Description</th>
                        <th>Unit Price</th>
                        <th>Qty</th>
                        <th>Tax %</th>
                        <th>Line Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($lines as $line): ?>
                        <tr>
                            <td><?= htmlspecialchars($line['product_name'] ?: '-') ?></td>
                            <td><?= htmlspecialchars($line['description'] ?: '-') ?></td>
                            <td><?= htmlspecialchars(number_format((float)$line['unit_price'], 2)) ?></td>
                            <td><?= htmlspecialchars((int)$line['quantity']) ?></td>
                            <td><?= htmlspecialchars(number_format((float)$line['tax_rate'], 2)) ?>%</td>
                            <td><?= htmlspecialchars(number_format((float)$line['line_total'], 2)) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            </div>
            <div style="margin-top:16px; display:flex; gap:24px; flex-wrap:wrap;">
                <div><strong>Subtotal:</strong> <?= htmlspecialchars(number_format((float)$invoice['subtotal'], 2)) ?></div>
                <div><strong>Total Tax:</strong> <?= htmlspecialchars(number_format((float)$invoice['tax'], 2)) ?></div>
                <div><strong>Discount:</strong> <?= htmlspecialchars(number_format((float)($invoice['discount'] ?? 0), 2)) ?></div>
                <div><strong>Delivery:</strong> <?= htmlspecialchars(number_format((float)($invoice['delivery_charge'] ?? 0), 2)) ?></div>
                <div><strong>Total:</strong> <?= htmlspecialchars(number_format((float)$invoice['total'], 2)) ?></div>
                <div><strong>Paid:</strong> <?= htmlspecialchars(number_format((float)$paidAmount, 2)) ?></div>
                <div><strong>Balance:</strong> <?= htmlspecialchars(number_format($balance, 2)) ?></div>
            </div>
            <div style="margin-top:24px;">
                <div class="panel-title"><i class="fa-solid fa-hand-holding-dollar"></i> Payments</div>
                <?php if ($payments === []): ?>
                    <p>No payments have been recorded for this invoice.</p>
                <?php else: ?>
                    <table class="sales-table" style="width:100%;margin-top:10px;">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Amount</th>
                                <th>Method</th>
                                <th>Reference</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($payments as $payment): ?>
                                <tr>
                                    <td><?= htmlspecialchars($payment['payment_date']) ?></td>
                                    <td><?= htmlspecialchars(number_format((float)$payment['amount'], 2)) ?></td>
                                    <td><?= htmlspecialchars($payment['method']) ?></td>
                                    <td><?= htmlspecialchars($payment['reference'] ?: '-') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </section>
        </div>
        <?php
        onyx_page_end();
        exit();
    }

    redirect_back('Unsupported sales action.', false);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'create_invoice') {
        $invoice_type = $_POST['invoice_type'] ?? 'invoice';
        if (!in_array($invoice_type, $allowedTypes, true)) {
            $invoice_type = 'invoice';
        }

        $customer_id = isset($_POST['customer_id']) ? (int) $_POST['customer_id'] : 0;
        $invoice_date = $_POST['invoice_date'] ?? date('Y-m-d');
        $due_date = $_POST['due_date'] ?: null;
        $notes = trim($_POST['notes'] ?? '');
        $terms = trim($_POST['terms'] ?? 'Net 30');
        $salesperson = trim($_POST['salesperson'] ?? '');
        $branch_name = trim($_POST['branch_name'] ?? '');
        $customer_reference = trim($_POST['customer_reference'] ?? '');
        $totals = sales_collect_lines($_POST);
        $lines = $totals['lines'];

        if ($customer_id <= 0) {
            redirect_back('Please select a customer.', false);
        }

        if ($lines === []) {
            redirect_back('Please add at least one invoice line with a quantity and unit price.', false);
        }

        $invoice_number = invoice_number_prefix($invoice_type) . '-' . strtoupper(uniqid());
        $title = invoice_type_label($invoice_type);

        $insertInvoice = $pdo->prepare('INSERT INTO invoices (tenant_id, invoice_number, invoice_type, customer_id, invoice_date, due_date, notes, terms, salesperson, branch_name, customer_reference, discount, delivery_charge, subtotal, tax, total, status, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())');
        $insertInvoice->execute([
            $tenant_id,
            $invoice_number,
            $invoice_type,
            $customer_id,
            $invoice_date,
            $due_date,
            $notes,
            $terms ?: 'Net 30',
            $salesperson ?: null,
            $branch_name ?: null,
            $customer_reference ?: null,
            $totals['discount'],
            $totals['delivery_charge'],
            $totals['subtotal'],
            $totals['tax'],
            $totals['total'],
            'draft',
        ]);
        $invoice_id = (int) $pdo->lastInsertId();

        $insertLine = $pdo->prepare('INSERT INTO invoice_lines (tenant_id, invoice_id, product_id, description, unit_price, quantity, tax_rate, line_total, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())');
        foreach ($lines as $line) {
            $insertLine->execute([
                $tenant_id,
                $invoice_id,
                $line['product_id'],
                $line['description'],
                $line['unit_price'],
                $line['quantity'],
                $line['tax_rate'],
                $line['line_total'],
            ]);
        }

        if ($invoice_type !== 'quotation') {
            sales_post_stock($pdo, $invoice_id, $tenant_id);
        }

        redirect_back($title . ' created successfully.');
    }

    if ($action === 'edit_invoice') {
        $invoice_id = isset($_POST['invoice_id']) ? (int) $_POST['invoice_id'] : 0;
        $customer_id = isset($_POST['customer_id']) ? (int) $_POST['customer_id'] : 0;
        $invoice_date = $_POST['invoice_date'] ?? date('Y-m-d');
        $due_date = $_POST['due_date'] ?: null;
        $notes = trim($_POST['notes'] ?? '');
        $terms = trim($_POST['terms'] ?? 'Net 30');
        $salesperson = trim($_POST['salesperson'] ?? '');
        $branch_name = trim($_POST['branch_name'] ?? '');
        $customer_reference = trim($_POST['customer_reference'] ?? '');
        $totals = sales_collect_lines($_POST);
        $lines = $totals['lines'];

        if ($invoice_id <= 0 || $customer_id <= 0) {
            redirect_back('Document and customer are required.', false);
        }
        if ($lines === []) {
            redirect_back('Please add at least one line with a quantity and unit price.', false);
        }

        $invoice = onyx_row('SELECT * FROM invoices WHERE id = :id AND tenant_id = :tenant_id', ['id' => $invoice_id, 'tenant_id' => $tenant_id]);
        if (! $invoice) {
            redirect_back('Sales document not found.', false);
        }
        if (in_array($invoice['status'], ['paid', 'cancelled'], true) || (int) ($invoice['accounting_posted'] ?? 0) === 1) {
            redirect_back('Posted, paid, or cancelled documents cannot be edited. Create a credit note or return instead.', false);
        }

        sales_reverse_stock($pdo, $invoice_id, $tenant_id);

        $update = $pdo->prepare('UPDATE invoices SET customer_id = ?, invoice_date = ?, due_date = ?, notes = ?, terms = ?, salesperson = ?, branch_name = ?, customer_reference = ?, discount = ?, delivery_charge = ?, subtotal = ?, tax = ?, total = ?, updated_at = NOW() WHERE id = ? AND tenant_id = ?');
        $update->execute([
            $customer_id,
            $invoice_date,
            $due_date,
            $notes,
            $terms ?: 'Net 30',
            $salesperson ?: null,
            $branch_name ?: null,
            $customer_reference ?: null,
            $totals['discount'],
            $totals['delivery_charge'],
            $totals['subtotal'],
            $totals['tax'],
            $totals['total'],
            $invoice_id,
            $tenant_id,
        ]);

        $pdo->prepare('DELETE FROM invoice_lines WHERE invoice_id = ? AND tenant_id = ?')->execute([$invoice_id, $tenant_id]);
        $insertLine = $pdo->prepare('INSERT INTO invoice_lines (tenant_id, invoice_id, product_id, description, unit_price, quantity, tax_rate, line_total, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())');
        foreach ($lines as $line) {
            $insertLine->execute([
                $tenant_id,
                $invoice_id,
                $line['product_id'],
                $line['description'],
                $line['unit_price'],
                $line['quantity'],
                $line['tax_rate'],
                $line['line_total'],
            ]);
        }

        if ($invoice['invoice_type'] !== 'quotation') {
            sales_post_stock($pdo, $invoice_id, $tenant_id);
            sales_refresh_payment_status($pdo, $invoice_id, $tenant_id);
        }

        redirect_back(invoice_type_label($invoice['invoice_type']) . ' updated successfully.');
    }

    if ($action === 'capture_payment') {
        $invoice_id = isset($_POST['invoice_id']) ? (int) $_POST['invoice_id'] : 0;
        $payment_date = $_POST['payment_date'] ?? date('Y-m-d');
        $amount = isset($_POST['amount']) ? (float) $_POST['amount'] : 0.0;
        $method = trim($_POST['method'] ?? 'cash');
        $reference = trim($_POST['reference'] ?? '');

        if ($invoice_id <= 0 || $amount <= 0) {
            redirect_back('Invoice and valid payment amount are required.', false);
        }

        $invoice = onyx_row('SELECT id, total, status FROM invoices WHERE id = :id AND tenant_id = :tenant_id AND invoice_type = :invoice_type', ['id' => $invoice_id, 'tenant_id' => $tenant_id, 'invoice_type' => 'invoice']);
        if (!$invoice) {
            redirect_back('Invoice not found.', false);
        }
        if (in_array($invoice['status'], ['paid', 'cancelled'], true)) {
            redirect_back('This invoice is already closed for payment.', false);
        }

        $paidAmount = sales_paid_amount($invoice_id, $tenant_id);
        $balance = max(0, (float) $invoice['total'] - $paidAmount);
        if ($amount > $balance + 0.001) {
            redirect_back('Payment cannot exceed the invoice balance.', false);
        }

        $insertPayment = $pdo->prepare('INSERT INTO invoice_payments (tenant_id, invoice_id, payment_date, amount, method, reference, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())');
        $insertPayment->execute([$tenant_id, $invoice_id, $payment_date, $amount, $method, $reference]);
        $payment_id = (int) $pdo->lastInsertId();

        sales_post_invoice_accounting($pdo, $invoice_id, $tenant_id);
        sales_refresh_payment_status($pdo, $invoice_id, $tenant_id);
        sales_post_payment_accounting($pdo, $payment_id, $tenant_id);

        redirect_back('Payment captured successfully.');
    }

    redirect_back('Unsupported sales action.', false);
}
