<?php

namespace Database\Seeders;

use App\Support\Permissions;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class PermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * 
     * This seeder uses the centralized Permissions class to ensure
     * consistency between defined permissions and database records.
     */
    public function run(): void
    {
        // Get all permissions from the centralized registry
        $allPermissions = Permissions::all();

        // Create all permissions in the database
        foreach ($allPermissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web'
            ]);
        }

        $this->command->info('Created ' . count($allPermissions) . ' permissions.');
        
        // Display permissions by module for better visibility
        $byModule = Permissions::byModule();
        foreach ($byModule as $module => $permissions) {
            $this->command->info("  [{$module}]: " . count($permissions) . ' permissions');
        }
    }
}
