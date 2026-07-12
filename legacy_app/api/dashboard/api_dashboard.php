<?php
// /api/dashboard/api_dashboard.php
header('Content-Type: application/json');
require_once($_SERVER['DOCUMENT_ROOT'] . '/config.php');

$action = $_GET['action'] ?? '';
$tenant_id = (int)($_GET['tenant_id'] ?? 80);
$router_id = (int)($_GET['router_id'] ?? 0);

try {
    // ACTION 1: Populate the Dropdown Selector
    if ($action === 'get_router_list') {
        $stmt = $pdo->prepare("SELECT id, router_name FROM routers WHERE tenant_id = ? AND status = 'online'");
        $stmt->execute([$tenant_id]);
        echo json_encode(['success' => true, 'routers' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
        exit;
    }

    // ACTION 2: Get Live Metrics for Selected Router
    $active_users = 0; $cpu = 0; $memory = 0; $uptime = 'Offline'; $metrics_list = [];

    // Query specific router or all assigned to tenant
    $query = "SELECT id, router_name, wireguard_ip FROM routers WHERE tenant_id = ? AND status = 'online'";
    if ($router_id > 0) $query .= " AND id = $router_id";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute([$tenant_id]);
    $routers = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($routers as $i => $r) {
        // Correcting the parameter to 'wireguard_ip' to match your working VPS link
        $url = "https://api.onyxhotspot.com/automation/listen.php?action=get_router_metrics&wireguard_ip={$r['wireguard_ip']}&key=Onyx_Automate_2026";
        $res = @file_get_contents($url, false, stream_context_create(['http'=>['timeout'=>5],'ssl'=>['verify_peer'=>false]]));
        $data = $res ? json_decode($res, true) : null;

        if ($data && $data['success']) {
            $metrics_list[] = [
                'router_name' => $r['router_name'],
                'cpu' => $data['cpu'],
                'memory' => $data['memory'],
                'active_users' => $data['active_users'],
                'uptime' => $data['uptime']
            ];
            $active_users += $data['active_users'];
            if ($i === 0) { $cpu = $data['cpu']; $memory = $data['memory']; $uptime = $data['uptime']; }
        }
    }

    echo json_encode([
        'success' => true,
        'active_users' => $active_users,
        'cpu' => $cpu,
        'memory' => $memory,
        'uptime' => $uptime,
        'router_metrics' => $metrics_list
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
