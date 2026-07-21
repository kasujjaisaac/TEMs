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

    public function test_approval_rules_create_ordered_steps_before_final_approval(): void
    {
        $admin = $this->createUser('super_admin');
        $approver = $this->createUser('super_admin', $admin->tenant_id);

        DB::table('approval_rules')->insert([
            [
                'tenant_id' => $admin->tenant_id,
                'module' => 'Finance',
                'request_type' => 'Expense',
                'minimum_amount' => 0,
                'approver_role' => 'Finance Lead',
                'sequence' => 1,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'tenant_id' => $admin->tenant_id,
                'module' => 'Finance',
                'request_type' => 'Expense',
                'minimum_amount' => 1000000,
                'approver_user_id' => $approver->id,
                'sequence' => 2,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $approval = app(ApprovalService::class)->request($admin->tenant_id, 'Finance', 'Expense', 'Approve logistics expense', [
            'amount' => 1500000,
            'requested_by' => $admin->id,
            'actor' => $admin,
        ]);

        $this->assertSame(2, $approval->steps()->count());
        $this->actingAs($admin)
            ->post(route('foundation.approvals.decision', $approval), ['decision' => 'Approved'])
            ->assertRedirect();

        $approval->refresh();
        $this->assertSame('Pending', $approval->status);
        $this->assertSame(2, $approval->current_step);
        $this->assertSame($approver->id, $approval->current_approver_id);

        $this->actingAs($approver)
            ->post(route('foundation.approvals.decision', $approval), ['decision' => 'Approved'])
            ->assertRedirect();

        $this->assertSame('Approved', $approval->fresh()->status);
        $this->assertDatabaseHas('domain_events', [
            'tenant_id' => $admin->tenant_id,
            'event_name' => 'approval.step.approved',
        ]);
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

    public function test_notification_preferences_can_mute_in_app_notifications_and_documents_are_numbered(): void
    {
        $admin = $this->createUser('super_admin');

        $this->actingAs($admin)
            ->post(route('foundation.notification_preferences.store'), [
                'user_id' => $admin->id,
                'source_module' => 'Finance',
                'type' => 'approval',
                'in_app_enabled' => 0,
            ])
            ->assertRedirect();

        $notification = app(NotificationService::class)->notify($admin, $admin->tenant_id, 'Muted finance approval', 'Preference should mute this.', [
            'source_module' => 'Finance',
            'type' => 'approval',
        ]);

        $this->assertSame('Muted', $notification->severity);
        $this->assertNotNull($notification->read_at);

        $this->actingAs($admin)
            ->post(route('foundation.documents.store'), [
                'module' => 'Enterprise Foundation',
                'document_type' => 'Policy',
                'prefix' => 'POL',
                'title' => 'Approval routing policy',
                'status' => 'Draft',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('document_records', [
            'tenant_id' => $admin->tenant_id,
            'reference' => 'POL-' . now()->format('Y') . '-00001',
            'title' => 'Approval routing policy',
        ]);
        $this->assertDatabaseHas('domain_events', [
            'tenant_id' => $admin->tenant_id,
            'event_name' => 'document.registered',
        ]);
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

    private function createUser(string $roleSlug, ?int $tenantId = null): User
    {
        $tenantId ??= DB::table('tenants')->insertGetId([
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
