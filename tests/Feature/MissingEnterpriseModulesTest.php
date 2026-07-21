<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use App\Support\Navigation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class MissingEnterpriseModulesTest extends TestCase
{
    use RefreshDatabase;

    public function test_missing_master_prompt_modules_render_as_real_workspaces(): void
    {
        $admin = $this->createUser('super_admin');

        $this->actingAs($admin)->get(route('strategy.dashboard'))->assertOk()->assertSee('Executive Strategy');
        $this->actingAs($admin)->get(route('marketing.dashboard'))->assertOk()->assertSee('Marketing &amp; Communications', false);
        $this->actingAs($admin)->get(route('engineering.dashboard'))->assertOk()->assertSee('Engineering');
        $this->actingAs($admin)->get(route('knowledge.dashboard'))->assertOk()->assertSee('Knowledge &amp; Documents', false);
        $this->actingAs($admin)->get(route('analytics.dashboard'))->assertOk()->assertSee('Reports &amp; Analytics', false);
    }

    public function test_new_module_workspaces_accept_core_operating_records(): void
    {
        $admin = $this->createUser('super_admin');

        $this->actingAs($admin)->post(route('strategy.directives.store'), [
            'title' => 'Launch board reporting rhythm',
            'directive' => 'Prepare monthly operating review pack.',
            'priority' => 'High',
            'due_on' => now()->addMonth()->toDateString(),
        ])->assertRedirect();

        $this->actingAs($admin)->post(route('marketing.plans.store'), [
            'title' => 'Customer acquisition communications',
            'channel' => 'Email',
            'audience' => 'Qualified enterprise leads',
            'budget' => 100000,
        ])->assertRedirect();

        $this->actingAs($admin)->post(route('engineering.backlog.store'), [
            'title' => 'Build executive decision register',
            'item_type' => 'Feature',
            'priority' => 'High',
            'release_target' => 'Strategy Module',
        ])->assertRedirect();

        $this->actingAs($admin)->post(route('knowledge.articles.store'), [
            'title' => 'Commercial-to-delivery handoff policy',
            'category' => 'Policy',
            'summary' => 'Defines required evidence before project creation.',
        ])->assertRedirect();

        $this->actingAs($admin)->post(route('analytics.reports.store'), [
            'name' => 'Monthly Executive Scorecard',
            'module' => 'Executive Office',
            'frequency' => 'Monthly',
            'visibility' => 'Executive',
        ])->assertRedirect();

        $this->assertDatabaseHas('executive_directives', ['tenant_id' => $admin->tenant_id, 'title' => 'Launch board reporting rhythm']);
        $this->assertDatabaseHas('marketing_communication_plans', ['tenant_id' => $admin->tenant_id, 'title' => 'Customer acquisition communications']);
        $this->assertDatabaseHas('engineering_backlog_items', ['tenant_id' => $admin->tenant_id, 'title' => 'Build executive decision register']);
        $this->assertDatabaseHas('knowledge_articles', ['tenant_id' => $admin->tenant_id, 'title' => 'Commercial-to-delivery handoff policy']);
        $this->assertDatabaseHas('report_definitions', ['tenant_id' => $admin->tenant_id, 'name' => 'Monthly Executive Scorecard']);
    }

    public function test_navigation_exposes_new_enterprise_modules_to_super_admin(): void
    {
        $admin = $this->createUser('super_admin');
        $labels = collect(Navigation::visibleGroups($admin))
            ->flatMap(fn (array $group) => collect($group['items'])->pluck('label'))
            ->values();

        $this->assertTrue($labels->contains('Executive Strategy'));
        $this->assertTrue($labels->contains('Marketing & Communications'));
        $this->assertTrue($labels->contains('Engineering'));
        $this->assertTrue($labels->contains('Knowledge & Documents'));
        $this->assertTrue($labels->contains('Reports & Analytics'));
    }

    private function createUser(string $roleSlug): User
    {
        $tenantId = DB::table('tenants')->insertGetId([
            'company_name' => 'Missing Modules Test Company',
            'slug' => 'missing-modules-test-' . $roleSlug . '-' . str()->random(6),
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
            'name' => 'Missing Modules User ' . $roleSlug,
            'email' => 'missing-modules-' . $roleSlug . '-' . str()->random(6) . '@example.test',
            'password' => Hash::make('Password#12345'),
            'role' => $roleSlug,
            'is_active' => true,
            'password_changed_at' => now(),
        ]);
    }
}
