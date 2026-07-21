<?php

namespace Tests\Feature;

use App\Models\Commercial\CommercialOpportunity;
use App\Models\Commercial\CommercialOrganization;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class EnterpriseExpansionPhasesTest extends TestCase
{
    use RefreshDatabase;

    public function test_delivery_workspace_creates_product_and_project_from_won_opportunity(): void
    {
        $admin = $this->createUser('super_admin');
        $organization = CommercialOrganization::create([
            'tenant_id' => $admin->tenant_id,
            'reference' => 'ORG-2026-90001',
            'legal_name' => 'Delivery Customer',
            'customer_status' => 'Active Customer',
        ]);
        $opportunity = CommercialOpportunity::create([
            'tenant_id' => $admin->tenant_id,
            'organization_id' => $organization->id,
            'reference' => 'OPP-2026-90001',
            'title' => 'Delivery Project',
            'current_stage' => 'Won',
            'probability' => 100,
            'estimated_value' => 12000000,
            'currency' => 'UGX',
        ]);

        $this->actingAs($admin)
            ->get(route('delivery.dashboard'))
            ->assertOk()
            ->assertSee('Products & Delivery', false);

        $this->actingAs($admin)
            ->post(route('delivery.products.store'), [
                'name' => 'Customer Portal',
                'category' => 'Software',
                'lifecycle_stage' => 'Active',
                'target_revenue' => 5000000,
            ])
            ->assertRedirect();

        $this->actingAs($admin)
            ->post(route('delivery.projects.from_opportunity'), ['opportunity_id' => $opportunity->id])
            ->assertRedirect();

        $this->assertDatabaseHas('products_portfolio', ['tenant_id' => $admin->tenant_id, 'name' => 'Customer Portal']);
        $this->assertDatabaseHas('implementation_projects', ['tenant_id' => $admin->tenant_id, 'opportunity_id' => $opportunity->id]);
        $this->assertDatabaseHas('project_milestones', ['tenant_id' => $admin->tenant_id, 'title' => 'Kickoff and requirements confirmation']);
        $this->assertDatabaseHas('domain_events', ['tenant_id' => $admin->tenant_id, 'event_name' => 'project.created_from_opportunity']);
    }

    public function test_customer_success_and_governance_registers_accept_operational_records(): void
    {
        $admin = $this->createUser('super_admin');

        $this->actingAs($admin)
            ->get(route('customer_success.dashboard'))
            ->assertOk()
            ->assertSee('Customer Success');

        $this->actingAs($admin)
            ->post(route('customer_success.tickets.store'), [
                'subject' => 'Customer cannot access reports',
                'description' => 'The finance report page fails for customer admin.',
                'priority' => 'High',
            ])
            ->assertRedirect();

        $this->actingAs($admin)
            ->post(route('governance.obligations.store'), [
                'title' => 'Renew data protection registration',
                'category' => 'Compliance',
                'due_on' => now()->addMonth()->toDateString(),
                'risk_level' => 'High',
                'notes' => 'Prepare renewal documents.',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('support_tickets', ['tenant_id' => $admin->tenant_id, 'subject' => 'Customer cannot access reports']);
        $this->assertDatabaseHas('compliance_obligations', ['tenant_id' => $admin->tenant_id, 'title' => 'Renew data protection registration']);
    }

    public function test_intelligence_refresh_generates_snapshots_signals_and_recommendations(): void
    {
        $admin = $this->createUser('super_admin');

        $this->actingAs($admin)
            ->get(route('intelligence.dashboard'))
            ->assertOk()
            ->assertSee('Enterprise Intelligence');

        $this->actingAs($admin)
            ->post(route('intelligence.refresh'))
            ->assertRedirect();

        $this->assertGreaterThan(0, DB::table('intelligence_metric_snapshots')->where('tenant_id', $admin->tenant_id)->count());
        $this->assertGreaterThan(0, DB::table('intelligence_signals')->where('tenant_id', $admin->tenant_id)->count());
        $this->assertGreaterThan(0, DB::table('intelligence_recommendations')->where('tenant_id', $admin->tenant_id)->count());
    }

    private function createUser(string $roleSlug): User
    {
        $tenantId = DB::table('tenants')->insertGetId([
            'company_name' => 'Expansion Test Company',
            'slug' => 'expansion-test-' . $roleSlug . '-' . str()->random(6),
            'currency' => 'UGX',
            'fiscal_year_start' => '2026-01-01',
            'status' => 'trial',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Role::ensureDefaultsForTenant($tenantId);
        $role = Role::where('tenant_id', $tenantId)->where('slug', $roleSlug)->firstOrFail();

        return User::create([
            'tenant_id' => $tenantId,
            'role_id' => $role->id,
            'name' => 'Expansion User ' . $roleSlug,
            'email' => 'expansion-' . $roleSlug . '-' . str()->random(6) . '@example.test',
            'password' => Hash::make('Password#12345'),
            'role' => $roleSlug,
            'is_active' => true,
            'password_changed_at' => now(),
        ]);
    }
}
