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

        $centerId = auth()->user()->current_center_id;
        $tenantId = auth()->user()->tenant_id;

        // Get departments with their services (accordion view) - scoped to this center
        // Exclude packages from regular departments view
        $departments = Department::with(['services' => function ($query) use ($centerId) {
            $query->where('center_id', $centerId)
                ->where('type', '!=', Service::TYPE_PACKAGE)
                ->with('updater:id,name')
                ->ordered();
        }])
            ->withCount(['services' => function ($query) use ($centerId) {
                $query->where('center_id', $centerId);
            }])
            ->ordered()
            ->get();

        // Get services without department - scoped to this center
        // Exclude packages from unassigned services
        $unassignedServices = Service::with('updater:id,name')
            ->where('center_id', $centerId)
            ->whereNull('department_id')
            ->where('type', '!=', Service::TYPE_PACKAGE)
            ->ordered()
            ->get();

        // Get packages - scoped to this center
        $packages = Service::with(['updater:id,name', 'items'])
            ->where('center_id', $centerId)
            ->where('type', Service::TYPE_PACKAGE)
            ->ordered()
            ->get();

        // Get all local service names in the current center to exclude them from the catalog dropdown
        $localServiceNames = Service::where('center_id', $centerId)
            ->pluck('name_ar')
            ->filter()
            ->toArray();

        // Get services from other branches under the same tenant for the catalog dropdown.
        // Grouped unique per (name_ar + department) — keeps one representative per service-department pair.
        $otherBranchesServices = Service::with(['department' => function ($query) {
                $query->withoutGlobalScope('center_scoped');
            }])
            ->where('tenant_id', $tenantId)
            ->where('center_id', '!=', $centerId)
            ->whereNotIn('name_ar', $localServiceNames)
            ->where('type', '!=', Service::TYPE_PACKAGE)
            ->get()
            ->unique(fn($s) => $s->name_ar . '|' . $s->name_en . '|' . ($s->department_id ?? 'null'))
            ->values()
            ->map(fn($s) => [
                'id'                 => $s->id,
                'name_ar'            => $s->name_ar,
                'name_en'            => $s->name_en,
                'description_ar'     => $s->description_ar,
                'description_en'     => $s->description_en,
                'duration_value'     => $s->duration_value,
                'duration_unit'      => $s->duration_unit,
                'warranty_value'     => $s->warranty_value,
                'warranty_unit'      => $s->warranty_unit,
                'type'               => $s->type,
                'department_id'      => $s->department_id,
                'department_name_ar' => $s->department?->name_ar,
                'department_name_en' => $s->department?->name_en,
            ]);

        // Get all local package names in the current center to exclude them from the catalog dropdown
        $localPackageNames = Service::where('center_id', $centerId)
            ->where('type', Service::TYPE_PACKAGE)
            ->pluck('name_ar')
            ->filter()
            ->toArray();

        // Get packages from other branches under the same tenant for the catalog dropdown
        $otherBranchesPackages = Service::with(['items'])
            ->where('tenant_id', $tenantId)
            ->where('center_id', '!=', $centerId)
            ->where('type', Service::TYPE_PACKAGE)
            ->whereNotIn('name_ar', $localPackageNames)
            ->get()
            ->unique(fn($s) => $s->name_ar . '|' . $s->name_en)
            ->values()
            ->map(fn($s) => [
                'id'                 => $s->id,
                'name_ar'            => $s->name_ar,
                'name_en'            => $s->name_en,
                'description_ar'     => $s->description_ar,
                'description_en'     => $s->description_en,
                'base_price'         => 0,
                'min_price'          => 0,
                'default_discount_type' => 'none',
                'default_discount_value' => null,
                'allow_price_override' => $s->allow_price_override,
                'type'               => $s->type,
                'is_active'          => true,
                'items'              => $s->items->map(fn($item) => [
                    'id' => $item->id,
                    'name_ar' => $item->name_ar,
                    'name_en' => $item->name_en,
                    'quantity' => $item->pivot->quantity,
                    'base_price' => $item->base_price,
                ]),
            ]);

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
            'otherBranchesServices' => $otherBranchesServices,
            'otherBranchesPackages' => $otherBranchesPackages,
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
            'department_id' => 'required_unless:type,package|nullable|exists:departments,id',
            'name_ar' => [
                'required',
                'string',
                'max:255',
                Rule::unique('services')->where(fn ($query) => $query->where('center_id', $centerId)->whereNull('deleted_at')),
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
        $validated['updated_by'] = auth()->id();
        
        \Illuminate\Support\Facades\Log::info('Creating service/package', $validated);

        // Check if there is a soft-deleted service with the same name in this center
        $existingTrashed = Service::onlyTrashed()
            ->where('center_id', $centerId)
            ->where('name_ar', $validated['name_ar'])
            ->first();

        if ($existingTrashed) {
            $existingTrashed->restore();
            $existingTrashed->update($validated);
            $service = $existingTrashed;
            \Illuminate\Support\Facades\Log::info('Service restored and updated from trash', ['id' => $service->id]);
        } else {
            $service = Service::create($validated);
            \Illuminate\Support\Facades\Log::info('Service created', ['id' => $service->id, 'type' => $service->type]);
        }

        if ($validated['type'] === Service::TYPE_PACKAGE && !empty($validated['items'])) {
            $items = collect($validated['items'])->mapWithKeys(function ($item) {
                return [$item['id'] => ['quantity' => $item['quantity']]];
            });
            $service->items()->sync($items);
            \Illuminate\Support\Facades\Log::info('Package items synced', ['items' => $items]);
        }

        return back()->with('new_service_id', $service->id);
    }

    public function update(Request $request, Service $service): RedirectResponse
    {
        $this->authorize('update', $service);

        $validated = $request->validate([
            'department_id' => 'required_unless:type,package|nullable|exists:departments,id',
            'name_ar' => [
                'sometimes',
                'required',
                'string',
                'max:255',
                Rule::unique('services')
                    ->where(fn ($query) => $query->where('center_id', $service->center_id)->whereNull('deleted_at'))
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

        $service->update([
            'is_active' => !$service->is_active,
            'updated_by' => auth()->id(),
        ]);

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
