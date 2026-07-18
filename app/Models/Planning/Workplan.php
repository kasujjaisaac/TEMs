<?php

namespace App\Models\Planning;

use App\Models\HR\HrDepartment;
use App\Models\HR\HrPosition;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Workplan extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'tenant_id', 'planning_year_id', 'department_id', 'position_id', 'employee_id', 'code',
        'title', 'level', 'description', 'owner_name', 'approved_by', 'approved_at',
        'approval_status', 'health_status',
    ];

    protected function casts(): array
    {
        return [
            'approved_at' => 'datetime',
        ];
    }

    public function planningYear(): BelongsTo
    {
        return $this->belongsTo(PlanningYear::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(HrDepartment::class, 'department_id');
    }

    public function position(): BelongsTo
    {
        return $this->belongsTo(HrPosition::class, 'position_id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'employee_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(WorkplanItem::class);
    }
}
