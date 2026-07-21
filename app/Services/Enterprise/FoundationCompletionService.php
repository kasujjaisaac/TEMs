<?php

namespace App\Services\Enterprise;

use App\Models\User;
use Illuminate\Support\Facades\DB;

class FoundationCompletionService
{
    public function nextDocumentNumber(int $tenantId, string $type, string $prefix): string
    {
        $year = (int) now()->format('Y');

        return DB::transaction(function () use ($tenantId, $type, $prefix, $year): string {
            $sequence = DB::table('document_number_sequences')
                ->where('tenant_id', $tenantId)
                ->where('document_type', $type)
                ->where('year', $year)
                ->lockForUpdate()
                ->first();

            if (! $sequence) {
                DB::table('document_number_sequences')->insert([
                    'tenant_id' => $tenantId,
                    'document_type' => $type,
                    'prefix' => $prefix,
                    'year' => $year,
                    'next_number' => 2,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $number = 1;
            } else {
                $number = (int) $sequence->next_number;
                DB::table('document_number_sequences')->where('id', $sequence->id)->update(['next_number' => $number + 1, 'updated_at' => now()]);
            }

            return sprintf('%s-%d-%05d', $prefix, $year, $number);
        });
    }

    public function createEmployeeProfile(int $tenantId, User $actor, array $data): int
    {
        $id = DB::table('employee_profiles')->insertGetId([
            'tenant_id' => $tenantId,
            'user_id' => $data['user_id'] ?? null,
            'department_id' => $data['department_id'] ?? null,
            'position_id' => $data['position_id'] ?? null,
            'employee_number' => ($data['employee_number'] ?? null) ?: $this->nextDocumentNumber($tenantId, 'employee', 'EMP'),
            'full_name' => $data['full_name'],
            'work_email' => $data['work_email'] ?? null,
            'employment_status' => $data['employment_status'] ?? 'Active',
            'joined_on' => $data['joined_on'] ?? null,
            'supervisor_id' => $data['supervisor_id'] ?? null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        app(DomainEventService::class)->record('employee.profile.created', 'Enterprise Foundation', null, ['employee_profile_id' => $id], $tenantId, $actor);

        return $id;
    }

    public function createApprovalRule(int $tenantId, User $actor, array $data): int
    {
        $id = DB::table('approval_rules')->insertGetId([
            'tenant_id' => $tenantId,
            'module' => $data['module'],
            'request_type' => $data['request_type'],
            'minimum_amount' => $data['minimum_amount'] ?? 0,
            'approver_role' => $data['approver_role'] ?? null,
            'approver_user_id' => $data['approver_user_id'] ?? null,
            'sequence' => $data['sequence'] ?? 1,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        app(DomainEventService::class)->record('approval.rule.created', 'Enterprise Foundation', null, ['approval_rule_id' => $id], $tenantId, $actor);

        return $id;
    }

    public function registerDocument(int $tenantId, User $actor, array $data): int
    {
        $reference = $data['reference'] ?? $this->nextDocumentNumber($tenantId, $data['document_type'], $data['prefix'] ?? 'DOC');

        $id = DB::table('document_records')->insertGetId([
            'tenant_id' => $tenantId,
            'module' => $data['module'],
            'document_type' => $data['document_type'],
            'reference' => $reference,
            'title' => $data['title'],
            'status' => $data['status'] ?? 'Draft',
            'owner_id' => $data['owner_id'] ?? $actor->id,
            'metadata' => json_encode(['numbered_by' => 'document_number_sequences']),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        app(DomainEventService::class)->record('document.registered', 'Enterprise Foundation', null, [
            'document_record_id' => $id,
            'reference' => $reference,
            'document_type' => $data['document_type'],
        ], $tenantId, $actor);

        return $id;
    }
}
