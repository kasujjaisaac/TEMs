<?php

namespace App\Services\CRM;

use App\Models\CustomerIdentityLink;
use App\Models\User;
use App\Services\Enterprise\DomainEventService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CustomerIdentityService
{
    public function linkSource(int $tenantId, int $customerId, string $sourceTable, int $sourceId, ?string $sourceReference = null, string $matchMethod = 'explicit', ?User $actor = null, array $metadata = []): CustomerIdentityLink
    {
        $link = CustomerIdentityLink::updateOrCreate(
            ['tenant_id' => $tenantId, 'source_table' => $sourceTable, 'source_id' => $sourceId],
            [
                'customer_id' => $customerId,
                'source_reference' => $sourceReference,
                'link_type' => 'canonical',
                'match_method' => $matchMethod,
                'confidence' => $matchMethod === 'explicit' ? 100 : 85,
                'status' => 'Active',
                'linked_at' => now(),
                'metadata' => $metadata,
            ]
        );

        if (Schema::hasTable('customers')) {
            DB::table('customers')
                ->where('tenant_id', $tenantId)
                ->where('id', $customerId)
                ->update(array_filter([
                    'enterprise_identity_status' => Schema::hasColumn('customers', 'enterprise_identity_status') ? 'Canonical' : null,
                    'source_of_truth' => Schema::hasColumn('customers', 'source_of_truth') ? 'CRM Customer Account' : null,
                    'updated_at' => now(),
                ], fn ($value) => $value !== null));
        }

        app(DomainEventService::class)->record('customer.identity.linked', 'CRM and Customer Accounts', $link, [
            'customer_id' => $customerId,
            'source_table' => $sourceTable,
            'source_id' => $sourceId,
            'match_method' => $matchMethod,
        ], $tenantId, $actor);

        return $link;
    }

    public function conflictCount(int $tenantId): int
    {
        if (! Schema::hasTable('customer_identity_links')) {
            return 0;
        }

        return DB::table('customer_identity_links')
            ->where('tenant_id', $tenantId)
            ->where('status', 'Conflict')
            ->count();
    }
}
