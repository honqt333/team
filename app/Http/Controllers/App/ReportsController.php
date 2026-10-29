<?php

declare(strict_types=1);

namespace App\Http\Controllers\App;

use App\Enums\PaymentType;
use App\Http\Controllers\Controller;
use App\Models\Center;
use App\Models\Customer;
use App\Models\Department;
use App\Models\Payment;
use App\Models\Service;
use App\Models\Supplier;
use App\Models\Tenant;
use App\Models\VehicleMake;
use App\Models\VehicleModel;
use App\Models\WorkOrder;
use App\Models\WorkOrderItem;
use App\Support\TenancyContext;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class ReportsController extends Controller
{
    /**
     * Reports Hub (Modular Units Grid).
     */
    public function hub(): Response
    {
        return Inertia::render('Reports/Hub');
    }

    /**
     * Dedicated Payments Report unit.
     */
    public function payments(Request $request): Response
    {
        $tenantId = auth()->user()->tenant_id;
        $centerId = TenancyContext::centerId();

        $range = (string) ($request->input('range') ?: 'this_month');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $method = (string) ($request->input('method') ?: 'all');
        $type = (string) ($request->input('type') ?: 'all');
        $customerId = (string) ($request->input('customer_id') ?: 'all');
        $customerType = (string) ($request->input('customer_type') ?: 'all');
        $supplierId = (string) ($request->input('supplier_id') ?: 'all');
        $makeId = (string) ($request->input('make_id') ?: 'all');
        $centerFilter = (string) ($request->input('center_id') ?: 'all');
        $groupBy = (string) ($request->input('group_by') ?: 'none');
        $search = trim((string) ($request->input('search') ?? ''));

        [$from, $to] = $this->resolveDateRange($range, $startDate, $endDate);

        $query = $this->buildPaymentsQuery(
            $tenantId,
            $centerId,
            $from,
            $to,
            $method,
            $type,
            $customerId,
            $customerType,
            $supplierId,
            $makeId,
            $centerFilter,
            $search
        );

        // Calculate KPIs on filtered dataset
        $kpiQuery = clone $query;
        $totalCollected = (float) (clone $kpiQuery)->where('type', PaymentType::PAYMENT)->sum('amount');
        $totalRefunded = (float) (clone $kpiQuery)->where('type', PaymentType::REFUND)->sum('amount');
        $totalBadDebt = (float) (clone $kpiQuery)->where('type', PaymentType::BAD_DEBT)->sum('amount');
        $netCollected = $totalCollected - $totalRefunded;
        $totalCount = (int) (clone $kpiQuery)->count();

        // Breakdown by payment method
        $methodsBreakdown = (clone $kpiQuery)
            ->where('type', PaymentType::PAYMENT)
            ->selectRaw('payment_method, sum(amount) as total_amount, count(*) as count')
            ->groupBy('payment_method')
            ->get()
            ->map(fn ($row) => [
                'method' => $row->payment_method,
                'total' => (float) $row->total_amount,
                'count' => (int) $row->count,
            ]);

        $relations = [
            'center:id,name',
            'invoice:id,invoice_number,customer_id,status,total_incl_tax,total_paid',
            'invoice.customer:id,name,phone,type',
            'workOrder:id,code,customer_id,vehicle_id,status',
            'workOrder.customer:id,name,phone,type',
            'workOrder.vehicle:id,plate_number,make_id,model_id,make_other,model_other,year',
            'workOrder.vehicle.make:id,name_ar,name_en',
            'workOrder.vehicle.model:id,name_ar,name_en',
            'purchaseInvoice:id,invoice_number,supplier_id',
            'purchaseInvoice.supplier:id,name,phone',
            'receivedBy:id,name',
        ];

        $groupedPayments = null;
        $payments = null;

        if ($groupBy !== 'none') {
            // Group payments by selected criterion
            $allPayments = (clone $query)
                ->with($relations)
                ->orderBy('payment_date', 'desc')
                ->limit(2000)
                ->get();

            $grouped = $allPayments->groupBy(function ($payment) use ($groupBy) {
                return match ($groupBy) {
                    'method' => $payment->payment_method ?: 'other',
                    'center' => $payment->center?->name ?: 'المركز الرئيسي',
                    'customer_type' => $payment->workOrder?->customer?->type
                        ?? $payment->invoice?->customer?->type
                        ?? 'individual',
                    'customer' => $payment->workOrder?->customer?->name
                        ?? $payment->invoice?->customer?->name
                        ?? 'عميل غير محدد',
                    'supplier' => $payment->purchaseInvoice?->supplier?->name ?: 'مورد غير محدد',
                    default => 'all',
                };
            });

            $groupedPayments = $grouped->map(function ($items, $key) {
                $gPayment = (float) $items->filter(fn ($p) => ($p->type instanceof PaymentType ? $p->type->value : (string) $p->type) === 'payment')->sum('amount');
                $gRefund = (float) $items->filter(fn ($p) => ($p->type instanceof PaymentType ? $p->type->value : (string) $p->type) === 'refund')->sum('amount');
                $gBadDebt = (float) $items->filter(fn ($p) => ($p->type instanceof PaymentType ? $p->type->value : (string) $p->type) === 'bad_debt')->sum('amount');
                $gNet = $gPayment - $gRefund;

                return [
                    'group_key' => $key,
                    'group_name' => (string) $key,
                    'total_records' => $items->count(),
                    'total_collected' => $gPayment,
                    'total_refunded' => $gRefund,
                    'total_bad_debt' => $gBadDebt,
                    'net_collected' => $gNet,
                    'payments' => $items->values(),
                ];
            })->values();
        } else {
            // Paginated flat records
            $payments = (clone $query)
                ->with($relations)
                ->orderBy('payment_date', 'desc')
                ->paginate(30)
                ->withQueryString();
        }

        $tenant = auth()->user()->tenant;
        $currentCenter = $centerId ? Center::find($centerId) : null;
        $accessibleCenterIds = $this->accessibleCenterIds();

        // The customer filter must not offer the contact details of
        // another branch's customers: the payments behind it are already
        // limited to the centres of the signed-in user.
        $customers = Customer::query()
            ->where('tenant_id', $tenantId)
            ->when(
                $accessibleCenterIds !== null,
                fn ($query) => $query->whereIn('center_id', $accessibleCenterIds)
            )
            ->select('id', 'name', 'phone')
            ->orderBy('name')
            ->limit(150)
            ->get();

        $suppliers = Supplier::query()
            ->where('tenant_id', $tenantId)
            ->select('id', 'name')
            ->orderBy('name')
            ->limit(100)
            ->get();

        $makes = VehicleMake::query()
            ->select('id', 'name_ar', 'name_en')
            ->orderBy('name_ar')
            ->get();

        $centers = Center::query()
            ->where('tenant_id', $tenantId)
            ->select('id', 'name')
            ->get();

        return Inertia::render('Reports/Payments', [
            'filters' => [
                'range' => $range,
                'start_date' => $from?->format('Y-m-d') ?? $startDate,
                'end_date' => $to?->format('Y-m-d') ?? $endDate,
                'method' => $method,
                'type' => $type,
                'customer_id' => $customerId,
                'customer_type' => $customerType,
                'supplier_id' => $supplierId,
                'make_id' => $makeId,
                'center_id' => $centerFilter,
                'group_by' => $groupBy,
                'search' => $search,
            ],
            'kpis' => [
                'total_collected' => $totalCollected,
                'total_refunded' => $totalRefunded,
                'total_bad_debt' => $totalBadDebt,
                'net_collected' => $netCollected,
                'total_count' => $totalCount,
            ],
            'methodsBreakdown' => $methodsBreakdown,
            'customers' => $customers,
            'suppliers' => $suppliers,
            'makes' => $makes,
            'centers' => $centers,
            'companyInfo' => [
                'name' => $tenant->legal_name ?: $tenant->name,
                'vat_number' => $tenant->taxSettings?->tax_number ?? $tenant->vat_number ?? '',
                'cr_number' => $tenant->cr_number ?? '',
                'logo_path' => $tenant->logo_path ?? '',
                'center_name' => $currentCenter?->name ?? '',
            ],
            'payments' => $payments,
            'groupedPayments' => $groupedPayments,
        ]);
    }

    /**
     * Export Payments Report as CSV (with UTF-8 BOM for Arabic Excel support).
     */
    public function exportPayments(Request $request): StreamedResponse
    {
        $tenantId = auth()->user()->tenant_id;
        $centerId = TenancyContext::centerId();

        $range = (string) ($request->input('range') ?: 'this_month');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $method = (string) ($request->input('method') ?: 'all');
        $type = (string) ($request->input('type') ?: 'all');
        $customerId = (string) ($request->input('customer_id') ?: 'all');
        $customerType = (string) ($request->input('customer_type') ?: 'all');
        $supplierId = (string) ($request->input('supplier_id') ?: 'all');
        $makeId = (string) ($request->input('make_id') ?: 'all');
        $centerFilter = (string) ($request->input('center_id') ?: 'all');
        $search = trim((string) ($request->input('search') ?? ''));

        [$from, $to] = $this->resolveDateRange($range, $startDate, $endDate);

        $query = $this->buildPaymentsQuery(
            $tenantId,
            $centerId,
            $from,
            $to,
            $method,
            $type,
            $customerId,
            $customerType,
            $supplierId,
            $makeId,
            $centerFilter,
            $search
        )->with([
            'center:id,name',
            'invoice:id,invoice_number,customer_id',
            'invoice.customer:id,name,phone',
            'workOrder:id,code,customer_id,vehicle_id',
            'workOrder.customer:id,name,phone',
            'workOrder.vehicle:id,plate_number,make_id,model_id,make_other,model_other,year',
            'workOrder.vehicle.make:id,name_ar,name_en',
            'workOrder.vehicle.model:id,name_ar,name_en',
            'purchaseInvoice:id,invoice_number,supplier_id',
            'purchaseInvoice.supplier:id,name',
            'receivedBy:id,name',
        ])->orderBy('payment_date', 'desc');

        $filename = 'تقرير_المدفوعات_'.now()->format('Y-m-d_His').'.csv';

        return response()->streamDownload(function () use ($query) {
            $output = fopen('php://output', 'w');
            // Write UTF-8 BOM for Excel
            fwrite($output, "\xEF\xBB\xBF");

            // CSV Header
            fputcsv($output, [
                'رقم المعاملة',
                'الفرع',
                'التاريخ',
                'نوع السند',
                'الطرف (العميل / المورد)',
                'رقم الهاتف',
                'رقم الفاتورة',
                'رقم أمر العمل',
                'المركبة',
                'رقم اللوحة',
                'طريقة الدفع',
                'المبلغ (ر.س)',
                'المستلم',
                'الملاحظات',
            ]);

            $query->chunk(200, function ($payments) use ($output) {
                foreach ($payments as $payment) {
                    $partyName = $payment->workOrder?->customer?->name
                        ?? $payment->invoice?->customer?->name
                        ?? $payment->purchaseInvoice?->supplier?->name
                        ?? '—';

                    $phone = $payment->workOrder?->customer?->phone
                        ?? $payment->invoice?->customer?->phone
                        ?? $payment->purchaseInvoice?->supplier?->phone
                        ?? '—';

                    $invoiceNo = $payment->invoice?->invoice_number
                        ?? $payment->purchaseInvoice?->invoice_number
                        ?? '—';

                    $woNo = $payment->workOrder?->work_order_number ?? $payment->workOrder?->code ?? '—';
                    $vehicleStr = ($payment->workOrder?->vehicle?->make?->name_ar ?? $payment->workOrder?->vehicle?->make_other ?? '').' '.($payment->workOrder?->vehicle?->model?->name_ar ?? $payment->workOrder?->vehicle?->model_other ?? '');
                    $plate = $payment->workOrder?->vehicle?->plate_number ?? '—';

                    $typeVal = $payment->type instanceof PaymentType ? $payment->type->value : (string) $payment->type;
                    $typeLabel = match ($typeVal) {
                        'refund' => 'استرجاع',
                        'bad_debt' => 'دين معدوم',
                        default => 'قبض',
                    };

                    fputcsv($output, [
                        $payment->reference ?: ('REC-'.$payment->id),
                        $payment->center?->name ?? 'المركز الرئيسي',
                        $payment->payment_date?->format('Y-m-d H:i') ?? '—',
                        $typeLabel,
                        $partyName,
                        $phone,
                        $invoiceNo,
                        $woNo,
                        trim($vehicleStr) ?: '—',
                        $plate,
                        $payment->payment_method,
                        number_format((float) $payment->amount, 2, '.', ''),
                        $payment->receivedBy?->name ?? '—',
                        $payment->notes ?? '',
                    ]);
                }
            });

            fclose($output);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Cache-Control' => 'no-store, no-cache',
        ]);
    }

    /**
     * Build reusable payments query with multi-tenancy, center and filters.
     */
    /**
     * The centres whose records the signed-in user may read.
     *
     * The tenant owner (super admin) manages every branch of the tenant,
     * so they are not limited. Every other account is limited to the
     * centres it is assigned to — the same rule the employee list uses.
     *
     * @return array<int>|null null means "every centre of the tenant"
     */
    private function accessibleCenterIds(): ?array
    {
        $user = auth()->user();

        if (! $user) {
            return null;
        }

        if ($user->hasRole('super_admin')) {
            return null;
        }

        $centerIds = $user->centers()
            ->pluck('centers.id')
            ->map(fn ($id) => (int) $id)
            ->all();

        if ($centerIds === []) {
            return null;
        }

        return array_values(array_unique($centerIds));
    }

    private function buildPaymentsQuery(
        int $tenantId,
        ?int $centerId,
        ?Carbon $from,
        ?Carbon $to,
        ?string $method = 'all',
        ?string $type = 'all',
        ?string $customerId = 'all',
        ?string $customerType = 'all',
        ?string $supplierId = 'all',
        ?string $makeId = 'all',
        ?string $centerFilter = 'all',
        ?string $search = ''
    ) {
        $method = $method ?: 'all';
        $type = $type ?: 'all';
        $customerId = $customerId ?: 'all';
        $customerType = $customerType ?: 'all';
        $supplierId = $supplierId ?: 'all';
        $makeId = $makeId ?: 'all';
        $centerFilter = $centerFilter ?: 'all';
        $search = trim((string) $search);

        $query = Payment::query()->where('tenant_id', $tenantId);

        $accessibleCenterIds = $this->accessibleCenterIds();

        if ($centerFilter !== 'all' && ! empty($centerFilter)) {
            $requestedCenterId = (int) $centerFilter;

            // A centre the signed-in user cannot read is not applied:
            // the report then shows the intersection of the chosen
            // centre and the centres of the user, which is empty —
            // instead of another branch's records.
            if ($accessibleCenterIds === null || in_array($requestedCenterId, $accessibleCenterIds, true)) {
                $query->where('center_id', $requestedCenterId);
            } else {
                $query->whereIn('center_id', []);
            }
        } elseif ($accessibleCenterIds !== null && ! empty($accessibleCenterIds)) {
            $query->where(function ($q) use ($accessibleCenterIds) {
                $q->whereIn('center_id', $accessibleCenterIds)->orWhereNull('center_id');
            });
        }

        if ($from && $to) {
            $query->whereBetween('payment_date', [$from, $to]);
        }

        if ($method !== 'all') {
            $query->where('payment_method', $method);
        }

        if ($type !== 'all') {
            $query->where('type', $type);
        }

        if ($customerId !== 'all') {
            $query->where(function ($q) use ($customerId) {
                $q->whereHas('invoice', fn ($iq) => $iq->where('customer_id', $customerId))
                    ->orWhereHas('workOrder', fn ($wq) => $wq->where('customer_id', $customerId));
            });
        }

        if ($customerType !== 'all') {
            $query->where(function ($q) use ($customerType) {
                $q->whereHas('invoice.customer', fn ($iq) => $iq->where('type', $customerType))
                    ->orWhereHas('workOrder.customer', fn ($wq) => $wq->where('type', $customerType));
            });
        }

        if ($supplierId !== 'all') {
            $query->whereHas('purchaseInvoice', fn ($pq) => $pq->where('supplier_id', $supplierId));
        }

        if ($makeId !== 'all') {
            $query->whereHas('workOrder.vehicle', fn ($vq) => $vq->where('make_id', $makeId));
        }

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('reference', 'like', "%{$search}%")
                    ->orWhere('notes', 'like', "%{$search}%")
                    ->orWhereHas('invoice', function ($iq) use ($search) {
                        $iq->where('invoice_number', 'like', "%{$search}%")
                            ->orWhereHas('customer', function ($cq) use ($search) {
                                $cq->where('name', 'like', "%{$search}%")
                                    ->orWhere('phone', 'like', "%{$search}%");
                            });
                    })
                    ->orWhereHas('workOrder', function ($wq) use ($search) {
                        $wq->where('code', 'like', "%{$search}%")
                            ->orWhereHas('customer', function ($cq) use ($search) {
                                $cq->where('name', 'like', "%{$search}%")
                                    ->orWhere('phone', 'like', "%{$search}%");
                            })
                            ->orWhereHas('vehicle', function ($vq) use ($search) {
                                $vq->where('plate_number', 'like', "%{$search}%")
                                    ->orWhere('vin', 'like', "%{$search}%");
                            });
                    })
                    ->orWhereHas('purchaseInvoice', function ($pq) use ($search) {
                        $pq->where('invoice_number', 'like', "%{$search}%")
                            ->orWhereHas('supplier', function ($sq) use ($search) {
                                $sq->where('name', 'like', "%{$search}%");
                            });
                    });
            });
        }

        return $query;
    }

    /**
     * Resolve date range presets.
     */
    private function resolveDateRange(?string $range = 'this_month', ?string $start = null, ?string $end = null): array
    {
        $now = Carbon::now();
        $range = $range ?: 'this_month';

        if ($range === 'custom' || (! empty($start) || ! empty($end))) {
            $parsedStart = null;
            $parsedEnd = null;

            if (! empty($start)) {
                try {
                    $parsedStart = Carbon::parse($start)->startOfDay();
                } catch (Throwable) {
                    $parsedStart = null;
                }
            }

            if (! empty($end)) {
                try {
                    $parsedEnd = Carbon::parse($end)->endOfDay();
                } catch (Throwable) {
                    $parsedEnd = null;
                }
            }

            return [$parsedStart, $parsedEnd];
        }

        return match ($range) {
            'today' => [$now->copy()->startOfDay(), $now->copy()->endOfDay()],
            'yesterday' => [$now->copy()->subDay()->startOfDay(), $now->copy()->subDay()->endOfDay()],
            'this_week' => [$now->copy()->startOfWeek(), $now->copy()->endOfWeek()],
            'this_month' => [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()],
            'this_year' => [$now->copy()->startOfYear(), $now->copy()->endOfYear()],
            'all' => [null, null],
            default => [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()],
        };
    }

    /**
     * Dedicated Work Orders & Tickets Report unit.
     */
    public function tickets(Request $request): Response
    {
        $tenantId = auth()->user()->tenant_id;
        $centerId = TenancyContext::centerId();

        $range = (string) ($request->input('range') ?: 'this_month');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $dateType = (string) ($request->input('date_type') ?: 'entry_date');
        $status = (string) ($request->input('status') ?: 'all');
        $paymentStatus = (string) ($request->input('payment_status') ?: 'all');
        $customerId = (string) ($request->input('customer_id') ?: 'all');
        $customerType = (string) ($request->input('customer_type') ?: 'all');
        $makeId = (string) ($request->input('make_id') ?: 'all');
        $centerFilter = (string) ($request->input('center_id') ?: 'all');
        $isOverdue = $request->boolean('is_overdue');
        $groupBy = (string) ($request->input('group_by') ?: 'none');
        $search = trim((string) ($request->input('search') ?? ''));

        [$from, $to] = $this->resolveDateRange($range, $startDate, $endDate);

        $query = $this->buildTicketsQuery(
            $tenantId,
            $centerId,
            $from,
            $to,
            $dateType,
            $status,
            $paymentStatus,
            $customerId,
            $customerType,
            $makeId,
            $centerFilter,
            $isOverdue,
            $search
        );

        // KPI calculations
        $kpiQuery = clone $query;
        $totalRecords = (int) (clone $kpiQuery)->count();
        $totalAmount = (float) (clone $kpiQuery)->sum('total_incl_tax');
        $totalTax = (float) (clone $kpiQuery)->sum('total_tax');

        // Subquery for WOs to aggregate linked parts and payments
        $woIdsQuery = (clone $kpiQuery)->select('work_orders.id');

        $totalPartsCost = (float) DB::table('work_order_item_parts')
            ->whereIn('work_order_id', $woIdsQuery)
            ->whereNull('deleted_at')
            ->sum(DB::raw('COALESCE(cost_snapshot, 0) * qty'));

        $totalPaidCollected = (float) DB::table('payments')
            ->whereIn('work_order_id', $woIdsQuery)
            ->whereNull('deleted_at')
            ->whereIn('type', ['payment', 'bad_debt'])
            ->sum('amount');

        $totalRefunded = (float) DB::table('payments')
            ->whereIn('work_order_id', $woIdsQuery)
            ->whereNull('deleted_at')
            ->where('type', 'refund')
            ->sum('amount');

        $totalPaid = $totalPaidCollected - $totalRefunded;

        $totalBadDebt = (float) DB::table('payments')
            ->whereIn('work_order_id', $woIdsQuery)
            ->whereNull('deleted_at')
            ->where('type', 'bad_debt')
            ->sum('amount');

        $totalDiscount = (float) DB::table('work_order_items')
            ->whereIn('work_order_id', $woIdsQuery)
            ->sum('discount_amount');

        $totalRemaining = max(0, $totalAmount - $totalPaid);
        $netAmount = $totalAmount - $totalPartsCost;

        $statusBreakdown = (clone $kpiQuery)
            ->selectRaw('status, count(*) as count, sum(total_incl_tax) as total_amount')
            ->groupBy('status')
            ->get()
            ->map(fn ($r) => [
                'status' => $r->status,
                'count' => (int) $r->count,
                'total' => (float) $r->total_amount,
            ]);

        $relations = [
            'center:id,name',
            'customer:id,name,phone,type,tax_number',
            'vehicle:id,plate_number,make_id,model_id,make_other,model_other,year,odometer',
            'vehicle.make:id,name_ar,name_en',
            'vehicle.model:id,name_ar,name_en',
            'invoice:id,work_order_id,invoice_number,status,total_incl_tax,total_paid',
            'payments:id,work_order_id,amount,type,payment_method,payment_date',
            'parts:id,work_order_id,qty,unit_price,total,cost_snapshot,deleted_at',
        ];

        $tickets = null;
        $groupedTickets = null;

        $mapTicket = function ($wo) {
            $partsCost = (float) $wo->parts->whereNull('deleted_at')->sum(fn ($p) => ($p->cost_snapshot ?? 0) * $p->qty);
            $totalPaid = (float) $wo->total_paid;
            $badDebt = (float) $wo->bad_debt;
            $balance = (float) $wo->balance;
            $totalIncl = (float) $wo->total_incl_tax;
            $net = $totalIncl - $partsCost;

            return [
                'id' => $wo->id,
                'code' => $wo->code,
                'status' => $wo->status,
                'entry_date' => $wo->entry_date ? Carbon::parse($wo->entry_date)->format('Y-m-d') : null,
                'opened_at' => $wo->opened_at ? Carbon::parse($wo->opened_at)->format('Y-m-d H:i') : null,
                'closed_at' => $wo->closed_at ? Carbon::parse($wo->closed_at)->format('Y-m-d H:i') : null,
                'expected_end_date' => $wo->expected_end_date ? Carbon::parse($wo->expected_end_date)->format('Y-m-d') : null,
                'created_at' => $wo->created_at ? Carbon::parse($wo->created_at)->format('Y-m-d H:i') : null,
                'is_overdue' => ! in_array($wo->status, [WorkOrder::STATUS_DONE, WorkOrder::STATUS_CANCELLED]) && $wo->expected_end_date && Carbon::parse($wo->expected_end_date)->isPast(),
                'odometer' => $wo->odometer ?? $wo->vehicle?->odometer,
                'total_excl_tax' => (float) $wo->total_excl_tax,
                'total_tax' => (float) $wo->total_tax,
                'total_incl_tax' => $totalIncl,
                'parts_cost' => $partsCost,
                'net_amount' => $net,
                'total_paid' => $totalPaid,
                'balance' => $balance,
                'bad_debt' => $badDebt,
                'total_discount' => (float) $wo->total_discount,
                'center' => $wo->center ? ['id' => $wo->center->id, 'name' => $wo->center->name] : null,
                'customer' => $wo->customer ? [
                    'id' => $wo->customer->id,
                    'name' => $wo->customer->name,
                    'phone' => $wo->customer->phone,
                    'type' => $wo->customer->type,
                ] : null,
                'vehicle' => $wo->vehicle ? [
                    'id' => $wo->vehicle->id,
                    'plate_number' => $wo->vehicle->plate_number,
                    'make_name' => $wo->vehicle->make?->name_ar ?? $wo->vehicle->make_other ?? '',
                    'model_name' => $wo->vehicle->model?->name_ar ?? $wo->vehicle->model_other ?? '',
                    'year' => $wo->vehicle->year,
                    'odometer' => $wo->odometer ?? $wo->vehicle->odometer,
                ] : null,
                'invoice' => $wo->invoice ? [
                    'id' => $wo->invoice->id,
                    'invoice_number' => $wo->invoice->invoice_number,
                    'status' => $wo->invoice->status,
                    'total_incl_tax' => (float) $wo->invoice->total_incl_tax,
                    'total_paid' => (float) $wo->invoice->total_paid,
                ] : null,
            ];
        };

        if ($groupBy !== 'none') {
            $allTickets = (clone $query)
                ->with($relations)
                ->orderBy('id', 'desc')
                ->limit(2000)
                ->get()
                ->map($mapTicket);

            $grouped = $allTickets->groupBy(function ($ticket) use ($groupBy) {
                return match ($groupBy) {
                    'center' => $ticket['center']['name'] ?? 'المركز الرئيسي',
                    'status' => $ticket['status'] ?? 'other',
                    'customer_type' => $ticket['customer']['type'] ?? 'individual',
                    'make' => $ticket['vehicle']['make_name'] ?: 'أخرى',
                    default => 'all',
                };
            });

            $groupedTickets = $grouped->map(function ($items, $key) {
                return [
                    'group_key' => $key,
                    'group_name' => (string) $key,
                    'total_records' => $items->count(),
                    'total_amount' => (float) $items->sum('total_incl_tax'),
                    'total_paid' => (float) $items->sum('total_paid'),
                    'total_remaining' => (float) $items->sum('balance'),
                    'total_bad_debt' => (float) $items->sum('bad_debt'),
                    'net_amount' => (float) $items->sum('net_amount'),
                    'tickets' => $items->values(),
                ];
            })->values();
        } else {
            $paginated = (clone $query)
                ->with($relations)
                ->orderBy('id', 'desc')
                ->paginate(30)
                ->withQueryString();

            $tickets = $paginated->through($mapTicket);
        }

        $tenant = auth()->user()->tenant;
        $currentCenter = $centerId ? Center::find($centerId) : null;

        $customers = Customer::query()
            ->where('tenant_id', $tenantId)
            ->select('id', 'name', 'phone')
            ->orderBy('name')
            ->limit(150)
            ->get();

        $makes = VehicleMake::query()
            ->select('id', 'name_ar', 'name_en')
            ->orderBy('name_ar')
            ->get();

        $centers = Center::query()
            ->where('tenant_id', $tenantId)
            ->select('id', 'name')
            ->get();

        return Inertia::render('Reports/Tickets', [
            'filters' => [
                'range' => $range,
                'start_date' => $from?->format('Y-m-d') ?? $startDate,
                'end_date' => $to?->format('Y-m-d') ?? $endDate,
                'date_type' => $dateType,
                'status' => $status,
                'payment_status' => $paymentStatus,
                'customer_id' => $customerId,
                'customer_type' => $customerType,
                'make_id' => $makeId,
                'center_id' => $centerFilter,
                'is_overdue' => $isOverdue,
                'group_by' => $groupBy,
                'search' => $search,
            ],
            'kpis' => [
                'total_records' => $totalRecords,
                'total_amount' => $totalAmount,
                'total_tax' => $totalTax,
                'total_parts_cost' => $totalPartsCost,
                'net_amount' => $netAmount,
                'total_paid' => $totalPaid,
                'total_remaining' => $totalRemaining,
                'total_discount' => $totalDiscount,
                'total_bad_debt' => $totalBadDebt,
            ],
            'statusBreakdown' => $statusBreakdown,
            'customers' => $customers,
            'makes' => $makes,
            'centers' => $centers,
            'tickets' => $tickets,
            'groupedTickets' => $groupedTickets,
            'companyInfo' => [
                'name' => $tenant?->name ?? 'مركز صيانة',
                'commercial_register' => $tenant?->cr_number ?? null,
                'tax_number' => $tenant?->tax_number ?? null,
                'address' => $tenant?->address ?? null,
                'phone' => $tenant?->phone ?? null,
                'center_name' => $currentCenter?->name ?? null,
            ],
        ]);
    }

    /**
     * Export Work Orders & Tickets Report as CSV.
     */
    public function exportTickets(Request $request): StreamedResponse
    {
        $tenantId = auth()->user()->tenant_id;
        $centerId = TenancyContext::centerId();

        $range = (string) ($request->input('range') ?: 'this_month');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $dateType = (string) ($request->input('date_type') ?: 'entry_date');
        $status = (string) ($request->input('status') ?: 'all');
        $paymentStatus = (string) ($request->input('payment_status') ?: 'all');
        $customerId = (string) ($request->input('customer_id') ?: 'all');
        $customerType = (string) ($request->input('customer_type') ?: 'all');
        $makeId = (string) ($request->input('make_id') ?: 'all');
        $centerFilter = (string) ($request->input('center_id') ?: 'all');
        $isOverdue = $request->boolean('is_overdue');
        $search = trim((string) ($request->input('search') ?? ''));

        [$from, $to] = $this->resolveDateRange($range, $startDate, $endDate);

        $query = $this->buildTicketsQuery(
            $tenantId,
            $centerId,
            $from,
            $to,
            $dateType,
            $status,
            $paymentStatus,
            $customerId,
            $customerType,
            $makeId,
            $centerFilter,
            $isOverdue,
            $search
        );

        $relations = [
            'center:id,name',
            'customer:id,name,phone,type',
            'vehicle:id,plate_number,make_id,model_id,make_other,model_other,year,odometer',
            'vehicle.make:id,name_ar,name_en',
            'vehicle.model:id,name_ar,name_en',
            'invoice:id,work_order_id,invoice_number,status,total_incl_tax,total_paid',
            'payments:id,work_order_id,amount,type',
            'parts:id,work_order_id,qty,cost_snapshot,deleted_at',
        ];

        $filename = 'tickets_report_'.now()->format('Y-m-d_His').'.csv';

        return response()->streamDownload(function () use ($query, $relations) {
            $handle = fopen('php://output', 'w');
            // Write UTF-8 BOM for proper Arabic display in Excel
            fwrite($handle, "\xEF\xBB\xBF");

            fputcsv($handle, [
                'رقم أمر العمل',
                'الفرع',
                'الحالة',
                'تاريخ الدخول',
                'موعد التسليم المتوقع',
                'تاريخ الإغلاق',
                'اسم العميل',
                'هاتف العميل',
                'تصنيف العميل',
                'المركبة',
                'رقم اللوحة',
                'قراءة العداد',
                'رقم الفاتورة',
                'الإجمالي قبل الضريبة',
                'الضريبة',
                'الإجمالي شامل الضريبة',
                'تكلفة القطع',
                'صافي المبلغ',
                'المدفوع',
                'المتبقي',
                'الديون المعدومة',
            ]);

            (clone $query)
                ->with($relations)
                ->orderBy('id', 'desc')
                ->chunk(250, function ($records) use ($handle) {
                    foreach ($records as $wo) {
                        $partsCost = (float) $wo->parts->whereNull('deleted_at')->sum(fn ($p) => ($p->cost_snapshot ?? 0) * $p->qty);
                        $totalPaid = (float) $wo->total_paid;
                        $balance = (float) $wo->balance;
                        $badDebt = (float) $wo->bad_debt;
                        $net = (float) ($wo->total_incl_tax - $partsCost);

                        $vehicleName = trim(($wo->vehicle?->make?->name_ar ?? $wo->vehicle?->make_other ?? '').' '.($wo->vehicle?->model?->name_ar ?? $wo->vehicle?->model_other ?? '').' '.($wo->vehicle?->year ?? ''));

                        fputcsv($handle, [
                            $wo->code,
                            $wo->center?->name ?? 'المركز الرئيسي',
                            $wo->status,
                            $wo->entry_date ? Carbon::parse($wo->entry_date)->format('Y-m-d') : '',
                            $wo->expected_end_date ? Carbon::parse($wo->expected_end_date)->format('Y-m-d') : '',
                            $wo->closed_at ? Carbon::parse($wo->closed_at)->format('Y-m-d H:i') : '',
                            $wo->customer?->name ?? 'عميل غير مسجل',
                            $wo->customer?->phone ?? '',
                            $wo->customer?->type ?? '',
                            $vehicleName,
                            $wo->vehicle?->plate_number ?? '',
                            $wo->odometer ?? $wo->vehicle?->odometer ?? '',
                            $wo->invoice?->invoice_number ?? '',
                            number_format((float) $wo->total_excl_tax, 2, '.', ''),
                            number_format((float) $wo->total_tax, 2, '.', ''),
                            number_format((float) $wo->total_incl_tax, 2, '.', ''),
                            number_format($partsCost, 2, '.', ''),
                            number_format($net, 2, '.', ''),
                            number_format($totalPaid, 2, '.', ''),
                            number_format($balance, 2, '.', ''),
                            number_format($badDebt, 2, '.', ''),
                        ]);
                    }
                });

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * Build the filtered WorkOrder query.
     */
    private function buildTicketsQuery(
        int $tenantId,
        ?int $centerId,
        ?Carbon $from,
        ?Carbon $to,
        string $dateType,
        string $status,
        string $paymentStatus,
        string $customerId,
        string $customerType,
        string $makeId,
        string $centerFilter,
        bool $isOverdue,
        string $search
    ): Builder {
        // Disable the automatic CenterScoped global scope so the report can filter any accessible center or all centers
        $query = WorkOrder::withoutGlobalScope('center_scoped')
            ->where('tenant_id', $tenantId);

        $user = auth()->user();
        $userCenterIds = $user?->centers()->pluck('centers.id')->toArray() ?? [];

        if ($centerFilter !== 'all' && ! empty($centerFilter)) {
            $query->where('center_id', (int) $centerFilter);
        } else {
            if (! empty($userCenterIds)) {
                $query->whereIn('center_id', $userCenterIds);
            }
        }

        $dateColumn = match ($dateType) {
            'opened_at' => 'opened_at',
            'closed_at' => 'closed_at',
            'expected_end_date' => 'expected_end_date',
            'created_at' => 'created_at',
            default => 'entry_date',
        };

        if ($from && $to) {
            $query->whereBetween($dateColumn, [$from->startOfDay(), $to->endOfDay()]);
        } elseif ($from) {
            $query->where($dateColumn, '>=', $from->startOfDay());
        } elseif ($to) {
            $query->where($dateColumn, '<=', $to->endOfDay());
        }

        if ($status !== 'all' && ! empty($status)) {
            $query->where('status', $status);
        }

        if ($isOverdue) {
            $query->whereNotIn('status', [WorkOrder::STATUS_DONE, WorkOrder::STATUS_CANCELLED])
                ->whereNotNull('expected_end_date')
                ->where('expected_end_date', '<', Carbon::now()->toDateString());
        }

        if ($customerId !== 'all' && ! empty($customerId)) {
            $query->where('customer_id', $customerId);
        }

        if ($customerType !== 'all' && ! empty($customerType)) {
            $query->whereHas('customer', fn ($c) => $c->where('type', $customerType));
        }

        if ($makeId !== 'all' && ! empty($makeId)) {
            $query->whereHas('vehicle', fn ($v) => $v->where('make_id', $makeId));
        }

        if ($paymentStatus === 'paid') {
            $query->where(function ($q) {
                $q->whereHas('invoice', fn ($inv) => $inv->where('status', 'paid'))
                    ->orWhereRaw('(total_incl_tax - (select COALESCE(SUM(CASE WHEN type IN ("payment", "bad_debt") THEN amount WHEN type = "refund" THEN -amount ELSE 0 END), 0) from payments where payments.work_order_id = work_orders.id and payments.deleted_at is null)) <= 0.01 and total_incl_tax > 0');
            });
        } elseif ($paymentStatus === 'unpaid') {
            $query->whereRaw('(select COALESCE(SUM(CASE WHEN type IN ("payment", "bad_debt") THEN amount WHEN type = "refund" THEN -amount ELSE 0 END), 0) from payments where payments.work_order_id = work_orders.id and payments.deleted_at is null) <= 0.01');
        } elseif ($paymentStatus === 'partial') {
            $query->whereRaw('(select COALESCE(SUM(CASE WHEN type IN ("payment", "bad_debt") THEN amount WHEN type = "refund" THEN -amount ELSE 0 END), 0) from payments where payments.work_order_id = work_orders.id and payments.deleted_at is null) > 0.01 and (total_incl_tax - (select COALESCE(SUM(CASE WHEN type IN ("payment", "bad_debt") THEN amount WHEN type = "refund" THEN -amount ELSE 0 END), 0) from payments where payments.work_order_id = work_orders.id and payments.deleted_at is null)) > 0.01');
        }

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                    ->orWhereHas('vehicle', fn ($v) => $v->where('plate_number', 'like', "%{$search}%"))
                    ->orWhereHas('customer', fn ($c) => $c->where('name', 'like', "%{$search}%")->orWhere('phone', 'like', "%{$search}%"))
                    ->orWhereHas('invoice', fn ($i) => $i->where('invoice_number', 'like', "%{$search}%"));
            });
        }

        return $query;
    }

    /**
     * Dedicated Services Report unit (matching wrshah /reports/services).
     */
    public function services(Request $request): Response
    {
        $tenantId = auth()->user()->tenant_id;
        $centerId = TenancyContext::centerId();

        $range = (string) ($request->input('range') ?: 'this_month');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $dateType = (string) ($request->input('date_type') ?: 'due_date');
        $centerFilter = (string) ($request->input('center_id') ?: 'all');
        $departmentId = (string) ($request->input('department_id') ?: 'all');
        $serviceId = (string) ($request->input('service_id') ?: 'all');
        $customerId = (string) ($request->input('customer_id') ?: 'all');
        $customerType = (string) ($request->input('customer_type') ?: 'all');
        $makeId = (string) ($request->input('make_id') ?: 'all');
        $modelId = (string) ($request->input('model_id') ?: 'all');
        $ticketStatus = (string) ($request->input('ticket_status') ?: 'all');
        $serviceStatus = (string) ($request->input('service_status') ?: 'all');
        $isOverdue = $request->boolean('is_overdue');
        $isWarranty = $request->boolean('is_warranty');
        $deductTicketDiscount = $request->boolean('deduct_ticket_discount');
        $groupBy = (string) ($request->input('group_by') ?: 'none');
        $search = trim((string) ($request->input('search') ?? ''));

        [$from, $to] = $this->resolveDateRange($range, $startDate, $endDate);

        $query = $this->buildServicesQuery(
            $tenantId,
            $centerId,
            $from,
            $to,
            $dateType,
            $centerFilter,
            $departmentId,
            $serviceId,
            $customerId,
            $customerType,
            $makeId,
            $modelId,
            $ticketStatus,
            $serviceStatus,
            $isOverdue,
            $isWarranty,
            $search
        );

        // KPI calculations
        $kpiQuery = clone $query;
        $totalRecords = (int) (clone $kpiQuery)->count();
        $totalServicePrice = (float) (clone $kpiQuery)->sum(DB::raw('COALESCE(unit_price, 0) * COALESCE(qty, 1)'));
        $totalServiceDiscount = (float) (clone $kpiQuery)->sum('discount_amount');
        $totalServiceCost = (float) (clone $kpiQuery)->sum('line_total');
        $totalServiceVat = (float) (clone $kpiQuery)->sum('tax_amount');
        $totalAmount = (float) (clone $kpiQuery)->sum(DB::raw('COALESCE(line_total, 0) + COALESCE(tax_amount, 0)'));

        $relations = [
            'center:id,name',
            'workOrder:id,tenant_id,center_id,customer_id,vehicle_id,code,status,entry_date,expected_end_date,opened_at,closed_at,pricing_mode_snapshot,tax_enabled_snapshot,tax_rate_snapshot',
            'workOrder.center:id,name',
            'workOrder.customer:id,name,phone,type',
            'workOrder.vehicle:id,plate_number,make_id,model_id,make_other,model_other,year',
            'workOrder.vehicle.make:id,name_ar,name_en',
            'workOrder.vehicle.model:id,name_ar,name_en',
            'service:id,name_ar,name_en,department_id,base_price,warranty_value,warranty_unit',
            'department:id,name_ar,name_en',
        ];

        $now = Carbon::now();

        $mapService = function (WorkOrderItem $item) use ($now) {
            $wo = $item->workOrder;
            $customer = $wo?->customer;
            $vehicle = $wo?->vehicle;
            $make = $vehicle?->make;
            $model = $vehicle?->model;
            $center = $item->center ?: $wo?->center;
            $service = $item->service;
            $department = $item->department ?: $service?->department;

            $makeName = $make ? ($make->name_ar ?: $make->name_en) : ($vehicle?->make_other ?: '');
            $modelName = $model ? ($model->name_ar ?: $model->name_en) : ($vehicle?->model_other ?: '');

            $qty = (float) ($item->qty ?: 1);
            $unitPrice = (float) $item->unit_price;
            $servicePrice = round($unitPrice * $qty, 2);
            $serviceDiscount = (float) $item->discount_amount;
            $serviceCost = (float) ($item->line_total ?: ($servicePrice - $serviceDiscount));

            $taxAmount = (float) $item->tax_amount;

            if ($taxAmount == 0 && $item->is_taxable && (float) $item->tax_rate_snapshot > 0) {
                $taxAmount = round($serviceCost * ((float) $item->tax_rate_snapshot / 100), 2);
            }
            $totalAmount = round($serviceCost + $taxAmount, 2);

            $isWarranty = (bool) $item->is_warranty;
            $warrantyRemainingDays = 0;

            if ($item->warranty_expires_at && $item->warranty_expires_at->isFuture()) {
                $warrantyRemainingDays = (int) $now->diffInDays($item->warranty_expires_at, false);
                $isWarranty = true;
            }

            $isOverdue = false;

            if ($item->due_date && $item->due_date->isPast() && ! in_array($item->status, [WorkOrderItem::STATUS_COMPLETED, WorkOrderItem::STATUS_CANCELLED])) {
                $isOverdue = true;
            } elseif (! $item->due_date && $wo?->expected_end_date && Carbon::parse($wo->expected_end_date)->isPast() && ! in_array($item->status, [WorkOrderItem::STATUS_COMPLETED, WorkOrderItem::STATUS_CANCELLED])) {
                $isOverdue = true;
            }

            return [
                'id' => $item->id,
                'work_order_id' => $item->work_order_id,
                'ticket_no' => $wo?->code ?? ('#'.$item->work_order_id),
                'ticket_status' => $wo?->status ?? 'unknown',
                'center_id' => $item->center_id,
                'center_name' => $center?->name ?? 'المركز الرئيسي',
                'service_id' => $item->service_id,
                'service_name' => $item->title ?: ($service?->name_ar ?: $service?->name_en ?: 'خدمة صيانة'),
                'department_id' => $item->department_id,
                'department_name' => $department ? ($department->name_ar ?: $department->name_en) : 'عام',
                'status' => $item->status,
                'customer' => [
                    'id' => $customer?->id,
                    'name' => $customer?->name ?? 'عميل غير مسجل',
                    'phone' => $customer?->phone ?? '',
                    'type' => $customer?->type ?? 'individual',
                ],
                'vehicle' => [
                    'make_name' => $makeName,
                    'model_name' => $modelName,
                    'year' => $vehicle?->year,
                    'plate_number' => $vehicle?->plate_number ?? '',
                    'display_name' => trim("{$makeName} {$modelName} ".($vehicle?->year ? "({$vehicle->year})" : '')),
                ],
                'start_date' => $item->started_at?->format('Y-m-d') ?: ($wo?->opened_at?->format('Y-m-d') ?: $item->created_at->format('Y-m-d')),
                'due_date' => $item->due_date?->format('Y-m-d') ?: ($wo?->expected_end_date ? Carbon::parse($wo->expected_end_date)->format('Y-m-d') : null),
                'completed_date' => $item->completed_at?->format('Y-m-d'),
                'qty' => $qty,
                'unit_price' => $unitPrice,
                'service_price' => $servicePrice,
                'service_discount' => $serviceDiscount,
                'service_cost' => $serviceCost,
                'service_vat' => $taxAmount,
                'total_amount' => $totalAmount,
                'is_vat_inclusive' => $wo?->pricing_mode_snapshot === 'inclusive',
                'is_warranty' => $isWarranty,
                'warranty_remaining_days' => $warrantyRemainingDays,
                'is_overdue' => $isOverdue,
            ];
        };

        $groupedServices = null;
        $services = null;

        if ($groupBy !== 'none') {
            $allServices = (clone $query)
                ->with($relations)
                ->orderBy('work_order_items.id', 'desc')
                ->get()
                ->map($mapService);

            $grouped = $allServices->groupBy(function ($item) use ($groupBy) {
                return match ($groupBy) {
                    'department' => $item['department_name'] ?: 'عام',
                    'service' => $item['service_name'] ?: 'خدمة أخرى',
                    'center' => $item['center_name'] ?: 'المركز الرئيسي',
                    'customer_type' => $item['customer']['type'] ?: 'individual',
                    'customer' => $item['customer']['name'] ?: 'عميل عام',
                    'make' => $item['vehicle']['make_name'] ?: 'أخرى',
                    'model' => $item['vehicle']['model_name'] ?: 'أخرى',
                    default => 'all',
                };
            });

            $groupedServices = $grouped->map(function ($items, $key) {
                return [
                    'group_key' => $key,
                    'group_name' => (string) $key,
                    'total_records' => $items->count(),
                    'total_service_price' => (float) $items->sum('service_price'),
                    'total_service_discount' => (float) $items->sum('service_discount'),
                    'total_service_cost' => (float) $items->sum('service_cost'),
                    'total_service_vat' => (float) $items->sum('service_vat'),
                    'total_amount' => (float) $items->sum('total_amount'),
                    'services' => $items->values(),
                ];
            })->values();
        } else {
            $paginated = (clone $query)
                ->with($relations)
                ->orderBy('work_order_items.id', 'desc')
                ->paginate(30)
                ->withQueryString();

            $services = $paginated->through($mapService);
        }

        $departments = Department::withoutGlobalScope('center_scoped')
            ->where('tenant_id', $tenantId)
            ->select('id', 'name_ar', 'name_en')
            ->orderBy('name_ar')
            ->get();

        $servicesList = Service::where('tenant_id', $tenantId)
            ->select('id', 'department_id', 'name_ar', 'name_en', 'base_price')
            ->orderBy('name_ar')
            ->get();

        $customers = Customer::query()
            ->where('tenant_id', $tenantId)
            ->select('id', 'name', 'phone')
            ->orderBy('name')
            ->limit(150)
            ->get();

        $makes = VehicleMake::query()
            ->select('id', 'name_ar', 'name_en')
            ->orderBy('name_ar')
            ->get();

        $models = VehicleModel::query()
            ->select('id', 'make_id', 'name_ar', 'name_en')
            ->orderBy('name_ar')
            ->get();

        $centers = Center::query()
            ->where('tenant_id', $tenantId)
            ->select('id', 'name')
            ->get();

        $tenant = auth()->user()->tenant;
        $currentCenter = $centerId ? Center::find($centerId) : null;

        return Inertia::render('Reports/Services', [
            'filters' => [
                'range' => $range,
                'start_date' => $from?->format('Y-m-d') ?? $startDate,
                'end_date' => $to?->format('Y-m-d') ?? $endDate,
                'date_type' => $dateType,
                'center_id' => $centerFilter,
                'department_id' => $departmentId,
                'service_id' => $serviceId,
                'customer_id' => $customerId,
                'customer_type' => $customerType,
                'make_id' => $makeId,
                'model_id' => $modelId,
                'ticket_status' => $ticketStatus,
                'service_status' => $serviceStatus,
                'is_overdue' => $isOverdue,
                'is_warranty' => $isWarranty,
                'deduct_ticket_discount' => $deductTicketDiscount,
                'group_by' => $groupBy,
                'search' => $search,
            ],
            'kpis' => [
                'total_records' => $totalRecords,
                'total_service_price' => $totalServicePrice,
                'total_service_discount' => $totalServiceDiscount,
                'total_service_cost' => $totalServiceCost,
                'total_service_vat' => $totalServiceVat,
                'total_amount' => $totalAmount,
            ],
            'services' => $services,
            'groupedServices' => $groupedServices,
            'departments' => $departments,
            'servicesList' => $servicesList,
            'customers' => $customers,
            'makes' => $makes,
            'models' => $models,
            'centers' => $centers,
            'meta' => [
                'tenant_name' => $tenant?->name ?? 'مركز صيانة السيارات',
                'tenant_logo' => $tenant?->logo_url,
                'center_name' => $currentCenter?->name ?? 'المركز الرئيسي',
                'printed_at' => now()->format('Y-m-d H:i'),
                'prepared_by' => auth()->user()->name,
            ],
        ]);
    }

    /**
     * Export Services Report as CSV.
     */
    public function exportServices(Request $request): StreamedResponse
    {
        $tenantId = auth()->user()->tenant_id;
        $centerId = TenancyContext::centerId();

        $range = (string) ($request->input('range') ?: 'this_month');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $dateType = (string) ($request->input('date_type') ?: 'due_date');
        $centerFilter = (string) ($request->input('center_id') ?: 'all');
        $departmentId = (string) ($request->input('department_id') ?: 'all');
        $serviceId = (string) ($request->input('service_id') ?: 'all');
        $customerId = (string) ($request->input('customer_id') ?: 'all');
        $customerType = (string) ($request->input('customer_type') ?: 'all');
        $makeId = (string) ($request->input('make_id') ?: 'all');
        $modelId = (string) ($request->input('model_id') ?: 'all');
        $ticketStatus = (string) ($request->input('ticket_status') ?: 'all');
        $serviceStatus = (string) ($request->input('service_status') ?: 'all');
        $isOverdue = $request->boolean('is_overdue');
        $isWarranty = $request->boolean('is_warranty');
        $deductTicketDiscount = $request->boolean('deduct_ticket_discount');
        $search = trim((string) ($request->input('search') ?? ''));

        [$from, $to] = $this->resolveDateRange($range, $startDate, $endDate);

        $query = $this->buildServicesQuery(
            $tenantId,
            $centerId,
            $from,
            $to,
            $dateType,
            $centerFilter,
            $departmentId,
            $serviceId,
            $customerId,
            $customerType,
            $makeId,
            $modelId,
            $ticketStatus,
            $serviceStatus,
            $isOverdue,
            $isWarranty,
            $search
        );

        $relations = [
            'center:id,name',
            'workOrder:id,tenant_id,center_id,customer_id,vehicle_id,code,status,entry_date,expected_end_date,opened_at,closed_at',
            'workOrder.center:id,name',
            'workOrder.customer:id,name,phone,type',
            'workOrder.vehicle:id,plate_number,make_id,model_id,make_other,model_other,year',
            'workOrder.vehicle.make:id,name_ar,name_en',
            'workOrder.vehicle.model:id,name_ar,name_en',
            'service:id,name_ar,name_en,department_id,base_price',
            'department:id,name_ar,name_en',
        ];

        $filename = 'services_report_'.now()->format('Y-m-d_His').'.csv';

        return response()->streamDownload(function () use ($query, $relations) {
            $handle = fopen('php://output', 'w');
            fwrite($handle, "\xEF\xBB\xBF");

            fputcsv($handle, [
                '#',
                'رقم أمر العمل',
                'الفرع',
                'القسم',
                'الخدمة',
                'حالة الخدمة',
                'العميل',
                'جوال العميل',
                'المركبة',
                'اللوحة',
                'تاريخ البدء',
                'تاريخ الاستحقاق',
                'الكمية',
                'سعر الوحدة',
                'السعر الإجمالي',
                'الخصم',
                'الصافي',
                'الضريبة',
                'الإجمالي شامل الضريبة',
                'تحت الضمان',
            ]);

            $rowNumber = 1;
            (clone $query)->with($relations)->orderBy('work_order_items.id', 'desc')->chunk(200, function ($items) use ($handle, &$rowNumber) {
                foreach ($items as $item) {
                    $wo = $item->workOrder;
                    $customer = $wo?->customer;
                    $vehicle = $wo?->vehicle;
                    $make = $vehicle?->make;
                    $model = $vehicle?->model;
                    $center = $item->center ?: $wo?->center;
                    $service = $item->service;
                    $department = $item->department ?: $service?->department;

                    $makeName = $make ? ($make->name_ar ?: $make->name_en) : ($vehicle?->make_other ?: '');
                    $modelName = $model ? ($model->name_ar ?: $model->name_en) : ($vehicle?->model_other ?: '');

                    $qty = (float) ($item->qty ?: 1);
                    $unitPrice = (float) $item->unit_price;
                    $servicePrice = round($unitPrice * $qty, 2);
                    $serviceDiscount = (float) $item->discount_amount;
                    $serviceCost = (float) ($item->line_total ?: ($servicePrice - $serviceDiscount));
                    $taxAmount = (float) $item->tax_amount;
                    $totalAmount = round($serviceCost + $taxAmount, 2);

                    fputcsv($handle, [
                        $rowNumber++,
                        $wo?->code ?? ('#'.$item->work_order_id),
                        $center?->name ?? 'المركز الرئيسي',
                        $department ? ($department->name_ar ?: $department->name_en) : 'عام',
                        $item->title ?: ($service?->name_ar ?: $service?->name_en ?: 'خدمة'),
                        $item->status,
                        $customer?->name ?? '',
                        $customer?->phone ?? '',
                        trim("{$makeName} {$modelName} {$vehicle?->year}"),
                        $vehicle?->plate_number ?? '',
                        $item->started_at?->format('Y-m-d') ?: ($wo?->opened_at?->format('Y-m-d') ?: ''),
                        $item->due_date?->format('Y-m-d') ?: ($wo?->expected_end_date ? Carbon::parse($wo->expected_end_date)->format('Y-m-d') : ''),
                        $qty,
                        $unitPrice,
                        $servicePrice,
                        $serviceDiscount,
                        $serviceCost,
                        $taxAmount,
                        $totalAmount,
                        $item->is_warranty ? 'نعم' : 'لا',
                    ]);
                }
            });

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    /**
     * Build unified query for Services report.
     */
    protected function buildServicesQuery(
        int $tenantId,
        ?int $centerId,
        ?Carbon $from,
        ?Carbon $to,
        string $dateType,
        string $centerFilter,
        string $departmentId,
        string $serviceId,
        string $customerId,
        string $customerType,
        string $makeId,
        string $modelId,
        string $ticketStatus,
        string $serviceStatus,
        bool $isOverdue,
        bool $isWarranty,
        string $search
    ): Builder {
        $query = WorkOrderItem::withoutGlobalScope('center_scoped')
            ->where('work_order_items.tenant_id', $tenantId);

        // Branch filter
        if ($centerFilter !== 'all' && $centerFilter !== '') {
            $query->where('work_order_items.center_id', (int) $centerFilter);
        }

        // Parent work order conditions
        $query->whereHas('workOrder', function (Builder $woQuery) use ($ticketStatus, $customerId, $customerType, $makeId, $modelId, $dateType, $from, $to) {
            $woQuery->withoutGlobalScope('center_scoped');

            if ($ticketStatus !== 'all' && $ticketStatus !== '') {
                $woQuery->where('status', $ticketStatus);
            }

            if ($customerId !== 'all' && $customerId !== '') {
                $woQuery->where('customer_id', (int) $customerId);
            }

            if ($customerType !== 'all' && $customerType !== '') {
                $woQuery->whereHas('customer', fn ($c) => $c->where('type', $customerType));
            }

            if ($makeId !== 'all' && $makeId !== '') {
                $woQuery->whereHas('vehicle', fn ($v) => $v->where('make_id', (int) $makeId));
            }

            if ($modelId !== 'all' && $modelId !== '') {
                $woQuery->whereHas('vehicle', fn ($v) => $v->where('model_id', (int) $modelId));
            }

            if ($dateType === 'entry_date' && $from && $to) {
                $woQuery->whereBetween('entry_date', [$from->toDateString(), $to->toDateString()]);
            }
        });

        // Date type filtering for work_order_items
        if ($from && $to) {
            if ($dateType === 'started_at') {
                $query->whereBetween('work_order_items.started_at', [$from, $to]);
            } elseif ($dateType === 'completed_at') {
                $query->whereBetween('work_order_items.completed_at', [$from, $to]);
            } elseif ($dateType === 'created_at') {
                $query->whereBetween('work_order_items.created_at', [$from, $to]);
            } elseif ($dateType === 'due_date') {
                $query->where(function (Builder $dq) use ($from, $to) {
                    $dq->whereBetween('work_order_items.due_date', [$from->toDateString(), $to->toDateString()])
                        ->orWhere(function (Builder $sub) use ($from, $to) {
                            $sub->whereNull('work_order_items.due_date')
                                ->whereBetween('work_order_items.created_at', [$from, $to]);
                        });
                });
            }
        }

        // Department filter
        if ($departmentId !== 'all' && $departmentId !== '') {
            $query->where('work_order_items.department_id', (int) $departmentId);
        }

        // Service filter
        if ($serviceId !== 'all' && $serviceId !== '') {
            $query->where('work_order_items.service_id', (int) $serviceId);
        }

        // Service status filter
        if ($serviceStatus !== 'all' && $serviceStatus !== '') {
            $query->where('work_order_items.status', $serviceStatus);
        }

        // Overdue filter
        if ($isOverdue) {
            $query->where(function (Builder $oq) {
                $oq->where('work_order_items.due_date', '<', Carbon::now()->toDateString())
                    ->whereNotIn('work_order_items.status', [WorkOrderItem::STATUS_COMPLETED, WorkOrderItem::STATUS_CANCELLED]);
            });
        }

        // Warranty filter
        if ($isWarranty) {
            $query->where(function (Builder $wq) {
                $wq->where('work_order_items.is_warranty', true)
                    ->orWhere('work_order_items.warranty_expires_at', '>', Carbon::now());
            });
        }

        // Search text
        if ($search !== '') {
            $query->where(function (Builder $sq) use ($search) {
                $sq->where('work_order_items.title', 'like', "%{$search}%")
                    ->orWhereHas('workOrder', function (Builder $woQuery) use ($search) {
                        $woQuery->where('code', 'like', "%{$search}%")
                            ->orWhereHas('customer', fn ($c) => $c->where('name', 'like', "%{$search}%")->orWhere('phone', 'like', "%{$search}%"))
                            ->orWhereHas('vehicle', fn ($v) => $v->where('plate_number', 'like', "%{$search}%"));
                    });
            });
        }

        return $query;
    }

    /**
     * Dedicated Customers & Accounts Report unit.
     */
    public function customers(Request $request): Response
    {
        $tenantId = auth()->user()->tenant_id;
        $centerId = TenancyContext::centerId();

        $range = (string) ($request->input('range') ?: 'this_month');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $dateType = (string) ($request->input('date_type') ?: 'entry_date');
        $centerFilter = (string) ($request->input('center_id') ?: 'all');
        $customerId = (string) ($request->input('customer_id') ?: 'all');
        $customerType = (string) ($request->input('customer_type') ?: 'all');
        $makeId = (string) ($request->input('make_id') ?: 'all');
        $modelId = (string) ($request->input('model_id') ?: 'all');
        $paymentStatus = (string) ($request->input('payment_status') ?: 'all');
        $groupBy = (string) ($request->input('group_by') ?: 'none');
        $search = trim((string) ($request->input('search') ?? ''));

        [$from, $to] = $this->resolveDateRange($range, $startDate, $endDate);

        $query = $this->buildCustomersQuery(
            $tenantId,
            $centerId,
            $from,
            $to,
            $dateType,
            $centerFilter,
            $customerId,
            $customerType,
            $makeId,
            $modelId,
            $paymentStatus,
            $search
        );

        // KPI computations
        $kpiQuery = clone $query;
        $totalRecords = (int) (clone $kpiQuery)->count();
        $totalPaymentDue = (float) (clone $kpiQuery)->sum('total_incl_tax');

        $woIdsQuery = (clone $kpiQuery)->select('work_orders.id');

        $totalPaidCollected = (float) DB::table('payments')
            ->whereIn('work_order_id', $woIdsQuery)
            ->whereNull('deleted_at')
            ->whereIn('type', ['payment', 'bad_debt'])
            ->sum('amount');

        $totalRefunded = (float) DB::table('payments')
            ->whereIn('work_order_id', $woIdsQuery)
            ->whereNull('deleted_at')
            ->where('type', 'refund')
            ->sum('amount');

        $totalAmountPaid = $totalPaidCollected - $totalRefunded;

        $totalBadDebt = (float) DB::table('payments')
            ->whereIn('work_order_id', $woIdsQuery)
            ->whereNull('deleted_at')
            ->where('type', 'bad_debt')
            ->sum('amount');

        $itemsDiscount = (float) DB::table('work_order_items')
            ->whereIn('work_order_id', $woIdsQuery)
            ->sum('discount_amount');

        $partsDiscount = (float) DB::table('work_order_item_parts')
            ->whereIn('work_order_id', $woIdsQuery)
            ->whereNull('deleted_at')
            ->sum('discount');

        $totalAmountAdjusted = $itemsDiscount + $partsDiscount;
        $totalPaymentRemaining = max(0, $totalPaymentDue - $totalAmountPaid);

        $relations = [
            'center:id,name',
            'customer:id,name,phone,type,tax_number',
            'vehicle:id,plate_number,make_id,model_id,make_other,model_other,year,odometer',
            'vehicle.make:id,name_ar,name_en',
            'vehicle.model:id,name_ar,name_en',
            'invoice:id,work_order_id,invoice_number,status,total_incl_tax,total_paid',
            'payments:id,work_order_id,amount,type,payment_method,payment_date',
            'items:id,work_order_id,discount_amount,status',
            'parts:id,work_order_id,discount,status,deleted_at',
        ];

        $records = null;
        $groupedRecords = null;

        if ($groupBy !== 'none') {
            $allRecords = (clone $query)->with($relations)->orderByDesc('id')->get();

            $grouped = match ($groupBy) {
                'customer_type' => $allRecords->groupBy(fn ($wo) => $wo->customer?->type ?: 'individual'),
                'customer' => $allRecords->groupBy(fn ($wo) => $wo->customer_id ? (string) $wo->customer_id : 'unregistered'),
                'center' => $allRecords->groupBy(fn ($wo) => $wo->center_id ? (string) $wo->center_id : 'default'),
                default => collect([$allRecords]),
            };

            $groupedRecords = $grouped->map(function ($items, $key) use ($groupBy) {
                $groupDue = (float) $items->sum('total_incl_tax');
                $groupPaid = (float) $items->sum(fn ($wo) => $wo->total_paid);
                $groupAdjusted = (float) $items->sum(fn ($wo) => $wo->total_discount);
                $groupRemaining = max(0, $groupDue - $groupPaid);
                $groupBadDebt = (float) $items->sum(fn ($wo) => $wo->bad_debt);

                $label = match ($groupBy) {
                    'customer_type' => match ($key) {
                        'individual' => __('reports.customers_report.type_individual'),
                        'company' => __('reports.customers_report.type_company'),
                        'government' => __('reports.customers_report.type_government'),
                        'vip' => __('reports.customers_report.type_vip'),
                        default => (string) $key,
                    },
                    'customer' => $items->first()?->customer?->name ?? 'عميل غير مسجل',
                    'center' => $items->first()?->center?->name ?? 'المركز الرئيسي',
                    default => (string) $key,
                };

                return [
                    'group_key' => (string) $key,
                    'group_label' => $label,
                    'total_records' => $items->count(),
                    'total_payment_due' => $groupDue,
                    'total_amount_paid' => $groupPaid,
                    'total_amount_adjusted' => $groupAdjusted,
                    'total_payment_remaining' => $groupRemaining,
                    'total_bad_debt' => $groupBadDebt,
                    'items' => $items->map(fn ($wo) => $this->transformCustomerReportRow($wo))->values(),
                ];
            })->values();
        } else {
            $paginated = (clone $query)->with($relations)->orderByDesc('id')->paginate(25)->withQueryString();
            $paginated->getCollection()->transform(fn ($wo) => $this->transformCustomerReportRow($wo));
            $records = $paginated;
        }

        // Filter Options
        $centers = Center::where('tenant_id', $tenantId)->select('id', 'name')->orderBy('name')->get();
        $customers = Customer::where('tenant_id', $tenantId)->select('id', 'name', 'phone', 'type')->orderBy('name')->get();
        $makes = VehicleMake::select('id', 'name_ar', 'name_en')->orderBy('name_ar')->get();
        $models = ($makeId !== 'all' && ! empty($makeId))
            ? VehicleModel::where('make_id', (int) $makeId)->select('id', 'make_id', 'name_ar', 'name_en')->orderBy('name_ar')->get()
            : collect();

        $tenant = Tenant::find($tenantId);
        $currentCenter = $centerId ? Center::find($centerId) : null;

        return Inertia::render('Reports/Customers', [
            'filters' => [
                'range' => $range,
                'start_date' => $from?->format('Y-m-d') ?? $startDate,
                'end_date' => $to?->format('Y-m-d') ?? $endDate,
                'date_type' => $dateType,
                'center_id' => $centerFilter,
                'customer_id' => $customerId,
                'customer_type' => $customerType,
                'make_id' => $makeId,
                'model_id' => $modelId,
                'payment_status' => $paymentStatus,
                'group_by' => $groupBy,
                'search' => $search,
            ],
            'kpis' => [
                'total_records' => $totalRecords,
                'total_payment_due' => $totalPaymentDue,
                'total_amount_paid' => $totalAmountPaid,
                'total_amount_adjusted' => $totalAmountAdjusted,
                'total_payment_remaining' => $totalPaymentRemaining,
                'total_bad_debt' => $totalBadDebt,
            ],
            'records' => $records,
            'groupedRecords' => $groupedRecords,
            'centers' => $centers,
            'customers' => $customers,
            'makes' => $makes,
            'models' => $models,
            'companyInfo' => [
                'name' => $tenant?->name ?? 'مركز صيانة',
                'commercial_register' => $tenant?->cr_number ?? null,
                'tax_number' => $tenant?->tax_number ?? null,
                'address' => $tenant?->address ?? null,
                'phone' => $tenant?->phone ?? null,
                'center_name' => $currentCenter?->name ?? null,
            ],
        ]);
    }

    /**
     * Export Customers Report as CSV.
     */
    public function exportCustomers(Request $request): StreamedResponse
    {
        $tenantId = auth()->user()->tenant_id;
        $centerId = TenancyContext::centerId();

        $range = (string) ($request->input('range') ?: 'this_month');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $dateType = (string) ($request->input('date_type') ?: 'entry_date');
        $centerFilter = (string) ($request->input('center_id') ?: 'all');
        $customerId = (string) ($request->input('customer_id') ?: 'all');
        $customerType = (string) ($request->input('customer_type') ?: 'all');
        $makeId = (string) ($request->input('make_id') ?: 'all');
        $modelId = (string) ($request->input('model_id') ?: 'all');
        $paymentStatus = (string) ($request->input('payment_status') ?: 'all');
        $search = trim((string) ($request->input('search') ?? ''));

        [$from, $to] = $this->resolveDateRange($range, $startDate, $endDate);

        $query = $this->buildCustomersQuery(
            $tenantId,
            $centerId,
            $from,
            $to,
            $dateType,
            $centerFilter,
            $customerId,
            $customerType,
            $makeId,
            $modelId,
            $paymentStatus,
            $search
        );

        $relations = [
            'center:id,name',
            'customer:id,name,phone,type,tax_number',
            'vehicle:id,plate_number,make_id,model_id,make_other,model_other,year,odometer',
            'vehicle.make:id,name_ar,name_en',
            'vehicle.model:id,name_ar,name_en',
            'invoice:id,work_order_id,invoice_number,status,total_incl_tax,total_paid',
            'payments:id,work_order_id,amount,type,payment_method,payment_date',
            'items:id,work_order_id,discount_amount,status',
            'parts:id,work_order_id,discount,status,deleted_at',
        ];

        $filename = 'customers_report_'.Carbon::now()->format('Y-m-d_His').'.csv';

        return response()->streamDownload(function () use ($query, $relations) {
            $handle = fopen('php://output', 'w');
            fwrite($handle, "\xEF\xBB\xBF");

            fputcsv($handle, [
                'رقم المرجع / التذكرة',
                'الفرع',
                'حالة أمر العمل',
                'تاريخ الدخول',
                'اسم العميل',
                'هاتف العميل',
                'تصنيف العميل',
                'المركبة',
                'رقم اللوحة',
                'قراءة العداد',
                'رقم الفاتورة',
                'المستحق (شامل الضريبة)',
                'المدفوع',
                'التسويات (الخصومات)',
                'المتبقي',
                'ديون معدومة',
                'حالة السداد',
            ]);

            (clone $query)
                ->with($relations)
                ->orderByDesc('id')
                ->chunk(250, function ($records) use ($handle) {
                    foreach ($records as $wo) {
                        $row = $this->transformCustomerReportRow($wo);

                        $statusLabel = match ($row['payment_status']) {
                            'paid' => 'مسدد بالكامل',
                            'partial' => 'مسدد جزئياً',
                            default => 'غير مسدد',
                        };

                        fputcsv($handle, [
                            $row['code'],
                            $row['center_name'],
                            $row['status'],
                            $row['entry_date'],
                            $row['customer']['name'],
                            $row['customer']['phone'],
                            $row['customer']['type'],
                            $row['vehicle']['name'],
                            $row['vehicle']['plate_number'],
                            $row['vehicle']['odometer'] ?? '',
                            $row['invoice']['invoice_number'] ?? '',
                            number_format($row['payment_due'], 2, '.', ''),
                            number_format($row['amount_paid'], 2, '.', ''),
                            number_format($row['amount_adjusted'], 2, '.', ''),
                            number_format($row['payment_remaining'], 2, '.', ''),
                            number_format($row['bad_debt'], 2, '.', ''),
                            $statusLabel,
                        ]);
                    }
                });

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * Build the filtered WorkOrder query for Customers Report.
     */
    private function buildCustomersQuery(
        int $tenantId,
        ?int $centerId,
        ?Carbon $from,
        ?Carbon $to,
        string $dateType,
        string $centerFilter,
        string $customerId,
        string $customerType,
        string $makeId,
        string $modelId,
        string $paymentStatus,
        string $search
    ): Builder {
        $query = WorkOrder::withoutGlobalScope('center_scoped')
            ->where('tenant_id', $tenantId);

        $user = auth()->user();
        $userCenterIds = $user?->centers()->pluck('centers.id')->toArray() ?? [];

        if ($centerFilter !== 'all' && ! empty($centerFilter)) {
            $query->where('center_id', (int) $centerFilter);
        } else {
            if (! empty($userCenterIds)) {
                $query->whereIn('center_id', $userCenterIds);
            }
        }

        $dateColumn = match ($dateType) {
            'closed_at' => 'closed_at',
            'created_at' => 'created_at',
            default => 'entry_date',
        };

        if ($from && $to) {
            if ($dateColumn === 'entry_date') {
                $query->where(function (Builder $dq) use ($from, $to) {
                    $dq->whereBetween('entry_date', [$from->toDateString(), $to->toDateString()])
                        ->orWhere(function (Builder $sub) use ($from, $to) {
                            $sub->whereNull('entry_date')
                                ->whereBetween('created_at', [$from, $to]);
                        });
                });
            } elseif ($dateColumn === 'closed_at') {
                $query->whereBetween('closed_at', [$from, $to]);
            } else {
                $query->whereBetween('created_at', [$from, $to]);
            }
        }

        if ($customerId !== 'all' && ! empty($customerId)) {
            $query->where('customer_id', (int) $customerId);
        }

        if ($customerType !== 'all' && ! empty($customerType)) {
            $query->whereHas('customer', function (Builder $cq) use ($customerType) {
                $cq->where('type', $customerType);
            });
        }

        if ($makeId !== 'all' && ! empty($makeId)) {
            $query->whereHas('vehicle', function (Builder $vq) use ($makeId) {
                $vq->where('make_id', (int) $makeId);
            });
        }

        if ($modelId !== 'all' && ! empty($modelId)) {
            $query->whereHas('vehicle', function (Builder $vq) use ($modelId) {
                $vq->where('model_id', (int) $modelId);
            });
        }

        if ($paymentStatus === 'paid') {
            $query->whereRaw('(COALESCE(work_orders.total_incl_tax, 0) - COALESCE((SELECT SUM(CASE WHEN type IN ("payment", "bad_debt") THEN amount WHEN type = "refund" THEN -amount ELSE 0 END) FROM payments WHERE payments.work_order_id = work_orders.id AND payments.deleted_at IS NULL), 0)) <= 0.01');
        } elseif ($paymentStatus === 'unpaid') {
            $query->whereRaw('COALESCE((SELECT SUM(CASE WHEN type IN ("payment", "bad_debt") THEN amount WHEN type = "refund" THEN -amount ELSE 0 END) FROM payments WHERE payments.work_order_id = work_orders.id AND payments.deleted_at IS NULL), 0) <= 0');
        } elseif ($paymentStatus === 'partial') {
            $query->whereRaw('(COALESCE((SELECT SUM(CASE WHEN type IN ("payment", "bad_debt") THEN amount WHEN type = "refund" THEN -amount ELSE 0 END) FROM payments WHERE payments.work_order_id = work_orders.id AND payments.deleted_at IS NULL), 0) > 0 AND (COALESCE(work_orders.total_incl_tax, 0) - COALESCE((SELECT SUM(CASE WHEN type IN ("payment", "bad_debt") THEN amount WHEN type = "refund" THEN -amount ELSE 0 END) FROM payments WHERE payments.work_order_id = work_orders.id AND payments.deleted_at IS NULL), 0)) > 0.01)');
        }

        if ($search !== '') {
            $query->where(function (Builder $sq) use ($search) {
                $sq->where('code', 'like', "%{$search}%")
                    ->orWhereHas('customer', function (Builder $cq) use ($search) {
                        $cq->where('name', 'like', "%{$search}%")
                            ->orWhere('phone', 'like', "%{$search}%")
                            ->orWhere('tax_number', 'like', "%{$search}%");
                    })
                    ->orWhereHas('vehicle', function (Builder $vq) use ($search) {
                        $vq->where('plate_number', 'like', "%{$search}%");
                    })
                    ->orWhereHas('invoice', function (Builder $iq) use ($search) {
                        $iq->where('invoice_number', 'like', "%{$search}%");
                    });
            });
        }

        return $query;
    }

    /**
     * Transform a WorkOrder into a Customer Report row array.
     */
    private function transformCustomerReportRow(WorkOrder $wo): array
    {
        $due = (float) $wo->total_incl_tax;
        $paid = (float) $wo->total_paid;
        $adjusted = (float) $wo->total_discount;
        $remaining = max(0, $due - $paid);
        $badDebt = (float) $wo->bad_debt;

        $paymentStatus = 'unpaid';

        if ($paid >= $due - 0.01 && $due > 0) {
            $paymentStatus = 'paid';
        } elseif ($paid > 0) {
            $paymentStatus = 'partial';
        }

        $isOverdue = false;

        if ($wo->expected_end_date && ! in_array($wo->status, [WorkOrder::STATUS_DONE, WorkOrder::STATUS_CANCELLED])) {
            $isOverdue = Carbon::parse($wo->expected_end_date)->isPast();
        }

        $vehicleName = trim(($wo->vehicle?->make?->name_ar ?? $wo->vehicle?->make_other ?? '').' '.($wo->vehicle?->model?->name_ar ?? $wo->vehicle?->model_other ?? '').' '.($wo->vehicle?->year ?? ''));

        return [
            'id' => $wo->id,
            'code' => $wo->code,
            'status' => $wo->status,
            'center_id' => $wo->center_id,
            'center_name' => $wo->center?->name ?? 'المركز الرئيسي',
            'is_overdue' => $isOverdue,
            'entry_date' => $wo->entry_date ? Carbon::parse($wo->entry_date)->format('Y-m-d') : ($wo->created_at ? $wo->created_at->format('Y-m-d') : ''),
            'expected_end_date' => $wo->expected_end_date ? Carbon::parse($wo->expected_end_date)->format('Y-m-d') : null,
            'closed_at' => $wo->closed_at ? Carbon::parse($wo->closed_at)->format('Y-m-d H:i') : null,
            'customer' => [
                'id' => $wo->customer?->id,
                'name' => $wo->customer?->name ?? 'عميل غير مسجل',
                'phone' => $wo->customer?->phone ?? '',
                'type' => $wo->customer?->type ?? 'individual',
                'tax_number' => $wo->customer?->tax_number ?? '',
            ],
            'vehicle' => [
                'id' => $wo->vehicle?->id,
                'name' => $vehicleName,
                'plate_number' => $wo->vehicle?->plate_number ?? '',
                'year' => $wo->vehicle?->year,
                'odometer' => $wo->odometer ?? $wo->vehicle?->odometer ?? null,
            ],
            'invoice' => $wo->invoice ? [
                'id' => $wo->invoice->id,
                'invoice_number' => $wo->invoice->invoice_number,
                'status' => $wo->invoice->status,
            ] : null,
            'payment_due' => $due,
            'amount_paid' => $paid,
            'amount_adjusted' => $adjusted,
            'payment_remaining' => $remaining,
            'bad_debt' => $badDebt,
            'payment_status' => $paymentStatus,
        ];
    }
}
