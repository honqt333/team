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
        // Find the first admin user of this tenant
        $tenantUser = User::where('tenant_id', $tenant->id)
            ->whereHas('roles', fn ($q) => $q->where('name', 'admin'))
            ->first();

        if (! $tenantUser) {
            // Fallback to any user of this tenant
            $tenantUser = User::where('tenant_id', $tenant->id)->first();
        }

        if (! $tenantUser) {
            return back()->with('error', __('messages.system.impersonation.no_users'));
        }

        // The system admin lives on the 'admin' guard (AdminUser), NOT
        // the default 'web' guard. Reading Auth::id() here used to
        // return the web guard's id, which is null for a system admin —
        // so the original identity was never stored and the admin
        // could not be restored on stop().
        $originalAdmin = Auth::guard('admin')->user();

        if (! $originalAdmin) {
            // Defensive: without an admin identity there is nothing to
            // return to, so refuse to start rather than trapping the
            // operator inside the tenant account.
            abort(403, __('messages.system.impersonation.no_admin'));
        }

        // Store the original ADMIN identity so we can restore it later.
        Session::put('impersonating_from', $originalAdmin->id);
        Session::put('impersonating_tenant', $tenant->id);

        // Audit log: record who started impersonating, when, from which IP.
        // Stored against the original admin so the trail is preserved even
        // after we switch the auth context.
        AdminActivityLog::create([
            'admin_user_id' => $originalAdmin->id,
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

        // Log in as the tenant user on the 'web' guard. The tenant's
        // staff session lives there; the admin session stays intact so
        // stop() can put it back.
        Auth::guard('web')->login($tenantUser);

        return redirect('/dashboard')->with('success', __('messages.system.impersonation.inside_account', ['tenant' => $tenant->trade_name]));
    }

    /**
     * Stop impersonating and return to system admin.
     */
    public function stop(Request $request): RedirectResponse
    {
        $originalAdminId = Session::get('impersonating_from');
        $impersonatedTenantId = Session::get('impersonating_tenant');

        // Audit log BEFORE we lose the impersonation context.
        $currentAdmin = Auth::guard('admin')->user();

        if ($currentAdmin && $impersonatedTenantId) {
            AdminActivityLog::create([
                'admin_user_id' => $currentAdmin->id,
                'action' => 'impersonate_stop',
                'model_type' => Tenant::class,
                'model_id' => $impersonatedTenantId,
                'changes' => [
                    'original_admin_user_id' => $originalAdminId,
                ],
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
        }

        // Drop the tenant staff session — we are leaving the tenant.
        Auth::guard('web')->logout();

        // Clear impersonation session.
        Session::forget('impersonating_from');
        Session::forget('impersonating_tenant');

        if (! $originalAdminId) {
            // Nothing to restore (e.g. the session was lost). The admin
            // guard may still be signed in — if so, go back to /system.
            return Auth::guard('admin')->check()
                ? redirect('/system')->with('success', __('messages.system.impersonation.stopped'))
                : redirect('/login');
        }

        // Restore the original system admin on the 'admin' guard.
        $originalAdmin = AdminUser::find($originalAdminId);

        if ($originalAdmin) {
            Auth::guard('admin')->login($originalAdmin);
        }

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
