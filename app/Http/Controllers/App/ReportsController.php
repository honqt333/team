<?php

declare(strict_types=1);

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\Center;
use App\Models\Customer;
use App\Models\Payment;
use App\Support\TenancyContext;
use Carbon\Carbon;
use Illuminate\Http\Request;
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
        $search = trim((string) ($request->input('search') ?? ''));

        [$from, $to] = $this->resolveDateRange($range, $startDate, $endDate);

        $query = $this->buildPaymentsQuery($tenantId, $centerId, $from, $to, $method, $type, $customerId, $search);

        // Calculate KPIs on filtered dataset
        $kpiQuery = clone $query;
        $totalCollected = (float) (clone $kpiQuery)->where('type', '!=', 'refund')->sum('amount');
        $totalRefunded = (float) (clone $kpiQuery)->where('type', 'refund')->sum('amount');
        $netCollected = $totalCollected - $totalRefunded;
        $totalCount = (int) (clone $kpiQuery)->count();

        // Breakdown by payment method
        $methodsBreakdown = (clone $kpiQuery)
            ->where('type', '!=', 'refund')
            ->selectRaw('payment_method, sum(amount) as total_amount, count(*) as count')
            ->groupBy('payment_method')
            ->get()
            ->map(fn ($row) => [
                'method' => $row->payment_method,
                'total' => (float) $row->total_amount,
                'count' => (int) $row->count,
            ]);

        // Paginated records
        $payments = $query
            ->with([
                'center:id,name',
                'invoice:id,invoice_number,customer_id,status,total_amount',
                'invoice.customer:id,name,phone',
                'workOrder:id,work_order_number,customer_id,vehicle_id,status',
                'workOrder.customer:id,name,phone',
                'workOrder.vehicle:id,plate_number,make,model,year',
                'purchaseInvoice:id,invoice_number,supplier_id',
                'purchaseInvoice.supplier:id,name',
                'receivedBy:id,name',
            ])
            ->orderBy('payment_date', 'desc')
            ->paginate(25)
            ->withQueryString();

        $tenant = auth()->user()->tenant;
        $currentCenter = $centerId ? Center::find($centerId) : null;

        $customers = Customer::query()
            ->where('tenant_id', $tenantId)
            ->select('id', 'name', 'phone')
            ->orderBy('name')
            ->limit(100)
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
                'search' => $search,
            ],
            'kpis' => [
                'total_collected' => $totalCollected,
                'total_refunded' => $totalRefunded,
                'net_collected' => $netCollected,
                'total_count' => $totalCount,
            ],
            'methodsBreakdown' => $methodsBreakdown,
            'customers' => $customers,
            'centers' => $centers,
            'companyInfo' => [
                'name' => $tenant->legal_name ?: $tenant->name,
                'vat_number' => $tenant->taxSettings?->tax_number ?? $tenant->vat_number ?? '',
                'cr_number' => $tenant->cr_number ?? '',
                'logo_path' => $tenant->logo_path ?? '',
                'center_name' => $currentCenter?->name ?? '',
            ],
            'payments' => $payments,
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
        $search = trim((string) ($request->input('search') ?? ''));

        [$from, $to] = $this->resolveDateRange($range, $startDate, $endDate);

        $query = $this->buildPaymentsQuery($tenantId, $centerId, $from, $to, $method, $type, $customerId, $search)
            ->with([
                'center:id,name',
                'invoice:id,invoice_number,customer_id',
                'invoice.customer:id,name,phone',
                'workOrder:id,work_order_number,customer_id,vehicle_id',
                'workOrder.customer:id,name,phone',
                'workOrder.vehicle:id,plate_number,make,model,year',
                'purchaseInvoice:id,invoice_number,supplier_id',
                'purchaseInvoice.supplier:id,name',
                'receivedBy:id,name',
            ])
            ->orderBy('payment_date', 'desc');

        $filename = 'تقرير_المدفوعات_'.now()->format('Y-m-d_His').'.csv';

        return response()->streamDownload(function () use ($query) {
            $output = fopen('php://output', 'w');
            // Write UTF-8 BOM for Excel
            fwrite($output, "\xEF\xBB\xBF");

            // CSV Header
            fputcsv($output, [
                'رقم المعاملة',
                'التاريخ',
                'نوع السند',
                'الطرف (العميل / المورد)',
                'رقم الهاتف',
                'رقم الفاتورة',
                'رقم أمر العمل',
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
                        ?? '—';

                    $invoiceNo = $payment->invoice?->invoice_number
                        ?? $payment->purchaseInvoice?->invoice_number
                        ?? '—';

                    $woNo = $payment->workOrder?->work_order_number ?? '—';
                    $plate = $payment->workOrder?->vehicle?->plate_number ?? '—';

                    $typeLabel = match ($payment->type) {
                        'refund' => 'استرجاع',
                        'bad_debt' => 'دين معدوم',
                        default => 'قبض',
                    };

                    fputcsv($output, [
                        $payment->reference ?: ('REC-'.$payment->id),
                        $payment->payment_date?->format('Y-m-d H:i') ?? '—',
                        $typeLabel,
                        $partyName,
                        $phone,
                        $invoiceNo,
                        $woNo,
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
    private function buildPaymentsQuery(
        int $tenantId,
        ?int $centerId,
        ?Carbon $from,
        ?Carbon $to,
        ?string $method = 'all',
        ?string $type = 'all',
        ?string $customerId = 'all',
        ?string $search = ''
    ) {
        $method = $method ?: 'all';
        $type = $type ?: 'all';
        $customerId = $customerId ?: 'all';
        $search = trim((string) $search);

        $query = Payment::query()->where('tenant_id', $tenantId);

        if ($centerId) {
            $query->where(function ($q) use ($centerId) {
                $q->where('center_id', $centerId)->orWhereNull('center_id');
            });
        }

        if ($from && $to) {
            $query->whereBetween('payment_date', [$from, $to]);
        }

        if (! empty($method) && $method !== 'all') {
            $query->where('payment_method', $method);
        }

        if (! empty($type) && $type !== 'all') {
            $query->where('type', $type);
        }

        if (! empty($customerId) && $customerId !== 'all') {
            $query->where(function ($q) use ($customerId) {
                $q->whereHas('invoice', fn ($iq) => $iq->where('customer_id', $customerId))
                    ->orWhereHas('workOrder', fn ($wq) => $wq->where('customer_id', $customerId));
            });
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
                        $wq->where('work_order_number', 'like', "%{$search}%")
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
}
