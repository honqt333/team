<?php

use App\Models\PurchaseOrder;
use App\Models\GoodsReceivedNote;
use App\Models\InventoryBalance;
use App\Models\InventoryMove;

$po = PurchaseOrder::latest()->first();
if (!$po) {
    echo "No PO found\n";
    exit;
}

echo "PO: {$po->code} (Status: {$po->status})\n";
foreach ($po->items as $item) {
    echo "  Item: {$item->part->sku} | Ordered: {$item->qty_ordered} | Received: {$item->qty_received}\n";
}

$grns = $po->goodsReceivedNotes;
echo "GRNs: " . $grns->count() . "\n";
foreach ($grns as $grn) {
    echo "  GRN: {$grn->code} (Status: {$grn->status})\n";
    foreach ($grn->items as $item) {
        echo "    Item: {$item->part->sku} | Received: {$item->qty_received} | Move ID: {$item->inventory_move_id}\n";
        
        $balance = InventoryBalance::where('warehouse_id', $grn->warehouse_id)
            ->where('part_id', $item->part_id)
            ->first();
        echo "      Balance in Warehouse {$grn->warehouse_id}: " . ($balance->qty_on_hand ?? 0) . "\n";
        
        if ($item->inventory_move_id) {
            $move = InventoryMove::find($item->inventory_move_id);
            echo "      Move found: Qty {$move->qty} | Balance After: {$move->balance_after}\n";
        }
    }
}
