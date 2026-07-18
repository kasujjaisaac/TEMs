<?php

namespace App\Http\Requests\Commercial;

use Illuminate\Foundation\Http\FormRequest;

class StoreMeetingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->hasPermission('commercial.meetings.create');
    }

    public function rules(): array
    {
        return [
            'lead_id' => ['nullable', 'integer', 'exists:commercial_leads,id'],
            'organization_id' => ['nullable', 'integer', 'exists:commercial_organizations,id'],
            'stakeholder_id' => ['nullable', 'integer', 'exists:commercial_stakeholders,id'],
            'opportunity_id' => ['nullable', 'integer', 'exists:commercial_opportunities,id'],
            'title' => ['required', 'string', 'max:180'],
            'meeting_type' => ['required', 'string', 'max:80'],
            'meeting_date' => ['required', 'date'],
            'start_time' => ['nullable', 'date_format:H:i'],
            'end_time' => ['nullable', 'date_format:H:i'],
            'location' => ['nullable', 'string', 'max:180'],
            'meeting_link' => ['nullable', 'string', 'max:255'],
            'internal_attendees' => ['nullable', 'string'],
            'external_attendees' => ['nullable', 'string'],
            'agenda' => ['nullable', 'string'],
            'discussion_notes' => ['nullable', 'string'],
            'customer_requirements' => ['nullable', 'string'],
            'decisions_made' => ['nullable', 'string'],
            'action_items' => ['nullable', 'string'],
            'next_meeting_date' => ['nullable', 'date'],
        ];
    }
}
