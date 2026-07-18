<?php

namespace App\Services\Enterprise;

use App\Models\CompanySetting;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CompanySettingsService
{
    public function settings(?int $tenantId): array
    {
        return CompanySetting::forTenant($tenantId);
    }

    public function update(int $tenantId, array $data, User $actor): array
    {
        foreach ($data as $key => $value) {
            CompanySetting::putForTenant($tenantId, $key, $value, $this->groupForKey($key));
        }

        DB::table('tenants')->where('id', $tenantId)->update(array_filter([
            'company_name' => $data['company_name'] ?? null,
            'currency' => $data['currency'] ?? null,
            'fiscal_year_start' => $data['fiscal_year_start'] ?? null,
            'updated_at' => now(),
        ], fn ($value): bool => $value !== null));

        app(DomainEventService::class)->record('company.settings.updated', 'Enterprise Foundation', null, [
            'updated_keys' => array_keys($data),
        ], $tenantId, $actor);

        return $this->settings($tenantId);
    }

    private function groupForKey(string $key): string
    {
        return str_contains($key, 'policy') ? 'governance' : 'company';
    }
}
