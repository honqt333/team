<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Center;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\PurchaseInvoice;
use App\Models\Supplier;
use App\Models\TaxPeriod;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\PerformanceBaselineSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaxPeriodsReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->get('/app/reports/tax-periods');
        $response->assertRedirect('/login');
    }

    public function test_authenticated_user_can_view_tax_periods_index(): void
    {
        app(PerformanceBaselineSeeder::class)->run();

        $tenant = Tenant::first();
        $user = User::where('tenant_id', $tenant->id)->where('is_system_admin', true)->first();

        $response = $this->actingAs($user)->get('/app/reports/tax-periods');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Reports/TaxPeriods/Index')
            ->has('taxPeriods')
            ->has('periods3Years')
            ->has('filters')
        );
    }

    public function test_user_can_view_three_years_periods_json(): void
    {
        app(PerformanceBaselineSeeder::class)->run();

        $tenant = Tenant::first();
        $user = User::where('tenant_id', $tenant->id)->where('is_system_admin', true)->first();

        $response = $this->actingAs($user)->get('/app/reports/tax-periods/3-years');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                '*' => [
                    'year',
                    'quarters' => [
                        '*' => [
                            'key',
                            'year',
                            'quarter',
                            'name_ar',
                            'start_date',
                            'end_date',
                            'is_created',
                        ],
                    ],
                ],
            ],
        ]);
    }

    public function test_user_can_preview_and_create_tax_period(): void
    {
        app(PerformanceBaselineSeeder::class)->run();

        $tenant = Tenant::first();
        $center = Center::where('tenant_id', $tenant->id)->first();
        $user = User::where('tenant_id', $tenant->id)->where('is_system_admin', true)->first();
        $customer = Customer::firstOrCreate(
            ['tenant_id' => $tenant->id, 'phone' => '0501234567'],
            ['center_id' => $center->id, 'name' => 'عميل تجريبي', 'phone' => '0501234567']
        );
        $supplier = Supplier::firstOrCreate(
            ['tenant_id' => $tenant->id, 'name' => 'مورد تجريبي'],
            ['name' => 'مورد تجريبي', 'phone' => '0507654321']
        );

        $currentYear = (int) date('Y');
        $periodKey = "{$currentYear}-Q1";

        // Create a sales invoice in Q1
        $salesInvoice = Invoice::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'customer_id' => $customer->id,
            'invoice_number' => 'INV-TEST-001',
            'issue_date' => "{$currentYear}-01-15",
            'supply_date' => "{$currentYear}-01-15",
            'type' => 'invoice',
            'status' => 'valid',
            'payment_status' => 'paid',
            'total_excl_tax' => 1000.00,
            'total_tax' => 150.00,
            'total_incl_tax' => 1150.00,
            'customer_name_snapshot' => 'عميل تجريبي',
        ]);

        // Create a purchase invoice in Q1
        $purchaseInvoice = PurchaseInvoice::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'supplier_id' => $supplier->id,
            'code' => 'PI-001',
            'invoice_number' => 'PI-TEST-001',
            'issue_date' => "{$currentYear}-02-10",
            'status' => 'valid',
            'subtotal' => 400.00,
            'tax_amount' => 60.00,
            'total' => 460.00,
        ]);

        // Preview creation page
        $response = $this->actingAs($user)->get("/app/reports/tax-periods/new/{$periodKey}");
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Reports/TaxPeriods/Create')
            ->has('periodMeta')
            ->has('transactions')
            ->has('summary')
        );

        // Store tax period
        $storeResponse = $this->actingAs($user)->post('/app/reports/tax-periods', [
            'period_key' => $periodKey,
            'transactions' => [
                [
                    'transaction_type' => 'sales_invoice',
                    'transaction_id' => $salesInvoice->id,
                    'ref_no' => 'INV-TEST-001',
                    'transaction_date' => "{$currentYear}-01-15",
                    'party_name' => 'عميل تجريبي',
                    'tax_number' => null,
                    'is_income' => true,
                    'subtotal' => 1000.00,
                    'vat_rate' => 15.00,
                    'vat_amount' => 150.00,
                    'total_amount' => 1150.00,
                    'is_selected' => true,
                ],
                [
                    'transaction_type' => 'purchase_invoice',
                    'transaction_id' => $purchaseInvoice->id,
                    'ref_no' => 'PI-TEST-001',
                    'transaction_date' => "{$currentYear}-02-10",
                    'party_name' => 'مورد تجريبي',
                    'tax_number' => null,
                    'is_income' => false,
                    'subtotal' => 400.00,
                    'vat_rate' => 15.00,
                    'vat_amount' => 60.00,
                    'total_amount' => 460.00,
                    'is_selected' => true,
                ],
            ],
        ]);

        $storeResponse->assertRedirect();

        $taxPeriod = TaxPeriod::where('tenant_id', $tenant->id)->where('period_key', $periodKey)->first();
        $this->assertNotNull($taxPeriod);
        $this->assertEquals(2, $taxPeriod->transactions_count);
        $this->assertEquals(150.00, (float) $taxPeriod->sum_income_vat_amount);
        $this->assertEquals(60.00, (float) $taxPeriod->sum_expense_vat_amount);
        $this->assertEquals(90.00, (float) $taxPeriod->net_vat_amount);
        $this->assertEquals('draft', $taxPeriod->status);
    }

    public function test_user_can_view_and_post_tax_period(): void
    {
        app(PerformanceBaselineSeeder::class)->run();

        $tenant = Tenant::first();
        $user = User::where('tenant_id', $tenant->id)->where('is_system_admin', true)->first();

        $currentYear = (int) date('Y');
        $periodKey = "{$currentYear}-Q2";

        $period = TaxPeriod::create([
            'tenant_id' => $tenant->id,
            'period_key' => $periodKey,
            'name' => "الربع الثاني {$currentYear}",
            'year' => $currentYear,
            'year_period' => '2',
            'period_type' => 'quarterly',
            'start_date' => "{$currentYear}-04-01",
            'end_date' => "{$currentYear}-06-30",
            'status' => 'draft',
            'transactions_count' => 1,
            'income_transactions_count' => 1,
            'sum_income_subtotal' => 2000.00,
            'sum_income_vat_amount' => 300.00,
            'sum_income_total_amount' => 2300.00,
            'sum_subtotal' => 2000.00,
            'sum_vat_amount' => 300.00,
            'sum_total_amount' => 2300.00,
            'net_vat_amount' => 300.00,
            'created_by' => $user->id,
        ]);

        // View show page
        $showResponse = $this->actingAs($user)->get("/app/reports/tax-periods/{$period->id}");
        $showResponse->assertStatus(200);
        $showResponse->assertInertia(fn ($page) => $page
            ->component('Reports/TaxPeriods/Show')
            ->has('taxPeriod')
            ->has('summary')
            ->has('transactions')
        );

        // Post period
        $postResponse = $this->actingAs($user)->post("/app/reports/tax-periods/{$period->id}/post");
        $postResponse->assertRedirect();

        $period->refresh();
        $this->assertEquals('posted', $period->status);
        $this->assertNotNull($period->posted_at);
        $this->assertEquals($user->id, $period->posted_by);
    }

    public function test_user_cannot_access_another_tenants_tax_period(): void
    {
        app(PerformanceBaselineSeeder::class)->run();

        $tenantA = Tenant::first();
        $tenantB = Tenant::skip(1)->first() ?? Tenant::create(['name' => 'Tenant B', 'slug' => 'tenant-b', 'status' => 'active']);

        $userA = User::where('tenant_id', $tenantA->id)->where('is_system_admin', true)->first();

        $periodB = TaxPeriod::create([
            'tenant_id' => $tenantB->id,
            'period_key' => '2026-Q1',
            'name' => 'الربع الأول 2026',
            'year' => 2026,
            'year_period' => '1',
            'period_type' => 'quarterly',
            'start_date' => '2026-01-01',
            'end_date' => '2026-03-31',
            'status' => 'draft',
        ]);

        // User A tries to view Period B (should be 404 due to TenantScoped global scope, or 403)
        $response = $this->actingAs($userA)->get("/app/reports/tax-periods/{$periodB->id}");
        $this->assertTrue(in_array($response->status(), [403, 404]));
    }

    public function test_user_can_export_tax_period_excel_and_zatca(): void
    {
        app(PerformanceBaselineSeeder::class)->run();

        $tenant = Tenant::first();
        $user = User::where('tenant_id', $tenant->id)->where('is_system_admin', true)->first();

        $period = TaxPeriod::create([
            'tenant_id' => $tenant->id,
            'period_key' => '2026-Q3',
            'name' => 'الربع الثالث 2026',
            'year' => 2026,
            'year_period' => '3',
            'period_type' => 'quarterly',
            'start_date' => '2026-07-01',
            'end_date' => '2026-09-30',
            'status' => 'draft',
            'sum_income_subtotal' => 1000.00,
            'sum_income_vat_amount' => 150.00,
            'net_vat_amount' => 150.00,
        ]);

        $excelResponse = $this->actingAs($user)->get("/app/reports/tax-periods/{$period->id}/export-excel");
        $excelResponse->assertStatus(200);
        $excelResponse->assertHeader('content-type', 'text/csv; charset=UTF-8');

        $zatcaResponse = $this->actingAs($user)->get("/app/reports/tax-periods/{$period->id}/export-zatca");
        $zatcaResponse->assertStatus(200);
        $zatcaResponse->assertHeader('content-type', 'text/csv; charset=UTF-8');
    }
}
