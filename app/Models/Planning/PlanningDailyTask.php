<?php

namespace App\Models\Planning;

use App\Models\HR\HrDepartment;
use App\Models\HR\HrPosition;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class PlanningDailyTask extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'tenant_id', 'workplan_item_id', 'target_allocation_id', 'corrective_action_id',
        'workplan_evidence_id', 'department_id', 'position_id', 'employee_id',
        'employee_profile_id', 'supervisor_id', 'source_module', 'source_type',
        'source_id', 'source_reference', 'title', 'description', 'expected_output',
        'priority', 'task_date', 'starts_at', 'due_at', 'status', 'progress_percent',
        'evidence_status', 'claimed_value', 'blocker_summary', 'completion_notes',
        'submitted_at', 'completed_at', 'reviewed_by', 'reviewed_at', 'review_decision',
        'review_notes', 'created_by', 'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'task_date' => 'date',
            'starts_at' => 'datetime',
            'due_at' => 'datetime',
            'submitted_at' => 'datetime',
            'completed_at' => 'datetime',
            'reviewed_at' => 'datetime',
            'claimed_value' => 'decimal:2',
        ];
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(WorkplanItem::class, 'workplan_item_id');
    }

    public function allocation(): BelongsTo
    {
        return $this->belongsTo(TargetAllocation::class, 'target_allocation_id');
    }

    public function correctiveAction(): BelongsTo
    {
        return $this->belongsTo(WorkplanCorrectiveAction::class, 'corrective_action_id');
    }

    public function evidence(): BelongsTo
    {
        return $this->belongsTo(WorkplanEvidence::class, 'workplan_evidence_id');
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

    public function supervisor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'supervisor_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
