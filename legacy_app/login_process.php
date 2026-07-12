<?php
header('Content-Type: application/json');
require_once($_SERVER['DOCUMENT_ROOT'] . '/config.php');

$tenant_input = trim($_POST['tenant_id'] ?? ''); 
$email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
$password = $_POST['password'] ?? '';

if (!$tenant_input || !$email || !$password) {
    echo json_encode(["status" => "error", "message" => "Please fill in all fields."]);
    exit;
}

try {
    // 1. Fetch user FIRST to get the numeric ID
    $stmt = $pdo->prepare("
        SELECT u.id, u.tenant_id, u.password, u.status, t.name AS organization
        FROM users u
        JOIN tenants t ON u.tenant_id = t.id
        WHERE u.email = ? AND t.name = ? LIMIT 1
    ");
    $stmt->execute([$email, $tenant_input]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password'])) {
        
        if (strtolower($user['status'] ?? '') !== 'active') {
            echo json_encode(["status" => "error", "message" => "Account is not active."]);
            exit;
        }

        // 2. Set session name based on the NUMERIC ID (e.g., Onyx_79)
        $numeric_id = $user['tenant_id'];
        session_name("Onyx_" . $numeric_id);

        session_set_cookie_params([
            'lifetime' => 86400,
            'path' => '/',
            'domain' => '',
            'secure' => isset($_SERVER['HTTPS']),
            'httponly' => true,
            'samesite' => 'Lax'
        ]);

        session_start();
        session_regenerate_id(true);

        $_SESSION['user_id'] = $user['id'];
        $_SESSION['tenant_id'] = (int)$user['tenant_id']; 
        $_SESSION['organization'] = $user['organization'];
        
        // 3. REDIRECT using the Numeric ID
        echo json_encode([
            "status" => "success",
            "message" => "Login successful!",
            "redirect" => "dashboard.php?tenant_id=" . $numeric_id
        ]);
    } else {
        echo json_encode(["status" => "error", "message" => "Invalid credentials or organization."]);
    }
} catch(Exception $e) {
    echo json_encode(["status" => "error", "message" => "Login failed."]);
}