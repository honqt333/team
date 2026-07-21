<?php

declare(strict_types=1);

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\InventoryBalance;
use App\Models\InventoryCategory;
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
        $warehouses = Warehouse::whereHas('center', function ($q) use ($tenantId) {
            $q->where('tenant_id', $tenantId);
        })
            ->with('center:id,name_ar,name_en')
            ->get()
            ->map(function ($w) {
                return [
                    'id' => $w->id,
                    'name' => $w->name.' - '.($w->center?->name ?? 'Main'),
                ];
            });

        // Determine which warehouse to show
        $selectedWarehouseId = $request->input('warehouse_id');

        if ($selectedWarehouseId) {
            $warehouse = Warehouse::whereHas('center', fn ($q) => $q->where('tenant_id', $tenantId))->find($selectedWarehouseId);
        }

        // Fallback to current center's default if not selected or not found
        if (! isset($warehouse) || ! $warehouse) {
            $warehouse = Warehouse::forCenter($centerId)->default()->first()
               ?? Warehouse::whereHas('center', fn ($q) => $q->where('tenant_id', $tenantId))->first();
        }

        // If still no warehouse (e.g. new tenant), create one
        if (! $warehouse) {
            $warehouse = Warehouse::getOrCreateDefault($centerId);
            // Re-fetch to get cleaner object if needed
            $warehouse = Warehouse::find($warehouse->id);
        }

        // Whitelist allowed sort columns and order directions to prevent SQL injection.
        $allowedSorts = ['qty_on_hand', 'min_stock', 'created_at', 'sku', 'name'];
        $sort = in_array($request->input('sort'), $allowedSorts, true)
            ? $request->input('sort')
            : 'qty_on_hand';
        $order = strtolower((string) $request->input('order', '')) === 'asc' ? 'asc' : 'desc';

        $query = InventoryBalance::forWarehouse($warehouse->id)
            ->with(['part' => fn ($q) => $q->select('id', 'sku', 'name_ar', 'name_en', 'unit_id', 'category_id', 'description')->with(['category', 'unit'])])
            ->when($request->input('search'), function ($q, $search) {
                $q->whereHas('part', fn ($pq) => $pq->where('sku', 'like', "%{$search}%")
                    ->orWhere('name_ar', 'like', "%{$search}%")
                    ->orWhere('name_en', 'like', "%{$search}%")
                );
            })
            ->when($request->input('category'), function ($q, $catId) {
                $q->whereHas('part', fn ($pq) => $pq->where('category_id', $catId));
            })
            ->when($request->input('stock_status') === 'in_stock', fn ($q) => $q->withStock())
            ->when($request->input('stock_status') === 'low_stock', fn ($q) => $q->lowStock())
            ->when($request->input('stock_status') === 'out_of_stock', fn ($q) => $q->where('qty_on_hand', '<=', 0));

        // Apply Sorting (sort is whitelisted above; order is forced to asc/desc)
        if ($sort === 'sku') {
            $query->join('parts', 'inventory_balances.part_id', '=', 'parts.id')
                ->orderBy('parts.sku', $order)
                ->select('inventory_balances.*');
        } elseif ($sort === 'name') {
            $query->join('parts', 'inventory_balances.part_id', '=', 'parts.id')
                ->orderBy(app()->getLocale() === 'ar' ? 'parts.name_ar' : 'parts.name_en', $order)
                ->select('inventory_balances.*');
        } else {
            $query->orderBy("inventory_balances.{$sort}", $order);
        }

        $balances = $query->paginate(25)->withQueryString();

        // Get categories for filter
        $categories = InventoryCategory::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->select('id', 'name_ar', 'name_en')
            ->get()
            ->map(function ($cat) {
                return [
                    'id' => $cat->id,
                    'name' => app()->getLocale() === 'ar' ? $cat->name_ar : ($cat->name_en ?? $cat->name_ar),
                ];
            });

        // Combined summary stats query (1 query instead of 4)
        // NOTE: alias 'stock_value' (not 'total_value') to avoid InventoryBalance::getTotalValueAttribute() accessor
        $statsRaw = InventoryBalance::forWarehouse($warehouse->id)
            ->leftJoin('parts', 'inventory_balances.part_id', '=', 'parts.id')
            ->selectRaw('
                COUNT(*) as total_items,
                SUM(CASE WHEN qty_on_hand > 0 THEN 1 ELSE 0 END) as in_stock,
                SUM(CASE WHEN qty_on_hand > 0 AND parts.min_qty IS NOT NULL AND qty_on_hand <= parts.min_qty THEN 1 ELSE 0 END) as low_stock,
                SUM(qty_on_hand * wac_cost) as stock_value
            ')
            ->first();

        $stats = [
            'total_items' => (int) ($statsRaw->total_items ?? 0),
            'in_stock' => (int) ($statsRaw->in_stock ?? 0),
            'low_stock' => (int) ($statsRaw->low_stock ?? 0),
            'total_value' => (float) ($statsRaw->stock_value ?? 0),
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

        $tenantId = auth()->user()->tenant_id;

        $warehouseId = $request->query('warehouse_id');

        if ($warehouseId) {
            $warehouse = Warehouse::whereHas('center', fn ($q) => $q->where('tenant_id', $tenantId))->find($warehouseId);
        } else {
            $centerId = auth()->user()->current_center_id;
            $warehouse = Warehouse::forCenter($centerId)->default()->first();
        }

        if (! $warehouse) {
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
