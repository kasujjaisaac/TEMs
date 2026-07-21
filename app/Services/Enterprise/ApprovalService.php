<?php

namespace App\Services\Enterprise;

use App\Models\ApprovalRequest;
use App\Models\ApprovalStep;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ApprovalService
{
    public function request(int $tenantId, string $module, string $type, string $title, array $data = []): ApprovalRequest
    {
        return DB::transaction(function () use ($tenantId, $module, $type, $title, $data): ApprovalRequest {
            $subject = $data['subject'] ?? null;
            $amount = isset($data['amount']) ? (float) $data['amount'] : (float) ($data['metadata']['amount'] ?? 0);

            $approval = ApprovalRequest::create([
                'tenant_id' => $tenantId,
                'module' => $module,
                'request_type' => $type,
                'subject_type' => $subject ? $subject::class : ($data['subject_type'] ?? null),
                'subject_id' => $subject->id ?? ($data['subject_id'] ?? null),
                'title' => $title,
                'summary' => $data['summary'] ?? null,
                'amount' => $amount ?: null,
                'status' => 'Pending',
                'priority' => $data['priority'] ?? 'Normal',
                'requested_by' => $data['requested_by'] ?? null,
                'requested_at' => now(),
                'metadata' => $data['metadata'] ?? [],
            ]);

            $this->createSteps($approval, $amount);
            $this->activateCurrentStep($approval);

            app(DomainEventService::class)->record('approval.requested', $module, $approval, [
                'request_type' => $type,
                'priority' => $approval->priority,
                'title' => $title,
                'current_step' => $approval->current_step,
            ], $tenantId, isset($data['actor']) && $data['actor'] instanceof User ? $data['actor'] : null);

            $this->notifyCurrentApprover($approval);

            return $approval->fresh(['steps']);
        });
    }

    public function decide(ApprovalRequest $approval, string $decision, User $reviewer, ?string $notes = null): ApprovalRequest
    {
        return DB::transaction(function () use ($approval, $decision, $reviewer, $notes): ApprovalRequest {
            $step = $approval->steps()->where('status', 'Pending')->orderBy('sequence')->lockForUpdate()->first();

            if ($step) {
                $step->forceFill([
                    'status' => $decision,
                    'decided_by' => $reviewer->id,
                    'decided_at' => now(),
                    'decision_notes' => $notes,
                ])->save();

                app(DomainEventService::class)->record('approval.step.' . strtolower($decision), $approval->module, $step, [
                    'approval_request_id' => $approval->id,
                    'sequence' => $step->sequence,
                    'notes' => $notes,
                ], (int) $approval->tenant_id, $reviewer);
            }

            if ($decision === 'Rejected') {
                $approval->forceFill([
                    'status' => 'Rejected',
                    'reviewed_by' => $reviewer->id,
                    'reviewed_at' => now(),
                    'decision_notes' => $notes,
                ])->save();
            } else {
                $nextStep = $approval->steps()->where('status', 'Pending')->orderBy('sequence')->first();
                if ($nextStep) {
                    $approval->forceFill([
                        'status' => 'Pending',
                        'current_step' => $nextStep->sequence,
                        'current_approver_id' => $nextStep->approver_user_id,
                    ])->save();
                    $this->notifyCurrentApprover($approval);
                } else {
                    $approval->forceFill([
                        'status' => 'Approved',
                        'reviewed_by' => $reviewer->id,
                        'reviewed_at' => now(),
                        'decision_notes' => $notes,
                        'current_approver_id' => null,
                    ])->save();
                }
            }

            if (in_array($approval->status, ['Approved', 'Rejected'], true)) {
                app(DomainEventService::class)->record('approval.' . strtolower($approval->status), $approval->module, $approval, [
                    'request_type' => $approval->request_type,
                    'notes' => $notes,
                ], (int) $approval->tenant_id, $reviewer);

                app(NotificationService::class)->notify(
                    $approval->requester,
                    (int) $approval->tenant_id,
                    'Approval ' . strtolower($approval->status),
                    $approval->title . ' was ' . strtolower($approval->status) . '.',
                    ['source_module' => $approval->module, 'type' => 'approval', 'severity' => $approval->status === 'Approved' ? 'Success' : 'Warning']
                );
            }

            return $approval->fresh(['steps']);
        });
    }

    private function createSteps(ApprovalRequest $approval, float $amount): void
    {
        $rules = DB::table('approval_rules')
            ->where('tenant_id', $approval->tenant_id)
            ->where('module', $approval->module)
            ->where('request_type', $approval->request_type)
            ->where('minimum_amount', '<=', $amount)
            ->where('is_active', true)
            ->orderBy('sequence')
            ->orderByDesc('minimum_amount')
            ->get()
            ->unique('sequence')
            ->values();

        if ($rules->isEmpty()) {
            ApprovalStep::create([
                'tenant_id' => $approval->tenant_id,
                'approval_request_id' => $approval->id,
                'sequence' => 1,
                'status' => 'Pending',
                'metadata' => ['routing' => 'default'],
            ]);
            return;
        }

        foreach ($rules as $rule) {
            $approverUserId = $this->approverUserId($approval, $rule);
            $approverRole = $approverUserId && is_numeric($rule->approver_role) ? null : $rule->approver_role;

            ApprovalStep::create([
                'tenant_id' => $approval->tenant_id,
                'approval_request_id' => $approval->id,
                'sequence' => $rule->sequence,
                'approver_role' => $approverRole,
                'approver_user_id' => $approverUserId,
                'status' => 'Pending',
                'metadata' => ['approval_rule_id' => $rule->id, 'minimum_amount' => $rule->minimum_amount],
            ]);
        }
    }

    private function activateCurrentStep(ApprovalRequest $approval): void
    {
        $step = $approval->steps()->where('status', 'Pending')->orderBy('sequence')->first();
        $approval->forceFill([
            'current_step' => $step?->sequence ?: 1,
            'current_approver_id' => $step?->approver_user_id,
        ])->save();
    }

    private function notifyCurrentApprover(ApprovalRequest $approval): void
    {
        if (! $approval->current_approver_id) {
            return;
        }

        app(NotificationService::class)->notify(
            $approval->currentApprover,
            (int) $approval->tenant_id,
            'Approval required',
            $approval->title . ' is awaiting your approval.',
            ['source_module' => $approval->module, 'type' => 'approval', 'severity' => $approval->priority === 'Critical' ? 'High' : 'Info']
        );
    }

    private function approverUserId(ApprovalRequest $approval, object $rule): ?int
    {
        if ($rule->approver_user_id) {
            return (int) $rule->approver_user_id;
        }

        if (! is_numeric($rule->approver_role)) {
            return null;
        }

        $userId = (int) $rule->approver_role;

        return User::where('tenant_id', $approval->tenant_id)->whereKey($userId)->exists() ? $userId : null;
    }
}
