<?php

namespace App\Services\Enterprise;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;

class AuditService
{
    public function record(int $tenantId, ?User $user, string $action, string $module, string $description, array $metadata = [], ?object $subject = null, ?Request $request = null): AuditLog
    {
        return AuditLog::create([
            'tenant_id' => $tenantId,
            'user_id' => $user?->id,
            'action' => $action,
            'module' => $module,
            'subject_type' => $subject ? $subject::class : null,
            'subject_id' => $subject->id ?? null,
            'description' => $description,
            'metadata' => $metadata,
            'ip_address' => $request?->ip(),
            'user_agent' => substr((string) $request?->userAgent(), 0, 1000),
        ]);
    }
}
