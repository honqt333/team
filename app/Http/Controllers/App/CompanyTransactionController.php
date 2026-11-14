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
use App\Models\Tenant;
use App\Models\TenantTaxSetting;
use App\Services\InvoiceService;
use App\Services\PaymentService;
use App\Support\CountryCurrencyRegistry;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
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

        $transactionType = $request->input('transaction_type');
        $contactType = $request->input('contact_type');
        $contactId = $request->input('contact_id');

        if ($request->boolean('is_taxable') && ! $contactId) {
            return redirect()->back()->withErrors([
                'contact_id' => __('messages.company_transactions.contact_required_for_taxable'),
            ])->withInput();
        }

        if ($request->boolean('is_taxable') && $transactionType === 'expense') {
            if ($contactType !== 'supplier' || ! $contactId) {
                return redirect()->back()->withErrors([
                    'contact_id' => __('messages.company_transactions.supplier_vat_required_for_taxable_expense'),
                ])->withInput();
            }

            $supplier = Supplier::withoutGlobalScope('center_scoped')
                ->where('tenant_id', $request->user()->tenant_id)
                ->find($contactId);

            if (! $supplier || empty(trim((string) $supplier->tax_number))) {
                return redirect()->back()->withErrors([
                    'contact_id' => __('messages.company_transactions.supplier_vat_required_for_taxable_expense'),
                ])->withInput();
            }
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'transaction_date' => 'required|date',
            'transaction_type' => 'required|string|in:revenue,expense',
            'income_category_id' => ['required', Rule::exists('income_categories', 'id')->where('tenant_id', $request->user()->tenant_id)->whereNull('deleted_at')],
            'amount' => 'required|numeric|min:0',
            'gross_amount' => 'nullable|numeric|min:0',
            'is_taxable' => 'nullable|boolean',
            'is_vat_inclusive' => 'nullable|boolean',
            'tax_amount' => 'nullable|numeric|min:0',
            'total_amount' => 'nullable|numeric|min:0',
            'contact_type' => 'nullable|required_with:contact_id|string|in:customer,supplier',
            'contact_id' => ['nullable', 'integer', Rule::exists($request->input('contact_type') === 'supplier' ? 'suppliers' : 'customers', 'id')->where('tenant_id', $request->user()->tenant_id)->whereNull('deleted_at')],
            'notes' => 'nullable|string',
            'center_id' => ['nullable', Rule::exists('centers', 'id')->where('tenant_id', $request->user()->tenant_id)->whereNull('deleted_at')],
            'attachment' => 'nullable|file|mimes:jpg,jpeg,png,webp,pdf|extensions:jpg,jpeg,png,webp,pdf|max:5120',
            'remove_attachment' => 'nullable|boolean',
        ]);

        $user = auth()->user();
        $validated['tenant_id'] = $user->tenant_id;
        $validated['center_id'] = $validated['center_id'] ?? null;
        $validated['updated_by'] = $user->id;
        $validated['status'] = 'draft';

        $computed = $this->computeAmounts($request, (int) $user->tenant_id);
        $validated['is_taxable'] = $computed['is_taxable'];
        $validated['is_vat_inclusive'] = $computed['is_vat_inclusive'];
        $validated['amount'] = $computed['amount'];
        $validated['tax_amount'] = $computed['tax_amount'];
        $validated['total_amount'] = $computed['total_amount'];

        if ($request->hasFile('attachment')) {
            $validated['attachment_path'] = $request->file('attachment')->store("company-transactions/{$user->tenant_id}", 'public');
        }

        unset($validated['gross_amount'], $validated['attachment'], $validated['remove_attachment']);

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

        $transactionType = $request->input('transaction_type');
        $contactType = $request->input('contact_type');
        $contactId = $request->input('contact_id');

        if ($request->boolean('is_taxable') && ! $contactId) {
            return redirect()->back()->withErrors([
                'contact_id' => __('messages.company_transactions.contact_required_for_taxable'),
            ])->withInput();
        }

        if ($request->boolean('is_taxable') && $transactionType === 'expense') {
            if ($contactType !== 'supplier' || ! $contactId) {
                return redirect()->back()->withErrors([
                    'contact_id' => __('messages.company_transactions.supplier_vat_required_for_taxable_expense'),
                ])->withInput();
            }

            $supplier = Supplier::withoutGlobalScope('center_scoped')
                ->where('tenant_id', $request->user()->tenant_id)
                ->find($contactId);

            if (! $supplier || empty(trim((string) $supplier->tax_number))) {
                return redirect()->back()->withErrors([
                    'contact_id' => __('messages.company_transactions.supplier_vat_required_for_taxable_expense'),
                ])->withInput();
            }
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'transaction_date' => 'required|date',
            'transaction_type' => 'required|string|in:revenue,expense',
            'income_category_id' => ['required', Rule::exists('income_categories', 'id')->where('tenant_id', $request->user()->tenant_id)->whereNull('deleted_at')],
            'amount' => 'required|numeric|min:0',
            'gross_amount' => 'nullable|numeric|min:0',
            'is_taxable' => 'nullable|boolean',
            'is_vat_inclusive' => 'nullable|boolean',
            'tax_amount' => 'nullable|numeric|min:0',
            'total_amount' => 'nullable|numeric|min:0',
            'contact_type' => 'nullable|required_with:contact_id|string|in:customer,supplier',
            'contact_id' => ['nullable', 'integer', Rule::exists($request->input('contact_type') === 'supplier' ? 'suppliers' : 'customers', 'id')->where('tenant_id', $request->user()->tenant_id)->whereNull('deleted_at')],
            'notes' => 'nullable|string',
            'center_id' => ['nullable', Rule::exists('centers', 'id')->where('tenant_id', $request->user()->tenant_id)->whereNull('deleted_at')],
            'attachment' => 'nullable|file|mimes:jpg,jpeg,png,webp,pdf|extensions:jpg,jpeg,png,webp,pdf|max:5120',
            'remove_attachment' => 'nullable|boolean',
        ]);

        $validated['updated_by'] = auth()->id();

        $computed = $this->computeAmounts($request, (int) $transaction->tenant_id);
        $validated['is_taxable'] = $computed['is_taxable'];
        $validated['is_vat_inclusive'] = $computed['is_vat_inclusive'];
        $validated['amount'] = $computed['amount'];
        $validated['tax_amount'] = $computed['tax_amount'];
        $validated['total_amount'] = $computed['total_amount'];

        if ($request->boolean('remove_attachment')) {
            if ($transaction->attachment_path && Storage::disk('public')->exists($transaction->attachment_path)) {
                Storage::disk('public')->delete($transaction->attachment_path);
            }
            $validated['attachment_path'] = null;
        } elseif ($request->hasFile('attachment')) {
            if ($transaction->attachment_path && Storage::disk('public')->exists($transaction->attachment_path)) {
                Storage::disk('public')->delete($transaction->attachment_path);
            }
            $validated['attachment_path'] = $request->file('attachment')->store("company-transactions/{$request->user()->tenant_id}", 'public');
        }

        unset($validated['gross_amount'], $validated['attachment'], $validated['remove_attachment']);

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
    public function approve(Request $request, CompanyTransaction $transaction): RedirectResponse
    {
        // Approving issues a real invoice and records a real payment.
        $this->authorize('approve', $transaction);

        if ($transaction->status === 'approved') {
            return redirect()->back()->with('error', __('messages.company_transactions.already_approved'));
        }

        $transactionDateStr = $transaction->transaction_date?->format('Y-m-d') ?? (string) $transaction->transaction_date;

        $request->validate([
            'approved_at' => [
                'nullable',
                'date',
                'after_or_equal:'.$transactionDateStr,
                'before_or_equal:today',
            ],
        ]);

        $approvedAt = $request->filled('approved_at')
            ? $request->date('approved_at')->format('Y-m-d')
            : now()->toDateString();

        $tenantId = (int) $transaction->tenant_id;

        try {
            DB::transaction(function () use ($transaction, $tenantId, $approvedAt) {
                $user = auth()->user();

                if ($transaction->is_taxable) {
                    $vatRate = (float) ($transaction->amount > 0 && $transaction->tax_amount > 0
                        ? round(((float) $transaction->tax_amount / (float) $transaction->amount) * 100, 2)
                        : (TenantTaxSetting::where('tenant_id', $tenantId)->value('vat_rate') ?? CountryCurrencyRegistry::defaultVatRate(Tenant::find($tenantId)?->country) ?? 15));

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
                            'issue_date' => $approvedAt,
                            'supply_date' => $transaction->transaction_date,
                            'due_date' => $approvedAt,
                            'type' => 'invoice',
                            'subtype' => 'simplified',
                            'status' => 'draft',
                            'payment_status' => 'unpaid',

                            // Snapshots
                            'customer_name_snapshot' => $customer?->name ?? __('messages.company_transactions.cash_customer'),
                            'customer_vat_snapshot' => $customer?->tax_number,
                            'customer_address_snapshot' => $customerAddress,

                            'tax_enabled_snapshot' => (float) $transaction->tax_amount > 0,
                            'pricing_mode_snapshot' => 'exclusive',
                            'tax_rate_snapshot' => $vatRate,
                            'currency_code' => CountryCurrencyRegistry::tenantCurrency(Tenant::find($tenantId)),

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
                            'tax_rate_snapshot' => $vatRate,
                            'tax_amount' => $transaction->tax_amount,
                            'line_total_excl_tax' => $transaction->amount,
                            'line_total_incl_tax' => $transaction->total_amount,
                        ]);

                        // Link transaction details
                        $transaction->invoice_id = $invoice->id;

                        // Issue Invoice using service.
                        //
                        // The approval date is passed in so the issued
                        // document carries the business date it was
                        // approved on. Without it, `issueInvoice()` stamps
                        // `now()` and the invoice would disagree with the
                        // transaction it was raised from.
                        $this->invoiceService->issueInvoice($invoice, $approvedAt);

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
                            'issue_date' => $approvedAt,
                            'due_date' => $approvedAt,
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
                            'tax_rate' => $vatRate,
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
                $transaction->approved_at = $approvedAt;
                $transaction->save();
            });

            return redirect()->back()->with('success', __('messages.company_transactions.approved'));
        } catch (Exception $e) {
            return redirect()->back()->with('error', __('messages.company_transactions.approve_failed', ['message' => $e->getMessage()]));
        }
    }

    /**
     * Upload an attachment directly to a draft transaction.
     */
    public function uploadAttachment(Request $request, CompanyTransaction $transaction): RedirectResponse
    {
        $this->authorize('update', $transaction);

        if ($transaction->status !== 'draft') {
            return redirect()->back()->with('error', __('messages.company_transactions.cannot_edit_approved'));
        }

        $request->validate([
            'attachment' => 'required|file|mimes:jpg,jpeg,png,webp,pdf|extensions:jpg,jpeg,png,webp,pdf|max:5120',
        ]);

        if ($transaction->attachment_path && Storage::disk('public')->exists($transaction->attachment_path)) {
            Storage::disk('public')->delete($transaction->attachment_path);
        }

        $path = $request->file('attachment')->store("company-transactions/{$request->user()->tenant_id}", 'public');
        $transaction->update([
            'attachment_path' => $path,
            'updated_by' => auth()->id(),
        ]);

        return redirect()->back()->with('success', __('messages.company_transactions.attachment_uploaded'));
    }

    /**
     * Delete an attachment from a draft transaction.
     */
    public function deleteAttachment(CompanyTransaction $transaction): RedirectResponse
    {
        $this->authorize('update', $transaction);

        if ($transaction->status !== 'draft') {
            return redirect()->back()->with('error', __('messages.company_transactions.cannot_edit_approved'));
        }

        if ($transaction->attachment_path && Storage::disk('public')->exists($transaction->attachment_path)) {
            Storage::disk('public')->delete($transaction->attachment_path);
        }

        $transaction->update([
            'attachment_path' => null,
            'updated_by' => auth()->id(),
        ]);

        return redirect()->back()->with('success', __('messages.company_transactions.attachment_deleted'));
    }

    /**
     * Compute authoritative net amount, tax amount, and total amount.
     *
     * Net (amount) represents the subtotal excluding tax.
     * Tax amount represents VAT.
     * Total amount represents the gross amount including tax.
     */
    private function computeAmounts(Request $request, int $tenantId): array
    {
        $rawAmount = (float) $request->input('amount', 0);
        $grossInput = $request->filled('gross_amount') ? (float) $request->input('gross_amount') : null;
        $rawTaxAmount = $request->filled('tax_amount') ? (float) $request->input('tax_amount') : null;
        $rawTotalAmount = $request->filled('total_amount') ? (float) $request->input('total_amount') : null;

        $hasExplicitTaxable = $request->has('is_taxable');
        $isTaxable = $hasExplicitTaxable
            ? $request->boolean('is_taxable')
            : ($rawTaxAmount !== null && $rawTaxAmount > 0);

        $isVatInclusive = $request->boolean('is_vat_inclusive');

        if (! $isTaxable) {
            $net = round($rawAmount, 2);

            return [
                'is_taxable' => false,
                'is_vat_inclusive' => false,
                'amount' => $net,
                'tax_amount' => 0.00,
                'total_amount' => $net,
            ];
        }

        $vatRate = (float) (TenantTaxSetting::where('tenant_id', $tenantId)->value('vat_rate')
            ?? CountryCurrencyRegistry::defaultVatRate(Tenant::find($tenantId)?->country)
            ?? 15);

        if ($isVatInclusive) {
            // If frontend already provided pre-computed net and tax that cleanly add up to total
            if ($rawTaxAmount !== null && $rawTotalAmount !== null && abs(($rawAmount + $rawTaxAmount) - $rawTotalAmount) < 0.05 && $rawTotalAmount > $rawAmount) {
                $net = round($rawAmount, 2);
                $tax = round($rawTaxAmount, 2);
                $total = round($net + $tax, 2);
            } else {
                // The entered amount is inclusive of tax
                $gross = $grossInput ?? $rawAmount;
                $tax = round($gross - ($gross / (1 + ($vatRate / 100))), 2);
                $net = round($gross - $tax, 2);
                $total = round($gross, 2);
            }

            return [
                'is_taxable' => true,
                'is_vat_inclusive' => true,
                'amount' => $net,
                'tax_amount' => $tax,
                'total_amount' => $total,
            ];
        }

        // Exclusive
        $net = round($rawAmount, 2);

        if ($rawTaxAmount !== null) {
            $tax = round($rawTaxAmount, 2);
        } else {
            $tax = round($net * ($vatRate / 100), 2);
        }
        $total = round($net + $tax, 2);

        return [
            'is_taxable' => true,
            'is_vat_inclusive' => false,
            'amount' => $net,
            'tax_amount' => $tax,
            'total_amount' => $total,
        ];
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
            ->select('id', 'name', 'phone', 'tax_number')
            ->limit(10)
            ->get()
            ->map(fn ($c) => [
                'value' => "customer-{$c->id}",
                'label' => "{$c->name} ({$c->phone}) - ".__('common.customer'),
                'type' => 'customer',
                'id' => $c->id,
                'tax_number' => $c->tax_number,
                'has_vat' => ! empty($c->tax_number),
            ]);

        // Same grouping fix as the customers above.
        $suppliers = Supplier::where(function ($query) use ($search) {
            $query->where('name', 'like', "%{$search}%")
                ->orWhere('phone', 'like', "%{$search}%");
        })
            ->select('id', 'name', 'phone', 'tax_number')
            ->limit(10)
            ->get()
            ->map(fn ($s) => [
                'value' => "supplier-{$s->id}",
                'label' => "{$s->name} ({$s->phone}) - ".__('common.supplier'),
                'type' => 'supplier',
                'id' => $s->id,
                'tax_number' => $s->tax_number,
                'has_vat' => ! empty($s->tax_number),
            ]);

        return response()->json($customers->merge($suppliers));
    }
}
