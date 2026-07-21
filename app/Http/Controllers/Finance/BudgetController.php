<?php

namespace App\Http\Controllers\Finance;

use App\Models\Finance\FinanceAccount;
use App\Models\Finance\FinanceBudgetLine;
use App\Models\Finance\FinanceCostCentre;
use App\Models\Commercial\CommercialBillingRequest;
use App\Services\Finance\FinanceControlService;
use App\Services\Finance\FinanceProcurementService;
use App\Services\Enterprise\EnterpriseOperatingControlService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class BudgetController extends FinanceController
{
    public function index(FinanceControlService $finance): View
    {
        $this->authorizeFinance('finance.budgets.view');
        $finance->bootstrapTenant($this->tenantId());

        return view('finance.budgets.index', [
            'page_title' => 'Budget Control | Texaro Technologies Limited',
            'budgetLines' => FinanceBudgetLine::with(['account', 'costCentre', 'fiscalYear'])
                ->where('tenant_id', $this->tenantId())
                ->latest()
                ->paginate(15),
            'accounts' => FinanceAccount::where('tenant_id', $this->tenantId())->where('type', 'Expense')->orderBy('code')->get(),
            'costCentres' => FinanceCostCentre::where('tenant_id', $this->tenantId())->orderBy('code')->get(),
            'fiscalYear' => $finance->currentFiscalYear($this->tenantId()),
            'expenses' => \DB::table('finance_expenses')->where('tenant_id', $this->tenantId())->latest()->limit(10)->get(),
            'purchaseRequests' => \DB::table('purchase_requests')->where('tenant_id', $this->tenantId())->latest()->limit(10)->get(),
            'purchaseOrders' => \DB::table('purchase_orders')->where('tenant_id', $this->tenantId())->latest()->limit(10)->get(),
            'supplierBills' => \DB::table('supplier_bills')->where('tenant_id', $this->tenantId())->latest()->limit(10)->get(),
            'payments' => \DB::table('finance_payments')->where('tenant_id', $this->tenantId())->latest()->limit(10)->get(),
            'assets' => \DB::table('asset_register')->where('tenant_id', $this->tenantId())->latest()->limit(10)->get(),
        ]);
    }

    public function store(Request $request, FinanceControlService $finance): RedirectResponse
    {
        $this->authorizeFinance('finance.budgets.manage');
        $fiscalYear = $finance->currentFiscalYear($this->tenantId());

        $data = $request->validate([
            'account_id' => ['required', 'integer', 'exists:finance_accounts,id'],
            'cost_centre_id' => ['nullable', 'integer', 'exists:finance_cost_centres,id'],
            'reference' => ['required', 'string', 'max:60'],
            'description' => ['required', 'string', 'max:220'],
            'workplan_objective' => ['nullable', 'string', 'max:220'],
            'annual_budget' => ['required', 'numeric', 'min:0'],
            'monthly_allocation' => ['nullable', 'numeric', 'min:0'],
            'owner_name' => ['nullable', 'string', 'max:160'],
            'approver_name' => ['nullable', 'string', 'max:160'],
            'status' => ['required', 'in:Draft,Submitted,Approved,Frozen,Closed'],
        ]);

        FinanceAccount::where('tenant_id', $this->tenantId())->findOrFail($data['account_id']);
        if (! empty($data['cost_centre_id'])) {
            FinanceCostCentre::where('tenant_id', $this->tenantId())->findOrFail($data['cost_centre_id']);
        }

        FinanceBudgetLine::create($data + [
            'tenant_id' => $this->tenantId(),
            'fiscal_year_id' => $fiscalYear->id,
            'created_by' => Auth::id(),
        ]);

        return redirect()->route('finance.budgets.index')->with('success', 'Budget line created successfully.');
    }

    public function storeExpense(Request $request, FinanceProcurementService $procurement): RedirectResponse
    {
        $this->authorizeFinance('finance.budgets.manage');
        $data = $request->validate([
            'budget_line_id' => ['nullable', 'integer'],
            'description' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'expense_date' => ['nullable', 'date'],
        ]);
        if (! empty($data['budget_line_id'])) {
            FinanceBudgetLine::where('tenant_id', $this->tenantId())->findOrFail($data['budget_line_id']);
        }
        $procurement->createExpense($this->tenantId(), $request->user(), $data);

        return back()->with('success', 'Expense submitted.');
    }

    public function storePurchaseRequest(Request $request, FinanceProcurementService $procurement): RedirectResponse
    {
        $this->authorizeFinance('finance.budgets.manage');
        $data = $request->validate([
            'budget_line_id' => ['nullable', 'integer'],
            'title' => ['required', 'string', 'max:255'],
            'justification' => ['nullable', 'string'],
            'estimated_amount' => ['nullable', 'numeric', 'min:0'],
        ]);
        $procurement->createPurchaseRequest($this->tenantId(), $request->user(), $data);

        return back()->with('success', 'Purchase request submitted.');
    }

    public function storePurchaseOrder(Request $request, FinanceProcurementService $procurement): RedirectResponse
    {
        $this->authorizeFinance('finance.budgets.manage');
        $data = $request->validate([
            'purchase_request_id' => ['nullable', 'integer'],
            'supplier_name' => ['required', 'string', 'max:255'],
            'total_amount' => ['required', 'numeric', 'min:0'],
        ]);
        $procurement->createPurchaseOrder($this->tenantId(), $request->user(), $data);

        return back()->with('success', 'Purchase order issued.');
    }

    public function storeSupplierBill(Request $request, FinanceProcurementService $procurement): RedirectResponse
    {
        $this->authorizeFinance('finance.budgets.manage');
        $data = $request->validate([
            'purchase_order_id' => ['nullable', 'integer'],
            'supplier_name' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'bill_date' => ['nullable', 'date'],
            'due_date' => ['nullable', 'date'],
        ]);
        $procurement->createSupplierBill($this->tenantId(), $request->user(), $data);

        return back()->with('success', 'Supplier bill recorded.');
    }

    public function storePayment(Request $request, FinanceProcurementService $procurement): RedirectResponse
    {
        $this->authorizeFinance('finance.budgets.manage');
        $data = $request->validate([
            'supplier_bill_id' => ['nullable', 'integer'],
            'payee_name' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'payment_date' => ['nullable', 'date'],
            'method' => ['nullable', 'string', 'max:80'],
        ]);
        $procurement->createPayment($this->tenantId(), $request->user(), $data);

        return back()->with('success', 'Payment recorded.');
    }

    public function storeAsset(Request $request, FinanceProcurementService $procurement): RedirectResponse
    {
        $this->authorizeFinance('finance.budgets.manage');
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:120'],
            'cost' => ['nullable', 'numeric', 'min:0'],
            'acquired_on' => ['nullable', 'date'],
        ]);
        $procurement->createAsset($this->tenantId(), $request->user(), $data);

        return back()->with('success', 'Asset registered.');
    }

    public function approvePurchaseRequest(Request $request, EnterpriseOperatingControlService $controls): RedirectResponse
    {
        $this->authorizeFinance('finance.budgets.manage');
        $data = $request->validate([
            'purchase_request_id' => ['required', 'integer'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $controls->approvePurchaseRequest($this->tenantId(), (int) $data['purchase_request_id'], $request->user(), $data['notes'] ?? null);

        return back()->with('success', 'Purchase request approved and finance review recorded.');
    }

    public function reviewBillingRequest(Request $request, EnterpriseOperatingControlService $controls): RedirectResponse
    {
        $this->authorizeFinance('finance.budgets.manage');
        $data = $request->validate([
            'billing_request_id' => ['required', 'integer'],
            'decision' => ['required', 'in:Approved,Rejected'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $billing = CommercialBillingRequest::where('tenant_id', $this->tenantId())->findOrFail($data['billing_request_id']);
        $controls->reviewBillingRequest($billing, $request->user(), $data['decision'], $data['notes'] ?? null);

        return back()->with('success', 'Billing request reviewed by Finance.');
    }
}
