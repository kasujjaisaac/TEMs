<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use App\Services\Commercial\CommercialLegacyCustomerBridgeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class CrmCustomerAccountsTest extends TestCase
{
    use RefreshDatabase;

    public function test_crm_dashboard_requires_customer_account_permission(): void
    {
        $viewer = $this->createUser('viewer');

        $this->actingAs($viewer)
            ->get(route('crm.dashboard'))
            ->assertForbidden();
    }

    public function test_clean_crm_route_is_native_and_php_route_stays_legacy(): void
    {
        $cleanCrmRoutes = collect(Route::getRoutes())
            ->filter(fn ($route): bool => in_array('GET', $route->methods(), true) && $route->uri() === 'crm')
            ->values();

        $this->assertCount(1, $cleanCrmRoutes);
        $this->assertSame('crm.dashboard', $cleanCrmRoutes->first()->getName());
        $this->assertSame('erp.crm.legacy', Route::getRoutes()->getByName('erp.crm.legacy')?->getName());
    }

    public function test_customer_360_blends_crm_commercial_and_finance_without_duplicate_customer_owner(): void
    {
        $admin = $this->createUser('super_admin');

        $organizationId = DB::table('commercial_organizations')->insertGetId([
            'tenant_id' => $admin->tenant_id,
            'reference' => 'ORG-2026-00901',
            'legal_name' => 'Unified Account Ltd',
            'customer_status' => 'Active Customer',
            'relationship_score' => 88,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $customerId = DB::table('customers')->insertGetId([
            'tenant_id' => $admin->tenant_id,
            'commercial_organization_id' => $organizationId,
            'commercial_reference' => 'ORG-2026-00901',
            'commercial_sync_status' => 'Synced',
            'name' => 'Unified Account',
            'company_name' => 'Unified Account Ltd',
            'contact_person' => 'Jane Account',
            'email' => 'jane@unified.test',
            'phone' => '+25670000901',
            'customer_code' => 'CUS-00901',
            'customer_group' => 'Commercial',
            'payment_terms' => 'Net 30',
            'credit_status' => 'approved',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('commercial_organizations')->where('id', $organizationId)->update([
            'legacy_customer_id' => $customerId,
        ]);

        DB::table('commercial_opportunities')->insert([
            'tenant_id' => $admin->tenant_id,
            'organization_id' => $organizationId,
            'reference' => 'OPP-2026-00901',
            'title' => 'Customer 360 Expansion',
            'current_stage' => 'Negotiation',
            'probability' => 75,
            'estimated_value' => 4500000,
            'currency' => 'UGX',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('invoices')->insert([
            'tenant_id' => $admin->tenant_id,
            'invoice_number' => 'INV-00901',
            'invoice_type' => 'invoice',
            'customer_id' => $customerId,
            'invoice_date' => now()->toDateString(),
            'status' => 'sent',
            'total' => 4500000,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($admin)
            ->get(route('crm.accounts.show', $customerId))
            ->assertOk()
            ->assertSee('Unified Account Ltd')
            ->assertSee('Customer 360')
            ->assertSee('ORG-2026-00901')
            ->assertSee('Customer 360 Expansion')
            ->assertSee('INV-00901')
            ->assertSee('CRM source of truth');
    }

    public function test_commercial_sync_records_customer_identity_link_as_canonical_source(): void
    {
        $admin = $this->createUser('super_admin');

        $organization = \App\Models\Commercial\CommercialOrganization::create([
            'tenant_id' => $admin->tenant_id,
            'reference' => 'ORG-2026-IDENT',
            'legal_name' => 'Identity Account Ltd',
            'primary_email' => 'identity@example.test',
            'customer_status' => 'Prospect',
        ]);

        $customerId = app(CommercialLegacyCustomerBridgeService::class)->syncOrganization($organization, $admin);

        $this->assertDatabaseHas('customers', [
            'tenant_id' => $admin->tenant_id,
            'id' => $customerId,
            'enterprise_identity_status' => 'Canonical',
            'source_of_truth' => 'CRM Customer Account',
        ]);
        $this->assertDatabaseHas('customer_identity_links', [
            'tenant_id' => $admin->tenant_id,
            'customer_id' => $customerId,
            'source_table' => 'commercial_organizations',
            'source_id' => $organization->id,
            'source_reference' => 'ORG-2026-IDENT',
            'status' => 'Active',
        ]);

        $this->actingAs($admin)
            ->get(route('crm.accounts.show', $customerId))
            ->assertOk()
            ->assertSee('Enterprise Identity Links')
            ->assertSee('commercial_organizations');
    }

    private function createUser(string $roleSlug): User
    {
        $tenantId = DB::table('tenants')->insertGetId([
            'company_name' => 'CRM Test Company',
            'slug' => 'crm-test-' . $roleSlug,
            'currency' => 'UGX',
            'status' => 'trial',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Role::ensureDefaultsForTenant($tenantId);
        $role = Role::where('tenant_id', $tenantId)->where('slug', $roleSlug)->firstOrFail();

        return User::create([
            'tenant_id' => $tenantId,
            'role_id' => $role->id,
            'name' => 'CRM User ' . $roleSlug,
            'email' => 'crm-' . $roleSlug . '@example.test',
            'password' => Hash::make('Password#12345'),
            'role' => $roleSlug,
            'is_active' => true,
            'password_changed_at' => now(),
        ]);
    }
}
