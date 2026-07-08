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
}

