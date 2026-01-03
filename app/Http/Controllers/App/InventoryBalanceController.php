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
        $centerId = $user->current_center_id;

        // Get warehouse for current center
        $warehouse = Warehouse::forCenter($centerId)->default()->first();

        if (!$warehouse) {
            $warehouse = Warehouse::getOrCreateDefault($centerId);
        }

        $query = InventoryBalance::forWarehouse($warehouse->id)
            ->with(['part' => fn($q) => $q->select('id', 'sku', 'name_ar', 'name_en', 'unit_id', 'category_id', 'min_qty')->with(['category'])])
            ->when($request->input('search'), function ($q, $search) {
                $q->whereHas('part', fn($pq) => 
                    $pq->where('sku', 'like', "%{$search}%")
                       ->orWhere('name_ar', 'like', "%{$search}%")
                       ->orWhere('name_en', 'like', "%{$search}%")
                );
            })
            ->when($request->input('category'), function ($q, $catId) {
                $q->whereHas('part', fn($pq) => $pq->where('category_id', $catId));
            })
            ->when($request->input('stock_status') === 'in_stock', fn($q) => $q->withStock())
            ->when($request->input('stock_status') === 'low_stock', fn($q) => $q->lowStock())
            ->when($request->input('stock_status') === 'out_of_stock', fn($q) => $q->where('qty_on_hand', '<=', 0))
            ->orderBy('qty_on_hand', 'desc');

        $balances = $query->paginate(25)->withQueryString();

        // Get categories for filter
        $categories = \App\Models\InventoryCategory::where('tenant_id', $user->tenant_id)
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
            'categories' => $categories,
            'filters' => $request->only(['search', 'category', 'stock_status']),
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
