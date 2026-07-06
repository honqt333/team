<?php

namespace Database\Seeders;

use App\Models\Center;
use App\Models\Tenant;
use App\Models\User;
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
        if (!$user->centers()->where('center_id', $center->id)->exists()) {
            $user->centers()->attach($center->id, ['tenant_id' => $tenant->id]);
        }

        // 5. Assign Super Admin Role
        // Note: RolesSeeder MUST have run before this for the tenants.
        app(PermissionRegistrar::class)->setPermissionsTeamId($tenant->id);
        
        $superAdminRole = Role::where('name', 'super_admin')
            ->where('tenant_id', $tenant->id)
            ->first();
            
        if ($superAdminRole) {
            if (!$user->hasRole('super_admin')) {
                $user->assignRole($superAdminRole);
            }
        }

        $this->command->info('✅ Initial System Setup Complete!');
        $this->command->info('📧 Email: admin@khidmh.pro');
        $this->command->info('🔑 Password: [As requested]');
    }
}
