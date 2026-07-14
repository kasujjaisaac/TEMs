<?php

namespace App\Support;

class PermissionCatalog
{
    public static function groups(): array
    {
        return [
            'Dashboard' => [
                'dashboard.view' => 'View dashboard',
                'dashboard.sensitive' => 'View sensitive figures',
            ],
            'Sales & POS' => [
                'pos.access' => 'Access POS',
                'sales.view' => 'View sales',
                'sales.create' => 'Create sales',
                'sales.edit' => 'Edit sales',
                'sales.delete' => 'Delete sales',
                'sales.refund' => 'Process refunds',
            ],
            'Inventory' => [
                'products.view' => 'View products',
                'products.manage' => 'Manage products',
                'inventory.view' => 'View stock',
                'inventory.adjust' => 'Adjust stock',
                'purchases.manage' => 'Manage purchases',
                'suppliers.manage' => 'Manage suppliers',
            ],
            'Customers & CRM' => [
                'customers.view' => 'View customers',
                'customers.manage' => 'Manage customers',
                'crm.manage' => 'Manage CRM',
            ],
            'Finance' => [
                'accounting.view' => 'View accounting',
                'accounting.manage' => 'Manage accounting',
                'banking.manage' => 'Manage banking',
                'budgets.manage' => 'Manage budgets',
                'reports.view' => 'View reports',
                'reports.export' => 'Export reports',
            ],
            'Human Resource' => [
                'hr.view' => 'View HR',
                'hr.manage' => 'Manage HR',
                'payroll.manage' => 'Manage payroll',
            ],
            'Administration' => [
                'settings.view' => 'View settings',
                'settings.manage' => 'Manage company settings',
                'users.manage' => 'Manage users',
                'roles.manage' => 'Manage roles and permissions',
                'security.manage' => 'Manage security settings',
                'audit.view' => 'View audit logs',
            ],
        ];
    }

    public static function allKeys(): array
    {
        return array_keys(array_merge(...array_values(self::groups())));
    }

    public static function defaultRolePermissions(string $slug): array
    {
        return match ($slug) {
            'super_admin', 'admin' => self::allKeys(),
            'manager' => [
                'dashboard.view',
                'sales.view',
                'sales.create',
                'products.view',
                'inventory.view',
                'customers.view',
                'customers.manage',
                'reports.view',
            ],
            'cashier' => [
                'dashboard.view',
                'pos.access',
                'sales.view',
                'sales.create',
                'customers.view',
            ],
            'accountant' => [
                'dashboard.view',
                'sales.view',
                'accounting.view',
                'accounting.manage',
                'banking.manage',
                'budgets.manage',
                'reports.view',
                'reports.export',
            ],
            'inventory_officer' => [
                'dashboard.view',
                'products.view',
                'products.manage',
                'inventory.view',
                'inventory.adjust',
                'purchases.manage',
                'suppliers.manage',
            ],
            default => ['dashboard.view'],
        };
    }

    public static function defaultRoles(): array
    {
        return [
            ['name' => 'Super Admin', 'slug' => 'super_admin', 'description' => 'Full workspace control including users, roles, security, and audit logs.'],
            ['name' => 'Admin', 'slug' => 'admin', 'description' => 'Administrative access for daily workspace configuration.'],
            ['name' => 'Manager', 'slug' => 'manager', 'description' => 'Operational oversight across sales, inventory, customers, and reports.'],
            ['name' => 'Cashier', 'slug' => 'cashier', 'description' => 'POS and sales entry access.'],
            ['name' => 'Accountant', 'slug' => 'accountant', 'description' => 'Accounting, banking, budgets, and financial reporting access.'],
            ['name' => 'Inventory Officer', 'slug' => 'inventory_officer', 'description' => 'Product, stock, purchases, and supplier access.'],
            ['name' => 'Viewer', 'slug' => 'viewer', 'description' => 'Read-only dashboard access.'],
        ];
    }
}
