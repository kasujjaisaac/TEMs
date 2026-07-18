<?php

namespace App\Http\Requests\Commercial;

use Illuminate\Foundation\Http\FormRequest;

class StoreLeadRequest extends FormRequest
{
    public function authorize(): bool
    {
        $permission = $this->isMethod('PUT') || $this->isMethod('PATCH')
            ? 'commercial.leads.update'
            : 'commercial.leads.create';

        return (bool) $this->user()?->hasPermission($permission);
    }

    public function rules(): array
    {
        return [
            'organization_name' => ['required', 'string', 'max:180'],
            'contact_person' => ['nullable', 'string', 'max:180'],
            'telephone' => ['nullable', 'string', 'max:80'],
            'email' => ['nullable', 'email:rfc', 'max:180'],
            'location' => ['nullable', 'string', 'max:180'],
            'district' => ['nullable', 'string', 'max:120'],
            'country' => ['nullable', 'string', 'max:80'],
            'industry' => ['nullable', 'string', 'max:120'],
            'sector' => ['nullable', 'string', 'max:120'],
            'customer_type' => ['nullable', 'string', 'max:80'],
            'lead_source' => ['nullable', 'string', 'max:80'],
            'source_campaign' => ['nullable', 'string', 'max:120'],
            'interested_product' => ['nullable', 'string', 'max:180'],
            'interested_service' => ['nullable', 'string', 'max:180'],
            'estimated_budget' => ['nullable', 'numeric', 'min:0'],
            'expected_decision_date' => ['nullable', 'date'],
            'description' => ['nullable', 'string'],
            'pain_points' => ['nullable', 'string'],
            'requirements_summary' => ['nullable', 'string'],
            'assigned_employee_id' => ['nullable', 'integer', 'exists:users,id'],
            'assigned_department' => ['nullable', 'string', 'max:120'],
            'temperature' => ['required', 'in:Cold,Warm,Hot'],
            'lead_score' => ['nullable', 'integer', 'min:0', 'max:100'],
            'status' => ['required', 'string', 'max:60'],
            'qualification_status' => ['nullable', 'string', 'max:80'],
            'next_action' => ['nullable', 'string', 'max:180'],
            'next_follow_up_date' => ['nullable', 'date'],
            'last_contacted_date' => ['nullable', 'date'],
        ];
    }
}
