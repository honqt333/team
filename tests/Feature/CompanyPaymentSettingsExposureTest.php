<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Center;
use App\Models\PaymentSettings;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Tenant settings pages must never serialize the global PaymentSettings
 * model: gateway rows contain secret keys. They only need the enabled
 * methods, bank-transfer instructions, and the public checkout flags.
 */
class CompanyPaymentSettingsExposureTest extends TestCase
{
    use RefreshDatabase;

    private const SECRET = 'sk_test_batch1_must_never_reach_the_browser_9f3c';

    private const INSTRUCTIONS = 'حوّل المبلغ إلى الحساب ثم ارفع صورة الإيصال.';

    private const RECEIPT_INSTRUCTIONS = 'اذكر رقم الفاتورة في حقل المرجع ثم ارفع الإيصال بصيغة PDF.';

    private Tenant $tenant;

    private Center $center;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::forget('payment_settings');
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->tenant = Tenant::factory()->create(['status' => 'active']);
        $this->center = Center::factory()->create(['tenant_id' => $this->tenant->id]);
        $this->user = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'current_center_id' => $this->center->id,
        ]);
        $this->user->centers()->attach($this->center->id, ['tenant_id' => $this->tenant->id]);

        PaymentSettings::query()->delete();
        PaymentSettings::create([
            'moyasar' => [
                'enabled' => true,
                'publishable_key' => 'pk_test_public_batch1',
                'secret_key' => self::SECRET,
            ],
            'bank_transfer_enabled' => true,
            'bank_accounts' => [[
                'bank_name' => 'بنك تجريبي',
                'account_name' => 'شركة تجريبية',
                'iban' => 'SA0000000000000000000001',
            ]],
            'bank_transfer_instructions' => self::INSTRUCTIONS,
            'bank_transfer_receipt_instructions' => self::RECEIPT_INSTRUCTIONS,
            'default_gateway' => 'moyasar',
        ]);
    }

    protected function tearDown(): void
    {
        Cache::forget('payment_settings');
        parent::tearDown();
    }

    public function test_company_page_never_exposes_gateway_secrets(): void
    {
        $response = $this->actingAsWithTeam($this->user)
            ->get(route('settings.company'));

        $response->assertOk();
        $this->assertStringNotContainsString(
            self::SECRET,
            $response->getContent(),
            'The company settings response leaked a gateway secret key.',
        );

        $response->assertInertia(fn (Assert $page) => $page
            ->component('Settings/Company/Index')
            ->where('payment_settings.bank_transfer_instructions', self::INSTRUCTIONS)
            ->where('payment_settings.bank_transfer_receipt_instructions', self::RECEIPT_INSTRUCTIONS)
            ->where('payment_settings.bank_accounts.0.iban', 'SA0000000000000000000001')
            ->where('payment_settings.bank_transfer_enabled', true)
            ->where('enabled_gateways', fn ($gateways) => collect($gateways)->pluck('name')->all() === ['moyasar', 'bank_transfer'])
            ->missing('payment_settings.moyasar')
            ->missing('payment_settings.tap')
            ->missing('payment_settings.paytabs')
        );
    }

    public function test_billing_page_keeps_the_same_safe_payment_summary(): void
    {
        $response = $this->actingAsWithTeam($this->user)
            ->get(route('app.billing.index'));

        $response->assertOk();
        $this->assertStringNotContainsString(
            self::SECRET,
            $response->getContent(),
            'The billing response leaked a gateway secret key.',
        );

        $response->assertInertia(fn (Assert $page) => $page
            ->component('Settings/Billing/Index')
            ->where('payment_settings.bank_transfer_instructions', self::INSTRUCTIONS)
            ->where('payment_settings.bank_transfer_receipt_instructions', self::RECEIPT_INSTRUCTIONS)
            ->where('payment_settings.bank_accounts.0.iban', 'SA0000000000000000000001')
            ->missing('payment_settings.moyasar')
            ->missing('payment_settings.tap')
        );
    }
}
