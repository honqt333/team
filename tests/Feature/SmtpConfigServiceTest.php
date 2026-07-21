<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Integration\Integration;
use App\Services\Email\SmtpConfigService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Regression tests for SmtpConfigService.
 *
 * The service is the single source of truth for SMTP credentials: both
 * development and production read from the `integrations` table instead
 * of `.env`. These tests pin that contract.
 */
class SmtpConfigServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_apply_with_no_integration_leaves_default_mailer_intact(): void
    {
        config(['mail.default' => 'log']);

        app(SmtpConfigService::class)->apply();

        $this->assertEquals('log', config('mail.default'));
    }

    public function test_apply_with_default_smtp_integration_overrides_mailer(): void
    {
        Integration::create([
            'type' => 'email',
            'provider' => 'smtp',
            'name' => 'Test SMTP',
            'name_ar' => 'SMTP اختبار',
            'config' => [
                'host' => 'smtp.example.com',
                'port' => 587,
                'username' => 'user@example.com',
                'password' => 'secret',
                'encryption' => 'tls',
                'from_address' => 'noreply@example.com',
                'from_name' => 'Carag',
            ],
            'is_active' => true,
            'is_default' => true,
        ]);

        app(SmtpConfigService::class)->apply();

        $this->assertEquals('smtp', config('mail.default'));
        $this->assertEquals('smtp.example.com', config('mail.mailers.smtp.host'));
        $this->assertEquals(587, config('mail.mailers.smtp.port'));
        $this->assertEquals('user@example.com', config('mail.mailers.smtp.username'));
        $this->assertEquals('secret', config('mail.mailers.smtp.password'));
        $this->assertEquals('tls', config('mail.mailers.smtp.encryption'));
        $this->assertEquals('noreply@example.com', config('mail.from.address'));
        $this->assertEquals('Carag', config('mail.from.name'));
    }

    public function test_apply_ignores_non_default_integrations(): void
    {
        Integration::create([
            'type' => 'email',
            'provider' => 'smtp',
            'name' => 'Backup SMTP',
            'name_ar' => 'SMTP احتياطي',
            'config' => [
                'host' => 'backup.example.com',
                'port' => 587,
                'username' => 'b',
                'password' => 'b',
            ],
            'is_active' => true,
            'is_default' => false,
        ]);

        config(['mail.default' => 'log']);

        app(SmtpConfigService::class)->apply();

        // Should NOT pick the non-default one
        $this->assertEquals('log', config('mail.default'));
    }

    public function test_apply_ignores_inactive_default(): void
    {
        Integration::create([
            'type' => 'email',
            'provider' => 'smtp',
            'name' => 'Disabled SMTP',
            'name_ar' => 'SMTP معطل',
            'config' => [
                'host' => 'x',
                'port' => 587,
                'username' => 'x',
                'password' => 'x',
            ],
            'is_active' => false,
            'is_default' => true,
        ]);

        config(['mail.default' => 'log']);

        app(SmtpConfigService::class)->apply();

        $this->assertEquals('log', config('mail.default'));
    }

    public function test_is_configured_returns_true_when_default_smtp_present(): void
    {
        Integration::create([
            'type' => 'email',
            'provider' => 'smtp',
            'name' => 'Test',
            'name_ar' => 'اختبار',
            'config' => [
                'host' => 'smtp.example.com',
                'username' => 'user',
            ],
            'is_active' => true,
            'is_default' => true,
        ]);

        $this->assertTrue(app(SmtpConfigService::class)->isConfigured());
    }

    public function test_is_configured_returns_false_without_integration(): void
    {
        $this->assertFalse(app(SmtpConfigService::class)->isConfigured());
    }

    public function test_apply_is_singleton_memoized(): void
    {
        Integration::create([
            'type' => 'email',
            'provider' => 'smtp',
            'name' => 'Test',
            'name_ar' => 'اختبار',
            'config' => [
                'host' => 'smtp.example.com',
                'port' => 587,
                'username' => 'user',
                'password' => 'pw',
            ],
            'is_active' => true,
            'is_default' => true,
        ]);

        $service = app(SmtpConfigService::class);
        $same = app(SmtpConfigService::class);

        $this->assertSame($service, $same);
    }
}
