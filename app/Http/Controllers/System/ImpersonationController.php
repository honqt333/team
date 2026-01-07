<?php

namespace App\Http\Controllers\System;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class ImpersonationController extends Controller
{
    /**
     * Start impersonating a tenant (login as their admin user).
     */
    public function start(Tenant $tenant): RedirectResponse
    {
        // Find the first admin user of this tenant
        $tenantUser = User::where('tenant_id', $tenant->id)
            ->whereHas('roles', fn($q) => $q->where('name', 'admin'))
            ->first();
        
        if (!$tenantUser) {
            // Fallback to any user of this tenant
            $tenantUser = User::where('tenant_id', $tenant->id)->first();
        }
        
        if (!$tenantUser) {
            return back()->with('error', 'لا يوجد مستخدمين لهذا المستأجر');
        }
        
        // Store original user ID for returning later
        Session::put('impersonating_from', Auth::id());
        Session::put('impersonating_tenant', $tenant->id);
        
        // Login as the tenant user
        Auth::login($tenantUser);
        
        return redirect('/dashboard')->with('success', "أنت الآن داخل حساب: {$tenant->trade_name}");
    }
    
    /**
     * Stop impersonating and return to system admin.
     */
    public function stop(): RedirectResponse
    {
        $originalUserId = Session::get('impersonating_from');
        
        if (!$originalUserId) {
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
