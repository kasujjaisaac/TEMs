<?php

namespace Tests\Feature;

use App\Models\Commercial\CommercialLead;
use App\Models\Commercial\CommercialOpportunity;
use App\Models\Commercial\CommercialOrganization;
use App\Models\Commercial\CommercialPipelineStage;
use App\Models\Commercial\CommercialSalesHandoff;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\Commercial\CommercialSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CommercialOperationsPhaseOneTest extends TestCase
{
    use RefreshDatabase;

    public function test_commercial_dashboard_requires_commercial_permission(): void
    {
        $viewer = $this->createUser('viewer');

        $this->actingAs($viewer)
            ->get(route('commercial.dashboard'))
            ->assertForbidden();
    }

    public function test_authorized_user_can_create_and_convert_lead(): void
    {
        $admin = $this->createUser('super_admin');
        $this->seed(CommercialSeeder::class);

        $this->actingAs($admin)
            ->post(route('commercial.leads.store'), [
                'organization_name' => 'Texaro Pilot Customer',
                'contact_person' => 'Jane Buyer',
                'telephone' => '+256700000111',
                'email' => 'jane@example.test',
                'lead_source' => 'Referral',
                'interested_product' => 'TEMS',
                'estimated_budget' => '2500000',
                'expected_decision_date' => now()->addDays(30)->toDateString(),
                'requirements_summary' => 'Needs a controlled commercial management workflow.',
                'pain_points' => 'Lead tracking is fragmented.',
                'temperature' => 'Hot',
                'lead_score' => 80,
                'status' => 'Qualified',
            ])
            ->assertRedirect();

        $lead = CommercialLead::firstOrFail();
        $this->assertSame('LEAD-' . now()->format('Y') . '-00001', $lead->reference);

        $this->actingAs($admin)
            ->post(route('commercial.leads.convert', $lead))
            ->assertRedirect();

        $lead->refresh();
        $opportunity = CommercialOpportunity::firstOrFail();

        $this->assertNotNull($lead->organization_id);
        $this->assertNotNull($lead->stakeholder_id);
        $this->assertSame($opportunity->id, $lead->opportunity_id);
        $this->assertSame('Converted', $lead->status);
        $this->assertSame(500000.0, $opportunity->weighted_value);
        $this->assertDatabaseHas('audit_logs', [
            'tenant_id' => $admin->tenant_id,
            'module' => 'commercial',
            'action' => 'converted',
        ]);
    }

    public function test_weighted_value_is_calculated_from_value_and_probability(): void
    {
        $admin = $this->createUser('super_admin');
        $this->seed(CommercialSeeder::class);

        $organizationId = DB::table('commercial_organizations')->insertGetId([
            'tenant_id' => $admin->tenant_id,
            'reference' => 'ORG-2026-99999',
            'legal_name' => 'Weighted Value Ltd',
            'customer_status' => 'Prospect',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($admin)
            ->post(route('commercial.opportunities.store'), [
                'organization_id' => $organizationId,
                'title' => 'Weighted Opportunity',
                'opportunity_type' => 'New Business',
                'current_stage' => 'Qualified',
                'probability' => 35,
                'estimated_value' => '1000000',
                'currency' => 'UGX',
            ])
            ->assertRedirect();

        $opportunity = CommercialOpportunity::firstOrFail();

        $this->assertSame(350000.0, $opportunity->weighted_value);
        $this->assertDatabaseHas('commercial_opportunity_stage_history', [
            'opportunity_id' => $opportunity->id,
            'new_stage' => 'Qualified',
        ]);
    }

    public function test_authorized_user_can_update_lead_and_organization_bridge(): void
    {
        $admin = $this->createUser('super_admin');

        $lead = CommercialLead::create([
            'tenant_id' => $admin->tenant_id,
            'reference' => 'LEAD-2026-00099',
            'organization_name' => 'Original Lead',
            'temperature' => 'Warm',
            'lead_score' => 20,
            'status' => 'New',
        ]);

        $this->actingAs($admin)
            ->put(route('commercial.leads.update', $lead), [
                'organization_name' => 'Updated Lead',
                'temperature' => 'Hot',
                'lead_score' => 70,
                'status' => 'Engaged',
            ])
            ->assertRedirect(route('commercial.leads.show', $lead));

        $this->assertDatabaseHas('commercial_leads', [
            'id' => $lead->id,
            'organization_name' => 'Updated Lead',
            'temperature' => 'Hot',
            'status' => 'Engaged',
        ]);

        $organization = CommercialOrganization::create([
            'tenant_id' => $admin->tenant_id,
            'reference' => 'ORG-2026-00099',
            'legal_name' => 'Bridge Customer',
            'customer_status' => 'Prospect',
        ]);

        $this->actingAs($admin)
            ->put(route('commercial.organizations.update', $organization), [
                'legacy_customer_id' => 15,
                'legal_name' => 'Bridge Customer Ltd',
                'customer_status' => 'Active Customer',
                'relationship_score' => 82,
            ])
            ->assertRedirect(route('commercial.organizations.show', $organization));

        $this->assertDatabaseHas('commercial_organizations', [
            'id' => $organization->id,
            'legacy_customer_id' => 15,
            'legal_name' => 'Bridge Customer Ltd',
            'customer_status' => 'Active Customer',
        ]);
    }

    public function test_opportunity_stage_change_updates_probability_and_history(): void
    {
        $admin = $this->createUser('super_admin');
        $this->seed(CommercialSeeder::class);

        $organization = CommercialOrganization::create([
            'tenant_id' => $admin->tenant_id,
            'reference' => 'ORG-2026-00100',
            'legal_name' => 'Stage Customer',
            'customer_status' => 'Prospect',
        ]);

        $opportunity = CommercialOpportunity::create([
            'tenant_id' => $admin->tenant_id,
            'organization_id' => $organization->id,
            'reference' => 'OPP-2026-00100',
            'title' => 'Stage Movement',
            'current_stage' => 'Qualified',
            'probability' => 20,
            'estimated_value' => 1000000,
            'currency' => 'UGX',
        ]);

        $stage = CommercialPipelineStage::where('tenant_id', $admin->tenant_id)->where('name', 'Negotiation')->firstOrFail();

        $this->actingAs($admin)
            ->patch(route('commercial.opportunities.stage.update', $opportunity), [
                'stage_id' => $stage->id,
                'reason' => 'Customer requested final pricing review',
                'notes' => 'Moved after discovery meeting.',
            ])
            ->assertRedirect(route('commercial.opportunities.show', $opportunity));

        $opportunity->refresh();
        $this->assertSame('Negotiation', $opportunity->current_stage);
        $this->assertSame(75, $opportunity->probability);
        $this->assertDatabaseHas('commercial_opportunity_stage_history', [
            'opportunity_id' => $opportunity->id,
            'previous_stage' => 'Qualified',
            'new_stage' => 'Negotiation',
            'reason' => 'Customer requested final pricing review',
        ]);
    }

    public function test_opportunity_can_be_handed_to_sales_as_customer_and_quotation(): void
    {
        $admin = $this->createUser('super_admin');

        $organization = CommercialOrganization::create([
            'tenant_id' => $admin->tenant_id,
            'reference' => 'ORG-2026-00400',
            'legal_name' => 'Sales Bridge Customer',
            'primary_email' => 'buyer@salesbridge.test',
            'primary_telephone' => '+25670000400',
            'customer_status' => 'Prospect',
            'payment_terms' => 'Net 15',
        ]);

        $opportunity = CommercialOpportunity::create([
            'tenant_id' => $admin->tenant_id,
            'organization_id' => $organization->id,
            'reference' => 'OPP-2026-00400',
            'title' => 'Sales Bridge Implementation',
            'product_or_service' => 'TEMS Commercial Suite',
            'current_stage' => 'Negotiation',
            'probability' => 75,
            'estimated_value' => 3200000,
            'currency' => 'UGX',
            'customer_need' => 'Needs one guided process from opportunity to payment.',
            'proposed_solution' => 'Deploy TEMS with Commercial and Sales traceability.',
        ]);

        $this->actingAs($admin)
            ->post(route('commercial.opportunities.handoff_to_sales', $opportunity), [
                'sales_instructions' => 'Protect the agreed scope and follow up within 24 hours.',
                'quotation_notes' => 'Commercially approved draft quotation.',
            ])
            ->assertRedirect(route('commercial.opportunities.show', $opportunity));

        $opportunity->refresh();
        $organization->refresh();
        $handoff = CommercialSalesHandoff::firstOrFail();

        $this->assertNotNull($organization->legacy_customer_id);
        $this->assertSame('Quotation Drafted', $opportunity->sales_handoff_status);
        $this->assertSame($handoff->quotation_id, $opportunity->legacy_quotation_id);

        $this->assertDatabaseHas('customers', [
            'id' => $organization->legacy_customer_id,
            'tenant_id' => $admin->tenant_id,
            'company_name' => 'Sales Bridge Customer',
        ]);
        $this->assertDatabaseHas('invoices', [
            'id' => $handoff->quotation_id,
            'tenant_id' => $admin->tenant_id,
            'invoice_type' => 'quotation',
            'customer_id' => $organization->legacy_customer_id,
            'commercial_opportunity_id' => $opportunity->id,
            'commercial_handoff_id' => $handoff->id,
            'total' => 3200000,
        ]);
        $this->assertDatabaseHas('invoice_lines', [
            'invoice_id' => $handoff->quotation_id,
            'tenant_id' => $admin->tenant_id,
            'line_total' => 3200000,
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'tenant_id' => $admin->tenant_id,
            'module' => 'commercial',
            'action' => 'sales_handoff_created',
        ]);
    }

    public function test_phase_zero_business_core_tables_are_migration_backed(): void
    {
        foreach ([
            'customers',
            'products',
            'product_categories',
            'income_categories',
            'expense_categories',
            'invoices',
            'invoice_lines',
            'invoice_payments',
            'suppliers',
            'purchases',
            'purchase_lines',
            'purchase_payments',
            'inventory_transactions',
        ] as $table) {
            $this->assertTrue(DB::getSchemaBuilder()->hasTable($table), "Expected [{$table}] to exist after migrations.");
        }

        foreach (['commercial_opportunity_id', 'commercial_handoff_id', 'stock_posted', 'accounting_posted'] as $column) {
            $this->assertTrue(DB::getSchemaBuilder()->hasColumn('invoices', $column), "Expected invoices.{$column} to exist.");
        }
    }

    private function createUser(string $roleSlug): User
    {
        $tenantId = DB::table('tenants')->insertGetId([
            'company_name' => 'Commercial Test Company',
            'slug' => 'commercial-test-' . $roleSlug,
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
            'name' => 'Commercial User ' . $roleSlug,
            'email' => 'commercial-' . $roleSlug . '@example.test',
            'password' => Hash::make('Password#12345'),
            'role' => $roleSlug,
            'is_active' => true,
            'password_changed_at' => now(),
        ]);
    }
}
