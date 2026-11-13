<?php

declare(strict_types=1);

namespace App\Http\Controllers\System;

use App\Http\Controllers\Controller;
use App\Models\AdminUser;
use App\Models\User;
use App\Services\System\SystemAuditService;
use App\Services\TwoFactorService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;

class TwoFactorController extends Controller
{
    protected TwoFactorService $twoFactor;

    protected SystemAuditService $audit;

    public function __construct(TwoFactorService $twoFactor, ?SystemAuditService $audit = null)
    {
        $this->twoFactor = $twoFactor;
        $this->audit = $audit ?? app(SystemAuditService::class);
    }

    /**
     * Resolve the acting account for the 2FA actions.
     *
     * `$request->user()` honours the guard that the `auth:web,admin`
     * middleware activated for this request, so it returns the
     * AdminUser for an admin session and the tenant User for a
     * tenant-session system admin. The bare `auth()` helper resolves
     * the *configured* default guard ('web') instead, which is not the
     * same identity space — it can hand back null (a fatal error on
     * the next line) or the wrong account entirely.
     *
     * We fail loudly with a 401 rather than dereferencing null: a
     * missing identity must never surface as a 500.
     */
    private function actor(Request $request): AdminUser|User
    {
        $actor = $request->user();

        abort_if(! $actor, 401, 'Unauthenticated');

        return $actor;
    }

    /**
     * Show 2FA setup page.
     */
    public function setup(Request $request): Response
    {
        $user = $this->actor($request);

        // Generate secret if not already set
        $secret = $user->two_factor_secret;
        $isEnabled = $user->two_factor_confirmed_at !== null;

        if (! $secret && ! $isEnabled) {
            $secret = $this->twoFactor->generateSecret();
            $user->update(['two_factor_secret' => encrypt($secret)]);
        } elseif ($secret && ! $isEnabled) {
            $secret = decrypt($secret);
        }

        $qrCode = ! $isEnabled ? $this->twoFactor->generateQrCode($user->email, $secret) : null;

        return Inertia::render('System/Security/TwoFactorSetup', [
            'secret' => ! $isEnabled ? $secret : null,
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

        $user = $this->actor($request);

        if ($request->method === 'sms') {
            if (! $user->phone) {
                return back()->withErrors(['method' => __('messages.system.two_factor.phone_required')]);
            }

            try {
                $this->twoFactor->sendCodeViaSms($user);
            } catch (Exception $e) {
                return back()->withErrors(['method' => $e->getMessage()]);
            }

            return back()->with('success', __('messages.system.two_factor.code_sent_phone'));
        }

        // Email
        $this->twoFactor->sendCodeViaEmail($user);

        return back()->with('success', __('messages.system.two_factor.code_sent_email'));
    }

    /**
     * Enable 2FA.
     */
    public function enable(Request $request)
    {
        $request->validate([
            'code' => 'required|string|size:6',
        ]);

        $user = $this->actor($request);
        $secret = decrypt($user->two_factor_secret);

        if (! $this->twoFactor->verify($secret, $request->code)) {
            return back()->withErrors(['code' => __('messages.system.two_factor.invalid_code')]);
        }

        // Generate recovery codes
        $recoveryCodes = $this->twoFactor->generateRecoveryCodes();

        $user->update([
            'two_factor_confirmed_at' => now(),
            'two_factor_recovery_codes' => encrypt(json_encode($recoveryCodes)),
        ]);

        $this->audit->log('two_factor_enabled', $user::class, $user->id, [], $request);

        return back()->with('success', __('messages.system.two_factor.enabled'));
    }

    /**
     * Disable 2FA.
     */
    public function disable(Request $request)
    {
        $request->validate([
            'password' => 'required|string',
        ]);

        $user = $this->actor($request);

        if (! Hash::check($request->password, $user->password)) {
            return back()->withErrors(['password' => __('messages.system.two_factor.invalid_password')]);
        }

        $user->update([
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ]);

        $this->audit->log('two_factor_disabled', $user::class, $user->id, [], $request);

        return back()->with('success', __('messages.system.two_factor.disabled'));
    }

    /**
     * Regenerate recovery codes.
     */
    public function regenerateRecoveryCodes(Request $request)
    {
        $request->validate([
            'password' => 'required|string',
        ]);

        $user = $this->actor($request);

        if (! Hash::check($request->password, $user->password)) {
            return back()->withErrors(['password' => __('messages.system.two_factor.invalid_password')]);
        }

        $recoveryCodes = $this->twoFactor->generateRecoveryCodes();
        $user->update([
            'two_factor_recovery_codes' => encrypt(json_encode($recoveryCodes)),
        ]);

        $this->audit->log('two_factor_recovery_codes_regenerated', $user::class, $user->id, [], $request);

        return back()->with('success', __('messages.system.two_factor.codes_regenerated'));
    }

    /**
     * Show 2FA challenge page (during login).
     */
    public function challenge(): Response
    {
        // The admin panel authenticates with a TOTP code from an
        // authenticator app — that is the mechanism its setup page
        // enrols (the QR code of the shared secret). So the challenge
        // does not send a code by mail or SMS: the admin reads the
        // current code from their app.
        //
        // The tenant flow is the one that sends a code, and it has its
        // own controller and its own cache key.
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

        if (! $userId) {
            return redirect()->route('login');
        }

        $user = $userType === 'admin'
            ? AdminUser::find($userId)
            : User::find($userId);

        if (! $user || ! $user->two_factor_secret) {
            session()->forget(['2fa:user_id', '2fa:user_type']);

            return redirect()->route('login');
        }

        $secret = decrypt($user->two_factor_secret);

        // Verify TOTP code
        if ($request->code) {
            if (! $this->twoFactor->verify($secret, $request->code)) {
                return back()->withErrors(['code' => __('messages.system.two_factor.invalid_code')]);
            }
        }
        // Verify recovery code
        elseif ($request->recovery_code) {
            if (! $user->two_factor_recovery_codes) {
                return back()->withErrors(['recovery_code' => __('messages.system.two_factor.invalid_recovery_code')]);
            }

            $recoveryCodes = json_decode(decrypt($user->two_factor_recovery_codes), true);

            if (! $this->twoFactor->verifyRecoveryCode($request->recovery_code, $recoveryCodes)) {
                return back()->withErrors(['recovery_code' => __('messages.system.two_factor.invalid_recovery_code')]);
            }

            // Remove used recovery code
            $newCodes = $this->twoFactor->removeRecoveryCode($request->recovery_code, $recoveryCodes);
            $user->update([
                'two_factor_recovery_codes' => encrypt(json_encode($newCodes)),
            ]);
        } else {
            return back()->withErrors(['code' => __('messages.system.two_factor.code_required')]);
        }

        // Clear 2FA session
        session()->forget(['2fa:user_id', '2fa:user_type']);

        // Login user on the guard that owns the account. The system
        // panel reads the 'admin' guard, and the default guard is
        // 'web' — logging an AdminUser into 'web' would leave the
        // panel unauthenticated.
        if ($userType === 'admin') {
            Auth::guard('admin')->login($user);
        } else {
            Auth::guard('web')->login($user);
        }

        $user->updateLoginInfo();

        return redirect()->intended('/system');
    }
}
