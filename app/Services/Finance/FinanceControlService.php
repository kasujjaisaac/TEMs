<?php

namespace App\Services\Finance;

use App\Models\Finance\FinanceAccount;
use App\Models\Finance\FinanceBudgetLine;
use App\Models\Finance\FinanceCostCentre;
use App\Models\Finance\FinanceFiscalYear;
use App\Models\Finance\FinancePeriod;
use App\Models\Finance\FinanceTransaction;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class FinanceControlService
{
    public function bootstrapTenant(int $tenantId): void
    {
        $year = $this->currentFiscalYear($tenantId);
        $this->ensurePeriods($tenantId, $year);
        $this->ensureAccounts($tenantId);
        $this->ensureCostCentres($tenantId);
    }

    public function syncOperatingActivity(int $tenantId): array
    {
        $this->bootstrapTenant($tenantId);

        return [
            'sales_invoices' => $this->syncSalesInvoices($tenantId),
            'sales_receipts' => $this->syncSalesReceipts($tenantId),
            'purchases' => $this->syncPurchases($tenantId),
            'purchase_payments' => $this->syncPurchasePayments($tenantId),
            'inventory' => $this->syncInventoryActivity($tenantId),
            'payroll' => $this->syncPayroll($tenantId),
            'commercial_handoffs' => $this->syncCommercialHandoffs($tenantId),
        ];
    }

    public function dashboard(int $tenantId): array
    {
        $this->syncOperatingActivity($tenantId);

        $today = now()->toDateString();
        $monthStart = now()->startOfMonth()->toDateString();
        $monthEnd = now()->endOfMonth()->toDateString();

        $transactions = FinanceTransaction::where('tenant_id', $tenantId);
        $revenueMonth = (clone $transactions)->where('direction', 'Inflow')->whereBetween('transaction_date', [$monthStart, $monthEnd])->sum('amount');
        $expenseMonth = (clone $transactions)->where('direction', 'Outflow')->whereBetween('transaction_date', [$monthStart, $monthEnd])->sum('amount');
        $receivables = Schema::hasTable('invoices')
            ? (float) DB::table('invoices')
                ->where('tenant_id', $tenantId)
                ->where('invoice_type', 'invoice')
                ->whereNotIn('status', ['paid', 'cancelled'])
                ->sum('total')
            : 0.0;
        $payables = Schema::hasTable('purchases')
            ? (float) DB::table('purchases')->where('tenant_id', $tenantId)->whereNotIn('payment_status', ['paid'])->sum('total_amount')
            : 0.0;
        $overdueInvoices = Schema::hasTable('invoices')
            ? DB::table('invoices')
                ->where('tenant_id', $tenantId)
                ->where('invoice_type', 'invoice')
                ->whereNotIn('status', ['paid', 'cancelled'])
                ->whereDate('due_date', '<', $today)
                ->count()
            : 0;

        $budgetLines = FinanceBudgetLine::with(['account', 'costCentre'])
            ->where('tenant_id', $tenantId)
            ->whereIn('status', ['Approved', 'Frozen'])
            ->orderByDesc('updated_at')
            ->limit(8)
            ->get();

        return [
            'metrics' => [
                'revenue_month' => $revenueMonth,
                'expense_month' => $expenseMonth,
                'net_month' => $revenueMonth - $expenseMonth,
                'receivables' => $receivables,
                'payables' => $payables,
                'overdue_invoices' => $overdueInvoices,
                'budget_lines' => FinanceBudgetLine::where('tenant_id', $tenantId)->count(),
                'unclassified_transactions' => FinanceTransaction::where('tenant_id', $tenantId)->whereNull('account_id')->count(),
            ],
            'budgetLines' => $budgetLines,
            'recentTransactions' => FinanceTransaction::with(['account', 'budgetLine', 'costCentre'])
                ->where('tenant_id', $tenantId)
                ->latest('transaction_date')
                ->latest('id')
                ->limit(12)
                ->get(),
            'alerts' => $this->alerts($tenantId, $receivables, $payables, $overdueInvoices, $budgetLines),
        ];
    }

    public function currentFiscalYear(int $tenantId): FinanceFiscalYear
    {
        $now = now();
        $startYear = $now->month >= 7 ? $now->year : $now->year - 1;
        $name = $startYear . '/' . ($startYear + 1);

        return FinanceFiscalYear::updateOrCreate(
            ['tenant_id' => $tenantId, 'name' => $name],
            [
                'starts_on' => Carbon::create($startYear, 7, 1)->toDateString(),
                'ends_on' => Carbon::create($startYear + 1, 6, 30)->toDateString(),
                'status' => 'Open',
                'is_current' => true,
            ]
        );
    }

    public function periodForDate(int $tenantId, string|Carbon|null $date): ?FinancePeriod
    {
        $date = $date ? Carbon::parse($date) : now();
        $year = $this->currentFiscalYear($tenantId);

        return FinancePeriod::where('tenant_id', $tenantId)
            ->where('fiscal_year_id', $year->id)
            ->whereDate('starts_on', '<=', $date->toDateString())
            ->whereDate('ends_on', '>=', $date->toDateString())
            ->first();
    }

    private function ensurePeriods(int $tenantId, FinanceFiscalYear $year): void
    {
        $cursor = $year->starts_on->copy();
        for ($i = 1; $i <= 12; $i++) {
            FinancePeriod::updateOrCreate(
                ['tenant_id' => $tenantId, 'fiscal_year_id' => $year->id, 'period_number' => $i],
                [
                    'name' => $cursor->format('M Y'),
                    'starts_on' => $cursor->copy()->startOfMonth()->toDateString(),
                    'ends_on' => $cursor->copy()->endOfMonth()->toDateString(),
                    'status' => 'Open',
                ]
            );
            $cursor->addMonth();
        }
    }

    private function ensureAccounts(int $tenantId): void
    {
        foreach ([
            ['1000', 'Cash and Bank', 'Asset', 'Debit', true, true],
            ['1100', 'Accounts Receivable', 'Asset', 'Debit', true, false],
            ['1200', 'Inventory Asset', 'Asset', 'Debit', true, false],
            ['1500', 'Fixed Assets', 'Asset', 'Debit', true, false],
            ['2000', 'Accounts Payable', 'Liability', 'Credit', true, false],
            ['2100', 'Tax and Statutory Payables', 'Liability', 'Credit', true, false],
            ['3000', 'Equity and Retained Earnings', 'Equity', 'Credit', true, false],
            ['4000', 'Sales Revenue', 'Revenue', 'Credit', true, false],
            ['4100', 'Subscription Revenue', 'Revenue', 'Credit', true, false],
            ['5000', 'Cost of Sales', 'Expense', 'Debit', true, false],
            ['6000', 'Operating Expenses', 'Expense', 'Debit', true, false],
            ['6100', 'Payroll Expenses', 'Expense', 'Debit', true, false],
            ['6200', 'Marketing Expenses', 'Expense', 'Debit', false, false],
            ['6300', 'Infrastructure Expenses', 'Expense', 'Debit', false, false],
        ] as [$code, $name, $type, $balance, $control, $cash]) {
            FinanceAccount::updateOrCreate(
                ['tenant_id' => $tenantId, 'code' => $code],
                ['name' => $name, 'type' => $type, 'normal_balance' => $balance, 'is_control_account' => $control, 'is_cash_account' => $cash, 'is_active' => true]
            );
        }
    }

    private function ensureCostCentres(int $tenantId): void
    {
        if (Schema::hasTable('hr_departments')) {
            DB::table('hr_departments')->where('tenant_id', $tenantId)->orderBy('code')->get()->each(function (object $department) use ($tenantId): void {
                FinanceCostCentre::updateOrCreate(
                    ['tenant_id' => $tenantId, 'code' => $department->code],
                    ['department_id' => $department->id, 'name' => $department->name, 'type' => $department->type ?: 'Department', 'is_active' => true]
                );
            });
        }

        FinanceCostCentre::updateOrCreate(
            ['tenant_id' => $tenantId, 'code' => 'GENERAL'],
            ['name' => 'General Company Control', 'type' => 'Corporate', 'is_active' => true]
        );
    }

    private function syncSalesInvoices(int $tenantId): int
    {
        if (! Schema::hasTable('invoices')) {
            return 0;
        }

        $account = $this->account($tenantId, '4000');
        $count = 0;
        $hasCustomers = Schema::hasTable('customers');

        DB::table('invoices')
            ->when($hasCustomers, function ($query): void {
                $query->leftJoin('customers', function ($join): void {
                    $join->on('customers.id', '=', 'invoices.customer_id')->on('customers.tenant_id', '=', 'invoices.tenant_id');
                });
            })
            ->where('invoices.tenant_id', $tenantId)
            ->whereIn('invoices.invoice_type', ['invoice', 'quotation', 'credit_note'])
            ->select(array_merge(
                ['invoices.*'],
                $hasCustomers
                    ? ['customers.name as customer_name', 'customers.company_name']
                    : [DB::raw('NULL as customer_name'), DB::raw('NULL as company_name')]
            ))
            ->orderBy('invoices.id')
            ->get()
            ->each(function (object $invoice) use ($tenantId, $account, &$count): void {
                $period = $this->periodForDate($tenantId, $invoice->invoice_date);
                $direction = $invoice->invoice_type === 'credit_note' ? 'Outflow' : 'Inflow';
                FinanceTransaction::updateOrCreate(
                    ['tenant_id' => $tenantId, 'source_module' => 'Sales', 'source_type' => $invoice->invoice_type, 'source_id' => $invoice->id],
                    [
                        'fiscal_year_id' => $period?->fiscal_year_id,
                        'period_id' => $period?->id,
                        'account_id' => $account?->id,
                        'reference' => $invoice->invoice_number ?: 'INV-' . $invoice->id,
                        'counterparty_type' => 'Customer',
                        'counterparty_id' => $invoice->customer_id,
                        'counterparty_name' => $invoice->company_name ?: $invoice->customer_name,
                        'direction' => $direction,
                        'amount' => (float) $invoice->total,
                        'transaction_date' => $invoice->invoice_date ?: now()->toDateString(),
                        'due_date' => $invoice->due_date,
                        'status' => $invoice->status ?: 'draft',
                        'approval_status' => $invoice->status === 'approved' ? 'Approved' : 'Pending Review',
                        'evidence_status' => $invoice->invoice_type === 'quotation' ? 'Commercial Draft' : 'Documented',
                        'description' => 'Sales ' . $invoice->invoice_type . ' synced into Finance.',
                        'source_snapshot' => (array) $invoice,
                    ]
                );
                $count++;
            });

        return $count;
    }

    private function syncSalesReceipts(int $tenantId): int
    {
        if (! Schema::hasTable('invoice_payments') || ! Schema::hasTable('invoices')) {
            return 0;
        }

        $account = $this->account($tenantId, '1000');
        $count = 0;
        $hasCustomers = Schema::hasTable('customers');

        DB::table('invoice_payments')
            ->join('invoices', function ($join): void {
                $join->on('invoices.id', '=', 'invoice_payments.invoice_id')->on('invoices.tenant_id', '=', 'invoice_payments.tenant_id');
            })
            ->when($hasCustomers, function ($query): void {
                $query->leftJoin('customers', function ($join): void {
                    $join->on('customers.id', '=', 'invoices.customer_id')->on('customers.tenant_id', '=', 'invoices.tenant_id');
                });
            })
            ->where('invoice_payments.tenant_id', $tenantId)
            ->select(array_merge(
                ['invoice_payments.*', 'invoices.invoice_number'],
                $hasCustomers
                    ? ['customers.name as customer_name', 'customers.company_name']
                    : [DB::raw('NULL as customer_name'), DB::raw('NULL as company_name')]
            ))
            ->get()
            ->each(function (object $payment) use ($tenantId, $account, &$count): void {
                $period = $this->periodForDate($tenantId, $payment->payment_date);
                FinanceTransaction::updateOrCreate(
                    ['tenant_id' => $tenantId, 'source_module' => 'Sales', 'source_type' => 'receipt', 'source_id' => $payment->id],
                    [
                        'fiscal_year_id' => $period?->fiscal_year_id,
                        'period_id' => $period?->id,
                        'account_id' => $account?->id,
                        'reference' => $payment->reference ?: 'RCPT-' . $payment->id,
                        'counterparty_type' => 'Customer',
                        'counterparty_name' => $payment->company_name ?: $payment->customer_name,
                        'direction' => 'Inflow',
                        'amount' => (float) $payment->amount,
                        'transaction_date' => $payment->payment_date ?: now()->toDateString(),
                        'status' => 'Received',
                        'approval_status' => 'Not Required',
                        'evidence_status' => $payment->reference ? 'Documented' : 'Reference Missing',
                        'description' => 'Receipt for ' . $payment->invoice_number,
                        'source_snapshot' => (array) $payment,
                    ]
                );
                $count++;
            });

        return $count;
    }

    private function syncPurchases(int $tenantId): int
    {
        if (! Schema::hasTable('purchases')) {
            return 0;
        }

        $account = $this->account($tenantId, '6000');
        $count = 0;
        DB::table('purchases')
            ->where('tenant_id', $tenantId)
            ->get()
            ->each(function (object $purchase) use ($tenantId, $account, &$count): void {
                $period = $this->periodForDate($tenantId, $purchase->purchase_date);
                FinanceTransaction::updateOrCreate(
                    ['tenant_id' => $tenantId, 'source_module' => 'Procurement', 'source_type' => 'purchase', 'source_id' => $purchase->id],
                    [
                        'fiscal_year_id' => $period?->fiscal_year_id,
                        'period_id' => $period?->id,
                        'account_id' => $account?->id,
                        'reference' => $purchase->purchase_number ?: 'PUR-' . $purchase->id,
                        'counterparty_type' => 'Supplier',
                        'counterparty_id' => $purchase->supplier_id,
                        'counterparty_name' => $purchase->supplier,
                        'direction' => 'Outflow',
                        'amount' => (float) $purchase->total_amount,
                        'transaction_date' => $purchase->purchase_date ?: now()->toDateString(),
                        'due_date' => $purchase->due_date,
                        'status' => $purchase->status ?: 'draft',
                        'approval_status' => in_array($purchase->status, ['approved', 'received', 'closed'], true) ? 'Approved' : 'Pending Review',
                        'evidence_status' => $purchase->invoice_number ? 'Documented' : 'Supplier Invoice Missing',
                        'description' => 'Purchase commitment synced into Finance.',
                        'source_snapshot' => (array) $purchase,
                    ]
                );
                $count++;
            });

        return $count;
    }

    private function syncPurchasePayments(int $tenantId): int
    {
        if (! Schema::hasTable('purchase_payments')) {
            return 0;
        }

        $account = $this->account($tenantId, '1000');
        $count = 0;
        DB::table('purchase_payments')
            ->leftJoin('purchases', function ($join): void {
                $join->on('purchases.id', '=', 'purchase_payments.purchase_id')->on('purchases.tenant_id', '=', 'purchase_payments.tenant_id');
            })
            ->where('purchase_payments.tenant_id', $tenantId)
            ->select('purchase_payments.*', 'purchases.purchase_number', 'purchases.supplier')
            ->get()
            ->each(function (object $payment) use ($tenantId, $account, &$count): void {
                $period = $this->periodForDate($tenantId, $payment->payment_date);
                FinanceTransaction::updateOrCreate(
                    ['tenant_id' => $tenantId, 'source_module' => 'Procurement', 'source_type' => 'payment', 'source_id' => $payment->id],
                    [
                        'fiscal_year_id' => $period?->fiscal_year_id,
                        'period_id' => $period?->id,
                        'account_id' => $account?->id,
                        'reference' => $payment->reference ?: 'PAY-' . $payment->id,
                        'counterparty_type' => 'Supplier',
                        'counterparty_id' => $payment->supplier_id,
                        'counterparty_name' => $payment->supplier,
                        'direction' => 'Outflow',
                        'amount' => (float) $payment->amount,
                        'transaction_date' => $payment->payment_date ?: now()->toDateString(),
                        'status' => 'Paid',
                        'approval_status' => 'Approved',
                        'evidence_status' => $payment->reference ? 'Documented' : 'Reference Missing',
                        'description' => 'Payment for ' . $payment->purchase_number,
                        'source_snapshot' => (array) $payment,
                    ]
                );
                $count++;
            });

        return $count;
    }

    private function syncPayroll(int $tenantId): int
    {
        if (! Schema::hasTable('hr_employees')) {
            return 0;
        }

        $account = $this->account($tenantId, '6100');
        $period = $this->periodForDate($tenantId, now());
        $activeEmployees = DB::table('hr_employees')->where('tenant_id', $tenantId)->where('status', 'Active')->count();
        if ($activeEmployees <= 0) {
            return 0;
        }

        FinanceTransaction::updateOrCreate(
            ['tenant_id' => $tenantId, 'source_module' => 'HR', 'source_type' => 'payroll_readiness', 'source_id' => $period?->id ?: 0],
            [
                'fiscal_year_id' => $period?->fiscal_year_id,
                'period_id' => $period?->id,
                'account_id' => $account?->id,
                'reference' => 'PAYROLL-' . now()->format('Ym'),
                'counterparty_type' => 'Employees',
                'counterparty_name' => 'Active payroll population',
                'direction' => 'Outflow',
                'amount' => 0,
                'transaction_date' => now()->toDateString(),
                'status' => 'Payroll Inputs Pending',
                'approval_status' => 'HR Review Required',
                'evidence_status' => 'Payroll Schedule Required',
                'description' => $activeEmployees . ' active employees require payroll validation.',
            ]
        );

        return 1;
    }

    private function syncInventoryActivity(int $tenantId): int
    {
        if (! Schema::hasTable('inventory_transactions')) {
            return 0;
        }

        $account = $this->account($tenantId, '1200');
        $count = 0;
        DB::table('inventory_transactions')
            ->leftJoin('products', function ($join): void {
                $join->on('products.id', '=', 'inventory_transactions.product_id')->on('products.tenant_id', '=', 'inventory_transactions.tenant_id');
            })
            ->where('inventory_transactions.tenant_id', $tenantId)
            ->select('inventory_transactions.*', 'products.name as product_name')
            ->get()
            ->each(function (object $movement) use ($tenantId, $account, &$count): void {
                $date = $movement->created_at ?: now();
                $period = $this->periodForDate($tenantId, $date);
                $direction = in_array($movement->transaction_type, ['received', 'adjustment_in', 'returned'], true) ? 'Inflow' : 'Outflow';
                FinanceTransaction::updateOrCreate(
                    ['tenant_id' => $tenantId, 'source_module' => 'Inventory', 'source_type' => $movement->transaction_type ?: 'movement', 'source_id' => $movement->id],
                    [
                        'fiscal_year_id' => $period?->fiscal_year_id,
                        'period_id' => $period?->id,
                        'account_id' => $account?->id,
                        'reference' => $movement->reference ?: 'INV-MOVE-' . $movement->id,
                        'counterparty_type' => 'Product',
                        'counterparty_id' => $movement->product_id,
                        'counterparty_name' => $movement->product_name,
                        'direction' => $direction,
                        'amount' => 0,
                        'transaction_date' => Carbon::parse($date)->toDateString(),
                        'status' => 'Stock Movement',
                        'approval_status' => 'Operational Control',
                        'evidence_status' => $movement->reference ? 'Reference Captured' : 'Reference Missing',
                        'description' => 'Inventory movement quantity ' . ($movement->quantity ?? 0) . '. ' . ($movement->notes ?? ''),
                        'source_snapshot' => (array) $movement,
                    ]
                );
                $count++;
            });

        return $count;
    }

    private function syncCommercialHandoffs(int $tenantId): int
    {
        if (! Schema::hasTable('commercial_sales_handoffs')) {
            return 0;
        }

        $account = $this->account($tenantId, '4000');
        $count = 0;
        DB::table('commercial_sales_handoffs')
            ->leftJoin('commercial_opportunities', 'commercial_opportunities.id', '=', 'commercial_sales_handoffs.opportunity_id')
            ->leftJoin('commercial_organizations', 'commercial_organizations.id', '=', 'commercial_sales_handoffs.organization_id')
            ->where('commercial_sales_handoffs.tenant_id', $tenantId)
            ->select('commercial_sales_handoffs.*', 'commercial_opportunities.reference as opportunity_reference', 'commercial_opportunities.title', 'commercial_organizations.legal_name')
            ->get()
            ->each(function (object $handoff) use ($tenantId, $account, &$count): void {
                $period = $this->periodForDate($tenantId, $handoff->handed_off_at ?: now());
                FinanceTransaction::updateOrCreate(
                    ['tenant_id' => $tenantId, 'source_module' => 'Commercial', 'source_type' => 'sales_handoff', 'source_id' => $handoff->id],
                    [
                        'fiscal_year_id' => $period?->fiscal_year_id,
                        'period_id' => $period?->id,
                        'account_id' => $account?->id,
                        'reference' => $handoff->opportunity_reference ?: 'HANDOFF-' . $handoff->id,
                        'counterparty_type' => 'Customer',
                        'counterparty_id' => $handoff->legacy_customer_id,
                        'counterparty_name' => $handoff->legal_name,
                        'direction' => 'Inflow',
                        'amount' => (float) $handoff->handoff_value,
                        'transaction_date' => Carbon::parse($handoff->handed_off_at ?: now())->toDateString(),
                        'status' => $handoff->status,
                        'approval_status' => 'Commercial Approved',
                        'evidence_status' => $handoff->quotation_id ? 'Quotation Drafted' : 'Quotation Missing',
                        'description' => 'Commercial opportunity handed to Sales.',
                        'source_snapshot' => (array) $handoff,
                    ]
                );
                $count++;
            });

        return $count;
    }

    private function account(int $tenantId, string $code): ?FinanceAccount
    {
        return FinanceAccount::where('tenant_id', $tenantId)->where('code', $code)->first();
    }

    private function alerts(int $tenantId, float $receivables, float $payables, int $overdueInvoices, mixed $budgetLines): array
    {
        $alerts = [];
        if ($overdueInvoices > 0) {
            $alerts[] = ['severity' => 'High', 'title' => 'Overdue receivables', 'message' => $overdueInvoices . ' invoices are overdue and require collection action.'];
        }
        if ($payables > $receivables && $payables > 0) {
            $alerts[] = ['severity' => 'Medium', 'title' => 'Payables pressure', 'message' => 'Open supplier obligations exceed open receivables. Review cash timing before approving payments.'];
        }
        foreach ($budgetLines as $line) {
            if ($line->utilization_percentage >= 80) {
                $alerts[] = ['severity' => 'Medium', 'title' => 'Budget line near limit', 'message' => $line->reference . ' has used or committed ' . $line->utilization_percentage . '% of its approved budget.'];
            }
        }
        if (FinanceTransaction::where('tenant_id', $tenantId)->where('evidence_status', 'like', '%Missing%')->exists()) {
            $alerts[] = ['severity' => 'Medium', 'title' => 'Missing evidence', 'message' => 'Some financial transactions are missing references, invoices or supporting evidence.'];
        }

        return $alerts;
    }
}
