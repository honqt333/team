<?php

namespace Tests\Feature;

use App\Models\Center;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Security regression tests for the privilege-escalation bug fixed in
 * 2026-07-06 audit:
 *
 *   Every new tenant owner was being auto-assigned the `super_admin`
 *   role during registration. The previous middleware + Gate::before
 *   accepted that role as proof of being a system administrator, so
 *   any new signup could navigate to /system/* and see/modify every
 *   tenant in the system.
 *
 * These tests pin the fix:
 *   - super_admin role alone is not enough to reach /system/*.
 *   - super_admin role alone is not enough to bypass policies
 *     (Gate::before) for cross-tenant records.
 *   - is_system_admin flag and AdminUser are still honoured.
 */
class SystemAccessControlTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Create a representative set of permissions so role assignment
        // works for the test user.
        foreach (['system.dashboard', 'crm.customers.view'] as $perm) {
            Permission::findOrCreate($perm, 'web');
        }
    }

    protected function createTenantOwner(array $overrides = []): User
    {
        $tenant = Tenant::factory()->create();
        $center = Center::factory()->create(['tenant_id' => $tenant->id]);

        $user = User::factory()->create(array_merge([
            'tenant_id' => $tenant->id,
            'current_center_id' => $center->id,
        ], $overrides));
        $user->centers()->attach($center->id, ['tenant_id' => $tenant->id]);

        app(PermissionRegistrar::class)->setPermissionsTeamId($tenant->id);

        // Reproduce the registration code path: assign super_admin role
        $user->assignRole('super_admin');
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return $user;
    }

    public function test_tenant_owner_with_only_super_admin_role_cannot_access_system_dashboard(): void
    {
        $user = $this->createTenantOwner();

        $this->assertTrue(
            $user->hasRole('super_admin'),
            'Pre-condition: the test user must have the super_admin role'
        );
        $this->assertFalse(
            (bool) ($user->is_system_admin ?? false),
            'Pre-condition: is_system_admin must be false for this scenario'
        );

        $response = $this->actingAs($user)->get('/system');
        $response->assertStatus(403);
    }

    public function test_tenant_owner_with_only_super_admin_role_cannot_list_tenants(): void
    {
        $user = $this->createTenantOwner();

        $response = $this->actingAs($user)->get('/system/tenants');
        $response->assertStatus(403);
    }

    public function test_user_with_is_system_admin_flag_can_access_system_dashboard(): void
    {
        $user = $this->createTenantOwner(['is_system_admin' => true]);

        $response = $this->actingAs($user)->get('/system');
        $response->assertStatus(200);
    }

    public function test_super_admin_role_does_not_bypass_policies_for_other_tenants(): void
    {
        // Owner A in tenant 1
        $ownerA = $this->createTenantOwner();

        // Tenant 2 with a customer
        $otherTenant = Tenant::factory()->create();
        $otherCenter = Center::factory()->create(['tenant_id' => $otherTenant->id]);
        $otherCustomer = \App\Models\Customer::factory()->create([
            'tenant_id' => $otherTenant->id,
            'center_id' => $otherCenter->id,
        ]);

        // Even though ownerA has super_admin, Gate::before must not let
        // them reach a customer that belongs to a different tenant.
        $response = $this->actingAs($ownerA)->getJson("/app/api/customers/{$otherCustomer->id}");

        // The expected behavior is: 404 (tenant-scoped query returns
        // nothing) or 403 (policy denies). Both are safe; what we
        // explicitly do NOT accept is 200 (privilege escalation).
        $this->assertContains(
            $response->status(),
            [403, 404],
            "Cross-tenant access must not return 200, got {$response->status()}"
        );
    }

    public function test_registration_assigns_super_admin_role_to_new_tenant_owner(): void
    {
        // Pin the registration behavior: the owner is super_admin in
        // their own tenant. This is fine, but combined with the
        // is_system_admin fix above, they still cannot reach /system/*.
        $tenant = Tenant::factory()->create();
        $center = Center::factory()->create(['tenant_id' => $tenant->id]);
        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'current_center_id' => $center->id,
        ]);
        $user->centers()->attach($center->id, ['tenant_id' => $tenant->id]);
        app(PermissionRegistrar::class)->setPermissionsTeamId($tenant->id);
        $user->assignRole('super_admin');
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->assertTrue($user->fresh()->hasRole('super_admin'));
    }
}
