<?php

declare(strict_types=1);

namespace Tests\Feature\System;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The /system panel language toggle.
 *
 * The button in SystemLayout.vue POSTs to the global `locale.set`
 * route (the same one the tenant AppLayout uses) and then flips the
 * client-side vue-i18n locale. This test pins the server half of that
 * contract so a refactor of the route, the middleware, or the session
 * key cannot silently break the switch.
 *
 * The client half (the button itself) is covered by the frontend
 * build + the translation-key parity test; what matters here is that
 * the endpoint accepts the locale, persists it in the session, and
 * that the SetLocale middleware picks the session value up on the
 * next request.
 */
class SystemLocaleToggleTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'is_system_admin' => true,
        ]);
    }

    public function test_locale_endpoint_accepts_english_and_persists_it_in_the_session(): void
    {
        $response = $this->actingAs($this->admin)
            ->post(route('locale.set'), ['locale' => 'en']);

        $response->assertRedirect();
        $response->assertSessionHas('locale', 'en');
    }

    public function test_locale_endpoint_accepts_arabic_and_persists_it_in_the_session(): void
    {
        // Seed the session with the opposite locale so we prove the
        // endpoint overwrites it rather than just passing through.
        $this->actingAs($this->admin)
            ->post(route('locale.set'), ['locale' => 'en'])
            ->assertSessionHas('locale', 'en');

        $response = $this->actingAs($this->admin)
            ->post(route('locale.set'), ['locale' => 'ar']);

        $response->assertRedirect();
        $response->assertSessionHas('locale', 'ar');
    }

    public function test_locale_endpoint_rejects_an_unsupported_locale(): void
    {
        $response = $this->actingAs($this->admin)
            ->from('/system')
            ->post(route('locale.set'), ['locale' => 'fr']);

        $response->assertSessionHasErrors('locale');
        $response->assertRedirect('/system');
    }

    public function test_locale_endpoint_requires_a_locale_value(): void
    {
        $response = $this->actingAs($this->admin)
            ->from('/system')
            ->post(route('locale.set'), []);

        $response->assertSessionHasErrors('locale');
    }

    public function test_set_locale_middleware_applies_the_session_locale_to_the_app(): void
    {
        // The switch button posts the locale, then Inertia re-renders
        // the page. The SetLocale middleware (priority: header >
        // session > cookie > config) must pick the session value up so
        // the server-side locale matches what the browser renders.
        $this->actingAs($this->admin)
            ->post(route('locale.set'), ['locale' => 'en']);

        $this->actingAs($this->admin)
            ->get('/system')
            ->assertOk();

        $this->assertSame('en', app()->getLocale());
    }

    public function test_locale_endpoint_is_reachable_from_the_system_panel_for_a_system_admin(): void
    {
        // The button lives in the /system top bar, so the endpoint has
        // to work for a system admin session (the `admin` guard) and
        // not just for a tenant user.
        $response = $this->actingAs($this->admin)
            ->post(route('locale.set'), ['locale' => 'en']);

        $response->assertRedirect();
        $response->assertSessionHas('locale', 'en');
    }

    public function test_locale_get_endpoint_returns_the_current_locale(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('locale.get'));

        $response->assertOk();
        $response->assertJsonStructure(['locale']);
        $this->assertContains($response->json('locale'), ['ar', 'en']);
    }
}
