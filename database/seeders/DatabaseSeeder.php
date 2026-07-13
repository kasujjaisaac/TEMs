<?php

namespace Database\Seeders;

use App\Models\User;
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
        $tenantId = DB::table('tenants')->value('id') ?? DB::table('tenants')->insertGetId([
            'company_name' => 'Clinic Test',
            'slug' => 'clinic-test',
            'currency' => 'UGX',
            'fiscal_year_start' => '2026-01-01',
            'status' => 'trial',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        User::updateOrCreate([
            'email' => 'superadmin@clinic.test',
        ], [
            'tenant_id' => $tenantId,
            'name' => 'Super Admin',
            'password' => Hash::make('password'),
            'role' => 'super_admin',
            'is_active' => true,
        ]);
    }
}
