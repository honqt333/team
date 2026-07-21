<?php

declare(strict_types=1);

namespace App\Models;

use Spatie\Permission\Models\Role as SpatieRole;

// @bypass-tenancy-scanner - Spatie role isolation enforced via PermissionRegistrar::setPermissionsTeamId
class Role extends SpatieRole
{
    protected $fillable = [
        'name',
        'guard_name',
        'label_ar',
        'label_en',
        'description',
        'tenant_id',
    ];
}
