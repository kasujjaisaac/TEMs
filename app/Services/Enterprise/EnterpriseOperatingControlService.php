<?php

namespace App\Services\Enterprise;

use App\Models\Commercial\CommercialBillingRequest;
use App\Models\Commercial\CommercialOpportunity;
use App\Models\DocumentRecord;
use App\Models\Finance\FinanceTransaction;
use App\Models\User;
use App\Services\Finance\FinanceControlService;
use Illuminate\Support\Facades\DB;

class EnterpriseOperatingControlService
{
    public function __construct(private readonly FinanceControlService $finance)
    {
    }

    public function verifyCommercialHandoff(CommercialOpportunity $opportunity, User $user): array
    {
        $checks = [
            'confirmed_organization' => ['Confirmed customer organization', (bool) $opportunity->organization_id],
            'approved_quotation' => ['Approved or accepted quotation', $opportunity->quotations()->whereIn('status', ['Approved', 'Accepted'])->exists()],
            'signed_contract' => ['Signed contract', $opportunity->contracts()->where('status', 'Signed')->exists()],
            'billing_requested' => ['Billing request created', $opportunity->billingRequests()->exists()],
        ];

        foreach ($checks as $key => [$label, $passed]) {
            $this->recordWorkflowCheck(
                (int) $opportunity->tenant_id,
                'Commercial Operations',
                'Commercial to Finance to Project',
                CommercialOpportunity::class,
                $opportunity->id,
                $key,
                $label,
                $passed ? 'Passed' : 'Failed',
                $user,
                ['opportunity_reference' => $opportunity->reference]
            );
        }

        $ready = collect($checks)->every(fn (array $check): bool => (bool) $check[1]);
        $opportunity->forceFill(['sales_handoff_status' => $ready ? 'Finance Ready' : 'Controls Failed'])->save();

        app(DomainEventService::class)->record(
            $ready ? 'commercial.handoff.verified' : 'commercial.handoff.blocked',
            'Commercial Operations',
            $opportunity,
            ['ready' => $ready, 'checks' => array_keys($checks)],
            (int) $opportunity->tenant_id,
            $user
        );
        app(AuditService::class)->record((int) $opportunity->tenant_id, $user, 'verified', 'commercial', 'Verified commercial handoff controls for ' . $opportunity->reference, ['ready' => $ready], $opportunity);

        return ['ready' => $ready, 'checks' => $checks];
    }

    public function reviewBillingRequest(CommercialBillingRequest $billing, User $user, string $decision = 'Approved', ?string $notes = null): FinanceTransaction
    {
        return DB::transaction(function () use ($billing, $user, $decision, $notes): FinanceTransaction {
            $status = $decision === 'Approved' ? 'Approved for Invoicing' : 'Returned for Correction';
            $billing->forceFill([
                'status' => $status,
                'approved_by' => $decision === 'Approved' ? $user->id : null,
                'approved_at' => $decision === 'Approved' ? now() : null,
            ])->save();

            DB::table('finance_control_reviews')->insert([
                'tenant_id' => $billing->tenant_id,
                'source_module' => 'Commercial Operations',
                'source_type' => 'billing_request',
                'source_id' => $billing->id,
                'decision' => $decision,
                'status' => 'Recorded',
                'amount' => $billing->amount,
                'currency' => $billing->currency ?: 'UGX',
                'reviewed_by' => $user->id,
                'reviewed_at' => now(),
                'notes' => $notes,
                'metadata' => json_encode(['billing_reference' => $billing->reference]),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $period = $this->finance->periodForDate((int) $billing->tenant_id, now());
            $transaction = FinanceTransaction::updateOrCreate(
                ['tenant_id' => $billing->tenant_id, 'source_module' => 'Commercial Operations', 'source_type' => 'billing_request', 'source_id' => $billing->id],
                [
                    'fiscal_year_id' => $period?->fiscal_year_id,
                    'period_id' => $period?->id,
                    'reference' => 'BILL-' . $billing->id,
                    'direction' => 'Inflow',
                    'amount' => $billing->amount,
                    'currency' => $billing->currency ?: 'UGX',
                    'transaction_date' => now()->toDateString(),
                    'status' => $decision === 'Approved' ? 'Ready for Invoice' : 'Review Required',
                    'approval_status' => $decision,
                    'evidence_status' => 'Commercially Verified',
                    'description' => 'Billing request ' . $billing->reference,
                    'created_by' => $user->id,
                ]
            );

            app(DomainEventService::class)->record('billing.finance_reviewed', 'Finance', $billing, [
                'decision' => $decision,
                'transaction_id' => $transaction->id,
            ], (int) $billing->tenant_id, $user);
            app(AuditService::class)->record((int) $billing->tenant_id, $user, strtolower($decision), 'finance', 'Reviewed billing request ' . $billing->reference, ['decision' => $decision], $billing);

            return $transaction;
        });
    }

    public function approvePurchaseRequest(int $tenantId, int $purchaseRequestId, User $user, ?string $notes = null): void
    {
        DB::transaction(function () use ($tenantId, $purchaseRequestId, $user, $notes): void {
            $request = DB::table('purchase_requests')->where('tenant_id', $tenantId)->where('id', $purchaseRequestId)->first();
            abort_unless($request, 404);

            DB::table('purchase_requests')->where('id', $purchaseRequestId)->update([
                'status' => 'Approved',
                'approved_by' => $user->id,
                'approved_at' => now(),
                'updated_at' => now(),
            ]);
            $this->financeReview($tenantId, 'Procurement', 'purchase_request', $purchaseRequestId, 'Approved', (float) $request->estimated_amount, $user, $notes);
            app(DomainEventService::class)->record('purchase_request.approved', 'Procurement', null, ['purchase_request_id' => $purchaseRequestId], $tenantId, $user);
        });
    }

    public function completeProjectGate(int $tenantId, int $projectId, int $milestoneId, User $user, string $evidenceSummary): void
    {
        DB::transaction(function () use ($tenantId, $projectId, $milestoneId, $user, $evidenceSummary): void {
            $project = DB::table('implementation_projects')->where('tenant_id', $tenantId)->where('id', $projectId)->first();
            $milestone = DB::table('project_milestones')->where('tenant_id', $tenantId)->where('id', $milestoneId)->where('project_id', $projectId)->first();
            abort_unless($project && $milestone, 404);

            DB::table('project_milestones')->where('id', $milestoneId)->update([
                'completed_at' => now(),
                'acceptance_status' => 'Accepted',
                'status' => 'Completed',
                'updated_at' => now(),
            ]);
            DB::table('project_delivery_gates')->insert([
                'tenant_id' => $tenantId,
                'project_id' => $projectId,
                'milestone_id' => $milestoneId,
                'gate_type' => 'Milestone Acceptance',
                'title' => $milestone->title,
                'status' => 'Verified',
                'verified_by' => $user->id,
                'verified_at' => now(),
                'evidence_summary' => $evidenceSummary,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $total = max(1, DB::table('project_milestones')->where('tenant_id', $tenantId)->where('project_id', $projectId)->count());
            $completed = DB::table('project_milestones')->where('tenant_id', $tenantId)->where('project_id', $projectId)->where('status', 'Completed')->count();
            $progress = (int) round(($completed / $total) * 100);
            DB::table('implementation_projects')->where('id', $projectId)->update([
                'progress' => $progress,
                'health_status' => $progress >= 100 ? 'Completed' : 'On Track',
                'status' => $progress >= 100 ? 'Ready for Customer Success' : 'Active',
                'updated_at' => now(),
            ]);

            app(DomainEventService::class)->record('project.milestone.accepted', 'Projects and Delivery', null, [
                'project_id' => $projectId,
                'milestone_id' => $milestoneId,
                'progress' => $progress,
            ], $tenantId, $user);
            app(AuditService::class)->record($tenantId, $user, 'verified', 'delivery', 'Verified project milestone acceptance', ['project_id' => $projectId, 'milestone_id' => $milestoneId]);
        });
    }

    public function handoverProjectToCustomerSuccess(int $tenantId, int $projectId, User $user, ?string $notes = null): int
    {
        return DB::transaction(function () use ($tenantId, $projectId, $user, $notes): int {
            $project = DB::table('implementation_projects')->where('tenant_id', $tenantId)->where('id', $projectId)->first();
            abort_unless($project, 404);

            $accountId = DB::table('customer_success_accounts')->updateOrInsert(
                ['tenant_id' => $tenantId, 'organization_id' => $project->organization_id, 'product_id' => $project->product_id],
                [
                    'owner_id' => $user->id,
                    'onboarding_status' => 'Ready',
                    'health_score' => 75,
                    'risk_level' => 'Low',
                    'success_plan' => 'Customer success handover created from project ' . $project->reference,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );

            $handoverId = DB::table('customer_success_handovers')->insertGetId([
                'tenant_id' => $tenantId,
                'organization_id' => $project->organization_id,
                'project_id' => $projectId,
                'product_id' => $project->product_id,
                'owner_id' => $user->id,
                'status' => 'Completed',
                'onboarding_status' => 'Ready',
                'health_score' => 75,
                'risk_level' => 'Low',
                'handover_notes' => $notes,
                'handover_at' => now(),
                'metadata' => json_encode(['customer_success_account_upserted' => $accountId]),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('implementation_projects')->where('id', $projectId)->update(['status' => 'Handed to Customer Success', 'updated_at' => now()]);
            app(DomainEventService::class)->record('customer_success.handover.completed', 'Customer Success', null, ['project_id' => $projectId, 'handover_id' => $handoverId], $tenantId, $user);

            return $handoverId;
        });
    }

    public function generateDocument(int $tenantId, User $user, string $module, string $documentType, ?object $source, string $title, array $metadata = []): int
    {
        return DB::transaction(function () use ($tenantId, $user, $module, $documentType, $source, $title, $metadata): int {
            $reference = sprintf('%s-%s-%05d', str($documentType)->upper()->substr(0, 3), now()->format('Y'), DB::table('enterprise_generated_documents')->where('tenant_id', $tenantId)->where('document_type', $documentType)->count() + 1);
            $document = DocumentRecord::create([
                'tenant_id' => $tenantId,
                'module' => $module,
                'document_type' => $documentType,
                'reference' => $reference,
                'title' => $title,
                'status' => 'Generated',
                'subject_type' => $source ? $source::class : null,
                'subject_id' => $source->id ?? null,
                'owner_id' => $user->id,
                'metadata' => $metadata,
            ]);

            $id = DB::table('enterprise_generated_documents')->insertGetId([
                'tenant_id' => $tenantId,
                'document_record_id' => $document->id,
                'module' => $module,
                'document_type' => $documentType,
                'source_type' => $source ? $source::class : null,
                'source_id' => $source->id ?? null,
                'reference' => $reference,
                'title' => $title,
                'status' => 'Generated',
                'generated_by' => $user->id,
                'generated_at' => now(),
                'content' => $this->documentContent($module, $documentType, $title, $metadata),
                'metadata' => json_encode($metadata),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            app(DomainEventService::class)->record('document.generated', $module, $document, ['generated_document_id' => $id], $tenantId, $user);

            return $id;
        });
    }

    public function captureCompanyScorecard(int $tenantId): int
    {
        $revenue = (float) DB::table('finance_transactions')->where('tenant_id', $tenantId)->where('direction', 'Inflow')->sum('amount');
        $pipeline = (float) DB::table('commercial_opportunities')->where('tenant_id', $tenantId)->whereNotIn('current_stage', ['Won', 'Lost'])->sum('estimated_value');
        $budget = (float) DB::table('finance_budget_lines')->where('tenant_id', $tenantId)->sum('annual_budget');
        $spend = (float) DB::table('finance_transactions')->where('tenant_id', $tenantId)->where('direction', 'Outflow')->sum('amount');
        $projectCompletion = (float) DB::table('implementation_projects')->where('tenant_id', $tenantId)->avg('progress');
        $customerHealth = (int) round((float) DB::table('customer_success_accounts')->where('tenant_id', $tenantId)->avg('health_score'));
        $verifiedEvidence = DB::table('workplan_evidence')->where('tenant_id', $tenantId)->where('status', 'Verified')->count();
        $risks = DB::table('intelligence_signals')->where('tenant_id', $tenantId)->where('status', 'Open')->whereIn('severity', ['High', 'Critical'])->count()
            + DB::table('corporate_risks')->where('tenant_id', $tenantId)->where('status', 'Open')->whereIn('risk_level', ['High', 'Critical'])->count();

        $budgetUtilization = $budget > 0 ? round(($spend / $budget) * 100, 2) : 0;
        $health = max(1, min(100, (int) round(60 + min(20, $verifiedEvidence * 2) + min(10, $revenue / 1000000) - min(25, $risks * 5))));

        DB::table('enterprise_scorecard_snapshots')->updateOrInsert(
            ['tenant_id' => $tenantId, 'scope' => 'Company', 'scope_id' => null, 'scorecard_date' => now()->toDateString()],
            [
                'health_score' => $health,
                'revenue_amount' => $revenue,
                'pipeline_amount' => $pipeline,
                'budget_utilization' => $budgetUtilization,
                'project_completion' => $projectCompletion ?: 0,
                'customer_health' => $customerHealth ?: 50,
                'verified_evidence_count' => $verifiedEvidence,
                'risk_count' => $risks,
                'metadata' => json_encode(['generated_by' => self::class]),
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        $id = (int) DB::table('enterprise_scorecard_snapshots')
            ->where('tenant_id', $tenantId)
            ->where('scope', 'Company')
            ->whereNull('scope_id')
            ->where('scorecard_date', now()->toDateString())
            ->value('id');

        app(DomainEventService::class)->record('scorecard.company.captured', 'Enterprise Intelligence', null, ['scorecard_id' => $id, 'health_score' => $health], $tenantId, null);

        return $id;
    }

    private function recordWorkflowCheck(int $tenantId, string $module, string $workflow, string $sourceType, int $sourceId, string $key, string $label, string $status, User $user, array $metadata = []): void
    {
        DB::table('enterprise_workflow_checks')->updateOrInsert(
            ['tenant_id' => $tenantId, 'workflow' => $workflow, 'source_type' => $sourceType, 'source_id' => $sourceId, 'control_key' => $key],
            [
                'module' => $module,
                'control_label' => $label,
                'status' => $status,
                'checked_by' => $user->id,
                'checked_at' => now(),
                'metadata' => json_encode($metadata),
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }

    private function financeReview(int $tenantId, string $module, string $sourceType, int $sourceId, string $decision, float $amount, User $user, ?string $notes): void
    {
        DB::table('finance_control_reviews')->insert([
            'tenant_id' => $tenantId,
            'source_module' => $module,
            'source_type' => $sourceType,
            'source_id' => $sourceId,
            'decision' => $decision,
            'status' => 'Recorded',
            'amount' => $amount,
            'currency' => 'UGX',
            'reviewed_by' => $user->id,
            'reviewed_at' => now(),
            'notes' => $notes,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function documentContent(string $module, string $documentType, string $title, array $metadata): string
    {
        $lines = [
            'Texaro Technologies Limited',
            $documentType,
            $title,
            'Module: ' . $module,
            'Generated at: ' . now()->toDateTimeString(),
        ];

        foreach ($metadata as $key => $value) {
            $lines[] = str($key)->replace('_', ' ')->title() . ': ' . (is_scalar($value) ? $value : json_encode($value));
        }

        return implode(PHP_EOL, $lines);
    }
}
