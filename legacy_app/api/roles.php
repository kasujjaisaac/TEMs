<?php
ini_set('display_errors', 1); ini_set('display_startup_errors', 1); error_reporting(E_ALL);
if (session_status() === PHP_SESSION_NONE) session_start();
require_once($_SERVER['DOCUMENT_ROOT'] . '/config.php');

if (!isset($_SESSION['user_id'], $_SESSION['tenant_id']) || $_SESSION['role'] !== 'Admin') {
    http_response_code(403); die("Access denied");
}
$user_id   = $_SESSION['user_id'];
$tenant_id = $_SESSION['tenant_id'];

$target_id = intval($_POST['user_id'] ?? 0);
$new_role  = $_POST['role'] ?? '';
$valid_roles = ['Admin', 'Staff', 'Viewer'];
if (!$target_id || !in_array($new_role, $valid_roles)) {
    http_response_code(400); die("Bad request");
}

// Prevent downgrading own role
if ($target_id == $user_id) {
    http_response_code(400); die("You can't change your own role.");
}

$stmt = $pdo->prepare("UPDATE users SET role = ? WHERE id = ? AND tenant_id = ?");
$stmt->execute([$new_role, $target_id, $tenant_id]);

// Audit log
$stmt = $pdo->prepare("INSERT INTO audit_logs (tenant_id, user_id, action, details) VALUES (?, ?, 'change_role', ?)");
$stmt->execute([$tenant_id, $user_id, json_encode(['target_id'=>$target_id,'new_role'=>$new_role])]);

echo "ok";
?>