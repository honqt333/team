<?php

declare(strict_types=1);

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Part;
use App\Models\Payment;
use App\Models\Vehicle;
use App\Models\WorkOrder;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $centerId = $request->attributes->get('center_id') ?? auth()->user()?->center_id;
        $tenantId = auth()->user()?->tenant_id;

        // Date ranges
        $today = Carbon::today();
        $thisMonth = Carbon::now()->startOfMonth();
        $lastMonth = Carbon::now()->subMonth()->startOfMonth();
        $lastMonthEnd = Carbon::now()->subMonth()->endOfMonth();
        $last30Days = Carbon::now()->subDays(30);
        $last7Days = Carbon::now()->subDays(7);

        // ─── Work Orders Stats ─────────────────────────────────────────────
        $workOrdersBase = WorkOrder::where('tenant_id', $tenantId)
            ->when($centerId, fn ($q) => $q->where('center_id', $centerId));

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

        // ─── Revenue Stats ─────────────────────────────────────────────────
        $invoiceBase = Invoice::where('tenant_id', $tenantId)
            ->when($centerId, fn ($q) => $q->where('center_id', $centerId))
            ->where('type', 'invoice')
            ->whereNotIn('status', ['draft', 'cancelled']);

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

        // Build 30-day array
        $revenueChart = [];

        for ($i = 29; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i)->format('Y-m-d');
            $revenueChart[] = [
                'date' => $date,
                'label' => Carbon::parse($date)->format('d/m'),
                'total' => isset($dailyRevenue[$date]) ? round($dailyRevenue[$date]['total'], 2) : 0,
            ];
        }

        // ─── Customer Stats ────────────────────────────────────────────────
        $customersBase = Customer::where('tenant_id', $tenantId)
            ->when($centerId, fn ($q) => $q->where('center_id', $centerId));

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
        // Overdue work orders (expected_end_date < today, not done/cancelled)
        $overdueWorkOrders = (clone $workOrdersBase)
            ->whereNotIn('status', ['done', 'cancelled'])
            ->whereNotNull('expected_end_date')
            ->whereDate('expected_end_date', '<', $today)
            ->count();

        // Overdue invoices (unpaid older than 30 days)
        $overdueInvoices = (clone $invoiceBase)
            ->whereIn('payment_status', ['unpaid', 'partial'])
            ->whereDate('issue_date', '<', $last30Days)
            ->count();

        // Low stock parts (join with inventory_balances)
        $lowStockCount = Part::where('parts.tenant_id', $tenantId)
            ->whereNotNull('parts.min_qty')
            ->where('parts.min_qty', '>', 0)
            ->join('inventory_balances', function ($join) use ($centerId) {
                $join->on('inventory_balances.part_id', '=', 'parts.id');

                if ($centerId) {
                    $join->where('inventory_balances.center_id', $centerId);
                }
            })
            ->whereColumn('parts.min_qty', '>', 'inventory_balances.qty_on_hand')
            ->count();

        // ─── Recent Work Orders ────────────────────────────────────────────
        $recentWorkOrders = (clone $workOrdersBase)
            ->with(['customer:id,name', 'vehicle:id,plate_number'])
            ->latest()
            ->take(8)
            ->get(['id', 'code', 'status', 'customer_id', 'vehicle_id', 'total_incl_tax', 'created_at', 'expected_end_date']);

        // ─── Recent Invoices ───────────────────────────────────────────────
        $recentInvoices = (clone $invoiceBase)
            ->with(['customer:id,name'])
            ->latest('issue_date')
            ->take(8)
            ->get(['id', 'invoice_number', 'customer_id', 'total_incl_tax', 'payment_status', 'issue_date', 'status']);

        // ─── Payments Today ────────────────────────────────────────────────
        $paymentsToday = Payment::where('tenant_id', $tenantId)
            ->when($centerId, fn ($q) => $q->where('center_id', $centerId))
            ->whereDate('payment_date', $today)
            ->sum('amount');

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
                'label' => Carbon::parse($date)->locale('ar')->isoFormat('ddd'),
                'count' => isset($weeklyWorkOrders[$date]) ? $weeklyWorkOrders[$date]['count'] : 0,
            ];
        }

        return Inertia::render('Dashboard', [
            'stats' => [
                'workOrders' => [
                    'thisMonth' => $workOrdersThisMonth,
                    'lastMonth' => $workOrdersLastMonth,
                    'active' => $activeWorkOrders,
                    'completedToday' => $completedToday,
                ],
                'revenue' => [
                    'thisMonth' => round($revenueThisMonth, 2),
                    'lastMonth' => round($revenueLastMonth, 2),
                    'outstanding' => round(max($outstandingBalance, 0), 2),
                    'paymentsToday' => round($paymentsToday, 2),
                ],
                'customers' => [
                    'total' => $totalCustomers,
                    'thisMonth' => $customersThisMonth,
                    'lastMonth' => $customersLastMonth,
                ],
                'vehicles' => [
                    'total' => $totalVehicles,
                ],
            ],
            'charts' => [
                'revenueDaily' => $revenueChart,
                'workOrdersByStatus' => $workOrdersByStatus,
                'weeklyWorkOrders' => $weeklyChart,
            ],
            'alerts' => [
                'overdueWorkOrders' => $overdueWorkOrders,
                'overdueInvoices' => $overdueInvoices,
                'lowStock' => $lowStockCount,
            ],
            'recentWorkOrders' => $recentWorkOrders,
            'recentInvoices' => $recentInvoices,
            'currency' => session('currency_code', 'SAR'),
        ]);
    }
}
