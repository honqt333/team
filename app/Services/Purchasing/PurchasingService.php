<?php

namespace App\Services\Purchasing;

use App\Models\GoodsReceivedNote;
use App\Models\GrnItem;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseInvoiceLine;
use App\Services\Inventory\InventoryService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PurchasingService
{
    public function __construct(
        protected InventoryService $inventoryService
    ) {}

    // ─────────────────────────────────────────────────────────────
    // Purchase Order Operations
    // ─────────────────────────────────────────────────────────────

    /**
     * Create a new purchase order.
     */
    public function createPurchaseOrder(array $data): PurchaseOrder
    {
        return DB::transaction(function () use ($data) {
            $code = PurchaseOrder::generateCode($data['tenant_id']);

            $po = PurchaseOrder::create([
                'tenant_id' => $data['tenant_id'],
                'center_id' => $data['center_id'],
                'supplier_id' => $data['supplier_id'],
                'warehouse_id' => $data['warehouse_id'],
                'code' => $code,
                'status' => PurchaseOrder::STATUS_DRAFT,
                'order_date' => $data['order_date'] ?? now(),
                'expected_date' => $data['expected_date'] ?? null,
                'notes' => $data['notes'] ?? null,
                'terms' => $data['terms'] ?? null,
                'create_credit_invoice' => $data['create_credit_invoice'] ?? false,
                'due_date' => $data['due_date'] ?? null,
            ]);

            // Add items if provided
            if (!empty($data['items'])) {
                foreach ($data['items'] as $item) {
                    $this->addPurchaseOrderItem($po, $item);
                }
            }

            return $po->fresh(['items.part', 'supplier', 'warehouse']);
        });
    }

    /**
     * Add an item to a purchase order.
     */
    public function addPurchaseOrderItem(PurchaseOrder $po, array $data): PurchaseOrderItem
    {
        if (!$po->isDraft()) {
            throw ValidationException::withMessages([
                'status' => ['Cannot modify a sent purchase order.'],
            ]);
        }

        return PurchaseOrderItem::create([
            'purchase_order_id' => $po->id,
            'part_id' => $data['part_id'],
            'qty_ordered' => $data['qty_ordered'],
            'unit_cost' => $data['unit_cost'],
            'tax_rate' => $data['tax_rate'] ?? 15.00,
            'notes' => $data['notes'] ?? null,
        ]);
    }

    /**
     * Update a purchase order item.
     */
    public function updatePurchaseOrderItem(PurchaseOrderItem $item, array $data): PurchaseOrderItem
    {
        if (!$item->purchaseOrder->isDraft()) {
            throw ValidationException::withMessages([
                'status' => ['Cannot modify a sent purchase order.'],
            ]);
        }

        $item->update($data);

        return $item->fresh();
    }

    /**
     * Remove an item from a purchase order.
     */
    public function removePurchaseOrderItem(PurchaseOrderItem $item): void
    {
        if (!$item->purchaseOrder->isDraft()) {
            throw ValidationException::withMessages([
                'status' => ['Cannot modify a sent purchase order.'],
            ]);
        }

        $item->delete();
    }

    /**
     * Send a purchase order to supplier.
     */
    public function sendPurchaseOrder(PurchaseOrder $po, int $userId): PurchaseOrder
    {
        if (!$po->canBeSent()) {
            throw ValidationException::withMessages([
                'status' => ['Cannot send this purchase order.'],
            ]);
        }

        $po->update([
            'status' => PurchaseOrder::STATUS_SENT,
            'sent_at' => now(),
            'sent_by' => $userId,
        ]);

        return $po->fresh();
    }

    /**
     * Cancel a purchase order.
     */
    public function cancelPurchaseOrder(PurchaseOrder $po, int $userId, ?string $reason = null): PurchaseOrder
    {
        if (!$po->canBeCancelled()) {
            throw ValidationException::withMessages([
                'status' => ['Cannot cancel this purchase order.'],
            ]);
        }

        $po->update([
            'status' => PurchaseOrder::STATUS_CANCELLED,
            'cancelled_at' => now(),
            'cancelled_by' => $userId,
            'cancel_reason' => $reason,
        ]);

        return $po->fresh();
    }

    // ─────────────────────────────────────────────────────────────
    // Goods Received Note Operations
    // ─────────────────────────────────────────────────────────────

    /**
     * Create a GRN for a purchase order.
     */
    public function createGoodsReceivedNote(PurchaseOrder $po, array $data): GoodsReceivedNote
    {
        if (!$po->canBeReceived()) {
            throw ValidationException::withMessages([
                'status' => ['Cannot receive goods for this order.'],
            ]);
        }

        return DB::transaction(function () use ($po, $data) {
            $code = GoodsReceivedNote::generateCode($po->tenant_id);

            $grn = GoodsReceivedNote::create([
                'purchase_order_id' => $po->id,
                'warehouse_id' => $data['warehouse_id'] ?? $po->warehouse_id,
                'code' => $code,
                'status' => GoodsReceivedNote::STATUS_DRAFT,
                'received_date' => $data['received_date'] ?? now(),
                'delivery_note' => $data['delivery_note'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);

            // Add items
            if (!empty($data['items'])) {
                foreach ($data['items'] as $item) {
                    $poItem = PurchaseOrderItem::findOrFail($item['purchase_order_item_id']);
                    
                    // Validate quantity (cannot receive more than pending)
                    if ($item['qty_received'] > $poItem->qty_pending) {
                        throw ValidationException::withMessages([
                            "items.{$item['purchase_order_item_id']}" => [
                                __('purchasing.grn.over_receiving_error', [
                                    'part' => $poItem->part->name_ar ?? $poItem->part->name_en,
                                    'pending' => $poItem->qty_pending
                                ])
                            ],
                        ]);
                    }

                    GrnItem::create([
                        'goods_received_note_id' => $grn->id,
                        'purchase_order_item_id' => $item['purchase_order_item_id'],
                        'part_id' => $item['part_id'],
                        'qty_received' => $item['qty_received'],
                        'unit_cost' => $item['unit_cost'],
                        'notes' => $item['notes'] ?? null,
                    ]);
                }
            }

            return $grn->fresh(['items.part', 'purchaseOrder']);
        });
    }

    /**
     * Post a GRN - creates inventory receipt moves.
     */
    public function postGoodsReceivedNote(GoodsReceivedNote $grn, int $userId): GoodsReceivedNote
    {
        if (!$grn->canBePosted()) {
            throw ValidationException::withMessages([
                'status' => ['Cannot post this GRN.'],
            ]);
        }

        return DB::transaction(function () use ($grn, $userId) {
            $po = $grn->purchaseOrder;

            // Process each GRN item
            foreach ($grn->items as $grnItem) {
                // Create inventory receipt move
                $move = $this->inventoryService->receipt(
                    warehouseId: $grn->warehouse_id,
                    partId: $grnItem->part_id,
                    qty: (float) $grnItem->qty_received,
                    unitCost: (float) $grnItem->unit_cost,
                    userId: $userId,
                    referenceType: GrnItem::class,
                    referenceId: $grnItem->id,
                    notes: "GRN: {$grn->code}"
                );

                // Link move to GRN item
                $grnItem->update(['inventory_move_id' => $move->id]);

                // Update PO item received qty
                $poItem = $grnItem->purchaseOrderItem;
                $poItem->increment('qty_received', $grnItem->qty_received);
            }

            // Update GRN status
            $grn->update([
                'status' => GoodsReceivedNote::STATUS_POSTED,
                'posted_at' => now(),
                'posted_by' => $userId,
            ]);

            // Update PO status based on received quantities
            $this->updatePurchaseOrderStatus($po);

            // Create invoice from GRN (Wrapped in try-catch to prevent blocking GRN posting)
            try {
                $this->createInvoiceFromGrn($grn);
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error("Failed to auto-create invoice for GRN {$grn->code}: " . $e->getMessage());
            }

            return $grn->fresh();
        });
    }

    /**
     * Cancel a GRN (only if draft).
     */
    public function cancelGoodsReceivedNote(GoodsReceivedNote $grn, int $userId, ?string $reason = null): GoodsReceivedNote
    {
        if (!$grn->canBeCancelled()) {
            throw ValidationException::withMessages([
                'status' => ['Cannot cancel a posted GRN. Reverse the inventory moves instead.'],
            ]);
        }

        $grn->update([
            'status' => GoodsReceivedNote::STATUS_CANCELLED,
            'cancelled_at' => now(),
            'cancelled_by' => $userId,
            'cancel_reason' => $reason,
        ]);

        return $grn->fresh();
    }

    /**
     * Update PO status based on item receipts.
     */
    protected function updatePurchaseOrderStatus(PurchaseOrder $po): void
    {
        $po->load('items');
        
        $allReceived = $po->items->every(fn($item) => $item->is_fully_received);
        $anyReceived = $po->items->some(fn($item) => $item->qty_received > 0);

        if ($allReceived) {
            $po->update(['status' => PurchaseOrder::STATUS_RECEIVED]);
        } elseif ($anyReceived) {
            $po->update(['status' => PurchaseOrder::STATUS_PARTIAL]);
        }
    }

    /**
     * Create a purchase invoice from a GRN.
     */
    public function createInvoiceFromGrn(GoodsReceivedNote $grn): PurchaseInvoice
    {
        return DB::transaction(function () use ($grn) {
            $po = $grn->purchaseOrder;
            
            $invoice = PurchaseInvoice::create([
                'tenant_id' => $po->tenant_id,
                'center_id' => $po->center_id,
                'supplier_id' => $po->supplier_id,
                'purchase_order_id' => $po->id,
                'invoice_number' => $grn->delivery_note, // Use delivery note as reference
                'code' => PurchaseInvoice::generateCode($po->tenant_id),
                'issue_date' => $grn->received_date,
                'due_date' => $po->due_date,
                'status' => PurchaseInvoice::STATUS_OPEN,
                'notes' => "Auto-generated from GRN: {$grn->code}",
            ]);

            $grn->update(['purchase_invoice_id' => $invoice->id]);

            $subtotal = 0;
            $taxAmount = 0;

            foreach ($grn->items as $item) {
                $poItem = $item->purchaseOrderItem;
                $taxRate = $poItem->tax_rate ?? 15.00;
                
                $lineSubtotal = $item->line_total; // calculated as qty_received * unit_cost
                $lineTax = bcdiv(bcmul($lineSubtotal, $taxRate, 4), 100, 2);
                $lineTotal = bcadd($lineSubtotal, $lineTax, 2);

                PurchaseInvoiceLine::create([
                    'purchase_invoice_id' => $invoice->id,
                    'part_id' => $item->part_id,
                    'qty' => $item->qty_received,
                    'unit_cost' => $item->unit_cost,
                    'tax_rate' => $taxRate,
                    'tax_amount' => $lineTax,
                    'total' => $lineTotal,
                ]);

                $subtotal = bcadd($subtotal, $lineSubtotal, 2);
                $taxAmount = bcadd($taxAmount, $lineTax, 2);
            }

            $invoice->update([
                'subtotal' => $subtotal,
                'tax_amount' => $taxAmount,
                'total' => bcadd($subtotal, $taxAmount, 2),
            ]);

            return $invoice;
        });
    }
}
