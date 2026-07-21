<?php

namespace App\Models\Planning;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkplanEvidenceReview extends Model
{
    protected $fillable = [
        'tenant_id', 'workplan_evidence_id', 'reviewed_by', 'decision',
        'verified_value', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'verified_value' => 'decimal:2',
        ];
    }

    public function evidence(): BelongsTo
    {
        return $this->belongsTo(WorkplanEvidence::class, 'workplan_evidence_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
