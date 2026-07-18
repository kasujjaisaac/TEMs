<?php

namespace App\Http\Requests\HR;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreHrPositionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->hasPermission('hr.positions.manage');
    }

    public function rules(): array
    {
        $position = $this->route('position');

        return [
            'department_id' => ['required', 'integer', 'exists:hr_departments,id'],
            'reports_to_position_id' => ['nullable', 'integer', 'exists:hr_positions,id'],
            'code' => ['required', 'string', 'max:40', Rule::unique('hr_positions', 'code')->where('tenant_id', $this->user()?->tenant_id)->ignore($position?->id)],
            'title' => ['required', 'string', 'max:160'],
            'job_family' => ['nullable', 'string', 'max:100'],
            'grade' => ['nullable', 'string', 'max:80'],
            'level' => ['nullable', 'string', 'max:80'],
            'employment_type' => ['required', 'string', 'max:80'],
            'work_location' => ['nullable', 'string', 'max:120'],
            'job_purpose' => ['nullable', 'string'],
            'key_responsibilities' => ['nullable', 'string'],
            'standard_kpis' => ['nullable', 'string'],
            'competencies' => ['nullable', 'string'],
            'decision_rights' => ['nullable', 'string'],
            'approval_limit' => ['nullable', 'numeric', 'min:0'],
            'approved_headcount' => ['required', 'integer', 'min:0'],
            'filled_headcount' => ['required', 'integer', 'min:0'],
            'position_status' => ['required', 'in:Planned,Approved,Occupied,Vacant,Frozen,Acting,Inactive,Archived'],
            'effective_from' => ['nullable', 'date'],
            'effective_to' => ['nullable', 'date', 'after_or_equal:effective_from'],
        ];
    }
}
