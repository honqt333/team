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
                'is_active' => true,
            ]
        );

        // 2. Create Main Center
        $center = Center::firstOrCreate(
            ['slug' => 'main', 'tenant_id' => $tenant->id],
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
            ]
        );

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
