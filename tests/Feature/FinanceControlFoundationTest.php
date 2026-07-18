<?php

namespace Tests\Feature;

use App\Models\Finance\FinanceAccount;
use App\Models\Finance\FinanceBudgetLine;
use App\Models\Finance\FinanceTransaction;
use App\Models\Role;
use App\Models\User;
use App\Services\Finance\FinanceControlService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class FinanceControlFoundationTest extends TestCase
{
    use RefreshDatabase;

    public function test_finance_dashboard_requires_permission(): void
    {
        $viewer = $this->createUser('viewer');

        $this->actingAs($viewer)
            ->get(route('finance.dashboard'))
            ->assertForbidden();
    }

    public function test_finance_foundation_bootstraps_accounts_periods_and_budget_lines(): void
    {
        $accountant = $this->createUser('accountant');

        $this->actingAs($accountant)
            ->get(route('finance.dashboard'))
            ->assertOk()
            ->assertSee('Finance Control Centre');

        $this->assertDatabaseHas('finance_accounts', [
            'tenant_id' => $accountant->tenant_id,
            'code' => '4000',
            'name' => 'Sales Revenue',
        ]);
        $this->assertDatabaseCount('finance_periods', 12);

        $expense = FinanceAccount::where('tenant_id', $accountant->tenant_id)->where('code', '6000')->firstOrFail();

        $this->actingAs($accountant)
            ->post(route('finance.budgets.store'), [
                'account_id' => $expense->id,
                'reference' => 'BUD-OPS-001',
                'description' => 'Operations discipline budget',
                'annual_budget' => 12000000,
                'monthly_allocation' => 1000000,
                'status' => 'Approved',
            ])
            ->assertRedirect(route('finance.budgets.index'));

        $line = FinanceBudgetLine::firstOrFail();
        $this->assertSame(12000000.0, (float) $line->available_balance);
    }

    public function test_finance_sync_pulls_sales_and_procurement_activity(): void
    {
        $accountant = $this->createUser('accountant');

        DB::table('customers')->insert([
            'id' => 10,
            'tenant_id' => $accountant->tenant_id,
            'name' => 'Finance Customer',
            'company_name' => 'Finance Customer Ltd',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('invoices')->insert([
            'id' => 20,
            'tenant_id' => $accountant->tenant_id,
            'invoice_number' => 'INV-2026-00020',
            'invoice_type' => 'invoice',
            'customer_id' => 10,
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addDays(10)->toDateString(),
            'total' => 2500000,
            'status' => 'sent',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('invoice_payments')->insert([
            'id' => 30,
            'tenant_id' => $accountant->tenant_id,
            'invoice_id' => 20,
            'payment_date' => now()->toDateString(),
            'amount' => 1000000,
            'method' => 'bank',
            'reference' => 'BANK-001',
            'created_at' => now(),
        ]);
        DB::table('purchases')->insert([
            'id' => 40,
            'tenant_id' => $accountant->tenant_id,
            'purchase_number' => 'PUR-2026-00040',
            'supplier_id' => 7,
            'supplier' => 'Finance Supplier Ltd',
            'invoice_number' => 'SUP-40',
            'purchase_date' => now()->toDateString(),
            'due_date' => now()->addDays(14)->toDateString(),
            'status' => 'approved',
            'payment_status' => 'unpaid',
            'total_amount' => 700000,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        app(FinanceControlService::class)->syncOperatingActivity($accountant->tenant_id);

        $this->assertDatabaseHas('finance_transactions', [
            'tenant_id' => $accountant->tenant_id,
            'source_module' => 'Sales',
            'source_type' => 'invoice',
            'source_id' => 20,
            'direction' => 'Inflow',
            'amount' => 2500000,
        ]);
        $this->assertDatabaseHas('finance_transactions', [
            'tenant_id' => $accountant->tenant_id,
            'source_module' => 'Procurement',
            'source_type' => 'purchase',
            'source_id' => 40,
            'direction' => 'Outflow',
            'amount' => 700000,
        ]);

        $this->assertSame(3, FinanceTransaction::where('tenant_id', $accountant->tenant_id)->count());
    }

    public function test_finance_dashboard_handles_sales_records_without_matching_customer(): void
    {
        $accountant = $this->createUser('accountant');

        DB::table('invoices')->insert([
            'id' => 21,
            'tenant_id' => $accountant->tenant_id,
            'invoice_number' => 'INV-2026-00021',
            'invoice_type' => 'invoice',
            'customer_id' => 99,
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addDays(10)->toDateString(),
            'total' => 1800000,
            'status' => 'sent',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('invoice_payments')->insert([
            'id' => 31,
            'tenant_id' => $accountant->tenant_id,
            'invoice_id' => 21,
            'payment_date' => now()->toDateString(),
            'amount' => 800000,
            'reference' => 'BANK-002',
            'created_at' => now(),
        ]);

        $this->actingAs($accountant)
            ->get(route('finance.dashboard'))
            ->assertOk()
            ->assertSee('Finance Control Centre');

        $this->assertDatabaseHas('finance_transactions', [
            'tenant_id' => $accountant->tenant_id,
            'source_module' => 'Sales',
            'source_type' => 'invoice',
            'source_id' => 21,
            'amount' => 1800000,
        ]);
        $this->assertDatabaseHas('finance_transactions', [
            'tenant_id' => $accountant->tenant_id,
            'source_module' => 'Sales',
            'source_type' => 'receipt',
            'source_id' => 31,
            'amount' => 800000,
        ]);
    }

    private function createUser(string $roleSlug): User
    {
        $tenantId = DB::table('tenants')->insertGetId([
            'company_name' => 'Finance Test Company',
            'slug' => 'finance-test-' . $roleSlug . '-' . str()->random(6),
            'currency' => 'UGX',
            'fiscal_year_start' => '2026-07-01',
            'status' => 'trial',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Role::ensureDefaultsForTenant($tenantId);
        $role = Role::where('tenant_id', $tenantId)->where('slug', $roleSlug)->firstOrFail();

        return User::create([
            'tenant_id' => $tenantId,
            'role_id' => $role->id,
            'name' => 'Finance User ' . $roleSlug,
            'email' => 'finance-' . $roleSlug . '-' . str()->random(6) . '@example.test',
            'password' => Hash::make('Password#12345'),
            'role' => $roleSlug,
            'is_active' => true,
            'password_changed_at' => now(),
        ]);
    }
}
