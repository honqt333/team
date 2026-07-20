<?php

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
     *
     * NOTE: This seeder depends on the Spatie permission tables being
     * populated (PermissionsSeeder) and the per-tenant roles existing
     * (RolesSeeder, via TenantSetupService::seedRolesForTenant). If a
     * developer runs `php artisan db:seed --class=InitialSystemSeeder`
     * in isolation — for example on a fresh local DB before CI — the
     * role-assignment step below would silently no-op because the
     * `super_admin` role row does not exist yet, and the admin user
     * would be created without any permissions. We call the prereq
     * seeders defensively so this seeder is always safe to run alone.
     */
    public function run(): void
    {
        // 0. Prerequisite: Spatie permission rows. RolesSeeder will
        //    also create these, but calling it before the tenant
        //    exists hits its "No tenants found" fallback path which
        //    doesn't seed per-tenant roles. So we call
        //    PermissionsSeeder explicitly, then we seed the roles
        //    for the khidmh tenant ourselves right after creating
        //    it below.
        $this->call(PermissionsSeeder::class);

        // 1. Create Main Tenant
        $tenant = Tenant::firstOrCreate(
            ['slug' => 'khidmh'],
            [
                'name' => 'Khidmh Pro',
            ]
        );

        // 2. Create Main Center. We use a tenant-scoped unique
        // slug so the seeder is safe to re-run and idempotent
        // even when other tenants already have a "main" slug.
        $centerSlug = "main-{$tenant->id}";
        $center = Center::firstOrCreate(
            ['slug' => $centerSlug],
            [
                'tenant_id' => $tenant->id,
                'name' => 'المركز الرئيسي',
                'is_active' => true,
            ]
        );

        // 2a. Seed the per-tenant roles (super_admin + others) so the
        //     role-assignment step below can find the `super_admin`
        //     role row. We do this here rather than calling
        //     RolesSeeder, because RolesSeeder iterates the existing
        //     tenants table and would have run before this tenant
        //     was created (when invoked as a prereq).
        $tenantSetupService = new TenantSetupService;
        $tenantSetupService->seedRolesForTenant($tenant->id);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        // 3. Create Admin User
        $user = User::firstOrCreate(
            ['email' => 'admin@khidmh.pro'],
            [
                'name' => 'System Admin',
                'tenant_id' => $tenant->id,
                'current_center_id' => $center->id,
                'password' => Hash::make('11223344'),
                'email_verified_at' => now(),
                // The main admin must be a real system admin so they
                // can reach /system/* (system-wide management panel).
                // The tenant-scope `super_admin` role is NOT enough
                // after the 2026-07-06 security hardening.
                'is_system_admin' => true,
            ]
        );

        // Backfill the is_system_admin flag on the row if it was
        // created by an older version of this seeder that did not
        // set it. Without this, the existing main admin can no
        // longer reach /system/* after the 2026-07-06 security
        // hardening.
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

        $this->command->info('✅ Initial System Setup Complete!');
        $this->command->info('📧 Email: admin@khidmh.pro');
        $this->command->info('🔑 Password: [As requested]');

        // 6. Chain the rest of the bootstrap so this seeder leaves the
        //    DB in a state where every admin page renders correctly.
        //    Order matters: metadata before services (services may
        //    reference departments/units seeded above), conditions
        //    after services (independent), communication templates
        //    last (purely static content).
        //
        //    Note: VehicleDataSeeder (which depends on
        //    storage/app/temp_data/car-list.json) is NOT called here
        //    because that JSON is not in the repo. VehicleMakesSeeder
        //    (chained via MetadataSeeder) provides the hardcoded
        //    make + model list, which is enough for the UI to work.
        $this->call([
            MetadataSeeder::class,
            ServiceSeeder::class,
            VehicleConditionSeeder::class,
            CommunicationTemplateSeeder::class,
        ]);
    }
}
