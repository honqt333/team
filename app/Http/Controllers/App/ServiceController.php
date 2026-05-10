<?php

namespace App\Http\Controllers\App;

use App\Models\Department;
use App\Models\Service;
use App\Models\VehicleConditionItem;
use App\Models\Setting;
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

        // Get inspection condition items
        $conditionCategories = \App\Models\VehicleConditionCategory::with(['items.updatedBy:id,name', 'updatedBy:id,name'])
            ->orderedBySource()
            ->ordered()
            ->get();
            
        $enableSystematicInspections = Setting::where('key', 'enable_systematic_inspections')->value('value') ?? 'true';

        \Illuminate\Support\Facades\Log::info('Packages found in index:', ['count' => $packages->count(), 'sample' => $packages->take(1)->toArray()]);

        return Inertia::render('Services/Index', [
            'departments' => $departments,
            'unassignedServices' => $unassignedServices,
            'packages' => $packages,
            'conditionCategories' => $conditionCategories,
            'enableSystematicInspections' => filter_var($enableSystematicInspections, FILTER_VALIDATE_BOOLEAN),
        ]);
    }

    public function toggleInspectionsSetting(Request $request): RedirectResponse
    {
        $setting = Setting::firstOrCreate(
            ['key' => 'enable_systematic_inspections'],
            ['group' => 'work_orders', 'value' => 'true']
        );

        $currentValue = filter_var($setting->value, FILTER_VALIDATE_BOOLEAN);
        $setting->value = $currentValue ? 'false' : 'true';
        $setting->save();

        return redirect()->back()->with('success', __('common.updated_success'));
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

        // Check if service has related data in ACTIVE quotes
        $quotes = \App\Models\Quote::whereHas('lines', function ($query) use ($service) {
                $query->where('service_id', $service->id);
            })
            ->whereIn('status', [
                \App\Models\Quote::STATUS_DRAFT,
                \App\Models\Quote::STATUS_SENT,
                \App\Models\Quote::STATUS_APPROVED
            ])
            ->limit(5)
            ->pluck('code');

        if ($quotes->isNotEmpty()) {
            return back()->withErrors([
                'service' => __('messages.service_has_quote_lines_with_codes', [
                    'count' => $quotes->count(),
                    'codes' => $quotes->implode(', ')
                ])
            ]);
        }

        // Check if service has related data in ACTIVE work orders
        $workOrders = \App\Models\WorkOrder::whereHas('items', function ($query) use ($service) {
                $query->where('service_id', $service->id);
            })
            ->whereIn('status', [
                \App\Models\WorkOrder::STATUS_DRAFT,
                \App\Models\WorkOrder::STATUS_OPEN,
                \App\Models\WorkOrder::STATUS_IN_PROGRESS,
                \App\Models\WorkOrder::STATUS_ON_HOLD
            ])
            ->limit(5)
            ->pluck('code');

        if ($workOrders->isNotEmpty()) {
            return back()->withErrors([
                'service' => __('messages.service_has_work_order_items_with_codes', [
                    'count' => $workOrders->count(),
                    'codes' => $workOrders->implode(', ')
                ])
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
