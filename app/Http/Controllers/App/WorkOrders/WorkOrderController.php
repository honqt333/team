<?php

namespace App\Http\Controllers\App\WorkOrders;

use App\Actions\WorkOrder\CreateWorkOrderAction;
use App\Actions\WorkOrder\UpdateWorkOrderAction;
use App\Http\Requests\WorkOrderStoreRequest;
use App\Http\Requests\WorkOrderUpdateRequest;
use App\Models\Customer;
use App\Models\Department;
use App\Models\InventoryUnit;
use App\Models\Part;
use App\Models\Service;
use App\Models\Setting;
use App\Models\VehicleColor;
use App\Models\VehicleMake;
use App\Models\VehicleModel;
use App\Models\Warehouse;
use App\Models\WorkOrder;
use App\Services\NotificationService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

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
        $this->authorize("viewAny", WorkOrder::class);

        $status = request("status");
        $subFilter = request("subFilter") ?: request("sub_filter");

        $workOrders = $this->buildWorkOrderQuery($status, $subFilter)
            ->orderByDesc("created_at")
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
            ->with(["customer", "vehicle.make", "invoice"])
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
                $query->whereHas('payments', fn($p) => $p->where('type', 'bad_debt'));
            } elseif ($subFilter === 'cancelled') {
                $query->where('status', WorkOrder::STATUS_CANCELLED);
            } elseif ($subFilter === 'closed') {
                $query->where('status', WorkOrder::STATUS_DONE)
                    ->whereRaw('NOT (' . WorkOrder::outstandingBalanceSql() . ')');
            } else {
                $query->where('status', WorkOrder::STATUS_DONE)
                    ->whereRaw('NOT (' . WorkOrder::outstandingBalanceSql() . ')');
            }
        }

        if (request("search")) {
            $search = request("search");
            $query->where(function ($q) use ($search) {
                $q->where("id", "like", "%{$search}%")
                  ->orWhereHas("customer", fn($c) => $c->where("name", "like", "%{$search}%"))
                  ->orWhereHas("vehicle", fn($v) => $v->where("plate_number", "like", "%{$search}%"));
            });
        }

        if (request("date_from")) {
            $query->whereDate('created_at', '>=', request("date_from"));
        }

        if (request("date_to")) {
            $query->whereDate('created_at', '<=', request("date_to"));
        }

        if (request("customer_type")) {
            $query->whereHas('customer', fn($c) => $c->where('type', request("customer_type")));
        }

        return $query;
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
            $baseQuery->whereIn('status', self::CLOSED_STATUSES)
                ->where(function ($q) {
                    $q->where('status', '!=', WorkOrder::STATUS_DONE)
                      ->orWhere(function ($sub) {
                          $sub->where('status', WorkOrder::STATUS_DONE)
                              ->where(function ($sub2) {
                                  $sub2->whereHas('invoice')
                                       ->orWhereRaw('NOT (' . WorkOrder::outstandingBalanceSql() . ')');
                              });
                      });
                });
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
        if (!empty($unstoredIds)) {
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
            ->selectRaw('SUM(CASE WHEN type IN ("payment", "Payment") THEN amount WHEN type IN ("refund", "Refund") THEN -amount ELSE 0 END) as paid')
            ->value('paid');

        return [
            'count' => $count,
            'balance' => round($totalRevenue - $totalPaid, 2)
        ];
    }

    /**
     * Display the work orders index page.
     */
    public function index(): Response
    {
        $this->authorize("viewAny", WorkOrder::class);

        $status = request("status");
        $subFilter = request("sub_filter");

        $customers = Customer::select("id", "name", "phone")->get();
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
                'closed' => WorkOrder::where('status', WorkOrder::STATUS_DONE)
                    ->whereRaw('NOT (' . WorkOrder::outstandingBalanceSql() . ')')
                    ->count(),
                'credit_invoices' => WorkOrder::where('status', WorkOrder::STATUS_DONE)
                    ->hasOutstandingBalance()
                    ->whereHas('invoice')
                    ->count(),
                'bad_debts' => WorkOrder::whereHas('payments', fn($p) => $p->where('type', 'bad_debt'))->count(),
                'cancelled' => WorkOrder::where('status', WorkOrder::STATUS_CANCELLED)->count(),
            ];
        }

        if ($status) {
            $workOrders = $this->buildWorkOrderQuery($status, $subFilter)
                ->orderByDesc("created_at")
                ->paginate(15)
                ->withQueryString();

            if ($workOrders) {
                $workOrders->getCollection()->each->append(['total', 'total_paid', 'balance', 'bad_debt']);
            }
        } else {
            $workOrders = null;
        }

        return Inertia::render("WorkOrders/Index", [
            "workOrders" => $workOrders,
            "counts" => $counts,
            "filterCounts" => $filterCounts,
            "statusFilter" => $status,
            "subFilter" => $subFilter,
            "customers" => $customers,
            "makes" => $makes,
            "colors" => $colors,
            "modelsByMake" => $modelsByMake,
            "departments" => $departments,
            "filters" => request()->only(["search", "date_from", "date_to", "status", "sub_filter"]),
        ]);
    }

    /**
     * Export work orders to XLSX.
     */
    public function export(): mixed
    {
        $this->authorize('viewAny', WorkOrder::class);

        $filename = 'work_orders_' . date('Y-m-d_His') . '.xlsx';

        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\WorkOrdersExport(),
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
        NotificationService::notifyOwner(
            tenantId: $request->user()->tenant_id,
            type: 'work_order.created',
            title: 'أمر عمل جديد #' . ($workOrder->code ?? $workOrder->id),
            body: 'تم إنشاء أمر عمل جديد' . ($workOrder->customer ? ' للعميل ' . $workOrder->customer->name : ''),
            actionUrl: '/app/work-orders/' . $workOrder->id,
            actorId: $request->user()->id,
        );

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
            'vehicle.make',
            'items' => fn($q) => $q->withoutGlobalScope('center_scoped')->with([
                'service' => fn($q) => $q->withoutGlobalScope('center_scoped')->with(['department' => fn($q) => $q->withoutGlobalScope('center_scoped')]),
                'technicians.employee.jobTitle',
                'parts' => fn($q) => $q->withoutGlobalScope('center_scoped')->with(['part', 'warehouse']),
                'itemNotes.user.roles',
            ]),
            'generalNotes.user.roles',
            'generalNotes.workOrderItem.service.department' => fn($q) => $q->withoutGlobalScope('center_scoped'),
            'damageMarks',
            'photos',
            'departments' => fn($q) => $q->withoutGlobalScope('center_scoped'),
            'payments' => fn($q) => $q->with('receivedBy')->orderByDesc('payment_date'),
            'parts' => fn($q) => $q->withoutGlobalScope('center_scoped')->with([
                'part' => fn($q) => $q->withSum('inventoryBalances', 'qty_on_hand'),
                'workOrderItem.service'
            ]),
            'attachments.user',
            'activities.user',
            'inspections' => fn($q) => $q->withoutGlobalScope('center_scoped')->with('performedBy'),
            'invoice' => fn($q) => $q->withoutGlobalScope('center_scoped'),
        ]);

        // TODO(refactor): Move tab-specific relations to partial-reload endpoints.

        $itemsByDepartment = $workOrder->items->groupBy(function ($item) {
            if ($item->service?->type === Service::TYPE_PACKAGE) {
                return 'packages';
            }
            return $item->department_id ?? $item->service?->department_id ?? 0;
        });

        $customers = Customer::select('id', 'name', 'phone')->get();

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

        $technicians = \App\Models\HR\Employee::where('center_id', $workOrder->center_id)
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

        $enableSystematicInspections = Setting::where('key', 'enable_systematic_inspections')->value('value') ?? 'true';

        return Inertia::render('WorkOrders/Show', [
            'workOrder' => $workOrder,
            'itemsByDepartment' => $itemsByDepartment,
            'customers' => $customers,
            'makes' => $makes,
            'colors' => $colors,
            'modelsByMake' => $modelsByMake,
            'departments' => $departments,
            'services' => $services,
            'technicians' => $technicians,
            'warehouses' => $warehouses,
            'inventoryParts' => $inventoryParts,
            'inventoryUnits' => $inventoryUnits,
            'enableSystematicInspections' => filter_var($enableSystematicInspections, FILTER_VALIDATE_BOOLEAN),
        ]);
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

