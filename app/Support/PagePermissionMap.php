<?php

namespace App\Support;

class PagePermissionMap
{
    public static function forPage(string $page): ?string
    {
        return [
            'dashboard' => 'dashboard.view',
            'pos' => 'pos.access',
            'sales' => 'sales.view',
            'sales_action' => 'sales.create',
            'customers' => 'customers.view',
            'customers_action' => 'customers.manage',
            'crm' => 'crm.manage',
            'products' => 'products.view',
            'products_action' => 'products.manage',
            'inventory' => 'inventory.view',
            'purchases' => 'purchases.manage',
            'suppliers' => 'suppliers.manage',
            'suppliers_action' => 'suppliers.manage',
            'accounting' => 'accounting.view',
            'banking' => 'banking.manage',
            'budgets' => 'budgets.manage',
            'assets' => 'accounting.view',
            'reports' => 'reports.view',
            'human_resources' => 'hr.view',
            'hr_profiles' => 'hr.view',
            'hr_employee' => 'hr.view',
            'hr_contracts' => 'hr.manage',
            'hr_attendance' => 'hr.manage',
            'hr_leave' => 'hr.manage',
            'hr_advances' => 'hr.manage',
            'hr_documents' => 'hr.manage',
            'hr_performance' => 'hr.manage',
            'hr_payroll_readiness' => 'payroll.manage',
            'payroll' => 'payroll.manage',
            'mobile_app' => 'settings.view',
            'notifications' => 'settings.view',
            'settings' => 'settings.view',
            'document_templates' => 'settings.view',
            'users' => 'users.manage',
            'roles' => 'roles.manage',
            'security' => 'security.manage',
            'audit_logs' => 'audit.view',
        ][$page] ?? null;
    }
}
