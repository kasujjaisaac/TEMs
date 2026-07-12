<?php
header('Content-Type: application/json');
require_once('../../includes/db_connect.php'); // Adjust path to your actual DB config file

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Method not allowed.']);
    exit;
}

// 1. Get and Sanitize inputs
$username  = filter_input(INPUT_POST, 'username', FILTER_DEFAULT);
$password  = filter_input(INPUT_POST, 'password', FILTER_DEFAULT);
$profile   = filter_input(INPUT_POST, 'profile', FILTER_DEFAULT);
$router_id = filter_input(INPUT_POST, 'router_id', FILTER_DEFAULT);

if (!$username || !$password || !$router_id) {
    echo json_encode(['success' => false, 'error' => 'Missing required fields.']);
    exit;
}

try {
    // 2. Insert into Web App Database
    // Adjust column names and table name depending on your actual layout
    $stmt = $pdo->prepare("INSERT INTO hotspot_users (username, password, profile, router_id, created_at) VALUES (?, ?, ?, ?, NOW())");
    $stmt->execute([$username, $password, $profile, $router_id]);
    
    // 3. Network Provisioning (Crucial Step!)
    // Your web app database is separate from what your router actually scans when a phone authenticates.
    // Here we must make a programmatic call to pass this credential directly onto your hardware node.
    
    $syncSuccess = syncToRouterNode($username, $password, $profile, $router_id);
    
    if ($syncSuccess) {
        echo json_encode(['success' => true, 'message' => 'User saved and synced.']);
    } else {
        // Soft fail: Saved locally but provisioning failed
        echo json_encode(['success' => true, 'error' => 'Saved locally but failed to sync to live router.']);
    }

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}

/**
 * Mock function for provisioning your router via RouterOS API or RADIUS database.
 */
function syncToRouterNode($user, $pass, $profile, $routerId) {
    // Example logic using a MikroTik API library or internal Router Client wrapper
    /*
    $router = getRouterConnectionDetails($routerId);
    $client = new RouterosAPI();
    if ($client->connect($router['ip'], $router['user'], $router['password'])) {
        $client->comm("/ip/hotspot/user/add", [
            "name"     => $user,
            "password" => $pass,
            "profile"  => $profile
        ]);
        $client->disconnect();
        return true;
    }
    */
    return true; // Return true once you integrate your specific router API client architecture
}