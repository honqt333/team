<?php

namespace App\Http\Controllers\System;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

use App\Services\TwoFactorService;

class ProfileController extends Controller
{
    protected TwoFactorService $twoFactor;

    public function __construct(TwoFactorService $twoFactor)
    {
        $this->twoFactor = $twoFactor;
    }

    /**
     * Display admin profile.
     */
    public function index(): Response
    {
        $admin = Auth::guard('admin')->user();
        
        // Generate secret if not already set
        $secret = $admin->two_factor_secret;
        $isEnabled = $admin->two_factor_confirmed_at !== null;
        
        if (!$secret && !$isEnabled) {
            $secret = $this->twoFactor->generateSecret();
            $admin->update(['two_factor_secret' => encrypt($secret)]);
        } elseif ($secret && !$isEnabled) {
            $secret = decrypt($secret);
        }

        $qrCode = !$isEnabled ? $this->twoFactor->generateQrCode($admin->email, $secret) : null;

        return Inertia::render('System/Profile/Index', [
            'admin' => $admin,
            'security' => [
                'isEnabled' => $isEnabled,
                'isEnforced' => false, // System admins might not enforce on themselves or it's global?
                'secret' => !$isEnabled ? $secret : null,
                'qrCode' => $qrCode,
                'recoveryCodes' => $isEnabled && $admin->two_factor_recovery_codes 
                    ? json_decode(decrypt($admin->two_factor_recovery_codes), true) 
                    : null,
                'smsEnabled' => $this->twoFactor->isSmsEnabled($admin) ?? false,
            ]
        ]);
    }

    /**
     * Update admin profile.
     */
    public function update(Request $request)
    {
        $admin = Auth::guard('admin')->user();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:admin_users,email,' . $admin->id,
            'phone' => 'nullable|string|max:20|unique:admin_users,phone,' . $admin->id,
        ]);

        $admin->update($validated);

        return back()->with('success', 'تم تحديث الملف الشخصي بنجاح');
    }

    /**
     * Update password.
     */
    public function updatePassword(Request $request)
    {
        $admin = Auth::guard('admin')->user();

        $request->validate([
            'current_password' => ['required', 'current_password:admin'],
            'password' => ['required', Password::defaults(), 'confirmed'],
        ]);

        $admin->update([
            'password' => Hash::make($request->password),
        ]);

        return back()->with('success', 'تم تحديث كلمة المرور بنجاح');
    }
}
