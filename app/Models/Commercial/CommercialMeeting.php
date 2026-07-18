<?php

namespace App\Models\Commercial;

use Illuminate\Database\Eloquent\Model;

class CommercialMeeting extends Model
{
    protected $fillable = [
        'tenant_id', 'lead_id', 'organization_id', 'stakeholder_id', 'opportunity_id',
        'title', 'meeting_type', 'meeting_date', 'start_time', 'end_time', 'location',
        'meeting_link', 'internal_attendees', 'external_attendees', 'agenda',
        'discussion_notes', 'customer_requirements', 'decisions_made', 'action_items',
        'next_meeting_date', 'recorded_by',
    ];

    protected function casts(): array
    {
        return [
            'meeting_date' => 'date',
            'next_meeting_date' => 'date',
        ];
    }
}
