<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Center;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\PerformanceBaselineSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class FinancialSummaryReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->get('/app/reports/financial-summary');
        $response->assertRedirect('/login');
    }

    public function test_wrshah_legacy_redirects(): void
    {
        app(PerformanceBaselineSeeder::class)->run();

        $tenant = Tenant::first();
        $user = User::where('tenant_id', $tenant->id)->where('is_system_admin', true)->first();

        $res1 = $this->actingAs($user)->get('/reports/financialSummary');
        $res1->assertRedirect(route('app.reports.financial-summary'));

        $res2 = $this->actingAs($user)->get('/app/reports/financialSummary');
        $res2->assertRedirect(route('app.reports.financial-summary'));
    }

    public function test_authenticated_user_can_view_financial_summary_report(): void
    {
        app(PerformanceBaselineSeeder::class)->run();

        $tenant = Tenant::first();
        $user = User::where('tenant_id', $tenant->id)->where('is_system_admin', true)->first();

        $response = $this->actingAs($user)->get('/app/reports/financial-summary');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Reports/FinancialSummary')
            ->has('filters')
            ->has('reportTitle')
            ->has('result')
            ->has('overallSummary')
            ->has('centers')
            ->has('companyInfo')
        );
    }

    public function test_financial_summary_aggregates_sales_and_returns(): void
    {
        app(PerformanceBaselineSeeder::class)->run();

        $tenant = Tenant::first();
        $center = Center::create([
            'tenant_id' => $tenant->id,
            'name' => 'فرع الملخص المالي التجريبي',
            'slug' => 'fin-summary-'.uniqid(),
            'address' => 'الرياض',
        ]);
        $user = User::where('tenant_id', $tenant->id)->where('is_system_admin', true)->first();

        $customer = Customer::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'name' => 'عميل مالي تجريبي',
            'phone' => '0509988776',
            'type' => 'individual',
        ]);

        // 1. Create a sales invoice with 1 service line and 1 spare part line
        $invoice = Invoice::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'customer_id' => $customer->id,
            'invoice_number' => 'INV-TEST-001',
            'type' => 'invoice',
            'status' => 'valid',
            'issue_date' => now(),
            'supply_date' => now(),
            'total_excl_tax' => 1500.00,
            'total_tax' => 225.00,
            'total_incl_tax' => 1725.00,
            'total_paid' => 1725.00,
        ]);

        // Service line: qty 1, price 1000, discount 100, subtotal 900, vat 135, net 1035
        DB::table('invoice_lines')->insert([
            'invoice_id' => $invoice->id,
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'is_part' => 0,
            'description' => 'أجور عمل فحص وتوضيب',
            'qty' => 1,
            'unit_price' => 1000.00,
            'discount_amount' => 100.00,
            'line_total_excl_tax' => 900.00,
            'tax_amount' => 135.00,
            'line_total_incl_tax' => 1035.00,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Spare part line: qty 2, price 300 = 600, discount 0, subtotal 600, vat 90, net 690
        DB::table('invoice_lines')->insert([
            'invoice_id' => $invoice->id,
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'is_part' => 1,
            'description' => 'فحمات فرامل أصلية',
            'qty' => 2,
            'unit_price' => 300.00,
            'discount_amount' => 0.00,
            'line_total_excl_tax' => 600.00,
            'tax_amount' => 90.00,
            'line_total_incl_tax' => 690.00,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($user)->get('/app/reports/financial-summary?range=this_month&center_id='.$center->id);
        $response->assertStatus(200);

        $response->assertInertia(fn ($page) => $page
            ->component('Reports/FinancialSummary')
            ->where('overallSummary.invoicesServicesSumGrossAmount', 1000)
            ->where('overallSummary.invoicesServicesSumDiscountAmount', 100)
            ->where('overallSummary.invoicesServicesSumSubTotal', 900)
            ->where('overallSummary.invoicesServicesSumVATAmount', 135)
            ->where('overallSummary.invoicesServicesSumNetAmount', 1035)
            ->where('overallSummary.invoicesSparePartsSumGrossAmount', 600)
            ->where('overallSummary.invoicesSparePartsSumNetAmount', 690)
            ->where('overallSummary.invoicesSumNetAmount', 1725)
        );
    }

    public function test_financial_summary_filters_by_center(): void
    {
        app(PerformanceBaselineSeeder::class)->run();

        $tenant = Tenant::first();
        $center2 = Center::create([
            'tenant_id' => $tenant->id,
            'name' => 'فرع الشمال التجريبي',
            'slug' => 'north-branch-'.uniqid(),
            'address' => 'الرياض',
        ]);

        $user = User::where('tenant_id', $tenant->id)->where('is_system_admin', true)->first();

        $response = $this->actingAs($user)->get('/app/reports/financial-summary?center_id='.$center2->id);

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Reports/FinancialSummary')
            ->has('result', 1)
            ->where('result.0.shop.id', $center2->id)
            ->where('result.0.shop.name', 'فرع الشمال التجريبي')
        );
    }

    public function test_financial_summary_export_csv(): void
    {
        app(PerformanceBaselineSeeder::class)->run();

        $tenant = Tenant::first();
        $user = User::where('tenant_id', $tenant->id)->where('is_system_admin', true)->first();

        $response = $this->actingAs($user)->get('/app/reports/financial-summary/export?range=this_month');

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');

        $content = $response->streamedContent();
        $this->assertStringStartsWith(chr(0xEF).chr(0xBB).chr(0xBF), $content);
        $this->assertStringContainsString('الفرع', $content);
        $this->assertStringContainsString('فواتير المبيعات', $content);
        $this->assertStringContainsString('صافي الربح التشغيلي', $content);
    }
}
