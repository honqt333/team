<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\Part;
use App\Models\InventoryBalance;
use App\Models\Warehouse;
use App\Services\Inventory\InventoryService;
use Illuminate\Http\Request;
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
            ->when($request->input('status') === 'active', fn($q) => $q->active())
            ->when($request->input('status') === 'inactive', fn($q) => $q->where('is_active', false))
            ->when($request->input('category'), fn($q, $catId) => $q->where('category_id', $catId))
            ->with(['unit', 'category', 'inventoryBalances.warehouse.center'])
            ->withSum('inventoryBalances', 'qty_on_hand')
            ->orderBy('name_ar');

        $parts = $query->paginate(25)->withQueryString();

        $units = \App\Models\InventoryUnit::where('tenant_id', $tenantId)->where('is_active', true)->get();
        $categories = \App\Models\InventoryCategory::where('tenant_id', $tenantId)->where('is_active', true)->get();

        $warehouses = Warehouse::whereHas('center', fn($q) => $q->where('tenant_id', $tenantId))
            ->where('is_active', true)
            ->with('center')
            ->orderBy('name')
            ->get(['id', 'name', 'center_id', 'is_default'])
            ->map(fn($w) => [
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
            'filters' => $request->only(['search', 'status', 'category']),
        ]);
    }

    public function create()
    {
        // Not used - modal handled by Index.vue
        abort(404);
    }

    public function store(Request $request)
    {
        $this->authorize('create', Part::class);

        $tenantId = auth()->user()->tenant_id;
        $userId = auth()->id();

        $validated = $request->validate([
            'sku' => [
                'required',
                'string',
                'max:50',
                Rule::unique('parts')->where('tenant_id', $tenantId),
            ],
            'barcode' => 'nullable|string|max:50',
            'name_ar' => 'required|string|max:255',
            'name_en' => 'required|string|max:255',
            'unit_id' => 'required|exists:inventory_units,id',
            'purchase_unit_id' => 'nullable|exists:inventory_units,id',
            'purchase_conversion_factor' => 'nullable|numeric|min:0.0001',
            'category_id' => 'nullable|exists:inventory_categories,id',
            'description' => 'nullable|string|max:1000',
            'image' => 'nullable|image|max:2048',
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
        ]);

        if ($request->hasFile('image')) {
            $validated['image_path'] = $request->file('image')->store('parts', 'public');
        }

        $validated['tenant_id'] = $tenantId;

        // Extract warehouse_data before creating the part (not a DB column)
        $warehouseData = $validated['warehouse_data'] ?? [];
        unset($validated['warehouse_data']);

        // Create part
        $part = Part::create($validated);

        // Handle warehouse stock entries
        if (!empty($warehouseData)) {
            $this->syncWarehouseBalances($part, $warehouseData, $userId);
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

        $userId = auth()->id();

        $validated = $request->validate([
            'sku' => [
                'required',
                'string',
                'max:50',
                Rule::unique('parts')->where('tenant_id', auth()->user()->tenant_id)->ignore($part->id),
            ],
            'barcode' => 'nullable|string|max:50',
            'name_ar' => 'required|string|max:255',
            'name_en' => 'required|string|max:255',
            'unit_id' => 'required|exists:inventory_units,id',
            'purchase_unit_id' => 'nullable|exists:inventory_units,id',
            'purchase_conversion_factor' => 'nullable|numeric|min:0.0001',
            'category_id' => 'nullable|exists:inventory_categories,id',
            'description' => 'nullable|string|max:1000',
            'image' => 'nullable|image|max:2048',
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
        ]);

        if ($request->hasFile('image')) {
            if ($part->image_path) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($part->image_path);
            }
            $validated['image_path'] = $request->file('image')->store('parts', 'public');
        } elseif ($request->boolean('remove_image')) {
            if ($part->image_path) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($part->image_path);
            }
            $validated['image_path'] = null;
        }

        // Extract warehouse_data before updating the part (not a DB column)
        $warehouseData = null;
        if (array_key_exists('warehouse_data', $validated)) {
            $warehouseData = $validated['warehouse_data'] ?? [];
            unset($validated['warehouse_data']);
        }

        // Update part
        $part->update($validated);

        // Handle warehouse stock entries
        if ($warehouseData !== null) {
            $this->syncWarehouseBalances($part, $warehouseData, $userId);
        }

        return redirect()->route('app.inventory.parts.index')
            ->with('success', __('inventory.parts.updated'));
    }

    public function toggleActive(Part $part)
    {
        $this->authorize('update', $part);

        $part->update(['is_active' => !$part->is_active]);

        return back()->with('success', $part->is_active 
            ? __('inventory.parts.activated') 
            : __('inventory.parts.deactivated'));
    }

    public function show(Part $part)
    {
        $this->authorize('view', $part);

        $part->load(['unit', 'category']);
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
            'units' => \App\Models\InventoryUnit::where('tenant_id', $tenantId)->where('is_active', true)->get(),
            'categories' => \App\Models\InventoryCategory::where('tenant_id', $tenantId)->where('is_active', true)->get(),
            'warehouses' => Warehouse::whereHas('center', fn($q) => $q->where('tenant_id', $tenantId))
                ->where('is_active', true)
                ->with('center')
                ->orderBy('name')
                ->get(['id', 'name', 'center_id', 'is_default'])
                ->map(fn($w) => [
                    'id' => $w->id,
                    'name' => $w->name,
                    'center_id' => $w->center_id,
                    'center_name' => $w->center?->name_ar ?? $w->center?->name_en ?? '',
                    'is_default' => $w->is_default,
                ]),
        ]);
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
            ->whereIn('warehouse_id', collect($warehouseData)->pluck('warehouse_id'))
            ->get()
            ->keyBy('warehouse_id');

        foreach ($warehouseData as $whData) {
            $warehouseId = $whData['warehouse_id'];
            $currentBalance = $currentBalances->get($warehouseId);
            $currentQty = $currentBalance?->qty_on_hand ?? 0;
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
            if ($stockDifference != 0) {
                $this->inventoryService->receipt(
                    warehouseId: $warehouseId,
                    partId: $part->id,
                    qty: abs($stockDifference),
                    unitCost: (float) ($whData['cost_price'] ?? 0),
                    userId: $userId,
                    notes: $stockDifference > 0 ? 'تحديث رصيد افتتاحي' : 'تعديل رصيد',
                    referenceType: null,
                    referenceId: null,
                );
            }
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
            ->withSum('inventoryBalances', 'qty_on_hand')
            ->when($request->boolean('hide_out_of_stock'), function($query) {
                $query->having('inventory_balances_sum_qty_on_hand', '>', 0);
            })
            ->limit(50)
            ->get();

        return response()->json($parts);
    }
}