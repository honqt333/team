<?php

namespace Tests\Feature\Auth;

use App\Services\TenantSetupService;
use App\Support\Permissions;
use Tests\TestCase;

/**
 * Pin the contract for the role → permissions matrix defined in
 * `App\Services\TenantSetupService::getDefaultRoles()`.
 *
 * These tests catch the kind of issue that slipped through code
 * review: past edits left duplicate permission entries in
 * branch_manager / accountant / hr. They would have been
 * persisted to the role_has_permissions pivot twice and they
 * silently inflated the count of "permissions this role has"
 * without actually granting any new capability.
 *
 * The tests assert:
 *   1. Every role's permission list is unique.
 *   2. The total count of permissions per role matches the
 *      audited baseline (so future adds land in code review).
 *   3. super_admin has every permission defined in Permissions.
 *   4. Every non-super role only has permissions that exist in
 *      the Permissions registry.
 *   5. `Permissions::byModule()` is consistent with the actual
 *      permission constants.
 */
class RolePermissionsContractTest extends TestCase
{
    /**
     * The audited baseline. Update intentionally when a role
     * is changed and the change is reviewed.
     */
    private const EXPECTED_PERMISSION_COUNTS = [
        'super_admin'    => 120,
        'branch_manager' => 30,
        'receptionist'   => 13,
        'technician'     => 4,
        'accountant'     => 13,
        'hr'             => 20,
        'employee'       => 6,
    ];

    public function test_every_role_has_unique_permissions(): void
    {
        $svc = new TenantSetupService();
        $roles = $svc->getDefaultRoles();

        foreach ($roles as $name => $data) {
            $perms = $data['permissions'];
            $this->assertSame(
                count($perms),
                count(array_unique($perms)),
                "Role '{$name}' has duplicate permission entries. Run the dedupe helper in TenantSetupService::getDefaultRoles()."
            );
        }
    }

    public function test_each_role_has_the_audited_baseline_count(): void
    {
        $svc = new TenantSetupService();
        $roles = $svc->getDefaultRoles();

        foreach (self::EXPECTED_PERMISSION_COUNTS as $name => $expected) {
            $this->assertArrayHasKey($name, $roles, "Role '{$name}' is missing from TenantSetupService::getDefaultRoles()");
            $this->assertCount(
                $expected,
                $roles[$name]['permissions'],
                "Role '{$name}' has a different number of permissions than the audited baseline ({$expected}). Update the constant in this test if the change is intentional."
            );
        }
    }

    public function test_super_admin_has_every_registered_permission(): void
    {
        $svc = new TenantSetupService();
        $roles = $svc->getDefaultRoles();

        $expected = Permissions::all();
        sort($expected);
        $actual = $roles['super_admin']['permissions'];
        sort($actual);

        $this->assertSame(
            $expected,
            $actual,
            'super_admin role must have every permission registered in App\Support\Permissions::all()'
        );
    }

    public function test_no_role_grants_unknown_permissions(): void
    {
        $svc = new TenantSetupService();
        $roles = $svc->getDefaultRoles();
        $registered = Permissions::all();

        foreach ($roles as $name => $data) {
            foreach ($data['permissions'] as $perm) {
                $this->assertContains(
                    $perm,
                    $registered,
                    "Role '{$name}' grants permission '{$perm}' which is not in App\Support\Permissions"
                );
            }
        }
    }

    public function test_no_orphan_permissions(): void
    {
        // Every permission defined as a constant on Permissions
        // must also appear in at least one role, otherwise it is
        // dead code that future devs will assume is "live" but
        // never has a chance to be granted.
        $svc = new TenantSetupService();
        $roles = $svc->getDefaultRoles();

        $allGranted = [];
        foreach ($roles as $data) {
            foreach ($data['permissions'] as $perm) {
                $allGranted[] = $perm;
            }
        }
        $allGranted = array_values(array_unique($allGranted));
        sort($allGranted);

        $registered = Permissions::all();
        sort($registered);

        $orphans = array_diff($registered, $allGranted);
        $this->assertEmpty(
            $orphans,
            'These permissions are registered but never granted to any role: ' . implode(', ', $orphans)
        );
    }

    public function test_by_module_is_consistent_with_registered_permissions(): void
    {
        // byModule() is a doc-style helper. Every permission
        // listed in it must exist; every permission not listed
        // would surprise readers.
        $registered = Permissions::all();
        $byModule = Permissions::byModule();
        $flat = array_merge(...array_values($byModule));
        $flat = array_unique($flat);
        sort($flat);

        $missing = array_diff($registered, $flat);
        $extra = array_diff($flat, $registered);

        $this->assertEmpty(
            $missing,
            'These permissions are registered but missing from Permissions::byModule(): ' . implode(', ', $missing)
        );
        $this->assertEmpty(
            $extra,
            'Permissions::byModule() lists permissions that are not in Permissions: ' . implode(', ', $extra)
        );
    }

    public function test_employee_role_grants_only_self_service_permissions(): void
    {
        // Hard constraint: the `employee` role must NEVER grant
        // any administrative or shared permission — it exists
        // only to power the /app/my self-service portal.
        $svc = new TenantSetupService();
        $roles = $svc->getDefaultRoles();

        $allowedPrefixes = ['employee.'];

        foreach ($roles['employee']['permissions'] as $perm) {
            $isAllowed = false;
            foreach ($allowedPrefixes as $prefix) {
                if (str_starts_with($perm, $prefix)) {
                    $isAllowed = true;
                    break;
                }
            }
            $this->assertTrue(
                $isAllowed,
                "Employee role grants a non-self-service permission: '{$perm}'. " .
                "Employee accounts should only have employee.* permissions."
            );
        }
    }

    public function test_branch_manager_cannot_delete_records(): void
    {
        // Audit rule: branch_manager is operational, not admin.
        // It must never have *DELETE / DESTROY permissions.
        $svc = new TenantSetupService();
        $roles = $svc->getDefaultRoles();

        foreach ($roles['branch_manager']['permissions'] as $perm) {
            $this->assertStringEndsNotWith(
                '.delete',
                $perm,
                "branch_manager role must not have delete permissions (found: '{$perm}')"
            );
            $this->assertStringEndsNotWith(
                '.destroy',
                $perm,
                "branch_manager role must not have destroy permissions (found: '{$perm}')"
            );
        }
    }
}
