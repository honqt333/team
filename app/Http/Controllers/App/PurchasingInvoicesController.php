<?php

declare(strict_types=1);

namespace App\Http\Controllers\App;

use App\Enums\InvoiceType;
use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Department;
use App\Models\InventoryBalance;
use App\Models\InventoryMove;
use App\Models\InventoryUnit;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\TenantTaxSetting;
use App\Models\VehicleColor;
use App\Models\VehicleMake;
use App\Models\VehicleModel;
use App\Models\Warehouse;
use App\Services\Inventory\InventoryService;
use App\Services\InvoiceService;
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
        $tenantId = auth()->user()->tenant_id;
        $centerId = auth()->user()->current_center_id;
        $userId = auth()->id();

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
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.discount' => 'nullable|numeric|min:0',
            'items.*.tax_amount' => 'required|numeric|min:0',
            'items.*.total' => 'required|numeric|min:0',
            'items.*.grand_total' => 'required|numeric|min:0',
            'payments' => 'nullable|array',
            'payments.*.amount' => 'required|numeric|min:0.01',
            'payments.*.payment_method' => 'required|string',
            'payments.*.payment_date' => 'required|date',
            'payments.*.reference' => 'nullable|string',
            'payments.*.notes' => 'nullable|string',
            'payments.*.type' => 'nullable|string|in:payment,refund',
        ]);

        $allowNegative = auth()->user()->can('inventory.override_negative_stock');

        // Check stock availability for all warehouse items before proceeding
        if (! $allowNegative) {
            foreach ($validated['items'] as $item) {
                $source = $item['source'] ?? 'warehouse';
                $partId = $item['part_id'] ?? null;
                $warehouseId = $item['warehouse_id'] ?? null;

                if ($source === 'warehouse' && $partId) {
                    if (! $warehouseId) {
                        $warehouseId = Warehouse::where('center_id', $centerId)
                            ->where('is_active', true)
                            ->where('is_default', true)
                            ->value('id') ?? Warehouse::where('center_id', $centerId)->where('is_active', true)->value('id');
                    }

                    if ($warehouseId) {
                        $balance = InventoryBalance::query()
                            ->withoutGlobalScopes()
                            ->where('warehouse_id', $warehouseId)
                            ->where('part_id', $partId)
                            ->first();

                        $onHand = (float) ($balance?->qty_on_hand ?? 0);

                        if ($onHand < (float) $item['qty']) {
                            throw ValidationException::withMessages([
                                'items' => [
                                    __('inventory.stock.insufficient_warning_with_qty', [
                                        'available' => $onHand,
                                        'requested' => $item['qty'],
                                    ]).' ('.$item['name'].')',
                                ],
                            ]);
                        }
                    }
                }
            }
        }

        $invoice = DB::transaction(function () use ($validated, $tenantId, $centerId, $userId, $allowNegative) {
            $customer = Customer::find($validated['customer_id']);

            // Get tax settings for snapshots
            $taxSetting = TenantTaxSetting::where('tenant_id', $tenantId)->first();
            $taxEnabled = $taxSetting?->vat_enabled ?? true;
            $partsInclusive = $taxSetting?->parts_inclusive ?? false;
            $taxRate = (float) ($taxSetting?->parts_vat_rate ?? $taxSetting?->vat_rate ?? 15);
            $pricingMode = $partsInclusive ? 'inclusive' : 'exclusive';

            // Calculate totals
            $totalExclTax = 0;
            $totalTax = 0;
            $totalInclTax = 0;

            foreach ($validated['items'] as $item) {
                if ($pricingMode === 'inclusive' && $taxEnabled) {
                    $totalExclTax += ($item['grand_total'] - $item['tax_amount']);
                } else {
                    $totalExclTax += $item['total'];
                }
                $totalTax += $item['tax_amount'];
                $totalInclTax += $item['grand_total'];
            }

            // Generate an invoice number
            $invoiceNumber = 'INV-'.strtoupper(uniqid());

            $addressParts = array_filter([
                $customer->building_number ? __('common.building').' '.$customer->building_number : null,
                $customer->address_line ?: null,
                $customer->district ? __('common.district').' '.$customer->district : null,
                $customer->city ?: null,
                $customer->postal_code ? __('common.postal_code').' '.$customer->postal_code : null,
            ]);
            $customerAddress = ! empty($addressParts) ? implode('، ', $addressParts) : null;

            $invoice = Invoice::create([
                'tenant_id' => $tenantId,
                'center_id' => $centerId,
                'customer_id' => $customer->id,
                'work_order_id' => null, // Direct sale
                'invoice_number' => $invoiceNumber,
                'issue_date' => $validated['issue_date'],
                'supply_date' => $validated['issue_date'],
                'type' => 'invoice',
                'status' => 'valid',
                'payment_status' => 'unpaid',

                // Snapshots
                'customer_name_snapshot' => $customer->name,
                'customer_vat_snapshot' => $customer->tax_number,
                'customer_address_snapshot' => $customerAddress,

                // Tax settings
                'tax_enabled_snapshot' => $taxEnabled,
                'pricing_mode_snapshot' => $pricingMode,
                'tax_rate_snapshot' => $taxRate,

                // Totals
                'total_excl_tax' => $totalExclTax,
                'total_tax' => $totalTax,
                'total_incl_tax' => $totalInclTax,
                'total_taxable_amount' => $totalExclTax,
                'total_paid' => 0,
            ]);

            if ($taxEnabled) {
                $invoice->update([
                    'zatca_qr_tlv' => $this->invoiceService->generateZatcaQr($invoice),
                ]);
            }

            // Create Invoice Lines and Issue from Inventory
            foreach ($validated['items'] as $item) {
                $lineTotalInclTax = $item['grand_total'];
                $lineTotalExclTax = ($pricingMode === 'inclusive' && $taxEnabled)
                    ? ($item['grand_total'] - $item['tax_amount'])
                    : $item['total'];

                $invoice->lines()->create([
                    'is_part' => true,
                    'part_id' => $item['part_id'] ?? null,
                    'description' => $item['name'],
                    'qty' => $item['qty'],
                    'unit_price' => $item['unit_price'],
                    'discount_value' => $item['discount'] ?? 0,
                    'discount_amount' => $item['discount'] ?? 0,
                    'is_taxable' => $taxEnabled,
                    'tax_rate_snapshot' => $taxRate,
                    'tax_amount' => $item['tax_amount'],
                    'line_total_excl_tax' => $lineTotalExclTax,
                    'line_total_incl_tax' => $lineTotalInclTax,
                ]);

                // Auto-issue from inventory if part is from warehouse
                $source = $item['source'] ?? 'warehouse';
                $partId = $item['part_id'] ?? null;
                $warehouseId = $item['warehouse_id'] ?? null;

                if ($source === 'warehouse' && $partId) {
                    if (! $warehouseId) {
                        $warehouseId = Warehouse::where('center_id', $centerId)
                            ->where('is_active', true)
                            ->where('is_default', true)
                            ->value('id') ?? Warehouse::where('center_id', $centerId)->where('is_active', true)->value('id');
                    }

                    if ($warehouseId) {
                        $this->inventoryService->issue(
                            warehouseId: (int) $warehouseId,
                            partId: (int) $partId,
                            qty: (float) $item['qty'],
                            userId: $userId,
                            referenceType: Invoice::class,
                            referenceId: (int) $invoice->id,
                            notes: 'بيع مباشر - فاتورة مبيعات #'.$invoiceNumber,
                            allowNegative: $allowNegative,
                            moveType: InventoryMove::TYPE_SALE
                        );
                    }
                }
            }

            // Create Payments
            $totalPaid = 0;

            if (! empty($validated['payments'])) {
                foreach ($validated['payments'] as $paymentData) {
                    $amount = $paymentData['amount'];
                    $isRefund = ($paymentData['type'] ?? 'payment') === 'refund';

                    Payment::create([
                        'tenant_id' => $tenantId,
                        'center_id' => $centerId,
                        'invoice_id' => $invoice->id,
                        'type' => $isRefund ? 'refund' : 'payment',
                        'amount' => $amount,
                        'payment_method' => $paymentData['payment_method'],
                        'payment_date' => $paymentData['payment_date'],
                        'reference' => $paymentData['reference'] ?? null,
                        'notes' => $paymentData['notes'] ?? null,
                        'received_by' => $userId,
                    ]);

                    $totalPaid += $isRefund ? -$amount : $amount;
                }
            }

            // Update payment status
            $paymentStatus = 'unpaid';

            if ($totalPaid > 0) {
                if ($totalPaid >= $totalInclTax - 0.01) {
                    $paymentStatus = 'paid';
                } else {
                    $paymentStatus = 'partial';
                }
            }

            $invoice->update([
                'total_paid' => $totalPaid,
                'payment_status' => $paymentStatus,
            ]);

            return $invoice;
        });

        return redirect()->route('app.invoices.show', $invoice->id)->with('success', 'تم إنشاء الفاتورة بنجاح');
    }

    /**
     * Purchase Invoices index
     */
    public function purchasesIndex(Request $request)
    {
        return redirect()->route('app.invoices.purchases.index', $request->query());
    }
}
