<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\InventoryTransfer;
use App\Models\InventoryTransferItem;
use App\Models\Part;
use App\Models\Warehouse;
use App\Services\Inventory\InventoryService;
use Illuminate\Http\Request;
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
            ->where('tenant_id', auth()->user()->tenant_id)
            ->orderBy('created_at', 'desc');

        // Status filter
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Search
        if ($request->filled('search')) {
            $query->where('code', 'like', '%' . $request->search . '%');
        }

        $transfers = $query->paginate(20)->withQueryString();

        $warehouses = Warehouse::where('is_active', true)
            ->whereHas('center', function ($query) {
                $query->where('tenant_id', auth()->user()->tenant_id);
            })
            ->orderBy('name')
            ->get(['id', 'name', 'center_id']);

        return Inertia::render('Inventory/Transfers/Index', [
            'transfers' => $transfers,
            'filters' => $request->only(['status', 'search']),
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

        if (!$transfer->canBeModified()) {
            return back()->with('error', __('inventory.transfers.cannot_modify'));
        }

        $validated = $request->validate([
            'part_id' => 'required|exists:parts,id',
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

        if (!$transfer->canBeModified()) {
            return back()->with('error', __('inventory.transfers.cannot_modify'));
        }

        $item->delete();

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
            return back()->with('success', __('inventory.transfers.cancelled'));
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->with('error', $e->getMessage());
        }
    }
}
