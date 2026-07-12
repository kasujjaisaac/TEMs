<?php
ini_set('display_errors', 1); ini_set('display_startup_errors', 1); error_reporting(E_ALL);
require_once($_SERVER['DOCUMENT_ROOT'] . '/config.php');

$token = trim($_GET['token'] ?? '');

if (!$token) {
    die("Invalid invite link.");
}
$stmt = $pdo->prepare("SELECT id, email, name, tenant_id, role FROM users WHERE invite_token = ? AND invite_expires_at > NOW() AND status = 'invited'");
$stmt->execute([$token]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) die("Invite expired or invalid.");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';
    if (!$password || strlen($password) < 8) {
        $error = "Password must be at least 8 characters.";
    } else {
        $stmt = $pdo->prepare("UPDATE users SET password = ?, status = 'active', invite_token = NULL, invite_expires_at = NULL WHERE id = ?");
        $stmt->execute([password_hash($password, PASSWORD_DEFAULT), $user['id']]);
        // (Optional) Auto-login
        session_set_cookie_params([ /* ...same as above... */ ]); session_start();
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['tenant_id'] = $user['tenant_id'];
        $_SESSION['role'] = $user['role'];
        header("Location: dashboard.php");
        exit;
    }
}
?>
<!DOCTYPE html>
<html><body>
<h2>Accept Your Invitation</h2>
<form method="post">
    <?php if (!empty($error)) echo "<div style='color:red'>$error</div>"; ?>
    <label>Set Password<br><input type="password" name="password" required></label><br>
    <button type="submit">Activate Account</button>
</form>
</body></html>