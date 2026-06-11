<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\Center;
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
            'name'         => 'required|string|max:255',
            'name_ar'      => 'nullable|string|max:255',
            'name_en'      => 'nullable|string|max:255',
            'center_type'  => 'required|string|in:main,branch,workshop,warehouse',
            'manager_name' => 'nullable|string|max:255',
            'phone'        => 'nullable|string|max:50',
            'email'        => 'nullable|email|max:255',
            'is_active'    => 'sometimes|boolean',
        ]);

        // Generate unique slug from English name (fallback to name or name_ar)
        $slugSource = $validated['name_en']
            ?? $validated['name_ar']
            ?? $validated['name'];
        $validated['slug'] = Str::slug($slugSource) . '-' . Str::lower(Str::random(6));

        // Normalize is_active (checkbox may send 'true'/'false' or true/false)
        $validated['is_active'] = $request->boolean('is_active', true);

        $user = auth()->user();
        $tenant = $user->tenant;
        $center = $tenant->centers()->create($validated);

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
