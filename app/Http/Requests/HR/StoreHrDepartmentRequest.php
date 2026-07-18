<?php

namespace App\Http\Requests\HR;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreHrDepartmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->hasPermission('hr.structure.manage');
    }

    public function rules(): array
    {
        $department = $this->route('department');

        return [
            'parent_id' => ['nullable', 'integer', 'exists:hr_departments,id'],
            'code' => ['required', 'string', 'max:40', Rule::unique('hr_departments', 'code')->where('tenant_id', $this->user()?->tenant_id)->ignore($department?->id)],
            'name' => ['required', 'string', 'max:160'],
            'short_name' => ['nullable', 'string', 'max:80'],
            'type' => ['required', 'in:Department,Unit,Team,Governance'],
            'description' => ['nullable', 'string'],
            'mandate' => ['nullable', 'string'],
            'responsibilities' => ['nullable', 'string'],
            'cost_centre' => ['nullable', 'string', 'max:80'],
            'status' => ['required', 'in:Proposed,Reviewed,Approved,Active,Restructured,Inactive,Archived'],
            'effective_from' => ['nullable', 'date'],
            'review_date' => ['nullable', 'date'],
        ];
    }
}
