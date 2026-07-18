<?php

namespace App\Models\Planning;

use App\Models\Finance\FinanceBudgetLine;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class WorkplanItem extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'tenant_id', 'workplan_id', 'strategic_objective_id', 'budget_line_id', 'reference',
        'title', 'description', 'target_type', 'kpi', 'baseline_value', 'target_value',
        'actual_value', 'unit', 'priority', 'weight', 'starts_on', 'due_on',
        'required_evidence_type', 'quality_standard', 'risk_summary', 'approval_status',
        'health_status', 'created_by', 'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'baseline_value' => 'decimal:2',
            'target_value' => 'decimal:2',
            'actual_value' => 'decimal:2',
            'starts_on' => 'date',
            'due_on' => 'date',
        ];
    }

    public function workplan(): BelongsTo
    {
        return $this->belongsTo(Workplan::class);
    }

    public function objective(): BelongsTo
    {
        return $this->belongsTo(StrategicObjective::class, 'strategic_objective_id');
    }

    public function budgetLine(): BelongsTo
    {
        return $this->belongsTo(FinanceBudgetLine::class, 'budget_line_id');
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(TargetAllocation::class);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(WorkplanAssignment::class);
    }

    public function getAchievementPercentageAttribute(): int
    {
        if ((float) $this->target_value <= 0) {
            return 0;
        }

        return (int) min(999, round(((float) $this->actual_value / (float) $this->target_value) * 100));
    }
}
