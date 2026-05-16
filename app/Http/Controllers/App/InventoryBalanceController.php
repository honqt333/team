<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\InventoryBalance;
use App\Models\Part;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Inertia\Inertia;

class InventoryBalanceController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', InventoryBalance::class);

        $user = auth()->user();
        $tenantId = $user->tenant_id;
        $centerId = $user->current_center_id;

        // Get all warehouses for tenant centers
        $warehouses = Warehouse::whereHas('center', function($q) use ($tenantId) {
                $q->where('tenant_id', $tenantId);
            })
            ->with('center:id,name_ar,name_en')
            ->get()
            ->map(function ($w) {
                return [
                    'id' => $w->id,
                    'name' => $w->name . ' - ' . ($w->center?->name ?? 'Main'),
                ];
            });

        // Determine which warehouse to show
        $selectedWarehouseId = $request->input('warehouse_id');
        
        if ($selectedWarehouseId) {
            $warehouse = Warehouse::whereHas('center', fn($q) => $q->where('tenant_id', $tenantId))->find($selectedWarehouseId);
        }
        
        // Fallback to current center's default if not selected or not found
        if (!isset($warehouse) || !$warehouse) {
             $warehouse = Warehouse::forCenter($centerId)->default()->first() 
                ?? Warehouse::whereHas('center', fn($q) => $q->where('tenant_id', $tenantId))->first();
        }

        // If still no warehouse (e.g. new tenant), create one
        if (!$warehouse) {
            $warehouse = Warehouse::getOrCreateDefault($centerId);
             // Re-fetch to get cleaner object if needed
             $warehouse = Warehouse::find($warehouse->id);
        }

        $sort = $request->input('sort', 'qty_on_hand');
        $order = $request->input('order', 'desc');
        
        $query = InventoryBalance::forWarehouse($warehouse->id)
            ->with(['part' => fn($q) => $q->select('id', 'sku', 'barcode', 'name_ar', 'name_en', 'unit_id', 'category_id', 'min_qty', 'description', 'default_sale_price', 'min_sale_price')->with(['category', 'unit'])])
            ->when($request->input('search'), function ($q, $search) {
                $q->whereHas('part', fn($pq) => 
                    $pq->where('sku', 'like', "%{$search}%")
                       ->orWhere('barcode', 'like', "%{$search}%")
                       ->orWhere('name_ar', 'like', "%{$search}%")
                       ->orWhere('name_en', 'like', "%{$search}%")
                );
            })
            ->when($request->input('category'), function ($q, $catId) {
                $q->whereHas('part', fn($pq) => $pq->where('category_id', $catId));
            })
            ->when($request->input('stock_status') === 'in_stock', fn($q) => $q->withStock())
            ->when($request->input('stock_status') === 'low_stock', fn($q) => $q->lowStock())
            ->when($request->input('stock_status') === 'out_of_stock', fn($q) => $q->where('qty_on_hand', '<=', 0));

        // Apply Sorting
        if ($sort === 'sku') {
            $query->join('parts', 'inventory_balances.part_id', '=', 'parts.id')
                  ->orderBy('parts.sku', $order)
                  ->select('inventory_balances.*');
        } elseif ($sort === 'name') {
            $query->join('parts', 'inventory_balances.part_id', '=', 'parts.id')
                  ->orderBy(app()->getLocale() === 'ar' ? 'parts.name_ar' : 'parts.name_en', $order)
                  ->select('inventory_balances.*');
        } else {
            $query->orderBy($sort, $order);
        }

        $balances = $query->paginate(25)->withQueryString();

        // Get categories for filter
        $categories = \App\Models\InventoryCategory::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->select('id', 'name_ar', 'name_en')
            ->get()
            ->map(function ($cat) {
                return [
                    'id' => $cat->id,
                    'name' => app()->getLocale() === 'ar' ? $cat->name_ar : ($cat->name_en ?? $cat->name_ar),
                ];
            });

        // Summary stats
        $stats = [
            'total_items' => InventoryBalance::forWarehouse($warehouse->id)->count(),
            'in_stock' => InventoryBalance::forWarehouse($warehouse->id)->withStock()->count(),
            'low_stock' => InventoryBalance::forWarehouse($warehouse->id)->lowStock()->count(),
            'total_value' => InventoryBalance::forWarehouse($warehouse->id)->selectRaw('SUM(qty_on_hand * wac_cost) as total')->value('total') ?? 0,
        ];

        return Inertia::render('Inventory/Stock/Index', [
            'balances' => $balances,
            'warehouse' => $warehouse,
            'warehouses' => $warehouses, // Pass list
            'categories' => $categories,
            'filters' => $request->only(['search', 'category', 'stock_status', 'warehouse_id']),
            'stats' => $stats,
        ]);
    }

    /**
     * API: Get stock level for a specific part in current warehouse.
     */
    public function getPartStock(Request $request, Part $part)
    {
        $this->authorize('viewAny', InventoryBalance::class);

        $centerId = auth()->user()->current_center_id;
        $warehouse = Warehouse::forCenter($centerId)->default()->first();

        if (!$warehouse) {
            return response()->json([
                'qty_on_hand' => 0,
                'wac_cost' => 0,
            ]);
        }

        $balance = InventoryBalance::forWarehouse($warehouse->id)
            ->forPart($part->id)
            ->first();

        return response()->json([
            'qty_on_hand' => $balance?->qty_on_hand ?? 0,
            'wac_cost' => $balance?->wac_cost ?? 0,
            'warehouse_id' => $warehouse->id,
            'warehouse_name' => $warehouse->name,
        ]);
    }
}
