<?php
require_once __DIR__ . '/includes/erp_layout.php';

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
        status ENUM('draft','sent','paid','overdue','cancelled') NOT NULL DEFAULT 'draft',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY uq_tenant_invoice_number (tenant_id, invoice_number),
        KEY idx_invoice_tenant_date (tenant_id, invoice_date)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    $pdo->exec("CREATE TABLE IF NOT EXISTS invoice_lines (
        id BIGINT(20) NOT NULL AUTO_INCREMENT,
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
    ensure_column($pdo, 'invoices', 'status', "ENUM('draft','sent','paid','overdue','cancelled') NOT NULL DEFAULT 'draft'");
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

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if ($action === 'create_invoice') {
        $invoice_type = $_GET['invoice_type'] ?? 'invoice';
        if (!in_array($invoice_type, $allowedTypes, true)) {
            $invoice_type = 'invoice';
        }

        $customers = onyx_rows('SELECT id, name FROM customers WHERE tenant_id = :tenant_id ORDER BY name ASC', ['tenant_id' => $tenant_id]);
        $products = onyx_rows('SELECT id, name, sku, selling_price, vat_rate FROM products WHERE tenant_id = :tenant_id ORDER BY name ASC', ['tenant_id' => $tenant_id]);

        $title = invoice_type_label($invoice_type);
        $context = onyx_page_start($title, 'Create a new ' . $title . '.');
        ?>
        <form method="POST" action="sales_action.php">
            <input type="hidden" name="action" value="create_invoice">
            <input type="hidden" name="invoice_type" value="<?= htmlspecialchars($invoice_type) ?>">
            <div class="panel span-8">
                <div class="panel-title"><i class="fa-solid fa-file-circle-plus"></i> <?= htmlspecialchars($title) ?></div>
                <div style="margin-top:12px;">
                    <label>Customer</label><br>
                    <select name="customer_id" required style="width:100%;padding:8px;margin:6px 0;">
                        <option value="">-- Select customer --</option>
                        <?php foreach ($customers as $customer): ?>
                            <option value="<?= htmlspecialchars($customer['id']) ?>"><?= htmlspecialchars($customer['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <label>Invoice Date</label><br>
                    <input type="date" name="invoice_date" value="<?= date('Y-m-d') ?>" required style="width:100%;padding:8px;margin:6px 0;">
                    <label>Due Date</label><br>
                    <input type="date" name="due_date" style="width:100%;padding:8px;margin:6px 0;">
                    <label>Notes</label><br>
                    <textarea name="notes" style="width:100%;padding:8px;margin:6px 0;" placeholder="Optional invoice notes"></textarea>
                </div>
                <div style="margin-top:20px;">
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
                    <button type="button" id="add-line" class="action-btn" style="margin-top:12px;">Add Line</button>
                </div>
                <div style="margin-top:20px; display:flex; justify-content:space-between; gap:16px; flex-wrap:wrap;">
                    <div style="min-width:240px;">
                        <label>Subtotal</label><br>
                        <input type="text" id="invoice-subtotal" readonly value="0.00" style="width:100%;padding:8px;background:#1c1c27;border:1px solid #333;color:#fff;">
                    </div>
                    <div style="min-width:240px;">
                        <label>Total Tax</label><br>
                        <input type="text" id="invoice-tax" readonly value="0.00" style="width:100%;padding:8px;background:#1c1c27;border:1px solid #333;color:#fff;">
                    </div>
                    <div style="min-width:240px;">
                        <label>Total Amount</label><br>
                        <input type="text" id="invoice-total" readonly value="0.00" style="width:100%;padding:8px;background:#1c1c27;border:1px solid #333;color:#fff;">
                    </div>
                </div>
                <button class="action-btn" type="submit" style="margin-top:24px;">Save <?= htmlspecialchars($title) ?></button>
            </div>
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
                totalInput.value = (subtotal + taxTotal).toFixed(2);
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
        onyx_page_start('Select Quotation', 'Choose a quotation to perform the requested action.');
        $rows = array_map(static fn (array $row): array => [
            $row['invoice_number'],
            $row['customer_name'] ?: '-',
            $row['invoice_date'] ?: '-',
            number_format((float) ($row['total'] ?? 0), 2),
            $row['status'] ?: '-',
            '<a class="action-btn" href="sales_action.php?action=select_quotation&task=' . urlencode($task) . '&id=' . htmlspecialchars($row['id']) . '">Select</a>',
        ], $quotations);
        onyx_table(['Quotation No', 'Customer', 'Date', 'Total', 'Status', 'Select'], $rows);
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
        onyx_page_start('Select Invoice', 'Choose an invoice to capture payment for.');
        $rows = array_map(static fn (array $row): array => [
            $row['invoice_number'],
            $row['customer_name'] ?: '-',
            $row['invoice_date'] ?: '-',
            number_format((float) ($row['total'] ?? 0), 2),
            $row['status'] ?: '-',
            '<a class="action-btn" href="sales_action.php?action=select_invoice&task=' . urlencode($task) . '&id=' . htmlspecialchars($row['id']) . '">Pay</a>',
        ], $invoices);
        onyx_table(['Invoice No', 'Customer', 'Date', 'Total', 'Status', 'Pay'], $rows);
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

        $lines = onyx_rows('SELECT * FROM invoice_lines WHERE invoice_id = :invoice_id', ['invoice_id' => $invoice_id]);
        $customers = onyx_rows('SELECT id, name FROM customers WHERE tenant_id = :tenant_id ORDER BY name ASC', ['tenant_id' => $tenant_id]);
        $products = onyx_rows('SELECT id, name, sku, selling_price, vat_rate FROM products WHERE tenant_id = :tenant_id ORDER BY name ASC', ['tenant_id' => $tenant_id]);
        $title = invoice_type_label($invoice['invoice_type']);
        $context = onyx_page_start('Edit ' . $title, 'Edit the selected quotation or invoice.');
        ?>
        <form method="POST" action="sales_action.php">
            <input type="hidden" name="action" value="edit_invoice">
            <input type="hidden" name="invoice_id" value="<?= htmlspecialchars($invoice_id) ?>">
            <input type="hidden" name="invoice_type" value="<?= htmlspecialchars($invoice['invoice_type']) ?>">
            <div class="panel span-8">
                <div class="panel-title"><i class="fa-solid fa-pen-to-square"></i> Edit <?= htmlspecialchars($title) ?></div>
                <div style="margin-top:12px;">
                    <label>Customer</label><br>
                    <select name="customer_id" required style="width:100%;padding:8px;margin:6px 0;">
                        <?php foreach ($customers as $customer): ?>
                            <option value="<?= htmlspecialchars($customer['id']) ?>" <?= $customer['id'] == $invoice['customer_id'] ? 'selected' : '' ?>><?= htmlspecialchars($customer['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <label>Invoice Date</label><br>
                    <input type="date" name="invoice_date" value="<?= htmlspecialchars($invoice['invoice_date']) ?>" required style="width:100%;padding:8px;margin:6px 0;">
                    <label>Due Date</label><br>
                    <input type="date" name="due_date" value="<?= htmlspecialchars($invoice['due_date'] ?? '') ?>" style="width:100%;padding:8px;margin:6px 0;">
                    <label>Notes</label><br>
                    <textarea name="notes" style="width:100%;padding:8px;margin:6px 0;" placeholder="Optional invoice notes"><?= htmlspecialchars($invoice['notes'] ?? '') ?></textarea>
                </div>
                <div style="margin-top:20px;">
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
                    <button type="button" id="add-line" class="action-btn" style="margin-top:12px;">Add Line</button>
                </div>
                <div style="margin-top:20px; display:flex; justify-content:space-between; gap:16px; flex-wrap:wrap;">
                    <div style="min-width:240px;">
                        <label>Subtotal</label><br>
                        <input type="text" id="invoice-subtotal" readonly value="<?= htmlspecialchars(number_format((float)$invoice['subtotal'], 2)) ?>" style="width:100%;padding:8px;background:#1c1c27;border:1px solid #333;color:#fff;">
                    </div>
                    <div style="min-width:240px;">
                        <label>Total Tax</label><br>
                        <input type="text" id="invoice-tax" readonly value="<?= htmlspecialchars(number_format((float)$invoice['tax'], 2)) ?>" style="width:100%;padding:8px;background:#1c1c27;border:1px solid #333;color:#fff;">
                    </div>
                    <div style="min-width:240px;">
                        <label>Total Amount</label><br>
                        <input type="text" id="invoice-total" readonly value="<?= htmlspecialchars(number_format((float)$invoice['total'], 2)) ?>" style="width:100%;padding:8px;background:#1c1c27;border:1px solid #333;color:#fff;">
                    </div>
                </div>
                <button class="action-btn" type="submit" style="margin-top:24px;">Save <?= htmlspecialchars($title) ?></button>
            </div>
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
                totalInput.value = (subtotal + taxTotal).toFixed(2);
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
        $stmt->execute(['sent', $invoice_id, $tenant_id, 'quotation']);
        redirect_back('Quotation approved and marked as sent.');
    }

    if ($action === 'convert_quotation') {
        $invoice_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        if ($invoice_id <= 0) {
            redirect_back('Quotation not specified for conversion.', false);
        }
        $newNumber = invoice_number_prefix('invoice') . '-' . strtoupper(uniqid());
        $stmt = $pdo->prepare('UPDATE invoices SET invoice_type = ?, invoice_number = ?, status = ?, updated_at = NOW() WHERE id = ? AND tenant_id = ? AND invoice_type = ?');
        $stmt->execute(['invoice', $newNumber, 'draft', $invoice_id, $tenant_id, 'quotation']);
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
        onyx_page_start($title, 'Send the quotation to the customer by email.');
        ?>
        <div class="panel span-8">
            <div class="panel-title"><i class="fa-solid fa-envelope"></i> <?= htmlspecialchars($invoice['invoice_number']) ?></div>
            <p><strong>Customer:</strong> <?= htmlspecialchars($invoice['customer_name'] ?: '-') ?></p>
            <p><strong>Recipient Email:</strong> <?= htmlspecialchars($invoice['customer_email'] ?: 'Not provided') ?></p>
            <form method="POST" action="sales_action.php?action=email_quotation&id=<?= htmlspecialchars($invoice_id) ?>">
                <button class="action-btn" type="submit" style="margin-top:16px;">Send Email</button>
            </form>
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

        $paidAmount = onyx_scalar('SELECT COALESCE(SUM(amount), 0) FROM invoice_payments WHERE invoice_id = :invoice_id AND tenant_id = :tenant_id', ['invoice_id' => $invoice_id, 'tenant_id' => $tenant_id]);
        $balance = (float)$invoice['total'] - (float)$paidAmount;

        $title = 'Receive Payment for ' . htmlspecialchars($invoice['invoice_number']);
        onyx_page_start($title, 'Capture payment against the selected invoice.');
        ?>
        <div class="panel span-8">
            <div class="panel-title"><i class="fa-solid fa-money-bill-transfer"></i> <?= htmlspecialchars($invoice['invoice_number']) ?></div>
            <div style="margin-top:12px;">
                <p><strong>Customer:</strong> <?= htmlspecialchars($invoice['customer_name'] ?: '-') ?></p>
                <p><strong>Invoice Date:</strong> <?= htmlspecialchars($invoice['invoice_date']) ?></p>
                <p><strong>Total:</strong> <?= htmlspecialchars(number_format((float)$invoice['total'], 2)) ?></p>
                <p><strong>Paid:</strong> <?= htmlspecialchars(number_format((float)$paidAmount, 2)) ?></p>
                <p><strong>Balance:</strong> <?= htmlspecialchars(number_format($balance, 2)) ?></p>
            </div>
            <form method="POST" action="sales_action.php">
                <input type="hidden" name="action" value="capture_payment">
                <input type="hidden" name="invoice_id" value="<?= htmlspecialchars($invoice_id) ?>">
                <label>Payment Date</label><br>
                <input type="date" name="payment_date" value="<?= date('Y-m-d') ?>" required style="width:100%;padding:8px;margin:6px 0;">
                <label>Amount</label><br>
                <input type="number" step="0.01" name="amount" value="<?= htmlspecialchars(number_format($balance, 2, '.', '')) ?>" required style="width:100%;padding:8px;margin:6px 0;">
                <label>Payment Method</label><br>
                <select name="method" style="width:100%;padding:8px;margin:6px 0;">
                    <option value="cash">Cash</option>
                    <option value="bank_transfer">Bank Transfer</option>
                    <option value="card">Card</option>
                    <option value="other">Other</option>
                </select>
                <label>Reference</label><br>
                <input type="text" name="reference" style="width:100%;padding:8px;margin:6px 0;" placeholder="Payment reference (optional)">
                <button class="action-btn" type="submit" style="margin-top:16px;">Record Payment</button>
            </form>
        </div>
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

        $lines = onyx_rows('SELECT l.*, p.name AS product_name FROM invoice_lines l LEFT JOIN products p ON p.id = l.product_id WHERE l.invoice_id = :invoice_id', ['invoice_id' => $invoice_id]);
        $payments = onyx_rows('SELECT * FROM invoice_payments WHERE invoice_id = :invoice_id ORDER BY payment_date DESC', ['invoice_id' => $invoice_id]);
        $paidAmount = onyx_scalar('SELECT COALESCE(SUM(amount), 0) FROM invoice_payments WHERE invoice_id = :invoice_id', ['invoice_id' => $invoice_id]);
        $balance = (float)$invoice['total'] - (float)$paidAmount;

                // If print=1 is present, render a full-page printable invoice/quotation
                if (isset($_GET['print']) && $_GET['print']) {
                        // Determine company context
                        $ctx = onyx_context();
                        $company_name = $ctx['company_name'] ?? 'Company';
                        $company_logo = $_SESSION['company_logo'] ?? ($_SESSION['company_logo'] ?? '');
                        $company_email = $_SESSION['email_address'] ?? '';
                        $company_phone = $_SESSION['phone_number'] ?? '';
                        $company_address = $_SESSION['physical_address'] ?? '';
                        $currency = $ctx['currency'] ?? 'UGX';

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
    <title>Tax Invoice <?= htmlspecialchars($invoice['invoice_number']) ?></title>
    <style>
        body { font-family: Arial, Helvetica, sans-serif; color: #222; margin: 0; }
        .page { width: 800px; margin: 20px auto; background: #f3f5f7; padding: 24px; box-shadow: 0 0 0 #000; }
        .header { display:flex; justify-content:space-between; align-items:flex-start; }
        .company { max-width:60%; }
        .company h2 { margin:0; font-size:16px; color:#111; }
        .company .meta { margin-top:8px; color:#333; font-size:13px; line-height:1.4 }
        .logo { width:160px; height:100px; display:flex; align-items:center; justify-content:center; }
        .invoice-badge { background:#9c1e7a; color:#fff; padding:12px 16px; border-radius:4px; text-align:right; }
        .invoice-badge h3 { margin:0; font-size:18px; }
        .meta-rows { margin-top:12px; display:flex; gap:20px; }
        .meta-rows .col { background:#fff; padding:8px 12px; border-radius:4px; }
        table.items { width:100%; border-collapse:collapse; margin-top:18px; background:#fff; }
        table.items thead th { background:#6d1050; color:#fff; padding:10px; text-align:left; }
        table.items td { padding:10px; border-bottom:1px solid #eee; }
        .right { text-align:right; }
        .summary { margin-top:12px; display:flex; justify-content:flex-end; gap:12px; }
        .summary .box { background:#fff; padding:10px 14px; border-radius:6px; min-width:200px; }
        .total-due { background:#9c1e7a; color:#fff; padding:10px 14px; border-radius:6px; font-weight:700; }
        .tax-summary { margin-top:18px; background:#fff; padding:10px; border-radius:6px; }
        @media print { body { background: #fff; } .page { box-shadow:none; margin:0; } }
    </style>
</head>
<body>
    <div class="page">
        <div class="header">
            <div class="company">
                <h2><?= htmlspecialchars($company_name) ?></h2>
                <div class="meta">
                    <?= nl2br(htmlspecialchars($company_address)) ?><br>
                    <?= htmlspecialchars($company_phone) ?> <?= $company_email ? ' | ' . htmlspecialchars($company_email) : '' ?>
                </div>
            </div>
            <div style="text-align:right;">
                <?php if ($company_logo): ?>
                    <div class="logo"><img src="<?= htmlspecialchars($company_logo) ?>" alt="logo" style="max-width:100%;max-height:100%;"></div>
                <?php endif; ?>
                <div style="height:10px"></div>
                <div class="invoice-badge">
                    <div style="font-size:12px;">Tax Invoice</div>
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
                    <td>VAT @ <?= htmlspecialchars(number_format((float)$invoice['tax_rate'] ?? 0, 0)) ?>%</td>
                    <td class="right"><?= $tax ?></td>
                    <td class="right"><?= $subtotal ?></td>
                </tr>
            </table>
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

                onyx_page_start('Invoice Preview', 'Preview and print invoice details.');
                ?>
                <div class="panel span-10">
                    <div style="display:flex;justify-content:space-between;align-items:center;">
                        <div class="panel-title"><i class="fa-solid fa-file-invoice"></i> <?= htmlspecialchars($invoice['invoice_number']) ?></div>
                        <div>
                            <a class="action-btn" href="sales_action.php?action=view_invoice&id=<?= htmlspecialchars($invoice_id) ?>&print=1" target="_blank" style="margin-left:8px;">Print</a>
                        </div>
                    </div>
            <div style="margin-top:12px;">
                <p><strong>Customer:</strong> <?= htmlspecialchars($invoice['customer_name'] ?: '-') ?></p>
                <p><strong>Type:</strong> <?= htmlspecialchars(invoice_type_label($invoice['invoice_type'])) ?></p>
                <p><strong>Date:</strong> <?= htmlspecialchars($invoice['invoice_date']) ?></p>
                <p><strong>Status:</strong> <?= htmlspecialchars(ucwords(str_replace('_', ' ', $invoice['status']))) ?></p>
                <p><strong>Notes:</strong> <?= nl2br(htmlspecialchars($invoice['notes'] ?? '-')) ?></p>
            </div>
            <table class="table" style="width:100%;margin-top:16px;">
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
            <div style="margin-top:16px; display:flex; gap:24px; flex-wrap:wrap;">
                <div><strong>Subtotal:</strong> <?= htmlspecialchars(number_format((float)$invoice['subtotal'], 2)) ?></div>
                <div><strong>Total Tax:</strong> <?= htmlspecialchars(number_format((float)$invoice['tax'], 2)) ?></div>
                <div><strong>Total:</strong> <?= htmlspecialchars(number_format((float)$invoice['total'], 2)) ?></div>
                <div><strong>Paid:</strong> <?= htmlspecialchars(number_format((float)$paidAmount, 2)) ?></div>
                <div><strong>Balance:</strong> <?= htmlspecialchars(number_format($balance, 2)) ?></div>
            </div>
            <div style="margin-top:24px;">
                <div class="panel-title"><i class="fa-solid fa-hand-holding-dollar"></i> Payments</div>
                <?php if ($payments === []): ?>
                    <p>No payments have been recorded for this invoice.</p>
                <?php else: ?>
                    <table class="table" style="width:100%;margin-top:10px;">
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
        $product_ids = $_POST['product_id'] ?? [];
        $descriptions = $_POST['description'] ?? [];
        $unit_prices = $_POST['unit_price'] ?? [];
        $quantities = $_POST['quantity'] ?? [];
        $tax_rates = $_POST['tax_rate'] ?? [];

        if ($customer_id <= 0) {
            redirect_back('Please select a customer.', false);
        }

        $lineCount = max(count($product_ids), count($descriptions));
        $subtotal = 0.0;
        $taxTotal = 0.0;
        $total = 0.0;
        $lines = [];

        for ($i = 0; $i < $lineCount; $i++) {
            $product_id = isset($product_ids[$i]) ? (int) $product_ids[$i] : null;
            $description = trim($descriptions[$i] ?? '');
            $unit_price = isset($unit_prices[$i]) ? (float) $unit_prices[$i] : 0.0;
            $quantity = isset($quantities[$i]) ? (int) $quantities[$i] : 0;
            $tax_rate = isset($tax_rates[$i]) ? (float) $tax_rates[$i] : 0.0;
            if ($quantity <= 0 || $unit_price <= 0) {
                continue;
            }
            $lineSubtotal = $unit_price * $quantity;
            $lineTax = $lineSubtotal * ($tax_rate / 100);
            $lineTotal = $lineSubtotal + $lineTax;
            $subtotal += $lineSubtotal;
            $taxTotal += $lineTax;
            $total += $lineTotal;
            $lines[] = [
                'product_id' => $product_id,
                'description' => $description,
                'unit_price' => $unit_price,
                'quantity' => $quantity,
                'tax_rate' => $tax_rate,
                'line_total' => $lineTotal,
            ];
        }

        if ($lines === []) {
            redirect_back('Please add at least one invoice line with a quantity and unit price.', false);
        }

        $invoice_number = invoice_number_prefix($invoice_type) . '-' . strtoupper(uniqid());
        $title = invoice_type_label($invoice_type);

        $insertInvoice = $pdo->prepare('INSERT INTO invoices (tenant_id, invoice_number, invoice_type, customer_id, invoice_date, due_date, notes, subtotal, tax, total, status, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())');
        $insertInvoice->execute([$tenant_id, $invoice_number, $invoice_type, $customer_id, $invoice_date, $due_date, $notes, $subtotal, $taxTotal, $total, 'draft']);
        $invoice_id = (int) $pdo->lastInsertId();

        $insertLine = $pdo->prepare('INSERT INTO invoice_lines (invoice_id, product_id, description, unit_price, quantity, tax_rate, line_total, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())');
        foreach ($lines as $line) {
            $insertLine->execute([
                $invoice_id,
                $line['product_id'],
                $line['description'],
                $line['unit_price'],
                $line['quantity'],
                $line['tax_rate'],
                $line['line_total'],
            ]);
        }

        redirect_back($title . ' created successfully.');
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

        $invoice = onyx_row('SELECT id, total FROM invoices WHERE id = :id AND tenant_id = :tenant_id', ['id' => $invoice_id, 'tenant_id' => $tenant_id]);
        if (!$invoice) {
            redirect_back('Invoice not found.', false);
        }

        $insertPayment = $pdo->prepare('INSERT INTO invoice_payments (tenant_id, invoice_id, payment_date, amount, method, reference, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())');
        $insertPayment->execute([$tenant_id, $invoice_id, $payment_date, $amount, $method, $reference]);

        $paidAmount = onyx_scalar('SELECT COALESCE(SUM(amount), 0) FROM invoice_payments WHERE invoice_id = :invoice_id AND tenant_id = :tenant_id', ['invoice_id' => $invoice_id, 'tenant_id' => $tenant_id]);
        if ((float)$paidAmount >= (float)$invoice['total']) {
            $updateInvoice = $pdo->prepare('UPDATE invoices SET status = ? WHERE id = ? AND tenant_id = ?');
            $updateInvoice->execute(['paid', $invoice_id, $tenant_id]);
        }

        redirect_back('Payment captured successfully.');
    }

    redirect_back('Unsupported sales action.', false);
}
