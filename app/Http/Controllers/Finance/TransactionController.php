<?php

namespace App\Http\Controllers\Finance;

use App\Models\Finance\FinanceTransaction;
use App\Services\Finance\FinanceControlService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TransactionController extends FinanceController
{
    public function index(Request $request, FinanceControlService $finance): View
    {
        $this->authorizeFinance('finance.transactions.view');
        $finance->syncOperatingActivity($this->tenantId());

        return view('finance.transactions.index', [
            'page_title' => 'Financial Transaction Register | Texaro Technologies Limited',
            'transactions' => FinanceTransaction::with(['account', 'budgetLine', 'costCentre'])
                ->where('tenant_id', $this->tenantId())
                ->when($request->string('direction')->toString(), fn ($query, $direction) => $query->where('direction', $direction))
                ->when($request->string('source_module')->toString(), fn ($query, $module) => $query->where('source_module', $module))
                ->latest('transaction_date')
                ->latest('id')
                ->paginate(20)
                ->withQueryString(),
        ]);
    }

    public function show(FinanceTransaction $transaction): View
    {
        $this->authorizeFinance('finance.transactions.view');
        $this->ensureTenant($transaction);

        return view('finance.transactions.show', [
            'page_title' => $transaction->reference . ' | Finance Transaction',
            'transaction' => $transaction->load(['account', 'budgetLine', 'costCentre']),
        ]);
    }
}
