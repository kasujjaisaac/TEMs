<?php

namespace App\Models\Commercial;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommercialOpportunityStageHistory extends Model
{
    protected $table = 'commercial_opportunity_stage_history';

    protected $fillable = [
        'tenant_id', 'opportunity_id', 'previous_stage', 'new_stage', 'changed_by',
        'reason', 'notes',
    ];

    public function opportunity(): BelongsTo
    {
        return $this->belongsTo(CommercialOpportunity::class, 'opportunity_id');
    }
}
