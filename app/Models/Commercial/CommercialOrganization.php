<?php

namespace App\Models\Commercial;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class CommercialOrganization extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'tenant_id', 'legacy_customer_id', 'reference', 'legal_name', 'trading_name',
        'organization_type', 'customer_category', 'industry', 'sector', 'tin',
        'registration_number', 'primary_email', 'primary_telephone', 'alternative_telephone',
        'website', 'country', 'district', 'city', 'physical_address', 'postal_address',
        'number_of_branches', 'number_of_employees', 'customer_status', 'account_manager_id',
        'acquisition_source', 'relationship_status', 'relationship_score', 'credit_status',
        'payment_terms', 'notes', 'logo_path', 'created_by', 'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'relationship_score' => 'integer',
            'number_of_branches' => 'integer',
            'number_of_employees' => 'integer',
        ];
    }

    public function accountManager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'account_manager_id');
    }

    public function stakeholders(): HasMany
    {
        return $this->hasMany(CommercialStakeholder::class, 'organization_id');
    }

    public function opportunities(): HasMany
    {
        return $this->hasMany(CommercialOpportunity::class, 'organization_id');
    }

    public function salesHandoffs(): HasMany
    {
        return $this->hasMany(CommercialSalesHandoff::class, 'organization_id');
    }
}
