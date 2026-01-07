<?php

namespace App\Http\Controllers\System;

use App\Http\Controllers\Controller;
use App\Models\AdminUser;
use App\Services\TwoFactorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;

class TwoFactorController extends Controller
{
    protected TwoFactorService $twoFactor;

    public function __construct(TwoFactorService $twoFactor)
    {
        $this->twoFactor = $twoFactor;
    }

    /**
     * Show 2FA setup page.
     */
    public function setup(): Response
    {
        $user = auth()->user();
        
        // Generate secret if not already set
        $secret = $user->two_factor_secret;
        $isEnabled = $user->two_factor_confirmed_at !== null;
        
        if (!$secret && !$isEnabled) {
            $secret = $this->twoFactor->generateSecret();
            $user->update(['two_factor_secret' => encrypt($secret)]);
        } elseif ($secret && !$isEnabled) {
            $secret = decrypt($secret);
        }

        $qrCode = !$isEnabled ? $this->twoFactor->generateQrCode($user->email, $secret) : null;

        return Inertia::render('System/Security/TwoFactorSetup', [
            'secret' => !$isEnabled ? $secret : null,
            'qrCode' => $qrCode,
            'isEnabled' => $isEnabled,
            'recoveryCodes' => $isEnabled && $user->two_factor_recovery_codes 
                ? json_decode(decrypt($user->two_factor_recovery_codes), true) 
                : null,
        ]);
    }

    /**
     * Send 2FA code (for setup).
     */
    public function sendCode(Request $request)
    {
        $request->validate([
            'method' => 'required|in:email,sms',
        ]);

        $user = auth()->user();
        
        if ($request->method === 'sms') {
             if (!$user->phone) {
                 return back()->withErrors(['method' => 'يجب تسجيل رقم الهاتف أولاً لاستخدام الرسائل النصية']);
             }
             
             try {
                 $this->twoFactor->sendCodeViaSms($user);
             } catch (\Exception $e) {
                 return back()->withErrors(['method' => $e->getMessage()]);
             }
             return back()->with('success', 'تم إرسال رمز التحقق إلى هاتفك');
        }

        // Email
        $this->twoFactor->sendCodeViaEmail($user);

        return back()->with('success', 'تم إرسال رمز التحقق إلى بريدك الإلكتروني');
    }

    /**
     * Enable 2FA.
     */
    public function enable(Request $request)
    {
        $request->validate([
            'code' => 'required|string|size:6',
        ]);

        $user = auth()->user();
        $secret = decrypt($user->two_factor_secret);

        if (!$this->twoFactor->verify($secret, $request->code)) {
            return back()->withErrors(['code' => 'رمز التحقق غير صحيح']);
        }

        // Generate recovery codes
        $recoveryCodes = $this->twoFactor->generateRecoveryCodes();

        $user->update([
            'two_factor_confirmed_at' => now(),
            'two_factor_recovery_codes' => encrypt(json_encode($recoveryCodes)),
        ]);

        return back()->with('success', 'تم تفعيل المصادقة الثنائية');
    }

    /**
     * Disable 2FA.
     */
    public function disable(Request $request)
    {
        $request->validate([
            'password' => 'required|string',
        ]);

        $user = auth()->user();

        if (!Hash::check($request->password, $user->password)) {
            return back()->withErrors(['password' => 'كلمة المرور غير صحيحة']);
        }

        $user->update([
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ]);

        return back()->with('success', 'تم إلغاء المصادقة الثنائية');
    }

    /**
     * Regenerate recovery codes.
     */
    public function regenerateRecoveryCodes(Request $request)
    {
        $request->validate([
            'password' => 'required|string',
        ]);

        $user = auth()->user();

        if (!Hash::check($request->password, $user->password)) {
            return back()->withErrors(['password' => 'كلمة المرور غير صحيحة']);
        }

        $recoveryCodes = $this->twoFactor->generateRecoveryCodes();
        $user->update([
            'two_factor_recovery_codes' => encrypt(json_encode($recoveryCodes)),
        ]);

        return back()->with('success', 'تم إنشاء رموز استرداد جديدة');
    }

    /**
     * Show 2FA challenge page (during login).
     */
    public function challenge(): Response
    {
        return Inertia::render('System/Security/TwoFactorChallenge');
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
        $userType = session('2fa:user_type', 'admin');
        
        if (!$userId) {
            return redirect()->route('login');
        }

        $user = $userType === 'admin' 
            ? AdminUser::find($userId)
            : \App\Models\User::find($userId);

        if (!$user) {
            return redirect()->route('login');
        }

        $secret = decrypt($user->two_factor_secret);

        // Verify TOTP code
        if ($request->code) {
            if (!$this->twoFactor->verify($secret, $request->code)) {
                return back()->withErrors(['code' => 'رمز التحقق غير صحيح']);
            }
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
        session()->forget(['2fa:user_id', '2fa:user_type']);

        // Login user
        auth()->login($user);
        $user->updateLoginInfo();

        return redirect()->intended('/system');
    }
}
