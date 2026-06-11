<?php

use App\Models\Tenant;
use App\Models\Center;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

require __DIR__.'/../../vendor/autoload.php';

$app = require_once __DIR__.'/../../bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// 1. Create/Retrieve Tenant
$tenant = Tenant::firstOrCreate(
    ['slug' => 'carag'], 
    ['name' => 'Carag', 'status' => 'active']
);
$tenant->status = 'active'; 
$tenant->save();

// 2. Create/Retrieve Center
$center = Center::firstOrCreate(
    ['slug' => 'main', 'tenant_id' => $tenant->id], 
    ['tenant_id' => $tenant->id, 'name' => 'Main Center', 'is_active' => true]
);

// 3. Create/Retrieve Super Admin User
$user = User::where('email', 'admin@admin.com')->first();
if (!$user) {
    $user = new User();
    $user->name = 'Super Admin';
    $user->email = 'admin@admin.com';
    $user->password = bcrypt('11223355');
    $user->tenant_id = $tenant->id;
    $user->current_center_id = $center->id;
    $user->is_active = true;
    $user->email_verified_at = now();
    $user->save();
} else {
    $user->password = bcrypt('11223355');
    $user->tenant_id = $tenant->id;
    $user->current_center_id = $center->id;
    $user->is_active = true;
    $user->email_verified_at = now();
    $user->save();
}

// 4. Attach User to Center
if (!$user->centers()->where('center_id', $center->id)->exists()) {
    $user->centers()->attach($center, ['tenant_id' => $tenant->id]);
}

// Seed all permissions globally
$allPermissions = \App\Support\Permissions::all();
foreach ($allPermissions as $permission) {
    Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
}

// Clear Spatie permissions cache to make sure it reads the newly inserted permissions
app(PermissionRegistrar::class)->forgetCachedPermissions();

// Seed roles for this tenant
$tenantSetupService = new \App\Services\TenantSetupService();
$tenantSetupService->seedRolesForTenant($tenant->id);

// Assign Super Admin Role to this user
app(PermissionRegistrar::class)->setPermissionsTeamId($tenant->id);

$superAdminRole = Role::where('name', 'super_admin')
    ->where('tenant_id', $tenant->id)
    ->first();

if ($superAdminRole) {
    if (!$user->hasRole('super_admin')) {
        $user->assignRole($superAdminRole);
    }
}

echo "SETUP DONE: Super Admin user admin@admin.com created and configured successfully.\n";
