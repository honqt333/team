<?php

declare(strict_types=1);

namespace App\Services;

use App\Mail\TemplateMail;
use App\Mail\TwoFactorCodeMail;
use App\Models\AdminUser;
use App\Models\CommunicationTemplate;
use App\Models\Integration\Integration;
use App\Models\Setting;
use App\Models\User;
use App\Services\Email\SmtpConfigService;
use App\Services\Sms\AuthenticaService;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use PragmaRX\Google2FA\Google2FA;

class TwoFactorService
{
    protected Google2FA $google2fa;

    public function __construct()
    {
        $this->google2fa = new Google2FA;
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
    public function generateQrCode(string $email, string $secret, string $appName = 'Khidmh Pro'): string
    {
        $otpAuthUrl = $this->google2fa->getQRCodeUrl(
            $appName,
            $email,
            $secret
        );

        $renderer = new ImageRenderer(
            new RendererStyle(200),
            new SvgImageBackEnd
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
            $codes[] = Str::upper(Str::random(4).'-'.Str::random(4));
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
        return array_values(array_filter($codes, fn ($code) => $code !== Str::upper($usedCode)));
    }

    /**
     * Send 2FA code via email or SMS based on preference.
     */
    /**
     * Send 2FA code via email or SMS based on preference.
     */
    public function sendCode(User|AdminUser $user): void
    {
        // Check preference
        $type = $user->two_factor_type ?? 'email'; // Default to email if column missing

        if ($type === 'sms' && $this->isSmsEnabled($user)) {
            Cache::put('2fa_login_'.$user->id, 'sms_sent', now()->addMinutes(10));
            $this->sendCodeViaSms($user);
        } else {
            $this->sendCodeViaEmail($user);
        }
    }

    /**
     * Send 2FA code via Email.
     */
    public function sendCodeViaEmail(User|AdminUser $user): void
    {
        SmtpConfigService::class;
        app(SmtpConfigService::class)->apply();
        $code = (string) rand(100000, 999999);
        // Cache code for verification
        Cache::put('2fa_login_'.$user->id, $code, now()->addMinutes(10));
        $this->sendCodeWithTemplate($user, $code);
    }

    /**
     * Send 2FA code via SMS using Authentica.
     */
    public function sendCodeViaSms(User|AdminUser $user): void
    {
        $phone = $user->phone;

        if (! $phone) {
            throw new Exception('رقم الهاتف غير مسجل');
        }

        // Get OTP-specific SMS provider (or fall back to default)
        $integration = Integration::getForPurpose('sms', 'otp');

        if (! $integration || ! $integration->isConfigured()) {
            throw new Exception('خدمة الرسائل غير متاحة');
        }

        // For Authentica, use OTP API; for others, send regular SMS with code
        if ($integration->provider !== 'authentica') {
            throw new Exception('مزود SMS الحالي لا يدعم التحقق الثنائي');
        }

        $formattedPhone = $this->formatPhoneNumber($phone);

        $service = AuthenticaService::fromIntegration($integration);
        $service->sendOtp($formattedPhone);
    }

    protected function formatPhoneNumber(string $phone): string
    {
        $phone = preg_replace('/[\s\-]/', '', $phone);

        if (str_starts_with($phone, '0')) {
            return '+966'.substr($phone, 1);
        }

        if (str_starts_with($phone, '5')) {
            return '+966'.$phone;
        }

        if (! str_starts_with($phone, '+')) {
            return '+966'.$phone;
        }

        return $phone;
    }

    /**
     * Verify SMS code using Authentica.
     */
    public function verifySmsCode(User|AdminUser $user, string $code): bool
    {
        // Get OTP-specific SMS provider
        $integration = Integration::getForPurpose('sms', 'otp');

        if (! $integration || ! $integration->isConfigured() || $integration->provider !== 'authentica') {
            return false;
        }

        $phone = $user->phone;
        $formattedPhone = $this->formatPhoneNumber($phone);

        $service = AuthenticaService::fromIntegration($integration);
        $result = $service->verifyOtp($formattedPhone, $code);

        return $result['success'];
    }

    /**
     * Check if SMS 2FA is enabled globally.
     */
    public function isSmsEnabled(User|AdminUser|null $user = null): bool
    {
        return Setting::get('security.2fa_sms_enabled', 'false') === 'true';
    }

    public function sendCodeWithTemplate(User|AdminUser $user, string $code): void
    {
        app(SmtpConfigService::class)->apply();

        // Get Template
        $template = CommunicationTemplate::getByCode('2fa_verification');

        if (! $template) {
            // Fallback to hardcoded if template missing (safety)
            Mail::to($user->email)->send(new TwoFactorCodeMail($code));

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

        Mail::to($user->email)->send(new TemplateMail($subject, $content));
    }
}
