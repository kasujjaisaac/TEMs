<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['tenant_id', 'key', 'value'])]
class SecuritySetting extends Model
{
    public static function defaults(): array
    {
        return [
            'password_min_length' => '10',
            'password_require_uppercase' => '1',
            'password_require_lowercase' => '1',
            'password_require_number' => '1',
            'password_require_symbol' => '1',
            'login_attempt_limit' => '5',
            'account_lockout_minutes' => '15',
            'session_timeout_minutes' => '60',
            'require_email_verification' => '0',
            'require_two_factor' => '0',
            'allow_multiple_sessions' => '1',
            'force_password_change_first_login' => '0',
            'password_expiry_days' => '90',
            'admin_approval_required' => '1',
        ];
    }

    public static function forTenant(?int $tenantId): array
    {
        $settings = self::where('tenant_id', $tenantId)->pluck('value', 'key')->all();

        return array_merge(self::defaults(), $settings);
    }
}
