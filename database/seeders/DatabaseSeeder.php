<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Database\Seeders\Commercial\CommercialSeeder;
use Database\Seeders\Finance\FinanceFoundationSeeder;
use Database\Seeders\HR\HrOrganizationCoreSeeder;
use Database\Seeders\Planning\PlanningPerformanceSeeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $tenantSlug = env('SUPER_ADMIN_TENANT_SLUG', 'texaro-technologies-limited');
        $tenantId = DB::table('tenants')->where('slug', $tenantSlug)->value('id');

        if (! $tenantId) {
            $tenantId = DB::table('tenants')->insertGetId([
                'company_name' => env('SUPER_ADMIN_COMPANY_NAME', 'Texaro Technologies Limited'),
                'slug' => $tenantSlug,
                'currency' => env('SUPER_ADMIN_CURRENCY', 'UGX'),
                'fiscal_year_start' => env('SUPER_ADMIN_FISCAL_YEAR_START', '2026-01-01'),
                'status' => env('SUPER_ADMIN_TENANT_STATUS', 'trial'),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        Role::ensureDefaultsForTenant((int) $tenantId);
        $superAdminRole = Role::where('tenant_id', $tenantId)->where('slug', 'super_admin')->first();

        User::updateOrCreate([
            'email' => env('SUPER_ADMIN_EMAIL', 'superadmin@texaro.local'),
        ], [
            'tenant_id' => $tenantId,
            'role_id' => $superAdminRole?->id,
            'name' => env('SUPER_ADMIN_NAME', 'Super Admin'),
            'password' => Hash::make(env('SUPER_ADMIN_PASSWORD', 'Password#12345')),
            'role' => 'super_admin',
            'is_active' => true,
            'email_verified_at' => now(),
            'password_changed_at' => now(),
        ]);

        $this->call(CommercialSeeder::class);
        $this->call(HrOrganizationCoreSeeder::class);
        $this->call(FinanceFoundationSeeder::class);
        $this->call(PlanningPerformanceSeeder::class);
    }
}
