<?php

namespace Tests\Feature;

use App\Models\CommunicationTemplate;
use App\Models\Department;
use App\Models\Service;
use App\Models\Tenant;
use App\Models\User;
use App\Models\VehicleColor;
use App\Models\VehicleConditionItem;
use App\Models\VehicleMake;
use App\Models\VehicleModel;
use Database\Seeders\InitialSystemSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
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

    /**
     * Regression: communication templates, services, vehicle
     * conditions, and reference metadata used to require running
     * `php artisan db:seed` (the full DatabaseSeeder) before the
     * admin pages rendered any data. Running only
     * InitialSystemSeeder in isolation left /system/communication/
     * templates and other admin pages empty.
     *
     * The seeder now chains the dependent seeders so the DB is
     * fully bootstrapped after a single call.
     */
    public function test_seeder_populates_communication_templates_and_reference_data(): void
    {
        $this->seed(InitialSystemSeeder::class);

        // Communication templates — without these the
        // /system/communication/templates page is empty and
        // notifications fall back to hard-coded HTML.
        $this->assertGreaterThan(
            0,
            CommunicationTemplate::count(),
            'communication templates must be seeded so /system/communication/templates is non-empty'
        );
        $this->assertTrue(
            CommunicationTemplate::where('code', '2fa_verification')->exists(),
            '2FA template is required by the auth flow'
        );

        // Service catalog — without these the work order "add
        // service" dropdown is empty.
        $this->assertGreaterThan(0, Service::count(), 'service catalog must be seeded');
        $this->assertGreaterThan(0, Department::count(), 'departments must be seeded');

        // Vehicle condition report — required by the work order
        // inspection tab on every new work order.
        $this->assertGreaterThan(0, VehicleConditionItem::count(), 'vehicle condition items must be seeded');

        // Vehicle catalog — the /app/customers and /app/vehicles
        // "select make" / "select model" dropdowns are empty
        // without these. VehicleMakesSeeder (chained via
        // MetadataSeeder) provides hardcoded makes + models.
        $this->assertGreaterThan(0, VehicleMake::count(), 'vehicle makes must be seeded');
        $this->assertGreaterThan(0, VehicleModel::count(), 'vehicle models must be seeded');
        $this->assertGreaterThan(0, VehicleColor::count(), 'vehicle colors must be seeded');

        // Logo files — VehicleMakeLogoSeeder generates a
        // placeholder SVG for every make. Without it the vehicles
        // list page renders an empty image placeholder.
        $makesWithLogos = VehicleMake::whereNotNull('logo_path')->count();
        $this->assertSame(
            VehicleMake::count(),
            $makesWithLogos,
            'every vehicle make must have a logo_path set after InitialSystemSeeder'
        );
        $firstMake = VehicleMake::whereNotNull('logo_path')->first();
        $this->assertTrue(
            Storage::disk('public')->exists($firstMake->logo_path),
            'logo file must exist on the public disk: '.$firstMake->logo_path
        );
    }
}
