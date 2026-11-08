<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Center;
use App\Models\PurchaseInvoice;
use App\Models\Supplier;
use App\Models\Tenant;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\PerformanceBaselineSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PurchaseInvoicesReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->get('/app/reports/purchase-invoices');
        $response->assertRedirect('/login');
    }

    public function test_authenticated_user_can_view_purchase_invoices_report(): void
    {
        app(PerformanceBaselineSeeder::class)->run();

        $tenant = Tenant::first();
        $user = User::where('tenant_id', $tenant->id)->where('is_system_admin', true)->first();

        $response = $this->actingAs($user)->get('/app/reports/purchase-invoices');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Reports/PurchaseInvoices')
            ->has('filters')
            ->has('kpis')
            ->has('invoices')
            ->has('centers')
            ->has('suppliers')
        );
    }

    public function test_purchase_invoices_report_calculates_kpis_accurately(): void
    {
        app(PerformanceBaselineSeeder::class)->run();

        $tenant = Tenant::first();
        $center = Center::where('tenant_id', $tenant->id)->first();
        $user = User::where('tenant_id', $tenant->id)->where('is_system_admin', true)->first();

        $supplier = Supplier::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'name' => 'شركة قطع الغيار المحدودة',
            'phone' => '0501112233',
            'is_active' => true,
        ]);

        PurchaseInvoice::query()->forceDelete();

        // Invoice 1: Open with partial payment
        // Subtotal: 1000, Tax: 150, Discount: 50, Total: 1100, Balance: 600 -> Paid: 500
        PurchaseInvoice::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'supplier_id' => $supplier->id,
            'code' => 'PINV-0001',
            'invoice_number' => 'SUPP-DOC-001',
            'issue_date' => now(),
            'due_date' => now()->addDays(10),
            'status' => PurchaseInvoice::STATUS_OPEN,
            'subtotal' => 1000.00,
            'tax_amount' => 150.00,
            'discount_amount' => 50.00,
            'total' => 1100.00,
            'balance' => 600.00,
        ]);

        // Invoice 2: Paid completely
        // Subtotal: 2000, Tax: 300, Discount: 0, Total: 2300, Balance: 0 -> Paid: 2300
        PurchaseInvoice::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'supplier_id' => $supplier->id,
            'code' => 'PINV-0002',
            'invoice_number' => 'SUPP-DOC-002',
            'issue_date' => now(),
            'due_date' => now()->addDays(5),
            'status' => PurchaseInvoice::STATUS_PAID,
            'subtotal' => 2000.00,
            'tax_amount' => 300.00,
            'discount_amount' => 0.00,
            'total' => 2300.00,
            'balance' => 0.00,
        ]);

        $response = $this->actingAs($user)->get('/app/reports/purchase-invoices?range=all&center_id='.$center->id);

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->where('kpis.total_records', 2)
            ->where('kpis.total_subtotal', fn ($v) => (float) $v === 3000.0)
            ->where('kpis.total_discount', fn ($v) => (float) $v === 50.0)
            ->where('kpis.total_vat', fn ($v) => (float) $v === 450.0)
            ->where('kpis.total_invoice_amount', fn ($v) => (float) $v === 3400.0)
            ->where('kpis.total_paid', fn ($v) => (float) $v === 2800.0)
            ->where('kpis.total_payment_remaining', fn ($v) => (float) $v === 600.0)
        );
    }

    public function test_purchase_invoices_report_filters_by_overdue(): void
    {
        app(PerformanceBaselineSeeder::class)->run();

        $tenant = Tenant::first();
        $center = Center::where('tenant_id', $tenant->id)->first();
        $user = User::where('tenant_id', $tenant->id)->where('is_system_admin', true)->first();

        $supplier = Supplier::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'name' => 'مورد إطارات',
            'phone' => '0502223344',
            'is_active' => true,
        ]);

        PurchaseInvoice::query()->forceDelete();

        // Overdue invoice (due date in past, balance > 0)
        PurchaseInvoice::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'supplier_id' => $supplier->id,
            'code' => 'PINV-OVERDUE',
            'invoice_number' => 'SUPP-OD-1',
            'issue_date' => now()->subDays(30),
            'due_date' => now()->subDays(5),
            'status' => PurchaseInvoice::STATUS_OPEN,
            'subtotal' => 500.00,
            'tax_amount' => 75.00,
            'discount_amount' => 0.00,
            'total' => 575.00,
            'balance' => 575.00,
        ]);

        // Future invoice (due date in future)
        PurchaseInvoice::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'supplier_id' => $supplier->id,
            'code' => 'PINV-FUTURE',
            'invoice_number' => 'SUPP-FUT-1',
            'issue_date' => now(),
            'due_date' => now()->addDays(15),
            'status' => PurchaseInvoice::STATUS_OPEN,
            'subtotal' => 400.00,
            'tax_amount' => 60.00,
            'discount_amount' => 0.00,
            'total' => 460.00,
            'balance' => 460.00,
        ]);

        $responseAll = $this->actingAs($user)->get('/app/reports/purchase-invoices?range=all&center_id='.$center->id);
        $responseAll->assertInertia(fn ($page) => $page->where('kpis.total_records', 2));

        $responseOverdue = $this->actingAs($user)->get('/app/reports/purchase-invoices?range=all&is_overdue=1&center_id='.$center->id);
        $responseOverdue->assertInertia(fn ($page) => $page
            ->where('kpis.total_records', 1)
            ->where('invoices.data.0.code', 'PINV-OVERDUE')
        );
    }

    public function test_purchase_invoices_report_filters_by_date_type(): void
    {
        app(PerformanceBaselineSeeder::class)->run();

        $tenant = Tenant::first();
        $center = Center::where('tenant_id', $tenant->id)->first();
        $user = User::where('tenant_id', $tenant->id)->where('is_system_admin', true)->first();

        $supplier = Supplier::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'name' => 'مورد زيوت',
            'phone' => '0503334455',
            'is_active' => true,
        ]);

        PurchaseInvoice::query()->forceDelete();

        PurchaseInvoice::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'supplier_id' => $supplier->id,
            'code' => 'PINV-DUE-TEST',
            'invoice_number' => 'SUPP-DT-1',
            'issue_date' => Carbon::parse('2026-01-01'),
            'due_date' => Carbon::parse('2026-02-15'),
            'status' => PurchaseInvoice::STATUS_OPEN,
            'subtotal' => 1000.00,
            'tax_amount' => 150.00,
            'discount_amount' => 0.00,
            'total' => 1150.00,
            'balance' => 1150.00,
        ]);

        // Issue date in Jan -> found
        $resIssueJan = $this->actingAs($user)->get('/app/reports/purchase-invoices?range=custom&start_date=2026-01-01&end_date=2026-01-31&date_type=issue_date&center_id='.$center->id);
        $resIssueJan->assertInertia(fn ($page) => $page->where('kpis.total_records', 1));

        // Due date in Jan -> not found
        $resDueJan = $this->actingAs($user)->get('/app/reports/purchase-invoices?range=custom&start_date=2026-01-01&end_date=2026-01-31&date_type=due_date&center_id='.$center->id);
        $resDueJan->assertInertia(fn ($page) => $page->where('kpis.total_records', 0));

        // Due date in Feb -> found
        $resDueFeb = $this->actingAs($user)->get('/app/reports/purchase-invoices?range=custom&start_date=2026-02-01&end_date=2026-02-28&date_type=due_date&center_id='.$center->id);
        $resDueFeb->assertInertia(fn ($page) => $page->where('kpis.total_records', 1));
    }

    public function test_purchase_invoices_report_groups_data(): void
    {
        app(PerformanceBaselineSeeder::class)->run();

        $tenant = Tenant::first();
        $center = Center::where('tenant_id', $tenant->id)->first();
        $user = User::where('tenant_id', $tenant->id)->where('is_system_admin', true)->first();

        $supplierA = Supplier::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'name' => 'المورد أ',
            'phone' => '0501111111',
            'is_active' => true,
        ]);

        $supplierB = Supplier::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'name' => 'المورد ب',
            'phone' => '0502222222',
            'is_active' => true,
        ]);

        PurchaseInvoice::query()->forceDelete();

        PurchaseInvoice::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'supplier_id' => $supplierA->id,
            'code' => 'PINV-A-1',
            'invoice_number' => 'DOC-A',
            'issue_date' => now(),
            'due_date' => now()->addDays(5),
            'status' => PurchaseInvoice::STATUS_PAID,
            'subtotal' => 100.00,
            'tax_amount' => 15.00,
            'discount_amount' => 0.00,
            'total' => 115.00,
            'balance' => 0.00,
        ]);

        PurchaseInvoice::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'supplier_id' => $supplierB->id,
            'code' => 'PINV-B-1',
            'invoice_number' => 'DOC-B',
            'issue_date' => now(),
            'due_date' => now()->addDays(5),
            'status' => PurchaseInvoice::STATUS_OPEN,
            'subtotal' => 200.00,
            'tax_amount' => 30.00,
            'discount_amount' => 0.00,
            'total' => 230.00,
            'balance' => 230.00,
        ]);

        $response = $this->actingAs($user)->get('/app/reports/purchase-invoices?range=all&group_by=supplier&center_id='.$center->id);

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->where('invoices', null)
            ->has('groupedInvoices', 2)
        );
    }

    public function test_purchase_invoices_report_exports_csv_successfully(): void
    {
        app(PerformanceBaselineSeeder::class)->run();

        $tenant = Tenant::first();
        $user = User::where('tenant_id', $tenant->id)->where('is_system_admin', true)->first();

        $response = $this->actingAs($user)->get('/app/reports/purchase-invoices/export?range=all');

        $response->assertStatus(200);
        $this->assertTrue($response->headers->contains('content-type', 'text/csv; charset=UTF-8'));
        $this->assertStringContainsString('attachment;', (string) $response->headers->get('content-disposition'));
    }
}
