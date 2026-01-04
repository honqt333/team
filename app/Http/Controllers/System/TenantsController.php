<?php

namespace App\Http\Controllers\System;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TenantsController extends Controller
{
    /**
     * Display a listing of tenants.
     */
    public function index(Request $request): Response
    {
        $query = Tenant::query()
            ->withCount('centers', 'users');
        
        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        
        // Search by name
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('trade_name', 'like', "%{$search}%")
                  ->orWhere('legal_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }
        
        $tenants = $query->orderBy('created_at', 'desc')
            ->paginate(20)
            ->withQueryString();
        
        return Inertia::render('System/Tenants/Index', [
            'tenants' => $tenants,
            'filters' => $request->only(['status', 'search']),
        ]);
    }
    
    /**
     * Display the specified tenant.
     */
    public function show(Tenant $tenant): Response
    {
        $tenant->load(['centers', 'users']);
        
        return Inertia::render('System/Tenants/Show', [
            'tenant' => $tenant,
        ]);
    }
    
    /**
     * Suspend a tenant.
     */
    public function suspend(Request $request, Tenant $tenant)
    {
        $request->validate([
            'reason' => 'required|string|max:500',
        ]);
        
        $tenant->update([
            'status' => 'suspended',
            'suspended_at' => now(),
            'suspension_reason' => $request->reason,
        ]);
        
        return back()->with('success', 'تم تعليق المستأجر بنجاح');
    }
    
    /**
     * Activate a tenant.
     */
    public function activate(Tenant $tenant)
    {
        $tenant->update([
            'status' => 'active',
            'suspended_at' => null,
            'suspension_reason' => null,
        ]);
        
        return back()->with('success', 'تم تفعيل المستأجر بنجاح');
    }
    
    /**
     * Extend trial period.
     */
    public function extendTrial(Request $request, Tenant $tenant)
    {
        $request->validate([
            'days' => 'required|integer|min:1|max:90',
        ]);
        
        $newEndDate = $tenant->trial_ends_at 
            ? $tenant->trial_ends_at->addDays($request->days)
            : now()->addDays($request->days);
        
        $tenant->update([
            'trial_ends_at' => $newEndDate,
            'status' => 'trial',
        ]);
        
        return back()->with('success', "تم تمديد الفترة التجريبية {$request->days} يوم");
    }
    
    /**
     * Hard delete a tenant and all related data.
     */
    public function destroy(Request $request, Tenant $tenant)
    {
        $request->validate([
            'confirmation' => 'required|string',
        ]);
        
        // Verify confirmation matches tenant name
        if ($request->confirmation !== $tenant->name) {
            return back()->with('error', 'اسم المستأجر غير متطابق');
        }
        
        // Delete all related data (CASCADE should handle most, but be explicit)
        // Users
        $tenant->users()->forceDelete();
        
        // Centers and their related data
        foreach ($tenant->centers as $center) {
            // Delete center-related data here if needed
            $center->forceDelete();
        }
        
        // Finally delete the tenant
        $tenant->forceDelete();
        
        return redirect()->route('system.tenants.index')->with('success', 'تم حذف المستأجر وجميع بياناته نهائياً');
    }
}
