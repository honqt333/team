<?php

declare(strict_types=1);
use App\Models\Center;
use App\Models\CompanyTransaction;
use App\Models\Customer;
use App\Models\IncomeCategory;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Tenant;
use App\Services\Reports\CashRegisterReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IndependentCashReviewTest extends TestCase
{
    use RefreshDatabase;

    private function records(string $status = 'approved'): array
    {
        $t = Tenant::factory()->create();
        $c = Center::factory()->create(['tenant_id' => $t->id]);
        $category = IncomeCategory::create(['tenant_id' => $t->id, 'name_ar' => 'Test', 'name_en' => 'Test', 'transaction_type' => 'revenue']);
        $tx = CompanyTransaction::create(['tenant_id' => $t->id, 'center_id' => $c->id, 'income_category_id' => $category->id, 'title' => 'Review fixture', 'transaction_type' => 'revenue', 'transaction_date' => '2026-01-15', 'amount' => 100, 'tax_amount' => 15, 'total_amount' => 115, 'is_taxable' => true, 'status' => $status]);

        return [$t, $c, $tx];
    }

    private function rows($t, $c, string $method = 'all')
    {
        return app(CashRegisterReportService::class)->buildCashRegisterData($t->id, $c->id, null, null, $method, accessibleCenterIds: [$c->id]);
    }

    public function test_draft_revenue_changes_cash_balance(): void
    {
        [$t,$c] = $this->records('draft');
        $rows = $this->rows($t, $c);
        $this->assertCount(1, $rows);
        $this->assertEquals(115, $rows->sum('debit'));
    }

    public function test_bank_filter_contains_cash_transaction(): void
    {
        [$t,$c] = $this->records();
        $rows = $this->rows($t, $c, 'transfer');
        $this->assertCount(1, $rows);
        $this->assertSame('cash', $rows->first()['payment_mode']);
    }

    public function test_payment_and_its_company_transaction_are_both_added(): void
    {
        [$t,$c,$tx] = $this->records();
        $customer = Customer::factory()->create(['tenant_id' => $t->id, 'center_id' => $c->id]);
        $invoice = Invoice::factory()->create(['tenant_id' => $t->id, 'center_id' => $c->id, 'customer_id' => $customer->id, 'work_order_id' => null, 'status' => 'valid', 'total_incl_tax' => 115]);
        $tx->update(['invoice_id' => $invoice->id]);
        Payment::withoutEvents(fn () => Payment::create(['tenant_id' => $t->id, 'center_id' => $c->id, 'invoice_id' => $invoice->id, 'amount' => 115, 'payment_date' => '2026-01-15', 'payment_method' => 'cash', 'type' => 'payment']));
        $rows = $this->rows($t, $c);
        $this->assertCount(2, $rows);
        $this->assertEquals(230,$rows->sum('debit'));
    }
}
