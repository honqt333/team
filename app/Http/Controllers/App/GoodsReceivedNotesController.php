<?php

declare(strict_types=1);

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\GoodsReceivedNote;
use App\Models\PurchaseOrder;
use App\Services\Purchasing\PurchasingService;
use App\Support\TenancyContext;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class GoodsReceivedNotesController extends Controller
{
    public function __construct(
        protected PurchasingService $purchasingService
    ) {}

    /**
     * Create GRN for a purchase order.
     *
     * A GRN is created from the "Receive" modal on the purchase order
     * page (Components/Purchasing/GrnModal.vue), which posts to the
     * same store() endpoint. This action used to render a standalone
     * "Purchasing/GRN/Form" page that never existed as a Vue file, and
     * it had no route registered — unreachable dead code.
     *
     * It now redirects to the purchase order that the GRN is for, so
     * that adding a route for it later cannot reintroduce a 500.
     */
    public function create(PurchaseOrder $purchaseOrder): RedirectResponse
    {
        $this->authorize('create', GoodsReceivedNote::class);

        return redirect()->route('app.purchasing.orders.show', $purchaseOrder);
    }

    /**
     * Store a new GRN.
     */
    public function store(Request $request, PurchaseOrder $purchaseOrder)
    {
        $this->authorize('create', GoodsReceivedNote::class);

        $validated = $request->validate([
            'warehouse_id' => [
                'required',
                // The warehouse has to belong to this tenant.
                Rule::exists('warehouses', 'id')->where('tenant_id', TenancyContext::tenantId()),
            ],
            'received_date' => 'required|date',
            'delivery_note' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:1000',
            'items' => 'required|array|min:1',
            // The line has to belong to the purchase order in the URL,
            // and the part is derived from that line — the service
            // refuses a line of another order and a mismatched part.
            'items.*.purchase_order_item_id' => 'required|integer',
            'items.*.part_id' => 'nullable|integer',
            'items.*.qty_received' => 'required|numeric|min:0.001',
            'items.*.unit_cost' => 'required|numeric|min:0',
        ]);

        $grn = $this->purchasingService->createGoodsReceivedNote($purchaseOrder, $validated);

        if ($request->boolean('post_now')) {
            // Posting is a different ability from creating the note:
            // it writes the inventory movements, credits the ordered
            // quantity and creates the purchase invoice. The policy
            // requires INVENTORY_MOVES_CREATE for it, while creating
            // the note only requires POS_VIEW. Without this check a
            // user who may only read purchase orders could post a GRN
            // through the create endpoint and move stock.
            $this->authorize('post', $grn);

            $this->purchasingService->postGoodsReceivedNote($grn, auth()->id());
        }

        return redirect()->route('app.purchasing.grn.show', $grn->id)
            ->with('success', $request->boolean('post_now') ? __('purchasing.grn.posted') : __('purchasing.grn.created'));
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
            'purchaseInvoice',
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

    /**
     * Create invoice from GRN manually.
     */
    public function createInvoice(GoodsReceivedNote $goodsReceivedNote)
    {
        $this->authorize('update', $goodsReceivedNote);

        if ($goodsReceivedNote->purchase_invoice_id) {
            return back()->with('error', __('invoices.already_exists'));
        }

        if ($goodsReceivedNote->status !== GoodsReceivedNote::STATUS_POSTED) {
            return back()->with('error', 'يجب ترحيل السند أولاً قبل إنشاء الفاتورة');
        }

        try {
            $this->purchasingService->createInvoiceFromGrn($goodsReceivedNote);

            return back()->with('success', __('invoices.created'));
        } catch (Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
