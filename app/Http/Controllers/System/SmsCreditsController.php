<?php

namespace App\Http\Controllers\System;

use App\Http\Controllers\Controller;
use App\Models\Credits\SmsPackage;
use App\Models\Credits\SmsPurchase;
use App\Models\Credits\TenantSmsBalance;
use App\Models\Credits\SmsUsageLog;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SmsCreditsController extends Controller
{
    /**
     * List SMS packages.
     */
    public function packages(): Response
    {
        $packages = SmsPackage::orderBy('sort_order')->get();
        
        return Inertia::render('System/Credits/SmsPackages', [
            'packages' => $packages,
        ]);
    }

    /**
     * Store new package.
     */
    public function storePackage(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'name_ar' => 'required|string|max:255',
            'credits' => 'required|integer|min:1',
            'price' => 'required|numeric|min:0',
            'is_active' => 'boolean',
            'is_popular' => 'boolean',
            'sort_order' => 'integer',
        ]);

        SmsPackage::create($validated);

        return back()->with('success', 'تم إنشاء الباقة بنجاح');
    }

    /**
     * Update package.
     */
    public function updatePackage(Request $request, SmsPackage $package)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'name_ar' => 'required|string|max:255',
            'credits' => 'required|integer|min:1',
            'price' => 'required|numeric|min:0',
            'is_active' => 'boolean',
            'is_popular' => 'boolean',
            'sort_order' => 'integer',
        ]);

        $package->update($validated);

        return back()->with('success', 'تم تحديث الباقة بنجاح');
    }

    /**
     * Delete package.
     */
    public function destroyPackage(SmsPackage $package)
    {
        $package->delete();
        return back()->with('success', 'تم حذف الباقة بنجاح');
    }

    /**
     * List tenant balances.
     */
    public function balances(Request $request): Response
    {
        $query = TenantSmsBalance::with('tenant');
        
        if ($request->search) {
            $search = $request->search;
            $query->whereHas('tenant', fn($q) => $q->where('trade_name', 'like', "%{$search}%"));
        }
        
        $balances = $query->orderByDesc('balance')->paginate(20)->withQueryString();
        
        // Stats
        $stats = [
            'total_purchased' => TenantSmsBalance::sum('total_purchased'),
            'total_used' => TenantSmsBalance::sum('total_used'),
            'total_balance' => TenantSmsBalance::sum('balance'),
            'total_revenue' => SmsPurchase::where('status', 'paid')->sum('amount'),
        ];

        return Inertia::render('System/Credits/SmsBalances', [
            'balances' => $balances,
            'stats' => $stats,
            'filters' => $request->only(['search']),
        ]);
    }

    /**
     * List purchases.
     */
    public function purchases(Request $request): Response
    {
        $query = SmsPurchase::with(['tenant', 'package']);
        
        if ($request->status) {
            $query->where('status', $request->status);
        }
        
        $purchases = $query->latest()->paginate(20)->withQueryString();

        return Inertia::render('System/Credits/SmsPurchases', [
            'purchases' => $purchases,
            'filters' => $request->only(['status']),
        ]);
    }

    /**
     * Add credits manually.
     */
    public function addCredits(Request $request)
    {
        $validated = $request->validate([
            'tenant_id' => 'required|exists:tenants,id',
            'credits' => 'required|integer|min:1',
            'reason' => 'nullable|string|max:500',
        ]);

        $balance = TenantSmsBalance::getOrCreate($validated['tenant_id']);
        $balance->addCredits($validated['credits']);

        // Log as purchase (free/manual)
        SmsPurchase::create([
            'tenant_id' => $validated['tenant_id'],
            'credits' => $validated['credits'],
            'amount' => 0,
            'payment_gateway' => 'manual',
            'payment_reference' => 'MANUAL-' . time(),
            'status' => 'paid',
            'payment_details' => ['reason' => $validated['reason'] ?? 'إضافة يدوية'],
        ]);

        return back()->with('success', "تم إضافة {$validated['credits']} رصيد بنجاح");
    }

    /**
     * Usage statistics.
     */
    public function usage(Request $request): Response
    {
        $query = SmsUsageLog::with(['tenant', 'center']);
        
        if ($request->tenant_id) {
            $query->where('tenant_id', $request->tenant_id);
        }
        
        if ($request->status) {
            $query->where('status', $request->status);
        }
        
        $logs = $query->latest()->paginate(50)->withQueryString();
        
        // Stats
        $stats = [
            'total_sent' => SmsUsageLog::count(),
            'delivered' => SmsUsageLog::where('status', 'delivered')->count(),
            'failed' => SmsUsageLog::where('status', 'failed')->count(),
            'total_credits_used' => SmsUsageLog::sum('credits_used'),
        ];

        return Inertia::render('System/Credits/SmsUsage', [
            'logs' => $logs,
            'stats' => $stats,
            'filters' => $request->only(['tenant_id', 'status']),
        ]);
    }
}
