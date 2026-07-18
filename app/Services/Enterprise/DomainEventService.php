<?php

namespace App\Services\Enterprise;

use App\Models\DomainEvent;
use App\Models\User;

class DomainEventService
{
    public function record(string $eventName, string $sourceModule, ?object $subject = null, array $payload = [], ?int $tenantId = null, ?User $actor = null): DomainEvent
    {
        return DomainEvent::create([
            'tenant_id' => $tenantId ?? ($subject->tenant_id ?? $actor?->tenant_id),
            'event_name' => $eventName,
            'source_module' => $sourceModule,
            'subject_type' => $subject ? $subject::class : null,
            'subject_id' => $subject->id ?? null,
            'actor_id' => $actor?->id,
            'occurred_at' => now(),
            'status' => 'Recorded',
            'payload' => $payload,
        ]);
    }
}
