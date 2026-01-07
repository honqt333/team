<?php

namespace App\Http\Controllers\System;

use App\Http\Controllers\Controller;
use App\Models\Credits\WhatsappPackage;
use App\Models\Credits\WhatsappPurchase;
use App\Models\Credits\TenantWhatsappBalance;
use App\Models\Credits\WhatsappUsageLog;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class WhatsappCreditsController extends Controller
{
    public function packages(): Response
    {
        $packages = WhatsappPackage::orderBy('sort_order')->get();
        
        return Inertia::render('System/Credits/WhatsappPackages', [
            'packages' => $packages,
        ]);
    }

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

        WhatsappPackage::create($validated);

        return back()->with('success', 'تم إنشاء الباقة بنجاح');
    }

    public function updatePackage(Request $request, WhatsappPackage $package)
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

    public function destroyPackage(WhatsappPackage $package)
    {
        $package->delete();
        return back()->with('success', 'تم حذف الباقة بنجاح');
    }

    public function balances(Request $request): Response
    {
        $query = TenantWhatsappBalance::with('tenant');
        
        if ($request->search) {
            $search = $request->search;
            $query->whereHas('tenant', fn($q) => $q->where('trade_name', 'like', "%{$search}%"));
        }
        
        $balances = $query->orderByDesc('balance')->paginate(20)->withQueryString();
        
        $stats = [
            'total_purchased' => TenantWhatsappBalance::sum('total_purchased'),
            'total_used' => TenantWhatsappBalance::sum('total_used'),
            'total_balance' => TenantWhatsappBalance::sum('balance'),
            'total_revenue' => WhatsappPurchase::where('status', 'paid')->sum('amount'),
        ];

        return Inertia::render('System/Credits/WhatsappBalances', [
            'balances' => $balances,
            'stats' => $stats,
            'filters' => $request->only(['search']),
        ]);
    }

    public function addCredits(Request $request)
    {
        $validated = $request->validate([
            'tenant_id' => 'required|exists:tenants,id',
            'credits' => 'required|integer|min:1',
            'reason' => 'nullable|string|max:500',
        ]);

        $balance = TenantWhatsappBalance::getOrCreate($validated['tenant_id']);
        $balance->addCredits($validated['credits']);

        WhatsappPurchase::create([
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

    public function usage(Request $request): Response
    {
        $query = WhatsappUsageLog::with(['tenant', 'center']);
        
        if ($request->status) {
            $query->where('status', $request->status);
        }
        
        $logs = $query->latest()->paginate(50)->withQueryString();
        
        $stats = [
            'total_sent' => WhatsappUsageLog::count(),
            'delivered' => WhatsappUsageLog::where('status', 'delivered')->count(),
            'read' => WhatsappUsageLog::where('status', 'read')->count(),
            'failed' => WhatsappUsageLog::where('status', 'failed')->count(),
        ];

        return Inertia::render('System/Credits/WhatsappUsage', [
            'logs' => $logs,
            'stats' => $stats,
            'filters' => $request->only(['status']),
        ]);
    }
}
