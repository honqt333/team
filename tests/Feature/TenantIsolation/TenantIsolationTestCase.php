<?php

declare(strict_types=1);

namespace Tests\Feature\TenantIsolation;

use App\Models\Center;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\PermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Shared scaffolding for TenantIsolation contract tests.
 *
 * Pattern: build two completely independent tenants (A & B) with their own
 * centers and users, create one record in each, then act as a user from A and
 * assert the record in B is invisible / inaccessible.
 *
 * Subclasses only need to define the model class under test and a factory call
 * that returns a tenant-owned row; everything else is provided here.
 */
abstract class TenantIsolationTestCase extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenantA;

    protected Tenant $tenantB;

    protected Center $centerA;

    protected Center $centerB;

    protected User $userA;

    protected User $userB;

    protected function setUp(): void
    {
        parent::setUp();

        // Reset Spatie permission cache so the role seeding works each test.
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Seed permissions so any role/permission assignment in factories works.
        $this->seed(PermissionsSeeder::class);

        // Tenant A
        $this->tenantA = Tenant::factory()->create(['name' => 'Tenant A']);
        $this->centerA = Center::factory()->create([
            'tenant_id' => $this->tenantA->id,
            'name' => 'Center A',
        ]);
        $this->userA = $this->createUserInContext($this->tenantA, $this->centerA);

        // Tenant B
        $this->tenantB = Tenant::factory()->create(['name' => 'Tenant B']);
        $this->centerB = Center::factory()->create([
            'tenant_id' => $this->tenantB->id,
            'name' => 'Center B',
        ]);
        $this->userB = $this->createUserInContext($this->tenantB, $this->centerB);
    }

    protected function createUserInContext(Tenant $tenant, Center $center): User
    {
        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'current_center_id' => $center->id,
        ]);
        $user->centers()->attach($center->id, ['tenant_id' => $tenant->id]);

        return $user;
    }

    /**
     * Subclasses implement this: a closure that returns a record created in
     * tenant B (and optionally also in A via the second return).
     *
     * The first element is the row id from tenant B that should NOT leak to A.
     */
    abstract protected function createRecordInTenantB(): mixed;
}
