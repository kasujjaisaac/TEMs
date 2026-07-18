<?php

namespace Database\Seeders\HR;

use App\Models\HR\HrDepartment;
use App\Models\HR\HrPosition;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class HrOrganizationCoreSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('tenants')->orderBy('id')->pluck('id')->each(function (int $tenantId): void {
            $departments = [
                'EXEC' => ['Office of the Managing Director', 'Executive accountability, governance, and strategic direction.', 'Executive'],
                'HR' => ['Human Resources', 'Organization design, employee lifecycle, policy, welfare, and workforce governance.', 'Human Resources'],
                'COMM' => ['Commercial Operations', 'Lead generation, customer acquisition, relationship growth, and commercial pipeline governance.', 'Commercial'],
                'FIN' => ['Finance', 'Financial control, invoicing, budgets, banking, compliance, and reporting.', 'Finance'],
                'ENG' => ['Engineering', 'Product delivery, technical operations, system reliability, and implementation support.', 'Engineering'],
                'CS' => ['Customer Success', 'Customer onboarding, service adoption, support coordination, and renewals.', 'Customer Success'],
            ];

            $departmentModels = [];
            foreach ($departments as $code => [$name, $mandate, $shortName]) {
                $departmentModels[$code] = HrDepartment::updateOrCreate(
                    ['tenant_id' => $tenantId, 'code' => $code],
                    [
                        'name' => $name,
                        'short_name' => $shortName,
                        'type' => 'Department',
                        'mandate' => $mandate,
                        'responsibilities' => $mandate,
                        'status' => 'Active',
                        'effective_from' => now()->startOfYear()->toDateString(),
                    ]
                );
            }

            $positions = [
                ['MD-001', 'Managing Director', 'EXEC', null, 'Executive', 'E1', 'Occupied', 1, 1],
                ['HR-001', 'Human Resources Manager', 'HR', 'MD-001', 'Human Resources', 'M2', 'Vacant', 1, 0],
                ['COMM-001', 'Commercial Director', 'COMM', 'MD-001', 'Commercial', 'M1', 'Vacant', 1, 0],
                ['COMM-002', 'Business Development Officer', 'COMM', 'COMM-001', 'Commercial', 'O2', 'Vacant', 2, 0],
                ['FIN-001', 'Finance Officer', 'FIN', 'MD-001', 'Finance', 'O2', 'Vacant', 1, 0],
                ['ENG-001', 'Software Engineer', 'ENG', 'MD-001', 'Engineering', 'O2', 'Vacant', 2, 0],
                ['CS-001', 'Customer Success Officer', 'CS', 'COMM-001', 'Customer Success', 'O2', 'Vacant', 1, 0],
            ];

            $positionModels = [];
            foreach ($positions as [$code, $title, $departmentCode, $reportsToCode, $family, $grade, $status, $approved, $filled]) {
                $positionModels[$code] = HrPosition::updateOrCreate(
                    ['tenant_id' => $tenantId, 'code' => $code],
                    [
                        'department_id' => $departmentModels[$departmentCode]->id,
                        'title' => $title,
                        'job_family' => $family,
                        'grade' => $grade,
                        'employment_type' => 'Full time',
                        'job_purpose' => $title . ' role established from the HR organization core design.',
                        'key_responsibilities' => 'Maintain accountability for assigned department outcomes, reporting, controls, and continuous improvement.',
                        'standard_kpis' => 'Delivery quality, cycle time, compliance, stakeholder satisfaction, and documented outcomes.',
                        'competencies' => 'Planning, communication, ownership, collaboration, and systems discipline.',
                        'decision_rights' => 'Act within assigned authority and escalate exceptions through the reporting line.',
                        'approved_headcount' => $approved,
                        'filled_headcount' => $filled,
                        'position_status' => $status,
                        'effective_from' => now()->startOfYear()->toDateString(),
                    ]
                );
            }

            foreach ($positions as [$code, , , $reportsToCode]) {
                if ($reportsToCode && isset($positionModels[$code], $positionModels[$reportsToCode])) {
                    $positionModels[$code]->forceFill([
                        'reports_to_position_id' => $positionModels[$reportsToCode]->id,
                    ])->save();
                }
            }

            foreach ([
                'EXEC' => 'MD-001',
                'HR' => 'HR-001',
                'COMM' => 'COMM-001',
                'FIN' => 'FIN-001',
                'ENG' => 'ENG-001',
                'CS' => 'CS-001',
            ] as $departmentCode => $positionCode) {
                $departmentModels[$departmentCode]->forceFill([
                    'head_position_id' => $positionModels[$positionCode]->id,
                ])->save();
            }
        });
    }
}
