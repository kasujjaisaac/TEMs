<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

abstract class FinanceController extends Controller
{
    protected function authorizeFinance(string $permission): void
    {
        abort_unless(Auth::user()?->hasPermission($permission), 403);
    }

    protected function tenantId(): int
    {
        return (int) Auth::user()?->tenant_id;
    }

    protected function ensureTenant(Model $model): void
    {
        abort_unless((int) $model->tenant_id === $this->tenantId(), 404);
    }
}
