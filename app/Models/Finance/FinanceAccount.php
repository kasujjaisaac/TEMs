<?php

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FinanceAccount extends Model
{
    protected $fillable = [
        'tenant_id', 'parent_id', 'code', 'name', 'type', 'normal_balance',
        'is_control_account', 'is_cash_account', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_control_account' => 'boolean',
            'is_cash_account' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(FinanceTransaction::class, 'account_id');
    }
}
