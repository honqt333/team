<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\VehicleMake;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Support\Facades\Storage;

class VehicleMakeController extends Controller
{
    /**
     * Display a listing of vehicle makes.
     */
    public function index(Request $request): Response
    {
        $query = VehicleMake::with('updatedBy')->orderedBySource()->ordered();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name_ar', 'like', "%{$search}%")
                  ->orWhere('name_en', 'like', "%{$search}%");
            });
        }

        $makes = $query->paginate(20)->withQueryString();

        return Inertia::render('Settings/System/Index', [
            'makes' => $makes,
            'activeSection' => 'makes',
            'filters' => $request->only('search'),
        ]);
    }

    /**
     * Store a newly created vehicle make.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name_ar' => 'required|string|max:255',
            'name_en' => 'nullable|string|max:255',
            'is_active' => 'boolean',
            'logo' => 'nullable|image|max:2048',
        ]);

        if ($request->hasFile('logo')) {
            $validated['logo_path'] = $request->file('logo')->store('makes', 'public');
        }

        // New items are always center-level
        $validated['source'] = 'center';
        $validated['updated_by'] = auth()->id();

        VehicleMake::create($validated);

        return redirect()->back();
    }

    /**
     * Update the specified vehicle make.
     */
    public function update(Request $request, VehicleMake $make): RedirectResponse
    {
        // Protect system data
        if ($make->source === 'system') {
            abort(403, __('common.cannot_modify_system_data'));
        }

        $validated = $request->validate([
            'name_ar' => 'required|string|max:255',
            'name_en' => 'nullable|string|max:255',
            'is_active' => 'boolean',
            'logo' => 'nullable|image|max:2048',
        ]);

        if ($request->hasFile('logo')) {
            if ($make->logo_path) {
                Storage::disk('public')->delete($make->logo_path);
            }
            $validated['logo_path'] = $request->file('logo')->store('makes', 'public');
        }

        $validated['updated_by'] = auth()->id();
        $make->update($validated);

        return redirect()->back();
    }

    /**
     * Remove the specified vehicle make.
     */
    public function destroy(VehicleMake $make): RedirectResponse
    {
        // Protect system data
        if ($make->source === 'system') {
            abort(403, __('common.cannot_delete_system_data'));
        }

        $make->delete();

        return redirect()->back();
    }

    /**
     * Toggle active status.
     */
    public function toggleActive(VehicleMake $make): RedirectResponse
    {
        // Protect system data
        if ($make->source === 'system') {
            abort(403, __('common.cannot_modify_system_data'));
        }

        $make->update(['is_active' => !$make->is_active]);

        return redirect()->back();
    }
}
