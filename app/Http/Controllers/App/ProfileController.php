<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Services\TwoFactorService;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
     * Display the user's profile.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();
        $tenant = $user->tenant;
        
        $isEnabled = $user->two_factor_confirmed_at !== null;
        
        return Inertia::render('App/Profile/Index', [
            'mustVerifyEmail' => $request->user() instanceof MustVerifyEmail,
            'status' => session('status'),
            'isEnabled' => $isEnabled,
            'isEnforced' => $tenant?->two_factor_enforcement === 'required',
            'currentMethod' => $user->two_factor_type,
            'smsEnabled' => $this->twoFactor->isSmsEnabled($user),
            'recoveryCodes' => $isEnabled && $user->two_factor_recovery_codes 
                ? json_decode(decrypt($user->two_factor_recovery_codes), true) 
                : null,
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email,' . $request->user()->id],
        ]);

        $request->user()->fill($request->only(['name', 'email']));

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return back()->with('success', 'تم تحديث الملف الشخصي بنجاح');
    }
}
