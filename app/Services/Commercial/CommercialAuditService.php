<?php

namespace App\Services\Commercial;

use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CommercialAuditService
{
    public function record(Request $request, string $action, object $subject, string $description, array $metadata = []): void
    {
        AuditLog::create([
            'tenant_id' => Auth::user()?->tenant_id,
            'user_id' => Auth::id(),
            'action' => $action,
            'module' => 'commercial',
            'subject_type' => $subject::class,
            'subject_id' => $subject->id ?? null,
            'description' => $description,
            'metadata' => $metadata,
            'ip_address' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 1000),
        ]);
    }
}
