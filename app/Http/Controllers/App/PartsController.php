<?php

declare(strict_types=1);

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\InventoryBalance;
use App\Models\InventoryCategory;
use App\Models\InventoryUnit;
use App\Models\Part;
use App\Models\PartVehicleCompatibility;
use App\Models\VehicleMake;
use App\Models\VehicleModel;
use App\Models\Warehouse;
use App\Services\Inventory\InventoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class PartsController extends Controller
{
    public function __construct(
        protected InventoryService $inventoryService
    ) {}

    public function index(Request $request)
    {
        $this->authorize('viewAny', Part::class);

        $tenantId = auth()->user()->tenant_id;

        $query = Part::forTenant($tenantId)
            ->search($request->input('search'))
            ->when($request->input('status') === 'active', fn ($q) => $q->active())
            ->when($request->input('status') === 'inactive', fn ($q) => $q->where('is_active', false))
            ->when($request->input('category'), fn ($q, $catId) => $q->where('category_id', $catId))
            ->when(
                $request->filled('vehicle_make_id') || $request->filled('vehicle_model_id') || $request->filled('manufacture_year'),
                function ($q) use ($request) {
                    $makeId = $request->input('vehicle_make_id');
                    $modelId = $request->input('vehicle_model_id');
                    $year = $request->filled('manufacture_year') ? (int) $request->input('manufacture_year') : null;

                    $q->whereHas('compatibilities', function ($cq) use ($makeId, $modelId, $year) {
                        if ($makeId) {
                            $cq->where('vehicle_make_id', $makeId);
                        }

                        if ($modelId) {
                            $cq->where(function ($mq) use ($modelId) {
                                $mq->where('vehicle_model_id', $modelId)
                                    ->orWhereNull('vehicle_model_id');
                            });
                        }

                        if ($year) {
                            $cq->where(function ($yq) use ($year) {
                                $yq->whereNull('year_from')->orWhere('year_from', '<=', $year);
                            })->where(function ($yq) use ($year) {
                                $yq->whereNull('year_to')->orWhere('year_to', '>=', $year);
                            });
                        }
                    });
                }
            )
            ->with([
                'unit',
                'category',
                'inventoryBalances.warehouse.center',
                'compatibilities.make:id,name_ar,name_en,logo_path',
                'compatibilities.model:id,make_id,name_ar,name_en',
            ])
            ->withSum('inventoryBalances', 'qty_on_hand');

        $sortableColumns = [
            'sku' => 'sku',
            'name' => 'name_ar',
            'name_ar' => 'name_ar',
            'name_en' => 'name_en',
            'qty' => 'inventory_balances_sum_qty_on_hand',
            'is_active' => 'is_active',
            'id' => 'id',
        ];

        $sort = (string) $request->input('sort');
        $direction = strtolower((string) $request->input('direction', 'asc')) === 'desc' ? 'desc' : 'asc';

        if ($sort && isset($sortableColumns[$sort])) {
            $query->orderBy($sortableColumns[$sort], $direction);
        } else {
            $query->orderBy('name_ar', 'asc');
        }

        $filters = $request->only(['search', 'status', 'category', 'vehicle_make_id', 'vehicle_model_id', 'manufacture_year', 'sort', 'direction']);

        if ($request->boolean('print')) {
            return Inertia::render('Reports/Print', [
                'reportType' => 'inventory_parts',
                'backRoute' => 'app.inventory.parts.index',
                'filters' => $filters,
                'kpis' => [],
                'rows' => $query->limit(5000)->get(),
                'companyInfo' => [],
            ]);
        }

        $parts = $query->paginate(25)->withQueryString();

        $units = InventoryUnit::where('tenant_id', $tenantId)->where('is_active', true)->get();
        $categories = InventoryCategory::where('tenant_id', $tenantId)->where('is_active', true)->get();

        $warehouses = Warehouse::whereHas('center', fn ($q) => $q->where('tenant_id', $tenantId))
            ->where('is_active', true)
            ->with('center')
            ->orderBy('name')
            ->get(['id', 'name', 'center_id', 'is_default'])
            ->map(fn ($w) => [
                'id' => $w->id,
                'name' => $w->name,
                'center_id' => $w->center_id,
                'center_name' => $w->center?->name_ar ?? $w->center?->name_en ?? '',
                'is_default' => $w->is_default,
            ]);

        return Inertia::render('Inventory/Parts/Index', [
            'parts' => $parts,
            'categories' => $categories,
            'units' => $units,
            'warehouses' => $warehouses,
            'vehicleMakes' => VehicleMake::where('is_active', true)->orderBy('sort_order')->orderBy('name_ar')->get(['id', 'name_ar', 'name_en', 'logo_path']),
            'vehicleModels' => VehicleModel::where('is_active', true)->orderBy('sort_order')->orderBy('name_ar')->get(['id', 'make_id', 'name_ar', 'name_en']),
            'filters' => $request->only(['search', 'status', 'category', 'vehicle_make_id', 'vehicle_model_id', 'manufacture_year', 'sort', 'direction']),
        ]);
    }

    public function create()
    {
        // Not used - modal handled by Index.vue
        abort(404);
    }

    /**
     * Shared validation rules for storing and updating a spare part.
     */
    protected function partValidationRules(int $tenantId, ?int $ignorePartId = null): array
    {
        $skuUniqueRule = Rule::unique('parts')->where('tenant_id', $tenantId);

        if ($ignorePartId !== null) {
            $skuUniqueRule->ignore($ignorePartId);
        }

        return [
            'sku' => ['required', 'string', 'max:50', $skuUniqueRule],
            'barcode' => 'nullable|string|max:50',
            'name_ar' => 'required|string|max:255',
            'name_en' => 'required|string|max:255',
            'unit_id' => 'required|exists:inventory_units,id',
            'purchase_unit_id' => 'nullable|exists:inventory_units,id',
            'purchase_conversion_factor' => 'nullable|numeric|min:0.0001',
            'category_id' => 'nullable|exists:inventory_categories,id',
            'description' => 'nullable|string|max:1000',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,webp|extensions:jpeg,png,jpg,webp|max:2048',
            'default_sale_price' => 'nullable|numeric|min:0',
            'min_sale_price' => 'nullable|numeric|min:0',
            'min_qty' => 'nullable|numeric|min:0',
            'reorder_qty' => 'nullable|numeric|min:0',
            'warehouse_data' => 'nullable|array',
            'warehouse_data.*.warehouse_id' => 'required_with:warehouse_data|exists:warehouses,id',
            'warehouse_data.*.cost_price' => 'nullable|numeric|min:0',
            'warehouse_data.*.sale_price' => 'nullable|numeric|min:0',
            'warehouse_data.*.min_sale_price' => 'nullable|numeric|min:0',
            'warehouse_data.*.initial_stock' => 'nullable|numeric|min:0',
            'warehouse_data.*.min_stock' => 'nullable|numeric|min:0',
            'warehouse_data.*.storage_location' => 'nullable|string|max:50',
            'warehouse_data.*.allow_price_change' => 'nullable|boolean',
            'warehouse_data.*.is_active' => 'nullable|boolean',
            'sync_compatibilities' => 'nullable|boolean',
            'compatibilities' => 'nullable|array',
            'compatibilities.*.vehicle_make_id' => 'required_with:compatibilities|exists:vehicle_makes,id',
            'compatibilities.*.vehicle_model_id' => 'nullable|exists:vehicle_models,id',
            'compatibilities.*.year_from' => 'nullable|integer|min:1950|max:2100',
            'compatibilities.*.year_to' => 'nullable|integer|min:1950|max:2100',
            'compatibilities.*.notes' => 'nullable|string|max:255',
        ];
    }

    public function store(Request $request)
    {
        $this->authorize('create', Part::class);

        $tenantId = auth()->user()->tenant_id;
        $userId = auth()->id();

        $validated = $request->validate($this->partValidationRules($tenantId));

        if ($request->hasFile('image')) {
            $validated['image_path'] = $request->file('image')->store('parts', 'public');
        }

        $validated['tenant_id'] = $tenantId;

        // Extract nested arrays before creating the part (not DB columns)
        $warehouseData = $validated['warehouse_data'] ?? [];
        $compatibilities = $validated['compatibilities'] ?? [];
        unset($validated['warehouse_data'], $validated['compatibilities'], $validated['sync_compatibilities']);

        if (empty($validated['purchase_conversion_factor']) || (float) $validated['purchase_conversion_factor'] <= 0) {
            $validated['purchase_conversion_factor'] = 1.0;
        }

        // Create part
        $part = Part::create($validated);

        // Handle warehouse stock entries & vehicle compatibilities
        if (! empty($warehouseData)) {
            $this->syncWarehouseBalances($part, $warehouseData, $userId);
        }

        if (! empty($compatibilities)) {
            $this->syncVehicleCompatibilities($part, $compatibilities, $tenantId);
        }

        return redirect()->route('app.inventory.parts.index')
            ->with('success', __('inventory.parts.created'));
    }

    public function edit(Part $part)
    {
        // Not used - modal handled by Index.vue
        abort(404);
    }

    public function update(Request $request, Part $part)
    {
        $this->authorize('update', $part);

        $tenantId = auth()->user()->tenant_id;
        $userId = auth()->id();

        $validated = $request->validate($this->partValidationRules($tenantId, $part->id));

        if ($request->hasFile('image')) {
            if ($part->image_path) {
                Storage::disk('public')->delete($part->image_path);
            }
            $validated['image_path'] = $request->file('image')->store('parts', 'public');
        } elseif ($request->boolean('remove_image')) {
            if ($part->image_path) {
                Storage::disk('public')->delete($part->image_path);
            }
            $validated['image_path'] = null;
        }

        // Extract nested arrays before updating the part (not DB columns)
        $warehouseData = null;

        if (array_key_exists('warehouse_data', $validated)) {
            $warehouseData = $validated['warehouse_data'] ?? [];
            unset($validated['warehouse_data']);
        }

        $shouldSyncCompat = array_key_exists('compatibilities', $validated) || $request->boolean('sync_compatibilities');
        $compatibilities = $validated['compatibilities'] ?? [];
        unset($validated['compatibilities'], $validated['sync_compatibilities']);

        if (array_key_exists('purchase_conversion_factor', $validated)) {
            $validated['purchase_conversion_factor'] = (! empty($validated['purchase_conversion_factor']) && (float) $validated['purchase_conversion_factor'] > 0)
                ? (float) $validated['purchase_conversion_factor']
                : 1.0;
        }

        // Update part
        $part->update($validated);

        // Handle warehouse stock entries & vehicle compatibilities
        if ($warehouseData !== null) {
            $this->syncWarehouseBalances($part, $warehouseData, $userId);
        }

        if ($shouldSyncCompat) {
            $this->syncVehicleCompatibilities($part, $compatibilities, $tenantId);
        }

        return redirect()->route('app.inventory.parts.index')
            ->with('success', __('inventory.parts.updated'));
    }

    public function toggleActive(Part $part)
    {
        $this->authorize('update', $part);

        $part->update(['is_active' => ! $part->is_active]);

        return back()->with('success', $part->is_active
            ? __('inventory.parts.activated')
            : __('inventory.parts.deactivated'));
    }

    public function show(Part $part)
    {
        $this->authorize('view', $part);

        $part->load([
            'unit',
            'category',
            'inventoryBalances.warehouse.center',
            'compatibilities.make:id,name_ar,name_en,logo_path',
            'compatibilities.model:id,make_id,name_ar,name_en',
        ]);
        $tenantId = auth()->user()->tenant_id;

        $balances = $part->inventoryBalances()
            ->with(['warehouse.center'])
            ->get();

        $moves = $part->inventoryMoves()
            ->with(['warehouse.center', 'reference'])
            ->orderBy('posted_at', 'desc')
            ->paginate(20);

        return Inertia::render('Inventory/Parts/Show', [
            'part' => $part,
            'balances' => $balances,
            'moves' => $moves,
            'units' => InventoryUnit::where('tenant_id', $tenantId)->where('is_active', true)->get(),
            'categories' => InventoryCategory::where('tenant_id', $tenantId)->where('is_active', true)->get(),
            'vehicleMakes' => VehicleMake::where('is_active', true)->orderBy('sort_order')->orderBy('name_ar')->get(['id', 'name_ar', 'name_en', 'logo_path']),
            'vehicleModels' => VehicleModel::where('is_active', true)->orderBy('sort_order')->orderBy('name_ar')->get(['id', 'make_id', 'name_ar', 'name_en']),
            'warehouses' => Warehouse::whereHas('center', fn ($q) => $q->where('tenant_id', $tenantId))
                ->where('is_active', true)
                ->with('center')
                ->orderBy('name')
                ->get(['id', 'name', 'center_id', 'is_default'])
                ->map(fn ($w) => [
                    'id' => $w->id,
                    'name' => $w->name,
                    'center_id' => $w->center_id,
                    'center_name' => $w->center?->name_ar ?? $w->center?->name_en ?? '',
                    'is_default' => $w->is_default,
                ]),
        ]);
    }

    /**
     * Sync compatible vehicle makes/models/years for a spare part.
     */
    protected function syncVehicleCompatibilities(Part $part, array $compatibilities, int $tenantId): void
    {
        $part->compatibilities()->delete();

        $seen = [];

        foreach ($compatibilities as $row) {
            if (empty($row['vehicle_make_id'])) {
                continue;
            }
            $makeId = (int) $row['vehicle_make_id'];
            $modelId = ! empty($row['vehicle_model_id']) ? (int) $row['vehicle_model_id'] : null;
            $yearFrom = ! empty($row['year_from']) ? (int) $row['year_from'] : null;
            $yearTo = ! empty($row['year_to']) ? (int) $row['year_to'] : null;

            if ($yearFrom && $yearTo && $yearFrom > $yearTo) {
                [$yearFrom, $yearTo] = [$yearTo, $yearFrom];
            }

            $dedupeKey = "{$makeId}-".($modelId ?? 'all').'-'.($yearFrom ?? '').'-'.($yearTo ?? '');

            if (isset($seen[$dedupeKey])) {
                continue;
            }
            $seen[$dedupeKey] = true;

            PartVehicleCompatibility::create([
                'tenant_id' => $tenantId,
                'part_id' => $part->id,
                'vehicle_make_id' => $makeId,
                'vehicle_model_id' => $modelId,
                'year_from' => $yearFrom,
                'year_to' => $yearTo,
                'notes' => $row['notes'] ?? null,
            ]);
        }
    }

    /**
     * Sync warehouse balances for a part (used by store, update, and updateStock).
     */
    protected function syncWarehouseBalances(Part $part, array $warehouseData, int $userId): void
    {
        if (empty($warehouseData)) {
            return;
        }

        $currentBalances = $part->inventoryBalances()
            ->whereIn('warehouse_id', collect($warehouseData)->pluck('warehouse_id')->map(fn ($id) => (int) $id))
            ->get()
            ->keyBy('warehouse_id');

        foreach ($warehouseData as $whData) {
            $warehouseId = (int) $whData['warehouse_id'];
            $currentBalance = $currentBalances->get($warehouseId);
            $currentQty = (float) ($currentBalance?->qty_on_hand ?? 0);
            $initialStock = (float) ($whData['initial_stock'] ?? 0);
            $stockDifference = $initialStock - $currentQty;

            InventoryBalance::updateOrCreate(
                [
                    'part_id' => $part->id,
                    'warehouse_id' => $warehouseId,
                ],
                [
                    'wac_cost' => $whData['cost_price'] ?? 0,
                    'sale_price' => $whData['sale_price'] ?? 0,
                    'min_sale_price' => $whData['min_sale_price'] ?? 0,
                    'min_stock' => $whData['min_stock'] ?? 0,
                    'storage_location' => $whData['storage_location'] ?? null,
                    'allow_price_change' => $whData['allow_price_change'] ?? false,
                    'is_active' => $whData['is_active'] ?? true,
                ]
            );

            // If stock changed, create receipt/adjustment
            // NOTE: No external document — this is a manual stock adjustment,
            // so reference is null (Part itself is not a polymorphic "document").
            if ($stockDifference > 0 && $currentQty == 0.0) {
                $this->inventoryService->receipt(
                    warehouseId: $warehouseId,
                    partId: (int) $part->id,
                    qty: $stockDifference,
                    unitCost: (float) ($whData['cost_price'] ?? 0),
                    userId: $userId,
                    referenceType: null,
                    referenceId: null,
                    notes: 'رصيد افتتاحي',
                );
            } elseif ($stockDifference != 0) {
                $this->inventoryService->adjust(
                    warehouseId: $warehouseId,
                    partId: (int) $part->id,
                    qty: $stockDifference,
                    unitCost: (float) ($whData['cost_price'] ?? 0),
                    userId: $userId,
                    notes: $stockDifference > 0 ? 'تحديث رصيد افتتاحي' : 'تعديل رصيد',
                );
            }
        }

        // Keep part's default_sale_price and min_qty in sync with warehouse balances
        $partBalances = $part->inventoryBalances()->with('warehouse')->get();
        $defaultWhBalance = $partBalances->first(fn ($b) => $b->warehouse?->is_default && (float) $b->sale_price > 0);
        $firstWhBalance = $partBalances->first(fn ($b) => (float) $b->sale_price > 0);
        $activeSalePrice = (float) ($defaultWhBalance?->sale_price ?? $firstWhBalance?->sale_price ?? 0);
        $totalMinStock = (float) $partBalances->sum('min_stock');

        $partUpdates = [];

        if ($activeSalePrice > 0 && ((float) $part->default_sale_price <= 0)) {
            $partUpdates['default_sale_price'] = $activeSalePrice;
        }

        if ($totalMinStock > 0 && ((float) $part->min_qty <= 0)) {
            $partUpdates['min_qty'] = $totalMinStock;
        }

        if (! empty($partUpdates)) {
            $part->update($partUpdates);
        }
    }

    /**
     * Update stock balances for a part.
     */
    public function updateStock(Request $request, Part $part)
    {
        $this->authorize('update', $part);

        $validated = $request->validate([
            'warehouse_data' => 'required|array|min:1',
            'warehouse_data.*.warehouse_id' => 'required|exists:warehouses,id',
            'warehouse_data.*.cost_price' => 'nullable|numeric|min:0',
            'warehouse_data.*.sale_price' => 'nullable|numeric|min:0',
            'warehouse_data.*.min_sale_price' => 'nullable|numeric|min:0',
            'warehouse_data.*.initial_stock' => 'nullable|numeric|min:0',
            'warehouse_data.*.min_stock' => 'nullable|numeric|min:0',
            'warehouse_data.*.storage_location' => 'nullable|string|max:50',
            'warehouse_data.*.allow_price_change' => 'nullable|boolean',
            'warehouse_data.*.is_active' => 'nullable|boolean',
        ]);

        $this->syncWarehouseBalances($part, $validated['warehouse_data'], auth()->id());

        return back()->with('success', __('inventory.parts.updated'));
    }

    /**
     * API: Search parts for autocomplete.
     */
    public function search(Request $request)
    {
        $this->authorize('viewAny', Part::class);

        $tenantId = auth()->user()->tenant_id;

        $parts = Part::forTenant($tenantId)
            ->active()
            ->search($request->input('q'))
            ->with(['unit', 'purchaseUnit', 'inventoryBalances.warehouse'])
            ->when($request->filled('warehouse_id'), function ($query) use ($request) {
                $query->withSum(['inventoryBalances' => function ($q) use ($request) {
                    $q->where('warehouse_id', $request->input('warehouse_id'));
                }], 'qty_on_hand');
            }, function ($query) {
                $query->withSum('inventoryBalances', 'qty_on_hand');
            })
            ->when($request->boolean('hide_out_of_stock'), function ($query) use ($request) {
                $query->whereHas('inventoryBalances', function ($q) use ($request) {
                    $q->where('qty_on_hand', '>', 0);

                    if ($request->filled('warehouse_id')) {
                        $q->where('warehouse_id', $request->input('warehouse_id'));
                    }
                });
            })
            ->limit(50)
            ->get();

        return response()->json($parts);
    }
}
