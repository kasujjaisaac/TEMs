<?php
// Include the validator from the parent directory
require_once '../session_validator.php';

$response = ['success' => true, 'total_active' => 0, 'results' => []];

try {
    // Fetch all routers for the logged-in tenant
    $stmt = $pdo->prepare("SELECT id, wireguard_ip, api_user, api_pass FROM routers WHERE tenant_id = ?");
    $stmt->execute([$tenant_id]);
    $routers = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($routers as $router) {
        $routerData = [
            'router_id' => $router['id'],
            'nas_ip' => $router['wireguard_ip'],
            'online' => false,
            'users' => []
        ];

        $API = new RouterosAPI();
        $API->timeout = 2; // Prevent hanging on offline nodes

        if (@$API->connect($router['wireguard_ip'], $router['api_user'], $router['api_pass'])) {
            // image_49cfb9.png shows we need 'user', 'address', and 'uptime'
            $active = $API->comm("/ip/hotspot/active/print");
            $API->disconnect();

            $routerData['online'] = true;
            $routerData['users'] = $active;
            $response['total_active'] += count($active);
        }
        $response['results'][] = $routerData;
    }

    echo json_encode($response);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}