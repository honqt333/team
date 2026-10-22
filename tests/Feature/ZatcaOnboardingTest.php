<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\TenantZatcaSetting;
use App\Models\User;
use Database\Seeders\PerformanceBaselineSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ZatcaOnboardingTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->get('/app/settings/zatca');
        $response->assertRedirect('/login');
    }

    public function test_authenticated_user_can_view_onboarding_wizard(): void
    {
        app(PerformanceBaselineSeeder::class)->run();

        $tenant = Tenant::first();
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
        $user = User::where('tenant_id', $tenant->id)->where('is_system_admin', true)->first();

        // Setup compliance state
        $this->actingAs($user)->post('/app/settings/zatca/csr', ['environment' => 'simulation']);
        $this->actingAs($user)->post('/app/settings/zatca/compliance', ['otp' => '123456']);

        $response = $this->actingAs($user)->postJson('/app/settings/zatca/check');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'status',
            'message',
            'checks' => [
                '*' => ['name', 'status', 'code'],
            ],
        ]);
        $response->assertJson([
            'status' => 'success',
        ]);
    }

    public function test_step_4_request_production_csid_and_activate(): void
    {
        app(PerformanceBaselineSeeder::class)->run();

        $tenant = Tenant::first();
        $user = User::where('tenant_id', $tenant->id)->where('is_system_admin', true)->first();

        // Setup previous steps
        $this->actingAs($user)->post('/app/settings/zatca/csr', ['environment' => 'simulation']);
        $this->actingAs($user)->post('/app/settings/zatca/compliance', ['otp' => '123456']);

        $response = $this->actingAs($user)->post('/app/settings/zatca/production');

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        $setting = TenantZatcaSetting::where('tenant_id', $tenant->id)->first();
        $this->assertEquals(TenantZatcaSetting::STATUS_ACTIVE, $setting->onboarding_status);
        $this->assertNotEmpty($setting->production_csid);
        $this->assertTrue($setting->isPhase2Active());
    }

    public function test_reset_onboarding_clears_data(): void
    {
        app(PerformanceBaselineSeeder::class)->run();

        $tenant = Tenant::first();
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
}
