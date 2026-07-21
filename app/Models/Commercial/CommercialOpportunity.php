<?php

namespace App\Models\Commercial;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class CommercialOpportunity extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'tenant_id', 'campaign_id', 'organization_id', 'lead_id', 'primary_stakeholder_id',
        'pipeline_stage_id', 'reference', 'title', 'assigned_employee_id',
        'product_or_service', 'opportunity_type', 'opportunity_source',
        'current_stage', 'probability', 'estimated_value', 'currency',
        'expected_close_date', 'expected_start_date', 'contract_duration_months',
        'revenue_type', 'billing_frequency', 'customer_need', 'problem_statement',
        'proposed_solution', 'commercial_strategy', 'competitors',
        'competitive_position', 'decision_criteria', 'decision_process',
        'identified_risks', 'risk_level', 'next_action', 'next_action_date',
        'last_activity_date', 'lost_reason', 'won_at', 'lost_at',
        'sales_handoff_status', 'sales_handoff_at', 'legacy_quotation_id',
        'legacy_invoice_id', 'created_by', 'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'estimated_value' => 'decimal:2',
            'probability' => 'integer',
            'expected_close_date' => 'date',
            'expected_start_date' => 'date',
            'next_action_date' => 'date',
            'last_activity_date' => 'date',
            'won_at' => 'datetime',
            'lost_at' => 'datetime',
            'sales_handoff_at' => 'datetime',
        ];
    }

    public function getWeightedValueAttribute(): float
    {
        return round(((float) $this->estimated_value * (int) $this->probability) / 100, 2);
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(CommercialOrganization::class, 'organization_id');
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(CommercialCampaign::class, 'campaign_id');
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(CommercialLead::class, 'lead_id');
    }

    public function primaryStakeholder(): BelongsTo
    {
        return $this->belongsTo(CommercialStakeholder::class, 'primary_stakeholder_id');
    }

    public function assignedEmployee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_employee_id');
    }

    public function stageHistory(): HasMany
    {
        return $this->hasMany(CommercialOpportunityStageHistory::class, 'opportunity_id');
    }

    public function salesHandoff(): HasMany
    {
        return $this->hasMany(CommercialSalesHandoff::class, 'opportunity_id');
    }

    public function latestSalesHandoff(): HasOne
    {
        return $this->hasOne(CommercialSalesHandoff::class, 'opportunity_id')->latestOfMany();
    }

    public function proposals(): HasMany
    {
        return $this->hasMany(CommercialProposal::class, 'opportunity_id');
    }

    public function quotations(): HasMany
    {
        return $this->hasMany(CommercialQuotation::class, 'opportunity_id');
    }

    public function contracts(): HasMany
    {
        return $this->hasMany(CommercialContract::class, 'opportunity_id');
    }

    public function billingRequests(): HasMany
    {
        return $this->hasMany(CommercialBillingRequest::class, 'opportunity_id');
    }
}
