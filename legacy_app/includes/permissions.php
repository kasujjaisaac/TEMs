<?php
/**
 * Simple permissions helper.
 * This provides a minimal role/permission check backed by session data.
 * It does not replace a full RBAC system but gives pages a way to gate functionality.
 */

function has_permission(string $permission): bool
{
    // Super admin shortcut
    if (!empty($_SESSION['role']) && $_SESSION['role'] === 'super_admin') {
        return true;
    }

    // Permissions can be stored directly (array) or derived from role
    if (!empty($_SESSION['permissions']) && is_array($_SESSION['permissions'])) {
        return in_array($permission, $_SESSION['permissions'], true);
    }

    // Simple role-based defaults
    $role = $_SESSION['role'] ?? 'user';
    $role_map = [
        'super_admin' => ['manage_settings', 'view_reports', 'manage_purchases', 'manage_products'],
        'accountant' => ['view_reports', 'manage_purchases', 'manage_products'],
        'sales' => ['manage_purchases', 'manage_products'],
        'user' => [],
    ];

    $perms = $role_map[$role] ?? [];
    return in_array($permission, $perms, true);
}

function require_permission(string $permission): void
{
    if (!has_permission($permission)) {
        header('HTTP/1.1 403 Forbidden');
        echo '<h2 style="color:#fff">403 Forbidden</h2><p style="color:#ccc">You do not have permission to access this page.</p>';
        exit();
    }
}

?>
