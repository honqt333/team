<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\InventoryMove;
use App\Models\Part;
use App\Models\Warehouse;
use App\Services\Inventory\InventoryService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class InventoryMoveController extends Controller
{
    public function __construct(
        protected InventoryService $inventoryService
    ) {}

    public function index(Request $request)
    {
        $this->authorize('viewAny', InventoryMove::class);

        $user = auth()->user();
        $centerId = $user->current_center_id;
        $warehouse = Warehouse::forCenter($centerId)->default()->first();

        if (!$warehouse) {
            $warehouse = Warehouse::getOrCreateDefault($centerId);
        }

        $query = InventoryMove::forWarehouse($warehouse->id)
            ->with([
                'part:id,sku,name_ar,name_en',
                'postedByUser:id,name',
            ])
            ->posted()
            ->when($request->input('search'), function ($q, $search) {
                $q->whereHas('part', fn($pq) => 
                    $pq->where('sku', 'like', "%{$search}%")
                       ->orWhere('name_ar', 'like', "%{$search}%")
                );
            })
            ->when($request->input('type'), fn($q, $type) => $q->ofType($type))
            ->when($request->input('part_id'), fn($q, $partId) => $q->forPart($partId))
            ->when($request->input('date_from'), fn($q, $date) => $q->whereDate('posted_at', '>=', $date))
            ->when($request->input('date_to'), fn($q, $date) => $q->whereDate('posted_at', '<=', $date))
            ->orderBy('posted_at', 'desc');

        if ($request->input('per_page') == -1) {
            return response()->json([
                'data' => $query->get(),
                'warehouse' => $warehouse
            ]);
        }

        $moves = $query->paginate(50)->withQueryString();

        $moveTypes = [
            InventoryMove::TYPE_RECEIPT,
            InventoryMove::TYPE_ISSUE_TO_WORKORDER,
            InventoryMove::TYPE_ADJUSTMENT_IN,
            InventoryMove::TYPE_ADJUSTMENT_OUT,
            InventoryMove::TYPE_TRANSFER_IN,
            InventoryMove::TYPE_TRANSFER_OUT,
            InventoryMove::TYPE_REVERSAL,
        ];

        return Inertia::render('Inventory/Moves/Index', [
            'moves' => $moves,
            'warehouse' => $warehouse,
            'moveTypes' => $moveTypes,
            'filters' => $request->only(['search', 'type', 'part_id', 'date_from', 'date_to']),
        ]);
    }

    /**
     * Manual receipt (opening balance, adjustments).
     */
    public function storeReceipt(Request $request)
    {
        $this->authorize('create', [InventoryMove::class, 'receipt']);

        $validated = $request->validate([
            'warehouse_id' => 'required|exists:warehouses,id',
            'part_id' => 'required|exists:parts,id',
            'qty' => 'required|numeric|min:0.001',
            'unit_cost' => 'required|numeric|min:0',
            'notes' => 'nullable|string|max:500',
        ]);

        $move = $this->inventoryService->receipt(
            warehouseId: $validated['warehouse_id'],
            partId: $validated['part_id'],
            qty: $validated['qty'],
            unitCost: $validated['unit_cost'],
            userId: auth()->id(),
            notes: $validated['notes'] ?? null
        );

        return back()->with('success', __('inventory.moves.receipt_created'));
    }

    /**
     * Manual adjustment.
     */
    public function storeAdjustment(Request $request)
    {
        $this->authorize('create', [InventoryMove::class, 'adjustment']);

        $validated = $request->validate([
            'warehouse_id' => 'required|exists:warehouses,id',
            'part_id' => 'required|exists:parts,id',
            'qty' => 'required|numeric|not_in:0',
            'unit_cost' => 'nullable|numeric|min:0',
            'notes' => 'required|string|max:500',
        ]);

        $allowNegative = auth()->user()->can('inventory.override_negative_stock');

        $move = $this->inventoryService->adjust(
            warehouseId: $validated['warehouse_id'],
            partId: $validated['part_id'],
            qty: $validated['qty'],
            unitCost: $validated['unit_cost'] ?? null,
            userId: auth()->id(),
            notes: $validated['notes'],
            allowNegative: $allowNegative
        );

        return back()->with('success', __('inventory.moves.adjustment_created'));
    }

    /**
     * Reverse a move.
     */
    public function reverse(InventoryMove $inventoryMove)
    {
        $this->authorize('reverse', $inventoryMove);

        if (!$inventoryMove->canBeReversed()) {
            return back()->with('error', __('inventory.moves.cannot_reverse'));
        }

        $reversal = $this->inventoryService->reverseMove(
            move: $inventoryMove,
            userId: auth()->id()
        );

        return back()->with('success', __('inventory.moves.reversed'));
    }
}
