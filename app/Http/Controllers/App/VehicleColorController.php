<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\VehicleColor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class VehicleColorController extends Controller
{
    /**
     * Display a listing of vehicle colors.
     */
    public function index(Request $request): Response
    {
        $query = VehicleColor::with('updatedBy')->orderedBySource()->ordered();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name_ar', 'like', "%{$search}%")
                  ->orWhere('name_en', 'like', "%{$search}%");
            });
        }

        $colors = $query->paginate(20)->withQueryString();

        return Inertia::render('Settings/System/Index', [
            'colors' => $colors,
            'activeSection' => 'colors',
            'filters' => $request->only('search'),
        ]);
    }

    /**
     * Store a newly created vehicle color.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name_ar' => 'required|string|max:255',
            'name_en' => 'nullable|string|max:255',
            'hex_code' => 'nullable|string|max:7|regex:/^#[0-9A-Fa-f]{6}$/',
            'is_active' => 'boolean',
        ]);

        // New items are always center-level
        $validated['source'] = 'center';
        $validated['updated_by'] = auth()->id();

        VehicleColor::create($validated);

        return redirect()->back();
    }

    /**
     * Update the specified vehicle color.
     */
    public function update(Request $request, VehicleColor $color): RedirectResponse
    {
        // Protect system data
        if ($color->source === 'system') {
            abort(403, __('common.cannot_modify_system_data'));
        }

        $validated = $request->validate([
            'name_ar' => 'required|string|max:255',
            'name_en' => 'nullable|string|max:255',
            'hex_code' => 'nullable|string|max:7|regex:/^#[0-9A-Fa-f]{6}$/',
            'is_active' => 'boolean',
        ]);

        $validated['updated_by'] = auth()->id();
        $color->update($validated);

        return redirect()->back();
    }

    /**
     * Remove the specified vehicle color.
     */
    public function destroy(VehicleColor $color): RedirectResponse
    {
        // Protect system data
        if ($color->source === 'system') {
            abort(403, __('common.cannot_delete_system_data'));
        }

        $color->delete();

        return redirect()->back();
    }

    /**
     * Toggle active status.
     */
    public function toggleActive(VehicleColor $color): RedirectResponse
    {
        // Protect system data
        if ($color->source === 'system') {
            abort(403, __('common.cannot_modify_system_data'));
        }

        $color->update(['is_active' => !$color->is_active]);

        return redirect()->back();
    }
}
