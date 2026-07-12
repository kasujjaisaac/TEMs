<?php
require_once __DIR__ . '/includes/erp_layout.php';

$context = onyx_page_start('POS', 'Point of sale workspace for product search, cart checkout, payments, receipts, and shift reports.');
$currency = $context['currency'];
$tenant_id = onyx_tenant_id();
$pos_products = onyx_rows(
    'SELECT name, selling_price, current_stock
     FROM products
     WHERE tenant_id = :tenant_id
     ORDER BY name ASC
     LIMIT 12',
    ['tenant_id' => $tenant_id]
);
?>

<div class="pos-layout">
    <section class="panel">
        <div class="panel-title"><i class="fa-solid fa-cash-register"></i> Main Interface</div>
        <div style="display: grid; gap: 12px; margin-bottom: 16px;">
            <div class="input-like"><i class="fa-solid fa-magnifying-glass"></i> Product Search</div>
        </div>
        <div class="product-grid">
            <?php if ($pos_products === []): ?>
                <div class="muted">No products found for this workspace.</div>
            <?php else: ?>
                <?php foreach ($pos_products as $product): ?>
                    <div class="product-tile">
                        <strong><?= htmlspecialchars($product['name']) ?></strong><br>
                        <span class="muted"><?= htmlspecialchars(onyx_money((float) $product['selling_price'], $currency)) ?></span><br>
                        <span class="muted">Stock: <?= htmlspecialchars((string) $product['current_stock']) ?></span>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </section>

    <aside class="panel">
        <div class="panel-title"><i class="fa-solid fa-cart-shopping"></i> Shopping Cart</div>
        <?php onyx_clean_list([
            ['Items', '0'],
            ['Subtotal', '0.00'],
            ['Tax', '0.00'],
            ['Total', '0.00'],
        ]); ?>
        <div style="height: 18px;"></div>
        <div class="panel-title"><i class="fa-solid fa-credit-card"></i> Checkout</div>
        <?php onyx_action_grid([
            ['label' => 'Cash Payment', 'icon' => 'fa-money-bill'],
            ['label' => 'Mobile Money', 'icon' => 'fa-mobile-screen'],
            ['label' => 'Bank Transfer', 'icon' => 'fa-building-columns'],
            ['label' => 'Credit Sale', 'icon' => 'fa-hand-holding-dollar'],
        ]); ?>
    </aside>
</div>

<div class="module-grid" style="margin-top: 18px;">
    <?php onyx_panel_start('Receipt', 'fa-receipt', 'span-6'); ?>
        <?php onyx_action_grid([
            ['label' => 'Print Receipt', 'icon' => 'fa-print'],
            ['label' => 'Email Receipt', 'icon' => 'fa-envelope'],
            ['label' => 'SMS Receipt', 'icon' => 'fa-comment-sms'],
        ]); ?>
    <?php onyx_panel_end(); ?>

    <?php onyx_panel_start('POS Reports', 'fa-chart-pie', 'span-6'); ?>
        <?php onyx_clean_list([
            ['Shift Reports', '0 Open'],
            ['Cash Drawer Report', '0.00'],
            ['Daily Collections', '0.00'],
        ]); ?>
    <?php onyx_panel_end(); ?>
</div>

<?php onyx_page_end(); ?>
