<?php

$action = $_GET['action'] ?? $_POST['action'] ?? null;
$tenant_id = onyx_tenant_id();
$pdo = onyx_db();

function slugify_category($value) {
    $value = strtolower(trim((string) $value));
    $value = preg_replace('/[^a-z0-9]+/', '-', $value);
    return trim((string) $value, '-') ?: 'category';
}

function redirect_back($msg = '', $success = true) {
    $q = '';
    if ($msg !== '') $q = ($success ? '?success=' : '?error=') . urlencode($msg);
    header('Location: products.php' . $q);
    exit();
}

if (!$action) {
    redirect_back('No action specified.', false);
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if ($action === 'add') {
        require_permission('manage_products');
        $categories = onyx_rows('SELECT id, name FROM product_categories WHERE tenant_id = :tenant_id ORDER BY name ASC', ['tenant_id' => $tenant_id]);
        $income_categories = onyx_rows('SELECT id, name FROM income_categories WHERE tenant_id = :tenant_id ORDER BY name ASC', ['tenant_id' => $tenant_id]);
        $expense_categories = onyx_rows('SELECT id, name FROM expense_categories WHERE tenant_id = :tenant_id ORDER BY name ASC', ['tenant_id' => $tenant_id]);
        $context = onyx_page_start('Add Product', 'Create a new product record.');
        ?>
        <form method="POST" action="products_action.php">
            <input type="hidden" name="action" value="add">
            <div class="panel span-6">
                <div class="panel-title"><i class="fa-solid fa-plus"></i> Add Product</div>
                <div style="margin-top:12px;">
                    <label>SKU</label><br>
                    <input type="text" name="sku" required style="width:100%;padding:8px;margin:6px 0;">
                    <label>Name</label><br>
                    <input type="text" name="name" required style="width:100%;padding:8px;margin:6px 0;">
                    <label>Product Category</label><br>
                    <select name="product_category_id" style="width:100%;padding:8px;margin:6px 0;">
                        <option value="">-- none --</option>
                        <?php foreach ($categories as $c): ?>
                            <option value="<?= htmlspecialchars($c['id']) ?>"><?= htmlspecialchars($c['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <div style="margin:-4px 0 10px 0;"><a href="products_action.php?action=add_category&type=product" style="font-size:12px;">+ Add new product category</a></div>
                    <label>Income Category</label><br>
                    <select name="income_category_id" style="width:100%;padding:8px;margin:6px 0;">
                        <option value="">-- none --</option>
                        <?php foreach ($income_categories as $c): ?>
                            <option value="<?= htmlspecialchars($c['id']) ?>"><?= htmlspecialchars($c['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <div style="margin:-4px 0 10px 0;"><a href="products_action.php?action=add_category&type=income" style="font-size:12px;">+ Add new income category</a></div>
                    <label>Expense Category</label><br>
                    <select name="expense_category_id" style="width:100%;padding:8px;margin:6px 0;">
                        <option value="">-- none --</option>
                        <?php foreach ($expense_categories as $c): ?>
                            <option value="<?= htmlspecialchars($c['id']) ?>"><?= htmlspecialchars($c['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <div style="margin:-4px 0 10px 0;"><a href="products_action.php?action=add_category&type=expense" style="font-size:12px;">+ Add new expense category</a></div>
                    <label>Cost Price</label><br>
                    <input type="number" step="0.01" name="buying_price" value="0.00" style="width:100%;padding:8px;margin:6px 0;">
                    <label>Selling Price</label><br>
                    <input type="number" step="0.01" name="selling_price" value="0.00" style="width:100%;padding:8px;margin:6px 0;">
                    <label>Current Stock</label><br>
                    <input type="number" step="1" name="current_stock" value="0" style="width:100%;padding:8px;margin:6px 0;">
                    <label>Reorder Level</label><br>
                    <input type="number" step="1" name="min_stock" value="0" style="width:100%;padding:8px;margin:6px 0;">
                    <button class="action-btn" type="submit" style="margin-top:8px;">Save Product</button>
                </div>
            </div>
        </form>
        <?php
        onyx_page_end();
        exit();
    }

    if ($action === 'add_category') {
        require_permission('manage_products');
        $type = $_GET['type'] ?? 'product';
        $title = 'Add Product Category';
        $description = 'Create a category that can be used on products and accounting entries.';
        if ($type === 'income') {
            $title = 'Add Income Category';
            $description = 'Create a category for income records and reporting.';
        } elseif ($type === 'expense') {
            $title = 'Add Expense Category';
            $description = 'Create a category for expense records and reporting.';
        }
        $context = onyx_page_start($title, $description);
        ?>
        <form method="POST" action="products_action.php">
            <input type="hidden" name="action" value="add_category">
            <input type="hidden" name="category_type" value="<?= htmlspecialchars($type) ?>">
            <div class="panel span-6">
                <div class="panel-title"><i class="fa-solid fa-tags"></i> <?= htmlspecialchars($title) ?></div>
                <div style="margin-top:12px;">
                    <label>Name</label><br>
                    <input type="text" name="name" required style="width:100%;padding:8px;margin:6px 0;">
                    <?php if ($type === 'expense'): ?>
                        <label>Code</label><br>
                        <input type="text" name="code" style="width:100%;padding:8px;margin:6px 0;">
                    <?php else: ?>
                        <label>Description</label><br>
                        <textarea name="description" rows="3" style="width:100%;padding:8px;margin:6px 0;"></textarea>
                    <?php endif; ?>
                    <button class="action-btn" type="submit" style="margin-top:8px;">Save Category</button>
                    <a class="action-btn" href="products.php" style="margin-top:8px; display:inline-block;">Back to Products</a>
                </div>
            </div>
        </form>
        <?php
        onyx_page_end();
        exit();
    }

    if ($action === 'edit') {
        require_permission('manage_products');
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        if ($id <= 0) redirect_back('Missing product id for edit.', false);
        $stmt = $pdo->prepare('SELECT * FROM products WHERE id = ? AND tenant_id = ? LIMIT 1');
        $stmt->execute([$id, $tenant_id]);
        $row = $stmt->fetch();
        if (!$row) redirect_back('Product not found.', false);
        $categories = onyx_rows('SELECT id, name FROM product_categories WHERE tenant_id = :tenant_id ORDER BY name ASC', ['tenant_id' => $tenant_id]);
        $income_categories = onyx_rows('SELECT id, name FROM income_categories WHERE tenant_id = :tenant_id ORDER BY name ASC', ['tenant_id' => $tenant_id]);
        $expense_categories = onyx_rows('SELECT id, name FROM expense_categories WHERE tenant_id = :tenant_id ORDER BY name ASC', ['tenant_id' => $tenant_id]);
        $context = onyx_page_start('Edit Product', 'Update product details.');
        ?>
        <form method="POST" action="products_action.php">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id" value="<?= htmlspecialchars($row['id']) ?>">
            <div class="panel span-6">
                <div class="panel-title"><i class="fa-solid fa-pen-to-square"></i> Edit Product</div>
                <div style="margin-top:12px;">
                    <label>SKU</label><br>
                    <input type="text" name="sku" value="<?= htmlspecialchars($row['sku']) ?>" required style="width:100%;padding:8px;margin:6px 0;">
                    <label>Name</label><br>
                    <input type="text" name="name" value="<?= htmlspecialchars($row['name']) ?>" required style="width:100%;padding:8px;margin:6px 0;">
                    <label>Product Category</label><br>
                    <select name="product_category_id" style="width:100%;padding:8px;margin:6px 0;">
                        <option value="">-- none --</option>
                        <?php foreach ($categories as $c): ?>
                            <option value="<?= htmlspecialchars($c['id']) ?>" <?= ($c['id'] == $row['product_category_id']) ? 'selected' : '' ?>><?= htmlspecialchars($c['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <div style="margin:-4px 0 10px 0;"><a href="products_action.php?action=add_category&type=product" style="font-size:12px;">+ Add new product category</a></div>
                    <label>Income Category</label><br>
                    <select name="income_category_id" style="width:100%;padding:8px;margin:6px 0;">
                        <option value="">-- none --</option>
                        <?php foreach ($income_categories as $c): ?>
                            <option value="<?= htmlspecialchars($c['id']) ?>" <?= ($c['id'] == $row['income_category_id']) ? 'selected' : '' ?>><?= htmlspecialchars($c['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <div style="margin:-4px 0 10px 0;"><a href="products_action.php?action=add_category&type=income" style="font-size:12px;">+ Add new income category</a></div>
                    <label>Expense Category</label><br>
                    <select name="expense_category_id" style="width:100%;padding:8px;margin:6px 0;">
                        <option value="">-- none --</option>
                        <?php foreach ($expense_categories as $c): ?>
                            <option value="<?= htmlspecialchars($c['id']) ?>" <?= ($c['id'] == $row['expense_category_id']) ? 'selected' : '' ?>><?= htmlspecialchars($c['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <div style="margin:-4px 0 10px 0;"><a href="products_action.php?action=add_category&type=expense" style="font-size:12px;">+ Add new expense category</a></div>
                    <label>Cost Price</label><br>
                    <input type="number" step="0.01" name="buying_price" value="<?= htmlspecialchars($row['buying_price']) ?>" style="width:100%;padding:8px;margin:6px 0;">
                    <label>Selling Price</label><br>
                    <input type="number" step="0.01" name="selling_price" value="<?= htmlspecialchars($row['selling_price']) ?>" style="width:100%;padding:8px;margin:6px 0;">
                    <label>Current Stock</label><br>
                    <input type="number" step="1" name="current_stock" value="<?= htmlspecialchars($row['current_stock']) ?>" style="width:100%;padding:8px;margin:6px 0;">
                    <label>Reorder Level</label><br>
                    <input type="number" step="1" name="min_stock" value="<?= htmlspecialchars($row['min_stock']) ?>" style="width:100%;padding:8px;margin:6px 0;">
                    <button class="action-btn" type="submit" style="margin-top:8px;">Update Product</button>
                </div>
            </div>
        </form>
        <?php
        onyx_page_end();
        exit();
    }

    if ($action === 'delete') {
        require_permission('manage_products');
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        if ($id <= 0) redirect_back('Missing product id for delete.', false);
        $context = onyx_page_start('Delete Product', 'Confirm deletion');
        ?>
        <div class="panel span-6">
            <div class="panel-title"><i class="fa-solid fa-trash"></i> Confirm Delete</div>
            <p>Are you sure you want to delete this product?</p>
            <form method="POST" action="products_action.php">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="<?= htmlspecialchars($id) ?>">
                <button class="action-btn" type="submit">Confirm Delete</button>
            </form>
        </div>
        <?php
        onyx_page_end();
        exit();
    }

    if ($action === 'view') {
        // allow view for users without manage_products but still require permission for edit/delete
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        if ($id <= 0) redirect_back('Missing product id for view.', false);
        $stmt = $pdo->prepare('SELECT p.*, pc.name as category_name FROM products p LEFT JOIN product_categories pc ON pc.id = p.product_category_id AND pc.tenant_id = p.tenant_id WHERE p.id = ? AND p.tenant_id = ? LIMIT 1');
        $stmt->execute([$id, $tenant_id]);
        $row = $stmt->fetch();
        if (!$row) redirect_back('Product not found.', false);
        $context = onyx_page_start('Product Details', $row['name']);
        ?>
        <div class="panel span-6">
            <div class="panel-title"><i class="fa-solid fa-box"></i> <?= htmlspecialchars($row['name']) ?></div>
            <div style="padding-top:12px;">
                <p><strong>SKU:</strong> <?= htmlspecialchars($row['sku']) ?></p>
                <p><strong>Category:</strong> <?= htmlspecialchars($row['category_name'] ?? '-') ?></p>
                <p><strong>Cost:</strong> <?= htmlspecialchars($row['buying_price']) ?></p>
                <p><strong>Price:</strong> <?= htmlspecialchars($row['selling_price']) ?></p>
                <p><strong>Stock:</strong> <?= htmlspecialchars($row['current_stock']) ?></p>
                <p><strong>Reorder Level:</strong> <?= htmlspecialchars($row['min_stock']) ?></p>
            </div>
        </div>
        <?php
        onyx_page_end();
        exit();
    }

    if ($action === 'export') {
        require_permission('manage_products');
        // CSV export of products for the tenant
        $rows = onyx_rows('SELECT p.sku, p.name, pc.name AS category, p.buying_price, p.selling_price, p.current_stock, p.min_stock FROM products p LEFT JOIN product_categories pc ON pc.id = p.product_category_id AND pc.tenant_id = p.tenant_id WHERE p.tenant_id = :tenant_id ORDER BY p.name ASC', ['tenant_id' => $tenant_id]);
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="products_export_' . date('Ymd_His') . '.csv"');
        $out = fopen('php://output', 'w');
        fputcsv($out, ['SKU', 'Name', 'Category', 'CostPrice', 'SellingPrice', 'CurrentStock', 'ReorderLevel']);
        foreach ($rows as $r) {
            fputcsv($out, [$r['sku'], $r['name'], $r['category'], $r['buying_price'], $r['selling_price'], $r['current_stock'], $r['min_stock']]);
        }
        fclose($out);
        exit();
    }

    if ($action === 'bulk_update') {
        require_permission('manage_products');
        // Show a form to update prices across products
        $products = onyx_rows('SELECT id, name, sku FROM products WHERE tenant_id = :tenant_id ORDER BY name ASC', ['tenant_id' => $tenant_id]);
        $context = onyx_page_start('Bulk Update Prices', 'Apply a percentage or fixed change to product selling prices');
        ?>
        <form method="POST" action="products_action.php">
            <input type="hidden" name="action" value="bulk_update">
            <label>Target Product</label><br>
            <select name="target" style="padding:8px;width:320px;margin:8px 0;">
                <option value="all">All Products</option>
                <?php foreach ($products as $p): ?>
                    <option value="<?= htmlspecialchars($p['id']) ?>"><?= htmlspecialchars($p['name']) ?> (<?= htmlspecialchars($p['sku']) ?>)</option>
                <?php endforeach; ?>
            </select>
            <label>Mode</label><br>
            <select name="mode" style="padding:8px;width:200px;margin:8px 0;">
                <option value="percent">Percent change</option>
                <option value="fixed">Fixed amount</option>
            </select>
            <label>Value (percent or fixed amount)</label><br>
            <input type="number" step="0.01" name="value" required style="padding:8px;width:200px;margin:8px 0;">
            <div style="margin-top:8px;"><button class="action-btn" type="submit">Apply Update</button></div>
        </form>
        <?php
        onyx_page_end();
        exit();
    }

    redirect_back('Unsupported view action: ' . $action, false);
}

// POST handlers
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if ($action === 'add_category') {
            require_permission('manage_products');
            $type = $_POST['category_type'] ?? 'product';
            $name = trim($_POST['name'] ?? '');
            if ($name === '') redirect_back('Category name is required.', false);
            if ($type === 'income') {
                $description = trim($_POST['description'] ?? '');
                $stmt = $pdo->prepare('INSERT INTO income_categories (tenant_id, name, description, created_at) VALUES (?, ?, ?, NOW())');
                $stmt->execute([$tenant_id, $name, $description]);
                redirect_back('Income category added successfully.');
            }
            if ($type === 'expense') {
                $code = trim($_POST['code'] ?? '');
                $stmt = $pdo->prepare('INSERT INTO expense_categories (tenant_id, name, code, created_at) VALUES (?, ?, ?, NOW())');
                $stmt->execute([$tenant_id, $name, $code]);
                redirect_back('Expense category added successfully.');
            }
            $description = trim($_POST['description'] ?? '');
            $slug = slugify_category($name);
            $stmt = $pdo->prepare('INSERT INTO product_categories (tenant_id, name, slug, description, created_at) VALUES (?, ?, ?, ?, NOW())');
            $stmt->execute([$tenant_id, $name, $slug, $description]);
            redirect_back('Product category added successfully.');
        }

        if ($action === 'add') {
            require_permission('manage_products');
            $sku = trim($_POST['sku'] ?? '');
            $name = trim($_POST['name'] ?? '');
            $product_category_id = isset($_POST['product_category_id']) && $_POST['product_category_id'] !== '' ? (int)$_POST['product_category_id'] : null;
            $income_category_id = isset($_POST['income_category_id']) && $_POST['income_category_id'] !== '' ? (int)$_POST['income_category_id'] : null;
            $expense_category_id = isset($_POST['expense_category_id']) && $_POST['expense_category_id'] !== '' ? (int)$_POST['expense_category_id'] : null;
            $buying_price = (float)($_POST['buying_price'] ?? 0);
            $selling_price = (float)($_POST['selling_price'] ?? 0);
            $current_stock = (int)($_POST['current_stock'] ?? 0);
            $min_stock = (int)($_POST['min_stock'] ?? 0);
            if ($sku === '' || $name === '') redirect_back('SKU and Name are required.', false);
            $stmt = $pdo->prepare('INSERT INTO products (tenant_id, sku, name, product_category_id, income_category_id, expense_category_id, buying_price, selling_price, current_stock, min_stock, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())');
            $stmt->execute([$tenant_id, $sku, $name, $product_category_id, $income_category_id, $expense_category_id, $buying_price, $selling_price, $current_stock, $min_stock]);
            redirect_back('Product added successfully.');
        }

        if ($action === 'edit') {
            require_permission('manage_products');
            $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
            if ($id <= 0) redirect_back('Missing product id.', false);
            $sku = trim($_POST['sku'] ?? '');
            $name = trim($_POST['name'] ?? '');
            $product_category_id = isset($_POST['product_category_id']) && $_POST['product_category_id'] !== '' ? (int)$_POST['product_category_id'] : null;
            $income_category_id = isset($_POST['income_category_id']) && $_POST['income_category_id'] !== '' ? (int)$_POST['income_category_id'] : null;
            $expense_category_id = isset($_POST['expense_category_id']) && $_POST['expense_category_id'] !== '' ? (int)$_POST['expense_category_id'] : null;
            $buying_price = (float)($_POST['buying_price'] ?? 0);
            $selling_price = (float)($_POST['selling_price'] ?? 0);
            $current_stock = (int)($_POST['current_stock'] ?? 0);
            $min_stock = (int)($_POST['min_stock'] ?? 0);
            if ($sku === '' || $name === '') redirect_back('SKU and Name are required.', false);
            $stmt = $pdo->prepare('UPDATE products SET sku = ?, name = ?, product_category_id = ?, income_category_id = ?, expense_category_id = ?, buying_price = ?, selling_price = ?, current_stock = ?, min_stock = ?, updated_at = NOW() WHERE id = ? AND tenant_id = ?');
            $stmt->execute([$sku, $name, $product_category_id, $income_category_id, $expense_category_id, $buying_price, $selling_price, $current_stock, $min_stock, $id, $tenant_id]);
            redirect_back('Product updated successfully.');
        }

        if ($action === 'delete') {
            require_permission('manage_products');
            $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
            if ($id <= 0) redirect_back('Missing product id.', false);
            $stmt = $pdo->prepare('DELETE FROM products WHERE id = ? AND tenant_id = ?');
            $stmt->execute([$id, $tenant_id]);
            redirect_back('Product deleted successfully.');
        }
    if ($action === 'bulk_update') {
        require_permission('manage_products');
        // apply bulk price updates
        $target = $_POST['target'] ?? 'all';
        $mode = $_POST['mode'] ?? 'percent';
        $value = (float)($_POST['value'] ?? 0);
        if ($mode === 'percent') {
            $factor = 1 + ($value / 100.0);
            if ($target === 'all') {
                $stmt = $pdo->prepare('UPDATE products SET selling_price = selling_price * ?, updated_at = NOW() WHERE tenant_id = ?');
                $stmt->execute([$factor, $tenant_id]);
            } else {
                $id = (int)$target;
                $stmt = $pdo->prepare('UPDATE products SET selling_price = selling_price * ?, updated_at = NOW() WHERE id = ? AND tenant_id = ?');
                $stmt->execute([$factor, $id, $tenant_id]);
            }
        } else {
            // fixed amount addition
            if ($target === 'all') {
                $stmt = $pdo->prepare('UPDATE products SET selling_price = selling_price + ?, updated_at = NOW() WHERE tenant_id = ?');
                $stmt->execute([$value, $tenant_id]);
            } else {
                $id = (int)$target;
                $stmt = $pdo->prepare('UPDATE products SET selling_price = selling_price + ?, updated_at = NOW() WHERE id = ? AND tenant_id = ?');
                $stmt->execute([$value, $id, $tenant_id]);
            }
        }
        redirect_back('Bulk update applied successfully.');
    }

    redirect_back('Unsupported action.', false);
}
