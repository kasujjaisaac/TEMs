<?php

namespace App\Services\Enterprise;

use Illuminate\Support\Facades\DB;

class EnterpriseExpansionService
{
    public function bootstrap(int $tenantId): void
    {
        $this->ensureProduct($tenantId);
        $this->ensureProject($tenantId);
        $this->ensureCustomerSuccess($tenantId);
        $this->ensureGovernance($tenantId);
    }

    public function metrics(int $tenantId): array
    {
        return [
            'products' => DB::table('products_portfolio')->where('tenant_id', $tenantId)->count(),
            'active_projects' => DB::table('implementation_projects')->where('tenant_id', $tenantId)->whereNotIn('status', ['Closed', 'Cancelled'])->count(),
            'open_tickets' => DB::table('support_tickets')->where('tenant_id', $tenantId)->whereNotIn('status', ['Resolved', 'Closed'])->count(),
            'customer_risks' => DB::table('customer_success_accounts')->where('tenant_id', $tenantId)->whereIn('risk_level', ['High', 'Critical'])->count(),
            'compliance_open' => DB::table('compliance_obligations')->where('tenant_id', $tenantId)->whereNotIn('status', ['Completed', 'Cancelled'])->count(),
            'board_actions_open' => DB::table('board_governance_actions')->where('tenant_id', $tenantId)->whereNotIn('status', ['Completed', 'Cancelled'])->count(),
            'critical_signals' => DB::table('intelligence_signals')->where('tenant_id', $tenantId)->whereIn('severity', ['High', 'Critical'])->where('status', 'Open')->count(),
            'recommendations' => DB::table('intelligence_recommendations')->where('tenant_id', $tenantId)->where('status', 'Open')->count(),
        ];
    }

    public function refreshIntelligence(int $tenantId): void
    {
        $this->bootstrap($tenantId);
        $now = now();
        foreach ($this->metrics($tenantId) as $key => $value) {
            DB::table('intelligence_metric_snapshots')->insert([
                'tenant_id' => $tenantId,
                'metric_key' => $key,
                'metric_name' => str($key)->replace('_', ' ')->title()->toString(),
                'metric_value' => $value,
                'unit' => 'count',
                'source_module' => 'Enterprise Intelligence',
                'captured_at' => $now,
                'metadata' => json_encode(['generated_by' => 'EnterpriseExpansionService']),
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $this->signal($tenantId, 'Project Risk', 'High', 'Project delivery attention required', 'Projects need milestone and acceptance discipline.', 'Projects and Delivery', ['active_projects' => $this->metrics($tenantId)['active_projects']]);
        if ($this->metrics($tenantId)['open_tickets'] > 0) {
            $this->signal($tenantId, 'Customer Risk', 'Medium', 'Support queue requires monitoring', 'Open customer tickets should be reviewed before SLA risk grows.', 'Customer Success', ['open_tickets' => $this->metrics($tenantId)['open_tickets']]);
        }
        if ($this->metrics($tenantId)['compliance_open'] > 0) {
            $this->recommend($tenantId, 'High', 'Review open compliance obligations', 'Assign owners and dates to every open compliance obligation before executive review.', 'Governance');
        }
    }

    public function createProjectFromOpportunity(int $tenantId, int $opportunityId, ?int $userId = null): int
    {
        $opportunity = DB::table('commercial_opportunities')->where('tenant_id', $tenantId)->where('id', $opportunityId)->first();
        abort_unless($opportunity, 404);

        $projectId = DB::table('implementation_projects')->insertGetId([
            'tenant_id' => $tenantId,
            'organization_id' => $opportunity->organization_id,
            'opportunity_id' => $opportunity->id,
            'reference' => $this->nextReference($tenantId, 'implementation_projects', 'PRJ'),
            'name' => $opportunity->title . ' Implementation',
            'scope' => $opportunity->proposed_solution ?: $opportunity->customer_need,
            'project_manager_id' => $userId,
            'starts_on' => now()->toDateString(),
            'due_on' => now()->addMonth()->toDateString(),
            'budget' => $opportunity->estimated_value,
            'health_status' => 'On Track',
            'status' => 'Initiated',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('project_milestones')->insert([
            'tenant_id' => $tenantId,
            'project_id' => $projectId,
            'title' => 'Kickoff and requirements confirmation',
            'due_on' => now()->addWeek()->toDateString(),
            'acceptance_status' => 'Pending',
            'status' => 'Open',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        app(DomainEventService::class)->record('project.created_from_opportunity', 'Projects and Delivery', null, [
            'opportunity_id' => $opportunity->id,
            'project_id' => $projectId,
        ], $tenantId, auth()->user());

        return $projectId;
    }

    public function nextReference(int $tenantId, string $table, string $prefix): string
    {
        $next = DB::table($table)->where('tenant_id', $tenantId)->count() + 1;

        return sprintf('%s-%s-%05d', $prefix, now()->format('Y'), $next);
    }

    private function ensureProduct(int $tenantId): void
    {
        if (DB::table('products_portfolio')->where('tenant_id', $tenantId)->exists()) {
            return;
        }

        DB::table('products_portfolio')->insert([
            'tenant_id' => $tenantId,
            'reference' => 'PROD-' . now()->format('Y') . '-00001',
            'name' => 'Texaro Enterprise Management System',
            'category' => 'Enterprise Software',
            'lifecycle_stage' => 'Active',
            'description' => 'Integrated operating system for strategy, commercial, finance, delivery and intelligence.',
            'health_score' => 72,
            'status' => 'Active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function ensureProject(int $tenantId): void
    {
        if (DB::table('implementation_projects')->where('tenant_id', $tenantId)->exists()) {
            return;
        }

        $productId = DB::table('products_portfolio')->where('tenant_id', $tenantId)->value('id');
        DB::table('implementation_projects')->insert([
            'tenant_id' => $tenantId,
            'product_id' => $productId,
            'reference' => 'PRJ-' . now()->format('Y') . '-00001',
            'name' => 'Internal TEMS rollout',
            'scope' => 'Stabilize and expand TEMS into the enterprise operating platform.',
            'starts_on' => now()->toDateString(),
            'due_on' => now()->addMonths(3)->toDateString(),
            'progress' => 35,
            'health_status' => 'On Track',
            'status' => 'Active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function ensureCustomerSuccess(int $tenantId): void
    {
        if (DB::table('support_tickets')->where('tenant_id', $tenantId)->exists()) {
            return;
        }

        DB::table('support_tickets')->insert([
            'tenant_id' => $tenantId,
            'reference' => 'SUP-' . now()->format('Y') . '-00001',
            'subject' => 'Prepare customer success onboarding templates',
            'description' => 'Create the first onboarding and support readiness workflow.',
            'priority' => 'Medium',
            'status' => 'Open',
            'sla_due_at' => now()->addDays(3),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function ensureGovernance(int $tenantId): void
    {
        if (! DB::table('compliance_obligations')->where('tenant_id', $tenantId)->exists()) {
            DB::table('compliance_obligations')->insert([
                'tenant_id' => $tenantId,
                'reference' => 'CMP-' . now()->format('Y') . '-00001',
                'title' => 'Maintain enterprise audit readiness',
                'category' => 'Internal Control',
                'due_on' => now()->addMonth()->toDateString(),
                'risk_level' => 'High',
                'status' => 'Open',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        if (! DB::table('board_governance_actions')->where('tenant_id', $tenantId)->exists()) {
            DB::table('board_governance_actions')->insert([
                'tenant_id' => $tenantId,
                'reference' => 'GOV-' . now()->format('Y') . '-00001',
                'title' => 'Review TEMS operating system rollout progress',
                'source_meeting' => 'Executive Review',
                'due_on' => now()->addWeeks(2)->toDateString(),
                'status' => 'Open',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    private function signal(int $tenantId, string $type, string $severity, string $title, string $message, string $module, array $metadata = []): void
    {
        DB::table('intelligence_signals')->updateOrInsert(
            ['tenant_id' => $tenantId, 'title' => $title, 'source_module' => $module, 'status' => 'Open'],
            [
                'signal_type' => $type,
                'severity' => $severity,
                'message' => $message,
                'metadata' => json_encode($metadata),
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }

    private function recommend(int $tenantId, string $priority, string $title, string $recommendation, string $module): void
    {
        DB::table('intelligence_recommendations')->updateOrInsert(
            ['tenant_id' => $tenantId, 'title' => $title, 'status' => 'Open'],
            [
                'priority' => $priority,
                'recommendation' => $recommendation,
                'source_module' => $module,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }
}
