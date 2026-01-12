<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/Login', [
            'canResetPassword' => Route::has('password.request'),
            'status' => session('status'),
        ]);
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        // Check if this is an admin login
        if (session('is_admin_login')) {
            session()->forget('is_admin_login');
            return redirect('/system');
        }

        // Regular user login
        $user = $request->user();

        // Setup center context if needed
        if ($user && (!$user->current_center_id || !$user->centers()->where('centers.id', $user->current_center_id)->exists())) {
            $firstCenter = $user->centers()->first();
            if ($firstCenter) {
                $user->update(['current_center_id' => $firstCenter->id]);
            }
        }

        // Check if user has 2FA enabled - redirect to challenge
        if ($user && $user->two_factor_confirmed_at !== null) {
            $request->session()->put('2fa:user_id', $user->id);
            Auth::guard('web')->logout();
            return redirect()->route('app.2fa.challenge');
        }

        // Set team context for role checking (required by Spatie Permission)
        if ($user->tenant_id) {
            app(\Spatie\Permission\PermissionRegistrar::class)->setPermissionsTeamId($user->tenant_id);
        }

        // Role-based redirect
        // If user ONLY has 'employee' role, redirect to employee portal
        $roles = $user->getRoleNames()->toArray();
        $isOnlyEmployee = count($roles) === 1 && in_array('employee', $roles);
        
        if ($isOnlyEmployee) {
            return redirect()->intended(route('employee.dashboard', absolute: false));
        }

        // Tenant user with other roles → Tenant Panel
        return redirect()->intended(route('dashboard', absolute: false));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        // Logout from both guards
        Auth::guard('web')->logout();
        Auth::guard('admin')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
