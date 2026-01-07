<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Mail\TwoFactorCodeMail;
use App\Services\TwoFactorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
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
        $tenant = $user->tenant;
        
        $isEnabled = $user->two_factor_confirmed_at !== null;
        
        return Inertia::render('App/Security/TwoFactorSetup', [
            'isEnabled' => $isEnabled,
            'isEnforced' => $tenant->two_factor_enforcement === 'required',
            'currentMethod' => $user->two_factor_type, // email or sms
            'smsEnabled' => $this->twoFactor->isSmsEnabled($user),
            'recoveryCodes' => $isEnabled && $user->two_factor_recovery_codes 
                ? json_decode(decrypt($user->two_factor_recovery_codes), true) 
                : null,
        ]);
    }

    /**
     * Send 2FA verification code.
     */
    public function sendCode(Request $request)
    {
        $request->validate([
            'method' => 'nullable|in:email,sms',
        ]);

        $user = auth()->user();
        $method = $request->method ?? 'email';

        if ($method === 'sms' && !$this->twoFactor->isSmsEnabled($user)) {
            return back()->withErrors(['method' => 'SMS 2FA is currently disabled']);
        }
        
        // Cache intended method
        Cache::put('2fa_setup_method_' . $user->id, $method, now()->addMinutes(10));

        if ($method === 'sms') {
             // Check if user has phone
             if (!$user->phone) {
                 return back()->withErrors(['method' => 'يجب تسجيل رقم الهاتف أولاً لاستخدام الرسائل النصية']);
             }
             
             try {
                 $this->twoFactor->sendCodeViaSms($user);
             } catch (\Exception $e) {
                 return back()->withErrors(['method' => $e->getMessage()]);
             }
             return back()->with('success', 'تم إرسال رمز التحقق إلى هاتفك');
        } else {
             // Email
             $code = (string) rand(100000, 999999);
             Cache::put('2fa_setup_' . $user->id, $code, now()->addMinutes(10));
             try {
                $this->twoFactor->sendCodeWithTemplate($user, $code);
             } catch (\Exception $e) {
                Cache::forget('2fa_setup_' . $user->id);
                return back()->withErrors(['email' => 'فشل في إرسال البريد الإلكتروني. يرجى التحقق من إعدادات البريد.']);
             }
             return back()->with('success', 'تم إرسال رمز التحقق إلى بريدك الإلكتروني');
        }
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
        $method = Cache::get('2fa_setup_method_' . $user->id, 'email');

        if ($method === 'sms') {
             if (!$this->twoFactor->verifySmsCode($user, $request->code)) {
                 return back()->withErrors(['code' => 'رمز التحقق غير صحيح']);
             }
        } else {
             $cachedCode = Cache::get('2fa_setup_' . $user->id);
             if (!$cachedCode || $cachedCode !== $request->code) {
                 return back()->withErrors(['code' => 'رمز التحقق غير صحيح أو منتهي الصلاحية']);
             }
        }

        // Generate recovery codes
        $recoveryCodes = $this->twoFactor->generateRecoveryCodes();

        $user->update([
            'two_factor_confirmed_at' => now(),
            'two_factor_type' => $method,
            'two_factor_recovery_codes' => encrypt(json_encode($recoveryCodes)),
            'two_factor_secret' => null, 
        ]);
        
        Cache::forget('2fa_setup_' . $user->id);
        Cache::forget('2fa_setup_method_' . $user->id);

        return back()->with('success', 'تم تفعيل المصادقة الثنائية');
    }

    /**
     * Disable 2FA.
     */
    public function disable(Request $request)
    {
        $user = auth()->user();
        $tenant = $user->tenant;

        if ($tenant->two_factor_enforcement === 'required') {
            return back()->withErrors(['error' => 'لا يمكن إلغاء المصادقة الثنائية لأنها إلزامية من قبل مدير النظام']);
        }

        $request->validate([
            'password' => 'required|string',
        ]);

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
}
