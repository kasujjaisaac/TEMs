<?php

namespace App\Models\HR;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class HrPosition extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'tenant_id', 'department_id', 'reports_to_position_id', 'code', 'title',
        'job_family', 'grade', 'level', 'employment_type', 'work_location',
        'job_purpose', 'key_responsibilities', 'standard_kpis', 'competencies',
        'decision_rights', 'approval_limit', 'approved_headcount',
        'filled_headcount', 'position_status', 'effective_from', 'effective_to',
        'version', 'created_by', 'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'approval_limit' => 'decimal:2',
            'effective_from' => 'date',
            'effective_to' => 'date',
        ];
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(HrDepartment::class, 'department_id');
    }

    public function reportsTo(): BelongsTo
    {
        return $this->belongsTo(self::class, 'reports_to_position_id');
    }

    public function directReports(): HasMany
    {
        return $this->hasMany(self::class, 'reports_to_position_id');
    }

    public function getVacancyCountAttribute(): int
    {
        return max(0, (int) $this->approved_headcount - (int) $this->filled_headcount);
    }
}
