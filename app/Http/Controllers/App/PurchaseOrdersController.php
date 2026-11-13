<?php

declare(strict_types=1);

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\InventoryUnit;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\Warehouse;
use App\Services\Purchasing\PurchasingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PurchaseOrdersController extends Controller
{
    public function __construct(
        protected PurchasingService $purchasingService
    ) {}

    public function index(Request $request)
    {
        $this->authorize('viewAny', PurchaseOrder::class);

        $user = auth()->user();
        $centerId = $user->current_center_id;

        $query = PurchaseOrder::forTenant($user->tenant_id)
            ->forCenter($centerId)
            ->with(['supplier:id,name', 'warehouse:id,name'])
            ->withCount('items')
            ->when($request->input('search'), function ($q, $search) {
                $q->where('code', 'like', "%{$search}%")
                    ->orWhereHas('supplier', fn ($sq) => $sq->where('name', 'like', "%{$search}%"));
            })
            ->when($request->input('status'), fn ($q, $status) => $q->ofStatus($status))
            ->when($request->input('supplier_id'), fn ($q, $id) => $q->where('supplier_id', $id));

        $sort = (string) $request->input('sort');
        $direction = strtolower((string) $request->input('direction', 'desc')) === 'asc' ? 'asc' : 'desc';

        if ($sort === 'code') {
            $query->orderBy('code', $direction);
        } elseif ($sort === 'supplier') {
            $query->orderBy(
                Supplier::select('name')
                    ->whereColumn('suppliers.id', 'purchase_orders.supplier_id')
                    ->limit(1),
                $direction
            );
        } elseif ($sort === 'order_date') {
            $query->orderBy('order_date', $direction);
        } elseif ($sort === 'total') {
            $query->orderBy('total', $direction);
        } elseif ($sort === 'status') {
            $query->orderBy('status', $direction);
        } elseif ($sort === 'created_at') {
            $query->orderBy('created_at', $direction);
        } else {
            $query->orderByDesc('created_at');
        }

        $filters = array_merge(
            $request->only(['search', 'status', 'supplier_id']),
            [
                'sort' => $sort ?: null,
                'direction' => $sort ? $direction : null,
            ]
        );

        if ($request->boolean('print')) {
            return Inertia::render('Reports/Print', [
                'reportType' => 'purchasing_orders',
                'backRoute' => 'app.purchasing.orders.index',
                'filters' => $filters,
                'kpis' => [],
                'rows' => $query->limit(5000)->get(),
                'companyInfo' => [],
            ]);
        }

        $orders = $query->paginate(25)->withQueryString();

        $suppliers = Supplier::forTenant($user->tenant_id)->forCenter($centerId)->active()->get(['id', 'name']);

        return Inertia::render('Purchasing/Orders/Index', [
            'orders' => $orders,
            'suppliers' => $suppliers,
            'filters' => array_merge(
                $request->only(['search', 'status', 'supplier_id']),
                [
                    'sort' => $sort ?: null,
                    'direction' => $sort ? $direction : null,
                ]
            ),
            'statuses' => [
                PurchaseOrder::STATUS_DRAFT,
                PurchaseOrder::STATUS_SENT,
                PurchaseOrder::STATUS_PARTIAL,
                PurchaseOrder::STATUS_RECEIVED,
                PurchaseOrder::STATUS_CANCELLED,
            ],
            'defaultWarehouse' => Warehouse::forCenter($centerId)->default()->first(),
            'warehouses' => Warehouse::forCenter($centerId)->active()->get(['id', 'name']),
            'units' => InventoryUnit::where('is_active', true)->get(['id', 'name_ar', 'name_en']),
        ]);
    }

    /**
     * Create a purchase order.
     *
     * The form is a modal on the orders index page
     * (Pages/Purchasing/Orders/Index.vue), which posts to the same
     * store() endpoint. This action used to render a standalone
     * "Purchasing/Orders/Form" page that never existed as a Vue file,
     * so the route returned HTTP 500 with "Unable to locate file in
     * Vite manifest". Nothing in the UI linked to it.
     *
     * It now redirects to the index page that hosts the form.
     */
    public function create(): RedirectResponse
    {
        $this->authorize('create', PurchaseOrder::class);

        return redirect()->route('app.purchasing.orders.index');
    }

    public function store(Request $request)
    {
        $this->authorize('create', PurchaseOrder::class);

        $user = auth()->user();

        $validated = $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'warehouse_id' => 'required|exists:warehouses,id',
            'order_date' => 'required|date',
            'expected_date' => 'nullable|date|after_or_equal:order_date',
            'create_credit_invoice' => 'nullable|boolean',
            'due_date' => 'nullable|date',
            'notes' => 'nullable|string|max:1000',
            'terms' => 'nullable|string|max:2000',
            'items' => 'required|array|min:1',
            'items.*.part_id' => 'required|exists:parts,id',
            'items.*.qty_ordered' => 'required|numeric|min:0.001',
            'items.*.unit_cost' => 'required|numeric|min:0',
            'items.*.tax_rate' => 'nullable|numeric|min:0|max:100',
            'items.*.purchase_unit_id' => 'nullable|exists:inventory_units,id',
            'items.*.purchase_conversion_factor' => 'nullable|numeric|min:0.0001',
        ]);

        $validated['tenant_id'] = $user->tenant_id;
        $validated['center_id'] = $user->current_center_id;

        // Append payment info to notes if present
        if ($request->has('payments') && count($request->input('payments')) > 0) {
            $paymentInfo = "\n\n--- Start Payment Details ---\n";
            $paymentInfo .= 'Payment Type: '.ucfirst($request->input('payment_type', 'unknown'))."\n";

            foreach ($request->input('payments') as $payment) {
                $paymentInfo .= sprintf(
                    "- Method: %s, Amount: %s, Date: %s, Notes: %s\n",
                    $payment['payment_method'] ?? 'N/A',
                    $payment['amount'] ?? 0,
                    $payment['payment_date'] ?? 'N/A',
                    $payment['notes'] ?? ''
                );
            }
            $paymentInfo .= '--- End Payment Details ---';

            $validated['notes'] = ($validated['notes'] ?? '').$paymentInfo;
        } elseif ($request->input('payment_type') === 'deferred') {
            $validated['notes'] = ($validated['notes'] ?? '')."\n\n[Marked as Deferred/Credit]";
        }

        $order = $this->purchasingService->createPurchaseOrder($validated);

        return redirect()->route('app.purchasing.orders.show', $order->id)
            ->with('success', __('purchasing.orders.created'));
    }

    public function show(PurchaseOrder $purchaseOrder)
    {
        $this->authorize('view', $purchaseOrder);

        $purchaseOrder->load([
            'supplier',
            'warehouse',
            'items.part',
            'goodsReceivedNotes.items',
            'sentByUser:id,name',
        ]);

        return Inertia::render('Purchasing/Orders/Show', [
            'order' => $purchaseOrder,
        ]);
    }

    public function update(Request $request, PurchaseOrder $purchaseOrder)
    {
        $this->authorize('update', $purchaseOrder);

        if (! $purchaseOrder->isDraft()) {
            return back()->with('error', __('purchasing.orders.cannot_modify_sent'));
        }

        $validated = $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'warehouse_id' => 'required|exists:warehouses,id',
            'order_date' => 'required|date',
            'expected_date' => 'nullable|date|after_or_equal:order_date',
            'notes' => 'nullable|string|max:1000',
            'terms' => 'nullable|string|max:2000',
        ]);

        $purchaseOrder->update($validated);

        return back()->with('success', __('purchasing.orders.updated'));
    }

    public function send(PurchaseOrder $purchaseOrder)
    {
        $this->authorize('send', $purchaseOrder);

        $this->purchasingService->sendPurchaseOrder($purchaseOrder, auth()->id());

        return back()->with('success', __('purchasing.orders.sent'));
    }

    public function cancel(Request $request, PurchaseOrder $purchaseOrder)
    {
        $this->authorize('cancel', $purchaseOrder);

        $validated = $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);

        $this->purchasingService->cancelPurchaseOrder($purchaseOrder, auth()->id(), $validated['reason'] ?? null);

        return back()->with('success', __('purchasing.orders.cancelled'));
    }

    /**
     * Add item to purchase order.
     */
    public function addItem(Request $request, PurchaseOrder $purchaseOrder)
    {
        $this->authorize('update', $purchaseOrder);

        $validated = $request->validate([
            'part_id' => 'required|exists:parts,id',
            'qty_ordered' => 'required|numeric|min:0.001',
            'unit_cost' => 'required|numeric|min:0',
            'tax_rate' => 'nullable|numeric|min:0|max:100',
            'purchase_unit_id' => 'nullable|exists:inventory_units,id',
            'purchase_conversion_factor' => 'nullable|numeric|min:0.0001',
        ]);

        $this->purchasingService->addPurchaseOrderItem($purchaseOrder, $validated);

        return back()->with('success', __('purchasing.orders.item_added'));
    }

    /**
     * Remove item from purchase order.
     */
    public function removeItem(PurchaseOrder $purchaseOrder, int $itemId)
    {
        $this->authorize('update', $purchaseOrder);

        $item = $purchaseOrder->items()->findOrFail($itemId);
        $this->purchasingService->removePurchaseOrderItem($item);

        return back()->with('success', __('purchasing.orders.item_removed'));
    }
}
