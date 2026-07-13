<?php

$tenant_id = (int) (onyx_tenant_id() ?? 0);
$pdo = onyx_db();

function inventory_h(mixed $value): string
{
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
}

function inventory_redirect(string $msg = '', bool $success = true): void
{
    $query = $msg !== '' ? ($success ? '?success=' : '?error=') . urlencode($msg) : '';
    header('Location: inventory.php' . $query);
    exit();
}

function inventory_columns(PDO $pdo, string $table): array
{
    try {
        return array_map(static fn (array $row): string => $row['Field'], $pdo->query('SHOW COLUMNS FROM `' . str_replace('`', '``', $table) . '`')->fetchAll(PDO::FETCH_ASSOC));
    } catch (Throwable) {
        return [];
    }
}

function inventory_ensure_column(PDO $pdo, string $table, string $column, string $definition): void
{
    if (! in_array($column, inventory_columns($pdo, $table), true)) {
        $pdo->exec('ALTER TABLE `' . str_replace('`', '``', $table) . '` ADD COLUMN `' . str_replace('`', '``', $column) . '` ' . $definition);
    }
}

function inventory_ensure_schema(PDO $pdo, int $tenantId): void
{
    $pdo->exec("CREATE TABLE IF NOT EXISTS inventory_categories (
        id BIGINT(20) NOT NULL AUTO_INCREMENT,
        tenant_id BIGINT(20) NOT NULL,
        name VARCHAR(155) NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY idx_inventory_category_tenant (tenant_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    $pdo->exec("CREATE TABLE IF NOT EXISTS inventory_brands (
        id BIGINT(20) NOT NULL AUTO_INCREMENT,
        tenant_id BIGINT(20) NOT NULL,
        name VARCHAR(155) NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY idx_inventory_brand_tenant (tenant_id)
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
        KEY idx_inventory_transaction_tenant (tenant_id),
        KEY idx_inventory_transaction_product (product_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    inventory_ensure_column($pdo, 'products', 'inventory_category_id', 'BIGINT(20) DEFAULT NULL');
    inventory_ensure_column($pdo, 'products', 'brand_id', 'BIGINT(20) DEFAULT NULL');
    inventory_ensure_column($pdo, 'products', 'serial_number', 'VARCHAR(100) DEFAULT NULL');
    inventory_ensure_column($pdo, 'products', 'warranty_end_date', 'DATE DEFAULT NULL');
    inventory_ensure_column($pdo, 'products', 'warehouse_id', 'BIGINT(20) DEFAULT NULL');
    inventory_ensure_column($pdo, 'products', 'barcode', 'VARCHAR(100) DEFAULT NULL');
    inventory_ensure_column($pdo, 'products', 'description', 'TEXT DEFAULT NULL');
    inventory_ensure_column($pdo, 'products', 'vat_rate', 'DECIMAL(5,2) NOT NULL DEFAULT 0.00');

    $categoryCount = (int) onyx_scalar('SELECT COUNT(*) FROM inventory_categories WHERE tenant_id = :tenant_id', ['tenant_id' => $tenantId], 0);
    if ($categoryCount === 0) {
        $insert = $pdo->prepare('INSERT INTO inventory_categories (tenant_id, name, created_at) VALUES (?, ?, NOW())');
        foreach (['CCTV Cameras', 'DVR/NVR', 'Solar Panels', 'Batteries', 'Inverters', 'Routers', 'Switches', 'Access Control Devices', 'Biometric Machines', 'Networking Cables'] as $name) {
            $insert->execute([$tenantId, $name]);
        }
    }

    $warehouseCount = (int) onyx_scalar('SELECT COUNT(*) FROM inventory_warehouses WHERE tenant_id = :tenant_id', ['tenant_id' => $tenantId], 0);
    if ($warehouseCount === 0) {
        $pdo->prepare('INSERT INTO inventory_warehouses (tenant_id, name, location, created_at) VALUES (?, ?, ?, NOW())')->execute([$tenantId, 'Main Warehouse', 'Head Office']);
    }
}

function inventory_stock_state(array $row): array
{
    $stock = (int) ($row['current_stock'] ?? 0);
    $min = max(0, (int) ($row['min_stock'] ?? 0));
    if ($stock <= 0) return ['out', 'Out'];
    if ($min > 0 && $stock <= $min) return ['low', 'Low'];
    return ['ok', 'Ready'];
}

inventory_ensure_schema($pdo, $tenant_id);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? null;

    if ($action === 'add_item') {
        $sku = trim($_POST['sku'] ?? '');
        $name = trim($_POST['name'] ?? '');
        if ($sku === '' || $name === '') inventory_redirect('SKU and item name are required.', false);

        $stmt = $pdo->prepare('INSERT INTO products (tenant_id, sku, barcode, name, inventory_category_id, brand_id, serial_number, warranty_end_date, warehouse_id, buying_price, selling_price, vat_rate, current_stock, min_stock, description, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())');
        $stmt->execute([
            $tenant_id,
            $sku,
            trim($_POST['barcode'] ?? ''),
            $name,
            ($_POST['inventory_category_id'] ?? '') !== '' ? (int) $_POST['inventory_category_id'] : null,
            ($_POST['brand_id'] ?? '') !== '' ? (int) $_POST['brand_id'] : null,
            trim($_POST['serial_number'] ?? ''),
            trim($_POST['warranty_end_date'] ?? '') ?: null,
            ($_POST['warehouse_id'] ?? '') !== '' ? (int) $_POST['warehouse_id'] : null,
            max(0, (float) ($_POST['buying_price'] ?? 0)),
            max(0, (float) ($_POST['selling_price'] ?? 0)),
            max(0, (float) ($_POST['vat_rate'] ?? 0)),
            max(0, (int) ($_POST['current_stock'] ?? 0)),
            max(0, (int) ($_POST['min_stock'] ?? 0)),
            trim($_POST['description'] ?? ''),
        ]);
        inventory_redirect('Inventory item added successfully.');
    }

    if ($action === 'add_category' || $action === 'add_brand') {
        $name = trim($_POST['name'] ?? '');
        if ($name === '') inventory_redirect('Name is required.', false);
        $table = $action === 'add_category' ? 'inventory_categories' : 'inventory_brands';
        $pdo->prepare("INSERT INTO {$table} (tenant_id, name, created_at) VALUES (?, ?, NOW())")->execute([$tenant_id, $name]);
        inventory_redirect($action === 'add_category' ? 'Inventory category added successfully.' : 'Brand added successfully.');
    }

    if ($action === 'add_warehouse') {
        $name = trim($_POST['name'] ?? '');
        if ($name === '') inventory_redirect('Warehouse name is required.', false);
        $pdo->prepare('INSERT INTO inventory_warehouses (tenant_id, name, location, created_at) VALUES (?, ?, ?, NOW())')->execute([$tenant_id, $name, trim($_POST['location'] ?? '')]);
        inventory_redirect('Warehouse added successfully.');
    }

    if (in_array($action, ['stock_receive', 'stock_transfer', 'stock_adjustment', 'stock_damage'], true)) {
        $product_id = (int) ($_POST['product_id'] ?? 0);
        $quantity = (int) ($_POST['quantity'] ?? 0);
        if ($product_id <= 0 || ($action !== 'stock_adjustment' && $quantity <= 0)) inventory_redirect('Please select a product and valid quantity.', false);

        if ($action === 'stock_receive') {
            $pdo->prepare('UPDATE products SET current_stock = current_stock + ?, warehouse_id = COALESCE(?, warehouse_id), updated_at = NOW() WHERE id = ? AND tenant_id = ?')->execute([$quantity, ($_POST['warehouse_id'] ?? '') !== '' ? (int) $_POST['warehouse_id'] : null, $product_id, $tenant_id]);
            $type = 'received';
        } elseif ($action === 'stock_damage') {
            $pdo->prepare('UPDATE products SET current_stock = GREATEST(0, current_stock - ?), updated_at = NOW() WHERE id = ? AND tenant_id = ?')->execute([$quantity, $product_id, $tenant_id]);
            $quantity *= -1;
            $type = 'damaged';
        } elseif ($action === 'stock_transfer') {
            $to = (int) ($_POST['to_warehouse_id'] ?? 0);
            if ($to <= 0) inventory_redirect('Please select a destination warehouse.', false);
            $pdo->prepare('UPDATE products SET warehouse_id = ?, updated_at = NOW() WHERE id = ? AND tenant_id = ?')->execute([$to, $product_id, $tenant_id]);
            $type = 'transfer';
        } else {
            $pdo->prepare('UPDATE products SET current_stock = GREATEST(0, current_stock + ?), updated_at = NOW() WHERE id = ? AND tenant_id = ?')->execute([$quantity, $product_id, $tenant_id]);
            $type = 'adjustment';
        }

        $pdo->prepare('INSERT INTO inventory_transactions (tenant_id, product_id, transaction_type, quantity, from_warehouse_id, to_warehouse_id, reference, notes, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())')
            ->execute([$tenant_id, $product_id, $type, $quantity, ($_POST['from_warehouse_id'] ?? '') !== '' ? (int) $_POST['from_warehouse_id'] : null, ($_POST['to_warehouse_id'] ?? '') !== '' ? (int) $_POST['to_warehouse_id'] : null, trim($_POST['reference'] ?? ''), trim($_POST['notes'] ?? '')]);
        inventory_redirect('Stock operation recorded successfully.');
    }
}

$context = onyx_page_start('Inventory', 'Stock control, warehouses, receipts, transfers, damages, valuation, and reorder monitoring.');
$currency = $context['currency'];

$products = onyx_rows(
    'SELECT p.id, p.name, p.sku, p.barcode, p.current_stock, p.min_stock, p.serial_number, p.warranty_end_date,
            p.buying_price, p.selling_price, p.vat_rate, p.description,
            p.current_stock * p.buying_price AS stock_value,
            c.name AS category_name, b.name AS brand_name, w.name AS warehouse_name, w.location AS warehouse_location
     FROM products p
     LEFT JOIN inventory_categories c ON c.id = p.inventory_category_id AND c.tenant_id = p.tenant_id
     LEFT JOIN inventory_brands b ON b.id = p.brand_id AND b.tenant_id = p.tenant_id
     LEFT JOIN inventory_warehouses w ON w.id = p.warehouse_id AND w.tenant_id = p.tenant_id
     WHERE p.tenant_id = :tenant_id
     ORDER BY p.name ASC',
    ['tenant_id' => $tenant_id]
);
$transactions = onyx_rows(
    'SELECT t.*, p.name AS product_name, p.sku, fw.name AS from_warehouse, tw.name AS to_warehouse
     FROM inventory_transactions t
     LEFT JOIN products p ON p.id = t.product_id AND p.tenant_id = t.tenant_id
     LEFT JOIN inventory_warehouses fw ON fw.id = t.from_warehouse_id AND fw.tenant_id = t.tenant_id
     LEFT JOIN inventory_warehouses tw ON tw.id = t.to_warehouse_id AND tw.tenant_id = t.tenant_id
     WHERE t.tenant_id = :tenant_id
     ORDER BY t.created_at DESC, t.id DESC
     LIMIT 40',
    ['tenant_id' => $tenant_id]
);
$categories = onyx_rows('SELECT id, name FROM inventory_categories WHERE tenant_id = :tenant_id ORDER BY name ASC', ['tenant_id' => $tenant_id]);
$brands = onyx_rows('SELECT id, name FROM inventory_brands WHERE tenant_id = :tenant_id ORDER BY name ASC', ['tenant_id' => $tenant_id]);
$warehouses = onyx_rows('SELECT id, name, location FROM inventory_warehouses WHERE tenant_id = :tenant_id ORDER BY name ASC', ['tenant_id' => $tenant_id]);
$items = onyx_rows('SELECT id, name, sku, current_stock FROM products WHERE tenant_id = :tenant_id ORDER BY name ASC', ['tenant_id' => $tenant_id]);

$inventory_value = 0.0;
$retail_value = 0.0;
$low_count = 0;
$out_count = 0;
foreach ($products as $product) {
    [$state] = inventory_stock_state($product);
    $inventory_value += max(0, (int) $product['current_stock']) * (float) $product['buying_price'];
    $retail_value += max(0, (int) $product['current_stock']) * (float) $product['selling_price'];
    if ($state === 'low') $low_count++;
    if ($state === 'out') $out_count++;
}
$message = $_GET['success'] ?? $_GET['error'] ?? '';
$message_type = isset($_GET['error']) ? 'error' : 'success';
?>

<style>
    .inventory-page,.inventory-page *{border-radius:0!important}.inventory-page{display:grid;gap:18px}.inventory-panel{background:var(--onyx-surface);border:1px solid var(--onyx-border);padding:18px;overflow:hidden}.inventory-title{color:var(--onyx-muted);font-size:11px;font-weight:900;letter-spacing:.8px;text-transform:uppercase}.inventory-muted{color:var(--onyx-muted);display:block;font-size:10px;margin-top:4px}.inventory-kpis{display:grid;gap:10px;grid-template-columns:repeat(5,minmax(0,1fr))}.inventory-kpi{background:var(--onyx-surface);border:1px solid var(--onyx-border);padding:14px}.inventory-kpi span{color:var(--onyx-muted);font-size:9px;font-weight:900;text-transform:uppercase}.inventory-kpi strong{color:#fff;display:block;font-size:16px;margin-top:8px;word-break:break-word}.inventory-grid{display:grid;gap:12px;grid-template-columns:repeat(12,minmax(0,1fr));margin-top:14px}.inventory-field{display:grid;gap:6px;grid-column:span 3}.inventory-field.wide{grid-column:span 6}.inventory-field.full{grid-column:span 12}.inventory-field label{color:var(--onyx-muted);font-size:10px;font-weight:900;text-transform:uppercase}.inventory-field input,.inventory-field select,.inventory-field textarea{background:#101016;border:1px solid rgba(255,255,255,.12);color:#fff;font-family:Poppins,system-ui,sans-serif;font-size:10px;min-height:38px;padding:8px 10px;width:100%}.inventory-field textarea{min-height:78px;resize:vertical}.inventory-field select option{background:#050506;color:#fff}.inventory-actions{align-items:center;display:flex;flex-wrap:wrap;gap:8px;justify-content:flex-end}.inventory-btn{align-items:center;background:rgba(255,255,255,.035);border:1px solid rgba(255,255,255,.1);color:#fff;cursor:pointer;display:inline-flex;font:inherit;font-size:10px;font-weight:900;gap:8px;min-height:38px;padding:0 12px;text-decoration:none;text-transform:uppercase}.inventory-btn.primary{background:#fff;color:#050506}.inventory-tabs{display:flex;flex-wrap:wrap;gap:8px}.inventory-tab{background:rgba(255,255,255,.035);border:1px solid rgba(255,255,255,.1);color:#fff;cursor:pointer;font-size:10px;font-weight:900;min-height:36px;padding:0 12px;text-transform:uppercase}.inventory-tab.active{background:#fff;color:#050506}.inventory-tab-panel{display:none}.inventory-tab-panel.active{display:block}.inventory-table-wrap{margin-top:14px;max-width:calc(100vw - 340px);overflow-x:auto;padding-bottom:14px}.inventory-table{border-collapse:collapse;table-layout:fixed;width:1380px}.inventory-table.compact{width:1120px}.inventory-table th,.inventory-table td{border-bottom:1px solid rgba(255,255,255,.06);font-size:10px;padding:9px;text-align:left;vertical-align:top}.inventory-table th{color:var(--onyx-muted);font-size:9px;font-weight:900;text-transform:uppercase}.inventory-name strong{color:#fff;display:block;font-size:11px}.inventory-name span{color:var(--onyx-muted);display:block;font-size:9px;margin-top:3px}.inventory-badge{border:1px solid rgba(255,255,255,.12);display:inline-flex;font-size:9px;font-weight:900;padding:4px 7px;text-transform:uppercase}.inventory-badge.ok{color:#8ff0c3}.inventory-badge.low{color:#ffd27a}.inventory-badge.out,.inventory-alert.error{color:#ff8a8a}.inventory-alert{border:1px solid rgba(143,240,195,.24);color:#8ff0c3;font-size:11px;font-weight:800;padding:11px 12px}.inventory-empty{border:1px solid rgba(255,255,255,.08);color:var(--onyx-muted);padding:14px}@media(max-width:1180px){.inventory-kpis{grid-template-columns:repeat(2,1fr)}.inventory-field,.inventory-field.wide{grid-column:span 6}.inventory-table-wrap{max-width:calc(100vw - 36px)}}@media(max-width:680px){.inventory-kpis{grid-template-columns:1fr}.inventory-field,.inventory-field.wide{grid-column:span 12}.inventory-actions{justify-content:stretch}.inventory-btn{justify-content:center;width:100%}}
</style>

<div class="inventory-page">
    <?php if ($message !== ''): ?><section class="inventory-panel"><div class="inventory-alert <?= $message_type === 'error' ? 'error' : '' ?>"><?= inventory_h($message) ?></div></section><?php endif; ?>

    <section class="inventory-kpis">
        <div class="inventory-kpi"><span>Tracked Items</span><strong><?= inventory_h(count($products)) ?></strong></div>
        <div class="inventory-kpi"><span>Low Stock</span><strong><?= inventory_h($low_count) ?></strong></div>
        <div class="inventory-kpi"><span>Out of Stock</span><strong><?= inventory_h($out_count) ?></strong></div>
        <div class="inventory-kpi"><span>Cost Value</span><strong><?= inventory_h(onyx_money($inventory_value, $currency)) ?></strong></div>
        <div class="inventory-kpi"><span>Retail Value</span><strong><?= inventory_h(onyx_money($retail_value, $currency)) ?></strong></div>
    </section>

    <section class="inventory-panel">
        <div class="inventory-title">Inventory Controls</div>
        <span class="inventory-muted">Add stock items, setup masters, receive goods, transfer warehouse locations, record damages, and correct counts.</span>
        <div class="inventory-tabs" style="margin-top:14px;">
            <button class="inventory-tab active" type="button" data-inventory-tab="add">Add Item</button>
            <button class="inventory-tab" type="button" data-inventory-tab="movement">Stock Operations</button>
            <button class="inventory-tab" type="button" data-inventory-tab="setup">Setup</button>
        </div>
    </section>

    <section class="inventory-panel inventory-tab-panel active" data-inventory-panel="add">
        <div class="inventory-title">Add Inventory Item</div>
        <form method="POST" action="inventory.php">
            <input type="hidden" name="action" value="add_item">
            <div class="inventory-grid">
                <div class="inventory-field"><label>SKU *</label><input name="sku" required></div>
                <div class="inventory-field"><label>Barcode</label><input name="barcode"></div>
                <div class="inventory-field wide"><label>Item Name *</label><input name="name" required></div>
                <div class="inventory-field"><label>Category</label><select name="inventory_category_id"><option value="">Select category</option><?php foreach ($categories as $c): ?><option value="<?= inventory_h($c['id']) ?>"><?= inventory_h($c['name']) ?></option><?php endforeach; ?></select></div>
                <div class="inventory-field"><label>Brand</label><select name="brand_id"><option value="">Select brand</option><?php foreach ($brands as $b): ?><option value="<?= inventory_h($b['id']) ?>"><?= inventory_h($b['name']) ?></option><?php endforeach; ?></select></div>
                <div class="inventory-field"><label>Warehouse</label><select name="warehouse_id"><option value="">Select warehouse</option><?php foreach ($warehouses as $w): ?><option value="<?= inventory_h($w['id']) ?>"><?= inventory_h($w['name']) ?></option><?php endforeach; ?></select></div>
                <div class="inventory-field"><label>Serial Number</label><input name="serial_number"></div>
                <div class="inventory-field"><label>Warranty End</label><input type="date" name="warranty_end_date"></div>
                <div class="inventory-field"><label>Cost Price</label><input type="number" step="0.01" min="0" name="buying_price" value="0.00"></div>
                <div class="inventory-field"><label>Selling Price</label><input type="number" step="0.01" min="0" name="selling_price" value="0.00"></div>
                <div class="inventory-field"><label>VAT %</label><input type="number" step="0.01" min="0" name="vat_rate" value="0.00"></div>
                <div class="inventory-field"><label>Current Stock</label><input type="number" step="1" min="0" name="current_stock" value="0"></div>
                <div class="inventory-field"><label>Reorder Level</label><input type="number" step="1" min="0" name="min_stock" value="0"></div>
                <div class="inventory-field full"><label>Description</label><textarea name="description"></textarea></div>
            </div>
            <div class="inventory-actions" style="margin-top:14px;"><button class="inventory-btn primary" type="submit"><i class="fa-solid fa-floppy-disk"></i> Save Item</button></div>
        </form>
    </section>

    <section class="inventory-panel inventory-tab-panel" data-inventory-panel="movement">
        <div class="inventory-title">Stock Operations</div>
        <form method="POST" action="inventory.php">
            <div class="inventory-grid">
                <div class="inventory-field"><label>Operation</label><select name="action"><option value="stock_receive">Goods Received</option><option value="stock_transfer">Warehouse Transfer</option><option value="stock_adjustment">Stock Adjustment</option><option value="stock_damage">Damaged Stock</option></select></div>
                <div class="inventory-field wide"><label>Product</label><select name="product_id" required><option value="">Select product</option><?php foreach ($items as $item): ?><option value="<?= inventory_h($item['id']) ?>"><?= inventory_h($item['name'] . ' (' . $item['sku'] . ') - Stock: ' . $item['current_stock']) ?></option><?php endforeach; ?></select></div>
                <div class="inventory-field"><label>Quantity</label><input type="number" step="1" name="quantity" value="1"></div>
                <div class="inventory-field"><label>From Warehouse</label><select name="from_warehouse_id"><option value="">Not applicable</option><?php foreach ($warehouses as $w): ?><option value="<?= inventory_h($w['id']) ?>"><?= inventory_h($w['name']) ?></option><?php endforeach; ?></select></div>
                <div class="inventory-field"><label>To / Receive Warehouse</label><select name="to_warehouse_id"><option value="">Not applicable</option><?php foreach ($warehouses as $w): ?><option value="<?= inventory_h($w['id']) ?>"><?= inventory_h($w['name']) ?></option><?php endforeach; ?></select></div>
                <div class="inventory-field"><label>Receive Warehouse</label><select name="warehouse_id"><option value="">Keep current</option><?php foreach ($warehouses as $w): ?><option value="<?= inventory_h($w['id']) ?>"><?= inventory_h($w['name']) ?></option><?php endforeach; ?></select></div>
                <div class="inventory-field wide"><label>Reference</label><input name="reference" placeholder="GRN, transfer, stock count, damage note"></div>
                <div class="inventory-field full"><label>Notes</label><textarea name="notes"></textarea></div>
            </div>
            <div class="inventory-actions" style="margin-top:14px;"><button class="inventory-btn primary" type="submit"><i class="fa-solid fa-check"></i> Record Operation</button></div>
        </form>
    </section>

    <section class="inventory-panel inventory-tab-panel" data-inventory-panel="setup">
        <div class="inventory-title">Inventory Setup</div>
        <div class="inventory-grid">
            <form class="inventory-field wide" method="POST" action="inventory.php"><input type="hidden" name="action" value="add_category"><label>New Category</label><input name="name"><button class="inventory-btn" type="submit">Add Category</button></form>
            <form class="inventory-field wide" method="POST" action="inventory.php"><input type="hidden" name="action" value="add_brand"><label>New Brand</label><input name="name"><button class="inventory-btn" type="submit">Add Brand</button></form>
            <form class="inventory-field full" method="POST" action="inventory.php"><input type="hidden" name="action" value="add_warehouse"><label>New Warehouse</label><input name="name"><label>Location</label><input name="location"><button class="inventory-btn" type="submit">Add Warehouse</button></form>
        </div>
    </section>

    <section class="inventory-panel">
        <div class="inventory-title">Current Inventory Register</div>
        <div class="inventory-table-wrap">
            <table class="inventory-table">
                <thead><tr><th>Item</th><th>Category</th><th>Brand</th><th>Warehouse</th><th>Serial / Warranty</th><th>Cost</th><th>Selling</th><th>Stock</th><th>Value</th><th>Tax</th></tr></thead>
                <tbody>
                    <?php if ($products === []): ?><tr><td colspan="10"><div class="inventory-empty">No inventory items registered yet.</div></td></tr><?php endif; ?>
                    <?php foreach ($products as $product): ?><?php [$state, $label] = inventory_stock_state($product); ?>
                        <tr>
                            <td><div class="inventory-name"><strong><?= inventory_h($product['name']) ?></strong><span><?= inventory_h($product['sku'] . ' / ' . ($product['barcode'] ?: 'No barcode')) ?></span></div></td>
                            <td><?= inventory_h($product['category_name'] ?: '-') ?></td>
                            <td><?= inventory_h($product['brand_name'] ?: '-') ?></td>
                            <td><?= inventory_h($product['warehouse_name'] ?: '-') ?><span class="inventory-muted"><?= inventory_h($product['warehouse_location'] ?: '') ?></span></td>
                            <td><?= inventory_h($product['serial_number'] ?: '-') ?><span class="inventory-muted">Warranty: <?= inventory_h($product['warranty_end_date'] ?: '-') ?></span></td>
                            <td><?= inventory_h(onyx_money((float) $product['buying_price'], $currency)) ?></td>
                            <td><?= inventory_h(onyx_money((float) $product['selling_price'], $currency)) ?></td>
                            <td><span class="inventory-badge <?= inventory_h($state) ?>"><?= inventory_h($label) ?></span><span class="inventory-muted"><?= inventory_h($product['current_stock']) ?> on hand / min <?= inventory_h($product['min_stock']) ?></span></td>
                            <td><?= inventory_h(onyx_money((float) $product['stock_value'], $currency)) ?></td>
                            <td><?= inventory_h(number_format((float) $product['vat_rate'], 2)) ?>%</td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>

    <section class="inventory-panel">
        <div class="inventory-title">Recent Stock Ledger</div>
        <div class="inventory-table-wrap">
            <table class="inventory-table compact">
                <thead><tr><th>Date</th><th>Product</th><th>Type</th><th>Quantity</th><th>Warehouse</th><th>Reference</th><th>Notes</th></tr></thead>
                <tbody>
                    <?php if ($transactions === []): ?><tr><td colspan="7"><div class="inventory-empty">No stock movements recorded yet.</div></td></tr><?php endif; ?>
                    <?php foreach ($transactions as $tx): ?>
                        <tr><td><?= inventory_h($tx['created_at']) ?></td><td><?= inventory_h(($tx['product_name'] ?: 'Unknown') . ' / ' . ($tx['sku'] ?: '-')) ?></td><td><?= inventory_h($tx['transaction_type']) ?></td><td><?= inventory_h($tx['quantity']) ?></td><td><?= inventory_h(($tx['from_warehouse'] ?: '-') . ' -> ' . ($tx['to_warehouse'] ?: '-')) ?></td><td><?= inventory_h($tx['reference'] ?: '-') ?></td><td><?= inventory_h($tx['notes'] ?: '-') ?></td></tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const tabs = Array.from(document.querySelectorAll('[data-inventory-tab]'));
    const panels = Array.from(document.querySelectorAll('[data-inventory-panel]'));
    tabs.forEach(function (tab) {
        tab.addEventListener('click', function () {
            tabs.forEach(function (item) { item.classList.toggle('active', item === tab); });
            panels.forEach(function (panel) { panel.classList.toggle('active', panel.dataset.inventoryPanel === tab.dataset.inventoryTab); });
        });
    });
});
</script>

<?php onyx_page_end(); ?>
