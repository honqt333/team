<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\AdminUser;
use App\Models\Center;
use App\Models\Service;
use App\Models\Tenant;
use App\Models\User;
use App\Models\VehicleMake;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\InitialSystemSeeder;
use Database\Seeders\TestingEnvironmentSeeder;
use Database\Seeders\VehicleMakeLogoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use LogicException;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class InitialSystemRecoveryTest extends TestCase
{
    use RefreshDatabase;

    public function test_standalone_setup_creates_dependencies_and_is_repeatable(): void
    {
        Storage::fake('public');
        $this->seed(InitialSystemSeeder::class);
        $user = User::where('email', 'admin@khidmh.pro')->firstOrFail();
        $this->assertTrue((bool) $user->is_system_admin);
        app(PermissionRegistrar::class)->setPermissionsTeamId($user->tenant_id);
        $this->assertTrue($user->hasRole('super_admin'));
        $this->assertGreaterThan(0, Service::count());
        $this->assertGreaterThan(0, VehicleMake::whereNotNull('logo_path')->count());
        $counts = [Tenant::count(), User::count(), Service::count(), VehicleMake::count()];
        $this->seed(InitialSystemSeeder::class);
        $this->assertSame($counts, [Tenant::count(), User::count(), Service::count(), VehicleMake::count()]);
    }

    public function test_demo_and_initial_setup_preserve_the_approved_local_account_and_its_centers(): void
    {
        Storage::fake('public');
        $tenant = Tenant::factory()->create(['slug' => 'active-workshop']);
        $main = Center::factory()->create(['tenant_id' => $tenant->id, 'slug' => 'active-main-branch', 'is_main' => true, 'is_active' => true]);
        $second = Center::factory()->create(['tenant_id' => $tenant->id, 'is_main' => false]);
        $user = User::factory()->create(['email' => 'admin@khidmh.pro', 'tenant_id' => $tenant->id, 'current_center_id' => $main->id]);
        $user->centers()->attach([$main->id, $second->id], ['tenant_id' => $tenant->id]);
        $before = $user->fresh()->getAttributes();

        $this->seed(InitialSystemSeeder::class);
        $this->seed(TestingEnvironmentSeeder::class);
        $this->seed(InitialSystemSeeder::class);

        $this->assertSame($before, $user->fresh()->getAttributes());
        $this->assertEqualsCanonicalizing([$main->id, $second->id], $user->centers()->pluck('centers.id')->all());
        $this->assertTrue((bool) $main->fresh()->is_main);
        $this->assertDatabaseMissing('tenants', ['slug' => 'khidmh']);
    }

    public function test_unrelated_tenant_account_cannot_be_claimed_by_initial_setup(): void
    {
        $user = User::factory()->create(['email' => 'admin@khidmh.pro']);

        try {
            $this->seed(InitialSystemSeeder::class);
            $this->fail('A conflicting email must not grant cross-tenant membership.');
        } catch (LogicException $exception) {
            $this->assertStringContainsString('ownership', $exception->getMessage());
            $this->assertFalse((bool) $user->fresh()->is_system_admin);
            $this->assertDatabaseMissing('tenants', ['slug' => 'khidmh']);
        }
    }

    public function test_default_database_seeder_keeps_the_full_bootstrap_chain(): void
    {
        Storage::fake('public');
        $this->seed(DatabaseSeeder::class);
        $this->assertGreaterThan(0, Service::count());
        $this->assertGreaterThan(0, AdminUser::count());
    }

    public function test_logo_seed_preserves_existing_artwork_and_skips_tenant_makes(): void
    {
        Storage::fake('public');
        $existing = VehicleMake::create(['source' => 'system', 'name_ar' => 'موجود', 'name_en' => 'Existing', 'logo_path' => 'makes/original.svg']);
        $missing = VehicleMake::create(['source' => 'system', 'name_ar' => 'ناقص', 'name_en' => 'Missing']);
        $tenant = VehicleMake::create(['source' => 'tenant', 'tenant_id' => Tenant::factory()->create()->id, 'name_ar' => 'خاص', 'name_en' => 'Private']);
        Storage::disk('public')->put('makes/original.svg', 'original-artwork');
        $this->seed(VehicleMakeLogoSeeder::class);
        $this->assertSame('makes/original.svg', $existing->fresh()->logo_path);
        $this->assertSame('original-artwork', Storage::disk('public')->get('makes/original.svg'));
        $this->assertTrue(Storage::disk('public')->exists($missing->fresh()->logo_path));
        $this->assertNull($tenant->fresh()->logo_path);
    }
}
