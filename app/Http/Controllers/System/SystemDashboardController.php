<?php

namespace App\Http\Controllers\System;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Integration\Integration;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class SystemDashboardController extends Controller
{
    /**
     * Display the system dashboard.
     */
    public function index(): Response
    {
        // Get statistics
        $stats = [
            'total_tenants' => Tenant::count(),
            'active_tenants' => Tenant::where('status', 'active')->count(),
            'trial_tenants' => Tenant::where('status', 'trial')->count(),
            'suspended_tenants' => Tenant::where('status', 'suspended')->count(),
            'total_users' => User::whereNotNull('tenant_id')->count(),
            'system_admins' => User::where('is_system_admin', true)->count(),
        ];
        
        // Recent registrations (last 7 days)
        $recentRegistrations = Tenant::where('created_at', '>=', now()->subDays(7))
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->get(['id', 'name', 'trade_name', 'status', 'created_at']);
        
        // Tenants with trial ending soon (next 7 days)
        $trialEndingSoon = Tenant::where('status', 'trial')
            ->whereNotNull('trial_ends_at')
            ->where('trial_ends_at', '<=', now()->addDays(7))
            ->where('trial_ends_at', '>=', now())
            ->orderBy('trial_ends_at')
            ->take(10)
            ->get(['id', 'name', 'trade_name', 'trial_ends_at']);
        
        // Get active SMS/WhatsApp integrations for balance display
        $integrations = Integration::whereIn('type', ['sms', 'whatsapp'])
            ->where('is_active', true)
            ->get(['id', 'name', 'name_ar', 'provider', 'type']);
            
        return Inertia::render('System/Dashboard', [
            'stats' => $stats,
            'recentRegistrations' => $recentRegistrations,
            'trialEndingSoon' => $trialEndingSoon,
            'integrations' => $integrations,
        ]);
    }
}

