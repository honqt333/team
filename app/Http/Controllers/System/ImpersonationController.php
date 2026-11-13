<?php

declare(strict_types=1);

namespace App\Http\Controllers\System;

use App\Http\Controllers\Controller;
use App\Models\AdminActivityLog;
use App\Models\AdminUser;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class ImpersonationController extends Controller
{
    /**
     * Start impersonating a tenant (login as their admin user).
     */
    public function start(Request $request, Tenant $tenant): RedirectResponse
    {
        // Find the owner / admin user of this tenant
        $tenantUser = User::where('tenant_id', $tenant->id)
            ->whereHas('roles', fn ($q) => $q->whereIn('name', ['super_admin', 'admin', 'branch_manager']))
            ->first();

        if (! $tenantUser) {
            // Fallback to any user of this tenant
            $tenantUser = User::where('tenant_id', $tenant->id)->first();
        }

        if (! $tenantUser) {
            return back()->with('error', __('messages.system.impersonation.no_users'));
        }

        $actingUser = $request->user();

        if (! $actingUser) {
            abort(403, __('messages.system.impersonation.no_admin'));
        }

        $isAdminUser = $actingUser instanceof AdminUser;
        $isSystemAdmin = $actingUser instanceof User && (bool) $actingUser->is_system_admin;

        $isSuperAdmin = ($isAdminUser && $actingUser->isSuperAdmin()) || $isSystemAdmin;

        if (! $isSuperAdmin) {
            abort(403, __('messages.system.impersonation.no_admin'));
        }

        $matchingAdmin = $isAdminUser
            ? $actingUser
            : AdminUser::where('email', $actingUser->email)->orWhere('user_id', $actingUser->id)->first();

        // If on web guard and matching AdminUser exists, log in to admin guard so session is preserved
        if ($matchingAdmin && ! Auth::guard('admin')->check()) {
            Auth::guard('admin')->login($matchingAdmin);
        }

        // Store the original identity and guard so we can restore it later.
        Session::put('impersonating_from', $actingUser->id);
        Session::put('impersonating_from_guard', $isAdminUser ? 'admin' : 'web');
        Session::put('impersonating_tenant', $tenant->id);

        // Audit log
        if ($matchingAdmin) {
            AdminActivityLog::create([
                'admin_user_id' => $matchingAdmin->id,
                'action' => 'impersonate_start',
                'model_type' => Tenant::class,
                'model_id' => $tenant->id,
                'changes' => [
                    'target_user_id' => $tenantUser->id,
                    'target_user_email' => $tenantUser->email,
                ],
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
        }

        // Log in as the tenant user on the 'web' guard and rotate session ID.
        Auth::guard('web')->login($tenantUser);
        $request->session()->regenerate();

        return redirect('/dashboard')->with('success', __('messages.system.impersonation.inside_account', ['tenant' => $tenant->trade_name]));
    }

    /**
     * Stop impersonating and return to system admin.
     */
    public function stop(Request $request): RedirectResponse
    {
        $originalId = Session::get('impersonating_from');
        $originalGuard = Session::get('impersonating_from_guard', 'admin');
        $impersonatedTenantId = Session::get('impersonating_tenant');

        // Audit log BEFORE we lose the impersonation context.
        $adminUserId = Auth::guard('admin')->id();

        if (! $adminUserId && $originalGuard === 'web') {
            $adminUserId = AdminUser::where('user_id', $originalId)->orWhere('id', $originalId)->value('id');
        }

        if ($adminUserId && $impersonatedTenantId) {
            AdminActivityLog::create([
                'admin_user_id' => $adminUserId,
                'action' => 'impersonate_stop',
                'model_type' => Tenant::class,
                'model_id' => $impersonatedTenantId,
                'changes' => [
                    'original_admin_user_id' => $originalId,
                ],
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
        }

        // Drop the tenant staff session — we are leaving the tenant.
        Auth::guard('web')->logout();

        // Clear impersonation session.
        Session::forget('impersonating_from');
        Session::forget('impersonating_from_guard');
        Session::forget('impersonating_tenant');

        if (! $originalId) {
            return Auth::guard('admin')->check()
                ? redirect('/system')->with('success', __('messages.system.impersonation.stopped'))
                : redirect('/login');
        }

        if ($originalGuard === 'web') {
            $originalUser = User::find($originalId);

            if ($originalUser) {
                Auth::guard('web')->login($originalUser);
            }
        } else {
            $originalAdmin = AdminUser::find($originalId);

            if ($originalAdmin) {
                Auth::guard('admin')->login($originalAdmin);
            }
        }

        $request->session()->regenerate();

        return redirect('/system')->with('success', __('messages.system.impersonation.stopped'));
    }

    /**
     * Check if currently impersonating.
     */
    public static function isImpersonating(): bool
    {
        return Session::has('impersonating_from');
    }

    /**
     * Get impersonated tenant ID.
     */
    public static function impersonatedTenantId(): ?int
    {
        $id = Session::get('impersonating_tenant');

        return $id === null ? null : (int) $id;
    }
}
