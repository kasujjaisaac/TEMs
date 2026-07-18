<?php

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FinanceTransaction extends Model
{
    protected $fillable = [
        'tenant_id', 'fiscal_year_id', 'period_id', 'account_id', 'budget_line_id',
        'cost_centre_id', 'reference', 'source_module', 'source_type', 'source_id',
        'counterparty_type', 'counterparty_id', 'counterparty_name', 'direction',
        'amount', 'currency', 'transaction_date', 'due_date', 'status',
        'approval_status', 'evidence_status', 'description', 'source_snapshot', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'transaction_date' => 'date',
            'due_date' => 'date',
            'source_snapshot' => 'array',
        ];
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(FinanceAccount::class, 'account_id');
    }

    public function budgetLine(): BelongsTo
    {
        return $this->belongsTo(FinanceBudgetLine::class, 'budget_line_id');
    }

    public function costCentre(): BelongsTo
    {
        return $this->belongsTo(FinanceCostCentre::class, 'cost_centre_id');
    }
}
