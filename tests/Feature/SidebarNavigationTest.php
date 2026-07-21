<?php

namespace Tests\Feature;

use App\Support\Navigation;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SidebarNavigationTest extends TestCase
{
    use RefreshDatabase;

    public function test_shared_erp_sidebar_includes_commercial_and_hr_core_links(): void
    {
        $groups = collect(Navigation::groups());

        $commercial = collect($groups->firstWhere('label', 'Commercial Operations')['items'] ?? []);
        $crm = collect($groups->firstWhere('label', 'CRM & Customer Accounts')['items'] ?? []);
        $hr = collect($groups->firstWhere('label', 'Human Resource')['items'] ?? []);

        $this->assertSame(route('commercial.dashboard'), $commercial->firstWhere('label', 'Commercial Dashboard')['url'] ?? null);
        $this->assertSame(route('commercial.leads.index'), $commercial->firstWhere('label', 'Leads')['url'] ?? null);
        $this->assertSame(route('commercial.opportunities.index'), $commercial->firstWhere('label', 'Opportunities')['url'] ?? null);
        $this->assertSame(route('commercial.organizations.index'), $commercial->firstWhere('label', 'Prospect Organizations')['url'] ?? null);

        $this->assertSame(route('crm.dashboard'), $crm->firstWhere('label', 'CRM Dashboard')['url'] ?? null);
        $this->assertSame(route('crm.accounts.index'), $crm->firstWhere('label', 'Customer Accounts')['url'] ?? null);
        $this->assertNull($commercial->firstWhere('label', 'Customers'));
        $this->assertNull($commercial->firstWhere('label', 'CRM'));

        $this->assertSame(route('hr.command'), $hr->firstWhere('label', 'HR Command Centre')['url'] ?? null);
        $this->assertSame(route('hr.departments.index'), $hr->firstWhere('label', 'Organization Structure')['url'] ?? null);
        $this->assertSame(route('hr.positions.index'), $hr->firstWhere('label', 'Positions & Jobs')['url'] ?? null);

        $planning = collect($groups->firstWhere('label', 'Planning & Performance')['items'] ?? []);
        $this->assertSame(route('planning.dashboard'), $planning->firstWhere('label', 'Performance Dashboard')['url'] ?? null);
        $this->assertSame(route('planning.objectives.index'), $planning->firstWhere('label', 'Strategic Objectives')['url'] ?? null);
        $this->assertSame(route('planning.workplans.index'), $planning->firstWhere('label', 'Corporate Workplans')['url'] ?? null);
    }

    public function test_navigation_refreshes_stale_system_role_permissions_before_filtering(): void
    {
        $tenantId = DB::table('tenants')->insertGetId([
            'company_name' => 'Navigation Test Company',
            'slug' => 'navigation-test',
            'currency' => 'UGX',
            'status' => 'trial',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $role = Role::create([
            'tenant_id' => $tenantId,
            'name' => 'Manager',
            'slug' => 'manager',
            'description' => 'Stale manager permissions',
            'permissions' => ['dashboard.view'],
            'is_system' => true,
            'is_active' => true,
        ]);

        $user = User::create([
            'tenant_id' => $tenantId,
            'role_id' => $role->id,
            'name' => 'Navigation Manager',
            'email' => 'navigation-manager@example.test',
            'password' => Hash::make('Password#12345'),
            'role' => 'manager',
            'is_active' => true,
            'password_changed_at' => now(),
        ]);

        $groups = collect(Navigation::visibleGroups($user))->pluck('label')->all();

        $this->assertContains('Commercial Operations', $groups);
        $this->assertContains('CRM & Customer Accounts', $groups);
        $this->assertContains('Finance', $groups);
        $this->assertContains('Planning & Performance', $groups);
    }

    public function test_super_admin_sidebar_includes_every_new_module_group(): void
    {
        $tenantId = DB::table('tenants')->insertGetId([
            'company_name' => 'Super Admin Navigation Company',
            'slug' => 'super-admin-navigation',
            'currency' => 'UGX',
            'status' => 'trial',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $user = User::create([
            'tenant_id' => $tenantId,
            'name' => 'Super Admin',
            'email' => 'super-admin-navigation@example.test',
            'password' => Hash::make('Password#12345'),
            'role' => 'super_admin',
            'is_active' => true,
            'password_changed_at' => now(),
        ]);

        $groups = collect(Navigation::visibleGroups($user));

        $this->assertNotEmpty($groups->firstWhere('label', 'Commercial Operations')['items'] ?? []);
        $this->assertNotEmpty($groups->firstWhere('label', 'CRM & Customer Accounts')['items'] ?? []);
        $this->assertNotEmpty($groups->firstWhere('label', 'Finance')['items'] ?? []);
        $this->assertNotEmpty($groups->firstWhere('label', 'Human Resource')['items'] ?? []);
        $this->assertNotEmpty($groups->firstWhere('label', 'Planning & Performance')['items'] ?? []);
    }
}
