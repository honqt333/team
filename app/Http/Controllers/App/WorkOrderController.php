<?php

namespace App\Http\Controllers\App;

use App\Actions\WorkOrder\CreateWorkOrderAction;
use App\Actions\WorkOrder\UpdateWorkOrderAction;
use App\Http\Requests\WorkOrderStoreRequest;
use App\Http\Requests\WorkOrderUpdateRequest;
use App\Models\Customer;
use App\Models\Service;
use App\Models\VehicleMake;
use App\Models\WorkOrder;
use App\Services\NotificationService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class WorkOrderController
{
    use AuthorizesRequests;

    // Status constants for consistency
    protected const OPEN_STATUSES = ['open', 'in_progress', 'draft', 'on_hold', 'ready_for_qc'];
    protected const CLOSED_STATUSES = ['done', 'cancelled'];
    protected const ACTIVE_STATUSES = ['open', 'in_progress', 'on_hold', 'ready_for_qc'];

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
            $workOrders->getCollection()->each->append(['total', 'total_paid', 'balance']);
        }

        return response()->json($workOrders);
    }

    /**
     * Build the base work order query with common filters.
     * Reduces code duplication between index() and apiIndex().
     */
    protected function buildWorkOrderQuery(?string $status = null, ?string $subFilter = null): Builder
    {
        $query = WorkOrder::query()
            ->with(["customer", "vehicle.make"])
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
     * Reduces N+1 by combining multiple queries into one.
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

        // Get count in one query
        $count = (clone $baseQuery)->count();

        if ($count === 0) {
            return ['count' => 0, 'balance' => 0];
        }

        // Single query to get stored totals sum
        $storedTotal = (float) (clone $baseQuery)
            ->where('total_incl_tax', '>', 0)
            ->sum('total_incl_tax');

        // Get unstored work order IDs (those with no total_incl_tax yet) using parameterized whereIn.
        // CenterScoped trait already filters by tenant/center, so no correlated subquery needed.
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

        // Single query to get total payments for all orders in these statuses
        $totalPaid = (float) DB::table('payments')
            ->whereIn('work_order_id', (clone $baseQuery)->select('id'))
            ->selectRaw('SUM(CASE WHEN type IN ("payment", "Payment") THEN amount WHEN type IN ("refund", "Refund") THEN -amount ELSE 0 END) as paid')
            ->value('paid');

        return [
            'count' => $count,
            'balance' => round($totalRevenue - $totalPaid, 2)
        ];
    }

    public function index(): Response
    {
        $this->authorize("viewAny", WorkOrder::class);

        $status = request("status");
        $subFilter = request("sub_filter");

        // Dependencies for Create Modal (always needed for Dashboard/Index)
        $customers = Customer::select("id", "name", "phone")->get();
        $makes = VehicleMake::ordered()->get();
        $colors = \App\Models\VehicleColor::active()->ordered()->get();
        $departments = \App\Models\Department::active()->ordered()->get();
        $modelsByMake = \App\Models\VehicleModel::query()
            ->select('id', 'make_id', 'name_ar', 'name_en')
            ->get()
            ->groupBy('make_id');

        // Get counts for open and closed statuses
        $counts = [
            'open' => $this->getStatsForStatuses('open'),
            'closed' => $this->getStatsForStatuses('closed'),
        ];

        // Filter tabs counts
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
                'bad_debts' => 0,
                'cancelled' => WorkOrder::where('status', WorkOrder::STATUS_CANCELLED)->count(),
            ];
        }

        if ($status) {
            $workOrders = $this->buildWorkOrderQuery($status, $subFilter)
                ->orderByDesc("created_at")
                ->paginate(15)
                ->withQueryString();

            if ($workOrders) {
                $workOrders->getCollection()->each->append(['total', 'total_paid', 'balance']);
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
    public function export()
    {
        $this->authorize('viewAny', WorkOrder::class);

        $filename = 'work_orders_' . date('Y-m-d_His') . '.xlsx';

        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\WorkOrdersExport(),
            $filename
        );
    }
    
    public function store(WorkOrderStoreRequest $request, CreateWorkOrderAction $createWorkOrder): \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
    {
        $this->authorize('create', WorkOrder::class);

        // Files are included in validated() if rules exist for them
        $workOrder = $createWorkOrder->execute($request->user(), $request->validated());

        // Return JSON for API requests, redirect for web requests
        if ($request->expectsJson()) {
            // Refresh to get items (created within transaction)
            $workOrder->refresh();
            $workOrder->load('items');
            return response()->json($workOrder, 201);
        }

        // Send notification to owner
        $workOrder->load('customer');
        NotificationService::notifyOwner(
            tenantId: $request->user()->tenant_id,
            type: 'work_order.created',
            title: 'أمر عمل جديد #' . ($workOrder->code ?? $workOrder->id),
            body: 'تم إنشاء أمر عمل جديد' . ($workOrder->customer ? ' للعميل ' . $workOrder->customer->name : ''),
            actionUrl: '/app/work-orders/' . $workOrder->id,
            actorId: $request->user()->id,
        );

        // Redirect to the newly created work order's show page
        return redirect()->route('work-orders.show', $workOrder)->with('success', __('messages.work_order_created'));
    }

    public function show(WorkOrder $workOrder): Response
    {
        $this->authorize('view', $workOrder);

        $workOrder->load([
            'customer',
            'vehicle.make',
            // Note: 'vehicle.customer' is intentionally not loaded — the
            // top-level `customer` relation already exposes the same record
            // and the Vue page does not read `workOrder.vehicle.customer`.
            'items.service.department',
            'items.technicians.employee.jobTitle',
            'items.parts.part',
            'items.parts.warehouse',
            'items.itemNotes.user.roles',
            'generalNotes.user.roles',
            'generalNotes.workOrderItem.service.department',
            'damageMarks',
            'photos',
            'departments',
            'payments' => fn($q) => $q->with('receivedBy')->orderByDesc('payment_date'),
            'parts.part' => fn($q) => $q->with('inventoryBalances')->withSum('inventoryBalances', 'qty_on_hand'),
            'parts.workOrderItem.service',
            'attachments.user',
            'activities.user',
            'inspections.performedBy',
            'invoice',
        ]);

        // TODO(refactor): the Vue page uses `v-if="activeTab === 'X'"` to
        // lazy-render tab content, but the controller still eager-loads
        // every tab's data (photos, attachments.user, activities.user,
        // inspections.performedBy, parts.part.inventoryBalances...) on
        // every page load. Move tab-specific relations to a partial-reload
        // endpoint and have the page call `router.reload({ only: [...] })`
        // when the active tab changes.

        // Group items by department_id for accordion display
        $itemsByDepartment = $workOrder->items->groupBy(function ($item) {
            if ($item->service?->type === \App\Models\Service::TYPE_PACKAGE) {
                return 'packages';
            }
            return $item->department_id ?? $item->service?->department_id ?? 0;
        });

        $customers = \App\Models\Customer::select('id', 'name', 'phone')->get();
        $makes = \App\Models\VehicleMake::ordered()->get();
        $colors = \App\Models\VehicleColor::active()->ordered()->get();
        $departments = \App\Models\Department::active()->ordered()->get();
        $services = \App\Models\Service::active()->ordered()->get();
        
        // Get technicians (employees with linked users belonging to the work order's center)
        $technicians = \App\Models\HR\Employee::where('center_id', $workOrder->center_id)
            ->whereNotNull('user_id')
            ->active()
            ->with(['jobTitle', 'user'])
            ->get()
            ->map(function ($employee) {
                return [
                    'id' => $employee->user_id, // Use user_id as the compatible ID for assignment
                    'name' => $employee->display_name,
                    'name_ar' => $employee->name_ar,
                    'name_en' => $employee->name_en,
                    'job_title_ar' => $employee->jobTitle?->name_ar,
                    'job_title_en' => $employee->jobTitle?->name_en,
                    'photo_url' => $employee->user?->photo_url,
                ];
            });
        
        $modelsByMake = \App\Models\VehicleModel::query()
            ->select('id', 'make_id', 'name_ar', 'name_en')
            ->get()
            ->groupBy('make_id');

        // Get warehouses for addPartModal
        $warehouses = \App\Models\Warehouse::where('center_id', $workOrder->center_id)
            ->orWhereNull('center_id') // Global warehouses if any
            ->select('id', 'name')
            ->get();

        // Get parts for addPartModal
        $inventoryParts = \App\Models\Part::where('tenant_id', $workOrder->tenant_id)
            ->where('is_active', true)
            ->select('id', 'sku', 'name_ar', 'name_en')
            ->get();

        // Get units for addPartModal
        $inventoryUnits = \App\Models\InventoryUnit::where('is_active', true)
            ->select('id', 'name_ar', 'name_en')
            ->get();

        $enableSystematicInspections = \App\Models\Setting::where('key', 'enable_systematic_inspections')->value('value') ?? 'true';

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

    public function update(WorkOrderUpdateRequest $request, WorkOrder $workOrder, UpdateWorkOrderAction $updateWorkOrder): \Illuminate\Http\RedirectResponse
    {
        $this->authorize('update', $workOrder);

        $updateWorkOrder->execute($workOrder, $request->user(), $request->validated());

        return redirect()->back()->with('success', __('messages.work_order_updated'));
    }

    public function destroy(WorkOrder $workOrder): \Illuminate\Http\RedirectResponse
    {
        $this->authorize('delete', $workOrder);

        // Delete photos from disk
        foreach ($workOrder->photos as $photo) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($photo->path);
        }

        $workOrder->delete();

        return redirect()->route('work-orders.index')->with('success', __('messages.work_order_deleted'));
    }

    /**
     * Update condition report (fuel level and damage marks).
     */
    public function updateCondition(Request $request, WorkOrder $workOrder)
    {
        $this->authorize('update', $workOrder);
        
        if (!$workOrder->canBeEdited()) {
            return back()->withErrors(['error' => __('messages.work_order_locked')]);
        }

        $validated = $request->validate([
            'fuel_level'               => 'nullable|numeric|min:0|max:100',
            'damage_marks'             => 'nullable|array',
            'damage_marks.*.x'         => 'required|numeric',
            'damage_marks.*.y'         => 'required|numeric',
            'damage_marks.*.color'     => 'required|string|in:red,blue,gray',
            'damage_marks.*.description' => 'nullable|string|max:500',
        ]);

        $hasChanges = false;
        
        if ($workOrder->fuel_level != ($validated['fuel_level'] ?? $workOrder->fuel_level)) {
            $hasChanges = true;
            $workOrder->update([
                'fuel_level' => $validated['fuel_level'],
            ]);
        }

        // Update damage marks through relationship
        if (isset($validated['damage_marks'])) {
            // Compare including description so description-only edits are saved
            $oldMarks = $workOrder->damageMarks->map(fn($m) => [
                'x' => round((float)$m->x, 2),
                'y' => round((float)$m->y, 2),
                'color' => $m->color,
                'description' => $m->description ?? '',
            ])->values()->toArray();

            $newMarks = collect($validated['damage_marks'])->map(fn($m) => [
                'x' => round((float)($m['x'] ?? 0), 2),
                'y' => round((float)($m['y'] ?? 0), 2),
                'color' => $m['color'] ?? 'red',
                'description' => $m['description'] ?? '',
            ])->values()->toArray();

            if ($oldMarks !== $newMarks) {
                $hasChanges = true;
                $workOrder->damageMarks()->delete();
                foreach ($validated['damage_marks'] as $mark) {
                    $workOrder->damageMarks()->create([
                        'x' => $mark['x'] ?? 0,
                        'y' => $mark['y'] ?? 0,
                        'color' => $mark['color'] ?? 'red',
                        'description' => $mark['description'] ?? '',
                    ]);
                }
            }
        }

        if ($hasChanges) {
            $workOrder->logActivity('condition_updated', __('work_orders.activities.actions.condition_updated'));
        }

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
            ]);
        }

        return back();
    }

    // ─────────────────────────────────────────────────────────────
    // Work Order Items (Services)
    // ─────────────────────────────────────────────────────────────

    public function addItem(Request $request, WorkOrder $work_order): \Illuminate\Http\RedirectResponse
    {
        $this->authorize('update', $work_order);

        // Pre-normalize virtual 'packages' department_id to null to prevent validation failure
        if ($request->input('department_id') === 'packages') {
            $request->merge(['department_id' => null]);
        }

        $validated = $request->validate([
            'service_id' => 'nullable|exists:services,id',
            'department_id' => 'nullable|exists:departments,id',
            'title' => 'required_without:service_id|nullable|string|max:255',
            'qty' => 'required|numeric|min:0.01',
            'unit_price' => 'required|numeric|min:0',
            'discount_type' => 'nullable|string|in:none,fixed,percentage',
            'discount_value' => 'nullable|numeric|min:0',
            'duration_value' => 'nullable|integer|min:0',
            'duration_unit' => 'nullable|string|max:50',
            'warranty_value_snapshot' => 'nullable|integer|min:0',
            'warranty_unit_snapshot' => 'nullable|string|max:50',
            'is_warranty' => 'nullable|boolean',
            'started_at' => 'nullable|date',
            'completed_at' => 'nullable|date',
            'due_date' => 'nullable|date',
            // Pending parts validation
            'pending_parts' => ['nullable', 'array'],
            'pending_parts.*.source' => ['required_with:pending_parts', 'in:warehouse,external,customer'],
            'pending_parts.*.name' => ['required_with:pending_parts', 'string', 'max:255'],
            'pending_parts.*.part_id' => ['nullable', 'exists:parts,id'],
            'pending_parts.*.warehouse_id' => ['nullable', 'exists:warehouses,id'],
            'pending_parts.*.qty' => ['required_with:pending_parts', 'numeric', 'min:0.01'],
            'pending_parts.*.unit_price' => ['required_with:pending_parts', 'numeric', 'min:0'],
            'pending_parts.*.discount' => ['nullable', 'numeric', 'min:0'],
            // Pending technicians validation
            'pending_technicians' => ['nullable', 'array'],
            'pending_technicians.*.user_id' => ['required_with:pending_technicians', 'exists:users,id'],
            'pending_technicians.*.share' => ['nullable', 'numeric', 'min:0', 'max:100'],
            // Pending notes validation
            'pending_notes' => ['nullable', 'array'],
            'pending_notes.*.content' => ['required_with:pending_notes', 'string'],
        ]);

        $service = $validated['service_id'] ? \App\Models\Service::find($validated['service_id']) : null;

        $line = $work_order->items()->create([
            'tenant_id' => $work_order->tenant_id,
            'center_id' => $work_order->center_id,
            'service_id' => $validated['service_id'],
            'department_id' => $validated['department_id'] ?? null,
            'title' => $validated['title'] ?? ($service ? $service->name_ar : 'أخرى'),
            'qty' => $validated['qty'],
            'unit_price' => $validated['unit_price'],
            'base_price_snapshot' => $service ? ($service->base_price ?? 0) : 0,
            'min_price_snapshot' => $service ? ($service->min_price ?? 0) : 0,
            'discount_type' => $validated['discount_type'] ?? 'none',
            'discount_value' => $validated['discount_value'] ?? 0,
            'duration_value' => $validated['duration_value'] ?? null,
            'duration_unit' => $validated['duration_unit'] ?? null,
            'warranty_value_snapshot' => $validated['warranty_value_snapshot'] ?? null,
            'warranty_unit_snapshot' => $validated['warranty_unit_snapshot'] ?? null,
            'is_warranty' => $validated['is_warranty'] ?? false,
            'started_at' => $validated['started_at'] ?? null,
            'completed_at' => $validated['completed_at'] ?? null,
            'due_date' => $validated['due_date'] ?? null,
        ]);

        // Auto-extend expected_end_date of the work order if the item's dates exceed it
        $maxItemDate = null;
        if ($line->due_date) {
            $maxItemDate = $line->due_date;
        } elseif ($line->started_at) {
            $maxItemDate = $line->started_at;
        }
        
        if ($maxItemDate) {
            $expectedEndDate = $work_order->expected_end_date ? \Carbon\Carbon::parse($work_order->expected_end_date) : null;
            if (!$expectedEndDate || \Carbon\Carbon::parse($maxItemDate)->gt($expectedEndDate)) {
                $work_order->update([
                    'expected_end_date' => $maxItemDate
                ]);
                $work_order->logActivity('expected_end_date_updated', 'تم تمديد تاريخ تسليم كرت العمل المتوقع إلى ' . \Carbon\Carbon::parse($maxItemDate)->format('Y-m-d') . ' تلقائياً لتجاوزه بمدة الخدمة');
            }
        }

        // Save pending parts linked to the new service item
        if (!empty($request->pending_parts)) {
            $partsService = app(\App\Services\Inventory\WorkOrderPartsService::class);
            $allowNegative = auth()->user()->can('inventory.override_negative_stock');
            foreach ($request->pending_parts as $partData) {
                $partsService->addPart([
                    'work_order_id' => $work_order->id,
                    'work_order_item_id' => $line->id,
                    'tenant_id' => $work_order->tenant_id,
                    'center_id' => $work_order->center_id,
                    'part_id' => $partData['part_id'] ?? null,
                    'warehouse_id' => $partData['warehouse_id'] ?? null,
                    'source' => $partData['source'],
                    'name' => $partData['name'],
                    'part_number' => $partData['part_number'] ?? null,
                    'unit_id' => $partData['unit_id'] ?? null,
                    'notes' => $partData['notes'] ?? null,
                    'qty' => $partData['qty'],
                    'unit_price' => $partData['unit_price'],
                    'discount' => $partData['discount'] ?? 0,
                    'include_in_package' => $partData['include_in_package'] ?? true,
                    'hide_on_print' => $partData['hide_on_print'] ?? false,
                ], $allowNegative);
            }
        }

        // Save pending technicians
        if (!empty($request->pending_technicians)) {
            foreach ($request->pending_technicians as $tech) {
                $line->technicians()->attach($tech['user_id'], [
                    'assigned_at' => now(),
                    'share' => $tech['share'] ?? 100.00
                ]);
            }
        }

        // Save pending notes
        if (!empty($request->pending_notes)) {
            foreach ($request->pending_notes as $note) {
                $line->itemNotes()->create([
                    'work_order_id' => $work_order->id,
                    'content' => $note['content'],
                    'user_id' => $request->user()->id,
                ]);
            }
        }

        $work_order->logActivity('item_added', __('work_orders.activities.actions.item_added', ['title' => $line->title]));

        return redirect()->back()->with('success', __('messages.service_added'));
    }

    public function updateItem(Request $request, WorkOrder $work_order, \App\Models\WorkOrderItem $item): \Illuminate\Http\RedirectResponse
    {
        $this->authorize('update', $work_order);

        $validated = $request->validate([
            'title' => 'nullable|string|max:255',
            'qty' => 'required|numeric|min:0.01',
            'unit_price' => 'required|numeric|min:0',
            'discount_type' => 'nullable|string|in:none,fixed,percentage',
            'discount_value' => 'nullable|numeric|min:0',
            'status' => 'nullable|in:' . implode(',', \App\Models\WorkOrderItem::STATUSES),
            'duration_value' => 'nullable|integer|min:0',
            'duration_unit' => 'nullable|string|max:50',
            'warranty_value_snapshot' => 'nullable|integer|min:0',
            'warranty_unit_snapshot' => 'nullable|string|max:50',
            'is_warranty' => 'nullable|boolean',
            'started_at' => 'nullable|date',
            'completed_at' => 'nullable|date',
            'due_date' => 'nullable|date',
            'technicians' => 'nullable|array',
            'technicians.*.user_id' => 'required_with:technicians|exists:users,id',
            'technicians.*.share' => 'nullable|numeric|min:0|max:100',
        ]);

        $item->update(collect($validated)->except(['technicians'])->toArray());

        if ($request->has('technicians')) {
            $syncData = [];
            foreach ($request->input('technicians', []) as $tech) {
                $syncData[$tech['user_id']] = [
                    'assigned_at' => now(),
                    'share' => $tech['share'] ?? 100.00
                ];
            }
            $item->technicians()->sync($syncData);
        }

        // Auto-extend expected_end_date of the work order if the item's dates exceed it
        $maxItemDate = null;
        if ($item->due_date) {
            $maxItemDate = $item->due_date;
        } elseif ($item->started_at) {
            $maxItemDate = $item->started_at;
        }
        
        if ($maxItemDate) {
            $expectedEndDate = $work_order->expected_end_date ? \Carbon\Carbon::parse($work_order->expected_end_date) : null;
            if (!$expectedEndDate || \Carbon\Carbon::parse($maxItemDate)->gt($expectedEndDate)) {
                $work_order->update([
                    'expected_end_date' => $maxItemDate
                ]);
                $work_order->logActivity('expected_end_date_updated', 'تم تمديد تاريخ تسليم كرت العمل المتوقع إلى ' . \Carbon\Carbon::parse($maxItemDate)->format('Y-m-d') . ' تلقائياً لتجاوزه بمدة الخدمة');
            }
        }

        $work_order->logActivity('item_updated', __('work_orders.activities.actions.item_updated', ['title' => $item->title]));

        return redirect()->back()->with('success', __('messages.service_updated'));
    }

    public function deleteItem(WorkOrder $work_order, \App\Models\WorkOrderItem $item): \Illuminate\Http\RedirectResponse
    {
        $this->authorize('update', $work_order);

        // Rule: Cannot delete/cancel item if it has active parts or technicians.
        // Active parts exclude cancelled or reversed parts.
        $hasActiveParts = $item->parts()
            ->whereNotIn('status', [
                \App\Models\WorkOrderItemPart::STATUS_CANCELLED,
                \App\Models\WorkOrderItemPart::STATUS_REVERSED
            ])
            ->exists();

        if ($item->technicians()->exists() || $hasActiveParts) {
            return redirect()->back()->with('error', __('messages.cannot_delete_item_has_parts_or_technicians'));
        }

        // Update status to cancelled instead of physical deletion
        $item->update(['status' => \App\Models\WorkOrderItem::STATUS_CANCELLED]);

        $work_order->logActivity(
            'item_status_updated',
            __('work_orders.activities.actions.item_status_updated', [
                'title' => $item->title,
                'status' => __('work_orders.item_status.cancelled')
            ])
        );

        return redirect()->back()->with('success', __('messages.item_status_updated'));
    }

    // ─────────────────────────────────────────────────────────────
    // Work Order Departments
    // ─────────────────────────────────────────────────────────────

    public function addDepartment(Request $request, WorkOrder $work_order): \Illuminate\Http\RedirectResponse
    {
        $this->authorize('update', $work_order);

        $validated = $request->validate([
            'department_id' => 'required',
        ]);

        if ($validated['department_id'] === 'packages') {
            $work_order->update(['show_packages_section' => true]);
            return redirect()->back()->with('success', __('messages.department_added'));
        }

        $request->validate([
            'department_id' => 'exists:departments,id',
        ]);

        // Check if already attached to avoid unique constraint violation
        $alreadyAttached = $work_order->departments()
            ->where('department_id', $validated['department_id'])
            ->exists();

        if (!$alreadyAttached) {
            $work_order->departments()->attach($validated['department_id']);
        }

        return redirect()->back()->with('success', __('messages.department_added'));
    }

    public function removeDepartment(WorkOrder $work_order, string $department_id): \Illuminate\Http\RedirectResponse
    {
        $this->authorize('update', $work_order);

        if ($department_id === 'packages') {
            $hasPackages = $work_order->items()
                ->whereHas('service', fn($q) => $q->where('type', \App\Models\Service::TYPE_PACKAGE))
                ->exists();

            if ($hasPackages) {
                return redirect()->back()->with('error', __('messages.cannot_remove_department_has_items'));
            }

            $work_order->update(['show_packages_section' => false]);

            return redirect()->back()->with('success', __('messages.department_removed'));
        }

        if (!is_numeric($department_id)) {
            abort(404);
        }
        $departmentId = (int) $department_id;

        // Rule R11: Can only remove department if no items belong to it (even cancelled ones)
        $hasItems = $work_order->items()
            ->where('department_id', $departmentId)
            ->exists();

        if ($hasItems) {
            return redirect()->back()->with('error', __('messages.cannot_remove_department_has_items'));
        }

        $work_order->departments()->detach($departmentId);

        return redirect()->back()->with('success', __('messages.department_removed'));
    }

    // ─────────────────────────────────────────────────────────────
    // Work Order Status Management
    // ─────────────────────────────────────────────────────────────

    /**
     * Start work on a work order.
     * Transition pending services to in_progress.
     */
    public function startWork(WorkOrder $work_order): \Illuminate\Http\RedirectResponse
    {
        $this->authorize('update', $work_order);

        if ($work_order->status !== WorkOrder::STATUS_OPEN && $work_order->status !== WorkOrder::STATUS_IN_PROGRESS) {
            return redirect()->back()->with('error', __('messages.cannot_start_work') ?? 'لا يمكن بدء العمل على كرت بهذه الحالة');
        }

        if ($work_order->items()->count() === 0) {
            return redirect()->back()->with('error', __('messages.cannot_start_work_no_services'));
        }

        \Illuminate\Support\Facades\DB::transaction(function () use ($work_order) {
            $work_order->update(['status' => WorkOrder::STATUS_IN_PROGRESS]);

            // Transition all pending services to in_progress using Eloquent to trigger events
            foreach ($work_order->items()->where('status', \App\Models\WorkOrderItem::STATUS_PENDING)->get() as $item) {
                $item->update(['status' => \App\Models\WorkOrderItem::STATUS_IN_PROGRESS]);
            }
        });

        $work_order->logActivity('status_changed', __('work_orders.activities.actions.status_changed', ['status' => __('work_orders.status.in_progress')]));

        return redirect()->back()->with('success', __('messages.work_order_started') ?? 'تم بدء العمل على كرت الصيانة بنجاح وتحويل الخدمات إلى جاري');
    }

    /**
     * Put work order on hold.
     * Rule R7: Suspends all items.
     */
    public function putOnHold(Request $request, WorkOrder $work_order): \Illuminate\Http\RedirectResponse
    {
        $this->authorize('update', $work_order);

        $validated = $request->validate([
            'reason' => 'required|string|max:255',
        ]);

        if (!$work_order->canBeOnHold()) {
            return redirect()->back()->with('error', __('messages.cannot_put_on_hold'));
        }

        $work_order->putOnHold($validated['reason']);

        $work_order->logActivity('status_changed', __('work_orders.activities.actions.status_changed_on_hold', ['reason' => $validated['reason']]) ?? ('تم تعليق كرت العمل بسبب: ' . $validated['reason']));

        return redirect()->back()->with('success', __('messages.work_order_on_hold'));
    }

    /**
     * Resume work order from hold.
     */
    public function resume(WorkOrder $work_order): \Illuminate\Http\RedirectResponse
    {
        $this->authorize('resume', $work_order);

        if (!$work_order->resume()) {
            return redirect()->back()->with('error', __('messages.cannot_resume'));
        }

        $work_order->logActivity('status_changed', __('work_orders.activities.actions.status_changed', ['status' => __('work_orders.status.in_progress')]));

        return redirect()->back()->with('success', __('messages.work_order_resumed'));
    }

    /**
     * Cancel work order.
     * Rules R5, R6: Cannot cancel if items have technicians or parts.
     */
    public function cancel(WorkOrder $work_order): \Illuminate\Http\RedirectResponse
    {
        $this->authorize('cancel', $work_order);

        if (!$work_order->canBeCancelled()) {
            return redirect()->back()->with('error', __('messages.cannot_cancel_has_technicians_or_parts'));
        }

        $work_order->update(['status' => WorkOrder::STATUS_CANCELLED]);

        $work_order->logActivity('status_changed', __('work_orders.activities.actions.status_changed', ['status' => __('work_orders.status.cancelled')]));

        return redirect()->back()->with('success', __('messages.work_order_cancelled'));
    }

    /**
     * Mark as completed (vehicle exit).
     * Rule R8: Only when all items completed and all dues are paid.
     */
    public function complete(Request $request, WorkOrder $work_order): \Illuminate\Http\RedirectResponse
    {
        $this->authorize('update', $work_order);

        $validated = $request->validate([
            'exit_date' => 'required|date',
            'notes' => 'nullable|string|max:1000',
            'is_deferred' => 'nullable|boolean',
            'due_date' => 'required_if:is_deferred,true|nullable|date|after_or_equal:exit_date',
        ]);

        if (!$work_order->allItemsCompleted()) {
            return redirect()->route('work-orders.show', $work_order->id)->with('error', __('messages.cannot_complete_items_pending') ?? 'لا يمكن إكمال العمل لوجود خدمات قيد الانتظار أو قيد التنفيذ');
        }

        if ($work_order->balance < -0.01) {
            return redirect()->route('work-orders.show', $work_order->id)->with('error', __('messages.cannot_complete_excess_payments') ?? 'لا يمكن تسجيل خروج المركبة لوجود مبالغ زائدة مدفوعة لم تسترجع! يرجى رد المبلغ الزائد أولاً.');
        }

        $exitDate = \Carbon\Carbon::parse($validated['exit_date']);
        $notes = $work_order->notes;
        if (!empty($validated['notes'])) {
            $notes = trim(($notes ? $notes . "\n" : '') . "ملاحظات الخروج: " . $validated['notes']);
        }

        if (!$work_order->markAsCompleted($exitDate, $notes)) {
            return redirect()->route('work-orders.show', $work_order->id)->with('error', __('messages.cannot_complete_error') ?? 'حدث خطأ أثناء محاولة إكمال كرت العمل');
        }

        $work_order->logActivity('status_changed', __('work_orders.activities.actions.status_changed', ['status' => __('work_orders.status.done')]));

        // Refresh from DB to get up-to-date totals, payments, and invoice state
        // (markAsCompleted triggers saving() which may reset cached attributes).
        $work_order = $work_order->fresh(['payments', 'invoice', 'items', 'parts']);

        // Generate and issue invoice if not already exists AND (fully paid OR deferred invoice is requested)
        if (!$work_order->invoice && ($work_order->balance <= 0 || $request->boolean('is_deferred'))) {
            try {
                $invoiceService = app(\App\Services\InvoiceService::class);
                $invoice = $invoiceService->createFromWorkOrder($work_order, auth()->user());

                if ($request->boolean('is_deferred') && $request->date('due_date')) {
                    $invoice->due_date = $request->date('due_date');
                    $invoice->save();
                }

                $invoiceService->issueInvoice($invoice);

                // Notify owner about new invoice
                \App\Services\NotificationService::notifyOwner(
                    tenantId: auth()->user()->tenant_id,
                    type: 'invoice.created',
                    title: 'فاتورة جديدة #' . $invoice->invoice_number,
                    body: 'تم إنشاء فاتورة من أمر العمل #' . ($work_order->code ?? $work_order->id),
                    actionUrl: '/app/invoices/' . $invoice->id,
                    actorId: auth()->id(),
                );

                return redirect()->route('app.invoices.show', $invoice->id)
                    ->with('success', __('messages.work_order_completed_and_invoice_issued') ?? 'تم خروج المركبة بنجاح وإصدار الفاتورة');
            } catch (\Exception $e) {
                return redirect()->back()->with('error', $e->getMessage());
            }
        }

        // No invoice created: stay on the work order page (with done status)
        return redirect()->route('work-orders.show', $work_order->id)->with('success', __('messages.work_order_completed'));
    }

    // ─────────────────────────────────────────────────────────────
    // Work Order Item Status Management
    // ─────────────────────────────────────────────────────────────

    /**
     * Update item status.
     * Rules R1, R2: Cannot cancel if has technicians or parts.
     */
    public function updateItemStatus(Request $request, WorkOrder $work_order, \App\Models\WorkOrderItem $item): \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
    {
        $this->authorize('update', $work_order);

        $validated = $request->validate([
            'status' => 'required|in:' . implode(',', \App\Models\WorkOrderItem::STATUSES),
        ]);

        $newStatus = $validated['status'];

        // Check business rules
        if (!$item->canChangeStatusTo($newStatus)) {
            $message = __('messages.cannot_change_item_status');
            return $request->expectsJson()
                ? response()->json(['error' => $message], 422)
                : redirect()->back()->with('error', $message);
        }

        $item->update(['status' => $newStatus]);

        $work_order->logActivity(
            'item_status_updated',
            __('work_orders.activities.actions.item_status_updated', [
                'title' => $item->title,
                'status' => __('work_orders.item_status.' . $newStatus)
            ])
        );

        $message = __('messages.item_status_updated');
        return $request->expectsJson()
            ? response()->json(['success' => $message, 'item' => $item->fresh()])
            : redirect()->back()->with('success', $message);
    }

    // ─────────────────────────────────────────────────────────────
    // Work Order Item Technicians
    // ─────────────────────────────────────────────────────────────

    /**
     * Assign technician to item.
     */
    public function assignTechnician(Request $request, WorkOrder $work_order, \App\Models\WorkOrderItem $item): \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
    {
        $this->authorize('update', $work_order);

        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'notes' => 'nullable|string|max:500',
        ]);

        // Security Check: Technician must belong to the Work Order's center
        $techExistsInCenter = \App\Models\User::where('id', $validated['user_id'])
            ->whereHas('centers', function ($q) use ($work_order) {
                $q->where('centers.id', $work_order->center_id);
            })
            ->exists();

        if (!$techExistsInCenter) {
            $message = __('messages.technician_not_belong_to_center');
            return $request->expectsJson()
                ? response()->json(['error' => $message], 403)
                : redirect()->back()->with('error', $message);
        }

        // Attach technician (ignore if already assigned)
        $item->technicians()->syncWithoutDetaching([
            $validated['user_id'] => [
                'assigned_at' => now(),
                'notes'       => $validated['notes'] ?? null,
                'share'       => 0, // temporary; rebalanced below
            ]
        ]);

        // Rebalance shares equally
        $this->rebalanceTechnicianShares($item);

        $work_order->logActivity('technician_assigned', __('work_orders.activities.actions.technician_assigned', [
            'name'    => \App\Models\User::find($validated['user_id'])->name,
            'service' => $item->title
        ]));

        $message = __('messages.technician_assigned');
        return $request->expectsJson()
            ? response()->json(['success' => $message, 'technicians' => $item->technicians()->withPivot(['assigned_at', 'completed_at', 'notes', 'share'])->get()])
            : redirect()->back()->with('success', $message);
    }

    /**
     * Remove technician from item.
     */
    public function removeTechnician(WorkOrder $work_order, \App\Models\WorkOrderItem $item, \App\Models\User $user): \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
    {
        $this->authorize('update', $work_order);

        $name = $user->name;
        $item->technicians()->detach($user->id);

        // Rebalance shares equally after removal
        $this->rebalanceTechnicianShares($item);

        $work_order->logActivity('technician_removed', __('work_orders.activities.actions.technician_removed', [
            'name'    => $name,
            'service' => $item->title
        ]));

        $message = __('messages.technician_removed');
        return request()->expectsJson()
            ? response()->json(['success' => $message])
            : redirect()->back()->with('success', $message);
    }

    // ─────────────────────────────────────────────────────────────
    // Helper: Rebalance technician shares equally to sum to 100%
    // ─────────────────────────────────────────────────────────────

    /**
     * Distribute shares equally among all technicians assigned to a work order item.
     * The last technician gets any rounding remainder so the total is always exactly 100%.
     */
    private function rebalanceTechnicianShares(\App\Models\WorkOrderItem $item): void
    {
        $techIds = $item->technicians()->pluck('users.id')->toArray();
        $count   = count($techIds);

        if ($count === 0) return;

        if ($count === 1) {
            $item->technicians()->updateExistingPivot($techIds[0], ['share' => 100.00]);
            return;
        }

        $base      = round(100 / $count, 2);
        $allocated = $base * ($count - 1);
        $last      = round(100 - $allocated, 2);

        foreach ($techIds as $index => $techId) {
            $share = ($index === $count - 1) ? $last : $base;
            $item->technicians()->updateExistingPivot($techId, ['share' => $share]);
        }
    }

    // ─────────────────────────────────────────────────────────────
    // Work Order Item Parts
    // ─────────────────────────────────────────────────────────────

    /**
     * Add part to item.
     */
    public function addPart(Request $request, WorkOrder $work_order, \App\Models\WorkOrderItem $item): \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
    {
        $this->authorize('update', $work_order);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'part_id' => 'nullable|exists:parts,id',
            'warehouse_id' => 'nullable|exists:warehouses,id',
            'part_number' => 'nullable|string|max:100',
            'source' => 'required|in:' . implode(',', \App\Models\WorkOrderItemPart::SOURCES),
            'unit_id' => 'nullable|exists:inventory_units,id',
            'qty' => 'required|numeric|min:0.01',
            'unit_price' => 'required|numeric|min:0',
            'discount' => 'nullable|numeric|min:0',
            'include_in_package' => 'boolean',
            'hide_on_print' => 'boolean',
            'notes' => 'nullable|string|max:500',
        ]);

        $partsService = app(\App\Services\Inventory\WorkOrderPartsService::class);
        $allowNegative = auth()->user()->can('inventory.override_negative_stock');
        $part = $partsService->addPart([
            'work_order_id' => $work_order->id,
            'work_order_item_id' => $item->id,
            'tenant_id' => $work_order->tenant_id,
            'center_id' => $work_order->center_id,
            ...$validated,
        ], $allowNegative);

        $work_order->logActivity('part_added', __('work_orders.activities.actions.part_added', ['name' => $validated['name']]));

        $message = __('messages.part_added');
        return $request->expectsJson()
            ? response()->json(['success' => $message, 'part' => $part])
            : redirect()->back()->with('success', $message);
    }

    /**
     * Update part.
     */
    public function updatePart(Request $request, WorkOrder $work_order, \App\Models\WorkOrderItem $item, \App\Models\WorkOrderItemPart $part): \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
    {
        $this->authorize('update', $work_order);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'part_id' => 'nullable|exists:parts,id',
            'part_number' => 'nullable|string|max:100',
            'source' => 'required|in:' . implode(',', \App\Models\WorkOrderItemPart::SOURCES),
            'unit_id' => 'nullable|exists:inventory_units,id',
            'qty' => 'required|numeric|min:0.01',
            'unit_price' => 'required|numeric|min:0',
            'discount' => 'nullable|numeric|min:0',
            'include_in_package' => 'boolean',
            'hide_on_print' => 'boolean',
            'notes' => 'nullable|string|max:500',
        ]);

        $part->update($validated);

        $work_order->logActivity('part_updated', __('work_orders.activities.actions.part_updated', ['name' => $validated['name']]));

        $message = __('messages.part_updated');
        return $request->expectsJson()
            ? response()->json(['success' => $message, 'part' => $part->fresh()])
            : redirect()->back()->with('success', $message);
    }

    /**
     * Delete part.
     */
    public function deletePart(WorkOrder $work_order, \App\Models\WorkOrderItem $item, \App\Models\WorkOrderItemPart $part): \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
    {
        $this->authorize('update', $work_order);

        $name = $part->name;
        $part->delete();

        $work_order->logActivity('part_deleted', __('work_orders.activities.actions.part_deleted', ['name' => $name]));

        $message = __('messages.part_deleted');
        return request()->expectsJson()
            ? response()->json(['success' => $message])
            : redirect()->back()->with('success', $message);
    }

    // ─────────────────────────────────────────────────────────────
    // Work Order Item Notes
    // ─────────────────────────────────────────────────────────────

    /**
     * Add note to item.
     */
    public function addNote(Request $request, WorkOrder $work_order, \App\Models\WorkOrderItem $item): \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
    {
        $this->authorize('update', $work_order);

        $validated = $request->validate([
            'content' => 'required|string|max:2000',
        ]);

        $note = $item->itemNotes()->create([
            'user_id' => $request->user()->id,
            'content' => $validated['content'],
            'work_order_id' => $work_order->id,
        ]);

        $note->load('user');

        $message = __('messages.note_added');
        
        $notes = $item->itemNotes()->with('user.roles')->latest()->get();
        
        return $request->wantsJson() && !$request->hasHeader('X-Inertia')
            ? response()->json(['success' => $message, 'notes' => $notes])
            : redirect()->back()->with('success', $message);
    }

    /**
     * Delete note.
     */
    public function deleteNote(WorkOrder $work_order, \App\Models\WorkOrderItem $item, \App\Models\WorkOrderItemNote $note): \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
    {
        $this->authorize('update', $work_order);

        $note->delete();

        $message = __('messages.note_deleted');
        
        $notes = $item->itemNotes()->with('user.roles')->latest()->get();
        
        return request()->wantsJson() && !request()->hasHeader('X-Inertia')
            ? response()->json(['success' => $message, 'notes' => $notes])
            : redirect()->back()->with('success', $message);
    }

    /**
     * Add general note to work order.
     */
    public function addGeneralNote(Request $request, WorkOrder $work_order): \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
    {
        $this->authorize('update', $work_order);

        $validated = $request->validate([
            'content' => 'required|string|max:2000',
        ]);

        $note = $work_order->generalNotes()->create([
            'user_id' => $request->user()->id,
            'content' => $validated['content'],
            'work_order_item_id' => null,
        ]);

        $note->load('user');

        $message = __('messages.note_added');
        return $request->wantsJson() && !$request->hasHeader('X-Inertia')
            ? response()->json(['success' => $message, 'note' => $note])
            : redirect()->back()->with('success', $message);
    }

    /**
     * Delete general note.
     */
    public function deleteGeneralNote(WorkOrder $work_order, \App\Models\WorkOrderItemNote $note): \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
    {
        $this->authorize('update', $work_order);

        $note->delete();

        $message = __('messages.note_deleted');
        return request()->wantsJson() && !request()->hasHeader('X-Inertia')
            ? response()->json(['success' => $message])
            : redirect()->back()->with('success', $message);
    }

    // ─────────────────────────────────────────────────────────────
    // Print Views
    // ─────────────────────────────────────────────────────────────

    /**
     * Print vehicle condition report.
     */
    public function printCondition(WorkOrder $workOrder): Response
    {
        $this->authorize('view', $workOrder);

        $workOrder->load([
            'customer',
            'vehicle.make',
            'vehicle.model',
            'damageMarks',
            'photos',
            'center',
            'tenant',
        ]);

        if ($workOrder->center) {
            $workOrder->center->append(['logo_light_url', 'logo_dark_url', 'logo_invoice_url', 'stamp_url']);
        }

        return Inertia::render('WorkOrders/Print/Condition', [
            'workOrder' => $workOrder,
        ]);
    }

    /**
     * Print work order services list.
     */
    public function printServices(WorkOrder $workOrder): Response
    {
        $this->authorize('view', $workOrder);

        $workOrder->load([
            'customer',
            'vehicle.make',
            'vehicle.model',
            'items.service.department',
            'items.technicians.employee.jobTitle',
            'center',
            'tenant',
        ]);

        // Filter out cancelled items
        $filteredItems = $workOrder->items->filter(fn($item) => $item->status !== \App\Models\WorkOrderItem::STATUS_CANCELLED);
        $workOrder->setRelation('items', $filteredItems->values());

        $departmentId = request('department_id');
        $printedDepartment = null;

        if ($departmentId !== null) {
            // Filter work order items to only those belonging to this department
            $filteredItems = $workOrder->items->filter(function ($item) use ($departmentId) {
                $itemDeptId = $item->service?->type === \App\Models\Service::TYPE_PACKAGE 
                    ? 'packages' 
                    : ($item->department_id ?? $item->service?->department_id ?? 0);
                return (string)$itemDeptId === (string)$departmentId;
            });
            $workOrder->setRelation('items', $filteredItems->values());

            // Prepare department details for document title
            if ($departmentId === 'packages') {
                $printedDepartment = [
                    'name_ar' => 'باقات الخدمات',
                    'name_en' => 'Service Packages',
                ];
            } elseif ($departmentId === '0') {
                $printedDepartment = [
                    'name_ar' => 'خدمات غير مصنفة',
                    'name_en' => 'Uncategorized Services',
                ];
            } else {
                $dept = \App\Models\Department::find($departmentId);
                if ($dept) {
                    $printedDepartment = [
                        'name_ar' => $dept->name_ar,
                        'name_en' => $dept->name_en,
                    ];
                }
            }
        }

        $technicianId = request('technician_id');
        $printedTechnician = null;

        if ($technicianId !== null) {
            // Filter work order items to only those assigned to this technician
            $filteredItems = $workOrder->items->filter(function ($item) use ($technicianId) {
                return $item->technicians->contains('id', $technicianId);
            });
            $workOrder->setRelation('items', $filteredItems->values());

            // Prepare technician details for document title
            $user = \App\Models\User::with('employee')->find($technicianId);
            if ($user) {
                $employeeNameAr = $user->employee?->name_ar;
                $employeeNameEn = $user->employee?->name_en;
                $printedTechnician = [
                    'name_ar' => $employeeNameAr ?: $user->name,
                    'name_en' => $employeeNameEn ?: $user->name,
                ];
            }
        }

        // Group items by department
        $itemsByDepartment = $workOrder->items->groupBy(function ($item) {
            if ($item->service?->type === \App\Models\Service::TYPE_PACKAGE) {
                return 'packages';
            }
            return $item->department_id ?? $item->service?->department_id ?? 0;
        });

        $departments = \App\Models\Department::active()->ordered()->get()->keyBy('id');

        return Inertia::render('WorkOrders/Print/Services', [
            'workOrder' => $workOrder,
            'itemsByDepartment' => $itemsByDepartment,
            'departments' => $departments,
            'printedDepartment' => $printedDepartment,
            'printedTechnician' => $printedTechnician,
        ]);
    }

    /**
     * Print proforma invoice.
     */
    public function printProforma(WorkOrder $workOrder): Response
    {
        $this->authorize('view', $workOrder);

        $workOrder->load([
            'customer',
            'vehicle.make',
            'vehicle.model',
            'items.service.department',
            'items.parts.part',
            'items.parts.warehouse',
            'center',
            'tenant',
        ]);

        // Filter out cancelled items
        $filteredItems = $workOrder->items->filter(fn($item) => $item->status !== \App\Models\WorkOrderItem::STATUS_CANCELLED);
        $workOrder->setRelation('items', $filteredItems->values());

        // Calculate totals
        $servicesTotal = $workOrder->items->sum(function ($item) {
            return $item->line_total ?? ($item->qty * $item->unit_price);
        });

        $partsTotal = $workOrder->items->sum(function ($item) {
            return $item->parts->sum(function ($part) {
                return $part->qty * $part->unit_price;
            });
        });

        $totalPaid = $workOrder->total_paid ?? 0;
        $grandTotal = $servicesTotal + $partsTotal;
        $balance = $grandTotal - $totalPaid;

        // Group items by department
        $itemsByDepartment = $workOrder->items->groupBy(function ($item) {
            if ($item->service?->type === \App\Models\Service::TYPE_PACKAGE) {
                return 'packages';
            }
            return $item->department_id ?? $item->service?->department_id ?? 0;
        });

        // Collect all parts
        $allParts = $workOrder->items->flatMap(function ($item) {
            return $item->parts;
        });

        $departments = \App\Models\Department::active()->ordered()->get()->keyBy('id');

        // Get tax settings
        $taxSettings = \App\Models\TenantTaxSetting::where('tenant_id', $workOrder->tenant_id)->first();

        return Inertia::render('WorkOrders/Print/Proforma', [
            'workOrder' => $workOrder,
            'itemsByDepartment' => $itemsByDepartment,
            'allParts' => $allParts,
            'departments' => $departments,
            'servicesTotal' => $servicesTotal,
            'partsTotal' => $partsTotal,
            'grandTotal' => $grandTotal,
            'totalPaid' => $totalPaid,
            'balance' => $balance,
            'taxSettings' => $taxSettings,
        ]);
    }

    /**
     * Print payments receipt.
     */
    public function printPayments(WorkOrder $workOrder): Response
    {
        $this->authorize('view', $workOrder);

        $workOrder->load([
            'customer',
            'vehicle.make',
            'vehicle.model',
            'center',
            'tenant',
        ]);

        // Get payments related to this work order
        $payments = $workOrder->payments()->with('receivedBy')->orderByDesc('payment_date')->get();

        // Calculate totals
        $servicesTotal = $workOrder->items->sum(function ($item) {
            return $item->line_total ?? ($item->qty * $item->unit_price);
        });

        $partsTotal = $workOrder->items->sum(function ($item) {
            return $item->parts?->sum(function ($part) {
                return $part->qty * $part->unit_price;
            }) ?? 0;
        });

        $grandTotal = $servicesTotal + $partsTotal;
        $totalPaid = $payments->sum('amount');
        $balance = $grandTotal - $totalPaid;

        return Inertia::render('WorkOrders/Print/Payments', [
            'workOrder' => $workOrder,
            'payments' => $payments,
            'grandTotal' => $grandTotal,
            'totalPaid' => $totalPaid,
            'balance' => $balance,
        ]);
    }

    // ==================== Payment Methods ====================

    /**
     * Store a new payment for a work order.
     */
    public function storePayment(Request $request, WorkOrder $workOrder)
    {
        $this->authorize('update', $workOrder);

        $validated = $request->validate([
            'payment_method' => 'required|in:' . implode(',', \App\Models\Payment::METHODS),
            'type' => 'required|in:' . implode(',', \App\Models\Payment::TYPES),
            'amount' => 'required|numeric|min:0.01',
            'payment_date' => 'required|date',
            'reference' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:1000',
        ]);

        // If the WO already has an invoice, link this payment to it as well
        // so the running totals stay in sync on both sides. Without this,
        // paying from the WO screen leaves the invoice total_paid stale and
        // the next page load shows mismatched balances.
        $linkedInvoiceId = $workOrder->invoice?->id;

        $workOrder->payments()->create([
            'tenant_id'      => $workOrder->tenant_id,
            'center_id'      => $workOrder->center_id,
            'invoice_id'     => $linkedInvoiceId,
            'type'           => $validated['type'],
            'payment_method' => $validated['payment_method'],
            'amount'         => $validated['amount'],
            'payment_date'   => $validated['payment_date'],
            'reference'      => $validated['reference'] ?? null,
            'notes'          => $validated['notes'] ?? null,
            'received_by'    => auth()->id(),
        ]);

        // Recalculate the invoice payment status if the WO is invoiced.
        if ($linkedInvoiceId) {
            $workOrder->invoice->updatePaymentStatus();
        }

        $workOrder->logActivity('payment_added', __('work_orders.activities.actions.payment_added', ['amount' => $validated['amount']]));

        return back()->with('success', __('payments.saved_successfully'));
    }

    /**
     * Update an existing payment.
     */
    public function updatePayment(Request $request, WorkOrder $workOrder, \App\Models\Payment $payment)
    {
        $this->authorize('update', $workOrder);

        // Ensure payment belongs to this work order
        if ($payment->work_order_id !== $workOrder->id) {
            abort(403);
        }

        $validated = $request->validate([
            'payment_method' => 'required|in:' . implode(',', \App\Models\Payment::METHODS),
            'type' => 'required|in:' . implode(',', \App\Models\Payment::TYPES),
            'amount' => 'required|numeric|min:0.01',
            'payment_date' => 'required|date',
            'reference' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:1000',
        ]);

        $payment->update($validated);

        return back()->with('success', __('payments.updated_successfully'));
    }

    /**
     * Delete a payment.
     */
    public function destroyPayment(WorkOrder $workOrder, \App\Models\Payment $payment)
    {
        $this->authorize('update', $workOrder);

        // Ensure payment belongs to this work order
        if ($payment->work_order_id !== $workOrder->id) {
            abort(403);
        }

        $payment->delete();

        return back()->with('success', __('payments.deleted_successfully'));
    }

    /**
     * Delete a photo from work order.
     */
    public function deletePhoto(WorkOrder $workOrder, \App\Models\WorkOrderPhoto $photo)
    {
        $this->authorize('update', $workOrder);

        // Verify photo belongs to this work order
        if ($photo->work_order_id !== $workOrder->id) {
            abort(403);
        }

        // Delete file from storage
        \Illuminate\Support\Facades\Storage::disk('public')->delete($photo->path);
        
        // Delete record
        $photo->delete();

        return back()->with('success', __('messages.photo_deleted'));
    }

    /**
     * Upload photos to work order.
     */
    public function uploadPhotos(Request $request, WorkOrder $workOrder)
    {
        $this->authorize('update', $workOrder);

        $request->validate([
            'photos' => 'required|array',
            'photos.*.file' => 'required|image|mimes:jpeg,png,jpg,gif|max:5120', // 5MB
            'photos.*.type' => 'nullable|string|in:general,before,after,damage',
            'photos.*.caption' => 'nullable|string|max:255',
        ]);

        foreach ($request->file('photos') as $index => $photoFile) {
            $file = $photoFile['file'];
            $type = $request->input("photos.$index.type") ?? 'general';
            $caption = $request->input("photos.$index.caption");

            $path = $file->store('work-orders/' . $workOrder->id . '/photos', 'public');

            $workOrder->photos()->create([
                'path' => $path,
                'type' => $type,
                'caption' => $caption,
            ]);
        }

        $workOrder->logActivity('photos_uploaded', __('work_orders.activities.actions.photos_uploaded'));

        return back()->with('success', __('messages.photos_uploaded_successfully'));
    }

    /**
     * Upload attachments to work order.
     */
    public function uploadAttachments(Request $request, WorkOrder $workOrder)
    {
        $this->authorize('update', $workOrder);

        $request->validate([
            'attachments' => 'required|array',
            'attachments.*' => 'required|file|mimes:pdf,jpg,png|max:1024', // 1MB
        ]);

        foreach ($request->file('attachments') as $file) {
            $path = $file->store('work-orders/' . $workOrder->id . '/attachments', 'public');

            $workOrder->attachments()->create([
                'tenant_id' => $workOrder->tenant_id,
                'file_name' => $file->getClientOriginalName(),
                'path' => $path,
                'file_type' => $file->getClientOriginalExtension(),
                'file_size' => $file->getSize(),
                'user_id' => auth()->id(),
            ]);
        }

        $workOrder->logActivity('attachments_uploaded', __('work_orders.activities.actions.attachments_uploaded'));

        return back()->with('success', __('messages.attachments_uploaded_successfully'));
    }

    /**
     * Delete an attachment.
     */
    public function destroyAttachment(WorkOrder $workOrder, \App\Models\WorkOrderAttachment $attachment)
    {
        $this->authorize('update', $workOrder);

        if ($attachment->work_order_id !== $workOrder->id) {
            abort(403);
        }

        \Illuminate\Support\Facades\Storage::disk('public')->delete($attachment->path);
        $attachment->delete();

        return back()->with('success', __('messages.attachment_deleted'));
    }

    /**
     * Get active warranties for a specific vehicle.
     */
    public function activeWarranties($vehicle_id): \Illuminate\Http\JsonResponse
    {
        // Find all completed work order items for this vehicle where warranty is still active
        $activeWarranties = \App\Models\WorkOrderItem::whereHas('workOrder', function ($q) use ($vehicle_id) {
                $q->where('vehicle_id', $vehicle_id)
                  ->where('status', 'done'); // completed work orders
            })
            ->where('status', \App\Models\WorkOrderItem::STATUS_COMPLETED)
            ->whereNotNull('warranty_expires_at')
            ->where('warranty_expires_at', '>', now())
            ->get()
            ->map(function ($item) use ($vehicle_id) {
                // Find previous cards for this vehicle where this service was done under warranty
                $claims = \App\Models\WorkOrderItem::whereHas('workOrder', function ($q) use ($vehicle_id) {
                        $q->where('vehicle_id', $vehicle_id)
                          ->where('status', 'done');
                    })
                    ->where('is_warranty', true)
                    ->where(function ($query) use ($item) {
                        if ($item->service_id) {
                            $query->where('service_id', $item->service_id);
                        } else {
                            $query->where('title', $item->title);
                        }
                    })
                    ->get()
                    ->map(function ($claim) {
                        return [
                            'work_order_code' => $claim->workOrder?->code,
                            'work_order_id' => $claim->work_order_id,
                            'service_date' => $claim->workOrder?->created_at?->format('Y-m-d'),
                            'center_name_ar' => $claim->workOrder?->center?->name_ar,
                            'center_name_en' => $claim->workOrder?->center?->name_en,
                        ];
                    });

                return [
                    'id' => $item->id,
                    'service_id' => $item->service_id,
                    'service_name_ar' => $item->service?->name_ar ?? $item->title,
                    'service_name_en' => $item->service?->name_en ?? $item->title,
                    'title' => $item->title,
                    'warranty_expires_at' => $item->warranty_expires_at->toIso8601String(),
                    'warranty_value_snapshot' => $item->warranty_value_snapshot,
                    'warranty_unit_snapshot' => $item->warranty_unit_snapshot,
                    'work_order_code' => $item->workOrder?->code,
                    'work_order_id' => $item->work_order_id,
                    'unit_price' => $item->unit_price,
                    'final_unit_price' => $item->final_unit_price,
                    'service_date' => $item->workOrder?->created_at?->format('Y-m-d'),
                    'center_name_ar' => $item->workOrder?->center?->name_ar,
                    'center_name_en' => $item->workOrder?->center?->name_en,
                    'claims' => $claims,
                ];
            });

        return response()->json([
            'active_warranties' => $activeWarranties
        ]);
    }
}
