<?php

declare(strict_types=1);

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\CompanyTransaction;
use App\Models\Customer;
use App\Models\InventoryUnit;
use App\Models\Invoice;
use App\Models\Part;
use App\Models\Payment;
use App\Models\PurchaseInvoice;
use App\Models\Supplier;
use App\Services\InvoiceService;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/** @bypass-authorization-scanner - Protected at route middleware level (auth + center.context + EnsureTwoFactorEnabled) */
class CompanyTransactionController extends Controller
{
    protected InvoiceService $invoiceService;

    public function __construct(InvoiceService $invoiceService)
    {
        $this->invoiceService = $invoiceService;
    }

    /**
     * Store a newly created company transaction.
     */
    public function store(Request $request): RedirectResponse
    {
        if ($request->input('is_taxable') && ! $request->input('contact_id')) {
            return redirect()->back()->withErrors([
                'contact_id' => 'يجب تحديد العميل/المورد عند تفعيل خاضع للضريبة لضمان التوافق مع الفوترة الضريبية',
            ])->withInput();
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'transaction_date' => 'required|date',
            'transaction_type' => 'required|string|in:revenue,expense',
            'income_category_id' => 'required|exists:income_categories,id',
            'amount' => 'required|numeric|min:0',
            'is_taxable' => 'boolean',
            'tax_amount' => 'nullable|numeric|min:0',
            'total_amount' => 'required|numeric|min:0',
            'contact_type' => 'nullable|string|in:customer,supplier',
            'contact_id' => 'nullable|integer',
            'notes' => 'nullable|string',
            'center_id' => 'nullable|exists:centers,id',
        ]);

        $user = auth()->user();
        $validated['tenant_id'] = $user->tenant_id;
        $validated['center_id'] = $validated['center_id'] ?? null;
        $validated['updated_by'] = $user->id;
        $validated['status'] = 'draft';

        CompanyTransaction::create($validated);

        return redirect()->back()->with('success', 'تم حفظ المعاملة الإدارية بنجاح كمسودة');
    }

    /**
     * Update the specified company transaction.
     */
    public function update(Request $request, CompanyTransaction $transaction): RedirectResponse
    {
        if ($transaction->status === 'approved') {
            return redirect()->back()->with('error', 'لا يمكن تعديل المعاملات المعتمدة');
        }

        if ($request->input('is_taxable') && ! $request->input('contact_id')) {
            return redirect()->back()->withErrors([
                'contact_id' => 'يجب تحديد العميل/المورد عند تفعيل خاضع للضريبة لضمان التوافق مع الفوترة الضريبية',
            ])->withInput();
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'transaction_date' => 'required|date',
            'transaction_type' => 'required|string|in:revenue,expense',
            'income_category_id' => 'required|exists:income_categories,id',
            'amount' => 'required|numeric|min:0',
            'is_taxable' => 'boolean',
            'tax_amount' => 'nullable|numeric|min:0',
            'total_amount' => 'required|numeric|min:0',
            'contact_type' => 'nullable|string|in:customer,supplier',
            'contact_id' => 'nullable|integer',
            'notes' => 'nullable|string',
            'center_id' => 'nullable|exists:centers,id',
        ]);

        $validated['updated_by'] = auth()->id();

        $transaction->update($validated);

        return redirect()->back()->with('success', 'تم تحديث المعاملة الإدارية بنجاح');
    }

    /**
     * Remove the specified company transaction.
     */
    public function destroy(CompanyTransaction $transaction): RedirectResponse
    {
        if ($transaction->status === 'approved') {
            return redirect()->back()->with('error', 'لا يمكن حذف المعاملات المعتمدة');
        }

        $transaction->delete();

        return redirect()->back()->with('success', 'تم حذف المعاملة الإدارية بنجاح');
    }

    /**
     * Approve/Post the company transaction and generate invoice if taxable.
     */
    public function approve(CompanyTransaction $transaction): RedirectResponse
    {
        if ($transaction->status === 'approved') {
            return redirect()->back()->with('error', 'هذه المعاملة معتمدة بالفعل');
        }

        try {
            DB::transaction(function () use ($transaction) {
                $user = auth()->user();

                if ($transaction->is_taxable) {
                    if ($transaction->transaction_type === 'revenue') {
                        // 1. Create Sales Invoice (Simplified type)
                        $customer = null;

                        if ($transaction->contact_type === 'customer' && $transaction->contact_id) {
                            $customer = Customer::find($transaction->contact_id);
                        }

                        if (! $customer) {
                            $customer = Customer::where('name', 'عميل إداري عام')
                                ->where('tenant_id', $transaction->tenant_id)
                                ->first();

                            if (! $customer) {
                                $customer = Customer::create([
                                    'tenant_id' => $transaction->tenant_id,
                                    'center_id' => $transaction->center_id,
                                    'name' => 'عميل إداري عام',
                                    'phone' => '0500000000',
                                    'type' => 'individual',
                                    'is_active' => true,
                                ]);
                            }
                        }

                        $customerAddress = null;

                        if ($customer) {
                            $addressParts = array_filter([
                                $customer->building_number ? 'مبنى '.$customer->building_number : null,
                                $customer->address_line ?: null,
                                $customer->district ? 'حي '.$customer->district : null,
                                $customer->city ?: null,
                                $customer->postal_code ? 'الرمز البريدي '.$customer->postal_code : null,
                            ]);
                            $customerAddress = ! empty($addressParts) ? implode('، ', $addressParts) : null;
                        }

                        $invoice = Invoice::create([
                            'tenant_id' => $transaction->tenant_id,
                            'center_id' => $transaction->center_id,
                            'customer_id' => $customer?->id,
                            'invoice_number' => 'DRAFT-COMP-'.$transaction->id,
                            'issue_date' => $transaction->transaction_date,
                            'supply_date' => $transaction->transaction_date,
                            'due_date' => $transaction->transaction_date,
                            'type' => 'invoice',
                            'subtype' => 'simplified',
                            'status' => 'draft',
                            'payment_status' => 'paid',

                            // Snapshots
                            'customer_name_snapshot' => $customer?->name ?? 'عميل نقدي',
                            'customer_vat_snapshot' => $customer?->tax_number,
                            'customer_address_snapshot' => $customerAddress,

                            'tax_enabled_snapshot' => true,
                            'pricing_mode_snapshot' => 'exclusive',
                            'tax_rate_snapshot' => 15,
                            'currency_code' => 'SAR',

                            'total_excl_tax' => $transaction->amount,
                            'total_tax' => $transaction->tax_amount,
                            'total_incl_tax' => $transaction->total_amount,
                            'total_taxable_amount' => $transaction->amount,
                            'total_paid' => $transaction->total_amount,
                        ]);

                        // Add Invoice Line
                        $invoice->lines()->create([
                            'is_part' => false,
                            'description' => $transaction->title,
                            'qty' => 1,
                            'unit_price' => $transaction->amount,
                            'discount_amount' => 0,
                            'is_taxable' => true,
                            'tax_category_code' => 'S',
                            'tax_rate_snapshot' => 15,
                            'tax_amount' => $transaction->tax_amount,
                            'line_total_excl_tax' => $transaction->amount,
                            'line_total_incl_tax' => $transaction->total_amount,
                        ]);

                        // Link transaction details
                        $transaction->invoice_id = $invoice->id;

                        // Issue Invoice using service
                        $this->invoiceService->issueInvoice($invoice);

                        // Create payment record
                        Payment::create([
                            'tenant_id' => $invoice->tenant_id,
                            'center_id' => $invoice->center_id,
                            'invoice_id' => $invoice->id,
                            'amount' => $transaction->total_amount,
                            'payment_date' => $transaction->transaction_date,
                            'payment_method' => 'cash',
                            'notes' => 'تسجيل دفع تلقائي لمعاملة إدارية رقم '.$transaction->id,
                            'received_by' => $user->id,
                            'type' => 'payment',
                        ]);

                    } elseif ($transaction->transaction_type === 'expense') {
                        // 2. Create Purchase Invoice
                        $supplier = null;

                        if ($transaction->contact_type === 'supplier' && $transaction->contact_id) {
                            $supplier = Supplier::find($transaction->contact_id);
                        }

                        if (! $supplier) {
                            $supplier = Supplier::withoutGlobalScope('center_scoped')
                                ->where('name', 'مورد إداري عام')
                                ->where('tenant_id', $transaction->tenant_id)
                                ->first();

                            if (! $supplier) {
                                $supplier = Supplier::create([
                                    'tenant_id' => $transaction->tenant_id,
                                    'name' => 'مورد إداري عام',
                                    'code' => 'SUP-ADMIN-'.$transaction->tenant_id,
                                    'is_active' => true,
                                ]);
                            }
                        }

                        // Ensure we have a dummy part for company administrative expenses
                        $unit = InventoryUnit::first();
                        $part = Part::firstOrCreate(
                            ['sku' => 'COMP-EXPENSE', 'tenant_id' => $transaction->tenant_id],
                            [
                                'name_ar' => 'مصروفات إدارية للشركة',
                                'name_en' => 'Company Administrative Expense',
                                'is_active' => true,
                                'unit_id' => $unit?->id ?? 1,
                            ]
                        );

                        $purchaseInvoice = PurchaseInvoice::create([
                            'tenant_id' => $transaction->tenant_id,
                            'center_id' => $transaction->center_id,
                            'supplier_id' => $supplier?->id,
                            'invoice_number' => PurchaseInvoice::generateCode($transaction->tenant_id),
                            'code' => PurchaseInvoice::generateCode($transaction->tenant_id),
                            'issue_date' => $transaction->transaction_date,
                            'due_date' => $transaction->transaction_date,
                            'status' => PurchaseInvoice::STATUS_PAID,
                            'subtotal' => $transaction->amount,
                            'tax_amount' => $transaction->tax_amount,
                            'discount_amount' => 0,
                            'total' => $transaction->total_amount,
                            'balance' => 0,
                            'notes' => $transaction->notes,
                        ]);

                        // Add Purchase Invoice Line
                        $purchaseInvoice->lines()->create([
                            'part_id' => $part->id,
                            'qty' => 1,
                            'unit_cost' => $transaction->amount,
                            'tax_rate' => 15,
                            'tax_amount' => $transaction->tax_amount,
                            'total' => $transaction->total_amount,
                        ]);

                        // Create payment record
                        Payment::create([
                            'tenant_id' => $purchaseInvoice->tenant_id,
                            'center_id' => $purchaseInvoice->center_id,
                            'purchase_invoice_id' => $purchaseInvoice->id,
                            'amount' => $transaction->total_amount,
                            'payment_date' => $transaction->transaction_date,
                            'payment_method' => 'cash',
                            'notes' => 'تسجيل دفع تلقائي لمعاملة إدارية رقم '.$transaction->id,
                            'received_by' => $user->id,
                            'type' => 'payment',
                        ]);

                        $transaction->purchase_invoice_id = $purchaseInvoice->id;
                    }
                }

                $transaction->status = 'approved';
                $transaction->approved_by = $user->id;
                $transaction->save();
            });

            return redirect()->back()->with('success', 'تم اعتماد المعاملة المالية وإصدار الفاتورة بنجاح');
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'حدث خطأ أثناء الاعتماد: '.$e->getMessage());
        }
    }

    /**
     * Search customers and suppliers.
     */
    public function searchContacts(Request $request)
    {
        $search = $request->query('search');

        $customers = Customer::where('name', 'like', "%{$search}%")
            ->orWhere('phone', 'like', "%{$search}%")
            ->select('id', 'name', 'phone')
            ->limit(10)
            ->get()
            ->map(fn ($c) => [
                'value' => "customer-{$c->id}",
                'label' => "{$c->name} ({$c->phone}) - عميل",
                'type' => 'customer',
                'id' => $c->id,
            ]);

        $suppliers = Supplier::where('name', 'like', "%{$search}%")
            ->orWhere('phone', 'like', "%{$search}%")
            ->select('id', 'name', 'phone')
            ->limit(10)
            ->get()
            ->map(fn ($s) => [
                'value' => "supplier-{$s->id}",
                'label' => "{$s->name} ({$s->phone}) - مورد",
                'type' => 'supplier',
                'id' => $s->id,
            ]);

        return response()->json($customers->merge($suppliers));
    }
}
