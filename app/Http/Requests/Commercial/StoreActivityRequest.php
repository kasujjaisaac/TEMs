<?php

namespace App\Http\Requests\Commercial;

use Illuminate\Foundation\Http\FormRequest;

class StoreActivityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->hasPermission('commercial.activities.create');
    }

    public function rules(): array
    {
        return [
            'activity_type' => ['required', 'string', 'max:80'],
            'related_type' => ['required', 'string', 'max:120'],
            'related_id' => ['required', 'integer'],
            'assigned_employee_id' => ['nullable', 'integer', 'exists:users,id'],
            'activity_date' => ['nullable', 'date'],
            'activity_time' => ['nullable', 'date_format:H:i'],
            'description' => ['required', 'string'],
            'outcome' => ['nullable', 'string'],
            'next_action' => ['nullable', 'string', 'max:180'],
            'next_action_date' => ['nullable', 'date'],
            'completion_status' => ['required', 'string', 'max:60'],
        ];
    }
}
