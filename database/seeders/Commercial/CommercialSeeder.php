<?php

namespace Database\Seeders\Commercial;

use App\Models\Commercial\CommercialPipelineStage;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CommercialSeeder extends Seeder
{
    public function run(): void
    {
        $tenantIds = DB::table('tenants')->pluck('id')->map(fn ($id) => (int) $id);

        foreach ($tenantIds as $tenantId) {
            foreach ($this->stages() as $stage) {
                CommercialPipelineStage::updateOrCreate(
                    ['tenant_id' => $tenantId, 'name' => $stage['name']],
                    $stage + ['tenant_id' => $tenantId]
                );
            }
        }
    }

    private function stages(): array
    {
        return [
            ['name' => 'Prospect Identified', 'display_order' => 1, 'default_probability' => 5, 'color' => '#8a8a98'],
            ['name' => 'Qualified', 'display_order' => 2, 'default_probability' => 20, 'exit_criteria' => 'Customer need, budget indication, decision maker, and expected timeline identified.', 'color' => '#7dd3fc'],
            ['name' => 'Discovery', 'display_order' => 3, 'default_probability' => 30, 'color' => '#a7f3d0'],
            ['name' => 'Solution Design', 'display_order' => 4, 'default_probability' => 40, 'color' => '#fde68a'],
            ['name' => 'Proposal Preparation', 'display_order' => 5, 'default_probability' => 50, 'color' => '#fbcfe8'],
            ['name' => 'Proposal Submitted', 'display_order' => 6, 'default_probability' => 60, 'color' => '#c4b5fd'],
            ['name' => 'Quotation Submitted', 'display_order' => 7, 'default_probability' => 70, 'color' => '#f9a8d4'],
            ['name' => 'Negotiation', 'display_order' => 8, 'default_probability' => 75, 'color' => '#fdba74'],
            ['name' => 'Awaiting Approval', 'display_order' => 9, 'default_probability' => 80, 'requires_approval' => true, 'color' => '#fef08a'],
            ['name' => 'Contract Signed', 'display_order' => 10, 'default_probability' => 90, 'color' => '#86efac'],
            ['name' => 'Won', 'display_order' => 11, 'default_probability' => 100, 'color' => '#8ff0c3'],
            ['name' => 'Lost', 'display_order' => 12, 'default_probability' => 0, 'color' => '#ff8a8a'],
            ['name' => 'On Hold', 'display_order' => 13, 'default_probability' => 10, 'color' => '#d8d8de'],
        ];
    }
}
