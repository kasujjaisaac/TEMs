<?php

namespace App\Models\Commercial;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class CommercialCampaign extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'tenant_id', 'reference', 'name', 'campaign_type', 'channel', 'objective',
        'target_audience', 'budget', 'actual_spend', 'starts_on', 'ends_on',
        'status', 'owner_id', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'budget' => 'decimal:2',
            'actual_spend' => 'decimal:2',
            'starts_on' => 'date',
            'ends_on' => 'date',
        ];
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function leads(): HasMany
    {
        return $this->hasMany(CommercialLead::class, 'campaign_id');
    }

    public function opportunities(): HasMany
    {
        return $this->hasMany(CommercialOpportunity::class, 'campaign_id');
    }
}
