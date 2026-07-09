<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\Center;
use App\Support\CenterTypeGuard;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class BranchesController extends Controller
{
    /**
     * Display branches/centers list.
     */
    public function index(): Response
    {
        $this->authorize('viewAny', Center::class);
        $tenant = auth()->user()->tenant;
        $branches = $tenant->centers()
            ->select('id', 'name', 'center_type', 'manager_name', 'phone', 'email', 'slug', 'is_active', 'created_at', 'logo_light_path', 'logo_dark_path')
            ->orderBy('id')
            ->get();

        // Append virtual attributes for logo URLs
        $branches->each->setAppends(['logo_light_url', 'logo_dark_url']);

        return Inertia::render('Settings/Branches/Index', [
            'branches' => $branches,
        ]);
    }

    /**
     * Store a newly created branch.
     */
    public function store(Request $request)
    {
        $this->authorize('create', Center::class);

        $validated = $request->validate([
            'name_ar' => 'required_without:name_en|nullable|string|max:255',
            'name_en' => 'required_without:name_ar|nullable|string|max:255',
            'center_type' => 'required|string|in:main,branch,workshop,warehouse',
            'manager_name' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'is_active' => 'sometimes|boolean',
        ]);

        // Derive the legacy `name` column from whichever localized name the
        // user provided. The DB column is NOT NULL but we don't expose it in
        // the form anymore — the form only has name_ar / name_en.
        $localizedName = $validated['name_ar']
            ?? $validated['name_en']
            ?? 'فرع بدون اسم';
        $validated['name'] = $localizedName;

        // Generate unique slug from English name (fallback to Arabic)
        $slugSource = $validated['name_en'] ?? $validated['name_ar'] ?? $localizedName;
        $validated['slug'] = Str::slug($slugSource).'-'.Str::lower(Str::random(6));

        // Normalize is_active (checkbox may send 'true'/'false' or true/false)
        $validated['is_active'] = $request->boolean('is_active', true);

        $user = auth()->user();
        $tenant = $user->tenant;

        // Check subscription limits for centers/branches
        $activeSubscription = $tenant->subscriptions()
            ->whereIn('status', ['active', 'trial', 'trialing'])
            ->with('plan')
            ->first();

        $limits = $activeSubscription?->plan?->limits ?? ['centers' => 1];
        $maxCenters = intval($limits['max_centers'] ?? $limits['centers'] ?? 1);
        $currentCentersCount = $tenant->centers()->count();

        if ($currentCentersCount >= $maxCenters) {
            $msg = app()->getLocale() === 'en'
                ? "You have reached the maximum limit of branches for your plan ({$maxCenters}). Please upgrade to add more."
                : "لقد وصلت للحد الأقصى المسموح به للفروع في باقتك الحالية ({$maxCenters} فرع). يرجى الترقية لإضافة المزيد.";

            return back()->with('error', $msg);
        }

        // Determine if this new center is being designated as main.
        // We do this BEFORE create so the flag is set correctly in one
        // transaction, and so the rule demotes any previous main center
        // atomically with the new center's insertion.
        $wantsMain = ($validated['center_type'] ?? null) === 'main';
        $validated['is_main'] = $wantsMain;

        $center = $tenant->centers()->create($validated);

        // Apply "single main center" rule: if the new center is being
        // designated as main, transfer that designation from any other
        // center that currently holds it.
        if ($wantsMain) {
            CenterTypeGuard::applyMainRule($center, $validated);
        }

        // Attach the current user (company manager) to the new center
        // so they can immediately switch to and access it
        $user->centers()->syncWithoutDetaching([
            $center->id => ['tenant_id' => $tenant->id],
        ]);

        return redirect()
            ->route('settings.branches')
            ->with('success', __('company_profile.branches.created_successfully'));
    }
}
