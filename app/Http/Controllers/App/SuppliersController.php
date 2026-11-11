<?php

declare(strict_types=1);

namespace App\Http\Controllers\App;

use App\Exceptions\PaymentExceedsBalanceException;
use App\Exports\SuppliersTemplateExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\App\Purchasing\SupplierRequest;
use App\Imports\SuppliersImport;
use App\Models\InventoryUnit;
use App\Models\Payment;
use App\Models\PurchaseInvoice;
use App\Models\Supplier;
use App\Models\Warehouse;
use App\Services\PaymentService;
use App\Support\CountryCurrencyRegistry;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use InvalidArgumentException;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class SuppliersController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', Supplier::class);

        $tenantId = auth()->user()->tenant_id;
        $isSuperAdmin = auth()->user()->hasRole('super_admin');
        // Filter by user's current center unless super admin wants to see all (and didnt filter)
        // But usually, 'Not isolated' complaint suggests strict isolation.
        // I will use current_center_id logic similar to Employee.
        $centerId = $isSuperAdmin ? $request->center_id : auth()->user()->current_center_id;

        $query = Supplier::forTenant($tenantId)
            ->when(! $isSuperAdmin || $centerId, fn ($q) => $q->forCenter($centerId))
            ->search($request->input('search'))
            ->when($request->input('status') === 'active', fn ($q) => $q->active())
            ->when($request->input('status') === 'inactive', fn ($q) => $q->where('is_active', false))
            ->when($request->input('type'), fn ($q, $type) => $q->where('type', $type))
            ->withCount('purchaseOrders');

        $sort = (string) $request->input('sort');
        $direction = strtolower((string) $request->input('direction', 'asc')) === 'desc' ? 'desc' : 'asc';

        if (in_array($sort, ['name', 'contact_person', 'phone', 'type', 'is_active', 'created_at', 'id'], true)) {
            $query->orderBy("suppliers.{$sort}", $direction);
        } elseif ($sort === 'status') {
            $query->orderBy('suppliers.is_active', $direction);
        } elseif ($sort === 'orders_count' || $sort === 'purchase_orders_count') {
            $query->orderBy('purchase_orders_count', $direction);
        } else {
            $query->orderBy('suppliers.name', 'asc');
        }

        $suppliers = $query->paginate(25)->withQueryString();

        // Calculate real balance using calculateBalance helper
        $suppliers->getCollection()->transform(function ($supplier) {
            $supplier->balance = $supplier->calculateBalance();

            return $supplier;
        });

        return Inertia::render('Purchasing/Suppliers/Index', [
            'suppliers' => $suppliers,
            'filters' => array_merge(
                $request->only(['search', 'status', 'type']),
                [
                    'sort' => $sort ?: null,
                    'direction' => $sort ? $direction : null,
                ]
            ),
        ]);
    }

    public function show(Supplier $supplier)
    {
        $this->authorize('view', $supplier);

        $supplier->loadCount('purchaseOrders');
        $supplier->load([
            'purchaseInvoices' => fn ($q) => $q->latest(),
            'returnInvoices' => fn ($q) => $q->with('purchaseInvoice')->latest('return_date'),
            'payments' => fn ($q) => $q->with(['receivedBy', 'purchaseInvoice'])->latest(),
        ]);

        $counts = [
            'orders' => $supplier->purchase_orders_count,
            'invoices' => $supplier->purchaseInvoices->count(),
            'returns' => $supplier->returnInvoices->count(),
            'payments' => $supplier->payments->count(),
        ];

        // Calculate balance using calculateBalance helper
        $balance = $supplier->calculateBalance();

        $tenantId = auth()->user()->tenant_id;
        $centerId = auth()->user()->current_center_id;

        $suppliers = Supplier::forTenant($tenantId)->forCenter($centerId)->active()->get(['id', 'name']);
        $defaultWarehouse = Warehouse::forCenter($centerId)->default()->first();
        $warehouses = Warehouse::forCenter($centerId)->active()->get(['id', 'name']);
        $units = InventoryUnit::where('is_active', true)->get(['id', 'name_ar', 'name_en']);

        return Inertia::render('Purchasing/Suppliers/Show', [
            'supplier' => $supplier,
            'purchaseInvoices' => $supplier->purchaseInvoices,
            'returnInvoices' => $supplier->returnInvoices,
            'payments' => $supplier->payments,
            'counts' => $counts,
            'balance' => $balance,
            'suppliers' => $suppliers,
            'defaultWarehouse' => $defaultWarehouse,
            'warehouses' => $warehouses,
            'units' => $units,
        ]);
    }

    public function store(SupplierRequest $request)
    {
        $this->authorize('create', Supplier::class);

        $validated = $request->validated();
        $validated['tenant_id'] = auth()->user()->tenant_id;
        $validated['center_id'] = auth()->user()->current_center_id;

        Supplier::create($validated);

        return redirect()->back()
            ->with('success', __('purchasing.suppliers.created'));
    }

    public function update(SupplierRequest $request, Supplier $supplier)
    {
        $this->authorize('update', $supplier);

        $supplier->update($request->validated());

        return redirect()->back()->with('success', __('purchasing.suppliers.updated'));
    }

    public function destroy(Supplier $supplier)
    {
        $this->authorize('delete', $supplier);

        if ($supplier->purchaseOrders()->exists()) {
            return back()->with('error', __('common.cannot_delete_system_data'));
        }

        $supplier->delete();

        return redirect()->route('app.purchasing.suppliers.index')
            ->with('success', __('common.deleted_success'));
    }

    public function toggleActive(Supplier $supplier)
    {
        $this->authorize('update', $supplier);

        $supplier->update(['is_active' => ! $supplier->is_active]);

        return back()->with('success', $supplier->is_active
            ? __('purchasing.suppliers.activated')
            : __('purchasing.suppliers.deactivated'));
    }

    /**
     * API: Search suppliers for autocomplete.
     */
    public function search(Request $request)
    {
        $this->authorize('viewAny', Supplier::class);

        $tenantId = auth()->user()->tenant_id;
        $centerId = auth()->user()->current_center_id;

        $suppliers = Supplier::forTenant($tenantId)
            ->forCenter($centerId)
            ->active()
            ->search($request->input('q'))
            ->select('id', 'name', 'code', 'phone')
            ->limit(20)
            ->get();

        return response()->json($suppliers);
    }

    /**
     * Export suppliers to Excel (XLSX).
     */
    public function export(Request $request)
    {
        $this->authorize('viewAny', Supplier::class);

        // Set locale from request if provided
        if ($request->has('locale') && in_array($request->input('locale'), ['ar', 'en'])) {
            app()->setLocale($request->input('locale'));
        }

        $tenantId = auth()->user()->tenant_id;
        $centerId = auth()->user()->current_center_id;

        $suppliers = Supplier::forTenant($tenantId)
            ->forCenter($centerId)
            ->search($request->input('search'))
            ->when($request->input('status') === 'active', fn ($q) => $q->active())
            ->when($request->input('status') === 'inactive', fn ($q) => $q->where('is_active', false))
            ->when($request->input('type'), fn ($q, $type) => $q->where('type', $type))
            ->orderBy('name')
            ->get(['id', 'name', 'code', 'phone', 'email', 'type', 'tax_number', 'is_active']);

        // Attach real balance using calculateBalance helper
        $suppliers->transform(function ($supplier) {
            $supplier->balance = $supplier->calculateBalance();

            return $supplier;
        });

        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();

        // Set RTL for Arabic
        $sheet->setRightToLeft(app()->getLocale() === 'ar');

        // Headers (added balance column → I)
        $headers = [
            '#',
            __('purchasing.suppliers.name'),
            __('purchasing.suppliers.code'),
            __('purchasing.suppliers.phone'),
            __('purchasing.suppliers.email'),
            __('purchasing.suppliers.type'),
            __('purchasing.suppliers.tax_number'),
            __('purchasing.suppliers.balance'),
            __('common.status'),
        ];

        $sheet->fromArray($headers, null, 'A1');

        // Style headers
        $sheet->getStyle('A1:I1')->getFont()->setBold(true);
        $sheet->getStyle('A1:I1')->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setRGB('E5E7EB');

        // Data
        $row = 2;

        foreach ($suppliers as $index => $supplier) {
            $sheet->fromArray([
                $index + 1,
                $supplier->name,
                $supplier->code,
                $supplier->phone,
                $supplier->email,
                $supplier->type === 'parts' ? __('purchasing.suppliers.type_parts') : __('purchasing.suppliers.type_services'),
                $supplier->tax_number,
                number_format($supplier->balance, 2),
                $supplier->is_active ? __('common.active') : __('common.inactive'),
            ], null, 'A'.$row);
            $row++;
        }

        // Auto-size columns
        foreach (range('A', 'I') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $filename = 'suppliers_'.date('Y-m-d_His').'.xlsx';

        $writer = new Xlsx($spreadsheet);

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * Download supplier import Excel template.
     */
    public function downloadTemplate()
    {
        return Excel::download(
            new SuppliersTemplateExport,
            'suppliers_template.xlsx'
        );
    }

    /**
     * Import suppliers from XLSX/CSV.
     */
    public function import(Request $request): JsonResponse
    {
        $this->authorize('create', Supplier::class);

        $file = $request->file('file');

        if (! $file) {
            return response()->json(['message' => 'لم يتم رفع ملف'], 400);
        }

        $extension = strtolower($file->getClientOriginalExtension());

        if (! in_array($extension, ['xlsx', 'xls', 'csv'])) {
            return response()->json(['message' => 'صيغة الملف غير مدعومة. الصيغ المدعومة: XLSX, XLS, CSV'], 400);
        }

        try {
            $import = new SuppliersImport;
            $import->import($file);

            return response()->json([
                'imported' => $import->getImportedCount(),
                'errors' => $import->getImportErrors(),
            ]);
        } catch (Exception $e) {
            Log::error('Supplier Import Error', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return response()->json([
                'message' => 'حدث خطأ أثناء الاستيراد: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Record a payment (سند صرف) for a supplier.
     * Can be applied to a specific purchase invoice or auto-allocated FIFO across open invoices.
     */
    public function recordPayment(Request $request, Supplier $supplier)
    {
        $this->authorize('update', $supplier);

        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'payment_method' => 'required|string',
            'payment_date' => 'nullable|date',
            'purchase_invoice_id' => 'nullable|integer',
            'reference' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:500',
        ]);

        $amount = (float) $validated['amount'];
        $paymentDate = $validated['payment_date'] ?? now();
        $user = auth()->user();

        // 1. If a specific invoice is targeted
        if (! empty($validated['purchase_invoice_id'])) {
            $invoice = $supplier->purchaseInvoices()
                ->where('id', $validated['purchase_invoice_id'])
                ->firstOrFail();

            try {
                app(PaymentService::class)->recordPurchasePayment($invoice, [
                    'amount' => $amount,
                    'payment_date' => $paymentDate,
                    'payment_method' => $validated['payment_method'],
                    'reference' => $validated['reference'] ?? null,
                    'notes' => $validated['notes'] ?? null,
                    'received_by' => $user->id,
                    'type' => Payment::TYPE_PAYMENT,
                ]);
            } catch (PaymentExceedsBalanceException $e) {
                return back()->with('error', $e->getMessage());
            } catch (InvalidArgumentException $e) {
                return back()->with('error', $e->getMessage());
            }

            return back()->with('success', __('payments.recorded') ?? 'تم تسجيل الدفعة بنجاح');
        }

        // 2. FIFO Auto-allocation across oldest open invoices
        $supplierBalance = (float) $supplier->calculateBalance();

        if ($supplierBalance <= 0.01) {
            return back()->with('error', __('payments.errors.no_outstanding_balance') ?? 'لا توجد أي مديونية مستحقة للمورد لتسجيل دفعة.');
        }

        if ($amount > $supplierBalance + 0.009) {
            $currencyLabel = CountryCurrencyRegistry::currencyLabel(
                CountryCurrencyRegistry::tenantCurrency(auth()->user()?->tenant),
                app()->getLocale()
            );

            return back()->with('error', __('payments.errors.amount_exceeds_supplier_balance', [
                'balance' => number_format($supplierBalance, 2),
                'currency' => $currencyLabel,
            ]) ?? ('المبلغ المدخل يتجاوز إجمالي الرصيد المستحق للمورد ('.number_format($supplierBalance, 2).' '.$currencyLabel.')'));
        }

        $openInvoices = $supplier->purchaseInvoices()
            ->where('balance', '>', 0.01)
            ->whereNotIn('status', [PurchaseInvoice::STATUS_DRAFT, PurchaseInvoice::STATUS_CANCELLED])
            ->orderBy('issue_date', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        $remainingToAllocate = $amount;
        $paymentService = app(PaymentService::class);

        foreach ($openInvoices as $invoice) {
            if ($remainingToAllocate <= 0) {
                break;
            }

            $invBalance = (float) $invoice->balance;
            $portion = min($remainingToAllocate, $invBalance);

            $paymentService->recordPurchasePayment($invoice, [
                'amount' => $portion,
                'payment_date' => $paymentDate,
                'payment_method' => $validated['payment_method'],
                'reference' => $validated['reference'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'received_by' => $user->id,
                'type' => Payment::TYPE_PAYMENT,
            ]);

            $remainingToAllocate -= $portion;
        }

        return back()->with('success', __('payments.recorded') ?? 'تم تسجيل الدفعة وتوزيعها على الفواتير بنجاح');
    }
}
