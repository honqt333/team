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
use App\Services\PaymentService;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/** @bypass-authorization-scanner - Protected at route middleware level (auth + center.context + EnsureTwoFactorEnabled) */
class CompanyTransactionController extends Controller
{
    public function __construct(
        protected InvoiceService $invoiceService,
        protected PaymentService $paymentService
    ) {}

    /**
     * Store a newly created company transaction.
     */
    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', CompanyTransaction::class);

        if ($request->input('is_taxable') && ! $request->input('contact_id')) {
            return redirect()->back()->withErrors([
                'contact_id' => __('messages.company_transactions.contact_required_for_taxable'),
            ])->withInput();
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'transaction_date' => 'required|date',
            'transaction_type' => 'required|string|in:revenue,expense',
            'income_category_id' => ['required', Rule::exists('income_categories', 'id')->where('tenant_id', $request->user()->tenant_id)->whereNull('deleted_at')],
            'amount' => 'required|numeric|min:0',
            'is_taxable' => 'boolean',
            'tax_amount' => 'nullable|numeric|min:0',
            // Derived server-side from amount + tax_amount; the value the
            // client sends is ignored.
            'total_amount' => 'nullable|numeric|min:0',
            'contact_type' => 'nullable|required_with:contact_id|string|in:customer,supplier',
            'contact_id' => ['nullable', 'integer', Rule::exists($request->input('contact_type') === 'supplier' ? 'suppliers' : 'customers', 'id')->where('tenant_id', $request->user()->tenant_id)->whereNull('deleted_at')],
            'notes' => 'nullable|string',
            'center_id' => ['nullable', Rule::exists('centers', 'id')->where('tenant_id', $request->user()->tenant_id)->whereNull('deleted_at')],
        ]);

        $user = auth()->user();
        $validated['tenant_id'] = $user->tenant_id;
        $validated['center_id'] = $validated['center_id'] ?? null;
        $validated['updated_by'] = $user->id;
        $validated['status'] = 'draft';

        // The total is DERIVED, not taken from the request. The client
        // used to send `total_amount` alongside `amount` and
        // `tax_amount` without any consistency check, so a crafted
        // request could store a total that matched neither — and
        // approve() then issues the invoice and the payment with that
        // figure.
        $validated['total_amount'] = $this->computeTotal($validated);

        CompanyTransaction::create($validated);

        return redirect()->back()->with('success', __('messages.company_transactions.saved'));
    }

    /**
     * Update the specified company transaction.
     */
    public function update(Request $request, CompanyTransaction $transaction): RedirectResponse
    {
        $this->authorize('update', $transaction);

        if ($transaction->status === 'approved') {
            return redirect()->back()->with('error', __('messages.company_transactions.cannot_edit_approved'));
        }

        if ($request->input('is_taxable') && ! $request->input('contact_id')) {
            return redirect()->back()->withErrors([
                'contact_id' => __('messages.company_transactions.contact_required_for_taxable'),
            ])->withInput();
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'transaction_date' => 'required|date',
            'transaction_type' => 'required|string|in:revenue,expense',
            'income_category_id' => ['required', Rule::exists('income_categories', 'id')->where('tenant_id', $request->user()->tenant_id)->whereNull('deleted_at')],
            'amount' => 'required|numeric|min:0',
            'is_taxable' => 'boolean',
            'tax_amount' => 'nullable|numeric|min:0',
            // Derived server-side from amount + tax_amount; the value the
            // client sends is ignored.
            'total_amount' => 'nullable|numeric|min:0',
            'contact_type' => 'nullable|required_with:contact_id|string|in:customer,supplier',
            'contact_id' => ['nullable', 'integer', Rule::exists($request->input('contact_type') === 'supplier' ? 'suppliers' : 'customers', 'id')->where('tenant_id', $request->user()->tenant_id)->whereNull('deleted_at')],
            'notes' => 'nullable|string',
            'center_id' => ['nullable', Rule::exists('centers', 'id')->where('tenant_id', $request->user()->tenant_id)->whereNull('deleted_at')],
        ]);

        $validated['updated_by'] = auth()->id();
        $validated['total_amount'] = $this->computeTotal($validated);

        $transaction->update($validated);

        return redirect()->back()->with('success', __('messages.company_transactions.updated'));
    }

    /**
     * Remove the specified company transaction.
     */
    public function destroy(CompanyTransaction $transaction): RedirectResponse
    {
        $this->authorize('delete', $transaction);

        if ($transaction->status === 'approved') {
            return redirect()->back()->with('error', __('messages.company_transactions.cannot_delete_approved'));
        }

        $transaction->delete();

        return redirect()->back()->with('success', __('messages.company_transactions.deleted'));
    }

    /**
     * Approve/Post the company transaction and generate invoice if taxable.
     */
    public function approve(CompanyTransaction $transaction): RedirectResponse
    {
        // Approving issues a real invoice and records a real payment.
        $this->authorize('approve', $transaction);

        if ($transaction->status === 'approved') {
            return redirect()->back()->with('error', __('messages.company_transactions.already_approved'));
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
                            $adminCustomerName = __('messages.company_transactions.general_admin_customer');
                            $customer = Customer::where('name', $adminCustomerName)
                                ->where('tenant_id', $transaction->tenant_id)
                                ->first();

                            if (! $customer) {
                                $customer = Customer::create([
                                    'tenant_id' => $transaction->tenant_id,
                                    'center_id' => $transaction->center_id,
                                    'name' => $adminCustomerName,
                                    'phone' => '0500000000',
                                    'type' => 'individual',
                                    'is_active' => true,
                                ]);
                            }
                        }

                        $customerAddress = null;

                        if ($customer) {
                            $addressParts = array_filter([
                                $customer->building_number ? __('common.building').' '.$customer->building_number : null,
                                $customer->address_line ?: null,
                                $customer->district ? __('common.district').' '.$customer->district : null,
                                $customer->city ?: null,
                                $customer->postal_code ? __('common.postal_code').' '.$customer->postal_code : null,
                            ]);
                            $customerAddress = ! empty($addressParts) ? implode(', ', $addressParts) : null;
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
                            'payment_status' => 'unpaid',

                            // Snapshots
                            'customer_name_snapshot' => $customer?->name ?? __('messages.company_transactions.cash_customer'),
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
                            'total_paid' => 0,
                            'balance' => $transaction->total_amount,
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

                        // Record payment using central PaymentService
                        $this->paymentService->recordPayment($invoice, [
                            'amount' => $transaction->total_amount,
                            'payment_date' => $transaction->transaction_date,
                            'payment_method' => 'cash',
                            'notes' => __('messages.company_transactions.payment_note', ['id' => $transaction->id]),
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
                            $adminSupplierName = __('messages.company_transactions.general_admin_supplier');
                            $supplier = Supplier::withoutGlobalScope('center_scoped')
                                ->where('name', $adminSupplierName)
                                ->where('tenant_id', $transaction->tenant_id)
                                ->first();

                            if (! $supplier) {
                                $supplier = Supplier::create([
                                    'tenant_id' => $transaction->tenant_id,
                                    'name' => $adminSupplierName,
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
                                'name_ar' => __('messages.company_transactions.admin_expenses'),
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
                            'status' => PurchaseInvoice::STATUS_OPEN,
                            'subtotal' => $transaction->amount,
                            'tax_amount' => $transaction->tax_amount,
                            'discount_amount' => 0,
                            'total' => $transaction->total_amount,
                            'balance' => $transaction->total_amount,
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

                        // Record payment centrally via PaymentService
                        $this->paymentService->recordPurchasePayment($purchaseInvoice, [
                            'amount' => $transaction->total_amount,
                            'payment_date' => $transaction->transaction_date,
                            'payment_method' => 'cash',
                            'notes' => __('messages.company_transactions.payment_note', ['id' => $transaction->id]),
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

            return redirect()->back()->with('success', __('messages.company_transactions.approved'));
        } catch (Exception $e) {
            return redirect()->back()->with('error', __('messages.company_transactions.approve_failed', ['message' => $e->getMessage()]));
        }
    }

    /**
     * The authoritative total for a company transaction.
     *
     * A transaction carries the net amount and the tax; the total is
     * their sum. It is computed here so the stored figures cannot
     * contradict each other, whatever the request body said.
     */
    private function computeTotal(array $validated): float
    {
        $amount = (float) ($validated['amount'] ?? 0);
        $tax = (float) ($validated['tax_amount'] ?? 0);

        return round($amount + $tax, 2);
    }

    /**
     * Search customers and suppliers.
     */
    public function searchContacts(Request $request)
    {
        $search = $request->query('search');

        // The conditions are GROUPED. Un-grouped, the query reads
        // "tenant_id = X AND name LIKE ? OR phone LIKE ?", and SQL
        // binds AND tighter than OR — so the second branch matched
        // the phone number in ANY tenant, and the search returned
        // another tenant's customers.
        $customers = Customer::where(function ($query) use ($search) {
            $query->where('name', 'like', "%{$search}%")
                ->orWhere('phone', 'like', "%{$search}%");
        })
            ->select('id', 'name', 'phone')
            ->limit(10)
            ->get()
            ->map(fn ($c) => [
                'value' => "customer-{$c->id}",
                'label' => "{$c->name} ({$c->phone}) - ".__('common.customer'),
                'type' => 'customer',
                'id' => $c->id,
            ]);

        // Same grouping fix as the customers above.
        $suppliers = Supplier::where(function ($query) use ($search) {
            $query->where('name', 'like', "%{$search}%")
                ->orWhere('phone', 'like', "%{$search}%");
        })
            ->select('id', 'name', 'phone')
            ->limit(10)
            ->get()
            ->map(fn ($s) => [
                'value' => "supplier-{$s->id}",
                'label' => "{$s->name} ({$s->phone}) - ".__('common.supplier'),
                'type' => 'supplier',
                'id' => $s->id,
            ]);

        return response()->json($customers->merge($suppliers));
    }
}
