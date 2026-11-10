<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Inertia\Inertia;
use Spatie\Permission\PermissionRegistrar;

class SetPasswordController extends Controller
{
    /**
     * Show the set password form.
     */
    public function show(Request $request, User $user)
    {
        $this->assertInvitation($request, $user);

        return Inertia::render('Auth/SetPassword', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
            // Pass the signature parameters to include in the form submission URL
            'signature_params' => [
                'expires' => $request->query('expires'),
                'signature' => $request->query('signature'),
                'token' => $request->query('token'),
            ],
        ]);
    }

    /**
     * Handle the set password request.
     */
    public function store(Request $request, User $user)
    {
        $this->assertInvitation($request, $user);

        $request->validate([
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $user = DB::transaction(function () use ($request, $user) {
            $locked = User::query()->lockForUpdate()->findOrFail($user->id);
            $this->assertInvitation($request, $locked);
            $locked->forceFill([
                'password' => Hash::make($request->password),
                'email_verified_at' => now(),
                'invitation_token_hash' => null,
                'invitation_expires_at' => null,
            ])->save();
            $locked->logActivity('user.invitation.accepted');

            return $locked;
        });

        $request->session()->regenerate();

        if ($user->two_factor_confirmed_at !== null) {
            $request->session()->put('2fa:user_id', $user->id);
            Auth::guard('web')->logout();

            return redirect()->route('app.2fa.challenge');
        }
        Auth::login($user);

        // Set team context for role checking (required by Spatie Permission)
        if ($user->tenant_id) {
            app(PermissionRegistrar::class)->setPermissionsTeamId($user->tenant_id);
        }

        // Role-based redirect
        // If user ONLY has 'employee' role, redirect to employee portal
        $roles = $user->getRoleNames()->toArray();
        $isOnlyEmployee = count($roles) === 1 && in_array('employee', $roles);

        if ($isOnlyEmployee) {
            return redirect()->intended(route('employee.dashboard'));
        }

        return redirect()->intended(route('dashboard'));
    }

    private function assertInvitation(Request $request, User $user): void
    {
        $token = $request->query('token');
        abort_unless($request->hasValidSignature()
            && $user->is_active
            && is_string($token)
            && $user->invitation_token_hash
            && $user->invitation_expires_at?->isFuture()
            && hash_equals($user->invitation_token_hash, hash('sha256', $token)),
            403, __('auth.invitation_invalid'));
    }
}
