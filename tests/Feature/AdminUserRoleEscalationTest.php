<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\AdminUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Tests for the system-admin role-escalation fix (audit finding #30).
 *
 * THE BUG
 *
 * Creating and editing system-admin accounts lived in the general
 * `system.admin` route group, which every admin account passes:
 *
 *   - AdminUsersController::store() accepted `role` from
 *     super_admin|admin|support and called AdminUser::create() with
 *     no authorization check at all.
 *   - AdminUsersController::update() accepted the same field and
 *     called AdminUser::update() — again with no check.
 *
 * Because `super_admin` bypasses the permission list entirely
 * (AdminUser::hasPermission), an account holding the read-oriented
 * `support` role could:
 *
 *   1. POST /system/admin-users with role=super_admin and mint
 *      itself a brand new platform owner account, or
 *   2. PUT /system/admin-users/{itself} with role=super_admin and
 *      promote its own account in place.
 *
 * Either way the escalation bypassed the per-operation guards that
 * `EnsureSuperAdmin` puts on the genuinely destructive system-panel
 * actions (delete tenant, impersonate, ...).
 *
 * THE FIX
 *
 *   - The write routes moved into a `super_admin` middleware group.
 *   - AdminUsersController repeats the check in the controller so a
 *     future route reshuffle cannot silently reopen the hole.
 *
 * These tests pin both layers.
 */
class AdminUserRoleEscalationTest extends TestCase
{
    use RefreshDatabase;

    private function makeAdmin(string $role, string $email): AdminUser
    {
        return AdminUser::create([
            'name' => ucfirst($role).' User',
            'email' => $email,
            'password' => Hash::make('password'),
            'role' => $role,
            'is_active' => true,
        ]);
    }

    /**
     * The core exploit: a support account promoting itself.
     */
    public function test_support_admin_cannot_promote_itself_to_super_admin(): void
    {
        $support = $this->makeAdmin('support', 'support@example.com');

        $response = $this->actingAs($support, 'admin')
            ->put('/system/admin-users/'.$support->id, [
                'name' => $support->name,
                'email' => $support->email,
                'role' => 'super_admin',
                'is_active' => true,
            ]);

        $response->assertForbidden();

        $this->assertSame(
            'support',
            $support->fresh()->role,
            'A support account must not be able to promote itself to super_admin.'
        );
    }

    /**
     * The core exploit, second shape: minting a new super_admin.
     */
    public function test_support_admin_cannot_create_a_super_admin_account(): void
    {
        $support = $this->makeAdmin('support', 'support@example.com');

        $response = $this->actingAs($support, 'admin')
            ->post('/system/admin-users', [
                'name' => 'Backdoor Owner',
                'email' => 'backdoor@example.com',
                'password' => 'Password!12345',
                'role' => 'super_admin',
                'is_active' => true,
            ]);

        $response->assertForbidden();

        $this->assertNull(
            AdminUser::where('email', 'backdoor@example.com')->first(),
            'A support account must not be able to create a super_admin account.'
        );
    }

    /**
     * The operational `admin` role is not privileged either.
     */
    public function test_operational_admin_cannot_create_a_super_admin_account(): void
    {
        $admin = $this->makeAdmin('admin', 'ops@example.com');

        $response = $this->actingAs($admin, 'admin')
            ->post('/system/admin-users', [
                'name' => 'Another Owner',
                'email' => 'another-owner@example.com',
                'password' => 'Password!12345',
                'role' => 'super_admin',
                'is_active' => true,
            ]);

        $response->assertForbidden();

        $this->assertNull(AdminUser::where('email', 'another-owner@example.com')->first());
    }

    /**
     * Deleting another admin account is equally privileged.
     */
    public function test_support_admin_cannot_delete_another_admin(): void
    {
        $support = $this->makeAdmin('support', 'support@example.com');
        $victim = $this->makeAdmin('admin', 'victim@example.com');

        $response = $this->actingAs($support, 'admin')
            ->delete('/system/admin-users/'.$victim->id);

        $response->assertForbidden();

        $this->assertNotNull(
            AdminUser::find($victim->id),
            'A support account must not be able to delete another admin account.'
        );
    }

    /**
     * A support account may still be able to update another account's
     * non-privileged details? No — the whole write surface is
     * super_admin-only, because the same request body carries the role
     * field. A partial guard on the role field alone would still let a
     * support account flip `is_active` and lock out a colleague.
     */
    public function test_support_admin_cannot_edit_another_admin_at_all(): void
    {
        $support = $this->makeAdmin('support', 'support@example.com');
        $victim = $this->makeAdmin('admin', 'victim@example.com');

        $response = $this->actingAs($support, 'admin')
            ->put('/system/admin-users/'.$victim->id, [
                'name' => 'Renamed By Support',
                'email' => $victim->email,
                'role' => 'support',
                'is_active' => false,
            ]);

        $response->assertForbidden();

        $this->assertSame(
            'victim@example.com',
            $victim->fresh()->email,
            'A support account must not be able to edit another admin account.'
        );
        $this->assertTrue((bool) $victim->fresh()->is_active);
    }

    /**
     * The guard must not lock out a genuine super_admin.
     */
    public function test_super_admin_can_still_create_an_admin_account(): void
    {
        $super = $this->makeAdmin('super_admin', 'owner@example.com');

        $response = $this->actingAs($super, 'admin')
            ->post('/system/admin-users', [
                'name' => 'New Operator',
                'email' => 'operator@example.com',
                'password' => 'Password!12345',
                'role' => 'admin',
                'is_active' => true,
            ]);

        $response->assertRedirect(route('system.admin-users.index'));

        $created = AdminUser::where('email', 'operator@example.com')->first();
        $this->assertNotNull($created, 'A super_admin must still be able to create admin accounts.');
        $this->assertSame('admin', $created->role);
    }

    public function test_super_admin_can_still_update_an_admin_account(): void
    {
        $super = $this->makeAdmin('super_admin', 'owner@example.com');
        $target = $this->makeAdmin('support', 'promote-me@example.com');

        $response = $this->actingAs($super, 'admin')
            ->put('/system/admin-users/'.$target->id, [
                'name' => $target->name,
                'email' => $target->email,
                'role' => 'admin',
                'is_active' => true,
            ]);

        $response->assertSessionHasNoErrors();
        $this->assertSame('admin', $target->fresh()->role);
    }

    /**
     * Reading the list stays available to every system admin — the fix
     * must not turn the admin roster into a super_admin-only page.
     */
    public function test_support_admin_can_still_read_the_admin_list(): void
    {
        $support = $this->makeAdmin('support', 'support@example.com');

        $response = $this->actingAs($support, 'admin')->get('/system/admin-users');

        $response->assertOk();
    }

    /**
     * The controller guard is a second layer, independent of the
     * route middleware. It is asserted here by calling the controller
     * action directly with no route middleware in the path — if the
     * middleware is ever removed from the route definition, this test
     * keeps the hole shut.
     */
    public function test_controller_guard_rejects_non_super_admin_even_without_route_middleware(): void
    {
        $support = $this->makeAdmin('support', 'support@example.com');

        $this->actingAs($support, 'admin');

        $response = $this->withoutMiddleware()
            ->post('/system/admin-users', [
                'name' => 'Sneaky',
                'email' => 'sneaky@example.com',
                'password' => 'Password!12345',
                'role' => 'super_admin',
                'is_active' => true,
            ]);

        $response->assertForbidden();

        $this->assertNull(AdminUser::where('email', 'sneaky@example.com')->first());
    }
}
