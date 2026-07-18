<?php

namespace Database\Seeders\Planning;

use App\Models\Finance\FinanceBudgetLine;
use App\Models\HR\HrDepartment;
use App\Models\Planning\StrategicObjective;
use App\Models\Planning\TargetAllocation;
use App\Models\Planning\Workplan;
use App\Models\Planning\WorkplanAssignment;
use App\Models\Planning\WorkplanItem;
use App\Services\Planning\PlanningPerformanceService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PlanningPerformanceSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('tenants')->orderBy('id')->pluck('id')->each(function (int $tenantId): void {
            $service = app(PlanningPerformanceService::class);
            $year = $service->bootstrapTenant($tenantId);
            $corporate = Workplan::where('tenant_id', $tenantId)->where('level', 'Corporate')->first();
            if (! $corporate) {
                return;
            }

            $objectives = StrategicObjective::where('tenant_id', $tenantId)->pluck('id', 'code');
            $departments = HrDepartment::where('tenant_id', $tenantId)->pluck('id', 'code');
            $budgetLine = FinanceBudgetLine::where('tenant_id', $tenantId)->orderBy('id')->first();

            foreach ([
                ['WP-COMM-001', 'Acquire active clients', 'OBJ-001', 'Numeric', 85, 12, 'clients', 'Commercial Operations', 'COMM', 'High', 30, 'Commercial opportunity, contract, invoice or onboarding confirmation.'],
                ['WP-SALES-001', 'Convert pipeline into revenue', 'OBJ-002', 'Financial', 250000000, 35000000, 'UGX', 'Sales and Finance', 'COMM', 'Critical', 25, 'Quotation, invoice, payment receipt or signed agreement.'],
                ['WP-DEL-001', 'Complete verified delivery milestones', 'OBJ-003', 'Milestone', 40, 6, 'milestones', 'Engineering and Customer Success', 'ENG', 'High', 20, 'Acceptance note, deployment record, release note or client sign-off.'],
                ['WP-FIN-001', 'Maintain budget discipline', 'OBJ-004', 'Percentage', 90, 65, '%', 'Finance', 'FIN', 'High', 15, 'Approved report, reconciliation, receipt or budget variance review.'],
                ['WP-HR-001', 'Assign workplans to accountable roles', 'OBJ-005', 'Percentage', 100, 45, '%', 'Human Resources', 'HR', 'Medium', 10, 'Approved position workplan or supervisor confirmation.'],
            ] as [$reference, $title, $objectiveCode, $type, $target, $actual, $unit, $owner, $departmentCode, $priority, $weight, $evidence]) {
                $item = WorkplanItem::updateOrCreate(
                    ['tenant_id' => $tenantId, 'reference' => $reference],
                    [
                        'workplan_id' => $corporate->id,
                        'strategic_objective_id' => $objectives[$objectiveCode] ?? null,
                        'budget_line_id' => $budgetLine?->id,
                        'title' => $title,
                        'description' => $title . ' tracked through annual, monthly and weekly planning commitments.',
                        'target_type' => $type,
                        'kpi' => $title,
                        'target_value' => $target,
                        'actual_value' => $actual,
                        'unit' => $unit,
                        'priority' => $priority,
                        'weight' => $weight,
                        'starts_on' => $year->starts_on,
                        'due_on' => $year->ends_on,
                        'required_evidence_type' => $evidence,
                        'quality_standard' => 'Evidence must be complete, dated, attributable and approved by an authorized supervisor.',
                        'approval_status' => 'Approved',
                        'health_status' => 'On Track',
                    ]
                );

                WorkplanAssignment::updateOrCreate(
                    ['tenant_id' => $tenantId, 'workplan_item_id' => $item->id, 'assignment_role' => 'Accountable'],
                    [
                        'department_id' => $departments[$departmentCode] ?? null,
                        'contribution_weight' => 100,
                        'status' => 'Active',
                    ]
                );

                $service->createAllocations($item);
            }
        });
    }
}
