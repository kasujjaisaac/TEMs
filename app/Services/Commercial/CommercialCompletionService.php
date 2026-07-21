<?php

namespace App\Services\Commercial;

use App\Models\Commercial\CommercialOpportunity;
use App\Models\User;
use App\Services\CRM\CustomerAccountLifecycleService;
use App\Services\Enterprise\AuditService;
use App\Services\Enterprise\DomainEventService;
use App\Services\Commercial\CommercialNumberingService;
use Illuminate\Support\Facades\DB;

class CommercialCompletionService
{
    public function __construct(private readonly CommercialNumberingService $numbering)
    {
    }

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

    public function assertStageCanMove(CommercialOpportunity $opportunity, string $nextStage, User $user): void
    {
        if ($nextStage === $opportunity->current_stage) {
            return;
        }

        $result = $this->verifyStageControls($opportunity, $user);
        abort_if(! $result['passed'], 422, 'Current stage controls must pass before moving this opportunity.');
    }

    public function addDecisionStep(CommercialOpportunity $opportunity, User $user, array $data): int
    {
        $id = DB::table('commercial_decision_process_maps')->insertGetId([
            'tenant_id' => $opportunity->tenant_id,
            'opportunity_id' => $opportunity->id,
            'step_name' => $data['step_name'],
            'sequence' => $data['sequence'] ?? 1,
            'stakeholder_id' => $data['stakeholder_id'] ?? null,
            'decision_role' => $data['decision_role'] ?? null,
            'status' => $data['status'] ?? 'Pending',
            'target_date' => $data['target_date'] ?? null,
            'notes' => $data['notes'] ?? null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        app(DomainEventService::class)->record('commercial.decision_step.recorded', 'Commercial Operations', $opportunity, ['decision_step_id' => $id], (int) $opportunity->tenant_id, $user);

        return $id;
    }

    public function createQuotationItem(int $tenantId, int $quotationId, User $user, array $data): int
    {
        $quotation = DB::table('commercial_quotations')->where('tenant_id', $tenantId)->where('id', $quotationId)->first();
        abort_unless($quotation, 404);

        $quantity = (float) ($data['quantity'] ?? 1);
        $unit = (float) ($data['unit_price'] ?? 0);
        $discount = (float) ($data['discount_amount'] ?? 0);
        $tax = (float) ($data['tax_amount'] ?? 0);
        $lineTotal = max(0, ($quantity * $unit) - $discount + $tax);
        $id = DB::table('commercial_quotation_items')->insertGetId([
            'tenant_id' => $tenantId,
            'quotation_id' => $quotationId,
            'product_id' => $data['product_id'] ?? null,
            'description' => $data['description'],
            'quantity' => $quantity,
            'unit_price' => $unit,
            'discount_amount' => $discount,
            'tax_amount' => $tax,
            'line_total' => $lineTotal,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $totals = DB::table('commercial_quotation_items')->where('tenant_id', $tenantId)->where('quotation_id', $quotationId)
            ->selectRaw('sum(quantity * unit_price) as subtotal, sum(discount_amount) as discount, sum(tax_amount) as tax, sum(line_total) as total')
            ->first();
        DB::table('commercial_quotations')->where('id', $quotationId)->update([
            'subtotal' => $totals->subtotal ?: 0,
            'discount_amount' => $totals->discount ?: 0,
            'tax_amount' => $totals->tax ?: 0,
            'total' => $totals->total ?: 0,
            'updated_at' => now(),
        ]);

        app(DomainEventService::class)->record('commercial.quotation_item.added', 'Commercial Operations', null, ['quotation_id' => $quotationId, 'item_id' => $id], $tenantId, $user);

        return $id;
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

    public function createInvoiceFromBillingRequest(int $tenantId, int $billingRequestId, User $user): int
    {
        return DB::transaction(function () use ($tenantId, $billingRequestId, $user): int {
            $billing = DB::table('commercial_billing_requests')->where('tenant_id', $tenantId)->where('id', $billingRequestId)->first();
            abort_unless($billing, 404);
            $opportunity = CommercialOpportunity::with('organization')->where('tenant_id', $tenantId)->findOrFail($billing->opportunity_id);
            $customerId = $opportunity->organization?->legacy_customer_id;
            abort_unless($customerId, 422, 'The opportunity organization must be linked to a CRM customer before invoicing.');

            $invoiceId = DB::table('invoices')->insertGetId([
                'tenant_id' => $tenantId,
                'invoice_number' => $this->nextInvoiceNumber($tenantId),
                'invoice_type' => 'invoice',
                'customer_id' => $customerId,
                'invoice_date' => now()->toDateString(),
                'due_date' => now()->addDays(30)->toDateString(),
                'notes' => $billing->instructions,
                'terms' => $billing->billing_terms,
                'salesperson' => $user->name,
                'subtotal' => $billing->amount,
                'tax' => 0,
                'total' => $billing->amount,
                'status' => 'sent',
                'commercial_opportunity_id' => $opportunity->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            DB::table('invoice_lines')->insert([
                'tenant_id' => $tenantId,
                'invoice_id' => $invoiceId,
                'description' => $opportunity->title,
                'unit_price' => $billing->amount,
                'quantity' => 1,
                'line_total' => $billing->amount,
                'created_at' => now(),
            ]);
            DB::table('commercial_billing_requests')->where('id', $billingRequestId)->update(['status' => 'Invoice Created', 'updated_at' => now()]);

            $this->generateDocument($opportunity, $user, 'Invoice', 'Invoice for ' . $opportunity->title, ['invoice_id' => $invoiceId, 'amount' => $billing->amount]);
            app(DomainEventService::class)->record('commercial.invoice.created_from_billing', 'Commercial Operations', $opportunity, ['billing_request_id' => $billingRequestId, 'invoice_id' => $invoiceId], $tenantId, $user);

            return $invoiceId;
        });
    }

    public function generateDocument(CommercialOpportunity $opportunity, User $user, string $documentType, string $title, array $metadata = []): int
    {
        $reference = sprintf('%s-%s-%05d', str($documentType)->upper()->substr(0, 3), now()->format('Y'), DB::table('commercial_generated_documents')->where('tenant_id', $opportunity->tenant_id)->where('document_type', $documentType)->count() + 1);

        return DB::table('commercial_generated_documents')->insertGetId([
            'tenant_id' => $opportunity->tenant_id,
            'opportunity_id' => $opportunity->id,
            'source_type' => CommercialOpportunity::class,
            'source_id' => $opportunity->id,
            'document_type' => $documentType,
            'reference' => $reference,
            'title' => $title,
            'status' => 'Generated',
            'content' => implode(PHP_EOL, [
                'Texaro Technologies Limited',
                $documentType,
                $title,
                'Opportunity: ' . $opportunity->reference,
                'Customer: ' . ($opportunity->organization?->legal_name ?: '-'),
                'Value: ' . $opportunity->currency . ' ' . number_format((float) $opportunity->estimated_value, 2),
            ]),
            'generated_by' => $user->id,
            'generated_at' => now(),
            'metadata' => json_encode($metadata),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function createReminder(int $tenantId, ?int $userId, string $sourceType, int $sourceId, string $type, string $title, ?string $message, mixed $dueAt): int
    {
        return DB::table('commercial_reminders')->insertGetId([
            'tenant_id' => $tenantId,
            'user_id' => $userId,
            'source_type' => $sourceType,
            'source_id' => $sourceId,
            'reminder_type' => $type,
            'title' => $title,
            'message' => $message,
            'due_at' => $dueAt,
            'status' => 'Open',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function convertRenewalToOpportunity(int $tenantId, int $renewalId, User $user): CommercialOpportunity
    {
        $renewal = DB::table('commercial_renewals')->where('tenant_id', $tenantId)->where('id', $renewalId)->first();
        abort_unless($renewal && $renewal->organization_id, 404);

        $opportunity = CommercialOpportunity::create([
            'tenant_id' => $tenantId,
            'organization_id' => $renewal->organization_id,
            'reference' => $this->numbering->next($tenantId, 'opportunity'),
            'title' => 'Renewal: ' . $renewal->reference,
            'opportunity_type' => 'Renewal',
            'current_stage' => 'Qualified',
            'probability' => 60,
            'estimated_value' => $renewal->renewal_value,
            'currency' => $renewal->currency,
            'expected_close_date' => $renewal->renewal_due_on,
            'customer_need' => $renewal->retention_plan,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);
        DB::table('commercial_renewals')->where('id', $renewalId)->update(['status' => 'Converted', 'updated_at' => now()]);
        app(DomainEventService::class)->record('commercial.renewal.converted_to_opportunity', 'Commercial Operations', $opportunity, ['renewal_id' => $renewalId], $tenantId, $user);

        return $opportunity;
    }

    public function convertExpansionToOpportunity(int $tenantId, int $expansionId, User $user): CommercialOpportunity
    {
        $expansion = DB::table('commercial_expansion_opportunities')->where('tenant_id', $tenantId)->where('id', $expansionId)->first();
        abort_unless($expansion && $expansion->organization_id, 404);

        $opportunity = CommercialOpportunity::create([
            'tenant_id' => $tenantId,
            'organization_id' => $expansion->organization_id,
            'reference' => $this->numbering->next($tenantId, 'opportunity'),
            'title' => $expansion->title,
            'opportunity_type' => $expansion->expansion_type,
            'current_stage' => 'Qualified',
            'probability' => 45,
            'estimated_value' => $expansion->estimated_value,
            'currency' => $expansion->currency,
            'customer_need' => $expansion->rationale,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);
        DB::table('commercial_expansion_opportunities')->where('id', $expansionId)->update(['status' => 'Converted', 'updated_at' => now()]);
        app(DomainEventService::class)->record('commercial.expansion.converted_to_opportunity', 'Commercial Operations', $opportunity, ['expansion_id' => $expansionId], $tenantId, $user);

        return $opportunity;
    }

    public function captureReportSnapshot(int $tenantId): int
    {
        $pipeline = DB::table('commercial_opportunities')->where('tenant_id', $tenantId)->whereNotIn('current_stage', ['Won', 'Lost']);
        $totalLeads = max(1, DB::table('commercial_leads')->where('tenant_id', $tenantId)->count());
        $converted = DB::table('commercial_leads')->where('tenant_id', $tenantId)->where('status', 'Converted')->count();

        DB::table('commercial_report_snapshots')->updateOrInsert(
            ['tenant_id' => $tenantId, 'report_date' => now()->toDateString()],
            [
                'pipeline_value' => (float) (clone $pipeline)->sum('estimated_value'),
                'weighted_pipeline_value' => (float) (clone $pipeline)->selectRaw('sum(estimated_value * probability / 100.0) as value')->value('value'),
                'won_value' => (float) DB::table('commercial_opportunities')->where('tenant_id', $tenantId)->where('current_stage', 'Won')->sum('estimated_value'),
                'open_leads' => DB::table('commercial_leads')->where('tenant_id', $tenantId)->whereNotIn('status', ['Converted', 'Lost'])->count(),
                'open_opportunities' => (clone $pipeline)->count(),
                'stale_opportunities' => DB::table('commercial_opportunities')->where('tenant_id', $tenantId)->whereNotIn('current_stage', ['Won', 'Lost'])->where(function ($query): void {
                    $query->whereNull('last_activity_date')->orWhere('last_activity_date', '<', now()->subDays(14)->toDateString());
                })->count(),
                'renewals_due' => DB::table('commercial_renewals')->where('tenant_id', $tenantId)->where('status', 'Due')->where('renewal_due_on', '<=', now()->addDays(60)->toDateString())->count(),
                'conversion_rate' => round(($converted / $totalLeads) * 100, 2),
                'metadata' => json_encode(['generated_by' => self::class]),
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        return (int) DB::table('commercial_report_snapshots')->where('tenant_id', $tenantId)->where('report_date', now()->toDateString())->value('id');
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

    private function nextInvoiceNumber(int $tenantId): string
    {
        return sprintf('INV-%s-%05d', now()->format('Y'), DB::table('invoices')->where('tenant_id', $tenantId)->where('invoice_type', 'invoice')->count() + 1);
    }
}
