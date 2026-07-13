<?php

$action = $_GET['action'] ?? $_POST['action'] ?? null;
$tenant_id = (int) (onyx_tenant_id() ?? 0);
$pdo = onyx_db();

function product_action_h(mixed $value): string
{
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
}

function product_action_redirect(string $msg = '', bool $success = true): void
{
    $q = '';
    if ($msg !== '') {
        $q = ($success ? '?success=' : '?error=') . urlencode($msg);
    }

    header('Location: products.php' . $q);
    exit();
}

function product_action_slug(string $value): string
{
    $value = strtolower(trim($value));
    $value = preg_replace('/[^a-z0-9]+/', '-', $value);

    return trim((string) $value, '-') ?: 'category';
}

function product_action_post_string(string $key, string $default = ''): string
{
    return trim((string) ($_POST[$key] ?? $default));
}

function product_action_nullable_int(string $key): ?int
{
    $value = $_POST[$key] ?? '';

    return $value === '' ? null : (int) $value;
}

function product_action_option(string $value, string $label, mixed $current = null): void
{
    echo '<option value="' . product_action_h($value) . '"' . ((string) $current === (string) $value ? ' selected' : '') . '>' . product_action_h($label) . '</option>';
}

function product_action_money(mixed $amount, string $currency): string
{
    return number_format((float) ($amount ?? 0), 2) . ' ' . $currency;
}

function product_action_columns(PDO $pdo, string $table): array
{
    try {
        return array_map(static fn (array $row): string => $row['Field'], $pdo->query('SHOW COLUMNS FROM `' . str_replace('`', '``', $table) . '`')->fetchAll(PDO::FETCH_ASSOC));
    } catch (Throwable) {
        return [];
    }
}

function product_action_ensure_column(PDO $pdo, string $table, string $column, string $definition): void
{
    if (! in_array($column, product_action_columns($pdo, $table), true)) {
        $pdo->exec('ALTER TABLE `' . str_replace('`', '``', $table) . '` ADD COLUMN `' . str_replace('`', '``', $column) . '` ' . $definition);
    }
}

function product_action_ensure_schema(PDO $pdo): void
{
    product_action_ensure_column($pdo, 'products', 'barcode', 'VARCHAR(100) DEFAULT NULL');
    product_action_ensure_column($pdo, 'products', 'vat_rate', 'DECIMAL(5,2) NOT NULL DEFAULT 0.00');
    product_action_ensure_column($pdo, 'products', 'description', 'TEXT DEFAULT NULL');
    product_action_ensure_column($pdo, 'products', 'image_url', 'VARCHAR(255) DEFAULT NULL');

    $pdo->exec("CREATE TABLE IF NOT EXISTS inventory_transactions (
        id BIGINT(20) NOT NULL AUTO_INCREMENT,
        tenant_id BIGINT(20) NOT NULL,
        product_id BIGINT(20) NOT NULL,
        transaction_type VARCHAR(50) NOT NULL,
        quantity INT(11) NOT NULL DEFAULT 0,
        reference VARCHAR(255) DEFAULT NULL,
        notes TEXT DEFAULT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY idx_inventory_product (product_id),
        KEY idx_inventory_tenant (tenant_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
}

function product_action_styles(): void
{
    ?>
    <style>
        .product-action-page,.product-action-page *{border-radius:0!important}
        .product-action-page{display:grid;gap:16px;max-width:1220px}
        .product-action-panel{background:var(--onyx-surface);border:1px solid var(--onyx-border);padding:18px}
        .product-action-head{align-items:flex-start;display:flex;flex-wrap:wrap;gap:12px;justify-content:space-between}
        .product-action-title{color:#fff;font-size:11px;font-weight:900;letter-spacing:.7px;text-transform:uppercase}
        .product-action-subtitle{color:var(--onyx-muted);display:block;font-size:10px;font-weight:700;line-height:1.6;margin-top:5px}
        .product-action-grid{display:grid;gap:12px;grid-template-columns:repeat(12,minmax(0,1fr));margin-top:14px}
        .product-field{display:grid;gap:6px;grid-column:span 4;min-width:0}.product-field.wide{grid-column:span 8}.product-field.full{grid-column:span 12}
        .product-field label{color:var(--onyx-muted);font-size:10px;font-weight:800;letter-spacing:.7px;text-transform:uppercase}
        .product-field input,.product-field select,.product-field textarea{background:#101016;border:1px solid rgba(255,255,255,.12);color:#fff;font-family:Poppins,system-ui,sans-serif;font-size:10px;min-height:38px;outline:0;padding:8px 10px;width:100%}
        .product-field textarea{min-height:82px;resize:vertical}.product-field select option{background:#050506;color:#fff}
        .product-field input:focus,.product-field select:focus,.product-field textarea:focus{border-color:rgba(255,255,255,.42);box-shadow:0 0 0 2px rgba(255,255,255,.08)}
        .product-action-buttons,.product-action-tools{align-items:center;display:flex;flex-wrap:wrap;gap:8px}.product-action-buttons{justify-content:flex-end}
        .product-btn{align-items:center;background:rgba(255,255,255,.035);border:1px solid rgba(255,255,255,.1);color:#fff;cursor:pointer;display:inline-flex;font:inherit;font-size:10px;font-weight:800;gap:8px;min-height:40px;padding:0 14px;text-decoration:none;text-transform:uppercase}
        .product-btn.primary{background:#fff;color:#050506}.product-btn.danger{border-color:rgba(255,138,138,.35);color:#ff8a8a}
        .product-note{border:1px solid rgba(255,255,255,.08);color:var(--onyx-muted);font-size:10px;line-height:1.7;padding:12px}
        .product-summary-grid{display:grid;gap:10px;grid-template-columns:repeat(4,minmax(0,1fr));margin-top:14px}
        .product-summary-card{border:1px solid rgba(255,255,255,.08);padding:13px}.product-summary-card span{color:var(--onyx-muted);display:block;font-size:9px;font-weight:800;text-transform:uppercase}.product-summary-card strong{color:#fff;display:block;font-size:16px;margin-top:7px;word-break:break-word}
        .product-detail-grid{display:grid;gap:10px;grid-template-columns:repeat(3,minmax(0,1fr));margin-top:14px}.product-detail-item{border:1px solid rgba(255,255,255,.08);padding:12px}.product-detail-item span{color:var(--onyx-muted);display:block;font-size:9px;font-weight:800;text-transform:uppercase}.product-detail-item strong{color:#fff;display:block;font-size:12px;margin-top:7px}
        .product-table-wrap{overflow-x:auto}.product-preview-table{border-collapse:collapse;min-width:760px;width:100%}.product-preview-table th,.product-preview-table td{border-bottom:1px solid rgba(255,255,255,.06);font-size:10px;padding:9px;text-align:left}.product-preview-table th{color:var(--onyx-muted);font-weight:800;text-transform:uppercase}
        @media(max-width:900px){.product-field,.product-field.wide,.product-summary-card,.product-detail-item{grid-column:span 6}.product-summary-grid,.product-detail-grid{grid-template-columns:repeat(2,minmax(0,1fr))}}
        @media(max-width:640px){.product-field,.product-field.wide,.product-summary-card,.product-detail-item{grid-column:span 12}.product-summary-grid,.product-detail-grid{grid-template-columns:1fr}.product-action-buttons{justify-content:stretch}.product-btn{justify-content:center;width:100%}}
    </style>
    <?php
}

function product_action_category_rows(int $tenantId): array
{
    return [
        'product' => onyx_rows('SELECT id, name FROM product_categories WHERE tenant_id = :tenant_id ORDER BY name ASC', ['tenant_id' => $tenantId]),
        'income' => onyx_rows('SELECT id, name FROM income_categories WHERE tenant_id = :tenant_id ORDER BY name ASC', ['tenant_id' => $tenantId]),
        'expense' => onyx_rows('SELECT id, name FROM expense_categories WHERE tenant_id = :tenant_id ORDER BY name ASC', ['tenant_id' => $tenantId]),
    ];
}

function product_action_render_form(string $mode, array $row = []): void
{
    $isEdit = $mode === 'edit';
    $categories = product_action_category_rows((int) (onyx_tenant_id() ?? 0));
    product_action_styles();
    ?>
    <form class="product-action-page" method="POST" action="products_action.php">
        <input type="hidden" name="action" value="<?= $isEdit ? 'edit' : 'add' ?>">
        <?php if ($isEdit): ?><input type="hidden" name="id" value="<?= product_action_h($row['id'] ?? '') ?>"><?php endif; ?>

        <section class="product-action-panel">
            <div class="product-action-head">
                <div>
                    <div class="product-action-title"><i class="fa-solid fa-box-open"></i> <?= $isEdit ? 'Edit Product' : 'Add Product' ?> / Catalog Identity</div>
                    <span class="product-action-subtitle">Keep naming, barcode, category, POS visibility, and sales reporting data clean from the start.</span>
                </div>
                <div class="product-action-tools">
                    <a class="product-btn" href="<?= product_action_h(onyx_legacy_url('products.php')) ?>"><i class="fa-solid fa-arrow-left"></i> Back</a>
                    <a class="product-btn" href="<?= product_action_h(onyx_legacy_url('products_action.php?action=add_category&type=product')) ?>"><i class="fa-solid fa-tags"></i> Category</a>
                    <a class="product-btn" href="<?= product_action_h(onyx_legacy_url('products_action.php?action=add_category&type=income')) ?>"><i class="fa-solid fa-coins"></i> Income</a>
                    <a class="product-btn" href="<?= product_action_h(onyx_legacy_url('products_action.php?action=add_category&type=expense')) ?>"><i class="fa-solid fa-receipt"></i> Expense</a>
                </div>
            </div>
            <div class="product-action-grid">
                <div class="product-field"><label for="sku">SKU *</label><input id="sku" name="sku" required value="<?= product_action_h($row['sku'] ?? '') ?>" placeholder="ONYX-001"></div>
                <div class="product-field"><label for="barcode">Barcode</label><input id="barcode" name="barcode" value="<?= product_action_h($row['barcode'] ?? '') ?>" placeholder="Scan or type barcode"></div>
                <div class="product-field"><label for="name">Product Name *</label><input id="name" name="name" required value="<?= product_action_h($row['name'] ?? '') ?>" placeholder="Product display name"></div>
                <div class="product-field"><label for="product_category_id">Product Category</label><select id="product_category_id" name="product_category_id"><option value="">Uncategorized</option><?php foreach ($categories['product'] as $c) product_action_option((string) $c['id'], $c['name'], $row['product_category_id'] ?? ''); ?></select></div>
                <div class="product-field"><label for="income_category_id">Income Category</label><select id="income_category_id" name="income_category_id"><option value="">Not linked</option><?php foreach ($categories['income'] as $c) product_action_option((string) $c['id'], $c['name'], $row['income_category_id'] ?? ''); ?></select></div>
                <div class="product-field"><label for="expense_category_id">Expense Category</label><select id="expense_category_id" name="expense_category_id"><option value="">Not linked</option><?php foreach ($categories['expense'] as $c) product_action_option((string) $c['id'], $c['name'], $row['expense_category_id'] ?? ''); ?></select></div>
                <div class="product-field wide"><label for="image_url">Image URL</label><input id="image_url" name="image_url" value="<?= product_action_h($row['image_url'] ?? '') ?>" placeholder="Catalog or POS image link"></div>
                <div class="product-field full"><label for="description">Description</label><textarea id="description" name="description" placeholder="Product notes, pack size, model, warranty, or selling details"><?= product_action_h($row['description'] ?? '') ?></textarea></div>
            </div>
        </section>

        <section class="product-action-panel">
            <div class="product-action-title"><i class="fa-solid fa-coins"></i> Pricing, Tax & Stock Control</div>
            <div class="product-action-grid">
                <div class="product-field"><label for="buying_price">Cost Price</label><input id="buying_price" type="number" step="0.01" min="0" name="buying_price" value="<?= product_action_h($row['buying_price'] ?? '0.00') ?>"></div>
                <div class="product-field"><label for="selling_price">Selling Price</label><input id="selling_price" type="number" step="0.01" min="0" name="selling_price" value="<?= product_action_h($row['selling_price'] ?? '0.00') ?>"></div>
                <div class="product-field"><label for="vat_rate">VAT Rate %</label><input id="vat_rate" type="number" step="0.01" min="0" name="vat_rate" value="<?= product_action_h($row['vat_rate'] ?? '0.00') ?>"></div>
                <div class="product-field"><label for="current_stock">Current Stock</label><input id="current_stock" type="number" step="1" min="0" name="current_stock" value="<?= product_action_h($row['current_stock'] ?? '0') ?>"></div>
                <div class="product-field"><label for="min_stock">Reorder Level</label><input id="min_stock" type="number" step="1" min="0" name="min_stock" value="<?= product_action_h($row['min_stock'] ?? '0') ?>"></div>
                <div class="product-field">
                    <label>Margin Preview</label>
                    <input id="margin_preview" readonly value="0.0%">
                </div>
            </div>
        </section>

        <section class="product-action-panel">
            <div class="product-action-buttons">
                <a class="product-btn" href="<?= product_action_h(onyx_legacy_url('products.php')) ?>"><i class="fa-solid fa-xmark"></i> Cancel</a>
                <button class="product-btn primary" type="submit"><i class="fa-solid fa-floppy-disk"></i> <?= $isEdit ? 'Update Product' : 'Save Product' ?></button>
            </div>
        </section>
    </form>
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const cost = document.getElementById('buying_price');
        const price = document.getElementById('selling_price');
        const preview = document.getElementById('margin_preview');
        function updateMargin() {
            const c = Number(cost.value) || 0;
            const p = Number(price.value) || 0;
            preview.value = p > 0 ? (((p - c) / p) * 100).toFixed(1) + '%' : '0.0%';
        }
        [cost, price].forEach(function (input) { input.addEventListener('input', updateMargin); });
        updateMargin();
    });
    </script>
    <?php
}

function product_action_render_category_form(string $type): void
{
    $type = in_array($type, ['product', 'income', 'expense'], true) ? $type : 'product';
    $title = $type === 'income' ? 'Add Income Category' : ($type === 'expense' ? 'Add Expense Category' : 'Add Product Category');
    $icon = $type === 'income' ? 'fa-coins' : ($type === 'expense' ? 'fa-receipt' : 'fa-tags');
    product_action_styles();
    ?>
    <form class="product-action-page" method="POST" action="products_action.php">
        <input type="hidden" name="action" value="add_category">
        <input type="hidden" name="category_type" value="<?= product_action_h($type) ?>">
        <section class="product-action-panel">
            <div class="product-action-head">
                <div>
                    <div class="product-action-title"><i class="fa-solid <?= product_action_h($icon) ?>"></i> <?= product_action_h($title) ?></div>
                    <span class="product-action-subtitle">Create categories once and reuse them across inventory, POS, sales, and reporting.</span>
                </div>
                <div class="product-action-tools">
                    <a class="product-btn" href="<?= product_action_h(onyx_legacy_url('products.php')) ?>"><i class="fa-solid fa-arrow-left"></i> Back</a>
                    <a class="product-btn" href="<?= product_action_h(onyx_legacy_url('products_action.php?action=add')) ?>"><i class="fa-solid fa-plus"></i> Product</a>
                </div>
            </div>
            <div class="product-action-grid">
                <div class="product-field"><label for="name">Category Name *</label><input id="name" name="name" required placeholder="Category name"></div>
                <?php if ($type === 'expense'): ?>
                    <div class="product-field"><label for="code">Expense Code</label><input id="code" name="code" placeholder="Optional accounting code"></div>
                    <div class="product-field wide"><label for="description">Control Note</label><input id="description" name="description" placeholder="Cost classification or usage note"></div>
                <?php else: ?>
                    <div class="product-field wide"><label for="description">Description</label><input id="description" name="description" placeholder="Where this category should be used"></div>
                <?php endif; ?>
                <div class="product-field full">
                    <div class="product-note">Use product categories for stock grouping, income categories for sale revenue mapping, and expense categories for purchase or cost reporting.</div>
                </div>
            </div>
        </section>
        <section class="product-action-panel">
            <div class="product-action-buttons">
                <a class="product-btn" href="<?= product_action_h(onyx_legacy_url('products.php')) ?>"><i class="fa-solid fa-xmark"></i> Cancel</a>
                <button class="product-btn primary" type="submit"><i class="fa-solid fa-floppy-disk"></i> Save Category</button>
            </div>
        </section>
    </form>
    <?php
}

function product_action_render_bulk_update(int $tenantId, string $currency): void
{
    $products = onyx_rows('SELECT id, name, sku, selling_price FROM products WHERE tenant_id = :tenant_id ORDER BY name ASC', ['tenant_id' => $tenantId]);
    $categories = onyx_rows('SELECT id, name FROM product_categories WHERE tenant_id = :tenant_id ORDER BY name ASC', ['tenant_id' => $tenantId]);
    product_action_styles();
    ?>
    <form class="product-action-page" method="POST" action="products_action.php" onsubmit="return confirm('Apply this bulk price update?');">
        <input type="hidden" name="action" value="bulk_update">
        <section class="product-action-panel">
            <div class="product-action-head">
                <div>
                    <div class="product-action-title"><i class="fa-solid fa-percent"></i> Bulk Update Prices</div>
                    <span class="product-action-subtitle">Apply controlled selling price changes by all products, category, stock condition, or a single product.</span>
                </div>
                <a class="product-btn" href="<?= product_action_h(onyx_legacy_url('products.php')) ?>"><i class="fa-solid fa-arrow-left"></i> Back</a>
            </div>
            <div class="product-action-grid">
                <div class="product-field"><label for="target_scope">Target Scope</label><select id="target_scope" name="target_scope"><option value="all">All products</option><option value="category">Category</option><option value="product">Single product</option><option value="low_stock">Low and out of stock</option></select></div>
                <div class="product-field" data-scope-field="category"><label for="category_id">Category</label><select id="category_id" name="category_id"><option value="">Choose category</option><?php foreach ($categories as $c) product_action_option((string) $c['id'], $c['name']); ?></select></div>
                <div class="product-field" data-scope-field="product"><label for="product_id">Product</label><select id="product_id" name="product_id"><option value="">Choose product</option><?php foreach ($products as $p) product_action_option((string) $p['id'], $p['name'] . ' (' . $p['sku'] . ')'); ?></select></div>
                <div class="product-field"><label for="mode">Update Mode</label><select id="mode" name="mode"><option value="percent">Percent change</option><option value="fixed">Fixed amount change</option><option value="set_price">Set exact price</option><option value="margin">Set margin over cost</option></select></div>
                <div class="product-field"><label for="value">Value</label><input id="value" type="number" step="0.01" name="value" required placeholder="Example: 10"></div>
                <div class="product-field wide"><label for="reason">Reason / Note</label><input id="reason" name="reason" placeholder="Supplier increase, promo reset, margin correction"></div>
                <div class="product-field full">
                    <div class="product-note">Percent uses 10 for +10% and -5 for a 5% reduction. Margin mode uses cost price, so 25 means selling price becomes cost plus 25%.</div>
                </div>
            </div>
        </section>
        <section class="product-action-panel">
            <div class="product-summary-grid">
                <div class="product-summary-card"><span>Products Available</span><strong><?= product_action_h(count($products)) ?></strong></div>
                <div class="product-summary-card"><span>Categories</span><strong><?= product_action_h(count($categories)) ?></strong></div>
                <div class="product-summary-card"><span>Currency</span><strong><?= product_action_h($currency) ?></strong></div>
                <div class="product-summary-card"><span>Safeguard</span><strong>Confirm</strong></div>
            </div>
            <div class="product-action-buttons" style="margin-top:14px;">
                <a class="product-btn" href="<?= product_action_h(onyx_legacy_url('products.php')) ?>"><i class="fa-solid fa-xmark"></i> Cancel</a>
                <button class="product-btn primary" type="submit"><i class="fa-solid fa-check"></i> Apply Update</button>
            </div>
        </section>
    </form>
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const scope = document.getElementById('target_scope');
        const fields = Array.from(document.querySelectorAll('[data-scope-field]'));
        function updateFields() {
            fields.forEach(function (field) {
                field.style.display = field.dataset.scopeField === scope.value ? 'grid' : 'none';
            });
        }
        scope.addEventListener('change', updateFields);
        updateFields();
    });
    </script>
    <?php
}

function product_action_render_stock_adjust(int $tenantId): void
{
    $products = onyx_rows('SELECT id, name, sku, current_stock FROM products WHERE tenant_id = :tenant_id ORDER BY name ASC', ['tenant_id' => $tenantId]);
    product_action_styles();
    ?>
    <form class="product-action-page" method="POST" action="products_action.php" onsubmit="return confirm('Post this stock adjustment?');">
        <input type="hidden" name="action" value="stock_adjust">
        <section class="product-action-panel">
            <div class="product-action-head">
                <div>
                    <div class="product-action-title"><i class="fa-solid fa-boxes-stacked"></i> Stock Adjustment</div>
                    <span class="product-action-subtitle">Correct opening stock, damages, recounts, returns, or internal stock movements.</span>
                </div>
                <a class="product-btn" href="<?= product_action_h(onyx_legacy_url('products.php')) ?>"><i class="fa-solid fa-arrow-left"></i> Back</a>
            </div>
            <div class="product-action-grid">
                <div class="product-field wide"><label for="product_id">Product *</label><select id="product_id" name="product_id" required><option value="">Choose product</option><?php foreach ($products as $p) product_action_option((string) $p['id'], $p['name'] . ' (' . $p['sku'] . ') - Stock: ' . $p['current_stock']); ?></select></div>
                <div class="product-field"><label for="movement">Movement</label><select id="movement" name="movement"><option value="increase">Increase stock</option><option value="decrease">Decrease stock</option><option value="set">Set stock exactly</option></select></div>
                <div class="product-field"><label for="quantity">Quantity *</label><input id="quantity" type="number" min="0" step="1" name="quantity" required></div>
                <div class="product-field"><label for="reference">Reference</label><input id="reference" name="reference" placeholder="GRN, count, damage, return"></div>
                <div class="product-field full"><label for="notes">Notes</label><textarea id="notes" name="notes" placeholder="Why this adjustment is being posted"></textarea></div>
            </div>
        </section>
        <section class="product-action-panel">
            <div class="product-action-buttons">
                <a class="product-btn" href="<?= product_action_h(onyx_legacy_url('products.php')) ?>"><i class="fa-solid fa-xmark"></i> Cancel</a>
                <button class="product-btn primary" type="submit"><i class="fa-solid fa-check"></i> Post Adjustment</button>
            </div>
        </section>
    </form>
    <?php
}

function product_action_render_view(array $row, string $currency): void
{
    $stock = (int) ($row['current_stock'] ?? 0);
    $min = (int) ($row['min_stock'] ?? 0);
    $cost = (float) ($row['buying_price'] ?? 0);
    $selling = (float) ($row['selling_price'] ?? 0);
    $margin = $selling > 0 ? (($selling - $cost) / $selling) * 100 : 0;
    product_action_styles();
    ?>
    <div class="product-action-page">
        <section class="product-action-panel">
            <div class="product-action-head">
                <div>
                    <div class="product-action-title"><i class="fa-solid fa-box"></i> <?= product_action_h($row['name']) ?></div>
                    <span class="product-action-subtitle"><?= product_action_h($row['sku']) ?> / <?= product_action_h($row['category_name'] ?: 'Uncategorized') ?></span>
                </div>
                <div class="product-action-tools">
                    <a class="product-btn" href="<?= product_action_h(onyx_legacy_url('products.php')) ?>"><i class="fa-solid fa-arrow-left"></i> Back</a>
                    <a class="product-btn primary" href="<?= product_action_h(onyx_legacy_url('products_action.php?action=edit&id=' . (int) $row['id'])) ?>"><i class="fa-solid fa-pen"></i> Edit</a>
                    <a class="product-btn" href="<?= product_action_h(onyx_legacy_url('products_action.php?action=stock_adjust&id=' . (int) $row['id'])) ?>"><i class="fa-solid fa-boxes-stacked"></i> Adjust Stock</a>
                </div>
            </div>
            <div class="product-summary-grid">
                <div class="product-summary-card"><span>Stock</span><strong><?= product_action_h($stock) ?></strong></div>
                <div class="product-summary-card"><span>Reorder Level</span><strong><?= product_action_h($min) ?></strong></div>
                <div class="product-summary-card"><span>Selling Price</span><strong><?= product_action_h(product_action_money($selling, $currency)) ?></strong></div>
                <div class="product-summary-card"><span>Margin</span><strong><?= product_action_h(number_format($margin, 1)) ?>%</strong></div>
            </div>
            <div class="product-detail-grid">
                <div class="product-detail-item"><span>Barcode</span><strong><?= product_action_h($row['barcode'] ?: 'Not captured') ?></strong></div>
                <div class="product-detail-item"><span>VAT Rate</span><strong><?= product_action_h(number_format((float) ($row['vat_rate'] ?? 0), 2)) ?>%</strong></div>
                <div class="product-detail-item"><span>Cost Value</span><strong><?= product_action_h(product_action_money($stock * $cost, $currency)) ?></strong></div>
                <div class="product-detail-item"><span>Income Category</span><strong><?= product_action_h($row['income_category_name'] ?: 'Not linked') ?></strong></div>
                <div class="product-detail-item"><span>Expense Category</span><strong><?= product_action_h($row['expense_category_name'] ?: 'Not linked') ?></strong></div>
                <div class="product-detail-item"><span>Image URL</span><strong><?= product_action_h($row['image_url'] ?: 'Not captured') ?></strong></div>
                <div class="product-detail-item" style="grid-column:1/-1;"><span>Description</span><strong><?= product_action_h($row['description'] ?: 'No description captured') ?></strong></div>
            </div>
        </section>
    </div>
    <?php
}

product_action_ensure_schema($pdo);

if (! $action) {
    product_action_redirect('No action specified.', false);
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if ($action === 'add') {
        require_permission('manage_products');
        onyx_page_start('Add Product', 'Create a complete product record for inventory, POS, and sales.');
        product_action_render_form('add');
        onyx_page_end();
        exit();
    }

    if ($action === 'edit') {
        require_permission('manage_products');
        $id = (int) ($_GET['id'] ?? 0);
        if ($id <= 0) product_action_redirect('Missing product id for edit.', false);
        $stmt = $pdo->prepare('SELECT * FROM products WHERE id = ? AND tenant_id = ? LIMIT 1');
        $stmt->execute([$id, $tenant_id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (! $row) product_action_redirect('Product not found.', false);
        onyx_page_start('Edit Product', 'Update product details, prices, accounting links, and stock control.');
        product_action_render_form('edit', $row);
        onyx_page_end();
        exit();
    }

    if ($action === 'add_category') {
        require_permission('manage_products');
        $type = $_GET['type'] ?? 'product';
        $title = $type === 'income' ? 'Add Income Category' : ($type === 'expense' ? 'Add Expense Category' : 'Add Product Category');
        onyx_page_start($title, 'Create reusable categories for product and accounting workflows.');
        product_action_render_category_form($type);
        onyx_page_end();
        exit();
    }

    if ($action === 'bulk_update') {
        require_permission('manage_products');
        $context = onyx_page_start('Bulk Update Prices', 'Apply controlled price changes to product groups.');
        product_action_render_bulk_update($tenant_id, $context['currency']);
        onyx_page_end();
        exit();
    }

    if ($action === 'stock_adjust') {
        require_permission('manage_products');
        onyx_page_start('Stock Adjustment', 'Post manual product stock corrections and recounts.');
        product_action_render_stock_adjust($tenant_id);
        onyx_page_end();
        exit();
    }

    if ($action === 'delete') {
        require_permission('manage_products');
        $id = (int) ($_GET['id'] ?? 0);
        if ($id <= 0) product_action_redirect('Missing product id for delete.', false);
        $stmt = $pdo->prepare('SELECT id, name, sku FROM products WHERE id = ? AND tenant_id = ? LIMIT 1');
        $stmt->execute([$id, $tenant_id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (! $row) product_action_redirect('Product not found.', false);
        onyx_page_start('Delete Product', 'Confirm product removal.');
        product_action_styles();
        ?>
        <form class="product-action-page" method="POST" action="products_action.php" onsubmit="return confirm('Delete this product permanently?');">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="id" value="<?= product_action_h($id) ?>">
            <section class="product-action-panel">
                <div class="product-action-title"><i class="fa-solid fa-trash"></i> Delete Product</div>
                <div class="product-note" style="margin-top:14px;">You are deleting <?= product_action_h($row['name']) ?> (<?= product_action_h($row['sku']) ?>). This should only be used for products created in error.</div>
            </section>
            <section class="product-action-panel">
                <div class="product-action-buttons">
                    <a class="product-btn" href="<?= product_action_h(onyx_legacy_url('products.php')) ?>"><i class="fa-solid fa-xmark"></i> Cancel</a>
                    <button class="product-btn danger" type="submit"><i class="fa-solid fa-trash"></i> Confirm Delete</button>
                </div>
            </section>
        </form>
        <?php
        onyx_page_end();
        exit();
    }

    if ($action === 'view') {
        $id = (int) ($_GET['id'] ?? 0);
        if ($id <= 0) product_action_redirect('Missing product id for view.', false);
        $stmt = $pdo->prepare('SELECT p.*, pc.name AS category_name, ic.name AS income_category_name, ec.name AS expense_category_name
            FROM products p
            LEFT JOIN product_categories pc ON pc.id = p.product_category_id AND pc.tenant_id = p.tenant_id
            LEFT JOIN income_categories ic ON ic.id = p.income_category_id AND ic.tenant_id = p.tenant_id
            LEFT JOIN expense_categories ec ON ec.id = p.expense_category_id AND ec.tenant_id = p.tenant_id
            WHERE p.id = ? AND p.tenant_id = ? LIMIT 1');
        $stmt->execute([$id, $tenant_id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (! $row) product_action_redirect('Product not found.', false);
        $context = onyx_page_start('Product Details', $row['name']);
        product_action_render_view($row, $context['currency']);
        onyx_page_end();
        exit();
    }

    if ($action === 'export') {
        require_permission('manage_products');
        $rows = onyx_rows('SELECT p.sku, p.barcode, p.name, pc.name AS category, p.buying_price, p.selling_price, p.vat_rate, p.current_stock, p.min_stock, p.description
            FROM products p
            LEFT JOIN product_categories pc ON pc.id = p.product_category_id AND pc.tenant_id = p.tenant_id
            WHERE p.tenant_id = :tenant_id ORDER BY p.name ASC', ['tenant_id' => $tenant_id]);
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="products_export_' . date('Ymd_His') . '.csv"');
        $out = fopen('php://output', 'w');
        fputcsv($out, ['SKU', 'Barcode', 'Name', 'Category', 'CostPrice', 'SellingPrice', 'VATRate', 'CurrentStock', 'ReorderLevel', 'Description']);
        foreach ($rows as $r) {
            fputcsv($out, [$r['sku'], $r['barcode'], $r['name'], $r['category'], $r['buying_price'], $r['selling_price'], $r['vat_rate'], $r['current_stock'], $r['min_stock'], $r['description']]);
        }
        fclose($out);
        exit();
    }

    product_action_redirect('Unsupported view action: ' . $action, false);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'add_category') {
        require_permission('manage_products');
        $type = $_POST['category_type'] ?? 'product';
        $name = product_action_post_string('name');
        if ($name === '') product_action_redirect('Category name is required.', false);

        if ($type === 'income') {
            $description = product_action_post_string('description');
            $stmt = $pdo->prepare('INSERT INTO income_categories (tenant_id, name, description, created_at) VALUES (?, ?, ?, NOW())');
            $stmt->execute([$tenant_id, $name, $description]);
            product_action_redirect('Income category added successfully.');
        }

        if ($type === 'expense') {
            $code = product_action_post_string('code');
            $stmt = $pdo->prepare('INSERT INTO expense_categories (tenant_id, name, code, created_at) VALUES (?, ?, ?, NOW())');
            $stmt->execute([$tenant_id, $name, $code]);
            product_action_redirect('Expense category added successfully.');
        }

        $description = product_action_post_string('description');
        $slug = product_action_slug($name);
        $stmt = $pdo->prepare('INSERT INTO product_categories (tenant_id, name, slug, description, created_at) VALUES (?, ?, ?, ?, NOW())');
        $stmt->execute([$tenant_id, $name, $slug, $description]);
        product_action_redirect('Product category added successfully.');
    }

    if ($action === 'add' || $action === 'edit') {
        require_permission('manage_products');
        $id = (int) ($_POST['id'] ?? 0);
        $sku = product_action_post_string('sku');
        $name = product_action_post_string('name');
        $barcode = product_action_post_string('barcode');
        $product_category_id = product_action_nullable_int('product_category_id');
        $income_category_id = product_action_nullable_int('income_category_id');
        $expense_category_id = product_action_nullable_int('expense_category_id');
        $buying_price = max(0, (float) ($_POST['buying_price'] ?? 0));
        $selling_price = max(0, (float) ($_POST['selling_price'] ?? 0));
        $vat_rate = max(0, (float) ($_POST['vat_rate'] ?? 0));
        $current_stock = max(0, (int) ($_POST['current_stock'] ?? 0));
        $min_stock = max(0, (int) ($_POST['min_stock'] ?? 0));
        $description = product_action_post_string('description');
        $image_url = product_action_post_string('image_url');

        if ($sku === '' || $name === '') product_action_redirect('SKU and product name are required.', false);

        $dupe = $pdo->prepare('SELECT COUNT(*) FROM products WHERE tenant_id = ? AND sku = ? AND id <> ?');
        $dupe->execute([$tenant_id, $sku, $action === 'edit' ? $id : 0]);
        if ((int) $dupe->fetchColumn() > 0) product_action_redirect('Another product already uses this SKU.', false);

        if ($action === 'add') {
            $stmt = $pdo->prepare('INSERT INTO products (tenant_id, sku, barcode, name, product_category_id, income_category_id, expense_category_id, buying_price, selling_price, vat_rate, current_stock, min_stock, description, image_url, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())');
            $stmt->execute([$tenant_id, $sku, $barcode, $name, $product_category_id, $income_category_id, $expense_category_id, $buying_price, $selling_price, $vat_rate, $current_stock, $min_stock, $description, $image_url]);
            product_action_redirect('Product added successfully.');
        }

        if ($id <= 0) product_action_redirect('Missing product id.', false);
        $stmt = $pdo->prepare('UPDATE products SET sku = ?, barcode = ?, name = ?, product_category_id = ?, income_category_id = ?, expense_category_id = ?, buying_price = ?, selling_price = ?, vat_rate = ?, current_stock = ?, min_stock = ?, description = ?, image_url = ?, updated_at = NOW() WHERE id = ? AND tenant_id = ?');
        $stmt->execute([$sku, $barcode, $name, $product_category_id, $income_category_id, $expense_category_id, $buying_price, $selling_price, $vat_rate, $current_stock, $min_stock, $description, $image_url, $id, $tenant_id]);
        product_action_redirect('Product updated successfully.');
    }

    if ($action === 'delete') {
        require_permission('manage_products');
        $id = (int) ($_POST['id'] ?? 0);
        if ($id <= 0) product_action_redirect('Missing product id.', false);
        $stmt = $pdo->prepare('DELETE FROM products WHERE id = ? AND tenant_id = ?');
        $stmt->execute([$id, $tenant_id]);
        product_action_redirect('Product deleted successfully.');
    }

    if ($action === 'bulk_update') {
        require_permission('manage_products');
        $scope = $_POST['target_scope'] ?? 'all';
        $mode = $_POST['mode'] ?? 'percent';
        $value = (float) ($_POST['value'] ?? 0);
        $params = [$tenant_id];
        $where = 'tenant_id = ?';

        if ($scope === 'category') {
            $categoryId = (int) ($_POST['category_id'] ?? 0);
            if ($categoryId <= 0) product_action_redirect('Choose a category for this bulk update.', false);
            $where .= ' AND product_category_id = ?';
            $params[] = $categoryId;
        } elseif ($scope === 'product') {
            $productId = (int) ($_POST['product_id'] ?? 0);
            if ($productId <= 0) product_action_redirect('Choose a product for this bulk update.', false);
            $where .= ' AND id = ?';
            $params[] = $productId;
        } elseif ($scope === 'low_stock') {
            $where .= ' AND current_stock <= min_stock';
        }

        if ($mode === 'percent') {
            $sql = 'UPDATE products SET selling_price = GREATEST(0, selling_price * ?), updated_at = NOW() WHERE ' . $where;
            array_unshift($params, 1 + ($value / 100));
        } elseif ($mode === 'fixed') {
            $sql = 'UPDATE products SET selling_price = GREATEST(0, selling_price + ?), updated_at = NOW() WHERE ' . $where;
            array_unshift($params, $value);
        } elseif ($mode === 'set_price') {
            $sql = 'UPDATE products SET selling_price = ?, updated_at = NOW() WHERE ' . $where;
            array_unshift($params, max(0, $value));
        } else {
            $sql = 'UPDATE products SET selling_price = buying_price * ?, updated_at = NOW() WHERE ' . $where;
            array_unshift($params, 1 + (max(0, $value) / 100));
        }

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        product_action_redirect('Bulk price update applied to ' . $stmt->rowCount() . ' product(s).');
    }

    if ($action === 'stock_adjust') {
        require_permission('manage_products');
        $productId = (int) ($_POST['product_id'] ?? 0);
        $movement = $_POST['movement'] ?? 'increase';
        $quantity = max(0, (int) ($_POST['quantity'] ?? 0));
        $reference = product_action_post_string('reference');
        $notes = product_action_post_string('notes');
        if ($productId <= 0 || $quantity < 0) product_action_redirect('Choose a product and quantity.', false);

        $stmt = $pdo->prepare('SELECT current_stock FROM products WHERE id = ? AND tenant_id = ? LIMIT 1');
        $stmt->execute([$productId, $tenant_id]);
        $current = $stmt->fetchColumn();
        if ($current === false) product_action_redirect('Product not found for adjustment.', false);

        $current = (int) $current;
        $newStock = $movement === 'set' ? $quantity : ($movement === 'decrease' ? max(0, $current - $quantity) : $current + $quantity);
        $delta = $newStock - $current;

        $pdo->beginTransaction();
        try {
            $update = $pdo->prepare('UPDATE products SET current_stock = ?, updated_at = NOW() WHERE id = ? AND tenant_id = ?');
            $update->execute([$newStock, $productId, $tenant_id]);
            $tx = $pdo->prepare('INSERT INTO inventory_transactions (tenant_id, product_id, transaction_type, quantity, reference, notes, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())');
            $tx->execute([$tenant_id, $productId, 'adjustment', $delta, $reference, $notes]);
            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();
            product_action_redirect('Stock adjustment failed: ' . $e->getMessage(), false);
        }

        product_action_redirect('Stock adjustment posted successfully.');
    }

    product_action_redirect('Unsupported action.', false);
}
