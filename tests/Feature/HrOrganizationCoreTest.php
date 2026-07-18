<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\HR\HrDepartment;
use App\Models\HR\HrPosition;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\HR\HrOrganizationCoreSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class HrOrganizationCoreTest extends TestCase
{
    use RefreshDatabase;

    public function test_hr_command_centre_requires_hr_permission(): void
    {
        $viewer = $this->createUser('viewer');

        $this->actingAs($viewer)
            ->get(route('hr.command'))
            ->assertForbidden();
    }

    public function test_seeded_organization_core_is_available_to_authorized_users(): void
    {
        $manager = $this->createUser('manager');
        $this->seed(HrOrganizationCoreSeeder::class);

        $this->actingAs($manager)
            ->get(route('hr.command'))
            ->assertOk()
            ->assertSee('HR Command Centre')
            ->assertSee('Commercial Operations');

        $this->assertDatabaseHas('hr_departments', [
            'tenant_id' => $manager->tenant_id,
            'code' => 'HR',
            'name' => 'Human Resources',
        ]);
        $this->assertDatabaseHas('hr_positions', [
            'tenant_id' => $manager->tenant_id,
            'code' => 'COMM-001',
            'title' => 'Commercial Director',
        ]);
    }

    public function test_authorized_user_can_create_department_and_position(): void
    {
        $admin = $this->createUser('super_admin');

        $this->actingAs($admin)
            ->post(route('hr.departments.store'), [
                'code' => 'OPS',
                'name' => 'Operations',
                'short_name' => 'Ops',
                'type' => 'Department',
                'mandate' => 'Coordinate implementation and daily operating discipline.',
                'responsibilities' => 'Own workflow execution, escalations, and operational reporting.',
                'status' => 'Active',
                'effective_from' => '2026-01-01',
            ])
            ->assertRedirect();

        $department = HrDepartment::firstOrFail();

        $this->actingAs($admin)
            ->post(route('hr.positions.store'), [
                'department_id' => $department->id,
                'code' => 'OPS-001',
                'title' => 'Operations Coordinator',
                'employment_type' => 'Full time',
                'job_purpose' => 'Coordinate daily operational workflows.',
                'approved_headcount' => 1,
                'filled_headcount' => 0,
                'position_status' => 'Vacant',
            ])
            ->assertRedirect();

        $position = HrPosition::firstOrFail();

        $this->assertSame('Operations Coordinator', $position->title);
        $this->assertSame(1, $position->vacancy_count);
        $this->assertTrue(AuditLog::where('tenant_id', $admin->tenant_id)->where('module', 'hr')->where('action', 'created')->exists());
    }

    public function test_position_cannot_report_to_itself(): void
    {
        $admin = $this->createUser('super_admin');
        $department = HrDepartment::create([
            'tenant_id' => $admin->tenant_id,
            'code' => 'QA',
            'name' => 'Quality Assurance',
            'type' => 'Department',
            'status' => 'Active',
        ]);
        $position = HrPosition::create([
            'tenant_id' => $admin->tenant_id,
            'department_id' => $department->id,
            'code' => 'QA-001',
            'title' => 'QA Lead',
            'employment_type' => 'Full time',
            'approved_headcount' => 1,
            'filled_headcount' => 1,
            'position_status' => 'Occupied',
        ]);

        $this->actingAs($admin)
            ->put(route('hr.positions.update', $position), [
                'department_id' => $department->id,
                'reports_to_position_id' => $position->id,
                'code' => 'QA-001',
                'title' => 'QA Lead',
                'employment_type' => 'Full time',
                'approved_headcount' => 1,
                'filled_headcount' => 1,
                'position_status' => 'Occupied',
            ])
            ->assertStatus(422);
    }

    private function createUser(string $roleSlug): User
    {
        $tenantId = DB::table('tenants')->insertGetId([
            'company_name' => 'HR Test Company',
            'slug' => 'hr-test-' . $roleSlug . '-' . str()->random(6),
            'currency' => 'UGX',
            'fiscal_year_start' => '2026-01-01',
            'status' => 'trial',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Role::ensureDefaultsForTenant($tenantId);
        $role = Role::where('tenant_id', $tenantId)->where('slug', $roleSlug)->firstOrFail();

        return User::create([
            'tenant_id' => $tenantId,
            'role_id' => $role->id,
            'name' => 'HR User ' . $roleSlug,
            'email' => 'hr-' . $roleSlug . '-' . str()->random(6) . '@example.test',
            'password' => Hash::make('Password#12345'),
            'role' => $roleSlug,
            'is_active' => true,
            'password_changed_at' => now(),
        ]);
    }
}
