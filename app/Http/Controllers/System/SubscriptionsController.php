<?php

namespace App\Http\Controllers\System;

use App\Http\Controllers\Controller;
use App\Models\Billing\Plan;
use App\Models\Billing\Subscription;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SubscriptionsController extends Controller
{
    /**
     * Display a listing of subscriptions.
     */
    public function index(Request $request): Response
    {
        $query = Subscription::with(['tenant', 'plan'])
            ->orderBy('created_at', 'desc');
        
        // Filter by status
        if ($request->status && $request->status !== 'all') {
            $query->where('status', $request->status);
        }
        
        // Filter by plan
        if ($request->plan_id) {
            $query->where('plan_id', $request->plan_id);
        }
        
        // Search
        if ($request->search) {
            $search = $request->search;
            $query->whereHas('tenant', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('trade_name', 'like', "%{$search}%");
            });
        }
        
        $subscriptions = $query->paginate(20)->withQueryString();
        
        return Inertia::render('System/Subscriptions/Index', [
            'subscriptions' => $subscriptions,
            'plans' => Plan::where('is_active', true)->get(['id', 'name_ar', 'name_en', 'price_monthly', 'price_yearly', 'trial_days']),
            'filters' => $request->only(['search', 'status', 'plan_id']),
            'stats' => [
                'total' => Subscription::count(),
                'active' => Subscription::where('status', 'active')->count(),
                'trial' => Subscription::where('status', 'trial')->count(),
                'cancelled' => Subscription::where('status', 'cancelled')->count(),
                'expired' => Subscription::where('status', 'expired')->count(),
            ],
            'eligibleTenants' => Tenant::whereDoesntHave('subscriptions', function ($q) {
                $q->whereIn('status', ['active', 'trial']);
            })->get(['id', 'name', 'trade_name']),
        ]);
    }
    
    /**
     * Display the specified subscription.
     */
    public function show(Subscription $subscription): Response
    {
        $subscription->load(['tenant', 'plan', 'invoices']);
        
        return Inertia::render('System/Subscriptions/Show', [
            'subscription' => $subscription,
        ]);
    }
    
    /**
     * Store a newly created subscription.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'tenant_id' => 'required|exists:tenants,id',
            'plan_id' => 'required|exists:plans,id',
            'billing_cycle' => 'required|in:monthly,yearly',
            'promo_code' => 'nullable|string|exists:promo_codes,code',
        ]);
        
        $plan = Plan::findOrFail($validated['plan_id']);
        $price = $validated['billing_cycle'] === 'yearly' ? $plan->price_yearly : $plan->price_monthly;
        
        // Calculate dates
        $startDate = now();
        $endDate = $validated['billing_cycle'] === 'yearly' 
            ? $startDate->copy()->addYear() 
            : $startDate->copy()->addMonth();
        
        // Check if trial
        $status = $plan->trial_days > 0 ? 'trialing' : 'active';
        $trialEndsAt = $plan->trial_days > 0 ? $startDate->copy()->addDays($plan->trial_days) : null;
        
        $subscription = Subscription::create([
            'tenant_id' => $validated['tenant_id'],
            'plan_id' => $validated['plan_id'],
            'status' => $status,
            'billing_cycle' => $validated['billing_cycle'],
            'starts_at' => $startDate,
            'ends_at' => $endDate,
            'trial_ends_at' => $trialEndsAt,
            'price' => $price,
            'auto_renew' => true,
            'promo_code' => $validated['promo_code'] ?? null,
        ]);
        
        // Update tenant status
        $subscription->tenant->update([
            'status' => $status === 'trialing' ? 'trial' : 'active',
            'trial_ends_at' => $trialEndsAt,
        ]);
        
        return redirect()->route('system.subscriptions.index')
            ->with('success', 'تم إنشاء الاشتراك بنجاح');
    }
    
    /**
     * Cancel subscription.
     */
    public function cancel(Subscription $subscription)
    {
        $subscription->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
        ]);
        
        $subscription->tenant->update(['status' => 'suspended']);
        
        return back()->with('success', 'تم إلغاء الاشتراك');
    }
    
    /**
     * Activate subscription.
     */
    public function activate(Subscription $subscription)
    {
        $subscription->update([
            'status' => 'active',
            'cancelled_at' => null,
        ]);
        
        $subscription->tenant->update(['status' => 'active']);
        
        return back()->with('success', 'تم تفعيل الاشتراك');
    }
    
    /**
     * Extend subscription.
     */
    public function extend(Request $request, Subscription $subscription)
    {
        $validated = $request->validate([
            'days' => 'required|integer|min:1|max:365',
        ]);
        
        $subscription->update([
            'ends_at' => $subscription->ends_at->addDays($validated['days']),
        ]);
        
        return back()->with('success', "تم تمديد الاشتراك {$validated['days']} يوم");
    }
}
