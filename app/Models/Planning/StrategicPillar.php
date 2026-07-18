<?php

namespace App\Models\Planning;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class StrategicPillar extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'tenant_id', 'planning_year_id', 'code', 'name', 'description', 'owner_name', 'weight', 'status',
    ];

    public function planningYear(): BelongsTo
    {
        return $this->belongsTo(PlanningYear::class);
    }

    public function objectives(): HasMany
    {
        return $this->hasMany(StrategicObjective::class);
    }
}
