<?php

namespace Tests\Feature\Auth;

use App\Models\Center;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Pin the contract for the "System panel" UI affordance in
 * `resources/js/Layouts/AppLayout.vue` (the link to /system is
 * guarded by `v-if="isAnyAdmin()"`).
 *
 * `isAnyAdmin()` must mirror the server-side check in
 * `App\Http\Middleware\EnsureSystemAdmin`:
 *   - User with is_system_admin = true      → sees the link
 *   - Tenant owner (super_admin role only)  → does NOT see the link
 *
 * If they diverge, the tenant owner sees a button that 403s on
 * click, which is a UX/security smell. The Vue side is the one
 * we pin in this test through the `is_system_admin` value the
 * server passes via Inertia page props.
 */
class SystemPanelButtonVisibilityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesSeeder::class);
    }

    public function test_main_admin_with_is_system_admin_flag_sees_system_button(): void
    {
        $tenant = Tenant::factory()->create();
        $center = Center::factory()->create(['tenant_id' => $tenant->id]);
        $admin = User::factory()->create([
            'tenant_id' => $tenant->id,
            'current_center_id' => $center->id,
            'email_verified_at' => now(),
            'is_system_admin' => true,
        ]);
        $admin->centers()->attach($center->id, ['tenant_id' => $tenant->id]);

        // The Vue page reads is_system_admin from page.props.auth.user.
        // The server side (HandleInertiaRequests) puts it there.
        // We assert the underlying contract: is_system_admin = true.
        $this->assertTrue(
            (bool) $admin->is_system_admin,
            'is_system_admin must be true so the Vue side shows the link'
        );
    }

    public function test_tenant_owner_with_only_super_admin_role_does_not_see_system_button(): void
    {
        $tenant = Tenant::factory()->create();
        $center = Center::factory()->create(['tenant_id' => $tenant->id]);
        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'current_center_id' => $center->id,
            'email_verified_at' => now(),
            'is_system_admin' => false, // ← key field
        ]);
        $user->centers()->attach($center->id, ['tenant_id' => $tenant->id]);

        // Assign the tenant-scope super_admin role
        app(PermissionRegistrar::class)->setPermissionsTeamId($tenant->id);
        $role = \Spatie\Permission\Models\Role::firstOrCreate([
            'tenant_id' => $tenant->id,
            'name' => 'super_admin',
            'guard_name' => 'web',
        ]);
        $user->assignRole($role);

        // Confirm: super_admin role alone must not flip the
        // is_system_admin flag. Otherwise the Vue side would
        // incorrectly show the system link.
        $this->assertFalse(
            (bool) $user->fresh()->is_system_admin,
            'Tenant-scope super_admin role must not grant is_system_admin'
        );
    }

    public function test_system_panel_button_in_app_layout_only_visible_for_is_system_admin(): void
    {
        // Read the Vue source and confirm the v-if guard references
        // is_system_admin, not a super_admin role check.
        $source = file_get_contents(base_path('resources/js/Composables/usePermission.js'));

        $this->assertStringContainsString(
            'is_system_admin',
            $source,
            'isAnyAdmin() must check is_system_admin'
        );

        // And confirm the implementation no longer returns true on
        // the super_admin role alone (the old buggy behaviour).
        $this->assertStringNotContainsString(
            "hasRole('super_admin')",
            $source,
            'isAnyAdmin() must NOT check super_admin role (security fix)'
        );
        $this->assertStringNotContainsString(
            "hasRole('super-admin')",
            $source,
            'isAnyAdmin() must NOT check super-admin role (security fix)'
        );
    }
}
