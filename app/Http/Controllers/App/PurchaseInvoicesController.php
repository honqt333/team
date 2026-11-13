<?php

declare(strict_types=1);

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\InventoryUnit;
use App\Models\Payment;
use App\Models\PurchaseInvoice;
use App\Models\Supplier;
use App\Models\Warehouse;
use App\Models\WorkOrder;
use App\Models\WorkOrderItemPart;
use App\Services\PurchaseAttachmentService;
use App\Services\Purchasing\PurchasingService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

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
            ->with(['supplier', 'purchaseOrder', 'workOrder:id,code'])
            ->when($request->input('search'), fn ($q, $s) => $q->where('code', 'like', "%{$s}%")
                ->orWhere('invoice_number', 'like', "%{$s}%")
                ->orWhereHas('supplier', fn ($sq) => $sq->where('name', 'like', "%{$s}%"))
            )
            ->when($request->input('status'), fn ($q, $s) => $q->where('status', $s))
            ->when($request->input('date_from'), fn ($q, $d) => $q->whereDate('issue_date', '>=', $d))
            ->when($request->input('date_to'), fn ($q, $d) => $q->whereDate('issue_date', '<=', $d))
            ->orderBy('id', 'desc');

        $suppliers = Supplier::forTenant($tenantId)->forCenter($centerId)->active()->get(['id', 'name', 'type']);
        $defaultWarehouse = Warehouse::forCenter($centerId)->default()->first();
        $warehouses = Warehouse::forCenter($centerId)->active()->get(['id', 'name']);
        $units = InventoryUnit::where('is_active', true)->get(['id', 'name_ar', 'name_en']);
        $workOrders = WorkOrder::where('tenant_id', $tenantId)
            ->where('center_id', $centerId)
            ->whereNotIn('status', [WorkOrder::STATUS_CANCELLED])
            ->with([
                'customer:id,name',
                'vehicle:id,plate_number',
                'items' => function ($q) {
                    $q->where('status', '!=', 'cancelled')
                        ->with(['department:id,name_ar,name_en', 'service:id,name_ar,name_en,department_id,type', 'service.department:id,name_ar,name_en']);
                },
            ])
            ->latest('id')
            ->limit(100)
            ->get(['id', 'code', 'customer_id', 'vehicle_id', 'status']);

        return Inertia::render('Purchasing/Invoices/Index', [
            'invoices' => $query->paginate(25)->withQueryString(),
            'filters' => $request->only(['search', 'status', 'date_from', 'date_to']),
            'stats' => [
                'total' => PurchaseInvoice::where('tenant_id', $tenantId)->where('center_id', $centerId)->count(),
                'open' => PurchaseInvoice::where('tenant_id', $tenantId)->where('center_id', $centerId)->where('status', 'open')->count(),
                'paid' => PurchaseInvoice::where('tenant_id', $tenantId)->where('center_id', $centerId)->where('status', 'paid')->count(),
                'draft' => PurchaseInvoice::where('tenant_id', $tenantId)->where('center_id', $centerId)->where('status', 'draft')->count(),
            ],
            'suppliers' => $suppliers,
            'defaultWarehouse' => $defaultWarehouse,
            'warehouses' => $warehouses,
            'units' => $units,
            'workOrders' => $workOrders,
        ]);
    }

    public function store(Request $request)
    {
        $this->authorize('create', PurchaseInvoice::class);

        $user = auth()->user();

        $validated = $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'warehouse_id' => 'required|exists:warehouses,id',
            'work_order_id' => [
                'nullable',
                'integer',
                Rule::exists('work_orders', 'id')->where('tenant_id', $user->tenant_id),
            ],
            'invoice_number' => 'required|string|max:100',
            'issue_date' => 'required|date',
            'due_date' => 'nullable|date|after_or_equal:issue_date',
            'create_credit_invoice' => 'nullable|boolean',
            'purchase_type' => 'nullable|string|in:inventory,external_part,service',
            'notes' => 'nullable|string|max:1000',
            'items' => 'required|array|min:1',
            'items.*.part_id' => 'nullable|exists:parts,id',
            'items.*.name' => 'nullable|string|max:255',
            'items.*.custom_name' => 'nullable|string|max:255',
            'items.*.sku' => 'nullable|string|max:100',
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
            'workOrder:id,code,status,customer_id,vehicle_id,entry_date,created_at',
            'workOrder.customer:id,name,phone',
            'workOrder.vehicle:id,plate_number,make_id,model_id,make_other,model_other,year',
            'workOrder.vehicle.make:id,name_ar,name_en',
            'workOrder.vehicle.model:id,name_ar,name_en',
            'lines.part',
            'lines.returnLines',
            'center.address',
            'payments.receivedBy',
            'returnInvoices.lines.part',
            'companyTransaction.incomeCategory',
            'tenant.address',
        ]);

        $linkedWorkOrders = [];

        if ($purchaseInvoice->workOrder) {
            $wo = $purchaseInvoice->workOrder;
            $vehicleMake = $wo->vehicle?->make?->name_ar
                ?? $wo->vehicle?->make?->name_en
                ?? $wo->vehicle?->make_other;
            $vehicleModel = $wo->vehicle?->model?->name_ar
                ?? $wo->vehicle?->model?->name_en
                ?? $wo->vehicle?->model_other;
            $vehicleLabel = trim(collect([$vehicleMake, $vehicleModel, $wo->vehicle?->plate_number])->filter()->implode(' - '));

            $allocatedQty = (float) $purchaseInvoice->lines->sum('qty');

            if ($allocatedQty <= 0) {
                $allocatedQty = 1.0;
            }

            $linkedWorkOrders[] = [
                'id' => $wo->id,
                'work_order_id' => $wo->id,
                'code' => $wo->code,
                'status' => $wo->status,
                'customer_name' => $wo->customer?->name,
                'vehicle_label' => $vehicleLabel !== '' ? $vehicleLabel : null,
                'plate_number' => $wo->vehicle?->plate_number,
                'allocated_qty' => $allocatedQty,
                'allocated_amount' => (float) $purchaseInvoice->total,
                'date' => $purchaseInvoice->issue_date?->format('Y-m-d') ?? $wo->entry_date?->format('Y-m-d') ?? $wo->created_at?->format('Y-m-d'),
            ];
        }

        $partIds = $purchaseInvoice->lines->pluck('part_id')->filter()->unique()->values();

        if ($partIds->isNotEmpty()) {
            $existingWoIds = collect($linkedWorkOrders)->pluck('work_order_id')->all();
            $issuedParts = WorkOrderItemPart::withoutGlobalScope('center_scoped')
                ->where('tenant_id', $purchaseInvoice->tenant_id)
                ->where('center_id', $purchaseInvoice->center_id)
                ->whereIn('part_id', $partIds)
                ->when(! empty($existingWoIds), fn ($q) => $q->whereNotIn('work_order_id', $existingWoIds))
                ->with([
                    'workOrder:id,code,status,customer_id,vehicle_id,entry_date,created_at',
                    'workOrder.customer:id,name,phone',
                    'workOrder.vehicle:id,plate_number,make_id,model_id,make_other,model_other,year',
                    'workOrder.vehicle.make:id,name_ar,name_en',
                    'workOrder.vehicle.model:id,name_ar,name_en',
                ])
                ->latest('id')
                ->limit(50)
                ->get()
                ->groupBy('work_order_id');

            foreach ($issuedParts as $woId => $partsGroup) {
                $wo = $partsGroup->first()?->workOrder;

                if (! $wo) {
                    continue;
                }

                $vehicleMake = $wo->vehicle?->make?->name_ar
                    ?? $wo->vehicle?->make?->name_en
                    ?? $wo->vehicle?->make_other;
                $vehicleModel = $wo->vehicle?->model?->name_ar
                    ?? $wo->vehicle?->model?->name_en
                    ?? $wo->vehicle?->model_other;
                $vehicleLabel = trim(collect([$vehicleMake, $vehicleModel, $wo->vehicle?->plate_number])->filter()->implode(' - '));

                $allocatedQty = (float) $partsGroup->sum('qty');
                $allocatedAmount = (float) $partsGroup->sum(function ($itemPart) {
                    $cost = (float) ($itemPart->cost_snapshot ?? 0);

                    if ($cost > 0) {
                        return $cost * (float) $itemPart->qty;
                    }

                    return (float) ($itemPart->total ?? ((float) $itemPart->unit_price * (float) $itemPart->qty));
                });

                $linkedWorkOrders[] = [
                    'id' => $wo->id,
                    'work_order_id' => $wo->id,
                    'code' => $wo->code,
                    'status' => $wo->status,
                    'customer_name' => $wo->customer?->name,
                    'vehicle_label' => $vehicleLabel !== '' ? $vehicleLabel : null,
                    'plate_number' => $wo->vehicle?->plate_number,
                    'allocated_qty' => $allocatedQty,
                    'allocated_amount' => $allocatedAmount,
                    'date' => $partsGroup->first()?->created_at?->format('Y-m-d') ?? $wo->entry_date?->format('Y-m-d'),
                ];
            }
        }

        $purchaseInvoice->setAttribute('linked_work_orders', $linkedWorkOrders);

        return Inertia::render('Purchasing/Invoices/Show', [
            'invoice' => $purchaseInvoice,
            'linkedWorkOrders' => $linkedWorkOrders,
            'canManageAttachment' => request()->user()->can('update', $purchaseInvoice),
        ]);
    }

    public function uploadAttachment(Request $request, PurchaseInvoice $purchaseInvoice, PurchaseAttachmentService $attachments)
    {
        $this->authorize('update', $purchaseInvoice);

        $request->validate([
            'attachment' => 'required|file|mimes:pdf,jpg,png|max:1024', // max 1MB (1024KB)
        ]);

        $attachments->replace($purchaseInvoice, $request->file('attachment'));

        return back()->with('success', __('messages.attachment_uploaded') ?? 'تم رفع المرفق بنجاح');
    }

    public function destroyAttachment(PurchaseInvoice $purchaseInvoice, PurchaseAttachmentService $attachments)
    {
        $this->authorize('update', $purchaseInvoice);

        $attachments->replace($purchaseInvoice, null);

        return back()->with('success', __('messages.attachment_deleted') ?? 'تم حذف المرفق بنجاح');
    }

    public function recordPayment(Request $request, PurchaseInvoice $purchaseInvoice)
    {
        $this->authorize('managePayments', $purchaseInvoice);

        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01|max:'.$purchaseInvoice->balance,
            'payment_method' => 'required|string',
            'payment_date' => 'nullable|date',
            'reference' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:500',
        ]);

        // Create the individual payment record
        Payment::create([
            'tenant_id' => $purchaseInvoice->tenant_id,
            'center_id' => $purchaseInvoice->center_id,
            'purchase_invoice_id' => $purchaseInvoice->id,
            'amount' => $validated['amount'],
            'payment_date' => $validated['payment_date'] ?? now(),
            'payment_method' => $validated['payment_method'],
            'reference' => $validated['reference'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'received_by' => auth()->id(),
            'type' => Payment::TYPE_PAYMENT,
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
            'workOrder:id,code,status',
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
