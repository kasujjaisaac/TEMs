<?php

namespace App\Services\CRM;

use App\Models\Commercial\CommercialOpportunity;
use App\Models\User;
use App\Services\Enterprise\AuditService;
use App\Services\Enterprise\DomainEventService;
use Illuminate\Support\Facades\DB;

class CustomerAccountLifecycleService
{
    public function upsertAccountPlan(int $tenantId, int $customerId, User $user, array $data): void
    {
        $customer = DB::table('customers')->where('tenant_id', $tenantId)->where('id', $customerId)->first();
        abort_unless($customer, 404);

        DB::table('crm_account_plans')->updateOrInsert(
            ['tenant_id' => $tenantId, 'customer_id' => $customerId],
            [
                'commercial_organization_id' => $customer->commercial_organization_id ?? null,
                'owner_id' => $data['owner_id'] ?? $user->id,
                'relationship_stage' => $data['relationship_stage'] ?? 'Active',
                'objectives' => $data['objectives'] ?? null,
                'growth_strategy' => $data['growth_strategy'] ?? null,
                'retention_strategy' => $data['retention_strategy'] ?? null,
                'health_status' => $data['health_status'] ?? 'Stable',
                'risk_level' => $data['risk_level'] ?? 'Medium',
                'next_review_on' => $data['next_review_on'] ?? now()->addMonth()->toDateString(),
                'status' => $data['status'] ?? 'Active',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        $this->timeline($tenantId, $customerId, $customer->commercial_organization_id ?? null, 'Account Plan', 'Account plan updated', $data['objectives'] ?? null, 'CRM', 'crm_account_plans', $customerId, $user);
        app(DomainEventService::class)->record('crm.account_plan.updated', 'CRM', null, ['customer_id' => $customerId], $tenantId, $user);
        app(AuditService::class)->record($tenantId, $user, 'updated', 'crm', 'Updated CRM account plan', ['customer_id' => $customerId]);
    }

    public function captureHealth(int $tenantId, int $customerId, ?User $user = null): int
    {
        $customer = DB::table('customers')->where('tenant_id', $tenantId)->where('id', $customerId)->first();
        abort_unless($customer, 404);

        $organizationId = $customer->commercial_organization_id ?? DB::table('commercial_organizations')
            ->where('tenant_id', $tenantId)
            ->where('legacy_customer_id', $customerId)
            ->value('id');

        $openPipeline = $organizationId ? (float) DB::table('commercial_opportunities')
            ->where('tenant_id', $tenantId)
            ->where('organization_id', $organizationId)
            ->whereNotIn('current_stage', ['Won', 'Lost'])
            ->sum('estimated_value') : 0;
        $activeOpportunities = $organizationId ? DB::table('commercial_opportunities')
            ->where('tenant_id', $tenantId)
            ->where('organization_id', $organizationId)
            ->whereNotIn('current_stage', ['Won', 'Lost'])
            ->count() : 0;
        $revenue = (float) DB::table('invoices')->where('tenant_id', $tenantId)->where('customer_id', $customerId)->whereIn('status', ['paid', 'sent', 'invoice', 'completed'])->sum('total');
        $openTickets = DB::table('support_tickets')->where('tenant_id', $tenantId)->where('organization_id', $organizationId)->whereNotIn('status', ['Resolved', 'Closed'])->count();
        $overdueInvoices = DB::table('invoices')->where('tenant_id', $tenantId)->where('customer_id', $customerId)->whereNotIn('status', ['paid', 'cancelled'])->where('due_date', '<', now()->toDateString())->count();

        $score = max(1, min(100, 65 + min(15, $activeOpportunities * 5) + min(10, $revenue / 1000000) - min(30, ($openTickets * 8) + ($overdueInvoices * 10))));
        $risk = $score >= 75 ? 'Low' : ($score >= 50 ? 'Medium' : 'High');
        $status = $score >= 75 ? 'Healthy' : ($score >= 50 ? 'Stable' : 'At Risk');

        DB::table('crm_customer_health_snapshots')->updateOrInsert(
            ['tenant_id' => $tenantId, 'customer_id' => $customerId, 'snapshot_date' => now()->toDateString()],
            [
                'commercial_organization_id' => $organizationId,
                'health_score' => (int) round($score),
                'health_status' => $status,
                'risk_level' => $risk,
                'open_pipeline_value' => $openPipeline,
                'lifetime_revenue' => $revenue,
                'open_ticket_count' => $openTickets,
                'active_opportunity_count' => $activeOpportunities,
                'metadata' => json_encode(['overdue_invoice_count' => $overdueInvoices]),
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        $id = (int) DB::table('crm_customer_health_snapshots')
            ->where('tenant_id', $tenantId)
            ->where('customer_id', $customerId)
            ->where('snapshot_date', now()->toDateString())
            ->value('id');

        $this->timeline($tenantId, $customerId, $organizationId, 'Health Snapshot', 'Customer health captured', $status . ' (' . (int) round($score) . ')', 'CRM', 'crm_customer_health_snapshots', $id, $user);
        app(DomainEventService::class)->record('crm.customer_health.captured', 'CRM', null, ['customer_id' => $customerId, 'health_score' => (int) round($score)], $tenantId, $user);

        return $id;
    }

    public function addBranch(int $tenantId, int $customerId, User $user, array $data): int
    {
        $customer = DB::table('customers')->where('tenant_id', $tenantId)->where('id', $customerId)->first();
        abort_unless($customer, 404);

        $id = DB::table('crm_customer_branches')->insertGetId([
            'tenant_id' => $tenantId,
            'customer_id' => $customerId,
            'commercial_organization_id' => $customer->commercial_organization_id ?? null,
            'name' => $data['name'],
            'branch_type' => $data['branch_type'] ?? 'Branch',
            'city' => $data['city'] ?? null,
            'country' => $data['country'] ?? null,
            'address' => $data['address'] ?? null,
            'contact_person' => $data['contact_person'] ?? null,
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'] ?? null,
            'is_primary' => (bool) ($data['is_primary'] ?? false),
            'status' => $data['status'] ?? 'Active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->timeline($tenantId, $customerId, $customer->commercial_organization_id ?? null, 'Branch', 'Customer branch registered: ' . $data['name'], $data['address'] ?? null, 'CRM', 'crm_customer_branches', $id, $user);

        return $id;
    }

    public function addDocument(int $tenantId, int $customerId, User $user, array $data): int
    {
        $customer = DB::table('customers')->where('tenant_id', $tenantId)->where('id', $customerId)->first();
        abort_unless($customer, 404);

        $id = DB::table('crm_customer_documents')->insertGetId([
            'tenant_id' => $tenantId,
            'customer_id' => $customerId,
            'document_type' => $data['document_type'],
            'title' => $data['title'],
            'reference' => $data['reference'] ?? null,
            'status' => $data['status'] ?? 'Current',
            'expires_on' => $data['expires_on'] ?? null,
            'storage_path' => $data['storage_path'] ?? null,
            'uploaded_by' => $user->id,
            'metadata' => json_encode(['notes' => $data['notes'] ?? null]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->timeline($tenantId, $customerId, $customer->commercial_organization_id ?? null, 'Document', 'Customer document registered: ' . $data['title'], $data['document_type'], 'CRM', 'crm_customer_documents', $id, $user);

        return $id;
    }

    public function addSubscription(int $tenantId, int $customerId, User $user, array $data): int
    {
        $customer = DB::table('customers')->where('tenant_id', $tenantId)->where('id', $customerId)->first();
        abort_unless($customer, 404);

        $id = DB::table('crm_customer_subscriptions')->insertGetId([
            'tenant_id' => $tenantId,
            'customer_id' => $customerId,
            'commercial_organization_id' => $customer->commercial_organization_id ?? null,
            'product_id' => $data['product_id'] ?? null,
            'product_name' => $data['product_name'],
            'plan_name' => $data['plan_name'] ?? null,
            'starts_on' => $data['starts_on'] ?? now()->toDateString(),
            'renews_on' => $data['renews_on'] ?? null,
            'recurring_amount' => $data['recurring_amount'] ?? 0,
            'currency' => $data['currency'] ?? 'UGX',
            'billing_frequency' => $data['billing_frequency'] ?? null,
            'status' => $data['status'] ?? 'Active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->timeline($tenantId, $customerId, $customer->commercial_organization_id ?? null, 'Subscription', 'Customer subscription registered: ' . $data['product_name'], $data['plan_name'] ?? null, 'CRM', 'crm_customer_subscriptions', $id, $user);

        return $id;
    }

    public function timeline(int $tenantId, int $customerId, ?int $organizationId, string $eventType, string $title, ?string $description, string $sourceModule, ?string $sourceType, ?int $sourceId, ?User $user): int
    {
        return DB::table('crm_account_timeline')->insertGetId([
            'tenant_id' => $tenantId,
            'customer_id' => $customerId,
            'commercial_organization_id' => $organizationId,
            'event_type' => $eventType,
            'title' => $title,
            'description' => $description,
            'source_module' => $sourceModule,
            'source_type' => $sourceType,
            'source_id' => $sourceId,
            'actor_id' => $user?->id,
            'occurred_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function recordOpportunityTimeline(CommercialOpportunity $opportunity, User $user, string $eventType, string $title, ?string $description = null): void
    {
        $customerId = DB::table('commercial_organizations')->where('tenant_id', $opportunity->tenant_id)->where('id', $opportunity->organization_id)->value('legacy_customer_id');
        if (! $customerId) {
            return;
        }

        $this->timeline((int) $opportunity->tenant_id, (int) $customerId, (int) $opportunity->organization_id, $eventType, $title, $description, 'Commercial Operations', CommercialOpportunity::class, $opportunity->id, $user);
    }
}
