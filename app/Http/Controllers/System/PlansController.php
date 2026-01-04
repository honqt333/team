<?php

namespace App\Http\Controllers\System;

use App\Http\Controllers\Controller;
use App\Models\Billing\Plan;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PlansController extends Controller
{
    /**
     * Display a listing of plans.
     */
    public function index(): Response
    {
        $plans = Plan::orderBy('sort_order')->get();
        
        return Inertia::render('System/Plans/Index', [
            'plans' => $plans,
        ]);
    }
    
    /**
     * Show the form for creating a new plan.
     */
    public function create(): Response
    {
        return Inertia::render('System/Plans/Form', [
            'plan' => null,
        ]);
    }
    
    /**
     * Store a newly created plan.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'name_ar' => 'required|string|max:255',
            'name_en' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:plans',
            'description_ar' => 'nullable|string',
            'description_en' => 'nullable|string',
            'price_monthly' => 'required|numeric|min:0',
            'price_yearly' => 'required|numeric|min:0',
            'trial_days' => 'required|integer|min:0',
            'features' => 'nullable|array',
            'limits' => 'nullable|array',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
            'sort_order' => 'integer',
        ]);
        
        $validated['name'] = $validated['name_ar'];
        
        Plan::create($validated);
        
        return redirect()->route('system.plans.index')->with('success', 'تم إنشاء الباقة بنجاح');
    }
    
    /**
     * Show the form for editing the specified plan.
     */
    public function edit(Plan $plan): Response
    {
        return Inertia::render('System/Plans/Form', [
            'plan' => $plan,
        ]);
    }
    
    /**
     * Update the specified plan.
     */
    public function update(Request $request, Plan $plan)
    {
        $validated = $request->validate([
            'name_ar' => 'required|string|max:255',
            'name_en' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:plans,slug,' . $plan->id,
            'description_ar' => 'nullable|string',
            'description_en' => 'nullable|string',
            'price_monthly' => 'required|numeric|min:0',
            'price_yearly' => 'required|numeric|min:0',
            'trial_days' => 'required|integer|min:0',
            'features' => 'nullable|array',
            'limits' => 'nullable|array',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
            'sort_order' => 'integer',
        ]);
        
        $validated['name'] = $validated['name_ar'];
        
        $plan->update($validated);
        
        return redirect()->route('system.plans.index')->with('success', 'تم تحديث الباقة بنجاح');
    }
    
    /**
     * Remove the specified plan.
     */
    public function destroy(Plan $plan)
    {
        // Check if plan has active subscriptions
        if ($plan->subscriptions()->whereIn('status', ['active', 'trialing'])->exists()) {
            return back()->with('error', 'لا يمكن حذف باقة لها اشتراكات نشطة');
        }
        
        $plan->delete();
        
        return redirect()->route('system.plans.index')->with('success', 'تم حذف الباقة');
    }
}
