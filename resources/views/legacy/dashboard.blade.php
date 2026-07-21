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

function dashboard_revenue_pie_svg(array $items, string $currency): string
{
    $values = [];
    foreach ($items as $item) {
        $value = max(0, (float) ($item['total_value'] ?? 0));
        if ($value > 0) {
            $values[] = [
                'label' => (string) ($item['product_name'] ?? 'Revenue'),
                'value' => $value,
            ];
        }
    }

    $total = array_sum(array_column($values, 'value'));
    if ($total <= 0) {
        return '<div class="revenue-pie-empty">No revenue mix yet</div>';
    }

    $strokes = ['#ffffff', '#d8d8de', '#ffb347', '#8d99a8', '#263241'];
    $svg = '<svg class="revenue-pie" viewBox="0 0 120 120" role="img" aria-label="Revenue by product distribution">';
    $svg .= '<circle cx="60" cy="60" r="42" fill="none" stroke="rgba(255,255,255,.055)" stroke-width="18"></circle>';

    $offset = 0.0;
    foreach ($values as $index => $item) {
        $percent = ($item['value'] / $total) * 100;
        $label = htmlspecialchars($item['label'], ENT_QUOTES, 'UTF-8');
        $amount = htmlspecialchars(onyx_money($item['value'], $currency), ENT_QUOTES, 'UTF-8');
        $percentLabel = number_format($percent, 1) . '%';
        $stroke = $strokes[$index % count($strokes)];
        $svg .= '<circle class="pie-segment" cx="60" cy="60" r="42" fill="none" stroke="' . $stroke . '" stroke-width="18" pathLength="100" stroke-dasharray="' . round($percent, 3) . ' ' . round(100 - $percent, 3) . '" stroke-dashoffset="' . round(-$offset, 3) . '" transform="rotate(-90 60 60)">';
        $svg .= '<title>' . $label . ': ' . $amount . ' (' . $percentLabel . ')</title>';
        $svg .= '</circle>';
        $offset += $percent;
    }

    $svg .= '<circle cx="60" cy="60" r="27" fill="#0a0a0c"></circle>';
    $svg .= '<text x="60" y="56" text-anchor="middle" class="pie-total-label">Total</text>';
    $svg .= '<text x="60" y="70" text-anchor="middle" class="pie-total-value">' . htmlspecialchars(onyx_money($total, $currency), ENT_QUOTES, 'UTF-8') . '</text>';
    $svg .= '</svg>';

    return $svg;
}

$context = onyx_page_start(
    'Dashboard',
    'Compact business control board with revenue, customers, invoices, stock, reports, and operational alerts.'
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

$sales_today = (float) dashboard_scalar($pdo, 'SELECT COALESCE(SUM(total), 0) FROM invoices WHERE tenant_id = :tenant_id AND invoice_type = "invoice" AND invoice_date = :today', ['tenant_id' => $tenant_id, 'today' => $today]);
$sales_month = (float) dashboard_scalar($pdo, 'SELECT COALESCE(SUM(total), 0) FROM invoices WHERE tenant_id = :tenant_id AND invoice_type = "invoice" AND invoice_date BETWEEN :start_date AND :end_date', ['tenant_id' => $tenant_id, 'start_date' => $this_month_start, 'end_date' => $this_month_end]);
$payments_today = (float) dashboard_scalar($pdo, 'SELECT COALESCE(SUM(amount), 0) FROM invoice_payments WHERE tenant_id = :tenant_id AND payment_date = :today', ['tenant_id' => $tenant_id, 'today' => $today]);
$open_invoice_balance = (float) dashboard_scalar($pdo, 'SELECT COALESCE(SUM(i.total), 0) - COALESCE(SUM(paid.total_paid), 0) FROM invoices i LEFT JOIN (SELECT invoice_id, tenant_id, SUM(amount) AS total_paid FROM invoice_payments GROUP BY invoice_id, tenant_id) paid ON paid.invoice_id = i.id AND paid.tenant_id = i.tenant_id WHERE i.tenant_id = :tenant_id AND i.invoice_type = "invoice" AND i.status <> "paid"', ['tenant_id' => $tenant_id]);
$quotation_value = (float) dashboard_scalar($pdo, 'SELECT COALESCE(SUM(total), 0) FROM invoices WHERE tenant_id = :tenant_id AND invoice_type = "quotation" AND status IN ("draft", "sent", "approved")', ['tenant_id' => $tenant_id]);
$purchase_month = (float) dashboard_scalar($pdo, 'SELECT COALESCE(SUM(total_amount), 0) FROM purchases WHERE tenant_id = :tenant_id AND purchase_date BETWEEN :start_date AND :end_date', ['tenant_id' => $tenant_id, 'start_date' => $this_month_start, 'end_date' => $this_month_end]);
$purchase_count = (int) dashboard_scalar($pdo, 'SELECT COUNT(*) FROM purchases WHERE tenant_id = :tenant_id AND purchase_date BETWEEN :start_date AND :end_date', ['tenant_id' => $tenant_id, 'start_date' => $this_month_start, 'end_date' => $this_month_end]);
$gross_margin_estimate = max(0, $sales_month - $purchase_month);

$sales_trend = dashboard_monthly_totals($pdo, 'invoices', 'invoice_date', 'total', 'tenant_id = :tenant_id AND invoice_type = "invoice"', ['tenant_id' => $tenant_id]);
$purchase_trend = dashboard_monthly_totals($pdo, 'purchases', 'purchase_date', 'total_amount', 'tenant_id = :tenant_id', ['tenant_id' => $tenant_id]);

$recent_invoices = dashboard_rows($pdo, 'SELECT i.id, i.invoice_number, i.invoice_date, i.total, i.status, COALESCE(c.name, "Walk-in customer") AS customer_name FROM invoices i LEFT JOIN customers c ON c.id = i.customer_id WHERE i.tenant_id = :tenant_id AND i.invoice_type = "invoice" ORDER BY i.invoice_date DESC, i.id DESC LIMIT 6', ['tenant_id' => $tenant_id]);
$recent_purchases = dashboard_rows($pdo, 'SELECT id, supplier, purchase_date, total_amount FROM purchases WHERE tenant_id = :tenant_id ORDER BY purchase_date DESC, id DESC LIMIT 6', ['tenant_id' => $tenant_id]);
$low_stock_rows = dashboard_rows($pdo, 'SELECT sku, name, current_stock, min_stock FROM products WHERE tenant_id = :tenant_id AND current_stock <= min_stock ORDER BY current_stock ASC, name ASC LIMIT 6', ['tenant_id' => $tenant_id]);
$top_products = dashboard_rows($pdo, 'SELECT COALESCE(p.name, il.description) AS product_name, COALESCE(SUM(il.quantity), 0) AS units, COALESCE(SUM(il.line_total), 0) AS total_value FROM invoice_lines il INNER JOIN invoices i ON i.id = il.invoice_id LEFT JOIN products p ON p.id = il.product_id WHERE i.tenant_id = :tenant_id AND i.invoice_type = "invoice" AND i.invoice_date BETWEEN :start_date AND :end_date GROUP BY product_name ORDER BY total_value DESC LIMIT 5', ['tenant_id' => $tenant_id, 'start_date' => $this_month_start, 'end_date' => $this_month_end]);
$new_customers_month = (int) dashboard_scalar($pdo, 'SELECT COUNT(*) FROM customers WHERE tenant_id = :tenant_id AND created_at BETWEEN :start_date AND :end_date', ['tenant_id' => $tenant_id, 'start_date' => $this_month_start . ' 00:00:00', 'end_date' => $this_month_end . ' 23:59:59']);
$invoice_count_month = (int) dashboard_scalar($pdo, 'SELECT COUNT(*) FROM invoices WHERE tenant_id = :tenant_id AND invoice_type = "invoice" AND invoice_date BETWEEN :start_date AND :end_date', ['tenant_id' => $tenant_id, 'start_date' => $this_month_start, 'end_date' => $this_month_end]);
$paid_invoice_count = (int) dashboard_scalar($pdo, 'SELECT COUNT(*) FROM invoices WHERE tenant_id = :tenant_id AND invoice_type = "invoice" AND status = "paid" AND invoice_date BETWEEN :start_date AND :end_date', ['tenant_id' => $tenant_id, 'start_date' => $this_month_start, 'end_date' => $this_month_end]);
$completion_rate = $invoice_count_month > 0 ? round(($paid_invoice_count / $invoice_count_month) * 100, 1) : 0;
$recent_customers = dashboard_rows($pdo, 'SELECT id, name, phone, email, credit_balance, created_at FROM customers WHERE tenant_id = :tenant_id ORDER BY created_at DESC, id DESC LIMIT 6', ['tenant_id' => $tenant_id]);

$attention_items = [
    ['label' => 'Products near reorder', 'value' => $near_reorder . ' items', 'icon' => 'fa-boxes-stacked', 'href' => 'inventory.php'],
    ['label' => 'Customer balances due', 'value' => $credit_customer_count . ' accounts', 'icon' => 'fa-user-clock', 'href' => 'customers.php'],
    ['label' => 'Supplier balances due', 'value' => $credit_supplier_count . ' accounts', 'icon' => 'fa-industry', 'href' => 'suppliers.php'],
    ['label' => 'Maintenance due', 'value' => $maintenance_due . ' jobs', 'icon' => 'fa-screwdriver-wrench', 'href' => 'customers.php'],
    ['label' => 'Warranty expiry', 'value' => $warranty_alerts . ' assets', 'icon' => 'fa-shield-halved', 'href' => 'customers.php'],
];

$revenue_mix = $top_products !== [] ? array_slice($top_products, 0, 4) : [
    ['product_name' => 'POS Sales', 'units' => 0, 'total_value' => $sales_month],
    ['product_name' => 'Quotations', 'units' => 0, 'total_value' => $quotation_value],
    ['product_name' => 'Payments', 'units' => 0, 'total_value' => $payments_today],
    ['product_name' => 'Inventory', 'units' => 0, 'total_value' => $inventory_value],
];
$revenue_mix_total = array_sum(array_map(static fn (array $item): float => max(0, (float) ($item['total_value'] ?? 0)), $revenue_mix));
$report_links = [
    ['label' => 'Revenue Report', 'note' => 'Sales and payment performance', 'icon' => 'fa-chart-line', 'href' => 'reports.php'],
    ['label' => 'Inventory Report', 'note' => 'Stock value and reorder needs', 'icon' => 'fa-boxes-stacked', 'href' => 'inventory.php'],
    ['label' => 'Customer Report', 'note' => 'Balances and customer growth', 'icon' => 'fa-users', 'href' => 'customers.php'],
    ['label' => 'Supplier Report', 'note' => 'Supplier balances and purchases', 'icon' => 'fa-industry', 'href' => 'suppliers.php'],
];
$activity_stats = [
    ['label' => 'Customers', 'value' => $customer_count, 'icon' => 'fa-users'],
    ['label' => 'Products', 'value' => $product_count, 'icon' => 'fa-box'],
    ['label' => 'Low Stock', 'value' => $low_stock_count, 'icon' => 'fa-triangle-exclamation'],
    ['label' => 'Service Jobs', 'value' => $upcoming_maintenance, 'icon' => 'fa-screwdriver-wrench'],
];
$sales_goal = max($sales_month, 10000000);
$expense_goal = max($purchase_month, 6000000);
$profit_goal = max($gross_margin_estimate, 4000000);
$sales_progress = min(100, $sales_goal > 0 ? ($sales_month / $sales_goal) * 100 : 0);
$expense_progress = min(100, $expense_goal > 0 ? ($purchase_month / $expense_goal) * 100 : 0);
$profit_progress = min(100, $profit_goal > 0 ? ($gross_margin_estimate / $profit_goal) * 100 : 0);
?>

<style>
    .dash-board{--dash-white:#fff;--dash-soft:rgba(255,255,255,.08);--dash-line:rgba(255,255,255,.12);display:grid;gap:10px}
    .dash-hero{align-items:center;background:#050506;border:1px solid var(--dash-line);display:flex;justify-content:space-between;padding:10px 12px}
    .dash-hero h2{font-size:1rem;line-height:1.25;margin:0 0 4px}.dash-hero p{color:var(--onyx-muted);font-size:.72rem;margin:0}.dash-date{border:1px solid var(--dash-line);color:#fff;font-size:.66rem;font-weight:900;padding:7px 10px;text-transform:uppercase;white-space:nowrap}
    .dash-kpis{display:grid;gap:9px;grid-template-columns:repeat(5,minmax(118px,1fr))}.dash-kpi{background:linear-gradient(180deg,rgba(255,255,255,.045),rgba(255,255,255,.012));border:1px solid var(--dash-line);display:grid;gap:7px;grid-template-columns:38px minmax(0,1fr);min-height:76px;padding:10px}.dash-kpi i{align-items:center;background:#fff;border:1px solid #fff;color:#050506;display:flex;font-size:1rem;height:38px;justify-content:center;width:38px}.dash-kpi span{color:var(--onyx-muted);display:block;font-size:.56rem;font-weight:900;text-transform:uppercase}.dash-kpi strong{display:block;font-size:1rem;font-weight:900;line-height:1.12;margin-top:4px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}.dash-kpi small{color:#d8d8de;display:block;font-size:.62rem;margin-top:3px;white-space:nowrap}
    .dash-grid{display:grid;gap:10px;grid-template-columns:repeat(12,1fr)}.dash-panel{background:#0a0a0c;border:1px solid var(--dash-line);padding:11px}.span-2x{grid-column:span 2}.span-3x{grid-column:span 3}.span-4x{grid-column:span 4}.span-5x{grid-column:span 5}.span-6x{grid-column:span 6}.span-8x{grid-column:span 8}.span-12x{grid-column:span 12}.dash-title{align-items:center;display:flex;gap:8px;justify-content:space-between;margin-bottom:8px}.dash-title strong{font-size:.66rem;font-weight:900;text-transform:uppercase}.dash-title i{color:#fff}.dash-title a,.dash-tabs span{border:1px solid var(--dash-line);color:#fff;font-size:.56rem;font-weight:900;padding:4px 7px;text-transform:uppercase;text-decoration:none}.dash-tabs span:first-child{background:#fff;color:#050506}
    .chart-shell{min-height:152px;padding:0}.chart-svg{height:152px}.dash-chart-note{color:var(--onyx-muted);font-size:.62rem;font-weight:900;margin-bottom:7px;text-transform:uppercase}.dash-donut-wrap{align-items:center;display:grid;gap:12px;grid-template-columns:122px 1fr}.revenue-pie{display:block;height:118px;width:118px}.pie-segment{cursor:pointer;transition:opacity .16s ease,stroke-width .16s ease}.pie-segment:hover{opacity:.72;stroke-width:21}.pie-total-label{fill:#8d8d98;font-size:8px;font-weight:900;text-transform:uppercase}.pie-total-value{fill:#fff;font-size:8px;font-weight:900}.revenue-pie-empty{align-items:center;border:1px dashed var(--dash-line);color:var(--onyx-muted);display:flex;font-size:.66rem;font-weight:900;height:98px;justify-content:center;text-align:center;text-transform:uppercase;width:118px}.mini-list{display:grid;gap:7px}.mini-row{align-items:center;border-bottom:1px solid rgba(255,255,255,.045);display:grid;gap:8px;grid-template-columns:minmax(0,1fr) auto;padding-bottom:7px}.mini-row span{color:var(--onyx-muted);font-size:.66rem;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}.mini-row strong{font-size:.66rem;white-space:nowrap}
    .dash-table{border-collapse:collapse;width:100%}.dash-table th{color:var(--onyx-muted);font-size:.55rem;font-weight:900;padding:7px 6px;text-align:left;text-transform:uppercase}.dash-table td{border-top:1px solid rgba(255,255,255,.045);font-size:.66rem;padding:7px 6px}.dash-empty{border:1px dashed var(--dash-line);color:var(--onyx-muted);font-size:.7rem;padding:13px;text-align:center}.dash-status{background:rgba(255,255,255,.08);border:1px solid var(--dash-line);color:#fff;font-size:.55rem;font-weight:900;padding:3px 6px;text-transform:uppercase;white-space:nowrap}
    .attention-grid,.report-grid{display:grid;gap:7px}.attention-item,.report-item{align-items:center;background:rgba(255,255,255,.025);border:1px solid rgba(255,255,255,.055);display:flex;gap:9px;min-height:44px;padding:7px;text-decoration:none}.attention-item:hover,.report-item:hover{background:rgba(255,255,255,.08);text-decoration:none}.attention-icon,.report-icon{align-items:center;background:transparent;color:#d8d8de;display:flex;flex:0 0 18px;font-size:.72rem;height:18px;justify-content:center;width:18px}.attention-item strong,.report-item strong{display:block;font-size:.66rem}.attention-item span,.report-item span{color:var(--onyx-muted);display:block;font-size:.6rem;margin-top:2px}
    .dash-goals{display:grid;gap:10px}.dash-goal-head{align-items:center;display:flex;justify-content:space-between}.dash-goal-head span{font-size:.64rem;font-weight:800}.dash-goal-head small{color:var(--onyx-muted);font-size:.58rem}.dash-progress{background:rgba(255,255,255,.07);height:5px;overflow:hidden}.dash-progress span{background:#fff;display:block;height:100%}.activity-map{background:radial-gradient(circle at 25% 30%,rgba(255,255,255,.22),transparent 8%),radial-gradient(circle at 70% 22%,rgba(255,255,255,.18),transparent 7%),radial-gradient(circle at 52% 70%,rgba(255,255,255,.16),transparent 8%),linear-gradient(135deg,rgba(255,255,255,.04),rgba(255,255,255,.01));border:1px solid rgba(255,255,255,.055);min-height:110px}.activity-stats{display:grid;gap:8px;grid-template-columns:repeat(4,1fr);margin-top:8px}.activity-stat{align-items:center;display:flex;gap:7px}.activity-stat i{color:#fff}.activity-stat strong{display:block;font-size:.86rem}.activity-stat span{color:var(--onyx-muted);display:block;font-size:.58rem}
    .bottom-kpis{display:grid;gap:10px;grid-template-columns:repeat(3,1fr)}.bottom-card{align-items:center;background:#0a0a0c;border:1px solid var(--dash-line);display:flex;gap:12px;min-height:72px;padding:13px}.bottom-card i{align-items:center;background:#fff;color:#050506;display:flex;height:42px;justify-content:center;width:42px}.bottom-card strong{display:block;font-size:1.12rem}.bottom-card span{color:var(--onyx-muted);display:block;font-size:.64rem;text-transform:uppercase}
    @media(max-width:1080px){.dash-kpis{grid-template-columns:repeat(2,minmax(140px,1fr))}.span-2x,.span-3x,.span-4x,.span-5x,.span-6x,.span-8x{grid-column:span 12}.dash-donut-wrap{grid-template-columns:1fr}.bottom-kpis{grid-template-columns:1fr}}
    @media(max-width:760px){.dash-hero{align-items:flex-start;flex-direction:column}.dash-kpis,.activity-stats{grid-template-columns:1fr}.dash-grid{grid-template-columns:1fr}.span-2x,.span-3x,.span-4x,.span-5x,.span-6x,.span-8x,.span-12x{grid-column:span 1}}
</style>

<div class="dash-board">
    <section class="dash-kpis" aria-label="Business statistics">
        <div class="dash-kpi"><i class="fa-solid fa-dollar-sign"></i><div><span>Total Revenue</span><strong><?= htmlspecialchars(onyx_money($sales_month, $currency)) ?></strong><small>Month to date</small></div></div>
        <div class="dash-kpi"><i class="fa-solid fa-user-plus"></i><div><span>New Customers</span><strong><?= htmlspecialchars((string) $new_customers_month) ?></strong><small>This month</small></div></div>
        <div class="dash-kpi"><i class="fa-solid fa-file-invoice"></i><div><span>Total Invoices</span><strong><?= htmlspecialchars((string) $invoice_count_month) ?></strong><small><?= htmlspecialchars((string) $paid_invoice_count) ?> paid</small></div></div>
        <div class="dash-kpi"><i class="fa-solid fa-box-open"></i><div><span>Active Products</span><strong><?= htmlspecialchars((string) $product_count) ?></strong><small><?= htmlspecialchars(onyx_money($inventory_value, $currency)) ?> stock</small></div></div>
        <div class="dash-kpi"><i class="fa-solid fa-circle-check"></i><div><span>Completion Rate</span><strong><?= htmlspecialchars((string) $completion_rate) ?>%</strong><small>Paid invoice ratio</small></div></div>
    </section>

    <section class="dash-grid">
        <div class="dash-panel span-4x">
            <div class="dash-title">
                <strong><i class="fa-solid fa-chart-area"></i> Revenue overview</strong>
                <div class="dash-tabs"><span>Monthly</span></div>
            </div>
            <div class="dash-chart-note">Total revenue <?= htmlspecialchars(onyx_money($sales_month, $currency)) ?></div>
            <div class="chart-shell"><?= dashboard_chart_svg($sales_trend, '#ffffff', 'rgba(255,255,255,0.16)') ?></div>
        </div>

        <div class="dash-panel span-4x">
            <div class="dash-title"><strong><i class="fa-solid fa-ranking-star"></i> Top products</strong><a href="<?= htmlspecialchars(onyx_legacy_url('products.php')) ?>">View all</a></div>
            <?php if ($top_products === []): ?>
                <div class="dash-empty">No product sales ranked yet.</div>
            <?php else: ?>
                <div class="mini-list">
                    <?php foreach ($top_products as $product): ?>
                        <div class="mini-row"><span><?= htmlspecialchars($product['product_name'] ?? '-') ?></span><strong><?= htmlspecialchars(onyx_money((float) ($product['total_value'] ?? 0), $currency)) ?></strong></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="dash-panel span-4x">
            <div class="dash-title">
                <strong><i class="fa-solid fa-chart-pie"></i> Revenue by product</strong>
                <a href="<?= htmlspecialchars(onyx_legacy_url('sales.php')) ?>">View all</a>
            </div>
            <div class="dash-donut-wrap">
                <?= dashboard_revenue_pie_svg($revenue_mix, $currency) ?>
                <div class="dash-breakdown">
                    <?php foreach ($revenue_mix as $item): ?>
                        <?php
                        $mixValue = max(0, (float) ($item['total_value'] ?? 0));
                        $mixShare = $revenue_mix_total > 0 ? number_format(($mixValue / $revenue_mix_total) * 100, 1) . '%' : '0.0%';
                        ?>
                        <div class="mini-row" title="<?= htmlspecialchars(($item['product_name'] ?? '-') . ': ' . onyx_money($mixValue, $currency) . ' (' . $mixShare . ')') ?>">
                            <span><?= htmlspecialchars($item['product_name'] ?? '-') ?> · <?= htmlspecialchars($mixShare) ?></span>
                            <strong><?= htmlspecialchars(onyx_money($mixValue, $currency)) ?></strong>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

    </section>

    <section class="dash-grid">
        <div class="dash-panel span-6x">
            <div class="dash-title">
                <strong><i class="fa-solid fa-chart-column"></i> Invoice overview</strong>
                <div class="dash-tabs"><span>Month</span></div>
            </div>
            <div class="mini-list">
                <div class="mini-row"><span>Total invoices</span><strong><?= htmlspecialchars((string) $invoice_count_month) ?></strong></div>
                <div class="mini-row"><span>Paid invoices</span><strong><?= htmlspecialchars((string) $paid_invoice_count) ?></strong></div>
                <div class="mini-row"><span>Cash today</span><strong><?= htmlspecialchars(onyx_money($payments_today, $currency)) ?></strong></div>
                <div class="mini-row"><span>Open balance</span><strong><?= htmlspecialchars(onyx_money($open_invoice_balance, $currency)) ?></strong></div>
            </div>
        </div>

        <div class="dash-panel span-6x">
            <div class="dash-title"><strong><i class="fa-solid fa-file-invoice-dollar"></i> Purchases</strong><a href="<?= htmlspecialchars(onyx_legacy_url('purchases.php')) ?>">View all</a></div>
            <?php if ($recent_purchases === []): ?>
                <div class="dash-empty">No purchase records found yet.</div>
            <?php else: ?>
                <table class="dash-table"><thead><tr><th>Supplier</th><th>Date</th><th>Total</th></tr></thead><tbody>
                    <?php foreach ($recent_purchases as $purchase): ?>
                        <tr>
                            <td><?= htmlspecialchars($purchase['supplier'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($purchase['purchase_date'] ?? '-') ?></td>
                            <td><?= htmlspecialchars(onyx_money((float) ($purchase['total_amount'] ?? 0), $currency)) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody></table>
            <?php endif; ?>
        </div>
    </section>

    <section class="dash-grid">
        <div class="dash-panel span-4x">
            <div class="dash-title"><strong><i class="fa-solid fa-receipt"></i> Sales invoices</strong><a href="<?= htmlspecialchars(onyx_legacy_url('sales.php')) ?>">View all</a></div>
            <?php if ($recent_invoices === []): ?>
                <div class="dash-empty">No sales invoices found yet.</div>
            <?php else: ?>
                <table class="dash-table"><thead><tr><th>Invoice</th><th>Customer</th><th>Date</th><th>Total</th><th>Status</th></tr></thead><tbody>
                    <?php foreach ($recent_invoices as $invoice): ?>
                        <tr>
                            <td><?= htmlspecialchars($invoice['invoice_number'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($invoice['customer_name'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($invoice['invoice_date'] ?? '-') ?></td>
                            <td><?= htmlspecialchars(onyx_money((float) ($invoice['total'] ?? 0), $currency)) ?></td>
                            <td><span class="dash-status"><?= htmlspecialchars((string) ($invoice['status'] ?? 'draft')) ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody></table>
            <?php endif; ?>
        </div>

        <div class="dash-panel span-4x">
            <div class="dash-title"><strong><i class="fa-solid fa-users"></i> Customers</strong><a href="<?= htmlspecialchars(onyx_legacy_url('customers.php')) ?>">View all</a></div>
            <?php if ($recent_customers === []): ?>
                <div class="dash-empty">No customers found yet.</div>
            <?php else: ?>
                <table class="dash-table"><thead><tr><th>Customer</th><th>Phone</th><th>Balance</th></tr></thead><tbody>
                    <?php foreach ($recent_customers as $customer): ?>
                        <tr>
                            <td><?= htmlspecialchars($customer['name'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($customer['phone'] ?? '-') ?></td>
                            <td><?= htmlspecialchars(onyx_money((float) ($customer['credit_balance'] ?? 0), $currency)) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody></table>
            <?php endif; ?>
        </div>

        <div class="dash-panel span-4x">
            <div class="dash-title"><strong><i class="fa-solid fa-boxes-packing"></i> Product stock</strong><a href="<?= htmlspecialchars(onyx_legacy_url('products.php')) ?>">View all</a></div>
            <?php if ($low_stock_rows === []): ?>
                <div class="dash-empty">Stock levels are healthy.</div>
            <?php else: ?>
                <table class="dash-table"><thead><tr><th>Product</th><th>Stock</th><th>Min</th></tr></thead><tbody>
                    <?php foreach ($low_stock_rows as $product): ?>
                        <tr>
                            <td><?= htmlspecialchars($product['name'] ?? $product['sku'] ?? '-') ?></td>
                            <td><?= htmlspecialchars((string) ($product['current_stock'] ?? 0)) ?></td>
                            <td><?= htmlspecialchars((string) ($product['min_stock'] ?? 0)) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody></table>
            <?php endif; ?>
        </div>

        <div class="dash-panel span-3x">
            <div class="dash-title"><strong><i class="fa-solid fa-bell"></i> Attention</strong></div>
            <div class="attention-grid">
                <?php foreach ($attention_items as $item): ?>
                    <a class="attention-item" href="<?= htmlspecialchars(onyx_legacy_url($item['href'])) ?>">
                        <span class="attention-icon"><i class="fa-solid <?= htmlspecialchars($item['icon']) ?>"></i></span>
                        <span><strong><?= htmlspecialchars($item['label']) ?></strong><span><?= htmlspecialchars($item['value']) ?></span></span>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="dash-panel span-3x">
            <div class="dash-title"><strong><i class="fa-solid fa-folder-open"></i> Reports</strong><a href="<?= htmlspecialchars(onyx_legacy_url('reports.php')) ?>">View all</a></div>
            <div class="report-grid">
                <?php foreach ($report_links as $report): ?>
                    <a class="report-item" href="<?= htmlspecialchars(onyx_legacy_url($report['href'])) ?>">
                        <span class="report-icon"><i class="fa-solid <?= htmlspecialchars($report['icon']) ?>"></i></span>
                        <span><strong><?= htmlspecialchars($report['label']) ?></strong><span><?= htmlspecialchars($report['note']) ?></span></span>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="dash-panel span-6x">
            <div class="dash-title"><strong><i class="fa-solid fa-location-dot"></i> Business activity map</strong><div class="dash-tabs"><span>This Week</span></div></div>
            <div class="activity-map"></div>
            <div class="activity-stats">
                <?php foreach ($activity_stats as $activity): ?>
                    <div class="activity-stat"><i class="fa-solid <?= htmlspecialchars($activity['icon']) ?>"></i><div><strong><?= htmlspecialchars((string) $activity['value']) ?></strong><span><?= htmlspecialchars($activity['label']) ?></span></div></div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <section class="bottom-kpis">
        <div class="bottom-card"><i class="fa-solid fa-users"></i><div><span>Total Customers</span><strong><?= htmlspecialchars((string) $customer_count) ?></strong></div></div>
        <div class="bottom-card"><i class="fa-solid fa-file-circle-check"></i><div><span>Open Balance</span><strong><?= htmlspecialchars(onyx_money($open_invoice_balance, $currency)) ?></strong></div></div>
        <div class="bottom-card"><i class="fa-solid fa-rotate"></i><div><span>Gross Margin Estimate</span><strong><?= htmlspecialchars(onyx_money($gross_margin_estimate, $currency)) ?></strong></div></div>
    </section>
</div>

<?php onyx_page_end(); ?>
