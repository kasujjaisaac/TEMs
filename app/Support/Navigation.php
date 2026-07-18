<?php

namespace App\Support;

use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Request;

class Navigation
{
    public static function groups(): array
    {
        return [
            [
                'label' => 'Dashboard',
                'icon' => 'fa-gauge-high',
                'items' => [
                    ['file' => 'dashboard.php', 'label' => 'Dashboard', 'icon' => 'fa-chart-pie', 'url' => url('dashboard'), 'patterns' => ['dashboard*']],
                ],
            ],
            [
                'label' => 'Planning & Performance',
                'icon' => 'fa-bullseye',
                'items' => [
                    ['file' => 'planning.php', 'label' => 'Performance Dashboard', 'icon' => 'fa-chart-line', 'url' => route('planning.dashboard'), 'patterns' => ['planning'], 'permission' => 'planning.dashboard.view'],
                    ['file' => 'planning-objectives.php', 'label' => 'Strategic Objectives', 'icon' => 'fa-compass', 'url' => route('planning.objectives.index'), 'patterns' => ['planning/objectives*'], 'permission' => 'planning.strategy.view'],
                    ['file' => 'planning-workplans.php', 'label' => 'Corporate Workplans', 'icon' => 'fa-list-check', 'url' => route('planning.workplans.index'), 'patterns' => ['planning/workplans*'], 'permission' => 'planning.workplans.view'],
                    ['file' => 'planning-daily.php', 'label' => 'My Daily Workspace', 'icon' => 'fa-calendar-day', 'url' => route('planning.dashboard'), 'patterns' => ['planning/daily*'], 'permission' => 'planning.dashboard.view'],
                    ['file' => 'planning-evidence.php', 'label' => 'Evidence & Verification', 'icon' => 'fa-file-shield', 'url' => route('planning.dashboard'), 'patterns' => ['planning/evidence*'], 'permission' => 'planning.workplans.view'],
                    ['file' => 'planning-recovery.php', 'label' => 'Corrective Actions', 'icon' => 'fa-route', 'url' => route('planning.dashboard'), 'patterns' => ['planning/recovery*'], 'permission' => 'planning.workplans.view'],
                ],
            ],
            [
                'label' => 'Sales',
                'icon' => 'fa-cash-register',
                'items' => [
                    ['file' => 'pos.php', 'label' => 'POS', 'icon' => 'fa-store', 'url' => url('pos'), 'patterns' => ['pos*']],
                    ['file' => 'sales.php', 'label' => 'Sales', 'icon' => 'fa-receipt', 'url' => url('sales'), 'patterns' => ['sales*', 'sales_action*']],
                    ['file' => 'customers.php', 'label' => 'Customers', 'icon' => 'fa-users', 'url' => url('customers'), 'patterns' => ['customers*', 'customers_action*']],
                    ['file' => 'crm.php', 'label' => 'CRM', 'icon' => 'fa-handshake', 'url' => url('crm'), 'patterns' => ['crm*']],
                ],
            ],
            [
                'label' => 'Commercial Operations',
                'icon' => 'fa-briefcase',
                'items' => [
                    ['file' => 'commercial.php', 'label' => 'Commercial Dashboard', 'icon' => 'fa-chart-line', 'url' => route('commercial.dashboard'), 'patterns' => ['commercial'], 'permission' => 'commercial.dashboard.view'],
                    ['file' => 'commercial-leads.php', 'label' => 'Leads', 'icon' => 'fa-filter-circle-dollar', 'url' => route('commercial.leads.index'), 'patterns' => ['commercial/leads*'], 'permission' => 'commercial.leads.view'],
                    ['file' => 'commercial-organizations.php', 'label' => 'Organizations', 'icon' => 'fa-building', 'url' => route('commercial.organizations.index'), 'patterns' => ['commercial/organizations*'], 'permission' => 'commercial.organizations.view'],
                    ['file' => 'commercial-stakeholders.php', 'label' => 'Stakeholders', 'icon' => 'fa-address-book', 'url' => route('commercial.stakeholders.index'), 'patterns' => ['commercial/stakeholders*'], 'permission' => 'commercial.stakeholders.view'],
                    ['file' => 'commercial-opportunities.php', 'label' => 'Opportunities', 'icon' => 'fa-handshake-angle', 'url' => route('commercial.opportunities.index'), 'patterns' => ['commercial/opportunities*'], 'permission' => 'commercial.opportunities.view'],
                    ['file' => 'commercial-pipeline.php', 'label' => 'Commercial Pipeline', 'icon' => 'fa-table-columns', 'url' => route('commercial.opportunities.index'), 'patterns' => ['commercial/pipeline*'], 'permission' => 'commercial.opportunities.view'],
                    ['file' => 'commercial-activities.php', 'label' => 'Activities', 'icon' => 'fa-list-check', 'url' => route('commercial.activities.index'), 'patterns' => ['commercial/activities*'], 'permission' => 'commercial.activities.view'],
                    ['file' => 'commercial-meetings.php', 'label' => 'Meetings', 'icon' => 'fa-calendar-days', 'url' => route('commercial.meetings.index'), 'patterns' => ['commercial/meetings*'], 'permission' => 'commercial.meetings.view'],
                    ['file' => 'commercial-site-visits.php', 'label' => 'Site Visits', 'icon' => 'fa-location-dot', 'url' => route('commercial.site_visits.index'), 'patterns' => ['commercial/site-visits*'], 'permission' => 'commercial.site_visits.view'],
                ],
            ],
            [
                'label' => 'Inventory',
                'icon' => 'fa-boxes-stacked',
                'items' => [
                    ['file' => 'products.php', 'label' => 'Products', 'icon' => 'fa-box', 'url' => url('products'), 'patterns' => ['products*', 'products_action*']],
                    ['file' => 'inventory.php', 'label' => 'Inventory', 'icon' => 'fa-warehouse', 'url' => url('inventory'), 'patterns' => ['inventory*']],
                    ['file' => 'purchases.php', 'label' => 'Purchases', 'icon' => 'fa-cart-shopping', 'url' => url('purchases'), 'patterns' => ['purchases*']],
                    ['file' => 'suppliers.php', 'label' => 'Suppliers', 'icon' => 'fa-truck', 'url' => url('suppliers'), 'patterns' => ['suppliers*', 'suppliers_action*']],
                ],
            ],
            [
                'label' => 'Finance',
                'icon' => 'fa-scale-balanced',
                'items' => [
                    ['file' => 'finance.php', 'label' => 'Finance Dashboard', 'icon' => 'fa-chart-pie', 'url' => route('finance.dashboard'), 'patterns' => ['finance'], 'permission' => 'finance.dashboard.view'],
                    ['file' => 'finance-transactions.php', 'label' => 'Transaction Register', 'icon' => 'fa-list-check', 'url' => route('finance.transactions.index'), 'patterns' => ['finance/transactions*'], 'permission' => 'finance.transactions.view'],
                    ['file' => 'finance-budgets.php', 'label' => 'Budget Control', 'icon' => 'fa-wallet', 'url' => route('finance.budgets.index'), 'patterns' => ['finance/budgets*'], 'permission' => 'finance.budgets.view'],
                    ['file' => 'finance-accounts.php', 'label' => 'Chart of Accounts', 'icon' => 'fa-layer-group', 'url' => route('finance.accounts.index'), 'patterns' => ['finance/accounts*'], 'permission' => 'finance.accounts.view'],
                    ['file' => 'accounting.php', 'label' => 'Accounting', 'icon' => 'fa-calculator', 'url' => url('accounting'), 'patterns' => ['accounting*']],
                    ['file' => 'document_templates.php', 'label' => 'Document Templates', 'icon' => 'fa-file-lines', 'url' => url('document_templates'), 'patterns' => ['document_templates*']],
                    ['file' => 'banking.php', 'label' => 'Banking', 'icon' => 'fa-building-columns', 'url' => url('banking'), 'patterns' => ['banking*']],
                    ['file' => 'budgets.php', 'label' => 'Budgets', 'icon' => 'fa-wallet', 'url' => url('budgets'), 'patterns' => ['budgets*']],
                    ['file' => 'assets.php', 'label' => 'Assets', 'icon' => 'fa-laptop-file', 'url' => url('assets'), 'patterns' => ['assets*']],
                    ['file' => 'reports.php', 'label' => 'Reports', 'icon' => 'fa-chart-column', 'url' => url('reports'), 'patterns' => ['reports*']],
                ],
            ],
            [
                'label' => 'Human Resource',
                'icon' => 'fa-users-gear',
                'items' => [
                    ['file' => 'hr.php', 'label' => 'HR Command Centre', 'icon' => 'fa-chart-pie', 'url' => route('hr.command'), 'patterns' => ['hr'], 'permission' => 'hr.command.view'],
                    ['file' => 'hr-departments.php', 'label' => 'Organization Structure', 'icon' => 'fa-sitemap', 'url' => route('hr.departments.index'), 'patterns' => ['hr/departments*'], 'permission' => 'hr.structure.view'],
                    ['file' => 'hr-positions.php', 'label' => 'Positions & Jobs', 'icon' => 'fa-id-badge', 'url' => route('hr.positions.index'), 'patterns' => ['hr/positions*'], 'permission' => 'hr.positions.view'],
                    ['file' => 'human_resources.php', 'label' => 'Employees', 'icon' => 'fa-users', 'url' => url('human_resources'), 'patterns' => ['human_resources*']],
                    ['file' => 'hr_contracts.php', 'label' => 'Contracts & Roles', 'icon' => 'fa-file-signature', 'url' => url('hr_contracts'), 'patterns' => ['hr_contracts*']],
                    ['file' => 'hr_attendance.php', 'label' => 'Attendance', 'icon' => 'fa-clock', 'url' => url('hr_attendance'), 'patterns' => ['hr_attendance*']],
                    ['file' => 'hr_leave.php', 'label' => 'Leave', 'icon' => 'fa-calendar-check', 'url' => url('hr_leave'), 'patterns' => ['hr_leave*']],
                    ['file' => 'hr_advances.php', 'label' => 'Advances & Loans', 'icon' => 'fa-hand-holding-dollar', 'url' => url('hr_advances'), 'patterns' => ['hr_advances*']],
                    ['file' => 'hr_documents.php', 'label' => 'Documents', 'icon' => 'fa-folder-open', 'url' => url('hr_documents'), 'patterns' => ['hr_documents*']],
                    ['file' => 'hr_performance.php', 'label' => 'Performance', 'icon' => 'fa-chart-line', 'url' => url('hr_performance'), 'patterns' => ['hr_performance*']],
                    ['file' => 'hr_payroll_readiness.php', 'label' => 'Payroll Readiness', 'icon' => 'fa-clipboard-check', 'url' => url('hr_payroll_readiness'), 'patterns' => ['hr_payroll_readiness*']],
                    ['file' => 'payroll.php', 'label' => 'Payroll', 'icon' => 'fa-money-check-dollar', 'url' => url('payroll'), 'patterns' => ['payroll*']],
                ],
            ],
            [
                'label' => 'Operations',
                'icon' => 'fa-briefcase',
                'items' => [
                    ['file' => 'mobile_app.php', 'label' => 'Mobile App', 'icon' => 'fa-mobile-screen-button', 'url' => url('mobile_app'), 'patterns' => ['mobile_app*']],
                    ['file' => 'notifications.php', 'label' => 'Notifications', 'icon' => 'fa-bell', 'url' => url('notifications'), 'patterns' => ['notifications*']],
                ],
            ],
            [
                'label' => 'Reports',
                'icon' => 'fa-file-lines',
                'items' => [
                    ['file' => 'reports.php', 'label' => 'Reports', 'icon' => 'fa-chart-column', 'url' => url('reports'), 'patterns' => ['reports*']],
                ],
            ],
            [
                'label' => 'Users & Roles Management',
                'icon' => 'fa-users-gear',
                'items' => [
                    ['file' => 'users.php', 'label' => 'Users', 'icon' => 'fa-users-gear', 'url' => route('settings.users'), 'patterns' => ['settings/users*'], 'permission' => 'users.manage'],
                    ['file' => 'roles.php', 'label' => 'Roles & Permissions', 'icon' => 'fa-shield-halved', 'url' => route('settings.roles'), 'patterns' => ['settings/roles*'], 'permission' => 'roles.manage'],
                    ['file' => 'security.php', 'label' => 'Security Settings', 'icon' => 'fa-lock', 'url' => route('settings.security'), 'patterns' => ['settings/security*'], 'permission' => 'security.manage'],
                    ['file' => 'audit-logs.php', 'label' => 'Audit Logs', 'icon' => 'fa-clock-rotate-left', 'url' => route('settings.audit_logs'), 'patterns' => ['settings/audit-logs*'], 'permission' => 'audit.view'],
                ],
            ],
            [
                'label' => 'Settings',
                'icon' => 'fa-sliders',
                'items' => [
                    ['file' => 'foundation.php', 'label' => 'Enterprise Foundation', 'icon' => 'fa-diagram-project', 'url' => route('foundation.dashboard'), 'patterns' => ['foundation*'], 'permission' => 'foundation.view'],
                    ['file' => 'settings.php', 'label' => 'Overview', 'icon' => 'fa-gear', 'url' => url('settings') . '?section=overview', 'patterns' => ['settings'], 'section' => 'overview'],
                    ['file' => 'settings.php', 'label' => 'Company', 'icon' => 'fa-building', 'url' => url('settings') . '?section=company', 'patterns' => ['settings'], 'section' => 'company'],
                    ['file' => 'settings.php', 'label' => 'Finance', 'icon' => 'fa-coins', 'url' => url('settings') . '?section=finance', 'patterns' => ['settings'], 'section' => 'finance'],
                    ['file' => 'settings.php', 'label' => 'Organization', 'icon' => 'fa-users-gear', 'url' => url('settings') . '?section=organization', 'patterns' => ['settings'], 'section' => 'organization'],
                    ['file' => 'settings.php', 'label' => 'Operations', 'icon' => 'fa-diagram-project', 'url' => url('settings') . '?section=operations', 'patterns' => ['settings'], 'section' => 'operations'],
                    ['file' => 'settings.php', 'label' => 'Documents', 'icon' => 'fa-file-lines', 'url' => url('settings') . '?section=documents', 'patterns' => ['settings'], 'section' => 'documents'],
                    ['file' => 'settings.php', 'label' => 'Communications', 'icon' => 'fa-envelope-open-text', 'url' => url('settings') . '?section=communications', 'patterns' => ['settings'], 'section' => 'communications'],
                    ['file' => 'settings.php', 'label' => 'Security & Data', 'icon' => 'fa-shield-halved', 'url' => url('settings') . '?section=security_data', 'patterns' => ['settings'], 'section' => 'security_data'],
                    ['file' => 'settings.php', 'label' => 'System', 'icon' => 'fa-server', 'url' => url('settings') . '?section=system', 'patterns' => ['settings'], 'section' => 'system'],
                ],
            ],
        ];
    }

    public static function visibleGroups(?User $user): array
    {
        if ($user?->tenant_id) {
            Role::ensureDefaultsForTenant((int) $user->tenant_id);
            $user->unsetRelation('assignedRole')->load('assignedRole');
        }

        return array_values(array_filter(array_map(function (array $group) use ($user): array {
            $group['items'] = array_values(array_filter(
                $group['items'],
                fn (array $item): bool => self::canOpen($item, $user)
            ));

            return $group;
        }, self::groups()), fn (array $group): bool => count($group['items']) > 0));
    }

    public static function canOpen(array $item, ?User $user): bool
    {
        if (! $user) {
            return false;
        }

        if (isset($item['permission'])) {
            return $user->hasPermission($item['permission']);
        }

        $page = str_replace('.php', '', $item['file'] ?? '');
        $permission = PagePermissionMap::forPage(str_replace('-', '_', $page));

        return ! $permission || $user->hasPermission($permission);
    }

    public static function isItemActive(array $item, ?string $currentPage = null): bool
    {
        if (($item['section'] ?? null) !== null) {
            return Request::is('settings') && request('section', 'overview') === $item['section'];
        }

        $currentPage ??= self::currentPage();
        if ($currentPage === ($item['file'] ?? '')) {
            return true;
        }

        foreach (($item['patterns'] ?? []) as $pattern) {
            if (Request::is($pattern)) {
                return true;
            }
        }

        return false;
    }

    public static function currentPage(): string
    {
        $path = request()->path();
        if ($path === '/') {
            return 'dashboard.php';
        }

        $page = basename($path);

        return str_ends_with($page, '.php') ? $page : $page . '.php';
    }
}
