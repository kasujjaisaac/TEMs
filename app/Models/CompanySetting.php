<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['tenant_id', 'group', 'key', 'value', 'value_type'])]
class CompanySetting extends Model
{
    public static function defaults(): array
    {
        return [
            'company_name' => 'Texaro Technologies Limited',
            'company_email' => '',
            'company_phone' => '',
            'company_country' => 'Uganda',
            'company_address' => '',
            'company_website' => '',
            'company_logo' => 'assets/texaro-logo.png',
            'currency' => 'UGX',
            'fiscal_year_start' => now()->startOfYear()->toDateString(),
            'approval_policy' => 'All financial, HR, commercial handoff and planning baseline decisions must pass through approval control.',
            'notification_policy' => 'Operational alerts, assignments, approvals and overdue actions are routed through the notification centre.',
            'document_policy' => 'Business documents must be registered against the module and source record that created them.',
        ];
    }

    public static function forTenant(?int $tenantId): array
    {
        $settings = self::where('tenant_id', $tenantId)->pluck('value', 'key')->all();

        return array_merge(self::defaults(), $settings);
    }

    public static function putForTenant(?int $tenantId, string $key, mixed $value, string $group = 'company', string $type = 'string'): self
    {
        return self::updateOrCreate(
            ['tenant_id' => $tenantId, 'key' => $key],
            ['group' => $group, 'value' => is_bool($value) ? (string) (int) $value : (string) $value, 'value_type' => $type]
        );
    }
}
