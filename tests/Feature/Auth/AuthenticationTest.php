<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Models\AdminUser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
    }

    public function test_users_can_authenticate_using_the_login_screen(): void
    {
        $user = User::factory()->create();

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_users_can_not_authenticate_with_invalid_password(): void
    {
        $user = User::factory()->create();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
    }

    public function test_users_can_logout(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/logout');

        $this->assertGuest();
        $response->assertRedirect('/');
    }

    /**
     * The admin panel has its own /admin/login route, and nothing in
     * the app links to /admin/login?as=admin.
     *
     * The page used to render the TENANT form on that route, because
     * the flag was derived from the query string alone. The form then
     * posted to the tenant login route, which only authenticates a
     * `users` row — so an `admin_users` account could never sign in
     * from the page built for it, and always saw "these credentials
     * do not match".
     */
    public function test_the_admin_login_page_renders_the_admin_form(): void
    {
        $response = $this->get('/admin/login');

        $response->assertOk();

        $page = $response->viewData('page');

        $this->assertTrue(
            $page['props']['asAdmin'] ?? false,
            'The /admin/login page must render the admin-panel login form.'
        );
    }

    /**
     * The tenant login page must NOT be turned into the admin form.
     */
    public function test_the_tenant_login_page_still_renders_the_tenant_form(): void
    {
        $response = $this->get('/login');

        $response->assertOk();

        $page = $response->viewData('page');

        $this->assertFalse(
            $page['props']['asAdmin'] ?? false,
            'The /login page must not render the admin-panel login form.'
        );
    }

    /**
     * ...and the query-string flag keeps working.
     */
    public function test_the_as_admin_query_flag_still_selects_the_admin_form(): void
    {
        $response = $this->get('/login?as=admin');

        $response->assertOk();

        $page = $response->viewData('page');

        $this->assertTrue($page['props']['asAdmin'] ?? false);
    }

    /**
     * An admin account can now actually sign in through the admin
     * login page.
     */
    public function test_an_admin_user_can_authenticate_through_the_admin_login_page(): void
    {
        $admin = AdminUser::create([
            'name' => 'System Admin',
            'email' => 'admin-login@example.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'is_active' => true,
        ]);

        $response = $this->post('/admin/login', [
            'email' => $admin->email,
            'password' => 'password',
        ]);

        $response->assertRedirect('/system');
        $this->assertAuthenticated('admin');
    }

    public function test_system_admin_logout_terminates_admin_session_and_blocks_system_access(): void
    {
        $admin = AdminUser::create([
            'name' => 'System Admin',
            'email' => 'admin-logout@example.com',
            'password' => Hash::make('password'),
            'role' => 'super_admin',
            'is_active' => true,
        ]);

        $this->post('/admin/login', [
            'email' => $admin->email,
            'password' => 'password',
        ])->assertRedirect('/system');

        $this->assertAuthenticated('admin');
        $oldSessionId = session()->getId();
        $oldCsrfToken = session()->token();

        $logoutResponse = $this->post(route('admin.logout'));

        $logoutResponse->assertRedirect(route('admin.login'));
        $this->assertGuest('admin');
        $this->assertNotSame($oldSessionId, session()->getId());
        $this->assertNotSame($oldCsrfToken, session()->token());

        $this->get('/system')->assertRedirect(route('login'));
    }

    public function test_system_admin_logout_preserves_isolated_web_guard_session(): void
    {
        $admin = AdminUser::create([
            'name' => 'System Admin',
            'email' => 'admin-isolated@example.com',
            'password' => Hash::make('password'),
            'role' => 'super_admin',
            'is_active' => true,
        ]);
        $user = User::factory()->create(['is_system_admin' => false]);

        $this->actingAs($user, 'web');
        $this->actingAs($admin, 'admin');

        $this->assertAuthenticated('web');
        $this->assertAuthenticated('admin');

        $oldSessionId = session()->getId();
        $oldCsrfToken = session()->token();

        $this->post(route('admin.logout'))
            ->assertRedirect(route('admin.login'));

        $this->assertGuest('admin');
        $this->assertAuthenticatedAs($user, 'web');
        $this->assertNotSame($oldSessionId, session()->getId());
        $this->assertNotSame($oldCsrfToken, session()->token());

        $this->get('/system')->assertForbidden();
    }

    public function test_unauthenticated_request_to_admin_logout_is_redirected(): void
    {
        $this->post('/admin/logout')->assertRedirect(route('login'));
        $this->assertGuest('admin');
    }

    public function test_regular_web_user_cannot_call_admin_logout(): void
    {
        $user = User::factory()->create(['is_system_admin' => false]);

        $this->actingAs($user, 'web')
            ->post(route('admin.logout'))
            ->assertForbidden();

        $this->assertAuthenticatedAs($user, 'web');
        $this->assertGuest('admin');
    }

    public function test_system_admin_logout_logs_out_both_admin_and_system_admin_web_guards(): void
    {
        $admin = AdminUser::create([
            'name' => 'System Admin',
            'email' => 'admin-dual@example.com',
            'password' => Hash::make('password'),
            'role' => 'super_admin',
            'is_active' => true,
        ]);
        $systemWebUser = User::factory()->create([
            'email' => 'admin-dual@example.com',
            'is_system_admin' => true,
        ]);

        $this->actingAs($systemWebUser, 'web');
        $this->actingAs($admin, 'admin');

        $oldSessionId = session()->getId();

        $this->post(route('admin.logout'))
            ->assertRedirect(route('admin.login'));

        $this->assertGuest('admin');
        $this->assertGuest('web');
        $this->assertNotSame($oldSessionId, session()->getId());

        $this->get('/system')->assertRedirect(route('login'));
    }

    public function test_admin_logout_rejects_get_requests_and_uses_web_csrf_middleware(): void
    {
        $admin = AdminUser::create([
            'name' => 'System Admin',
            'email' => 'admin-get@example.com',
            'password' => Hash::make('password'),
            'role' => 'super_admin',
            'is_active' => true,
        ]);

        $this->actingAs($admin, 'admin')
            ->get('/admin/logout')
            ->assertMethodNotAllowed();

        $this->assertAuthenticatedAs($admin, 'admin');

        $route = app('router')->getRoutes()->getByName('admin.logout');
        $this->assertNotNull($route);
        $this->assertContains('POST', $route->methods());
        $this->assertContains('web', $route->gatherMiddleware());
    }
}
