<?php

declare(strict_types=1);

namespace App\Http\Controllers\System;

use App\Http\Controllers\Controller;
use App\Models\AdminUser;
use App\Models\User;
use App\Services\TwoFactorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    protected TwoFactorService $twoFactor;

    public function __construct(TwoFactorService $twoFactor)
    {
        $this->twoFactor = $twoFactor;
    }

    /**
     * Resolve the acting account for system profile actions.
     */
    private function actor(Request $request): AdminUser|User
    {
        $actor = $request->user();

        abort_if(! $actor, 401, 'Unauthorized');

        return $actor;
    }

    /**
     * Display admin profile.
     */
    public function index(Request $request): Response
    {
        $admin = $this->actor($request);

        // Generate secret if not already set
        $secret = $admin->two_factor_secret;
        $isEnabled = $admin->two_factor_confirmed_at !== null;

        if (! $secret && ! $isEnabled) {
            $secret = $this->twoFactor->generateSecret();
            $admin->forceFill(['two_factor_secret' => encrypt($secret)])->save();
        } elseif ($secret && ! $isEnabled) {
            $secret = decrypt($secret);
        }

        $qrCode = ! $isEnabled ? $this->twoFactor->generateQrCode($admin->email, $secret) : null;

        if ($admin instanceof User && empty($admin->role)) {
            $admin->setAttribute('role', 'super_admin');
        }

        return Inertia::render('System/Profile/Index', [
            'admin' => $admin,
            'security' => [
                'isEnabled' => $isEnabled,
                'isEnforced' => false, // System admins might not enforce on themselves or it's global?
                'secret' => ! $isEnabled ? $secret : null,
                'qrCode' => $qrCode,
                'recoveryCodes' => $isEnabled && $admin->two_factor_recovery_codes
                    ? json_decode(decrypt($admin->two_factor_recovery_codes), true)
                    : null,
                'smsEnabled' => $this->twoFactor->isSmsEnabled($admin) ?? false,
            ],
        ]);
    }

    /**
     * Update admin profile.
     */
    public function update(Request $request)
    {
        $admin = $this->actor($request);
        $table = $admin instanceof AdminUser ? 'admin_users' : 'users';

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:'.$table.',email,'.$admin->id,
            'phone' => 'nullable|string|max:20|unique:'.$table.',phone,'.$admin->id,
        ]);

        $admin->update($validated);

        return back()->with('success', __('messages.system.profile.updated'));
    }

    /**
     * Update password.
     */
    public function updatePassword(Request $request)
    {
        $admin = $this->actor($request);
        $guard = $admin instanceof AdminUser ? 'admin' : 'web';

        $request->validate([
            'current_password' => ['required', "current_password:{$guard}"],
            'password' => ['required', Password::defaults(), 'confirmed'],
        ]);

        $admin->update([
            'password' => Hash::make($request->password),
        ]);

        return back()->with('success', __('messages.system.profile.password_updated'));
    }
}
