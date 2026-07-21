<?php

namespace App\Models\Commercial;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class CommercialLead extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'tenant_id', 'campaign_id', 'organization_id', 'stakeholder_id', 'opportunity_id', 'reference',
        'organization_name', 'contact_person', 'telephone', 'email', 'location',
        'district', 'country', 'industry', 'sector', 'customer_type', 'lead_source',
        'source_campaign', 'interested_product', 'interested_service', 'estimated_budget',
        'expected_decision_date', 'description', 'pain_points', 'requirements_summary',
        'assigned_employee_id', 'assigned_department', 'temperature', 'lead_score',
        'status', 'qualification_status', 'next_action', 'next_follow_up_date',
        'last_contacted_date', 'created_by', 'updated_by', 'converted_at',
    ];

    protected function casts(): array
    {
        return [
            'estimated_budget' => 'decimal:2',
            'expected_decision_date' => 'date',
            'next_follow_up_date' => 'date',
            'last_contacted_date' => 'date',
            'converted_at' => 'datetime',
        ];
    }

    public function assignedEmployee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_employee_id');
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(CommercialCampaign::class, 'campaign_id');
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(CommercialOrganization::class, 'organization_id');
    }

    public function stakeholder(): BelongsTo
    {
        return $this->belongsTo(CommercialStakeholder::class, 'stakeholder_id');
    }

    public function opportunity(): BelongsTo
    {
        return $this->belongsTo(CommercialOpportunity::class, 'opportunity_id');
    }
}
