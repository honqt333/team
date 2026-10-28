<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Http\Middleware\HandleInertiaRequests;
use App\Models\AdminUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Tests for the Inertia 403 page wiring.
 *
 * The page `resources/js/Pages/Errors/403.vue` existed but nothing
 * rendered it: no route, no exception handler. It was consequently
 * classified as an unreachable Vue file and removed in 374e5f23 —
 * while a contract test kept failing because it still pinned the
 * page's behaviour.
 *
 * The page is now rendered by the 403 branch in bootstrap/app.php for
 * plain document navigations. These tests pin that wiring:
 *
 *   - a document request that 403s gets the Inertia page;
 *   - an Inertia XHR keeps Laravel's JSON error (the SPA has its own
 *     error handling and expects JSON);
 *   - the page still carries the role-aware home link.
 */
class Error403PageRenderingTest extends TestCase
{
    use RefreshDatabase;

    private function supportAdmin(): AdminUser
    {
        return AdminUser::create([
            'name' => 'Support User',
            'email' => 'support403@example.com',
            'password' => Hash::make('password'),
            'role' => 'support',
            'is_active' => true,
        ]);
    }

    /**
     * A support admin hitting a super_admin-only route gets a 403.
     */
    public function test_a_document_request_that_forbids_renders_the_inertia_403_page(): void
    {
        $admin = $this->supportAdmin();

        $response = $this->actingAs($admin, 'admin')->get('/system/backups');

        $response->assertForbidden();

        $page = $response->viewData('page');

        $this->assertSame(
            'Errors/403',
            $page['component'],
            'A forbidden document request must render the Inertia 403 page.'
        );
    }

    /**
     * The page needs its Inertia props (the auth user) to pick the
     * right home link.
     */
    public function test_the_403_page_receives_the_shared_inertia_props(): void
    {
        $admin = $this->supportAdmin();

        $response = $this->actingAs($admin, 'admin')->get('/system/backups');

        $page = $response->viewData('page');

        $this->assertArrayHasKey('auth', $page['props']);
        $this->assertArrayHasKey('locale', $page['props']);
    }

    /**
     * An Inertia XHR expects a JSON error payload, not an HTML page.
     */
    public function test_an_inertia_xhr_keeps_the_json_error(): void
    {
        $admin = $this->supportAdmin();

        // The X-Inertia-Version header has to match the app's asset
        // version — which is the hash of the Vite manifest, computed
        // by the HandleInertiaRequests middleware — otherwise Inertia
        // answers 409 (asset version mismatch) instead of the 403.
        $response = $this->actingAs($admin, 'admin')
            ->get('/system/backups', [
                'X-Inertia' => 'true',
                'X-Inertia-Version' => $this->assetVersion(),
            ]);

        $response->assertForbidden();

        // The SPA must NOT receive the Inertia page for an XHR: it
        // expects a non-2xx it can handle itself, and the page is a
        // full document render. The app's own error handling for XHR
        // requests is left exactly as it was.
        $this->assertStringNotContainsString(
            'Errors/403',
            (string) $response->getContent(),
            'An Inertia XHR must not receive the Inertia 403 page.'
        );
    }

    /**
     * A JSON API call must not be answered with an HTML page either.
     */
    public function test_a_json_request_keeps_the_json_error(): void
    {
        $admin = $this->supportAdmin();

        $response = $this->actingAs($admin, 'admin')
            ->getJson('/system/backups');

        $response->assertForbidden();

        $this->assertStringNotContainsString(
            'Errors/403',
            (string) $response->getContent(),
            'A JSON request must not receive the Inertia 403 page.'
        );
    }

    /**
     * The asset version Inertia expects in the request header: the
     * hash of the built manifest.
     */
    private function assetVersion(): string
    {
        return (string) (new HandleInertiaRequests)->version(request());
    }

    /**
     * A successful request must be untouched by the 403 branch.
     */
    public function test_a_successful_request_is_not_affected(): void
    {
        $admin = AdminUser::create([
            'name' => 'Owner',
            'email' => 'owner403@example.com',
            'password' => Hash::make('password'),
            'role' => 'super_admin',
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin, 'admin')->get('/system/backups');

        $response->assertOk();
    }
}
