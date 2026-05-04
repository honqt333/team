<?php

namespace App\Services;

use App\Models\Quote;
use App\Models\User;
use App\Models\WorkOrder;
use App\Models\WorkOrderItem;
use App\Models\WorkOrderItemPart;
use App\Support\TenancyContext;
use Illuminate\Support\Facades\DB;

class QuoteConversionService
{
    /**
     * Convert an approved quote to a work order.
     *
     * @throws \InvalidArgumentException If quote cannot be converted
     * @throws \RuntimeException If conversion fails
     */
    public function convert(Quote $quote, User $user): WorkOrder
    {
        // Validate quote can be converted
        if ($quote->isConverted()) {
            throw new \InvalidArgumentException('Quote has already been converted.');
        }

        if (!$quote->isApproved()) {
            throw new \InvalidArgumentException('Quote must be approved before conversion.');
        }

        return DB::transaction(function () use ($quote, $user) {
            // Generate work order code
            $code = WorkOrder::generateCode($quote->tenant_id, $quote->center_id);

            // Create work order
            $workOrder = WorkOrder::create([
                'tenant_id' => $quote->tenant_id,
                'center_id' => $quote->center_id,
                'customer_id' => $quote->customer_id,
                'vehicle_id' => $quote->vehicle_id,
                'quote_id' => $quote->id,
                'code' => $code,
                'status' => WorkOrder::STATUS_OPEN,
                'notes' => $quote->notes,
                'opened_at' => now(),
                'entry_date' => now()->toDateString(),
                'expected_end_date' => now()->toDateString(),
            ]);

            // Copy quote lines to work order items
            $lineIdMap = [];
            foreach ($quote->lines as $quoteLine) {
                $workOrderItem = WorkOrderItem::create([
                    'work_order_id' => $workOrder->id,
                    'service_id' => $quoteLine->service_id,
                    'tenant_id' => $quote->tenant_id,
                    'center_id' => $quote->center_id,
                    'title' => $quoteLine->description,
                    'qty' => $quoteLine->qty,
                    'unit_price' => $quoteLine->unit_price,
                    'base_price_snapshot' => $quoteLine->base_price_snapshot,
                    'min_price_snapshot' => $quoteLine->min_price_snapshot,
                    'discount_type' => $quoteLine->discount_type,
                    'discount_value' => $quoteLine->discount_value,
                    'discount_amount' => $quoteLine->discount_amount,
                    'final_unit_price' => $quoteLine->final_unit_price,
                    'line_total' => $quoteLine->line_total,
                    'price_locked' => true, // Lock prices from quote
                    'total' => $quoteLine->line_total, // Legacy field
                ]);
                $lineIdMap[$quoteLine->id] = $workOrderItem->id;
            }

            // Copy quote parts to work order item parts
            foreach ($quote->parts as $quotePart) {
                WorkOrderItemPart::create([
                    'work_order_id' => $workOrder->id,
                    'work_order_item_id' => $quotePart->quote_line_id ? ($lineIdMap[$quotePart->quote_line_id] ?? null) : null,
                    'tenant_id' => $quote->tenant_id,
                    'center_id' => $quote->center_id,
                    'part_id' => $quotePart->part_id,
                    'unit_id' => $quotePart->unit_id,
                    'source' => $quotePart->source,
                    'name' => $quotePart->name,
                    'part_number' => $quotePart->part_number,
                    'notes' => $quotePart->description,
                    'qty' => $quotePart->qty,
                    'unit_price' => $quotePart->unit_price,
                    'discount' => $quotePart->discount,
                    'total' => $quotePart->total,
                    'include_in_package' => $quotePart->include_in_package,
                    'hide_on_print' => $quotePart->hide_on_print,
                    'status' => WorkOrderItemPart::STATUS_PENDING,
                ]);
            }

            // Update quote status
            $quote->update([
                'status' => Quote::STATUS_CONVERTED,
                'converted_at' => now(),
                'converted_work_order_id' => $workOrder->id,
            ]);

            return $workOrder;
        });
    }
}
