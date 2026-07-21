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
 *   - Tenant user  → /app/dashboard (route name)
 *   - Guest        → /
 *
 * We assert the Vue source because the page is rendered by
 * Inertia on the client; running the actual Vue requires
 * JSDOM, which we deliberately avoid.
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
            "route('app.dashboard')",
            $source,
            '403.vue must keep the app.dashboard link for tenant users'
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
