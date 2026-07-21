<?php

namespace Tests\Feature;

use App\Models\Finance\FinanceAccount;
use App\Models\Finance\FinanceBudgetLine;
use App\Models\Role;
use App\Models\User;
use App\Services\Finance\FinanceControlService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class FoundationFinanceCompletionTest extends TestCase
{
    use RefreshDatabase;

    public function test_phase_one_employee_profile_and_approval_rule_can_be_created(): void
    {
        $admin = $this->createUser('super_admin');

        $this->actingAs($admin)
            ->post(route('foundation.employees.store'), [
                'user_id' => $admin->id,
                'full_name' => 'Foundation Employee',
                'work_email' => 'employee@example.test',
                'employment_status' => 'Active',
                'joined_on' => now()->toDateString(),
            ])
            ->assertRedirect();

        $this->actingAs($admin)
            ->post(route('foundation.approval_rules.store'), [
                'module' => 'Finance',
                'request_type' => 'Expense',
                'minimum_amount' => 1000000,
                'approver_role' => 'Managing Director',
                'sequence' => 1,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('employee_profiles', [
            'tenant_id' => $admin->tenant_id,
            'full_name' => 'Foundation Employee',
        ]);
        $this->assertDatabaseHas('approval_rules', [
            'tenant_id' => $admin->tenant_id,
            'module' => 'Finance',
            'request_type' => 'Expense',
        ]);
        $this->assertDatabaseHas('domain_events', [
            'tenant_id' => $admin->tenant_id,
            'event_name' => 'employee.profile.created',
        ]);
    }

    public function test_phase_four_finance_procurement_controls_create_transactions_and_registers(): void
    {
        $accountant = $this->createUser('accountant');
        app(FinanceControlService::class)->bootstrapTenant($accountant->tenant_id);
        $account = FinanceAccount::where('tenant_id', $accountant->tenant_id)->where('code', '6000')->firstOrFail();
        $line = FinanceBudgetLine::create([
            'tenant_id' => $accountant->tenant_id,
            'fiscal_year_id' => app(FinanceControlService::class)->currentFiscalYear($accountant->tenant_id)->id,
            'account_id' => $account->id,
            'reference' => 'BUD-FINAL-001',
            'description' => 'Final phase budget',
            'annual_budget' => 10000000,
            'status' => 'Approved',
        ]);

        $this->actingAs($accountant)
            ->post(route('finance.expenses.store'), [
                'budget_line_id' => $line->id,
                'description' => 'Implementation logistics',
                'amount' => 400000,
                'expense_date' => now()->toDateString(),
            ])
            ->assertRedirect();

        $this->actingAs($accountant)
            ->post(route('finance.purchase_requests.store'), [
                'budget_line_id' => $line->id,
                'title' => 'Procure implementation laptops',
                'estimated_amount' => 3000000,
            ])
            ->assertRedirect();

        $this->actingAs($accountant)
            ->post(route('finance.purchase_orders.store'), [
                'supplier_name' => 'Hardware Supplier Ltd',
                'total_amount' => 3000000,
            ])
            ->assertRedirect();

        $this->actingAs($accountant)
            ->post(route('finance.supplier_bills.store'), [
                'supplier_name' => 'Hardware Supplier Ltd',
                'amount' => 3000000,
                'bill_date' => now()->toDateString(),
            ])
            ->assertRedirect();

        $this->actingAs($accountant)
            ->post(route('finance.payments.store'), [
                'payee_name' => 'Hardware Supplier Ltd',
                'amount' => 1500000,
                'method' => 'Bank',
            ])
            ->assertRedirect();

        $this->actingAs($accountant)
            ->post(route('finance.assets.store'), [
                'name' => 'Implementation Laptop',
                'category' => 'Computer Equipment',
                'cost' => 1500000,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('finance_expenses', ['tenant_id' => $accountant->tenant_id, 'description' => 'Implementation logistics']);
        $this->assertDatabaseHas('purchase_requests', ['tenant_id' => $accountant->tenant_id, 'title' => 'Procure implementation laptops']);
        $this->assertDatabaseHas('purchase_orders', ['tenant_id' => $accountant->tenant_id, 'supplier_name' => 'Hardware Supplier Ltd']);
        $this->assertDatabaseHas('supplier_bills', ['tenant_id' => $accountant->tenant_id, 'amount' => 3000000]);
        $this->assertDatabaseHas('finance_payments', ['tenant_id' => $accountant->tenant_id, 'amount' => 1500000]);
        $this->assertDatabaseHas('asset_register', ['tenant_id' => $accountant->tenant_id, 'name' => 'Implementation Laptop']);
        $this->assertDatabaseHas('finance_transactions', ['tenant_id' => $accountant->tenant_id, 'source_type' => 'expense', 'amount' => 400000]);
        $this->assertDatabaseHas('finance_transactions', ['tenant_id' => $accountant->tenant_id, 'source_type' => 'payment', 'amount' => 1500000]);
    }

    private function createUser(string $roleSlug): User
    {
        $tenantId = DB::table('tenants')->insertGetId([
            'company_name' => 'Completion Test Company',
            'slug' => 'completion-test-' . $roleSlug . '-' . str()->random(6),
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
            'name' => 'Completion User ' . $roleSlug,
            'email' => 'completion-' . $roleSlug . '-' . str()->random(6) . '@example.test',
            'password' => Hash::make('Password#12345'),
            'role' => $roleSlug,
            'is_active' => true,
            'password_changed_at' => now(),
        ]);
    }
}
