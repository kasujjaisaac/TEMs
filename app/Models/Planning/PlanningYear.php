<?php

namespace App\Models\Planning;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class PlanningYear extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'tenant_id', 'name', 'starts_on', 'ends_on', 'annual_theme', 'status', 'is_current', 'scoring_rules',
    ];

    protected function casts(): array
    {
        return [
            'starts_on' => 'date',
            'ends_on' => 'date',
            'is_current' => 'boolean',
            'scoring_rules' => 'array',
        ];
    }

    public function pillars(): HasMany
    {
        return $this->hasMany(StrategicPillar::class);
    }

    public function objectives(): HasMany
    {
        return $this->hasMany(StrategicObjective::class);
    }

    public function workplans(): HasMany
    {
        return $this->hasMany(Workplan::class);
    }
}
