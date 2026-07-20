<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\InitialSystemSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class InitialSystemSeederTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Regression: InitialSystemSeeder used to silently no-op the
     * role-assignment step when run in isolation on a fresh DB,
     * because the Spatie permissions and per-tenant roles did not
     * exist yet. The admin user was created but had no roles, so
     * every authenticated request 403'd.
     *
     * The seeder now calls PermissionsSeeder and seeds the
     * per-tenant roles itself, so it must work standalone.
     */
    public function test_seeder_creates_admin_with_super_admin_role_in_isolation(): void
    {
        $this->seed(InitialSystemSeeder::class);

        $tenant = Tenant::where('slug', 'khidmh')->first();
        $this->assertNotNull($tenant, 'khidmh tenant must be created');

        $user = User::where('email', 'admin@khidmh.pro')->first();
        $this->assertNotNull($user, 'admin user must be created');
        $this->assertTrue((bool) $user->is_system_admin, 'main admin must be a system admin to reach /system/*');

        // The role assignment must succeed — this is the regression.
        app(PermissionRegistrar::class)->setPermissionsTeamId($tenant->id);
        $user->refresh();

        $this->assertTrue(
            $user->hasRole('super_admin'),
            'admin user must have super_admin role after InitialSystemSeeder runs in isolation'
        );
    }

    public function test_seeder_is_idempotent_when_run_twice(): void
    {
        $this->seed(InitialSystemSeeder::class);
        $firstUserId = User::where('email', 'admin@khidmh.pro')->first()->id;
        $firstTenantId = Tenant::where('slug', 'khidmh')->first()->id;

        // Run again — must not duplicate rows or throw.
        $this->seed(InitialSystemSeeder::class);

        $this->assertSame(1, User::where('email', 'admin@khidmh.pro')->count(), 'admin user must not be duplicated');
        $this->assertSame(1, Tenant::where('slug', 'khidmh')->count(), 'khidmh tenant must not be duplicated');

        $user = User::find($firstUserId);
        app(PermissionRegistrar::class)->setPermissionsTeamId($firstTenantId);
        $user->refresh();
        $this->assertTrue($user->hasRole('super_admin'), 'admin must still have super_admin role after re-run');
    }
}
