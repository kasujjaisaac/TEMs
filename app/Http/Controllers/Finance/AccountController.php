<?php

namespace App\Http\Controllers\Finance;

use App\Models\Finance\FinanceAccount;
use App\Services\Finance\FinanceControlService;
use Illuminate\View\View;

class AccountController extends FinanceController
{
    public function index(FinanceControlService $finance): View
    {
        $this->authorizeFinance('finance.accounts.view');
        $finance->bootstrapTenant($this->tenantId());

        return view('finance.accounts.index', [
            'page_title' => 'Chart of Accounts | Texaro Technologies Limited',
            'accounts' => FinanceAccount::where('tenant_id', $this->tenantId())->orderBy('code')->get(),
        ]);
    }
}
