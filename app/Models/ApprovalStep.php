<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['tenant_id', 'approval_request_id', 'sequence', 'approver_role', 'approver_user_id', 'status', 'decided_by', 'decided_at', 'decision_notes', 'metadata'])]
class ApprovalStep extends Model
{
    protected function casts(): array
    {
        return [
            'decided_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function approval(): BelongsTo
    {
        return $this->belongsTo(ApprovalRequest::class, 'approval_request_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approver_user_id');
    }

    public function decider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by');
    }
}
