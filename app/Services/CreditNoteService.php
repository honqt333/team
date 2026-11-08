<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\InvoiceType;
use App\Enums\PaymentType;
use App\Models\CenterSequence;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Tenant;
use App\Models\User;
use App\Models\WorkOrder;
use App\Models\WorkOrderItem;
use App\Models\WorkOrderItemPart;
use App\Services\Inventory\InventoryService;
use Exception;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class CreditNoteService
{
    public function __construct(
        protected InvoiceService $invoiceService,
        protected InventoryService $inventoryService
    ) {}

    /**
     * Get returnable items summary for a work order.
     */
    public function getReturnableSummary(WorkOrder $workOrder): array
    {
        $originalInvoice = $workOrder->invoice;

        if (! $originalInvoice) {
            throw new InvalidArgumentException(__('invoices.original_invoice_not_found'));
        }

        $taxEnabled = (bool) ($originalInvoice->tax_enabled_snapshot ?? false);

        if (! $taxEnabled && ((float) $originalInvoice->total_tax > 0.001 || (bool) ($workOrder->tax_enabled_snapshot ?? false))) {
            $taxEnabled = true;
        }
        $taxRate = $taxEnabled ? (float) ($originalInvoice->tax_rate_snapshot ?: $workOrder->tax_rate_snapshot ?: 15.00) : 0.0;
        $pricingMode = $originalInvoice->pricing_mode_snapshot ?? $workOrder->pricing_mode_snapshot ?? 'exclusive';

        // Existing credit notes for this invoice or work order
        $creditNoteIds = Invoice::withoutGlobalScopes()
            ->where(function ($q) use ($workOrder, $originalInvoice) {
                $q->where('original_invoice_id', $originalInvoice->id)
                    ->orWhere('work_order_id', $workOrder->id);
            })
            ->where('type', InvoiceType::CREDIT_NOTE)
            ->where('status', '!=', 'cancelled')
            ->pluck('id');

        $creditLines = DB::table('invoice_lines')
            ->whereIn('invoice_id', $creditNoteIds)
            ->get();

        // 1. Returnable services
        $activeItems = $workOrder->items()
            ->where('status', '!=', WorkOrderItem::STATUS_CANCELLED)
            ->get();

        $returnableServices = $activeItems->map(function ($item) use ($creditLines, $taxEnabled, $taxRate) {
            $itemTotal = (float) $item->total;
            $itemLineIncl = (float) $item->line_total_incl_tax;
            $itemLineExcl = (float) $item->line_total_excl_tax;
            $itemLineTotal = (float) $item->line_total;
            $itemUnitPrice = (float) $item->final_unit_price ?: (float) $item->unit_price;

            if ($taxEnabled) {
                $originalTotal = $itemLineIncl > 0 ? $itemLineIncl : ($itemTotal > 0 ? $itemTotal : $itemLineTotal);
                $originalExcl = $itemLineExcl > 0 ? $itemLineExcl : ($itemLineTotal > 0 ? $itemLineTotal : $itemUnitPrice);
            } else {
                $originalTotal = $itemTotal > 0 ? $itemTotal : ($itemLineTotal > 0 ? $itemLineTotal : $itemUnitPrice);
                $originalExcl = $originalTotal;
            }

            $previouslyReturnedExcl = (float) $creditLines
                ->filter(function ($l) use ($item) {
                    if (isset($l->work_order_item_id) && $l->work_order_item_id !== null) {
                        return (int) $l->work_order_item_id === (int) $item->id;
                    }

                    return empty($l->is_part) && str_contains((string) $l->description, (string) $item->title);
                })
                ->sum('line_total_excl_tax');

            $refundableAmount = max(0.0, round($originalExcl - $previouslyReturnedExcl, 2));

            return [
                'id' => $item->id,
                'work_order_item_id' => $item->id,
                'name' => $item->title,
                'title' => $item->title,
                'department_name' => $item->department?->name,
                'original_cost' => $originalExcl,
                'original_subtotal' => $originalExcl,
                'original_total' => $originalTotal,
                'returned_amount_previous' => $previouslyReturnedExcl,
                'returned_subtotal' => $previouslyReturnedExcl,
                'refundable_amount' => $refundableAmount,
                'remaining_refundable_subtotal' => $refundableAmount,
                'tax_rate' => $taxRate,
            ];
        })->values();

        // 2. Returnable parts
        $activeParts = $workOrder->parts()
            ->whereNotIn('status', [WorkOrderItemPart::STATUS_CANCELLED, WorkOrderItemPart::STATUS_REVERSED])
            ->get();

        $returnableParts = $activeParts->map(function ($part) use ($creditLines, $taxRate) {
            $previouslyReturnedQty = (float) $creditLines
                ->filter(function ($l) use ($part) {
                    if (isset($l->work_order_item_part_id) && $l->work_order_item_part_id !== null) {
                        return (int) $l->work_order_item_part_id === (int) $part->id;
                    }

                    if (! empty($l->is_part)) {
                        if (! empty($part->part_id) && ! empty($l->part_id)) {
                            return (int) $l->part_id === (int) $part->part_id;
                        }

                        return ! empty($part->name) && str_contains((string) $l->description, (string) $part->name);
                    }

                    return false;
                })
                ->sum('qty');

            $originalQty = (float) $part->qty;
            $refundableQty = max(0.0, round($originalQty - $previouslyReturnedQty, 2));

            return [
                'id' => $part->id,
                'work_order_item_part_id' => $part->id,
                'part_id' => $part->part_id,
                'name' => $part->name,
                'part_number' => $part->part_number,
                'unit_price' => (float) $part->unit_price,
                'discount' => (float) $part->discount,
                'original_qty' => $originalQty,
                'returned_qty_previous' => $previouslyReturnedQty,
                'returned_qty' => $previouslyReturnedQty,
                'refundable_qty' => $refundableQty,
                'remaining_refundable_qty' => $refundableQty,
                'tax_rate' => $taxRate,
                'total' => (float) $part->total,
                'warehouse_id' => $part->warehouse_id,
            ];
        })->values();

        $originalTotal = (float) $originalInvoice->total_incl_tax;
        $originalPaid = (float) $originalInvoice->total_paid;

        // Calculate remaining debt considering all previous valid credit notes
        $totalPreviousCreditNotes = (float) Invoice::withoutGlobalScopes()
            ->where('original_invoice_id', $originalInvoice->id)
            ->where('type', InvoiceType::CREDIT_NOTE)
            ->where('status', '!=', 'cancelled')
            ->sum('total_incl_tax');

        $netInvoiceAmount = max(0.0, round($originalTotal - $totalPreviousCreditNotes, 2));
        $originalRemaining = max(0.0, round($netInvoiceAmount - $originalPaid, 2));

        $hasReturnableItems = $returnableServices->sum('refundable_amount') > 0
            || $returnableParts->sum('refundable_qty') > 0;

        $invoiceData = [
            'id' => $originalInvoice->id,
            'invoice_number' => $originalInvoice->invoice_number,
            'issue_date' => $originalInvoice->issue_date?->format('Y-m-d'),
            'total_incl_tax' => $originalTotal,
            'paid_amount' => $originalPaid,
            'total_paid' => $originalPaid,
            'remaining_amount' => $originalRemaining,
            'remaining' => $originalRemaining,
            'tax_enabled_snapshot' => $taxEnabled,
            'tax_rate' => $taxRate,
            'subtype' => $originalInvoice->subtype ?? 'simplified',
        ];

        return [
            'is_possible' => $hasReturnableItems,
            'original_invoice' => $invoiceData,
            'invoice' => $invoiceData,
            'tax_settings' => [
                'tax_enabled' => $taxEnabled,
                'pricing_mode' => $pricingMode,
                'tax_rate' => $taxRate,
            ],
            'services' => $returnableServices,
            'parts' => $returnableParts,
        ];
    }

    /**
     * Create Credit Note (Return Invoice) from Work Order.
     */
    public function createCreditNote(WorkOrder $workOrder, array $data, User $user): Invoice
    {
        $originalInvoice = $workOrder->invoice;

        if (! $originalInvoice) {
            throw new Exception(__('invoices.original_invoice_not_found'));
        }

        $summary = $this->getReturnableSummary($workOrder);

        $servicesInput = $data['services'] ?? [];
        $partsInput = $data['parts'] ?? [];

        $taxEnabled = (bool) ($originalInvoice->tax_enabled_snapshot ?? false);

        if (! $taxEnabled && ((float) $originalInvoice->total_tax > 0.001 || (bool) ($workOrder->tax_enabled_snapshot ?? false))) {
            $taxEnabled = true;
        }
        $pricingMode = $originalInvoice->pricing_mode_snapshot ?? $workOrder->pricing_mode_snapshot ?? 'exclusive';
        $taxRate = $taxEnabled ? (float) ($originalInvoice->tax_rate_snapshot ?: $workOrder->tax_rate_snapshot ?: 15.00) : 0.0;

        $linesToCreate = [];
        $totalReturnIncl = 0.0;
        $totalReturnExcl = 0.0;
        $totalReturnTax = 0.0;

        // 1. Process returned services
        foreach ($servicesInput as $srvInput) {
            $itemId = (int) ($srvInput['work_order_item_id'] ?? $srvInput['id'] ?? 0);
            $amount = (float) ($srvInput['return_amount'] ?? 0);

            if ($amount <= 0.001) {
                continue;
            }

            $matchedSummary = collect($summary['services'])->firstWhere('work_order_item_id', $itemId);

            if (! $matchedSummary) {
                throw new InvalidArgumentException("Service item #{$itemId} not found.");
            }

            if ($amount > ($matchedSummary['refundable_amount'] + 0.01)) {
                throw new InvalidArgumentException("Return amount for service '{$matchedSummary['title']}' exceeds allowable refundable amount.");
            }

            $item = $workOrder->items()->findOrFail($itemId);

            if ($pricingMode === 'inclusive' && $taxEnabled) {
                $lineIncl = round($amount, 2);
                $lineExcl = round($lineIncl / (1 + ($taxRate / 100)), 2);
                $lineTax = round($lineIncl - $lineExcl, 2);
            } else {
                $lineExcl = round($amount, 2);
                $lineTax = $taxEnabled ? round($lineExcl * ($taxRate / 100), 2) : 0.0;
                $lineIncl = round($lineExcl + $lineTax, 2);
            }

            $linesToCreate[] = [
                'is_part' => false,
                'part_id' => null,
                'description' => 'مرتجع خدمة: '.$item->title,
                'qty' => 1,
                'unit_price' => $lineExcl,
                'discount_amount' => 0,
                'is_taxable' => $taxEnabled,
                'tax_category_code' => 'S',
                'tax_rate_snapshot' => $taxRate,
                'tax_amount' => $lineTax,
                'line_total_excl_tax' => $lineExcl,
                'line_total_incl_tax' => $lineIncl,
                'work_order_item_id' => $itemId,
            ];

            $totalReturnIncl += $lineIncl;
            $totalReturnExcl += $lineExcl;
            $totalReturnTax += $lineTax;
        }

        // 2. Process returned parts
        $partsToRestock = [];

        foreach ($partsInput as $prtInput) {
            $partLineId = (int) ($prtInput['work_order_item_part_id'] ?? $prtInput['id'] ?? 0);
            $returnQty = (float) ($prtInput['return_qty'] ?? 0);
            $returnToStock = (bool) ($prtInput['return_to_stock'] ?? true);

            if ($returnQty <= 0.001) {
                continue;
            }

            $matchedSummary = collect($summary['parts'])->firstWhere('work_order_item_part_id', $partLineId);

            if (! $matchedSummary) {
                throw new InvalidArgumentException("Part item #{$partLineId} not found.");
            }

            if ($returnQty > ($matchedSummary['refundable_qty'] + 0.001)) {
                throw new InvalidArgumentException("Return quantity for part '{$matchedSummary['name']}' exceeds allowable refundable quantity.");
            }

            $part = $workOrder->parts()->findOrFail($partLineId);

            $proportion = $part->qty > 0 ? ($returnQty / (float) $part->qty) : 1;
            $partTotal = (float) $part->total * $proportion;

            if ($pricingMode === 'inclusive' && $taxEnabled) {
                $lineIncl = round($partTotal, 2);
                $lineExcl = round($lineIncl / (1 + ($taxRate / 100)), 2);
                $lineTax = round($lineIncl - $lineExcl, 2);
            } else {
                $lineExcl = round($partTotal, 2);
                $lineTax = $taxEnabled ? round($lineExcl * ($taxRate / 100), 2) : 0.0;
                $lineIncl = round($lineExcl + $lineTax, 2);
            }

            $linesToCreate[] = [
                'is_part' => true,
                'part_id' => $part->part_id,
                'description' => 'مرتجع قطعة: '.$part->name,
                'qty' => $returnQty,
                'unit_price' => round($lineExcl / $returnQty, 2),
                'discount_amount' => 0,
                'is_taxable' => $taxEnabled,
                'tax_category_code' => 'S',
                'tax_rate_snapshot' => $taxRate,
                'tax_amount' => $lineTax,
                'line_total_excl_tax' => $lineExcl,
                'line_total_incl_tax' => $lineIncl,
                'work_order_item_part_id' => $partLineId,
            ];

            if ($returnToStock && $part->part_id && $part->warehouse_id) {
                $partsToRestock[] = [
                    'warehouse_id' => $part->warehouse_id,
                    'part_id' => $part->part_id,
                    'qty' => $returnQty,
                    'unit_cost' => (float) ($part->cost_snapshot ?? 0),
                    'part_name' => $part->name,
                ];
            }

            $totalReturnIncl += $lineIncl;
            $totalReturnExcl += $lineExcl;
            $totalReturnTax += $lineTax;
        }

        if (empty($linesToCreate)) {
            throw new InvalidArgumentException('يجب تحديد خدمة واحدة على الأقل أو قطعة غيار واحدة للإرجاع.');
        }

        return DB::transaction(function () use (
            $workOrder,
            $originalInvoice,
            $summary,
            $linesToCreate,
            $partsToRestock,
            $totalReturnIncl,
            $totalReturnExcl,
            $totalReturnTax,
            $data,
            $user,
            $taxRate
        ) {
            $tenantId = $workOrder->tenant_id;
            $centerId = $workOrder->center_id;
            $year = Carbon::now()->year;

            // Generate Credit Note sequential number
            $seq = CenterSequence::getNextValue($tenantId, $centerId, 'credit_note', $year);
            $tenant = Tenant::find($tenantId);
            $creditNoteNumber = $tenant
                ? $tenant->formatDocumentCode('credit_note', $centerId, $seq, $year)
                : sprintf('CN-%s-%s-%06d', $centerId, $year, $seq);

            // Reconcile and calculate auto-offset
            $originalRemaining = $summary['original_invoice']['remaining'];
            $amountAdjusted = min($originalRemaining, $totalReturnIncl);
            $netRefundDue = max(0.0, round($totalReturnIncl - $amountAdjusted, 2));

            // Create Credit Note
            $creditNote = Invoice::create([
                'tenant_id' => $tenantId,
                'center_id' => $centerId,
                'customer_id' => $workOrder->customer_id,
                'work_order_id' => $workOrder->id,
                'original_invoice_id' => $originalInvoice->id,
                'invoice_number' => $creditNoteNumber,
                'issue_date' => Carbon::now(),
                'supply_date' => Carbon::now()->toDateString(),
                'type' => InvoiceType::CREDIT_NOTE,
                'subtype' => $originalInvoice->subtype ?? 'simplified',
                'status' => 'valid',
                'payment_status' => 'paid',
                'notes' => $data['notes'] ?? null,
                'return_reason' => $data['return_reason'] ?? 'طلب العميل',
                'customer_name_snapshot' => $originalInvoice->customer_name_snapshot,
                'customer_vat_snapshot' => $originalInvoice->customer_vat_snapshot,
                'customer_address_snapshot' => $originalInvoice->customer_address_snapshot,
                'tax_enabled_snapshot' => $originalInvoice->tax_enabled_snapshot,
                'pricing_mode_snapshot' => $originalInvoice->pricing_mode_snapshot,
                'tax_rate_snapshot' => $taxRate,
                'currency_code' => $originalInvoice->currency_code ?? 'SAR',
                'total_excl_tax' => round($totalReturnExcl, 2),
                'total_tax' => round($totalReturnTax, 2),
                'total_incl_tax' => round($totalReturnIncl, 2),
                'total_taxable_amount' => round($totalReturnExcl, 2),
                'total_paid' => round($totalReturnIncl, 2),
            ]);

            // Generate ZATCA QR Code
            $creditNote->zatca_qr_tlv = $this->invoiceService->generateZatcaQr($creditNote);
            $creditNote->saveQuietly();

            // Insert invoice lines
            foreach ($linesToCreate as $line) {
                $creditNote->lines()->create([
                    'tenant_id' => $tenantId,
                    'center_id' => $centerId,
                    'is_part' => $line['is_part'],
                    'part_id' => $line['part_id'],
                    'work_order_item_id' => $line['work_order_item_id'] ?? null,
                    'work_order_item_part_id' => $line['work_order_item_part_id'] ?? null,
                    'description' => $line['description'],
                    'qty' => $line['qty'],
                    'unit_price' => $line['unit_price'],
                    'discount_amount' => $line['discount_amount'],
                    'is_taxable' => $line['is_taxable'],
                    'tax_category_code' => $line['tax_category_code'],
                    'tax_rate_snapshot' => $line['tax_rate_snapshot'],
                    'tax_amount' => $line['tax_amount'],
                    'line_total_excl_tax' => $line['line_total_excl_tax'],
                    'line_total_incl_tax' => $line['line_total_incl_tax'],
                ]);
            }

            // Restock returned parts into inventory
            foreach ($partsToRestock as $partStock) {
                try {
                    $this->inventoryService->receipt(
                        warehouseId: $partStock['warehouse_id'],
                        partId: $partStock['part_id'],
                        qty: (float) $partStock['qty'],
                        unitCost: (float) $partStock['unit_cost'],
                        userId: $user->id,
                        referenceType: 'credit_note',
                        referenceId: $creditNote->id,
                        notes: 'إرجاع مستودعي عبر إشعار دائن رقم #'.$creditNote->invoice_number.' لأمر العمل #'.$workOrder->code
                    );
                } catch (Exception $e) {
                    // Log or handle non-fatal inventory movement warning
                }
            }

            // Record Refund Payment if specified
            $refundPayment = $data['refund_payment'] ?? null;
            $hasRefundPayment = $refundPayment && ! empty($refundPayment['amount']) && (float) $refundPayment['amount'] > 0;
            $hasDirectRefund = ! empty($data['record_refund']) && $netRefundDue > 0;

            if ($hasRefundPayment && (float) $refundPayment['amount'] > ($netRefundDue + 0.001)) {
                throw new InvalidArgumentException(__('invoices.refund_exceeds_net_due'));
            }

            if ($hasRefundPayment || $hasDirectRefund) {
                $paymentAmount = $hasRefundPayment ? (float) $refundPayment['amount'] : $netRefundDue;
                $paymentMethod = $hasRefundPayment ? ($refundPayment['payment_method'] ?? 'cash') : ($data['refund_payment_method'] ?? 'cash');
                $paymentDate = $hasRefundPayment ? ($refundPayment['payment_date'] ?? Carbon::now()) : Carbon::now();
                $paymentRef = $hasRefundPayment ? ($refundPayment['reference'] ?? null) : ($data['refund_reference'] ?? null);

                Payment::create([
                    'tenant_id' => $tenantId,
                    'center_id' => $centerId,
                    'work_order_id' => $workOrder->id,
                    'invoice_id' => $creditNote->id,
                    'amount' => $paymentAmount,
                    'payment_date' => $paymentDate,
                    'payment_method' => $paymentMethod,
                    'reference' => $paymentRef,
                    'notes' => 'سند رد مبلغ - إشعار دائن رقم #'.$creditNote->invoice_number,
                    'type' => PaymentType::REFUND,
                    'received_by' => $user->id,
                ]);

                $creditNote->payment_status = 'paid';
                $creditNote->total_paid = $creditNote->total_incl_tax;
            } else {
                // If money was NOT refunded immediately: keep as customer credit (unpaid due credit note)
                if ($netRefundDue > 0) {
                    $creditNote->payment_status = 'unpaid';
                    $creditNote->total_paid = $amountAdjusted;
                    $creditNote->due_date = $data['due_date'] ?? $data['payment_due_date'] ?? Carbon::now()->addDays(30);
                } else {
                    $creditNote->payment_status = 'paid';
                    $creditNote->total_paid = $creditNote->total_incl_tax;
                }
            }
            $creditNote->saveQuietly();

            // If auto-offset debt adjustment occurred, adjust original invoice payment status
            if ($amountAdjusted > 0) {
                $originalInvoice->updatePaymentStatus();
            }

            $creditNote->debt_offset = $amountAdjusted;
            $creditNote->net_refund_due = $netRefundDue;

            return $creditNote;
        });
    }

    /**
     * Get returnable items summary directly for an invoice (handles both Work Order invoices and Direct Sale invoices).
     */
    public function getReturnableSummaryForInvoice(Invoice $invoice): array
    {
        if ($invoice->work_order_id && $invoice->workOrder) {
            return $this->getReturnableSummary($invoice->workOrder);
        }

        if ($invoice->type === InvoiceType::CREDIT_NOTE || $invoice->type?->value === 'credit_note') {
            throw new InvalidArgumentException('لا يمكن إنشاء إشعار دائن لإشعار دائن آخر.');
        }

        if ($invoice->status === 'cancelled') {
            throw new InvalidArgumentException('لا يمكن إنشاء إشعار دائن لفاتورة ملغاة.');
        }

        $taxEnabled = (bool) ($invoice->tax_enabled_snapshot ?? false);

        if (! $taxEnabled && (float) $invoice->total_tax > 0.001) {
            $taxEnabled = true;
        }
        $taxRate = $taxEnabled ? (float) ($invoice->tax_rate_snapshot ?: 15.00) : 0.0;
        $pricingMode = $invoice->pricing_mode_snapshot ?? 'exclusive';

        // Existing credit notes for this invoice
        $creditNoteIds = Invoice::withoutGlobalScopes()
            ->where('original_invoice_id', $invoice->id)
            ->where('type', InvoiceType::CREDIT_NOTE)
            ->where('status', '!=', 'cancelled')
            ->pluck('id');

        $creditLines = DB::table('invoice_lines')
            ->whereIn('invoice_id', $creditNoteIds)
            ->get();

        $invoiceLines = $invoice->lines()->get();

        // Find warehouses used by sale moves for this invoice
        $saleMoves = DB::table('inventory_moves')
            ->where(function ($q) {
                $q->where('reference_type', Invoice::class)
                    ->orWhere('reference_type', 'invoice');
            })
            ->where('reference_id', $invoice->id)
            ->where('move_type', 'sale')
            ->get()
            ->keyBy('part_id');

        $defaultWarehouseId = DB::table('warehouses')
            ->where('center_id', $invoice->center_id)
            ->where('is_active', true)
            ->where('is_default', true)
            ->value('id') ?? DB::table('warehouses')
            ->where('center_id', $invoice->center_id)
            ->where('is_active', true)
            ->value('id');

        // 1. Returnable services (if direct sale has any service line)
        $serviceLines = $invoiceLines->where('is_part', false);
        $returnableServices = $serviceLines->map(function ($line) use ($creditLines, $taxRate) {
            $previouslyReturnedExcl = (float) $creditLines
                ->filter(fn ($l) => (int) ($l->original_invoice_line_id ?? 0) === (int) $line->id)
                ->sum('line_total_excl_tax');

            $originalExcl = (float) $line->line_total_excl_tax;
            $originalTotal = (float) $line->line_total_incl_tax;
            $refundableAmount = max(0.0, round($originalExcl - $previouslyReturnedExcl, 2));

            return [
                'id' => $line->id,
                'work_order_item_id' => $line->id,
                'name' => $line->description,
                'title' => $line->description,
                'department_name' => null,
                'original_cost' => $originalExcl,
                'original_subtotal' => $originalExcl,
                'original_total' => $originalTotal,
                'returned_amount_previous' => $previouslyReturnedExcl,
                'returned_subtotal' => $previouslyReturnedExcl,
                'refundable_amount' => $refundableAmount,
                'remaining_refundable_subtotal' => $refundableAmount,
                'tax_rate' => $taxRate,
            ];
        })->values();

        // 2. Returnable parts
        $partLines = $invoiceLines->where('is_part', true);
        $returnableParts = $partLines->map(function ($line) use ($creditLines, $taxRate, $saleMoves, $defaultWarehouseId) {
            $previouslyReturnedQty = (float) $creditLines
                ->filter(function ($l) use ($line) {
                    if (isset($l->original_invoice_line_id) && $l->original_invoice_line_id !== null) {
                        return (int) $l->original_invoice_line_id === (int) $line->id;
                    }

                    return ! empty($l->is_part) && (int) ($l->part_id ?? 0) === (int) ($line->part_id ?? 0);
                })
                ->sum('qty');

            $originalQty = (float) $line->qty;
            $refundableQty = max(0.0, round($originalQty - $previouslyReturnedQty, 2));

            $warehouseId = $saleMoves->get($line->part_id)?->warehouse_id ?? $defaultWarehouseId;

            return [
                'id' => $line->id,
                'work_order_item_part_id' => $line->id,
                'part_id' => $line->part_id,
                'name' => $line->description,
                'part_number' => $line->part?->sku ?? $line->part?->part_number ?? null,
                'unit_price' => (float) $line->unit_price,
                'discount' => (float) ($line->discount_amount ?? $line->discount_value ?? 0),
                'original_qty' => $originalQty,
                'returned_qty_previous' => $previouslyReturnedQty,
                'returned_qty' => $previouslyReturnedQty,
                'refundable_qty' => $refundableQty,
                'remaining_refundable_qty' => $refundableQty,
                'tax_rate' => $taxRate,
                'total' => (float) $line->line_total_excl_tax,
                'warehouse_id' => $warehouseId,
            ];
        })->values();

        $originalTotal = (float) $invoice->total_incl_tax;
        $originalPaid = (float) $invoice->total_paid;

        $totalPreviousCreditNotes = (float) Invoice::withoutGlobalScopes()
            ->where('original_invoice_id', $invoice->id)
            ->where('type', InvoiceType::CREDIT_NOTE)
            ->where('status', '!=', 'cancelled')
            ->sum('total_incl_tax');

        $netInvoiceAmount = max(0.0, round($originalTotal - $totalPreviousCreditNotes, 2));
        $originalRemaining = max(0.0, round($netInvoiceAmount - $originalPaid, 2));

        $hasReturnableItems = $returnableServices->sum('refundable_amount') > 0
            || $returnableParts->sum('refundable_qty') > 0;

        $invoiceData = [
            'id' => $invoice->id,
            'invoice_number' => $invoice->invoice_number,
            'issue_date' => $invoice->issue_date?->format('Y-m-d'),
            'total_incl_tax' => $originalTotal,
            'paid_amount' => $originalPaid,
            'total_paid' => $originalPaid,
            'remaining_amount' => $originalRemaining,
            'remaining' => $originalRemaining,
            'tax_enabled_snapshot' => $taxEnabled,
            'tax_rate' => $taxRate,
            'subtype' => $invoice->subtype ?? 'simplified',
        ];

        return [
            'is_possible' => $hasReturnableItems,
            'original_invoice' => $invoiceData,
            'invoice' => $invoiceData,
            'tax_settings' => [
                'tax_enabled' => $taxEnabled,
                'pricing_mode' => $pricingMode,
                'tax_rate' => $taxRate,
            ],
            'services' => $returnableServices,
            'parts' => $returnableParts,
        ];
    }

    /**
     * Create Credit Note for an invoice (handles both Work Order and Direct Sale invoices).
     */
    public function createCreditNoteForInvoice(Invoice $invoice, array $data, User $user): Invoice
    {
        if ($invoice->work_order_id && $invoice->workOrder) {
            return $this->createCreditNote($invoice->workOrder, $data, $user);
        }

        $summary = $this->getReturnableSummaryForInvoice($invoice);

        $servicesInput = $data['services'] ?? [];
        $partsInput = $data['parts'] ?? [];

        $taxEnabled = (bool) ($invoice->tax_enabled_snapshot ?? false);

        if (! $taxEnabled && (float) $invoice->total_tax > 0.001) {
            $taxEnabled = true;
        }
        $pricingMode = $invoice->pricing_mode_snapshot ?? 'exclusive';
        $taxRate = $taxEnabled ? (float) ($invoice->tax_rate_snapshot ?: 15.00) : 0.0;

        $linesToCreate = [];
        $totalReturnIncl = 0.0;
        $totalReturnExcl = 0.0;
        $totalReturnTax = 0.0;

        // 1. Services
        foreach ($servicesInput as $srvInput) {
            $lineId = (int) ($srvInput['work_order_item_id'] ?? $srvInput['id'] ?? 0);
            $amount = (float) ($srvInput['return_amount'] ?? 0);

            if ($amount <= 0.001) {
                continue;
            }

            $matchedSummary = collect($summary['services'])->firstWhere('id', $lineId);

            if (! $matchedSummary) {
                throw new InvalidArgumentException("Service line #{$lineId} not found.");
            }

            if ($amount > ($matchedSummary['refundable_amount'] + 0.01)) {
                throw new InvalidArgumentException('Return amount for service exceeds allowable refundable amount.');
            }

            $line = $invoice->lines()->findOrFail($lineId);

            $lineExcl = round($amount, 2);
            $lineTax = $taxEnabled ? round($lineExcl * ($taxRate / 100), 2) : 0.0;
            $lineIncl = round($lineExcl + $lineTax, 2);

            $linesToCreate[] = [
                'is_part' => false,
                'part_id' => null,
                'original_invoice_line_id' => $line->id,
                'description' => 'مرتجع: '.$line->description,
                'qty' => 1,
                'unit_price' => $lineExcl,
                'discount_amount' => 0,
                'is_taxable' => $taxEnabled,
                'tax_category_code' => 'S',
                'tax_rate_snapshot' => $taxRate,
                'tax_amount' => $lineTax,
                'line_total_excl_tax' => $lineExcl,
                'line_total_incl_tax' => $lineIncl,
            ];

            $totalReturnIncl += $lineIncl;
            $totalReturnExcl += $lineExcl;
            $totalReturnTax += $lineTax;
        }

        // 2. Parts
        $partsToRestock = [];

        foreach ($partsInput as $prtInput) {
            $lineId = (int) ($prtInput['work_order_item_part_id'] ?? $prtInput['id'] ?? 0);
            $returnQty = (float) ($prtInput['return_qty'] ?? 0);
            $returnToStock = (bool) ($prtInput['return_to_stock'] ?? true);

            if ($returnQty <= 0.001) {
                continue;
            }

            $matchedSummary = collect($summary['parts'])->firstWhere('id', $lineId);

            if (! $matchedSummary) {
                throw new InvalidArgumentException("Part line #{$lineId} not found.");
            }

            if ($returnQty > ($matchedSummary['refundable_qty'] + 0.001)) {
                throw new InvalidArgumentException("Return quantity for part '{$matchedSummary['name']}' exceeds allowable refundable quantity.");
            }

            $line = $invoice->lines()->findOrFail($lineId);

            $proportion = $line->qty > 0 ? ($returnQty / (float) $line->qty) : 1;
            $lineExcl = round((float) $line->line_total_excl_tax * $proportion, 2);
            $lineTax = round((float) $line->tax_amount * $proportion, 2);
            $lineIncl = round((float) $line->line_total_incl_tax * $proportion, 2);
            $unitPrice = (float) $line->unit_price;

            $linesToCreate[] = [
                'is_part' => true,
                'part_id' => $line->part_id,
                'original_invoice_line_id' => $line->id,
                'description' => 'مرتجع: '.$line->description,
                'qty' => $returnQty,
                'unit_price' => $unitPrice,
                'discount_amount' => round((float) ($line->discount_amount ?? 0) * $proportion, 2),
                'is_taxable' => $taxEnabled,
                'tax_category_code' => 'S',
                'tax_rate_snapshot' => $taxRate,
                'tax_amount' => $lineTax,
                'line_total_excl_tax' => $lineExcl,
                'line_total_incl_tax' => $lineIncl,
            ];

            if ($returnToStock && $line->part_id && ! empty($matchedSummary['warehouse_id'])) {
                $partsToRestock[] = [
                    'warehouse_id' => $matchedSummary['warehouse_id'],
                    'part_id' => $line->part_id,
                    'qty' => $returnQty,
                    'unit_cost' => (float) ($line->part?->cost_price ?? $line->unit_price),
                    'part_name' => $line->description,
                ];
            }

            $totalReturnIncl += $lineIncl;
            $totalReturnExcl += $lineExcl;
            $totalReturnTax += $lineTax;
        }

        if (empty($linesToCreate)) {
            throw new InvalidArgumentException('يجب تحديد خدمة واحدة على الأقل أو قطعة غيار واحدة للإرجاع.');
        }

        return DB::transaction(function () use (
            $invoice,
            $summary,
            $linesToCreate,
            $partsToRestock,
            $totalReturnIncl,
            $totalReturnExcl,
            $totalReturnTax,
            $data,
            $user,
            $taxRate
        ) {
            $tenantId = $invoice->tenant_id;
            $centerId = $invoice->center_id;
            $year = Carbon::now()->year;

            // Generate Credit Note sequential number
            $seq = CenterSequence::getNextValue($tenantId, $centerId, 'credit_note', $year);
            $tenant = Tenant::find($tenantId);
            $creditNoteNumber = $tenant
                ? $tenant->formatDocumentCode('credit_note', $centerId, $seq, $year)
                : sprintf('CN-%s-%s-%06d', $centerId, $year, $seq);

            // Reconcile and calculate auto-offset
            $originalRemaining = $summary['original_invoice']['remaining'];
            $amountAdjusted = min($originalRemaining, $totalReturnIncl);
            $netRefundDue = max(0.0, round($totalReturnIncl - $amountAdjusted, 2));

            // Create Credit Note
            $creditNote = Invoice::create([
                'tenant_id' => $tenantId,
                'center_id' => $centerId,
                'customer_id' => $invoice->customer_id,
                'work_order_id' => null,
                'original_invoice_id' => $invoice->id,
                'invoice_number' => $creditNoteNumber,
                'issue_date' => Carbon::now(),
                'supply_date' => Carbon::now()->toDateString(),
                'type' => InvoiceType::CREDIT_NOTE,
                'subtype' => $invoice->subtype ?? 'simplified',
                'status' => 'valid',
                'payment_status' => 'paid',
                'notes' => $data['notes'] ?? null,
                'return_reason' => $data['return_reason'] ?? 'طلب العميل',
                'customer_name_snapshot' => $invoice->customer_name_snapshot,
                'customer_vat_snapshot' => $invoice->customer_vat_snapshot,
                'customer_address_snapshot' => $invoice->customer_address_snapshot,
                'tax_enabled_snapshot' => $invoice->tax_enabled_snapshot,
                'pricing_mode_snapshot' => $invoice->pricing_mode_snapshot,
                'tax_rate_snapshot' => $taxRate,
                'currency_code' => $invoice->currency_code ?? 'SAR',
                'total_excl_tax' => round($totalReturnExcl, 2),
                'total_tax' => round($totalReturnTax, 2),
                'total_incl_tax' => round($totalReturnIncl, 2),
                'total_taxable_amount' => round($totalReturnExcl, 2),
                'total_paid' => round($totalReturnIncl, 2),
            ]);

            // Generate ZATCA QR Code
            $creditNote->zatca_qr_tlv = $this->invoiceService->generateZatcaQr($creditNote);
            $creditNote->saveQuietly();

            // Insert invoice lines
            foreach ($linesToCreate as $line) {
                $creditNote->lines()->create([
                    'tenant_id' => $tenantId,
                    'center_id' => $centerId,
                    'is_part' => $line['is_part'],
                    'part_id' => $line['part_id'],
                    'original_invoice_line_id' => $line['original_invoice_line_id'] ?? null,
                    'description' => $line['description'],
                    'qty' => $line['qty'],
                    'unit_price' => $line['unit_price'],
                    'discount_amount' => $line['discount_amount'],
                    'is_taxable' => $line['is_taxable'],
                    'tax_category_code' => $line['tax_category_code'],
                    'tax_rate_snapshot' => $line['tax_rate_snapshot'],
                    'tax_amount' => $line['tax_amount'],
                    'line_total_excl_tax' => $line['line_total_excl_tax'],
                    'line_total_incl_tax' => $line['line_total_incl_tax'],
                ]);
            }

            // Restock returned parts into inventory
            foreach ($partsToRestock as $partStock) {
                try {
                    $this->inventoryService->receipt(
                        warehouseId: $partStock['warehouse_id'],
                        partId: $partStock['part_id'],
                        qty: (float) $partStock['qty'],
                        unitCost: (float) $partStock['unit_cost'],
                        userId: $user->id,
                        referenceType: 'credit_note',
                        referenceId: $creditNote->id,
                        notes: 'إرجاع مبيعات مباشرة عبر إشعار دائن رقم #'.$creditNote->invoice_number
                    );
                } catch (Exception $e) {
                    // Non-fatal inventory movement warning
                }
            }

            // Record Refund Payment if specified
            $refundPayment = $data['refund_payment'] ?? null;
            $hasRefundPayment = $refundPayment && ! empty($refundPayment['amount']) && (float) $refundPayment['amount'] > 0;
            $hasDirectRefund = ! empty($data['record_refund']) && $netRefundDue > 0;

            if ($hasRefundPayment && (float) $refundPayment['amount'] > ($netRefundDue + 0.001)) {
                throw new InvalidArgumentException(__('invoices.refund_exceeds_net_due'));
            }

            if ($hasRefundPayment || $hasDirectRefund) {
                $paymentAmount = $hasRefundPayment ? (float) $refundPayment['amount'] : $netRefundDue;
                $paymentMethod = $hasRefundPayment ? ($refundPayment['payment_method'] ?? 'cash') : ($data['refund_payment_method'] ?? 'cash');
                $paymentDate = $hasRefundPayment ? ($refundPayment['payment_date'] ?? Carbon::now()) : Carbon::now();
                $paymentRef = $hasRefundPayment ? ($refundPayment['reference'] ?? null) : ($data['refund_reference'] ?? null);

                Payment::create([
                    'tenant_id' => $tenantId,
                    'center_id' => $centerId,
                    'work_order_id' => null,
                    'invoice_id' => $creditNote->id,
                    'amount' => $paymentAmount,
                    'payment_date' => $paymentDate,
                    'payment_method' => $paymentMethod,
                    'reference' => $paymentRef,
                    'notes' => 'سند رد مبلغ - إشعار دائن رقم #'.$creditNote->invoice_number,
                    'type' => PaymentType::REFUND,
                    'received_by' => $user->id,
                ]);

                $creditNote->payment_status = 'paid';
                $creditNote->total_paid = $creditNote->total_incl_tax;
            } else {
                if ($netRefundDue > 0) {
                    $creditNote->payment_status = 'unpaid';
                    $creditNote->total_paid = $amountAdjusted;
                    $creditNote->due_date = $data['due_date'] ?? $data['payment_due_date'] ?? Carbon::now()->addDays(30);
                } else {
                    $creditNote->payment_status = 'paid';
                    $creditNote->total_paid = $creditNote->total_incl_tax;
                }
            }
            $creditNote->saveQuietly();

            if ($amountAdjusted > 0) {
                $invoice->updatePaymentStatus();
            }

            $creditNote->debt_offset = $amountAdjusted;
            $creditNote->net_refund_due = $netRefundDue;

            return $creditNote;
        });
    }
}
