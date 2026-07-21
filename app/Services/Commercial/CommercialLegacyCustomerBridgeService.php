<?php

namespace App\Services\Commercial;

use App\Models\Commercial\CommercialLead;
use App\Models\Commercial\CommercialOpportunity;
use App\Models\Commercial\CommercialOrganization;
use App\Models\User;
use App\Services\CRM\CustomerIdentityService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CommercialLegacyCustomerBridgeService
{
    public function syncOrganization(CommercialOrganization $organization, ?User $user = null): int
    {
        $organization->loadMissing(['accountManager', 'stakeholders']);
        $tenantId = (int) $organization->tenant_id;
        $primaryStakeholder = $organization->stakeholders->firstWhere('is_primary_contact', true) ?: $organization->stakeholders->first();
        $customerId = $this->resolveCustomerId($organization);

        $payload = [
            'tenant_id' => $tenantId,
            'commercial_organization_id' => $organization->id,
            'commercial_reference' => $organization->reference,
            'name' => $organization->trading_name ?: $organization->legal_name,
            'company_name' => $organization->legal_name,
            'contact_person' => $primaryStakeholder?->full_name,
            'customer_type' => $organization->organization_type ?: 'company',
            'email' => $organization->primary_email ?: $primaryStakeholder?->email,
            'phone' => $organization->primary_telephone ?: $primaryStakeholder?->telephone,
            'address' => $organization->physical_address,
            'billing_address' => $organization->postal_address ?: $organization->physical_address,
            'city' => $organization->city,
            'country' => $organization->country,
            'tin_number' => $organization->tin,
            'customer_group' => $organization->customer_category ?: 'Commercial',
            'payment_terms' => $organization->payment_terms ?: 'Net 30',
            'credit_status' => $organization->credit_status ?: 'pending_review',
            'account_manager' => $organization->accountManager?->name ?: $user?->name,
            'customer_source' => $organization->acquisition_source ?: 'Commercial Operations',
            'commercial_sync_status' => 'Synced',
            'commercial_synced_at' => now(),
            'internal_notes' => trim(implode("\n", array_filter([
                $organization->notes,
                'Linked to commercial organization ' . $organization->reference . '.',
            ]))),
            'is_active' => $organization->customer_status !== 'Inactive',
            'updated_at' => now(),
        ];

        $payload = $this->filterColumns('customers', $payload);

        if ($customerId > 0) {
            DB::table('customers')->where('tenant_id', $tenantId)->where('id', $customerId)->update($payload);
        } else {
            $payload['customer_code'] = $this->nextCustomerCode($tenantId);
            $payload['credit_limit'] = 0;
            $payload['credit_balance'] = 0;
            $payload['created_at'] = now();
            $customerId = (int) DB::table('customers')->insertGetId($this->filterColumns('customers', $payload));
        }

        if ((int) $organization->legacy_customer_id !== $customerId) {
            $organization->forceFill([
                'legacy_customer_id' => $customerId,
                'customer_status' => str_contains(strtolower($organization->customer_status), 'customer') ? $organization->customer_status : 'Active Customer',
                'updated_by' => $user?->id,
            ])->save();
        }

        if (Schema::hasTable('customer_identity_links')) {
            app(CustomerIdentityService::class)->linkSource(
                $tenantId,
                $customerId,
                'commercial_organizations',
                (int) $organization->id,
                $organization->reference,
                $customerId === (int) $organization->legacy_customer_id ? 'explicit' : 'matched',
                $user,
                ['commercial_sync_status' => 'Synced']
            );
        }

        return $customerId;
    }

    public function syncLead(CommercialLead $lead, ?User $user = null): ?int
    {
        if (! Schema::hasTable('crm_leads') || ! Schema::hasColumn('crm_leads', 'commercial_lead_id')) {
            return null;
        }

        $lead->loadMissing(['assignedEmployee', 'organization']);
        $tenantId = (int) $lead->tenant_id;
        $customerId = $lead->organization?->legacy_customer_id ?: null;
        $existingId = (int) DB::table('crm_leads')->where('tenant_id', $tenantId)->where('commercial_lead_id', $lead->id)->value('id');
        $payload = [
            'tenant_id' => $tenantId,
            'customer_id' => $customerId,
            'commercial_lead_id' => $lead->id,
            'contact_name' => $lead->contact_person ?: $lead->organization_name,
            'company_name' => $lead->organization_name,
            'phone' => $lead->telephone,
            'email' => $lead->email,
            'source' => $lead->lead_source ?: 'commercial_operations',
            'status' => $this->legacyLeadStatus($lead->status),
            'priority' => $this->legacyPriority($lead->temperature),
            'assigned_to' => $lead->assignedEmployee?->name ?: $user?->name,
            'estimated_value' => $lead->estimated_budget ?: 0,
            'expected_close_date' => $lead->expected_decision_date,
            'converted_customer_id' => $lead->status === 'Converted' ? $customerId : null,
            'notes' => $lead->requirements_summary,
            'updated_at' => now(),
        ];

        if ($existingId > 0) {
            DB::table('crm_leads')->where('id', $existingId)->where('tenant_id', $tenantId)->update($this->filterColumns('crm_leads', $payload));
            return $existingId;
        }

        $payload['created_at'] = now();

        return (int) DB::table('crm_leads')->insertGetId($this->filterColumns('crm_leads', $payload));
    }

    public function syncOpportunity(CommercialOpportunity $opportunity, ?User $user = null): ?int
    {
        if (! Schema::hasTable('crm_opportunities') || ! Schema::hasColumn('crm_opportunities', 'commercial_opportunity_id')) {
            return null;
        }

        $opportunity->loadMissing(['organization', 'assignedEmployee', 'lead']);
        $tenantId = (int) $opportunity->tenant_id;
        $customerId = $opportunity->organization?->legacy_customer_id
            ?: ($opportunity->organization ? $this->syncOrganization($opportunity->organization, $user) : null);
        $crmLeadId = $opportunity->lead_id
            ? (int) DB::table('crm_leads')->where('tenant_id', $tenantId)->where('commercial_lead_id', $opportunity->lead_id)->value('id')
            : null;
        $existingId = (int) DB::table('crm_opportunities')->where('tenant_id', $tenantId)->where('commercial_opportunity_id', $opportunity->id)->value('id');
        $stage = $this->legacyOpportunityStage($opportunity->current_stage);
        $payload = [
            'tenant_id' => $tenantId,
            'lead_id' => $crmLeadId ?: null,
            'customer_id' => $customerId,
            'commercial_opportunity_id' => $opportunity->id,
            'title' => $opportunity->title,
            'stage' => $stage,
            'value' => $opportunity->estimated_value ?: 0,
            'probability' => $opportunity->probability ?: 0,
            'expected_close_date' => $opportunity->expected_close_date,
            'owner' => $opportunity->assignedEmployee?->name ?: $user?->name,
            'status' => in_array($stage, ['won', 'lost'], true) ? $stage : 'open',
            'notes' => trim(implode("\n", array_filter([$opportunity->customer_need, $opportunity->proposed_solution]))),
            'updated_at' => now(),
        ];

        if ($existingId > 0) {
            DB::table('crm_opportunities')->where('id', $existingId)->where('tenant_id', $tenantId)->update($this->filterColumns('crm_opportunities', $payload));
            return $existingId;
        }

        $payload['created_at'] = now();

        return (int) DB::table('crm_opportunities')->insertGetId($this->filterColumns('crm_opportunities', $payload));
    }

    private function resolveCustomerId(CommercialOrganization $organization): int
    {
        $tenantId = (int) $organization->tenant_id;
        if ($organization->legacy_customer_id && DB::table('customers')->where('tenant_id', $tenantId)->where('id', $organization->legacy_customer_id)->exists()) {
            return (int) $organization->legacy_customer_id;
        }

        if (Schema::hasColumn('customers', 'commercial_organization_id')) {
            $id = (int) DB::table('customers')->where('tenant_id', $tenantId)->where('commercial_organization_id', $organization->id)->value('id');
            if ($id > 0) {
                return $id;
            }
        }

        if ($organization->primary_email) {
            $id = (int) DB::table('customers')->where('tenant_id', $tenantId)->where('email', $organization->primary_email)->value('id');
            if ($id > 0) {
                return $id;
            }
        }

        if ($organization->tin) {
            $id = (int) DB::table('customers')->where('tenant_id', $tenantId)->where('tin_number', $organization->tin)->value('id');
            if ($id > 0) {
                return $id;
            }
        }

        return 0;
    }

    private function nextCustomerCode(int $tenantId): string
    {
        return sprintf('CUS-%05d', DB::table('customers')->where('tenant_id', $tenantId)->count() + 1);
    }

    private function filterColumns(string $table, array $payload): array
    {
        return array_filter(
            $payload,
            fn (string $column): bool => Schema::hasColumn($table, $column),
            ARRAY_FILTER_USE_KEY
        );
    }

    private function legacyLeadStatus(?string $status): string
    {
        return match (strtolower((string) $status)) {
            'converted' => 'converted',
            'qualified' => 'qualified',
            'engaged', 'contacted' => 'contacted',
            'lost', 'disqualified' => 'lost',
            default => 'new',
        };
    }

    private function legacyPriority(?string $temperature): string
    {
        return match (strtolower((string) $temperature)) {
            'hot' => 'high',
            'cold' => 'low',
            default => 'normal',
        };
    }

    private function legacyOpportunityStage(?string $stage): string
    {
        $normalized = strtolower((string) $stage);

        return match (true) {
            str_contains($normalized, 'proposal') => 'proposal',
            str_contains($normalized, 'negotiation') => 'negotiation',
            str_contains($normalized, 'won') => 'won',
            str_contains($normalized, 'lost') => 'lost',
            default => 'qualification',
        };
    }
}
