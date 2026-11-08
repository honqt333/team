<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Reports;

use App\Http\Controllers\Controller;
use App\Models\TaxPeriod;
use App\Models\Tenant;
use App\Services\Reports\TaxPeriodService;
use App\Support\TenancyContext;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TaxPeriodsController extends Controller
{
    public function __construct(
        protected TaxPeriodService $taxPeriodService
    ) {}

    /**
     * Display list of created tax periods and 3-year selector.
     */
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', TaxPeriod::class);

        $user = $request->user();
        $tenantId = (int) $user->tenant_id;
        $centerId = TenancyContext::centerId();

        $yearFilter = $request->input('year');
        $statusFilter = $request->input('status');

        $query = TaxPeriod::where('tenant_id', $tenantId)
            ->with(['creator:id,name', 'poster:id,name', 'center:id,name'])
            ->orderBy('start_date', 'desc');

        if ($yearFilter) {
            $query->where('year', (int) $yearFilter);
        }

        if ($statusFilter && in_array($statusFilter, ['draft', 'posted'])) {
            $query->where('status', $statusFilter);
        }

        $taxPeriods = $query->paginate(20)->withQueryString();

        $periods3Years = $this->taxPeriodService->get3YearsPeriods($tenantId, $centerId);

        return Inertia::render('Reports/TaxPeriods/Index', [
            'taxPeriods' => $taxPeriods,
            'periods3Years' => $periods3Years,
            'filters' => [
                'year' => $yearFilter ?? '',
                'status' => $statusFilter ?? '',
            ],
        ]);
    }

    /**
     * Return 3-year periods matrix for modal / carousel.
     */
    public function threeYears(Request $request): JsonResponse
    {
        $this->authorize('viewAny', TaxPeriod::class);

        $user = $request->user();
        $tenantId = (int) $user->tenant_id;
        $centerId = TenancyContext::centerId();

        $data = $this->taxPeriodService->get3YearsPeriods($tenantId, $centerId);

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * Preview and prepare creation of a tax period.
     */
    public function create(Request $request, string $periodKey): Response|RedirectResponse
    {
        $this->authorize('create', TaxPeriod::class);

        $user = $request->user();
        $tenantId = (int) $user->tenant_id;
        $centerId = TenancyContext::centerId();

        try {
            $meta = $this->taxPeriodService->parsePeriodKey($periodKey);
        } catch (InvalidArgumentException $e) {
            return redirect()->route('app.reports.tax-periods.index')
                ->with('error', __('reports.tax_periods_report.invalid_period_key'));
        }

        // Check if already posted
        $existing = TaxPeriod::where('tenant_id', $tenantId)
            ->where('period_key', $periodKey)
            ->first();

        if ($existing && $existing->isPosted()) {
            return redirect()->route('app.reports.tax-periods.show', $existing->id)
                ->with('info', __('reports.tax_periods_report.period_already_posted'));
        }

        $transactions = $this->taxPeriodService->collectTransactionsForPeriod(
            $tenantId,
            $meta['start_date'],
            $meta['end_date'],
            $centerId,
            $existing?->id
        );

        $summary = $this->taxPeriodService->computeSummary($transactions);

        return Inertia::render('Reports/TaxPeriods/Create', [
            'periodMeta' => $meta,
            'transactions' => $transactions,
            'summary' => $summary,
            'existingPeriod' => $existing,
        ]);
    }

    /**
     * Store newly created tax period.
     */
    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', TaxPeriod::class);

        $validated = $request->validate([
            'period_key' => ['required', 'string', 'regex:/^\d{4}-Q[1-4]$/'],
            'transactions' => ['required', 'array'],
            'transactions.*.transaction_type' => ['required', 'string'],
            'transactions.*.transaction_id' => ['nullable'],
            'transactions.*.ref_no' => ['required', 'string'],
            'transactions.*.transaction_date' => ['required', 'string'],
            'transactions.*.party_name' => ['nullable', 'string'],
            'transactions.*.tax_number' => ['nullable', 'string'],
            'transactions.*.is_income' => ['required', 'boolean'],
            'transactions.*.subtotal' => ['required', 'numeric'],
            'transactions.*.vat_amount' => ['required', 'numeric'],
            'transactions.*.total_amount' => ['required', 'numeric'],
            'transactions.*.is_selected' => ['required', 'boolean'],
            'notes' => ['nullable', 'string'],
        ]);

        $user = $request->user();
        $tenantId = (int) $user->tenant_id;
        $centerId = TenancyContext::centerId();

        try {
            $period = $this->taxPeriodService->createPeriod(
                $tenantId,
                $validated['period_key'],
                $validated['transactions'],
                $user->id,
                $centerId,
                $validated['notes'] ?? null
            );

            return redirect()->route('app.reports.tax-periods.show', $period->id)
                ->with('success', __('reports.tax_periods_report.period_saved_success'));
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Display Tax Period details & ZATCA VAT declaration.
     */
    public function show(Request $request, TaxPeriod $taxPeriod): Response
    {
        $this->authorize('view', $taxPeriod);

        $taxPeriod->load([
            'transactions' => fn ($q) => $q->orderBy('transaction_date', 'desc'),
            'creator:id,name',
            'poster:id,name',
            'center:id,name',
        ]);

        $user = $request->user();
        $tenant = Tenant::with('taxSettings')->find($taxPeriod->tenant_id);

        $vatSummary = [
            'income' => [
                'count' => $taxPeriod->income_transactions_count,
                'subtotal' => (float) $taxPeriod->sum_income_subtotal,
                'vat_amount' => (float) $taxPeriod->sum_income_vat_amount,
                'total_amount' => (float) $taxPeriod->sum_income_total_amount,
            ],
            'expense' => [
                'count' => $taxPeriod->expense_transactions_count,
                'subtotal' => (float) $taxPeriod->sum_expense_subtotal,
                'vat_amount' => (float) $taxPeriod->sum_expense_vat_amount,
                'total_amount' => (float) $taxPeriod->sum_expense_total_amount,
            ],
            'total' => [
                'count' => $taxPeriod->transactions_count,
                'subtotal' => (float) $taxPeriod->sum_subtotal,
                'vat_amount' => (float) $taxPeriod->sum_vat_amount,
                'total_amount' => (float) $taxPeriod->sum_total_amount,
                'net_vat_amount' => (float) $taxPeriod->net_vat_amount,
            ],
        ];

        return Inertia::render('Reports/TaxPeriods/Show', [
            'taxPeriod' => $taxPeriod,
            'summary' => $vatSummary,
            'transactions' => $taxPeriod->transactions,
            'tenant' => [
                'name' => $tenant?->legal_name ?: ($tenant?->trade_name ?: $tenant?->name),
                'vat_number' => $tenant?->taxSettings?->tax_number ?? $tenant?->tax_number ?? $tenant?->vat_number,
                'commercial_registration' => $tenant?->cr_number ?? $tenant?->commercial_registration,
                'address' => $tenant?->address,
            ],
        ]);
    }

    /**
     * Post (finalize and lock) tax period.
     */
    public function post(Request $request, TaxPeriod $taxPeriod): RedirectResponse
    {
        $this->authorize('post', $taxPeriod);

        try {
            $this->taxPeriodService->postPeriod($taxPeriod, $request->user()->id);

            return redirect()->route('app.reports.tax-periods.show', $taxPeriod->id)
                ->with('success', __('reports.tax_periods_report.period_posted_success'));
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Recreate tax period (re-scans and updates transactions from original records).
     */
    public function recreate(Request $request, TaxPeriod $taxPeriod): RedirectResponse
    {
        $this->authorize('update', $taxPeriod);

        try {
            $this->taxPeriodService->recreatePeriod($taxPeriod, $request->user()->id);

            return redirect()->route('app.reports.tax-periods.show', $taxPeriod->id)
                ->with('success', __('reports.tax_periods_report.period_recreated_success'));
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Export tax period transactions to Excel (CSV with UTF-8 BOM).
     */
    public function exportExcel(Request $request, TaxPeriod $taxPeriod): StreamedResponse
    {
        $this->authorize('view', $taxPeriod);

        $taxPeriod->load(['transactions' => fn ($q) => $q->orderBy('transaction_date', 'desc')]);

        $filename = "تقرير_الفترة_الضريبية_{$taxPeriod->period_key}_".now()->format('Y-m-d_His').'.csv';

        return response()->streamDownload(function () use ($taxPeriod) {
            $output = fopen('php://output', 'w');
            fwrite($output, "\xEF\xBB\xBF");

            // Header info
            fputcsv($output, ['تقرير الحركات الضريبية - '.$taxPeriod->name]);
            fputcsv($output, ['تاريخ البداية:', $taxPeriod->start_date->format('Y-m-d'), 'تاريخ النهاية:', $taxPeriod->end_date->format('Y-m-d')]);
            fputcsv($output, ['الحالة:', $taxPeriod->status === 'posted' ? 'مرحلة ومقفلة' : 'مسودة']);
            fputcsv($output, []); // empty line

            // Table headers
            fputcsv($output, [
                '#',
                'رقم المرجع',
                'تاريخ الحركة',
                'نوع الحركة الضريبية',
                'الطرف الآخر',
                'الرقم الضريبي',
                'المبلغ قبل الضريبة',
                'نسبة الضريبة',
                'مبلغ الضريبة',
                'الإجمالي شامل الضريبة',
                'مدرجة بالإقرار',
            ]);

            $index = 1;

            foreach ($taxPeriod->transactions as $tx) {
                fputcsv($output, [
                    $index++,
                    $tx->ref_no,
                    $tx->transaction_date ? $tx->transaction_date->format('Y-m-d') : '',
                    $this->formatTransactionTypeArabic($tx->transaction_type),
                    $tx->party_name ?? '-',
                    $tx->tax_number ?? '-',
                    number_format((float) $tx->subtotal, 2, '.', ''),
                    number_format((float) $tx->vat_rate, 2, '.', '').'%',
                    number_format((float) $tx->vat_amount, 2, '.', ''),
                    number_format((float) $tx->total_amount, 2, '.', ''),
                    $tx->is_selected ? 'نعم' : 'لا',
                ]);
            }

            fputcsv($output, []);
            fputcsv($output, ['الملخص الإجمالي']);
            fputcsv($output, ['إجمالي مبيعات وإيرادات قبل الضريبة', number_format((float) $taxPeriod->sum_income_subtotal, 2, '.', '')]);
            fputcsv($output, ['إجمالي ضريبة المبيعات والإيرادات', number_format((float) $taxPeriod->sum_income_vat_amount, 2, '.', '')]);
            fputcsv($output, ['إجمالي مشتريات ومصروفات قبل الضريبة', number_format((float) $taxPeriod->sum_expense_subtotal, 2, '.', '')]);
            fputcsv($output, ['إجمالي ضريبة المشتريات والمصروفات', number_format((float) $taxPeriod->sum_expense_vat_amount, 2, '.', '')]);
            fputcsv($output, ['صافي الضريبة المستحقة / (المستردة)', number_format((float) $taxPeriod->net_vat_amount, 2, '.', '')]);

            fclose($output);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    /**
     * Export official ZATCA VAT declaration layout.
     */
    public function exportZatca(Request $request, TaxPeriod $taxPeriod): StreamedResponse
    {
        $this->authorize('view', $taxPeriod);

        $tenant = Tenant::with('taxSettings')->find($taxPeriod->tenant_id);
        $companyName = $tenant?->legal_name ?: ($tenant?->trade_name ?: ($tenant?->name ?? ''));
        $taxNumber = $tenant?->taxSettings?->tax_number ?? $tenant?->tax_number ?? $tenant?->vat_number ?? '';
        $filename = "إقرار_ضريبة_القيمة_المضافة_ZATCA_{$taxPeriod->period_key}_".now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($taxPeriod, $companyName, $taxNumber) {
            $output = fopen('php://output', 'w');
            fwrite($output, "\xEF\xBB\xBF");

            fputcsv($output, ['هيئة الزكاة والضريبة والجمارك - ZATCA']);
            fputcsv($output, ['نموذج الإقرار الضريبي لضريبة القيمة المضافة / VAT Declaration Form']);
            fputcsv($output, ['اسم المنشأة:', $companyName]);
            fputcsv($output, ['الرقم الضريبي:', $taxNumber]);
            fputcsv($output, ['الفترة الضريبية:', $taxPeriod->name, 'من:', $taxPeriod->start_date->format('Y-m-d'), 'إلى:', $taxPeriod->end_date->format('Y-m-d')]);
            fputcsv($output, []);

            // ZATCA Boxes Header
            fputcsv($output, ['البند / Box', 'الوصف بالعربية', 'English Description', 'المبلغ الخاضع للضريبة (SAR)', 'مبلغ التعديل (SAR)', 'مبلغ الضريبة (SAR)']);

            // Box 1: Standard Rated 15% Sales
            fputcsv($output, [
                '1',
                'المبيعات الخاضعة للنسبة الأساسية (15%)',
                'Standard rated 15% sales',
                number_format((float) $taxPeriod->sum_income_subtotal, 2, '.', ''),
                '0.00',
                number_format((float) $taxPeriod->sum_income_vat_amount, 2, '.', ''),
            ]);

            // Box 2: Special Sales
            fputcsv($output, ['2', 'المبيعات للمواطنين (الخدمات الصحية / التعليم الأهلي)', 'Sales to citizens', '0.00', '0.00', '0.00']);
            // Box 3: Zero Rated
            fputcsv($output, ['3', 'المبيعات المحلية الخاضعة للنسبة الصفرية', 'Zero rated domestic sales', '0.00', '0.00', '0.00']);
            // Box 4: Exports
            fputcsv($output, ['4', 'الصادرات', 'Exports', '0.00', '0.00', '0.00']);
            // Box 5: Exempt Sales
            fputcsv($output, ['5', 'المبيعات المعفاة من الضريبة', 'Exempt sales', '0.00', '0.00', '0.00']);

            // Box 6: Total Sales
            fputcsv($output, [
                '6',
                'إجمالي المبيعات (1 + 2 + 3 + 4 + 5)',
                'Total Sales',
                number_format((float) $taxPeriod->sum_income_subtotal, 2, '.', ''),
                '0.00',
                number_format((float) $taxPeriod->sum_income_vat_amount, 2, '.', ''),
            ]);

            fputcsv($output, []);

            // Box 7: Standard Rated 15% Purchases
            fputcsv($output, [
                '7',
                'المشتريات الخاضعة للنسبة الأساسية (15%)',
                'Standard rated 15% purchases',
                number_format((float) $taxPeriod->sum_expense_subtotal, 2, '.', ''),
                '0.00',
                number_format((float) $taxPeriod->sum_expense_vat_amount, 2, '.', ''),
            ]);

            // Box 8: Imports subject to VAT paid at customs
            fputcsv($output, ['8', 'الاستيرادات الخاضعة للضريبة المدفوعة في الجمارك', 'Imports paid at customs', '0.00', '0.00', '0.00']);
            // Box 9: Imports reverse charge
            fputcsv($output, ['9', 'الاستيرادات الخاضعة للضريبة بآلية الاحتساب العكسي', 'Imports subject to reverse charge', '0.00', '0.00', '0.00']);
            // Box 10: Zero rated purchases
            fputcsv($output, ['10', 'المشتريات الخاضعة للنسبة الصفرية', 'Zero rated purchases', '0.00', '0.00', '0.00']);
            // Box 11: Exempt purchases
            fputcsv($output, ['11', 'المشتريات المعفاة من الضريبة', 'Exempt purchases', '0.00', '0.00', '0.00']);

            // Box 12: Total Purchases
            fputcsv($output, [
                '12',
                'إجمالي المشتريات (7 + 8 + 9 + 10 + 11)',
                'Total Purchases',
                number_format((float) $taxPeriod->sum_expense_subtotal, 2, '.', ''),
                '0.00',
                number_format((float) $taxPeriod->sum_expense_vat_amount, 2, '.', ''),
            ]);

            fputcsv($output, []);

            // Box 13: Net VAT
            fputcsv($output, [
                '13',
                'إجمالي ضريبة القيمة المضافة المستحقة للفترة الحالية (6 - 12)',
                'Total VAT due for the current period',
                '-',
                '-',
                number_format((float) $taxPeriod->net_vat_amount, 2, '.', ''),
            ]);

            fclose($output);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    private function formatTransactionTypeArabic(string $type): string
    {
        return match ($type) {
            'sales_invoice' => 'فاتورة مبيعات',
            'credit_note' => 'إشعار دائن (مرتجع مبيعات)',
            'purchase_invoice' => 'فاتورة مشتريات',
            'purchase_return' => 'إشعار مدين (مرتجع مشتريات)',
            'company_transaction' => 'حركة مالية (إيراد/مصروف)',
            default => $type,
        };
    }
}
