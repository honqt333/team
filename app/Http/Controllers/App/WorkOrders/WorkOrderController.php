<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\WorkOrders;

use App\Actions\WorkOrder\CreateWorkOrderAction;
use App\Actions\WorkOrder\UpdateWorkOrderAction;
use App\Exports\WorkOrdersExport;
use App\Http\Requests\WorkOrderStoreRequest;
use App\Http\Requests\WorkOrderUpdateRequest;
use App\Models\Customer;
use App\Models\Dealer;
use App\Models\Department;
use App\Models\HR\Employee;
use App\Models\InventoryUnit;
use App\Models\Part;
use App\Models\PurchaseInvoice;
use App\Models\Service;
use App\Models\Setting;
use App\Models\Supplier;
use App\Models\VehicleColor;
use App\Models\VehicleMake;
use App\Models\VehicleModel;
use App\Models\Warehouse;
use App\Models\WorkOrder;
use App\Models\WorkOrderPurchaseAllocation;
use BackedEnum;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;

class WorkOrderController
{
    use AuthorizesRequests;

    // Status constants for consistency
    protected const OPEN_STATUSES = ['open', 'in_progress', 'draft', 'on_hold', 'ready_for_qc'];

    protected const CLOSED_STATUSES = ['done', 'cancelled'];

    protected const ACTIVE_STATUSES = ['open', 'in_progress', 'on_hold', 'ready_for_qc'];

    /**
     * API index endpoint for work orders.
     */
    public function apiIndex(): JsonResponse
    {
        $this->authorize('viewAny', WorkOrder::class);

        $status = request('status');
        $subFilter = request('subFilter') ?: request('sub_filter');

        $workOrders = $this->buildWorkOrderQuery($status, $subFilter)
            ->orderByDesc('created_at')
            ->paginate(15)
            ->withQueryString();

        if ($workOrders) {
            $workOrders->getCollection()->each->append(['total', 'total_paid', 'balance', 'bad_debt']);
        }

        return response()->json($workOrders);
    }

    /**
     * Build the base work order query with common filters.
     */
    protected function buildWorkOrderQuery(?string $status = null, ?string $subFilter = null): Builder
    {
        $query = WorkOrder::query()
            ->with(['customer', 'vehicle.make', 'invoice', 'payments', 'items.parts'])
            ->withCount('items');

        if ($status === 'open') {
            if ($subFilter === 'overdue') {
                $query->whereIn('status', self::ACTIVE_STATUSES)
                    ->whereNotNull('expected_end_date')
                    ->where('expected_end_date', '<', now()->startOfDay());
            } elseif ($subFilter === 'draft') {
                $query->where('status', WorkOrder::STATUS_DRAFT);
            } elseif ($subFilter === 'pending_payment') {
                $query->where('status', WorkOrder::STATUS_DONE)
                    ->hasOutstandingBalance()
                    ->whereDoesntHave('invoice');
            } elseif ($subFilter === 'completed') {
                $query->readyForExit();
            } elseif ($subFilter === 'in_progress') {
                $query->whereIn('status', self::ACTIVE_STATUSES);
            } else {
                $query->whereIn('status', self::ACTIVE_STATUSES);
            }
        } elseif ($status === 'closed') {
            if ($subFilter === 'credit_invoices') {
                $query->where('status', WorkOrder::STATUS_DONE)
                    ->hasOutstandingBalance()
                    ->whereHas('invoice');
            } elseif ($subFilter === 'bad_debts') {
                $query->whereHas('payments', fn ($p) => $p->where('type', 'bad_debt'));
            } elseif ($subFilter === 'cancelled') {
                $query->where('status', WorkOrder::STATUS_CANCELLED);
            } elseif ($subFilter === 'closed') {
                $query->where('status', WorkOrder::STATUS_DONE)
                    ->whereRaw('NOT ('.WorkOrder::outstandingBalanceSql().')');
            } else {
                $this->applyClosedSectionScope($query);
            }
        }

        if (request('search')) {
            $search = request('search');
            $query->where(function ($q) use ($search) {
                $q->where('id', 'like', "%{$search}%")
                    ->orWhereHas('customer', fn ($c) => $c->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('vehicle', fn ($v) => $v->where('plate_number', 'like', "%{$search}%"));
            });
        }

        if (request('date_from')) {
            $query->whereDate('created_at', '>=', request('date_from'));
        }

        if (request('date_to')) {
            $query->whereDate('created_at', '<=', request('date_to'));
        }

        if (request('customer_type')) {
            $query->whereHas('customer', fn ($c) => $c->where('type', request('customer_type')));
        }

        return $query;
    }

    /**
     * Scope query to all work orders belonging to the closed section
     * (settled closed, credit invoices, bad debts, and cancelled).
     */
    protected function applyClosedSectionScope(Builder $query): Builder
    {
        return $query->where(function (Builder $q) {
            $q->where('status', WorkOrder::STATUS_CANCELLED)
                ->orWhere(function (Builder $done) {
                    $done->where('status', WorkOrder::STATUS_DONE)
                        ->where(function (Builder $doneSub) {
                            $doneSub->whereHas('invoice')
                                ->orWhereRaw('NOT ('.WorkOrder::outstandingBalanceSql().')');
                        });
                })
                ->orWhereHas('payments', fn (Builder $p) => $p->where('type', 'bad_debt'));
        });
    }

    /**
     * Calculate stats for multiple statuses in a single efficient query.
     */
    protected function getStatsForStatuses(string $tabType): array
    {
        $baseQuery = WorkOrder::query();

        if ($tabType === 'open') {
            $baseQuery->where(function ($q) {
                $q->whereIn('status', self::OPEN_STATUSES)
                    ->orWhere(function ($sub) {
                        $sub->where('status', WorkOrder::STATUS_DONE)
                            ->hasOutstandingBalance()
                            ->whereDoesntHave('invoice');
                    });
            });
        } else {
            $this->applyClosedSectionScope($baseQuery);
        }

        $count = (clone $baseQuery)->count();

        if ($count === 0) {
            return ['count' => 0, 'balance' => 0];
        }

        $storedTotal = (float) (clone $baseQuery)
            ->where('total_incl_tax', '>', 0)
            ->sum('total_incl_tax');

        $unstoredIds = (clone $baseQuery)
            ->where('total_incl_tax', '<=', 0)
            ->pluck('id')
            ->all();

        $unstoredItems = 0.0;
        $unstoredParts = 0.0;

        if (! empty($unstoredIds)) {
            $unstoredItems = (float) DB::table('work_order_items')
                ->whereIn('work_order_id', $unstoredIds)
                ->sum(DB::raw('(unit_price * qty) - discount_amount'));

            $unstoredParts = (float) DB::table('work_order_item_parts')
                ->whereIn('work_order_id', $unstoredIds)
                ->sum(DB::raw('(unit_price * qty) - discount'));
        }

        $unstoredTotal = ($unstoredItems + $unstoredParts) * 1.15;
        $totalRevenue = $storedTotal + $unstoredTotal;

        $totalPaid = (float) DB::table('payments')
            ->whereIn('work_order_id', (clone $baseQuery)->select('id'))
            ->selectRaw('SUM(CASE WHEN type = "payment" THEN amount WHEN type = "refund" THEN -amount ELSE 0 END) as paid')
            ->value('paid');

        return [
            'count' => $count,
            'balance' => round($totalRevenue - $totalPaid, 2),
        ];
    }

    /**
     * Display the work orders index page.
     */
    public function index(): Response
    {
        $this->authorize('viewAny', WorkOrder::class);

        $status = request('status');
        $subFilter = request('sub_filter');

        $customers = Customer::select('id', 'name', 'phone')->get();
        $dealers = Dealer::where('tenant_id', auth()->user()->tenant_id)
            ->active()
            ->orderBy('name')
            ->get(['id', 'name', 'name_en', 'type']);
        $makes = VehicleMake::ordered()->get();
        $colors = VehicleColor::active()->ordered()->get();
        $departments = Department::active()->ordered()->get();
        $modelsByMake = VehicleModel::query()
            ->select('id', 'make_id', 'name_ar', 'name_en')
            ->get()
            ->groupBy('make_id');

        $counts = [
            'open' => $this->getStatsForStatuses('open'),
            'closed' => $this->getStatsForStatuses('closed'),
        ];

        $filterCounts = [];

        if ($status === 'open') {
            $filterCounts = [
                'open' => WorkOrder::whereIn('status', self::ACTIVE_STATUSES)->count(),
                'draft' => WorkOrder::where('status', WorkOrder::STATUS_DRAFT)->count(),
                'overdue' => WorkOrder::whereIn('status', self::ACTIVE_STATUSES)
                    ->whereNotNull('expected_end_date')
                    ->where('expected_end_date', '<', now()->startOfDay())
                    ->count(),
                'pending_payment' => WorkOrder::where('status', WorkOrder::STATUS_DONE)
                    ->hasOutstandingBalance()
                    ->whereDoesntHave('invoice')
                    ->count(),
                'completed' => WorkOrder::readyForExit()->count(),
            ];
        } elseif ($status === 'closed') {
            $filterCounts = [
                'all' => $this->applyClosedSectionScope(WorkOrder::query())->count(),
                'closed' => WorkOrder::where('status', WorkOrder::STATUS_DONE)
                    ->whereRaw('NOT ('.WorkOrder::outstandingBalanceSql().')')
                    ->count(),
                'credit_invoices' => WorkOrder::where('status', WorkOrder::STATUS_DONE)
                    ->hasOutstandingBalance()
                    ->whereHas('invoice')
                    ->count(),
                'bad_debts' => WorkOrder::whereHas('payments', fn ($p) => $p->where('type', 'bad_debt'))->count(),
                'cancelled' => WorkOrder::where('status', WorkOrder::STATUS_CANCELLED)->count(),
            ];
        }

        $filters = request()->only(['search', 'date_from', 'date_to', 'status', 'sub_filter']);

        if (request()->boolean('print')) {
            $printRows = $status
                ? $this->buildWorkOrderQuery($status, $subFilter)
                    ->orderByDesc('created_at')
                    ->limit(5000)
                    ->get()
                    ->each->append(['total', 'total_paid', 'balance', 'bad_debt'])
                    ->values()
                : collect();

            return Inertia::render('Reports/Print', [
                'reportType' => 'work_orders',
                'backRoute' => 'work-orders.index',
                'filters' => $filters,
                'kpis' => [],
                'rows' => $printRows,
                'companyInfo' => [],
            ]);
        }

        if ($status) {
            $workOrders = $this->buildWorkOrderQuery($status, $subFilter)
                ->orderByDesc('created_at')
                ->paginate(15)
                ->withQueryString();

            if ($workOrders) {
                $workOrders->getCollection()->each->append(['total', 'total_paid', 'balance', 'bad_debt']);
            }
        } else {
            $workOrders = null;
        }

        return Inertia::render('WorkOrders/Index', [
            'workOrders' => $workOrders,
            'counts' => $counts,
            'filterCounts' => $filterCounts,
            'statusFilter' => $status,
            'subFilter' => $subFilter,
            'customers' => $customers,
            'dealers' => $dealers,
            'makes' => $makes,
            'colors' => $colors,
            'modelsByMake' => $modelsByMake,
            'departments' => $departments,
            'filters' => request()->only(['search', 'date_from', 'date_to', 'status', 'sub_filter']),
        ]);
    }

    /**
     * Export work orders to XLSX.
     */
    public function export(): mixed
    {
        $this->authorize('viewAny', WorkOrder::class);

        $filename = 'work_orders_'.date('Y-m-d_His').'.xlsx';

        return Excel::download(
            new WorkOrdersExport,
            $filename
        );
    }

    /**
     * Create a new work order.
     */
    public function store(WorkOrderStoreRequest $request, CreateWorkOrderAction $createWorkOrder): RedirectResponse|JsonResponse
    {
        $this->authorize('create', WorkOrder::class);

        $workOrder = $createWorkOrder->execute($request->user(), $request->validated());

        if ($request->expectsJson()) {
            $workOrder->refresh();
            $workOrder->load('items');

            return response()->json($workOrder, 201);
        }

        $workOrder->load('customer');

        // NO notifyOwner() HERE ON PURPOSE.
        //
        // Creating the work order already fired WorkOrderObserver::created()
        // → WorkOrderCreated → NotifyOwnerOnCreation, which sends the
        // 'work_order.created' notification to the tenant owner. Calling
        // notifyOwner() again here produced a SECOND, identical notification
        // row — the owner saw every new work order twice in their bell.
        //
        // The listener is the single source of truth for this event, and it
        // also fires for work orders created outside this controller (API
        // routes, imports, actions).

        return redirect()->route('work-orders.show', $workOrder)->with('success', __('messages.work_order_created'));
    }

    /**
     * Display a work order.
     */
    public function show(WorkOrder $workOrder): Response
    {
        $this->authorize('view', $workOrder);

        $workOrder->load([
            'customer',
            'dealer:id,name,name_en,type,phone,tax_number',
            'vehicle.make',
            'items' => fn ($q) => $q->withoutGlobalScope('center_scoped')->with([
                'service' => fn ($q) => $q->withoutGlobalScope('center_scoped')->with(['department' => fn ($q) => $q->withoutGlobalScope('center_scoped')]),
                'technicians.employee.jobTitle',
                'parts' => fn ($q) => $q->withoutGlobalScope('center_scoped')->with(['part' => fn ($pq) => $pq->with('inventoryBalances'), 'warehouse', 'purchaseAllocation']),
                'supplierServiceAllocation.purchaseInvoice.supplier',
                'itemNotes.user.roles',
                'qualityChecks' => fn ($q) => $q->with('checkedBy:id,name')->latest('created_at')->limit(5),
                'heldBy:id,name',
            ]),
            'generalNotes.user.roles',
            'generalNotes.workOrderItem.service.department' => fn ($q) => $q->withoutGlobalScope('center_scoped'),
            'damageMarks',
            'photos',
            'departments' => fn ($q) => $q->withoutGlobalScope('center_scoped'),
            'payments' => fn ($q) => $q->with('receivedBy')->orderByDesc('payment_date'),
            'parts' => fn ($q) => $q->withoutGlobalScope('center_scoped')->with([
                'part' => fn ($q) => $q->with('inventoryBalances')->withSum('inventoryBalances', 'qty_on_hand'),
                'workOrderItem.service',
                'purchaseAllocation.purchaseInvoice.supplier',
            ]),
            'attachments.user',
            'activities.user',
            'inspections' => fn ($q) => $q->withoutGlobalScope('center_scoped')->with('performedBy'),
            'invoices' => fn ($q) => $q->withoutGlobalScope('center_scoped')->orderBy('id'),
            'purchaseInvoices' => fn ($q) => $q->with(['supplier:id,name', 'lines.part'])->withCount('lines')->orderByDesc('issue_date'),
            'purchaseAllocations' => fn ($q) => $q->withoutGlobalScope('center_scoped')->with([
                'purchaseInvoice' => fn ($pi) => $pi->select('id', 'code', 'invoice_number', 'supplier_id', 'issue_date'),
                'purchaseInvoice.supplier:id,name',
                'targetPart:id,name,part_number',
                'targetService:id,title',
            ])->orderByDesc('id'),
        ]);

        $workOrder->setRelation(
            'invoice',
            $workOrder->invoices
                ->first(fn ($inv) => ($inv->type instanceof BackedEnum ? $inv->type->value : $inv->type) === 'invoice')
        );
        $workOrder->setRelation(
            'creditNotes',
            $workOrder->invoices
                ->filter(fn ($inv) => ($inv->type instanceof BackedEnum ? $inv->type->value : $inv->type) === 'credit_note')
                ->values()
        );

        // TODO(refactor): Move tab-specific relations to partial-reload endpoints.

        $itemsByDepartment = $workOrder->items->groupBy(function ($item) {
            if ($item->service?->type === Service::TYPE_PACKAGE) {
                return 'packages';
            }

            return $item->department_id ?? $item->service?->department_id ?? 0;
        });

        $customers = Customer::select('id', 'name', 'phone')->get();
        $dealers = Dealer::where('tenant_id', $workOrder->tenant_id)
            ->active()
            ->orderBy('name')
            ->get(['id', 'name', 'name_en', 'type']);

        $makes = VehicleMake::withoutGlobalScope('center_scoped')
            ->where('center_id', $workOrder->center_id)
            ->ordered()
            ->get();

        $colors = VehicleColor::withoutGlobalScope('center_scoped')
            ->where('center_id', $workOrder->center_id)
            ->active()
            ->ordered()
            ->get();

        $departments = Department::withoutGlobalScope('center_scoped')
            ->where('center_id', $workOrder->center_id)
            ->active()
            ->ordered()
            ->get();

        $services = Service::withoutGlobalScope('center_scoped')
            ->where('center_id', $workOrder->center_id)
            ->active()
            ->ordered()
            ->get();

        $technicians = Employee::where('center_id', $workOrder->center_id)
            ->whereNotNull('user_id')
            ->active()
            ->with(['jobTitle', 'user'])
            ->get()
            ->map(function ($employee) {
                return [
                    'id' => $employee->user_id,
                    'name' => $employee->display_name,
                    'name_ar' => $employee->name_ar,
                    'name_en' => $employee->name_en,
                    'job_title_ar' => $employee->jobTitle?->name_ar,
                    'job_title_en' => $employee->jobTitle?->name_en,
                    'photo_url' => $employee->user?->photo_url,
                ];
            });

        $modelsByMake = VehicleModel::query()
            ->select('id', 'make_id', 'name_ar', 'name_en')
            ->get()
            ->groupBy('make_id');

        $warehouses = Warehouse::where('center_id', $workOrder->center_id)
            ->orWhereNull('center_id')
            ->select('id', 'name')
            ->get();

        $inventoryParts = Part::where('tenant_id', $workOrder->tenant_id)
            ->where('is_active', true)
            ->select('id', 'sku', 'name_ar', 'name_en')
            ->get();

        $inventoryUnits = InventoryUnit::where('is_active', true)
            ->select('id', 'name_ar', 'name_en')
            ->get();

        $availablePurchaseInvoices = PurchaseInvoice::query()
            ->where('tenant_id', $workOrder->tenant_id)
            ->where('center_id', $workOrder->center_id)
            ->whereNull('work_order_id')
            ->where('status', '!=', PurchaseInvoice::STATUS_CANCELLED)
            ->whereNotExists(function ($q) {
                $q->select(DB::raw(1))
                    ->from('company_transactions')
                    ->whereColumn('company_transactions.purchase_invoice_id', 'purchase_invoices.id');
            })
            ->with(['supplier:id,name', 'lines.part'])
            ->latest('id')
            ->limit(100)
            ->get(['id', 'code', 'invoice_number', 'supplier_id', 'purchase_order_id', 'issue_date', 'subtotal', 'tax_amount', 'total', 'balance', 'status', 'notes']);

        $suppliers = Supplier::forTenant($workOrder->tenant_id)
            ->forCenter($workOrder->center_id)
            ->active()
            ->get(['id', 'name', 'type']);

        // The inspection preference belongs to the tenant — reading the
        // system-wide row would show one workshop's setting to another.
        $enableSystematicInspections = Setting::forTenant(
            auth()->user()?->tenant_id,
            'enable_systematic_inspections',
            'true'
        );

        return Inertia::render('WorkOrders/Show', [
            'workOrder' => $workOrder,
            'itemsByDepartment' => $itemsByDepartment,
            'customers' => $customers,
            'dealers' => $dealers,
            'makes' => $makes,
            'colors' => $colors,
            'modelsByMake' => $modelsByMake,
            'departments' => $departments,
            'services' => $services,
            'technicians' => $technicians,
            'laborEntries' => $workOrder->laborEntries()
                ->with('user:id,name')
                ->get()
                ->map(fn ($e) => [
                    'id' => $e->id,
                    'work_order_id' => $e->work_order_id,
                    'work_order_item_id' => $e->work_order_item_id,
                    'user_id' => $e->user_id,
                    'user_name' => $e->user?->name,
                    'status' => $e->status,
                    'started_at' => $e->started_at?->toIso8601String(),
                    'paused_at' => $e->paused_at?->toIso8601String(),
                    'ended_at' => $e->ended_at?->toIso8601String(),
                    'paused_duration_seconds' => (int) $e->paused_duration_seconds,
                    'effective_duration_seconds' => $e->effectiveDurationSeconds(),
                ]),
            'serverNow' => now()->toIso8601String(),
            'warehouses' => $warehouses,
            'inventoryParts' => $inventoryParts,
            'inventoryUnits' => $inventoryUnits,
            'availablePurchaseInvoices' => $availablePurchaseInvoices,
            'purchaseAllocations' => $workOrder->purchaseAllocations,
            'suppliers' => $suppliers,
            'enableSystematicInspections' => filter_var($enableSystematicInspections, FILTER_VALIDATE_BOOLEAN),
        ]);
    }

    /**
     * Link an existing purchase invoice in the same tenant/center to this work order.
     */
    public function linkPurchaseInvoice(Request $request, WorkOrder $workOrder): RedirectResponse
    {
        $this->authorize('update', $workOrder);

        $validated = $request->validate([
            'purchase_invoice_id' => [
                'required',
                'integer',
                Rule::exists('purchase_invoices', 'id')
                    ->where('tenant_id', $workOrder->tenant_id)
                    ->where('center_id', $workOrder->center_id)
                    ->whereNull('deleted_at'),
            ],
            'allocated_amount' => ['nullable', 'numeric', 'min:0'],
            'allocated_qty' => ['nullable', 'numeric', 'min:0'],
        ]);

        $purchaseInvoice = PurchaseInvoice::query()
            ->where('tenant_id', $workOrder->tenant_id)
            ->where('center_id', $workOrder->center_id)
            ->findOrFail($validated['purchase_invoice_id']);

        $purchaseInvoice->update(['work_order_id' => $workOrder->id]);

        $workOrder->activities()->create([
            'tenant_id' => $workOrder->tenant_id,
            'center_id' => $workOrder->center_id,
            'user_id' => $request->user()?->id,
            'action' => 'purchase_invoice_linked',
            'description' => __('messages.work_orders.purchase_invoice_linked_activity', ['code' => $purchaseInvoice->code]),
        ]);

        return redirect()->back()->with('success', __('messages.work_orders.purchase_invoice_linked'));
    }

    /**
     * Allocate a purchase line/item to this work order.
     */
    public function allocatePurchaseLine(Request $request, WorkOrder $workOrder): RedirectResponse|JsonResponse
    {
        $this->authorize('update', $workOrder);

        $validated = $request->validate([
            'purchase_invoice_id' => [
                'required',
                'integer',
                Rule::exists('purchase_invoices', 'id')
                    ->where('tenant_id', $workOrder->tenant_id)
                    ->where('center_id', $workOrder->center_id)
                    ->whereNull('deleted_at'),
            ],
            'purchase_invoice_line_id' => [
                'nullable',
                'integer',
                Rule::exists('purchase_invoice_lines', 'id')
                    ->where('tenant_id', $workOrder->tenant_id),
            ],
            'type' => ['nullable', 'string', Rule::in(WorkOrderPurchaseAllocation::TYPES)],
            'item_description' => ['nullable', 'string', 'max:255'],
            'allocated_qty' => ['nullable', 'numeric', 'min:0.01'],
            'unit_cost' => ['nullable', 'numeric', 'min:0'],
            'tax_amount' => ['nullable', 'numeric', 'min:0'],
            'total_cost' => ['nullable', 'numeric', 'min:0'],
            'allocated_amount' => ['nullable', 'numeric', 'min:0'],
            'work_order_item_part_id' => [
                'nullable',
                'integer',
                Rule::exists('work_order_item_parts', 'id')
                    ->where('work_order_id', $workOrder->id),
            ],
            'work_order_item_id' => [
                'nullable',
                'integer',
                Rule::exists('work_order_items', 'id')
                    ->where('work_order_id', $workOrder->id),
            ],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $purchaseInvoice = PurchaseInvoice::query()
            ->where('tenant_id', $workOrder->tenant_id)
            ->where('center_id', $workOrder->center_id)
            ->findOrFail($validated['purchase_invoice_id']);

        $purchaseInvoice->update(['work_order_id' => $workOrder->id]);

        $qty = (float) ($validated['allocated_qty'] ?? 1);
        $unitCost = isset($validated['unit_cost'])
            ? (float) $validated['unit_cost']
            : (isset($validated['allocated_amount']) ? (float) $validated['allocated_amount'] / max(1, $qty) : (float) $purchaseInvoice->total);
        $taxAmount = isset($validated['tax_amount']) ? (float) $validated['tax_amount'] : 0.00;
        $totalCost = isset($validated['total_cost']) && (float) $validated['total_cost'] > 0
            ? (float) $validated['total_cost']
            : round(($qty * $unitCost) + $taxAmount, 2);

        $type = $validated['type'] ?? (
            $purchaseInvoice->source_type === 'supplier_service'
                ? WorkOrderPurchaseAllocation::TYPE_SUPPLIER_SERVICE
                : ($purchaseInvoice->source_type === 'warehouse' ? WorkOrderPurchaseAllocation::TYPE_WAREHOUSE_PART : WorkOrderPurchaseAllocation::TYPE_EXTERNAL_PART)
        );

        $itemDescription = ! empty($validated['item_description'])
            ? $validated['item_description']
            : ($purchaseInvoice->invoice_number ?: $purchaseInvoice->code ?: 'بند مشتريات');

        $allocation = $workOrder->purchaseAllocations()->create([
            'tenant_id' => $workOrder->tenant_id,
            'center_id' => $workOrder->center_id,
            'purchase_invoice_id' => $validated['purchase_invoice_id'],
            'purchase_invoice_line_id' => $validated['purchase_invoice_line_id'] ?? null,
            'work_order_item_part_id' => $validated['work_order_item_part_id'] ?? null,
            'work_order_item_id' => $validated['work_order_item_id'] ?? null,
            'type' => $type,
            'item_description' => $itemDescription,
            'allocated_qty' => $qty,
            'unit_cost' => $unitCost,
            'tax_amount' => $taxAmount,
            'total_cost' => $totalCost,
            'notes' => $validated['notes'] ?? null,
        ]);

        $workOrder->logActivity(
            'purchase_allocation_created',
            __('messages.work_orders.purchase_allocation_created_activity', ['item' => $allocation->item_description])
        );

        if ($request->wantsJson()) {
            return response()->json([
                'message' => __('messages.work_orders.purchase_allocation_created'),
                'allocation' => $allocation->load(['purchaseInvoice.supplier', 'targetPart', 'targetService']),
            ], 201);
        }

        return redirect()->back()->with('success', __('messages.work_orders.purchase_allocation_created'));
    }

    /**
     * Map or unmap a purchase allocation to a work order item part or work order item service.
     */
    public function mapAllocation(Request $request, WorkOrder $workOrder, WorkOrderPurchaseAllocation $allocation): RedirectResponse|JsonResponse
    {
        $this->authorize('update', $workOrder);

        if ((int) $allocation->work_order_id !== (int) $workOrder->id
            || (int) $allocation->tenant_id !== (int) $workOrder->tenant_id) {
            abort(403);
        }

        $validated = $request->validate([
            'target_type' => ['nullable', 'string', 'in:part,service,none'],
            'work_order_item_part_id' => [
                'nullable',
                'integer',
                Rule::exists('work_order_item_parts', 'id')
                    ->where('work_order_id', $workOrder->id),
            ],
            'work_order_item_id' => [
                'nullable',
                'integer',
                Rule::exists('work_order_items', 'id')
                    ->where('work_order_id', $workOrder->id),
            ],
            'unmap' => ['nullable', 'boolean'],
        ]);

        $unmap = $request->boolean('unmap') || ($validated['target_type'] ?? null) === 'none';

        if ($unmap) {
            $allocation->update([
                'work_order_item_part_id' => null,
                'work_order_item_id' => null,
            ]);
            $message = __('messages.work_orders.purchase_allocation_unmapped');
        } else {
            $updates = [];

            if ($request->has('work_order_item_part_id')) {
                $updates['work_order_item_part_id'] = $validated['work_order_item_part_id'];
            }

            if ($request->has('work_order_item_id')) {
                $updates['work_order_item_id'] = $validated['work_order_item_id'];
            }

            if (($validated['target_type'] ?? null) === 'part' && ! $request->has('work_order_item_id')) {
                $updates['work_order_item_id'] = null;
            } elseif (($validated['target_type'] ?? null) === 'service' && ! $request->has('work_order_item_part_id')) {
                $updates['work_order_item_part_id'] = null;
            }

            $allocation->update($updates);
            $message = __('messages.work_orders.purchase_allocation_mapped');
        }

        $workOrder->logActivity(
            'purchase_allocation_mapped',
            $message.' ('.$allocation->item_description.')'
        );

        if ($request->wantsJson()) {
            return response()->json([
                'message' => $message,
                'allocation' => $allocation->fresh(['targetPart', 'targetService']),
            ]);
        }

        return redirect()->back()->with('success', $message);
    }

    /**
     * Delete/deallocate a purchase line allocation.
     */
    public function deallocatePurchaseLine(WorkOrder $workOrder, WorkOrderPurchaseAllocation $allocation): RedirectResponse|JsonResponse
    {
        $this->authorize('update', $workOrder);

        if ((int) $allocation->work_order_id !== (int) $workOrder->id
            || (int) $allocation->tenant_id !== (int) $workOrder->tenant_id) {
            abort(403);
        }

        $description = $allocation->item_description;
        $allocation->delete();

        $workOrder->logActivity(
            'purchase_allocation_deleted',
            __('messages.work_orders.purchase_allocation_deleted').' ('.$description.')'
        );

        if (request()->wantsJson()) {
            return response()->json([
                'message' => __('messages.work_orders.purchase_allocation_deleted'),
            ]);
        }

        return redirect()->back()->with('success', __('messages.work_orders.purchase_allocation_deleted'));
    }

    /**
     * Create a quick purchase invoice directly allocated to this work order.
     */
    public function storeQuickPurchaseInvoice(Request $request, WorkOrder $workOrder): RedirectResponse|JsonResponse
    {
        $this->authorize('update', $workOrder);

        $validated = $request->validate([
            'supplier_id' => [
                'required',
                'integer',
                Rule::exists('suppliers', 'id')
                    ->where('tenant_id', $workOrder->tenant_id)
                    ->where('center_id', $workOrder->center_id),
            ],
            'invoice_number' => ['nullable', 'string', 'max:100'],
            'issue_date' => ['required', 'date'],
            'due_date' => ['nullable', 'date'],
            'type' => ['nullable', 'string', 'in:external_parts,supplier_service,external_part'],
            'is_deferred' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'lines' => ['nullable', 'array'],
            'lines.*.item_description' => ['nullable', 'string', 'max:255'],
            'lines.*.allocated_qty' => ['nullable', 'numeric', 'min:0.001'],
            'lines.*.unit_cost' => ['nullable', 'numeric', 'min:0'],
            'lines.*.tax_amount' => ['nullable', 'numeric', 'min:0'],
            'lines.*.total_cost' => ['nullable', 'numeric', 'min:0'],
            'lines.*.work_order_item_part_id' => [
                'nullable',
                'integer',
                Rule::exists('work_order_item_parts', 'id')->where('work_order_id', $workOrder->id),
            ],
            'lines.*.work_order_item_id' => [
                'nullable',
                'integer',
                Rule::exists('work_order_items', 'id')->where('work_order_id', $workOrder->id),
            ],
            'lines.*.notes' => ['nullable', 'string', 'max:500'],
            // Fallback to support legacy 'items'
            'items' => ['nullable', 'array'],
            'items.*.description' => ['nullable', 'string', 'max:255'],
            'items.*.unit_cost' => ['nullable', 'numeric', 'min:0'],
            'items.*.qty' => ['nullable', 'numeric', 'min:0.001'],
            'items.*.tax_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ]);

        $rawLines = $validated['lines'] ?? [];

        if (empty($rawLines) && ! empty($validated['items'])) {
            foreach ($validated['items'] as $item) {
                $qty = (float) ($item['qty'] ?? 1);
                $cost = (float) ($item['unit_cost'] ?? 0);
                $rate = isset($item['tax_rate']) ? (float) $item['tax_rate'] : 15.0;
                $lineSubtotal = round($qty * $cost, 2);
                $lineTax = round($lineSubtotal * ($rate / 100), 2);
                $rawLines[] = [
                    'item_description' => $item['description'] ?? '',
                    'allocated_qty' => $qty,
                    'unit_cost' => $cost,
                    'tax_amount' => $lineTax,
                    'total_cost' => $lineSubtotal + $lineTax,
                ];
            }
        }

        if (empty($rawLines)) {
            throw ValidationException::withMessages([
                'lines' => [__('validation.required', ['attribute' => 'lines'])],
            ]);
        }

        $allocationType = ($validated['type'] ?? 'external_parts') === 'supplier_service'
            ? WorkOrderPurchaseAllocation::TYPE_SUPPLIER_SERVICE
            : WorkOrderPurchaseAllocation::TYPE_EXTERNAL_PART;

        $createdAllocations = [];

        $invoice = DB::transaction(function () use ($workOrder, $validated, $rawLines, $allocationType, &$createdAllocations) {
            $subtotal = 0.0;
            $taxAmount = 0.0;

            foreach ($rawLines as $line) {
                $qty = (float) ($line['allocated_qty'] ?? 1);
                $cost = (float) ($line['unit_cost'] ?? 0);
                $lineSubtotal = round($qty * $cost, 2);
                $lineTax = isset($line['tax_amount']) ? (float) $line['tax_amount'] : 0.0;
                $subtotal += $lineSubtotal;
                $taxAmount += $lineTax;
            }
            $total = round($subtotal + $taxAmount, 2);

            $invoiceNumber = $validated['invoice_number'] ?? null;

            if (empty($invoiceNumber)) {
                $invoiceNumber = 'PINV-'.now()->format('YmdHis');
            }

            $purchaseInvoice = PurchaseInvoice::create([
                'tenant_id' => $workOrder->tenant_id,
                'center_id' => $workOrder->center_id,
                'supplier_id' => $validated['supplier_id'],
                'work_order_id' => $workOrder->id,
                'invoice_number' => $invoiceNumber,
                'code' => PurchaseInvoice::generateCode($workOrder->tenant_id),
                'issue_date' => $validated['issue_date'],
                'due_date' => $validated['due_date'] ?? null,
                'status' => PurchaseInvoice::STATUS_OPEN,
                'subtotal' => $subtotal,
                'tax_amount' => $taxAmount,
                'total' => $total,
                'balance' => ($validated['is_deferred'] ?? false) ? $total : 0.0,
                'notes' => $validated['notes'] ?? null,
            ]);

            foreach ($rawLines as $line) {
                $qty = (float) ($line['allocated_qty'] ?? 1);
                $cost = (float) ($line['unit_cost'] ?? 0);
                $lineSubtotal = round($qty * $cost, 2);
                $lineTax = isset($line['tax_amount']) ? (float) $line['tax_amount'] : 0.0;
                $lineTotal = isset($line['total_cost']) && (float) $line['total_cost'] > 0
                    ? (float) $line['total_cost']
                    : round($lineSubtotal + $lineTax, 2);

                $createdAllocations[] = $workOrder->purchaseAllocations()->create([
                    'tenant_id' => $workOrder->tenant_id,
                    'center_id' => $workOrder->center_id,
                    'purchase_invoice_id' => $purchaseInvoice->id,
                    'purchase_invoice_line_id' => null,
                    'work_order_item_part_id' => $line['work_order_item_part_id'] ?? null,
                    'work_order_item_id' => $line['work_order_item_id'] ?? null,
                    'type' => $allocationType,
                    'item_description' => $line['item_description'] ?? '',
                    'allocated_qty' => $qty,
                    'unit_cost' => $cost,
                    'tax_amount' => $lineTax,
                    'total_cost' => $lineTotal,
                    'notes' => $line['notes'] ?? null,
                ]);
            }

            return $purchaseInvoice;
        });

        $workOrder->logActivity(
            'quick_purchase_invoice_created',
            __('messages.work_orders.quick_purchase_invoice_created').' ('.$invoice->code.')'
        );

        if ($request->wantsJson()) {
            return response()->json([
                'message' => __('messages.work_orders.quick_purchase_invoice_created'),
                'purchase_invoice' => $invoice->load('supplier'),
                'allocations' => $createdAllocations,
            ], 201);
        }

        return redirect()->back()->with('success', __('messages.work_orders.quick_purchase_invoice_created'));
    }

    /**
     * Backward-compatible alias for quick purchase invoice creation.
     */
    public function quickPurchaseInvoice(Request $request, WorkOrder $workOrder): RedirectResponse|JsonResponse
    {
        return $this->storeQuickPurchaseInvoice($request, $workOrder);
    }

    /**
     * Unlink a purchase invoice from this work order.
     */
    public function unlinkPurchaseInvoice(Request $request, WorkOrder $workOrder, PurchaseInvoice $purchaseInvoice): RedirectResponse
    {
        $this->authorize('update', $workOrder);

        if ((int) $purchaseInvoice->tenant_id !== (int) $workOrder->tenant_id
            || (int) $purchaseInvoice->work_order_id !== (int) $workOrder->id) {
            abort(403);
        }

        $purchaseInvoice->update(['work_order_id' => null]);

        $workOrder->activities()->create([
            'tenant_id' => $workOrder->tenant_id,
            'center_id' => $workOrder->center_id,
            'user_id' => $request->user()?->id,
            'action' => 'purchase_invoice_unlinked',
            'description' => __('messages.work_orders.purchase_invoice_unlinked_activity', ['code' => $purchaseInvoice->code]),
        ]);

        return redirect()->back()->with('success', __('messages.work_orders.purchase_invoice_unlinked'));
    }

    /**
     * Update a work order.
     */
    public function update(WorkOrderUpdateRequest $request, WorkOrder $workOrder, UpdateWorkOrderAction $updateWorkOrder): RedirectResponse
    {
        $this->authorize('update', $workOrder);

        $updateWorkOrder->execute($workOrder, $request->user(), $request->validated());

        return redirect()->back()->with('success', __('messages.work_order_updated'));
    }

    /**
     * Delete a work order.
     */
    public function destroy(WorkOrder $workOrder): RedirectResponse
    {
        $this->authorize('delete', $workOrder);

        foreach ($workOrder->photos as $photo) {
            Storage::disk('public')->delete($photo->path);
        }

        $workOrder->delete();

        return redirect()->route('work-orders.index')->with('success', __('messages.work_order_deleted'));
    }
}
