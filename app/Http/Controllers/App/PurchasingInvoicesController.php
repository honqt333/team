<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\PurchaseInvoice;
use App\Models\Supplier;
use App\Models\Warehouse;
use App\Models\InventoryUnit;
use App\Models\TenantTaxSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class PurchasingInvoicesController extends Controller
{
    /**
     * Sales index for inventory/parts only (without services/labor)
     */
    public function salesIndex(Request $request)
    {
        $tenantId = auth()->user()->tenant_id;
        $centerId = auth()->user()->current_center_id;

        // Query invoices for this tenant/center where there are no services in the parent workOrder
        $query = Invoice::where('tenant_id', $tenantId)
            ->where('center_id', $centerId)
            ->where(function ($q) {
                // Option 1: The work order exists but doesn't have any labor/service items
                $q->whereHas('workOrder', function ($woQuery) {
                    $woQuery->whereDoesntHave('items');
                })
                // Option 2: Or there is no work order associated at all (direct sales if any exist)
                ->orWhereDoesntHave('workOrder');
            })
            ->with(['customer', 'workOrder.items', 'workOrder.parts'])
            ->when($request->input('search'), function ($q, $search) {
                $q->where(function ($query) use ($search) {
                    $query->where('invoice_number', 'like', "%{$search}%")
                          ->orWhereHas('customer', fn($c) => $c->where('name', 'like', "%{$search}%"));
                });
            })
            ->when($request->input('payment_status'), fn($q, $s) => $q->where('payment_status', $s))
            ->when($request->input('date_from'), fn($q, $d) => $q->whereDate('issue_date', '>=', $d))
            ->when($request->input('date_to'), fn($q, $d) => $q->whereDate('issue_date', '<=', $d))
            ->orderBy('id', 'desc');

        $invoices = $query->paginate(25)->withQueryString();

        // Load dependencies for the Create Sales WorkOrder Modal
        $customers = \App\Models\Customer::select("id", "name", "phone")->get();
        $makes = \App\Models\VehicleMake::ordered()->get();
        $colors = \App\Models\VehicleColor::active()->ordered()->get();
        $departments = \App\Models\Department::active()->ordered()->get();
        $modelsByMake = \App\Models\VehicleModel::query()
            ->select('id', 'make_id', 'name_ar', 'name_en')
            ->get()
            ->groupBy('make_id');
        $warehouses = \App\Models\Warehouse::forCenter($centerId)->active()->get(['id', 'name']);

        return Inertia::render('Purchasing/Sales/Index', [
            'invoices' => $invoices,
            'filters'  => $request->only(['search', 'payment_status', 'date_from', 'date_to']),
            'customers' => $customers,
            'makes' => $makes,
            'colors' => $colors,
            'departments' => $departments,
            'modelsByMake' => $modelsByMake,
            'warehouses' => $warehouses,
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
            'payments.*.notes' => 'nullable|string',
            'payments.*.type' => 'nullable|string|in:payment,refund',
        ]);

        $invoice = \Illuminate\Support\Facades\DB::transaction(function () use ($validated, $tenantId, $centerId, $userId) {
            $customer = \App\Models\Customer::find($validated['customer_id']);

            // Calculate totals
            $totalExclTax = 0;
            $totalTax = 0;
            $totalInclTax = 0;

            foreach ($validated['items'] as $item) {
                $totalExclTax += $item['total'];
                $totalTax += $item['tax_amount'];
                $totalInclTax += $item['grand_total'];
            }

            // Generate an invoice number
            $invoiceNumber = 'INV-' . strtoupper(uniqid());

            // Get tax settings for snapshots
            $taxSetting = TenantTaxSetting::where('tenant_id', $tenantId)->first();
            $taxEnabled   = $taxSetting?->vat_enabled ?? true;
            $partsInclusive = $taxSetting?->parts_inclusive ?? false;
            $taxRate      = (float) ($taxSetting?->parts_vat_rate ?? $taxSetting?->vat_rate ?? 15);
            $pricingMode  = $partsInclusive ? 'inclusive' : 'exclusive';

            $addressParts = array_filter([
                $customer->building_number ? __('common.building') . ' ' . $customer->building_number : null,
                $customer->address_line ?: null,
                $customer->district ? __('common.district') . ' ' . $customer->district : null,
                $customer->city ?: null,
                $customer->postal_code ? __('common.postal_code') . ' ' . $customer->postal_code : null,
            ]);
            $customerAddress = !empty($addressParts) ? implode('، ', $addressParts) : null;

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

            // Create Invoice Lines
            foreach ($validated['items'] as $item) {
                $invoice->lines()->create([
                    'description'       => $item['name'],
                    'qty'               => $item['qty'],
                    'unit_price'        => $item['unit_price'],
                    'is_taxable'        => $taxEnabled,
                    'tax_rate_snapshot' => $taxRate,
                    'tax_amount'        => $item['tax_amount'],
                    'line_total_excl_tax' => $item['total'],
                    'line_total_incl_tax' => $item['grand_total'],
                ]);
            }

            // Create Payments
            $totalPaid = 0;
            if (!empty($validated['payments'])) {
                foreach ($validated['payments'] as $paymentData) {
                    $amount = $paymentData['amount'];
                    $isRefund = ($paymentData['type'] ?? 'payment') === 'refund';
                    
                    \App\Models\Payment::create([
                        'tenant_id'      => $tenantId,
                        'center_id'      => $centerId,
                        'invoice_id'     => $invoice->id,
                        'type'           => $isRefund ? 'refund' : 'payment',
                        'amount'         => $amount,
                        'payment_method' => $paymentData['payment_method'],
                        'payment_date'   => $paymentData['payment_date'],
                        'reference'      => null,
                        'notes'          => $paymentData['notes'] ?? null,
                        'received_by'    => $userId,
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
        $tenantId = auth()->user()->tenant_id;
        $centerId = auth()->user()->current_center_id;

        $query = PurchaseInvoice::where('tenant_id', $tenantId)
            ->where('center_id', $centerId)
            ->with(['supplier', 'purchaseOrder'])
            ->when($request->input('search'), function ($q, $search) {
                $q->where(function ($query) use ($search) {
                    $query->where('code', 'like', "%{$search}%")
                          ->orWhere('invoice_number', 'like', "%{$search}%")
                          ->orWhereHas('supplier', fn($s) => $s->where('name', 'like', "%{$search}%"));
                });
            })
            ->when($request->input('status'), fn($q, $s) => $q->where('status', $s))
            ->when($request->input('date_from'), fn($q, $d) => $q->whereDate('issue_date', '>=', $d))
            ->when($request->input('date_to'), fn($q, $d) => $q->whereDate('issue_date', '<=', $d))
            ->orderBy('id', 'desc');

        $invoices = $query->paginate(25)->withQueryString();

        $returns = \App\Models\PurchaseReturnInvoice::where('tenant_id', $tenantId)
            ->where('center_id', $centerId)
            ->with(['purchaseInvoice.supplier'])
            ->when($request->input('search'), function ($q, $search) {
                $q->where(function ($query) use ($search) {
                    $query->where('code', 'like', "%{$search}%")
                          ->orWhereHas('purchaseInvoice.supplier', fn($s) => $s->where('name', 'like', "%{$search}%"));
                });
            })
            ->when($request->input('date_from'), fn($q, $d) => $q->whereDate('return_date', '>=', $d))
            ->when($request->input('date_to'), fn($q, $d) => $q->whereDate('return_date', '<=', $d))
            ->orderBy('id', 'desc')
            ->paginate(25, ['*'], 'returns_page')
            ->withQueryString();

        $suppliers = Supplier::forTenant($tenantId)->active()->get(['id', 'name']);
        $defaultWarehouse = Warehouse::forCenter($centerId)->default()->first();
        $warehouses = Warehouse::forCenter($centerId)->active()->get(['id', 'name']);
        $units = InventoryUnit::where('is_active', true)->get(['id', 'name_ar', 'name_en']);

        return Inertia::render('Purchasing/Invoices/Index', [
            'invoices' => $invoices,
            'returns'  => $returns,
            'filters'  => $request->only(['search', 'status', 'date_from', 'date_to']),
            'statuses' => [
                PurchaseInvoice::STATUS_DRAFT,
                PurchaseInvoice::STATUS_OPEN,
                PurchaseInvoice::STATUS_PAID,
                PurchaseInvoice::STATUS_CANCELLED,
            ],
            'suppliers' => $suppliers,
            'defaultWarehouse' => $defaultWarehouse,
            'warehouses' => $warehouses,
            'units' => $units,
            'stats' => [
                'total'  => PurchaseInvoice::where('tenant_id', $tenantId)->where('center_id', $centerId)->count(),
                'open'   => PurchaseInvoice::where('tenant_id', $tenantId)->where('center_id', $centerId)->where('status', 'open')->count(),
                'paid'   => PurchaseInvoice::where('tenant_id', $tenantId)->where('center_id', $centerId)->where('status', 'paid')->count(),
                'draft'  => PurchaseInvoice::where('tenant_id', $tenantId)->where('center_id', $centerId)->where('status', 'draft')->count(),
            ],
        ]);
    }
}
