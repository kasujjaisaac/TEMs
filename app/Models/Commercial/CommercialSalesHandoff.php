<?php

namespace App\Models\Commercial;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommercialSalesHandoff extends Model
{
    protected $fillable = [
        'tenant_id', 'opportunity_id', 'organization_id', 'legacy_customer_id',
        'quotation_id', 'invoice_id', 'status', 'handoff_value', 'currency',
        'sales_owner', 'handoff_summary', 'sales_instructions', 'created_by',
        'handed_off_at',
    ];

    protected function casts(): array
    {
        return [
            'handoff_value' => 'decimal:2',
            'handed_off_at' => 'datetime',
        ];
    }

    public function opportunity(): BelongsTo
    {
        return $this->belongsTo(CommercialOpportunity::class, 'opportunity_id');
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(CommercialOrganization::class, 'organization_id');
    }
}
