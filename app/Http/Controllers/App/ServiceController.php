<?php

namespace App\Http\Controllers\App;

use App\Models\Department;
use App\Models\Service;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ServiceController
{
    use AuthorizesRequests;

    public function index(): Response
    {
        $this->authorize('viewAny', Service::class);

        // Get departments with their services (accordion view)
        $departments = Department::with(['services' => function ($query) {
            $query->ordered();
        }])
            ->withCount('services')
            ->ordered()
            ->get();

        // Get services without department
        $unassignedServices = Service::whereNull('department_id')
            ->ordered()
            ->get();

        return Inertia::render('Services/Index', [
            'departments' => $departments,
            'unassignedServices' => $unassignedServices,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Service::class);

        $centerId = auth()->user()->current_center_id;

        $validated = $request->validate([
            'department_id' => 'required|exists:departments,id',
            'name_ar' => [
                'required',
                'string',
                'max:255',
                Rule::unique('services')->where(fn ($query) => $query->where('center_id', $centerId)),
            ],
            'name_en' => 'nullable|string|max:255',
            'description_ar' => 'nullable|string|max:1000',
            'description_en' => 'nullable|string|max:1000',
            'base_price' => 'required|numeric|min:0',
            'duration_value' => 'nullable|integer|min:1',
            'duration_unit' => 'nullable|in:minutes,hours,days,weeks',
            'warranty_value' => 'nullable|integer|min:1',
            'warranty_unit' => 'nullable|in:days,weeks,months,years',
            'type' => 'required|in:internal,external',
            'requires_approval' => 'boolean',
            'is_active' => 'boolean',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        Service::create($validated);

        return back();
    }

    public function update(Request $request, Service $service): RedirectResponse
    {
        $this->authorize('update', $service);

        $validated = $request->validate([
            'department_id' => 'required|exists:departments,id',
            'name_ar' => [
                'sometimes',
                'required',
                'string',
                'max:255',
                Rule::unique('services')
                    ->where(fn ($query) => $query->where('center_id', $service->center_id))
                    ->ignore($service->id),
            ],
            'name_en' => 'nullable|string|max:255',
            'description_ar' => 'nullable|string|max:1000',
            'description_en' => 'nullable|string|max:1000',
            'base_price' => 'sometimes|required|numeric|min:0',
            'duration_value' => 'nullable|integer|min:1',
            'duration_unit' => 'nullable|in:minutes,hours,days,weeks',
            'warranty_value' => 'nullable|integer|min:1',
            'warranty_unit' => 'nullable|in:days,weeks,months,years',
            'type' => 'sometimes|required|in:internal,external',
            'requires_approval' => 'boolean',
            'is_active' => 'boolean',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $service->update($validated);

        return back();
    }

    public function destroy(Service $service): RedirectResponse
    {
        $this->authorize('delete', $service);

        $service->delete();

        return back();
    }

    public function toggleActive(Service $service): RedirectResponse
    {
        $this->authorize('update', $service);

        $service->update(['is_active' => !$service->is_active]);

        return back();
    }

    /**
     * API endpoint to get all active services for forms
     */
    public function apiList(): JsonResponse
    {
        $this->authorize('viewAny', Service::class);

        $services = Service::with('department')
            ->active()
            ->internal()
            ->ordered()
            ->get()
            ->groupBy('department_id');

        return response()->json($services);
    }
}
