<?php

namespace App\Services\Enterprise;

use App\Models\SystemNotification;
use App\Models\User;

class NotificationService
{
    public function notify(?User $user, int $tenantId, string $title, string $message, array $options = []): SystemNotification
    {
        return SystemNotification::create([
            'tenant_id' => $tenantId,
            'user_id' => $user?->id,
            'source_module' => $options['source_module'] ?? 'Enterprise Foundation',
            'type' => $options['type'] ?? 'system',
            'severity' => $options['severity'] ?? 'Info',
            'title' => $title,
            'message' => $message,
            'action_url' => $options['action_url'] ?? null,
            'metadata' => $options['metadata'] ?? [],
        ]);
    }
}
