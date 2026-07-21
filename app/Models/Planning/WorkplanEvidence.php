<?php

namespace App\Models\Planning;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class WorkplanEvidence extends Model
{
    use SoftDeletes;

    protected $table = 'workplan_evidence';

    protected $fillable = [
        'tenant_id', 'workplan_item_id', 'submitted_by', 'title', 'evidence_type',
        'description', 'source_module', 'source_reference', 'claimed_value',
        'verified_value', 'status', 'submitted_at', 'reviewed_by', 'reviewed_at',
        'review_notes', 'metadata',
    ];

    protected function casts(): array
    {
        return [
            'claimed_value' => 'decimal:2',
            'verified_value' => 'decimal:2',
            'submitted_at' => 'datetime',
            'reviewed_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(WorkplanItem::class, 'workplan_item_id');
    }

    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(WorkplanEvidenceReview::class, 'workplan_evidence_id');
    }
}
