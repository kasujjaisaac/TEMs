<?php

namespace App\Services\Enterprise;

use App\Models\NotificationPreference;
use App\Models\SystemNotification;
use App\Models\User;

class NotificationService
{
    public function notify(?User $user, int $tenantId, string $title, string $message, array $options = []): SystemNotification
    {
        $sourceModule = $options['source_module'] ?? 'Enterprise Foundation';
        $type = $options['type'] ?? 'system';

        $existing = SystemNotification::where('tenant_id', $tenantId)
            ->where('user_id', $user?->id)
            ->where('title', $title)
            ->where('message', $message)
            ->whereNull('read_at')
            ->where('created_at', '>=', now()->subMinutes(5))
            ->latest()
            ->first();

        if ($existing) {
            return $existing;
        }

        if ($user && ! $this->inAppEnabled($tenantId, $user, $sourceModule, $type)) {
            return SystemNotification::create([
                'tenant_id' => $tenantId,
                'user_id' => $user->id,
                'source_module' => $sourceModule,
                'type' => $type,
                'severity' => 'Muted',
                'title' => $title,
                'message' => $message,
                'read_at' => now(),
                'metadata' => ['suppressed_by_preference' => true] + ($options['metadata'] ?? []),
            ]);
        }

        return SystemNotification::create([
            'tenant_id' => $tenantId,
            'user_id' => $user?->id,
            'source_module' => $sourceModule,
            'type' => $type,
            'severity' => $options['severity'] ?? 'Info',
            'title' => $title,
            'message' => $message,
            'action_url' => $options['action_url'] ?? null,
            'metadata' => $options['metadata'] ?? [],
        ]);
    }

    private function inAppEnabled(int $tenantId, User $user, string $sourceModule, string $type): bool
    {
        $preference = NotificationPreference::where('tenant_id', $tenantId)
            ->where('user_id', $user->id)
            ->where(function ($query) use ($sourceModule): void {
                $query->where('source_module', $sourceModule)->orWhere('source_module', '*');
            })
            ->where(function ($query) use ($type): void {
                $query->where('type', $type)->orWhere('type', '*');
            })
            ->orderByRaw("CASE WHEN source_module = ? THEN 0 ELSE 1 END", [$sourceModule])
            ->orderByRaw("CASE WHEN type = ? THEN 0 ELSE 1 END", [$type])
            ->first();

        return $preference ? (bool) $preference->in_app_enabled : true;
    }
}
