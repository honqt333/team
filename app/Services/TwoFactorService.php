<?php

namespace App\Services;

use Illuminate\Support\Str;
use PragmaRX\Google2FA\Google2FA;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;

class TwoFactorService
{
    protected Google2FA $google2fa;

    public function __construct()
    {
        $this->google2fa = new Google2FA();
    }

    /**
     * Generate a new 2FA secret.
     */
    public function generateSecret(): string
    {
        return $this->google2fa->generateSecretKey();
    }

    /**
     * Generate QR code as SVG for authenticator app.
     */
    public function generateQrCode(string $email, string $secret, string $appName = 'Khidma Pro'): string
    {
        $otpAuthUrl = $this->google2fa->getQRCodeUrl(
            $appName,
            $email,
            $secret
        );

        $renderer = new ImageRenderer(
            new RendererStyle(200),
            new SvgImageBackEnd()
        );
        
        $writer = new Writer($renderer);
        return $writer->writeString($otpAuthUrl);
    }

    /**
     * Verify 2FA code.
     */
    public function verify(string $secret, string $code): bool
    {
        return $this->google2fa->verifyKey($secret, $code);
    }

    /**
     * Generate recovery codes.
     */
    public function generateRecoveryCodes(int $count = 8): array
    {
        $codes = [];
        for ($i = 0; $i < $count; $i++) {
            $codes[] = Str::upper(Str::random(4) . '-' . Str::random(4));
        }
        return $codes;
    }

    /**
     * Verify recovery code.
     */
    public function verifyRecoveryCode(string $usedCode, array $codes): bool
    {
        return in_array(Str::upper($usedCode), $codes);
    }

    /**
     * Remove used recovery code.
     */
    public function removeRecoveryCode(string $usedCode, array $codes): array
    {
        return array_values(array_filter($codes, fn($code) => $code !== Str::upper($usedCode)));
    }


    /**
     * Send 2FA code via email or SMS based on preference.
     */
    /**
     * Send 2FA code via email or SMS based on preference.
     */
    public function sendCode(\App\Models\User|\App\Models\AdminUser $user): void
    {
        // Check preference
        $type = $user->two_factor_type ?? 'email'; // Default to email if column missing

        if ($type === 'sms' && $this->isSmsEnabled($user)) {
             \Illuminate\Support\Facades\Cache::put('2fa_login_' . $user->id, 'sms_sent', now()->addMinutes(10));
             $this->sendCodeViaSms($user);
        } else {
             $this->sendCodeViaEmail($user);
        }
    }

    /**
     * Send 2FA code via Email.
     */
    public function sendCodeViaEmail(\App\Models\User|\App\Models\AdminUser $user): void
    {
        $this->configureMailer();
        $code = (string) rand(100000, 999999);
        // Cache code for verification
        \Illuminate\Support\Facades\Cache::put('2fa_login_' . $user->id, $code, now()->addMinutes(10));
        $this->sendCodeWithTemplate($user, $code);
    }

    /**
     * Send 2FA code via SMS using Authentica.
     */
    public function sendCodeViaSms(\App\Models\User|\App\Models\AdminUser $user): void
    {
        $phone = $user->phone;
        
        if (!$phone) {
            throw new \Exception('رقم الهاتف غير مسجل');
        }

        // Get OTP-specific SMS provider (or fall back to default)
        $integration = \App\Models\Integration\Integration::getForPurpose('sms', 'otp');

        if (!$integration || !$integration->isConfigured()) {
            throw new \Exception('خدمة الرسائل غير متاحة');
        }
        
        // For Authentica, use OTP API; for others, send regular SMS with code
        if ($integration->provider !== 'authentica') {
            throw new \Exception('مزود SMS الحالي لا يدعم التحقق الثنائي');
        }

        $formattedPhone = $this->formatPhoneNumber($phone);
        
        $service = \App\Services\Sms\AuthenticaService::fromIntegration($integration);
        $service->sendOtp($formattedPhone); 
    }

    protected function formatPhoneNumber(string $phone): string
    {
        $phone = preg_replace('/[\s\-]/', '', $phone);
        if (str_starts_with($phone, '0')) return '+966' . substr($phone, 1);
        if (str_starts_with($phone, '5')) return '+966' . $phone;
        if (!str_starts_with($phone, '+')) return '+966' . $phone;
        return $phone;
    }

    /**
     * Verify SMS code using Authentica.
     */
    public function verifySmsCode(\App\Models\User|\App\Models\AdminUser $user, string $code): bool
    {
        // Get OTP-specific SMS provider
        $integration = \App\Models\Integration\Integration::getForPurpose('sms', 'otp');

        if (!$integration || !$integration->isConfigured() || $integration->provider !== 'authentica') {
            return false;
        }

        $phone = $user->phone;
        $formattedPhone = $this->formatPhoneNumber($phone);
        
        $service = \App\Services\Sms\AuthenticaService::fromIntegration($integration);
        $result = $service->verifyOtp($formattedPhone, $code);
        
        return $result['success'];
    }

    /**
     * Check if SMS 2FA is enabled globally.
     */
    public function isSmsEnabled(\App\Models\User|\App\Models\AdminUser|null $user = null): bool
    {
        return \App\Models\Setting::get('security.2fa_sms_enabled', 'false') === 'true';
    }

    public function sendCodeWithTemplate(\App\Models\User|\App\Models\AdminUser $user, string $code): void
    {
        $this->configureMailer();

        // Get Template
        $template = \App\Models\CommunicationTemplate::getByCode('2fa_verification');
        
        if (!$template) {
            // Fallback to hardcoded if template missing (safety)
            \Illuminate\Support\Facades\Mail::to($user->email)->send(new \App\Mail\TwoFactorCodeMail($code));
            return;
        }

        // Replace variables
        $subject = str_replace(
            ['{code}', '{app_name}', '{name}'],
            [$code, config('app.name'), $user->name],
            $template->subject ?? 'Activation Code'
        );

        $content = str_replace(
            ['{code}', '{app_name}', '{name}'],
            [$code, config('app.name'), $user->name],
            $template->content
        );

        \Illuminate\Support\Facades\Mail::to($user->email)->send(new \App\Mail\TemplateMail($subject, $content));
    }

   /**
     * Configure mailer from database settings.
     */
    protected function configureMailer(): void
    {
        $integration = \App\Models\Integration\Integration::where('type', 'email')
            ->where('is_active', true)
            ->where('is_default', true)
            ->first();

        if (!$integration || !$integration->isConfigured()) {
            return;
        }

        $config = $integration->config;

        if ($integration->provider === 'smtp') {
            config([
                'mail.default' => 'smtp',
                'mail.mailers.smtp.host' => $config['host'],
                'mail.mailers.smtp.port' => $config['port'],
                'mail.mailers.smtp.username' => $config['username'],
                'mail.mailers.smtp.password' => $config['password'],
                'mail.mailers.smtp.encryption' => $config['encryption'] ?? 'tls',
                'mail.from.address' => $config['from_address'],
                'mail.from.name' => $config['from_name'],
            ]);
        }
    }
}
