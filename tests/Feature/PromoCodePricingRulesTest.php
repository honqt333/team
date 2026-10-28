<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Billing\Plan;
use App\Models\Billing\PromoCode;
use App\Models\Center;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Tests for the promo code rules of the billing pages (audit finding
 * #64, second half).
 *
 * The rules were written four times and the copies did not agree: the
 * price calculation and the payment flows only looked at `is_active`,
 * so an expired code still produced a discount, while the validation
 * endpoint also checked the dates — a code the form accepted could be
 * refused when the payment was made.
 *
 * A second fault lived in the same lines: every type that was not a
 * percentage was subtracted as a fixed amount, so a code that gives
 * seven TRIAL DAYS was taken off the price as seven riyals.
 */
class PromoCodePricingRulesTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    private Center $center;

    private User $user;

    private Plan $plan;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        Permission::findOrCreate('company.subscription.view', 'web');
        Permission::findOrCreate('company.subscription.create', 'web');

        $this->tenant = Tenant::factory()->create(['status' => 'active']);
        $this->center = Center::factory()->create(['tenant_id' => $this->tenant->id]);
        $this->user = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'current_center_id' => $this->center->id,
        ]);
        $this->user->centers()->attach($this->center->id, ['tenant_id' => $this->tenant->id]);

        app(PermissionRegistrar::class)->setPermissionsTeamId($this->tenant->id);
        $this->user->givePermissionTo(['company.subscription.view', 'company.subscription.create']);

        $this->plan = Plan::create([
            'name' => 'Pro',
            'name_ar' => 'احترافي',
            'name_en' => 'Pro',
            'slug' => 'pro-plan-test',
            'price_monthly' => 100,
            'price_yearly' => 1000,
            'is_active' => true,
        ]);
    }

    private function promoCode(array $overrides = []): PromoCode
    {
        return PromoCode::create(array_merge([
            'code' => 'SAVE25',
            'name' => 'Save 25',
            'discount_type' => 'percentage',
            'discount_value' => 25,
            'is_active' => true,
        ], $overrides));
    }

    public function test_a_valid_percentage_code_takes_its_share_off_the_price(): void
    {
        $promo = $this->promoCode();

        $response = $this->actingAsWithTeam($this->user)
            ->postJson(route('app.billing.calculate'), [
                'plan_id' => $this->plan->id,
                'billing_cycle' => 'yearly',
                'promo_code' => $promo->code,
            ]);

        $response->assertOk();

        $this->assertSame(1000.0, (float) $response->json('base_price'));
        $this->assertSame(250.0, (float) $response->json('discount'));
        $this->assertSame(750.0, (float) $response->json('subtotal'));
    }

    public function test_an_expired_code_takes_nothing_off_the_price(): void
    {
        $promo = $this->promoCode(['expires_at' => now()->subDay()]);

        $response = $this->actingAsWithTeam($this->user)
            ->postJson(route('app.billing.calculate'), [
                'plan_id' => $this->plan->id,
                'billing_cycle' => 'yearly',
                'promo_code' => $promo->code,
            ]);

        $response->assertOk();

        $this->assertSame(0.0, (float) $response->json('discount'), 'An expired code must not discount anything.');
        $this->assertSame(1000.0, (float) $response->json('subtotal'));
    }

    public function test_a_code_that_gives_trial_days_is_not_subtracted_as_money(): void
    {
        $promo = $this->promoCode(['discount_type' => 'trial_days', 'discount_value' => 7]);

        $response = $this->actingAsWithTeam($this->user)
            ->postJson(route('app.billing.calculate'), [
                'plan_id' => $this->plan->id,
                'billing_cycle' => 'yearly',
                'promo_code' => $promo->code,
            ]);

        $response->assertOk();

        $this->assertSame(0.0, (float) $response->json('discount'), 'Trial days are not an amount in riyals.');
        $this->assertSame(1000.0, (float) $response->json('subtotal'));
    }

    public function test_the_validation_endpoint_refuses_an_expired_code(): void
    {
        $promo = $this->promoCode(['expires_at' => now()->subDay()]);

        $response = $this->actingAsWithTeam($this->user)
            ->postJson(route('app.billing.validate-promo'), ['code' => $promo->code]);

        $response->assertStatus(422);
        $this->assertFalse($response->json('valid'));
    }

    public function test_the_validation_endpoint_accepts_a_valid_code(): void
    {
        $promo = $this->promoCode();

        $response = $this->actingAsWithTeam($this->user)
            ->postJson(route('app.billing.validate-promo'), ['code' => $promo->code]);

        $response->assertOk();
        $this->assertTrue($response->json('valid'));
        $this->assertSame($promo->code, $response->json('code'));
    }
}
