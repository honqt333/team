<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\InvoiceType;
use App\Enums\PaymentType;
use App\Models\Center;
use App\Models\Customer;
use App\Models\InventoryBalance;
use App\Models\Invoice;
use App\Models\Part;
use App\Models\Payment;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\Warehouse;
use App\Models\WorkOrder;
use App\Models\WorkOrderItem;
use App\Models\WorkOrderItemPart;
use App\Support\Permissions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class WorkOrderCreditNoteTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        Permission::findOrCreate('crm.work_orders.view', 'web');
        Permission::findOrCreate('crm.work_orders.create', 'web');
        Permission::findOrCreate('crm.work_orders.update', 'web');
        Permission::findOrCreate('crm.invoices.view', 'web');
        Permission::findOrCreate('crm.invoices.create', 'web');
    }

    private function createTenantContext(): array
    {
        $tenant = Tenant::factory()->create();
        $center = Center::factory()->create(['tenant_id' => $tenant->id]);
        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'current_center_id' => $center->id,
        ]);
        $user->centers()->attach($center->id, ['tenant_id' => $tenant->id]);

        app(PermissionRegistrar::class)->setPermissionsTeamId($tenant->id);
        $user->givePermissionTo([
            'crm.work_orders.view',
            'crm.work_orders.update',
            'crm.invoices.view',
            'crm.invoices.create',
        ]);

        $warehouse = Warehouse::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'name' => 'Main Warehouse',
            'is_default' => true,
        ]);

        $customer = Customer::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'name' => 'Emaar Int.',
            'phone' => '0501234567',
        ]);

        $vehicle = Vehicle::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'customer_id' => $customer->id,
            'plate_number' => 'ABC 1234',
            'make' => 'Toyota',
            'model' => 'Land Cruiser',
            'year' => 2024,
        ]);

        return compact('tenant', 'center', 'user', 'warehouse', 'customer', 'vehicle');
    }

    public function test_summary_returns_refundable_services_and_parts_for_completed_invoiced_wo(): void
    {
        $ctx = $this->createTenantContext();

        $wo = WorkOrder::create([
            'tenant_id' => $ctx['tenant']->id,
            'center_id' => $ctx['center']->id,
            'customer_id' => $ctx['customer']->id,
            'vehicle_id' => $ctx['vehicle']->id,
            'code' => 'WO-TEST-001',
            'status' => 'done',
            'tax_enabled_snapshot' => true,
            'pricing_mode_snapshot' => 'exclusive',
            'tax_rate_snapshot' => 15.00,
        ]);

        $svc = WorkOrderItem::create([
            'tenant_id' => $ctx['tenant']->id,
            'center_id' => $ctx['center']->id,
            'work_order_id' => $wo->id,
            'title' => 'Mechanical Labor',
            'status' => 'completed',
            'qty' => 1,
            'unit_price' => 125.00,
            'line_total' => 125.00,
            'total' => 143.75,
            'tax_amount' => 18.75,
            'line_total_excl_tax' => 125.00,
            'line_total_incl_tax' => 143.75,
        ]);

        $part = Part::create([
            'tenant_id' => $ctx['tenant']->id,
            'sku' => 'SKU-BP-001',
            'name_ar' => 'Brake Pad',
            'name_en' => 'Brake Pad',
            'default_sale_price' => 50.00,
        ]);

        $woPart = WorkOrderItemPart::create([
            'tenant_id' => $ctx['tenant']->id,
            'center_id' => $ctx['center']->id,
            'work_order_id' => $wo->id,
            'work_order_item_id' => $svc->id,
            'part_id' => $part->id,
            'name' => 'Brake Pad',
            'part_number' => 'BP-001',
            'qty' => 2,
            'unit_price' => 50.00,
            'total' => 115.00,
        ]);

        $invoice = Invoice::create([
            'tenant_id' => $ctx['tenant']->id,
            'center_id' => $ctx['center']->id,
            'customer_id' => $ctx['customer']->id,
            'work_order_id' => $wo->id,
            'invoice_number' => 'INV-2026-0001',
            'type' => 'invoice',
            'status' => 'valid',
            'issue_date' => now(),
            'supply_date' => now(),
            'total_excl_tax' => 225.00,
            'total_tax' => 33.75,
            'total_incl_tax' => 258.75,
            'total_paid' => 258.75,
            'payment_status' => 'paid',
        ]);

        $response = $this->actingAs($ctx['user'])
            ->getJson(route('app.work-orders.credit-note.summary', $wo->id));

        $response->assertOk();
        $response->assertJson([
            'is_possible' => true,
            'invoice' => [
                'id' => $invoice->id,
                'invoice_number' => 'INV-2026-0001',
            ],
        ]);

        $data = $response->json();
        $this->assertCount(1, $data['services']);
        $this->assertEquals(125.00, $data['services'][0]['remaining_refundable_subtotal']);
        $this->assertCount(1, $data['parts']);
        $this->assertEquals(2, $data['parts'][0]['remaining_refundable_qty']);
    }

    public function test_cannot_create_credit_note_if_work_order_not_done(): void
    {
        $ctx = $this->createTenantContext();

        $wo = WorkOrder::create([
            'tenant_id' => $ctx['tenant']->id,
            'center_id' => $ctx['center']->id,
            'customer_id' => $ctx['customer']->id,
            'vehicle_id' => $ctx['vehicle']->id,
            'code' => 'WO-OPEN-001',
            'status' => 'in_progress',
        ]);

        $response = $this->actingAs($ctx['user'])
            ->getJson(route('app.work-orders.credit-note.summary', $wo->id));

        $response->assertOk();
        $this->assertFalse($response->json('is_possible'));

        $postResponse = $this->actingAs($ctx['user'])
            ->postJson(route('app.work-orders.credit-note.store', $wo->id), [
                'services' => [],
                'parts' => [],
            ]);

        $postResponse->assertStatus(422);
    }

    public function test_can_create_credit_note_for_service_with_automatic_debt_offset(): void
    {
        $ctx = $this->createTenantContext();

        $wo = WorkOrder::create([
            'tenant_id' => $ctx['tenant']->id,
            'center_id' => $ctx['center']->id,
            'customer_id' => $ctx['customer']->id,
            'vehicle_id' => $ctx['vehicle']->id,
            'code' => 'WO-DONE-002',
            'status' => 'done',
            'tax_enabled_snapshot' => true,
            'pricing_mode_snapshot' => 'exclusive',
            'tax_rate_snapshot' => 15.00,
        ]);

        $svc = WorkOrderItem::create([
            'tenant_id' => $ctx['tenant']->id,
            'center_id' => $ctx['center']->id,
            'work_order_id' => $wo->id,
            'title' => 'Diagnostic Scan',
            'status' => 'completed',
            'qty' => 1,
            'unit_price' => 100.00,
            'line_total' => 100.00,
            'total' => 115.00,
            'tax_amount' => 15.00,
            'line_total_excl_tax' => 100.00,
            'line_total_incl_tax' => 115.00,
        ]);

        // Original invoice is unpaid: total 115.00, paid 0.00, balance 115.00
        $invoice = Invoice::create([
            'tenant_id' => $ctx['tenant']->id,
            'center_id' => $ctx['center']->id,
            'customer_id' => $ctx['customer']->id,
            'work_order_id' => $wo->id,
            'invoice_number' => 'INV-2026-0002',
            'type' => 'invoice',
            'status' => 'valid',
            'issue_date' => now(),
            'supply_date' => now(),
            'total_excl_tax' => 100.00,
            'total_tax' => 15.00,
            'total_incl_tax' => 115.00,
            'total_paid' => 0.00,
            'payment_status' => 'unpaid',
        ]);

        // Return $50 of the service (tax 15% = $7.50 -> Total credit note = $57.50)
        // Debt offset should be full $57.50, net refund to customer should be 0.00
        $payload = [
            'services' => [
                ['id' => $svc->id, 'return_amount' => 50.00],
            ],
            'parts' => [],
            'return_reason' => 'Customer requested discount on diagnostic',
            'record_refund' => false,
        ];

        $res = $this->actingAs($ctx['user'])
            ->postJson(route('app.work-orders.credit-note.store', $wo->id), $payload);

        $res->assertOk();
        $creditNoteId = $res->json('credit_note_id');
        $this->assertNotNull($creditNoteId);

        $creditNote = Invoice::find($creditNoteId);
        $this->assertNotNull($creditNote);
        $this->assertEquals('credit_note', $creditNote->type instanceof InvoiceType ? $creditNote->type->value : (string) $creditNote->type);
        $this->assertEquals($invoice->id, $creditNote->original_invoice_id);
        $this->assertEquals(50.00, (float) $creditNote->total_excl_tax);
        $this->assertEquals(7.50, (float) $creditNote->total_tax);
        $this->assertEquals(57.50, (float) $creditNote->total_incl_tax);
        $this->assertEquals('Customer requested discount on diagnostic', $creditNote->return_reason);
        $this->assertNotNull($creditNote->zatca_qr_tlv);

        // Check line items
        $this->assertCount(1, $creditNote->lines);
        $this->assertEquals('مرتجع خدمة: Diagnostic Scan', $creditNote->lines[0]->description);
        $this->assertEquals(50.00, (float) $creditNote->lines[0]->unit_price);
        $this->assertEquals(57.50, (float) $creditNote->lines[0]->line_total_incl_tax);

        // Check offset against remaining debt
        $this->assertEquals(57.50, (float) $res->json('debt_offset'));
        $this->assertEquals(0.00, (float) $res->json('net_refund_due'));
    }

    public function test_can_create_credit_note_for_part_with_restock_and_payment_refund(): void
    {
        $ctx = $this->createTenantContext();

        $part = Part::create([
            'tenant_id' => $ctx['tenant']->id,
            'sku' => 'SKU-OF-100',
            'name_ar' => 'Oil Filter',
            'name_en' => 'Oil Filter',
            'default_sale_price' => 40.00,
        ]);

        // Seed initial warehouse inventory
        InventoryBalance::getOrCreate($ctx['warehouse']->id, $part->id)
            ->update(['qty_on_hand' => 10]);

        $wo = WorkOrder::create([
            'tenant_id' => $ctx['tenant']->id,
            'center_id' => $ctx['center']->id,
            'customer_id' => $ctx['customer']->id,
            'vehicle_id' => $ctx['vehicle']->id,
            'code' => 'WO-DONE-003',
            'status' => 'done',
            'tax_enabled_snapshot' => true,
            'pricing_mode_snapshot' => 'exclusive',
            'tax_rate_snapshot' => 15.00,
        ]);

        $woPart = WorkOrderItemPart::create([
            'tenant_id' => $ctx['tenant']->id,
            'center_id' => $ctx['center']->id,
            'work_order_id' => $wo->id,
            'part_id' => $part->id,
            'warehouse_id' => $ctx['warehouse']->id,
            'name' => 'Oil Filter',
            'part_number' => 'OF-100',
            'qty' => 2,
            'unit_price' => 40.00,
            'total' => 92.00,
        ]);

        // Paid in full
        $invoice = Invoice::create([
            'tenant_id' => $ctx['tenant']->id,
            'center_id' => $ctx['center']->id,
            'customer_id' => $ctx['customer']->id,
            'work_order_id' => $wo->id,
            'invoice_number' => 'INV-2026-0003',
            'type' => 'invoice',
            'status' => 'valid',
            'issue_date' => now(),
            'supply_date' => now(),
            'total_excl_tax' => 80.00,
            'total_tax' => 12.00,
            'total_incl_tax' => 92.00,
            'total_paid' => 92.00,
            'payment_status' => 'paid',
        ]);

        // Return 1 oil filter, return to stock = true, execute cash refund
        $payload = [
            'services' => [],
            'parts' => [
                [
                    'id' => $woPart->id,
                    'return_qty' => 1,
                    'return_to_stock' => true,
                ],
            ],
            'return_reason' => 'Extra part not needed',
            'record_refund' => true,
            'refund_payment_method' => 'cash',
            'refund_reference' => 'CASH-REF-01',
        ];

        $res = $this->actingAs($ctx['user'])
            ->postJson(route('app.work-orders.credit-note.store', $wo->id), $payload);

        $res->assertOk();

        // 1 part @ $40 + 15% VAT = $46.00
        $this->assertEquals(46.00, (float) $res->json('credit_note_total'));
        $this->assertEquals(0.00, (float) $res->json('debt_offset'));
        $this->assertEquals(46.00, (float) $res->json('net_refund_due'));

        // Check inventory restocked: from 10 to 11
        $currentStock = (float) InventoryBalance::getOrCreate($ctx['warehouse']->id, $part->id)->qty_on_hand;
        $this->assertEquals(11.0, $currentStock);

        // Check payment record created for refund
        $refundPayment = Payment::where('invoice_id', $res->json('credit_note_id'))
            ->where('type', 'refund')
            ->first();

        $this->assertNotNull($refundPayment);
        $this->assertEquals(46.00, (float) $refundPayment->amount);
        $this->assertEquals('cash', $refundPayment->payment_method);
    }

    public function test_cannot_exceed_remaining_refundable_quantities(): void
    {
        $ctx = $this->createTenantContext();

        $wo = WorkOrder::create([
            'tenant_id' => $ctx['tenant']->id,
            'center_id' => $ctx['center']->id,
            'customer_id' => $ctx['customer']->id,
            'vehicle_id' => $ctx['vehicle']->id,
            'code' => 'WO-DONE-004',
            'status' => 'done',
            'tax_enabled_snapshot' => true,
            'pricing_mode_snapshot' => 'exclusive',
            'tax_rate_snapshot' => 15.00,
        ]);

        $svc = WorkOrderItem::create([
            'tenant_id' => $ctx['tenant']->id,
            'center_id' => $ctx['center']->id,
            'work_order_id' => $wo->id,
            'title' => 'Oil Change Service',
            'status' => 'completed',
            'qty' => 1,
            'unit_price' => 50.00,
            'line_total' => 50.00,
            'total' => 57.50,
            'tax_amount' => 7.50,
            'line_total_excl_tax' => 50.00,
            'line_total_incl_tax' => 57.50,
        ]);

        Invoice::create([
            'tenant_id' => $ctx['tenant']->id,
            'center_id' => $ctx['center']->id,
            'customer_id' => $ctx['customer']->id,
            'work_order_id' => $wo->id,
            'invoice_number' => 'INV-2026-0004',
            'type' => 'invoice',
            'status' => 'valid',
            'issue_date' => now(),
            'supply_date' => now(),
            'total_excl_tax' => 50.00,
            'total_tax' => 7.50,
            'total_incl_tax' => 57.50,
            'total_paid' => 57.50,
            'payment_status' => 'paid',
        ]);

        // Attempt to return $100 when original cost is $50 (refundable is 57.50)
        $payload = [
            'services' => [
                ['id' => $svc->id, 'return_amount' => 100.00],
            ],
            'parts' => [],
        ];

        $res = $this->actingAs($ctx['user'])
            ->postJson(route('app.work-orders.credit-note.store', $wo->id), $payload);

        $res->assertStatus(422);
    }

    public function test_tenant_isolation_prevents_unauthorized_access(): void
    {
        $ctxA = $this->createTenantContext();
        $ctxB = $this->createTenantContext();

        $woA = WorkOrder::create([
            'tenant_id' => $ctxA['tenant']->id,
            'center_id' => $ctxA['center']->id,
            'customer_id' => $ctxA['customer']->id,
            'vehicle_id' => $ctxA['vehicle']->id,
            'code' => 'WO-A-001',
            'status' => 'done',
        ]);

        // User B tries to view summary or create credit note on WO of Tenant A
        $res = $this->actingAs($ctxB['user'])
            ->getJson(route('app.work-orders.credit-note.summary', $woA->id));

        $res->assertNotFound();

        $postRes = $this->actingAs($ctxB['user'])
            ->postJson(route('app.work-orders.credit-note.store', $woA->id), [
                'services' => [],
                'parts' => [],
            ]);

        $postRes->assertNotFound();
    }

    public function test_credit_note_on_tax_exempt_invoice_calculates_zero_tax(): void
    {
        $ctx = $this->createTenantContext();

        $wo = WorkOrder::create([
            'tenant_id' => $ctx['tenant']->id,
            'center_id' => $ctx['center']->id,
            'customer_id' => $ctx['customer']->id,
            'vehicle_id' => $ctx['vehicle']->id,
            'code' => 'WO-NO-TAX-01',
            'status' => 'done',
            'tax_enabled_snapshot' => false,
            'pricing_mode_snapshot' => 'exclusive',
            'tax_rate_snapshot' => 0.00,
        ]);

        $svc = WorkOrderItem::create([
            'tenant_id' => $ctx['tenant']->id,
            'center_id' => $ctx['center']->id,
            'work_order_id' => $wo->id,
            'title' => 'Mechanic Inspection',
            'status' => 'completed',
            'qty' => 1,
            'unit_price' => 125.00,
            'line_total' => 125.00,
            'total' => 125.00,
            'tax_amount' => 0.00,
            'line_total_excl_tax' => 125.00,
            'line_total_incl_tax' => 125.00,
        ]);

        $invoice = Invoice::create([
            'tenant_id' => $ctx['tenant']->id,
            'center_id' => $ctx['center']->id,
            'customer_id' => $ctx['customer']->id,
            'work_order_id' => $wo->id,
            'invoice_number' => 'INV-NOTAX-0001',
            'type' => 'invoice',
            'status' => 'valid',
            'issue_date' => now(),
            'supply_date' => now(),
            'tax_enabled_snapshot' => false,
            'total_excl_tax' => 125.00,
            'total_tax' => 0.00,
            'total_incl_tax' => 125.00,
            'total_paid' => 125.00,
            'payment_status' => 'paid',
        ]);

        $payload = [
            'services' => [
                ['id' => $svc->id, 'return_amount' => 125.00],
            ],
            'parts' => [],
            'return_reason' => 'Customer cancellation - no tax invoice',
            'record_refund' => true,
            'refund_payment_method' => 'cash',
        ];

        $res = $this->actingAs($ctx['user'])
            ->postJson(route('app.work-orders.credit-note.store', $wo->id), $payload);

        $res->assertOk();
        $creditNoteId = $res->json('credit_note_id');
        $creditNote = Invoice::find($creditNoteId);

        $this->assertNotNull($creditNote);
        $this->assertEquals(0.00, (float) $creditNote->total_tax);
        $this->assertEquals(125.00, (float) $creditNote->total_excl_tax);
        $this->assertEquals(125.00, (float) $creditNote->total_incl_tax);
        $this->assertEquals(125.00, (float) $res->json('net_refund_due'));
    }

    public function test_can_create_multiple_consecutive_credit_notes_for_same_work_order(): void
    {
        $ctx = $this->createTenantContext();

        $wo = WorkOrder::create([
            'tenant_id' => $ctx['tenant']->id,
            'center_id' => $ctx['center']->id,
            'customer_id' => $ctx['customer']->id,
            'vehicle_id' => $ctx['vehicle']->id,
            'code' => 'WO-MULTI-CN',
            'status' => 'done',
            'tax_enabled_snapshot' => true,
            'pricing_mode_snapshot' => 'exclusive',
            'tax_rate_snapshot' => 15.00,
        ]);

        $svc = WorkOrderItem::create([
            'tenant_id' => $ctx['tenant']->id,
            'center_id' => $ctx['center']->id,
            'work_order_id' => $wo->id,
            'title' => 'Major Service',
            'status' => 'completed',
            'qty' => 1,
            'unit_price' => 200.00,
            'line_total' => 200.00,
            'total' => 230.00,
            'tax_amount' => 30.00,
            'line_total_excl_tax' => 200.00,
            'line_total_incl_tax' => 230.00,
        ]);

        $part = Part::create([
            'tenant_id' => $ctx['tenant']->id,
            'sku' => 'SKU-SPARK-01',
            'name_ar' => 'Spark Plug',
            'name_en' => 'Spark Plug',
            'default_sale_price' => 50.00,
        ]);

        $woPart = WorkOrderItemPart::create([
            'tenant_id' => $ctx['tenant']->id,
            'center_id' => $ctx['center']->id,
            'work_order_id' => $wo->id,
            'work_order_item_id' => $svc->id,
            'part_id' => $part->id,
            'name' => 'Spark Plug',
            'qty' => 4,
            'unit_price' => 50.00,
            'total' => 230.00,
        ]);

        $invoice = Invoice::create([
            'tenant_id' => $ctx['tenant']->id,
            'center_id' => $ctx['center']->id,
            'customer_id' => $ctx['customer']->id,
            'work_order_id' => $wo->id,
            'invoice_number' => 'INV-MULTI-001',
            'type' => 'invoice',
            'status' => 'valid',
            'issue_date' => now(),
            'supply_date' => now(),
            'tax_enabled_snapshot' => true,
            'total_excl_tax' => 400.00,
            'total_tax' => 60.00,
            'total_incl_tax' => 460.00,
            'total_paid' => 460.00,
            'payment_status' => 'paid',
        ]);

        // Credit Note 1: Return 100.00 of the service
        $cn1Res = $this->actingAs($ctx['user'])
            ->postJson(route('app.work-orders.credit-note.store', $wo->id), [
                'services' => [['id' => $svc->id, 'return_amount' => 100.00]],
                'parts' => [],
                'record_refund' => true,
                'refund_payment_method' => 'cash',
            ]);
        $cn1Res->assertOk();

        // Check summary after CN 1: remaining refundable service should be 100.00 (200 - 100)
        $summary1 = $this->actingAs($ctx['user'])
            ->getJson(route('app.work-orders.credit-note.summary', $wo->id))->json();
        $this->assertTrue($summary1['is_possible']);
        $this->assertEquals(100.00, $summary1['services'][0]['remaining_refundable_subtotal']);
        $this->assertEquals(4, $summary1['parts'][0]['remaining_refundable_qty']);

        // Credit Note 2: Return 2 spark plugs
        $cn2Res = $this->actingAs($ctx['user'])
            ->postJson(route('app.work-orders.credit-note.store', $wo->id), [
                'services' => [],
                'parts' => [['id' => $woPart->id, 'return_qty' => 2, 'return_to_stock' => false]],
                'record_refund' => true,
                'refund_payment_method' => 'cash',
            ]);
        $cn2Res->assertOk();

        // Check summary after CN 2: remaining parts should be 2, remaining service should be 100.00
        $summary2 = $this->actingAs($ctx['user'])
            ->getJson(route('app.work-orders.credit-note.summary', $wo->id))->json();
        $this->assertTrue($summary2['is_possible']);
        $this->assertEquals(100.00, $summary2['services'][0]['remaining_refundable_subtotal']);
        $this->assertEquals(2, $summary2['parts'][0]['remaining_refundable_qty']);

        // Credit Note 3: Return remaining service (100.00) and remaining 2 parts
        $cn3Res = $this->actingAs($ctx['user'])
            ->postJson(route('app.work-orders.credit-note.store', $wo->id), [
                'services' => [['id' => $svc->id, 'return_amount' => 100.00]],
                'parts' => [['id' => $woPart->id, 'return_qty' => 2, 'return_to_stock' => false]],
                'record_refund' => true,
                'refund_payment_method' => 'cash',
            ]);
        $cn3Res->assertOk();

        // Now all items returned, is_possible should be false
        $summary3 = $this->actingAs($ctx['user'])
            ->getJson(route('app.work-orders.credit-note.summary', $wo->id))->json();
        $this->assertFalse($summary3['is_possible']);
    }

    public function test_deferred_refund_creates_unpaid_credit_note_without_cash_payment(): void
    {
        $ctx = $this->createTenantContext();

        $wo = WorkOrder::create([
            'tenant_id' => $ctx['tenant']->id,
            'center_id' => $ctx['center']->id,
            'customer_id' => $ctx['customer']->id,
            'vehicle_id' => $ctx['vehicle']->id,
            'code' => 'WO-DEFERRED-01',
            'status' => 'done',
            'tax_enabled_snapshot' => true,
            'pricing_mode_snapshot' => 'exclusive',
            'tax_rate_snapshot' => 15.00,
        ]);

        $svc = WorkOrderItem::create([
            'tenant_id' => $ctx['tenant']->id,
            'center_id' => $ctx['center']->id,
            'work_order_id' => $wo->id,
            'title' => 'AC Flush Service',
            'status' => 'completed',
            'qty' => 1,
            'unit_price' => 100.00,
            'line_total' => 100.00,
            'total' => 115.00,
            'tax_amount' => 15.00,
            'line_total_excl_tax' => 100.00,
            'line_total_incl_tax' => 115.00,
        ]);

        $invoice = Invoice::create([
            'tenant_id' => $ctx['tenant']->id,
            'center_id' => $ctx['center']->id,
            'customer_id' => $ctx['customer']->id,
            'work_order_id' => $wo->id,
            'invoice_number' => 'INV-DEF-0001',
            'type' => 'invoice',
            'status' => 'valid',
            'issue_date' => now(),
            'supply_date' => now(),
            'tax_enabled_snapshot' => true,
            'total_excl_tax' => 100.00,
            'total_tax' => 15.00,
            'total_incl_tax' => 115.00,
            'total_paid' => 115.00,
            'payment_status' => 'paid',
        ]);

        // Issue credit note WITHOUT immediate refund (deferred credit / debit note style)
        $dueDate = now()->addDays(15)->toDateString();
        $payload = [
            'services' => [
                ['id' => $svc->id, 'return_amount' => 100.00],
            ],
            'parts' => [],
            'return_reason' => 'Deferred credit note to customer account',
            'record_refund' => false,
            'payment_due_date' => $dueDate,
        ];

        $res = $this->actingAs($ctx['user'])
            ->postJson(route('app.work-orders.credit-note.store', $wo->id), $payload);

        $res->assertOk();
        $creditNoteId = $res->json('credit_note_id');
        $creditNote = Invoice::find($creditNoteId);

        $this->assertNotNull($creditNote);
        $this->assertEquals('unpaid', $creditNote->payment_status);
        $this->assertEquals(0.00, (float) $creditNote->total_paid);
        $this->assertEquals(115.00, (float) $creditNote->balance);
        $this->assertEquals($dueDate, $creditNote->due_date?->toDateString());

        // CRITICAL: Ensure NO payment was recorded in payments table
        $paymentsCount = Payment::where('invoice_id', $creditNoteId)->count();
        $this->assertEquals(0, $paymentsCount, 'No payment record should be created when refund is deferred');
    }

    public function test_can_get_summary_and_create_credit_note_directly_via_invoice_route(): void
    {
        $ctx = $this->createTenantContext();

        $wo = WorkOrder::create([
            'tenant_id' => $ctx['tenant']->id,
            'center_id' => $ctx['center']->id,
            'customer_id' => $ctx['customer']->id,
            'vehicle_id' => $ctx['vehicle']->id,
            'code' => 'WO-INV-CN-01',
            'status' => 'done',
            'tax_enabled_snapshot' => true,
            'pricing_mode_snapshot' => 'exclusive',
            'tax_rate_snapshot' => 15.00,
        ]);

        $svc = WorkOrderItem::create([
            'tenant_id' => $ctx['tenant']->id,
            'center_id' => $ctx['center']->id,
            'work_order_id' => $wo->id,
            'title' => 'Brake Fluid Flush',
            'status' => 'completed',
            'qty' => 1,
            'unit_price' => 80.00,
            'line_total' => 80.00,
            'total' => 92.00,
            'tax_amount' => 12.00,
            'line_total_excl_tax' => 80.00,
            'line_total_incl_tax' => 92.00,
        ]);

        $invoice = Invoice::create([
            'tenant_id' => $ctx['tenant']->id,
            'center_id' => $ctx['center']->id,
            'customer_id' => $ctx['customer']->id,
            'work_order_id' => $wo->id,
            'invoice_number' => 'INV-DIRECT-001',
            'type' => 'invoice',
            'status' => 'valid',
            'issue_date' => now(),
            'supply_date' => now(),
            'tax_enabled_snapshot' => true,
            'total_excl_tax' => 80.00,
            'total_tax' => 12.00,
            'total_incl_tax' => 92.00,
            'total_paid' => 92.00,
            'payment_status' => 'paid',
        ]);

        // 1. Check summary via invoice route
        $summaryRes = $this->actingAs($ctx['user'])
            ->getJson(route('app.invoices.credit-note.summary', $invoice->id));

        $summaryRes->assertOk();
        $this->assertTrue($summaryRes->json('is_possible'));
        $this->assertEquals(80.00, (float) $summaryRes->json('services.0.remaining_refundable_subtotal'));

        // 2. Create credit note directly via invoice route
        $storeRes = $this->actingAs($ctx['user'])
            ->postJson(route('app.invoices.credit-note.store', $invoice->id), [
                'services' => [['id' => $svc->id, 'return_amount' => 80.00]],
                'parts' => [],
                'record_refund' => true,
                'refund_payment_method' => 'card',
            ]);

        $storeRes->assertOk();
        $this->assertEquals(92.00, (float) $storeRes->json('credit_note_total'));
        $this->assertEquals(92.00, (float) $storeRes->json('net_refund_due'));

        $cn = Invoice::find($storeRes->json('credit_note_id'));
        $this->assertNotNull($cn);
        $this->assertEquals($invoice->id, $cn->original_invoice_id);
    }

    public function test_can_record_refund_payment_against_unpaid_credit_note(): void
    {
        $ctx = $this->createTenantContext();
        $ctx['user']->givePermissionTo(Permissions::PURCHASE_PAYMENTS_MANAGE);

        $invoice = Invoice::create([
            'tenant_id' => $ctx['tenant']->id,
            'center_id' => $ctx['center']->id,
            'customer_id' => $ctx['customer']->id,
            'invoice_number' => 'INV-PAY-0001',
            'type' => 'invoice',
            'status' => 'valid',
            'issue_date' => now(),
            'supply_date' => now(),
            'tax_enabled_snapshot' => true,
            'total_excl_tax' => 100.00,
            'total_tax' => 15.00,
            'total_incl_tax' => 115.00,
            'total_paid' => 115.00,
            'payment_status' => 'paid',
        ]);

        $creditNote = Invoice::create([
            'tenant_id' => $ctx['tenant']->id,
            'center_id' => $ctx['center']->id,
            'customer_id' => $ctx['customer']->id,
            'original_invoice_id' => $invoice->id,
            'invoice_number' => 'CN-PAY-0001',
            'type' => 'credit_note',
            'status' => 'valid',
            'issue_date' => now(),
            'supply_date' => now(),
            'tax_enabled_snapshot' => true,
            'total_excl_tax' => 100.00,
            'total_tax' => 15.00,
            'total_incl_tax' => 115.00,
            'total_paid' => 0.00,
            'payment_status' => 'unpaid',
        ]);

        $this->assertEquals(115.00, (float) $creditNote->balance);

        // 1. Partial refund of 50.00
        $res1 = $this->actingAs($ctx['user'])
            ->post(route('app.invoices.payments.store', $creditNote->id), [
                'amount' => 50.00,
                'payment_method' => 'cash',
                'type' => 'payment', // intentionally sent as 'payment', backend should coerce to 'refund'
                'notes' => 'Partial cash refund to customer',
            ]);

        $res1->assertSessionHasNoErrors();

        $creditNote->refresh();
        $this->assertEquals(50.00, (float) $creditNote->total_paid);
        $this->assertEquals('partial', $creditNote->payment_status);
        $this->assertEquals(65.00, (float) $creditNote->balance);

        $payment1 = Payment::where('invoice_id', $creditNote->id)->first();
        $this->assertNotNull($payment1);
        $this->assertEquals('refund', $payment1->type instanceof PaymentType ? $payment1->type->value : (string) $payment1->type);
        $this->assertEquals(50.00, (float) $payment1->amount);

        // 2. Final settlement for remaining 65.00
        $res2 = $this->actingAs($ctx['user'])
            ->post(route('app.invoices.payments.store', $creditNote->id), [
                'amount' => 65.00,
                'payment_method' => 'transfer',
                'type' => 'refund',
                'notes' => 'Final bank transfer payout',
            ]);

        $res2->assertSessionHasNoErrors();

        $creditNote->refresh();
        $this->assertEquals(115.00, (float) $creditNote->total_paid);
        $this->assertEquals('paid', $creditNote->payment_status);
        $this->assertEquals(0.00, (float) $creditNote->balance);
        $this->assertTrue($creditNote->is_paid);

        // 3. Trying to record additional payment should be rejected because balance is 0 and status is paid
        $res3 = $this->actingAs($ctx['user'])
            ->post(route('app.invoices.payments.store', $creditNote->id), [
                'amount' => 10.00,
                'payment_method' => 'cash',
            ]);

        $res3->assertForbidden();
    }

    public function test_cannot_refund_more_than_net_refund_due_during_credit_note_creation(): void
    {
        $ctx = $this->createTenantContext();

        $wo = WorkOrder::create([
            'tenant_id' => $ctx['tenant']->id,
            'center_id' => $ctx['center']->id,
            'customer_id' => $ctx['customer']->id,
            'vehicle_id' => $ctx['vehicle']->id,
            'code' => 'WO-REFUND-CAP-01',
            'status' => 'done',
            'tax_enabled_snapshot' => true,
            'pricing_mode_snapshot' => 'exclusive',
            'tax_rate_snapshot' => 15.00,
        ]);

        $svc = WorkOrderItem::create([
            'tenant_id' => $ctx['tenant']->id,
            'center_id' => $ctx['center']->id,
            'work_order_id' => $wo->id,
            'title' => 'Brake Pad Replacement Service',
            'status' => 'completed',
            'qty' => 1,
            'unit_price' => 100.00,
            'line_total' => 100.00,
            'total' => 115.00,
            'tax_amount' => 15.00,
            'line_total_excl_tax' => 100.00,
            'line_total_incl_tax' => 115.00,
        ]);

        $invoice = Invoice::create([
            'tenant_id' => $ctx['tenant']->id,
            'center_id' => $ctx['center']->id,
            'customer_id' => $ctx['customer']->id,
            'work_order_id' => $wo->id,
            'invoice_number' => 'INV-CAP-0001',
            'type' => 'invoice',
            'status' => 'valid',
            'issue_date' => now(),
            'supply_date' => now(),
            'total_excl_tax' => 100.00,
            'total_tax' => 15.00,
            'total_incl_tax' => 115.00,
            'total_paid' => 115.00,
            'payment_status' => 'paid',
            'tax_enabled_snapshot' => true,
            'pricing_mode_snapshot' => 'exclusive',
            'tax_rate_snapshot' => 15.00,
        ]);

        // Trying to refund 250.00 when net refund due is 115.00
        $res = $this->actingAs($ctx['user'])
            ->postJson(route('app.work-orders.credit-note.store', $wo->id), [
                'services' => [
                    ['id' => $svc->id, 'return_amount' => 100.00],
                ],
                'parts' => [],
                'refund_payment' => [
                    'amount' => 250.00,
                    'payment_method' => 'cash',
                ],
            ]);

        $res->assertUnprocessable();
        $res->assertJsonFragment([
            'message' => __('invoices.refund_exceeds_net_due'),
        ]);
    }
}
