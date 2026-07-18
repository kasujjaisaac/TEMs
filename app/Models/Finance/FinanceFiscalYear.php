<?php

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FinanceFiscalYear extends Model
{
    protected $fillable = ['tenant_id', 'name', 'starts_on', 'ends_on', 'status', 'is_current'];

    protected function casts(): array
    {
        return [
            'starts_on' => 'date',
            'ends_on' => 'date',
            'is_current' => 'boolean',
        ];
    }

    public function periods(): HasMany
    {
        return $this->hasMany(FinancePeriod::class, 'fiscal_year_id');
    }
}
