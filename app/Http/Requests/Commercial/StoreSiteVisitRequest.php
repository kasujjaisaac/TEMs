<?php

namespace App\Http\Requests\Commercial;

use Illuminate\Foundation\Http\FormRequest;

class StoreSiteVisitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->hasPermission('commercial.site_visits.create');
    }

    public function rules(): array
    {
        return [
            'organization_id' => ['nullable', 'integer', 'exists:commercial_organizations,id'],
            'opportunity_id' => ['nullable', 'integer', 'exists:commercial_opportunities,id'],
            'site_location' => ['required', 'string', 'max:180'],
            'visit_date' => ['required', 'date'],
            'visit_purpose' => ['nullable', 'string', 'max:180'],
            'internal_team' => ['nullable', 'string'],
            'customer_representatives' => ['nullable', 'string'],
            'current_environment' => ['nullable', 'string'],
            'existing_systems' => ['nullable', 'string'],
            'technical_infrastructure' => ['nullable', 'string'],
            'internet_availability' => ['nullable', 'string', 'max:120'],
            'number_of_users' => ['nullable', 'integer', 'min:0'],
            'number_of_branches' => ['nullable', 'integer', 'min:0'],
            'business_processes_observed' => ['nullable', 'string'],
            'customer_challenges' => ['nullable', 'string'],
            'functional_requirements' => ['nullable', 'string'],
            'technical_requirements' => ['nullable', 'string'],
            'implementation_considerations' => ['nullable', 'string'],
            'risks' => ['nullable', 'string'],
            'recommendations' => ['nullable', 'string'],
            'follow_up_actions' => ['nullable', 'string'],
            'report_status' => ['required', 'string', 'max:80'],
        ];
    }
}
