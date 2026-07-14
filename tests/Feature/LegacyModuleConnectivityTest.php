<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\TestCase;

class LegacyModuleConnectivityTest extends TestCase
{
    public function test_all_legacy_modules_render_through_laravel_routes(): void
    {
        if (config('database.default') === 'sqlite') {
            $this->markTestSkipped('Legacy ERP modules require the MySQL demo schema.');
        }

        $admin = User::where('role', 'super_admin')->firstOrFail();

        foreach (onyx_legacy_pages() as $page) {
            $this->actingAs($admin)
                ->get('/' . $page . '.php')
                ->assertOk()
                ->assertSee('ONYX ACCOUNTING SYSTEM', false);
        }
    }
}
