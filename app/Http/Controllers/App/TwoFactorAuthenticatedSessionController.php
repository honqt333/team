<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Mail\TwoFactorCodeMail;
use App\Models\User;
use App\Services\TwoFactorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;
use Inertia\Response;

class TwoFactorAuthenticatedSessionController extends Controller
{
    protected TwoFactorService $twoFactor;

    public function __construct(TwoFactorService $twoFactor)
    {
        $this->twoFactor = $twoFactor;
    }

    /**
     * Show 2FA challenge page.
     */
    public function challenge(): Response
    {
        $userId = session('2fa:user_id');
        
        if (!$userId) {
            abort(404); // Should redirect to login usually but Inertia handles redirects better in middleware
        }
        
        // Check if code already sent recently (throttle)
        if (!Cache::has('2fa_login_' . $userId)) {
             $this->sendCode($userId);
        }

        return Inertia::render('App/Security/TwoFactorChallenge');
    }

    /**
     * Resend 2FA code.
     */
    public function resend(Request $request)
    {
        $userId = session('2fa:user_id');

        if (!$userId) {
            return redirect()->route('login');
        }

        $this->sendCode($userId);
        
        // Determine message based on user preference
        $user = User::find($userId);
        $method = ($user && $user->two_factor_type === 'sms' && $this->twoFactor->isSmsEnabled()) ? 'الرسائل النصية' : 'بريدك الإلكتروني';

        return back()->with('success', "تم إرسال رمز جديد إلى {$method}");
    }

    /**
     * Helper to send code
     */
    protected function sendCode($userId)
    {
        $user = User::find($userId);
        if ($user) {
            $this->twoFactor->sendCode($user);
        }
    }

    /**
     * Verify 2FA during login.
     */
    public function verify(Request $request)
    {
        $request->validate([
            'code' => 'nullable|string',
            'recovery_code' => 'nullable|string',
        ]);

        $userId = session('2fa:user_id');
        
        if (!$userId) {
            return redirect()->route('login');
        }

        $user = User::find($userId);

        if (!$user) {
            return redirect()->route('login');
        }

        // Verify OTP code
        if ($request->code) {
            $cachedCode = Cache::get('2fa_login_' . $userId);

            // Check how to verify (SMS vs Email)
            $isSms = $user->two_factor_type === 'sms' && $this->twoFactor->isSmsEnabled();

            if ($isSms) {
                // Verify via Authentica
                if (!$this->twoFactor->verifySmsCode($user, $request->code)) {
                     return back()->withErrors(['code' => 'رمز التحقق غير صحيح']);
                }
            } else {
                // Verify via Cache (Email)
                if (!$cachedCode || $cachedCode !== $request->code) {
                    return back()->withErrors(['code' => 'رمز التحقق غير صحيح أو منتهي الصلاحية']);
                }
            }
            
            Cache::forget('2fa_login_' . $userId);
        } 
        // Verify recovery code
        elseif ($request->recovery_code) {
            $recoveryCodes = json_decode(decrypt($user->two_factor_recovery_codes), true);
            
            if (!$this->twoFactor->verifyRecoveryCode($request->recovery_code, $recoveryCodes)) {
                return back()->withErrors(['recovery_code' => 'رمز الاسترداد غير صحيح']);
            }

            // Remove used recovery code
            $newCodes = $this->twoFactor->removeRecoveryCode($request->recovery_code, $recoveryCodes);
            $user->update([
                'two_factor_recovery_codes' => encrypt(json_encode($newCodes)),
            ]);
        } else {
            return back()->withErrors(['code' => 'يرجى إدخال رمز التحقق']);
        }

        // Clear 2FA session
        session()->forget(['2fa:user_id']);

        // Login user
        auth()->login($user);

        return redirect()->intended(route('dashboard'));
    }
}
