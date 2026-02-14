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
        // Exclude packages from regular departments view
        $departments = Department::with(['services' => function ($query) {
            $query->where('type', '!=', Service::TYPE_PACKAGE)
                ->with('updater:id,name')
                ->ordered();
        }])
            ->withCount('services')
            ->ordered()
            ->get();

        // Get services without department
        // Exclude packages from unassigned services
        $unassignedServices = Service::with('updater:id,name')
            ->whereNull('department_id')
            ->where('type', '!=', Service::TYPE_PACKAGE)
            ->ordered()
            ->get();

        // Get packages
        $packages = Service::with(['updater:id,name', 'items'])
            ->where('type', Service::TYPE_PACKAGE)
            ->ordered()
            ->get();

        \Illuminate\Support\Facades\Log::info('Packages found in index:', ['count' => $packages->count(), 'sample' => $packages->take(1)->toArray()]);

        return Inertia::render('Services/Index', [
            'departments' => $departments,
            'unassignedServices' => $unassignedServices,
            'packages' => $packages,
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
            'name_en' => 'required|string|max:255',
            'description_ar' => 'nullable|string|max:1000',
            'description_en' => 'nullable|string|max:1000',
            'base_price' => 'required|numeric|min:0',
            'min_price' => 'nullable|numeric|min:0|lte:base_price',
            'default_discount_type' => 'nullable|in:none,percentage,fixed',
            'default_discount_value' => 'nullable|numeric|min:0',
            'allow_price_override' => 'boolean',
            'duration_value' => 'nullable|integer|min:1',
            'duration_unit' => 'nullable|in:minutes,hours,days,weeks',
            'warranty_value' => 'nullable|integer|min:1',
            'warranty_unit' => 'nullable|in:days,weeks,months,years',
            'warranty_unit' => 'nullable|in:days,weeks,months,years',
            'type' => 'required|in:internal,external,package',
            'items' => 'required_if:type,package|array|min:1',
            'items.*.id' => 'required|exists:services,id',
            'items.*.quantity' => 'required|integer|min:1',
            'requires_approval' => 'boolean',
            'is_active' => 'boolean',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $validated['tenant_id'] = auth()->user()->tenant_id;
        $validated['center_id'] = $centerId;
        
        \Illuminate\Support\Facades\Log::info('Creating service/package', $validated);

        $service = Service::create($validated);
        
        \Illuminate\Support\Facades\Log::info('Service created', ['id' => $service->id, 'type' => $service->type]);

        if ($validated['type'] === Service::TYPE_PACKAGE && !empty($validated['items'])) {
            $items = collect($validated['items'])->mapWithKeys(function ($item) {
                return [$item['id'] => ['quantity' => $item['quantity']]];
            });
            $service->items()->sync($items);
            \Illuminate\Support\Facades\Log::info('Package items synced', ['items' => $items]);
        }

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
            'name_en' => 'required|string|max:255',
            'description_ar' => 'nullable|string|max:1000',
            'description_en' => 'nullable|string|max:1000',
            'base_price' => 'sometimes|required|numeric|min:0',
            'min_price' => 'nullable|numeric|min:0|lte:base_price',
            'default_discount_type' => 'nullable|in:none,percentage,fixed',
            'default_discount_value' => 'nullable|numeric|min:0',
            'allow_price_override' => 'boolean',
            'duration_value' => 'nullable|integer|min:1',
            'duration_unit' => 'nullable|in:minutes,hours,days,weeks',
            'warranty_value' => 'nullable|integer|min:1',
            'warranty_unit' => 'nullable|in:days,weeks,months,years',
            'warranty_unit' => 'nullable|in:days,weeks,months,years',
            'type' => 'sometimes|required|in:internal,external,package',
            'items' => 'required_if:type,package|array|min:1',
            'items.*.id' => 'required|exists:services,id',
            'items.*.quantity' => 'required|integer|min:1',
            'requires_approval' => 'boolean',
            'is_active' => 'boolean',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $validated['updated_by'] = auth()->id();
        $service->update($validated);

        if (($validated['type'] ?? $service->type) === Service::TYPE_PACKAGE) {
            if (isset($validated['items'])) {
                $items = collect($validated['items'])->mapWithKeys(function ($item) {
                    return [$item['id'] => ['quantity' => $item['quantity']]];
                });
                $service->items()->sync($items);
            }
        }

        return back();
    }

    public function destroy(Service $service): RedirectResponse
    {
        $this->authorize('delete', $service);

        // Check if service has related data in quotes
        $quoteLineCount = \App\Models\QuoteLine::where('service_id', $service->id)->count();
        if ($quoteLineCount > 0) {
            return back()->withErrors([
                'service' => __('messages.service_has_quote_lines', ['count' => $quoteLineCount])
            ]);
        }

        // Check if service has related data in work orders
        $workOrderItemCount = \App\Models\WorkOrderItem::where('service_id', $service->id)->count();
        if ($workOrderItemCount > 0) {
            return back()->withErrors([
                'service' => __('messages.service_has_work_order_items', ['count' => $workOrderItemCount])
            ]);
        }

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
