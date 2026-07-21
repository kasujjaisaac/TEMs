<?php

namespace App\Models\Commercial;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class CommercialContract extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'tenant_id', 'opportunity_id', 'quotation_id', 'reference',
        'contract_title', 'contract_value', 'currency', 'starts_on', 'ends_on',
        'payment_terms', 'status', 'prepared_by', 'approved_by', 'approved_at',
        'signed_at',
    ];

    protected function casts(): array
    {
        return [
            'contract_value' => 'decimal:2',
            'starts_on' => 'date',
            'ends_on' => 'date',
            'approved_at' => 'datetime',
            'signed_at' => 'datetime',
        ];
    }

    public function opportunity(): BelongsTo
    {
        return $this->belongsTo(CommercialOpportunity::class, 'opportunity_id');
    }

    public function quotation(): BelongsTo
    {
        return $this->belongsTo(CommercialQuotation::class, 'quotation_id');
    }

    public function preparer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'prepared_by');
    }
}
