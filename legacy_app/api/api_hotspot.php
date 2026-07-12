<?php
ini_set('display_errors', 0);
error_reporting(0);
header('Content-Type: application/json');

try {

    require_once($_SERVER['DOCUMENT_ROOT'] . '/db.php');

    if (!isset($pdo)) {
        throw new Exception("Database connection not found");
    }

    $tenant_id = $_GET['tenant_id'] ?? 0;
    $action = $_GET['action'] ?? '';

    if (!$tenant_id) {
        throw new Exception("Missing tenant_id");
    }

    // ============================================
    // 🔥 COMMON FINANCIAL DATA (SAME AS DASHBOARD)
    // ============================================
    function getFinancials($pdo, $tenant_id) {

        // Net Sales
        $stmt = $pdo->prepare("
            SELECT COALESCE(SUM(p.price),0) as total
            FROM vouchers v
            JOIN packages p ON v.package_id = p.id
            WHERE v.tenant_id = :tenant_id
        ");
        $stmt->execute(['tenant_id' => $tenant_id]);
        $net_sales = (int)$stmt->fetch(PDO::FETCH_ASSOC)['total'];

        // Commission (5%)
        $commission = (int) round($net_sales * 0.05);

        // Withdrawals
        $stmt = $pdo->prepare("
            SELECT COALESCE(SUM(amount),0) as total
            FROM withdrawals
            WHERE tenant_id = :tenant_id
            AND status = 'completed'
        ");
        $stmt->execute(['tenant_id' => $tenant_id]);
        $withdrawals = (int)$stmt->fetch(PDO::FETCH_ASSOC)['total'];

        // Balance
        $balance = $net_sales - ($commission + $withdrawals);

        return [
            "net_sales" => $net_sales,
            "commission" => $commission,
            "balance" => $balance
        ];
    }

    // ============================================
    // 📊 OVERVIEW (CHART + KPIs)
    // ============================================
    if ($action === 'overview') {

        $days = (int)($_GET['days'] ?? 30);

        // Financials
        $financials = getFinancials($pdo, $tenant_id);

        // Chart Data
        $stmt = $pdo->prepare("
            SELECT DATE(v.created_at) as day,
                   SUM(p.price) as total
            FROM vouchers v
            JOIN packages p ON v.package_id = p.id
            WHERE v.tenant_id = :tenant_id
            AND v.created_at >= DATE_SUB(NOW(), INTERVAL :days DAY)
            GROUP BY day
            ORDER BY day ASC
        ");
        $stmt->bindValue(':tenant_id', $tenant_id);
        $stmt->bindValue(':days', $days, PDO::PARAM_INT);
        $stmt->execute();

        $labels = [];
        $proceeds = [];

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $labels[] = date('d M', strtotime($row['day']));
            $proceeds[] = (int)$row['total'];
        }

        // Fake commission series for chart
        $commission_series = array_map(fn($v) => (int)($v * 0.05), $proceeds);

        $gross = array_map(function($p, $c){
            return $p + $c;
        }, $proceeds, $commission_series);

        // Active users
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM pppoe_users WHERE status='online'");
        $active_users = (int)$stmt->fetch(PDO::FETCH_ASSOC)['total'];

        echo json_encode([
            "success" => true,
            "totals" => [
                "netSales" => $financials['net_sales'],
                "commissions" => $financials['commission'],
                "balance" => $financials['balance']
            ],
            "labels" => $labels,
            "series" => [
                "proceeds" => $proceeds,
                "commission" => $commission_series,
                "gross" => $gross
            ],
            "active_nodes" => $active_users,
            "cpu_usage" => rand(10, 40),
            "total_data" => rand(1, 5)
        ]);
    }

    // ============================================
    // 🧾 RECENT SALES
    // ============================================
    elseif ($action === 'recent_sales') {

        $stmt = $pdo->prepare("
            SELECT v.id, p.price, v.created_at
            FROM vouchers v
            JOIN packages p ON v.package_id = p.id
            WHERE v.tenant_id = :tenant_id
            ORDER BY v.created_at DESC
            LIMIT 10
        ");
        $stmt->execute(['tenant_id' => $tenant_id]);

        $sales = [];

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $sales[] = [
                "name" => "Voucher #" . $row['id'],
                "amount" => (int)$row['price'],
                "ago" => timeAgo($row['created_at'])
            ];
        }

        echo json_encode($sales);
    }

    else {
        throw new Exception("Invalid action");
    }

} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "error" => $e->getMessage()
    ]);
}


// ============================================
// ⏱️ TIME AGO HELPER
// ============================================
function timeAgo($datetime) {
    $time = time() - strtotime($datetime);

    if ($time < 60) return $time . " sec ago";
    if ($time < 3600) return floor($time/60) . " min ago";
    if ($time < 86400) return floor($time/3600) . " hrs ago";
    return floor($time/86400) . " days ago";
}