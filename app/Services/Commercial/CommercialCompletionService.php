<?php

namespace App\Services\Commercial;

use App\Models\Commercial\CommercialOpportunity;
use App\Models\User;
use App\Services\CRM\CustomerAccountLifecycleService;
use App\Services\Enterprise\AuditService;
use App\Services\Enterprise\DomainEventService;
use Illuminate\Support\Facades\DB;

class CommercialCompletionService
{
    public function verifyStageControls(CommercialOpportunity $opportunity, User $user): array
    {
        $controls = $this->controlsForStage($opportunity);

        foreach ($controls as $key => [$label, $passed]) {
            DB::table('commercial_stage_controls')->updateOrInsert(
                ['tenant_id' => $opportunity->tenant_id, 'opportunity_id' => $opportunity->id, 'stage' => $opportunity->current_stage, 'control_key' => $key],
                [
                    'control_label' => $label,
                    'status' => $passed ? 'Passed' : 'Failed',
                    'verified_by' => $user->id,
                    'verified_at' => now(),
                    'notes' => $passed ? 'Control satisfied.' : 'Control must be completed before this stage is reliable.',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }

        $passed = collect($controls)->every(fn (array $control): bool => (bool) $control[1]);
        app(DomainEventService::class)->record($passed ? 'commercial.stage_controls.passed' : 'commercial.stage_controls.failed', 'Commercial Operations', $opportunity, [
            'stage' => $opportunity->current_stage,
            'controls' => array_keys($controls),
        ], (int) $opportunity->tenant_id, $user);
        app(AuditService::class)->record((int) $opportunity->tenant_id, $user, 'verified', 'commercial', 'Verified opportunity stage controls for ' . $opportunity->reference, ['passed' => $passed], $opportunity);

        return ['passed' => $passed, 'controls' => $controls];
    }

    public function recordNegotiation(CommercialOpportunity $opportunity, User $user, array $data): int
    {
        $id = DB::table('commercial_negotiations')->insertGetId([
            'tenant_id' => $opportunity->tenant_id,
            'opportunity_id' => $opportunity->id,
            'stakeholder_id' => $data['stakeholder_id'] ?? null,
            'topic' => $data['topic'],
            'customer_position' => $data['customer_position'] ?? null,
            'texaro_position' => $data['texaro_position'] ?? null,
            'proposed_value' => $data['proposed_value'] ?? null,
            'agreed_value' => $data['agreed_value'] ?? null,
            'status' => $data['status'] ?? 'Open',
            'next_follow_up_on' => $data['next_follow_up_on'] ?? null,
            'recorded_by' => $user->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $opportunity->forceFill([
            'current_stage' => 'Negotiation',
            'probability' => max((int) $opportunity->probability, 75),
            'next_action' => 'Follow up negotiation: ' . $data['topic'],
            'next_action_date' => $data['next_follow_up_on'] ?? $opportunity->next_action_date,
            'updated_by' => $user->id,
        ])->save();

        app(CustomerAccountLifecycleService::class)->recordOpportunityTimeline($opportunity, $user, 'Negotiation', 'Negotiation recorded: ' . $data['topic'], $data['customer_position'] ?? null);
        app(DomainEventService::class)->record('commercial.negotiation.recorded', 'Commercial Operations', $opportunity, ['negotiation_id' => $id], (int) $opportunity->tenant_id, $user);

        return $id;
    }

    public function scheduleRenewal(CommercialOpportunity $opportunity, User $user, array $data): int
    {
        $organization = $opportunity->organization;
        $id = DB::table('commercial_renewals')->insertGetId([
            'tenant_id' => $opportunity->tenant_id,
            'organization_id' => $opportunity->organization_id,
            'customer_id' => $organization?->legacy_customer_id,
            'contract_id' => $data['contract_id'] ?? null,
            'reference' => $this->nextReference((int) $opportunity->tenant_id, 'commercial_renewals', 'REN'),
            'renewal_due_on' => $data['renewal_due_on'],
            'renewal_value' => $data['renewal_value'] ?? $opportunity->estimated_value,
            'currency' => $opportunity->currency ?: 'UGX',
            'status' => $data['status'] ?? 'Due',
            'owner_id' => $user->id,
            'retention_plan' => $data['retention_plan'] ?? null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        if ($organization?->legacy_customer_id) {
            app(CustomerAccountLifecycleService::class)->timeline((int) $opportunity->tenant_id, (int) $organization->legacy_customer_id, (int) $opportunity->organization_id, 'Renewal', 'Renewal scheduled', $data['retention_plan'] ?? null, 'Commercial Operations', 'commercial_renewals', $id, $user);
        }
        app(DomainEventService::class)->record('commercial.renewal.scheduled', 'Commercial Operations', $opportunity, ['renewal_id' => $id], (int) $opportunity->tenant_id, $user);

        return $id;
    }

    public function identifyExpansion(CommercialOpportunity $opportunity, User $user, array $data): int
    {
        $organization = $opportunity->organization;
        $id = DB::table('commercial_expansion_opportunities')->insertGetId([
            'tenant_id' => $opportunity->tenant_id,
            'organization_id' => $opportunity->organization_id,
            'customer_id' => $organization?->legacy_customer_id,
            'source_opportunity_id' => $opportunity->id,
            'reference' => $this->nextReference((int) $opportunity->tenant_id, 'commercial_expansion_opportunities', 'EXPAN'),
            'expansion_type' => $data['expansion_type'] ?? 'Upsell',
            'title' => $data['title'],
            'estimated_value' => $data['estimated_value'] ?? 0,
            'currency' => $opportunity->currency ?: 'UGX',
            'status' => $data['status'] ?? 'Identified',
            'owner_id' => $user->id,
            'rationale' => $data['rationale'] ?? null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        if ($organization?->legacy_customer_id) {
            app(CustomerAccountLifecycleService::class)->timeline((int) $opportunity->tenant_id, (int) $organization->legacy_customer_id, (int) $opportunity->organization_id, 'Expansion', 'Expansion identified: ' . $data['title'], $data['rationale'] ?? null, 'Commercial Operations', 'commercial_expansion_opportunities', $id, $user);
        }
        app(DomainEventService::class)->record('commercial.expansion.identified', 'Commercial Operations', $opportunity, ['expansion_id' => $id], (int) $opportunity->tenant_id, $user);

        return $id;
    }

    public function recordLostAnalysis(CommercialOpportunity $opportunity, User $user, array $data): void
    {
        DB::table('commercial_lost_opportunity_analyses')->updateOrInsert(
            ['tenant_id' => $opportunity->tenant_id, 'opportunity_id' => $opportunity->id],
            [
                'primary_reason' => $data['primary_reason'],
                'competitor_name' => $data['competitor_name'] ?? null,
                'lessons_learned' => $data['lessons_learned'] ?? null,
                'recovery_action' => $data['recovery_action'] ?? null,
                'recorded_by' => $user->id,
                'recorded_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        $opportunity->forceFill([
            'current_stage' => 'Lost',
            'probability' => 0,
            'lost_reason' => $data['primary_reason'],
            'lost_at' => now(),
            'updated_by' => $user->id,
        ])->save();

        app(CustomerAccountLifecycleService::class)->recordOpportunityTimeline($opportunity, $user, 'Lost Opportunity', 'Lost opportunity analysis recorded', $data['lessons_learned'] ?? null);
        app(DomainEventService::class)->record('commercial.opportunity.lost_analyzed', 'Commercial Operations', $opportunity, ['primary_reason' => $data['primary_reason']], (int) $opportunity->tenant_id, $user);
    }

    private function controlsForStage(CommercialOpportunity $opportunity): array
    {
        return match ($opportunity->current_stage) {
            'Qualified' => [
                'organization_confirmed' => ['Organization confirmed', (bool) $opportunity->organization_id],
                'need_recorded' => ['Customer need or problem recorded', (bool) ($opportunity->customer_need ?: $opportunity->problem_statement)],
                'value_estimated' => ['Estimated opportunity value recorded', (float) $opportunity->estimated_value > 0],
            ],
            'Negotiation' => [
                'stakeholder_or_process' => ['Decision stakeholder or process known', (bool) ($opportunity->primary_stakeholder_id ?: $opportunity->decision_process)],
                'quotation_ready' => ['Quotation exists', $opportunity->quotations()->exists()],
                'risks_recorded' => ['Commercial risks reviewed', (bool) ($opportunity->identified_risks ?: $opportunity->risk_level)],
            ],
            'Contracting' => [
                'accepted_quotation' => ['Accepted quotation exists', $opportunity->quotations()->where('status', 'Accepted')->exists()],
                'payment_terms' => ['Payment terms captured', $opportunity->contracts()->whereNotNull('payment_terms')->exists()],
                'contract_drafted' => ['Contract drafted', $opportunity->contracts()->exists()],
            ],
            'Won' => [
                'signed_contract' => ['Signed contract exists', $opportunity->contracts()->where('status', 'Signed')->exists()],
                'billing_request' => ['Billing request exists', $opportunity->billingRequests()->exists()],
                'handover_ready' => ['Handover status ready', in_array($opportunity->sales_handoff_status, ['Billing Requested', 'Finance Ready', 'Handover Complete'], true)],
            ],
            default => [
                'organization_confirmed' => ['Organization confirmed', (bool) $opportunity->organization_id],
                'owner_activity' => ['Next action recorded', (bool) $opportunity->next_action],
            ],
        };
    }

    private function nextReference(int $tenantId, string $table, string $prefix): string
    {
        return sprintf('%s-%s-%05d', $prefix, now()->format('Y'), DB::table($table)->where('tenant_id', $tenantId)->count() + 1);
    }
}
