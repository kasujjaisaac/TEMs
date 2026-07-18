<?php

namespace App\Models\Commercial;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class CommercialStakeholder extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'tenant_id', 'organization_id', 'full_name', 'position_title', 'department',
        'email', 'telephone', 'alternative_telephone', 'decision_role', 'influence_level',
        'interest_level', 'relationship_status', 'preferred_contact_method',
        'communication_preference', 'decision_authority', 'is_primary_contact',
        'is_billing_contact', 'is_technical_contact', 'is_contract_signatory',
        'notes', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'is_primary_contact' => 'boolean',
            'is_billing_contact' => 'boolean',
            'is_technical_contact' => 'boolean',
            'is_contract_signatory' => 'boolean',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(CommercialOrganization::class, 'organization_id');
    }
}
