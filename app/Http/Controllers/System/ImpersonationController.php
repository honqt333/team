<?php

declare(strict_types=1);

namespace App\Http\Controllers\System;

use App\Http\Controllers\Controller;
use App\Models\AdminActivityLog;
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
            return back()->with('error', 'لا يوجد مستخدمين لهذا المستأجر');
        }

        $originalId = Auth::id();
        $originalAdmin = Auth::guard('admin')->user();

        // Store original user ID for returning later
        Session::put('impersonating_from', $originalId);
        Session::put('impersonating_tenant', $tenant->id);

        // Audit log: record who started impersonating, when, from which IP.
        // Stored against the original admin so the trail is preserved even
        // after we switch the auth context.
        if ($originalAdmin) {
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
        }

        // Login as the tenant user
        Auth::login($tenantUser);

        return redirect('/dashboard')->with('success', "أنت الآن داخل حساب: {$tenant->trade_name}");
    }

    /**
     * Stop impersonating and return to system admin.
     */
    public function stop(Request $request): RedirectResponse
    {
        $originalUserId = Session::get('impersonating_from');
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
                    'original_user_id' => $originalUserId,
                ],
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
        }

        if (! $originalUserId) {
            Session::forget(['impersonating_from', 'impersonating_tenant']);

            return redirect('/dashboard');
        }

        // Restore original admin
        $originalUser = User::find($originalUserId);

        if ($originalUser) {
            Auth::login($originalUser);
        }

        // Clear impersonation session
        Session::forget('impersonating_from');
        Session::forget('impersonating_tenant');

        return redirect('/system')->with('success', 'تم العودة للوحة النظام');
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
        return Session::get('impersonating_tenant');
    }
}
