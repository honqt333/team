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
}
