<?php

use App\Models\Tenant;
use App\Models\Center;
use App\Models\User;

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$user = User::where('email', 'admin@admin.com')->first();
if (!$user) {
    $user = User::factory()->create(['name' => 'Admin', 'email' => 'admin@admin.com', 'password' => bcrypt('password')]);
}

$tenant = Tenant::firstOrCreate(
    ['slug' => 'carag'], 
    ['name' => 'Carag', 'status' => 'active']
);
$tenant->status = 'active'; 
$tenant->save();

$center = Center::firstOrCreate(
    ['slug' => 'main'], 
    ['tenant_id' => $tenant->id, 'name' => 'Main Center', 'is_active' => true]
);

$user->tenant_id = $tenant->id;
$user->current_center_id = $center->id;
$user->save();

if (!$user->centers()->where('center_id', $center->id)->exists()) {
    $user->centers()->attach($center, ['tenant_id' => $tenant->id]);
}


use Spatie\Permission\Models\Permission;

// Reset cached roles and permissions
app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

$perms = [
    'crm.customers.view',
    'crm.customers.create',
    'crm.customers.edit',
    'crm.customers.delete',
];

foreach ($perms as $perm) {
    Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
    $user->givePermissionTo($perm);
}

echo "SETUP DONE: User {$user->email} linked to Tenant {$tenant->name} and Center {$center->name} with Permissions.\n";
