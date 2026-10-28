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

class ZatcaOnboardingTest extends TestCase
{
    use RefreshDatabase;

    protected function enableTaxForTenant(Tenant $tenant): void
    {
        $tenant->taxSettings()->updateOrCreate(
            ['tenant_id' => $tenant->id],
            [
                'vat_enabled' => true,
                'tax_number' => '300000000000003',
                'services_vat_rate' => 15,
                'parts_vat_rate' => 15,
            ]
        );
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->get('/app/settings/zatca');
        $response->assertRedirect('/login');
    }

    public function test_user_is_redirected_to_company_settings_if_vat_not_enabled(): void
    {
        app(PerformanceBaselineSeeder::class)->run();

        $tenant = Tenant::first();
        // Ensure tax is not enabled
        $tenant->taxSettings()->updateOrCreate(
            ['tenant_id' => $tenant->id],
            ['vat_enabled' => false, 'tax_number' => '']
        );

        $user = User::where('tenant_id', $tenant->id)->where('is_system_admin', true)->first();

        $response = $this->actingAs($user)->get('/app/settings/zatca');

        $response->assertRedirect(route('settings.company', ['tab' => 'vat']));
        $response->assertSessionHas('error');
    }

    public function test_user_is_redirected_if_vat_number_invalid(): void
    {
        app(PerformanceBaselineSeeder::class)->run();

        $tenant = Tenant::first();
        // Tax enabled but invalid VAT number
        $tenant->taxSettings()->updateOrCreate(
            ['tenant_id' => $tenant->id],
            ['vat_enabled' => true, 'tax_number' => '123456']
        );

        $user = User::where('tenant_id', $tenant->id)->where('is_system_admin', true)->first();

        $response = $this->actingAs($user)->get('/app/settings/zatca');

        $response->assertRedirect(route('settings.company', ['tab' => 'vat']));
        $response->assertSessionHas('error');
    }

    public function test_authenticated_user_can_view_onboarding_wizard_when_tax_enabled(): void
    {
        app(PerformanceBaselineSeeder::class)->run();

        $tenant = Tenant::first();
        $this->enableTaxForTenant($tenant);
        $user = User::where('tenant_id', $tenant->id)->where('is_system_admin', true)->first();

        $response = $this->actingAs($user)->get('/app/settings/zatca');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Settings/Zatca/Index')
            ->has('tenant')
            ->has('setting')
        );
    }

    public function test_step_1_generate_csr(): void
    {
        app(PerformanceBaselineSeeder::class)->run();

        $tenant = Tenant::first();
        $this->enableTaxForTenant($tenant);
        $user = User::where('tenant_id', $tenant->id)->where('is_system_admin', true)->first();

        $response = $this->actingAs($user)->post('/app/settings/zatca/csr', [
            'environment' => 'simulation',
            'egs_serial_number' => '1-CARAG|100|TEST-001',
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        $setting = TenantZatcaSetting::where('tenant_id', $tenant->id)->first();
        $this->assertNotNull($setting);
        $this->assertNotEmpty($setting->csr_content);
        $this->assertNotEmpty($setting->private_key);
        $this->assertEquals(TenantZatcaSetting::STATUS_CSR_READY, $setting->onboarding_status);
        $this->assertEquals('simulation', $setting->environment);
    }

    public function test_step_2_request_compliance_csid(): void
    {
        app(PerformanceBaselineSeeder::class)->run();

        $tenant = Tenant::first();
        $this->enableTaxForTenant($tenant);
        $user = User::where('tenant_id', $tenant->id)->where('is_system_admin', true)->first();

        // Step 1 first
        $this->actingAs($user)->post('/app/settings/zatca/csr', [
            'environment' => 'simulation',
        ]);

        // Step 2 OTP
        $response = $this->actingAs($user)->post('/app/settings/zatca/compliance', [
            'otp' => '123456',
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        $setting = TenantZatcaSetting::where('tenant_id', $tenant->id)->first();
        $this->assertEquals(TenantZatcaSetting::STATUS_COMPLIANCE_PASSED, $setting->onboarding_status);
        $this->assertNotEmpty($setting->compliance_csid);
        $this->assertNotEmpty($setting->compliance_secret);
    }

    public function test_step_2_handles_zatca_remote_error(): void
    {
        app(PerformanceBaselineSeeder::class)->run();

        $tenant = Tenant::first();
        $this->enableTaxForTenant($tenant);
        $user = User::where('tenant_id', $tenant->id)->where('is_system_admin', true)->first();

        // Step 1 CSR
        $this->actingAs($user)->post('/app/settings/zatca/csr', ['environment' => 'production']);

        // Fake ZATCA 400 rejection
        Http::fake([
            '*/compliance' => Http::response([
                'errors' => [
                    ['message' => 'رمز التحقق غير صالح أو منتهي الصلاحية'],
                ],
            ], 400),
        ]);

        $response = $this->actingAs($user)->post('/app/settings/zatca/compliance', [
            'otp' => '999888',
        ]);

        $response->assertSessionHasErrors('otp');
    }

    public function test_step_3_run_compliance_checks(): void
    {
        app(PerformanceBaselineSeeder::class)->run();

        $tenant = Tenant::first();
        $this->enableTaxForTenant($tenant);
        $user = User::where('tenant_id', $tenant->id)->where('is_system_admin', true)->first();

        // Setup compliance state
        $this->actingAs($user)->post('/app/settings/zatca/csr', ['environment' => 'simulation']);
        $this->actingAs($user)->post('/app/settings/zatca/compliance', ['otp' => '123456']);

        // The OTP 123456 path stores a locally fabricated CSID. The
        // credential check has to notice that, so use a real-looking
        // one here to exercise the "everything executable passed"
        // branch.
        $setting = TenantZatcaSetting::where('tenant_id', $tenant->id)->first();
        $setting->fill(['compliance_csid' => 'REAL-COMPLIANCE-CSID-0001'])->save();

        Http::fake(['*' => Http::response([], 200)]);

        $response = $this->actingAs($user)->postJson('/app/settings/zatca/check');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'status',
            'message',
            'all_passed',
            'checks' => [
                '*' => ['name', 'status', 'code', 'executed', 'detail'],
            ],
        ]);

        // The four invoice checks cannot be executed by this build, so
        // the step must NOT report a blanket success.
        $response->assertJsonPath('status', 'incomplete');
        $response->assertJsonPath('all_passed', false);

        $checks = $response->json('checks');
        $this->assertCount(6, $checks);

        $documentCodes = ['ZATCA-SIMP-01', 'ZATCA-CR-02', 'ZATCA-STD-03', 'ZATCA-DB-04'];

        $documentChecks = array_values(array_filter(
            $checks,
            fn (array $check): bool => in_array($check['code'], $documentCodes, true)
        ));

        $this->assertCount(4, $documentChecks);

        foreach ($documentChecks as $check) {
            $this->assertSame('NOT_EXECUTED', $check['status']);
            $this->assertFalse($check['executed']);
        }

        // Nothing may claim PASSED for a check that never ran.
        foreach ($checks as $check) {
            if ($check['status'] === 'PASSED') {
                $this->assertTrue($check['executed']);
            }
        }
    }

    public function test_step_3_fails_when_the_compliance_credential_is_only_a_local_simulation(): void
    {
        app(PerformanceBaselineSeeder::class)->run();

        $tenant = Tenant::first();
        $this->enableTaxForTenant($tenant);
        $user = User::where('tenant_id', $tenant->id)->where('is_system_admin', true)->first();

        $this->actingAs($user)->post('/app/settings/zatca/csr', ['environment' => 'simulation']);
        $this->actingAs($user)->post('/app/settings/zatca/compliance', ['otp' => '123456']);

        Http::fake(['*' => Http::response([], 200)]);

        $response = $this->actingAs($user)->postJson('/app/settings/zatca/check');

        // The OTP 123456 path stores a fabricated CSID, so the
        // credential check must fail rather than pass.
        $response->assertStatus(200);
        $response->assertJsonPath('status', 'failed');
        $response->assertJsonPath('all_passed', false);

        $credentialCheck = collect($response->json('checks'))
            ->firstWhere('code', 'ZATCA-CRED-00');

        $this->assertSame('FAILED', $credentialCheck['status']);
        $this->assertStringContainsString('محاكاة محلية', $credentialCheck['detail']);
    }

    public function test_step_3_reports_a_failed_check_when_the_gateway_is_unreachable(): void
    {
        app(PerformanceBaselineSeeder::class)->run();

        $tenant = Tenant::first();
        $this->enableTaxForTenant($tenant);
        $user = User::where('tenant_id', $tenant->id)->where('is_system_admin', true)->first();

        $this->actingAs($user)->post('/app/settings/zatca/csr', ['environment' => 'simulation']);
        $this->actingAs($user)->post('/app/settings/zatca/compliance', ['otp' => '123456']);

        Http::fake(fn () => throw new ConnectionException('network down'));

        $response = $this->actingAs($user)->postJson('/app/settings/zatca/check');

        $response->assertStatus(200);
        $response->assertJsonPath('status', 'failed');
        $response->assertJsonPath('all_passed', false);
    }

    public function test_generated_key_and_csr_are_real_openssl_material(): void
    {
        app(PerformanceBaselineSeeder::class)->run();

        $tenant = Tenant::first();
        $this->enableTaxForTenant($tenant);
        $user = User::where('tenant_id', $tenant->id)->where('is_system_admin', true)->first();

        $this->actingAs($user)->post('/app/settings/zatca/csr', ['environment' => 'simulation']);

        $setting = TenantZatcaSetting::where('tenant_id', $tenant->id)->first();

        // The old implementation always fell through to a fabricated
        // PEM (a random blob, and a "CSR" that was really base64 of a
        // VAT number). ZATCA rejects both.
        $this->assertStringContainsString('PRIVATE KEY', (string) $setting->private_key);
        $this->assertNotFalse(
            @openssl_pkey_get_private((string) $setting->private_key),
            'The stored private key is not a readable EC key.'
        );

        $this->assertStringContainsString('CERTIFICATE REQUEST', (string) $setting->csr_content);

        // A real certificate request is a DER SEQUENCE. The fabricated
        // one this service used to store was base64 of a plain string
        // ("ZATCA:<vat>:<serial>"), which decodes to no structure at all.
        $csrBody = (string) preg_replace('/-----[^-]+-----|\s+/', '', (string) $setting->csr_content);
        $der = base64_decode($csrBody, true);

        $this->assertNotFalse($der);
        $this->assertNotSame('', $der);
        $this->assertSame(
            0x30,
            ord((string) $der[0]),
            'The stored CSR is not a DER-encoded certificate request.'
        );
    }

    public function test_the_wizard_does_not_fabricate_a_passed_result_in_the_browser(): void
    {
        $source = (string) file_get_contents(resource_path('js/Pages/Settings/Zatca/Index.vue'));

        // The previous implementation marked every check as passed from
        // a setTimeout, without ever calling the server.
        $this->assertStringNotContainsString('c.passed = true', $source);
        $this->assertStringNotContainsString('passed: props.setting.has_compliance_csid', $source);
        $this->assertStringContainsString('/app/settings/zatca/check', $source);
    }

    public function test_step_4_outside_production_produces_a_labelled_simulation_not_a_live_link(): void
    {
        app(PerformanceBaselineSeeder::class)->run();

        $tenant = Tenant::first();
        $this->enableTaxForTenant($tenant);
        $user = User::where('tenant_id', $tenant->id)->where('is_system_admin', true)->first();

        // Setup previous steps
        $this->actingAs($user)->post('/app/settings/zatca/csr', ['environment' => 'simulation']);
        $this->actingAs($user)->post('/app/settings/zatca/compliance', ['otp' => '123456']);

        $response = $this->actingAs($user)->post('/app/settings/zatca/production');

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        $setting = TenantZatcaSetting::where('tenant_id', $tenant->id)->first();

        // A simulation must never look like a live production link.
        $this->assertEquals(TenantZatcaSetting::STATUS_SIMULATED, $setting->onboarding_status);
        $this->assertStringStartsWith(
            'SIM_PROD_CSID_',
            (string) $setting->production_csid
        );
        $this->assertFalse($setting->isPhase2Active());
        $this->assertTrue($setting->isSimulated());
        $this->assertFalse($setting->qr_enabled);
        $this->assertNull($setting->production_csid_expires_at);
    }

    public function test_reset_onboarding_clears_data(): void
    {
        app(PerformanceBaselineSeeder::class)->run();

        $tenant = Tenant::first();
        $this->enableTaxForTenant($tenant);
        $user = User::where('tenant_id', $tenant->id)->where('is_system_admin', true)->first();

        // Complete onboarding
        $this->actingAs($user)->post('/app/settings/zatca/csr', ['environment' => 'simulation']);
        $this->actingAs($user)->post('/app/settings/zatca/compliance', ['otp' => '123456']);
        $this->actingAs($user)->post('/app/settings/zatca/production');

        // Reset
        $response = $this->actingAs($user)->post('/app/settings/zatca/reset');

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        $setting = TenantZatcaSetting::where('tenant_id', $tenant->id)->first();
        $this->assertEquals(TenantZatcaSetting::STATUS_NOT_STARTED, $setting->onboarding_status);
        $this->assertNull($setting->csr_content);
        $this->assertNull($setting->private_key);
        $this->assertNull($setting->compliance_csid);
        $this->assertNull($setting->production_csid);
        $this->assertFalse($setting->isPhase2Active());
    }

    public function test_step_2_rejects_mock_otp_123456_in_production_environment(): void
    {
        app(PerformanceBaselineSeeder::class)->run();

        $tenant = Tenant::first();
        $this->enableTaxForTenant($tenant);
        $user = User::where('tenant_id', $tenant->id)->where('is_system_admin', true)->first();

        // Step 1: CSR in production mode
        $this->actingAs($user)->post('/app/settings/zatca/csr', [
            'environment' => 'production',
        ]);

        // Step 2: Try using mock OTP 123456 in production mode
        $response = $this->actingAs($user)->post('/app/settings/zatca/compliance', [
            'otp' => '123456',
        ]);

        $response->assertSessionHasErrors('otp');

        $setting = TenantZatcaSetting::where('tenant_id', $tenant->id)->first();
        $setting->refresh();
        $this->assertNull($setting->compliance_csid);
        $this->assertEquals(TenantZatcaSetting::STATUS_CSR_READY, $setting->onboarding_status);
        $this->assertStringContainsString('123456', (string) $setting->last_onboarding_error);
    }

    public function test_step_2_rejects_arabic_mock_otp_in_production_environment(): void
    {
        app(PerformanceBaselineSeeder::class)->run();

        $tenant = Tenant::first();
        $this->enableTaxForTenant($tenant);
        $user = User::where('tenant_id', $tenant->id)->where('is_system_admin', true)->first();

        // Step 1: CSR in production mode
        $this->actingAs($user)->post('/app/settings/zatca/csr', [
            'environment' => 'production',
        ]);

        // Step 2: Try using Arabic mock OTP ١٢٣٤٥٦ in production mode
        $response = $this->actingAs($user)->post('/app/settings/zatca/compliance', [
            'otp' => '١٢٣٤٥٦',
        ]);

        $response->assertSessionHasErrors('otp');

        $setting = TenantZatcaSetting::where('tenant_id', $tenant->id)->first();
        $this->assertNull($setting->compliance_csid);
        $this->assertEquals(TenantZatcaSetting::STATUS_CSR_READY, $setting->onboarding_status);
    }

    public function test_step_4_rejects_simulated_csid_in_production_environment(): void
    {
        app(PerformanceBaselineSeeder::class)->run();

        $tenant = Tenant::first();
        $this->enableTaxForTenant($tenant);
        $user = User::where('tenant_id', $tenant->id)->where('is_system_admin', true)->first();

        // Manually place a simulated compliance CSID while setting environment is production
        $setting = TenantZatcaSetting::firstOrCreate(['tenant_id' => $tenant->id]);
        $setting->fill([
            'environment' => 'production',
            'compliance_csid' => 'CSID_SIM_TEST_DUMMY',
            'compliance_secret' => 'SECRET_SIM_DUMMY',
            'onboarding_status' => TenantZatcaSetting::STATUS_COMPLIANCE_PASSED,
        ]);
        $setting->save();

        $response = $this->actingAs($user)->post('/app/settings/zatca/production');

        $response->assertSessionHasErrors('error');
        $setting->refresh();
        $this->assertNull($setting->production_csid);
        $this->assertFalse($setting->isPhase2Active());
    }
}
