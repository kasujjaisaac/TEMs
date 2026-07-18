<?php

namespace App\Http\Requests\Commercial;

use Illuminate\Foundation\Http\FormRequest;

class StoreOpportunityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->hasPermission('commercial.opportunities.create');
    }

    public function rules(): array
    {
        return [
            'organization_id' => ['required', 'integer', 'exists:commercial_organizations,id'],
            'primary_stakeholder_id' => ['nullable', 'integer', 'exists:commercial_stakeholders,id'],
            'title' => ['required', 'string', 'max:180'],
            'assigned_employee_id' => ['nullable', 'integer', 'exists:users,id'],
            'product_or_service' => ['nullable', 'string', 'max:180'],
            'opportunity_type' => ['required', 'string', 'max:80'],
            'opportunity_source' => ['nullable', 'string', 'max:80'],
            'current_stage' => ['required', 'string', 'max:120'],
            'probability' => ['required', 'integer', 'min:0', 'max:100'],
            'estimated_value' => ['required', 'numeric', 'min:0.01'],
            'currency' => ['required', 'string', 'max:8'],
            'expected_close_date' => ['nullable', 'date'],
            'expected_start_date' => ['nullable', 'date'],
            'contract_duration_months' => ['nullable', 'integer', 'min:0'],
            'revenue_type' => ['nullable', 'string', 'max:80'],
            'billing_frequency' => ['nullable', 'string', 'max:80'],
            'customer_need' => ['nullable', 'string'],
            'problem_statement' => ['nullable', 'string'],
            'proposed_solution' => ['nullable', 'string'],
            'risk_level' => ['nullable', 'string', 'max:60'],
            'next_action' => ['nullable', 'string', 'max:180'],
            'next_action_date' => ['nullable', 'date'],
        ];
    }
}
