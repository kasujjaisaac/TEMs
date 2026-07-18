<?php

namespace App\Services\Commercial;

use Illuminate\Support\Facades\DB;

class CommercialNumberingService
{
    private const PREFIXES = [
        'lead' => 'LEAD',
        'organization' => 'ORG',
        'opportunity' => 'OPP',
        'site_visit' => 'SV',
    ];

    public function next(int $tenantId, string $type): string
    {
        $year = (int) now()->format('Y');
        $prefix = self::PREFIXES[$type] ?? strtoupper($type);

        return DB::transaction(function () use ($tenantId, $type, $year, $prefix): string {
            $sequence = DB::table('commercial_number_sequences')
                ->where('tenant_id', $tenantId)
                ->where('type', $type)
                ->where('year', $year)
                ->lockForUpdate()
                ->first();

            if (! $sequence) {
                DB::table('commercial_number_sequences')->insert([
                    'tenant_id' => $tenantId,
                    'type' => $type,
                    'year' => $year,
                    'next_number' => 2,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $number = 1;
            } else {
                $number = (int) $sequence->next_number;
                DB::table('commercial_number_sequences')
                    ->where('id', $sequence->id)
                    ->update([
                        'next_number' => $number + 1,
                        'updated_at' => now(),
                    ]);
            }

            return sprintf('%s-%d-%05d', $prefix, $year, $number);
        });
    }
}
