<?php

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FinancePeriod extends Model
{
    protected $fillable = [
        'tenant_id', 'fiscal_year_id', 'name', 'period_number', 'starts_on', 'ends_on', 'status',
    ];

    protected function casts(): array
    {
        return [
            'starts_on' => 'date',
            'ends_on' => 'date',
        ];
    }

    public function fiscalYear(): BelongsTo
    {
        return $this->belongsTo(FinanceFiscalYear::class, 'fiscal_year_id');
    }
}
