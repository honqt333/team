<?php

declare(strict_types=1);

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\InventoryTransfer;
use App\Models\InventoryTransferItem;
use App\Models\Part;
use App\Models\Warehouse;
use App\Services\Inventory\InventoryService;
use App\Support\TenancyContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class InventoryTransfersController extends Controller
{
    public function __construct(
        protected InventoryService $inventoryService
    ) {}

    /**
     * List transfers.
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', InventoryTransfer::class);

        $query = InventoryTransfer::query()
            ->with(['fromWarehouse', 'toWarehouse', 'createdByUser'])
            ->withCount('items')
            ->where('tenant_id', auth()->user()->tenant_id);

        // Status filter
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Warehouse filter (from / to)
        if ($request->filled('warehouse_id')) {
            $query->where(function ($q) use ($request) {
                $q->where('from_warehouse_id', $request->warehouse_id)
                    ->orWhere('to_warehouse_id', $request->warehouse_id);
            });
        }

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                    ->orWhereHas('fromWarehouse', fn ($w) => $w->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('toWarehouse', fn ($w) => $w->where('name', 'like', "%{$search}%"));
            });
        }

        // Whitelist sorting
        $sort = (string) $request->input('sort');
        $direction = strtolower((string) ($request->input('direction') ?? 'desc')) === 'asc' ? 'asc' : 'desc';

        if ($sort === 'code') {
            $query->orderBy('code', $direction);
        } elseif ($sort === 'status') {
            $query->orderBy('status', $direction);
        } elseif ($sort === 'created_at') {
            $query->orderBy('created_at', $direction);
        } elseif ($sort === 'from_warehouse') {
            $query->orderBy(
                Warehouse::select('name')->whereColumn('warehouses.id', 'inventory_transfers.from_warehouse_id')->limit(1),
                $direction
            );
        } elseif ($sort === 'to_warehouse') {
            $query->orderBy(
                Warehouse::select('name')->whereColumn('warehouses.id', 'inventory_transfers.to_warehouse_id')->limit(1),
                $direction
            );
        } elseif ($sort === 'items_count') {
            $query->orderBy('items_count', $direction);
        } else {
            $query->orderBy('created_at', 'desc');
        }

        $transfers = $query->paginate(25)->withQueryString();

        $warehouses = Warehouse::where('is_active', true)
            ->whereHas('center', function ($query) {
                $query->where('tenant_id', auth()->user()->tenant_id);
            })
            ->orderBy('name')
            ->get(['id', 'name', 'center_id']);

        return Inertia::render('Inventory/Transfers/Index', [
            'transfers' => $transfers,
            'filters' => array_merge(
                $request->only(['status', 'search', 'warehouse_id']),
                [
                    'sort' => $sort ?: null,
                    'direction' => $sort ? $direction : null,
                ]
            ),
            'warehouses' => $warehouses,
        ]);
    }

    /**
     * Show create form.
     */
    public function create()
    {
        $this->authorize('create', InventoryTransfer::class);

        $warehouses = Warehouse::where('is_active', true)
            ->whereHas('center', function ($query) {
                $query->where('tenant_id', auth()->user()->tenant_id);
            })
            ->orderBy('name')
            ->get(['id', 'name', 'center_id']);

        return Inertia::render('Inventory/Transfers/Form', [
            'warehouses' => $warehouses,
            'transfer' => null,
        ]);
    }

    /**
     * Store new transfer.
     */
    public function store(Request $request)
    {
        $this->authorize('create', InventoryTransfer::class);

        $validated = $request->validate([
            'from_warehouse_id' => 'required|exists:warehouses,id',
            'to_warehouse_id' => 'required|exists:warehouses,id|different:from_warehouse_id',
            'notes' => 'nullable|string|max:500',
        ]);

        $transfer = InventoryTransfer::create([
            'tenant_id' => auth()->user()->tenant_id,
            'from_warehouse_id' => $validated['from_warehouse_id'],
            'to_warehouse_id' => $validated['to_warehouse_id'],
            'notes' => $validated['notes'] ?? null,
            'created_by' => auth()->id(),
            'status' => InventoryTransfer::STATUS_DRAFT,
        ]);

        $this->logActivity('create', $transfer, "Created draft transfer from warehouse {$transfer->from_warehouse_id} to {$transfer->to_warehouse_id}");

        return redirect()->route('app.inventory.transfers.show', $transfer)
            ->with('success', __('inventory.transfers.created'));
    }

    /**
     * Show transfer details.
     */
    public function show(InventoryTransfer $transfer)
    {
        $this->authorize('view', $transfer);

        $transfer->load([
            'items.part',
            'fromWarehouse',
            'toWarehouse',
            'createdByUser',
            'sentByUser',
            'receivedByUser',
        ]);

        $parts = Part::where('tenant_id', auth()->user()->tenant_id)
            ->where('is_active', true)
            ->orderBy('name_ar')
            ->get(['id', 'sku', 'name_ar', 'name_en']);

        return Inertia::render('Inventory/Transfers/Show', [
            'transfer' => $transfer,
            'parts' => $parts,
        ]);
    }

    /**
     * Add item to transfer.
     */
    public function addItem(Request $request, InventoryTransfer $transfer)
    {
        $this->authorize('update', $transfer);

        if (! $transfer->canBeModified()) {
            return back()->with('error', __('inventory.transfers.cannot_modify'));
        }

        $validated = $request->validate([
            'part_id' => [
                'required',
                // The part has to belong to this tenant — a bare id
                // check would accept a part of another tenant, and
                // the item would only fail later, at send time.
                Rule::exists('parts', 'id')->where('tenant_id', TenancyContext::tenantId()),
            ],
            'qty_requested' => 'required|numeric|min:0.001',
            'notes' => 'nullable|string|max:255',
        ]);

        // Check if part already exists
        $existing = $transfer->items()->where('part_id', $validated['part_id'])->first();

        if ($existing) {
            return back()->with('error', __('inventory.transfers.item_exists'));
        }

        InventoryTransferItem::create([
            'inventory_transfer_id' => $transfer->id,
            'part_id' => $validated['part_id'],
            'qty_requested' => $validated['qty_requested'],
            'notes' => $validated['notes'] ?? null,
        ]);

        return back()->with('success', __('inventory.transfers.item_added'));
    }

    /**
     * Remove item from transfer.
     */
    public function removeItem(InventoryTransfer $transfer, InventoryTransferItem $item)
    {
        $this->authorize('update', $transfer);

        if (! $transfer->canBeModified()) {
            return back()->with('error', __('inventory.transfers.cannot_modify'));
        }

        // The item has to belong to the transfer in the URL. A bare
        // item id let a draft transfer delete an item of a transfer
        // that had already been sent — which cuts the settlement
        // trail between the issue move and the receipt move, and
        // leaves the source warehouse short.
        $transfer->items()->findOrFail($item->id)->delete();

        return back()->with('success', __('inventory.transfers.item_removed'));
    }

    /**
     * Send transfer.
     */
    public function send(InventoryTransfer $transfer)
    {
        $this->authorize('send', $transfer);

        try {
            $this->inventoryService->sendTransfer($transfer, auth()->id());
            $this->logActivity('send', $transfer, "Sent transfer from warehouse {$transfer->from_warehouse_id} to {$transfer->to_warehouse_id}");

            return back()->with('success', __('inventory.transfers.sent'));
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->with('error', $e->getMessage());
        }
    }

    /**
     * Receive transfer.
     */
    public function receive(Request $request, InventoryTransfer $transfer)
    {
        $this->authorize('receive', $transfer);

        $validated = $request->validate([
            'received_qtys' => 'nullable|array',
            'received_qtys.*' => 'numeric|min:0',
        ]);

        try {
            $this->inventoryService->receiveTransfer(
                $transfer,
                $validated['received_qtys'] ?? [],
                auth()->id()
            );
            $this->logActivity('receive', $transfer, 'Received transfer items');

            return back()->with('success', __('inventory.transfers.received'));
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->with('error', $e->getMessage());
        }
    }

    /**
     * Cancel transfer.
     */
    public function cancel(Request $request, InventoryTransfer $transfer)
    {
        $this->authorize('cancel', $transfer);

        $validated = $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);

        try {
            $this->inventoryService->cancelTransfer(
                $transfer,
                auth()->id(),
                $validated['reason'] ?? null
            );
            $this->logActivity('cancel', $transfer, 'Cancelled transfer. Reason: '.($validated['reason'] ?? 'N/A'));

            return back()->with('success', __('inventory.transfers.cancelled'));
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->with('error', $e->getMessage());
        }
    }

    /**
     * Log activity for inventory transfers.
     */
    protected function logActivity(string $action, InventoryTransfer $transfer, ?string $description = null): void
    {
        Log::info("Inventory Transfer Action: {$action}", [
            'transfer_id' => $transfer->id,
            'code' => $transfer->code,
            'user_id' => auth()->id(),
            'tenant_id' => auth()->user()->tenant_id,
            'description' => $description,
        ]);
    }
}
