<?php
/**
 * ONYX Accounting System - Production Login Processor
 * Location: public/business/process_login.php
 */

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: login.php');
    exit();
}

define('ONYX_SKIP_AUTO_CONNECT', true);
require_once __DIR__ . '/config.php';

$tenant_id_input = trim($_POST['tenant_id'] ?? '');
$tenant_slug = trim($_POST['tenant_slug'] ?? '');
$email = strtolower(trim($_POST['email'] ?? ''));
$password = $_POST['password'] ?? '';

if (empty($email) || empty($password) || (empty($tenant_id_input) && empty($tenant_slug))) {
    header('Location: login.php?error=' . urlencode('All fields are strictly required.'));
    exit();
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header('Location: login.php?error=' . urlencode('Enter a valid email address.'));
    exit();
}

try {
    $pdo = onyx_create_pdo();

    $query = "
        SELECT u.id AS user_id,
               u.tenant_id,
               u.password,
               u.role,
               u.name AS user_name,
               t.company_name AS company_name,
               t.currency,
               u.is_active
        FROM users u
        JOIN tenants t ON u.tenant_id = t.id
        WHERE u.email = :email
    ";

    $params = ['email' => $email];

    if (ctype_digit($tenant_id_input)) {
        $query .= " AND t.id = :tenant_id";
        $params['tenant_id'] = (int) $tenant_id_input;
    } else {
        $query .= " AND (t.slug = :tenant_slug OR LOWER(t.company_name) = LOWER(:tenant_company_name))";
        $params['tenant_slug'] = $tenant_slug;
        $params['tenant_company_name'] = $tenant_slug;
    }

    $query .= " LIMIT 1";
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user || !password_verify($password, $user['password'])) {
        header('Location: login.php?error=' . urlencode('Invalid email, password, or workspace handle.'));
        exit();
    }

    $is_active = true;
    if (isset($user['is_active'])) {
        $is_active = (int) $user['is_active'] === 1;
    } elseif (isset($user['status'])) {
        $is_active = in_array(strtolower($user['status']), ['active', 'trial'], true);
    }

    if (!$is_active) {
        header('Location: login.php?error=' . urlencode('This user account is not active.'));
        exit();
    }

    $tenant_id = (int) $user['tenant_id'];

    session_name('Onyx_' . $tenant_id);
    session_set_cookie_params([
        'lifetime' => 86400,
        'path' => '/',
        'domain' => '',
        'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    session_start();
    session_regenerate_id(true);

    $_SESSION['user_id'] = $user['user_id'];
    $_SESSION['tenant_id'] = $tenant_id;
    $_SESSION['company_name'] = $user['company_name'] ?? 'Onyx Workspace';
    $_SESSION['user_name'] = $user['user_name'] ?: $email;
    $_SESSION['user_role'] = $user['role'] ?? 'company_admin';
    $_SESSION['currency'] = $user['currency'] ?? 'UGX';

    header('Location: dashboard.php?tenant_id=' . $tenant_id);
    exit();

} catch (PDOException $e) {
    error_log('Login processing failed: ' . $e->getMessage());
    header('Location: login.php?error=' . urlencode('Login failed due to a server error.'));
    exit();
}
