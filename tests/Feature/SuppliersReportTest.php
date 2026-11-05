<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Center;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseReturnInvoice;
use App\Models\Supplier;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\PerformanceBaselineSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SuppliersReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->get('/app/reports/suppliers');
        $response->assertRedirect('/login');
    }

    public function test_wrshah_redirect_to_suppliers_report(): void
    {
        app(PerformanceBaselineSeeder::class)->run();

        $tenant = Tenant::first();
        $user = User::where('tenant_id', $tenant->id)->where('is_system_admin', true)->first();

        $response = $this->actingAs($user)->get('/reports/suppliers');
        $response->assertRedirect(route('app.reports.suppliers'));
    }

    public function test_authenticated_user_can_view_suppliers_report(): void
    {
        app(PerformanceBaselineSeeder::class)->run();

        $tenant = Tenant::first();
        $user = User::where('tenant_id', $tenant->id)->where('is_system_admin', true)->first();

        $response = $this->actingAs($user)->get('/app/reports/suppliers');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Reports/Suppliers')
            ->has('filters')
            ->has('kpis')
            ->has('invoices')
            ->has('centers')
            ->has('suppliers')
        );
    }

    public function test_suppliers_report_calculates_kpis_accurately(): void
    {
        app(PerformanceBaselineSeeder::class)->run();

        $tenant = Tenant::first();
        $center = Center::where('tenant_id', $tenant->id)->first();
        $user = User::where('tenant_id', $tenant->id)->where('is_system_admin', true)->first();

        $supplier = Supplier::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'name' => 'شركة التوريدات العالمية',
            'phone' => '0509998877',
            'type' => 'company',
            'is_active' => true,
        ]);

        PurchaseInvoice::query()->forceDelete();

        // Invoice 1: Partial payment
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

        // Invoice 2: Fully paid
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

        $response = $this->actingAs($user)->get('/app/reports/suppliers?range=all');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Reports/Suppliers')
            ->where('kpis.total_records', 2)
            ->where('kpis.total_subtotal', 3000)
            ->where('kpis.total_vat', 450)
            ->where('kpis.total_discount', 50)
            ->where('kpis.total_invoice_amount', 3400)
            ->where('kpis.total_paid', 2800)
            ->where('kpis.total_payment_remaining', 600)
        );
    }

    public function test_suppliers_report_filters_by_supplier_and_payment_status(): void
    {
        app(PerformanceBaselineSeeder::class)->run();

        $tenant = Tenant::first();
        $center = Center::where('tenant_id', $tenant->id)->first();
        $user = User::where('tenant_id', $tenant->id)->where('is_system_admin', true)->first();

        $supplierA = Supplier::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'name' => 'المورد الأول',
            'is_active' => true,
        ]);

        $supplierB = Supplier::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'name' => 'المورد الثاني',
            'is_active' => true,
        ]);

        PurchaseInvoice::query()->forceDelete();

        // Invoice for Supplier A (Paid)
        PurchaseInvoice::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'supplier_id' => $supplierA->id,
            'code' => 'PINV-0010',
            'issue_date' => now(),
            'status' => PurchaseInvoice::STATUS_PAID,
            'subtotal' => 500.00,
            'tax_amount' => 75.00,
            'total' => 575.00,
            'balance' => 0.00,
        ]);

        // Invoice for Supplier B (Unpaid)
        PurchaseInvoice::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'supplier_id' => $supplierB->id,
            'code' => 'PINV-0020',
            'issue_date' => now(),
            'status' => PurchaseInvoice::STATUS_OPEN,
            'subtotal' => 800.00,
            'tax_amount' => 120.00,
            'total' => 920.00,
            'balance' => 920.00,
        ]);

        // Filter by Supplier A
        $responseA = $this->actingAs($user)->get("/app/reports/suppliers?range=all&supplier_id={$supplierA->id}");
        $responseA->assertInertia(fn ($page) => $page
            ->where('kpis.total_records', 1)
            ->where('kpis.total_invoice_amount', 575)
            ->has('invoices.data', 1)
        );

        // Filter by Unpaid
        $responseUnpaid = $this->actingAs($user)->get('/app/reports/suppliers?range=all&payment_status=unpaid');
        $responseUnpaid->assertInertia(fn ($page) => $page
            ->where('kpis.total_records', 1)
            ->where('kpis.total_invoice_amount', 920)
            ->has('invoices.data', 1)
        );
    }

    public function test_suppliers_report_groups_data_by_supplier(): void
    {
        app(PerformanceBaselineSeeder::class)->run();

        $tenant = Tenant::first();
        $center = Center::where('tenant_id', $tenant->id)->first();
        $user = User::where('tenant_id', $tenant->id)->where('is_system_admin', true)->first();

        $supplierA = Supplier::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'name' => 'شركة البابطين',
            'is_active' => true,
        ]);

        $supplierB = Supplier::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'name' => 'مؤسسة الرمال',
            'is_active' => true,
        ]);

        PurchaseInvoice::query()->forceDelete();

        PurchaseInvoice::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'supplier_id' => $supplierA->id,
            'code' => 'PINV-0001',
            'issue_date' => now(),
            'status' => PurchaseInvoice::STATUS_PAID,
            'subtotal' => 1000.00,
            'tax_amount' => 150.00,
            'total' => 1150.00,
            'balance' => 0.00,
        ]);

        PurchaseInvoice::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'supplier_id' => $supplierB->id,
            'code' => 'PINV-0002',
            'issue_date' => now(),
            'status' => PurchaseInvoice::STATUS_OPEN,
            'subtotal' => 500.00,
            'tax_amount' => 75.00,
            'total' => 575.00,
            'balance' => 575.00,
        ]);

        $response = $this->actingAs($user)->get('/app/reports/suppliers?range=all&group_by=supplier');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->has('groupedInvoices', 2)
            ->where('invoices', null)
        );
    }

    public function test_suppliers_report_exports_csv_successfully(): void
    {
        app(PerformanceBaselineSeeder::class)->run();

        $tenant = Tenant::first();
        $center = Center::where('tenant_id', $tenant->id)->first();
        $user = User::where('tenant_id', $tenant->id)->where('is_system_admin', true)->first();

        $supplier = Supplier::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'name' => 'مورد التصدير الفوري',
            'phone' => '0555555555',
            'is_active' => true,
        ]);

        PurchaseInvoice::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'supplier_id' => $supplier->id,
            'code' => 'PINV-EX-001',
            'invoice_number' => 'EXT-100',
            'issue_date' => now(),
            'status' => PurchaseInvoice::STATUS_PAID,
            'subtotal' => 1000.00,
            'tax_amount' => 150.00,
            'total' => 1150.00,
            'balance' => 0.00,
        ]);

        $response = $this->actingAs($user)->get('/app/reports/suppliers/export?range=all');

        $response->assertStatus(200);
        $this->assertTrue(str_contains((string) $response->headers->get('content-type'), 'text/csv'));

        $content = $response->streamedContent();
        $this->assertStringStartsWith("\xEF\xBB\xBF", $content);
        $this->assertStringContainsString('رقم الفاتورة / المستند', $content);
        $this->assertStringContainsString('مورد التصدير الفوري', $content);
        $this->assertStringContainsString('PINV-EX-001', $content);
    }

    public function test_suppliers_report_accounts_for_return_invoices_and_debit_notes(): void
    {
        app(PerformanceBaselineSeeder::class)->run();

        $tenant = Tenant::first();
        $center = Center::where('tenant_id', $tenant->id)->first();
        $user = User::where('tenant_id', $tenant->id)->where('is_system_admin', true)->first();

        // Clear existing to test exact numbers
        PurchaseInvoice::where('tenant_id', $tenant->id)->forceDelete();
        PurchaseReturnInvoice::where('tenant_id', $tenant->id)->forceDelete();

        $supplier = Supplier::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'name' => 'شركة التوريدات المتطورة',
            'is_active' => true,
        ]);

        $invoice = PurchaseInvoice::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'supplier_id' => $supplier->id,
            'code' => 'PINV-TEST-RET-01',
            'invoice_number' => 'INV-ORIG-999',
            'issue_date' => now()->toDateString(),
            'status' => 'paid',
            'subtotal' => 1000.00,
            'tax_amount' => 150.00,
            'discount_amount' => 0.00,
            'total' => 1150.00,
            'balance' => 0.00,
        ]);

        PurchaseReturnInvoice::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'purchase_invoice_id' => $invoice->id,
            'code' => 'PRET-TEST-01',
            'return_date' => now()->toDateString(),
            'subtotal' => 300.00,
            'tax_amount' => 45.00,
            'total' => 345.00,
            'create_debit_note' => true,
            'debit_note_date' => now()->toDateString(),
            'notes' => 'قطع تالفة مع إشعار مدين',
        ]);

        // 1. All documents
        $response = $this->actingAs($user)->get('/app/reports/suppliers?range=all');
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Reports/Suppliers')
            ->where('kpis.total_records', 2)
            ->where('kpis.total_invoices_count', 1)
            ->where('kpis.total_returns_count', 1)
            ->where('kpis.total_debit_notes_count', 1)
            ->where('kpis.total_subtotal', 700)
            ->where('kpis.total_vat', 105)
            ->where('kpis.total_invoice_amount', 805)
            ->where('kpis.total_returns_amount', 345)
            ->where('kpis.total_debit_notes_amount', 345)
            ->has('invoices.data', 2)
        );

        // 2. Filter debit notes only
        $responseDebit = $this->actingAs($user)->get('/app/reports/suppliers?range=all&document_type=debit_notes');
        $responseDebit->assertStatus(200);
        $responseDebit->assertInertia(fn ($page) => $page
            ->where('kpis.total_records', 1)
            ->has('invoices.data', 1)
            ->where('invoices.data.0.record_type', 'debit_note')
            ->where('invoices.data.0.code', 'PRET-TEST-01')
        );
    }
}
