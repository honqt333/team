<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Center;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\InvoiceLine;
use App\Models\Tenant;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\PerformanceBaselineSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoicesReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->get('/app/reports/invoices');
        $response->assertRedirect('/login');
    }

    public function test_authenticated_user_can_view_invoices_report(): void
    {
        app(PerformanceBaselineSeeder::class)->run();

        $tenant = Tenant::first();
        $user = User::where('tenant_id', $tenant->id)->where('is_system_admin', true)->first();

        $response = $this->actingAs($user)->get('/app/reports/invoices');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Reports/Invoices')
            ->has('filters')
            ->has('kpis')
            ->has('invoices')
            ->has('centers')
            ->has('customers')
        );
    }

    public function test_invoices_report_calculates_kpis_accurately(): void
    {
        app(PerformanceBaselineSeeder::class)->run();

        $tenant = Tenant::first();
        $center = Center::where('tenant_id', $tenant->id)->first();
        $customer = Customer::where('tenant_id', $tenant->id)->first();
        $user = User::where('tenant_id', $tenant->id)->where('is_system_admin', true)->first();

        // Create specific invoices with known amounts
        $invoice1 = Invoice::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'customer_id' => $customer->id,
            'invoice_number' => 'INV-TEST-001',
            'type' => 'invoice',
            'subtype' => 'simplified',
            'status' => 'valid',
            'payment_status' => 'unpaid',
            'issue_date' => now(),
            'supply_date' => now(),
            'due_date' => now()->addDays(5),
            'total_excl_tax' => 1000.00,
            'total_tax' => 150.00,
            'total_incl_tax' => 1150.00,
            'total_paid' => 0.00,
        ]);

        InvoiceLine::create([
            'invoice_id' => $invoice1->id,
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'description' => 'Service item',
            'qty' => 1,
            'unit_price' => 1050.00,
            'discount_amount' => 50.00,
            'tax_amount' => 150.00,
            'line_total_excl_tax' => 1000.00,
            'line_total_incl_tax' => 1150.00,
        ]);

        $invoice2 = Invoice::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'customer_id' => $customer->id,
            'invoice_number' => 'INV-TEST-002',
            'type' => 'invoice',
            'subtype' => 'simplified',
            'status' => 'valid',
            'payment_status' => 'paid',
            'issue_date' => now(),
            'supply_date' => now(),
            'due_date' => now()->addDays(5),
            'total_excl_tax' => 2000.00,
            'total_tax' => 300.00,
            'total_incl_tax' => 2300.00,
            'total_paid' => 2300.00,
        ]);

        $response = $this->actingAs($user)->get('/app/reports/invoices?range=today&center_id='.$center->id);

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Reports/Invoices')
            ->where('kpis.total_records', 2)
            ->where('kpis.total_subtotal', fn ($v) => (float) $v === 3000.0)
            ->where('kpis.total_discount', fn ($v) => (float) $v === 50.0)
            ->where('kpis.total_vat', fn ($v) => (float) $v === 450.0)
            ->where('kpis.total_invoice_amount', fn ($v) => (float) $v === 3450.0)
            ->where('kpis.total_paid', fn ($v) => (float) $v === 2300.0)
            ->where('kpis.total_payment_remaining', fn ($v) => (float) $v === 1150.0)
        );
    }

    public function test_invoices_report_filters_by_overdue(): void
    {
        app(PerformanceBaselineSeeder::class)->run();

        $tenant = Tenant::first();
        $center = Center::where('tenant_id', $tenant->id)->first();
        $customer = Customer::where('tenant_id', $tenant->id)->first();
        $user = User::where('tenant_id', $tenant->id)->where('is_system_admin', true)->first();

        // Clean out existing invoices for clear count
        Invoice::query()->forceDelete();

        // Overdue invoice (due yesterday, unpaid)
        Invoice::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'customer_id' => $customer->id,
            'invoice_number' => 'INV-OVERDUE',
            'type' => 'invoice',
            'subtype' => 'simplified',
            'status' => 'valid',
            'payment_status' => 'unpaid',
            'issue_date' => now()->subDays(5),
            'supply_date' => now()->subDays(5),
            'due_date' => now()->subDay(),
            'total_excl_tax' => 500.00,
            'total_tax' => 75.00,
            'total_incl_tax' => 575.00,
            'total_paid' => 0.00,
        ]);

        // Not overdue (due next week, unpaid)
        Invoice::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'customer_id' => $customer->id,
            'invoice_number' => 'INV-FUTURE',
            'type' => 'invoice',
            'subtype' => 'simplified',
            'status' => 'valid',
            'payment_status' => 'unpaid',
            'issue_date' => now(),
            'supply_date' => now(),
            'due_date' => now()->addDays(7),
            'total_excl_tax' => 300.00,
            'total_tax' => 45.00,
            'total_incl_tax' => 345.00,
            'total_paid' => 0.00,
        ]);

        $response = $this->actingAs($user)->get('/app/reports/invoices?range=all&is_overdue=1&center_id='.$center->id);

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->where('kpis.total_records', 1)
            ->where('invoices.data.0.invoice_number', 'INV-OVERDUE')
        );
    }

    public function test_invoices_report_filters_by_date_type(): void
    {
        app(PerformanceBaselineSeeder::class)->run();

        $tenant = Tenant::first();
        $center = Center::where('tenant_id', $tenant->id)->first();
        $customer = Customer::where('tenant_id', $tenant->id)->first();
        $user = User::where('tenant_id', $tenant->id)->where('is_system_admin', true)->first();

        Invoice::query()->forceDelete();

        Invoice::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'customer_id' => $customer->id,
            'invoice_number' => 'INV-DUE-TEST',
            'type' => 'invoice',
            'subtype' => 'simplified',
            'status' => 'valid',
            'payment_status' => 'unpaid',
            'issue_date' => Carbon::parse('2026-01-01 10:00:00'),
            'supply_date' => Carbon::parse('2026-01-01'),
            'due_date' => Carbon::parse('2026-02-15'),
            'total_excl_tax' => 100.00,
            'total_tax' => 15.00,
            'total_incl_tax' => 115.00,
            'total_paid' => 0.00,
        ]);

        // Filter by issue_date range in January -> found
        $resIssue = $this->actingAs($user)->get('/app/reports/invoices?range=custom&start_date=2026-01-01&end_date=2026-01-31&date_type=issue_date&center_id='.$center->id);
        $resIssue->assertInertia(fn ($page) => $page->where('kpis.total_records', 1));

        // Filter by due_date range in January -> not found (due is in Feb)
        $resDueJan = $this->actingAs($user)->get('/app/reports/invoices?range=custom&start_date=2026-01-01&end_date=2026-01-31&date_type=due_date&center_id='.$center->id);
        $resDueJan->assertInertia(fn ($page) => $page->where('kpis.total_records', 0));

        // Filter by due_date range in February -> found
        $resDueFeb = $this->actingAs($user)->get('/app/reports/invoices?range=custom&start_date=2026-02-01&end_date=2026-02-28&date_type=due_date&center_id='.$center->id);
        $resDueFeb->assertInertia(fn ($page) => $page->where('kpis.total_records', 1));
    }

    public function test_invoices_report_groups_data(): void
    {
        app(PerformanceBaselineSeeder::class)->run();

        $tenant = Tenant::first();
        $center = Center::where('tenant_id', $tenant->id)->first();
        $customer = Customer::where('tenant_id', $tenant->id)->first();
        $user = User::where('tenant_id', $tenant->id)->where('is_system_admin', true)->first();

        Invoice::query()->forceDelete();

        Invoice::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'customer_id' => $customer->id,
            'invoice_number' => 'INV-GRP-1',
            'type' => 'invoice',
            'subtype' => 'simplified',
            'status' => 'valid',
            'payment_status' => 'paid',
            'issue_date' => now(),
            'supply_date' => now(),
            'total_excl_tax' => 100.00,
            'total_tax' => 15.00,
            'total_incl_tax' => 115.00,
            'total_paid' => 115.00,
        ]);

        Invoice::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'customer_id' => $customer->id,
            'invoice_number' => 'INV-GRP-2',
            'type' => 'invoice',
            'subtype' => 'simplified',
            'status' => 'valid',
            'payment_status' => 'unpaid',
            'issue_date' => now(),
            'supply_date' => now(),
            'total_excl_tax' => 200.00,
            'total_tax' => 30.00,
            'total_incl_tax' => 230.00,
            'total_paid' => 0.00,
        ]);

        $response = $this->actingAs($user)->get('/app/reports/invoices?range=all&group_by=payment_status&center_id='.$center->id);

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->where('invoices', null)
            ->has('groupedInvoices', 2)
        );
    }

    public function test_invoices_report_exports_csv_successfully(): void
    {
        app(PerformanceBaselineSeeder::class)->run();

        $tenant = Tenant::first();
        $user = User::where('tenant_id', $tenant->id)->where('is_system_admin', true)->first();

        $response = $this->actingAs($user)->get('/app/reports/invoices/export?range=all');

        $response->assertStatus(200);
        $this->assertTrue($response->headers->contains('content-type', 'text/csv; charset=UTF-8'));
        $this->assertStringContainsString('attachment;', (string) $response->headers->get('content-disposition'));
    }
}
