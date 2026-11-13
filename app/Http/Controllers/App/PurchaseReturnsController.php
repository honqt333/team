<?php

declare(strict_types=1);

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\InventoryMove;
use App\Models\Payment;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseInvoiceLine;
use App\Models\PurchaseReturnInvoice;
use App\Models\PurchaseReturnInvoiceLine;
use App\Models\Warehouse;
use App\Services\Inventory\InventoryService;
use App\Services\PurchaseAttachmentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class PurchaseReturnsController extends Controller
{
    /**
     * Record a return for a purchase invoice.
     */
    public function recordReturn(Request $request, PurchaseInvoice $purchaseInvoice)
    {
        $this->authorize('createReturn', $purchaseInvoice);

        $user = auth()->user();

        $validated = $request->validate([
            'return_date' => 'required|date',
            'notes' => 'nullable|string|max:1000',
            'items' => 'required|array|min:1',
            'items.*.purchase_invoice_line_id' => 'required|exists:purchase_invoice_lines,id',
            'items.*.qty' => 'required|numeric|min:0.01',
            'items.*.unit_cost' => 'required|numeric|min:0',
            'refund_payments' => 'nullable|array',
            'refund_payments.*.payment_method' => 'required_with:refund_payments|string',
            'refund_payments.*.amount' => 'required_with:refund_payments|numeric|min:0.01',
            'refund_payments.*.payment_date' => 'nullable|date',
            'refund_payments.*.reference' => 'nullable|string|max:100',
            'refund_payments.*.notes' => 'nullable|string|max:500',
            'create_debit_note' => 'nullable|boolean',
            'debit_note_date' => 'nullable|required_if:create_debit_note,true|date',
        ]);

        $returnInvoice = DB::transaction(function () use ($validated, $purchaseInvoice, $user) {
            $code = PurchaseReturnInvoice::generateCode($user->tenant_id);

            $subtotal = 0;
            $taxAmount = 0;
            $itemsToCreate = [];

            foreach ($validated['items'] as $item) {
                $line = PurchaseInvoiceLine::findOrFail($item['purchase_invoice_line_id']);

                $qty = (float) $item['qty'];
                $unitCost = (float) $item['unit_cost'];
                $taxRate = (float) ($line->tax_rate ?? 0);

                $originalTotal = (float) $line->total;
                $originalGross = (float) $line->qty * (float) $line->unit_cost;
                $isInclusive = abs($originalTotal - $originalGross) < 0.05;

                if ($isInclusive) {
                    $lineTotal = $qty * $unitCost;
                    $lineSubtotal = $taxRate > 0 ? $lineTotal / (1 + $taxRate / 100) : $lineTotal;
                    $lineTax = $lineTotal - $lineSubtotal;
                } else {
                    $lineSubtotal = $qty * $unitCost;
                    $lineTax = $lineSubtotal * ($taxRate / 100);
                    $lineTotal = $lineSubtotal + $lineTax;
                }

                $subtotal += $lineSubtotal;
                $taxAmount += $lineTax;

                $itemsToCreate[] = [
                    'purchase_invoice_line_id' => $line->id,
                    'part_id' => $line->part_id,
                    'qty' => $qty,
                    'unit_cost' => $unitCost,
                    'tax_rate' => $taxRate,
                    'tax_amount' => $lineTax,
                    'total' => $lineTotal,
                ];
            }

            $total = $subtotal + $taxAmount;

            $returnInvoice = PurchaseReturnInvoice::create([
                'tenant_id' => $user->tenant_id,
                'center_id' => $user->current_center_id,
                'purchase_invoice_id' => $purchaseInvoice->id,
                'code' => $code,
                'return_date' => $validated['return_date'],
                'subtotal' => $subtotal,
                'tax_amount' => $taxAmount,
                'total' => $total,
                'create_debit_note' => $validated['create_debit_note'] ?? false,
                'debit_note_date' => $validated['debit_note_date'] ?? null,
                'notes' => $validated['notes'] ?? null,
            ]);

            $inventoryService = app(InventoryService::class);

            $warehouseId = $purchaseInvoice->purchaseOrder?->warehouse_id
                ?? Warehouse::forCenter($user->current_center_id)->default()->first()?->id
                ?? Warehouse::forCenter($user->current_center_id)->first()?->id;

            if (! $warehouseId) {
                throw ValidationException::withMessages([
                    'warehouse' => ['No active warehouse found for center.'],
                ]);
            }

            foreach ($itemsToCreate as $itemData) {
                $itemData['purchase_return_invoice_id'] = $returnInvoice->id;
                PurchaseReturnInvoiceLine::create($itemData);

                $inventoryService->issue(
                    warehouseId: $warehouseId,
                    partId: (int) $itemData['part_id'],
                    qty: (float) $itemData['qty'],
                    userId: $user->id,
                    referenceType: PurchaseReturnInvoiceLine::class,
                    referenceId: $returnInvoice->id,
                    notes: "Returned to supplier via return invoice: {$returnInvoice->code}",
                    moveType: InventoryMove::TYPE_PURCHASE_RETURN,
                    unitCost: (float) $itemData['unit_cost']
                );
            }

            if (! empty($validated['refund_payments'])) {
                $hasPayments = $purchaseInvoice->payments()->where('type', Payment::TYPE_PAYMENT)->exists();

                if (! $hasPayments) {
                    throw ValidationException::withMessages([
                        'refund_payments' => [__('payments.errors.cannot_refund_unpaid_invoice') ?? 'لا يمكن تسجيل دفعة مستردة لفاتورة شراء لم تدفع بعد'],
                    ]);
                }
            }

            foreach ($validated['refund_payments'] ?? [] as $refundEntry) {
                $refundAmt = (float) $refundEntry['amount'];

                if ($refundAmt <= 0) {
                    continue;
                }

                Payment::create([
                    'tenant_id' => $user->tenant_id,
                    'center_id' => $user->current_center_id,
                    'purchase_invoice_id' => $purchaseInvoice->id,
                    'amount' => $refundAmt,
                    'payment_date' => $refundEntry['payment_date'] ?? $validated['return_date'] ?? now(),
                    'payment_method' => $refundEntry['payment_method'] ?? 'cash',
                    'reference' => $refundEntry['reference'] ?? null,
                    'notes' => $refundEntry['notes'] ?? ('Refund for return invoice: '.$code),
                    'received_by' => $user->id,
                    'type' => Payment::TYPE_REFUND,
                ]);
            }

            $newBalance = max(0, $purchaseInvoice->balance - $total);
            $newStatus = $purchaseInvoice->status;

            if ($newBalance <= 0.01 && $purchaseInvoice->status !== PurchaseInvoice::STATUS_PAID) {
                $newStatus = PurchaseInvoice::STATUS_PAID;
            }

            $purchaseInvoice->update([
                'balance' => $newBalance,
                'status' => $newStatus,
            ]);

            return $returnInvoice;
        });

        return redirect()->route('app.invoices.purchases.returns.show', $returnInvoice->id)
            ->with('success', __('purchasing.returns.created') ?? 'تم تسجيل فاتورة الإرجاع وتخفيض المخزن بنجاح');
    }

    /**
     * Show return invoice.
     */
    public function showReturn(PurchaseReturnInvoice $purchaseReturnInvoice)
    {
        $this->authorize('viewReturn', $purchaseReturnInvoice);

        $purchaseReturnInvoice->load([
            'purchaseInvoice.supplier',
            'purchaseInvoice.payments.receivedBy',
            'purchaseInvoice.returnInvoices',
            'lines.part',
            'center.address',
        ]);

        return Inertia::render('Purchasing/Invoices/ReturnShow', [
            'returnInvoice' => $purchaseReturnInvoice,
            'canManageAttachment' => request()->user()->can('updateReturn', $purchaseReturnInvoice),
        ]);
    }

    /**
     * Upload return attachment.
     */
    public function uploadReturnAttachment(Request $request, PurchaseReturnInvoice $purchaseReturnInvoice, PurchaseAttachmentService $attachments)
    {
        $this->authorize('updateReturn', $purchaseReturnInvoice);

        $request->validate([
            'attachment' => 'required|file|mimes:pdf,jpg,png|max:1024',
        ]);

        $attachments->replace($purchaseReturnInvoice, $request->file('attachment'));

        return back()->with('success', __('messages.attachment_uploaded') ?? 'تم رفع المرفق بنجاح');
    }

    /**
     * Destroy return attachment.
     */
    public function destroyReturnAttachment(PurchaseReturnInvoice $purchaseReturnInvoice, PurchaseAttachmentService $attachments)
    {
        $this->authorize('updateReturn', $purchaseReturnInvoice);

        $attachments->replace($purchaseReturnInvoice, null);

        return back()->with('success', __('messages.attachment_deleted') ?? 'تم حذف المرفق بنجاح');
    }

    /**
     * Record refund payment for return invoice.
     */
    public function recordReturnRefund(Request $request, PurchaseReturnInvoice $purchaseReturnInvoice)
    {
        $this->authorize('managePayments', $purchaseReturnInvoice);

        $purchaseInvoice = $purchaseReturnInvoice->purchaseInvoice;
        $remainingBalance = $purchaseReturnInvoice->calculateRemainingRefund();

        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01|max:'.$remainingBalance,
            'payment_method' => 'required|string',
            'payment_date' => 'nullable|date',
            'reference' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:500',
        ]);

        $paymentNotes = '['.$purchaseReturnInvoice->code.']';

        if (! empty($validated['notes'])) {
            $paymentNotes .= ' - '.$validated['notes'];
        }

        Payment::create([
            'tenant_id' => $purchaseReturnInvoice->tenant_id,
            'center_id' => $purchaseReturnInvoice->center_id,
            'purchase_invoice_id' => $purchaseInvoice->id,
            'amount' => $validated['amount'],
            'payment_date' => $validated['payment_date'] ?? now(),
            'payment_method' => $validated['payment_method'],
            'reference' => $validated['reference'] ?? null,
            'notes' => $paymentNotes,
            'received_by' => auth()->id(),
            'type' => Payment::TYPE_REFUND,
        ]);

        return back()->with('success', __('payments.recorded') ?? 'تم تسجيل الدفعة بنجاح');
    }

    /**
     * Print return invoice view.
     */
    public function printReturn(PurchaseReturnInvoice $purchaseReturnInvoice)
    {
        $this->authorize('viewReturn', $purchaseReturnInvoice);

        $purchaseReturnInvoice->load([
            'purchaseInvoice.supplier',
            'purchaseInvoice.payments.receivedBy',
            'purchaseInvoice.returnInvoices',
            'lines.part',
            'center.address',
        ]);

        return Inertia::render('Purchasing/Invoices/ReturnPrint', [
            'returnInvoice' => $purchaseReturnInvoice,
        ]);
    }
}
