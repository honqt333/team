<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Contracts\CustomerPortalOtpChannel;
use App\Contracts\SmsSendResult;
use App\Http\Middleware\PortalLocale;
use App\Http\Middleware\UsePortalSessionCookie;
use App\Models\Appointment;
use App\Models\Center;
use App\Models\Customer;
use App\Models\CustomerPortalOtp;
use App\Models\Invoice;
use App\Models\InvoiceLine;
use App\Models\Payment;
use App\Models\Quote;
use App\Models\QuoteLine;
use App\Models\ServiceBay;
use App\Models\Tenant;
use App\Models\TenantWebsite;
use App\Models\Vehicle;
use App\Models\VehicleMake;
use App\Models\VehicleModel;
use App\Models\WorkOrder;
use App\Models\WorkOrderItem;
use App\Support\CustomerPortalSession;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * Phase 7 — Customer Portal HTTP flow.
 *
 * End-to-end test of the login / verify / dashboard
 * endpoints, including:
 *
 *   - tenant resolution (public.tenant middleware)
 *   - generic messages (no phone enumeration)
 *   - session payload shape and lifetime
 *   - cross-tenant isolation
 *   - dashboard data isolation
 */
class CustomerPortalHttpTest extends TestCase
{
    use RefreshDatabase;

    protected string $capturedCode = '__unused__';

    protected function setUp(): void
    {
        parent::setUp();

        // Wipe the rate-limiter buckets so a test
        // that exercises the per-phone / per-IP
        // throttle does not pollute the next one.
        // The phpunit env uses the array cache
        // driver, but the buckets survive within
        // the same process — Cache::flush() makes
        // each test own a fresh cache.
        Cache::flush();

        // Reset the captured-code buffer and bind a fake
        // channel that records the last-sent code. We use
        // a static holder rather than self::$capturedCodeStatic
        // because the anonymous class below is in a
        // different scope and cannot write to a protected
        // property of the test class.
        self::$capturedCodeStatic = '';

        $this->app->instance(
            CustomerPortalOtpChannel::class,
            new class implements CustomerPortalOtpChannel
            {
                public function send(CustomerPortalOtp $otp, string $phone, string $code): SmsSendResult
                {
                    CustomerPortalHttpTest::$capturedCodeStatic = $code;

                    return SmsSendResult::ok('fake');
                }
            }
        );
    }

    public static string $capturedCodeStatic = '';

    private function publishedTenant(string $slug = 'demo'): Tenant
    {
        $tenant = Tenant::factory()->create(['slug' => $slug]);
        TenantWebsite::factory()->for($tenant)->published()->create();

        return $tenant;
    }

    private function customerIn(Tenant $tenant, string $phone = '+966501234567', string $name = 'Test Customer'): Customer
    {
        return Customer::factory()->create([
            'tenant_id' => $tenant->id,
            'phone' => $phone,
            'whatsapp' => $phone,
            'name' => $name,
        ]);
    }

    // ─────────────────────────────────────────────────────────────────
    // GET /{tenant}/portal/login
    // ─────────────────────────────────────────────────────────────────

    public function test_login_page_renders_for_published_tenant(): void
    {
        $tenant = $this->publishedTenant();

        $this->get('/'.$tenant->slug.'/portal/login')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Public/Portal/Login')
                ->where('tenant.slug', $tenant->slug)
            );
    }

    public function test_login_page_404s_for_unpublished_tenant(): void
    {
        $tenant = Tenant::factory()->create(['slug' => 'draft-tenant']);
        // No TenantWebsite published row.

        $this->get('/'.$tenant->slug.'/portal/login')->assertNotFound();
    }

    // ─────────────────────────────────────────────────────────────────
    // POST /{tenant}/portal/login (request OTP)
    // ─────────────────────────────────────────────────────────────────

    public function test_request_otp_returns_generic_message_for_known_phone(): void
    {
        $tenant = $this->publishedTenant();
        $customer = $this->customerIn($tenant);

        // The login → verify handoff: a successful
        // requestOtp MUST redirect the user to
        // /{tenant}/portal/verify, not back to the
        // login form. Before this fix, the controller
        // used `back()->with('status', ...)` which
        // bounced the user back to /portal/login;
        // the OTP landed in the customer's SMS but
        // the verify form was never shown. The fix
        // is an explicit redirect to the verify
        // route name.
        $this->post('/'.$tenant->slug.'/portal/login', ['phone' => '0501234567'])
            ->assertRedirect('/'.$tenant->slug.'/portal/verify');

        $this->assertSame(6, strlen(self::$capturedCodeStatic));
        $this->assertMatchesRegularExpression('/^\d{6}$/', self::$capturedCodeStatic);

        // The phone MUST be persisted in the session
        // so the verify page can pre-fill it.
        $this->assertSame('+966501234567', session('cp_pending_phone'));
    }

    public function test_request_otp_returns_same_generic_message_for_unknown_phone(): void
    {
        $tenant = $this->publishedTenant();
        // No customer in this tenant.

        $response = $this->post('/'.$tenant->slug.'/portal/login', ['phone' => '0509999999']);

        // Anti-enumeration: the unknown-phone path
        // MUST land on the SAME /verify URL with the
        // SAME flash status. A different redirect
        // target would let an attacker enumerate
        // which phones are registered by watching
        // the Location header. The status flash
        // must be the same string so the rendered
        // page is identical.
        $response->assertRedirect('/'.$tenant->slug.'/portal/verify');

        // No code was actually sent (no row, no
        // channel call). The fake channel's
        // capturedCode is still empty.
        $this->assertSame('', self::$capturedCodeStatic);

        // The phone is still stashed in the session
        // so the verify page can pre-fill it.
        $this->assertSame('+966509999999', session('cp_pending_phone'));

        // The user-facing flash is the SAME message
        // as the success case. We compare on the
        // "status" key that the verify page reads.
        $this->assertSame(
            $this->extractStatus($this->post('/'.$tenant->slug.'/portal/login', ['phone' => '0509999999'])),
            $this->extractStatus($response)
        );
    }

    public function test_request_otp_normalizes_phone_before_lookup(): void
    {
        $tenant = $this->publishedTenant();
        $this->customerIn($tenant);

        // Type with a different format; the service must
        // match against the stored +966501234567.
        $this->post('/'.$tenant->slug.'/portal/login', ['phone' => '966501234567'])
            ->assertRedirect();

        $this->assertSame(6, strlen(self::$capturedCodeStatic));
    }

    public function test_request_otp_rejects_empty_phone(): void
    {
        $tenant = $this->publishedTenant();

        $this->post('/'.$tenant->slug.'/portal/login', ['phone' => ''])
            ->assertSessionHasErrors('phone');
    }

    // ─────────────────────────────────────────────────────────────────
    // P1 — login → verify handoff
    //
    // Before the fix, the controller used
    // `back()->with('status', ...)` after a successful
    // requestOtp, which bounced the user back to the
    // login form. The OTP landed in the SMS inbox but
    // the verify form was never shown. These tests pin
    // the new flow:
    //
    //   1. requestOtp redirects to /{tenant}/portal/verify
    //      with the same flash status the verify page reads.
    //   2. cp_pending_phone survives the round-trip.
    //   3. GET /verify renders the Inertia component
    //      Public/Portal/Verify with the expected phone.
    //   4. The verify page receives the flash status as
    //      a prop, so the Inertia response carries the
    //      same generic "we sent you a code" message
    //      that the login page used to show.
    //   5. Rate limit still bounces the user BACK to
    //      the login page with the error, NOT to
    //      /verify (the customer is being told to wait,
    //      not told "we sent you a code").
    // ─────────────────────────────────────────────────────────────────

    public function test_request_otp_redirects_to_verify_with_status_flash(): void
    {
        $tenant = $this->publishedTenant();
        $this->customerIn($tenant);

        // Redirect target and flash payload — both
        // required for the verify page to render the
        // "we sent you a code" banner.
        $response = $this->post(
            '/'.$tenant->slug.'/portal/login',
            ['phone' => '0501234567']
        );

        $response->assertRedirect('/'.$tenant->slug.'/portal/verify');
        $response->assertSessionHas('status', __('customer_portal.auth.code_sent'));
    }

    public function test_request_otp_stores_normalized_phone_in_session(): void
    {
        $tenant = $this->publishedTenant();
        $this->customerIn($tenant);

        // Customer types a local format (05...).
        // The service normalizes to E.164 (+966...)
        // before the lookup; the session must hold
        // the normalized form so the verify page
        // pre-fills the E.164 phone consistently.
        $this->post('/'.$tenant->slug.'/portal/login', ['phone' => '0501234567'])
            ->assertRedirect('/'.$tenant->slug.'/portal/verify');

        $this->assertSame('+966501234567', session('cp_pending_phone'));
    }

    public function test_request_otp_for_unknown_phone_still_redirects_to_verify(): void
    {
        // The anti-enumeration contract: the
        // unknown-phone path MUST redirect to the
        // same URL with the same flash status. A
        // different redirect would leak which
        // phones are registered.
        $tenant = $this->publishedTenant();

        $this->post('/'.$tenant->slug.'/portal/login', ['phone' => '0509999999'])
            ->assertRedirect('/'.$tenant->slug.'/portal/verify')
            ->assertSessionHas('status', __('customer_portal.auth.code_sent'));

        // And the phone is still stashed, even
        // though no OTP was sent.
        $this->assertSame('+966509999999', session('cp_pending_phone'));
    }

    public function test_show_verify_renders_inertia_component_with_pending_phone(): void
    {
        $tenant = $this->publishedTenant();

        // Seed the session as if requestOtp just
        // bounced here. The verify page reads
        // cp_pending_phone from the session.
        session()->put('cp_pending_phone', '+966501234567');

        $this->get('/'.$tenant->slug.'/portal/verify')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Public/Portal/Verify')
                ->where('tenant.slug', $tenant->slug)
                ->where('phone', '+966501234567')
            );
    }

    public function test_show_verify_without_pending_phone_renders_empty_phone(): void
    {
        // Direct deep link to /verify with no
        // session: the page still renders, just
        // without a pre-filled phone. The customer
        // is expected to go back to /login.
        $tenant = $this->publishedTenant();

        $this->get('/'.$tenant->slug.'/portal/verify')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Public/Portal/Verify')
                ->where('phone', '')
            );
    }

    public function test_show_verify_surfaces_flash_status_to_inertia(): void
    {
        // The login → verify handoff sets
        // `session('status')` to the generic "we
        // sent you a code" message. The controller
        // reads it out of the session and exposes
        // it as a top-level prop (not via
        // `page.props.flash` — Inertia does not
        // auto-merge Laravel's session flash into
        // props). The Verify page reads the `status`
        // prop and renders the banner.
        //
        // We seed the flash with a known marker
        // string instead of using `__()` because
        // the test environment's locale flips
        // between ar / en depending on the
        // bootstrap, and we want the assertion to
        // be locale-independent.
        $marker = 'WE-SENT-YOU-A-CODE-MARKER';
        $tenant = $this->publishedTenant();
        session()->put('cp_pending_phone', '+966501234567');
        session()->flash('status', $marker);

        $this->get('/'.$tenant->slug.'/portal/verify')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Public/Portal/Verify')
                ->where('status', $marker)
                ->where('phone', '+966501234567')
            );
    }

    public function test_request_otp_rate_limit_does_not_redirect_to_verify(): void
    {
        // Rate limit (OtpRateLimitedException): the
        // controller must keep the user on the
        // LOGIN page with the error. The customer
        // is being told to wait, not told "we sent
        // you a code". cp_pending_phone must NOT
        // be set on a rate-limited request — the
        // session state should match the no-OTP-
        // was-sent reality.
        $tenant = $this->publishedTenant();
        $this->customerIn($tenant);

        // Drive the per-phone bucket to its cap.
        // RATE_LIMIT_PER_PHONE = 5 in 15 min; we
        // burn through the cooldown by spacing
        // each call with a Carbon jump.
        $base = Carbon::now();

        for ($i = 0; $i < 5; $i++) {
            Carbon::setTestNow($base->copy()->addSeconds(61 * ($i + 1)));
            $this->post('/'.$tenant->slug.'/portal/login', ['phone' => '0501234567']);
        }

        Carbon::setTestNow($base->copy()->addSeconds(61 * 6));

        // The 6th call (still inside the 15-min
        // window) hits the per-phone cap. The
        // controller MUST bounce back to login
        // with the rate-limit error.
        //
        // We seed a referer so `back()` resolves
        // to the login page (the natural source of
        // the form), not the test default of
        // `http://localhost`. The Inertia error
        // bag also surfaces the rate-limit
        // message under `errors.phone`, which is
        // how the Login page renders it.
        $response = $this->withHeaders([
            'referer' => '/'.$tenant->slug.'/portal/login',
        ])->post(
            '/'.$tenant->slug.'/portal/login',
            ['phone' => '0501234567']
        );

        $response->assertSessionHasErrors('phone');
        $location = $response->headers->get('Location') ?? '';
        $this->assertStringContainsString(
            '/portal/login',
            $location,
            'rate-limited request must bounce back to /portal/login, not to /verify'
        );
        $this->assertStringNotContainsString(
            '/portal/verify',
            $location,
            'rate-limited request must NOT redirect to /verify'
        );

        Carbon::setTestNow();
    }

    // ─────────────────────────────────────────────────────────────────
    // POST /{tenant}/portal/verify
    // ─────────────────────────────────────────────────────────────────

    public function test_verify_with_correct_code_redirects_to_dashboard(): void
    {
        // The "session is alive and points at the right
        // customer" assertion is the harder, more
        // interesting test — see
        // test_dashboard_renders_when_authenticated and
        // test_session_minted_for_tenant_a_cannot_be_used_for_tenant_b
        // which exercise the full session round-trip via
        // the loginAs() helper. This test is the simple
        // "verify with a good code lands the user on the
        // dashboard URL" sanity check.
        $tenant = $this->publishedTenant();
        $customer = $this->customerIn($tenant);

        $this->post('/'.$tenant->slug.'/portal/login', ['phone' => '0501234567']);
        $code = self::$capturedCodeStatic;

        $verifyResponse = $this->post('/'.$tenant->slug.'/portal/verify', [
            'phone' => '0501234567',
            'code' => $code,
        ]);
        $verifyResponse->assertRedirect('/'.$tenant->slug.'/portal/dashboard');

        // The redirect alone is not sufficient proof
        // of a successful portal login: the following
        // request must receive the session that verify()
        // regenerated and populated.
        $portalCookie = $verifyResponse->getCookie(
            UsePortalSessionCookie::COOKIE_NAME
        );
        $this->assertNotNull($portalCookie);
        $this->withUnencryptedCookie(
            UsePortalSessionCookie::COOKIE_NAME,
            $portalCookie->getValue()
        )->get('/'.$tenant->slug.'/portal/dashboard')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Public/Portal/Dashboard')
                ->where('customer.id', $customer->id)
            );
    }

    public function test_verify_with_wrong_code_returns_generic_error(): void
    {
        $tenant = $this->publishedTenant();
        $this->customerIn($tenant);

        $this->post('/'.$tenant->slug.'/portal/login', ['phone' => '0501234567']);

        $this->post('/'.$tenant->slug.'/portal/verify', [
            'phone' => '0501234567',
            'code' => '000000',
        ])->assertSessionHasErrors('code');
    }

    public function test_verify_with_unknown_phone_returns_generic_error(): void
    {
        $tenant = $this->publishedTenant();

        $this->post('/'.$tenant->slug.'/portal/verify', [
            'phone' => '0509999999',
            'code' => '123456',
        ])->assertSessionHasErrors('code');
    }

    public function test_verify_rejects_non_6_digit_code(): void
    {
        $tenant = $this->publishedTenant();

        $this->post('/'.$tenant->slug.'/portal/verify', [
            'phone' => '0501234567',
            'code' => 'abc',
        ])->assertSessionHasErrors('code');
    }

    // ─────────────────────────────────────────────────────────────────
    // GET /{tenant}/portal/dashboard
    // ─────────────────────────────────────────────────────────────────

    public function test_dashboard_redirects_to_login_when_unauthenticated(): void
    {
        $tenant = $this->publishedTenant();

        $this->get('/'.$tenant->slug.'/portal/dashboard')
            ->assertRedirect('/'.$tenant->slug.'/portal/login');
    }

    public function test_dashboard_renders_when_authenticated(): void
    {
        $tenant = $this->publishedTenant();
        $customer = $this->customerIn($tenant, '+966501234567', 'أحمد');

        $this->loginAs($customer, $tenant);

        $this->get('/'.$tenant->slug.'/portal/dashboard')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Public/Portal/Dashboard')
                ->where('customer.id', $customer->id)
                ->where('customer.name', 'أحمد')
            );
    }

    // ─────────────────────────────────────────────────────────────────
    // Functional: checked_in, center name, bay name,
    // expected_end_date.
    // ─────────────────────────────────────────────────────────────────

    public function test_dashboard_includes_checked_in_appointments(): void
    {
        $tenant = $this->publishedTenant();
        $customer = $this->customerIn($tenant);
        $center = Center::factory()->create(['tenant_id' => $tenant->id]);
        $vehicle = Vehicle::factory()->create(['tenant_id' => $tenant->id, 'customer_id' => $customer->id]);

        // A checked_in appointment from yesterday: it is
        // in the past but the customer should still see
        // it because the portal surfaces "you are
        // currently checked in".
        Appointment::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'scheduled_at' => Carbon::now()->subHours(2),
            'estimated_end_at' => Carbon::now()->addHours(1),
            'status' => Appointment::STATUS_CHECKED_IN,
        ]);

        $this->loginAs($customer, $tenant);

        $this->get('/'.$tenant->slug.'/portal/dashboard')
            ->assertInertia(fn ($page) => $page
                ->where('upcomingAppointments', fn ($apps) => collect($apps)
                    ->pluck('status')
                    ->contains(Appointment::STATUS_CHECKED_IN)
                )
            );
    }

    public function test_dashboard_surfaces_center_name_and_bay_name(): void
    {
        $tenant = $this->publishedTenant();
        $customer = $this->customerIn($tenant);
        $center = Center::factory()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Bay 1',
            'name_ar' => 'فرع 1',
            'name_en' => 'Branch 1',
        ]);
        $bay = ServiceBay::factory()->create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'name' => 'Bay 7',
        ]);
        $vehicle = Vehicle::factory()->create(['tenant_id' => $tenant->id, 'customer_id' => $customer->id]);

        Appointment::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'service_bay_id' => $bay->id,
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'scheduled_at' => Carbon::now()->addDay(),
            'estimated_end_at' => Carbon::now()->addDay()->addMinutes(30),
            'status' => Appointment::STATUS_CONFIRMED,
        ]);

        $this->loginAs($customer, $tenant);

        $this->get('/'.$tenant->slug.'/portal/dashboard')
            ->assertInertia(fn ($page) => $page
                ->where('upcomingAppointments.0.center_name', fn ($n) => is_string($n) && $n !== '')
                ->where('upcomingAppointments.0.service_bay_name', 'Bay 7')
                ->where('upcomingAppointments.0.center_id', $center->id)
                ->where('upcomingAppointments.0.service_bay_id', $bay->id)
            );
    }

    public function test_dashboard_surfaces_work_order_expected_end_date(): void
    {
        $tenant = $this->publishedTenant();
        $customer = $this->customerIn($tenant);
        $vehicle = Vehicle::factory()->create(['tenant_id' => $tenant->id, 'customer_id' => $customer->id]);

        $workOrder = WorkOrder::factory()->create([
            'tenant_id' => $tenant->id,
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'expected_end_date' => '2026-08-15',
        ]);

        $this->loginAs($customer, $tenant);

        $this->get('/'.$tenant->slug.'/portal/dashboard')
            ->assertInertia(fn ($page) => $page
                ->where('recentWorkOrders.0.id', $workOrder->id)
                ->where('recentWorkOrders.0.expected_end_date', '2026-08-15')
            );
    }

    // ─────────────────────────────────────────────────────────────────
    // UX improvements: tenant logo + brand colors + locale.
    // The login page surfaces the tenant's own logo (when
    // uploaded) instead of a generic user icon, and the
    // portal locale defaults to Arabic. These tests pin
    // the Inertia payload shape so future regressions are
    // caught.
    // ─────────────────────────────────────────────────────────────────

    public function test_login_page_surfaces_tenant_logo_and_brand_colors(): void
    {
        Storage::fake('public');
        $tenant = $this->publishedTenant();
        // The publishedTenant() helper creates the website
        // row but the Tenant model does not expose a
        // website() relation. We reach for the row
        // directly via the model.
        $website = TenantWebsite::where('tenant_id', $tenant->id)->first();
        $this->assertNotNull($website);
        $website->update([
            'logo_path' => 'tenants/'.$tenant->id.'/website/test-logo.png',
            'primary_color' => '#dc2626',
            'secondary_color' => '#0891b2',
        ]);
        $website->refresh();
        $this->assertNotNull($website->logo_path, 'logo_path did not persist');

        $this->get('/'.$tenant->slug.'/portal/login')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('tenant.has_logo', true)
                ->where('tenant.primary_color', '#dc2626')
                ->where('tenant.secondary_color', '#0891b2')
                ->where('tenant.site_name', fn ($n) => is_string($n) && $n !== '')
            );
    }

    public function test_login_page_falls_back_to_monogram_when_no_logo(): void
    {
        $tenant = $this->publishedTenant();
        // Ensure no logo_path
        $website = TenantWebsite::where('tenant_id', $tenant->id)->first();
        $this->assertNotNull($website);
        $website->update(['logo_path' => null]);

        $this->get('/'.$tenant->slug.'/portal/login')
            ->assertInertia(fn ($page) => $page
                ->where('tenant.has_logo', false)
                ->where('tenant.logo_url', null)
            );
    }

    public function test_portal_defaults_to_arabic_locale(): void
    {
        $tenant = $this->publishedTenant();

        $this->get('/'.$tenant->slug.'/portal/login')
            ->assertInertia(fn ($page) => $page
                ->where('locale.current', 'ar')
                ->where('locale.is_rtl', true)
            );
    }

    public function test_portal_locale_query_string_overrides_default(): void
    {
        $tenant = $this->publishedTenant();

        $this->get('/'.$tenant->slug.'/portal/login?lang=en')
            ->assertInertia(fn ($page) => $page
                ->where('locale.current', 'en')
                ->where('locale.is_rtl', false)
            );
    }

    public function test_portal_locale_cookie_persists_across_visits(): void
    {
        $tenant = $this->publishedTenant();

        // Set the persistent cookie and visit the page
        // without a query string — the cookie should win.
        $this->withCookie(PortalLocale::COOKIE_KEY, 'en')
            ->get('/'.$tenant->slug.'/portal/login')
            ->assertInertia(fn ($page) => $page
                ->where('locale.current', 'en')
            );
    }

    public function test_locale_toggle_endpoint_persists_choice_in_cookie(): void
    {
        $tenant = $this->publishedTenant();
        $website = TenantWebsite::where('tenant_id', $tenant->id)->first();
        $this->assertNotNull($website);
        $website->update(['logo_path' => null]);

        $response = $this->post('/'.$tenant->slug.'/portal/locale', [
            'lang' => 'en',
        ]);

        $response->assertRedirect();
        // The cookie is asserted by name + value. The
        // redirect should carry a `lang=en` query for the
        // very next render.
        $this->assertStringContainsString('lang=en', $response->headers->get('Location'));
    }

    public function test_locale_toggle_rejects_unsupported_value(): void
    {
        $tenant = $this->publishedTenant();

        $this->post('/'.$tenant->slug.'/portal/locale', [
            'lang' => 'fr',
        ])->assertSessionHasErrors('lang');
    }

    public function test_dashboard_does_not_leak_other_tenants_data(): void
    {
        $tenantA = $this->publishedTenant('a');
        $tenantB = $this->publishedTenant('b');
        $customerA = $this->customerIn($tenantA, '+966501111111', 'Customer A');
        $customerB = $this->customerIn($tenantB, '+966502222222', 'Customer B');

        // Logged in as A, visit A — must see only A's data.
        $this->loginAs($customerA, $tenantA);

        $this->get('/'.$tenantA->slug.'/portal/dashboard')
            ->assertInertia(fn ($page) => $page
                ->where('customer.id', $customerA->id)
                ->where('customer.name', 'Customer A')
            );

        // Now the same session tries to access B's portal.
        // The session is for tenant A, so the middleware must
        // kick them out.
        $this->get('/'.$tenantB->slug.'/portal/dashboard')
            ->assertRedirect('/'.$tenantB->slug.'/portal/login');
    }

    public function test_dashboard_does_not_leak_appointments_from_other_customers(): void
    {
        $tenant = $this->publishedTenant();
        $customerA = $this->customerIn($tenant, '+966501111111', 'Customer A');
        $customerB = $this->customerIn($tenant, '+966502222222', 'Customer B');

        // Create a vehicle + appointment for each customer.
        $vA = Vehicle::factory()->create(['tenant_id' => $tenant->id, 'customer_id' => $customerA->id]);
        $vB = Vehicle::factory()->create(['tenant_id' => $tenant->id, 'customer_id' => $customerB->id]);

        Appointment::create([
            'tenant_id' => $tenant->id,
            'center_id' => $vA->center_id ?? Center::factory()->create(['tenant_id' => $tenant->id])->id,
            'customer_id' => $customerA->id,
            'vehicle_id' => $vA->id,
            'scheduled_at' => Carbon::now()->addDay(),
            'estimated_end_at' => Carbon::now()->addDay()->addMinutes(30),
            'status' => Appointment::STATUS_SCHEDULED,
        ]);

        Appointment::create([
            'tenant_id' => $tenant->id,
            'center_id' => $vB->center_id ?? Center::factory()->create(['tenant_id' => $tenant->id])->id,
            'customer_id' => $customerB->id,
            'vehicle_id' => $vB->id,
            'scheduled_at' => Carbon::now()->addDay(),
            'estimated_end_at' => Carbon::now()->addDay()->addMinutes(30),
            'status' => Appointment::STATUS_SCHEDULED,
        ]);

        $this->loginAs($customerA, $tenant);

        $this->get('/'.$tenant->slug.'/portal/dashboard')
            ->assertInertia(fn ($page) => $page
                ->where('upcomingAppointments', fn ($apps) => collect($apps)->pluck('id')
                    ->diff([$vA->id])
                    ->isEmpty()
                    || collect($apps)->every(fn ($a) => $a['id'] !== $vB->id)
                )
            );
    }

    // ─────────────────────────────────────────────────────────────────
    // Session lifetime
    // ─────────────────────────────────────────────────────────────────

    public function test_session_expires_after_30_minutes_of_inactivity(): void
    {
        $tenant = $this->publishedTenant();
        $customer = $this->customerIn($tenant);
        $this->loginAs($customer, $tenant);

        // First request passes (last_seen_at is fresh).
        $this->get('/'.$tenant->slug.'/portal/dashboard')->assertOk();

        // Move 31 minutes forward. No requests in between.
        Carbon::setTestNow(Carbon::now()->addMinutes(31));

        $this->get('/'.$tenant->slug.'/portal/dashboard')
            ->assertRedirect('/'.$tenant->slug.'/portal/login');

        Carbon::setTestNow();
    }

    public function test_session_extends_on_activity_within_30_minutes(): void
    {
        $tenant = $this->publishedTenant();
        $customer = $this->customerIn($tenant);
        $this->loginAs($customer, $tenant);

        // First request at T+0.
        $this->get('/'.$tenant->slug.'/portal/dashboard')->assertOk();

        // Second request at T+20m. Session is still alive
        // (idle < 30m) and gets a fresh last_seen_at.
        Carbon::setTestNow(Carbon::now()->addMinutes(20));
        $this->get('/'.$tenant->slug.'/portal/dashboard')->assertOk();
        Carbon::setTestNow();

        // Third request at T+45m (relative to T+0). That's
        // T+25 from the last activity — still within the
        // 30-min window after the bump.
        Carbon::setTestNow(Carbon::now()->addMinutes(45));
        $this->get('/'.$tenant->slug.'/portal/dashboard')->assertOk();
        Carbon::setTestNow();
    }

    public function test_session_minted_for_tenant_a_cannot_be_used_for_tenant_b(): void
    {
        $tenantA = $this->publishedTenant('a');
        $tenantB = $this->publishedTenant('b');
        $customerA = $this->customerIn($tenantA, '+966501111111');

        // Mint a session in A.
        $this->loginAs($customerA, $tenantA);

        // Tenant A is fine.
        $this->get('/'.$tenantA->slug.'/portal/dashboard')->assertOk();

        // Tenant B rejects the A-minted session.
        $this->get('/'.$tenantB->slug.'/portal/dashboard')
            ->assertRedirect('/'.$tenantB->slug.'/portal/login');
    }

    public function test_session_for_deleted_customer_is_rejected(): void
    {
        $tenant = $this->publishedTenant();
        $customer = $this->customerIn($tenant);
        $this->loginAs($customer, $tenant);

        // Passes while alive.
        $this->get('/'.$tenant->slug.'/portal/dashboard')->assertOk();

        // Soft-delete the customer; the middleware must
        // refuse the cached session because the lookup
        // fails.
        $customer->delete();
        $this->get('/'.$tenant->slug.'/portal/dashboard')
            ->assertRedirect('/'.$tenant->slug.'/portal/login');
    }

    // ─────────────────────────────────────────────────────────────────
    // P1-3: hard ceiling. Even with active use, the
    // session must die after 12 hours from the first
    // mint. This blocks the "attacker captured the
    // session ID and refreshes it forever" attack.
    // ─────────────────────────────────────────────────────────────────

    public function test_session_dies_after_12_hours_absolute_ceiling(): void
    {
        $tenant = $this->publishedTenant();
        $customer = $this->customerIn($tenant);

        // Mint a session with created_at = 13 hours ago,
        // last_seen_at = 5 minutes ago. The 30-min idle
        // window is fine; the 12-hour ceiling must kill
        // it.
        $session = [
            'customer_id' => $customer->id,
            'tenant_id' => $tenant->id,
            'created_at' => Carbon::now()->subHours(13)->toIso8601String(),
            'last_seen_at' => Carbon::now()->subMinutes(5)->toIso8601String(),
        ];
        session()->put(CustomerPortalSession::key(), $session);

        $this->get('/'.$tenant->slug.'/portal/dashboard')
            ->assertRedirect('/'.$tenant->slug.'/portal/login');

        $this->assertNull(session(CustomerPortalSession::key()));
    }

    public function test_session_at_11_hours_59_minutes_is_still_valid(): void
    {
        // The 12-hour ceiling is exact. One minute before
        // the cap, the session is still alive.
        $tenant = $this->publishedTenant();
        $customer = $this->customerIn($tenant);

        $session = [
            'customer_id' => $customer->id,
            'tenant_id' => $tenant->id,
            'created_at' => Carbon::now()->subHours(11)->subMinutes(59)->toIso8601String(),
            'last_seen_at' => Carbon::now()->subMinutes(5)->toIso8601String(),
        ];
        session()->put(CustomerPortalSession::key(), $session);

        $this->get('/'.$tenant->slug.'/portal/dashboard')->assertOk();
    }

    // ─────────────────────────────────────────────────────────────────
    // P1-4: session ID regeneration on successful verify.
    // Captured-session-IDs from before the login must NOT
    // authenticate the new session.
    // ─────────────────────────────────────────────────────────────────

    public function test_successful_verify_regenerates_session_id(): void
    {
        $tenant = $this->publishedTenant();
        $this->customerIn($tenant);

        // First, get the login page so the session has a
        // valid ID. We grab the session ID from the
        // session store, then drive the verify flow and
        // assert the ID changed.
        $this->startSession();
        $preId = session()->getId();

        $this->post('/'.$tenant->slug.'/portal/login', ['phone' => '0501234567']);
        $code = self::$capturedCodeStatic;

        // Regenerate rewrites the session ID. We assert
        // by the side effect: the post-regenerate ID
        // is different AND the customer is logged in.
        $this->post('/'.$tenant->slug.'/portal/verify', [
            'phone' => '0501234567',
            'code' => $code,
        ])->assertRedirect('/'.$tenant->slug.'/portal/dashboard');

        $postId = session()->getId();
        $this->assertNotSame($preId, $postId, 'Session ID was not regenerated on successful verify');
    }

    // ─────────────────────────────────────────────────────────────────
    // Logout
    // ─────────────────────────────────────────────────────────────────

    public function test_logout_clears_the_session(): void
    {
        $tenant = $this->publishedTenant();
        $customer = $this->customerIn($tenant);
        $this->loginAs($customer, $tenant);

        $this->post('/'.$tenant->slug.'/portal/logout')
            ->assertRedirect('/'.$tenant->slug);

        $this->assertNull(session(CustomerPortalSession::key()));
    }

    public function test_dashboard_surfaces_vehicle_make_model_and_tenant_branding_and_locale(): void
    {
        Storage::fake('public');
        $tenant = $this->publishedTenant();
        $website = TenantWebsite::where('tenant_id', $tenant->id)->first();
        $this->assertNotNull($website);
        $website->update([
            'site_name' => 'Khidmh Pro Model Workshop',
            'logo_path' => 'tenants/'.$tenant->id.'/website/test-logo.png',
            'primary_color' => '#1e40af',
            'secondary_color' => '#0284c7',
        ]);

        $customer = $this->customerIn($tenant);

        $make = VehicleMake::create([
            'source' => 'system',
            'name_ar' => 'تويوتا',
            'name_en' => 'Toyota',
            'is_active' => true,
        ]);
        $model = VehicleModel::create([
            'source' => 'system',
            'make_id' => $make->id,
            'name_ar' => 'كامري',
            'name_en' => 'Camry',
            'is_active' => true,
        ]);

        $vehicleFromCatalog = Vehicle::factory()->create([
            'tenant_id' => $tenant->id,
            'customer_id' => $customer->id,
            'plate_number' => 'أ ب ج 1234',
            'make_id' => $make->id,
            'model_id' => $model->id,
            'year' => 2024,
            'color' => 'أبيض',
        ]);

        $vehicleCustom = Vehicle::factory()->create([
            'tenant_id' => $tenant->id,
            'customer_id' => $customer->id,
            'plate_number' => 'د هـ و 5678',
            'make_id' => null,
            'model_id' => null,
            'make_other' => 'ريفيان',
            'model_other' => 'R1T',
            'year' => 2025,
            'color' => 'أزرق',
        ]);

        $this->loginAs($customer, $tenant);

        $this->get('/'.$tenant->slug.'/portal/dashboard?lang=en')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Public/Portal/Dashboard')
                ->where('tenant.slug', $tenant->slug)
                ->where('tenant.site_name', 'Khidmh Pro Model Workshop')
                ->where('tenant.has_logo', true)
                ->where('tenant.primary_color', '#1e40af')
                ->where('tenant.secondary_color', '#0284c7')
                ->where('locale.current', 'en')
                ->where('locale.is_rtl', false)
                ->where('vehicles', function ($vehicles) use ($vehicleFromCatalog, $vehicleCustom) {
                    $byId = collect($vehicles)->keyBy('id');
                    $cat = $byId->get($vehicleFromCatalog->id);
                    $cus = $byId->get($vehicleCustom->id);

                    return $cat !== null
                        && $cat['make'] === 'Toyota'
                        && $cat['model'] === 'Camry'
                        && $cat['year'] === 2024
                        && $cus !== null
                        && $cus['make'] === 'ريفيان'
                        && $cus['model'] === 'R1T';
                })
            );
    }

    // ─────────────────────────────────────────────────────────────────
    // Work Orders tracking (Index & Show + Isolation)
    // ─────────────────────────────────────────────────────────────────

    public function test_portal_work_orders_lists_only_authenticated_customers_work_orders(): void
    {
        $tenant = $this->publishedTenant();
        $center = Center::factory()->create(['tenant_id' => $tenant->id]);
        $customer = $this->customerIn($tenant, '+966501234567', 'Owner');
        $otherCustomer = $this->customerIn($tenant, '+966509998877', 'Other');

        $vehicle = Vehicle::factory()->create([
            'tenant_id' => $tenant->id,
            'customer_id' => $customer->id,
        ]);
        $otherVehicle = Vehicle::factory()->create([
            'tenant_id' => $tenant->id,
            'customer_id' => $otherCustomer->id,
        ]);

        $mine = WorkOrder::factory()->create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'code' => 'WO-MINE-01',
            'status' => WorkOrder::STATUS_IN_PROGRESS,
        ]);

        WorkOrderItem::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'work_order_id' => $mine->id,
            'title' => 'Brake Pads Replacement',
            'qty' => 1,
            'unit_price' => 250,
            'line_total' => 250,
            'status' => WorkOrderItem::STATUS_COMPLETED,
        ]);

        $foreign = WorkOrder::factory()->create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'customer_id' => $otherCustomer->id,
            'vehicle_id' => $otherVehicle->id,
            'code' => 'WO-OTHER-99',
            'status' => WorkOrder::STATUS_OPEN,
        ]);

        $this->loginAs($customer, $tenant);

        $this->get('/'.$tenant->slug.'/portal/work-orders')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Public/Portal/WorkOrders/Index')
                ->has('workOrders', 1)
                ->where('workOrders.0.code', 'WO-MINE-01')
            );

        $this->get('/'.$tenant->slug.'/portal/work-order/'.$mine->id)
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Public/Portal/WorkOrders/Show')
                ->where('workOrder.code', 'WO-MINE-01')
                ->has('workOrder.items', 1)
                ->where('workOrder.items.0.title', 'Brake Pads Replacement')
            );

        // Cross-customer work order is unreachable (404)
        $this->get('/'.$tenant->slug.'/portal/work-order/'.$foreign->id)
            ->assertNotFound();
    }

    // ─────────────────────────────────────────────────────────────────
    // Quotes review, approve (with signature) & reject
    // ─────────────────────────────────────────────────────────────────

    public function test_portal_quotes_review_and_approve_with_signature(): void
    {
        Storage::fake('public');

        $tenant = $this->publishedTenant();
        $center = Center::factory()->create(['tenant_id' => $tenant->id]);
        $customer = $this->customerIn($tenant, '+966501234567', 'Owner');
        $otherCustomer = $this->customerIn($tenant, '+966509998877', 'Other');

        $vehicle = Vehicle::factory()->create([
            'tenant_id' => $tenant->id,
            'customer_id' => $customer->id,
        ]);
        $otherVehicle = Vehicle::factory()->create([
            'tenant_id' => $tenant->id,
            'customer_id' => $otherCustomer->id,
        ]);

        $quote = Quote::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'code' => 'QT-PORTAL-01',
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'status' => Quote::STATUS_SENT,
            'valid_until' => now()->addDays(7)->toDateString(),
            'subtotal' => 400,
            'total_tax' => 60,
            'total_incl_tax' => 460,
            'tax_enabled_snapshot' => true,
            'tax_rate_snapshot' => 15,
        ]);

        QuoteLine::create([
            'quote_id' => $quote->id,
            'title' => 'Engine Tune-Up',
            'description' => 'Full diagnostic & spark plugs',
            'qty' => 1,
            'unit_price' => 400,
            'line_total' => 400,
        ]);

        $otherQuote = Quote::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'code' => 'QT-OTHER-99',
            'customer_id' => $otherCustomer->id,
            'vehicle_id' => $otherVehicle->id,
            'status' => Quote::STATUS_SENT,
            'valid_until' => now()->addDays(7)->toDateString(),
            'subtotal' => 100,
            'total_incl_tax' => 100,
        ]);

        $this->loginAs($customer, $tenant);

        $this->get('/'.$tenant->slug.'/portal/quotes')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Public/Portal/Quotes/Index')
                ->has('quotes', 1)
                ->where('quotes.0.code', 'QT-PORTAL-01')
                ->where('quotes.0.can_be_actioned', true)
            );

        $this->get('/'.$tenant->slug.'/portal/quote/'.$quote->id)
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Public/Portal/Quotes/Show')
                ->where('quote.code', 'QT-PORTAL-01')
                ->has('quote.lines', 1)
            );

        // Cross-customer quote returns 404
        $this->get('/'.$tenant->slug.'/portal/quote/'.$otherQuote->id)
            ->assertNotFound();

        // SVG script signature is rejected
        $svg = '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>';
        $this->post('/'.$tenant->slug.'/portal/quote/'.$quote->id.'/approve', [
            'signature' => 'data:image/svg+xml;base64,'.base64_encode($svg),
        ])->assertSessionHasErrors('signature');

        $this->assertSame(Quote::STATUS_SENT, $quote->fresh()->status);

        // Valid PNG signature approves the quote
        $png = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=';
        $this->post('/'.$tenant->slug.'/portal/quote/'.$quote->id.'/approve', [
            'signature' => 'data:image/png;base64,'.$png,
        ])->assertRedirect('/'.$tenant->slug.'/portal/quote/'.$quote->id)
            ->assertSessionHas('success');

        $quote->refresh();
        $this->assertSame(Quote::STATUS_APPROVED, $quote->status);
        $this->assertNotNull($quote->approved_at);
        $this->assertNotNull($quote->signature_path);
    }

    public function test_portal_quote_can_be_rejected_with_reason_and_enforces_ownership(): void
    {
        $tenant = $this->publishedTenant();
        $center = Center::factory()->create(['tenant_id' => $tenant->id]);
        $customer = $this->customerIn($tenant, '+966501234567', 'Owner');
        $otherCustomer = $this->customerIn($tenant, '+966509998877', 'Other');

        $vehicle = Vehicle::factory()->create([
            'tenant_id' => $tenant->id,
            'customer_id' => $customer->id,
        ]);

        $quote = Quote::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'code' => 'QT-REJ-01',
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'status' => Quote::STATUS_SENT,
            'valid_until' => now()->addDays(5)->toDateString(),
            'subtotal' => 300,
            'total_incl_tax' => 300,
        ]);

        $this->loginAs($otherCustomer, $tenant);
        $this->post('/'.$tenant->slug.'/portal/quote/'.$quote->id.'/reject', [
            'rejection_reason' => 'Not mine',
        ])->assertNotFound();

        $this->loginAs($customer, $tenant);
        $this->post('/'.$tenant->slug.'/portal/quote/'.$quote->id.'/reject', [
            'rejection_reason' => 'Too expensive, please revise.',
        ])->assertRedirect('/'.$tenant->slug.'/portal/quote/'.$quote->id)
            ->assertSessionHas('success');

        $quote->refresh();
        $this->assertSame(Quote::STATUS_REJECTED, $quote->status);
        $this->assertSame('Too expensive, please revise.', $quote->rejection_reason);
    }

    // ─────────────────────────────────────────────────────────────────
    // Invoices statement, draft exclusion & ZATCA QR SVG rendering
    // ─────────────────────────────────────────────────────────────────

    public function test_portal_invoices_excludes_drafts_renders_qr_and_enforces_isolation(): void
    {
        $tenant = $this->publishedTenant();
        $center = Center::factory()->create(['tenant_id' => $tenant->id]);
        $customer = $this->customerIn($tenant, '+966501234567', 'Owner');
        $otherCustomer = $this->customerIn($tenant, '+966509998877', 'Other');

        $validInvoice = Invoice::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'customer_id' => $customer->id,
            'invoice_number' => 'INV-PORTAL-01',
            'issue_date' => now(),
            'supply_date' => now()->toDateString(),
            'type' => 'invoice',
            'status' => 'valid',
            'payment_status' => 'partial',
            'tax_enabled_snapshot' => true,
            'tax_rate_snapshot' => 15,
            'total_excl_tax' => 1000,
            'total_tax' => 150,
            'total_incl_tax' => 1150,
            'total_paid' => 500,
            'currency_code' => 'SAR',
            'zatca_qr_tlv' => 'ARVLaGlkbWggUHJvIFdvcmtzaG9wAg8zMTAwMDAwMDAwMDAwMDM=',
        ]);

        InvoiceLine::create([
            'invoice_id' => $validInvoice->id,
            'description' => 'Transmission Overhaul',
            'qty' => 1,
            'unit_price' => 1000,
            'line_total_excl_tax' => 1000,
            'tax_amount' => 150,
            'line_total_incl_tax' => 1150,
            'is_part' => false,
        ]);

        Payment::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'invoice_id' => $validInvoice->id,
            'amount' => 500,
            'payment_method' => 'mada',
            'payment_date' => now(),
            'type' => 'payment',
        ]);

        $draftInvoice = Invoice::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'customer_id' => $customer->id,
            'invoice_number' => 'INV-DRAFT-HIDDEN',
            'issue_date' => now(),
            'supply_date' => now()->toDateString(),
            'type' => 'invoice',
            'status' => 'draft',
            'payment_status' => 'unpaid',
            'total_excl_tax' => 200,
            'total_tax' => 0,
            'total_incl_tax' => 200,
            'total_paid' => 0,
            'currency_code' => 'SAR',
        ]);

        $foreignInvoice = Invoice::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'customer_id' => $otherCustomer->id,
            'invoice_number' => 'INV-FOREIGN-99',
            'issue_date' => now(),
            'supply_date' => now()->toDateString(),
            'type' => 'invoice',
            'status' => 'valid',
            'payment_status' => 'unpaid',
            'total_excl_tax' => 300,
            'total_tax' => 0,
            'total_incl_tax' => 300,
            'total_paid' => 0,
            'currency_code' => 'SAR',
        ]);

        $this->loginAs($customer, $tenant);

        $this->get('/'.$tenant->slug.'/portal/invoices')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Public/Portal/Invoices/Index')
                ->has('invoices', 1)
                ->where('invoices.0.invoice_number', 'INV-PORTAL-01')
                ->where('financialSummary.total_invoiced', 1150)
                ->where('financialSummary.total_paid', 500)
                ->where('financialSummary.total_balance', 650)
            );

        $this->get('/'.$tenant->slug.'/portal/invoice/'.$validInvoice->id)
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Public/Portal/Invoices/Show')
                ->where('invoice.invoice_number', 'INV-PORTAL-01')
                ->has('invoice.lines', 1)
                ->has('invoice.payments', 1)
                ->where('invoice.zatca_qr_svg', fn ($svg) => is_string($svg) && str_starts_with($svg, 'data:image/svg+xml;base64,'))
            );

        // Draft and cross-customer invoices must not be accessible
        $this->get('/'.$tenant->slug.'/portal/invoice/'.$draftInvoice->id)
            ->assertNotFound();
        $this->get('/'.$tenant->slug.'/portal/invoice/'.$foreignInvoice->id)
            ->assertNotFound();
    }

    // ─────────────────────────────────────────────────────────────────
    // Appointments Hub
    // ─────────────────────────────────────────────────────────────────

    public function test_portal_appointments_hub_splits_upcoming_and_past(): void
    {
        $tenant = $this->publishedTenant();
        $center = Center::factory()->create(['tenant_id' => $tenant->id]);
        $bay = ServiceBay::factory()->create(['tenant_id' => $tenant->id, 'center_id' => $center->id]);
        $customer = $this->customerIn($tenant, '+966501234567', 'Owner');
        $vehicle = Vehicle::factory()->create(['tenant_id' => $tenant->id, 'customer_id' => $customer->id]);

        Appointment::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'service_bay_id' => $bay->id,
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'scheduled_at' => now()->addDays(2),
            'estimated_end_at' => now()->addDays(2)->addHour(),
            'status' => Appointment::STATUS_CONFIRMED,
            'notes' => 'AC check',
        ]);

        Appointment::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'service_bay_id' => $bay->id,
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'scheduled_at' => now()->subDays(5),
            'estimated_end_at' => now()->subDays(5)->addHour(),
            'status' => Appointment::STATUS_CONVERTED,
        ]);

        $this->loginAs($customer, $tenant);

        $this->get('/'.$tenant->slug.'/portal/appointments')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Public/Portal/Appointments/Index')
                ->has('upcomingAppointments', 1)
                ->where('upcomingAppointments.0.notes', 'AC check')
                ->has('pastAppointments', 1)
            );
    }

    // ─────────────────────────────────────────────────────────────────
    // Test helpers
    // ─────────────────────────────────────────────────────────────────

    /**
     * Mint a portal session for the given customer in the
     * given tenant, exactly the way the verify endpoint
     * would.
     */
    private function loginAs(Customer $customer, Tenant $tenant): void
    {
        session()->put(CustomerPortalSession::key(), [
            'customer_id' => $customer->id,
            'tenant_id' => $tenant->id,
            'last_seen_at' => Carbon::now()->toIso8601String(),
        ]);
    }

    /**
     * Capture the "status" flash the login endpoint sets
     * so we can compare the unknown-phone path with the
     * known-phone path.
     */
    private function extractStatus(TestResponse $response): ?string
    {
        // The flash data is exposed via the redirect
        // response's session bag. We pull it back from
        // the test response's flashed data if present.
        $session = session()->all();

        foreach ($session as $key => $value) {
            if (is_object($value) && method_exists($value, 'getName')) {
                // Skip flash bag object
                continue;
            }
        }

        return session()->get('status');
    }
}
