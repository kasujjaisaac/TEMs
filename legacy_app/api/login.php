<?php
header('Content-Type: application/json');
require_once($_SERVER['DOCUMENT_ROOT'] . '/config.php');

// 1. Get Inputs
$email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
$password = $_POST['password'] ?? '';
$org_input = trim($_POST['organization'] ?? ''); // User typed "FAMILY BUSINESS"

if (!$email || !$password || !$org_input) {
    echo json_encode(["status" => "error", "message" => "All fields are required."]);
    exit;
}

// 2. Set the session name BEFORE starting
// This matches the session_handler: "Onyx_FAMILYBUSINESS"
$safe_org = preg_replace("/[^a-zA-Z0-9]/", "", $org_input);
session_name("Onyx_" . $safe_org);

session_set_cookie_params([
    'lifetime' => 86400,
    'path' => '/',
    'domain' => '',
    'secure' => isset($_SERVER['HTTPS']),
    'httponly' => true,
    'samesite' => 'Lax'
]);

session_start();

try {
    $pdo = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME.";charset=utf8mb4", DB_USER, DB_PASS, [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);

    // 3. Fetch user and tenant numeric ID
    $stmt = $pdo->prepare("
        SELECT u.id, u.tenant_id, u.password, u.email_verified, u.status, t.name AS organization
        FROM users u
        JOIN tenants t ON u.tenant_id = t.id
        WHERE u.email = ? AND t.name = ? LIMIT 1
    ");
    $stmt->execute([$email, $org_input]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password'])) {
        
        if ((int)$user['email_verified'] !== 1) {
            echo json_encode(["status" => "error", "message" => "Email not verified."]);
            exit;
        }

        // 4. Setup Session
        session_regenerate_id(true);
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['tenant_id'] = $user['tenant_id']; // Numeric (79)
        $_SESSION['organization'] = $user['organization']; // String (FAMILY BUSINESS)

        // 5. Redirect using the STRING NAME so session_handler finds the right session name
        echo json_encode([
            "status" => "success",
            "message" => "Login successful",
            "redirect" => "/dashboard.php?tenant_id=" . urlencode($org_input)
        ]);
    } else {
        echo json_encode(["status" => "error", "message" => "Invalid credentials."]);
    }
} catch(PDOException $e) {
    echo json_encode(["status" => "error", "message" => "Database error."]);
}