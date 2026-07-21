<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\Center;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Part;
use App\Models\Service;
use App\Models\Tenant;
use App\Models\TenantTaxSetting;
use App\Models\User;
use App\Models\WorkOrder;
use App\Models\WorkOrderItem;
use App\Models\WorkOrderItemPart;
use App\Services\InvoiceService;
use App\Services\Optimization\TaxCalculator;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoiceServiceTest extends TestCase
{
    use RefreshDatabase;

    protected InvoiceService $invoiceService;

    protected Tenant $tenant;

    protected Center $center;

    protected User $user;

    protected Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->invoiceService = new InvoiceService(new TaxCalculator);

        $this->tenant = Tenant::factory()->create(['name' => 'Test Repair Shop', 'legal_name' => 'Test Repair LLC']);
        $this->center = Center::factory()->create(['tenant_id' => $this->tenant->id, 'name' => 'Main Branch']);
        $this->user = User::factory()->create(['tenant_id' => $this->tenant->id, 'current_center_id' => $this->center->id]);

        TenantTaxSetting::create([
            'tenant_id' => $this->tenant->id,
            'vat_enabled' => true,
            'tax_number' => '300000000000003',
            'tax_rate' => 15.00,
        ]);

        $this->customer = Customer::factory()->create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'name' => 'John Customer',
            'tax_number' => '311111111111113',
            'address_line' => 'Olaya St, Riyadh',
        ]);
    }

    public function test_create_from_work_order_creates_draft_invoice_with_lines()
    {
        $workOrder = WorkOrder::factory()->create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'customer_id' => $this->customer->id,
            'code' => 'WO-000099',
            'tax_enabled_snapshot' => true,
            'pricing_mode_snapshot' => 'exclusive',
            'tax_rate_snapshot' => 15.00,
            'currency_code' => 'SAR',
            'total_excl_tax' => 200.00,
            'total_tax' => 30.00,
            'total_incl_tax' => 230.00,
        ]);

        $service = Service::factory()->create(['tenant_id' => $this->tenant->id, 'name_ar' => 'فحص كمبيوتر']);

        $item = WorkOrderItem::create([
            'work_order_id' => $workOrder->id,
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'service_id' => $service->id,
            'title' => 'فحص كمبيوتر وبرمجة',
            'qty' => 1,
            'unit_price' => 100.00,
            'discount_type' => 'none',
            'discount_value' => 0,
            'discount_amount' => 0,
            'is_taxable' => true,
            'status' => 'completed',
        ]);

        $part = Part::factory()->create(['tenant_id' => $this->tenant->id, 'name_ar' => 'فلتر زيت']);

        WorkOrderItemPart::create([
            'work_order_id' => $workOrder->id,
            'work_order_item_id' => $item->id,
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'part_id' => $part->id,
            'name' => 'فلتر زيت تويوتا الأصلي',
            'source' => 'warehouse',
            'qty' => 1,
            'unit_price' => 100.00,
            'discount' => 0,
            'status' => 'issued',
        ]);

        $invoice = $this->invoiceService->createFromWorkOrder($workOrder, $this->user);

        $this->assertInstanceOf(Invoice::class, $invoice);
        $this->assertEquals('draft', $invoice->status);
        $this->assertEquals('DRAFT-WO-000099', $invoice->invoice_number);
        $this->assertEquals($this->customer->name, $invoice->customer_name_snapshot);
        $this->assertCount(2, $invoice->lines);
        $this->assertEquals(200.00, (float) $invoice->total_excl_tax);
        $this->assertEquals(30.00, (float) $invoice->total_tax);
        $this->assertEquals(230.00, (float) $invoice->total_incl_tax);
    }

    public function test_create_from_work_order_ignores_cancelled_items_and_parts()
    {
        $workOrder = WorkOrder::factory()->create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'customer_id' => $this->customer->id,
            'code' => 'WO-000100',
            'tax_enabled_snapshot' => false,
            'total_excl_tax' => 100.00,
            'total_tax' => 0,
            'total_incl_tax' => 100.00,
        ]);

        WorkOrderItem::create([
            'work_order_id' => $workOrder->id,
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'title' => 'خدمة نشطة',
            'qty' => 1,
            'unit_price' => 100.00,
            'status' => 'completed',
        ]);

        WorkOrderItem::create([
            'work_order_id' => $workOrder->id,
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'title' => 'خدمة ملغاة',
            'qty' => 1,
            'unit_price' => 500.00,
            'status' => 'cancelled',
        ]);

        WorkOrderItemPart::create([
            'work_order_id' => $workOrder->id,
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'name' => 'قطعة ملغاة',
            'source' => 'warehouse',
            'qty' => 1,
            'unit_price' => 200.00,
            'status' => 'cancelled',
        ]);

        $invoice = $this->invoiceService->createFromWorkOrder($workOrder, $this->user);

        $this->assertCount(1, $invoice->lines);
        $this->assertEquals('خدمة نشطة', $invoice->lines->first()->description);
        $this->assertEquals(100.00, (float) $invoice->total_excl_tax);
    }

    public function test_issue_invoice_assigns_sequential_invoice_number_and_status()
    {
        $invoice = Invoice::create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'customer_id' => $this->customer->id,
            'invoice_number' => 'DRAFT-TEMP',
            'issue_date' => now(),
            'supply_date' => now(),
            'status' => 'draft',
            'payment_status' => 'unpaid',
            'total_excl_tax' => 100.00,
            'total_tax' => 15.00,
            'total_incl_tax' => 115.00,
        ]);

        $issuedInvoice = $this->invoiceService->issueInvoice($invoice);

        $this->assertEquals('valid', $issuedInvoice->status);
        $this->assertStringStartsWith('INV-'.$this->center->id.'-'.now()->year, $issuedInvoice->invoice_number);
    }

    public function test_issue_invoice_throws_exception_if_not_draft()
    {
        $invoice = Invoice::create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'customer_id' => $this->customer->id,
            'invoice_number' => 'INV-001',
            'issue_date' => now(),
            'supply_date' => now(),
            'status' => 'valid',
            'payment_status' => 'paid',
            'total_excl_tax' => 100.00,
            'total_tax' => 15.00,
            'total_incl_tax' => 115.00,
        ]);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Only draft invoices can be issued.');

        $this->invoiceService->issueInvoice($invoice);
    }

    public function test_get_proforma_data_returns_structured_data_without_persisting()
    {
        $workOrder = WorkOrder::factory()->create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'customer_id' => $this->customer->id,
            'code' => 'WO-PROFORMA-1',
            'tax_enabled_snapshot' => true,
        ]);

        WorkOrderItem::create([
            'work_order_id' => $workOrder->id,
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'title' => 'خدمة فحص',
            'qty' => 1,
            'unit_price' => 150.00,
            'status' => 'pending',
        ]);

        $data = $this->invoiceService->getProformaData($workOrder);

        $this->assertIsArray($data);
        $this->assertTrue($data['is_proforma']);
        $this->assertEquals(150.00, $data['servicesTotal']);
        $this->assertEquals(150.00, $data['grandTotal']);
        $this->assertEquals($workOrder->id, $data['workOrder']->id);
        $this->assertDatabaseMissing('invoices', ['work_order_id' => $workOrder->id]);
    }

    public function test_generate_zatca_qr_encodes_base64_tlv()
    {
        $invoice = Invoice::create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'customer_id' => $this->customer->id,
            'invoice_number' => 'INV-2026-000001',
            'issue_date' => now(),
            'supply_date' => now(),
            'status' => 'valid',
            'payment_status' => 'unpaid',
            'total_excl_tax' => 100.00,
            'total_tax' => 15.00,
            'total_incl_tax' => 115.00,
        ]);
        $invoice->setRelation('tenant', $this->tenant);

        $qrCode = $this->invoiceService->generateZatcaQr($invoice);

        $this->assertIsString($qrCode);
        $this->assertNotEmpty($qrCode);
        $decoded = base64_decode($qrCode);
        $this->assertStringContainsString('Test Repair LLC', $decoded);
    }
}
