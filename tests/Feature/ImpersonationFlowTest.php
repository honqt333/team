<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Http\Controllers\System\ImpersonationController;
use App\Models\AdminActivityLog;
use App\Models\AdminUser;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Tests for the system-admin impersonation flow.
 *
 * THE BUGS THIS COVERS
 *
 * 1. The original identity was never stored. `start()` read
 *    `Auth::id()`, which is the DEFAULT ('web') guard. A system admin
 *    logs in through `Auth::guard('admin')->login()` (see
 *    Auth\LoginRequest::authenticate()), so the web guard was empty and
 *    `Auth::id()` returned null. `Session::put('impersonating_from',
 *    null)` stored null.
 *
 * 2. Consequences of (1):
 *    - `Session::has('impersonating_from')` was false, so the
 *      impersonation banner never rendered
 *      (HandleInertiaRequests) — the admin silently became the tenant
 *      user with no indication.
 *    - `stop()` hit its `if (! $originalUserId)` branch, which forgot
 *      the session and redirected to /dashboard WITHOUT logging the
 *      tenant user out and WITHOUT restoring the admin. The operator
 *      stayed stuck inside the tenant account.
 *
 * 3. `EnsureSystemAdmin`'s own auto-stop path looked the stored id up
 *    with `User::find()` and restored it with `Auth::login()` — but
 *    the stored id belongs to the `admin_users` table, so that
 *    recovery path could never fire.
 */
class ImpersonationFlowTest extends TestCase
{
    use RefreshDatabase;

    private function makeAdmin(): AdminUser
    {
        return AdminUser::create([
            'name' => 'System Admin',
            'email' => 'admin@example.test',
            'password' => Hash::make('secret-password'),
            'role' => 'super_admin',
            'is_active' => true,
        ]);
    }

    private function makeTenantUser(Tenant $tenant): User
    {
        return User::factory()->create([
            'tenant_id' => $tenant->id,
            'email' => 'tenant-user@example.test',
            'two_factor_confirmed_at' => now(),
        ]);
    }

    public function test_start_stores_the_system_admin_identity_and_logs_in_the_tenant_user(): void
    {
        $admin = $this->makeAdmin();
        $tenant = Tenant::factory()->create();
        $tenantUser = $this->makeTenantUser($tenant);

        Auth::guard('admin')->login($admin);

        $response = $this->post(route('system.impersonate.start', $tenant));

        $response->assertRedirect('/dashboard');

        // The stored id must be the ADMIN's id — that is what stop()
        // needs to restore the original identity.
        $this->assertSame(
            $admin->id,
            session('impersonating_from'),
            'The original system admin id must be stored in the session.'
        );
        $this->assertSame($tenant->id, session('impersonating_tenant'));

        // The impersonation banner depends on this key being present.
        $this->assertTrue(
            ImpersonationController::isImpersonating(),
            'isImpersonating() must report true so the UI shows the banner.'
        );

        // The tenant user is now signed in on the web guard.
        $this->assertTrue(Auth::guard('web')->check());
        $this->assertSame($tenantUser->id, Auth::guard('web')->id());
    }

    public function test_stop_restores_the_system_admin_and_logs_the_tenant_user_out(): void
    {
        $admin = $this->makeAdmin();
        $tenant = Tenant::factory()->create();
        $tenantUser = $this->makeTenantUser($tenant);

        Auth::guard('admin')->login($admin);

        $this->post(route('system.impersonate.start', $tenant));

        $this->assertSame($tenantUser->id, Auth::guard('web')->id());

        // Now stop impersonating.
        $response = $this->post(route('impersonate.stop'));

        $response->assertRedirect('/system');

        // The tenant user must be signed OUT of the web guard.
        $this->assertFalse(
            Auth::guard('web')->check(),
            'Stopping impersonation must log the tenant user out of the web guard.'
        );

        // The admin must be signed back IN on the admin guard.
        $this->assertTrue(
            Auth::guard('admin')->check(),
            'Stopping impersonation must restore the system admin session.'
        );
        $this->assertSame($admin->id, Auth::guard('admin')->id());

        // The impersonation state must be cleared.
        $this->assertFalse(ImpersonationController::isImpersonating());
        $this->assertNull(session('impersonating_from'));
        $this->assertNull(session('impersonating_tenant'));
    }

    public function test_start_and_stop_are_recorded_in_the_admin_activity_log(): void
    {
        $admin = $this->makeAdmin();
        $tenant = Tenant::factory()->create();
        $this->makeTenantUser($tenant);

        Auth::guard('admin')->login($admin);

        $this->post(route('system.impersonate.start', $tenant));
        $this->post(route('impersonate.stop'));

        $this->assertSame(1, AdminActivityLog::query()->where('action', 'impersonate_start')->count());
        $this->assertSame(1, AdminActivityLog::query()->where('action', 'impersonate_stop')->count());

        $stopLog = AdminActivityLog::query()->where('action', 'impersonate_stop')->first();
        $this->assertSame($admin->id, $stopLog->admin_user_id);
        $this->assertSame(
            $admin->id,
            $stopLog->changes['original_admin_user_id'] ?? null,
            'The stop log must record which admin identity was restored.'
        );
    }

    public function test_start_is_refused_when_there_is_no_system_admin_identity(): void
    {
        $tenant = Tenant::factory()->create();
        $this->makeTenantUser($tenant);

        // No admin guard session. The route's own middleware
        // (EnsureSystemAdmin) rejects the request before the controller
        // runs — it redirects an unauthenticated visitor to the login
        // page. Either way the operator must NOT be dropped into the
        // tenant account.
        $response = $this->post(route('system.impersonate.start', $tenant));

        $this->assertTrue(
            in_array($response->status(), [302, 403], true),
            'Starting impersonation without a system admin identity must be refused. '
            .'Got status '.$response->status()
        );

        $this->assertFalse(
            Auth::guard('web')->check(),
            'No tenant session may be created without a system admin to return to.'
        );
        $this->assertFalse(ImpersonationController::isImpersonating());
    }

    public function test_stop_without_a_stored_identity_still_logs_the_tenant_user_out(): void
    {
        $tenant = Tenant::factory()->create();
        $tenantUser = $this->makeTenantUser($tenant);

        // Simulate a session that lost the impersonation marker but is
        // still signed in as the tenant user.
        Auth::guard('web')->login($tenantUser);

        $response = $this->post(route('impersonate.stop'));

        $response->assertRedirect();

        $this->assertFalse(
            Auth::guard('web')->check(),
            'The tenant session must be dropped even when there is no admin to restore.'
        );
    }
}
