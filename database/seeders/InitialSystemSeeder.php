<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Center;
use App\Models\Tenant;
use App\Models\User;
use App\Services\TenantSetupService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class InitialSystemSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Creates the primary production-ready admin user and center.
     */
    public function run(): void
    {
        $this->call(PermissionsSeeder::class);

        // 1. Create Main Tenant
        $tenant = Tenant::firstOrCreate(
            ['slug' => 'khidmh'],
            [
                'name' => config('app.name', 'Khidmh Pro'),
            ]
        );

        app(TenantSetupService::class)->seedRolesForTenant($tenant->id);

        // 2. Create Main Center. We use a tenant-scoped unique
        // slug so the seeder is safe to re-run and idempotent
        // even when other tenants already have a "main" slug.
        $centerSlug = "main-{$tenant->id}";
        $center = Center::firstOrCreate(
            ['slug' => $centerSlug],
            [
                'tenant_id' => $tenant->id,
                'name' => __('messages.setup.main_center'),
                'is_active' => true,
            ]
        );

        // 3. Create Admin User
        $user = User::firstOrCreate(
            ['email' => 'admin@khidmh.pro'],
            [
                'name' => 'System Admin',
                'tenant_id' => $tenant->id,
                'current_center_id' => $center->id,
                'password' => Hash::make('11223344'),
                'email_verified_at' => now(),
            ]
        );

        // The main admin must be a real system admin so they can reach
        // /system/* (system-wide management panel). The tenant-scope
        // `super_admin` role is NOT enough after the 2026-07-06 security
        // hardening. `is_system_admin` is no longer mass-assignable, so
        // it must be set explicitly via forceFill.
        if (! $user->is_system_admin) {
            $user->forceFill(['is_system_admin' => true])->save();
        }

        // 4. Attach User to Center
        if (! $user->centers()->where('center_id', $center->id)->exists()) {
            $user->centers()->attach($center->id, ['tenant_id' => $tenant->id]);
        }

        // 5. Assign Super Admin Role
        // Note: RolesSeeder MUST have run before this for the tenants.
        app(PermissionRegistrar::class)->setPermissionsTeamId($tenant->id);

        $superAdminRole = Role::where('name', 'super_admin')
            ->where('tenant_id', $tenant->id)
            ->first();

        if ($superAdminRole) {
            if (! $user->hasRole('super_admin')) {
                $user->assignRole($superAdminRole);
            }
        }

        $this->call([
            MetadataSeeder::class,
            ServiceSeeder::class,
            VehicleConditionSeeder::class,
            VehicleMakeLogoSeeder::class,
            CommunicationTemplateSeeder::class,
        ]);

        $this->command->info(__('messages.setup.complete'));
        $this->command->info('📧 Email: admin@khidmh.pro');
        $this->command->info('🔑 Password: [As requested]');
    }
}
