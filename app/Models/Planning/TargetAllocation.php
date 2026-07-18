<?php

namespace App\Models\Planning;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TargetAllocation extends Model
{
    protected $fillable = [
        'tenant_id', 'workplan_item_id', 'period_type', 'period_start', 'period_end',
        'target_value', 'actual_value', 'status',
    ];

    protected function casts(): array
    {
        return [
            'period_start' => 'date',
            'period_end' => 'date',
            'target_value' => 'decimal:2',
            'actual_value' => 'decimal:2',
        ];
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(WorkplanItem::class, 'workplan_item_id');
    }
}
