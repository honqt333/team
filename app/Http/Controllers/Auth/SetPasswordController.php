<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Inertia\Inertia;

class SetPasswordController extends Controller
{
    /**
     * Show the set password form.
     */
    public function show(Request $request, User $user)
    {
        if (!$request->hasValidSignature()) {
            abort(403, 'Invalid or expired invitation link.');
        }

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
            ]
        ]);
    }

    /**
     * Handle the set password request.
     */
    public function store(Request $request, User $user)
    {
        if (!$request->hasValidSignature()) {
            abort(403, 'Invalid or expired invitation link.');
        }

        $request->validate([
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $user->forceFill([
            'password' => Hash::make($request->password),
            'email_verified_at' => now(), // Ensure confirmed
            'is_active' => true,
        ])->save();

        Auth::login($user);

        // Set team context for role checking (required by Spatie Permission)
        if ($user->tenant_id) {
            app(\Spatie\Permission\PermissionRegistrar::class)->setPermissionsTeamId($user->tenant_id);
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
}
