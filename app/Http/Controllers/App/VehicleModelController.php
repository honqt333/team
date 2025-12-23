<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\VehicleMake;
use App\Models\VehicleModel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class VehicleModelController extends Controller
{
    /**
     * Display a listing of vehicle models.
     */
    public function index(Request $request): Response
    {
        $query = VehicleModel::with(['make', 'updatedBy'])->orderedBySource()->ordered();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name_ar', 'like', "%{$search}%")
                  ->orWhere('name_en', 'like', "%{$search}%");
            });
        }

        if ($request->filled('make_id')) {
            $query->where('make_id', $request->make_id);
        }

        $models = $query->paginate(20)->withQueryString();
        $makes = VehicleMake::active()->ordered()->get();

        return Inertia::render('Settings/System/Index', [
            'models' => $models,
            'makes' => $makes,
            'activeSection' => 'models',
            'filters' => $request->only(['search', 'make_id']),
        ]);
    }

    /**
     * Store a newly created vehicle model.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'make_id' => 'required|exists:vehicle_makes,id',
            'name_ar' => 'required|string|max:255',
            'name_en' => 'nullable|string|max:255',
            'is_active' => 'boolean',
        ]);

        // New items are always center-level
        $validated['source'] = 'center';
        $validated['updated_by'] = auth()->id();

        VehicleModel::create($validated);

        return redirect()->back();
    }

    /**
     * Update the specified vehicle model.
     */
    public function update(Request $request, VehicleModel $model): RedirectResponse
    {
        // Protect system data
        if ($model->source === 'system') {
            abort(403, __('common.cannot_modify_system_data'));
        }

        $validated = $request->validate([
            'make_id' => 'required|exists:vehicle_makes,id',
            'name_ar' => 'required|string|max:255',
            'name_en' => 'nullable|string|max:255',
            'is_active' => 'boolean',
        ]);

        $validated['updated_by'] = auth()->id();
        $model->update($validated);

        return redirect()->back();
    }

    /**
     * Remove the specified vehicle model.
     */
    public function destroy(VehicleModel $model): RedirectResponse
    {
        // Protect system data
        if ($model->source === 'system') {
            abort(403, __('common.cannot_delete_system_data'));
        }

        $model->delete();

        return redirect()->back();
    }

    /**
     * Toggle active status.
     */
    public function toggleActive(VehicleModel $model): RedirectResponse
    {
        // Protect system data
        if ($model->source === 'system') {
            abort(403, __('common.cannot_modify_system_data'));
        }

        $model->update(['is_active' => !$model->is_active]);

        return redirect()->back();
    }
}
