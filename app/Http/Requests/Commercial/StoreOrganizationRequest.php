<?php

namespace App\Http\Requests\Commercial;

use Illuminate\Foundation\Http\FormRequest;

class StoreOrganizationRequest extends FormRequest
{
    public function authorize(): bool
    {
        $permission = $this->isMethod('PUT') || $this->isMethod('PATCH')
            ? 'commercial.organizations.update'
            : 'commercial.organizations.create';

        return (bool) $this->user()?->hasPermission($permission);
    }

    public function rules(): array
    {
        return [
            'legacy_customer_id' => ['nullable', 'integer'],
            'legal_name' => ['required', 'string', 'max:180'],
            'trading_name' => ['nullable', 'string', 'max:180'],
            'organization_type' => ['nullable', 'string', 'max:80'],
            'customer_category' => ['nullable', 'string', 'max:80'],
            'industry' => ['nullable', 'string', 'max:120'],
            'sector' => ['nullable', 'string', 'max:120'],
            'tin' => ['nullable', 'string', 'max:80'],
            'registration_number' => ['nullable', 'string', 'max:120'],
            'primary_email' => ['nullable', 'email:rfc', 'max:180'],
            'primary_telephone' => ['nullable', 'string', 'max:80'],
            'alternative_telephone' => ['nullable', 'string', 'max:80'],
            'website' => ['nullable', 'string', 'max:180'],
            'country' => ['nullable', 'string', 'max:80'],
            'district' => ['nullable', 'string', 'max:120'],
            'city' => ['nullable', 'string', 'max:120'],
            'physical_address' => ['nullable', 'string'],
            'postal_address' => ['nullable', 'string'],
            'number_of_branches' => ['nullable', 'integer', 'min:0'],
            'number_of_employees' => ['nullable', 'integer', 'min:0'],
            'customer_status' => ['required', 'string', 'max:80'],
            'account_manager_id' => ['nullable', 'integer', 'exists:users,id'],
            'acquisition_source' => ['nullable', 'string', 'max:120'],
            'relationship_status' => ['nullable', 'string', 'max:80'],
            'relationship_score' => ['nullable', 'integer', 'min:0', 'max:100'],
            'credit_status' => ['nullable', 'string', 'max:80'],
            'payment_terms' => ['nullable', 'string', 'max:120'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
