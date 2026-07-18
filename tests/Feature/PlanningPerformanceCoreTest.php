<?php

namespace Tests\Feature;

use App\Models\Planning\PlanningYear;
use App\Models\Planning\TargetAllocation;
use App\Models\Planning\Workplan;
use App\Models\Planning\WorkplanItem;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\HR\HrOrganizationCoreSeeder;
use Database\Seeders\Planning\PlanningPerformanceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PlanningPerformanceCoreTest extends TestCase
{
    use RefreshDatabase;

    public function test_planning_dashboard_requires_permission(): void
    {
        $viewer = $this->createUser('viewer');

        $this->actingAs($viewer)
            ->get(route('planning.dashboard'))
            ->assertForbidden();
    }

    public function test_seeded_planning_engine_is_available_to_authorized_users(): void
    {
        $manager = $this->createUser('manager');
        $this->seed(HrOrganizationCoreSeeder::class);
        $this->seed(PlanningPerformanceSeeder::class);

        $this->actingAs($manager)
            ->get(route('planning.dashboard'))
            ->assertOk()
            ->assertSee('Planning')
            ->assertSee('Company Achievement');

        $this->assertDatabaseHas('strategic_pillars', [
            'tenant_id' => $manager->tenant_id,
            'code' => 'GROWTH',
        ]);
        $this->assertDatabaseHas('workplan_items', [
            'tenant_id' => $manager->tenant_id,
            'reference' => 'WP-COMM-001',
        ]);
        $this->assertGreaterThan(0, TargetAllocation::where('tenant_id', $manager->tenant_id)->where('period_type', 'Weekly')->count());
    }

    public function test_authorized_user_can_create_target_and_generate_allocations(): void
    {
        $admin = $this->createUser('super_admin');
        $this->seed(HrOrganizationCoreSeeder::class);
        $this->seed(PlanningPerformanceSeeder::class);
        $workplan = Workplan::where('tenant_id', $admin->tenant_id)->where('level', 'Corporate')->firstOrFail();

        $this->actingAs($admin)
            ->post(route('planning.workplans.items.store', $workplan), [
                'reference' => 'WP-TEST-001',
                'title' => 'Create planning discipline',
                'target_type' => 'Numeric',
                'target_value' => 12,
                'actual_value' => 3,
                'unit' => 'reviews',
                'priority' => 'High',
                'weight' => 10,
                'starts_on' => now()->toDateString(),
                'due_on' => now()->addWeeks(6)->toDateString(),
                'assignment_role' => 'Accountable',
            ])
            ->assertRedirect(route('planning.workplans.show', $workplan));

        $item = WorkplanItem::where('tenant_id', $admin->tenant_id)->where('reference', 'WP-TEST-001')->firstOrFail();

        $this->assertDatabaseHas('workplan_assignments', [
            'tenant_id' => $admin->tenant_id,
            'workplan_item_id' => $item->id,
            'assignment_role' => 'Accountable',
        ]);
        $this->assertGreaterThan(0, TargetAllocation::where('workplan_item_id', $item->id)->where('period_type', 'Weekly')->count());
    }

    public function test_workplan_can_be_approved_as_baseline(): void
    {
        $admin = $this->createUser('super_admin');
        $year = PlanningYear::create([
            'tenant_id' => $admin->tenant_id,
            'name' => '2026/2027',
            'starts_on' => '2026-07-01',
            'ends_on' => '2027-06-30',
            'status' => 'Open',
            'is_current' => true,
        ]);
        $workplan = Workplan::create([
            'tenant_id' => $admin->tenant_id,
            'planning_year_id' => $year->id,
            'code' => 'CORP-TEST',
            'title' => 'Corporate Test Workplan',
            'level' => 'Corporate',
            'approval_status' => 'Draft',
        ]);

        $this->actingAs($admin)
            ->post(route('planning.workplans.approve', $workplan))
            ->assertRedirect(route('planning.workplans.show', $workplan));

        $this->assertSame('Approved', $workplan->fresh()->approval_status);
    }

    private function createUser(string $roleSlug): User
    {
        $tenantId = DB::table('tenants')->insertGetId([
            'company_name' => 'Planning Test Company',
            'slug' => 'planning-test-' . $roleSlug . '-' . str()->random(6),
            'currency' => 'UGX',
            'fiscal_year_start' => '2026-07-01',
            'status' => 'trial',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Role::ensureDefaultsForTenant($tenantId);
        $role = Role::where('tenant_id', $tenantId)->where('slug', $roleSlug)->firstOrFail();

        return User::create([
            'tenant_id' => $tenantId,
            'role_id' => $role->id,
            'name' => 'Planning User ' . $roleSlug,
            'email' => 'planning-' . $roleSlug . '-' . str()->random(6) . '@example.test',
            'password' => Hash::make('Password#12345'),
            'role' => $roleSlug,
            'is_active' => true,
            'password_changed_at' => now(),
        ]);
    }
}
