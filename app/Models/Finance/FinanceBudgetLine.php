<?php

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FinanceBudgetLine extends Model
{
    protected $fillable = [
        'tenant_id', 'fiscal_year_id', 'period_id', 'account_id', 'cost_centre_id',
        'reference', 'description', 'workplan_objective', 'annual_budget',
        'monthly_allocation', 'committed_amount', 'actual_spent', 'forecast_amount',
        'owner_name', 'approver_name', 'status', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'annual_budget' => 'decimal:2',
            'monthly_allocation' => 'decimal:2',
            'committed_amount' => 'decimal:2',
            'actual_spent' => 'decimal:2',
            'forecast_amount' => 'decimal:2',
        ];
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(FinanceAccount::class, 'account_id');
    }

    public function costCentre(): BelongsTo
    {
        return $this->belongsTo(FinanceCostCentre::class, 'cost_centre_id');
    }

    public function fiscalYear(): BelongsTo
    {
        return $this->belongsTo(FinanceFiscalYear::class, 'fiscal_year_id');
    }

    public function getAvailableBalanceAttribute(): float
    {
        return round((float) $this->annual_budget - (float) $this->committed_amount - (float) $this->actual_spent, 2);
    }

    public function getUtilizationPercentageAttribute(): int
    {
        if ((float) $this->annual_budget <= 0) {
            return 0;
        }

        return (int) min(999, round((((float) $this->committed_amount + (float) $this->actual_spent) / (float) $this->annual_budget) * 100));
    }
}
