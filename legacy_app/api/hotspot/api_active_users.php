<?php
header('Content-Type: application/json');

require_once($_SERVER['DOCUMENT_ROOT'] . '/config.php');

$tenant_id = (int)($_GET['tenant_id'] ?? 80);
$router_id = (int)($_GET['router_id'] ?? 0);
$action    = $_GET['action'] ?? 'list';

try {

    /*
    |--------------------------------------------------------------------------
    | USER MANAGEMENT ACTIONS
    |--------------------------------------------------------------------------
    */
    if ($action === 'user_action') {

        $router_id = (int)($_POST['router_id'] ?? 0);
        $username  = trim($_POST['username'] ?? '');
        $command   = trim($_POST['command'] ?? '');

        if (!$router_id || !$username || !$command) {
            throw new Exception("Missing required fields");
        }

        // Get router IP
        $stmt = $pdo->prepare("
            SELECT wireguard_ip 
            FROM routers 
            WHERE id = ? 
            LIMIT 1
        ");

        $stmt->execute([$router_id]);

        $wireguard_ip = $stmt->fetchColumn();

        if (!$wireguard_ip) {
            throw new Exception("Router not found");
        }

        // Send request to automation bridge
        $url = "https://api.onyxhotspot.com/automation/listen.php?"
             . "action=manage_hotspot_user"
             . "&command=" . urlencode($command)
             . "&username=" . urlencode($username)
             . "&wireguard_ip=" . urlencode($wireguard_ip)
             . "&key=Onyx_Automate_2026";

        $context = stream_context_create([
            'http' => [
                'timeout' => 10
            ],
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false
            ]
        ]);

        $response = @file_get_contents($url, false, $context);

        if (!$response) {
            throw new Exception("Automation server unreachable");
        }

        echo $response;
        exit;
    }

    /*
    |--------------------------------------------------------------------------
    | FETCH ACTIVE USERS
    |--------------------------------------------------------------------------
    */

    // Base router query
    $query = "
        SELECT 
            id,
            router_name,
            wireguard_ip
        FROM routers
        WHERE tenant_id = ?
        AND status = 'online'
    ";

    // Filter specific router
    if ($router_id > 0) {
        $query .= " AND id = " . (int)$router_id;
    }

    $stmt = $pdo->prepare($query);
    $stmt->execute([$tenant_id]);

    $routers = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $all_users = [];

    foreach ($routers as $router) {

        /*
        |--------------------------------------------------------------------------
        | CALL ROUTER AUTOMATION API
        |--------------------------------------------------------------------------
        */

        $url = "https://api.onyxhotspot.com/automation/listen.php?"
             . "action=get_active_sessions"
             . "&wireguard_ip=" . urlencode($router['wireguard_ip'])
             . "&key=Onyx_Automate_2026";

        $context = stream_context_create([
            'http' => [
                'timeout' => 8
            ],
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false
            ]
        ]);

        $response = @file_get_contents($url, false, $context);

        if (!$response) {
            continue;
        }

        $data = json_decode($response, true);

        if (
            !$data ||
            !isset($data['success']) ||
            !$data['success']
        ) {
            continue;
        }

        /*
        |--------------------------------------------------------------------------
        | PROCESS USERS
        |--------------------------------------------------------------------------
        */

        foreach ($data['users'] as $user) {

            $mac = $user['mac-address'] ?? '';
            $host = $user['host-name'] ?? '';

            // Detect device type
            $device = detectDevice($host, $mac);

            $all_users[] = [
                'username'      => $user['user'] ?? 'Unknown',
                'ip_address'    => $user['address'] ?? '-',
                'mac_address'   => $mac,
                'uptime'        => $user['uptime'] ?? '-',
                'bytes_in'      => $user['bytes-in'] ?? 0,
                'bytes_out'     => $user['bytes-out'] ?? 0,
                'device'        => $device,
                'router_name'   => $router['router_name'],
                'router_id'     => $router['id'],
                'status'        => 'online'
            ];
        }
    }

    /*
    |--------------------------------------------------------------------------
    | FINAL RESPONSE
    |--------------------------------------------------------------------------
    */

    echo json_encode([
        'success' => true,
        'count'   => count($all_users),
        'users'   => $all_users
    ]);

} catch (Exception $e) {

    echo json_encode([
        'success' => false,
        'error'   => $e->getMessage()
    ]);
}


/*
|--------------------------------------------------------------------------
| DEVICE DETECTION
|--------------------------------------------------------------------------
*/

function detectDevice($hostname = '', $mac = '')
{
    $text = strtolower($hostname . ' ' . $mac);

    // Phones
    if (
        strpos($text, 'tecno') !== false ||
        strpos($text, 'camon') !== false
    ) {
        return 'Tecno Phone';
    }

    if (strpos($text, 'samsung') !== false) {
        return 'Samsung Phone';
    }

    if (strpos($text, 'iphone') !== false ||
        strpos($text, 'ios') !== false) {
        return 'iPhone';
    }

    if (strpos($text, 'redmi') !== false ||
        strpos($text, 'xiaomi') !== false) {
        return 'Xiaomi Phone';
    }

    if (strpos($text, 'huawei') !== false) {
        return 'Huawei Phone';
    }

    // PCs
    if (strpos($text, 'windows') !== false) {
        return 'Windows PC';
    }

    if (strpos($text, 'macbook') !== false ||
        strpos($text, 'imac') !== false) {
        return 'Apple Mac';
    }

    if (strpos($text, 'linux') !== false) {
        return 'Linux PC';
    }

    return 'Unknown Device';
}