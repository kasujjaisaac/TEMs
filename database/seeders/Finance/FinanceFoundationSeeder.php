<?php

namespace Database\Seeders\Finance;

use App\Services\Finance\FinanceControlService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FinanceFoundationSeeder extends Seeder
{
    public function run(): void
    {
        $finance = app(FinanceControlService::class);

        DB::table('tenants')->orderBy('id')->pluck('id')->each(function (int $tenantId) use ($finance): void {
            $finance->bootstrapTenant($tenantId);
        });
    }
}
