<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\PurchaseInvoice;
use Illuminate\Http\Request;
use Inertia\Inertia;

use App\Models\Supplier;
use App\Models\Warehouse;
use App\Models\InventoryUnit;
use App\Services\Purchasing\PurchasingService;

class PurchaseInvoicesController extends Controller
{
    public function __construct(
        protected PurchasingService $purchasingService
    ) {}

    public function index(Request $request)
    {
        $this->authorize('viewAny', PurchaseInvoice::class);

        $tenantId = auth()->user()->tenant_id;
        $centerId = auth()->user()->current_center_id;

        $query = PurchaseInvoice::where('tenant_id', $tenantId)
            ->where('center_id', $centerId)
            ->with(['supplier', 'purchaseOrder'])
            ->when($request->input('search'), fn($q, $s) =>
                $q->where('code', 'like', "%{$s}%")
                  ->orWhere('invoice_number', 'like', "%{$s}%")
                  ->orWhereHas('supplier', fn($sq) => $sq->where('name', 'like', "%{$s}%"))
            )
            ->when($request->input('status'), fn($q, $s) => $q->where('status', $s))
            ->when($request->input('date_from'), fn($q, $d) => $q->whereDate('issue_date', '>=', $d))
            ->when($request->input('date_to'), fn($q, $d) => $q->whereDate('issue_date', '<=', $d))
            ->orderBy('id', 'desc');

        $suppliers = Supplier::forTenant($tenantId)->forCenter($centerId)->active()->get(['id', 'name']);
        $defaultWarehouse = Warehouse::forCenter($centerId)->default()->first();
        $warehouses = Warehouse::forCenter($centerId)->active()->get(['id', 'name']);
        $units = InventoryUnit::where('is_active', true)->get(['id', 'name_ar', 'name_en']);

        return Inertia::render('Purchasing/Invoices/Index', [
            'invoices' => $query->paginate(25)->withQueryString(),
            'filters'  => $request->only(['search', 'status', 'date_from', 'date_to']),
            'stats'    => [
                'total'  => PurchaseInvoice::where('tenant_id', $tenantId)->where('center_id', $centerId)->count(),
                'open'   => PurchaseInvoice::where('tenant_id', $tenantId)->where('center_id', $centerId)->where('status', 'open')->count(),
                'paid'   => PurchaseInvoice::where('tenant_id', $tenantId)->where('center_id', $centerId)->where('status', 'paid')->count(),
                'draft'  => PurchaseInvoice::where('tenant_id', $tenantId)->where('center_id', $centerId)->where('status', 'draft')->count(),
            ],
            'suppliers' => $suppliers,
            'defaultWarehouse' => $defaultWarehouse,
            'warehouses' => $warehouses,
            'units' => $units,
        ]);
    }

    public function store(Request $request)
    {
        $this->authorize('create', PurchaseInvoice::class);

        $user = auth()->user();

        $validated = $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'warehouse_id' => 'required|exists:warehouses,id',
            'invoice_number' => 'required|string|max:100',
            'issue_date' => 'required|date',
            'due_date' => 'nullable|date|after_or_equal:issue_date',
            'create_credit_invoice' => 'nullable|boolean',
            'notes' => 'nullable|string|max:1000',
            'items' => 'required|array|min:1',
            'items.*.part_id' => 'required|exists:parts,id',
            'items.*.qty' => 'required|numeric|min:0.001',
            'items.*.unit_cost' => 'required|numeric|min:0',
            'items.*.tax_rate' => 'nullable|numeric|min:0|max:100',
            'items.*.purchase_unit_id' => 'nullable|exists:inventory_units,id',
            'items.*.purchase_conversion_factor' => 'nullable|numeric|min:0.0001',
            'payments' => 'nullable|array',
            'payments.*.amount' => 'required|numeric|min:0.01',
            'payments.*.payment_method' => 'required|string',
            'payments.*.payment_date' => 'required|date',
            'payments.*.notes' => 'nullable|string|max:250',
        ]);

        $validated['tenant_id'] = $user->tenant_id;
        $validated['center_id'] = $user->current_center_id;

        $invoice = $this->purchasingService->createDirectPurchaseInvoice($validated, auth()->id());

        // Double-entry ledger mapping is handled via Billing/InvoiceService/transactions under the hood

        return redirect()->route('app.invoices.purchases.show', $invoice->id)
            ->with('success', __('purchasing.invoices.created') ?? 'تم إنشاء فاتورة الشراء بنجاح');
    }

    public function show(PurchaseInvoice $purchaseInvoice)
    {
        $this->authorize('view', $purchaseInvoice);

        $purchaseInvoice->load([
            'supplier', 
            'purchaseOrder', 
            'lines.part', 
            'lines.returnLines', 
            'center.address', 
            'payments.receivedBy',
            'returnInvoices.lines.part',
            'companyTransaction.incomeCategory',
            'tenant.address'
        ]);

        return Inertia::render('Purchasing/Invoices/Show', [
            'invoice' => $purchaseInvoice,
        ]);
    }

    public function uploadAttachment(Request $request, PurchaseInvoice $purchaseInvoice)
    {
        $this->authorize('update', $purchaseInvoice);

        $request->validate([
            'attachment' => 'required|file|mimes:pdf,jpg,png|max:1024', // max 1MB (1024KB)
        ]);

        if ($purchaseInvoice->attachment_path) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($purchaseInvoice->attachment_path);
        }

        $file = $request->file('attachment');
        $path = $file->store('purchases/invoices/' . $purchaseInvoice->id, 'public');

        $purchaseInvoice->update([
            'attachment_path' => $path
        ]);

        return back()->with('success', __('messages.attachment_uploaded') ?? 'تم رفع المرفق بنجاح');
    }

    public function destroyAttachment(PurchaseInvoice $purchaseInvoice)
    {
        $this->authorize('update', $purchaseInvoice);

        if ($purchaseInvoice->attachment_path) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($purchaseInvoice->attachment_path);
            $purchaseInvoice->update([
                'attachment_path' => null
            ]);
        }

        return back()->with('success', __('messages.attachment_deleted') ?? 'تم حذف المرفق بنجاح');
    }

    public function recordPayment(Request $request, PurchaseInvoice $purchaseInvoice)
    {
        $this->authorize('managePayments', $purchaseInvoice);

        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01|max:' . $purchaseInvoice->balance,
            'payment_method' => 'required|string',
            'payment_date' => 'nullable|date',
            'reference' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:500',
        ]);

        // Create the individual payment record
        \App\Models\Payment::create([
            'tenant_id' => $purchaseInvoice->tenant_id,
            'center_id' => $purchaseInvoice->center_id,
            'purchase_invoice_id' => $purchaseInvoice->id,
            'amount' => $validated['amount'],
            'payment_date' => $validated['payment_date'] ?? now(),
            'payment_method' => $validated['payment_method'],
            'reference' => $validated['reference'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'received_by' => auth()->id(),
            'type' => \App\Models\Payment::TYPE_PAYMENT,
        ]);

        $newBalance = max(0, $purchaseInvoice->balance - (float) $validated['amount']);
        $status = $newBalance <= 0.01 ? PurchaseInvoice::STATUS_PAID : PurchaseInvoice::STATUS_OPEN;

        $purchaseInvoice->update([
            'balance' => $newBalance,
            'status' => $status,
        ]);

        return back()->with('success', __('payments.recorded') ?? 'تم تسجيل الدفعة بنجاح');
    }

    public function recordReturn(Request $request, PurchaseInvoice $purchaseInvoice)
    {
        $this->authorize('createReturn', $purchaseInvoice);

        $user = auth()->user();

        $validated = $request->validate([
            'return_date'                          => 'required|date',
            'notes'                                => 'nullable|string|max:1000',
            'items'                                => 'required|array|min:1',
            'items.*.purchase_invoice_line_id'     => 'required|exists:purchase_invoice_lines,id',
            'items.*.qty'                          => 'required|numeric|min:0.01',
            'items.*.unit_cost'                    => 'required|numeric|min:0',
            // Multi-entry refund payments array
            'refund_payments'                      => 'nullable|array',
            'refund_payments.*.payment_method'     => 'required_with:refund_payments|string',
            'refund_payments.*.amount'             => 'required_with:refund_payments|numeric|min:0.01',
            'refund_payments.*.payment_date'       => 'nullable|date',
            'refund_payments.*.reference'          => 'nullable|string|max:100',
            'refund_payments.*.notes'              => 'nullable|string|max:500',
            'create_debit_note'                    => 'nullable|boolean',
            'debit_note_date'                      => 'nullable|required_if:create_debit_note,true|date',
        ]);

        $returnInvoice = \Illuminate\Support\Facades\DB::transaction(function () use ($validated, $purchaseInvoice, $user) {
            $code = \App\Models\PurchaseReturnInvoice::generateCode($user->tenant_id);
            
            // Calculate totals
            $subtotal = 0;
            $taxAmount = 0;
            
            $itemsToCreate = [];
            
            foreach ($validated['items'] as $item) {
                $line = \App\Models\PurchaseInvoiceLine::findOrFail($item['purchase_invoice_line_id']);
                
                $qty      = (float) $item['qty'];
                $unitCost = (float) $item['unit_cost'];
                $taxRate  = (float) ($line->tax_rate ?? 0);

                // ── Detect whether the original line was tax-inclusive ──────────────────
                // If the stored line total ≈ qty × unit_cost, the price already included
                // VAT (inclusive). Otherwise the tax was added on top (exclusive).
                $originalTotal     = (float) $line->total;
                $originalGross     = (float) $line->qty * (float) $line->unit_cost;
                $isInclusive       = abs($originalTotal - $originalGross) < 0.05;

                if ($isInclusive) {
                    // Price is inclusive: gross = qty × unit_cost, extract tax from it
                    $lineTotal    = $qty * $unitCost;
                    $lineSubtotal = $taxRate > 0 ? $lineTotal / (1 + $taxRate / 100) : $lineTotal;
                    $lineTax      = $lineTotal - $lineSubtotal;
                } else {
                    // Price is exclusive: add tax on top
                    $lineSubtotal = $qty * $unitCost;
                    $lineTax      = $lineSubtotal * ($taxRate / 100);
                    $lineTotal    = $lineSubtotal + $lineTax;
                }

                $subtotal  += $lineSubtotal;
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
            
            // Create PurchaseReturnInvoice
            $returnInvoice = \App\Models\PurchaseReturnInvoice::create([
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
            
            // Create lines and issue stock from inventory!
            $inventoryService = app(\App\Services\Inventory\InventoryService::class);
            
            // We need to know which warehouse to subtract from.
            $warehouseId = $purchaseInvoice->purchaseOrder->warehouse_id 
                ?? \App\Models\Warehouse::forCenter($user->current_center_id)->default()->first()?->id
                ?? \App\Models\Warehouse::forCenter($user->current_center_id)->first()?->id;

            if (!$warehouseId) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'warehouse' => ['No active warehouse found for center.'],
                ]);
            }

            foreach ($itemsToCreate as $itemData) {
                $itemData['purchase_return_invoice_id'] = $returnInvoice->id;
                \App\Models\PurchaseReturnInvoiceLine::create($itemData);
                
                // Process inventory deduction (issue stock due to return)
                $inventoryService->issue(
                    warehouseId: $warehouseId,
                    partId: $itemData['part_id'],
                    qty: (float) $itemData['qty'],
                    userId: $user->id,
                    referenceType: \App\Models\PurchaseReturnInvoiceLine::class,
                    referenceId: $returnInvoice->id,
                    notes: "Returned to supplier via return invoice: {$returnInvoice->code}"
                );
            }
            
            // Record refund payments (multi-entry array)
            $refundPaymentsTotal = 0;
            if (!empty($validated['refund_payments'])) {
                $hasPayments = $purchaseInvoice->payments()->where('type', \App\Models\Payment::TYPE_PAYMENT)->exists();
                if (!$hasPayments) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'refund_payments' => [__('payments.errors.cannot_refund_unpaid_invoice') ?? 'لا يمكن تسجيل دفعة مستردة لفاتورة شراء لم تدفع بعد']
                    ]);
                }
            }

            foreach ($validated['refund_payments'] ?? [] as $refundEntry) {
                $refundPaymentsTotal += (float) ($refundEntry['amount'] ?? 0);
            }

            foreach ($validated['refund_payments'] ?? [] as $refundEntry) {
                $refundAmt = (float) $refundEntry['amount'];
                if ($refundAmt <= 0) continue;

                \App\Models\Payment::create([
                    'tenant_id'           => $user->tenant_id,
                    'center_id'           => $user->current_center_id,
                    'purchase_invoice_id' => $purchaseInvoice->id,
                    'amount'              => $refundAmt,
                    'payment_date'        => $refundEntry['payment_date'] ?? $validated['return_date'] ?? now(),
                    'payment_method'      => $refundEntry['payment_method'] ?? 'cash',
                    'reference'           => $refundEntry['reference'] ?? null,
                    'notes'               => $refundEntry['notes'] ?? ('Refund for return invoice: ' . $code),
                    'received_by'         => $user->id,
                    'type'                => \App\Models\Payment::TYPE_REFUND,
                ]);
            }

            // Debit note handling — NO `payments` row is created.
            //
            // A debit note is a bookkeeping note (the supplier owes us this
            // amount back, or our payable to them is reduced). It is NOT an
            // actual cash movement, so it has no business in the `payments`
            // table — only the user's `create_debit_note` checkbox + the
            // `debit_note_date` are stored on the PurchaseReturnInvoice itself
            // (see the PurchaseReturnInvoice::create() call above).
            //
            // The full return total is then deducted from the invoice balance
            // in the block below, which is what actually reduces the
            // supplier's payable in our books:
            $newBalance = max(0, $purchaseInvoice->balance - $total);
            $newStatus = $purchaseInvoice->status;
            if ($newBalance <= 0.01 && $purchaseInvoice->status !== \App\Models\PurchaseInvoice::STATUS_PAID) {
                $newStatus = \App\Models\PurchaseInvoice::STATUS_PAID;
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

    public function showReturn(\App\Models\PurchaseReturnInvoice $purchaseReturnInvoice)
    {
        $this->authorize('viewReturn', $purchaseReturnInvoice);

        $purchaseReturnInvoice->load([
            'purchaseInvoice.supplier',
            'purchaseInvoice.payments.receivedBy',
            'lines.part',
            'center.address',
        ]);

        return Inertia::render('Purchasing/Invoices/ReturnShow', [
            'returnInvoice' => $purchaseReturnInvoice,
        ]);
    }

    public function uploadReturnAttachment(Request $request, \App\Models\PurchaseReturnInvoice $purchaseReturnInvoice)
    {
        $this->authorize('updateReturn', $purchaseReturnInvoice);

        $request->validate([
            'attachment' => 'required|file|mimes:pdf,jpg,png|max:1024', // max 1MB (1024KB)
        ]);

        if ($purchaseReturnInvoice->attachment_path) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($purchaseReturnInvoice->attachment_path);
        }

        $file = $request->file('attachment');
        $path = $file->store('purchases/returns/' . $purchaseReturnInvoice->id, 'public');

        $purchaseReturnInvoice->update([
            'attachment_path' => $path
        ]);

        return back()->with('success', __('messages.attachment_uploaded') ?? 'تم رفع المرفق بنجاح');
    }

    public function destroyReturnAttachment(\App\Models\PurchaseReturnInvoice $purchaseReturnInvoice)
    {
        $this->authorize('updateReturn', $purchaseReturnInvoice);

        if ($purchaseReturnInvoice->attachment_path) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($purchaseReturnInvoice->attachment_path);
            $purchaseReturnInvoice->update([
                'attachment_path' => null
            ]);
        }

        return back()->with('success', __('messages.attachment_deleted') ?? 'تم حذف المرفق بنجاح');
    }

    public function recordReturnRefund(Request $request, \App\Models\PurchaseReturnInvoice $purchaseReturnInvoice)
    {
        $this->authorize('managePayments', $purchaseReturnInvoice);

        $purchaseInvoice = $purchaseReturnInvoice->purchaseInvoice;

        // Calculate current remaining balance on this return invoice.
        // We only consider actual REFUND payments (cash/card paid back to the supplier).
        // Debit notes are excluded because they represent a credit/debt adjustment,
        // not an actual cash outflow — the remaining refund is only what is still
        // un-refunded in real money.
        $allRefunds = $purchaseInvoice->payments()
            ->where('type', \App\Models\Payment::TYPE_REFUND)
            ->get();
        
        $code = $purchaseReturnInvoice->code;
        $matchedRefunds = $allRefunds->filter(function($p) use ($code) {
            return str_contains($p->notes ?? '', $code);
        });

        $refunds = $matchedRefunds->count() > 0 ? $matchedRefunds : $allRefunds;
        $refundPaymentsTotal = $refunds->sum('amount');
        $remainingBalance = max(0.00, (float)$purchaseReturnInvoice->total - (float)$refundPaymentsTotal);

        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01|max:' . $remainingBalance,
            'payment_method' => 'required|string',
            'payment_date' => 'nullable|date',
            'reference' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:500',
        ]);

        // Automatically append return invoice code to the payment notes
        $paymentNotes = '[' . $purchaseReturnInvoice->code . ']';
        if (!empty($validated['notes'])) {
            $paymentNotes .= ' - ' . $validated['notes'];
        }

        // Create the individual refund payment record
        \App\Models\Payment::create([
            'tenant_id' => $purchaseReturnInvoice->tenant_id,
            'center_id' => $purchaseReturnInvoice->center_id,
            'purchase_invoice_id' => $purchaseInvoice->id,
            'amount' => $validated['amount'],
            'payment_date' => $validated['payment_date'] ?? now(),
            'payment_method' => $validated['payment_method'],
            'reference' => $validated['reference'] ?? null,
            'notes' => $paymentNotes,
            'received_by' => auth()->id(),
            'type' => \App\Models\Payment::TYPE_REFUND,
        ]);

        return back()->with('success', __('payments.recorded') ?? 'تم تسجيل الدفعة بنجاح');
    }

    /**
     * Print purchase invoice view
     */
    public function print(PurchaseInvoice $purchaseInvoice)
    {
        $this->authorize('view', $purchaseInvoice);

        $purchaseInvoice->load([
            'supplier', 
            'purchaseOrder', 
            'lines.part', 
            'center.address', 
            'payments.receivedBy',
            'tenant.address',
            'companyTransaction.incomeCategory',
        ]);

        return Inertia::render('Purchasing/Invoices/Print', [
            'invoice' => $purchaseInvoice,
        ]);
    }

    /**
     * Print purchase return invoice view
     */
    public function printReturn(\App\Models\PurchaseReturnInvoice $purchaseReturnInvoice)
    {
        $this->authorize('viewReturn', $purchaseReturnInvoice);

        $purchaseReturnInvoice->load([
            'purchaseInvoice.supplier',
            'purchaseInvoice.payments.receivedBy',
            'lines.part',
            'center.address',
        ]);

        return Inertia::render('Purchasing/Invoices/ReturnPrint', [
            'returnInvoice' => $purchaseReturnInvoice,
        ]);
    }
}
