<?php

namespace App\Services\Enterprise;

use App\Models\ApprovalRequest;
use App\Models\User;

class ApprovalService
{
    public function request(int $tenantId, string $module, string $type, string $title, array $data = []): ApprovalRequest
    {
        $subject = $data['subject'] ?? null;

        $approval = ApprovalRequest::create([
            'tenant_id' => $tenantId,
            'module' => $module,
            'request_type' => $type,
            'subject_type' => $subject ? $subject::class : ($data['subject_type'] ?? null),
            'subject_id' => $subject->id ?? ($data['subject_id'] ?? null),
            'title' => $title,
            'summary' => $data['summary'] ?? null,
            'status' => 'Pending',
            'priority' => $data['priority'] ?? 'Normal',
            'requested_by' => $data['requested_by'] ?? null,
            'requested_at' => now(),
            'metadata' => $data['metadata'] ?? [],
        ]);

        app(DomainEventService::class)->record('approval.requested', $module, $approval, [
            'request_type' => $type,
            'priority' => $approval->priority,
            'title' => $title,
        ], $tenantId, isset($data['actor']) && $data['actor'] instanceof User ? $data['actor'] : null);

        return $approval;
    }

    public function decide(ApprovalRequest $approval, string $decision, User $reviewer, ?string $notes = null): ApprovalRequest
    {
        $approval->forceFill([
            'status' => $decision,
            'reviewed_by' => $reviewer->id,
            'reviewed_at' => now(),
            'decision_notes' => $notes,
        ])->save();

        app(DomainEventService::class)->record('approval.' . strtolower($decision), $approval->module, $approval, [
            'request_type' => $approval->request_type,
            'notes' => $notes,
        ], (int) $approval->tenant_id, $reviewer);

        app(NotificationService::class)->notify(
            $approval->requester,
            (int) $approval->tenant_id,
            'Approval ' . strtolower($decision),
            $approval->title . ' was ' . strtolower($decision) . '.',
            ['source_module' => $approval->module, 'type' => 'approval', 'severity' => $decision === 'Approved' ? 'Success' : 'Warning']
        );

        return $approval;
    }
}
