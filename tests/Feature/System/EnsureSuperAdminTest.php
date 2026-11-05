<?php

declare(strict_types=1);

namespace Tests\Feature\System;

use App\Models\AdminUser;
use App\Models\Center;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * The `super_admin` middleware gates the destructive /system routes.
 *
 * ## Why there is no Spatie-role check
 *
 * Every tenant owner is auto-assigned the tenant-scoped `super_admin`
 * role at registration (RegisteredUserController::store). That role
 * therefore says nothing about platform-level privilege — it is true
 * for nearly every tenant owner in the system.
 *
 * The platform-level signal for a tenant `User` is the
 * `is_system_admin` flag, which is NOT tenant-settable: both
 * StoreUserRequest and UpdateUserRequest mark it `forbidden`.
 *
 * For an `AdminUser` the signal is the `role` column
 * (`super_admin` / `admin` / `support`).
 *
 * These tests pin both paths, so a future change cannot quietly widen
 * or narrow the destructive surface.
 */
class EnsureSuperAdminTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        foreach (['system.dashboard', 'crm.customers.view'] as $perm) {
            Permission::findOrCreate($perm, 'web');
        }
    }

    /**
     * A tenant owner: has the tenant-scoped `super_admin` role but is
     * NOT a platform admin.
     */
    private function tenantOwner(array $overrides = []): User
    {
        $tenant = Tenant::factory()->create();
        $center = Center::factory()->create(['tenant_id' => $tenant->id]);

        $user = User::factory()->create(array_merge([
            'tenant_id' => $tenant->id,
            'current_center_id' => $center->id,
        ], $overrides));
        $user->centers()->attach($center->id, ['tenant_id' => $tenant->id]);

        app(PermissionRegistrar::class)->setPermissionsTeamId($tenant->id);
        $user->assignRole('super_admin');
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return $user;
    }

    private function adminUser(string $role): AdminUser
    {
        return AdminUser::create([
            'name' => 'Admin '.$role,
            'email' => $role.'-'.uniqid().'@example.test',
            'password' => Hash::make('secret-password'),
            'role' => $role,
            'is_active' => true,
        ]);
    }

    // ── AdminUser paths ───────────────────────────────────────────────

    public function test_an_admin_user_with_the_super_admin_role_reaches_a_destructive_route(): void
    {
        $admin = $this->adminUser('super_admin');

        $response = $this->actingAs($admin, 'admin')
            ->get(route('system.backups.index'));

        $response->assertOk();
    }

    public function test_an_admin_user_with_the_support_role_is_refused_a_destructive_route(): void
    {
        $admin = $this->adminUser('support');

        $response = $this->actingAs($admin, 'admin')
            ->get(route('system.backups.index'));

        $response->assertForbidden();
    }

    public function test_an_admin_user_with_the_admin_role_is_refused_a_destructive_route(): void
    {
        $admin = $this->adminUser('admin');

        $response = $this->actingAs($admin, 'admin')
            ->get(route('system.backups.index'));

        $response->assertForbidden();
    }

    // ── Tenant User paths ─────────────────────────────────────────────

    public function test_a_tenant_owner_with_the_tenant_scoped_super_admin_role_is_refused(): void
    {
        // This is the 2026-07-06 privilege-escalation regression: the
        // tenant-scoped role must NOT open the platform surface.
        $owner = $this->tenantOwner();

        $this->assertTrue($owner->hasRole('super_admin'));

        $response = $this->actingAs($owner)->get(route('system.backups.index'));

        $response->assertForbidden();
    }

    public function test_a_tenant_user_flagged_is_system_admin_reaches_a_destructive_route(): void
    {
        // The `is_system_admin` flag IS the platform-level gate for a
        // tenant User — there is no additional Spatie role requirement,
        // because every tenant owner already carries the tenant-scoped
        // `super_admin` role and it carries no platform meaning.
        $user = $this->tenantOwner(['is_system_admin' => true]);

        $response = $this->actingAs($user)->get(route('system.backups.index'));

        $response->assertOk();
    }

    public function test_a_tenant_user_flagged_is_system_admin_without_the_tenant_role_still_reaches_the_route(): void
    {
        // Proves the role is genuinely NOT part of the check: removing
        // it must not lock the user out.
        $tenant = Tenant::factory()->create();
        $center = Center::factory()->create(['tenant_id' => $tenant->id]);

        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'current_center_id' => $center->id,
            'is_system_admin' => true,
        ]);
        $user->centers()->attach($center->id, ['tenant_id' => $tenant->id]);

        $this->assertFalse($user->hasRole('super_admin'));

        $response = $this->actingAs($user)->get(route('system.backups.index'));

        $response->assertOk();
    }

    // ── The flag must not be tenant-settable ──────────────────────────

    public function test_the_is_system_admin_flag_cannot_be_set_through_the_user_api(): void
    {
        $owner = $this->tenantOwner();

        $response = $this->actingAs($owner)
            ->post(route('settings.users.store'), [
                'name' => 'Escalation Attempt',
                'email' => 'escalation-attempt@example.test',
                'password' => 'Secret-password-1',
                'password_confirmation' => 'Secret-password-1',
                'is_system_admin' => true,
                'roles' => [],
            ]);

        $response->assertSessionHasErrors('is_system_admin');

        $this->assertDatabaseMissing('users', ['email' => 'escalation-attempt@example.test']);
    }
}
