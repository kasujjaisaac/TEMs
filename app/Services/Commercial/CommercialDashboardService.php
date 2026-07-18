<?php

namespace App\Services\Commercial;

use App\Models\Commercial\CommercialLead;
use App\Models\Commercial\CommercialMeeting;
use App\Models\Commercial\CommercialOpportunity;
use App\Models\Commercial\CommercialOrganization;
use App\Models\Commercial\CommercialSiteVisit;

class CommercialDashboardService
{
    public function metrics(int $tenantId): array
    {
        $opportunities = CommercialOpportunity::where('tenant_id', $tenantId);
        $pipelineValue = (clone $opportunities)->sum('estimated_value');
        $weightedValue = (clone $opportunities)->get()->sum->weighted_value;

        return [
            'active_leads' => CommercialLead::where('tenant_id', $tenantId)->whereNotIn('status', ['Converted', 'Lost', 'Archived'])->count(),
            'new_leads_month' => CommercialLead::where('tenant_id', $tenantId)->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->count(),
            'qualified_leads' => CommercialLead::where('tenant_id', $tenantId)->where('status', 'Qualified')->count(),
            'organizations' => CommercialOrganization::where('tenant_id', $tenantId)->count(),
            'active_opportunities' => (clone $opportunities)->whereNotIn('current_stage', ['Won', 'Lost'])->count(),
            'pipeline_value' => $pipelineValue,
            'weighted_pipeline_value' => $weightedValue,
            'upcoming_meetings' => CommercialMeeting::where('tenant_id', $tenantId)->whereDate('meeting_date', '>=', now()->toDateString())->count(),
            'upcoming_site_visits' => CommercialSiteVisit::where('tenant_id', $tenantId)->whereDate('visit_date', '>=', now()->toDateString())->count(),
        ];
    }
}
