<?php

declare(strict_types=1);

namespace Tests\Feature\Frontend;

use Tests\TestCase;

/**
 * Pin the smart-home logic in `resources/js/Pages/Errors/403.vue`.
 *
 * The page picks the "Home" link target based on who is looking
 * at the 403:
 *   - System admin → /system
 *   - Tenant user  → the tenant dashboard (route name `dashboard`)
 *   - Guest        → /
 *
 * We assert the Vue source because the page is rendered by
 * Inertia on the client; running the actual Vue requires
 * JSDOM, which we deliberately avoid.
 *
 * HISTORY
 *
 * The page was removed in 374e5f23 as an "unreachable Vue file" —
 * the reachability graph was built from the app entry point and the
 * Inertia pages referenced in PHP, and it never looked at the test
 * files. This contract test kept failing afterwards, which is how the
 * removal surfaced. The page is now rendered by the 403 branch in
 * bootstrap/app.php, and the test pins the route name it uses.
 *
 * The route name was ALSO wrong in the original page: it used
 * `route('app.dashboard')`, and no route by that name exists — the
 * tenant dashboard is named `dashboard`. Following the old
 * expectation literally would have shipped a page that throws
 * "Route [app.dashboard] not defined" at the exact moment it is
 * needed.
 */
class Error403HomeLinkTest extends TestCase
{
    public function test_403_vue_picks_home_target_based_on_user_role(): void
    {
        $source = file_get_contents(base_path('resources/js/Pages/Errors/403.vue'));

        // The page must branch on is_system_admin, auth.user, and
        // a guest fallback. Hard-coding the link to a single
        // target caused the redirect loop we are fixing.
        $this->assertStringContainsString(
            'is_system_admin',
            $source,
            '403.vue must check is_system_admin to route system admins to /system'
        );

        $this->assertStringContainsString(
            "route('dashboard')",
            $source,
            '403.vue must keep the tenant dashboard link for tenant users'
        );

        $this->assertStringContainsString(
            "return '/'",
            $source,
            '403.vue must fall back to / for guests'
        );

        // The legacy hard-coded link is what caused the bug.
        $this->assertStringNotContainsString(
            ':href="route(\'app.dashboard\')"',
            $source,
            '403.vue must NOT hard-code the home link to app.dashboard'
        );
    }

    /**
     * The route name the page uses must actually exist — otherwise
     * the page throws at the moment it is needed.
     */
    public function test_the_route_name_used_by_the_403_page_exists(): void
    {
        $source = file_get_contents(base_path('resources/js/Pages/Errors/403.vue'));

        // Strip the comments first: the file documents the OLD broken
        // route name in a comment, and that name is not supposed to
        // exist.
        $code = preg_replace('#/\*.*?\*/#s', '', $source);
        $code = preg_replace('#//[^\n]*#', '', $code);

        preg_match_all("/route\('([a-z0-9._-]+)'\)/i", $code, $matches);

        $this->assertNotEmpty($matches[1], 'The 403 page must resolve its home link through a route name.');

        foreach (array_unique($matches[1]) as $routeName) {
            $this->assertNotNull(
                app('router')->getRoutes()->getByName($routeName),
                "The 403 page uses route('{$routeName}'), which does not exist in the route table."
            );
        }
    }

    public function test_403_vue_go_back_falls_back_to_home(): void
    {
        $source = file_get_contents(base_path('resources/js/Pages/Errors/403.vue'));

        // The goBack() function should be defensive: if there is no
        // history to go back to, it should still send the user
        // somewhere safe rather than leaving them stuck on the
        // 403 page.
        $this->assertStringContainsString(
            'document.referrer',
            $source,
            'goBack() should check document.referrer before window.history.back()'
        );
    }
}
