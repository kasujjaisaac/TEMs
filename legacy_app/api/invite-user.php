<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Secure session start
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => $_SERVER['HTTP_HOST'],
        'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    session_start();
}

require_once($_SERVER['DOCUMENT_ROOT'] . '/config.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/utils/email.php');

var_dump($_SESSION); exit;

// RBAC: Only allow org admins
if (!isset($_SESSION['user_id'], $_SESSION['tenant_id'], $_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    http_response_code(403);
    exit("Access denied");
}

$tenant_id = $_SESSION['tenant_id'];
$admin_id  = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name  = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $role  = trim($_POST['role'] ?? 'Viewer');

    $valid_roles = ['Admin', 'Staff', 'Viewer'];
    if (!$name || !$email || !in_array($role, $valid_roles)) {
        http_response_code(400);
        exit("Invalid data");
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        exit("Invalid email address");
    }

    // Check if user already exists in this tenant
    $stmt = $pdo->prepare("SELECT id FROM users WHERE tenant_id = ? AND email = ?");
    $stmt->execute([$tenant_id, $email]);
    if ($stmt->fetch()) {
        http_response_code(409);
        exit("User already exists");
    }

    // Generate invite token (valid for 48h, single-use)
    $invite_token = bin2hex(random_bytes(32));
    $expires_at = date('Y-m-d H:i:s', strtotime('+2 days'));

    // Insert invited user (status: invited, no password yet)
    $stmt = $pdo->prepare("INSERT INTO users (tenant_id, name, email, role, status, invite_token, invite_expires_at, created_by) VALUES (?, ?, ?, ?, 'invited', ?, ?, ?)");
    $stmt->execute([$tenant_id, $name, $email, $role, $invite_token, $expires_at, $admin_id]);

    // Send invitation email
    $invite_url = "https://onyxhotspot.com/accept-invite.php?token=$invite_token";
    $subject = "Your Onyx Hotspot Invitation";
    $body = "<p>Hello " . htmlspecialchars($name) . ",</p>
        <p>You have been invited to join Onyx Hotspot as a <strong>" . htmlspecialchars($role) . "</strong>.</p>
        <p>Click the link below to set your password and activate your account:</p>
        <p><a href='" . htmlspecialchars($invite_url) . "'>$invite_url</a></p>
        <p>This link expires in 48 hours.</p>
        <br><p>If you did not expect this invitation, you can ignore this email.</p>";

    $sent = send_email($email, $subject, $body, strip_tags($body));

    // Optionally, handle/send failure
    if (!$sent) {
        http_response_code(500);
        exit("Failed to send invitation email. Please contact support.");
    }

    // Log admin action (audit log)
    $stmt = $pdo->prepare("INSERT INTO audit_logs (tenant_id, user_id, actor_name, action, details) VALUES (?, ?, ?, 'invite_user', ?)");
    $actor_name = $_SESSION['name'] ?? 'Admin';
    $stmt->execute([$tenant_id, $admin_id, $actor_name, json_encode(['invited_email'=>$email, 'role'=>$role])]);

    // Optionally, add activity notification here

    // If AJAX, respond as JSON
    if (
        !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
        strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest'
    ) {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'success', 'message' => 'Invitation sent']);
        exit;
    }

    // If traditional POST, redirect
    header("Location: /settings.php?invited=1");
    exit;
}
?>