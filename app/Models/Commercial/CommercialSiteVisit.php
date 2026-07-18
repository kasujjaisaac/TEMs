<?php

namespace App\Models\Commercial;

use Illuminate\Database\Eloquent\Model;

class CommercialSiteVisit extends Model
{
    protected $fillable = [
        'tenant_id', 'organization_id', 'opportunity_id', 'reference', 'site_location',
        'visit_date', 'visit_purpose', 'internal_team', 'customer_representatives',
        'current_environment', 'existing_systems', 'technical_infrastructure',
        'internet_availability', 'number_of_users', 'number_of_branches',
        'business_processes_observed', 'customer_challenges', 'functional_requirements',
        'technical_requirements', 'implementation_considerations', 'risks',
        'recommendations', 'follow_up_actions', 'report_status', 'recorded_by',
    ];

    protected function casts(): array
    {
        return [
            'visit_date' => 'date',
        ];
    }
}
