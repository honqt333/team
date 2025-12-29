<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\GoodsReceivedNote;
use App\Models\PurchaseOrder;
use App\Services\Purchasing\PurchasingService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class GoodsReceivedNotesController extends Controller
{
    public function __construct(
        protected PurchasingService $purchasingService
    ) {}

    /**
     * Create GRN for a purchase order.
     */
    public function create(PurchaseOrder $purchaseOrder)
    {
        $this->authorize('create', GoodsReceivedNote::class);

        $purchaseOrder->load(['items.part', 'supplier', 'warehouse']);

        // Prepare items with pending quantities
        $pendingItems = $purchaseOrder->items->map(fn($item) => [
            'purchase_order_item_id' => $item->id,
            'part_id' => $item->part_id,
            'part' => $item->part,
            'qty_ordered' => $item->qty_ordered,
            'qty_received' => $item->qty_received,
            'qty_pending' => $item->qty_pending,
            'unit_cost' => $item->unit_cost,
        ])->filter(fn($item) => $item['qty_pending'] > 0);

        return Inertia::render('Purchasing/GRN/Form', [
            'purchaseOrder' => $purchaseOrder,
            'pendingItems' => $pendingItems->values(),
        ]);
    }

    /**
     * Store a new GRN.
     */
    public function store(Request $request, PurchaseOrder $purchaseOrder)
    {
        $this->authorize('create', GoodsReceivedNote::class);

        $validated = $request->validate([
            'warehouse_id' => 'required|exists:warehouses,id',
            'received_date' => 'required|date',
            'delivery_note' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:1000',
            'items' => 'required|array|min:1',
            'items.*.purchase_order_item_id' => 'required|exists:purchase_order_items,id',
            'items.*.part_id' => 'required|exists:parts,id',
            'items.*.qty_received' => 'required|numeric|min:0.001',
            'items.*.unit_cost' => 'required|numeric|min:0',
        ]);

        $grn = $this->purchasingService->createGoodsReceivedNote($purchaseOrder, $validated);

        return redirect()->route('app.purchasing.grn.show', $grn->id)
            ->with('success', __('purchasing.grn.created'));
    }

    /**
     * Show GRN details.
     */
    public function show(GoodsReceivedNote $goodsReceivedNote)
    {
        $this->authorize('view', $goodsReceivedNote);

        $goodsReceivedNote->load([
            'purchaseOrder.supplier',
            'warehouse',
            'items.part',
            'postedByUser:id,name',
        ]);

        return Inertia::render('Purchasing/GRN/Show', [
            'grn' => $goodsReceivedNote,
        ]);
    }

    /**
     * Post GRN - creates inventory receipt moves.
     */
    public function post(GoodsReceivedNote $goodsReceivedNote)
    {
        $this->authorize('post', $goodsReceivedNote);

        $this->purchasingService->postGoodsReceivedNote($goodsReceivedNote, auth()->id());

        return back()->with('success', __('purchasing.grn.posted'));
    }

    /**
     * Cancel a draft GRN.
     */
    public function cancel(Request $request, GoodsReceivedNote $goodsReceivedNote)
    {
        $this->authorize('cancel', $goodsReceivedNote);

        $validated = $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);

        $this->purchasingService->cancelGoodsReceivedNote($goodsReceivedNote, auth()->id(), $validated['reason'] ?? null);

        return back()->with('success', __('purchasing.grn.cancelled'));
    }
}
