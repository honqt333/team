<?php

declare(strict_types=1);

namespace App\Http\Controllers\App;

use App\Enums\PaymentType;
use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\WorkOrder;
use App\Models\WorkOrderItem;
use App\Support\TenancyContext;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class ReportsController extends Controller
{
    /**
     * Display the Central Reports Hub.
     */
    public function hub(Request $request): Response
    {
        $tenantId = TenancyContext::tenantId();
        $centerId = TenancyContext::centerId();

        $range = $request->input('range', 'this_month');
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');

        [$start, $end] = $this->resolveDateRange($range, $dateFrom, $dateTo);

        // 1. Core KPIs
        $paymentsQuery = Payment::query()
            ->where('tenant_id', $tenantId)
            ->whereBetween('payment_date', [$start, $end]);

        if ($centerId) {
            $paymentsQuery->where('center_id', $centerId);
        }

        $paymentsSum = (clone $paymentsQuery)
            ->selectRaw('
                SUM(CASE WHEN type IN ("payment", "'.PaymentType::PAYMENT->value.'") THEN amount ELSE 0 END) as total_received,
                SUM(CASE WHEN type IN ("refund", "'.PaymentType::REFUND->value.'") THEN amount ELSE 0 END) as total_refunded
            ')
            ->first();

        $netPaid = (float) (($paymentsSum?->total_received ?? 0) - ($paymentsSum?->total_refunded ?? 0));

        $invoicesQuery = Invoice::query()
            ->where('tenant_id', $tenantId)
            ->whereBetween('issue_date', [$start->toDateString(), $end->toDateString()]);

        if ($centerId) {
            $invoicesQuery->where('center_id', $centerId);
        }

        $invoicesSum = (clone $invoicesQuery)
            ->where('type', 'invoice')
            ->selectRaw('
                SUM(total_incl_tax) as gross_revenue,
                SUM(total_tax) as vat_amount,
                SUM(total_excl_tax) as net_revenue,
                COUNT(*) as count
            ')
            ->first();

        $workOrdersQuery = WorkOrder::query()
            ->where('tenant_id', $tenantId)
            ->whereBetween('created_at', [$start, $end]);

        if ($centerId) {
            $workOrdersQuery->where('center_id', $centerId);
        }

        $workOrdersStats = (clone $workOrdersQuery)
            ->selectRaw('
                COUNT(*) as total_count,
                SUM(CASE WHEN status = "done" THEN 1 ELSE 0 END) as completed_count,
                SUM(CASE WHEN status IN ("open", "in_progress", "ready_for_qc") THEN 1 ELSE 0 END) as in_progress_count,
                SUM(CASE WHEN total_incl_tax > 0 THEN total_incl_tax ELSE 0 END) as total_value
            ')
            ->first();

        // 2. Payments by method
        $paymentsByMethod = (clone $paymentsQuery)
            ->whereIn('type', ['payment', PaymentType::PAYMENT])
            ->selectRaw('COALESCE(payment_method, "other") as method, SUM(amount) as total, COUNT(*) as count')
            ->groupBy('payment_method')
            ->get()
            ->map(fn ($row) => [
                'method' => $row->method,
                'total' => (float) $row->total,
                'count' => (int) $row->count,
            ]);

        // 3. Detailed Recent Tables (limit 25 for quick load)
        $detailedPayments = (clone $paymentsQuery)
            ->with([
                'workOrder:id,code,customer_id',
                'workOrder.customer:id,name',
                'invoice:id,invoice_number',
            ])
            ->orderByDesc('payment_date')
            ->limit(25)
            ->get()
            ->map(fn ($p) => [
                'id' => $p->id,
                'amount' => (float) $p->amount,
                'date' => $p->payment_date?->format('Y-m-d H:i'),
                'method' => $p->payment_method ?? 'other',
                'type' => $p->type instanceof PaymentType ? $p->type->value : (string) $p->type,
                'work_order_code' => $p->workOrder?->code,
                'customer_name' => $p->workOrder?->customer?->name,
                'invoice_number' => $p->invoice?->invoice_number,
            ]);

        $detailedInvoices = (clone $invoicesQuery)
            ->with(['customer:id,name', 'workOrder:id,code'])
            ->orderByDesc('issue_date')
            ->limit(25)
            ->get()
            ->map(fn ($inv) => [
                'id' => $inv->id,
                'invoice_number' => $inv->invoice_number,
                'date' => $inv->issue_date?->format('Y-m-d'),
                'customer_name' => $inv->customer_name_snapshot ?? $inv->customer?->name ?? '-',
                'total_excl_tax' => (float) $inv->total_excl_tax,
                'total_tax' => (float) $inv->total_tax,
                'total_incl_tax' => (float) $inv->total_incl_tax,
                'total_paid' => (float) $inv->total_paid,
                'payment_status' => $inv->payment_status,
                'work_order_code' => $inv->workOrder?->code,
            ]);

        $detailedWorkOrders = (clone $workOrdersQuery)
            ->with(['customer:id,name', 'vehicle:id,plate_number'])
            ->orderByDesc('created_at')
            ->limit(25)
            ->get()
            ->map(fn ($wo) => [
                'id' => $wo->id,
                'code' => $wo->code,
                'status' => $wo->status,
                'created_at' => $wo->created_at?->format('Y-m-d'),
                'customer_name' => $wo->customer?->name ?? '-',
                'vehicle_plate' => $wo->vehicle?->plate_number ?? '-',
                'total_incl_tax' => (float) $wo->total_incl_tax,
            ]);

        // 4. Top Services
        $topServices = WorkOrderItem::query()
            ->where('tenant_id', $tenantId)
            ->whereBetween('created_at', [$start, $end])
            ->whereNotNull('service_id')
            ->selectRaw('service_id, COUNT(*) as usage_count, SUM((unit_price * qty) - discount_amount) as total_revenue')
            ->groupBy('service_id')
            ->orderByDesc('usage_count')
            ->limit(8)
            ->with('service:id,name')
            ->get()
            ->map(fn ($row) => [
                'service_id' => $row->service_id,
                'name' => $row->service?->name ?? 'خدمة عامة',
                'count' => (int) $row->usage_count,
                'revenue' => (float) $row->total_revenue,
            ]);

        // 5. Technicians summary
        $technicians = DB::table('work_order_item_technician')
            ->join('users', 'work_order_item_technician.user_id', '=', 'users.id')
            ->join('work_order_items', 'work_order_item_technician.work_order_item_id', '=', 'work_order_items.id')
            ->where('work_order_items.tenant_id', $tenantId)
            ->whereBetween('work_order_items.created_at', [$start, $end])
            ->selectRaw('
                users.id,
                users.name,
                COUNT(work_order_items.id) as total_assigned_count,
                SUM(CASE WHEN work_order_items.status = "done" OR work_order_items.status = "'.WorkOrderItem::STATUS_COMPLETED.'" THEN 1 ELSE 0 END) as completed_items_count
            ')
            ->groupBy('users.id', 'users.name')
            ->orderByDesc('total_assigned_count')
            ->limit(10)
            ->get()
            ->map(fn ($t) => [
                'id' => $t->id,
                'name' => $t->name,
                'completed_items_count' => (int) $t->completed_items_count,
                'total_assigned_count' => (int) $t->total_assigned_count,
            ]);

        return Inertia::render('Reports/Hub', [
            'filters' => [
                'range' => $range,
                'date_from' => $start->toDateString(),
                'date_to' => $end->toDateString(),
            ],
            'kpis' => [
                'gross_revenue' => (float) ($invoicesSum?->gross_revenue ?? $workOrdersStats?->total_value ?? 0),
                'net_paid' => $netPaid,
                'total_tax' => (float) ($invoicesSum?->vat_amount ?? 0),
                'total_work_orders' => (int) ($workOrdersStats?->total_count ?? 0),
                'completed_work_orders' => (int) ($workOrdersStats?->completed_count ?? 0),
                'in_progress_work_orders' => (int) ($workOrdersStats?->in_progress_count ?? 0),
                'invoices_count' => (int) ($invoicesSum?->count ?? 0),
            ],
            'paymentsByMethod' => $paymentsByMethod,
            'recentPayments' => $detailedPayments,
            'recentInvoices' => $detailedInvoices,
            'recentWorkOrders' => $detailedWorkOrders,
            'topServices' => $topServices,
            'technicians' => $technicians,
        ]);
    }

    /**
     * Resolve date range from string preset or custom dates.
     */
    private function resolveDateRange(string $range, ?string $dateFrom, ?string $dateTo): array
    {
        $now = Carbon::now();

        if ($range === 'custom' && $dateFrom && $dateTo) {
            return [
                Carbon::parse($dateFrom)->startOfDay(),
                Carbon::parse($dateTo)->endOfDay(),
            ];
        }

        return match ($range) {
            'today' => [$now->copy()->startOfDay(), $now->copy()->endOfDay()],
            'yesterday' => [$now->copy()->subDay()->startOfDay(), $now->copy()->subDay()->endOfDay()],
            'this_week' => [$now->copy()->startOfWeek(), $now->copy()->endOfWeek()],
            'last_month' => [$now->copy()->subMonth()->startOfMonth(), $now->copy()->subMonth()->endOfMonth()],
            'this_year' => [$now->copy()->startOfYear(), $now->copy()->endOfYear()],
            'all' => [Carbon::parse('2020-01-01')->startOfDay(), $now->copy()->endOfDay()],
            default => [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()], // 'this_month'
        };
    }
}
