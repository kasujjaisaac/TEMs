<?php

namespace App\Models\Commercial;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class CommercialQuotation extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'tenant_id', 'opportunity_id', 'proposal_id', 'legacy_invoice_id',
        'reference', 'quotation_date', 'valid_until', 'subtotal',
        'discount_amount', 'tax_amount', 'total', 'currency', 'status',
        'terms', 'prepared_by', 'approved_by', 'approved_at', 'accepted_at',
    ];

    protected function casts(): array
    {
        return [
            'quotation_date' => 'date',
            'valid_until' => 'date',
            'subtotal' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'total' => 'decimal:2',
            'approved_at' => 'datetime',
            'accepted_at' => 'datetime',
        ];
    }

    public function opportunity(): BelongsTo
    {
        return $this->belongsTo(CommercialOpportunity::class, 'opportunity_id');
    }

    public function proposal(): BelongsTo
    {
        return $this->belongsTo(CommercialProposal::class, 'proposal_id');
    }

    public function preparer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'prepared_by');
    }
}
