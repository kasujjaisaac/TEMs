<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['tenant_id', 'module', 'request_type', 'subject_type', 'subject_id', 'title', 'summary', 'status', 'priority', 'requested_by', 'reviewed_by', 'requested_at', 'reviewed_at', 'decision_notes', 'metadata'])]
class ApprovalRequest extends Model
{
    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'requested_at' => 'datetime',
            'reviewed_at' => 'datetime',
        ];
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
