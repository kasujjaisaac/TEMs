<?php
require_once __DIR__ . '/includes/erp_layout.php';

$tenant_id = (int) (onyx_tenant_id() ?? 0);
$pdo = onyx_db();

function inventory_redirect(string $msg = '', bool $success = true): void
{
    $query = $msg !== '' ? ($success ? '?success=' : '?error=') . urlencode($msg) : '';
    header('Location: inventory.php' . $query);
    exit();
}

function ensure_inventory_column(PDO $pdo, string $table, string $column, string $definition): void
{
    $stmt = $pdo->query("SHOW COLUMNS FROM `{$table}` LIKE '{$column}'");
    if ($stmt && $stmt->rowCount() === 0) {
        $pdo->exec("ALTER TABLE `{$table}` ADD COLUMN `{$column}` {$definition}");
    }
}

function ensure_inventory_schema(PDO $pdo, int $tenant_id): void
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
        KEY idx_inventory_transaction_tenant (tenant_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    ensure_inventory_column($pdo, 'products', 'inventory_category_id', 'BIGINT(20) DEFAULT NULL');
    ensure_inventory_column($pdo, 'products', 'brand_id', 'BIGINT(20) DEFAULT NULL');
    ensure_inventory_column($pdo, 'products', 'serial_number', 'VARCHAR(100) DEFAULT NULL');
    ensure_inventory_column($pdo, 'products', 'warranty_end_date', 'DATE DEFAULT NULL');
    ensure_inventory_column($pdo, 'products', 'warehouse_id', 'BIGINT(20) DEFAULT NULL');
    ensure_inventory_column($pdo, 'products', 'barcode', 'VARCHAR(100) DEFAULT NULL');

    $categoryStmt = $pdo->prepare('SELECT COUNT(*) FROM inventory_categories WHERE tenant_id = ?');
    $categoryStmt->execute([$tenant_id]);
    $categoryCount = (int) $categoryStmt->fetchColumn();
    if ($categoryCount === 0) {
        $defaults = ['CCTV Cameras','DVR/NVR','Solar Panels','Batteries','Inverters','Routers','Switches','Access Control Devices','Biometric Machines','Networking Cables'];
        $insertCategory = $pdo->prepare('INSERT INTO inventory_categories (tenant_id, name, created_at) VALUES (?, ?, NOW())');
        foreach ($defaults as $name) {
            $insertCategory->execute([$tenant_id, $name]);
        }
    }

    $warehouseStmt = $pdo->prepare('SELECT COUNT(*) FROM inventory_warehouses WHERE tenant_id = ?');
    $warehouseStmt->execute([$tenant_id]);
    $warehouseCount = (int) $warehouseStmt->fetchColumn();
    if ($warehouseCount === 0) {
        $pdo->prepare('INSERT INTO inventory_warehouses (tenant_id, name, location, created_at) VALUES (?, ?, ?, NOW())')->execute([$tenant_id, 'Main Warehouse', 'Head Office']);
    }
}

ensure_inventory_schema($pdo, $tenant_id);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? null;
    if ($action === 'add_item') {
        $sku = trim($_POST['sku'] ?? '');
        $name = trim($_POST['name'] ?? '');
        $barcode = trim($_POST['barcode'] ?? '');
        $serial_number = trim($_POST['serial_number'] ?? '');
        $warranty_end_date = trim($_POST['warranty_end_date'] ?? '');
        $buying_price = (float) ($_POST['buying_price'] ?? 0);
        $selling_price = (float) ($_POST['selling_price'] ?? 0);
        $current_stock = (int) ($_POST['current_stock'] ?? 0);
        $min_stock = (int) ($_POST['min_stock'] ?? 0);
        $inventory_category_id = isset($_POST['inventory_category_id']) && $_POST['inventory_category_id'] !== '' ? (int) $_POST['inventory_category_id'] : null;
        $brand_id = isset($_POST['brand_id']) && $_POST['brand_id'] !== '' ? (int) $_POST['brand_id'] : null;
        $warehouse_id = isset($_POST['warehouse_id']) && $_POST['warehouse_id'] !== '' ? (int) $_POST['warehouse_id'] : null;
        if ($sku === '' || $name === '') {
            inventory_redirect('SKU and item name are required.', false);
        }
        $stmt = $pdo->prepare('INSERT INTO products (tenant_id, sku, barcode, name, inventory_category_id, brand_id, serial_number, warranty_end_date, warehouse_id, buying_price, selling_price, current_stock, min_stock, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())');
        $stmt->execute([$tenant_id, $sku, $barcode, $name, $inventory_category_id, $brand_id, $serial_number, $warranty_end_date ?: null, $warehouse_id, $buying_price, $selling_price, $current_stock, $min_stock]);
        inventory_redirect('Inventory item added successfully.');
    }

    if ($action === 'add_category') {
        $name = trim($_POST['name'] ?? '');
        if ($name === '') inventory_redirect('Category name is required.', false);
        $stmt = $pdo->prepare('INSERT INTO inventory_categories (tenant_id, name, created_at) VALUES (?, ?, NOW())');
        $stmt->execute([$tenant_id, $name]);
        inventory_redirect('Inventory category added successfully.');
    }

    if ($action === 'add_brand') {
        $name = trim($_POST['name'] ?? '');
        if ($name === '') inventory_redirect('Brand name is required.', false);
        $stmt = $pdo->prepare('INSERT INTO inventory_brands (tenant_id, name, created_at) VALUES (?, ?, NOW())');
        $stmt->execute([$tenant_id, $name]);
        inventory_redirect('Brand added successfully.');
    }

    if ($action === 'add_warehouse') {
        $name = trim($_POST['name'] ?? '');
        $location = trim($_POST['location'] ?? '');
        if ($name === '') inventory_redirect('Warehouse name is required.', false);
        $stmt = $pdo->prepare('INSERT INTO inventory_warehouses (tenant_id, name, location, created_at) VALUES (?, ?, ?, NOW())');
        $stmt->execute([$tenant_id, $name, $location]);
        inventory_redirect('Warehouse added successfully.');
    }

    if ($action === 'stock_receive') {
        $product_id = (int) ($_POST['product_id'] ?? 0);
        $quantity = (int) ($_POST['quantity'] ?? 0);
        $reference = trim($_POST['reference'] ?? '');
        $notes = trim($_POST['notes'] ?? '');
        if ($product_id <= 0 || $quantity <= 0) inventory_redirect('Please select a product and a valid quantity.', false);
        $pdo->prepare('UPDATE products SET current_stock = current_stock + ?, updated_at = NOW() WHERE id = ? AND tenant_id = ?')->execute([$quantity, $product_id, $tenant_id]);
        $pdo->prepare('INSERT INTO inventory_transactions (tenant_id, product_id, transaction_type, quantity, reference, notes, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())')->execute([$tenant_id, $product_id, 'received', $quantity, $reference, $notes]);
        inventory_redirect('Stock received successfully.');
    }

    if ($action === 'stock_transfer') {
        $product_id = (int) ($_POST['product_id'] ?? 0);
        $quantity = (int) ($_POST['quantity'] ?? 0);
        $to_warehouse_id = (int) ($_POST['to_warehouse_id'] ?? 0);
        if ($product_id <= 0 || $quantity <= 0 || $to_warehouse_id <= 0) inventory_redirect('Please complete all transfer fields.', false);
        $pdo->prepare('UPDATE products SET warehouse_id = ?, updated_at = NOW() WHERE id = ? AND tenant_id = ?')->execute([$to_warehouse_id, $product_id, $tenant_id]);
        $pdo->prepare('INSERT INTO inventory_transactions (tenant_id, product_id, transaction_type, quantity, to_warehouse_id, reference, notes, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())')->execute([$tenant_id, $product_id, 'transfer', $quantity, $to_warehouse_id, trim($_POST['reference'] ?? ''), trim($_POST['notes'] ?? '')]);
        inventory_redirect('Stock transfer recorded.');
    }

    if ($action === 'stock_adjustment') {
        $product_id = (int) ($_POST['product_id'] ?? 0);
        $quantity = (int) ($_POST['quantity'] ?? 0);
        if ($product_id <= 0) inventory_redirect('Please select a product.', false);
        $pdo->prepare('UPDATE products SET current_stock = current_stock + ?, updated_at = NOW() WHERE id = ? AND tenant_id = ?')->execute([$quantity, $product_id, $tenant_id]);
        $pdo->prepare('INSERT INTO inventory_transactions (tenant_id, product_id, transaction_type, quantity, reference, notes, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())')->execute([$tenant_id, $product_id, 'adjustment', $quantity, trim($_POST['reference'] ?? ''), trim($_POST['notes'] ?? '')]);
        inventory_redirect('Stock adjustment applied.');
    }

    if ($action === 'stock_damage') {
        $product_id = (int) ($_POST['product_id'] ?? 0);
        $quantity = (int) ($_POST['quantity'] ?? 0);
        if ($product_id <= 0 || $quantity <= 0) inventory_redirect('Please select a product and a valid quantity.', false);
        $pdo->prepare('UPDATE products SET current_stock = current_stock - ?, updated_at = NOW() WHERE id = ? AND tenant_id = ?')->execute([$quantity, $product_id, $tenant_id]);
        $pdo->prepare('INSERT INTO inventory_transactions (tenant_id, product_id, transaction_type, quantity, reference, notes, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())')->execute([$tenant_id, $product_id, 'damaged', $quantity, trim($_POST['reference'] ?? ''), trim($_POST['notes'] ?? '')]);
        inventory_redirect('Damaged stock recorded.');
    }
}

$context = onyx_page_start('Inventory', 'Equipment inventory management for categories, brands, warranties, warehouses, stock movements, and valuation.');
$currency = $context['currency'];

$stock_rows = array_map(
    static fn (array $row): array => [
        $row['name'],
        $row['sku'],
        $row['category_name'] ?: '-',
        $row['brand_name'] ?: '-',
        $row['serial_number'] ?: '-',
        $row['warranty_end_date'] ?: '-',
        $row['warehouse_name'] ?: '-',
        $row['current_stock'],
        onyx_money((float) $row['stock_value'], $currency),
    ],
    onyx_rows(
        'SELECT p.id, p.name, p.sku, p.current_stock, p.min_stock, p.serial_number, p.warranty_end_date, p.barcode,
                p.buying_price, p.current_stock * p.buying_price AS stock_value,
                c.name AS category_name,
                b.name AS brand_name,
                w.name AS warehouse_name
         FROM products p
         LEFT JOIN inventory_categories c ON c.id = p.inventory_category_id AND c.tenant_id = p.tenant_id
         LEFT JOIN inventory_brands b ON b.id = p.brand_id AND b.tenant_id = p.tenant_id
         LEFT JOIN inventory_warehouses w ON w.id = p.warehouse_id AND w.tenant_id = p.tenant_id
         WHERE p.tenant_id = :tenant_id
         ORDER BY p.name ASC',
        ['tenant_id' => $tenant_id]
    )
);
$inventory_value = (float) onyx_scalar(
    'SELECT COALESCE(SUM(current_stock * buying_price), 0) FROM products WHERE tenant_id = :tenant_id',
    ['tenant_id' => $tenant_id],
    0
);
$low_stock_count = (int) onyx_scalar(
    'SELECT COUNT(*) FROM products WHERE tenant_id = :tenant_id AND current_stock <= min_stock',
    ['tenant_id' => $tenant_id]
);
$inventory_items = onyx_rows('SELECT id, name, sku FROM products WHERE tenant_id = :tenant_id ORDER BY name ASC', ['tenant_id' => $tenant_id]);
$categories = onyx_rows('SELECT id, name FROM inventory_categories WHERE tenant_id = :tenant_id ORDER BY name ASC', ['tenant_id' => $tenant_id]);
$brands = onyx_rows('SELECT id, name FROM inventory_brands WHERE tenant_id = :tenant_id ORDER BY name ASC', ['tenant_id' => $tenant_id]);
$warehouses = onyx_rows('SELECT id, name, location FROM inventory_warehouses WHERE tenant_id = :tenant_id ORDER BY name ASC', ['tenant_id' => $tenant_id]);
$message = $_GET['success'] ?? $_GET['error'] ?? '';
$message_type = isset($_GET['error']) ? 'error' : 'success';
?>

<div class="module-grid">
    <?php if ($message !== ''): ?>
        <div class="panel span-12" style="margin-bottom:12px;">
            <div class="<?= $message_type === 'error' ? 'text-danger' : 'text-success' ?>"><?= htmlspecialchars($message) ?></div>
        </div>
    <?php endif; ?>

    <?php onyx_panel_start('Inventory Summary', 'fa-warehouse', 'span-12'); ?>
        <div class="profile-grid">
            <div class="profile-card"><strong><?= count($stock_rows) ?></strong><span>Tracked inventory items</span></div>
            <div class="profile-card"><strong><?= $low_stock_count ?></strong><span>Items below reorder level</span></div>
            <div class="profile-card"><strong><?= onyx_money($inventory_value, $currency) ?></strong><span>Current stock valuation</span></div>
            <div class="profile-card"><strong><?= count($warehouses) ?></strong><span>Active warehouses</span></div>
        </div>
    <?php onyx_panel_end(); ?>

    <?php onyx_panel_start('Add Inventory Item', 'fa-plus', 'span-6'); ?>
        <form method="POST" action="inventory.php">
            <input type="hidden" name="action" value="add_item">
            <label>SKU</label><br>
            <input type="text" name="sku" required style="width:100%;padding:8px;margin:6px 0;">
            <label>Item Name</label><br>
            <input type="text" name="name" required style="width:100%;padding:8px;margin:6px 0;">
            <label>Barcode</label><br>
            <input type="text" name="barcode" style="width:100%;padding:8px;margin:6px 0;">
            <label>Serial Number</label><br>
            <input type="text" name="serial_number" style="width:100%;padding:8px;margin:6px 0;">
            <label>Warranty End Date</label><br>
            <input type="date" name="warranty_end_date" style="width:100%;padding:8px;margin:6px 0;">
            <label>Category</label><br>
            <select name="inventory_category_id" style="width:100%;padding:8px;margin:6px 0;">
                <option value="">-- select category --</option>
                <?php foreach ($categories as $category): ?>
                    <option value="<?= htmlspecialchars((string) $category['id']) ?>"><?= htmlspecialchars($category['name']) ?></option>
                <?php endforeach; ?>
            </select>
            <label>Brand</label><br>
            <select name="brand_id" style="width:100%;padding:8px;margin:6px 0;">
                <option value="">-- select brand --</option>
                <?php foreach ($brands as $brand): ?>
                    <option value="<?= htmlspecialchars((string) $brand['id']) ?>"><?= htmlspecialchars($brand['name']) ?></option>
                <?php endforeach; ?>
            </select>
            <label>Warehouse</label><br>
            <select name="warehouse_id" style="width:100%;padding:8px;margin:6px 0;">
                <option value="">-- select warehouse --</option>
                <?php foreach ($warehouses as $warehouse): ?>
                    <option value="<?= htmlspecialchars((string) $warehouse['id']) ?>"><?= htmlspecialchars($warehouse['name']) ?></option>
                <?php endforeach; ?>
            </select>
            <label>Cost Price</label><br>
            <input type="number" step="0.01" name="buying_price" value="0.00" style="width:100%;padding:8px;margin:6px 0;">
            <label>Selling Price</label><br>
            <input type="number" step="0.01" name="selling_price" value="0.00" style="width:100%;padding:8px;margin:6px 0;">
            <label>Current Stock</label><br>
            <input type="number" step="1" name="current_stock" value="0" style="width:100%;padding:8px;margin:6px 0;">
            <label>Reorder Level</label><br>
            <input type="number" step="1" name="min_stock" value="0" style="width:100%;padding:8px;margin:6px 0;">
            <button class="action-btn" type="submit" style="margin-top:8px;">Save Item</button>
        </form>
    <?php onyx_panel_end(); ?>

    <?php onyx_panel_start('Setup & Maintenance', 'fa-screwdriver-wrench', 'span-6'); ?>
        <div style="display:grid; gap:12px;">
            <form method="POST" action="inventory.php">
                <input type="hidden" name="action" value="add_category">
                <label>New Category</label><br>
                <input type="text" name="name" style="width:100%;padding:8px;margin:6px 0;">
                <button class="action-btn" type="submit">Add Category</button>
            </form>
            <form method="POST" action="inventory.php">
                <input type="hidden" name="action" value="add_brand">
                <label>New Brand</label><br>
                <input type="text" name="name" style="width:100%;padding:8px;margin:6px 0;">
                <button class="action-btn" type="submit">Add Brand</button>
            </form>
            <form method="POST" action="inventory.php">
                <input type="hidden" name="action" value="add_warehouse">
                <label>New Warehouse</label><br>
                <input type="text" name="name" style="width:100%;padding:8px;margin:6px 0;">
                <label>Location</label><br>
                <input type="text" name="location" style="width:100%;padding:8px;margin:6px 0;">
                <button class="action-btn" type="submit">Add Warehouse</button>
            </form>
        </div>
    <?php onyx_panel_end(); ?>

    <?php onyx_panel_start('Stock Operations', 'fa-right-left', 'span-12'); ?>
        <div class="profile-grid">
            <div class="profile-card">
                <strong>Goods Received</strong>
                <form method="POST" action="inventory.php" style="margin-top:8px;">
                    <input type="hidden" name="action" value="stock_receive">
                    <select name="product_id" style="width:100%;padding:8px;margin:6px 0;">
                        <option value="">-- select item --</option>
                        <?php foreach ($inventory_items as $item): ?>
                            <option value="<?= htmlspecialchars((string) $item['id']) ?>"><?= htmlspecialchars($item['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <input type="number" step="1" name="quantity" value="1" style="width:100%;padding:8px;margin:6px 0;">
                    <input type="text" name="reference" placeholder="Reference" style="width:100%;padding:8px;margin:6px 0;">
                    <input type="text" name="notes" placeholder="Notes" style="width:100%;padding:8px;margin:6px 0;">
                    <button class="action-btn" type="submit">Record Receipt</button>
                </form>
            </div>
            <div class="profile-card">
                <strong>Stock Transfer</strong>
                <form method="POST" action="inventory.php" style="margin-top:8px;">
                    <input type="hidden" name="action" value="stock_transfer">
                    <select name="product_id" style="width:100%;padding:8px;margin:6px 0;">
                        <option value="">-- select item --</option>
                        <?php foreach ($inventory_items as $item): ?>
                            <option value="<?= htmlspecialchars((string) $item['id']) ?>"><?= htmlspecialchars($item['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <select name="to_warehouse_id" style="width:100%;padding:8px;margin:6px 0;">
                        <option value="">-- select destination --</option>
                        <?php foreach ($warehouses as $warehouse): ?>
                            <option value="<?= htmlspecialchars((string) $warehouse['id']) ?>"><?= htmlspecialchars($warehouse['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <input type="number" step="1" name="quantity" value="1" style="width:100%;padding:8px;margin:6px 0;">
                    <input type="text" name="reference" placeholder="Transfer reference" style="width:100%;padding:8px;margin:6px 0;">
                    <button class="action-btn" type="submit">Record Transfer</button>
                </form>
            </div>
            <div class="profile-card">
                <strong>Stock Adjustments</strong>
                <form method="POST" action="inventory.php" style="margin-top:8px;">
                    <input type="hidden" name="action" value="stock_adjustment">
                    <select name="product_id" style="width:100%;padding:8px;margin:6px 0;">
                        <option value="">-- select item --</option>
                        <?php foreach ($inventory_items as $item): ?>
                            <option value="<?= htmlspecialchars((string) $item['id']) ?>"><?= htmlspecialchars($item['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <input type="number" step="1" name="quantity" value="0" style="width:100%;padding:8px;margin:6px 0;">
                    <input type="text" name="reference" placeholder="Adjustment reference" style="width:100%;padding:8px;margin:6px 0;">
                    <button class="action-btn" type="submit">Apply Adjustment</button>
                </form>
            </div>
            <div class="profile-card">
                <strong>Damaged Stock</strong>
                <form method="POST" action="inventory.php" style="margin-top:8px;">
                    <input type="hidden" name="action" value="stock_damage">
                    <select name="product_id" style="width:100%;padding:8px;margin:6px 0;">
                        <option value="">-- select item --</option>
                        <?php foreach ($inventory_items as $item): ?>
                            <option value="<?= htmlspecialchars((string) $item['id']) ?>"><?= htmlspecialchars($item['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <input type="number" step="1" name="quantity" value="1" style="width:100%;padding:8px;margin:6px 0;">
                    <input type="text" name="reference" placeholder="Damage reference" style="width:100%;padding:8px;margin:6px 0;">
                    <button class="action-btn" type="submit">Record Damage</button>
                </form>
            </div>
        </div>
    <?php onyx_panel_end(); ?>

    <?php onyx_panel_start('Current Inventory', 'fa-boxes-stacked', 'span-12'); ?>
        <?php onyx_table(
            ['Product', 'SKU', 'Category', 'Brand', 'Serial', 'Warranty', 'Warehouse', 'Stock', 'Value'],
            $stock_rows
        ); ?>
    <?php onyx_panel_end(); ?>
</div>

<?php onyx_page_end(); ?>
