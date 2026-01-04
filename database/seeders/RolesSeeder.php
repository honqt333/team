<?php

namespace Database\Seeders;

use App\Support\Permissions;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * 
     * Creates default system roles with their permissions.
     * These roles serve as templates for tenant-specific roles.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Create all permissions (Global)
        $allPermissions = Permissions::all();
        foreach ($allPermissions as $permission) {
            Permission::firstOrCreate(
                ['name' => $permission, 'guard_name' => 'web'],
                ['name' => $permission, 'guard_name' => 'web']
            );
        }
        $this->command->info("Created " . count($allPermissions) . " permissions.");

        // Get all tenants
        $tenants = \App\Models\Tenant::all();
        
        if ($tenants->isEmpty()) {
            $this->command->warn("No tenants found. Seeding roles for default tenant (ID 1) as fallback.");
            // Create a dummy tenant object/ID or just force ID 1 if that's the convention
             $tenants = collect([(object)['id' => 1]]); 
        }

        $tenantSetupService = new \App\Services\TenantSetupService();

        foreach ($tenants as $tenant) {
            $tenantSetupService->seedRolesForTenant($tenant->id);
            $this->command->info("Seeded roles for Tenant ID: {$tenant->id}");
        }

        // Assign Super Admin to the FIRST user of EACH tenant
        foreach ($tenants as $tenant) {
            $firstUser = \App\Models\User::where('tenant_id', $tenant->id)->orderBy('id')->first();
            
            if ($firstUser) {
                $superAdminRole = \App\Models\Role::where('name', 'super_admin')
                    ->where('tenant_id', $tenant->id)
                    ->first();
                    
                if ($superAdminRole) {
                    // Set the team id for the assignment context
                    app(\Spatie\Permission\PermissionRegistrar::class)->setPermissionsTeamId($tenant->id);
                    
                    if (!$firstUser->hasRole('super_admin')) {
                        $firstUser->assignRole($superAdminRole);
                        $this->command->info("User {$firstUser->id} assigned to Super Admin Role (Tenant {$tenant->id}).");
                    }
                }
            }
        }
    }
}
