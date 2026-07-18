<?php

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FinanceCostCentre extends Model
{
    protected $fillable = [
        'tenant_id', 'department_id', 'code', 'name', 'type', 'owner_name', 'is_active',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function budgetLines(): HasMany
    {
        return $this->hasMany(FinanceBudgetLine::class, 'cost_centre_id');
    }
}
