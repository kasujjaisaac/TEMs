<?php

namespace App\Http\Controllers\Finance;

use App\Services\Finance\FinanceControlService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends FinanceController
{
    public function __invoke(FinanceControlService $finance): View
    {
        $this->authorizeFinance('finance.dashboard.view');

        return view('finance.dashboard', [
            'page_title' => 'Finance Control Centre | Texaro Technologies Limited',
            ...$finance->dashboard($this->tenantId()),
        ]);
    }

    public function sync(Request $request, FinanceControlService $finance): RedirectResponse
    {
        $this->authorizeFinance('finance.sync.manage');
        $result = $finance->syncOperatingActivity($this->tenantId());

        return redirect()->route('finance.dashboard')->with('success', 'Finance sync completed: ' . collect($result)->sum() . ' records checked.');
    }
}
