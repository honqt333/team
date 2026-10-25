<?php

declare(strict_types=1);

use App\Models\Tenant;
use App\Services\TenantSetupService;
use App\Support\Permissions;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        // Seed all permissions defined in Permissions class
        foreach (Permissions::all() as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web',
            ]);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        // Update roles for all existing tenants so roles receive the newly added permissions
        $service = app(TenantSetupService::class);

        foreach (Tenant::all() as $tenant) {
            $service->seedRolesForTenant($tenant->id);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Keep permissions intact
    }
};
