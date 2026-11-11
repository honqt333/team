<?php

declare(strict_types=1);

namespace App\Http\Controllers\App;

use App\Enums\InvoiceType;
use App\Http\Controllers\Controller;
use App\Models\InventoryUnit;
use App\Models\Invoice;
use App\Models\InvoiceTemplate;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseReturnInvoice;
use App\Models\Supplier;
use App\Models\VehicleColor;
use App\Models\Warehouse;
use App\Models\WorkOrder;
use App\Services\CreditNoteService;
use App\Services\InvoiceService;
use App\Services\NotificationService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class InvoicesController extends Controller
{
    protected InvoiceService $invoiceService;

    protected CreditNoteService $creditNoteService;

    public function __construct(InvoiceService $invoiceService, CreditNoteService $creditNoteService)
    {
        $this->invoiceService = $invoiceService;
        $this->creditNoteService = $creditNoteService;
    }

    /**
     * Show single invoice
     */
    public function show(Invoice $invoice)
    {
        $this->authorize('view', $invoice);

        $invoice->load([
            'customer',
            'originalInvoice:id,invoice_number,issue_date,total_incl_tax',
            'creditNotes:id,original_invoice_id,invoice_number,issue_date,total_incl_tax,status',
            'workOrder.vehicle.make',
            'workOrder.vehicle.model',
            'workOrder.items',
            'workOrder.parts',
            'lines',
            'payments.receivedBy',
            'center',
            'tenant.taxSettings',
            'companyTransaction',
        ]);

        if ($invoice->status !== 'draft' && $invoice->tax_enabled_snapshot && empty($invoice->zatca_qr_tlv)) {
            $invoice->update([
                'zatca_qr_tlv' => $this->invoiceService->generateZatcaQr($invoice),
            ]);
        }

        return Inertia::render('Invoices/Show', [
            'invoice' => $invoice,
            'colors' => VehicleColor::active()->ordered()->get(['id', 'name_ar', 'name_en', 'hex_code']),
        ]);
    }

    /**
     * Generate invoice from work order
     */
    public function createFromWorkOrder(Request $request, WorkOrder $workOrder)
    {
        $this->authorize('update', $workOrder);

        // Check if already has invoice
        if ($workOrder->invoice) {
            return back()->with('error', __('invoices.already_exists'));
        }

        // Check work order status
        if (! in_array($workOrder->status, ['completed', 'delivered', 'done'])) {
            return back()->with('error', __('invoices.work_order_not_completed'));
        }

        try {
            $data = $request->validate([
                'due_date' => 'nullable|date|after_or_equal:today',
                'notes' => 'nullable|string',
            ]);

            $invoice = $this->invoiceService->createFromWorkOrder($workOrder, auth()->user(), $data);
            $this->invoiceService->issueInvoice($invoice);

            // Notify owner about new invoice
            NotificationService::notifyOwner(
                tenantId: auth()->user()->tenant_id,
                type: 'invoice.created',
                title: 'فاتورة جديدة #'.$invoice->invoice_number,
                body: 'تم إنشاء فاتورة من أمر العمل #'.($workOrder->code ?? $workOrder->id),
                actionUrl: '/app/invoices/'.$invoice->id,
                actorId: auth()->id(),
            );

            return redirect()->route('app.invoices.show', $invoice->id)
                ->with('success', __('invoices.created'));
        } catch (Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Get returnable items summary for Credit Note modal.
     */
    public function creditNoteSummary(WorkOrder $workOrder): JsonResponse
    {
        $this->authorize('view', $workOrder);

        if (! in_array($workOrder->status, ['done', 'delivered', 'completed'])) {
            return response()->json([
                'is_possible' => false,
                'message' => 'يجب أن يكون كرت الصيانة مكتملاً (Done) لإنشاء إشعار دائن.',
                'invoice' => null,
                'services' => [],
                'parts' => [],
            ]);
        }

        if (! $workOrder->invoice()->exists()) {
            return response()->json([
                'is_possible' => false,
                'message' => 'لا توجد فاتورة ضريبية مسجلة لهذا الكرت بعد. يجب إصدار الفاتورة أولاً.',
                'invoice' => null,
                'services' => [],
                'parts' => [],
            ]);
        }

        try {
            $summary = app(CreditNoteService::class)->getReturnableSummary($workOrder);

            return response()->json($summary);
        } catch (Exception $e) {
            return response()->json([
                'is_possible' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Store a new Credit Note (Return Invoice) for a Work Order.
     */
    public function storeCreditNote(Request $request, WorkOrder $workOrder)
    {
        $this->authorize('update', $workOrder);

        if (! in_array($workOrder->status, ['done', 'delivered', 'completed']) || ! $workOrder->invoice()->exists()) {
            return response()->json([
                'message' => 'يجب إكمال كرت الصيانة وإصدار فاتورة ضريبية له قبل إصدار إشعار دائن.',
            ], 422);
        }

        $validated = $request->validate([
            'services' => 'nullable|array',
            'services.*.work_order_item_id' => 'nullable|integer',
            'services.*.id' => 'nullable|integer',
            'services.*.return_amount' => 'required|numeric|min:0',
            'parts' => 'nullable|array',
            'parts.*.work_order_item_part_id' => 'nullable|integer',
            'parts.*.id' => 'nullable|integer',
            'parts.*.return_qty' => 'required|numeric|min:0',
            'parts.*.return_to_stock' => 'nullable|boolean',
            'return_reason' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:1000',
            'offset_unpaid_balance' => 'nullable|boolean',
            'record_refund' => 'nullable|boolean',
            'refund_payment_method' => 'nullable|string',
            'refund_reference' => 'nullable|string|max:100',
            'refund_notes' => 'nullable|string|max:255',
            'payment_due_date' => 'nullable|date',
            'refund_payment' => 'nullable|array',
            'refund_payment.amount' => 'nullable|numeric|min:0',
            'refund_payment.payment_method' => 'nullable|string',
            'refund_payment.payment_date' => 'nullable|date',
            'refund_payment.reference' => 'nullable|string|max:100',
            'refund_payment.notes' => 'nullable|string|max:255',
        ]);

        try {
            $creditNote = app(CreditNoteService::class)->createCreditNote($workOrder, $validated, $request->user());

            if ($request->wantsJson()) {
                $totalReturn = (float) $creditNote->total_incl_tax;
                $debtOffset = (float) ($creditNote->debt_offset ?? 0);
                $netRefundDue = (float) ($creditNote->net_refund_due ?? 0);

                return response()->json([
                    'success' => true,
                    'message' => 'تم إنشاء إشعار الدائن بنجاح.',
                    'credit_note_id' => $creditNote->id,
                    'credit_note_number' => $creditNote->invoice_number,
                    'credit_note_total' => $totalReturn,
                    'debt_offset' => $debtOffset,
                    'net_refund_due' => $netRefundDue,
                    'redirect_url' => route('app.invoices.show', $creditNote->id),
                ]);
            }

            return redirect()->route('app.invoices.show', $creditNote->id)
                ->with('success', 'تم إنشاء إشعار الدائن (فاتورة الإرجاع) بنجاح.');
        } catch (Exception $e) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 422);
            }

            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Get returnable items summary for Credit Note modal directly from an invoice.
     */
    public function invoiceCreditNoteSummary(Invoice $invoice): JsonResponse
    {
        $this->authorize('view', $invoice);

        if ($invoice->type === InvoiceType::CREDIT_NOTE || $invoice->type?->value === 'credit_note') {
            return response()->json([
                'is_possible' => false,
                'message' => 'لا يمكن إنشاء إشعار دائن لإشعار دائن آخر.',
                'invoice' => null,
                'services' => [],
                'parts' => [],
            ], 422);
        }

        try {
            $summary = $this->creditNoteService->getReturnableSummaryForInvoice($invoice);

            return response()->json($summary);
        } catch (Exception $e) {
            return response()->json([
                'is_possible' => false,
                'message' => $e->getMessage(),
                'invoice' => null,
                'services' => [],
                'parts' => [],
            ], 422);
        }
    }

    /**
     * Store a new Credit Note directly from an invoice.
     */
    public function storeInvoiceCreditNote(Request $request, Invoice $invoice)
    {
        $this->authorize('createCreditNote', $invoice);

        $data = $request->validate([
            'services' => 'nullable|array',
            'services.*.work_order_item_id' => 'nullable|integer',
            'services.*.id' => 'nullable|integer',
            'services.*.return_amount' => 'required|numeric|min:0',
            'parts' => 'nullable|array',
            'parts.*.work_order_item_part_id' => 'nullable|integer',
            'parts.*.id' => 'nullable|integer',
            'parts.*.return_qty' => 'required|numeric|min:0',
            'parts.*.return_to_stock' => 'nullable|boolean',
            'return_reason' => 'nullable|string',
            'notes' => 'nullable|string',
            'record_refund' => 'nullable|boolean',
            'refund_payment_method' => 'nullable|string',
            'refund_reference' => 'nullable|string',
            'refund_notes' => 'nullable|string',
            'refund_payment' => 'nullable|array',
            'refund_payment.amount' => 'nullable|numeric|min:0',
            'refund_payment.payment_method' => 'nullable|string',
            'refund_payment.payment_date' => 'nullable|date',
            'refund_payment.reference' => 'nullable|string',
            'payment_due_date' => 'nullable|date',
            'due_date' => 'nullable|date',
        ]);

        try {
            $creditNote = $this->creditNoteService->createCreditNoteForInvoice($invoice, $data, auth()->user());

            if ($request->wantsJson()) {
                $totalReturn = (float) $creditNote->total_incl_tax;
                $debtOffset = (float) ($creditNote->debt_offset ?? 0);
                $netRefundDue = (float) ($creditNote->net_refund_due ?? 0);

                return response()->json([
                    'success' => true,
                    'message' => 'تم إنشاء إشعار الدائن (فاتورة الإرجاع) بنجاح.',
                    'credit_note_id' => $creditNote->id,
                    'credit_note_number' => $creditNote->invoice_number,
                    'credit_note_total' => $totalReturn,
                    'debt_offset' => $debtOffset,
                    'net_refund_due' => $netRefundDue,
                    'credit_note' => [
                        'id' => $creditNote->id,
                        'invoice_number' => $creditNote->invoice_number,
                        'total_incl_tax' => $creditNote->total_incl_tax,
                        'payment_status' => $creditNote->payment_status,
                    ],
                    'redirect_url' => route('app.invoices.show', $creditNote->id),
                ]);
            }

            return redirect()->route('app.invoices.show', $creditNote->id)
                ->with('success', 'تم إنشاء إشعار الدائن (فاتورة الإرجاع) بنجاح.');
        } catch (Exception $e) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 422);
            }

            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Print invoice view
     */
    public function print(Invoice $invoice)
    {
        $this->authorize('view', $invoice);

        $invoice->load([
            'customer',
            'originalInvoice:id,invoice_number,issue_date,total_incl_tax',
            'workOrder.vehicle.make',
            'workOrder.vehicle.model',
            'lines',
            'payments',
            'center.address',
            'tenant.address',
            'tenant.taxSettings',
            'companyTransaction',
        ]);

        if ($invoice->status !== 'draft' && $invoice->tax_enabled_snapshot && empty($invoice->zatca_qr_tlv)) {
            $invoice->update([
                'zatca_qr_tlv' => $this->invoiceService->generateZatcaQr($invoice),
            ]);
        }

        $template = InvoiceTemplate::getDefault($invoice->tenant_id, 'tax_invoice');

        $labels = $template->getAllLabels();

        if ($invoice->tenant->invoice_title) {
            $labels['document_title'] = $invoice->tenant->invoice_title;
        }

        $invoice->append(['balance', 'is_paid', 'payment_status_label']);

        return Inertia::render('Invoices/Print', [
            'invoice' => $invoice,
            'template' => $template,
            'labels' => $labels,
        ]);
    }

    /**
     * Print proforma from work order
     */
    public function printProforma(WorkOrder $workOrder)
    {
        $this->authorize('view', $workOrder);

        return redirect()->route('work-orders.print.proforma', $workOrder->id);
    }

    /**
     * Invoices Hub - landing page
     */
    public function hub()
    {
        $tenantId = auth()->user()->tenant_id;
        $centerId = auth()->user()->current_center_id;

        $salesCount = Invoice::where('tenant_id', $tenantId)
            ->where('center_id', $centerId)
            ->whereNotExists(function ($q) {
                $q->select(DB::raw(1))
                    ->from('company_transactions')
                    ->whereColumn('company_transactions.invoice_id', 'invoices.id');
            })
            ->count();

        $purchasesCount = PurchaseInvoice::where('tenant_id', $tenantId)
            ->where('center_id', $centerId)
            ->whereNotExists(function ($q) {
                $q->select(DB::raw(1))
                    ->from('company_transactions')
                    ->whereColumn('company_transactions.purchase_invoice_id', 'purchase_invoices.id');
            })
            ->count();

        return Inertia::render('Invoices/Hub', [
            'salesCount' => $salesCount,
            'purchasesCount' => $purchasesCount,
        ]);
    }

    /**
     * Sales Invoices index
     */
    public function salesIndex(Request $request)
    {
        $tenantId = auth()->user()->tenant_id;
        $centerId = auth()->user()->current_center_id;

        $baseQuery = Invoice::where('tenant_id', $tenantId)
            ->where('center_id', $centerId)
            ->whereNotExists(function ($q) {
                $q->select(DB::raw(1))
                    ->from('company_transactions')
                    ->whereColumn('company_transactions.invoice_id', 'invoices.id');
            })
            ->with(['customer', 'originalInvoice:id,invoice_number,issue_date,total_incl_tax', 'workOrder.items', 'workOrder.parts'])
            ->when($request->input('search'), function ($q, $search) {
                $q->where(function ($query) use ($search) {
                    $query->where('invoice_number', 'like', "%{$search}%")
                        ->orWhereHas('customer', fn ($c) => $c->where('name', 'like', "%{$search}%"));
                });
            })
            ->when($request->input('payment_status'), fn ($q, $s) => $q->where('payment_status', $s))
            ->when($request->input('date_from'), fn ($q, $d) => $q->whereDate('issue_date', '>=', $d))
            ->when($request->input('date_to'), fn ($q, $d) => $q->whereDate('issue_date', '<=', $d));

        $sortableColumns = [
            'invoice_number' => 'invoice_number',
            'issue_date' => 'issue_date',
            'total_incl_tax' => 'total_incl_tax',
            'total_excl_tax' => 'total_excl_tax',
            'balance' => 'balance',
        ];

        $sortColumn = (string) $request->input('sort');
        $sortDirection = strtolower((string) $request->input('direction', 'desc')) === 'asc' ? 'asc' : 'desc';

        if ($sortColumn && isset($sortableColumns[$sortColumn])) {
            $baseQuery->orderBy($sortableColumns[$sortColumn], $sortDirection);
        } else {
            $baseQuery->orderBy('issue_date', 'desc')
                ->orderBy('id', 'desc');
        }

        // Sales Invoices tab: standard sales invoices (exclude credit notes)
        $invoices = (clone $baseQuery)
            ->where(function ($q) {
                $q->where('type', 'invoice')
                    ->orWhere('type', InvoiceType::INVOICE)
                    ->orWhereNull('type');
            })
            ->paginate(25, ['*'], 'invoices_page')
            ->withQueryString();

        // Return Invoices tab: credit notes
        $returns = (clone $baseQuery)
            ->where(function ($q) {
                $q->where('type', 'credit_note')
                    ->orWhere('type', InvoiceType::CREDIT_NOTE);
            })
            ->paginate(25, ['*'], 'returns_page')
            ->withQueryString();

        return Inertia::render('Invoices/Sales/Index', [
            'invoices' => $invoices,
            'returns' => $returns,
            'filters' => $request->only(['search', 'payment_status', 'date_from', 'date_to', 'tab', 'sort', 'direction']),
        ]);
    }

    /**
     * Purchase Invoices index
     */
    public function purchasesIndex(Request $request)
    {
        $tenantId = auth()->user()->tenant_id;
        $centerId = auth()->user()->current_center_id;

        $query = PurchaseInvoice::where('tenant_id', $tenantId)
            ->where('center_id', $centerId)
            ->whereNotExists(function ($q) {
                $q->select(DB::raw(1))
                    ->from('company_transactions')
                    ->whereColumn('company_transactions.purchase_invoice_id', 'purchase_invoices.id');
            })
            ->with(['supplier', 'purchaseOrder'])
            ->when($request->input('search'), function ($q, $search) {
                $q->where(function ($query) use ($search) {
                    $query->where('code', 'like', "%{$search}%")
                        ->orWhere('invoice_number', 'like', "%{$search}%")
                        ->orWhereHas('supplier', fn ($s) => $s->where('name', 'like', "%{$search}%"));
                });
            })
            ->when($request->input('status'), fn ($q, $s) => $q->where('status', $s))
            ->when($request->input('date_from'), fn ($q, $d) => $q->whereDate('issue_date', '>=', $d))
            ->when($request->input('date_to'), fn ($q, $d) => $q->whereDate('issue_date', '<=', $d));

        $sort = (string) $request->input('sort');
        $direction = strtolower((string) $request->input('direction', 'desc')) === 'asc' ? 'asc' : 'desc';

        if ($sort === 'code') {
            $query->orderBy('code', $direction);
        } elseif ($sort === 'invoice_number') {
            $query->orderBy('invoice_number', $direction);
        } elseif ($sort === 'supplier') {
            $query->orderBy(
                Supplier::select('name')
                    ->whereColumn('suppliers.id', 'purchase_invoices.supplier_id')
                    ->limit(1),
                $direction
            );
        } elseif ($sort === 'issue_date') {
            $query->orderBy('issue_date', $direction);
        } elseif ($sort === 'due_date') {
            $query->orderBy('due_date', $direction);
        } elseif ($sort === 'subtotal') {
            $query->orderBy('subtotal', $direction);
        } elseif ($sort === 'total') {
            $query->orderBy('total', $direction);
        } elseif ($sort === 'balance') {
            $query->orderBy('balance', $direction);
        } elseif ($sort === 'status') {
            $query->orderBy('status', $direction);
        } elseif ($sort === 'created_at') {
            $query->orderBy('created_at', $direction);
        } else {
            $query->orderBy('id', 'desc');
        }

        $invoices = $query->paginate(25)->withQueryString();

        $returnsQuery = PurchaseReturnInvoice::where('tenant_id', $tenantId)
            ->where('center_id', $centerId)
            ->whereHas('purchaseInvoice', function ($q) {
                $q->whereNotExists(function ($sub) {
                    $sub->select(DB::raw(1))
                        ->from('company_transactions')
                        ->whereColumn('company_transactions.purchase_invoice_id', 'purchase_invoices.id');
                });
            })
            ->with(['purchaseInvoice.supplier', 'purchaseInvoice.payments'])
            ->when($request->input('search'), function ($q, $search) {
                $q->where(function ($query) use ($search) {
                    $query->where('code', 'like', "%{$search}%")
                        ->orWhereHas('purchaseInvoice.supplier', fn ($s) => $s->where('name', 'like', "%{$search}%"));
                });
            })
            ->when($request->input('date_from'), fn ($q, $d) => $q->whereDate('return_date', '>=', $d))
            ->when($request->input('date_to'), fn ($q, $d) => $q->whereDate('return_date', '<=', $d));

        if ($request->input('tab') === 'returns') {
            if ($sort === 'code') {
                $returnsQuery->orderBy('code', $direction);
            } elseif ($sort === 'return_date') {
                $returnsQuery->orderBy('return_date', $direction);
            } elseif ($sort === 'total') {
                $returnsQuery->orderBy('total', $direction);
            } elseif ($sort === 'subtotal') {
                $returnsQuery->orderBy('subtotal', $direction);
            } else {
                $returnsQuery->orderBy('id', 'desc');
            }
        } else {
            $returnsQuery->orderBy('id', 'desc');
        }

        $returns = $returnsQuery->paginate(25, ['*'], 'returns_page')->withQueryString();

        $suppliers = Supplier::forTenant($tenantId)->forCenter($centerId)->active()->get(['id', 'name']);
        $defaultWarehouse = Warehouse::forCenter($centerId)->default()->first();
        $warehouses = Warehouse::forCenter($centerId)->active()->get(['id', 'name']);
        $units = InventoryUnit::where('is_active', true)->get(['id', 'name_ar', 'name_en']);

        return Inertia::render('Invoices/Purchasing/Index', [
            'invoices' => $invoices,
            'returns' => $returns,
            'filters' => array_merge(
                $request->only(['search', 'status', 'date_from', 'date_to', 'tab']),
                [
                    'sort' => $sort ?: null,
                    'direction' => $sort ? $direction : null,
                ]
            ),
            'statuses' => [
                PurchaseInvoice::STATUS_DRAFT,
                PurchaseInvoice::STATUS_OPEN,
                PurchaseInvoice::STATUS_PAID,
                PurchaseInvoice::STATUS_CANCELLED,
            ],
            'suppliers' => $suppliers,
            'defaultWarehouse' => $defaultWarehouse,
            'warehouses' => $warehouses,
            'units' => $units,
        ]);
    }
}
