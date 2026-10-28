<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Center;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests for the two-factor enforcement flow (audit finding #11).
 *
 * THE BUG
 *
 * EnsureTwoFactorEnabled redirects a user who has not confirmed 2FA
 * yet to the route name `app.security.2fa.setup`:
 *
 *     return redirect()->route('app.security.2fa.setup');
 *
 * That route did not exist. The controller action
 * (App\TwoFactorController::setup) and the Vue page
 * (App/Security/TwoFactorSetup) were both there — only the GET route
 * was missing. So a tenant that had switched 2FA enforcement to
 * "required" could not serve a single protected page: every request
 * raised "Route [app.security.2fa.setup] not defined" and answered
 * HTTP 500.
 *
 * The audit also found the enforcement was bypassed on the pages it
 * mattered most: /dashboard (which renders the tenant's operating and
 * financial figures) and the /app profile + employee portal group
 * carried no guard at all.
 *
 * THE FIX
 *
 *   - The GET route `app.security.2fa.setup` is registered.
 *   - The guard now also covers /dashboard and the profile/employee
 *     portal group.
 *   - The middleware exempts both `app.security.2fa.*` and
 *     `app.2fa.*`, because the send-code route lives in the second
 *     family and a redirected user needs it to finish the setup.
 */
class TwoFactorEnforcementRouteTest extends TestCase
{
    use RefreshDatabase;

    private function tenantRequiringTwoFactor(): Tenant
    {
        return Tenant::factory()->create([
            'two_factor_enabled' => true,
            'two_factor_enforcement' => 'required',
        ]);
    }

    private function userFor(Tenant $tenant, bool $confirmed): User
    {
        $center = Center::factory()->create(['tenant_id' => $tenant->id]);

        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'current_center_id' => $center->id,
            'two_factor_confirmed_at' => $confirmed ? now() : null,
        ]);

        $user->centers()->attach($center->id, ['tenant_id' => $tenant->id]);

        return $user;
    }

    /**
     * The route the middleware redirects to has to exist — this is the
     * direct cause of the 500.
     */
    public function test_the_two_factor_setup_route_exists(): void
    {
        $route = app('router')->getRoutes()->getByName('app.security.2fa.setup');

        $this->assertNotNull(
            $route,
            'The app.security.2fa.setup route must exist — EnsureTwoFactorEnabled redirects to it.'
        );
        $this->assertContains('GET', $route->methods());
    }

    /**
     * A user who has not confirmed 2FA is redirected to the setup
     * screen — and the screen renders instead of erroring.
     */
    public function test_an_unconfirmed_user_is_redirected_to_a_working_setup_page(): void
    {
        $tenant = $this->tenantRequiringTwoFactor();
        $user = $this->userFor($tenant, confirmed: false);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertRedirect(route('app.security.2fa.setup'));
        $response->assertSessionHas('warning');

        // The redirect target itself must work.
        $setup = $this->actingAs($user)->get(route('app.security.2fa.setup'));
        $setup->assertOk();
    }

    /**
     * The dashboard used to be reachable without confirming 2FA, even
     * when the tenant required it. It renders the tenant's financial
     * figures, so it must be guarded.
     */
    public function test_the_dashboard_requires_confirmed_two_factor(): void
    {
        $tenant = $this->tenantRequiringTwoFactor();
        $user = $this->userFor($tenant, confirmed: false);

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertRedirect(route('app.security.2fa.setup'));
    }

    /**
     * The profile and the employee portal used to sit in a group with
     * no guard at all.
     */
    public function test_the_profile_requires_confirmed_two_factor(): void
    {
        $tenant = $this->tenantRequiringTwoFactor();
        $user = $this->userFor($tenant, confirmed: false);

        $this->actingAs($user)
            ->get('/app/profile')
            ->assertRedirect(route('app.security.2fa.setup'));
    }

    /**
     * A user who HAS confirmed 2FA passes straight through.
     */
    public function test_a_confirmed_user_reaches_the_dashboard(): void
    {
        $tenant = $this->tenantRequiringTwoFactor();
        $user = $this->userFor($tenant, confirmed: true);

        $this->actingAs($user)->get('/dashboard')->assertOk();
    }

    /**
     * Tenants that do not require 2FA are completely unaffected.
     */
    public function test_a_tenant_without_the_requirement_is_unaffected(): void
    {
        $tenant = Tenant::factory()->create([
            'two_factor_enabled' => false,
            'two_factor_enforcement' => 'disabled',
        ]);
        $user = $this->userFor($tenant, confirmed: false);

        $this->actingAs($user)->get('/dashboard')->assertOk();
        $this->actingAs($user)->get('/app/profile')->assertOk();
    }

    /**
     * The send-code route lives in the `app.2fa.*` name family, not
     * `app.security.2fa.*`. A user who was redirected to the setup
     * screen must still be able to request the code that lets them
     * confirm 2FA, otherwise the flow dead-ends.
     */
    public function test_the_send_code_route_is_reachable_for_an_unconfirmed_user(): void
    {
        $tenant = $this->tenantRequiringTwoFactor();
        $user = $this->userFor($tenant, confirmed: false);

        $response = $this->actingAs($user)->postJson(route('app.2fa.send-code'), [
            'method' => 'email',
        ]);

        // The route must not redirect back to the setup page. The
        // service itself may fail (no SMTP configured in the test
        // environment), but that is a different failure from being
        // blocked by the guard.
        $this->assertNotSame(
            route('app.security.2fa.setup'),
            $response->headers->get('Location'),
            'The send-code route must not be blocked by the 2FA guard.'
        );
    }
}
