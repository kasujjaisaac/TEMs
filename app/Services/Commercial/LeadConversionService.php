<?php

namespace App\Services\Commercial;

use App\Models\Commercial\CommercialLead;
use App\Models\Commercial\CommercialOpportunity;
use App\Models\Commercial\CommercialOpportunityStageHistory;
use App\Models\Commercial\CommercialOrganization;
use App\Models\Commercial\CommercialPipelineStage;
use App\Models\Commercial\CommercialStakeholder;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class LeadConversionService
{
    public function __construct(private readonly CommercialNumberingService $numbering)
    {
    }

    /**
     * @return array{organization: CommercialOrganization, stakeholder: CommercialStakeholder|null, opportunity: CommercialOpportunity}
     */
    public function convert(CommercialLead $lead, User $user, array $data = []): array
    {
        if ($lead->converted_at) {
            abort(422, 'This lead has already been converted.');
        }

        return DB::transaction(function () use ($lead, $user, $data): array {
            $tenantId = (int) $lead->tenant_id;
            $stage = CommercialPipelineStage::where('tenant_id', $tenantId)
                ->where('is_active', true)
                ->where('name', 'Qualified')
                ->first()
                ?: CommercialPipelineStage::where('tenant_id', $tenantId)
                    ->where('is_active', true)
                    ->orderBy('display_order')
                    ->first();

            $organization = CommercialOrganization::create([
                'tenant_id' => $tenantId,
                'reference' => $this->numbering->next($tenantId, 'organization'),
                'legal_name' => $data['legal_name'] ?? $lead->organization_name,
                'trading_name' => $data['trading_name'] ?? null,
                'industry' => $lead->industry,
                'sector' => $lead->sector,
                'primary_email' => $lead->email,
                'primary_telephone' => $lead->telephone,
                'country' => $lead->country,
                'district' => $lead->district,
                'physical_address' => $lead->location,
                'customer_status' => 'Qualified Prospect',
                'account_manager_id' => $lead->assigned_employee_id ?: $user->id,
                'acquisition_source' => $lead->lead_source,
                'notes' => $lead->requirements_summary,
                'created_by' => $user->id,
            ]);

            $stakeholder = null;
            if ($lead->contact_person || $lead->email || $lead->telephone) {
                $stakeholder = CommercialStakeholder::create([
                    'tenant_id' => $tenantId,
                    'organization_id' => $organization->id,
                    'full_name' => $lead->contact_person ?: 'Primary Contact',
                    'email' => $lead->email,
                    'telephone' => $lead->telephone,
                    'decision_role' => 'Decision Maker',
                    'is_primary_contact' => true,
                    'created_by' => $user->id,
                ]);
            }

            $opportunity = CommercialOpportunity::create([
                'tenant_id' => $tenantId,
                'campaign_id' => $lead->campaign_id,
                'organization_id' => $organization->id,
                'lead_id' => $lead->id,
                'primary_stakeholder_id' => $stakeholder?->id,
                'pipeline_stage_id' => $stage?->id,
                'reference' => $this->numbering->next($tenantId, 'opportunity'),
                'title' => $data['opportunity_title'] ?? ($lead->organization_name . ' Opportunity'),
                'assigned_employee_id' => $lead->assigned_employee_id ?: $user->id,
                'product_or_service' => $lead->interested_product ?: $lead->interested_service,
                'opportunity_type' => $data['opportunity_type'] ?? 'New Business',
                'opportunity_source' => $lead->lead_source,
                'current_stage' => $stage?->name ?? 'Qualified',
                'probability' => $stage?->default_probability ?? 20,
                'estimated_value' => $lead->estimated_budget ?? 0,
                'currency' => config('app.currency', 'UGX'),
                'expected_close_date' => $lead->expected_decision_date,
                'customer_need' => $lead->requirements_summary,
                'problem_statement' => $lead->pain_points,
                'next_action' => $lead->next_action,
                'next_action_date' => $lead->next_follow_up_date,
                'created_by' => $user->id,
            ]);

            CommercialOpportunityStageHistory::create([
                'tenant_id' => $tenantId,
                'opportunity_id' => $opportunity->id,
                'previous_stage' => null,
                'new_stage' => $opportunity->current_stage,
                'changed_by' => $user->id,
                'reason' => 'Lead converted',
            ]);

            $lead->forceFill([
                'organization_id' => $organization->id,
                'stakeholder_id' => $stakeholder?->id,
                'opportunity_id' => $opportunity->id,
                'status' => 'Converted',
                'qualification_status' => 'Converted',
                'converted_at' => now(),
                'updated_by' => $user->id,
            ])->save();

            return compact('organization', 'stakeholder', 'opportunity');
        });
    }
}
