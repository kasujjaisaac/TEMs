<?php

namespace App\Services\Planning;

use App\Models\Finance\FinanceBudgetLine;
use App\Models\HR\HrDepartment;
use App\Models\HR\HrPosition;
use App\Models\Planning\PlanningYear;
use App\Models\Planning\StrategicObjective;
use App\Models\Planning\StrategicPillar;
use App\Models\Planning\TargetAllocation;
use App\Models\Planning\Workplan;
use App\Models\Planning\WorkplanItem;
use Carbon\CarbonPeriod;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class PlanningPerformanceService
{
    public function bootstrapTenant(int $tenantId): PlanningYear
    {
        $year = $this->currentPlanningYear($tenantId);
        $this->ensureStrategy($tenantId, $year);
        $this->ensureWorkplans($tenantId, $year);

        return $year;
    }

    public function currentPlanningYear(int $tenantId): PlanningYear
    {
        $now = now();
        $startYear = $now->month >= 7 ? $now->year : $now->year - 1;
        $name = $startYear . '/' . ($startYear + 1);

        PlanningYear::where('tenant_id', $tenantId)->where('is_current', true)->update(['is_current' => false]);

        return PlanningYear::updateOrCreate(
            ['tenant_id' => $tenantId, 'name' => $name],
            [
                'starts_on' => Carbon::create($startYear, 7, 1)->toDateString(),
                'ends_on' => Carbon::create($startYear + 1, 6, 30)->toDateString(),
                'annual_theme' => 'Disciplined growth through verified execution',
                'status' => 'Open',
                'is_current' => true,
                'scoring_rules' => [
                    'achievement' => 40,
                    'timeliness' => 20,
                    'quality_evidence' => 15,
                    'strategic_importance' => 10,
                    'budget_discipline' => 5,
                    'collaboration_reporting' => 10,
                ],
            ]
        );
    }

    public function dashboard(int $tenantId): array
    {
        $year = $this->bootstrapTenant($tenantId);
        $items = WorkplanItem::with(['workplan.department', 'objective.pillar', 'assignments.department', 'assignments.position', 'budgetLine'])
            ->where('tenant_id', $tenantId)
            ->latest('updated_at')
            ->get();

        $snapshots = $items->map(fn (WorkplanItem $item): array => $this->itemSnapshot($item));
        $healthCounts = $snapshots->countBy('health');
        $weightedTarget = max(1, $items->sum(fn (WorkplanItem $item): int => max(1, (int) $item->weight)));
        $weightedAchievement = $items->sum(fn (WorkplanItem $item): float => min(100, $item->achievement_percentage) * max(1, (int) $item->weight));

        return [
            'planningYear' => $year,
            'metrics' => [
                'strategic_pillars' => StrategicPillar::where('tenant_id', $tenantId)->count(),
                'strategic_objectives' => StrategicObjective::where('tenant_id', $tenantId)->count(),
                'workplans' => Workplan::where('tenant_id', $tenantId)->count(),
                'targets' => $items->count(),
                'monthly_allocations' => TargetAllocation::where('tenant_id', $tenantId)->where('period_type', 'Monthly')->count(),
                'weekly_allocations' => TargetAllocation::where('tenant_id', $tenantId)->where('period_type', 'Weekly')->count(),
                'company_achievement' => $items->isEmpty() ? 0 : (int) round($weightedAchievement / $weightedTarget),
                'at_risk_or_behind' => ($healthCounts['At Risk'] ?? 0) + ($healthCounts['Behind'] ?? 0),
            ],
            'healthCounts' => $healthCounts,
            'recentItems' => $snapshots->take(10),
            'workplans' => Workplan::withCount('items')->with(['department', 'planningYear'])
                ->where('tenant_id', $tenantId)
                ->orderBy('level')
                ->orderBy('title')
                ->limit(8)
                ->get(),
            'objectives' => StrategicObjective::with('pillar')
                ->where('tenant_id', $tenantId)
                ->orderBy('code')
                ->limit(8)
                ->get(),
            'alerts' => $this->alerts($snapshots),
        ];
    }

    public function itemSnapshot(WorkplanItem $item): array
    {
        $timeElapsed = $this->timeElapsedPercentage($item->starts_on, $item->due_on);
        $achievement = $item->achievement_percentage;
        $expected = round(((float) $item->target_value * $timeElapsed) / 100, 2);
        $gap = round((float) $item->actual_value - $expected, 2);
        $pace = $timeElapsed > 0 ? (int) round(($achievement / $timeElapsed) * 100) : ($achievement > 0 ? 100 : 0);
        $forecast = $timeElapsed > 0 ? round(((float) $item->actual_value / $timeElapsed) * 100, 2) : (float) $item->actual_value;
        $health = $this->healthStatus($item, $pace, $achievement);

        return [
            'item' => $item,
            'achievement' => $achievement,
            'time_elapsed' => $timeElapsed,
            'expected' => $expected,
            'gap' => $gap,
            'pace' => $pace,
            'forecast' => $forecast,
            'health' => $health,
        ];
    }

    public function createAllocations(WorkplanItem $item): void
    {
        if (! $item->starts_on || ! $item->due_on || (float) $item->target_value <= 0) {
            return;
        }

        $months = collect(CarbonPeriod::create($item->starts_on->copy()->startOfMonth(), '1 month', $item->due_on->copy()->startOfMonth()));
        $monthlyTarget = $months->isEmpty() ? (float) $item->target_value : round((float) $item->target_value / $months->count(), 2);
        $months->each(function (Carbon $month) use ($item, $monthlyTarget): void {
            TargetAllocation::updateOrCreate(
                ['tenant_id' => $item->tenant_id, 'workplan_item_id' => $item->id, 'period_type' => 'Monthly', 'period_start' => $month->copy()->startOfMonth()->toDateString()],
                ['period_end' => $month->copy()->endOfMonth()->toDateString(), 'target_value' => $monthlyTarget, 'status' => 'Planned']
            );
        });

        $weeks = collect(CarbonPeriod::create($item->starts_on->copy()->startOfWeek(), '1 week', $item->due_on->copy()->startOfWeek()));
        $weeklyTarget = $weeks->isEmpty() ? (float) $item->target_value : round((float) $item->target_value / $weeks->count(), 2);
        $weeks->each(function (Carbon $week) use ($item, $weeklyTarget): void {
            TargetAllocation::updateOrCreate(
                ['tenant_id' => $item->tenant_id, 'workplan_item_id' => $item->id, 'period_type' => 'Weekly', 'period_start' => $week->copy()->startOfWeek()->toDateString()],
                ['period_end' => $week->copy()->endOfWeek()->toDateString(), 'target_value' => $weeklyTarget, 'status' => 'Planned']
            );
        });
    }

    private function ensureStrategy(int $tenantId, PlanningYear $year): void
    {
        $pillars = [
            ['GROWTH', 'Commercial Growth', 'Acquire, convert, and retain profitable customers.', 30],
            ['DELIVERY', 'Product & Delivery Excellence', 'Deliver reliable products, implementations, support, and uptime.', 25],
            ['FINCTRL', 'Financial Control', 'Protect cash, budget discipline, profitability, and audit readiness.', 20],
            ['PEOPLE', 'People & Organization', 'Build accountable roles, capacity, culture, and performance.', 15],
            ['GOV', 'Governance & Compliance', 'Maintain legal, security, policy, and board discipline.', 10],
        ];

        $pillarModels = [];
        foreach ($pillars as [$code, $name, $description, $weight]) {
            $pillarModels[$code] = StrategicPillar::updateOrCreate(
                ['tenant_id' => $tenantId, 'code' => $code],
                ['planning_year_id' => $year->id, 'name' => $name, 'description' => $description, 'weight' => $weight, 'status' => 'Active']
            );
        }

        foreach ([
            ['OBJ-001', 'GROWTH', 'Acquire active customers', 'Acquire and activate paying customers from Commercial and Sales.', 'Active clients', 85, 'clients', 30],
            ['OBJ-002', 'GROWTH', 'Convert pipeline into revenue', 'Move qualified opportunities into quotations, invoices and payments.', 'Revenue achieved', 250000000, 'UGX', 25],
            ['OBJ-003', 'DELIVERY', 'Deliver reliable implementations', 'Complete product, project and support milestones with verified acceptance.', 'Verified milestones', 40, 'milestones', 20],
            ['OBJ-004', 'FINCTRL', 'Maintain budget and cash discipline', 'Keep budgets, receivables, payables and evidence under management control.', 'Budget compliant lines', 90, '%', 15],
            ['OBJ-005', 'PEOPLE', 'Strengthen accountable execution', 'Ensure positions, workplans, evidence and reviews guide every employee.', 'Roles with workplans', 100, '%', 10],
        ] as [$code, $pillar, $title, $description, $kpi, $target, $unit, $weight]) {
            StrategicObjective::updateOrCreate(
                ['tenant_id' => $tenantId, 'code' => $code],
                [
                    'planning_year_id' => $year->id,
                    'strategic_pillar_id' => $pillarModels[$pillar]->id,
                    'title' => $title,
                    'description' => $description,
                    'kpi' => $kpi,
                    'target_value' => $target,
                    'unit' => $unit,
                    'weight' => $weight,
                    'status' => 'Approved',
                ]
            );
        }
    }

    private function ensureWorkplans(int $tenantId, PlanningYear $year): void
    {
        Workplan::updateOrCreate(
            ['tenant_id' => $tenantId, 'code' => 'CORP-' . str_replace('/', '-', $year->name)],
            [
                'planning_year_id' => $year->id,
                'title' => 'Corporate Workplan ' . $year->name,
                'level' => 'Corporate',
                'description' => 'Company strategy translated into annual, monthly and weekly execution targets.',
                'owner_name' => 'Managing Director',
                'approval_status' => 'Approved',
                'approved_at' => now(),
                'health_status' => 'On Track',
            ]
        );

        HrDepartment::where('tenant_id', $tenantId)->where('status', 'Active')->get()->each(function (HrDepartment $department) use ($tenantId, $year): void {
            Workplan::updateOrCreate(
                ['tenant_id' => $tenantId, 'code' => 'DEPT-' . $department->code . '-' . str_replace('/', '-', $year->name)],
                [
                    'planning_year_id' => $year->id,
                    'department_id' => $department->id,
                    'title' => $department->name . ' Workplan',
                    'level' => 'Department',
                    'description' => $department->mandate,
                    'owner_name' => $department->headPosition?->title ?: $department->short_name,
                    'approval_status' => 'Draft',
                    'health_status' => 'Not Started',
                ]
            );
        });
    }

    private function timeElapsedPercentage(?Carbon $start, ?Carbon $due): int
    {
        if (! $start || ! $due) {
            return 0;
        }

        $today = now()->startOfDay();
        if ($today->lessThanOrEqualTo($start)) {
            return 0;
        }

        $total = max(1, $start->diffInDays($due) + 1);
        $elapsed = min($total, $start->diffInDays($today) + 1);

        return (int) min(100, round(($elapsed / $total) * 100));
    }

    private function healthStatus(WorkplanItem $item, int $pace, int $achievement): string
    {
        if ($achievement >= 100 && $item->approval_status === 'Approved') {
            return 'Completed';
        }
        if ($item->due_on && now()->startOfDay()->greaterThan($item->due_on) && $achievement < 100) {
            return 'Missed';
        }
        if ($pace >= 110) {
            return 'Ahead';
        }
        if ($pace >= 90) {
            return 'On Track';
        }
        if ($pace >= 70) {
            return 'At Risk';
        }

        return $achievement > 0 ? 'Behind' : 'Not Started';
    }

    private function alerts(Collection $snapshots): array
    {
        $alerts = [];
        $behind = $snapshots->whereIn('health', ['Behind', 'Missed'])->count();
        $risk = $snapshots->where('health', 'At Risk')->count();
        if ($behind > 0) {
            $alerts[] = ['severity' => 'High', 'title' => 'Behind targets', 'message' => $behind . ' workplan targets require recovery or escalation.'];
        }
        if ($risk > 0) {
            $alerts[] = ['severity' => 'Medium', 'title' => 'At-risk targets', 'message' => $risk . ' targets are below expected pace and need supervisor attention.'];
        }
        if ($snapshots->filter(fn (array $row): bool => $row['item']->assignments->isEmpty())->count() > 0) {
            $alerts[] = ['severity' => 'Medium', 'title' => 'Unassigned work', 'message' => 'Some approved targets do not yet have accountable departments, positions or employees.'];
        }
        if ($snapshots->isEmpty()) {
            $alerts[] = ['severity' => 'Medium', 'title' => 'Planning baseline required', 'message' => 'Create workplan targets and allocations to activate performance intelligence.'];
        }

        return $alerts;
    }
}
