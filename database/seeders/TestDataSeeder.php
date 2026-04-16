<?php

namespace Database\Seeders;

use App\Models\Center;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class TestDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create permissions
        $permissions = [
            'crm.customers.view',
            'crm.customers.create',
            'crm.customers.update',
            'crm.customers.delete',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        // Create tenant
        $tenant = Tenant::firstOrCreate(
            ['slug' => 'test-company'],
            ['name' => 'Test Company']
        );

        // Create center
        $center = Center::firstOrCreate(
            ['slug' => 'main', 'tenant_id' => $tenant->id],
            [
                'tenant_id' => $tenant->id,
                'name' => 'Main Branch',
                'is_active' => true,
            ]
        );

        // Create admin user
        $user = User::firstOrCreate(
            ['email' => 'admin@test.com'],
            [
                'name' => 'Admin User',
                'tenant_id' => $tenant->id,
                'current_center_id' => $center->id,
                'password' => bcrypt('password'),
            ]
        );

        // Set the team id for the assignment context (CRITICAL for Spatie Teams)
        app(\Spatie\Permission\PermissionRegistrar::class)->setPermissionsTeamId($tenant->id);

        // Attach user to center
        if (!$user->centers()->where('center_id', $center->id)->exists()) {
            $user->centers()->attach($center->id, ['tenant_id' => $tenant->id]);
        }

        // Assign Super Admin Role (This includes all permissions)
        $superAdminRole = \App\Models\Role::where('name', 'super_admin')
            ->where('tenant_id', $tenant->id)
            ->first();
            
        if ($superAdminRole) {
            $user->assignRole($superAdminRole);
        }

        // Give individual permissions just in case
        foreach ($permissions as $permission) {
            $user->givePermissionTo($permission);
        }

        $this->command->info('✅ Test data created successfully!');
        $this->command->info('📧 Email: admin@test.com');
        $this->command->info('🔑 Password: password');
    }
}
