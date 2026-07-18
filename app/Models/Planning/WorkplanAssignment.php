<?php

namespace App\Models\Planning;

use App\Models\HR\HrDepartment;
use App\Models\HR\HrPosition;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkplanAssignment extends Model
{
    protected $fillable = [
        'tenant_id', 'workplan_item_id', 'department_id', 'position_id', 'employee_id',
        'supervisor_id', 'assignment_role', 'contribution_weight', 'status',
    ];

    public function item(): BelongsTo
    {
        return $this->belongsTo(WorkplanItem::class, 'workplan_item_id');
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
}
