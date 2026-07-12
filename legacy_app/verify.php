<?php
// Enable error reporting for debugging (remove in production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Always use an absolute path for config
require_once($_SERVER['DOCUMENT_ROOT'] . '/config.php');
session_start();

// Validate and get token
if (!isset($_GET['token']) || strlen($_GET['token']) < 32) {
    die("Invalid verification link.");
}
$token = $_GET['token'];

// Debug: show token being checked
// echo "<pre>Token from URL: " . htmlspecialchars($token) . "</pre>";

// 1. Look for user with matching token, inactive status, and not yet verified
$stmt = $pdo->prepare("SELECT id, tenant_id, name FROM users WHERE token = ? AND email_verified = 0 AND status = 'inactive'");
$stmt->execute([$token]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($user) {
    // 2. Mark user as verified and activate
    $stmt = $pdo->prepare("UPDATE users SET email_verified = 1, status = 'active', token = NULL WHERE id = ?");
    $stmt->execute([$user['id']]);
    // 3. Optionally activate tenant
    $stmt = $pdo->prepare("UPDATE tenants SET status = 'active' WHERE id = ?");
    $stmt->execute([$user['tenant_id']]);
    // 4. Log user in and redirect to profile completion
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['tenant_id'] = $user['tenant_id'];
    header("Location: /complete-profile.php");
    exit;
} else {
    // Debug: Show what's in the database for troubleshooting
    $debug = '';
    $check = $pdo->prepare("SELECT id, email, token, email_verified, status FROM users WHERE token = ?");
    $check->execute([$token]);
    $row = $check->fetch(PDO::FETCH_ASSOC);

    if ($row) {
        $debug .= "Token found in DB, but email_verified/status don't match.<br>";
        $debug .= "email_verified: {$row['email_verified']}, status: {$row['status']}<br>";
    } else {
        $debug .= "No user found with this token.<br>";
        // Optional: dump all tokens for you
        $all = $pdo->query("SELECT id, email, token, email_verified, status FROM users")->fetchAll(PDO::FETCH_ASSOC);
        $debug .= "Current tokens/statuses in DB:<pre>" . print_r($all, true) . "</pre>";
    }
    $msg = "Invalid or expired verification link.<br><small>{$debug}</small>";
}
?>
<!DOCTYPE html>
<html>
<head><title>Email Verification</title></head>
<body>
    <h2>Email Verification</h2>
    <p><?php echo isset($msg) ? $msg : "Redirecting..."; ?></p>
    <a href="/login.html">Go to Login</a>
</body>
</html>