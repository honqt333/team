<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Models\Billing\Plan;
use App\Models\Billing\PromoCode;
use App\Models\Billing\PromoCodeUsage;
use App\Models\Billing\Subscription;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests for the promo code of the registration form (audit finding #64).
 *
 * The form asked for a promo code and the server accepted any string of
 * up to fifty characters — then ignored it. A `TODO` in the controller
 * said what should have happened. The customer entered a code, saw the
 * registration succeed, and had every reason to believe the discount had
 * been applied.
 *
 * The code is now checked against the same rules the billing pages use,
 * a code that gives trial days is applied to the trial, and the use of
 * the code is recorded so its limits count.
 */
class RegistrationPromoCodeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // The trial subscription is only created when a trial plan
        // exists — the plans are seeded by a migration, so this only
        // fills the gap when they are not.
        Plan::firstOrCreate(
            ['slug' => 'trial'],
            [
                'name' => 'Trial',
                'name_ar' => 'تجريبية',
                'name_en' => 'Trial',
                'price_monthly' => 0,
                'price_yearly' => 0,
                'trial_days' => 14,
                'is_active' => true,
            ]
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function registrationPayload(array $overrides = []): array
    {
        return array_merge([
            'company_name' => 'Test Company',
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'test@example.com',
            'phone' => '0512345678',
            'password' => 'password',
            'password_confirmation' => 'password',
            'terms' => 'on',
        ], $overrides);
    }

    private function promoCode(array $overrides = []): PromoCode
    {
        return PromoCode::create(array_merge([
            'code' => 'WELCOME7',
            'name' => 'Welcome',
            'discount_type' => 'trial_days',
            'discount_value' => 7,
            'is_active' => true,
        ], $overrides));
    }

    public function test_an_unknown_promo_code_stops_the_registration(): void
    {
        $response = $this->post('/register', $this->registrationPayload([
            'promo_code' => 'NOT-A-CODE',
        ]));

        $response->assertSessionHasErrors('promo_code');

        $this->assertSame(0, Tenant::count(), 'A refused promo code must not leave a half-registered tenant behind.');
        $this->assertGuest();
    }

    public function test_an_expired_promo_code_stops_the_registration(): void
    {
        $this->promoCode(['expires_at' => now()->subDay()]);

        $response = $this->post('/register', $this->registrationPayload([
            'promo_code' => 'WELCOME7',
        ]));

        $response->assertSessionHasErrors('promo_code');
        $this->assertSame(0, Tenant::count());
    }

    public function test_an_inactive_promo_code_stops_the_registration(): void
    {
        $this->promoCode(['is_active' => false]);

        $response = $this->post('/register', $this->registrationPayload([
            'promo_code' => 'WELCOME7',
        ]));

        $response->assertSessionHasErrors('promo_code');
        $this->assertSame(0, Tenant::count());
    }

    public function test_a_promo_code_that_is_not_used_up_yet_stops_the_registration(): void
    {
        $promo = $this->promoCode(['max_uses' => 1, 'times_used' => 1]);

        $response = $this->post('/register', $this->registrationPayload([
            'promo_code' => $promo->code,
        ]));

        $response->assertSessionHasErrors('promo_code');
        $this->assertSame(0, Tenant::count());
    }

    public function test_a_trial_days_promo_code_gives_the_tenant_extra_days(): void
    {
        $promo = $this->promoCode(['discount_type' => 'trial_days', 'discount_value' => 7]);

        $response = $this->post('/register', $this->registrationPayload([
            'promo_code' => $promo->code,
        ]));

        $response->assertRedirect(route('dashboard', absolute: false));
        $this->assertAuthenticated();

        $tenant = Tenant::first();
        $this->assertNotNull($tenant);

        // The trial is 14 days by default; the code adds seven.
        $subscription = Subscription::where('tenant_id', $tenant->id)->first();
        $this->assertNotNull($subscription);
        $this->assertSame($promo->code, $subscription->promo_code);
        $this->assertEqualsWithDelta(
            21,
            now()->diffInDays($subscription->ends_at, false),
            0.1,
            'The trial must be extended by the days the code gives.'
        );
        $this->assertEqualsWithDelta(
            21,
            now()->diffInDays($tenant->trial_ends_at, false),
            0.1
        );

        // The use of the code is recorded, so the limit on it counts.
        $this->assertSame(1, PromoCodeUsage::where('promo_code_id', $promo->id)->count());
        $this->assertSame(1, (int) $promo->fresh()->times_used);
    }

    public function test_a_discount_promo_code_is_kept_valid_for_the_paid_plan(): void
    {
        $promo = $this->promoCode(['discount_type' => 'percentage', 'discount_value' => 25]);

        $response = $this->post('/register', $this->registrationPayload([
            'promo_code' => $promo->code,
        ]));

        $response->assertRedirect(route('dashboard', absolute: false));
        $this->assertAuthenticated();

        $tenant = Tenant::first();
        $subscription = Subscription::where('tenant_id', $tenant->id)->first();

        $this->assertSame($promo->code, $subscription->promo_code);

        // The trial is free, so a discount on a paid plan has nothing to
        // discount — and the code must NOT be marked as used, or the
        // tenant would lose it before the upgrade.
        $this->assertSame(0, PromoCodeUsage::where('promo_code_id', $promo->id)->count());
        $this->assertSame(0, (int) $promo->fresh()->times_used);
    }

    public function test_registration_without_a_promo_code_still_works(): void
    {
        $response = $this->post('/register', $this->registrationPayload());

        $response->assertRedirect(route('dashboard', absolute: false));
        $this->assertAuthenticated();

        $tenant = Tenant::first();
        $subscription = Subscription::where('tenant_id', $tenant->id)->first();

        $this->assertNull($subscription->promo_code);
        $this->assertEqualsWithDelta(
            14,
            now()->diffInDays($subscription->ends_at, false),
            0.1
        );
    }
}
