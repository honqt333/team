<?php

declare(strict_types=1);

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Part;
use App\Models\Payment;
use App\Models\PurchaseInvoice;
use App\Models\ServiceBay;
use App\Models\Tenant;
use App\Models\Vehicle;
use App\Models\WorkOrder;
use App\Models\WorkOrderItem;
use App\Support\CountryCurrencyRegistry;
use App\Support\Permissions;
use App\Support\TenancyContext;
use Carbon\Carbon;
use DateTimeInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $tenantId = TenancyContext::tenantId() ?? $user?->tenant_id;
        $centerId = TenancyContext::centerId() ?? $user?->current_center_id;

        abort_if(! $tenantId || ! $centerId, 403, 'No active center selected');

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

        // Date ranges & interactive period filter (resolved in Tenant Timezone, queried in UTC half-open bounds)
        $period = in_array($request->string('period')->toString(), ['today', '7d', 'month', '30d'], true)
            ? $request->string('period')->toString()
            : 'month';

        $tz = $this->resolveTenantTimezone($user?->tenant);
        $localNow = Carbon::now($tz);
        $localTodayDate = $localNow->toDateString();
        $localTodayStart = $localNow->copy()->startOfDay();
        $localTomorrowStart = $localTodayStart->copy()->addDay();
        $localThisMonthStart = $localNow->copy()->startOfMonth();
        $localNextMonthStart = $localThisMonthStart->copy()->addMonth();
        $localLastMonthStart = $localThisMonthStart->copy()->subMonth();
        $local7DaysStart = $localTodayStart->copy()->subDays(6);
        $local30DaysStart = $localTodayStart->copy()->subDays(29);
        $localOverdueCutoff = $localTodayStart->copy()->subDays(30);

        $todayStartUtc = $localTodayStart->copy()->utc();
        $tomorrowStartUtc = $localTomorrowStart->copy()->utc();
        $thisMonthStartUtc = $localThisMonthStart->copy()->utc();
        $nextMonthStartUtc = $localNextMonthStart->copy()->utc();
        $lastMonthStartUtc = $localLastMonthStart->copy()->utc();
        $sevenDaysStartUtc = $local7DaysStart->copy()->utc();
        $thirtyDaysStartUtc = $local30DaysStart->copy()->utc();
        $overdueCutoffUtc = $localOverdueCutoff->copy()->utc();

        [$localPeriodStart, $localPeriodNextStart, $localPrevPeriodStart, $localPrevPeriodNextStart] = match ($period) {
            'today' => [
                $localTodayStart,
                $localTomorrowStart,
                $localTodayStart->copy()->subDay(),
                $localTodayStart,
            ],
            '7d' => [
                $localTodayStart->copy()->subDays(6),
                $localTomorrowStart,
                $localTodayStart->copy()->subDays(13),
                $localTodayStart->copy()->subDays(6),
            ],
            '30d' => [
                $localTodayStart->copy()->subDays(29),
                $localTomorrowStart,
                $localTodayStart->copy()->subDays(59),
                $localTodayStart->copy()->subDays(29),
            ],
            default => [
                $localThisMonthStart,
                $localNextMonthStart,
                $localLastMonthStart,
                $localThisMonthStart,
            ],
        };

        $periodStartUtc = $localPeriodStart->copy()->utc();
        $periodNextStartUtc = $localPeriodNextStart->copy()->utc();
        $prevPeriodStartUtc = $localPrevPeriodStart->copy()->utc();
        $prevPeriodNextStartUtc = $localPrevPeriodNextStart->copy()->utc();
        $localPeriodStartDate = $localPeriodStart->toDateString();
        $localPeriodEndDate = $localPeriodNextStart->copy()->subDay()->toDateString();

        $cacheKey = sprintf(
            'dashboard_v2:%d:%d:%s:%s:%s:%d:%d:%d:%d:%d:%d',
            (int) $tenantId,
            (int) $centerId,
            $period,
            $tz,
            $localTodayDate,
            $user ? (int) $user->id : 0,
            $isTechnician ? 1 : 0,
            $isReceptionist ? 1 : 0,
            $canViewFinancials ? 1 : 0,
            $canViewInventory ? 1 : 0,
            $canCreateWorkOrder ? 1 : 0
        );

        if (! app()->runningUnitTests() && ! $request->boolean('refresh')) {
            $cached = Cache::get($cacheKey);

            if (is_array($cached)) {
                return Inertia::render('Dashboard', $cached);
            }
        }

        // ─── Base query builders (re-used everywhere) ─────────────────────
        $workOrdersBase = WorkOrder::where('tenant_id', $tenantId)
            ->when($centerId, fn ($q) => $q->where('center_id', $centerId));

        $invoiceBase = Invoice::where('tenant_id', $tenantId)
            ->when($centerId, fn ($q) => $q->where('center_id', $centerId))
            ->where('type', 'invoice')
            ->whereNotIn('status', ['draft', 'cancelled']);

        $netSalesBase = Invoice::where('tenant_id', $tenantId)
            ->where('center_id', $centerId)
            ->whereIn('type', ['invoice', 'credit_note'])
            ->whereNotIn('status', ['draft', 'cancelled']);

        $netSalesExpr = "SUM(CASE WHEN type = 'credit_note' THEN -total_incl_tax ELSE total_incl_tax END)";

        $customersBase = Customer::where('tenant_id', $tenantId)
            ->when($centerId, fn ($q) => $q->where('center_id', $centerId));

        // ─── Work Orders Stats ─────────────────────────────────────────────
        $workOrdersThisMonth = (clone $workOrdersBase)
            ->where('created_at', '>=', $thisMonthStartUtc)
            ->where('created_at', '<', $nextMonthStartUtc)
            ->count();

        $workOrdersLastMonth = (clone $workOrdersBase)
            ->where('created_at', '>=', $lastMonthStartUtc)
            ->where('created_at', '<', $thisMonthStartUtc)
            ->count();

        $activeWorkOrders = (clone $workOrdersBase)
            ->whereIn('status', ['open', 'in_progress', 'on_hold', 'ready_for_qc'])->count();

        $completedToday = (clone $workOrdersBase)
            ->where('status', 'done')
            ->where('closed_at', '>=', $todayStartUtc)
            ->where('closed_at', '<', $tomorrowStartUtc)
            ->count();

        $periodWorkOrders = (clone $workOrdersBase)
            ->where('created_at', '>=', $periodStartUtc)
            ->where('created_at', '<', $periodNextStartUtc)
            ->count();

        $prevPeriodWorkOrders = (clone $workOrdersBase)
            ->where('created_at', '>=', $prevPeriodStartUtc)
            ->where('created_at', '<', $prevPeriodNextStartUtc)
            ->count();

        // ─── Workshop Intelligence (Service Bays, Cycle Time, On-Time Rate) ─
        $baysTotal = ServiceBay::where('tenant_id', $tenantId)
            ->when($centerId, fn ($q) => $q->where('center_id', $centerId))
            ->where('is_active', true)
            ->count();

        $baysBookedToday = Appointment::where('tenant_id', $tenantId)
            ->when($centerId, fn ($q) => $q->where('center_id', $centerId))
            ->where('scheduled_at', '>=', $todayStartUtc)
            ->where('scheduled_at', '<', $tomorrowStartUtc)
            ->whereNotNull('service_bay_id')
            ->whereNotIn('status', ['cancelled', 'converted'])
            ->distinct('service_bay_id')
            ->count('service_bay_id');

        $doneInPeriod = (clone $workOrdersBase)
            ->where('status', 'done')
            ->whereNotNull('closed_at')
            ->where('closed_at', '>=', $periodStartUtc)
            ->where('closed_at', '<', $periodNextStartUtc)
            ->get(['created_at', 'closed_at', 'expected_end_date']);

        $avgCycleHours = $doneInPeriod->isNotEmpty()
            ? round((float) $doneInPeriod->avg(fn ($wo) => max(0, $wo->created_at->diffInMinutes($wo->closed_at) / 60)), 1)
            : 0.0;

        $withExpected = $doneInPeriod->filter(fn ($wo) => $wo->expected_end_date !== null);
        $onTimeRate = $withExpected->isNotEmpty()
            ? (int) round(($withExpected->filter(function ($wo) use ($tz) {
                $expectedDate = $wo->expected_end_date instanceof DateTimeInterface
                    ? $wo->expected_end_date->format('Y-m-d')
                    : (string) $wo->expected_end_date;

                return $wo->closed_at->lte(Carbon::parse($expectedDate, $tz)->endOfDay()->utc());
            })->count() / $withExpected->count()) * 100)
            : null;

        // Work Orders by Status (for donut chart)
        $workOrdersByStatus = (clone $workOrdersBase)
            ->select('status', DB::raw('count(*) as count'))
            ->where('created_at', '>=', $thisMonthStartUtc)
            ->where('created_at', '<', $nextMonthStartUtc)
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
        $periodRevenue = null;
        $prevPeriodRevenue = null;
        $periodExpenses = null;
        $periodNetFlow = null;
        $paymentMethodsChart = [];
        $outstandingBalance = null;
        $avgInvoiceValue = null;
        $paymentsToday = null;
        $revenueChart = [];
        $monthlyRevenue = [];
        $recentInvoices = [];
        $topCustomers = [];
        $overdueInvoices = 0;

        if ($canViewFinancials) {
            $revenueThisMonth = (float) ((clone $netSalesBase)
                ->where('issue_date', '>=', $thisMonthStartUtc)
                ->where('issue_date', '<', $nextMonthStartUtc)
                ->selectRaw("{$netSalesExpr} as net_total")
                ->value('net_total') ?? 0);

            $revenueLastMonth = (float) ((clone $netSalesBase)
                ->where('issue_date', '>=', $lastMonthStartUtc)
                ->where('issue_date', '<', $thisMonthStartUtc)
                ->selectRaw("{$netSalesExpr} as net_total")
                ->value('net_total') ?? 0);

            $paymentsByInvoiceSub = DB::table('payments')
                ->where('tenant_id', $tenantId)
                ->where('center_id', $centerId)
                ->whereNull('deleted_at')
                ->select(
                    'invoice_id',
                    DB::raw("SUM(CASE WHEN type IN ('payment', 'bad_debt') THEN amount WHEN type = 'refund' THEN -amount ELSE 0 END) as net_paid")
                )
                ->groupBy('invoice_id');

            $creditsByInvoiceSub = DB::table('invoices')
                ->where('tenant_id', $tenantId)
                ->where('center_id', $centerId)
                ->where('type', 'credit_note')
                ->whereNotIn('status', ['draft', 'cancelled'])
                ->whereNotNull('original_invoice_id')
                ->whereNull('deleted_at')
                ->select(
                    'original_invoice_id',
                    DB::raw('SUM(total_incl_tax) as credited_total')
                )
                ->groupBy('original_invoice_id');

            $outstandingBalance = (clone $invoiceBase)
                ->leftJoinSub($paymentsByInvoiceSub, 'inv_payments', 'inv_payments.invoice_id', '=', 'invoices.id')
                ->leftJoinSub($creditsByInvoiceSub, 'inv_credits', 'inv_credits.original_invoice_id', '=', 'invoices.id')
                ->sum(DB::raw('CASE WHEN (invoices.total_incl_tax - COALESCE(inv_credits.credited_total, 0) - COALESCE(inv_payments.net_paid, 0)) > 0 THEN (invoices.total_incl_tax - COALESCE(inv_credits.credited_total, 0) - COALESCE(inv_payments.net_paid, 0)) ELSE 0 END'));

            $avgInvoiceValue = (clone $invoiceBase)
                ->where('issue_date', '>=', $thisMonthStartUtc)
                ->where('issue_date', '<', $nextMonthStartUtc)
                ->avg('total_incl_tax');

            $overdueInvoices = (clone $invoiceBase)
                ->whereIn('payment_status', ['unpaid', 'partial'])
                ->where('issue_date', '<', $overdueCutoffUtc)
                ->count();

            $paymentsToday = (float) (Payment::where('tenant_id', $tenantId)
                ->where('center_id', $centerId)
                ->where('payment_date', '>=', $todayStartUtc)
                ->where('payment_date', '<', $tomorrowStartUtc)
                ->selectRaw("SUM(CASE WHEN type = 'payment' THEN amount WHEN type = 'refund' THEN -amount ELSE 0 END) as net_cash")
                ->value('net_cash') ?? 0);

            $periodRevenue = (float) ((clone $netSalesBase)
                ->where('issue_date', '>=', $periodStartUtc)
                ->where('issue_date', '<', $periodNextStartUtc)
                ->selectRaw("{$netSalesExpr} as net_total")
                ->value('net_total') ?? 0);

            $prevPeriodRevenue = (float) ((clone $netSalesBase)
                ->where('issue_date', '>=', $prevPeriodStartUtc)
                ->where('issue_date', '<', $prevPeriodNextStartUtc)
                ->selectRaw("{$netSalesExpr} as net_total")
                ->value('net_total') ?? 0);

            $periodExpenses = PurchaseInvoice::where('tenant_id', $tenantId)
                ->when($centerId, fn ($q) => $q->where('center_id', $centerId))
                ->whereNotIn('status', ['draft', 'cancelled'])
                ->whereDate('issue_date', '>=', $localPeriodStartDate)
                ->whereDate('issue_date', '<=', $localPeriodEndDate)
                ->sum('total');

            $periodNetFlow = round((float) $periodRevenue - (float) $periodExpenses, 2);

            $paymentMethodsChart = Payment::where('tenant_id', $tenantId)
                ->when($centerId, fn ($q) => $q->where('center_id', $centerId))
                ->where('type', 'payment')
                ->where('payment_date', '>=', $periodStartUtc)
                ->where('payment_date', '<', $periodNextStartUtc)
                ->select('payment_method', DB::raw('SUM(amount) as total'))
                ->groupBy('payment_method')
                ->orderByDesc('total')
                ->get()
                ->map(fn ($row) => [
                    'method' => $row->payment_method ?: 'cash',
                    'total' => round((float) $row->getAttribute('total'), 2),
                ])
                ->values()
                ->toArray();

            // Revenue last 30 local calendar days (daily, grouped in tenant timezone)
            $dailyRevenueMap = [];
            (clone $netSalesBase)
                ->where('issue_date', '>=', $thirtyDaysStartUtc)
                ->where('issue_date', '<', $tomorrowStartUtc)
                ->get(['issue_date', 'type', 'total_incl_tax'])
                ->each(function ($inv) use ($tz, &$dailyRevenueMap): void {
                    $localDate = ($inv->issue_date instanceof Carbon
                        ? $inv->issue_date->copy()
                        : Carbon::parse((string) $inv->issue_date, 'UTC'))
                        ->timezone($tz)
                        ->format('Y-m-d');
                    $signed = $inv->type === 'credit_note'
                        ? -(float) $inv->total_incl_tax
                        : (float) $inv->total_incl_tax;
                    $dailyRevenueMap[$localDate] = ($dailyRevenueMap[$localDate] ?? 0.0) + $signed;
                });

            for ($i = 29; $i >= 0; $i--) {
                $localDay = $localTodayStart->copy()->subDays($i);
                $date = $localDay->format('Y-m-d');
                $revenueChart[] = [
                    'date' => $date,
                    'label' => $localDay->format('d/m'),
                    'total' => isset($dailyRevenueMap[$date]) ? round((float) $dailyRevenueMap[$date], 2) : 0,
                ];
            }

            // Monthly revenue (last 6 local months, grouped in tenant timezone)
            $sixMonthsStartUtc = $localThisMonthStart->copy()->subMonths(5)->utc();
            $monthlyRevenueMap = [];
            (clone $netSalesBase)
                ->where('issue_date', '>=', $sixMonthsStartUtc)
                ->where('issue_date', '<', $nextMonthStartUtc)
                ->get(['issue_date', 'type', 'total_incl_tax'])
                ->each(function ($inv) use ($tz, &$monthlyRevenueMap): void {
                    $localMonth = ($inv->issue_date instanceof Carbon
                        ? $inv->issue_date->copy()
                        : Carbon::parse((string) $inv->issue_date, 'UTC'))
                        ->timezone($tz)
                        ->format('Y-m');
                    $signed = $inv->type === 'credit_note'
                        ? -(float) $inv->total_incl_tax
                        : (float) $inv->total_incl_tax;
                    $monthlyRevenueMap[$localMonth] = ($monthlyRevenueMap[$localMonth] ?? 0.0) + $signed;
                });

            for ($i = 5; $i >= 0; $i--) {
                $start = $localThisMonthStart->copy()->subMonths($i);
                $monthKey = $start->format('Y-m');
                $monthlyRevenue[] = [
                    'month' => $monthKey,
                    'label' => $start->copy()->locale(app()->getLocale())->isoFormat('MMM'),
                    'total' => round((float) ($monthlyRevenueMap[$monthKey] ?? 0.0), 2),
                ];
            }

            // Recent Invoices
            $recentInvoices = (clone $invoiceBase)
                ->with(['customer:id,name'])
                ->latest('issue_date')
                ->take(8)
                ->get(['id', 'invoice_number', 'customer_id', 'total_incl_tax', 'payment_status', 'issue_date', 'status']);

            // Top Customers (by net revenue last 30 local days)
            $topCustomers = (clone $netSalesBase)
                ->with('customer:id,name')
                ->where('issue_date', '>=', $thirtyDaysStartUtc)
                ->where('issue_date', '<', $tomorrowStartUtc)
                ->select(
                    'customer_id',
                    DB::raw("SUM(CASE WHEN type = 'invoice' THEN 1 ELSE 0 END) as invoice_count"),
                    DB::raw("{$netSalesExpr} as total_revenue")
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
            ->where('created_at', '>=', $thisMonthStartUtc)
            ->where('created_at', '<', $nextMonthStartUtc)
            ->count();

        $customersLastMonth = (clone $customersBase)
            ->where('created_at', '>=', $lastMonthStartUtc)
            ->where('created_at', '<', $thisMonthStartUtc)
            ->count();

        $totalCustomers = (clone $customersBase)->count();

        // ─── Vehicles Stats ────────────────────────────────────────────────
        $totalVehicles = Vehicle::where('tenant_id', $tenantId)
            ->when($centerId, fn ($q) => $q->where('center_id', $centerId))
            ->count();

        // ─── Alerts ───────────────────────────────────────────────────────
        $overdueWorkOrders = (clone $workOrdersBase)
            ->whereNotIn('status', ['done', 'cancelled'])
            ->whereNotNull('expected_end_date')
            ->whereDate('expected_end_date', '<', $localTodayDate)
            ->count();

        $lowStockCount = 0;
        $totalParts = 0;
        $outOfStockCount = 0;

        if ($canViewInventory) {
            $totalParts = Part::where('tenant_id', $tenantId)->count();
            $outOfStockCount = Part::where('tenant_id', $tenantId)
                ->whereDoesntHave('inventoryBalances', function ($q) use ($tenantId, $centerId) {
                    $q->where('tenant_id', $tenantId)
                        ->where('center_id', $centerId)
                        ->where('qty_on_hand', '>', 0);
                })
                ->count();

            $lowStockCount = Part::where('parts.tenant_id', $tenantId)
                ->join('inventory_balances', function ($join) use ($tenantId, $centerId) {
                    $join->on('inventory_balances.part_id', '=', 'parts.id')
                        ->where('inventory_balances.tenant_id', $tenantId)
                        ->where('inventory_balances.center_id', $centerId);
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
            ->with(['customer:id,name,phone,whatsapp', 'vehicle:id,plate_number'])
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
            ->whereDate('expected_end_date', $localTodayDate)
            ->orderBy('expected_end_date')
            ->take(10)
            ->get(['id', 'code', 'status', 'customer_id', 'vehicle_id', 'expected_end_date']);

        // ─── Today's New Work Orders ───────────────────────────────────────
        $todayNewWorkOrders = (clone $workOrdersBase)
            ->with(['customer:id,name', 'vehicle:id,plate_number'])
            ->where('created_at', '>=', $todayStartUtc)
            ->where('created_at', '<', $tomorrowStartUtc)
            ->latest()
            ->take(5)
            ->get(['id', 'code', 'status', 'customer_id', 'vehicle_id', 'created_at']);

        // ─── Ready for QC / Delivery (Cars finished or waiting customer) ──
        $readyForDelivery = (clone $workOrdersBase)
            ->with(['customer:id,name,phone,whatsapp', 'vehicle:id,plate_number'])
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
            ->where('scheduled_at', '>=', $todayStartUtc)
            ->where('scheduled_at', '<', $tomorrowStartUtc)
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

        // ─── Work Orders Weekly trend (last 7 local calendar days) ─────────
        $weeklyWorkOrdersMap = [];
        (clone $workOrdersBase)
            ->where('created_at', '>=', $sevenDaysStartUtc)
            ->where('created_at', '<', $tomorrowStartUtc)
            ->get(['id', 'created_at'])
            ->each(function ($wo) use ($tz, &$weeklyWorkOrdersMap): void {
                $localDate = ($wo->created_at instanceof Carbon
                    ? $wo->created_at->copy()
                    : Carbon::parse((string) $wo->created_at, 'UTC'))
                    ->timezone($tz)
                    ->format('Y-m-d');
                $weeklyWorkOrdersMap[$localDate] = ($weeklyWorkOrdersMap[$localDate] ?? 0) + 1;
            });

        $weeklyChart = [];

        for ($i = 6; $i >= 0; $i--) {
            $localDay = $localTodayStart->copy()->subDays($i);
            $date = $localDay->format('Y-m-d');
            $weeklyChart[] = [
                'date' => $date,
                'label' => $localDay->copy()->locale(app()->getLocale())->isoFormat('ddd'),
                'count' => $weeklyWorkOrdersMap[$date] ?? 0,
            ];
        }

        // ─── Top Services (last 30 local days) ─────────────────────────────
        $topServices = WorkOrderItem::whereHas('workOrder', function ($q) use ($tenantId, $centerId, $thirtyDaysStartUtc, $tomorrowStartUtc) {
            $q->where('tenant_id', $tenantId)
                ->when($centerId, fn ($qq) => $qq->where('center_id', $centerId))
                ->where('created_at', '>=', $thirtyDaysStartUtc)
                ->where('created_at', '<', $tomorrowStartUtc);
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

        // ─── Checked-Out / Done Work Orders with Balance Due ──────────────
        $checkedOutPaymentDue = null;

        if ($canViewFinancials) {
            $paidSubquery = DB::table('payments')
                ->selectRaw('work_order_id, COALESCE(SUM(CASE WHEN type IN ("payment", "bad_debt") THEN amount WHEN type = "refund" THEN -amount ELSE 0 END), 0) as net_paid')
                ->whereNull('deleted_at')
                ->groupBy('work_order_id');

            $creditNotesSubquery = DB::table('invoices as cn')
                ->leftJoin('invoices as orig', function ($join) {
                    $join->on('orig.id', '=', 'cn.original_invoice_id')
                        ->on('orig.tenant_id', '=', 'cn.tenant_id')
                        ->on('orig.center_id', '=', 'cn.center_id')
                        ->whereNull('orig.deleted_at');
                })
                ->selectRaw('COALESCE(cn.work_order_id, orig.work_order_id) as work_order_id, COALESCE(SUM(cn.total_incl_tax), 0) as total_credited')
                ->where('cn.type', 'credit_note')
                ->whereNotIn('cn.status', ['draft', 'cancelled'])
                ->whereNull('cn.deleted_at')
                ->whereRaw('COALESCE(cn.work_order_id, orig.work_order_id) IS NOT NULL')
                ->groupByRaw('COALESCE(cn.work_order_id, orig.work_order_id)');

            $dueRow = (clone $workOrdersBase)
                ->leftJoinSub($paidSubquery, 'wo_paid', 'wo_paid.work_order_id', '=', 'work_orders.id')
                ->leftJoinSub($creditNotesSubquery, 'wo_credited', 'wo_credited.work_order_id', '=', 'work_orders.id')
                ->where('work_orders.status', 'done')
                ->where('work_orders.total_incl_tax', '>', 0)
                ->whereRaw('(work_orders.total_incl_tax - COALESCE(wo_credited.total_credited, 0) - COALESCE(wo_paid.net_paid, 0)) > 0.01')
                ->selectRaw('COUNT(*) as due_count, COALESCE(SUM(work_orders.total_incl_tax - COALESCE(wo_credited.total_credited, 0) - COALESCE(wo_paid.net_paid, 0)), 0) as due_amount')
                ->first();

            $checkedOutPaymentDue = [
                'count' => (int) ($dueRow->due_count ?? 0),
                'amount' => round((float) ($dueRow->due_amount ?? 0), 2),
            ];
        }

        // ─── Active Workshop Vehicles (Live Progress & WhatsApp) ───────────
        $activeWorkshopVehicles = (clone $workOrdersBase)
            ->with([
                'customer:id,name,phone,whatsapp',
                'vehicle:id,plate_number,make_id,model_id,make_other,model_other,year',
                'vehicle.make:id,name_ar,name_en',
                'vehicle.model:id,name_ar,name_en',
                'items:id,work_order_id,status',
            ])
            ->whereNotIn('status', ['done', 'cancelled'])
            ->latest()
            ->take(8)
            ->get(['id', 'code', 'status', 'customer_id', 'vehicle_id', 'expected_end_date', 'created_at'])
            ->map(function ($wo) {
                $activeItems = $wo->items->where('status', '!=', 'cancelled');
                $totalItems = $activeItems->count();
                $doneItems = $activeItems->whereIn('status', ['completed', 'ready_for_qc'])->count();
                $progress = $totalItems > 0 ? (int) round(($doneItems / $totalItems) * 100) : 0;
                $rawPhone = preg_replace('/\D+/', '', (string) ($wo->customer?->whatsapp ?: $wo->customer?->phone ?: ''));

                return [
                    'id' => $wo->id,
                    'code' => $wo->code,
                    'status' => $wo->status,
                    'customer_name' => $wo->customer?->name ?? '—',
                    'customer_phone' => $wo->customer?->whatsapp ?: $wo->customer?->phone,
                    'whatsapp_phone' => $rawPhone ?: null,
                    'plate_number' => $wo->vehicle?->plate_number ?? '—',
                    'vehicle_label' => trim(($wo->vehicle?->make?->name_ar ?? $wo->vehicle?->make_other ?? '').' '.($wo->vehicle?->model?->name_ar ?? $wo->vehicle?->model_other ?? '').' '.($wo->vehicle?->year ?? '')),
                    'progress_percent' => $progress,
                    'done_items' => $doneItems,
                    'total_items' => $totalItems,
                ];
            })
            ->values()
            ->toArray();

        // ─── Currently Working Technicians ──────────────────────────────────
        $workingTechniciansNow = DB::table('work_order_item_technician as wit')
            ->join('work_order_items as woi', 'woi.id', '=', 'wit.work_order_item_id')
            ->join('work_orders as wo', 'wo.id', '=', 'woi.work_order_id')
            ->join('users as u', 'u.id', '=', 'wit.user_id')
            ->leftJoin('vehicles as v', 'v.id', '=', 'wo.vehicle_id')
            ->where('wo.tenant_id', $tenantId)
            ->where('wo.center_id', $centerId)
            ->where('woi.tenant_id', $tenantId)
            ->where('woi.center_id', $centerId)
            ->where('u.tenant_id', $tenantId)
            ->whereNull('wo.deleted_at')
            ->whereNotIn('wo.status', ['done', 'cancelled'])
            ->where('woi.status', 'in_progress')
            ->select(
                'u.id as user_id',
                'u.name as technician_name',
                'wo.id as work_order_id',
                'wo.code as work_order_code',
                'woi.title as service_title',
                'v.plate_number'
            )
            ->orderByDesc('woi.updated_at')
            ->limit(10)
            ->get()
            ->map(fn ($row) => [
                'user_id' => $row->user_id,
                'technician_name' => $row->technician_name,
                'work_order_id' => $row->work_order_id,
                'work_order_code' => $row->work_order_code,
                'service_title' => $row->service_title,
                'plate_number' => $row->plate_number ?? '—',
            ])
            ->toArray();

        // ─── Technician Performance (last 30 local days) ───────────────────
        $technicianPerformance = DB::table('work_order_item_technician as wit')
            ->join('work_order_items as woi', 'woi.id', '=', 'wit.work_order_item_id')
            ->join('work_orders as wo', 'wo.id', '=', 'woi.work_order_id')
            ->join('users as u', 'u.id', '=', 'wit.user_id')
            ->where('wo.tenant_id', $tenantId)
            ->where('wo.center_id', $centerId)
            ->where('woi.tenant_id', $tenantId)
            ->where('woi.center_id', $centerId)
            ->where('u.tenant_id', $tenantId)
            ->whereNull('wo.deleted_at')
            ->where('wo.created_at', '>=', $thirtyDaysStartUtc)
            ->where('wo.created_at', '<', $tomorrowStartUtc)
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

        $payload = [
            'period' => $period,
            'lastUpdatedAt' => Carbon::now()->toIso8601String(),
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
                    'periodCount' => $periodWorkOrders,
                    'prevPeriodCount' => $prevPeriodWorkOrders,
                    'active' => $activeWorkOrders,
                    'completedToday' => $completedToday,
                ],
                'workshop' => [
                    'baysTotal' => $baysTotal,
                    'baysBookedToday' => $baysBookedToday,
                    'avgCycleHours' => $avgCycleHours,
                    'onTimeRate' => $onTimeRate,
                ],
                'revenue' => $canViewFinancials ? [
                    'thisMonth' => round((float) $revenueThisMonth, 2),
                    'lastMonth' => round((float) $revenueLastMonth, 2),
                    'periodTotal' => round((float) $periodRevenue, 2),
                    'prevPeriodTotal' => round((float) $prevPeriodRevenue, 2),
                    'periodExpenses' => round((float) $periodExpenses, 2),
                    'periodNetFlow' => $periodNetFlow,
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
                'paymentMethods' => $paymentMethodsChart,
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
            'checkedOutPaymentDue' => $checkedOutPaymentDue,
            'activeWorkshopVehicles' => $activeWorkshopVehicles,
            'workingTechniciansNow' => $workingTechniciansNow,
            'currency' => CountryCurrencyRegistry::tenantCurrency($user?->tenant),
        ];

        if (! app()->runningUnitTests()) {
            Cache::put($cacheKey, $payload, now()->addSeconds(60));
        }

        return Inertia::render('Dashboard', $payload);
    }

    /**
     * Resolve and validate the tenant's IANA timezone without mutating global app timezone.
     */
    private function resolveTenantTimezone(?Tenant $tenant): string
    {
        $candidate = ($tenant !== null ? $tenant->timezone : null)
            ?? CountryCurrencyRegistry::countryMeta(CountryCurrencyRegistry::tenantCountry($tenant))['timezone']
            ?? config('app.timezone', 'UTC');

        if (is_string($candidate) && in_array($candidate, timezone_identifiers_list(), true)) {
            return $candidate;
        }

        $fallback = config('app.timezone', 'UTC');

        return is_string($fallback) && in_array($fallback, timezone_identifiers_list(), true)
            ? $fallback
            : 'UTC';
    }
}
