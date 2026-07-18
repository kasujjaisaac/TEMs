<?php

namespace App\Http\Controllers\Finance;

use App\Models\Finance\FinanceAccount;
use App\Models\Finance\FinanceBudgetLine;
use App\Models\Finance\FinanceCostCentre;
use App\Services\Finance\FinanceControlService;
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
}
