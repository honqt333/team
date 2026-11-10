<?php

declare(strict_types=1);
use App\Models\Center;
use App\Models\CompanyTransaction;
use App\Models\Customer;
use App\Models\IncomeCategory;
use App\Models\Invoice;
use App\Models\Tenant;
use App\Services\Reports\FinancialSummaryReportService;
use App\Services\Reports\TaxPeriodService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class IndependentFinanceReviewTest extends TestCase
{
    use RefreshDatabase;

    private function setupRecords(): array
    {
        $t = Tenant::factory()->create();
        $c = Center::factory()->create(['tenant_id' => $t->id]);
        $customer = Customer::factory()->create(['tenant_id' => $t->id, 'center_id' => $c->id]);
        $i = Invoice::factory()->create(['tenant_id' => $t->id, 'center_id' => $c->id, 'customer_id' => $customer->id, 'work_order_id' => null, 'status' => 'draft', 'issue_date' => '2026-01-15', 'total_excl_tax' => 100, 'total_tax' => 15, 'total_incl_tax' => 115]);
        DB::table('invoice_lines')->insert(['invoice_id' => $i->id, 'tenant_id' => $t->id, 'center_id' => $c->id, 'is_part' => false, 'description' => 'Review fixture', 'qty' => 1, 'unit_price' => 100, 'discount_amount' => 0, 'line_total_excl_tax' => 100, 'tax_amount' => 15, 'line_total_incl_tax' => 115, 'created_at' => now(), 'updated_at' => now()]);

        return [$t, $c, $i];
    }

    private function revenue($t, $c, $invoice = null): CompanyTransaction
    {
        $category = IncomeCategory::create(['tenant_id' => $t->id, 'name_ar' => 'Test', 'name_en' => 'Test', 'transaction_type' => 'revenue']);

        return CompanyTransaction::create(['tenant_id' => $t->id, 'center_id' => $c->id, 'income_category_id' => $category->id, 'title' => 'Review fixture', 'transaction_type' => 'revenue', 'transaction_date' => '2026-01-15', 'amount' => 100, 'tax_amount' => 15, 'total_amount' => 115, 'is_taxable' => true, 'status' => 'approved', 'invoice_id' => $invoice?->id]);
    }

    public function test_draft_and_cancelled_invoice_lines_are_counted_as_sales(): void
    {
        [$t,$c,$i] = $this->setupRecords();
        $service = app(FinancialSummaryReportService::class);

        foreach (['draft', 'cancelled'] as $status) {
            $i->update(['status' => $status]);
            $r = $service->computeCenterFinancialStatement($t->id, $c->id, null, null, false);
            $this->assertEquals(115, $r['invoicesSumNetAmount']);
        }
    }

    public function test_linked_company_revenue_is_counted_twice_in_financial_totals(): void
    {
        [$t,$c,$i] = $this->setupRecords();
        $i->update(['status' => 'valid']);
        $this->revenue($t, $c, $i);
        $r = app(FinancialSummaryReportService::class)->computeCenterFinancialStatement($t->id, $c->id, null, null, false);
        $this->assertEquals(115, $r['invoicesSumNetAmount']);
        $this->assertEquals(115, $r['incomeSumNetAmount']);
        $this->assertEquals(230, $r['sumNetAmount']);
    }

    public function test_revenue_transaction_is_classified_as_expense_in_tax_collection(): void
    {
        [$t,$c,$i] = $this->setupRecords();
        $tx = $this->revenue($t, $c);
        $rows = app(TaxPeriodService::class)->collectTransactionsForPeriod($t->id, '2026-01-01', '2026-03-31');
        $row = collect($rows)->firstWhere('transaction_type', 'company_transaction');
        $this->assertSame($tx->id, $row['transaction_id']);
        $this->assertFalse($row['is_income']);
    }
}
