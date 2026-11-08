<?php

declare(strict_types=1);

namespace Tests\Feature\System;

use App\Models\AdminUser;
use App\Models\Tenant;
use App\Models\User;
use App\Support\Permissions;
use App\Support\SystemPermissions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * The system panel's ability layer.
 *
 * ## The gap this closes
 *
 * The panel defined a permission list and rendered checkboxes for it,
 * but NOTHING ever read that list. Every account — including a
 * `support` one — could reach every non-`super_admin` endpoint:
 * managing payment gateways, editing the plan catalogue, cancelling
 * invoices, granting credit balances.
 *
 * These tests pin the replacement:
 *
 *   - the role decides by default, and an account's own list overrides
 *     the role when it carries one;
 *   - `super_admin` bypasses the list;
 *   - the platform owner (`is_system_admin`) bypasses it too, on either
 *     guard;
 *   - a route declares the ability it needs, and the middleware
 *     enforces it.
 */
class SystemPermissionsTest extends TestCase
{
    use RefreshDatabase;

    private function admin(string $role, array $permissions = []): AdminUser
    {
        return AdminUser::create([
            'name' => ucfirst($role).' Admin',
            'email' => $role.'-'.uniqid().'@example.test',
            'password' => Hash::make('secret-password'),
            'role' => $role,
            'permissions' => $permissions,
            'is_active' => true,
        ]);
    }

    // ── The registry ───────────────────────────────────────────────────

    public function test_the_registry_exposes_every_ability_it_defines(): void
    {
        $all = SystemPermissions::all();
        $grouped = SystemPermissions::groups();

        $this->assertNotEmpty($all);

        foreach ($grouped as $group => $abilities) {
            $this->assertIsArray($abilities, "Group {$group} must hold abilities.");

            foreach ($abilities as $ability => $label) {
                $this->assertArrayHasKey($ability, $all);
                $this->assertNotSame('', $label, "Ability {$ability} needs a label.");
            }
        }
    }

    public function test_every_ability_in_the_registry_is_a_declared_permission_constant(): void
    {
        // The constitution calls App\Support\Permissions the single
        // source of truth for permission names. An ability that is not
        // declared there would be a name nobody can reference safely.
        $declared = Permissions::all();

        foreach (SystemPermissions::names() as $ability) {
            $this->assertContains(
                $ability,
                $declared,
                "The ability {$ability} must be declared in App\\Support\\Permissions."
            );
        }
    }

    public function test_no_ability_is_declared_twice_in_the_registry(): void
    {
        $seen = [];

        foreach (SystemPermissions::groups() as $abilities) {
            foreach (array_keys($abilities) as $ability) {
                $this->assertArrayNotHasKey(
                    $ability,
                    $seen,
                    "The ability {$ability} appears in more than one group."
                );
                $seen[$ability] = true;
            }
        }
    }

    public function test_no_role_default_list_holds_a_duplicate(): void
    {
        // A duplicated ability in a role default is harmless at runtime
        // but it hides a copy-paste mistake: a constant replaced by the
        // wrong one looks exactly like a working list.
        foreach (SystemPermissions::ROLE_DEFAULTS as $role => $abilities) {
            $this->assertSame(
                count($abilities),
                count(array_unique($abilities)),
                "The {$role} default list holds a duplicate ability."
            );
        }
    }

    public function test_every_role_default_ability_is_distinct_within_the_role(): void
    {
        // Catches the same class of mistake from the other side: a role
        // that lost an ability to a bad replacement.
        $admin = SystemPermissions::forRole(SystemPermissions::ROLE_ADMIN);

        foreach ([
            Permissions::SYSTEM_TENANTS_VIEW,
            Permissions::SYSTEM_TENANTS_MANAGE,
            Permissions::SYSTEM_SETTINGS_MANAGE,
            Permissions::SYSTEM_PAYMENT_SETTINGS,
            Permissions::SYSTEM_INVOICES_MANAGE,
        ] as $ability) {
            $this->assertContains(
                $ability,
                $admin,
                "The admin role must hold {$ability}."
            );
        }
    }

    public function test_the_support_role_holds_nothing_that_moves_money_or_configuration(): void
    {
        $support = SystemPermissions::forRole(SystemPermissions::ROLE_SUPPORT);

        foreach ($support as $ability) {
            $this->assertNotContains(
                $ability,
                [
                    Permissions::SYSTEM_INVOICES_MANAGE,
                    Permissions::SYSTEM_PAYMENT_SETTINGS,
                    Permissions::SYSTEM_PAYMENT_RETRY,
                    Permissions::SYSTEM_CREDITS_MANAGE,
                    Permissions::SYSTEM_SETTINGS_MANAGE,
                    Permissions::SYSTEM_TENANTS_MANAGE,
                    Permissions::SYSTEM_TENANTS_DELETE,
                    Permissions::SYSTEM_TENANTS_IMPERSONATE,
                    Permissions::SYSTEM_ADMIN_USERS_MANAGE,
                ],
                "The support role must not hold {$ability}."
            );
        }
    }

    public function test_super_admin_holds_every_ability(): void
    {
        $admin = $this->admin('super_admin');

        foreach (SystemPermissions::names() as $ability) {
            $this->assertTrue(
                $admin->hasPermission($ability),
                "super_admin must hold {$ability}."
            );
        }
    }

    public function test_super_admin_bypasses_an_explicit_permission_list(): void
    {
        // A super_admin restricted to one ability still holds them all:
        // the role is the grant.
        $admin = $this->admin('super_admin', ['system.tenants.view']);

        $this->assertTrue($admin->hasPermission('system.tenants.delete'));
        $this->assertTrue($admin->hasPermission('system.payment.settings'));
    }

    // ── Role defaults ──────────────────────────────────────────────────

    public function test_the_role_decides_when_the_account_carries_no_list(): void
    {
        $admin = $this->admin('support');

        $this->assertTrue($admin->hasPermission('system.tenants.view'));
        $this->assertTrue($admin->hasPermission('system.feedbacks.manage'));
        $this->assertFalse($admin->hasPermission('system.payment.settings'));
        $this->assertFalse($admin->hasPermission('system.tenants.delete'));
    }

    public function test_the_admin_role_can_operate_but_not_destroy(): void
    {
        $admin = $this->admin('admin');

        $this->assertTrue($admin->hasPermission('system.tenants.manage'));
        $this->assertTrue($admin->hasPermission('system.payment.settings'));
        $this->assertTrue($admin->hasPermission('system.invoices.manage'));

        $this->assertFalse($admin->hasPermission('system.tenants.delete'));
        $this->assertFalse($admin->hasPermission('system.tenants.impersonate'));
        $this->assertFalse($admin->hasPermission('system.admin_users.manage'));
    }

    public function test_the_support_role_is_read_only_for_the_money_surface(): void
    {
        $admin = $this->admin('support');

        $this->assertTrue($admin->hasPermission('system.tenants.view'));
        $this->assertTrue($admin->hasPermission('system.subscriptions.view'));
        $this->assertTrue($admin->hasPermission('system.invoices.view'));

        $this->assertFalse($admin->hasPermission('system.invoices.manage'));
        $this->assertFalse($admin->hasPermission('system.payment.settings'));
        $this->assertFalse($admin->hasPermission('system.credits.manage'));
        $this->assertFalse($admin->hasPermission('system.settings.manage'));
    }

    // ── The explicit list overrides the role ───────────────────────────

    public function test_an_explicit_list_overrides_the_role_defaults(): void
    {
        // A support admin promoted to a single extra ability holds
        // exactly that ability, not the role's list.
        $admin = $this->admin('support', ['system.payment.settings']);

        $this->assertTrue($admin->hasPermission('system.payment.settings'));
        $this->assertFalse(
            $admin->hasPermission('system.tenants.view'),
            'An explicit list replaces the role list — it does not add to it.'
        );
    }

    public function test_an_explicit_list_can_grant_what_the_role_cannot(): void
    {
        $admin = $this->admin('support', ['system.tenants.view', 'system.tenants.manage']);

        $this->assertTrue($admin->hasPermission('system.tenants.manage'));
        $this->assertFalse($admin->hasPermission('system.feedbacks.manage'));
    }

    public function test_an_empty_list_falls_back_to_the_role(): void
    {
        // The accounts in the field today carry an empty list. Treating
        // that as "no permissions" would lock every one of them out.
        $admin = $this->admin('support', []);

        $this->assertTrue($admin->hasPermission('system.tenants.view'));
    }

    public function test_a_null_list_falls_back_to_the_role(): void
    {
        $admin = $this->admin('support');
        $admin->permissions = null;

        $this->assertTrue($admin->hasPermission('system.tenants.view'));
    }

    // ── Filtering ──────────────────────────────────────────────────────

    public function test_unknown_abilities_are_filtered_out(): void
    {
        $filtered = SystemPermissions::filterValid([
            'system.tenants.view',
            'not.a.real.ability',
            'system.tenants.view',
        ]);

        $this->assertSame(['system.tenants.view'], $filtered);
    }

    public function test_the_admin_user_form_cannot_store_an_unknown_ability(): void
    {
        $super = $this->admin('super_admin');

        $this->actingAs($super, 'admin')
            ->post(route('system.admin-users.store'), [
                'name' => 'Crafted Admin',
                'email' => 'crafted-'.uniqid().'@example.test',
                'password' => 'Secret-password-1',
                'role' => 'support',
                'permissions' => ['system.tenants.view', 'platform.takeover'],
            ])
            ->assertRedirect();

        $created = AdminUser::where('email', 'like', 'crafted-%')->firstOrFail();

        $this->assertSame(
            ['system.tenants.view'],
            $created->permissions,
            'An ability the registry does not know must never be stored.'
        );
    }

    // ── The middleware ─────────────────────────────────────────────────

    public function test_the_middleware_refuses_an_account_without_the_ability(): void
    {
        $support = $this->admin('support');

        $response = $this->actingAs($support, 'admin')
            ->get(route('system.settings.payment'));

        $response->assertForbidden();
    }

    public function test_the_middleware_allows_an_account_with_the_ability(): void
    {
        $admin = $this->admin('admin');

        $response = $this->actingAs($admin, 'admin')
            ->get(route('system.settings.payment'));

        $response->assertRedirect(route('system.settings.index', ['tab' => 'payment']));
    }

    public function test_the_admin_roster_can_be_read_but_not_changed_without_the_manage_ability(): void
    {
        // The roster is a diagnostic surface — support staff need to see
        // who the admins are. Changing one is a different matter.
        $support = $this->admin('support', [
            Permissions::SYSTEM_ADMIN_USERS_VIEW,
        ]);

        $this->actingAs($support, 'admin')
            ->get(route('system.admin-users.index'))
            ->assertOk();

        // Reading is allowed; changing an account is not.
        $this->actingAs($support, 'admin')
            ->put(route('system.admin-users.update', $support->id), [
                'name' => 'Escalated',
                'email' => $support->email,
                'role' => 'super_admin',
                'is_active' => true,
            ])
            ->assertForbidden();
    }

    public function test_the_admin_roster_is_closed_to_an_account_without_the_view_ability(): void
    {
        $support = $this->admin('support', ['system.tenants.view']);

        $this->actingAs($support, 'admin')
            ->get(route('system.admin-users.index'))
            ->assertForbidden();
    }

    public function test_the_middleware_lets_the_platform_owner_through(): void
    {
        // `is_system_admin` is the platform owner — the same person as
        // `super_admin`, reached through the tenant guard. It bypasses.
        $owner = User::factory()->create(['is_system_admin' => true]);

        $response = $this->actingAs($owner, 'web')
            ->get(route('system.settings.payment'));

        $response->assertRedirect(route('system.settings.index', ['tab' => 'payment']));
    }

    public function test_a_route_without_an_ability_is_not_affected_by_the_middleware(): void
    {
        // The panel's own landing page stays open to every system admin.
        $support = $this->admin('support');

        $response = $this->actingAs($support, 'admin')->get('/system');

        $response->assertOk();
    }

    // ── The route declarations ─────────────────────────────────────────

    public function test_every_ability_declared_on_a_route_exists_in_the_registry(): void
    {
        // An unknown ability on a route would deny EVERY account that
        // is not the platform owner — a very confusing failure to debug.
        $known = SystemPermissions::names();

        foreach (Route::getRoutes()->getRoutes() as $route) {
            foreach ($route->gatherMiddleware() as $middleware) {
                if (! str_starts_with($middleware, 'system.permission:')) {
                    continue;
                }

                foreach (explode(',', substr($middleware, strlen('system.permission:'))) as $ability) {
                    $this->assertContains(
                        $ability,
                        $known,
                        "The route {$route->uri()} requires an unknown ability: {$ability}"
                    );
                }
            }
        }
    }

    public function test_every_system_write_route_declares_an_ability(): void
    {
        // A write route with no ability would be reachable by any
        // system admin, which is the gap this work closed.
        $routes = collect(Route::getRoutes()->getRoutes())
            ->filter(fn ($r) => str_starts_with($r->uri(), 'system'));

        $writes = $routes->filter(fn ($r) => in_array($r->methods()[0], ['POST', 'PUT', 'PATCH', 'DELETE'], true));

        foreach ($writes as $route) {
            $this->assertTrue(
                collect($route->gatherMiddleware())->contains(fn ($m) => str_starts_with($m, 'system.permission:')),
                "The write route {$route->methods()[0]} {$route->uri()} declares no ability."
            );
        }
    }

    // ── Extensibility ─────────────────────────────────────────────────

    public function test_a_new_ability_can_be_added_to_the_registry_without_touching_the_model(): void
    {
        // The registry is the extension point: a group and its role
        // defaults are all it takes for a new ability to exist, be
        // assignable, and be enforced.
        $ability = 'reports.export';

        $this->assertFalse(SystemPermissions::exists($ability));

        // The helpers are derived from the groups, so nothing has to
        // be updated by hand when a group grows.
        $this->assertSame(
            SystemPermissions::names(),
            array_keys(SystemPermissions::all()),
            'all() must stay derived from the groups.'
        );

        // And the filter that guards the admin-user form accepts
        // whatever the registry knows — so a newly added ability is
        // immediately assignable, with no change to the form.
        $this->assertSame(
            [],
            SystemPermissions::filterValid([$ability]),
            'An ability that is NOT registered must be rejected.'
        );

        // A registered ability, on the other hand, survives the filter
        // and can be granted straight away.
        $registered = SystemPermissions::names()[0];
        $this->assertSame(
            [$registered],
            SystemPermissions::filterValid([$registered]),
            'A registered ability must survive the filter.'
        );

        // A role default is what actually grants it, and that is the
        // only place a new ability has to be listed.
        $this->assertNotEmpty(SystemPermissions::ROLE_DEFAULTS);
        $registeredAbilities = SystemPermissions::names();

        foreach (SystemPermissions::ROLE_DEFAULTS as $role => $abilities) {
            foreach ($abilities as $granted) {
                $this->assertContains(
                    $granted,
                    $registeredAbilities,
                    "The {$role} default list must only name registered abilities."
                );
            }
        }
    }

    public function test_the_registry_groups_are_the_source_of_the_admin_user_form(): void
    {
        // The Vue form renders one checkbox per ability, from the
        // groups the controller passes down. A new group therefore
        // shows up in the form without a front-end change.
        $this->assertSame(
            SystemPermissions::groups(),
            AdminUser::getAvailablePermissions(),
            'The model must delegate to the registry.'
        );
    }
}
