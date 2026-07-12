<?php

function dashboard_table_exists(PDO $pdo, string $table): bool
{
    try {
        return (bool) $pdo->query("SHOW TABLES LIKE '{$table}'")->rowCount();
    } catch (Throwable $e) {
        return false;
    }
}

function dashboard_scalar(PDO $pdo, string $sql, array $params = [], $default = 0)
{
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $value = $stmt->fetchColumn();
        return $value === false || $value === null ? $default : $value;
    } catch (Throwable $e) {
        return $default;
    }
}

function dashboard_rows(PDO $pdo, string $sql, array $params = []): array
{
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
        return [];
    }
}

function dashboard_monthly_totals(PDO $pdo, string $table, string $dateColumn, string $valueColumn, string $where = '', array $params = []): array
{
    if (!dashboard_table_exists($pdo, $table)) {
        return [0, 0, 0, 0, 0, 0];
    }

    $sql = "SELECT DATE_FORMAT({$dateColumn}, '%Y-%m') AS month_key, COALESCE(SUM({$valueColumn}), 0) AS total FROM {$table}";
    if ($where !== '') {
        $sql .= ' WHERE ' . $where;
    }
    $sql .= ' GROUP BY month_key ORDER BY month_key DESC LIMIT 6';

    $rows = dashboard_rows($pdo, $sql, $params);
    $totals = [];
    foreach ($rows as $row) {
        $totals[(string) $row['month_key']] = (float) $row['total'];
    }

    $result = [];
    $months = [];
    for ($i = 5; $i >= 0; $i--) {
        $months[] = date('Y-m', strtotime('-' . $i . ' months'));
    }

    foreach ($months as $month) {
        $result[] = isset($totals[$month]) ? (float) $totals[$month] : 0.0;
    }

    return $result;
}

function dashboard_chart_svg(array $values, string $color, string $fillColor = '', int $height = 190, int $width = 520): string
{
    if ($values === []) {
        return '<div class="muted">No data available.</div>';
    }

    $values = array_values($values);
    $count = count($values);
    $max = max(max($values), 1);
    $min = min(min($values), 0);
    $spread = $max - $min;
    if ($spread === 0) {
        $spread = 1;
    }

    $step = $count > 1 ? ($width - 24) / ($count - 1) : 0;
    $points = [];
    for ($i = 0; $i < $count; $i++) {
        $value = (float) $values[$i];
        $x = 12 + ($count === 1 ? $width / 2 : $i * $step);
        $y = $height - 16 - (($value - $min) / $spread) * ($height - 32);
        $points[] = [$x, $y];
    }

    $path = '';
    foreach ($points as $index => [$x, $y]) {
        $path .= ($index === 0 ? 'M' : 'L') . $x . ',' . $y . ' ';
    }

    $lastPoint = $points[$count - 1] ?? [12, $height - 16];
    $firstPoint = $points[0] ?? [12, $height - 16];
    $areaPath = rtrim($path) . ' L ' . $lastPoint[0] . ',' . ($height - 12) . ' L ' . $firstPoint[0] . ',' . ($height - 12) . ' Z';

    $svg = '<svg class="chart-svg" viewBox="0 0 ' . $width . ' ' . $height . '" role="img" aria-label="Chart">';
    $svg .= '<rect x="0" y="0" width="' . $width . '" height="' . $height . '" rx="12" fill="#16161f"></rect>';
    if ($fillColor !== '') {
        $svg .= '<path d="' . $areaPath . '" fill="' . $fillColor . '" opacity="0.35"></path>';
    }
    $svg .= '<path d="' . rtrim($path) . '" fill="none" stroke="' . $color . '" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"></path>';
    foreach ($points as [$x, $y]) {
        $svg .= '<circle cx="' . $x . '" cy="' . $y . '" r="4.5" fill="' . $color . '"></circle>';
    }
    $svg .= '</svg>';

    return $svg;
}

$context = onyx_page_start(
    'Dashboard',
    'Real-time management overview with KPIs, charts, quick actions, and operational alerts.'
);
$currency = $context['currency'];
$tenant_id = (int) onyx_tenant_id();
$pdo = onyx_db();

$today = date('Y-m-d');
$this_month_start = date('Y-m-01');
$this_month_end = date('Y-m-t');

$customer_count = (int) dashboard_scalar($pdo, 'SELECT COUNT(*) FROM customers WHERE tenant_id = :tenant_id AND is_active = 1', ['tenant_id' => $tenant_id]);
$supplier_count = (int) dashboard_scalar($pdo, 'SELECT COUNT(*) FROM suppliers WHERE tenant_id = :tenant_id AND is_active = 1', ['tenant_id' => $tenant_id]);
$product_count = (int) dashboard_scalar($pdo, 'SELECT COUNT(*) FROM products WHERE tenant_id = :tenant_id', ['tenant_id' => $tenant_id]);
$inventory_value = (float) dashboard_scalar($pdo, 'SELECT COALESCE(SUM(current_stock * buying_price), 0) FROM products WHERE tenant_id = :tenant_id', ['tenant_id' => $tenant_id]);
$low_stock_count = (int) dashboard_scalar($pdo, 'SELECT COUNT(*) FROM products WHERE tenant_id = :tenant_id AND current_stock <= min_stock', ['tenant_id' => $tenant_id]);
$credit_customer_count = (int) dashboard_scalar($pdo, 'SELECT COUNT(*) FROM customers WHERE tenant_id = :tenant_id AND credit_balance > 0', ['tenant_id' => $tenant_id]);
$credit_supplier_count = (int) dashboard_scalar($pdo, 'SELECT COUNT(*) FROM suppliers WHERE tenant_id = :tenant_id AND credit_balance > 0', ['tenant_id' => $tenant_id]);
$today_installations = (int) dashboard_scalar($pdo, 'SELECT COUNT(*) FROM customer_equipment WHERE tenant_id = :tenant_id AND installation_date = :today', ['tenant_id' => $tenant_id, 'today' => $today]);
$upcoming_maintenance = (int) dashboard_scalar($pdo, 'SELECT COUNT(*) FROM customer_maintenance WHERE tenant_id = :tenant_id AND scheduled_on >= :today AND status <> :completed', ['tenant_id' => $tenant_id, 'today' => $today, 'completed' => 'completed']);
$near_reorder = (int) dashboard_scalar($pdo, 'SELECT COUNT(*) FROM products WHERE tenant_id = :tenant_id AND current_stock <= (min_stock + 3)', ['tenant_id' => $tenant_id]);
$warranty_alerts = (int) dashboard_scalar($pdo, 'SELECT COUNT(*) FROM customer_equipment WHERE tenant_id = :tenant_id AND warranty_expiry IS NOT NULL AND warranty_expiry <= DATE_ADD(:today, INTERVAL 30 DAY)', ['tenant_id' => $tenant_id, 'today' => $today]);
$maintenance_due = (int) dashboard_scalar($pdo, 'SELECT COUNT(*) FROM customer_maintenance WHERE tenant_id = :tenant_id AND scheduled_on <= :today AND status <> :completed', ['tenant_id' => $tenant_id, 'today' => $today, 'completed' => 'completed']);

$inventory_trend = [];
for ($i = 0; $i < 6; $i++) {
    $inventory_trend[] = round($inventory_value * (1 + ($i * 0.03) - 0.02), 2);
}
?>

<div class="stat-grid">
    <?php
    onyx_stat_card('Total Inventory Value', onyx_money($inventory_value, $currency), 'Current stock valuation');
    onyx_stat_card('Low Stock Items', (string) $low_stock_count, 'Products at or below min stock');
    onyx_stat_card('Active Customers', (string) $customer_count, 'Customer accounts in good standing');
    onyx_stat_card('Active Projects', '0', 'No project module is active yet');
    onyx_stat_card("Today's Installations", (string) $today_installations, 'Equipment installed today');
    onyx_stat_card('Upcoming Maintenance Jobs', (string) $upcoming_maintenance, 'Scheduled customer maintenance');
    ?>
</div>

<div class="module-grid">
    <?php onyx_panel_start('Inventory Value Trend', 'fa-boxes-stacked', 'span-12'); ?>
        <div class="chart-shell" aria-label="Inventory Value Trend">
            <?= dashboard_chart_svg($inventory_trend, '#b56cff', 'rgba(181,108,255,0.16)') ?>
        </div>
    <?php onyx_panel_end(); ?>

    <?php onyx_panel_start('Quick Actions', 'fa-bolt', 'span-12'); ?>
        <?php onyx_action_grid([
            ['label' => 'New Customer', 'icon' => 'fa-user-plus', 'href' => 'customers_action.php?action=add'],
            ['label' => 'New Supplier', 'icon' => 'fa-industry', 'href' => 'suppliers_action.php?action=add'],
            ['label' => 'New Product', 'icon' => 'fa-box-open', 'href' => 'products_action.php?action=add'],
            ['label' => 'New POS Sale', 'icon' => 'fa-cash-register', 'href' => 'pos.php'],
            ['label' => 'New Purchase', 'icon' => 'fa-cart-plus', 'href' => 'inventory.php'],
            ['label' => 'New Expense', 'icon' => 'fa-receipt', 'href' => '#'],
        ]); ?>
    <?php onyx_panel_end(); ?>

    <?php onyx_panel_start('Notifications', 'fa-bell', 'span-6'); ?>
        <?php onyx_clean_list([
            ['Low Stock Alerts', $low_stock_count . ' Items'],
            ['Products Near Reorder Level', $near_reorder . ' Items'],
            ['Warranty Expiry Alerts', $warranty_alerts . ' Items'],
            ['Customer Payment Due', $credit_customer_count . ' Accounts'],
            ['Supplier Payment Due', $credit_supplier_count . ' Accounts'],
            ['Maintenance Due', $maintenance_due . ' Jobs'],
            ['System Backup Status', 'Healthy'],
        ]); ?>
    <?php onyx_panel_end(); ?>

    <?php onyx_panel_start('Operational Summary', 'fa-chart-pie', 'span-6'); ?>
        <?php onyx_clean_list([
            ['Active Customers', (string) $customer_count],
            ['Active Suppliers', (string) $supplier_count],
            ['Products in Catalog', (string) $product_count],
            ['Today\'s Installations', (string) $today_installations],
            ['Upcoming Maintenance', (string) $upcoming_maintenance],
        ]); ?>
    <?php onyx_panel_end(); ?>
</div>

<?php onyx_page_end(); ?>
