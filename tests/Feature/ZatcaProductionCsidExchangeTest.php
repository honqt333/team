<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\TenantZatcaSetting;
use App\Models\User;
use Database\Seeders\PerformanceBaselineSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * The production CSID exchange is the one step that decides whether a
 * tenant is really integrated with ZATCA. It used to fabricate the
 * value locally (`PROD_CSID_` + `Str::random()`) and switch the tenant
 * into live Phase 2 mode without ever talking to the gateway.
 *
 * These tests pin the real exchange: the request that goes out, the
 * credentials that come back, and — just as important — what happens
 * when the gateway refuses.
 */
class ZatcaProductionCsidExchangeTest extends TestCase
{
    use RefreshDatabase;

    private const PRODUCTION_URL = 'https://gw-fatoora.zatca.gov.sa/e-invoicing/core/production/csids';

    protected function seedTenantWithRealComplianceCsid(): array
    {
        app(PerformanceBaselineSeeder::class)->run();

        $tenant = Tenant::first();
        $tenant->taxSettings()->updateOrCreate(
            ['tenant_id' => $tenant->id],
            [
                'vat_enabled' => true,
                'tax_number' => '300000000000003',
                'services_vat_rate' => 15,
                'parts_vat_rate' => 15,
            ]
        );

        $setting = TenantZatcaSetting::firstOrCreate(['tenant_id' => $tenant->id]);
        $setting->fill([
            'environment' => 'production',
            'compliance_csid' => 'REAL-COMPLIANCE-CSID-0001',
            'compliance_secret' => 'REAL-COMPLIANCE-SECRET',
            'onboarding_status' => TenantZatcaSetting::STATUS_COMPLIANCE_PASSED,
        ]);
        $setting->save();

        $user = User::where('tenant_id', $tenant->id)->where('is_system_admin', true)->first();

        return [$tenant, $user, $setting];
    }

    public function test_a_successful_gateway_exchange_activates_phase_two_with_the_issued_certificate(): void
    {
        [$tenant, $user] = $this->seedTenantWithRealComplianceCsid();

        Http::fake([
            self::PRODUCTION_URL => Http::response([
                'binarySecurityToken' => 'ISSUED-PRODUCTION-TOKEN-9999',
                'secret' => 'ISSUED-PRODUCTION-SECRET-9999',
                'expirationDate' => '2027-10-28T00:00:00Z',
            ], 200),
        ]);

        $response = $this->actingAs($user)->post('/app/settings/zatca/production');

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        $setting = TenantZatcaSetting::where('tenant_id', $tenant->id)->first();

        // The certificate that ZATCA issued — not one we made up.
        $this->assertSame('ISSUED-PRODUCTION-TOKEN-9999', $setting->production_csid);
        $this->assertSame('ISSUED-PRODUCTION-SECRET-9999', $setting->production_secret);
        $this->assertSame(TenantZatcaSetting::STATUS_ACTIVE, $setting->onboarding_status);
        $this->assertTrue($setting->isPhase2Active());
        $this->assertTrue($setting->qr_enabled);
        $this->assertNotNull($setting->production_csid_expires_at);
    }

    public function test_the_exchange_request_carries_the_compliance_csid_and_the_expected_headers(): void
    {
        [$tenant, $user] = $this->seedTenantWithRealComplianceCsid();

        Http::fake([
            self::PRODUCTION_URL => Http::response([
                'binarySecurityToken' => 'ISSUED-PRODUCTION-TOKEN-9999',
                'secret' => 'ISSUED-PRODUCTION-SECRET-9999',
            ], 200),
        ]);

        $this->actingAs($user)->post('/app/settings/zatca/production');

        Http::assertSent(function ($request): bool {
            return $request->url() === self::PRODUCTION_URL
                && $request->method() === 'POST'
                && $request['complianceCsid'] === 'REAL-COMPLIANCE-CSID-0001'
                && $request->hasHeader('Accept-Version', 'V2')
                && $request->hasHeader('Content-Type', 'application/json');
        });
    }

    public function test_a_rejected_exchange_leaves_the_tenant_inactive_and_records_the_error(): void
    {
        [$tenant, $user] = $this->seedTenantWithRealComplianceCsid();

        Http::fake([
            self::PRODUCTION_URL => Http::response([
                'errors' => [
                    ['message' => 'الرقم الضريبي غير مسجل لدى الهيئة'],
                ],
            ], 400),
        ]);

        $response = $this->actingAs($user)->post('/app/settings/zatca/production');

        $response->assertSessionHasErrors('error');

        $setting = TenantZatcaSetting::where('tenant_id', $tenant->id)->first();

        $this->assertNull($setting->production_csid);
        $this->assertNull($setting->production_secret);
        $this->assertNotSame(TenantZatcaSetting::STATUS_ACTIVE, $setting->onboarding_status);
        $this->assertFalse($setting->isPhase2Active());
        $this->assertFalse($setting->qr_enabled);
        $this->assertStringContainsString(
            'الرقم الضريبي غير مسجل لدى الهيئة',
            (string) $setting->last_onboarding_error
        );
    }

    public function test_a_response_without_a_token_is_treated_as_a_failure(): void
    {
        [$tenant, $user] = $this->seedTenantWithRealComplianceCsid();

        Http::fake([
            self::PRODUCTION_URL => Http::response([
                'validationResult' => ['errorMessages' => ['no token here']],
            ], 200),
        ]);

        $response = $this->actingAs($user)->post('/app/settings/zatca/production');

        $response->assertSessionHasErrors('error');

        $setting = TenantZatcaSetting::where('tenant_id', $tenant->id)->first();

        $this->assertNull($setting->production_csid);
        $this->assertFalse($setting->isPhase2Active());
    }

    public function test_an_unreachable_gateway_does_not_activate_anything(): void
    {
        [$tenant, $user] = $this->seedTenantWithRealComplianceCsid();

        Http::fake(fn () => throw new ConnectionException('network down'));

        $response = $this->actingAs($user)->post('/app/settings/zatca/production');

        $response->assertSessionHasErrors('error');

        $setting = TenantZatcaSetting::where('tenant_id', $tenant->id)->first();

        $this->assertNull($setting->production_csid);
        $this->assertFalse($setting->isPhase2Active());
        $this->assertStringContainsString(
            'تعذر الاتصال ببوابة هيئة الزكاة',
            (string) $setting->last_onboarding_error
        );
    }

    public function test_a_simulated_compliance_csid_cannot_be_exchanged_for_a_production_one(): void
    {
        app(PerformanceBaselineSeeder::class)->run();

        $tenant = Tenant::first();
        $tenant->taxSettings()->updateOrCreate(
            ['tenant_id' => $tenant->id],
            [
                'vat_enabled' => true,
                'tax_number' => '300000000000003',
                'services_vat_rate' => 15,
                'parts_vat_rate' => 15,
            ]
        );

        $setting = TenantZatcaSetting::firstOrCreate(['tenant_id' => $tenant->id]);
        $setting->fill([
            'environment' => 'production',
            'compliance_csid' => 'CSID_SIM_FAKEVALUE',
            'compliance_secret' => 'SECRET_SIM_FAKEVALUE',
            'onboarding_status' => TenantZatcaSetting::STATUS_COMPLIANCE_PASSED,
        ]);
        $setting->save();

        $user = User::where('tenant_id', $tenant->id)->where('is_system_admin', true)->first();

        Http::fake();

        $response = $this->actingAs($user)->post('/app/settings/zatca/production');

        $response->assertSessionHasErrors('error');

        $setting->refresh();

        $this->assertNull($setting->production_csid);
        $this->assertFalse($setting->isPhase2Active());

        // No request may even be attempted with a simulated credential.
        Http::assertNothingSent();
    }
}
