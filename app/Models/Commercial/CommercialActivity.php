<?php

namespace App\Models\Commercial;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class CommercialActivity extends Model
{
    protected $fillable = [
        'tenant_id', 'activity_type', 'related_type', 'related_id', 'owner_id',
        'assigned_employee_id', 'activity_date', 'activity_time', 'description',
        'outcome', 'next_action', 'next_action_date', 'completion_status', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'activity_date' => 'date',
            'next_action_date' => 'date',
        ];
    }

    public function related(): MorphTo
    {
        return $this->morphTo(__FUNCTION__, 'related_type', 'related_id');
    }
}
