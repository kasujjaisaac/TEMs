<?php

namespace Tests\Feature;

use App\Models\ApprovalRequest;
use App\Models\CompanySetting;
use App\Models\DomainEvent;
use App\Models\Role;
use App\Models\SystemNotification;
use App\Models\User;
use App\Services\Enterprise\ApprovalService;
use App\Services\Enterprise\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class EnterpriseFoundationTest extends TestCase
{
    use RefreshDatabase;

    public function test_enterprise_foundation_requires_permission(): void
    {
        $viewer = $this->createUser('viewer');

        $this->actingAs($viewer)
            ->get(route('foundation.dashboard'))
            ->assertForbidden();
    }

    public function test_super_admin_can_update_company_foundation_settings(): void
    {
        $admin = $this->createUser('super_admin');

        $this->actingAs($admin)
            ->put(route('foundation.company.update'), [
                'company_name' => 'Texaro Technologies Limited',
                'company_email' => 'ops@texaro.test',
                'company_phone' => '+256700000000',
                'company_country' => 'Uganda',
                'company_address' => 'Kampala',
                'company_website' => 'https://texaro.test',
                'company_logo' => 'assets/texaro-logo.png',
                'currency' => 'UGX',
                'fiscal_year_start' => '2026-01-01',
                'approval_policy' => 'Approvals are required for controlled business decisions.',
                'notification_policy' => 'Notifications guide employee action.',
                'document_policy' => 'Documents must be registered by source module.',
            ])
            ->assertRedirect();

        $this->assertSame('ops@texaro.test', CompanySetting::forTenant($admin->tenant_id)['company_email']);
        $this->assertDatabaseHas('tenants', [
            'id' => $admin->tenant_id,
            'company_name' => 'Texaro Technologies Limited',
            'currency' => 'UGX',
        ]);
        $this->assertDatabaseHas('domain_events', [
            'tenant_id' => $admin->tenant_id,
            'event_name' => 'company.settings.updated',
            'source_module' => 'Enterprise Foundation',
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'tenant_id' => $admin->tenant_id,
            'module' => 'foundation',
            'action' => 'updated',
        ]);
    }

    public function test_approval_service_records_events_and_decisions_notify_requester(): void
    {
        $admin = $this->createUser('super_admin');
        $approval = app(ApprovalService::class)->request($admin->tenant_id, 'Finance', 'Budget Release', 'Approve operational budget', [
            'summary' => 'Budget release needs management approval.',
            'priority' => 'High',
            'requested_by' => $admin->id,
            'actor' => $admin,
        ]);

        $this->assertSame('Pending', $approval->status);
        $this->assertDatabaseHas('domain_events', [
            'tenant_id' => $admin->tenant_id,
            'event_name' => 'approval.requested',
            'source_module' => 'Finance',
        ]);

        $this->actingAs($admin)
            ->post(route('foundation.approvals.decision', $approval), [
                'decision' => 'Approved',
                'decision_notes' => 'Approved for Phase 1 test.',
            ])
            ->assertRedirect();

        $approval->refresh();
        $this->assertSame('Approved', $approval->status);
        $this->assertDatabaseHas('system_notifications', [
            'tenant_id' => $admin->tenant_id,
            'user_id' => $admin->id,
            'type' => 'approval',
            'title' => 'Approval approved',
        ]);
        $this->assertTrue(DomainEvent::where('tenant_id', $admin->tenant_id)->where('event_name', 'approval.approved')->exists());
    }

    public function test_notification_can_be_marked_as_read(): void
    {
        $admin = $this->createUser('super_admin');
        $notification = app(NotificationService::class)->notify($admin, $admin->tenant_id, 'Foundation notice', 'A foundation control needs attention.');

        $this->actingAs($admin)
            ->post(route('foundation.notifications.read', $notification))
            ->assertRedirect();

        $this->assertNotNull($notification->fresh()->read_at);
        $this->assertTrue(DomainEvent::where('tenant_id', $admin->tenant_id)->where('event_name', 'notification.read')->exists());
    }

    public function test_foundation_dashboard_shows_enterprise_registers(): void
    {
        $admin = $this->createUser('super_admin');
        ApprovalRequest::create([
            'tenant_id' => $admin->tenant_id,
            'module' => 'HR',
            'request_type' => 'Headcount',
            'title' => 'Approve headcount',
            'status' => 'Pending',
            'priority' => 'Normal',
            'requested_by' => $admin->id,
            'requested_at' => now(),
        ]);
        SystemNotification::create([
            'tenant_id' => $admin->tenant_id,
            'user_id' => $admin->id,
            'source_module' => 'HR',
            'type' => 'assignment',
            'severity' => 'Info',
            'title' => 'HR assignment',
            'message' => 'Review HR setup.',
        ]);

        $this->actingAs($admin)
            ->get(route('foundation.dashboard'))
            ->assertOk()
            ->assertSee('Enterprise Foundation')
            ->assertSee('Approve headcount')
            ->assertSee('HR assignment');
    }

    private function createUser(string $roleSlug): User
    {
        $tenantId = DB::table('tenants')->insertGetId([
            'company_name' => 'Foundation Test Company',
            'slug' => 'foundation-test-' . $roleSlug . '-' . str()->random(6),
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
            'name' => 'Foundation User ' . $roleSlug,
            'email' => 'foundation-' . $roleSlug . '-' . str()->random(6) . '@example.test',
            'password' => Hash::make('Password#12345'),
            'role' => $roleSlug,
            'is_active' => true,
            'password_changed_at' => now(),
        ]);
    }
}
