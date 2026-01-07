<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Integration\Integration;
use App\Services\Sms\AuthenticaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;

class PhoneVerificationController extends Controller
{
    // OTP Settings
    const OTP_COOLDOWN_SECONDS = 60;  // Time before allowing resend
    const OTP_MAX_ATTEMPTS = 5;       // Max resend attempts per hour
    const OTP_BLOCK_DURATION = 3600;  // Block duration in seconds (1 hour)

    /**
     * Send OTP to phone number for verification.
     */
    public function sendOtp(Request $request)
    {
        $request->validate([
            'phone' => 'required|string|min:9|max:15',
        ]);

        // Format phone number
        $phone = $this->formatPhoneNumber($request->phone);

        // Check rate limiting
        $rateLimitResult = $this->checkRateLimit($phone);
        if (!$rateLimitResult['allowed']) {
            return back()->withErrors(['phone' => $rateLimitResult['message']]);
        }

        // Check if phone is unique (not already registered)
        if (\App\Models\Tenant::where('phone', $phone)->exists()) {
            return back()->withErrors(['phone' => __('auth.phone_already_registered')]);
        }

        // Get Authentica integration
        $integration = Integration::where('provider', 'authentica')
            ->where('is_active', true)
            ->first();

        if (!$integration || !$integration->isConfigured()) {
            return back()->withErrors(['phone' => __('auth.verification_unavailable')]);
        }

        $service = AuthenticaService::fromIntegration($integration);
        $result = $service->sendOtp($phone);

        if ($result['success']) {
            // Store phone in session and update rate limit
            session(['phone_verification_number' => $phone]);
            $this->recordAttempt($phone);
            
            // Store cooldown end time
            $cooldownEndsAt = now()->addSeconds(self::OTP_COOLDOWN_SECONDS)->timestamp;
            session(['otp_cooldown_ends_at' => $cooldownEndsAt]);
            
            return back()->with([
                'success' => __('auth.otp_sent'),
                'cooldown_ends_at' => $cooldownEndsAt,
            ]);
        }

        return back()->withErrors(['phone' => $result['message'] ?? __('auth.otp_send_failed')]);
    }

    /**
     * Verify OTP code.
     */
    public function verifyOtp(Request $request)
    {
        $request->validate([
            'otp' => 'required|string|size:6',
        ]);

        $phone = session('phone_verification_number');

        if (!$phone) {
            return back()->withErrors(['otp' => __('auth.otp_request_again')]);
        }

        // Get Authentica integration
        $integration = Integration::where('provider', 'authentica')
            ->where('is_active', true)
            ->first();

        if (!$integration || !$integration->isConfigured()) {
            return back()->withErrors(['otp' => __('auth.verification_unavailable')]);
        }

        $service = AuthenticaService::fromIntegration($integration);
        $result = $service->verifyOtp($phone, $request->otp);

        // Debug logging
        \Log::info('Phone verification attempt', [
            'phone' => $phone,
            'otp' => $request->otp,
            'result' => $result,
        ]);

        if ($result['success']) {
            // Mark phone as verified in session
            session(['phone_verified' => true]);
            session(['verified_phone_number' => $phone]);
            
            // Clear rate limit for this phone
            Cache::forget('otp_attempts_' . $phone);
            
            return back()->with('success', __('auth.otp_verified'));
        }

        return back()->withErrors(['otp' => __('auth.otp_invalid')]);
    }

    /**
     * Check if phone verification is enabled.
     */
    public static function isEnabled(): bool
    {
        return Integration::where('provider', 'authentica')
            ->where('is_active', true)
            ->exists();
    }

    /**
     * Check rate limiting for OTP requests.
     */
    protected function checkRateLimit(string $phone): array
    {
        $cacheKey = 'otp_attempts_' . $phone;
        $attempts = Cache::get($cacheKey, []);

        // Check if blocked
        if (count($attempts) >= self::OTP_MAX_ATTEMPTS) {
            $oldestAttempt = min($attempts);
            $blockEndsAt = $oldestAttempt + self::OTP_BLOCK_DURATION;
            
            if (now()->timestamp < $blockEndsAt) {
                $remainingMinutes = ceil(($blockEndsAt - now()->timestamp) / 60);
                return [
                    'allowed' => false,
                    'message' => __('auth.rate_limit_exceeded', ['minutes' => $remainingMinutes]),
                ];
            }
            
            // Block period ended, reset attempts
            Cache::forget($cacheKey);
            $attempts = [];
        }

        // Check cooldown
        $cooldownEndsAt = session('otp_cooldown_ends_at', 0);
        if (now()->timestamp < $cooldownEndsAt) {
            $remainingSeconds = max(1, (int) ($cooldownEndsAt - now()->timestamp));
            return [
                'allowed' => false,
                'message' => __('auth.resend_cooldown', ['seconds' => $remainingSeconds]),
            ];
        }

        return ['allowed' => true];
    }

    /**
     * Record OTP attempt for rate limiting.
     */
    protected function recordAttempt(string $phone): void
    {
        $cacheKey = 'otp_attempts_' . $phone;
        $attempts = Cache::get($cacheKey, []);
        
        // Add current timestamp
        $attempts[] = now()->timestamp;
        
        // Remove attempts older than 1 hour
        $oneHourAgo = now()->subHour()->timestamp;
        $attempts = array_filter($attempts, fn($t) => $t > $oneHourAgo);
        
        Cache::put($cacheKey, array_values($attempts), self::OTP_BLOCK_DURATION);
    }

    /**
     * Format phone number to international format.
     */
    protected function formatPhoneNumber(string $phone): string
    {
        // Remove any spaces or dashes
        $phone = preg_replace('/[\s\-]/', '', $phone);

        // If starts with 0, remove it and add +966
        if (str_starts_with($phone, '0')) {
            $phone = '+966' . substr($phone, 1);
        }
        // If starts with 5 (Saudi mobile), add +966
        elseif (str_starts_with($phone, '5')) {
            $phone = '+966' . $phone;
        }
        // If starts with 966, add +
        elseif (str_starts_with($phone, '966')) {
            $phone = '+' . $phone;
        }
        // If doesn't start with +, add it
        elseif (!str_starts_with($phone, '+')) {
            $phone = '+' . $phone;
        }

        return $phone;
    }
}
