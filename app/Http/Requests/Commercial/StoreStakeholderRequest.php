<?php

namespace App\Http\Requests\Commercial;

use Illuminate\Foundation\Http\FormRequest;

class StoreStakeholderRequest extends FormRequest
{
    public function authorize(): bool
    {
        $permission = $this->isMethod('PUT') || $this->isMethod('PATCH')
            ? 'commercial.stakeholders.update'
            : 'commercial.stakeholders.create';

        return (bool) $this->user()?->hasPermission($permission);
    }

    public function rules(): array
    {
        return [
            'organization_id' => ['required', 'integer', 'exists:commercial_organizations,id'],
            'full_name' => ['required', 'string', 'max:180'],
            'position_title' => ['nullable', 'string', 'max:120'],
            'department' => ['nullable', 'string', 'max:120'],
            'email' => ['nullable', 'email:rfc', 'max:180'],
            'telephone' => ['nullable', 'string', 'max:80'],
            'alternative_telephone' => ['nullable', 'string', 'max:80'],
            'decision_role' => ['nullable', 'string', 'max:80'],
            'influence_level' => ['nullable', 'string', 'max:60'],
            'interest_level' => ['nullable', 'string', 'max:60'],
            'relationship_status' => ['nullable', 'string', 'max:80'],
            'preferred_contact_method' => ['nullable', 'string', 'max:80'],
            'communication_preference' => ['nullable', 'string', 'max:120'],
            'decision_authority' => ['nullable', 'string', 'max:80'],
            'is_primary_contact' => ['nullable', 'boolean'],
            'is_billing_contact' => ['nullable', 'boolean'],
            'is_technical_contact' => ['nullable', 'boolean'],
            'is_contract_signatory' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
