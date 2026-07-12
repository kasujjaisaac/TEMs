<?php
/**
 * ONYX Accounting System - Database Safe Registration Processor
 * Location: public/business/process_register.php
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: register.php');
    exit();
}

define('ONYX_SKIP_AUTO_CONNECT', true);
require_once __DIR__ . '/config.php';

$company_name = trim($_POST['company_name'] ?? '');
$currency = trim($_POST['currency'] ?? 'UGX');
$admin_name = trim($_POST['admin_name'] ?? '');
$email = strtolower(trim($_POST['email'] ?? ''));
$password = $_POST['password'] ?? '';
$password_confirmation = $_POST['password_confirmation'] ?? '';

// Basic entry audit verification validation checks
if (empty($company_name) || empty($admin_name) || empty($email) || empty($password)) {
    header('Location: register.php?error=' . urlencode('All registry form parameters are strictly required.'));
    exit();
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header('Location: register.php?error=' . urlencode('Enter a valid administrator email address.'));
    exit();
}

if ($password !== $password_confirmation) {
    header('Location: register.php?error=' . urlencode('Account secure passphrases do not match.'));
    exit();
}

// Generate a clean slug prefix for the multi-tenant partition lookups
$tenant_slug = strtolower(preg_replace('/[^a-z0-9]+/', '-', $company_name));
$tenant_slug = trim($tenant_slug, '-');
if ($tenant_slug === '') {
    $tenant_slug = 'workspace-' . bin2hex(random_bytes(4));
}

function onyx_table_columns(PDO $pdo, string $table): array
{
    $allowed_tables = ['tenants', 'users'];
    if (!in_array($table, $allowed_tables, true)) {
        return [];
    }

    $columns = [];
    $stmt = $pdo->query("SHOW COLUMNS FROM `$table`");
    foreach ($stmt->fetchAll() as $column) {
        $columns[] = $column['Field'];
    }

    return $columns;
}

try {
    $pdo = onyx_create_pdo();

    $tenant_columns_available = onyx_table_columns($pdo, 'tenants');
    $user_columns_available = onyx_table_columns($pdo, 'users');

    if (!$tenant_columns_available || !$user_columns_available) {
        header('Location: register.php?error=' . urlencode('Required registration tables were not found in the selected database.'));
        exit();
    }

    $tenant_name_column = in_array('company_name', $tenant_columns_available, true) ? 'company_name' : 'name';
    if (!in_array($tenant_name_column, $tenant_columns_available, true)) {
        header('Location: register.php?error=' . urlencode('The tenants table needs a name or company_name column.'));
        exit();
    }

    $tenant_conditions = ["LOWER(TRIM(`$tenant_name_column`)) = LOWER(TRIM(:company_name))"];
    $tenant_params = ['company_name' => $company_name];

    $has_tenant_slug = in_array('slug', $tenant_columns_available, true);

    if ($has_tenant_slug) {
        $tenant_conditions[] = '`slug` = :slug';
        $tenant_params['slug'] = $tenant_slug;
    }

    // Ensure the company handle variation isn't already active.
    $stmt = $pdo->prepare("
        SELECT id
        FROM tenants
        WHERE " . implode(' OR ', $tenant_conditions) . "
        LIMIT 1
    ");
    $stmt->execute($tenant_params);
    if ($stmt->fetch()) {
        header('Location: register.php?error=' . urlencode('A company matching this corporate naming handle variation is already active.'));
        exit();
    }

    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = :email LIMIT 1");
    $stmt->execute(['email' => $email]);
    if ($stmt->fetch()) {
        header('Location: register.php?error=' . urlencode('That administrator email is already registered. Use login instead.'));
        exit();
    }

    // Execute safe operations using explicit isolation rollback boundaries
    $pdo->beginTransaction();

    /**
     * 2. Provision Tenant Partition Account
     * Uses explicit date formatting constraints to support strict schema structures smoothly
     */
    $tenant_columns = [$tenant_name_column];
    $tenant_placeholders = [':tenant_name'];
    $tenant_values = ['tenant_name' => $company_name];

    if ($has_tenant_slug) {
        $tenant_columns[] = 'slug';
        $tenant_placeholders[] = ':slug';
        $tenant_values['slug'] = $tenant_slug;
    }

    if (in_array('currency', $tenant_columns_available, true)) {
        $tenant_columns[] = 'currency';
        $tenant_placeholders[] = ':currency';
        $tenant_values['currency'] = $currency;
    }

    if (in_array('email', $tenant_columns_available, true)) {
        $tenant_columns[] = 'email';
        $tenant_placeholders[] = ':tenant_email';
        $tenant_values['tenant_email'] = $email;
    }

    if (in_array('fiscal_year_start', $tenant_columns_available, true)) {
        $tenant_columns[] = 'fiscal_year_start';
        $tenant_placeholders[] = ':fiscal';
        $tenant_values['fiscal'] = date('Y-01-01');
    }

    if (in_array('status', $tenant_columns_available, true)) {
        $tenant_columns[] = 'status';
        $tenant_placeholders[] = ':tenant_status';
        $tenant_values['tenant_status'] = 'trial';
    }

    $stmtTenant = $pdo->prepare("
        INSERT INTO tenants (" . implode(', ', $tenant_columns) . ")
        VALUES (" . implode(', ', $tenant_placeholders) . ")
    ");
    $stmtTenant->execute($tenant_values);
    $tenant_id = (int)$pdo->lastInsertId();

    /**
     * 3. Provision Master Administrator User Account Properties
     */
    $user_columns = ['tenant_id', 'name', 'email', 'password', 'role'];
    $user_placeholders = [':tenant_id', ':name', ':email', ':password', ':role'];
    $user_values = [
        'tenant_id' => $tenant_id,
        'name'      => $admin_name,
        'email'     => $email,
        'password'  => password_hash($password, PASSWORD_BCRYPT),
        'role'      => 'company_admin',
    ];

    if (in_array('organization', $user_columns_available, true)) {
        $user_columns[] = 'organization';
        $user_placeholders[] = ':organization';
        $user_values['organization'] = $company_name;
    }

    if (in_array('is_active', $user_columns_available, true)) {
        $user_columns[] = 'is_active';
        $user_placeholders[] = ':is_active';
        $user_values['is_active'] = 1;
    }

    if (in_array('status', $user_columns_available, true)) {
        $user_columns[] = 'status';
        $user_placeholders[] = ':status';
        $user_values['status'] = 'active';
    }

    if (in_array('email_verified', $user_columns_available, true)) {
        $user_columns[] = 'email_verified';
        $user_placeholders[] = ':email_verified';
        $user_values['email_verified'] = 1;
    }

    if (in_array('profile_complete', $user_columns_available, true)) {
        $user_columns[] = 'profile_complete';
        $user_placeholders[] = ':profile_complete';
        $user_values['profile_complete'] = 1;
    }

    $stmtUser = $pdo->prepare("
        INSERT INTO users (" . implode(', ', $user_columns) . ")
        VALUES (" . implode(', ', $user_placeholders) . ")
    ");
    $stmtUser->execute($user_values);

    $pdo->commit();

    // Verification completely processed. Forward client to login with the tenant configuration parameter active
    $login_workspace = $has_tenant_slug ? $tenant_slug : $company_name;
    header('Location: login.php?tenant_slug=' . urlencode($login_workspace) . '&success=' . urlencode('Workspace partition successfully initialized!'));
    exit();

} catch (\Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    // Record internal runtime engine exception context for verification debugging
    error_log("Tenant Allocation Flow Process Exception: " . $e->getMessage());
    $message = $e->getMessage();
    if (stripos($message, 'Access denied for user') !== false) {
        $message = 'Database login failed. Update DB_USER and DB_PASS in config.php with the correct MySQL database credentials.';
    } else {
        $message = 'Transactional execution fault: ' . $message;
    }
    header('Location: register.php?error=' . urlencode($message));
    exit();
}
