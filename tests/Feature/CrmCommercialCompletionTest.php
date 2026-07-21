<?php

namespace Tests\Feature;

use App\Models\Commercial\CommercialOpportunity;
use App\Models\Commercial\CommercialOrganization;
use App\Models\Commercial\CommercialQuotation;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CrmCommercialCompletionTest extends TestCase
{
    use RefreshDatabase;

    public function test_crm_account_plan_health_timeline_renewal_and_expansion_are_visible_on_customer_360(): void
    {
        $admin = $this->createUser('super_admin');
        [$customerId, $organization, $opportunity] = $this->createCustomerOpportunity($admin);

        $this->actingAs($admin)
            ->post(route('crm.accounts.account_plan.store', $customerId), [
                'relationship_stage' => 'Strategic Account',
                'objectives' => 'Grow adoption across departments.',
                'growth_strategy' => 'Expand to finance and project delivery teams.',
                'retention_strategy' => 'Quarterly executive reviews.',
                'risk_level' => 'Low',
                'next_review_on' => now()->addMonth()->toDateString(),
            ])
            ->assertRedirect();

        $this->actingAs($admin)
            ->post(route('crm.accounts.health.capture', $customerId))
            ->assertRedirect();

        $this->actingAs($admin)
            ->post(route('commercial.opportunities.renewals.store', $opportunity), [
                'renewal_due_on' => now()->addYear()->toDateString(),
                'renewal_value' => 6000000,
                'retention_plan' => 'Begin renewal conversation 90 days before expiry.',
            ])
            ->assertRedirect();

        $this->actingAs($admin)
            ->post(route('commercial.opportunities.expansions.store', $opportunity), [
                'expansion_type' => 'Cross-sell',
                'title' => 'Add Customer Success module',
                'estimated_value' => 2000000,
                'rationale' => 'Customer has open onboarding needs.',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('crm_account_plans', ['tenant_id' => $admin->tenant_id, 'customer_id' => $customerId, 'relationship_stage' => 'Strategic Account']);
        $this->assertDatabaseHas('crm_customer_health_snapshots', ['tenant_id' => $admin->tenant_id, 'customer_id' => $customerId]);
        $this->assertDatabaseHas('crm_account_timeline', ['tenant_id' => $admin->tenant_id, 'customer_id' => $customerId, 'event_type' => 'Renewal']);
        $this->assertDatabaseHas('commercial_renewals', ['tenant_id' => $admin->tenant_id, 'customer_id' => $customerId, 'renewal_value' => 6000000]);
        $this->assertDatabaseHas('commercial_expansion_opportunities', ['tenant_id' => $admin->tenant_id, 'customer_id' => $customerId, 'title' => 'Add Customer Success module']);

        $this->actingAs($admin)
            ->get(route('crm.accounts.show', $customerId))
            ->assertOk()
            ->assertSee('Account Plan')
            ->assertSee('Strategic Account')
            ->assertSee('Customer Health')
            ->assertSee('Renewals')
            ->assertSee('Add Customer Success module');
    }

    public function test_commercial_stage_controls_negotiation_and_lost_analysis_complete_daily_sales_workflow(): void
    {
        $admin = $this->createUser('super_admin');
        [$customerId, $organization, $opportunity] = $this->createCustomerOpportunity($admin, [
            'current_stage' => 'Negotiation',
            'probability' => 75,
            'customer_need' => 'Needs integrated CRM and Commercial workflows.',
            'decision_process' => 'Managing Director and Finance approve final price.',
            'identified_risks' => 'Budget approval may delay signature.',
        ]);

        CommercialQuotation::create([
            'tenant_id' => $admin->tenant_id,
            'opportunity_id' => $opportunity->id,
            'reference' => 'QUO-COMP-001',
            'quotation_date' => now()->toDateString(),
            'valid_until' => now()->addDays(14)->toDateString(),
            'subtotal' => 6000000,
            'discount_amount' => 0,
            'tax_amount' => 0,
            'total' => 6000000,
            'currency' => 'UGX',
            'status' => 'Draft',
            'prepared_by' => $admin->id,
        ]);

        $this->actingAs($admin)
            ->post(route('commercial.opportunities.stage_controls.verify', $opportunity))
            ->assertRedirect();

        $this->actingAs($admin)
            ->post(route('commercial.opportunities.negotiations.store', $opportunity), [
                'topic' => 'Final subscription price',
                'customer_position' => 'Customer requested phased payment.',
                'texaro_position' => 'Texaro accepts phased payment after contract signature.',
                'proposed_value' => 6000000,
                'next_follow_up_on' => now()->addDays(2)->toDateString(),
            ])
            ->assertRedirect();

        $this->actingAs($admin)
            ->post(route('commercial.opportunities.lost_analysis.store', $opportunity), [
                'primary_reason' => 'Budget deferred',
                'competitor_name' => 'Manual spreadsheets',
                'lessons_learned' => 'Earlier finance stakeholder engagement needed.',
                'recovery_action' => 'Reopen in next planning cycle.',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('commercial_stage_controls', ['tenant_id' => $admin->tenant_id, 'opportunity_id' => $opportunity->id, 'stage' => 'Negotiation', 'status' => 'Passed']);
        $this->assertDatabaseHas('commercial_negotiations', ['tenant_id' => $admin->tenant_id, 'opportunity_id' => $opportunity->id, 'topic' => 'Final subscription price']);
        $this->assertDatabaseHas('commercial_lost_opportunity_analyses', ['tenant_id' => $admin->tenant_id, 'opportunity_id' => $opportunity->id, 'primary_reason' => 'Budget deferred']);
        $this->assertSame('Lost', $opportunity->fresh()->current_stage);
        $this->assertDatabaseHas('crm_account_timeline', ['tenant_id' => $admin->tenant_id, 'customer_id' => $customerId, 'event_type' => 'Lost Opportunity']);

        $this->actingAs($admin)
            ->get(route('commercial.opportunities.show', $opportunity))
            ->assertOk()
            ->assertSee('Stage Control')
            ->assertSee('Negotiation History')
            ->assertSee('Lost Opportunity Analysis')
            ->assertSee('Budget deferred');
    }

    private function createCustomerOpportunity(User $admin, array $opportunityOverrides = []): array
    {
        $organization = CommercialOrganization::create([
            'tenant_id' => $admin->tenant_id,
            'reference' => 'ORG-COMP-' . str()->random(5),
            'legal_name' => 'Complete CRM Customer',
            'primary_email' => 'complete@example.test',
            'customer_status' => 'Active Customer',
        ]);

        $customerId = DB::table('customers')->insertGetId([
            'tenant_id' => $admin->tenant_id,
            'commercial_organization_id' => $organization->id,
            'commercial_reference' => $organization->reference,
            'commercial_sync_status' => 'Synced',
            'name' => 'Complete CRM Customer',
            'company_name' => 'Complete CRM Customer',
            'email' => 'complete@example.test',
            'customer_code' => 'CUS-COMP-' . str()->random(5),
            'customer_group' => 'Commercial',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $organization->forceFill(['legacy_customer_id' => $customerId])->save();

        $opportunity = CommercialOpportunity::create(array_merge([
            'tenant_id' => $admin->tenant_id,
            'organization_id' => $organization->id,
            'reference' => 'OPP-COMP-' . str()->random(5),
            'title' => 'Complete commercial workflow',
            'current_stage' => 'Qualified',
            'probability' => 35,
            'estimated_value' => 6000000,
            'currency' => 'UGX',
            'customer_need' => 'Needs customer account and revenue workflow.',
        ], $opportunityOverrides));

        return [$customerId, $organization, $opportunity];
    }

    private function createUser(string $roleSlug): User
    {
        $tenantId = DB::table('tenants')->insertGetId([
            'company_name' => 'CRM Commercial Completion Test Company',
            'slug' => 'crm-commercial-completion-' . $roleSlug . '-' . str()->random(6),
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
            'name' => 'CRM Commercial User ' . $roleSlug,
            'email' => 'crm-commercial-' . $roleSlug . '-' . str()->random(6) . '@example.test',
            'password' => Hash::make('Password#12345'),
            'role' => $roleSlug,
            'is_active' => true,
            'password_changed_at' => now(),
        ]);
    }
}
