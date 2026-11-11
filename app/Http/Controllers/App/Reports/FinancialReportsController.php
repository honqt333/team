<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Reports;

use App\Http\Controllers\App\Reports\Concerns\ReportScopingTrait;
use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\User;
use App\Services\Reports\CashRegisterReportService;
use App\Services\Reports\FinancialSummaryReportService;
use App\Support\TenancyContext;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FinancialReportsController extends Controller
{
    use ReportScopingTrait;

    public function __construct(
        protected FinancialSummaryReportService $financialSummaryService,
        protected CashRegisterReportService $cashRegisterService
    ) {}

    /**
     * Dedicated Financial Summary & P&L Statement Report.
     */
    public function financialSummary(Request $request): Response
    {
        $this->authorize('viewAny', Invoice::class);

        $tenantId = (int) auth()->user()->tenant_id;
        $centerId = TenancyContext::centerId();

        $range = (string) ($request->input('range') ?: 'this_month');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $requestedCenter = $request->input('center_id');

        if ($requestedCenter === 'admin' || $requestedCenter === '0') {
            $centerFilter = 'admin';
        } elseif ($requestedCenter === 'all' || ! $request->filled('center_id')) {
            $centerFilter = 'all';
        } else {
            $centerFilter = (string) $requestedCenter;
        }
        $storeSalesOnly = $request->boolean('store_sales_only');

        [$from, $to] = $this->resolveDateRange($range, $startDate, $endDate);

        $accessibleCenters = $this->resolveAccessibleCenters($tenantId);

        $targetCenters = $accessibleCenters;

        if ($centerFilter !== 'all' && is_numeric($centerFilter)) {
            $targetCenters = $accessibleCenters->where('id', (int) $centerFilter);
        }

        $includeAdmin = ($centerFilter === 'all' || $centerFilter === 'admin' || $centerFilter === '0');
        $includeBranches = ($centerFilter !== 'admin' && $centerFilter !== '0');

        $results = [];
        $overall = [
            'invoicesServicesSumGrossAmount' => 0.0,
            'invoicesServicesSumDiscountAmount' => 0.0,
            'invoicesServicesSumSubTotal' => 0.0,
            'invoicesServicesSumVATAmount' => 0.0,
            'invoicesServicesSumNetAmount' => 0.0,

            'invoicesSparePartsSumGrossAmount' => 0.0,
            'invoicesSparePartsSumDiscountAmount' => 0.0,
            'invoicesSparePartsSumSubTotal' => 0.0,
            'invoicesSparePartsSumVATAmount' => 0.0,
            'invoicesSparePartsSumNetAmount' => 0.0,

            'invoicesSumGrossAmount' => 0.0,
            'invoicesSumDiscountAmount' => 0.0,
            'invoicesSumSubTotal' => 0.0,
            'invoicesSumVATAmount' => 0.0,
            'invoicesSumNetAmount' => 0.0,

            'returnInvoicesServicesSumGrossAmount' => 0.0,
            'returnInvoicesServicesSumDiscountAmount' => 0.0,
            'returnInvoicesServicesSumSubTotal' => 0.0,
            'returnInvoicesServicesSumVATAmount' => 0.0,
            'returnInvoicesServicesSumNetAmount' => 0.0,

            'returnInvoicesSparePartsSumGrossAmount' => 0.0,
            'returnInvoicesSparePartsSumDiscountAmount' => 0.0,
            'returnInvoicesSparePartsSumSubTotal' => 0.0,
            'returnInvoicesSparePartsSumVATAmount' => 0.0,
            'returnInvoicesSparePartsSumNetAmount' => 0.0,

            'returnInvoicesSumGrossAmount' => 0.0,
            'returnInvoicesSumDiscountAmount' => 0.0,
            'returnInvoicesSumSubTotal' => 0.0,
            'returnInvoicesSumVATAmount' => 0.0,
            'returnInvoicesSumNetAmount' => 0.0,

            'receivedSparePartsSumGrossAmount' => 0.0,
            'receivedSparePartsSumDiscountAmount' => 0.0,
            'receivedSparePartsSumSubTotal' => 0.0,
            'receivedSparePartsSumVATAmount' => 0.0,
            'receivedSparePartsSumNetAmount' => 0.0,

            'returnedSparePartsSumGrossAmount' => 0.0,
            'returnedSparePartsSumDiscountAmount' => 0.0,
            'returnedSparePartsSumSubTotal' => 0.0,
            'returnedSparePartsSumVATAmount' => 0.0,
            'returnedSparePartsSumNetAmount' => 0.0,

            'purchaseSparePartsSumGrossAmount' => 0.0,
            'purchaseSparePartsSumDiscountAmount' => 0.0,
            'purchaseSparePartsSumSubTotal' => 0.0,
            'purchaseSparePartsSumVATAmount' => 0.0,
            'purchaseSparePartsSumNetAmount' => 0.0,

            'incomeSumGrossAmount' => 0.0,
            'incomeSumDiscountAmount' => 0.0,
            'incomeSumSubTotal' => 0.0,
            'incomeSumVATAmount' => 0.0,
            'incomeSumNetAmount' => 0.0,

            'expenseSumGrossAmount' => 0.0,
            'expenseSumDiscountAmount' => 0.0,
            'expenseSumSubTotal' => 0.0,
            'expenseSumVATAmount' => 0.0,
            'expenseSumNetAmount' => 0.0,

            'incomeExpenseSumGrossAmount' => 0.0,
            'incomeExpenseSumDiscountAmount' => 0.0,
            'incomeExpenseSumSubTotal' => 0.0,
            'incomeExpenseSumVATAmount' => 0.0,
            'incomeExpenseSumNetAmount' => 0.0,

            'sumGrossAmount' => 0.0,
            'sumDiscountAmount' => 0.0,
            'sumSubTotal' => 0.0,
            'sumVATAmount' => 0.0,
            'sumNetAmount' => 0.0,

            'totalPayrollSalaryPayments' => 0.0,
            'totalOtherSalaryPayments' => 0.0,
            'totalSalary' => 0.0,
            'sumBadDebt' => 0.0,
            'sumFinalAmount' => 0.0,
            'profit' => 0.0,
        ];

        $tenant = auth()->user()->tenant;
        $adminName = __('messages.company_transactions.administration_center', [], app()->getLocale())
            ?: (app()->getLocale() === 'ar' ? 'الإدارة العامة' : 'Administration');

        // 1. Administration statement (center_id = null)
        if ($includeAdmin) {
            $adminStatement = $this->financialSummaryService->computeCenterFinancialStatement(
                $tenantId,
                null,
                $from,
                $to,
                $storeSalesOnly
            );

            $results[] = [
                'center_id' => null,
                'center_name' => $adminName,
                'is_administration' => true,
                'shop' => [
                    'id' => 'admin',
                    'center_id' => null,
                    'name' => $adminName,
                    'center_name' => $adminName,
                    'address' => $tenant->address ?? '',
                    'address_details' => null,
                    'postalCode' => '',
                    'city' => $tenant->city ?? '',
                    'district' => '',
                    'region' => '',
                    'country' => 'المملكة العربية السعودية',
                    'isWorkShop' => false,
                    'is_administration' => true,
                    'commercial_register' => $tenant->cr_number ?? '',
                    'vat_number' => $tenant->taxSettings->tax_number ?? $tenant->tax_number ?? $tenant->vat_number ?? '',
                    'phone' => $tenant->phone ?? '',
                ],
                'shopFinancialSummary' => $adminStatement,
            ];

            foreach ($overall as $key => $val) {
                if (isset($adminStatement[$key])) {
                    $overall[$key] += (float) $adminStatement[$key];
                }
            }
        }

        // 2. Physical branches statements
        if ($includeBranches) {
            $targetCenters->load('address');

            foreach ($targetCenters as $c) {
                $statement = $this->financialSummaryService->computeCenterFinancialStatement($tenantId, (int) $c->id, $from, $to, $storeSalesOnly);

                $addr = $c->address;
                $addressParts = [];

                if ($addr) {
                    if (! empty($addr->city)) {
                        $addressParts[] = $addr->city;
                    }

                    if (! empty($addr->district)) {
                        $addressParts[] = 'حي '.$addr->district;
                    }

                    if (! empty($addr->street)) {
                        $addressParts[] = 'شارع '.$addr->street;
                    }

                    if (! empty($addr->building_number)) {
                        $addressParts[] = 'مبنى '.$addr->building_number;
                    }

                    if (! empty($addr->address_line)) {
                        $addressParts[] = $addr->address_line;
                    }
                }
                $formattedAddress = implode('، ', $addressParts);

                $results[] = [
                    'center_id' => (int) $c->id,
                    'center_name' => $c->name,
                    'is_administration' => false,
                    'shop' => [
                        'id' => $c->id,
                        'center_id' => (int) $c->id,
                        'name' => $c->name,
                        'center_name' => $c->name,
                        'address' => $formattedAddress,
                        'address_details' => $addr ? [
                            'city' => $addr->city,
                            'district' => $addr->district,
                            'street' => $addr->street,
                            'building_number' => $addr->building_number,
                            'postal_code' => $addr->postal_code,
                        ] : null,
                        'postalCode' => $addr->postal_code ?? '',
                        'city' => $addr->city ?? '',
                        'district' => $addr->district ?? '',
                        'region' => $addr->city ?? '',
                        'country' => 'المملكة العربية السعودية',
                        'isWorkShop' => true,
                        'is_administration' => false,
                        'commercial_register' => $c->license_number ?: ($tenant->cr_number ?? ''),
                        'vat_number' => $c->vat_number ?: ($tenant->vat_number ?? ''),
                        'phone' => $c->phone ?: ($tenant->phone ?? ''),
                    ],
                    'shopFinancialSummary' => $statement,
                ];

                foreach ($overall as $key => $val) {
                    if (isset($statement[$key])) {
                        $overall[$key] += (float) $statement[$key];
                    }
                }
            }
        }

        return Inertia::render('Reports/FinancialSummary', [
            'filters' => [
                'range' => $range,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'center_id' => $centerFilter,
                'store_sales_only' => $storeSalesOnly,
            ],
            'reportTitle' => __('reports.financial_summary_report.title'),
            'result' => $results,
            'overallSummary' => $overall,
            'centers' => $accessibleCenters,
            'companyInfo' => $this->resolveReportCompanyInfo($tenant, $centerFilter, $centerId),
        ]);
    }

    /**
     * Export Financial Summary Report as CSV (UTF-8 BOM).
     */
    public function exportFinancialSummary(Request $request): StreamedResponse
    {
        $this->authorize('viewAny', Invoice::class);

        $tenantId = (int) auth()->user()->tenant_id;
        $centerId = TenancyContext::centerId();

        $range = (string) ($request->input('range') ?: 'this_month');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $requestedCenter = $request->input('center_id');

        if ($requestedCenter === 'admin' || $requestedCenter === '0') {
            $centerFilter = 'admin';
        } elseif ($requestedCenter === 'all' || ! $request->filled('center_id')) {
            $centerFilter = 'all';
        } else {
            $centerFilter = (string) $requestedCenter;
        }
        $storeSalesOnly = $request->boolean('store_sales_only');

        [$from, $to] = $this->resolveDateRange($range, $startDate, $endDate);

        $accessibleCenters = $this->resolveAccessibleCenters($tenantId);
        $targetCenters = $accessibleCenters;

        if ($centerFilter !== 'all' && is_numeric($centerFilter)) {
            $targetCenters = $accessibleCenters->where('id', (int) $centerFilter);
        }

        $includeAdmin = ($centerFilter === 'all' || $centerFilter === 'admin' || $centerFilter === '0');
        $includeBranches = ($centerFilter !== 'admin' && $centerFilter !== '0');

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="financial_summary_'.date('Y-m-d_His').'.csv"',
        ];

        return response()->stream(function () use ($targetCenters, $tenantId, $from, $to, $storeSalesOnly, $includeAdmin, $includeBranches) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($handle, [
                'الفرع',
                'التصنيف الرئيسي',
                'البند التفصيلي',
                'المبلغ الإجمالي',
                'الخصم',
                'المجموع الخاضع للضريبة',
                'ضريبة القيمة المضافة',
                'الصافي شامل الضريبة',
            ]);

            $writeStatementRows = function (string $shopName, array $st) use ($handle) {
                $rows = [
                    ['فواتير المبيعات', 'الخدمات', $st['invoicesServicesSumGrossAmount'], $st['invoicesServicesSumDiscountAmount'], $st['invoicesServicesSumSubTotal'], $st['invoicesServicesSumVATAmount'], $st['invoicesServicesSumNetAmount']],
                    ['فواتير المبيعات', 'قطع الغيار', $st['invoicesSparePartsSumGrossAmount'], $st['invoicesSparePartsSumDiscountAmount'], $st['invoicesSparePartsSumSubTotal'], $st['invoicesSparePartsSumVATAmount'], $st['invoicesSparePartsSumNetAmount']],
                    ['فواتير المبيعات', 'إجمالي المبيعات', $st['invoicesSumGrossAmount'], $st['invoicesSumDiscountAmount'], $st['invoicesSumSubTotal'], $st['invoicesSumVATAmount'], $st['invoicesSumNetAmount']],

                    ['فواتير المرتجعات', 'الخدمات المرتجعة', $st['returnInvoicesServicesSumGrossAmount'], $st['returnInvoicesServicesSumDiscountAmount'], $st['returnInvoicesServicesSumSubTotal'], $st['returnInvoicesServicesSumVATAmount'], $st['returnInvoicesServicesSumNetAmount']],
                    ['فواتير المرتجعات', 'قطع الغيار المرتجعة', $st['returnInvoicesSparePartsSumGrossAmount'], $st['returnInvoicesSparePartsSumDiscountAmount'], $st['returnInvoicesSparePartsSumSubTotal'], $st['returnInvoicesSparePartsSumVATAmount'], $st['returnInvoicesSparePartsSumNetAmount']],
                    ['فواتير المرتجعات', 'إجمالي المرتجعات', $st['returnInvoicesSumGrossAmount'], $st['returnInvoicesSumDiscountAmount'], $st['returnInvoicesSumSubTotal'], $st['returnInvoicesSumVATAmount'], $st['returnInvoicesSumNetAmount']],

                    ['حركات المخزون والمشتريات', 'المستلم من الموردين', $st['receivedSparePartsSumGrossAmount'], $st['receivedSparePartsSumDiscountAmount'], $st['receivedSparePartsSumSubTotal'], $st['receivedSparePartsSumVATAmount'], $st['receivedSparePartsSumNetAmount']],
                    ['حركات المخزون والمشتريات', 'المرتجع إلى الموردين', $st['returnedSparePartsSumGrossAmount'], $st['returnedSparePartsSumDiscountAmount'], $st['returnedSparePartsSumSubTotal'], $st['returnedSparePartsSumVATAmount'], $st['returnedSparePartsSumNetAmount']],
                    ['حركات المخزون والمشتريات', 'صافي المشتريات', $st['purchaseSparePartsSumGrossAmount'], $st['purchaseSparePartsSumDiscountAmount'], $st['purchaseSparePartsSumSubTotal'], $st['purchaseSparePartsSumVATAmount'], $st['purchaseSparePartsSumNetAmount']],

                    ['الإيرادات والمصروفات الأخرى', 'إيرادات أخرى', $st['incomeSumGrossAmount'], $st['incomeSumDiscountAmount'], $st['incomeSumSubTotal'], $st['incomeSumVATAmount'], $st['incomeSumNetAmount']],
                    ['الإيرادات والمصروفات الأخرى', 'مصروفات تشغيلية', $st['expenseSumGrossAmount'], $st['expenseSumDiscountAmount'], $st['expenseSumSubTotal'], $st['expenseSumVATAmount'], $st['expenseSumNetAmount']],
                    ['الإيرادات والمصروفات الأخرى', 'صافي الإيرادات والمصروفات', $st['incomeExpenseSumGrossAmount'], $st['incomeExpenseSumDiscountAmount'], $st['incomeExpenseSumSubTotal'], $st['incomeExpenseSumVATAmount'], $st['incomeExpenseSumNetAmount']],

                    ['إجمالي العمليات', 'إجمالي العمليات التشغيلية', $st['sumGrossAmount'], $st['sumDiscountAmount'], $st['sumSubTotal'], $st['sumVATAmount'], $st['sumNetAmount']],

                    ['الرواتب والأجور', 'مسير الرواتب المعتمد', 0.0, 0.0, 0.0, 0.0, $st['totalPayrollSalaryPayments']],
                    ['الرواتب والأجور', 'مكافآت ومدفوعات أخرى', 0.0, 0.0, 0.0, 0.0, $st['totalOtherSalaryPayments']],
                    ['الرواتب والأجور', 'إجمالي الرواتب والأجور', 0.0, 0.0, 0.0, 0.0, $st['totalSalary']],

                    ['الديون المعدومة', 'الديون المعدومة', 0.0, 0.0, 0.0, 0.0, $st['sumBadDebt']],

                    ['الخلاصة المالية', 'المبلغ الإجمالي الصافي', 0.0, 0.0, 0.0, 0.0, $st['sumFinalAmount']],
                    ['الخلاصة المالية', 'ضريبة القيمة المضافة المستحقة', 0.0, 0.0, 0.0, $st['sumVATAmount'], -$st['sumVATAmount']],
                    ['الخلاصة المالية', 'صافي الربح التشغيلي', 0.0, 0.0, 0.0, 0.0, $st['profit']],
                ];

                foreach ($rows as $row) {
                    fputcsv($handle, [
                        $shopName,
                        $row[0],
                        $row[1],
                        number_format((float) $row[2], 2, '.', ''),
                        number_format((float) $row[3], 2, '.', ''),
                        number_format((float) $row[4], 2, '.', ''),
                        number_format((float) $row[5], 2, '.', ''),
                        number_format((float) $row[6], 2, '.', ''),
                    ]);
                }
            };

            if ($includeAdmin) {
                $adminStatement = $this->financialSummaryService->computeCenterFinancialStatement($tenantId, null, $from, $to, $storeSalesOnly);
                $adminName = __('messages.company_transactions.administration_center', [], app()->getLocale())
                    ?: (app()->getLocale() === 'ar' ? 'الإدارة العامة' : 'Administration');
                $writeStatementRows($adminName, $adminStatement);
            }

            if ($includeBranches) {
                foreach ($targetCenters as $c) {
                    $st = $this->financialSummaryService->computeCenterFinancialStatement($tenantId, (int) $c->id, $from, $to, $storeSalesOnly);
                    $writeStatementRows($c->name, $st);
                }
            }

            fclose($handle);
        }, 200, $headers);
    }

    /**
     * Dedicated Cash Register & Daily Cash Flow Report.
     */
    public function cashRegister(Request $request): Response
    {
        $this->authorize('viewAny', Payment::class);

        $tenantId = (int) auth()->user()->tenant_id;
        $centerId = TenancyContext::centerId();

        $range = (string) ($request->input('range') ?: 'this_month');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $centerFilter = $this->resolveCenterFilter($request, $centerId);
        $paymentMethod = (string) ($request->input('payment_method') ?: 'all');
        $transactionType = (string) ($request->input('transaction_type') ?: 'all');
        $userFilter = (string) ($request->input('user_id') ?: 'all');
        $search = trim((string) ($request->input('search') ?? ''));
        $sortBy = (string) ($request->input('sort_by') ?: 'transaction_date');
        $sortOrder = strtolower((string) ($request->input('sort_order') ?: 'desc'));

        [$from, $to] = $this->resolveDateRange($range, $startDate, $endDate);

        $items = $this->cashRegisterService->buildCashRegisterData(
            $tenantId,
            $centerId,
            $from,
            $to,
            $paymentMethod,
            $transactionType,
            $userFilter,
            $centerFilter,
            $search,
            $this->accessibleCenterIds()
        );

        // Sorting
        $sortedItems = $items->sortBy([
            [$sortBy === 'transaction_date' ? 'timestamp' : $sortBy, $sortOrder === 'asc' ? 'asc' : 'desc'],
        ])->values();

        // Calculate KPIs
        $totalRecords = $sortedItems->count();
        $totalDebit = (float) $sortedItems->sum('debit');
        $totalCredit = (float) $sortedItems->sum('credit');
        $netBalance = $totalDebit - $totalCredit;
        $cashBalance = (float) $sortedItems->where('payment_mode', 'cash')->sum(fn ($i) => $i['debit'] - $i['credit']);
        $electronicBalance = $netBalance - $cashBalance;

        // Paginate
        $currentPage = LengthAwarePaginator::resolveCurrentPage('page');
        $perPage = 25;
        $currentPageItems = $sortedItems->slice(($currentPage - 1) * $perPage, $perPage)->values();

        $paginated = new LengthAwarePaginator(
            $currentPageItems,
            $totalRecords,
            $perPage,
            $currentPage,
            ['path' => LengthAwarePaginator::resolveCurrentPath()]
        );
        $paginated->withQueryString();

        $accessibleCenters = $this->resolveAccessibleCenters($tenantId);
        $tenantUsers = User::query()
            ->where('tenant_id', $tenantId)
            ->select('id', 'name')
            ->orderBy('name')
            ->get();

        $tenant = auth()->user()->tenant;

        return Inertia::render('Reports/CashRegister', [
            'filters' => [
                'range' => $range,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'center_id' => $centerFilter,
                'payment_method' => $paymentMethod,
                'transaction_type' => $transactionType,
                'user_id' => $userFilter,
                'search' => $search,
                'sort_by' => $sortBy,
                'sort_order' => $sortOrder,
            ],
            'reportTitle' => __('reports.cash_register_report.title'),
            'items' => $paginated,
            'kpis' => [
                'total_records' => $totalRecords,
                'total_debit' => $totalDebit,
                'total_credit' => $totalCredit,
                'net_balance' => $netBalance,
                'cash_balance' => $cashBalance,
                'electronic_balance' => $electronicBalance,
            ],
            'centers' => $accessibleCenters,
            'users' => $tenantUsers,
            'companyInfo' => $this->resolveReportCompanyInfo($tenant, $centerFilter, $centerId),
        ]);
    }

    /**
     * Export Cash Register Report as CSV (UTF-8 BOM).
     */
    public function exportCashRegister(Request $request): StreamedResponse
    {
        $this->authorize('viewAny', Payment::class);

        $tenantId = (int) auth()->user()->tenant_id;
        $centerId = TenancyContext::centerId();

        $range = (string) ($request->input('range') ?: 'this_month');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $centerFilter = $this->resolveCenterFilter($request, $centerId);
        $paymentMethod = (string) ($request->input('payment_method') ?: 'all');
        $transactionType = (string) ($request->input('transaction_type') ?: 'all');
        $userFilter = (string) ($request->input('user_id') ?: 'all');
        $search = trim((string) ($request->input('search') ?? ''));
        $sortBy = (string) ($request->input('sort_by') ?: 'transaction_date');
        $sortOrder = strtolower((string) ($request->input('sort_order') ?: 'desc'));

        [$from, $to] = $this->resolveDateRange($range, $startDate, $endDate);

        $items = $this->cashRegisterService->buildCashRegisterData(
            $tenantId,
            $centerId,
            $from,
            $to,
            $paymentMethod,
            $transactionType,
            $userFilter,
            $centerFilter,
            $search,
            $this->accessibleCenterIds()
        );

        $sortedItems = $items->sortBy([
            [$sortBy === 'transaction_date' ? 'timestamp' : $sortBy, $sortOrder === 'asc' ? 'asc' : 'desc'],
        ])->values();

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="cash_register_'.date('Y-m-d_His').'.csv"',
        ];

        return response()->stream(function () use ($sortedItems) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($handle, [
                '#',
                'الفرع',
                'رقم المرجع',
                'البيان / الوصف',
                'نوع الحركة / التصنيف',
                'طريقة الدفع',
                'تاريخ ووقت المعاملة',
                'المبلغ المقبوض (وارد)',
                'المبلغ المدفوع (صادر)',
                'المستخدم المسؤول',
            ]);

            $index = 1;

            foreach ($sortedItems as $row) {
                fputcsv($handle, [
                    $index++,
                    $row['center_name'],
                    $row['ref_no'],
                    $row['description'],
                    $row['category_label'],
                    $row['payment_mode_label'],
                    $row['transaction_date'],
                    number_format((float) $row['debit'], 2, '.', ''),
                    number_format((float) $row['credit'], 2, '.', ''),
                    $row['user_name'],
                ]);
            }

            fclose($handle);
        }, 200, $headers);
    }
}
