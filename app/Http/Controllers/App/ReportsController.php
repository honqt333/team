<?php

declare(strict_types=1);

namespace App\Http\Controllers\App;

use App\Enums\InvoiceType;
use App\Enums\PaymentType;
use App\Http\Controllers\Controller;
use App\Models\Center;
use App\Models\Customer;
use App\Models\Department;
use App\Models\GrnItem;
use App\Models\HR\Employee;
use App\Models\HR\JobTitle;
use App\Models\InventoryCategory;
use App\Models\InventoryMove;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseReturn;
use App\Models\PurchaseReturnInvoice;
use App\Models\Service;
use App\Models\Supplier;
use App\Models\Tenant;
use App\Models\User;
use App\Models\VehicleMake;
use App\Models\VehicleModel;
use App\Models\Warehouse;
use App\Models\WorkOrder;
use App\Models\WorkOrderItem;
use App\Models\WorkOrderItemPart;
use App\Support\TenancyContext;
use Carbon\Carbon;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
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
        // The report exposes the financial records of the tenant, so it
        // needs the same ability as reading a payment. Without this
        // check any signed-in user of the app could open the report and
        // the CSV export and read the amounts they collected.
        $this->authorize('viewAny', Payment::class);

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
        $centerFilter = $this->resolveCenterFilter($request, $centerId);
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
        // The export carries the same financial records as the report
        // itself, so it carries the same gate.
        $this->authorize('viewAny', Payment::class);

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
        $centerFilter = $this->resolveCenterFilter($request, $centerId);
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

        if ($user->is_system_admin || $user->hasRole('super_admin')) {
            return null;
        }

        $centerIds = $user->centers()
            ->pluck('centers.id')
            ->map(fn ($id) => (int) $id)
            ->all();

        if ($centerIds === []) {
            if ($user->current_center_id) {
                return [(int) $user->current_center_id];
            }

            return null;
        }

        return array_values(array_unique($centerIds));
    }

    /**
     * Resolve the center filter:
     * - Explicit center request parameter takes precedence
     * - Defaults to currently active center context (TenancyContext::centerId())
     * - Falls back to 'all' if no center context exists
     */
    private function resolveCenterFilter(Request $request, ?int $activeCenterId): string
    {
        if ($request->has('center_id') && $request->input('center_id') !== null && $request->input('center_id') !== '') {
            return (string) $request->input('center_id');
        }

        if ($activeCenterId !== null) {
            return (string) $activeCenterId;
        }

        return 'all';
    }

    /**
     * Resolve the collection of centers accessible to the signed-in user.
     */
    private function resolveAccessibleCenters(int $tenantId)
    {
        $accessibleCenterIds = $this->accessibleCenterIds();

        return Center::query()
            ->where('tenant_id', $tenantId)
            ->when($accessibleCenterIds !== null, fn ($q) => $q->whereIn('id', $accessibleCenterIds))
            ->select('id', 'name')
            ->orderBy('name')
            ->get();
    }

    /**
     * Resolve company info for reports, aligning branch name to filtered/current center.
     */
    private function resolveReportCompanyInfo(Tenant $tenant, string $centerFilter, ?int $activeCenterId): array
    {
        $targetCenterId = ($centerFilter !== 'all' && is_numeric($centerFilter))
            ? (int) $centerFilter
            : $activeCenterId;

        $targetCenter = $targetCenterId ? Center::find($targetCenterId) : null;

        return [
            'name' => $tenant->legal_name ?: $tenant->name,
            'commercial_register' => $tenant->cr_number ?? null,
            'cr_number' => $tenant->cr_number ?? '',
            'tax_number' => $tenant->taxSettings?->tax_number ?? $tenant->tax_number ?? $tenant->vat_number ?? null,
            'vat_number' => $tenant->taxSettings?->tax_number ?? $tenant->tax_number ?? $tenant->vat_number ?? '',
            'address' => $tenant->address ?? null,
            'phone' => $tenant->phone ?? null,
            'logo_path' => $tenant->logo_path ?? '',
            'center_name' => $targetCenter?->name ?? '',
        ];
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

        if ($range === 'all' || $range === 'all_time') {
            return [null, null];
        }

        if ($range === 'custom' || (empty($range) && (! empty($start) || ! empty($end)))) {
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
            'all', 'all_time' => [null, null],
            default => [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()],
        };
    }

    /**
     * Dedicated Invoices Report unit.
     */
    public function invoices(Request $request): Response
    {
        $this->authorize('viewAny', Invoice::class);

        $tenantId = auth()->user()->tenant_id;
        $centerId = TenancyContext::centerId();

        $range = (string) ($request->input('range') ?: 'this_month');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $dateType = (string) ($request->input('date_type') ?: 'issue_date');
        $type = (string) ($request->input('type') ?: 'all');
        $subtype = (string) ($request->input('subtype') ?: 'all');
        $paymentStatus = (string) ($request->input('payment_status') ?: 'all');
        $customerId = (string) ($request->input('customer_id') ?: 'all');
        $customerType = (string) ($request->input('customer_type') ?: 'all');
        $centerFilter = $this->resolveCenterFilter($request, $centerId);
        $isOverdue = $request->boolean('is_overdue');
        $groupBy = (string) ($request->input('group_by') ?: 'none');
        $search = trim((string) ($request->input('search') ?? ''));

        [$from, $to] = $this->resolveDateRange($range, $startDate, $endDate);

        $query = $this->buildInvoicesQuery(
            $tenantId,
            $centerId,
            $from,
            $to,
            $dateType,
            $type,
            $subtype,
            $paymentStatus,
            $customerId,
            $customerType,
            $centerFilter,
            $isOverdue,
            $search
        );

        // Aggregate KPIs on filtered dataset
        $kpiQuery = clone $query;
        $totalRecords = (int) (clone $kpiQuery)->count();
        $totalSubtotal = (float) (clone $kpiQuery)->sum('total_excl_tax');
        $totalVat = (float) (clone $kpiQuery)->sum('total_tax');
        $totalInvoiceAmount = (float) (clone $kpiQuery)->sum('total_incl_tax');
        $totalPaid = (float) (clone $kpiQuery)->sum('total_paid');
        $totalPaymentRemaining = (float) ((clone $kpiQuery)->selectRaw('SUM(CASE WHEN (total_incl_tax - total_paid) > 0 THEN (total_incl_tax - total_paid) ELSE 0 END) as rem')->value('rem') ?? 0);

        $totalDiscount = (float) DB::table('invoice_lines')
            ->whereIn('invoice_id', (clone $kpiQuery)->select('id'))
            ->sum('discount_amount');

        $relations = [
            'center:id,name',
            'customer:id,name,phone,type,tax_number',
            'workOrder:id,code,vehicle_id',
            'workOrder.vehicle:id,plate_number,make_id,model_id,make_other,model_other,year',
            'workOrder.vehicle.make:id,name_ar,name_en,logo_path',
            'workOrder.vehicle.model:id,name_ar,name_en',
        ];

        $mapInvoice = function (Invoice $inv) {
            $discount = (float) ($inv->total_discount ?? 0);
            $remaining = max(0, (float) $inv->total_incl_tax - (float) $inv->total_paid);
            $isOverdue = $inv->due_date && $inv->due_date->isPast() && $inv->payment_status !== 'paid';

            return [
                'id' => $inv->id,
                'invoice_number' => $inv->invoice_number,
                'type' => $inv->type instanceof InvoiceType ? $inv->type->value : (string) $inv->type,
                'subtype' => $inv->subtype,
                'status' => $inv->status,
                'payment_status' => $inv->payment_status,
                'payment_status_label' => $inv->payment_status_label,
                'issue_date' => $inv->issue_date?->format('Y-m-d H:i') ?? $inv->created_at?->format('Y-m-d H:i'),
                'due_date' => $inv->due_date?->format('Y-m-d'),
                'supply_date' => $inv->supply_date?->format('Y-m-d'),
                'is_overdue' => $isOverdue,
                'center' => $inv->center ? [
                    'id' => $inv->center->id,
                    'name' => $inv->center->name,
                ] : null,
                'customer' => $inv->customer ? [
                    'id' => $inv->customer->id,
                    'name' => $inv->customer->name,
                    'phone' => $inv->customer->phone,
                    'type' => $inv->customer->type,
                    'tax_number' => $inv->customer->tax_number,
                ] : [
                    'name' => $inv->customer_name_snapshot ?? 'عميل غير مسجل',
                    'phone' => null,
                    'type' => 'individual',
                    'tax_number' => $inv->customer_vat_snapshot,
                ],
                'work_order' => $inv->workOrder ? [
                    'id' => $inv->workOrder->id,
                    'code' => $inv->workOrder->code,
                    'vehicle' => $inv->workOrder->vehicle ? [
                        'id' => $inv->workOrder->vehicle->id,
                        'plate_number' => $inv->workOrder->vehicle->plate_number,
                        'make' => $inv->workOrder->vehicle->make?->name_ar ?? $inv->workOrder->vehicle->make_other,
                        'model' => $inv->workOrder->vehicle->model?->name_ar ?? $inv->workOrder->vehicle->model_other,
                        'year' => $inv->workOrder->vehicle->year,
                    ] : null,
                ] : null,
                'total_excl_tax' => (float) $inv->total_excl_tax,
                'total_discount' => $discount,
                'total_tax' => (float) $inv->total_tax,
                'total_incl_tax' => (float) $inv->total_incl_tax,
                'total_paid' => (float) $inv->total_paid,
                'remaining' => $remaining,
            ];
        };

        $invoices = null;
        $groupedInvoices = null;

        if ($groupBy !== 'none') {
            $allInvoices = (clone $query)
                ->with($relations)
                ->withSum('lines as total_discount', 'discount_amount')
                ->orderBy('issue_date', 'desc')
                ->limit(2000)
                ->get()
                ->map($mapInvoice);

            $grouped = $allInvoices->groupBy(function ($inv) use ($groupBy) {
                return match ($groupBy) {
                    'center' => $inv['center']['name'] ?? 'المركز الرئيسي',
                    'customer' => $inv['customer']['name'] ?? 'عميل غير محدد',
                    'customer_type' => ($inv['customer']['type'] ?? '') === 'company' ? 'شركة / منشأة' : 'فرد',
                    'payment_status' => match ($inv['payment_status']) {
                        'paid' => 'مدفوع بالكامل',
                        'partial' => 'مدفوع جزئياً',
                        'unpaid' => 'غير مدفوع',
                        default => 'أخرى',
                    },
                    'type' => match ($inv['type']) {
                        'credit_note' => 'إشعار دائن',
                        'debit_note' => 'إشعار مدين',
                        default => 'فاتورة ضريبية',
                    },
                    default => 'all',
                };
            });

            $groupedInvoices = $grouped->map(function ($items, $key) {
                return [
                    'group_key' => $key,
                    'group_name' => (string) $key,
                    'total_records' => $items->count(),
                    'total_subtotal' => (float) $items->sum('total_excl_tax'),
                    'total_discount' => (float) $items->sum('total_discount'),
                    'total_vat' => (float) $items->sum('total_tax'),
                    'total_invoice_amount' => (float) $items->sum('total_incl_tax'),
                    'total_paid' => (float) $items->sum('total_paid'),
                    'total_payment_remaining' => (float) $items->sum('remaining'),
                    'items' => $items->values(),
                ];
            })->values();
        } else {
            $paginated = (clone $query)
                ->with($relations)
                ->withSum('lines as total_discount', 'discount_amount')
                ->orderBy('issue_date', 'desc')
                ->paginate(25)
                ->withQueryString();

            $invoices = $paginated->through($mapInvoice);
        }

        $customers = Customer::query()
            ->where('tenant_id', $tenantId)
            ->select('id', 'name', 'phone', 'type')
            ->orderBy('name')
            ->limit(300)
            ->get();

        $centers = $this->resolveAccessibleCenters($tenantId);
        $tenant = auth()->user()->tenant;

        return Inertia::render('Reports/Invoices', [
            'filters' => [
                'range' => $range,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'date_type' => $dateType,
                'type' => $type,
                'subtype' => $subtype,
                'payment_status' => $paymentStatus,
                'customer_id' => $customerId,
                'customer_type' => $customerType,
                'center_id' => $centerFilter,
                'is_overdue' => $isOverdue,
                'group_by' => $groupBy,
                'search' => $search,
            ],
            'kpis' => [
                'total_records' => $totalRecords,
                'total_subtotal' => $totalSubtotal,
                'total_discount' => $totalDiscount,
                'total_vat' => $totalVat,
                'total_invoice_amount' => $totalInvoiceAmount,
                'total_paid' => $totalPaid,
                'total_payment_remaining' => $totalPaymentRemaining,
            ],
            'invoices' => $invoices,
            'groupedInvoices' => $groupedInvoices,
            'customers' => $customers,
            'centers' => $centers,
            'companyInfo' => $this->resolveReportCompanyInfo($tenant, $centerFilter, $centerId),
        ]);
    }

    /**
     * Export Invoices Report as CSV (with UTF-8 BOM for Arabic Excel support).
     */
    public function exportInvoices(Request $request): StreamedResponse
    {
        $this->authorize('viewAny', Invoice::class);

        $tenantId = auth()->user()->tenant_id;
        $centerId = TenancyContext::centerId();

        $range = (string) ($request->input('range') ?: 'this_month');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $dateType = (string) ($request->input('date_type') ?: 'issue_date');
        $type = (string) ($request->input('type') ?: 'all');
        $subtype = (string) ($request->input('subtype') ?: 'all');
        $paymentStatus = (string) ($request->input('payment_status') ?: 'all');
        $customerId = (string) ($request->input('customer_id') ?: 'all');
        $customerType = (string) ($request->input('customer_type') ?: 'all');
        $centerFilter = $this->resolveCenterFilter($request, $centerId);
        $isOverdue = $request->boolean('is_overdue');
        $search = trim((string) ($request->input('search') ?? ''));

        [$from, $to] = $this->resolveDateRange($range, $startDate, $endDate);

        $query = $this->buildInvoicesQuery(
            $tenantId,
            $centerId,
            $from,
            $to,
            $dateType,
            $type,
            $subtype,
            $paymentStatus,
            $customerId,
            $customerType,
            $centerFilter,
            $isOverdue,
            $search
        )->with([
            'center:id,name',
            'customer:id,name,phone,type,tax_number',
            'workOrder:id,code,vehicle_id',
            'workOrder.vehicle:id,plate_number',
        ])
            ->withSum('lines as total_discount', 'discount_amount')
            ->orderBy('issue_date', 'desc');

        $filename = 'تقرير_الفواتير_'.now()->format('Y-m-d_His').'.csv';

        return response()->streamDownload(function () use ($query) {
            $output = fopen('php://output', 'w');
            fwrite($output, "\xEF\xBB\xBF");

            // CSV Header
            fputcsv($output, [
                '#',
                'الفرع',
                'رقم الفاتورة',
                'نوع الفاتورة',
                'رقم كرت العمل',
                'تاريخ الإصدار',
                'تاريخ الاستحقاق',
                'العميل',
                'نوع العميل',
                'هاتف العميل',
                'الرقم الضريبي للعميل',
                'رقم اللوحة',
                'المجموع قبل الضريبة',
                'الخصم',
                'الضريبة',
                'الإجمالي شامل الضريبة',
                'المدفوع',
                'المتبقي',
                'حالة السداد',
                'متأخرة عن السداد',
            ]);

            $index = 1;
            $query->chunk(200, function ($invoices) use ($output, &$index) {
                foreach ($invoices as $inv) {
                    $discount = (float) ($inv->total_discount ?? 0);
                    $remaining = max(0, (float) $inv->total_incl_tax - (float) $inv->total_paid);
                    $isOverdue = $inv->due_date && $inv->due_date->isPast() && $inv->payment_status !== 'paid';

                    $customerName = $inv->customer?->name ?? $inv->customer_name_snapshot ?? '—';
                    $customerType = ($inv->customer?->type ?? '') === 'company' ? 'شركة / منشأة' : 'فرد';
                    $customerPhone = $inv->customer?->phone ?? '—';
                    $customerVat = $inv->customer?->tax_number ?? $inv->customer_vat_snapshot ?? '—';

                    $invType = match ($inv->type instanceof InvoiceType ? $inv->type->value : (string) $inv->type) {
                        'credit_note' => 'إشعار دائن',
                        'debit_note' => 'إشعار مدين',
                        default => $inv->subtype === 'standard' ? 'فاتورة ضريبية قياسية' : 'فاتورة ضريبية مبسطة',
                    };

                    $paymentLabel = match ($inv->payment_status) {
                        'paid' => 'مدفوع بالكامل',
                        'partial' => 'مدفوع جزئياً',
                        'unpaid' => 'غير مدفوع',
                        default => (string) $inv->payment_status,
                    };

                    fputcsv($output, [
                        $index++,
                        $inv->center?->name ?? 'المركز الرئيسي',
                        $inv->invoice_number,
                        $invType,
                        $inv->workOrder?->code ?: '—',
                        $inv->issue_date?->format('Y-m-d H:i') ?? $inv->created_at?->format('Y-m-d H:i'),
                        $inv->due_date?->format('Y-m-d') ?? '—',
                        $customerName,
                        $customerType,
                        $customerPhone,
                        $customerVat,
                        $inv->workOrder?->vehicle?->plate_number ?? '—',
                        number_format((float) $inv->total_excl_tax, 2, '.', ''),
                        number_format($discount, 2, '.', ''),
                        number_format((float) $inv->total_tax, 2, '.', ''),
                        number_format((float) $inv->total_incl_tax, 2, '.', ''),
                        number_format((float) $inv->total_paid, 2, '.', ''),
                        number_format($remaining, 2, '.', ''),
                        $paymentLabel,
                        $isOverdue ? 'نعم' : 'لا',
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
     * Build reusable invoices query with multi-tenancy, center and filters.
     */
    private function buildInvoicesQuery(
        int $tenantId,
        ?int $centerId,
        ?Carbon $from,
        ?Carbon $to,
        string $dateType = 'issue_date',
        ?string $type = 'all',
        ?string $subtype = 'all',
        ?string $paymentStatus = 'all',
        ?string $customerId = 'all',
        ?string $customerType = 'all',
        ?string $centerFilter = 'all',
        bool $isOverdue = false,
        ?string $search = ''
    ): Builder {
        $dateType = in_array($dateType, ['issue_date', 'due_date'], true) ? $dateType : 'issue_date';
        $type = $type ?: 'all';
        $subtype = $subtype ?: 'all';
        $paymentStatus = $paymentStatus ?: 'all';
        $customerId = $customerId ?: 'all';
        $customerType = $customerType ?: 'all';
        $centerFilter = $centerFilter ?: 'all';
        $search = trim((string) $search);

        $query = Invoice::query()->where('tenant_id', $tenantId);

        $accessibleCenterIds = $this->accessibleCenterIds();

        // Branch Isolation: if restricted, must stay within accessible centers
        if ($accessibleCenterIds !== null) {
            $query->whereIn('center_id', $accessibleCenterIds);
        }

        // Branch Filter
        if ($centerFilter !== 'all' && is_numeric($centerFilter)) {
            $query->where('center_id', (int) $centerFilter);
        }

        // Date Range
        if ($dateType === 'due_date') {
            if ($from && $to) {
                $query->whereBetween('due_date', [$from->toDateString(), $to->toDateString()]);
            } elseif ($from) {
                $query->where('due_date', '>=', $from->toDateString());
            } elseif ($to) {
                $query->where('due_date', '<=', $to->toDateString());
            }
        } else {
            if ($from && $to) {
                $query->whereBetween('issue_date', [$from, $to]);
            } elseif ($from) {
                $query->where('issue_date', '>=', $from);
            } elseif ($to) {
                $query->where('issue_date', '<=', $to);
            }
        }

        // Type
        if ($type !== 'all') {
            $query->where('type', $type);
        }

        // Subtype
        if ($subtype !== 'all') {
            $query->where('subtype', $subtype);
        }

        // Payment Status
        if ($paymentStatus !== 'all') {
            $query->where('payment_status', $paymentStatus);
        }

        // Overdue (due_date < today && payment_status != 'paid')
        if ($isOverdue) {
            $query->whereNotNull('due_date')
                ->where('due_date', '<', now()->toDateString())
                ->where('payment_status', '!=', 'paid');
        }

        // Customer
        if ($customerId !== 'all' && is_numeric($customerId)) {
            $query->where('customer_id', (int) $customerId);
        }

        // Customer Type
        if ($customerType !== 'all') {
            $query->whereHas('customer', fn ($q) => $q->where('type', $customerType));
        }

        // Search
        if ($search !== '') {
            $query->where(function (Builder $sub) use ($search) {
                $sub->where('invoice_number', 'like', "%{$search}%")
                    ->orWhere('customer_name_snapshot', 'like', "%{$search}%")
                    ->orWhereHas('customer', function (Builder $cq) use ($search) {
                        $cq->where('name', 'like', "%{$search}%")
                            ->orWhere('phone', 'like', "%{$search}%")
                            ->orWhere('tax_number', 'like', "%{$search}%");
                    })
                    ->orWhereHas('workOrder', function (Builder $wq) use ($search) {
                        $wq->where('code', 'like', "%{$search}%")
                            ->orWhereHas('vehicle', function (Builder $vq) use ($search) {
                                $vq->where('plate_number', 'like', "%{$search}%");
                            });
                    });
            });
        }

        return $query;
    }

    /**
     * Dedicated Purchase Invoices Report unit.
     */
    public function purchaseInvoices(Request $request): Response
    {
        $this->authorize('viewAny', PurchaseInvoice::class);

        $tenantId = auth()->user()->tenant_id;
        $centerId = TenancyContext::centerId();

        $range = (string) ($request->input('range') ?: 'this_month');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $dateType = (string) ($request->input('date_type') ?: 'issue_date');
        $status = (string) ($request->input('status') ?: 'all');
        $paymentStatus = (string) ($request->input('payment_status') ?: 'all');
        $supplierId = (string) ($request->input('supplier_id') ?: 'all');
        $supplierType = (string) ($request->input('supplier_type') ?: 'all');
        $centerFilter = $this->resolveCenterFilter($request, $centerId);
        $isOverdue = $request->boolean('is_overdue');
        $groupBy = (string) ($request->input('group_by') ?: 'none');
        $search = trim((string) ($request->input('search') ?? ''));

        [$from, $to] = $this->resolveDateRange($range, $startDate, $endDate);

        $query = $this->buildPurchaseInvoicesQuery(
            $tenantId,
            $centerId,
            $from,
            $to,
            $dateType,
            $status,
            $paymentStatus,
            $supplierId,
            $supplierType,
            $centerFilter,
            $isOverdue,
            $search
        );

        // Aggregate KPIs on filtered dataset
        $kpiQuery = clone $query;
        $totalRecords = (int) (clone $kpiQuery)->count();
        $totalSubtotal = (float) (clone $kpiQuery)->sum('subtotal');
        $totalDiscount = (float) (clone $kpiQuery)->sum('discount_amount');
        $totalVat = (float) (clone $kpiQuery)->sum('tax_amount');
        $totalInvoiceAmount = (float) (clone $kpiQuery)->sum('total');
        $totalPaymentRemaining = (float) (clone $kpiQuery)->sum('balance');
        $totalPaid = (float) ((clone $kpiQuery)->selectRaw('SUM(total - balance) as paid')->value('paid') ?? 0);

        $relations = [
            'center:id,name',
            'supplier:id,name,phone,type,tax_number,cr_number',
            'purchaseOrder:id,code',
        ];

        $mapPurchaseInvoice = function (PurchaseInvoice $inv) {
            $total = (float) $inv->total;
            $balance = (float) $inv->balance;
            $paid = max(0, $total - $balance);
            $isOverdue = $inv->due_date && $inv->due_date->isPast() && $balance > 0;

            // Compute payment status
            $paymentStatusKey = 'unpaid';

            if ($inv->status === 'paid' || $balance <= 0.01) {
                $paymentStatusKey = 'paid';
            } elseif ($balance < $total - 0.01) {
                $paymentStatusKey = 'partial';
            }

            return [
                'id' => $inv->id,
                'code' => $inv->code,
                'invoice_number' => $inv->invoice_number,
                'issue_date' => $inv->issue_date?->format('Y-m-d'),
                'due_date' => $inv->due_date?->format('Y-m-d'),
                'status' => $inv->status,
                'payment_status' => $paymentStatusKey,
                'is_overdue' => $isOverdue,
                'subtotal' => (float) $inv->subtotal,
                'discount_amount' => (float) $inv->discount_amount,
                'tax_amount' => (float) $inv->tax_amount,
                'total' => $total,
                'paid' => $paid,
                'balance' => $balance,
                'notes' => $inv->notes,
                'center' => $inv->center ? [
                    'id' => $inv->center->id,
                    'name' => $inv->center->name,
                ] : null,
                'supplier' => $inv->supplier ? [
                    'id' => $inv->supplier->id,
                    'name' => $inv->supplier->name,
                    'phone' => $inv->supplier->phone,
                    'type' => $inv->supplier->type,
                    'tax_number' => $inv->supplier->tax_number,
                ] : null,
                'purchase_order' => $inv->purchaseOrder ? [
                    'id' => $inv->purchaseOrder->id,
                    'code' => $inv->purchaseOrder->code,
                ] : null,
            ];
        };

        $invoices = null;
        $groupedInvoices = null;

        if ($groupBy !== 'none') {
            $allInvoices = (clone $query)
                ->with($relations)
                ->orderBy('issue_date', 'desc')
                ->limit(2000)
                ->get()
                ->map($mapPurchaseInvoice);

            $grouped = $allInvoices->groupBy(function ($inv) use ($groupBy) {
                return match ($groupBy) {
                    'center' => $inv['center']['name'] ?? 'المركز الرئيسي',
                    'supplier' => $inv['supplier']['name'] ?? 'مورد غير محدد',
                    'payment_status' => match ($inv['payment_status']) {
                        'paid' => 'مدفوع بالكامل',
                        'partial' => 'مدفوع جزئياً',
                        'unpaid' => 'غير مدفوع',
                        default => 'أخرى',
                    },
                    'status' => match ($inv['status']) {
                        'paid' => 'مسددة',
                        'open' => 'مفتوحة / مستحقة',
                        'draft' => 'مسودة',
                        'cancelled' => 'ملغاة',
                        default => (string) $inv['status'],
                    },
                    default => 'all',
                };
            });

            $groupedInvoices = $grouped->map(function ($items, $key) {
                return [
                    'group_key' => $key,
                    'group_name' => (string) $key,
                    'total_records' => $items->count(),
                    'total_subtotal' => (float) $items->sum('subtotal'),
                    'total_discount' => (float) $items->sum('discount_amount'),
                    'total_vat' => (float) $items->sum('tax_amount'),
                    'total_invoice_amount' => (float) $items->sum('total'),
                    'total_paid' => (float) $items->sum('paid'),
                    'total_payment_remaining' => (float) $items->sum('balance'),
                    'items' => $items->values(),
                ];
            })->values();
        } else {
            $paginated = (clone $query)
                ->with($relations)
                ->orderBy('issue_date', 'desc')
                ->paginate(25)
                ->withQueryString();

            $invoices = $paginated->through($mapPurchaseInvoice);
        }

        $suppliers = Supplier::query()
            ->where('tenant_id', $tenantId)
            ->select('id', 'name', 'phone', 'type')
            ->orderBy('name')
            ->limit(300)
            ->get();

        $centers = $this->resolveAccessibleCenters($tenantId);
        $tenant = auth()->user()->tenant;

        return Inertia::render('Reports/PurchaseInvoices', [
            'filters' => [
                'range' => $range,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'date_type' => $dateType,
                'status' => $status,
                'payment_status' => $paymentStatus,
                'supplier_id' => $supplierId,
                'supplier_type' => $supplierType,
                'center_id' => $centerFilter,
                'is_overdue' => $isOverdue,
                'group_by' => $groupBy,
                'search' => $search,
            ],
            'kpis' => [
                'total_records' => $totalRecords,
                'total_subtotal' => $totalSubtotal,
                'total_discount' => $totalDiscount,
                'total_vat' => $totalVat,
                'total_invoice_amount' => $totalInvoiceAmount,
                'total_paid' => $totalPaid,
                'total_payment_remaining' => $totalPaymentRemaining,
            ],
            'invoices' => $invoices,
            'groupedInvoices' => $groupedInvoices,
            'suppliers' => $suppliers,
            'centers' => $centers,
            'companyInfo' => $this->resolveReportCompanyInfo($tenant, $centerFilter, $centerId),
        ]);
    }

    /**
     * Export Purchase Invoices Report as CSV (with UTF-8 BOM for Arabic Excel support).
     */
    public function exportPurchaseInvoices(Request $request): StreamedResponse
    {
        $this->authorize('viewAny', PurchaseInvoice::class);

        $tenantId = auth()->user()->tenant_id;
        $centerId = TenancyContext::centerId();

        $range = (string) ($request->input('range') ?: 'this_month');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $dateType = (string) ($request->input('date_type') ?: 'issue_date');
        $status = (string) ($request->input('status') ?: 'all');
        $paymentStatus = (string) ($request->input('payment_status') ?: 'all');
        $supplierId = (string) ($request->input('supplier_id') ?: 'all');
        $supplierType = (string) ($request->input('supplier_type') ?: 'all');
        $centerFilter = $this->resolveCenterFilter($request, $centerId);
        $isOverdue = $request->boolean('is_overdue');
        $search = trim((string) ($request->input('search') ?? ''));

        [$from, $to] = $this->resolveDateRange($range, $startDate, $endDate);

        $query = $this->buildPurchaseInvoicesQuery(
            $tenantId,
            $centerId,
            $from,
            $to,
            $dateType,
            $status,
            $paymentStatus,
            $supplierId,
            $supplierType,
            $centerFilter,
            $isOverdue,
            $search
        )->with([
            'center:id,name',
            'supplier:id,name,phone,type,tax_number',
            'purchaseOrder:id,code',
        ])
            ->orderBy('issue_date', 'desc');

        $filename = 'تقرير_فواتير_الشراء_'.now()->format('Y-m-d_His').'.csv';

        return response()->streamDownload(function () use ($query) {
            $output = fopen('php://output', 'w');
            fwrite($output, "\xEF\xBB\xBF");

            // CSV Header
            fputcsv($output, [
                '#',
                'الفرع',
                'رقم الفاتورة الداخلي',
                'رقم مستند المورد',
                'أمر الشراء',
                'المورد',
                'هاتف المورد',
                'الرقم الضريبي للمورد',
                'تاريخ الفاتورة',
                'تاريخ الاستحقاق',
                'المجموع قبل الضريبة',
                'الخصم',
                'ضريبة القيمة المضافة',
                'الإجمالي شامل الضريبة',
                'المبلغ المسدد',
                'المتبقي للمورد',
                'حالة السداد',
                'متأخرة عن السداد',
            ]);

            $index = 1;
            $query->chunk(200, function ($invoices) use ($output, &$index) {
                foreach ($invoices as $inv) {
                    $total = (float) $inv->total;
                    $balance = (float) $inv->balance;
                    $paid = max(0, $total - $balance);
                    $isOverdue = $inv->due_date && $inv->due_date->isPast() && $balance > 0;

                    $paymentLabel = match (true) {
                        $inv->status === 'paid' || $balance <= 0.01 => 'مدفوع بالكامل',
                        $balance < $total - 0.01 => 'مدفوع جزئياً',
                        default => 'غير مدفوع',
                    };

                    fputcsv($output, [
                        $index++,
                        $inv->center?->name ?? 'المركز الرئيسي',
                        $inv->code,
                        $inv->invoice_number ?: '—',
                        $inv->purchaseOrder?->code ?: '—',
                        $inv->supplier?->name ?? '—',
                        $inv->supplier?->phone ?? '—',
                        $inv->supplier?->tax_number ?? '—',
                        $inv->issue_date?->format('Y-m-d') ?? '—',
                        $inv->due_date?->format('Y-m-d') ?? '—',
                        number_format((float) $inv->subtotal, 2, '.', ''),
                        number_format((float) $inv->discount_amount, 2, '.', ''),
                        number_format((float) $inv->tax_amount, 2, '.', ''),
                        number_format($total, 2, '.', ''),
                        number_format($paid, 2, '.', ''),
                        number_format($balance, 2, '.', ''),
                        $paymentLabel,
                        $isOverdue ? 'نعم' : 'لا',
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
     * Dedicated Suppliers Report unit (wrshah /reports/suppliers equivalent).
     */
    public function suppliers(Request $request): Response
    {
        $this->authorize('viewAny', PurchaseInvoice::class);

        $tenantId = auth()->user()->tenant_id;
        $centerId = TenancyContext::centerId();

        $range = (string) ($request->input('range') ?: 'this_month');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $paymentStatus = (string) ($request->input('payment_status') ?: 'all');
        $documentType = (string) ($request->input('document_type') ?: 'all');
        $supplierId = (string) ($request->input('supplier_id') ?: 'all');
        $supplierType = (string) ($request->input('supplier_type') ?: 'all');
        $centerFilter = $this->resolveCenterFilter($request, $centerId);
        $groupBy = (string) ($request->input('group_by') ?: 'none');
        $search = trim((string) ($request->input('search') ?? ''));

        [$from, $to] = $this->resolveDateRange($range, $startDate, $endDate);

        $invoiceQuery = $this->buildPurchaseInvoicesQuery(
            $tenantId,
            $centerId,
            $from,
            $to,
            'issue_date',
            'all',
            $paymentStatus,
            $supplierId,
            $supplierType,
            $centerFilter,
            false,
            $search
        );

        $returnQuery = $this->buildPurchaseReturnInvoicesQuery(
            $tenantId,
            $centerId,
            $from,
            $to,
            $documentType,
            $supplierId,
            $supplierType,
            $centerFilter,
            $search
        );

        // Aggregate KPIs
        $totalInvoicesCount = (int) (clone $invoiceQuery)->count();
        $grossSubtotal = (float) (clone $invoiceQuery)->sum('subtotal');
        $grossDiscount = (float) (clone $invoiceQuery)->sum('discount_amount');
        $grossVat = (float) (clone $invoiceQuery)->sum('tax_amount');
        $grossInvoiceAmount = (float) (clone $invoiceQuery)->sum('total');
        $rawBalance = (float) (clone $invoiceQuery)->sum('balance');

        $totalReturnsCount = (int) (clone $returnQuery)->count();
        $returnsSubtotal = (float) (clone $returnQuery)->sum('subtotal');
        $returnsVat = (float) (clone $returnQuery)->sum('tax_amount');
        $returnsTotal = (float) (clone $returnQuery)->sum('total');

        $debitNotesSubQuery = (clone $returnQuery)->where('create_debit_note', true);
        $totalDebitNotesCount = (int) (clone $debitNotesSubQuery)->count();
        $totalDebitNotesAmount = (float) (clone $debitNotesSubQuery)->sum('total');

        // Financial Net Totals
        if ($documentType === 'returns' || $documentType === 'debit_notes') {
            $totalSubtotal = -$returnsSubtotal;
            $totalVat = -$returnsVat;
            $totalInvoiceAmount = -$returnsTotal;
            $totalDiscount = $returnsTotal;
            $totalPaid = 0.0;
            $totalPaymentRemaining = -$returnsTotal;
            $totalRecords = $documentType === 'debit_notes' ? $totalDebitNotesCount : ($totalReturnsCount - $totalDebitNotesCount);
        } else {
            // Net calculation accounting for returns & debit notes
            $totalSubtotal = round($grossSubtotal - $returnsSubtotal, 2);
            $totalVat = round($grossVat - $returnsVat, 2);
            $totalInvoiceAmount = round($grossInvoiceAmount - $returnsTotal, 2);
            $totalDiscount = round($grossDiscount + $returnsTotal, 2);
            $totalPaymentRemaining = max(0, round($rawBalance - $totalDebitNotesAmount, 2));
            $totalPaid = max(0, round($grossInvoiceAmount - $rawBalance, 2));
            $totalRecords = $documentType === 'invoices' ? $totalInvoicesCount : ($totalInvoicesCount + $totalReturnsCount);
        }

        $mapSupplierInvoice = function (PurchaseInvoice $inv) {
            $total = (float) $inv->total;
            $balance = (float) $inv->balance;
            $paid = max(0, round($total - $balance, 2));

            $paymentStatusKey = 'unpaid';

            if ($inv->status === 'paid' || $balance <= 0.01) {
                $paymentStatusKey = 'paid';
            } elseif ($balance < $total - 0.01) {
                $paymentStatusKey = 'partial';
            }

            $returnsCollection = $inv->returnInvoices ?? collect();
            $invReturnsTotal = (float) $returnsCollection->sum('total');
            $invDebitNotesTotal = (float) $returnsCollection->where('create_debit_note', true)->sum('total');
            $adjustedAmount = (float) $inv->discount_amount + $invReturnsTotal;

            return [
                'id' => 'inv_'.$inv->id,
                'raw_id' => $inv->id,
                'record_type' => 'purchase_invoice',
                'code' => $inv->code,
                'invoice_number' => $inv->invoice_number,
                'issue_date' => $inv->issue_date?->format('Y-m-d'),
                'due_date' => $inv->due_date?->format('Y-m-d'),
                'status' => $inv->status,
                'payment_status' => $paymentStatusKey,
                'subtotal' => (float) $inv->subtotal,
                'discount_amount' => $adjustedAmount,
                'raw_discount' => (float) $inv->discount_amount,
                'tax_amount' => (float) $inv->tax_amount,
                'total' => $total,
                'paid' => $paid,
                'balance' => $balance,
                'bad_debt' => 0.0,
                'notes' => $inv->notes,
                'show_url' => Route::has('app.invoices.purchases.show') ? route('app.invoices.purchases.show', $inv->id) : null,
                'has_returns' => $returnsCollection->isNotEmpty(),
                'returns_count' => $returnsCollection->count(),
                'returns_total' => $invReturnsTotal,
                'debit_notes_total' => $invDebitNotesTotal,
                'return_invoices' => $returnsCollection->map(fn ($r) => [
                    'id' => $r->id,
                    'code' => $r->code,
                    'total' => (float) $r->total,
                    'create_debit_note' => (bool) $r->create_debit_note,
                    'return_date' => $r->return_date?->format('Y-m-d'),
                    'show_url' => Route::has('app.invoices.purchases.returns.show') ? route('app.invoices.purchases.returns.show', $r->id) : null,
                ])->values()->all(),
                'center' => $inv->center ? [
                    'id' => $inv->center->id,
                    'name' => $inv->center->name,
                ] : null,
                'supplier' => $inv->supplier ? [
                    'id' => $inv->supplier->id,
                    'name' => $inv->supplier->name,
                    'phone' => $inv->supplier->phone,
                    'type' => $inv->supplier->type,
                    'tax_number' => $inv->supplier->tax_number,
                ] : null,
            ];
        };

        $mapSupplierReturn = function (PurchaseReturnInvoice $ret) {
            $isDebit = (bool) $ret->create_debit_note;
            $parent = $ret->purchaseInvoice;
            $sub = (float) $ret->subtotal;
            $vat = (float) $ret->tax_amount;
            $tot = (float) $ret->total;

            return [
                'id' => 'ret_'.$ret->id,
                'raw_id' => $ret->id,
                'record_type' => $isDebit ? 'debit_note' : 'return_invoice',
                'code' => $ret->code,
                'invoice_number' => ($isDebit ? 'إشعار مدين: ' : 'مرتجع: ').($parent?->code ?: '—'),
                'issue_date' => $ret->return_date?->format('Y-m-d'),
                'due_date' => $ret->debit_note_date?->format('Y-m-d') ?? $ret->return_date?->format('Y-m-d'),
                'status' => $isDebit ? 'debit_note' : 'returned',
                'payment_status' => $isDebit ? 'debit_note' : 'returned',
                'subtotal' => -$sub,
                'discount_amount' => $tot,
                'raw_discount' => 0.0,
                'tax_amount' => -$vat,
                'total' => -$tot,
                'paid' => 0.0,
                'balance' => -$tot,
                'bad_debt' => 0.0,
                'notes' => $ret->notes,
                'show_url' => Route::has('app.invoices.purchases.returns.show') ? route('app.invoices.purchases.returns.show', $ret->id) : null,
                'has_returns' => false,
                'returns_count' => 0,
                'returns_total' => 0.0,
                'debit_notes_total' => 0.0,
                'return_invoices' => [],
                'parent_invoice_code' => $parent?->code,
                'parent_invoice_url' => $parent && Route::has('app.invoices.purchases.show') ? route('app.invoices.purchases.show', $parent->id) : null,
                'center' => $ret->center ? [
                    'id' => $ret->center->id,
                    'name' => $ret->center->name,
                ] : ($parent?->center ? [
                    'id' => $parent->center->id,
                    'name' => $parent->center->name,
                ] : null),
                'supplier' => $parent?->supplier ? [
                    'id' => $parent->supplier->id,
                    'name' => $parent->supplier->name,
                    'phone' => $parent->supplier->phone,
                    'type' => $parent->supplier->type,
                    'tax_number' => $parent->supplier->tax_number,
                ] : null,
            ];
        };

        // Gather records based on document_type
        $invoiceItems = collect();

        if ($documentType !== 'returns' && $documentType !== 'debit_notes') {
            $invoiceItems = (clone $invoiceQuery)
                ->with([
                    'center:id,name',
                    'supplier:id,name,phone,type,tax_number',
                    'returnInvoices:id,purchase_invoice_id,code,return_date,subtotal,tax_amount,total,create_debit_note',
                ])
                ->get()
                ->map($mapSupplierInvoice);
        }

        $returnItems = collect();

        if ($documentType !== 'invoices' && ($paymentStatus === 'all' || $paymentStatus === '')) {
            $returnItems = (clone $returnQuery)
                ->with([
                    'center:id,name',
                    'purchaseInvoice.center:id,name',
                    'purchaseInvoice.supplier:id,name,phone,type,tax_number',
                ])
                ->get()
                ->map($mapSupplierReturn);
        }

        $allTransactions = $invoiceItems->concat($returnItems)->sortByDesc(fn ($t) => $t['issue_date'])->values();

        $invoices = null;
        $groupedInvoices = null;

        if ($groupBy !== 'none') {
            $grouped = $allTransactions->groupBy(function ($inv) use ($groupBy) {
                return match ($groupBy) {
                    'center' => $inv['center']['name'] ?? 'المركز الرئيسي',
                    'supplier' => $inv['supplier']['name'] ?? 'مورد غير محدد',
                    'payment_status' => match ($inv['payment_status']) {
                        'paid' => 'مدفوع بالكامل',
                        'partial' => 'مدفوع جزئياً',
                        'debit_note' => 'إشعار مدين للمورد',
                        'returned' => 'مرتجع مسجل',
                        default => 'غير مدفوع',
                    },
                    default => 'all',
                };
            });

            $groupedInvoices = $grouped->map(function ($items, $key) {
                $subtotal = (float) $items->sum('subtotal');
                $discount = (float) $items->sum('discount_amount');
                $vat = (float) $items->sum('tax_amount');
                $total = (float) $items->sum('total');
                $paid = (float) $items->sum('paid');
                $balance = (float) $items->sum('balance');

                return [
                    'group_key' => $key,
                    'group_name' => (string) $key,
                    'total_records' => $items->count(),
                    'total_subtotal' => $subtotal,
                    'total_discount' => $discount,
                    'total_vat' => $vat,
                    'total_invoice_amount' => $total,
                    'total_paid' => $paid,
                    'total_payment_remaining' => $balance,
                    'items' => $items->values(),
                ];
            })->values();
        } else {
            $page = (int) ($request->input('page') ?: 1);
            $perPage = 50;
            $slice = $allTransactions->forPage($page, $perPage)->values();
            $invoices = new LengthAwarePaginator(
                $slice,
                $allTransactions->count(),
                $perPage,
                $page,
                ['path' => $request->url(), 'query' => $request->query()]
            );
        }

        $suppliers = Supplier::query()
            ->where('tenant_id', $tenantId)
            ->select('id', 'name', 'phone', 'type')
            ->orderBy('name')
            ->limit(300)
            ->get();

        $centers = $this->resolveAccessibleCenters($tenantId);
        $tenant = auth()->user()->tenant;

        return Inertia::render('Reports/Suppliers', [
            'filters' => [
                'range' => $range,
                'start_date' => $range === 'custom' ? $startDate : '',
                'end_date' => $range === 'custom' ? $endDate : '',
                'from_date' => $from?->toDateString(),
                'to_date' => $to?->toDateString(),
                'payment_status' => $paymentStatus,
                'document_type' => $documentType,
                'supplier_id' => $supplierId,
                'supplier_type' => $supplierType,
                'center_id' => $centerFilter,
                'group_by' => $groupBy,
                'search' => $search,
            ],
            'kpis' => [
                'total_records' => $totalRecords,
                'total_invoices_count' => $totalInvoicesCount,
                'total_returns_count' => $totalReturnsCount,
                'total_debit_notes_count' => $totalDebitNotesCount,
                'total_subtotal' => round($totalSubtotal, 2),
                'total_discount' => round($totalDiscount, 2),
                'total_vat' => round($totalVat, 2),
                'total_invoice_amount' => round($totalInvoiceAmount, 2),
                'total_paid' => round($totalPaid, 2),
                'total_payment_remaining' => round($totalPaymentRemaining, 2),
                'gross_purchases' => round($grossInvoiceAmount, 2),
                'total_returns_amount' => round($returnsTotal, 2),
                'total_debit_notes_amount' => round($totalDebitNotesAmount, 2),
            ],
            'invoices' => $invoices,
            'groupedInvoices' => $groupedInvoices,
            'suppliers' => $suppliers,
            'centers' => $centers,
            'companyInfo' => $this->resolveReportCompanyInfo($tenant, $centerFilter, $centerId),
        ]);
    }

    /**
     * Export Suppliers Report as CSV with UTF-8 BOM.
     */
    public function exportSuppliers(Request $request): StreamedResponse
    {
        $this->authorize('viewAny', PurchaseInvoice::class);

        $tenantId = auth()->user()->tenant_id;
        $centerId = TenancyContext::centerId();

        $range = (string) ($request->input('range') ?: 'this_month');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $paymentStatus = (string) ($request->input('payment_status') ?: 'all');
        $documentType = (string) ($request->input('document_type') ?: 'all');
        $supplierId = (string) ($request->input('supplier_id') ?: 'all');
        $supplierType = (string) ($request->input('supplier_type') ?: 'all');
        $centerFilter = $this->resolveCenterFilter($request, $centerId);
        $search = trim((string) ($request->input('search') ?? ''));

        [$from, $to] = $this->resolveDateRange($range, $startDate, $endDate);

        $invoiceQuery = $this->buildPurchaseInvoicesQuery(
            $tenantId,
            $centerId,
            $from,
            $to,
            'issue_date',
            'all',
            $paymentStatus,
            $supplierId,
            $supplierType,
            $centerFilter,
            false,
            $search
        )->with([
            'center:id,name',
            'supplier:id,name,phone,type,tax_number',
            'returnInvoices:id,purchase_invoice_id,code,return_date,subtotal,tax_amount,total,create_debit_note',
        ])
            ->orderBy('issue_date', 'desc');

        $returnQuery = $this->buildPurchaseReturnInvoicesQuery(
            $tenantId,
            $centerId,
            $from,
            $to,
            $documentType,
            $supplierId,
            $supplierType,
            $centerFilter,
            $search
        )->with([
            'center:id,name',
            'purchaseInvoice.center:id,name',
            'purchaseInvoice.supplier:id,name,phone,type,tax_number',
        ])
            ->orderBy('return_date', 'desc');

        $filename = 'تقرير_الموردين_'.now()->format('Y-m-d_His').'.csv';

        return response()->streamDownload(function () use ($invoiceQuery, $returnQuery, $documentType, $paymentStatus) {
            $output = fopen('php://output', 'w');
            fwrite($output, "\xEF\xBB\xBF");

            fputcsv($output, [
                '#',
                'نوع المعاملة',
                'الفرع / المركز',
                'رقم الفاتورة / المستند',
                'رقم مستند المورد / البيان',
                'الفاتورة الأصلية المرتبطة',
                'اسم المورد',
                'هاتف المورد',
                'الرقم الضريبي للمورد',
                'التاريخ',
                'المجموع قبل الضريبة',
                'الخصومات والتسويات والمرتجعات',
                'ضريبة القيمة المضافة',
                'المستحق / الإجمالي',
                'المبلغ المسدد',
                'المتبقي للسداد',
                'حالة السداد / المعاملة',
            ]);

            $index = 1;

            if ($documentType !== 'returns' && $documentType !== 'debit_notes') {
                $invoiceQuery->chunk(200, function ($invoices) use ($output, &$index) {
                    foreach ($invoices as $inv) {
                        $total = (float) $inv->total;
                        $balance = (float) $inv->balance;
                        $paid = max(0, round($total - $balance, 2));
                        $returnsTotal = (float) ($inv->returnInvoices ? $inv->returnInvoices->sum('total') : 0);
                        $adjusted = (float) $inv->discount_amount + $returnsTotal;

                        $paymentLabel = match (true) {
                            $inv->status === 'paid' || $balance <= 0.01 => 'مدفوع بالكامل',
                            $balance < $total - 0.01 => 'مدفوع جزئياً',
                            default => 'غير مدفوع',
                        };

                        fputcsv($output, [
                            $index++,
                            'فاتورة شراء',
                            $inv->center?->name ?? 'المركز الرئيسي',
                            $inv->code,
                            $inv->invoice_number ?: '—',
                            '—',
                            $inv->supplier?->name ?? '—',
                            $inv->supplier?->phone ?? '—',
                            $inv->supplier?->tax_number ?? '—',
                            $inv->issue_date?->format('Y-m-d') ?? '—',
                            number_format((float) $inv->subtotal, 2, '.', ''),
                            number_format($adjusted, 2, '.', ''),
                            number_format((float) $inv->tax_amount, 2, '.', ''),
                            number_format($total, 2, '.', ''),
                            number_format($paid, 2, '.', ''),
                            number_format($balance, 2, '.', ''),
                            $paymentLabel,
                        ]);
                    }
                });
            }

            if ($documentType !== 'invoices' && ($paymentStatus === 'all' || $paymentStatus === '')) {
                $returnQuery->chunk(200, function ($returns) use ($output, &$index) {
                    foreach ($returns as $ret) {
                        $isDebit = (bool) $ret->create_debit_note;
                        $parent = $ret->purchaseInvoice;
                        $tot = (float) $ret->total;

                        fputcsv($output, [
                            $index++,
                            $isDebit ? 'إشعار مدين للمورد' : 'فاتورة مرتجعة',
                            $ret->center?->name ?? ($parent?->center?->name ?? 'المركز الرئيسي'),
                            $ret->code,
                            $ret->notes ?: ($isDebit ? 'إشعار مدين للمورد' : 'مرتجع بضاعة'),
                            $parent?->code ?? '—',
                            $parent?->supplier?->name ?? '—',
                            $parent?->supplier?->phone ?? '—',
                            $parent?->supplier?->tax_number ?? '—',
                            $ret->return_date?->format('Y-m-d') ?? '—',
                            number_format(-(float) $ret->subtotal, 2, '.', ''),
                            number_format($tot, 2, '.', ''),
                            number_format(-(float) $ret->tax_amount, 2, '.', ''),
                            number_format(-$tot, 2, '.', ''),
                            '0.00',
                            number_format(-$tot, 2, '.', ''),
                            $isDebit ? 'إشعار مدين نشط' : 'مرتجع مسجل',
                        ]);
                    }
                });
            }

            fclose($output);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Cache-Control' => 'no-store, no-cache',
        ]);
    }

    /**
     * Build query for purchase return invoices & debit notes.
     */
    private function buildPurchaseReturnInvoicesQuery(
        int $tenantId,
        ?int $centerId,
        ?Carbon $from,
        ?Carbon $to,
        string $documentType = 'all',
        string $supplierId = 'all',
        string $supplierType = 'all',
        string $centerFilter = 'all',
        string $search = ''
    ) {
        $supplierId = $supplierId ?: 'all';
        $supplierType = $supplierType ?: 'all';
        $centerFilter = $centerFilter ?: 'all';
        $documentType = $documentType ?: 'all';
        $search = trim((string) $search);

        $query = PurchaseReturnInvoice::query()->where('tenant_id', $tenantId);

        $accessibleCenterIds = $this->accessibleCenterIds();

        if ($centerFilter !== 'all' && ! empty($centerFilter)) {
            $requestedCenterId = (int) $centerFilter;

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
            $query->whereBetween('return_date', [$from, $to]);
        }

        if ($documentType === 'debit_notes') {
            $query->where('create_debit_note', true);
        } elseif ($documentType === 'returns') {
            $query->where('create_debit_note', false);
        }

        if ($supplierId !== 'all') {
            $query->whereHas('purchaseInvoice', fn ($pi) => $pi->where('supplier_id', (int) $supplierId));
        }

        if ($supplierType !== 'all') {
            $query->whereHas('purchaseInvoice.supplier', fn ($s) => $s->where('type', $supplierType));
        }

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                    ->orWhere('notes', 'like', "%{$search}%")
                    ->orWhereHas('purchaseInvoice', function ($pi) use ($search) {
                        $pi->where('code', 'like', "%{$search}%")
                            ->orWhere('invoice_number', 'like', "%{$search}%")
                            ->orWhereHas('supplier', fn ($s) => $s->where('name', 'like', "%{$search}%"));
                    });
            });
        }

        return $query;
    }

    /**
     * Build reusable purchase invoices query with multi-tenancy, center and filters.
     */
    private function buildPurchaseInvoicesQuery(
        int $tenantId,
        ?int $centerId,
        ?Carbon $from,
        ?Carbon $to,
        string $dateType = 'issue_date',
        ?string $status = 'all',
        ?string $paymentStatus = 'all',
        ?string $supplierId = 'all',
        ?string $supplierType = 'all',
        ?string $centerFilter = 'all',
        bool $isOverdue = false,
        ?string $search = ''
    ): Builder {
        $dateType = in_array($dateType, ['issue_date', 'due_date'], true) ? $dateType : 'issue_date';
        $status = $status ?: 'all';
        $paymentStatus = $paymentStatus ?: 'all';
        $supplierId = $supplierId ?: 'all';
        $supplierType = $supplierType ?: 'all';
        $centerFilter = $centerFilter ?: 'all';
        $search = trim((string) $search);

        $query = PurchaseInvoice::query()->where('tenant_id', $tenantId);

        $accessibleCenterIds = $this->accessibleCenterIds();

        if ($accessibleCenterIds !== null) {
            $query->whereIn('center_id', $accessibleCenterIds);
        }

        if ($centerFilter !== 'all' && is_numeric($centerFilter)) {
            $query->where('center_id', (int) $centerFilter);
        }

        if ($dateType === 'due_date') {
            if ($from && $to) {
                $query->whereBetween('due_date', [$from->toDateString(), $to->toDateString()]);
            } elseif ($from) {
                $query->where('due_date', '>=', $from->toDateString());
            } elseif ($to) {
                $query->where('due_date', '<=', $to->toDateString());
            }
        } else {
            if ($from && $to) {
                $query->whereBetween('issue_date', [$from->toDateString(), $to->toDateString()]);
            } elseif ($from) {
                $query->where('issue_date', '>=', $from->toDateString());
            } elseif ($to) {
                $query->where('issue_date', '<=', $to->toDateString());
            }
        }

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        if ($paymentStatus !== 'all') {
            if ($paymentStatus === 'paid') {
                $query->where(function (Builder $q) {
                    $q->where('status', 'paid')->orWhere('balance', '<=', 0);
                });
            } elseif ($paymentStatus === 'partial') {
                $query->where('balance', '>', 0)->whereRaw('balance < total');
            } elseif ($paymentStatus === 'unpaid') {
                $query->whereRaw('balance >= total')->where('total', '>', 0);
            }
        }

        if ($isOverdue) {
            $query->whereNotNull('due_date')
                ->where('due_date', '<', now()->toDateString())
                ->where('balance', '>', 0);
        }

        if ($supplierId !== 'all' && is_numeric($supplierId)) {
            $query->where('supplier_id', (int) $supplierId);
        }

        if ($supplierType !== 'all') {
            $query->whereHas('supplier', fn ($q) => $q->where('type', $supplierType));
        }

        if ($search !== '') {
            $query->where(function (Builder $sub) use ($search) {
                $sub->where('code', 'like', "%{$search}%")
                    ->orWhere('invoice_number', 'like', "%{$search}%")
                    ->orWhere('notes', 'like', "%{$search}%")
                    ->orWhereHas('supplier', function (Builder $sq) use ($search) {
                        $sq->where('name', 'like', "%{$search}%")
                            ->orWhere('phone', 'like', "%{$search}%")
                            ->orWhere('tax_number', 'like', "%{$search}%");
                    })
                    ->orWhereHas('purchaseOrder', function (Builder $poq) use ($search) {
                        $poq->where('code', 'like', "%{$search}%");
                    });
            });
        }

        return $query;
    }

    /**
     * Dedicated Stock Activities Report unit.
     */
    public function stockActivities(Request $request): Response
    {
        $this->authorize('viewAny', InventoryMove::class);

        $tenantId = auth()->user()->tenant_id;
        $centerId = TenancyContext::centerId();

        $range = (string) ($request->input('range') ?: 'this_month');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $moveType = (string) ($request->input('move_type') ?: 'all');
        $warehouseId = (string) ($request->input('warehouse_id') ?: 'all');
        $categoryId = (string) ($request->input('category_id') ?: 'all');
        $partId = (string) ($request->input('part_id') ?: 'all');
        $centerFilter = $this->resolveCenterFilter($request, $centerId);
        $groupBy = (string) ($request->input('group_by') ?: 'none');
        $search = trim((string) ($request->input('search') ?? ''));

        [$from, $to] = $this->resolveDateRange($range, $startDate, $endDate);

        $query = $this->buildStockActivitiesQuery(
            $tenantId,
            $centerId,
            $from,
            $to,
            $moveType,
            $warehouseId,
            $categoryId,
            $partId,
            $centerFilter,
            $search
        );

        // Aggregate KPIs
        $kpiQuery = clone $query;
        $totalRecords = (int) (clone $kpiQuery)->count();
        $totalCost = (float) (clone $kpiQuery)->sum('total_cost');
        $totalIncomingQty = (float) ((clone $kpiQuery)->where('qty', '>', 0)->sum('qty') ?? 0);
        $totalOutgoingQty = (float) ((clone $kpiQuery)->where('qty', '<', 0)->sum(DB::raw('ABS(qty)')) ?? 0);
        $netQty = round($totalIncomingQty - $totalOutgoingQty, 3);

        $relations = [
            'center:id,name',
            'warehouse:id,name,code',
            'part:id,sku,barcode,name_ar,name_en,category_id,default_sale_price,unit_id',
            'part.unit:id,name_ar,name_en',
            'part.category:id,name_ar,name_en',
            'postedByUser:id,name',
            'reference',
        ];

        $mapStockActivity = function (InventoryMove $move) {
            $qty = (float) $move->qty;
            $balanceAfter = (float) $move->balance_after;
            $balanceBefore = round($balanceAfter - $qty, 3);
            $unitCost = (float) $move->unit_cost;
            $totalCost = (float) $move->total_cost;

            $refLabel = '—';
            $refCode = '—';
            $refUrl = null;

            try {
                $ref = $move->reference;
            } catch (Throwable) {
                $ref = null;
            }

            if ($ref instanceof WorkOrderItemPart) {
                $refLabel = 'أمر عمل';
                $wo = $move->work_order;
                $refCode = $wo?->code ?? ('#'.($ref->work_order_id ?? $move->reference_id));
                $refUrl = $wo && Route::has('work-orders.show') ? route('work-orders.show', $wo->id) : null;
            } elseif ($ref instanceof Invoice) {
                $isCreditNote = ($ref->type === 'credit_note' || (is_object($ref->type) && $ref->type->value === 'credit_note'));
                $refLabel = $isCreditNote ? 'إشعار دائن' : 'فاتورة مبيعات';
                $refCode = $ref->invoice_number ?: ('#'.$ref->id);
                $refUrl = $ref && Route::has('app.invoices.show') ? route('app.invoices.show', $ref->id) : null;
            } elseif ($ref instanceof GrnItem) {
                $refLabel = 'سند استلام';
                $grn = $ref->goodsReceivedNote;
                $refCode = $grn?->code ?: ('#'.($ref->goods_received_note_id ?? $move->reference_id));
                $refUrl = $grn && Route::has('app.purchasing.grn.show') ? route('app.purchasing.grn.show', $grn->id) : null;
            } elseif ($ref instanceof PurchaseReturn) {
                $refLabel = 'مرتجع شراء';
                $refCode = $ref->code ?: ('#'.$move->reference_id);
            } elseif (! empty($move->notes)) {
                $refLabel = 'ملاحظة';
                $refCode = $move->notes;
            }

            $salePrice = null;
            $totalSaleAmount = null;

            if ($ref instanceof WorkOrderItemPart) {
                $salePrice = (float) $ref->unit_price;
                $totalSaleAmount = (float) $ref->total;
            } elseif ($move->part && $move->part->default_sale_price) {
                $salePrice = (float) $move->part->default_sale_price;
                $totalSaleAmount = round(abs($qty) * $salePrice, 2);
            }

            return [
                'id' => $move->id,
                'move_type' => $move->move_type,
                'move_type_label' => InventoryMove::getMoveTypeLabel($move->move_type),
                'is_incoming' => $qty > 0,
                'qty' => $qty,
                'unit_cost' => $unitCost,
                'total_cost' => $totalCost,
                'sale_price' => $salePrice,
                'total_sale_amount' => $totalSaleAmount,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'posted_at' => $move->posted_at?->format('Y-m-d H:i') ?? $move->created_at?->format('Y-m-d H:i'),
                'notes' => $move->notes,
                'reference_label' => $refLabel,
                'reference_code' => $refCode,
                'reference_url' => $refUrl,
                'center' => $move->center ? [
                    'id' => $move->center->id,
                    'name' => $move->center->name,
                ] : null,
                'warehouse' => $move->warehouse ? [
                    'id' => $move->warehouse->id,
                    'name' => $move->warehouse->name,
                    'code' => $move->warehouse->code,
                ] : null,
                'part' => $move->part ? [
                    'id' => $move->part->id,
                    'sku' => $move->part->sku,
                    'barcode' => $move->part->barcode,
                    'name_ar' => $move->part->name_ar,
                    'name_en' => $move->part->name_en,
                    'unit_name' => $move->part->unit?->name_ar ?? $move->part->unit?->name_en ?? 'حبة',
                    'category_name' => $move->part->category?->name_ar ?? $move->part->category?->name_en ?? '—',
                ] : null,
                'posted_by' => $move->postedByUser ? [
                    'id' => $move->postedByUser->id,
                    'name' => $move->postedByUser->name,
                ] : null,
            ];
        };

        $moves = null;
        $groupedMoves = null;

        if ($groupBy !== 'none') {
            $allMoves = (clone $query)
                ->with($relations)
                ->orderBy('posted_at', 'desc')
                ->limit(2000)
                ->get()
                ->map($mapStockActivity);

            $grouped = $allMoves->groupBy(function ($m) use ($groupBy) {
                return match ($groupBy) {
                    'center' => $m['center']['name'] ?? 'المركز الرئيسي',
                    'part' => ($m['part']['name_ar'] ?? $m['part']['name_en'] ?? 'قطعة').' ('.($m['part']['sku'] ?? '-').')',
                    'warehouse' => $m['warehouse']['name'] ?? 'المستودع الرئيسي',
                    'move_type' => $m['move_type_label'] ?? 'أخرى',
                    default => 'all',
                };
            });

            $groupedMoves = $grouped->map(function ($items, $key) {
                $incoming = (float) $items->where('qty', '>', 0)->sum('qty');
                $outgoing = (float) $items->where('qty', '<', 0)->sum(fn ($i) => abs($i['qty']));

                return [
                    'group_key' => $key,
                    'group_name' => (string) $key,
                    'total_records' => $items->count(),
                    'total_incoming' => $incoming,
                    'total_outgoing' => $outgoing,
                    'net_qty' => round($incoming - $outgoing, 3),
                    'total_cost' => (float) $items->sum('total_cost'),
                    'items' => $items->values(),
                ];
            })->values();
        } else {
            $paginated = (clone $query)
                ->with($relations)
                ->orderBy('posted_at', 'desc')
                ->paginate(50)
                ->withQueryString();

            $moves = $paginated->through($mapStockActivity);
        }

        $centers = $this->resolveAccessibleCenters($tenantId);
        $warehouses = Warehouse::where('tenant_id', $tenantId)->select('id', 'name', 'code', 'center_id')->get();
        $categories = InventoryCategory::where('tenant_id', $tenantId)->select('id', 'name_ar', 'name_en')->get();

        $moveTypes = [
            ['id' => 'all', 'value' => 'all', 'name' => 'كافة أنواع الحركات', 'label' => 'كافة أنواع الحركات'],
            ['id' => InventoryMove::TYPE_RECEIPT, 'value' => InventoryMove::TYPE_RECEIPT, 'name' => 'استلام / شراء', 'label' => 'استلام / شراء'],
            ['id' => InventoryMove::TYPE_ISSUE_TO_WORKORDER, 'value' => InventoryMove::TYPE_ISSUE_TO_WORKORDER, 'name' => 'صرف لكرت عمل', 'label' => 'صرف لكرت عمل'],
            ['id' => InventoryMove::TYPE_SALE, 'value' => InventoryMove::TYPE_SALE, 'name' => 'بيع مباشر', 'label' => 'بيع مباشر'],
            ['id' => InventoryMove::TYPE_ADJUSTMENT_IN, 'value' => InventoryMove::TYPE_ADJUSTMENT_IN, 'name' => 'تسوية زيادة', 'label' => 'تسوية زيادة'],
            ['id' => InventoryMove::TYPE_ADJUSTMENT_OUT, 'value' => InventoryMove::TYPE_ADJUSTMENT_OUT, 'name' => 'تسوية عجز', 'label' => 'تسوية عجز'],
            ['id' => InventoryMove::TYPE_TRANSFER_IN, 'value' => InventoryMove::TYPE_TRANSFER_IN, 'name' => 'تحويل وارد', 'label' => 'تحويل وارد'],
            ['id' => InventoryMove::TYPE_TRANSFER_OUT, 'value' => InventoryMove::TYPE_TRANSFER_OUT, 'name' => 'تحويل صادر', 'label' => 'تحويل صادر'],
            ['id' => InventoryMove::TYPE_PURCHASE_RETURN, 'value' => InventoryMove::TYPE_PURCHASE_RETURN, 'name' => 'مرتجع شراء', 'label' => 'مرتجع شراء'],
            ['id' => InventoryMove::TYPE_REVERSAL, 'value' => InventoryMove::TYPE_REVERSAL, 'name' => 'عكس حركة', 'label' => 'عكس حركة'],
        ];

        return Inertia::render('Reports/StockActivities', [
            'filters' => [
                'range' => $range,
                'start_date' => $range === 'custom' ? $startDate : '',
                'end_date' => $range === 'custom' ? $endDate : '',
                'from_date' => $from?->toDateString(),
                'to_date' => $to?->toDateString(),
                'move_type' => $moveType,
                'warehouse_id' => $warehouseId,
                'category_id' => $categoryId,
                'part_id' => $partId,
                'center_id' => $centerFilter,
                'group_by' => $groupBy,
                'search' => $search,
            ],
            'kpis' => [
                'total_records' => $totalRecords,
                'total_cost' => round($totalCost, 2),
                'total_incoming_qty' => round($totalIncomingQty, 3),
                'total_outgoing_qty' => round($totalOutgoingQty, 3),
                'net_qty' => $netQty,
            ],
            'moves' => $moves,
            'groupedMoves' => $groupedMoves,
            'centers' => $centers,
            'warehouses' => $warehouses,
            'categories' => $categories,
            'moveTypes' => $moveTypes,
            'currentCenterId' => $centerId,
        ]);
    }

    /**
     * Export Stock Activities to CSV.
     */
    public function exportStockActivities(Request $request): StreamedResponse
    {
        $this->authorize('viewAny', InventoryMove::class);

        $tenantId = auth()->user()->tenant_id;
        $centerId = TenancyContext::centerId();

        $range = (string) ($request->input('range') ?: 'this_month');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $moveType = (string) ($request->input('move_type') ?: 'all');
        $warehouseId = (string) ($request->input('warehouse_id') ?: 'all');
        $categoryId = (string) ($request->input('category_id') ?: 'all');
        $partId = (string) ($request->input('part_id') ?: 'all');
        $centerFilter = $this->resolveCenterFilter($request, $centerId);
        $search = trim((string) ($request->input('search') ?? ''));

        [$from, $to] = $this->resolveDateRange($range, $startDate, $endDate);

        $query = $this->buildStockActivitiesQuery(
            $tenantId,
            $centerId,
            $from,
            $to,
            $moveType,
            $warehouseId,
            $categoryId,
            $partId,
            $centerFilter,
            $search
        )->with([
            'center:id,name',
            'warehouse:id,name,code',
            'part:id,sku,name_ar,name_en,category_id,unit_id',
            'part.unit:id,name_ar',
            'part.category:id,name_ar',
            'postedByUser:id,name',
            'reference',
        ])
            ->orderBy('posted_at', 'desc');

        $filename = 'تقرير_حركة_المخزون_'.now()->format('Y-m-d_His').'.csv';

        return response()->streamDownload(function () use ($query) {
            $output = fopen('php://output', 'w');
            fwrite($output, "\xEF\xBB\xBF");

            // CSV Header
            fputcsv($output, [
                '#',
                'الفرع',
                'المستودع',
                'كود القطعة (SKU)',
                'اسم القطعة',
                'التصنيف',
                'نوع الحركة',
                'التاريخ والوقت',
                'الرصيد قبل',
                'الكمية',
                'الرصيد بعد',
                'تكلفة الوحدة',
                'إجمالي التكلفة',
                'المرجع',
                'الملاحظات',
                'بواسطة',
            ]);

            $index = 1;
            $query->chunk(200, function ($moves) use ($output, &$index) {
                foreach ($moves as $m) {
                    $qty = (float) $m->qty;
                    $after = (float) $m->balance_after;
                    $before = round($after - $qty, 3);

                    $refText = '—';

                    try {
                        $ref = $m->reference;
                    } catch (Throwable) {
                        $ref = null;
                    }

                    if ($ref instanceof WorkOrderItemPart) {
                        $refText = 'كرت عمل: '.($m->work_order?->code ?? ('#'.$ref->work_order_id));
                    } elseif ($ref instanceof Invoice) {
                        $isCreditNote = ($ref->type === 'credit_note' || (is_object($ref->type) && $ref->type->value === 'credit_note'));
                        $refText = ($isCreditNote ? 'إشعار دائن: ' : 'فاتورة مبيعات: ').($ref->invoice_number ?: ('#'.$ref->id));
                    } elseif ($ref instanceof GrnItem) {
                        $refText = 'سند استلام: '.($ref->goodsReceivedNote?->code ?: ('#'.$ref->goods_received_note_id));
                    } elseif (! empty($m->notes)) {
                        $refText = $m->notes;
                    }

                    fputcsv($output, [
                        $index++,
                        $m->center?->name ?? 'المركز الرئيسي',
                        $m->warehouse?->name ?? 'المستودع الرئيسي',
                        $m->part?->sku ?? '—',
                        $m->part?->name_ar ?? $m->part?->name_en ?? '—',
                        $m->part?->category?->name_ar ?? '—',
                        InventoryMove::getMoveTypeLabel($m->move_type),
                        $m->posted_at?->format('Y-m-d H:i') ?? '—',
                        number_format($before, 3, '.', ''),
                        number_format($qty, 3, '.', ''),
                        number_format($after, 3, '.', ''),
                        number_format((float) $m->unit_cost, 2, '.', ''),
                        number_format((float) $m->total_cost, 2, '.', ''),
                        $refText,
                        $m->notes ?: '—',
                        $m->postedByUser?->name ?? '—',
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
     * Build reusable stock activities query with multi-tenancy, center and filters.
     */
    private function buildStockActivitiesQuery(
        int $tenantId,
        ?int $centerId,
        ?Carbon $from,
        ?Carbon $to,
        string $moveType,
        string $warehouseId,
        string $categoryId,
        string $partId,
        string $centerFilter,
        string $search
    ): Builder {
        $query = InventoryMove::withoutGlobalScope('center_scoped')
            ->where('inventory_moves.tenant_id', $tenantId)
            ->whereNotNull('inventory_moves.posted_at');

        $accessibleCenterIds = $this->accessibleCenterIds();

        if ($accessibleCenterIds !== null) {
            $query->whereIn('inventory_moves.center_id', $accessibleCenterIds);
        }

        if ($centerFilter !== 'all' && is_numeric($centerFilter)) {
            $query->where('inventory_moves.center_id', (int) $centerFilter);
        }

        if ($from && $to) {
            $query->whereBetween('inventory_moves.posted_at', [$from, $to]);
        } elseif ($from) {
            $query->where('inventory_moves.posted_at', '>=', $from);
        } elseif ($to) {
            $query->where('inventory_moves.posted_at', '<=', $to);
        }

        if ($moveType !== 'all') {
            $query->where('inventory_moves.move_type', $moveType);
        }

        if ($warehouseId !== 'all' && is_numeric($warehouseId)) {
            $query->where('inventory_moves.warehouse_id', (int) $warehouseId);
        }

        if ($categoryId !== 'all' && is_numeric($categoryId)) {
            $query->whereHas('part', fn ($pq) => $pq->where('category_id', (int) $categoryId));
        }

        if ($partId !== 'all' && is_numeric($partId)) {
            $query->where('inventory_moves.part_id', (int) $partId);
        }

        if ($search !== '') {
            $query->where(function (Builder $sub) use ($search) {
                $sub->where('inventory_moves.notes', 'like', "%{$search}%")
                    ->orWhere('inventory_moves.reference_id', 'like', "%{$search}%")
                    ->orWhereHas('part', function (Builder $pq) use ($search) {
                        $pq->where('sku', 'like', "%{$search}%")
                            ->orWhere('barcode', 'like', "%{$search}%")
                            ->orWhere('name_ar', 'like', "%{$search}%")
                            ->orWhere('name_en', 'like', "%{$search}%");
                    });
            });
        }

        return $query;
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
        $centerFilter = $this->resolveCenterFilter($request, $centerId);
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
        $centerFilter = $this->resolveCenterFilter($request, $centerId);
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
        $centerFilter = $this->resolveCenterFilter($request, $centerId);
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
        $centerFilter = $this->resolveCenterFilter($request, $centerId);
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

    /**
     * Dedicated Employees & Technicians Productivity Report unit (matching wrshah /reports/employees).
     */
    public function employees(Request $request): Response
    {
        $tenantId = auth()->user()->tenant_id;
        $centerId = TenancyContext::centerId();

        $range = (string) ($request->input('range') ?: 'this_month');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $dateType = (string) ($request->input('date_type') ?: 'service_date');
        $centerFilter = $this->resolveCenterFilter($request, $centerId);
        $employeeFilter = (string) ($request->input('employee_id') ?: 'all');
        $jobTitleFilter = (string) ($request->input('job_title_id') ?: 'all');
        $departmentFilter = (string) ($request->input('department_id') ?: 'all');
        $serviceFilter = (string) ($request->input('service_id') ?: 'all');
        $serviceStatusFilter = (string) ($request->input('service_status') ?: 'all');
        $makeFilter = (string) ($request->input('make_id') ?: 'all');
        $modelFilter = (string) ($request->input('model_id') ?: 'all');
        $ticketNo = trim((string) ($request->input('ticket_no') ?? ''));
        $isOverdue = $request->boolean('is_overdue');
        $removeInactiveEmployees = $request->boolean('remove_inactive_employees');
        $deductTicketDiscount = $request->boolean('deduct_ticket_discount');
        $groupBy = (string) ($request->input('group_by') ?: 'none');
        $search = trim((string) ($request->input('search') ?? ''));

        [$from, $to] = $this->resolveDateRange($range, $startDate, $endDate);

        $query = $this->buildEmployeesReportQuery(
            $tenantId,
            $centerId,
            $from,
            $to,
            $dateType,
            $centerFilter,
            $employeeFilter,
            $jobTitleFilter,
            $departmentFilter,
            $serviceFilter,
            $serviceStatusFilter,
            $makeFilter,
            $modelFilter,
            $ticketNo,
            $isOverdue,
            $removeInactiveEmployees,
            $search
        );

        $allRows = (clone $query)
            ->orderBy('pivot.id', 'desc')
            ->get();

        $mapRow = $this->getEmployeeRowMapper($deductTicketDiscount);
        $mappedItems = $allRows->map($mapRow);

        // Overall KPIs calculation
        $totalRecords = $mappedItems->count();
        $totalServiceHours = round((float) $mappedItems->sum('service_hours'), 2);
        $totalAvgPercentContribution = round((float) $mappedItems->sum('avg_percent_contribution'), 2);
        $totalAvgContributionByServiceHours = $totalServiceHours > 0
            ? round($totalAvgPercentContribution / $totalServiceHours, 2)
            : 0.0;
        $totalWeightedAvgContribution = $totalRecords > 0
            ? round($totalAvgPercentContribution / $totalRecords, 2)
            : 0.0;
        $totalServicePrice = round((float) $mappedItems->sum('service_price'), 2);
        $totalServiceDiscount = round((float) $mappedItems->sum('service_discount'), 2);
        $totalServiceCost = round((float) $mappedItems->sum('service_cost'), 2);

        $kpis = [
            'total_records' => $totalRecords,
            'total_service_hours' => $totalServiceHours,
            'total_avg_percent_contribution' => $totalAvgPercentContribution,
            'total_avg_contribution_by_service_hours' => $totalAvgContributionByServiceHours,
            'total_weighted_avg_contribution' => $totalWeightedAvgContribution,
            'total_service_price' => $totalServicePrice,
            'total_service_discount' => $totalServiceDiscount,
            'total_service_cost' => $totalServiceCost,
        ];

        $groupedEmployees = null;
        $employees = null;

        if ($groupBy !== 'none') {
            $grouped = $mappedItems->groupBy(function ($item) use ($groupBy) {
                return match ($groupBy) {
                    'job_title' => $item['job_title_name'] ?: 'غير محدد',
                    'employee' => $item['employee_name'] ?: 'غير محدد',
                    'service_status' => $item['service_status'] ?: 'غير محدد',
                    'ticket_no' => $item['ticket_no'] ?: 'غير محدد',
                    'center' => $item['center_name'] ?: 'المركز الرئيسي',
                    default => 'عام',
                };
            });

            $groupSummaryList = [];
            $groupIdx = 1;

            foreach ($grouped as $groupName => $items) {
                $groupRecords = $items->count();
                $groupHours = round((float) $items->sum('service_hours'), 2);
                $groupAvgContrib = round((float) $items->sum('avg_percent_contribution'), 2);
                $groupAvgByHours = $groupHours > 0 ? round($groupAvgContrib / $groupHours, 2) : 0.0;
                $groupWeighted = $groupRecords > 0 ? round($groupAvgContrib / $groupRecords, 2) : 0.0;
                $groupCost = round((float) $items->sum('service_cost'), 2);

                $groupSummaryList[] = [
                    'group_id' => $groupIdx++,
                    'group_name' => $groupName,
                    'summary' => [
                        'total_records' => $groupRecords,
                        'total_service_cost' => $groupCost,
                        'total_service_hours' => $groupHours,
                        'total_avg_percent_contribution' => $groupAvgContrib,
                        'total_avg_contribution_by_service_hours' => $groupAvgByHours,
                        'total_weighted_avg_contribution' => $groupWeighted,
                    ],
                    'items' => $items->values(),
                ];
            }
            $groupedEmployees = $groupSummaryList;
        } else {
            $page = max(1, (int) $request->input('page', 1));
            $perPage = 25;
            $totalCount = $mappedItems->count();
            $sliced = $mappedItems->slice(($page - 1) * $perPage, $perPage)->values();

            $employees = new LengthAwarePaginator(
                $sliced,
                $totalCount,
                $perPage,
                $page,
                ['path' => $request->url(), 'query' => $request->query()]
            );
        }

        $centers = Center::where('tenant_id', $tenantId)->get(['id', 'name']);
        $employeesList = Employee::where('tenant_id', $tenantId)->get(['id', 'name_ar', 'name_en', 'user_id']);
        $jobTitles = JobTitle::where('tenant_id', $tenantId)->get(['id', 'name_ar', 'name_en']);
        $departments = Department::where('tenant_id', $tenantId)->get(['id', 'name_ar', 'name_en']);
        $services = Service::where('tenant_id', $tenantId)->get(['id', 'name_ar', 'name_en']);
        $makes = VehicleMake::all(['id', 'name_ar', 'name_en']);
        $models = VehicleModel::all(['id', 'make_id', 'name_ar', 'name_en']);

        return Inertia::render('Reports/Employees', [
            'filters' => [
                'range' => $range,
                'start_date' => $from?->toDateString(),
                'end_date' => $to?->toDateString(),
                'date_type' => $dateType,
                'center_id' => $centerFilter,
                'employee_id' => $employeeFilter,
                'job_title_id' => $jobTitleFilter,
                'department_id' => $departmentFilter,
                'service_id' => $serviceFilter,
                'service_status' => $serviceStatusFilter,
                'make_id' => $makeFilter,
                'model_id' => $modelFilter,
                'ticket_no' => $ticketNo,
                'is_overdue' => $isOverdue,
                'remove_inactive_employees' => $removeInactiveEmployees,
                'deduct_ticket_discount' => $deductTicketDiscount,
                'group_by' => $groupBy,
                'search' => $search,
            ],
            'kpis' => $kpis,
            'employees' => $employees,
            'groupedEmployees' => $groupedEmployees,
            'centers' => $centers,
            'employeesList' => $employeesList,
            'jobTitles' => $jobTitles,
            'departments' => $departments,
            'services' => $services,
            'makes' => $makes,
            'models' => $models,
        ]);
    }

    /**
     * Export Employees Report as CSV.
     */
    public function exportEmployees(Request $request): StreamedResponse
    {
        $tenantId = auth()->user()->tenant_id;
        $centerId = TenancyContext::centerId();

        $range = (string) ($request->input('range') ?: 'this_month');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $dateType = (string) ($request->input('date_type') ?: 'service_date');
        $centerFilter = $this->resolveCenterFilter($request, $centerId);
        $employeeFilter = (string) ($request->input('employee_id') ?: 'all');
        $jobTitleFilter = (string) ($request->input('job_title_id') ?: 'all');
        $departmentFilter = (string) ($request->input('department_id') ?: 'all');
        $serviceFilter = (string) ($request->input('service_id') ?: 'all');
        $serviceStatusFilter = (string) ($request->input('service_status') ?: 'all');
        $makeFilter = (string) ($request->input('make_id') ?: 'all');
        $modelFilter = (string) ($request->input('model_id') ?: 'all');
        $ticketNo = trim((string) ($request->input('ticket_no') ?? ''));
        $isOverdue = $request->boolean('is_overdue');
        $removeInactiveEmployees = $request->boolean('remove_inactive_employees');
        $deductTicketDiscount = $request->boolean('deduct_ticket_discount');
        $search = trim((string) ($request->input('search') ?? ''));

        [$from, $to] = $this->resolveDateRange($range, $startDate, $endDate);

        $query = $this->buildEmployeesReportQuery(
            $tenantId,
            $centerId,
            $from,
            $to,
            $dateType,
            $centerFilter,
            $employeeFilter,
            $jobTitleFilter,
            $departmentFilter,
            $serviceFilter,
            $serviceStatusFilter,
            $makeFilter,
            $modelFilter,
            $ticketNo,
            $isOverdue,
            $removeInactiveEmployees,
            $search
        );

        $rows = (clone $query)->orderBy('pivot.id', 'desc')->get();
        $mapRow = $this->getEmployeeRowMapper($deductTicketDiscount);

        $filename = 'employees_report_'.Carbon::now()->format('Y-m-d_His').'.csv';

        return response()->streamDownload(function () use ($rows, $mapRow) {
            $handle = fopen('php://output', 'w');
            // Write UTF-8 BOM for Excel
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($handle, [
                '#',
                'الفرع',
                'الموظف',
                'المسمى الوظيفي',
                'الدور في الخدمة',
                'رقم كارت العمل',
                'القسم',
                'الخدمة',
                'حالة الخدمة',
                'متأخرة',
                'المركبة',
                'رقم اللوحة',
                'تاريخ الخدمة',
                'تاريخ الفاتورة',
                'رقم الفاتورة',
                'سعر الخدمة',
                'خصم الخدمة',
                'تكلفة الخدمة (الصافي)',
                'مساهمة الخدمة %',
                'ساعات الخدمة',
                'الفنيين العاملين',
                'متوسط المساهمة',
                'متوسط المساهمة / ساعة',
                'متوسط المساهمة المرجح',
            ]);

            $i = 1;

            foreach ($rows as $raw) {
                $item = $mapRow($raw);
                fputcsv($handle, [
                    $i++,
                    $item['center_name'],
                    $item['employee_name'],
                    $item['job_title_name'],
                    $item['technician_service_role_name'],
                    $item['ticket_no'],
                    $item['section_name'],
                    $item['service_name'],
                    $item['service_status'],
                    $item['is_overdue'] ? 'نعم' : 'لا',
                    $item['vehicle']['display_name'],
                    $item['vehicle']['plate_number'],
                    $item['service_date'],
                    $item['invoice_date'] ?: '-',
                    $item['invoice_number'] ?: '-',
                    number_format((float) $item['service_price'], 2, '.', ''),
                    number_format((float) $item['service_discount'], 2, '.', ''),
                    number_format((float) $item['service_cost'], 2, '.', ''),
                    number_format((float) $item['service_contribution'], 2, '.', '').'%',
                    number_format((float) $item['service_hours'], 2, '.', ''),
                    $item['working_technicians'],
                    number_format((float) $item['avg_percent_contribution'], 2, '.', ''),
                    number_format((float) $item['avg_contribution_by_service_hours'], 2, '.', ''),
                    number_format((float) $item['weighted_avg_contribution'], 2, '.', ''),
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * Build base query for the Employees / Technicians Productivity report.
     */
    protected function buildEmployeesReportQuery(
        int $tenantId,
        ?int $centerId,
        ?Carbon $from,
        ?Carbon $to,
        string $dateType,
        string $centerFilter,
        string $employeeFilter,
        string $jobTitleFilter,
        string $departmentFilter,
        string $serviceFilter,
        string $serviceStatusFilter,
        string $makeFilter,
        string $modelFilter,
        string $ticketNo,
        bool $isOverdue,
        bool $removeInactiveEmployees,
        string $search
    ): \Illuminate\Database\Query\Builder {
        $query = DB::table('work_order_item_technician as pivot')
            ->join('work_order_items as items', 'items.id', '=', 'pivot.work_order_item_id')
            ->join('work_orders as wo', 'wo.id', '=', 'items.work_order_id')
            ->join('users as u', 'u.id', '=', 'pivot.user_id')
            ->leftJoin('hr_employees as emp', function ($j) {
                $j->on('emp.user_id', '=', 'u.id')->whereNull('emp.deleted_at');
            })
            ->leftJoin('hr_job_titles as jt', 'jt.id', '=', 'emp.job_title_id')
            ->leftJoin('services as s', 's.id', '=', 'items.service_id')
            ->leftJoin('departments as d', function ($j) {
                $j->on('d.id', '=', 'items.department_id')
                    ->orOn('d.id', '=', 's.department_id');
            })
            ->leftJoin('vehicles as v', 'v.id', '=', 'wo.vehicle_id')
            ->leftJoin('vehicle_makes as vm', 'vm.id', '=', 'v.make_id')
            ->leftJoin('vehicle_models as vmod', 'vmod.id', '=', 'v.model_id')
            ->leftJoin('centers as c', 'c.id', '=', 'wo.center_id')
            ->leftJoin('invoices as inv', function ($j) {
                $j->on('inv.work_order_id', '=', 'wo.id')
                    ->where('inv.type', '=', 'invoice')
                    ->where('inv.status', '!=', 'cancelled');
            })
            ->where('wo.tenant_id', $tenantId);

        // Center scoping
        if ($centerFilter !== 'all' && ! empty($centerFilter)) {
            $query->where('wo.center_id', (int) $centerFilter);
        } elseif ($centerId) {
            $query->where('wo.center_id', $centerId);
        }

        // Date filter
        if ($from && $to) {
            if ($dateType === 'invoice_date') {
                $query->whereNotNull('inv.issue_date')
                    ->whereBetween('inv.issue_date', [
                        $from->copy()->startOfDay()->format('Y-m-d H:i:s'),
                        $to->copy()->endOfDay()->format('Y-m-d H:i:s'),
                    ]);
            } else {
                $query->where(function ($q) use ($from, $to) {
                    $start = $from->copy()->startOfDay();
                    $end = $to->copy()->endOfDay();
                    $q->whereBetween('items.started_at', [$start, $end])
                        ->orWhere(function ($q2) use ($start, $end) {
                            $q2->whereNull('items.started_at')
                                ->whereBetween('wo.entry_date', [$start->format('Y-m-d'), $end->format('Y-m-d')]);
                        })
                        ->orWhere(function ($q3) use ($start, $end) {
                            $q3->whereNull('items.started_at')
                                ->whereNull('wo.entry_date')
                                ->whereBetween('items.created_at', [$start, $end]);
                        });
                });
            }
        }

        // Employee filter
        if ($employeeFilter !== 'all') {
            $query->where(function ($q) use ($employeeFilter) {
                $q->where('emp.id', (int) $employeeFilter)
                    ->orWhere('u.id', (int) $employeeFilter);
            });
        }

        // Job Title filter
        if ($jobTitleFilter !== 'all') {
            $query->where('emp.job_title_id', (int) $jobTitleFilter);
        }

        // Department filter
        if ($departmentFilter !== 'all') {
            $query->where(function ($q) use ($departmentFilter) {
                $q->where('items.department_id', (int) $departmentFilter)
                    ->orWhere('s.department_id', (int) $departmentFilter);
            });
        }

        // Service filter
        if ($serviceFilter !== 'all') {
            $query->where('items.service_id', (int) $serviceFilter);
        }

        // Service Status filter
        if ($serviceStatusFilter !== 'all') {
            $query->where('items.status', $serviceStatusFilter);
        }

        // Make filter
        if ($makeFilter !== 'all') {
            $query->where('v.make_id', (int) $makeFilter);
        }

        // Model filter
        if ($modelFilter !== 'all') {
            $query->where('v.model_id', (int) $modelFilter);
        }

        // Ticket No filter
        if ($ticketNo !== '') {
            $query->where('wo.code', 'like', "%{$ticketNo}%");
        }

        // Overdue filter
        if ($isOverdue) {
            $nowStr = Carbon::now()->toDateTimeString();
            $query->whereNotNull('items.due_date')
                ->where('items.due_date', '<', $nowStr)
                ->whereNotIn('items.status', [WorkOrderItem::STATUS_COMPLETED, WorkOrderItem::STATUS_CANCELLED]);
        }

        // Remove inactive employees filter
        if ($removeInactiveEmployees) {
            $query->where('u.is_active', true)
                ->where(function ($q) {
                    $q->whereNull('emp.status')
                        ->orWhere('emp.status', 'active');
                });
        }

        // Direct search
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('u.name', 'like', "%{$search}%")
                    ->orWhere('emp.name_ar', 'like', "%{$search}%")
                    ->orWhere('emp.name_en', 'like', "%{$search}%")
                    ->orWhere('jt.name_ar', 'like', "%{$search}%")
                    ->orWhere('wo.code', 'like', "%{$search}%")
                    ->orWhere('s.name_ar', 'like', "%{$search}%")
                    ->orWhere('s.name_en', 'like', "%{$search}%")
                    ->orWhere('items.title', 'like', "%{$search}%")
                    ->orWhere('v.plate_number', 'like', "%{$search}%");
            });
        }

        $query->select([
            'pivot.id as pivot_id',
            'pivot.work_order_item_id',
            'pivot.user_id',
            'pivot.share',
            'pivot.notes as pivot_notes',
            'u.name as user_name',
            'emp.id as employee_id',
            'emp.name_ar as employee_name_ar',
            'emp.name_en as employee_name_en',
            'jt.id as job_title_id',
            'jt.name_ar as job_title_name_ar',
            'jt.name_en as job_title_name_en',
            'wo.id as work_order_id',
            'wo.code as ticket_no',
            'wo.status as ticket_status',
            'wo.entry_date',
            'wo.expected_end_date',
            'wo.center_id',
            'c.name as center_name',
            'items.id as item_id',
            'items.title as item_title',
            'items.status as service_status',
            'items.unit_price',
            'items.qty',
            'items.discount_amount',
            'items.line_total',
            'items.actual_duration',
            'items.estimated_duration',
            'items.started_at',
            'items.due_date',
            'items.completed_at',
            'items.created_at as item_created_at',
            's.id as service_id',
            's.name_ar as service_name_ar',
            's.name_en as service_name_en',
            'd.id as department_id',
            'd.name_ar as department_name_ar',
            'd.name_en as department_name_en',
            'v.plate_number',
            'v.year as vehicle_year',
            'v.make_other',
            'v.model_other',
            'vm.id as make_id',
            'vm.name_ar as make_name_ar',
            'vm.name_en as make_name_en',
            'vmod.id as model_id',
            'vmod.name_ar as model_name_ar',
            'vmod.name_en as model_name_en',
            'inv.id as invoice_id',
            'inv.invoice_number',
            'inv.issue_date as invoice_date',
            DB::raw('(SELECT COUNT(*) FROM work_order_item_technician WHERE work_order_item_id = items.id) as working_technicians_count'),
            DB::raw(DB::connection()->getDriverName() === 'sqlite'
                ? '(SELECT COALESCE(SUM((strftime(\'%s\', COALESCE(ended_at, datetime(\'now\'))) - strftime(\'%s\', started_at)) - COALESCE(paused_duration_seconds, 0)), 0) FROM work_order_labor_entries WHERE work_order_item_id = items.id AND user_id = pivot.user_id) as labor_duration_seconds'
                : '(SELECT COALESCE(SUM(TIMESTAMPDIFF(SECOND, started_at, COALESCE(ended_at, NOW())) - COALESCE(paused_duration_seconds, 0)), 0) FROM work_order_labor_entries WHERE work_order_item_id = items.id AND user_id = pivot.user_id) as labor_duration_seconds'
            ),
        ]);

        return $query;
    }

    /**
     * Helper to map raw employee assignment records into structured report rows.
     */
    protected function getEmployeeRowMapper(bool $deductTicketDiscount): Closure
    {
        return function ($row) {
            $share = floatval($row->share ?: 100);
            $qty = floatval($row->qty ?: 1);
            $unitPrice = floatval($row->unit_price ?: 0);
            $servicePrice = round($unitPrice * $qty, 2);
            $serviceDiscount = floatval($row->discount_amount ?: 0);
            $serviceCost = round(floatval($row->line_total ?: ($servicePrice - $serviceDiscount)), 2);

            $workingTechnicians = (int) ($row->working_technicians_count ?: 1);

            // Calculate service hours
            $laborSeconds = (int) ($row->labor_duration_seconds ?: 0);

            if ($laborSeconds > 0) {
                $serviceHours = round($laborSeconds / 3600, 2);
            } elseif (! empty($row->actual_duration) && $row->actual_duration > 0) {
                $serviceHours = round(($row->actual_duration / 60) * ($share / 100), 2);
            } elseif (! empty($row->estimated_duration) && $row->estimated_duration > 0) {
                $serviceHours = round(($row->estimated_duration / 60) * ($share / 100), 2);
            } else {
                $serviceHours = 0.0;
            }

            // Calculate contributions
            $avgPercentContribution = round($serviceCost * ($share / 100), 2);
            $avgContributionByServiceHours = $serviceHours > 0 ? round($avgPercentContribution / $serviceHours, 2) : 0.0;
            $weightedAvgContribution = round($avgPercentContribution * ($share / 100), 2);

            $isOverdue = false;

            if ($row->due_date && Carbon::parse($row->due_date)->isPast() && ! in_array($row->service_status, [WorkOrderItem::STATUS_COMPLETED, WorkOrderItem::STATUS_CANCELLED])) {
                $isOverdue = true;
            } elseif (! $row->due_date && $row->expected_end_date && Carbon::parse($row->expected_end_date)->isPast() && ! in_array($row->service_status, [WorkOrderItem::STATUS_COMPLETED, WorkOrderItem::STATUS_CANCELLED])) {
                $isOverdue = true;
            }

            $serviceDate = $row->started_at
                ? Carbon::parse($row->started_at)->format('Y-m-d')
                : ($row->entry_date
                    ? Carbon::parse($row->entry_date)->format('Y-m-d')
                    : ($row->item_created_at ? Carbon::parse($row->item_created_at)->format('Y-m-d') : ''));

            $invoiceDate = $row->invoice_date ? Carbon::parse($row->invoice_date)->format('Y-m-d') : null;

            $makeName = $row->make_name_ar ?: $row->make_name_en ?: $row->make_other ?: '';
            $modelName = $row->model_name_ar ?: $row->model_name_en ?: $row->model_other ?: '';

            return [
                'id' => $row->pivot_id,
                'pivot_id' => $row->pivot_id,
                'work_order_item_id' => $row->work_order_item_id,
                'work_order_id' => $row->work_order_id,
                'ticket_no' => $row->ticket_no,
                'ticket_status' => $row->ticket_status,
                'center_id' => $row->center_id,
                'center_name' => $row->center_name ?: 'المركز الرئيسي',
                'user_id' => $row->user_id,
                'employee_id' => $row->employee_id ?: $row->user_id,
                'employee_name' => $row->employee_name_ar ?: $row->user_name,
                'job_title_id' => $row->job_title_id,
                'job_title_name' => $row->job_title_name_ar ?: 'فني صيانة',
                'technician_service_role_name' => $row->pivot_notes ?: ($share >= 100 ? 'فني رئيسي' : 'فني مشارك'),
                'service_id' => $row->service_id,
                'service_name' => $row->item_title ?: ($row->service_name_ar ?: 'خدمة صيانة'),
                'department_id' => $row->department_id,
                'section_name' => $row->department_name_ar ?: 'عام',
                'service_status' => $row->service_status,
                'is_overdue' => $isOverdue,
                'vehicle' => [
                    'make_id' => $row->make_id,
                    'make_name' => $makeName,
                    'model_id' => $row->model_id,
                    'model_name' => $modelName,
                    'year' => $row->vehicle_year,
                    'plate_number' => $row->plate_number ?: '',
                    'display_name' => trim("{$makeName} {$modelName} ".($row->vehicle_year ? "({$row->vehicle_year})" : '')),
                ],
                'service_date' => $serviceDate,
                'invoice_date' => $invoiceDate,
                'invoice_number' => $row->invoice_number,
                'service_price' => $servicePrice,
                'service_discount' => $serviceDiscount,
                'service_cost' => $serviceCost,
                'service_contribution' => $share,
                'service_hours' => $serviceHours,
                'working_technicians' => $workingTechnicians,
                'avg_percent_contribution' => $avgPercentContribution,
                'avg_contribution_by_service_hours' => $avgContributionByServiceHours,
                'weighted_avg_contribution' => $weightedAvgContribution,
            ];
        };
    }

    /**
     * Dedicated Vehicles Report unit (matching wrshah /reports/vehicles).
     */
    public function vehicles(Request $request): Response
    {
        $tenantId = auth()->user()->tenant_id;
        $centerId = TenancyContext::centerId();

        $range = (string) ($request->input('range') ?: 'this_month');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $centerFilter = $this->resolveCenterFilter($request, $centerId);
        $makeFilter = (string) ($request->input('make_id') ?: 'all');
        $modelFilter = (string) ($request->input('model_id') ?: 'all');
        $serviceStatusFilter = (string) ($request->input('service_status') ?: 'all');
        $ticketNo = trim((string) ($request->input('ticket_no') ?? ''));
        $plateNo = trim((string) ($request->input('plate_no') ?? ''));
        $isOverdue = $request->boolean('is_overdue');
        $warehousePartsOnly = $request->boolean('warehouse_parts_only');
        $groupBy = (string) ($request->input('group_by') ?: 'none');
        $search = trim((string) ($request->input('search') ?? ''));

        [$from, $to] = $this->resolveDateRange($range, $startDate, $endDate);

        $query = $this->buildVehiclesReportQuery(
            $tenantId,
            $centerId,
            $from,
            $to,
            $centerFilter,
            $makeFilter,
            $modelFilter,
            $serviceStatusFilter,
            $ticketNo,
            $plateNo,
            $isOverdue,
            $warehousePartsOnly,
            $search
        );

        $allRows = (clone $query)
            ->orderBy('work_order_items.id', 'desc')
            ->get();

        $mappedItems = $allRows->map(function ($row) {
            $makeName = $row->make_name_ar ?: ($row->make_name_en ?: ($row->make_other ?: ''));
            $modelName = $row->model_name_ar ?: ($row->model_name_en ?: ($row->model_other ?: ''));
            $vehicleDisplayName = trim("{$makeName} {$modelName} ".($row->vehicle_year ? "({$row->vehicle_year})" : ''));
            $serviceCost = round((float) ($row->item_total ?? $row->line_total ?? ($row->unit_price * $row->qty)), 2);
            $sparePartCost = round((float) $row->spare_part_cost, 2);
            $total = round($serviceCost + $sparePartCost, 2);

            $dueDate = $row->item_due_date ?: ($row->expected_end_date ? Carbon::parse($row->expected_end_date)->format('Y-m-d') : ($row->entry_date ?: ($row->ticket_created_at ? Carbon::parse($row->ticket_created_at)->format('Y-m-d') : '')));

            $isOverdue = false;

            if ($dueDate && ! in_array($row->service_status, ['completed', 'cancelled']) && ! in_array($row->ticket_status, ['done', 'cancelled'])) {
                $isOverdue = Carbon::parse($dueDate)->isPast();
            }

            return [
                'id' => $row->item_id,
                'work_order_id' => $row->work_order_id,
                'ticket_no' => $row->ticket_no,
                'ticket_status' => $row->ticket_status,
                'is_ticket_closed' => in_array($row->ticket_status, [WorkOrder::STATUS_DONE, WorkOrder::STATUS_CANCELLED]),
                'center_id' => $row->center_id,
                'shop_name' => $row->shop_name ?: 'المركز الرئيسي',
                'vehicle_id' => $row->vehicle_id,
                'plate_no' => $row->plate_no ?: '',
                'make_id' => $row->make_id ?? null,
                'make_name' => $makeName,
                'model_id' => $row->model_id ?? null,
                'model_name' => $modelName,
                'vehicle_year' => $row->vehicle_year,
                'vehicle_display_name' => $vehicleDisplayName,
                'odometer_km' => $row->ticket_odometer ?: ($row->vehicle_odometer ?: null),
                'details' => $row->vehicle_notes ?: ($row->ticket_notes ?: ''),
                'section_name' => $row->section_name ?: 'عام',
                'service_name' => $row->item_title ?: ($row->service_name ?: 'خدمة صيانة'),
                'service_status' => $row->service_status,
                'due_date' => $dueDate,
                'service_cost' => $serviceCost,
                'spare_part_cost' => $sparePartCost,
                'total' => $total,
                'is_overdue' => $isOverdue,
            ];
        });

        // Overall KPIs calculation
        $totalRecords = $mappedItems->count();
        $totalVehicles = $mappedItems->pluck('vehicle_id')->unique()->filter()->count();
        $totalTickets = $mappedItems->pluck('work_order_id')->unique()->filter()->count();
        $totalServiceCost = round((float) $mappedItems->sum('service_cost'), 2);
        $totalSparePartCost = round((float) $mappedItems->sum('spare_part_cost'), 2);
        $grandTotal = round((float) $mappedItems->sum('total'), 2);

        $kpis = [
            'total_records' => $totalRecords,
            'total_vehicles' => $totalVehicles,
            'total_tickets' => $totalTickets,
            'total_service_cost' => $totalServiceCost,
            'total_spare_part_cost' => $totalSparePartCost,
            'grand_total' => $grandTotal,
        ];

        // Grouping
        $groups = null;

        if ($groupBy !== 'none') {
            $groupedCollection = $mappedItems->groupBy(function ($item) use ($groupBy) {
                return match ($groupBy) {
                    'shopName' => $item['shop_name'],
                    'plateNo' => $item['plate_no'] ?: 'بدون لوحة',
                    'serviceStatusName' => $item['service_status'],
                    default => 'all',
                };
            });

            $groups = $groupedCollection->map(function ($items, $key) use ($groupBy) {
                $groupName = match ($groupBy) {
                    'shopName' => $key,
                    'plateNo' => $key,
                    'serviceStatusName' => match ($key) {
                        'pending' => 'قيد الانتظار',
                        'in_progress' => 'قيد التنفيذ',
                        'ready_for_qc' => 'جاهز للفحص',
                        'completed' => 'مكتمل',
                        'on_hold' => 'معلق',
                        'cancelled' => 'ملغي',
                        default => $key,
                    },
                    default => $key,
                };

                $totalCost = round((float) $items->sum('service_cost'), 2);
                $totalParts = round((float) $items->sum('spare_part_cost'), 2);

                return [
                    'group_key' => (string) $key,
                    'group_name' => $groupName,
                    'items' => $items->values()->all(),
                    'summary' => [
                        'total_records' => $items->count(),
                        'total_service_cost' => $totalCost,
                        'total_spare_part_cost' => $totalParts,
                        'grand_total' => round($totalCost + $totalParts, 2),
                    ],
                ];
            })->values()->all();
        }

        // Pagination for flat table
        $page = (int) ($request->input('page') ?: 1);
        $perPage = (int) ($request->input('per_page') ?: 25);
        $paginatedItems = new LengthAwarePaginator(
            $mappedItems->forPage($page, $perPage)->values(),
            $totalRecords,
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        $tenant = Tenant::find($tenantId);
        $centers = $tenant?->centers()->select('id', 'name')->get() ?? collect();
        $makes = VehicleMake::orderBy('name_ar')->get(['id', 'name_ar', 'name_en']);
        $models = VehicleModel::orderBy('name_ar')->get(['id', 'make_id', 'name_ar', 'name_en']);

        return Inertia::render('Reports/Vehicles', [
            'items' => $paginatedItems,
            'groups' => $groups,
            'kpis' => $kpis,
            'filters' => [
                'range' => $range,
                'start_date' => $startDate ?: ($from ? $from->toDateString() : null),
                'end_date' => $endDate ?: ($to ? $to->toDateString() : null),
                'center_id' => $centerFilter,
                'make_id' => $makeFilter,
                'model_id' => $modelFilter,
                'service_status' => $serviceStatusFilter,
                'ticket_no' => $ticketNo,
                'plate_no' => $plateNo,
                'is_overdue' => $isOverdue,
                'warehouse_parts_only' => $warehousePartsOnly,
                'group_by' => $groupBy,
                'search' => $search,
            ],
            'lookups' => [
                'centers' => $centers,
                'makes' => $makes,
                'models' => $models,
                'service_statuses' => [
                    ['id' => 'pending', 'label' => 'قيد الانتظار'],
                    ['id' => 'in_progress', 'label' => 'قيد التنفيذ'],
                    ['id' => 'ready_for_qc', 'label' => 'جاهز للفحص'],
                    ['id' => 'completed', 'label' => 'مكتمل'],
                    ['id' => 'on_hold', 'label' => 'معلق'],
                    ['id' => 'cancelled', 'label' => 'ملغي'],
                ],
                'groups' => [
                    ['id' => 'none', 'label' => 'بدون تجميع (قائمة مسطحة)'],
                    ['id' => 'shopName', 'label' => 'الفرع / المركز'],
                    ['id' => 'plateNo', 'label' => 'المركبة'],
                    ['id' => 'serviceStatusName', 'label' => 'حالة الخدمة'],
                ],
            ],
        ]);
    }

    /**
     * Export Vehicles Report as CSV with UTF-8 BOM.
     */
    public function exportVehicles(Request $request): StreamedResponse
    {
        $tenantId = auth()->user()->tenant_id;
        $centerId = TenancyContext::centerId();

        $range = (string) ($request->input('range') ?: 'this_month');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $centerFilter = $this->resolveCenterFilter($request, $centerId);
        $makeFilter = (string) ($request->input('make_id') ?: 'all');
        $modelFilter = (string) ($request->input('model_id') ?: 'all');
        $serviceStatusFilter = (string) ($request->input('service_status') ?: 'all');
        $ticketNo = trim((string) ($request->input('ticket_no') ?? ''));
        $plateNo = trim((string) ($request->input('plate_no') ?? ''));
        $isOverdue = $request->boolean('is_overdue');
        $warehousePartsOnly = $request->boolean('warehouse_parts_only');
        $search = trim((string) ($request->input('search') ?? ''));

        [$from, $to] = $this->resolveDateRange($range, $startDate, $endDate);

        $query = $this->buildVehiclesReportQuery(
            $tenantId,
            $centerId,
            $from,
            $to,
            $centerFilter,
            $makeFilter,
            $modelFilter,
            $serviceStatusFilter,
            $ticketNo,
            $plateNo,
            $isOverdue,
            $warehousePartsOnly,
            $search
        )->orderBy('work_order_items.id', 'desc');

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="vehicles_report_'.now()->format('Y-m-d_His').'.csv"',
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        return response()->stream(function () use ($query) {
            $handle = fopen('php://output', 'w');
            // Write UTF-8 BOM for Excel compatibility
            fwrite($handle, "\xEF\xBB\xBF");

            fputcsv($handle, [
                '#',
                'الفرع',
                'المركبة',
                'رقم اللوحة',
                'سنة الصنع',
                'قراءة العداد (كم)',
                'رقم كرت العمل',
                'حالة كرت العمل',
                'القسم',
                'الخدمة',
                'حالة الخدمة',
                'تاريخ الاستحقاق',
                'تكلفة الخدمة (ر.س)',
                'تكلفة قطع الغيار (ر.س)',
                'الإجمالي (ر.س)',
                'الملاحظات',
            ]);

            $rowNumber = 1;
            $query->chunk(250, function ($rows) use ($handle, &$rowNumber) {
                foreach ($rows as $row) {
                    $makeName = $row->make_name_ar ?: ($row->make_name_en ?: ($row->make_other ?: ''));
                    $modelName = $row->model_name_ar ?: ($row->model_name_en ?: ($row->model_other ?: ''));
                    $vehicleDisplayName = trim("{$makeName} {$modelName}");
                    $serviceCost = round((float) ($row->item_total ?? $row->line_total ?? ($row->unit_price * $row->qty)), 2);
                    $sparePartCost = round((float) $row->spare_part_cost, 2);
                    $total = round($serviceCost + $sparePartCost, 2);

                    $dueDate = $row->item_due_date ?: ($row->expected_end_date ? Carbon::parse($row->expected_end_date)->format('Y-m-d') : ($row->entry_date ?: ($row->ticket_created_at ? Carbon::parse($row->ticket_created_at)->format('Y-m-d') : '')));

                    $serviceStatusLabel = match ($row->service_status) {
                        'pending' => 'قيد الانتظار',
                        'in_progress' => 'قيد التنفيذ',
                        'ready_for_qc' => 'جاهز للفحص',
                        'completed' => 'مكتمل',
                        'on_hold' => 'معلق',
                        'cancelled' => 'ملغي',
                        default => $row->service_status,
                    };

                    $ticketStatusLabel = match ($row->ticket_status) {
                        'draft' => 'مسودة',
                        'open' => 'مفتوح',
                        'in_progress' => 'قيد العمل',
                        'on_hold' => 'معلق',
                        'done' => 'مكتمل',
                        'ready_for_qc' => 'جاهز للفحص',
                        'cancelled' => 'ملغي',
                        default => $row->ticket_status,
                    };

                    fputcsv($handle, [
                        $rowNumber++,
                        $row->shop_name ?: 'المركز الرئيسي',
                        $vehicleDisplayName,
                        $row->plate_no ?: '',
                        $row->vehicle_year ?: '',
                        $row->ticket_odometer ?: ($row->vehicle_odometer ?: ''),
                        $row->ticket_no ?: '',
                        $ticketStatusLabel,
                        $row->section_name ?: 'عام',
                        $row->item_title ?: ($row->service_name ?: 'خدمة صيانة'),
                        $serviceStatusLabel,
                        $dueDate,
                        number_format($serviceCost, 2, '.', ''),
                        number_format($sparePartCost, 2, '.', ''),
                        number_format($total, 2, '.', ''),
                        $row->vehicle_notes ?: ($row->ticket_notes ?: ''),
                    ]);
                }
            });

            fclose($handle);
        }, 200, $headers);
    }

    /**
     * Build the filtered Query for Vehicles Report (matching wrshah /reports/vehicles).
     */
    private function buildVehiclesReportQuery(
        int $tenantId,
        ?int $centerId,
        ?Carbon $from,
        ?Carbon $to,
        string $centerFilter,
        string $makeFilter,
        string $modelFilter,
        string $serviceStatusFilter,
        string $ticketNo,
        string $plateNo,
        bool $isOverdue,
        bool $warehousePartsOnly,
        string $search
    ): \Illuminate\Database\Query\Builder {
        $user = auth()->user();
        $userCenterIds = $user?->centers()->pluck('centers.id')->toArray() ?? [];

        $query = DB::table('work_order_items')
            ->join('work_orders', 'work_order_items.work_order_id', '=', 'work_orders.id')
            ->join('vehicles', 'work_orders.vehicle_id', '=', 'vehicles.id')
            ->leftJoin('centers', 'work_orders.center_id', '=', 'centers.id')
            ->leftJoin('vehicle_makes', 'vehicles.make_id', '=', 'vehicle_makes.id')
            ->leftJoin('vehicle_models', 'vehicles.model_id', '=', 'vehicle_models.id')
            ->leftJoin('departments', 'work_order_items.department_id', '=', 'departments.id')
            ->leftJoin('services', 'work_order_items.service_id', '=', 'services.id')
            ->leftJoin('customers', 'work_orders.customer_id', '=', 'customers.id')
            ->where('work_order_items.tenant_id', $tenantId)
            ->whereNull('work_orders.deleted_at')
            ->whereNull('vehicles.deleted_at');

        // Center scoping
        if ($centerFilter !== 'all' && ! empty($centerFilter)) {
            $query->where('work_orders.center_id', (int) $centerFilter);
        } else {
            if (! empty($userCenterIds)) {
                $query->whereIn('work_orders.center_id', $userCenterIds);
            }
        }

        // Date Range (due_date / expected_end_date / entry_date / created_at)
        $effectiveDateExpr = 'COALESCE(work_order_items.due_date, DATE(work_orders.expected_end_date), work_orders.entry_date, DATE(work_order_items.created_at))';

        if ($from && $to) {
            $query->whereRaw("{$effectiveDateExpr} BETWEEN ? AND ?", [$from->toDateString(), $to->toDateString()]);
        }

        // Make filter
        if ($makeFilter !== 'all' && ! empty($makeFilter)) {
            $query->where('vehicles.make_id', (int) $makeFilter);
        }

        // Model filter
        if ($modelFilter !== 'all' && ! empty($modelFilter)) {
            $query->where('vehicles.model_id', (int) $modelFilter);
        }

        // Service Status filter
        if ($serviceStatusFilter !== 'all' && ! empty($serviceStatusFilter)) {
            $query->where('work_order_items.status', $serviceStatusFilter);
        }

        // Ticket number filter
        if ($ticketNo !== '') {
            $query->where('work_orders.code', 'like', "%{$ticketNo}%");
        }

        // Plate number filter
        if ($plateNo !== '') {
            $query->where('vehicles.plate_number', 'like', "%{$plateNo}%");
        }

        // Overdue filter
        if ($isOverdue) {
            $today = now()->toDateString();
            $query->whereRaw("{$effectiveDateExpr} < ? AND work_order_items.status NOT IN ('completed', 'cancelled') AND work_orders.status NOT IN ('done', 'cancelled')", [$today]);
        }

        // Warehouse parts only filter
        if ($warehousePartsOnly) {
            $query->whereExists(function ($subQuery) {
                $subQuery->select(DB::raw(1))
                    ->from('work_order_item_parts as woip')
                    ->whereRaw('(woip.work_order_item_id = work_order_items.id OR (woip.work_order_item_id IS NULL AND woip.work_order_id = work_orders.id))')
                    ->where('woip.source', WorkOrderItemPart::SOURCE_WAREHOUSE)
                    ->whereNotIn('woip.status', ['cancelled', 'reversed'])
                    ->whereNull('woip.deleted_at');
            });
        }

        // Direct search
        if ($search !== '') {
            $query->where(function ($sq) use ($search) {
                $sq->where('work_orders.code', 'like', "%{$search}%")
                    ->orWhere('vehicles.plate_number', 'like', "%{$search}%")
                    ->orWhere('vehicle_makes.name_ar', 'like', "%{$search}%")
                    ->orWhere('vehicle_makes.name_en', 'like', "%{$search}%")
                    ->orWhere('vehicle_models.name_ar', 'like', "%{$search}%")
                    ->orWhere('vehicle_models.name_en', 'like', "%{$search}%")
                    ->orWhere('vehicles.make_other', 'like', "%{$search}%")
                    ->orWhere('vehicles.model_other', 'like', "%{$search}%")
                    ->orWhere('work_order_items.title', 'like', "%{$search}%")
                    ->orWhere('services.name_ar', 'like', "%{$search}%")
                    ->orWhere('services.name_en', 'like', "%{$search}%")
                    ->orWhere('departments.name_ar', 'like', "%{$search}%")
                    ->orWhere('customers.name', 'like', "%{$search}%")
                    ->orWhere('customers.phone', 'like', "%{$search}%");
            });
        }

        $sourceCondition = $warehousePartsOnly ? "AND woip.source = 'warehouse'" : '';
        $partsSubquery = "(SELECT COALESCE(SUM(woip.total), 0) FROM work_order_item_parts woip WHERE (woip.work_order_item_id = work_order_items.id OR (woip.work_order_item_id IS NULL AND woip.work_order_id = work_orders.id AND work_order_items.id = (SELECT MIN(sub_woi.id) FROM work_order_items sub_woi WHERE sub_woi.work_order_id = work_orders.id AND sub_woi.status != 'cancelled'))) AND woip.status NOT IN ('cancelled', 'reversed') AND woip.deleted_at IS NULL {$sourceCondition})";

        return $query->select([
            'work_order_items.id as item_id',
            'work_order_items.work_order_id',
            'work_order_items.service_id',
            'work_order_items.department_id',
            'work_order_items.title as item_title',
            'work_order_items.status as service_status',
            'work_order_items.total as item_total',
            'work_order_items.line_total',
            'work_order_items.unit_price',
            'work_order_items.qty',
            'work_order_items.due_date as item_due_date',
            'work_orders.code as ticket_no',
            'work_orders.status as ticket_status',
            'work_orders.center_id',
            'work_orders.expected_end_date',
            'work_orders.entry_date',
            'work_orders.created_at as ticket_created_at',
            'work_orders.odometer as ticket_odometer',
            'work_orders.notes as ticket_notes',
            'centers.name as shop_name',
            'vehicles.id as vehicle_id',
            'vehicles.plate_number as plate_no',
            'vehicles.year as vehicle_year',
            'vehicles.odometer as vehicle_odometer',
            'vehicles.notes as vehicle_notes',
            'vehicle_makes.name_ar as make_name_ar',
            'vehicle_makes.name_en as make_name_en',
            'vehicles.make_other',
            'vehicle_models.name_ar as model_name_ar',
            'vehicle_models.name_en as model_name_en',
            'vehicles.model_other',
            'departments.name_ar as section_name',
            'services.name_ar as service_name',
            DB::raw("{$partsSubquery} as spare_part_cost"),
        ]);
    }
}
