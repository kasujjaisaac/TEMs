<?php

namespace App\Services\Finance;

use App\Models\Finance\FinanceTransaction;
use App\Models\User;
use App\Services\Enterprise\DomainEventService;
use App\Services\Enterprise\FoundationCompletionService;
use Illuminate\Support\Facades\DB;

class FinanceProcurementService
{
    public function __construct(private readonly FoundationCompletionService $numbers, private readonly FinanceControlService $finance)
    {
    }

    public function createExpense(int $tenantId, User $user, array $data): int
    {
        return DB::transaction(function () use ($tenantId, $user, $data): int {
            $id = DB::table('finance_expenses')->insertGetId([
                'tenant_id' => $tenantId,
                'budget_line_id' => $data['budget_line_id'] ?? null,
                'reference' => $this->numbers->nextDocumentNumber($tenantId, 'expense', 'EXP'),
                'description' => $data['description'],
                'amount' => $data['amount'],
                'currency' => 'UGX',
                'expense_date' => $data['expense_date'] ?? now()->toDateString(),
                'status' => 'Submitted',
                'requested_by' => $user->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $this->transaction($tenantId, 'Finance', 'expense', $id, $data['description'], (float) $data['amount'], 'Outflow', $data['budget_line_id'] ?? null, $user);
            app(DomainEventService::class)->record('expense.submitted', 'Finance', null, ['expense_id' => $id], $tenantId, $user);

            return $id;
        });
    }

    public function createPurchaseRequest(int $tenantId, User $user, array $data): int
    {
        return DB::table('purchase_requests')->insertGetId([
            'tenant_id' => $tenantId,
            'budget_line_id' => $data['budget_line_id'] ?? null,
            'reference' => $this->numbers->nextDocumentNumber($tenantId, 'purchase_request', 'PR'),
            'title' => $data['title'],
            'justification' => $data['justification'] ?? null,
            'estimated_amount' => $data['estimated_amount'] ?? 0,
            'status' => 'Submitted',
            'requested_by' => $user->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function createPurchaseOrder(int $tenantId, User $user, array $data): int
    {
        return DB::transaction(function () use ($tenantId, $user, $data): int {
            $id = DB::table('purchase_orders')->insertGetId([
                'tenant_id' => $tenantId,
                'purchase_request_id' => $data['purchase_request_id'] ?? null,
                'supplier_id' => $data['supplier_id'] ?? null,
                'reference' => $this->numbers->nextDocumentNumber($tenantId, 'purchase_order', 'PO'),
                'supplier_name' => $data['supplier_name'] ?? null,
                'total_amount' => $data['total_amount'] ?? 0,
                'status' => 'Issued',
                'issued_on' => now()->toDateString(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            app(DomainEventService::class)->record('purchase_order.issued', 'Procurement', null, ['purchase_order_id' => $id], $tenantId, $user);

            return $id;
        });
    }

    public function createSupplierBill(int $tenantId, User $user, array $data): int
    {
        $id = DB::table('supplier_bills')->insertGetId([
            'tenant_id' => $tenantId,
            'purchase_order_id' => $data['purchase_order_id'] ?? null,
            'reference' => $this->numbers->nextDocumentNumber($tenantId, 'supplier_bill', 'BILL'),
            'supplier_name' => $data['supplier_name'],
            'amount' => $data['amount'],
            'bill_date' => $data['bill_date'] ?? now()->toDateString(),
            'due_date' => $data['due_date'] ?? now()->addDays(14)->toDateString(),
            'status' => 'Unpaid',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $this->transaction($tenantId, 'Procurement', 'supplier_bill', $id, 'Supplier bill: ' . $data['supplier_name'], (float) $data['amount'], 'Outflow', null, $user);

        return $id;
    }

    public function createPayment(int $tenantId, User $user, array $data): int
    {
        return DB::transaction(function () use ($tenantId, $user, $data): int {
            $id = DB::table('finance_payments')->insertGetId([
                'tenant_id' => $tenantId,
                'supplier_bill_id' => $data['supplier_bill_id'] ?? null,
                'reference' => $this->numbers->nextDocumentNumber($tenantId, 'payment', 'PAY'),
                'payee_name' => $data['payee_name'],
                'amount' => $data['amount'],
                'payment_date' => $data['payment_date'] ?? now()->toDateString(),
                'method' => $data['method'] ?? null,
                'status' => 'Paid',
                'paid_by' => $user->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $this->transaction($tenantId, 'Finance', 'payment', $id, 'Payment to ' . $data['payee_name'], (float) $data['amount'], 'Outflow', null, $user);
            app(DomainEventService::class)->record('payment.completed', 'Finance', null, ['payment_id' => $id], $tenantId, $user);

            return $id;
        });
    }

    public function createAsset(int $tenantId, User $user, array $data): int
    {
        $id = DB::table('asset_register')->insertGetId([
            'tenant_id' => $tenantId,
            'reference' => $this->numbers->nextDocumentNumber($tenantId, 'asset', 'AST'),
            'name' => $data['name'],
            'category' => $data['category'] ?? null,
            'cost' => $data['cost'] ?? 0,
            'acquired_on' => $data['acquired_on'] ?? now()->toDateString(),
            'assigned_to' => $data['assigned_to'] ?? null,
            'status' => 'In Use',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        app(DomainEventService::class)->record('asset.registered', 'Finance', null, ['asset_id' => $id], $tenantId, $user);

        return $id;
    }

    private function transaction(int $tenantId, string $module, string $type, int $id, string $description, float $amount, string $direction, ?int $budgetLineId, User $user): void
    {
        $this->finance->bootstrapTenant($tenantId);
        $period = $this->finance->periodForDate($tenantId, now());
        FinanceTransaction::updateOrCreate(
            ['tenant_id' => $tenantId, 'source_module' => $module, 'source_type' => $type, 'source_id' => $id],
            [
                'fiscal_year_id' => $period?->fiscal_year_id,
                'period_id' => $period?->id,
                'budget_line_id' => $budgetLineId,
                'reference' => strtoupper($type) . '-' . $id,
                'direction' => $direction,
                'amount' => $amount,
                'currency' => 'UGX',
                'transaction_date' => now()->toDateString(),
                'status' => 'Recorded',
                'approval_status' => 'Pending Review',
                'evidence_status' => 'Documented',
                'description' => $description,
                'created_by' => $user->id,
            ]
        );
    }
}
