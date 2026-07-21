<?php

namespace App\Models\Planning;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlanningWorkplanImport extends Model
{
    protected $fillable = [
        'tenant_id', 'uploaded_by', 'planning_year_id', 'original_filename', 'status',
        'rows_read', 'workplans_created', 'targets_imported', 'errors', 'metadata',
        'imported_at',
    ];

    protected function casts(): array
    {
        return [
            'errors' => 'array',
            'metadata' => 'array',
            'imported_at' => 'datetime',
        ];
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function planningYear(): BelongsTo
    {
        return $this->belongsTo(PlanningYear::class, 'planning_year_id');
    }
}
