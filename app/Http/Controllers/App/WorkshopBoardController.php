<?php

declare(strict_types=1);

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\WorkOrder;
use App\Models\WorkOrderItem;
use App\Models\WorkOrderLaborEntry;
use App\Support\Permissions;
use App\Support\TenancyContext;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Inertia\Inertia;
use Inertia\Response;

class WorkshopBoardController extends Controller
{
    /**
     * Statuses shown on the workshop Kanban board.
     *
     * Order is significant — the columns are rendered in this order.
     */
    public const BOARD_STATUSES = [
        WorkOrderItem::STATUS_PENDING,
        WorkOrderItem::STATUS_IN_PROGRESS,
        WorkOrderItem::STATUS_ON_HOLD,
        WorkOrderItem::STATUS_READY_FOR_QC,
    ];

    /**
     * Max number of appointments the board shows in its
     * "today" section. We cap the payload to keep the
     * primary page render fast — the operator goes to
     * /app/appointments for the full list.
     */
    public const TODAY_APPOINTMENTS_LIMIT = 8;

    /**
     * Display the workshop Kanban board.
     *
     * Phase 1: read-only board grouping active service items by status.
     * No scheduling, bay allocation, technician timers, or new tables.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();

        // Authorization — same gate the work-orders module uses for viewing.
        abort_unless($user->can(Permissions::WORK_ORDERS_VIEW), 403);

        // Defense in depth: the work order list is always filtered by the
        // current tenant + center. We re-state the predicate explicitly so
        // a future change to the global scope can't accidentally widen the
        // board's visibility.
        $tenantId = TenancyContext::tenantId();
        $centerId = TenancyContext::centerId();

        abort_unless($tenantId !== null && $centerId !== null, 403);

        // Single eager-loaded query. The CenterScoped global scope on
        // WorkOrderItem already enforces tenant_id + center_id; we add
        // the parent work-order status filter here.
        //
        // Ordering: the board sorts in PHP after computing the unified
        // due date (item.due_date, falling back to the parent work
        // order's expected_end_date). Items with no date at all must
        // appear after dated ones, so we cannot do this purely in SQL
        // (SQLite/MySQL NULL ordering would surface the wrong end of
        // the list). Loading + sorting in PHP keeps the rule clear.
        $items = WorkOrderItem::query()
            ->with([
                'workOrder:id,tenant_id,center_id,code,status,expected_end_date,entry_date,hold_reason,customer_id,vehicle_id',
                'workOrder.customer:id,tenant_id,center_id,name',
                'workOrder.vehicle:id,tenant_id,center_id,plate_number,customer_id,make_id,model_id,year',
                'service:id,tenant_id,center_id,name,department_id',
                'department:id,tenant_id,center_id,name',
                'technicians:id,name',
                'laborEntries' => fn ($q) => $q->with('user:id,name'),
            ])
            ->whereHas('workOrder', function ($query) use ($tenantId, $centerId) {
                $query
                    ->where('tenant_id', $tenantId)
                    ->where('center_id', $centerId)
                    ->whereNotIn('status', [WorkOrder::STATUS_DONE, WorkOrder::STATUS_CANCELLED]);
            })
            ->whereIn('status', self::BOARD_STATUSES)
            ->get();

        // Group by status. The Blade-side loop never has to know the
        // underlying SQL order — we shape the data here so the component
        // is purely presentational.
        $today = now()->startOfDay();
        $columns = [];

        foreach (self::BOARD_STATUSES as $status) {
            $columns[$status] = [];
        }

        foreach ($items as $item) {
            $workOrder = $item->workOrder;
            $customer = $workOrder?->customer;
            $vehicle = $workOrder?->vehicle;

            // Unified due date: prefer the item's own due_date, fall
            // back to the work order's expected_end_date. This is the
            // single source of truth for both display and overdue.
            $dueDate = $item->due_date ?? $workOrder?->expected_end_date;

            // Overdue is strictly BEFORE today — the day-of is still
            // on time. We don't paint the badge on completed items.
            $isOverdue = $dueDate !== null
                && $dueDate->lt($today)
                && $item->status !== WorkOrderItem::STATUS_COMPLETED;

            // Labor summary for this card. We surface a brief
            // snapshot of who is currently working the item (if
            // anyone) plus the total effective time logged.
            $activeLabor = $item->laborEntries
                ->firstWhere('status', WorkOrderLaborEntry::STATUS_RUNNING);
            $pausedLabor = $item->laborEntries
                ->firstWhere('status', WorkOrderLaborEntry::STATUS_PAUSED);
            $totalSeconds = $item->laborEntries
                ->sum(fn ($e) => $e->effectiveDurationSeconds());

            $columns[$item->status][] = [
                'id' => $item->id,
                'work_order_id' => $workOrder?->id,
                'work_order_code' => $workOrder?->code,
                'title' => $item->title,
                'service_name' => $item->service?->name,
                'department_name' => $item->department?->name,
                'customer_name' => $customer?->name,
                'vehicle_plate' => $vehicle?->plate_number,
                'technicians' => $item->technicians
                    ->map(fn ($tech) => ['id' => $tech->id, 'name' => $tech->name])
                    ->all(),
                'started_at' => $item->started_at?->toIso8601String(),
                'due_date' => $dueDate?->toDateString(),
                'work_order_hold_reason' => $workOrder?->hold_reason,
                'is_overdue' => $isOverdue,
                'status' => $item->status,
                'labor' => [
                    'active_user_name' => $activeLabor?->user?->name
                        ?? $pausedLabor?->user?->name,
                    'active_status' => $activeLabor
                        ? 'running'
                        : ($pausedLabor ? 'paused' : null),
                    'total_seconds' => (int) $totalSeconds,
                ],
                // Sentinel used only by the in-PHP sort below; the
                // frontend ignores it.
                '_sort_due_at' => $dueDate?->timestamp,
            ];
        }

        // Sort each column: dated items ascending by due date, then
        // undated items (NULL sentinel) last. Items with the same due
        // date break the tie on started_at (oldest first) for stable
        // ordering.
        foreach ($columns as $status => $cards) {
            usort($cards, function (array $a, array $b) {
                $aTs = $a['_sort_due_at'];
                $bTs = $b['_sort_due_at'];

                if ($aTs === null && $bTs === null) {
                    return 0;
                }

                if ($aTs === null) {
                    return 1; // undated -> after
                }

                if ($bTs === null) {
                    return -1; // undated -> after
                }

                return $aTs <=> $bTs;
            });

            // Strip the sort helper before sending to Inertia.
            $columns[$status] = array_map(
                fn (array $card) => Arr::except($card, ['_sort_due_at']),
                $cards
            );
        }

        return Inertia::render('Workshop/Board', [
            'columns' => $columns,
            'statuses' => self::BOARD_STATUSES,
            'counts' => [
                'pending' => count($columns[WorkOrderItem::STATUS_PENDING]),
                'in_progress' => count($columns[WorkOrderItem::STATUS_IN_PROGRESS]),
                'on_hold' => count($columns[WorkOrderItem::STATUS_ON_HOLD]),
                'ready_for_qc' => count($columns[WorkOrderItem::STATUS_READY_FOR_QC]),
                'total' => $items->count(),
            ],
            'todayAppointments' => $this->loadTodayAppointments($tenantId, $centerId),
            'canManageAppointments' => $user->can(Permissions::APPOINTMENTS_MANAGE),
        ]);
    }

    /**
     * Today's appointments for the current center. The board
     * surfaces a brief list as the operator's "what's coming in
     * today" — separate from the Kanban columns, which are
     * about work that's already in motion.
     *
     * Returned statuses: scheduled, confirmed, checked_in. We
     * deliberately exclude cancelled / no_show / converted —
     * those have no useful "today" meaning.
     *
     * The cap of 8 (TODAY_APPOINTMENTS_LIMIT) keeps the payload
     * tiny. The Vue side always renders a "see all" link to
     * /app/appointments for the complete view.
     */
    private function loadTodayAppointments(int $tenantId, int $centerId): array
    {
        $start = now()->startOfDay();
        $end = now()->endOfDay();

        $appointments = Appointment::query()
            ->where('center_id', $centerId)
            ->where('tenant_id', $tenantId)
            ->whereBetween('scheduled_at', [$start, $end])
            ->whereIn('status', [
                Appointment::STATUS_SCHEDULED,
                Appointment::STATUS_CONFIRMED,
                Appointment::STATUS_CHECKED_IN,
            ])
            ->with([
                'customer:id,tenant_id,center_id,name,phone',
                'vehicle:id,tenant_id,center_id,plate_number,customer_id',
                'serviceBay:id,tenant_id,center_id,name',
                'assignedUser:id,name',
                'services:id,name_ar,name_en',
            ])
            ->orderBy('scheduled_at')
            ->limit(self::TODAY_APPOINTMENTS_LIMIT)
            ->get();

        return $appointments->map(fn (Appointment $a) => [
            'id' => $a->id,
            'status' => $a->status,
            'scheduled_at' => $a->scheduled_at?->toIso8601String(),
            'estimated_end_at' => $a->estimated_end_at?->toIso8601String(),
            'customer' => $a->customer ? [
                'id' => $a->customer->id,
                'name' => $a->customer->name,
            ] : null,
            'vehicle' => $a->vehicle ? [
                'id' => $a->vehicle->id,
                'plate_number' => $a->vehicle->plate_number,
            ] : null,
            'service_bay' => $a->serviceBay ? [
                'id' => $a->serviceBay->id,
                'name' => $a->serviceBay->name,
            ] : null,
            'assigned_user' => $a->assignedUser ? [
                'id' => $a->assignedUser->id,
                'name' => $a->assignedUser->name,
            ] : null,
            // We render only the first 2 service names on the
            // card and a "+N more" badge for the rest. Sending
            // the full list would make the payload noisy. We
            // route through the model's name accessor so the
            // localized name (name_ar / name_en) is used.
            'services_preview' => $a->services
                ->take(2)
                ->map(fn ($s) => ['id' => $s->id, 'name' => $s->name])
                ->all(),
            'services_extra_count' => max(0, $a->services->count() - 2),
        ])->all();
    }
}
