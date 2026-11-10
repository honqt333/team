<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Center;
use App\Models\CompanyTransaction;
use App\Models\Customer;
use App\Models\IncomeCategory;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\PurchaseInvoice;
use App\Models\Supplier;
use App\Models\Tenant;
use App\Services\PaymentService;
use App\Services\Reports\CashRegisterReportService;
use App\Services\Reports\FinancialSummaryReportService;
use App\Services\Reports\TaxPeriodService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PurchasePaymentReviewTest extends TestCase
{
    use RefreshDatabase;

    private function createFixtures(): array
    {
        $tenant = Tenant::factory()->create();
        $center = Center::factory()->create(['tenant_id' => $tenant->id]);
        $customer = Customer::factory()->create(['tenant_id' => $tenant->id, 'center_id' => $center->id]);
        $supplier = Supplier::create([
            'tenant_id' => $tenant->id,
            'name' => 'مورد قطع غيار تجريبي',
            'code' => 'SUP-TEST-'.uniqid(),
            'is_active' => true,
        ]);

        return [$tenant, $center, $customer, $supplier];
    }

    private function createInvoice(Tenant $tenant, Center $center, Customer $customer, string $status = 'valid', string $type = 'invoice'): Invoice
    {
        $invoice = Invoice::factory()->create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'customer_id' => $customer->id,
            'work_order_id' => null,
            'status' => $status,
            'type' => $type,
            'issue_date' => '2026-01-15',
            'total_excl_tax' => 100.00,
            'total_tax' => 15.00,
            'total_incl_tax' => 115.00,
        ]);

        DB::table('invoice_lines')->insert([
            'invoice_id' => $invoice->id,
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'is_part' => false,
            'description' => 'بند خدمة تجريبي',
            'qty' => 1,
            'unit_price' => 100.00,
            'discount_amount' => 0.00,
            'line_total_excl_tax' => 100.00,
            'tax_amount' => 15.00,
            'line_total_incl_tax' => 115.00,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $invoice;
    }

    private function createCompanyTx(
        Tenant $tenant,
        Center $center,
        string $type = 'revenue',
        string $status = 'approved',
        ?Invoice $invoice = null,
        ?PurchaseInvoice $purchaseInvoice = null,
        bool $isTaxable = true
    ): CompanyTransaction {
        $category = IncomeCategory::create([
            'tenant_id' => $tenant->id,
            'name_ar' => 'تصنيف تجريبي',
            'name_en' => 'Test Category',
            'transaction_type' => $type === 'income' ? 'revenue' : $type,
            'is_active' => true,
        ]);

        return CompanyTransaction::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'income_category_id' => $category->id,
            'title' => 'معاملة إدارية تجريبية',
            'transaction_type' => $type,
            'transaction_date' => '2026-01-15',
            'amount' => 100.00,
            'tax_amount' => $isTaxable ? 15.00 : 0.00,
            'total_amount' => $isTaxable ? 115.00 : 100.00,
            'is_taxable' => $isTaxable,
            'status' => $status,
            'invoice_id' => $invoice?->id,
            'purchase_invoice_id' => $purchaseInvoice?->id,
        ]);
    }

    // =========================================================================
    // R01 Acceptance Tests: Draft & Cancelled status exclusions
    // =========================================================================

    public function test_r01_financial_summary_excludes_draft_and_cancelled_sales_invoices(): void
    {
        [$tenant, $center, $customer] = $this->createFixtures();
        $service = app(FinancialSummaryReportService::class);

        // Draft invoice
        $draft = $this->createInvoice($tenant, $center, $customer, 'draft');
        $repDraft = $service->computeCenterFinancialStatement($tenant->id, $center->id, null, null, false);
        $this->assertEquals(0.0, $repDraft['invoicesSumNetAmount'], 'Draft sales invoice must NOT be counted in sales');

        // Cancelled invoice
        $draft->update(['status' => 'cancelled']);
        $repCancelled = $service->computeCenterFinancialStatement($tenant->id, $center->id, null, null, false);
        $this->assertEquals(0.0, $repCancelled['invoicesSumNetAmount'], 'Cancelled sales invoice must NOT be counted in sales');

        // Valid invoice is counted
        $draft->update(['status' => 'valid']);
        $repValid = $service->computeCenterFinancialStatement($tenant->id, $center->id, null, null, false);
        $this->assertEquals(115.0, $repValid['invoicesSumNetAmount'], 'Valid sales invoice must be counted in sales');

        // Soft-deleted invoice is excluded
        $draft->delete();
        $repDeleted = $service->computeCenterFinancialStatement($tenant->id, $center->id, null, null, false);
        $this->assertEquals(0.0, $repDeleted['invoicesSumNetAmount'], 'Soft-deleted sales invoice must NOT be counted in sales');
    }

    public function test_r01_financial_summary_excludes_draft_and_cancelled_credit_notes(): void
    {
        [$tenant, $center, $customer] = $this->createFixtures();
        $service = app(FinancialSummaryReportService::class);

        $creditNote = $this->createInvoice($tenant, $center, $customer, 'draft', 'credit_note');
        $repDraft = $service->computeCenterFinancialStatement($tenant->id, $center->id, null, null, false);
        $this->assertEquals(0.0, $repDraft['returnInvoicesSumNetAmount'], 'Draft credit note must NOT be counted in returns');

        $creditNote->update(['status' => 'cancelled']);
        $repCancelled = $service->computeCenterFinancialStatement($tenant->id, $center->id, null, null, false);
        $this->assertEquals(0.0, $repCancelled['returnInvoicesSumNetAmount'], 'Cancelled credit note must NOT be counted in returns');

        $creditNote->update(['status' => 'valid']);
        $repValid = $service->computeCenterFinancialStatement($tenant->id, $center->id, null, null, false);
        $this->assertEquals(115.0, $repValid['returnInvoicesSumNetAmount'], 'Valid credit note must be counted in returns');
    }

    public function test_r01_financial_summary_excludes_draft_and_cancelled_purchases(): void
    {
        [$tenant, $center, , $supplier] = $this->createFixtures();
        $service = app(FinancialSummaryReportService::class);

        $purchase = PurchaseInvoice::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'supplier_id' => $supplier->id,
            'code' => 'PINV-001',
            'invoice_number' => 'PINV-001',
            'issue_date' => '2026-01-15',
            'status' => PurchaseInvoice::STATUS_DRAFT,
            'subtotal' => 100.00,
            'tax_amount' => 15.00,
            'total' => 115.00,
        ]);

        $repDraft = $service->computeCenterFinancialStatement($tenant->id, $center->id, null, null, false);
        $this->assertEquals(0.0, $repDraft['receivedSparePartsSumNetAmount'], 'Draft purchase invoice must NOT be counted');

        $purchase->update(['status' => PurchaseInvoice::STATUS_CANCELLED]);
        $repCancelled = $service->computeCenterFinancialStatement($tenant->id, $center->id, null, null, false);
        $this->assertEquals(0.0, $repCancelled['receivedSparePartsSumNetAmount'], 'Cancelled purchase invoice must NOT be counted');

        $purchase->update(['status' => PurchaseInvoice::STATUS_PAID]);
        $repPaid = $service->computeCenterFinancialStatement($tenant->id, $center->id, null, null, false);
        $this->assertEquals(115.0, $repPaid['receivedSparePartsSumNetAmount'], 'Paid purchase invoice must be counted');
    }

    public function test_r01_financial_summary_excludes_draft_company_transactions(): void
    {
        [$tenant, $center] = $this->createFixtures();
        $service = app(FinancialSummaryReportService::class);

        $tx = $this->createCompanyTx($tenant, $center, 'revenue', 'draft', null, null, false);
        $repDraft = $service->computeCenterFinancialStatement($tenant->id, $center->id, null, null, false);
        $this->assertEquals(0.0, $repDraft['incomeSumNetAmount'], 'Draft company transaction must NOT be counted in other income');

        $tx->update(['status' => 'approved']);
        $repApproved = $service->computeCenterFinancialStatement($tenant->id, $center->id, null, null, false);
        $this->assertEquals(100.0, $repApproved['incomeSumNetAmount'], 'Approved company transaction must be counted in other income');
    }

    // =========================================================================
    // R02 Acceptance Tests: Prevention of double-counting on linked transactions
    // =========================================================================

    public function test_r02_linked_company_revenue_is_counted_exactly_once_in_financial_totals(): void
    {
        [$tenant, $center, $customer] = $this->createFixtures();
        $invoice = $this->createInvoice($tenant, $center, $customer, 'valid');

        // Link company revenue transaction to the valid invoice
        $this->createCompanyTx($tenant, $center, 'revenue', 'approved', $invoice);

        $result = app(FinancialSummaryReportService::class)->computeCenterFinancialStatement($tenant->id, $center->id, null, null, false);

        // The financial event must be categorized under Other Income and NOT duplicated in Sales Invoices
        $this->assertEquals(0.0, $result['invoicesSumNetAmount'], 'Invoice linked to company transaction must not be counted in workshop sales');
        $this->assertEquals(115.0, $result['incomeSumNetAmount'], 'Company revenue must be counted in Other Income');
        $this->assertEquals(115.0, $result['sumNetAmount'], 'Net total must reflect the 115 event exactly ONCE, not 230');
    }

    public function test_r02_linked_company_expense_is_counted_exactly_once_in_financial_totals(): void
    {
        [$tenant, $center, , $supplier] = $this->createFixtures();
        $purchase = PurchaseInvoice::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'supplier_id' => $supplier->id,
            'code' => 'PINV-EXP-001',
            'invoice_number' => 'PINV-EXP-001',
            'issue_date' => '2026-01-15',
            'status' => PurchaseInvoice::STATUS_PAID,
            'subtotal' => 100.00,
            'tax_amount' => 15.00,
            'total' => 115.00,
        ]);

        $this->createCompanyTx($tenant, $center, 'expense', 'approved', null, $purchase);

        $result = app(FinancialSummaryReportService::class)->computeCenterFinancialStatement($tenant->id, $center->id, null, null, false);

        $this->assertEquals(0.0, $result['receivedSparePartsSumNetAmount'], 'Purchase linked to company expense must not be counted in parts purchases');
        $this->assertEquals(115.0, $result['expenseSumNetAmount'], 'Company expense must be counted in Other Expenses');
        $this->assertEquals(-115.0, $result['sumNetAmount'], 'Net total must reflect expense once');
    }

    public function test_r02_cancelled_linked_invoice_does_not_count_in_sales_or_income(): void
    {
        [$tenant, $center, $customer] = $this->createFixtures();
        $invoice = $this->createInvoice($tenant, $center, $customer, 'cancelled');

        $this->createCompanyTx($tenant, $center, 'revenue', 'approved', $invoice);

        $result = app(FinancialSummaryReportService::class)->computeCenterFinancialStatement($tenant->id, $center->id, null, null, false);

        $this->assertEquals(0.0, $result['invoicesSumNetAmount']);
        $this->assertEquals(0.0, $result['incomeSumNetAmount']);
        $this->assertEquals(0.0, $result['sumNetAmount']);
    }

    public function test_r02_unlinked_company_revenue_is_safely_counted_in_income(): void
    {
        [$tenant, $center] = $this->createFixtures();

        // Transaction with no invoice (e.g. non-taxable income)
        $this->createCompanyTx($tenant, $center, 'revenue', 'approved', null, null, false);

        $result = app(FinancialSummaryReportService::class)->computeCenterFinancialStatement($tenant->id, $center->id, null, null, false);

        $this->assertEquals(0.0, $result['invoicesSumNetAmount']);
        $this->assertEquals(100.0, $result['incomeSumNetAmount']);
        $this->assertEquals(100.0, $result['sumNetAmount']);
    }

    public function test_r02_tax_period_deduplicates_linked_company_transactions(): void
    {
        [$tenant, $center, $customer] = $this->createFixtures();
        $invoice = $this->createInvoice($tenant, $center, $customer, 'valid');

        // Linked transaction
        $tx = $this->createCompanyTx($tenant, $center, 'revenue', 'approved', $invoice);

        $rows = app(TaxPeriodService::class)->collectTransactionsForPeriod($tenant->id, '2026-01-01', '2026-03-31');

        $salesRows = collect($rows)->where('transaction_type', 'sales_invoice');
        $txRows = collect($rows)->where('transaction_type', 'company_transaction');

        $this->assertCount(1, $salesRows, 'The tax invoice must be collected as sales_invoice');
        $this->assertCount(0, $txRows, 'Linked company transaction must NOT duplicate the sales invoice in tax collection');
    }

    public function test_r02_cash_register_deduplicates_payments_and_company_transactions(): void
    {
        [$tenant, $center, $customer] = $this->createFixtures();
        $invoice = $this->createInvoice($tenant, $center, $customer, 'valid');

        // Payment recorded for the invoice
        Payment::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'invoice_id' => $invoice->id,
            'amount' => 115.00,
            'payment_date' => '2026-01-15',
            'payment_method' => 'cash',
            'type' => 'payment',
        ]);

        // Approved transaction linked to the invoice
        $this->createCompanyTx($tenant, $center, 'revenue', 'approved', $invoice);

        $items = app(CashRegisterReportService::class)->buildCashRegisterData(
            $tenant->id,
            $center->id,
            Carbon::parse('2026-01-01'),
            Carbon::parse('2026-01-31')
        );

        $paymentItems = $items->where('type_group', 'payment');
        $txItems = $items->where('type_group', 'company_transaction');

        $this->assertCount(1, $paymentItems, 'Payment must be listed in cash register');
        $this->assertCount(0, $txItems, 'Linked company transaction must NOT duplicate payment in cash register');
    }

    // =========================================================================
    // R03 Acceptance Tests: Revenue is classified as income in tax collection
    // =========================================================================

    public function test_r03_revenue_transaction_is_classified_as_income_in_tax_collection(): void
    {
        [$tenant, $center] = $this->createFixtures();

        // Independent (unlinked) taxable revenue transaction
        $tx = $this->createCompanyTx($tenant, $center, 'revenue', 'approved', null, null, true);

        $rows = app(TaxPeriodService::class)->collectTransactionsForPeriod($tenant->id, '2026-01-01', '2026-03-31');
        $row = collect($rows)->firstWhere('transaction_id', $tx->id);

        $this->assertNotNull($row);
        $this->assertSame('company_transaction', $row['transaction_type']);
        $this->assertTrue($row['is_income'], 'Revenue company transaction must have is_income = true');
        $this->assertEquals(100.0, $row['subtotal']);
        $this->assertEquals(15.0, $row['vat_amount']);
    }

    public function test_r03_expense_transaction_is_classified_as_expense_in_tax_collection(): void
    {
        [$tenant, $center] = $this->createFixtures();

        $tx = $this->createCompanyTx($tenant, $center, 'expense', 'approved', null, null, true);

        $rows = app(TaxPeriodService::class)->collectTransactionsForPeriod($tenant->id, '2026-01-01', '2026-03-31');
        $row = collect($rows)->firstWhere('transaction_id', $tx->id);

        $this->assertNotNull($row);
        $this->assertSame('company_transaction', $row['transaction_type']);
        $this->assertFalse($row['is_income'], 'Expense company transaction must have is_income = false');
    }

    public function test_r03_legacy_income_type_is_supported_in_tax_collection(): void
    {
        [$tenant, $center] = $this->createFixtures();

        // Test backward compatibility with legacy 'income' transaction_type string
        $tx = $this->createCompanyTx($tenant, $center, 'income', 'approved', null, null, true);

        $rows = app(TaxPeriodService::class)->collectTransactionsForPeriod($tenant->id, '2026-01-01', '2026-03-31');
        $row = collect($rows)->firstWhere('transaction_id', $tx->id);

        $this->assertNotNull($row);
        $this->assertTrue($row['is_income'], 'Legacy "income" transaction_type must be supported and classified as is_income = true');
    }

    public function test_r03_draft_and_deleted_company_transactions_are_excluded_from_tax_collection(): void
    {
        [$tenant, $center] = $this->createFixtures();

        $draftTx = $this->createCompanyTx($tenant, $center, 'revenue', 'draft', null, null, true);
        $deletedTx = $this->createCompanyTx($tenant, $center, 'revenue', 'approved', null, null, true);
        $deletedTx->delete();

        $rows = app(TaxPeriodService::class)->collectTransactionsForPeriod($tenant->id, '2026-01-01', '2026-03-31');

        $this->assertNull(collect($rows)->firstWhere('transaction_id', $draftTx->id), 'Draft company transaction must NOT be collected in tax period');
        $this->assertNull(collect($rows)->firstWhere('transaction_id', $deletedTx->id), 'Soft-deleted company transaction must NOT be collected in tax period');
    }

    public function test_review_payment_deletion_leaves_purchase_balance_stale(): void
    {
        [$tenant,$center,,$supplier] = $this->createFixtures();
        $purchase = PurchaseInvoice::create(['tenant_id' => $tenant->id, 'center_id' => $center->id, 'supplier_id' => $supplier->id, 'code' => 'REVIEW-DELETE', 'issue_date' => '2026-01-15', 'status' => PurchaseInvoice::STATUS_OPEN, 'subtotal' => 100, 'tax_amount' => 15, 'total' => 115, 'balance' => 115]);
        $service = app(PaymentService::class);
        $payment = $service->recordPurchasePayment($purchase, ['amount' => 115, 'payment_method' => 'cash']);
        $this->assertEquals(0, $purchase->fresh()->balance);
        $service->deletePayment($payment);
        $this->assertCount(0, Payment::query()->where('purchase_invoice_id', $purchase->id)->get());
        $this->assertEquals(0, $purchase->fresh()->balance);
        $this->assertSame(PurchaseInvoice::STATUS_PAID, $purchase->fresh()->status);
    }
}
