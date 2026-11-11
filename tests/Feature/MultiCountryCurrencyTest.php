<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Http\Requests\Customer\CustomerStoreRequest;
use App\Models\Center;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\PaymentSettings;
use App\Models\Tenant;
use App\Models\TenantTaxSetting;
use App\Models\User;
use App\Services\CreditNoteService;
use App\Services\InvoiceService;
use App\Services\Zatca\ZatcaOnboardingService;
use App\Support\CountryCurrencyRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Inertia\Testing\AssertableInertia as Assert;
use ReflectionMethod;
use Tests\TestCase;

class MultiCountryCurrencyTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{0: Tenant, 1: Center, 2: User}
     */
    private function createTenantUser(string $country = 'SA', string $currency = 'SAR'): array
    {
        $tenant = Tenant::create([
            'name' => 'مركز متعدد الدول',
            'legal_name' => 'شركة اختبار الدول والعملات',
            'legal_name_en' => 'Multi Country Test Co',
            'trade_name' => 'مركز الاختبار',
            'owner_name' => 'أحمد',
            'slug' => 'multi-country-'.uniqid(),
            'status' => 'active',
            'country' => $country,
            'currency' => $currency,
            'timezone' => CountryCurrencyRegistry::countryMeta($country)['timezone'] ?? 'Asia/Riyadh',
            'vat_number' => $country === 'SA' ? '300000000000003' : null,
        ]);

        TenantTaxSetting::create([
            'tenant_id' => $tenant->id,
            'vat_enabled' => true,
            'vat_rate' => CountryCurrencyRegistry::defaultVatRate($country),
            'services_vat_rate' => CountryCurrencyRegistry::defaultVatRate($country),
            'parts_vat_rate' => CountryCurrencyRegistry::defaultVatRate($country),
            'currency_code' => $currency,
            'tax_number' => $country === 'SA' ? '300000000000003' : null,
        ]);

        $center = Center::create([
            'tenant_id' => $tenant->id,
            'name' => 'الفرع الرئيسي',
            'name_ar' => 'الفرع الرئيسي',
            'slug' => 'main-'.uniqid(),
            'is_active' => true,
            'is_main' => true,
        ]);

        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'current_center_id' => $center->id,
        ]);
        $user->centers()->attach($center->id, ['tenant_id' => $tenant->id]);

        return [$tenant, $center, $user];
    }

    public function test_company_profile_updates_country_currency_timezone_and_shares_inertia_meta(): void
    {
        [$tenant, , $user] = $this->createTenantUser('SA', 'SAR');

        $this->actingAs($user)
            ->put(route('settings.company.update'), [
                'section' => 'profile',
                'data' => [
                    'legal_name' => 'مركز دبي للصيانة',
                    'legal_name_en' => 'Dubai Auto Care',
                    'trade_name' => 'دبي أوتو',
                    'owner_name' => 'أحمد',
                    'country' => 'AE',
                    'currency' => 'AED',
                    'timezone' => 'Asia/Dubai',
                ],
            ])
            ->assertRedirect();

        $tenant->refresh();
        $tenant->load('taxSettings');

        $this->assertSame('AE', $tenant->country);
        $this->assertSame('AED', $tenant->currency);
        $this->assertSame('Asia/Dubai', $tenant->timezone);
        $this->assertSame('AED', $tenant->taxSettings?->currency_code);

        $this->actingAs($user)
            ->get(route('settings.company'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('tenant.country', 'AE')
                ->where('tenant.currency', 'AED')
                ->where('tenant.currency_meta.code', 'AED')
                ->where('tenant.currency_meta.decimals', 2)
                ->where('tenant.country_meta.dial_code', '+971')
                ->where('tenant.country_meta.zatca_applicable', false)
            );
    }

    public function test_non_saudi_tenant_can_configure_custom_vat_rate_and_flexible_tax_number_while_saudi_stays_locked(): void
    {
        [$uaeTenant, , $uaeUser] = $this->createTenantUser('AE', 'AED');

        $this->actingAs($uaeUser)
            ->put(route('settings.company.update'), [
                'section' => 'vat',
                'data' => [
                    'vat_enabled' => true,
                    'services_vat_rate' => 5,
                    'parts_vat_rate' => 5,
                    'vat_number' => '100234567890123',
                ],
            ])
            ->assertRedirect(route('login'));

        $uaeTenant->refresh();
        $this->assertSame('100234567890123', $uaeTenant->vat_number);
        $this->assertEquals(5.0, (float) $uaeTenant->taxSettings->services_vat_rate);

        // Saudi tenant cannot bypass 3...3 rule and rate remains locked at 15%
        [$saTenant, , $saUser] = $this->createTenantUser('SA', 'SAR');

        $this->actingAs($saUser)
            ->put(route('settings.company.update'), [
                'section' => 'vat',
                'data' => [
                    'vat_enabled' => true,
                    'services_vat_rate' => 5,
                    'parts_vat_rate' => 5,
                    'vat_number' => '100234567890123',
                ],
            ])
            ->assertSessionHasErrors('vat_number');

        $this->assertSame('300000000000003', $saTenant->fresh()->vat_number);
    }

    public function test_zatca_is_blocked_outside_saudi_arabia_and_non_saudi_invoices_never_generate_zatca_qr(): void
    {
        [$kwTenant, $kwCenter, $kwUser] = $this->createTenantUser('KW', 'KWD');
        $kwTenant->taxSettings()->update([
            'vat_enabled' => true,
            'vat_rate' => 0,
            'services_vat_rate' => 0,
            'parts_vat_rate' => 0,
            'tax_number' => 'KW-TAX-12345',
        ]);
        $kwTenant->refresh()->load('taxSettings');

        $onboarding = app(ZatcaOnboardingService::class);
        $errors = $onboarding->validatePrerequisites($kwTenant);
        $this->assertNotEmpty($errors);

        $customer = Customer::create([
            'tenant_id' => $kwTenant->id,
            'center_id' => $kwCenter->id,
            'name' => 'عميل الكويت',
            'phone' => '+96550123456',
        ]);

        $invoice = Invoice::create([
            'tenant_id' => $kwTenant->id,
            'center_id' => $kwCenter->id,
            'customer_id' => $customer->id,
            'invoice_number' => 'DRAFT-KW-1',
            'issue_date' => now(),
            'supply_date' => now(),
            'status' => 'draft',
            'payment_status' => 'unpaid',
            'tax_enabled_snapshot' => true,
            'tax_rate_snapshot' => 0,
            'total_excl_tax' => 100.000,
            'total_tax' => 0.000,
            'total_incl_tax' => 100.000,
        ]);

        $line = $invoice->lines()->create([
            'is_part' => false,
            'description' => 'خدمة صيانة عامة',
            'qty' => 1,
            'unit_price' => 100.000,
            'discount_amount' => 0,
            'is_taxable' => true,
            'tax_category_code' => 'S',
            'tax_rate_snapshot' => 0,
            'tax_amount' => 0,
            'line_total_excl_tax' => 100.000,
            'line_total_incl_tax' => 100.000,
        ]);

        $issued = app(InvoiceService::class)->issueInvoice($invoice);

        $this->assertSame('KWD', $issued->currency_code);
        $this->assertNull($issued->zatca_qr_tlv);

        $creditNote = app(CreditNoteService::class)->createCreditNoteForInvoice($issued, [
            'return_reason' => 'إلغاء فاتورة تجربة',
            'services' => [
                ['id' => $line->id, 'return_amount' => 100],
            ],
        ], $kwUser);

        $this->assertSame('KWD', $creditNote->currency_code);
        $this->assertEquals(0.0, (float) $creditNote->total_tax);
        $this->assertNull($creditNote->zatca_qr_tlv);
    }

    public function test_print_settings_only_stores_currency_display_options_and_never_allows_currency_code_override(): void
    {
        [$tenant, , $user] = $this->createTenantUser('AE', 'AED');

        $this->actingAs($user)
            ->put(route('settings.system.update'), [
                'section' => 'print',
                'documents' => [
                    'invoice' => [
                        'title_ar' => 'فاتورة ضريبية',
                        'title_en' => 'Tax Invoice',
                    ],
                ],
                'visual' => [
                    'active_template' => 'TemplateDefaultA4',
                    'show_logo' => true,
                    'primary_color' => '#fbbf24',
                    'show_currency_symbol' => true,
                    'currency_display_style' => 'symbol',
                    'currency_code' => 'USD', // Tampering attempt must be stripped
                ],
            ])
            ->assertRedirect();

        $visual = $tenant->fresh()->print_settings['visual'] ?? [];

        $this->assertTrue((bool) ($visual['show_currency_symbol'] ?? false));
        $this->assertSame('symbol', $visual['currency_display_style'] ?? null);
        $this->assertArrayNotHasKey('currency_code', $visual);
        $this->assertSame('AED', CountryCurrencyRegistry::tenantCurrency($tenant->fresh()));
    }

    public function test_customer_validation_supports_non_saudi_phones_and_flexible_tax_numbers(): void
    {
        [, , $aeUser] = $this->createTenantUser('AE', 'AED');

        $this->actingAs($aeUser);

        $request = CustomerStoreRequest::create('/app/customers', 'POST', [
            'type' => 'company',
            'name' => 'شركة الإمارات للسيارات',
            'contact_name' => 'خالد',
            'phone' => '0501234567',
            'whatsapp' => '+447911123456',
            'tax_number' => 'AE-TRN-9988',
        ]);
        $request->setUserResolver(fn () => $aeUser);

        $method = new ReflectionMethod($request, 'prepareForValidation');
        $method->setAccessible(true);
        $method->invoke($request);

        $this->assertSame('+971501234567', $request->input('phone'));
        $this->assertSame('+447911123456', $request->input('whatsapp'));

        $validator = Validator::make($request->all(), $request->rules(), $request->messages());
        $this->assertFalse($validator->fails(), json_encode($validator->errors()->all()));
    }

    public function test_platform_billing_currency_remains_decoupled_from_tenant_currency(): void
    {
        config(['app.default_currency' => 'SAR']);
        [$tenant] = $this->createTenantUser('KW', 'KWD');

        $this->assertSame('SAR', PaymentSettings::getPlatformCurrency());
        $this->assertSame('KWD', CountryCurrencyRegistry::tenantCurrency($tenant));
    }
}
