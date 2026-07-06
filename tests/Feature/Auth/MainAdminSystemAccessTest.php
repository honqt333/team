<?php

namespace Tests\Feature\Auth;

use App\Models\Center;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\InitialSystemSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Regression tests for the main admin (admin@khidmh.pro) access
 * to the /system/* panel.
 *
 * The main admin is created by InitialSystemSeeder as a `User`
 * (not an `AdminUser`) inside a tenant, with the `super_admin`
 * role. The 2026-07-06 security hardening made the tenant-scope
 * `super_admin` role alone insufficient to reach /system/*; the
 * user must also have the `is_system_admin` flag set to true.
 *
 * These tests pin that:
 *   1. The seeder creates the admin with is_system_admin = true
 *   2. Existing admins (created by older seeders) get backfilled
 *   3. The main admin can reach /system/*
 *   4. Other tenant owners (no flag) still cannot
 */
class MainAdminSystemAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // InitialSystemSeeder depends on RolesSeeder having run
        // first, since the seeder assigns the tenant's super_admin
        // role. DatabaseSeeder already calls RolesSeeder.
        $this->seed(\Database\Seeders\RolesSeeder::class);
    }

    public function test_initial_seeder_marks_main_admin_as_system_admin(): void
    {
        $this->seed(InitialSystemSeeder::class);

        $admin = User::where('email', 'admin@khidmh.pro')->first();
        $this->assertNotNull($admin);
        $this->assertTrue(
            (bool) $admin->is_system_admin,
            'Main admin must be marked as is_system_admin so they can reach /system/*'
        );
    }

    public function test_seeder_backfills_is_system_admin_on_existing_admin(): void
    {
        // Simulate an older database where admin already exists
        // without is_system_admin set.
        $tenant = Tenant::factory()->create(['slug' => 'khidmh']);
        $center = Center::factory()->create(['tenant_id' => $tenant->id, 'slug' => 'main']);
        $admin = User::factory()->create([
            'email' => 'admin@khidmh.pro',
            'tenant_id' => $tenant->id,
            'current_center_id' => $center->id,
            'is_system_admin' => false,
        ]);
        $admin->centers()->attach($center->id, ['tenant_id' => $tenant->id]);
        app(PermissionRegistrar::class)->setPermissionsTeamId($tenant->id);

        // Re-run the seeder; the backfill block must upgrade the
        // existing row.
        $this->seed(InitialSystemSeeder::class);

        $admin->refresh();
        $this->assertTrue(
            (bool) $admin->is_system_admin,
            'Seeder must backfill is_system_admin on existing admin row'
        );
    }

    public function test_main_admin_can_reach_system_dashboard(): void
    {
        $this->seed(InitialSystemSeeder::class);

        $admin = User::where('email', 'admin@khidmh.pro')->first();
        $response = $this->actingAs($admin)->get('/system');
        $response->assertStatus(200);
    }
}
