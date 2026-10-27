<?php

declare(strict_types=1);

namespace App\Http\Controllers\System;

use App\Http\Controllers\Controller;
use App\Models\Billing\Plan;
use App\Models\Billing\PromoCode;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PromoCodesController extends Controller
{
    /**
     * Display a listing of promo codes.
     */
    public function index(): Response
    {
        $promoCodes = PromoCode::with(['plan', 'usages.tenant'])
            ->withCount('usages')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn ($code) => [
                ...$code->toArray(),
                'is_valid' => $code->isValid(),
                'discount_description' => $code->discount_description,
                'total_discount_given' => $code->usages->sum('discount_amount'),
            ]);

        return Inertia::render('System/PromoCodes/Index', [
            'promoCodes' => $promoCodes,
            'plans' => Plan::where('is_active', true)->get(['id', 'name_ar', 'name_en']),
        ]);
    }

    /**
     * Show the form for creating a new promo code.
     *
     * The promo code form is a modal on the index page
     * (Pages/System/PromoCodes/Index.vue), which posts to the same
     * store() endpoint. This action used to render a standalone
     * "System/PromoCodes/Form" page that never existed as a Vue
     * file, and it had no route registered — unreachable dead code.
     *
     * It now redirects to the index page that actually hosts the
     * form, so adding a route for it later cannot reintroduce a 500.
     */
    public function create(): RedirectResponse
    {
        return redirect()->route('system.promo-codes.index');
    }

    /**
     * Store a newly created promo code.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:50|unique:promo_codes',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'discount_type' => 'required|in:percentage,fixed,trial_days',
            'discount_value' => 'required|numeric|min:0',
            'max_uses' => 'nullable|integer|min:1',
            'max_uses_per_tenant' => 'required|integer|min:1',
            'starts_at' => 'nullable|date',
            'expires_at' => 'nullable|date|after:starts_at',
            'plan_id' => 'nullable|exists:plans,id',
            'billing_cycle' => 'required|in:any,monthly,yearly',
            'first_subscription_only' => 'boolean',
            'is_active' => 'boolean',
        ]);

        // Validate percentage max 100
        if ($validated['discount_type'] === 'percentage' && $validated['discount_value'] > 100) {
            return back()->withErrors(['discount_value' => 'النسبة يجب أن تكون بين 0 و 100']);
        }

        PromoCode::create($validated);

        return redirect()->route('system.promo-codes.index')->with('success', 'تم إنشاء الرمز الترويجي بنجاح');
    }

    /**
     * Show the form for editing the specified promo code.
     *
     * Like create(), the edit form is a modal on the index page. See
     * the note on create() for why this no longer renders a
     * standalone page.
     */
    public function edit(PromoCode $promoCode): RedirectResponse
    {
        return redirect()->route('system.promo-codes.index');
    }

    /**
     * Update the specified promo code.
     */
    public function update(Request $request, PromoCode $promoCode)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:50|unique:promo_codes,code,'.$promoCode->id,
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'discount_type' => 'required|in:percentage,fixed,trial_days',
            'discount_value' => 'required|numeric|min:0',
            'max_uses' => 'nullable|integer|min:1',
            'max_uses_per_tenant' => 'required|integer|min:1',
            'starts_at' => 'nullable|date',
            'expires_at' => 'nullable|date|after:starts_at',
            'plan_id' => 'nullable|exists:plans,id',
            'billing_cycle' => 'required|in:any,monthly,yearly',
            'first_subscription_only' => 'boolean',
            'is_active' => 'boolean',
        ]);

        if ($validated['discount_type'] === 'percentage' && $validated['discount_value'] > 100) {
            return back()->withErrors(['discount_value' => 'النسبة يجب أن تكون بين 0 و 100']);
        }

        $promoCode->update($validated);

        return redirect()->route('system.promo-codes.index')->with('success', 'تم تحديث الرمز الترويجي');
    }

    /**
     * Remove the specified promo code.
     */
    public function destroy(PromoCode $promoCode)
    {
        $promoCode->delete();

        return redirect()->route('system.promo-codes.index')->with('success', 'تم حذف الرمز الترويجي');
    }
}
