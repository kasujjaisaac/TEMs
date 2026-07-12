<?php
header('Content-Type: application/json');

$root = $_SERVER['DOCUMENT_ROOT'];
$tenant_id = $_GET['tenant_id'] ?? '82';

try {
    require_once($root . '/config.php');

    // Check routers for this tenant
    $stmt = $pdo->prepare("
        SELECT id, router_name, status, wireguard_ip, last_seen
        FROM routers 
        WHERE tenant_id = ?
        ORDER BY id DESC
    ");
    $stmt->execute([$tenant_id]);
    $routers = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'tenant_id' => $tenant_id,
        'router_count' => count($routers),
        'routers' => $routers,
        'online_count' => count(array_filter($routers, fn($r) => $r['status'] === 'online'))
    ], JSON_PRETTY_PRINT);

} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>