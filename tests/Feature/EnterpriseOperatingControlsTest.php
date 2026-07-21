<?php

namespace Tests\Feature;

use App\Models\Commercial\CommercialBillingRequest;
use App\Models\Commercial\CommercialContract;
use App\Models\Commercial\CommercialOpportunity;
use App\Models\Commercial\CommercialOrganization;
use App\Models\Commercial\CommercialQuotation;
use App\Models\Finance\FinanceAccount;
use App\Models\Finance\FinanceBudgetLine;
use App\Models\Role;
use App\Models\User;
use App\Services\Enterprise\EnterpriseOperatingControlService;
use App\Services\Finance\FinanceControlService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class EnterpriseOperatingControlsTest extends TestCase
{
    use RefreshDatabase;

    public function test_commercial_billing_finance_project_and_customer_success_flow_is_controlled(): void
    {
        $admin = $this->createUser('super_admin');
        $organization = CommercialOrganization::create([
            'tenant_id' => $admin->tenant_id,
            'reference' => 'ORG-CTRL-001',
            'legal_name' => 'Controlled Customer',
            'customer_status' => 'Active Customer',
        ]);
        $opportunity = CommercialOpportunity::create([
            'tenant_id' => $admin->tenant_id,
            'organization_id' => $organization->id,
            'reference' => 'OPP-CTRL-001',
            'title' => 'Controlled Implementation',
            'current_stage' => 'Won',
            'probability' => 100,
            'estimated_value' => 8000000,
            'currency' => 'UGX',
        ]);
        $quotation = CommercialQuotation::create([
            'tenant_id' => $admin->tenant_id,
            'opportunity_id' => $opportunity->id,
            'reference' => 'QUO-CTRL-001',
            'quotation_date' => now()->toDateString(),
            'valid_until' => now()->addDays(14)->toDateString(),
            'subtotal' => 8000000,
            'discount_amount' => 0,
            'tax_amount' => 0,
            'total' => 8000000,
            'currency' => 'UGX',
            'status' => 'Accepted',
            'prepared_by' => $admin->id,
            'accepted_at' => now(),
        ]);
        $contract = CommercialContract::create([
            'tenant_id' => $admin->tenant_id,
            'opportunity_id' => $opportunity->id,
            'quotation_id' => $quotation->id,
            'reference' => 'CON-CTRL-001',
            'contract_title' => 'Controlled implementation contract',
            'contract_value' => 8000000,
            'currency' => 'UGX',
            'status' => 'Signed',
            'prepared_by' => $admin->id,
            'signed_at' => now(),
        ]);
        $billing = CommercialBillingRequest::create([
            'tenant_id' => $admin->tenant_id,
            'opportunity_id' => $opportunity->id,
            'contract_id' => $contract->id,
            'quotation_id' => $quotation->id,
            'reference' => 'BIL-CTRL-001',
            'amount' => 8000000,
            'currency' => 'UGX',
            'requested_invoice_date' => now()->toDateString(),
            'status' => 'Requested',
            'requested_by' => $admin->id,
        ]);

        $result = app(EnterpriseOperatingControlService::class)->verifyCommercialHandoff($opportunity->fresh(), $admin);
        $this->assertTrue($result['ready']);
        $this->assertSame('Finance Ready', $opportunity->fresh()->sales_handoff_status);

        $this->actingAs($admin)
            ->post(route('finance.billing_requests.review'), [
                'billing_request_id' => $billing->id,
                'decision' => 'Approved',
                'notes' => 'Commercial controls verified.',
            ])
            ->assertRedirect();

        $projectId = app(\App\Services\Enterprise\EnterpriseExpansionService::class)->createProjectFromOpportunity($admin->tenant_id, $opportunity->id, $admin->id);
        $milestoneId = (int) DB::table('project_milestones')->where('tenant_id', $admin->tenant_id)->where('project_id', $projectId)->value('id');

        $this->actingAs($admin)
            ->post(route('delivery.projects.milestones.complete'), [
                'project_id' => $projectId,
                'milestone_id' => $milestoneId,
                'evidence_summary' => 'Customer accepted kickoff requirements.',
            ])
            ->assertRedirect();

        $this->actingAs($admin)
            ->post(route('delivery.projects.handover_customer_success'), [
                'project_id' => $projectId,
                'handover_notes' => 'Implementation accepted and ready for onboarding.',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('enterprise_workflow_checks', ['tenant_id' => $admin->tenant_id, 'workflow' => 'Commercial to Finance to Project', 'status' => 'Passed']);
        $this->assertDatabaseHas('finance_control_reviews', ['tenant_id' => $admin->tenant_id, 'source_type' => 'billing_request', 'decision' => 'Approved']);
        $this->assertDatabaseHas('finance_transactions', ['tenant_id' => $admin->tenant_id, 'source_type' => 'billing_request', 'direction' => 'Inflow', 'approval_status' => 'Approved']);
        $this->assertDatabaseHas('project_delivery_gates', ['tenant_id' => $admin->tenant_id, 'milestone_id' => $milestoneId, 'status' => 'Verified']);
        $this->assertDatabaseHas('customer_success_handovers', ['tenant_id' => $admin->tenant_id, 'project_id' => $projectId, 'status' => 'Completed']);
        $this->assertDatabaseHas('domain_events', ['tenant_id' => $admin->tenant_id, 'event_name' => 'customer_success.handover.completed']);
    }

    public function test_finance_procurement_approval_document_generation_and_scorecard_are_recorded(): void
    {
        $admin = $this->createUser('super_admin');
        app(FinanceControlService::class)->bootstrapTenant($admin->tenant_id);
        $account = FinanceAccount::where('tenant_id', $admin->tenant_id)->where('code', '6000')->firstOrFail();
        $line = FinanceBudgetLine::create([
            'tenant_id' => $admin->tenant_id,
            'fiscal_year_id' => app(FinanceControlService::class)->currentFiscalYear($admin->tenant_id)->id,
            'account_id' => $account->id,
            'reference' => 'BUD-CTRL-001',
            'description' => 'Control budget',
            'annual_budget' => 10000000,
            'status' => 'Approved',
        ]);

        $this->actingAs($admin)
            ->post(route('finance.purchase_requests.store'), [
                'budget_line_id' => $line->id,
                'title' => 'Controlled procurement',
                'estimated_amount' => 1200000,
            ])
            ->assertRedirect();
        $purchaseRequestId = (int) DB::table('purchase_requests')->where('tenant_id', $admin->tenant_id)->where('title', 'Controlled procurement')->value('id');

        $this->actingAs($admin)
            ->post(route('finance.purchase_requests.approve'), [
                'purchase_request_id' => $purchaseRequestId,
                'notes' => 'Budget available.',
            ])
            ->assertRedirect();

        $this->actingAs($admin)
            ->post(route('foundation.documents.generate'), [
                'module' => 'Finance',
                'document_type' => 'Purchase Order',
                'title' => 'Controlled procurement order',
                'summary' => 'Generated from approved procurement controls.',
            ])
            ->assertRedirect();

        $this->actingAs($admin)
            ->post(route('intelligence.refresh'))
            ->assertRedirect();

        $this->assertDatabaseHas('purchase_requests', ['id' => $purchaseRequestId, 'status' => 'Approved']);
        $this->assertDatabaseHas('finance_control_reviews', ['tenant_id' => $admin->tenant_id, 'source_type' => 'purchase_request', 'decision' => 'Approved']);
        $this->assertDatabaseHas('document_records', ['tenant_id' => $admin->tenant_id, 'document_type' => 'Purchase Order', 'status' => 'Generated']);
        $this->assertDatabaseHas('enterprise_generated_documents', ['tenant_id' => $admin->tenant_id, 'document_type' => 'Purchase Order']);
        $this->assertDatabaseHas('enterprise_scorecard_snapshots', ['tenant_id' => $admin->tenant_id, 'scope' => 'Company']);
        $this->assertDatabaseHas('domain_events', ['tenant_id' => $admin->tenant_id, 'event_name' => 'scorecard.company.captured']);
    }

    private function createUser(string $roleSlug): User
    {
        $tenantId = DB::table('tenants')->insertGetId([
            'company_name' => 'Operating Controls Test Company',
            'slug' => 'controls-test-' . $roleSlug . '-' . str()->random(6),
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
            'name' => 'Controls User ' . $roleSlug,
            'email' => 'controls-' . $roleSlug . '-' . str()->random(6) . '@example.test',
            'password' => Hash::make('Password#12345'),
            'role' => $roleSlug,
            'is_active' => true,
            'password_changed_at' => now(),
        ]);
    }
}
