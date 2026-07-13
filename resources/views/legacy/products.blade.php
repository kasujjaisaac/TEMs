<?php

$pdo = onyx_db();

if (! function_exists('product_page_h')) {
    function product_page_h(mixed $value): string
    {
        return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
    }
}

if (! function_exists('product_page_money')) {
    function product_page_money(mixed $amount, string $currency): string
    {
        return number_format((float) ($amount ?? 0), 2) . ' ' . $currency;
    }
}

if (! function_exists('product_page_stock_status')) {
    function product_page_stock_status(array $product): array
    {
        $stock = (int) ($product['current_stock'] ?? 0);
        $minimum = max(0, (int) ($product['min_stock'] ?? 0));

        if ($stock <= 0) {
            return ['out', 'Out of stock'];
        }

        if ($minimum > 0 && $stock <= $minimum) {
            return ['low', 'Low stock'];
        }

        return ['ok', 'In stock'];
    }
}

if (! function_exists('product_page_margin')) {
    function product_page_margin(array $product): float
    {
        $selling = (float) ($product['selling_price'] ?? 0);
        $cost = (float) ($product['buying_price'] ?? 0);

        if ($selling <= 0) {
            return 0.0;
        }

        return (($selling - $cost) / $selling) * 100;
    }
}

$context = onyx_page_start('Products', 'Product catalog, pricing, stock health, reorder levels, and sales readiness.');
$currency = $context['currency'];
$tenant_id = (int) (onyx_tenant_id() ?? 0);

$products = onyx_rows(
    'SELECT p.id, p.sku, p.barcode, p.name, p.buying_price, p.selling_price, p.vat_rate,
            p.current_stock, p.min_stock, p.description, p.image_url, pc.name AS category_name
     FROM products p
     LEFT JOIN product_categories pc ON pc.id = p.product_category_id AND pc.tenant_id = p.tenant_id
     WHERE p.tenant_id = :tenant_id
     ORDER BY p.name ASC',
    ['tenant_id' => $tenant_id]
);

$categories = onyx_rows(
    'SELECT name FROM product_categories WHERE tenant_id = :tenant_id ORDER BY name ASC',
    ['tenant_id' => $tenant_id]
);

$total_products = count($products);
$low_stock = 0;
$out_stock = 0;
$stock_value = 0.0;
$retail_value = 0.0;
$reorder_products = [];

foreach ($products as $product) {
    [$stockClass] = product_page_stock_status($product);
    $stock = max(0, (int) ($product['current_stock'] ?? 0));
    $stock_value += $stock * (float) ($product['buying_price'] ?? 0);
    $retail_value += $stock * (float) ($product['selling_price'] ?? 0);

    if ($stockClass === 'low') {
        $low_stock++;
        $reorder_products[] = $product;
    }

    if ($stockClass === 'out') {
        $out_stock++;
        $reorder_products[] = $product;
    }
}

$healthy_products = max(0, $total_products - $low_stock - $out_stock);
$gross_margin_value = $retail_value - $stock_value;
?>

<style>
    .product-page,
    .product-page * {
        border-radius: 0 !important;
    }

    .product-page {
        display: grid;
        gap: 18px;
    }

    .product-panel {
        background: var(--onyx-surface);
        border: 1px solid var(--onyx-border);
        max-width: 100%;
        min-width: 0;
        overflow: hidden;
        padding: 18px;
    }

    .product-toolbar,
    .product-filters,
    .product-table-actions {
        align-items: center;
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
    }

    .product-toolbar {
        justify-content: space-between;
    }

    .product-toolbar-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
    }

    .product-action {
        align-items: center;
        background: rgba(255,255,255,0.035);
        border: 1px solid rgba(255,255,255,0.09);
        color: #fff;
        display: inline-flex;
        font-size: 0.68rem;
        font-weight: 800;
        gap: 8px;
        min-height: 38px;
        padding: 0 12px;
        text-decoration: none;
        text-transform: uppercase;
        white-space: nowrap;
    }

    .product-action.primary {
        background: #fff;
        color: #050506;
    }

    .product-action.danger {
        color: #ff8a8a;
    }

    .product-section-title {
        color: var(--onyx-muted);
        font-size: 0.72rem;
        font-weight: 800;
        letter-spacing: 0.8px;
        line-height: 1.45;
        text-transform: uppercase;
    }

    .product-muted {
        color: var(--onyx-muted);
        display: block;
        font-size: 0.62rem;
        margin-top: 3px;
    }

    .product-kpis {
        display: grid;
        gap: 10px;
        grid-template-columns: repeat(5, minmax(148px, 1fr));
    }

    .product-kpi {
        background: var(--onyx-surface);
        border: 1px solid var(--onyx-border);
        min-width: 0;
        padding: 14px;
    }

    .product-kpi span {
        color: var(--onyx-muted);
        display: block;
        font-size: 0.58rem;
        font-weight: 800;
        letter-spacing: 0.8px;
        text-transform: uppercase;
    }

    .product-kpi strong {
        color: #fff;
        display: block;
        font-size: 1.08rem;
        font-weight: 900;
        line-height: 1.2;
        margin-top: 8px;
        word-break: break-word;
    }

    .product-field {
        display: grid;
        gap: 6px;
        min-width: 150px;
    }

    .product-field.search {
        flex: 1 1 260px;
    }

    .product-field label {
        color: var(--onyx-muted);
        font-size: 0.58rem;
        font-weight: 800;
        letter-spacing: 0.8px;
        text-transform: uppercase;
    }

    .product-field input,
    .product-field select {
        background: #101016;
        border: 1px solid rgba(255,255,255,0.12);
        color: #fff;
        font-size: 0.78rem;
        min-height: 38px;
        outline: none;
        padding: 0 10px;
        width: 100%;
    }

    .product-field select option {
        background: #050506;
        color: #fff;
    }

    .product-count {
        color: var(--onyx-muted);
        font-size: 0.64rem;
        font-weight: 800;
        margin-left: auto;
        text-transform: uppercase;
    }

    .product-table-wrap {
        max-width: calc(100vw - 340px);
        margin-top: 14px;
        overflow-x: auto;
        overflow-y: hidden;
        padding-bottom: 16px;
        scrollbar-color: rgba(255,255,255,0.28) rgba(255,255,255,0.06);
        scrollbar-width: thin;
        width: 100%;
    }

    .product-table-wrap::-webkit-scrollbar {
        height: 10px;
    }

    .product-table-wrap::-webkit-scrollbar-track {
        background: rgba(255,255,255,0.06);
    }

    .product-table-wrap::-webkit-scrollbar-thumb {
        background: rgba(255,255,255,0.28);
    }

    .product-table {
        border-collapse: collapse;
        table-layout: fixed;
        width: 1420px;
    }

    .product-table th,
    .product-table td {
        border-bottom: 1px solid rgba(255,255,255,0.06);
        font-size: 0.68rem;
        padding: 9px 9px;
        text-align: left;
        vertical-align: middle;
    }

    .product-table th {
        color: var(--onyx-muted);
        font-size: 0.58rem;
        font-weight: 800;
        letter-spacing: 0.8px;
        text-transform: uppercase;
    }

    .product-table tbody tr:hover {
        background: rgba(255,255,255,0.045);
    }

    .product-name strong {
        color: #fff;
        display: block;
        font-size: 0.72rem;
        font-weight: 800;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .product-name span {
        color: var(--onyx-muted);
        display: block;
        font-size: 0.62rem;
        margin-top: 3px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .product-badge {
        border: 1px solid rgba(255,255,255,0.12);
        color: #d8d8de;
        display: inline-flex;
        font-size: 0.56rem;
        font-weight: 800;
        padding: 5px 8px;
        text-transform: uppercase;
        white-space: nowrap;
    }

    .product-badge.ok {
        color: #8ff0c3;
    }

    .product-badge.low {
        color: #ffd27a;
    }

    .product-badge.out,
    .product-badge.danger {
        color: #ff8a8a;
    }

    .product-empty {
        border: 1px solid rgba(255,255,255,0.08);
        color: var(--onyx-muted);
        padding: 16px;
    }

    .product-compact-table {
        border-collapse: collapse;
        margin-top: 14px;
        table-layout: fixed;
        width: 100%;
    }

    .product-compact-table th,
    .product-compact-table td {
        border-bottom: 1px solid rgba(255,255,255,0.06);
        font-size: 0.68rem;
        padding: 9px;
        text-align: left;
    }

    .product-compact-table th {
        color: var(--onyx-muted);
        font-size: 0.58rem;
        font-weight: 800;
        letter-spacing: 0.8px;
        text-transform: uppercase;
    }

    @media (max-width: 1180px) {
        .product-kpis {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    @media (max-width: 992px) {
        .product-table-wrap {
            max-width: calc(100vw - 36px);
        }
    }

    @media (max-width: 680px) {
        .product-kpis {
            grid-template-columns: 1fr;
        }

        .product-toolbar {
            align-items: stretch;
        }

        .product-toolbar-actions,
        .product-action {
            width: 100%;
        }

        .product-action {
            justify-content: center;
        }
    }
</style>

<div class="product-page">
    <section class="product-panel">
        <div class="product-toolbar">
            <div>
                <div class="product-section-title">Inventory Workbench</div>
                <span class="product-muted">Manage products, pricing, stock readiness, and catalog actions from one register.</span>
            </div>
            <div class="product-toolbar-actions">
                <a class="product-action primary" href="<?= product_page_h(onyx_legacy_url('products_action.php?action=add')) ?>">
                    <i class="fa-solid fa-plus"></i>
                    Add Product
                </a>
                <a class="product-action" href="<?= product_page_h(onyx_legacy_url('products_action.php?action=add_category&type=product')) ?>">
                    <i class="fa-solid fa-tags"></i>
                    Product Category
                </a>
                <a class="product-action" href="<?= product_page_h(onyx_legacy_url('products_action.php?action=add_category&type=income')) ?>">
                    <i class="fa-solid fa-coins"></i>
                    Income Category
                </a>
                <a class="product-action" href="<?= product_page_h(onyx_legacy_url('products_action.php?action=add_category&type=expense')) ?>">
                    <i class="fa-solid fa-receipt"></i>
                    Expense Category
                </a>
                <a class="product-action" href="<?= product_page_h(onyx_legacy_url('products_action.php?action=bulk_update')) ?>">
                    <i class="fa-solid fa-percent"></i>
                    Update Prices
                </a>
                <a class="product-action" href="<?= product_page_h(onyx_legacy_url('products_action.php?action=stock_adjust')) ?>">
                    <i class="fa-solid fa-boxes-stacked"></i>
                    Adjust Stock
                </a>
                <a class="product-action" href="<?= product_page_h(onyx_legacy_url('products_action.php?action=export')) ?>">
                    <i class="fa-solid fa-file-export"></i>
                    Export
                </a>
            </div>
        </div>
    </section>

    <section class="product-kpis" aria-label="Product summary">
        <div class="product-kpi">
            <span>Total Products</span>
            <strong><?= product_page_h($total_products) ?></strong>
        </div>
        <div class="product-kpi">
            <span>Healthy Stock</span>
            <strong><?= product_page_h($healthy_products) ?></strong>
        </div>
        <div class="product-kpi">
            <span>Needs Reorder</span>
            <strong><?= product_page_h($low_stock + $out_stock) ?></strong>
        </div>
        <div class="product-kpi">
            <span>Cost Value</span>
            <strong><?= product_page_h(product_page_money($stock_value, $currency)) ?></strong>
        </div>
        <div class="product-kpi">
            <span>Potential Margin</span>
            <strong><?= product_page_h(product_page_money($gross_margin_value, $currency)) ?></strong>
        </div>
    </section>

    <section class="product-panel">
        <div class="product-section-title">Product Filters</div>
        <div class="product-filters" style="margin-top:12px;">
            <div class="product-field search">
                <label for="product-search">Search</label>
                <input id="product-search" type="search" placeholder="Search name, SKU, barcode, category">
            </div>
            <div class="product-field">
                <label for="product-category">Category</label>
                <select id="product-category">
                    <option value="">All categories</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?= product_page_h(strtolower((string) $category['name'])) ?>"><?= product_page_h($category['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="product-field">
                <label for="product-stock">Stock</label>
                <select id="product-stock">
                    <option value="">All stock</option>
                    <option value="ok">In stock</option>
                    <option value="low">Low stock</option>
                    <option value="out">Out of stock</option>
                </select>
            </div>
            <div class="product-field">
                <label for="product-sort">Sort</label>
                <select id="product-sort">
                    <option value="name">Name</option>
                    <option value="stock_asc">Lowest stock</option>
                    <option value="margin_desc">Best margin</option>
                    <option value="selling_desc">Highest selling price</option>
                    <option value="value_desc">Highest stock value</option>
                </select>
            </div>
            <div class="product-count" id="product-count"><?= product_page_h($total_products) ?> shown</div>
        </div>
    </section>

    <section class="product-panel">
        <div class="product-section-title">Product Register</div>
        <div class="product-table-wrap">
            <table class="product-table" id="product-table">
                <colgroup>
                    <col style="width: 230px;">
                    <col style="width: 130px;">
                    <col style="width: 130px;">
                    <col style="width: 120px;">
                    <col style="width: 120px;">
                    <col style="width: 100px;">
                    <col style="width: 130px;">
                    <col style="width: 115px;">
                    <col style="width: 150px;">
                    <col style="width: 295px;">
                </colgroup>
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Category</th>
                        <th>SKU / Barcode</th>
                        <th>Cost</th>
                        <th>Selling</th>
                        <th>Margin</th>
                        <th>Stock</th>
                        <th>VAT</th>
                        <th>Stock Value</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="product-table-body">
                    <?php if ($products === []): ?>
                        <tr data-empty-row="1">
                            <td colspan="10">
                                <div class="product-empty">No products have been registered yet.</div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($products as $product): ?>
                            <?php
                                [$stockClass, $stockLabel] = product_page_stock_status($product);
                                $category = (string) ($product['category_name'] ?? 'Uncategorized');
                                $sku = (string) ($product['sku'] ?? '');
                                $barcode = (string) ($product['barcode'] ?? '');
                                $stock = (int) ($product['current_stock'] ?? 0);
                                $minimum = (int) ($product['min_stock'] ?? 0);
                                $margin = product_page_margin($product);
                                $stockValue = max(0, $stock) * (float) ($product['buying_price'] ?? 0);
                                $search = strtolower(trim(implode(' ', [
                                    $product['name'] ?? '',
                                    $sku,
                                    $barcode,
                                    $category,
                                    $product['description'] ?? '',
                                ])));
                            ?>
                            <tr
                                data-product-row="1"
                                data-search="<?= product_page_h($search) ?>"
                                data-category="<?= product_page_h(strtolower($category)) ?>"
                                data-stock-status="<?= product_page_h($stockClass) ?>"
                                data-name="<?= product_page_h(strtolower((string) $product['name'])) ?>"
                                data-stock="<?= product_page_h($stock) ?>"
                                data-margin="<?= product_page_h($margin) ?>"
                                data-selling="<?= product_page_h((float) ($product['selling_price'] ?? 0)) ?>"
                                data-value="<?= product_page_h($stockValue) ?>"
                            >
                                <td>
                                    <div class="product-name">
                                        <strong><?= product_page_h($product['name']) ?></strong>
                                        <span><?= product_page_h($product['description'] ?: 'No description captured') ?></span>
                                    </div>
                                </td>
                                <td><?= product_page_h($category ?: 'Uncategorized') ?></td>
                                <td>
                                    <span><?= product_page_h($sku ?: '-') ?></span>
                                    <span class="product-muted"><?= product_page_h($barcode ?: 'No barcode') ?></span>
                                </td>
                                <td><?= product_page_h(product_page_money($product['buying_price'], $currency)) ?></td>
                                <td><?= product_page_h(product_page_money($product['selling_price'], $currency)) ?></td>
                                <td><?= product_page_h(number_format($margin, 1)) ?>%</td>
                                <td>
                                    <span class="product-badge <?= product_page_h($stockClass) ?>"><?= product_page_h($stockLabel) ?></span>
                                    <span class="product-muted"><?= product_page_h($stock) ?> on hand / min <?= product_page_h($minimum) ?></span>
                                </td>
                                <td><?= product_page_h(number_format((float) ($product['vat_rate'] ?? 0), 2)) ?>%</td>
                                <td><?= product_page_h(product_page_money($stockValue, $currency)) ?></td>
                                <td>
                                    <div class="product-table-actions">
                                        <a class="product-action" href="<?= product_page_h(onyx_legacy_url('products_action.php?action=view&id=' . (int) $product['id'])) ?>">
                                            <i class="fa-solid fa-eye"></i>
                                            View
                                        </a>
                                        <a class="product-action" href="<?= product_page_h(onyx_legacy_url('products_action.php?action=edit&id=' . (int) $product['id'])) ?>">
                                            <i class="fa-solid fa-pen"></i>
                                            Edit
                                        </a>
                                        <a class="product-action danger" href="<?= product_page_h(onyx_legacy_url('products_action.php?action=delete&id=' . (int) $product['id'])) ?>">
                                            <i class="fa-solid fa-trash"></i>
                                            Delete
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>

    <section class="product-panel">
        <div class="product-section-title">Reorder Watchlist</div>
        <?php if ($reorder_products === []): ?>
            <div class="product-empty" style="margin-top:14px;">No products are currently below reorder level.</div>
        <?php else: ?>
            <table class="product-compact-table">
                <colgroup>
                    <col style="width: 34%;">
                    <col style="width: 18%;">
                    <col style="width: 16%;">
                    <col style="width: 16%;">
                    <col style="width: 16%;">
                </colgroup>
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Category</th>
                        <th>Current Stock</th>
                        <th>Reorder Level</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($reorder_products as $product): ?>
                        <?php [$stockClass, $stockLabel] = product_page_stock_status($product); ?>
                        <tr>
                            <td>
                                <div class="product-name">
                                    <strong><?= product_page_h($product['name']) ?></strong>
                                    <span><?= product_page_h($product['sku'] ?: 'No SKU') ?></span>
                                </div>
                            </td>
                            <td><?= product_page_h($product['category_name'] ?: 'Uncategorized') ?></td>
                            <td><?= product_page_h((int) ($product['current_stock'] ?? 0)) ?></td>
                            <td><?= product_page_h((int) ($product['min_stock'] ?? 0)) ?></td>
                            <td><span class="product-badge <?= product_page_h($stockClass) ?>"><?= product_page_h($stockLabel) ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </section>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const searchInput = document.getElementById('product-search');
    const categoryInput = document.getElementById('product-category');
    const stockInput = document.getElementById('product-stock');
    const sortInput = document.getElementById('product-sort');
    const body = document.getElementById('product-table-body');
    const count = document.getElementById('product-count');
    const rows = Array.from(body.querySelectorAll('[data-product-row="1"]'));

    function value(row, key) {
        return row.dataset[key] || '';
    }

    function numeric(row, key) {
        return Number(value(row, key)) || 0;
    }

    function applyProductsView() {
        const search = (searchInput.value || '').trim().toLowerCase();
        const category = categoryInput.value || '';
        const stock = stockInput.value || '';
        const sort = sortInput.value || 'name';

        rows.forEach(function (row) {
            const matchesSearch = search === '' || value(row, 'search').includes(search);
            const matchesCategory = category === '' || value(row, 'category') === category;
            const matchesStock = stock === '' || value(row, 'stockStatus') === stock;
            row.hidden = !(matchesSearch && matchesCategory && matchesStock);
        });

        const sorted = rows.slice().sort(function (a, b) {
            if (sort === 'stock_asc') {
                return numeric(a, 'stock') - numeric(b, 'stock');
            }
            if (sort === 'margin_desc') {
                return numeric(b, 'margin') - numeric(a, 'margin');
            }
            if (sort === 'selling_desc') {
                return numeric(b, 'selling') - numeric(a, 'selling');
            }
            if (sort === 'value_desc') {
                return numeric(b, 'value') - numeric(a, 'value');
            }
            return value(a, 'name').localeCompare(value(b, 'name'));
        });

        sorted.forEach(function (row) {
            body.appendChild(row);
        });

        const visible = rows.filter(function (row) {
            return !row.hidden;
        }).length;
        count.textContent = visible + ' shown';
    }

    [searchInput, categoryInput, stockInput, sortInput].forEach(function (input) {
        input.addEventListener('input', applyProductsView);
        input.addEventListener('change', applyProductsView);
    });

    applyProductsView();
});
</script>

<?php onyx_page_end(); ?>
