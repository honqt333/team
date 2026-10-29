<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Models\AdminUser;
use App\Services\TwoFactorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use PragmaRX\Google2FA\Google2FA;
use Tests\TestCase;

/**
 * Tests for the two-factor authentication of a system administrator
 * (audit finding #62).
 *
 * The admin panel login authenticates on the 'admin' guard, so the
 * identity it returns is an AdminUser. The two-factor check in the
 * login response read the tenant user instead, and the admin branch
 * returned the redirect to /system before reaching it — so an admin
 * who had turned two-factor authentication on reached the system panel
 * with the password alone.
 *
 * The challenge for the admin panel lives at /2fa/challenge and reads
 * '2fa:user_type' to know which table to load the identity from, so
 * the login response has to hand that over.
 */
class AdminTwoFactorLoginTest extends TestCase
{
    use RefreshDatabase;

    private function admin(array $attributes = []): AdminUser
    {
        return AdminUser::create(array_merge([
            'name' => 'System Admin',
            'email' => 'admin@example.test',
            'password' => Hash::make('secret-password'),
            'role' => 'super_admin',
            'is_active' => true,
        ], $attributes));
    }

    /**
     * An admin with two-factor authentication turned on.
     */
    private function adminWithTwoFactor(): AdminUser
    {
        $service = app(TwoFactorService::class);

        return $this->admin([
            'two_factor_secret' => encrypt($service->generateSecret()),
            'two_factor_confirmed_at' => now(),
        ]);
    }

    public function test_an_admin_with_two_factor_enabled_is_sent_to_the_challenge(): void
    {
        $admin = $this->adminWithTwoFactor();

        $response = $this->post('/admin/login', [
            'email' => $admin->email,
            'password' => 'secret-password',
        ]);

        $response->assertRedirect('/2fa/challenge');

        // The identity is handed to the challenge in the session, and
        // the admin guard is released until the code is verified.
        $this->assertSame($admin->id, session('2fa:user_id'));
        $this->assertSame('admin', session('2fa:user_type'));
        $this->assertFalse(Auth::guard('admin')->check());
    }

    public function test_an_admin_with_two_factor_enabled_does_not_reach_the_system_panel(): void
    {
        $admin = $this->adminWithTwoFactor();

        $this->post('/admin/login', [
            'email' => $admin->email,
            'password' => 'secret-password',
        ]);

        // The panel must not be reachable while the challenge is open.
        $this->assertFalse(Auth::guard('admin')->check());
    }

    public function test_an_admin_without_two_factor_still_reaches_the_system_panel(): void
    {
        $admin = $this->admin();

        $response = $this->post('/admin/login', [
            'email' => $admin->email,
            'password' => 'secret-password',
        ]);

        $response->assertRedirect('/system');
        $this->assertTrue(Auth::guard('admin')->check());
        $this->assertSame($admin->id, Auth::guard('admin')->id());
    }

    public function test_the_challenge_page_renders_for_a_pending_admin(): void
    {
        $admin = $this->adminWithTwoFactor();

        $this->post('/admin/login', [
            'email' => $admin->email,
            'password' => 'secret-password',
        ]);

        $response = $this->get('/2fa/challenge');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('System/Security/TwoFactorChallenge')
        );
    }

    public function test_verifying_the_code_logs_the_admin_in_on_the_admin_guard(): void
    {
        $admin = $this->adminWithTwoFactor();

        $this->post('/admin/login', [
            'email' => $admin->email,
            'password' => 'secret-password',
        ]);

        // The admin panel enrols a TOTP secret (the QR code of the
        // setup page), so the code the admin types is the current
        // one from their authenticator app.
        $code = $this->currentTotp($admin);

        $response = $this->post('/2fa/verify', ['code' => $code]);

        $response->assertRedirect('/system');
        $this->assertTrue(Auth::guard('admin')->check());
        $this->assertSame($admin->id, Auth::guard('admin')->id());

        // The session markers are cleared once the identity is in.
        $this->assertNull(session('2fa:user_id'));
        $this->assertNull(session('2fa:user_type'));
    }

    public function test_a_wrong_code_does_not_log_the_admin_in(): void
    {
        $admin = $this->adminWithTwoFactor();

        $this->post('/admin/login', [
            'email' => $admin->email,
            'password' => 'secret-password',
        ]);

        $response = $this->post('/2fa/verify', ['code' => '000000']);

        $response->assertSessionHasErrors('code');
        $this->assertFalse(Auth::guard('admin')->check());
    }

    /**
     * The code an authenticator app shows right now for this admin.
     */
    private function currentTotp(AdminUser $admin): string
    {
        $secret = decrypt($admin->two_factor_secret);

        return (new Google2FA)->getCurrentOtp($secret);
    }
}
