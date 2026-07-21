<?php

namespace App\Models\Commercial;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class CommercialProposal extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'tenant_id', 'opportunity_id', 'reference', 'title', 'scope_summary',
        'value_proposition', 'version', 'proposed_value', 'currency', 'status',
        'prepared_by', 'approved_by', 'approved_at',
    ];

    protected function casts(): array
    {
        return [
            'proposed_value' => 'decimal:2',
            'approved_at' => 'datetime',
        ];
    }

    public function opportunity(): BelongsTo
    {
        return $this->belongsTo(CommercialOpportunity::class, 'opportunity_id');
    }

    public function preparer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'prepared_by');
    }
}
