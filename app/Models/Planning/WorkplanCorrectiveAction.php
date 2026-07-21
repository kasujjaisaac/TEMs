<?php

namespace App\Models\Planning;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class WorkplanCorrectiveAction extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'tenant_id', 'workplan_item_id', 'owner_id', 'created_by', 'title',
        'root_cause', 'recovery_plan', 'due_on', 'status', 'severity', 'closed_at',
    ];

    protected function casts(): array
    {
        return [
            'due_on' => 'date',
            'closed_at' => 'datetime',
        ];
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(WorkplanItem::class, 'workplan_item_id');
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
