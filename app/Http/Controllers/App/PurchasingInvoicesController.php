<?php

declare(strict_types=1);

namespace App\Http\Controllers\App;

use App\Enums\InvoiceType;
use App\Exceptions\PaymentExceedsBalanceException;
use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Department;
use App\Models\InventoryUnit;
use App\Models\Invoice;
use App\Models\VehicleColor;
use App\Models\VehicleMake;
use App\Models\VehicleModel;
use App\Models\Warehouse;
use App\Services\Inventory\InventoryService;
use App\Services\InvoiceService;
use App\Support\Permissions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class PurchasingInvoicesController extends Controller
{
    public function __construct(
        protected InventoryService $inventoryService,
        protected InvoiceService $invoiceService
    ) {}

    /**
     * Sales index for direct store/inventory sales (without work orders).
     */
    public function salesIndex(Request $request)
    {
        $this->authorize(Permissions::INVOICES_VIEW);

        $tenantId = auth()->user()->tenant_id;
        $centerId = auth()->user()->current_center_id;

        $baseQuery = Invoice::where('tenant_id', $tenantId)
            ->where('center_id', $centerId)
            ->whereNull('work_order_id')
            ->whereNotExists(function ($q) {
                $q->select(DB::raw(1))
                    ->from('company_transactions')
                    ->whereColumn('company_transactions.invoice_id', 'invoices.id');
            })
            ->with(['customer', 'lines', 'originalInvoice:id,invoice_number,issue_date,total_incl_tax'])
            ->when($request->input('search'), function ($q, $search) {
                $q->where(function ($query) use ($search) {
                    $query->where('invoice_number', 'like', "%{$search}%")
                        ->orWhereHas('customer', fn ($c) => $c->where('name', 'like', "%{$search}%"));
                });
            })
            ->when($request->input('payment_status'), fn ($q, $s) => $q->where('payment_status', $s))
            ->when($request->input('date_from'), fn ($q, $d) => $q->whereDate('issue_date', '>=', $d))
            ->when($request->input('date_to'), fn ($q, $d) => $q->whereDate('issue_date', '<=', $d));

        $allowedSorts = ['invoice_number', 'customer', 'issue_date', 'subtotal', 'total_incl_tax', 'balance', 'payment_status', 'created_at'];
        $sort = in_array($request->input('sort'), $allowedSorts, true) ? $request->input('sort') : 'issue_date';
        $direction = strtolower((string) ($request->input('direction') ?? $request->input('order', ''))) === 'asc' ? 'asc' : 'desc';

        if ($sort === 'customer') {
            $baseQuery->orderBy(
                Customer::select('name')
                    ->whereColumn('customers.id', 'invoices.customer_id')
                    ->limit(1),
                $direction
            );
        } else {
            $baseQuery->orderBy("invoices.{$sort}", $direction);
        }
        $baseQuery->orderBy('invoices.id', 'desc');

        $filters = array_merge($request->only(['search', 'payment_status', 'date_from', 'date_to', 'tab']), [
            'sort' => $sort,
            'direction' => $direction,
        ]);

        if ($request->boolean('print')) {
            $tab = (string) $request->input('tab', 'invoices');
            $printQuery = (clone $baseQuery)->where(function ($q) use ($tab) {
                if ($tab === 'returns') {
                    $q->where('type', 'credit_note')
                        ->orWhere('type', InvoiceType::CREDIT_NOTE);
                } else {
                    $q->where('type', 'invoice')
                        ->orWhere('type', InvoiceType::INVOICE)
                        ->orWhereNull('type');
                }
            });

            return Inertia::render('Reports/Print', [
                'reportType' => 'purchasing_sales',
                'backRoute' => 'app.purchasing.sales.index',
                'filters' => $filters,
                'kpis' => [],
                'rows' => $printQuery->limit(5000)->get(),
                'companyInfo' => [],
            ]);
        }

        // Sales Invoices tab: standard sales invoices (exclude credit notes)
        $invoices = (clone $baseQuery)
            ->where(function ($q) {
                $q->where('type', 'invoice')
                    ->orWhere('type', InvoiceType::INVOICE)
                    ->orWhereNull('type');
            })
            ->paginate(25, ['*'], 'invoices_page')
            ->withQueryString();

        // Return Invoices tab: credit notes
        $returns = (clone $baseQuery)
            ->where(function ($q) {
                $q->where('type', 'credit_note')
                    ->orWhere('type', InvoiceType::CREDIT_NOTE);
            })
            ->paginate(25, ['*'], 'returns_page')
            ->withQueryString();

        // Load dependencies for the Create Sales WorkOrder Modal
        $customers = Customer::select('id', 'name', 'phone')->get();
        $makes = VehicleMake::ordered()->get();
        $colors = VehicleColor::active()->ordered()->get();
        $departments = Department::active()->ordered()->get();
        $modelsByMake = VehicleModel::query()
            ->select('id', 'make_id', 'name_ar', 'name_en')
            ->get()
            ->groupBy('make_id');
        $warehouses = Warehouse::forCenter($centerId)->active()->get(['id', 'name']);
        $units = InventoryUnit::where('tenant_id', $tenantId)->where('is_active', true)->get();

        return Inertia::render('Purchasing/Sales/Index', [
            'invoices' => $invoices,
            'returns' => $returns,
            'filters' => array_merge($request->only(['search', 'payment_status', 'date_from', 'date_to', 'tab']), [
                'sort' => $sort,
                'direction' => $direction,
            ]),
            'customers' => $customers,
            'makes' => $makes,
            'colors' => $colors,
            'departments' => $departments,
            'modelsByMake' => $modelsByMake,
            'warehouses' => $warehouses,
            'units' => $units,
        ]);
    }

    /**
     * Store a direct sales invoice (without a Work Order)
     */
    public function storeSalesInvoice(Request $request)
    {
        $this->authorize(Permissions::INVOICES_CREATE);

        $tenantId = (int) auth()->user()->tenant_id;
        $centerId = (int) auth()->user()->current_center_id;
        $userId = (int) auth()->id();

        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'issue_date' => 'required|date',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.part_id' => 'nullable|exists:parts,id',
            'items.*.warehouse_id' => 'nullable|exists:warehouses,id',
            'items.*.source' => 'nullable|string|in:warehouse,external,customer',
            'items.*.name' => 'required|string',
            'items.*.qty' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'nullable|numeric|min:0',
            'items.*.discount' => 'nullable|numeric|min:0',
            'items.*.discount_type' => 'nullable|string|in:fixed,percentage',
            'items.*.discount_value' => 'nullable|numeric|min:0',
            'items.*.tax_amount' => 'nullable|numeric|min:0',
            'items.*.total' => 'nullable|numeric|min:0',
            'items.*.grand_total' => 'nullable|numeric|min:0',
            'payments' => 'nullable|array',
            'payments.*.amount' => 'required|numeric|min:0.01',
            'payments.*.payment_method' => 'required|string',
            'payments.*.payment_date' => 'required|date',
            'payments.*.reference' => 'nullable|string',
            'payments.*.notes' => 'nullable|string',
            'payments.*.type' => 'nullable|string|in:payment,refund',
        ]);

        $allowNegative = auth()->user()->can('inventory.override_negative_stock');
        $allowCustomPricing = auth()->user()->can(Permissions::INVOICES_EXTRA_DISCOUNT);

        try {
            $invoice = $this->invoiceService->createDirectSalesInvoice(
                tenantId: $tenantId,
                centerId: $centerId,
                userId: $userId,
                validated: $validated,
                allowNegative: $allowNegative,
                allowCustomPricing: $allowCustomPricing,
            );
        } catch (PaymentExceedsBalanceException $e) {
            throw ValidationException::withMessages([
                'payments' => [$e->getMessage()],
            ]);
        }

        return redirect()->route('app.invoices.show', $invoice->id)->with('success', 'تم إنشاء الفاتورة بنجاح');
    }

    /**
     * Purchase Invoices index
     */
    public function purchasesIndex(Request $request)
    {
        $this->authorize(Permissions::PURCHASE_INVOICES_VIEW);

        return redirect()->route('app.invoices.purchases.index', $request->query());
    }
}
