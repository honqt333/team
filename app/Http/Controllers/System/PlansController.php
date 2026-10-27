<?php

declare(strict_types=1);

namespace App\Http\Controllers\System;

use App\Http\Controllers\Controller;
use App\Models\Billing\Plan;
use Illuminate\Http\RedirectResponse;
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
        $plans = Plan::withCount([
            'subscriptions as active_subscribers_count' => function ($q) {
                $q->whereIn('status', ['active', 'trial', 'trialing']);
            },
            'subscriptions as total_subscribers_count',
        ])->orderBy('sort_order')->get();

        return Inertia::render('System/Plans/Index', [
            'plans' => $plans,
        ]);
    }

    /**
     * Show the form for creating a new plan.
     *
     * The plan form is a modal on the plans index page
     * (Pages/System/Plans/Index.vue), which posts to the same
     * store() endpoint. This action used to render a standalone
     * "System/Plans/Form" page that never existed as a Vue file.
     *
     * It had no route registered either, so it was unreachable dead
     * code. The action is kept and pointed at the index page that
     * actually hosts the form, so that adding a route for it later
     * cannot reintroduce a 500.
     */
    public function create(): RedirectResponse
    {
        return redirect()->route('system.plans.index');
    }

    /**
     * Store a newly created plan.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name_ar' => 'required|string|max:255',
            'name_en' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:plans',
            'description_ar' => 'nullable|string',
            'description_en' => 'nullable|string',
            'price_monthly' => 'nullable|numeric|min:0',
            'price_yearly' => 'required|numeric|min:0',
            'trial_days' => 'required|integer|min:0',
            'features_ar' => 'nullable|array',
            'features_en' => 'nullable|array',
            'limits' => 'nullable|array',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
            'sort_order' => 'integer',
        ]);

        $validated['price_monthly'] = $validated['price_monthly'] ?? round(((float) $validated['price_yearly']) / 12, 2);
        $validated['name'] = $validated['name_ar'];
        $validated['features'] = [
            'ar' => array_values(array_filter($request->input('features_ar', []))),
            'en' => array_values(array_filter($request->input('features_en', []))),
        ];

        Plan::create($validated);

        return redirect()->route('system.plans.index')->with('success', 'تم إنشاء الباقة بنجاح');
    }

    /**
     * Show the form for editing the specified plan.
     *
     * Like create(), the edit form is a modal on the index page. See
     * the note on create() for why this no longer renders a
     * standalone page.
     */
    public function edit(Plan $plan): RedirectResponse
    {
        return redirect()->route('system.plans.index');
    }

    /**
     * Update the specified plan.
     */
    public function update(Request $request, Plan $plan)
    {
        $validated = $request->validate([
            'name_ar' => 'required|string|max:255',
            'name_en' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:plans,slug,'.$plan->id,
            'description_ar' => 'nullable|string',
            'description_en' => 'nullable|string',
            'price_monthly' => 'nullable|numeric|min:0',
            'price_yearly' => 'required|numeric|min:0',
            'trial_days' => 'required|integer|min:0',
            'features_ar' => 'nullable|array',
            'features_en' => 'nullable|array',
            'limits' => 'nullable|array',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
            'sort_order' => 'integer',
        ]);

        $validated['price_monthly'] = $validated['price_monthly'] ?? round(((float) $validated['price_yearly']) / 12, 2);
        $validated['name'] = $validated['name_ar'];
        $validated['features'] = [
            'ar' => array_values(array_filter($request->input('features_ar', []))),
            'en' => array_values(array_filter($request->input('features_en', []))),
        ];

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
