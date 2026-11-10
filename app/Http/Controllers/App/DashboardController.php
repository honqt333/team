<?php

declare(strict_types=1);

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Part;
use App\Models\Payment;
use App\Models\Vehicle;
use App\Models\WorkOrder;
use App\Models\WorkOrderItem;
use App\Support\Permissions;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $centerId = $request->attributes->get('center_id') ?? $user?->center_id;
        $tenantId = $user?->tenant_id;

        // ─── Authorization & Role Context (Strict Server-Side Privacy) ────
        $isSystemAdmin = (bool) ($user->is_system_admin ?? false);
        $isSuperAdmin = (bool) ($user?->hasRole('super_admin') ?? false);
        $isBranchManager = (bool) ($user?->hasRole('branch_manager') ?? false);
        $isAccountant = (bool) ($user?->hasRole('accountant') ?? false);
        $isTechnician = (bool) ($user?->hasRole('technician') && ! $isSuperAdmin && ! $isBranchManager);
        $isReceptionist = (bool) ($user?->hasRole('receptionist') && ! $isSuperAdmin && ! $isBranchManager);

        $canViewFinancials = $isSystemAdmin
            || $isSuperAdmin
            || $isBranchManager
            || $isAccountant
            || (bool) $user?->hasPermissionTo(Permissions::INVOICES_VIEW);

        $canViewInventory = $isSystemAdmin
            || $isSuperAdmin
            || $isBranchManager
            || $isAccountant
            || (bool) $user?->hasPermissionTo(Permissions::INVENTORY_VIEW);

        $canCreateWorkOrder = $isSystemAdmin
            || $isSuperAdmin
            || $isBranchManager
            || $isReceptionist
            || (bool) $user?->hasPermissionTo(Permissions::WORK_ORDERS_CREATE);

        // Date ranges
        $today = Carbon::today();
        $thisMonth = Carbon::now()->startOfMonth();
        $lastMonth = Carbon::now()->subMonth()->startOfMonth();
        $lastMonthEnd = Carbon::now()->subMonth()->endOfMonth();
        $last30Days = Carbon::now()->subDays(30);
        $last7Days = Carbon::now()->subDays(7);

        // ─── Base query builders (re-used everywhere) ─────────────────────
        $workOrdersBase = WorkOrder::where('tenant_id', $tenantId)
            ->when($centerId, fn ($q) => $q->where('center_id', $centerId));

        $invoiceBase = Invoice::where('tenant_id', $tenantId)
            ->when($centerId, fn ($q) => $q->where('center_id', $centerId))
            ->where('type', 'invoice')
            ->whereNotIn('status', ['draft', 'cancelled']);

        $customersBase = Customer::where('tenant_id', $tenantId)
            ->when($centerId, fn ($q) => $q->where('center_id', $centerId));

        // ─── Work Orders Stats ─────────────────────────────────────────────
        $workOrdersThisMonth = (clone $workOrdersBase)
            ->whereDate('created_at', '>=', $thisMonth)->count();

        $workOrdersLastMonth = (clone $workOrdersBase)
            ->whereDate('created_at', '>=', $lastMonth)
            ->whereDate('created_at', '<=', $lastMonthEnd)->count();

        $activeWorkOrders = (clone $workOrdersBase)
            ->whereIn('status', ['open', 'in_progress', 'on_hold', 'ready_for_qc'])->count();

        $completedToday = (clone $workOrdersBase)
            ->where('status', 'done')
            ->whereDate('closed_at', $today)->count();

        // Work Orders by Status (for donut chart)
        $workOrdersByStatus = (clone $workOrdersBase)
            ->select('status', DB::raw('count(*) as count'))
            ->whereDate('created_at', '>=', $thisMonth)
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        // Work Orders by Status (all-time) for Operations tab
        $workOrdersByStatusAll = (clone $workOrdersBase)
            ->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        // ─── Revenue Stats (Protected by $canViewFinancials) ───────────────
        $revenueThisMonth = null;
        $revenueLastMonth = null;
        $outstandingBalance = null;
        $avgInvoiceValue = null;
        $paymentsToday = null;
        $revenueChart = [];
        $monthlyRevenue = [];
        $recentInvoices = [];
        $topCustomers = [];
        $overdueInvoices = 0;

        if ($canViewFinancials) {
            $revenueThisMonth = (clone $invoiceBase)
                ->whereDate('issue_date', '>=', $thisMonth)
                ->sum('total_incl_tax');

            $revenueLastMonth = (clone $invoiceBase)
                ->whereDate('issue_date', '>=', $lastMonth)
                ->whereDate('issue_date', '<=', $lastMonthEnd)
                ->sum('total_incl_tax');

            $outstandingBalance = (clone $invoiceBase)
                ->whereIn('payment_status', ['unpaid', 'partial'])
                ->sum(DB::raw('total_incl_tax - COALESCE((SELECT SUM(amount) FROM payments WHERE payments.invoice_id = invoices.id), 0)'));

            $avgInvoiceValue = (clone $invoiceBase)
                ->whereDate('issue_date', '>=', $thisMonth)
                ->avg('total_incl_tax');

            $overdueInvoices = (clone $invoiceBase)
                ->whereIn('payment_status', ['unpaid', 'partial'])
                ->whereDate('issue_date', '<', $last30Days)
                ->count();

            $paymentsToday = Payment::where('tenant_id', $tenantId)
                ->when($centerId, fn ($q) => $q->where('center_id', $centerId))
                ->whereDate('payment_date', $today)
                ->sum('amount');

            // Revenue last 30 days (daily)
            $dailyRevenue = (clone $invoiceBase)
                ->whereDate('issue_date', '>=', $last30Days)
                ->select(
                    DB::raw('DATE(issue_date) as date'),
                    DB::raw('SUM(total_incl_tax) as total')
                )
                ->groupBy('date')
                ->orderBy('date')
                ->get()
                ->keyBy('date')
                ->toArray();

            for ($i = 29; $i >= 0; $i--) {
                $date = Carbon::now()->subDays($i)->format('Y-m-d');
                $revenueChart[] = [
                    'date' => $date,
                    'label' => Carbon::parse($date)->format('d/m'),
                    'total' => isset($dailyRevenue[$date]) ? round((float) $dailyRevenue[$date]['total'], 2) : 0,
                ];
            }

            // Monthly revenue (last 6 months) for trend chart
            for ($i = 5; $i >= 0; $i--) {
                $start = Carbon::now()->subMonths($i)->startOfMonth();
                $end = Carbon::now()->subMonths($i)->endOfMonth();
                $total = (clone $invoiceBase)
                    ->whereDate('issue_date', '>=', $start)
                    ->whereDate('issue_date', '<=', $end)
                    ->sum('total_incl_tax');
                $monthlyRevenue[] = [
                    'month' => $start->format('Y-m'),
                    'label' => $start->locale(app()->getLocale())->isoFormat('MMM'),
                    'total' => round((float) $total, 2),
                ];
            }

            // Recent Invoices
            $recentInvoices = (clone $invoiceBase)
                ->with(['customer:id,name'])
                ->latest('issue_date')
                ->take(8)
                ->get(['id', 'invoice_number', 'customer_id', 'total_incl_tax', 'payment_status', 'issue_date', 'status']);

            // Top Customers (by revenue last 30 days)
            $topCustomers = (clone $invoiceBase)
                ->with('customer:id,name')
                ->whereDate('issue_date', '>=', $last30Days)
                ->select(
                    'customer_id',
                    DB::raw('count(*) as invoice_count'),
                    DB::raw('SUM(total_incl_tax) as total_revenue')
                )
                ->groupBy('customer_id')
                ->orderByDesc('total_revenue')
                ->take(5)
                ->get()
                ->map(fn ($inv) => [
                    'id' => $inv->customer_id,
                    'name' => $inv->customer->name ?? '—',
                    'invoice_count' => (int) $inv->invoice_count,
                    'total_revenue' => round((float) $inv->total_revenue, 2),
                ])
                ->toArray();
        }

        // ─── Customer Stats ────────────────────────────────────────────────
        $customersThisMonth = (clone $customersBase)
            ->whereDate('created_at', '>=', $thisMonth)->count();

        $customersLastMonth = (clone $customersBase)
            ->whereDate('created_at', '>=', $lastMonth)
            ->whereDate('created_at', '<=', $lastMonthEnd)->count();

        $totalCustomers = (clone $customersBase)->count();

        // ─── Vehicles Stats ────────────────────────────────────────────────
        $totalVehicles = Vehicle::where('tenant_id', $tenantId)
            ->when($centerId, fn ($q) => $q->where('center_id', $centerId))
            ->count();

        // ─── Alerts ───────────────────────────────────────────────────────
        $overdueWorkOrders = (clone $workOrdersBase)
            ->whereNotIn('status', ['done', 'cancelled'])
            ->whereNotNull('expected_end_date')
            ->whereDate('expected_end_date', '<', $today)
            ->count();

        $lowStockCount = 0;
        $totalParts = 0;
        $outOfStockCount = 0;

        if ($canViewInventory) {
            $totalParts = Part::where('tenant_id', $tenantId)->count();
            $outOfStockCount = Part::where('tenant_id', $tenantId)
                ->whereDoesntHave('inventoryBalances', function ($q) use ($centerId) {
                    if ($centerId) {
                        $q->where('center_id', $centerId);
                    }
                    $q->where('qty_on_hand', '>', 0);
                })
                ->count();

            $lowStockCount = Part::where('parts.tenant_id', $tenantId)
                ->join('inventory_balances', function ($join) use ($centerId) {
                    $join->on('inventory_balances.part_id', '=', 'parts.id');

                    if ($centerId) {
                        $join->where('inventory_balances.center_id', $centerId);
                    }
                })
                ->where(function ($q) {
                    $q->where(function ($sub) {
                        $sub->where('inventory_balances.min_stock', '>', 0)
                            ->whereColumn('inventory_balances.qty_on_hand', '<=', 'inventory_balances.min_stock');
                    })->orWhere(function ($sub) {
                        $sub->where(function ($zeroMin) {
                            $zeroMin->whereNull('inventory_balances.min_stock')
                                ->orWhere('inventory_balances.min_stock', '<=', 0);
                        })
                            ->where('parts.min_qty', '>', 0)
                            ->whereColumn('inventory_balances.qty_on_hand', '<=', 'parts.min_qty');
                    });
                })
                ->distinct('parts.id')
                ->count('parts.id');
        }

        // ─── Recent Work Orders (Sanitized: hide price if no financial access)
        $recentWorkOrders = (clone $workOrdersBase)
            ->with(['customer:id,name', 'vehicle:id,plate_number'])
            ->latest()
            ->take(8)
            ->get(['id', 'code', 'status', 'customer_id', 'vehicle_id', 'total_incl_tax', 'created_at', 'expected_end_date'])
            ->map(function ($wo) use ($canViewFinancials) {
                if (! $canViewFinancials) {
                    $wo->total_incl_tax = null;
                }

                return $wo;
            });

        // ─── Today's Schedule (work orders with expected_end_date today) ──
        $todaySchedule = (clone $workOrdersBase)
            ->with(['customer:id,name', 'vehicle:id,plate_number'])
            ->whereNotIn('status', ['done', 'cancelled'])
            ->whereDate('expected_end_date', $today)
            ->orderBy('expected_end_date')
            ->take(10)
            ->get(['id', 'code', 'status', 'customer_id', 'vehicle_id', 'expected_end_date']);

        // ─── Today's New Work Orders ───────────────────────────────────────
        $todayNewWorkOrders = (clone $workOrdersBase)
            ->with(['customer:id,name', 'vehicle:id,plate_number'])
            ->whereDate('created_at', $today)
            ->latest()
            ->take(5)
            ->get(['id', 'code', 'status', 'customer_id', 'vehicle_id', 'created_at']);

        // ─── Ready for QC / Delivery (Cars finished or waiting customer) ──
        $readyForDelivery = (clone $workOrdersBase)
            ->with(['customer:id,name,phone', 'vehicle:id,plate_number'])
            ->whereIn('status', ['ready_for_qc', 'done'])
            ->latest()
            ->take(6)
            ->get(['id', 'code', 'status', 'customer_id', 'vehicle_id', 'total_incl_tax', 'expected_end_date'])
            ->map(function ($wo) use ($canViewFinancials) {
                if (! $canViewFinancials) {
                    $wo->total_incl_tax = null;
                }

                return $wo;
            });

        // ─── Today's Appointments (Receptionist / Frontdesk focus) ─────────
        $todayAppointments = Appointment::where('tenant_id', $tenantId)
            ->when($centerId, fn ($q) => $q->where('center_id', $centerId))
            ->whereDate('scheduled_at', $today)
            ->with(['customer:id,name,phone', 'vehicle:id,plate_number'])
            ->orderBy('scheduled_at')
            ->take(8)
            ->get(['id', 'customer_id', 'vehicle_id', 'scheduled_at', 'status', 'notes']);

        // ─── Technician Assigned Work Orders ───────────────────────────────
        $myAssignedWorkOrders = [];

        if ($isTechnician && $user) {
            $myAssignedWorkOrders = (clone $workOrdersBase)
                ->whereHas('items.technicians', function ($q) use ($user) {
                    $q->where('users.id', $user->id);
                })
                ->with(['customer:id,name', 'vehicle:id,plate_number'])
                ->whereNotIn('status', ['done', 'cancelled'])
                ->latest()
                ->take(8)
                ->get(['id', 'code', 'status', 'customer_id', 'vehicle_id', 'expected_end_date']);
        }

        // ─── Work Orders Weekly trend (last 7 days) ─────────────────────────
        $weeklyWorkOrders = (clone $workOrdersBase)
            ->whereDate('created_at', '>=', $last7Days)
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('count(*) as count')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->keyBy('date')
            ->toArray();

        $weeklyChart = [];

        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i)->format('Y-m-d');
            $weeklyChart[] = [
                'date' => $date,
                'label' => Carbon::parse($date)->locale(app()->getLocale())->isoFormat('ddd'),
                'count' => isset($weeklyWorkOrders[$date]) ? $weeklyWorkOrders[$date]['count'] : 0,
            ];
        }

        // ─── Top Services (last 30 days) ───────────────────────────────────
        $topServices = WorkOrderItem::whereHas('workOrder', function ($q) use ($tenantId, $centerId, $last30Days) {
            $q->where('tenant_id', $tenantId)
                ->when($centerId, fn ($qq) => $qq->where('center_id', $centerId))
                ->whereDate('created_at', '>=', $last30Days);
        })
            ->whereNotNull('service_id')
            ->select(
                'service_id',
                DB::raw('count(*) as times_used'),
                DB::raw('SUM(line_total_incl_tax) as total_revenue')
            )
            ->groupBy('service_id')
            ->orderByDesc('times_used')
            ->with('service:id,name_ar,name_en')
            ->take(8)
            ->get()
            ->map(fn ($item) => [
                'id' => $item->service_id,
                'name' => $item->service->name ?? '—',
                'times_used' => (int) $item->times_used,
                'total_revenue' => $canViewFinancials ? round((float) $item->total_revenue, 2) : null,
            ])
            ->toArray();

        // ─── Technician Performance (last 30 days) ─────────────────────────
        $technicianPerformance = DB::table('work_order_item_technician as wit')
            ->join('work_order_items as woi', 'woi.id', '=', 'wit.work_order_item_id')
            ->join('work_orders as wo', 'wo.id', '=', 'woi.work_order_id')
            ->join('users as u', 'u.id', '=', 'wit.user_id')
            ->where('wo.tenant_id', $tenantId)
            ->when($centerId, fn ($q) => $q->where('wo.center_id', $centerId))
            ->whereDate('wo.created_at', '>=', $last30Days)
            ->select(
                'u.id',
                'u.name',
                DB::raw('count(*) as assigned_items'),
                DB::raw('SUM(CASE WHEN woi.status = "completed" THEN 1 ELSE 0 END) as completed_items'),
                DB::raw('SUM(CASE WHEN woi.status = "in_progress" THEN 1 ELSE 0 END) as in_progress_items')
            )
            ->groupBy('u.id', 'u.name')
            ->orderByDesc('assigned_items')
            ->take(8)
            ->get()
            ->map(fn ($row) => [
                'id' => $row->id,
                'name' => $row->name,
                'assigned' => (int) $row->assigned_items,
                'completed' => (int) $row->completed_items,
                'in_progress' => (int) $row->in_progress_items,
                'completion_rate' => $row->assigned_items > 0
                    ? round(($row->completed_items / $row->assigned_items) * 100, 1)
                    : 0,
            ])
            ->toArray();

        // Determine user's primary role label
        $primaryRole = $user?->roles->first();
        $roleLabel = $primaryRole->label_ar ?? ($isTechnician ? 'فني صيانة' : ($isReceptionist ? 'موظف استقبال' : 'عضو فريق'));

        return Inertia::render('Dashboard', [
            'userContext' => [
                'canViewFinancials' => $canViewFinancials,
                'canViewInventory' => $canViewInventory,
                'canCreateWorkOrder' => $canCreateWorkOrder,
                'isTechnician' => $isTechnician,
                'isReceptionist' => $isReceptionist,
                'roleName' => $primaryRole->name ?? 'user',
                'roleLabel' => $roleLabel,
            ],
            'stats' => [
                'workOrders' => [
                    'thisMonth' => $workOrdersThisMonth,
                    'lastMonth' => $workOrdersLastMonth,
                    'active' => $activeWorkOrders,
                    'completedToday' => $completedToday,
                ],
                'revenue' => $canViewFinancials ? [
                    'thisMonth' => round((float) $revenueThisMonth, 2),
                    'lastMonth' => round((float) $revenueLastMonth, 2),
                    'outstanding' => round((float) max((float) $outstandingBalance, 0), 2),
                    'paymentsToday' => round((float) $paymentsToday, 2),
                    'avgInvoiceValue' => round((float) $avgInvoiceValue, 2),
                ] : null,
                'customers' => [
                    'total' => $totalCustomers,
                    'thisMonth' => $customersThisMonth,
                    'lastMonth' => $customersLastMonth,
                ],
                'vehicles' => [
                    'total' => $totalVehicles,
                ],
                'inventory' => $canViewInventory ? [
                    'totalParts' => $totalParts,
                    'outOfStock' => $outOfStockCount,
                ] : null,
            ],
            'charts' => [
                'revenueDaily' => $revenueChart,
                'revenueMonthly' => $monthlyRevenue,
                'workOrdersByStatus' => $workOrdersByStatus,
                'workOrdersByStatusAll' => $workOrdersByStatusAll,
                'weeklyWorkOrders' => $weeklyChart,
            ],
            'alerts' => [
                'overdueWorkOrders' => $overdueWorkOrders,
                'overdueInvoices' => $overdueInvoices,
                'lowStock' => $lowStockCount,
            ],
            'recentWorkOrders' => $recentWorkOrders,
            'recentInvoices' => $recentInvoices,
            'todaySchedule' => $todaySchedule,
            'todayNewWorkOrders' => $todayNewWorkOrders,
            'todayAppointments' => $todayAppointments,
            'readyForDelivery' => $readyForDelivery,
            'myAssignedWorkOrders' => $myAssignedWorkOrders,
            'topServices' => $topServices,
            'technicianPerformance' => $technicianPerformance,
            'topCustomers' => $topCustomers,
            'currency' => session('currency_code', 'SAR'),
        ]);
    }
}
