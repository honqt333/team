<?php

namespace Tests;

use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Spatie\Permission\PermissionRegistrar;

abstract class TestCase extends BaseTestCase
{
    /**
     * Act as the given user with proper team permissions context.
     */
    protected function actingAsWithTeam(User $user): static
    {
        // Reset cached permissions
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        
        // Set the permissions team context based on user's tenant
        if ($user->tenant_id) {
            app(PermissionRegistrar::class)->setPermissionsTeamId($user->tenant_id);
        }

        return $this->actingAs($user);
    }
}
