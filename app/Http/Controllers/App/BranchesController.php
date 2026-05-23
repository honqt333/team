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
            'name_ar'      => 'required|string|max:255',
            'name_en'      => 'required|string|max:255',
            'center_type'  => 'required|string|max:50',
            'manager_name' => 'nullable|string|max:255',
            'phone'        => 'nullable|string|max:50',
            'email'        => 'nullable|email|max:255',
            'is_active'    => 'boolean',
        ]);

        $validated['slug'] = Str::slug($validated['name']) . '-' . uniqid();
        $validated['is_active'] = $request->input('is_active', true);

        $user = auth()->user();
        $tenant = $user->tenant;
        $center = $tenant->centers()->create($validated);

        // Attach the current user (company manager) to the new center
        // so they can immediately switch to and access it
        $user->centers()->syncWithoutDetaching([
            $center->id => ['tenant_id' => $tenant->id],
        ]);

        return redirect()->back()->with('success', __('company_profile.branches.created_successfully'));
    }
}
