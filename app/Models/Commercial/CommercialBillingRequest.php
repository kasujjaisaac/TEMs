<?php

namespace App\Models\Commercial;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class CommercialBillingRequest extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'tenant_id', 'opportunity_id', 'contract_id', 'quotation_id', 'reference',
        'amount', 'currency', 'requested_invoice_date', 'billing_terms',
        'instructions', 'status', 'requested_by', 'approved_by', 'approved_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'requested_invoice_date' => 'date',
            'approved_at' => 'datetime',
        ];
    }

    public function opportunity(): BelongsTo
    {
        return $this->belongsTo(CommercialOpportunity::class, 'opportunity_id');
    }

    public function contract(): BelongsTo
    {
        return $this->belongsTo(CommercialContract::class, 'contract_id');
    }

    public function quotation(): BelongsTo
    {
        return $this->belongsTo(CommercialQuotation::class, 'quotation_id');
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }
}
