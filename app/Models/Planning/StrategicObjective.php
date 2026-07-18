<?php

namespace App\Models\Planning;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class StrategicObjective extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'tenant_id', 'planning_year_id', 'strategic_pillar_id', 'code', 'title', 'description',
        'kpi', 'baseline_value', 'target_value', 'unit', 'weight', 'owner_name', 'status',
    ];

    protected function casts(): array
    {
        return [
            'baseline_value' => 'decimal:2',
            'target_value' => 'decimal:2',
        ];
    }

    public function planningYear(): BelongsTo
    {
        return $this->belongsTo(PlanningYear::class);
    }

    public function pillar(): BelongsTo
    {
        return $this->belongsTo(StrategicPillar::class, 'strategic_pillar_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(WorkplanItem::class);
    }
}
