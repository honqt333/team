<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\InvoiceType;
use App\Enums\PaymentType;
use App\Models\Center;
use App\Models\Customer;
use App\Models\InventoryBalance;
use App\Models\InventoryMove;
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
use App\Services\CreditNoteService;
use App\Services\Inventory\InventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class CreditNoteAtomicInventoryReturnTest extends TestCase
{
    use RefreshDatabase;

    private function createContext(): array
    {
        $tenant = Tenant::factory()->create();
        $center = Center::factory()->create(['tenant_id' => $tenant->id]);
        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'current_center_id' => $center->id,
        ]);
        $user->centers()->attach($center->id, ['tenant_id' => $tenant->id]);

        $warehouse = Warehouse::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'name' => 'Main Warehouse',
            'is_default' => true,
            'is_active' => true,
        ]);

        $customer = Customer::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'name' => 'Test Customer',
            'phone' => '0501234567',
        ]);

        $vehicle = Vehicle::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'customer_id' => $customer->id,
            'plate_number' => 'XYZ 9999',
            'make' => 'Toyota',
            'model' => 'Camry',
            'year' => 2024,
        ]);

        $part = Part::create([
            'tenant_id' => $tenant->id,
            'sku' => 'PART-'.uniqid(),
            'name_ar' => 'Brake Pad',
            'name_en' => 'Brake Pad',
            'default_sale_price' => 100,
        ]);

        InventoryBalance::getOrCreate($warehouse->id, $part->id)
            ->update(['qty_on_hand' => 10]);

        return compact('tenant', 'center', 'user', 'warehouse', 'customer', 'vehicle', 'part');
    }

    private function createInvoicedWorkOrder(array $ctx, array $partOverrides = []): array
    {
        $wo = WorkOrder::create([
            'tenant_id' => $ctx['tenant']->id,
            'center_id' => $ctx['center']->id,
            'customer_id' => $ctx['customer']->id,
            'vehicle_id' => $ctx['vehicle']->id,
            'code' => 'WO-'.uniqid(),
            'status' => 'completed',
            'tax_enabled_snapshot' => false,
            'tax_rate_snapshot' => 0,
        ]);

        $item = WorkOrderItem::create([
            'tenant_id' => $ctx['tenant']->id,
            'center_id' => $ctx['center']->id,
            'work_order_id' => $wo->id,
            'title' => 'Brake Service',
            'qty' => 1,
            'unit_price' => 100,
            'line_total' => 100,
            'status' => 'completed',
        ]);

        $woPart = WorkOrderItemPart::create(array_merge([
            'tenant_id' => $ctx['tenant']->id,
            'center_id' => $ctx['center']->id,
            'work_order_id' => $wo->id,
            'work_order_item_id' => $item->id,
            'part_id' => $ctx['part']->id,
            'warehouse_id' => $ctx['warehouse']->id,
            'source' => WorkOrderItemPart::SOURCE_WAREHOUSE,
            'name' => $ctx['part']->name_ar,
            'qty' => 2,
            'unit_price' => 100,
            'total' => 200,
            'cost_snapshot' => 50,
            'status' => 'issued',
        ], $partOverrides));

        $invoice = Invoice::create([
            'tenant_id' => $ctx['tenant']->id,
            'center_id' => $ctx['center']->id,
            'customer_id' => $ctx['customer']->id,
            'work_order_id' => $wo->id,
            'type' => InvoiceType::INVOICE,
            'invoice_number' => 'INV-'.uniqid(),
            'status' => 'issued',
            'payment_status' => 'paid',
            'paid_amount' => 300,
            'subtotal' => 300,
            'discount_total' => 0,
            'tax_enabled_snapshot' => false,
            'tax_rate_snapshot' => 0,
            'total_tax' => 0,
            'total' => 300,
            'issue_date' => now(),
            'supply_date' => now(),
            'issued_at' => now(),
            'created_by' => $ctx['user']->id,
        ]);

        Payment::create([
            'tenant_id' => $ctx['tenant']->id,
            'center_id' => $ctx['center']->id,
            'invoice_id' => $invoice->id,
            'payment_type' => PaymentType::PAYMENT,
            'amount' => 300,
            'method' => 'cash',
            'payment_date' => now(),
            'paid_at' => now(),
            'received_by' => $ctx['user']->id,
        ]);

        return compact('wo', 'item', 'woPart', 'invoice');
    }

    private function createDirectPartInvoice(array $ctx, bool $withSaleMove = true): array
    {
        $invoice = Invoice::create([
            'tenant_id' => $ctx['tenant']->id,
            'center_id' => $ctx['center']->id,
            'customer_id' => $ctx['customer']->id,
            'type' => InvoiceType::INVOICE,
            'invoice_number' => 'DINV-'.uniqid(),
            'status' => 'issued',
            'payment_status' => 'paid',
            'paid_amount' => 200,
            'subtotal' => 200,
            'discount_total' => 0,
            'tax_enabled_snapshot' => false,
            'tax_rate_snapshot' => 0,
            'total_tax' => 0,
            'total' => 200,
            'issue_date' => now(),
            'supply_date' => now(),
            'issued_at' => now(),
            'created_by' => $ctx['user']->id,
        ]);

        $line = $invoice->lines()->create([
            'is_part' => true,
            'part_id' => $ctx['part']->id,
            'description' => $ctx['part']->name_ar,
            'qty' => 2,
            'unit_price' => 100,
            'discount_amount' => 0,
            'is_taxable' => false,
            'tax_category_code' => 'Z',
            'tax_rate_snapshot' => 0,
            'tax_amount' => 0,
            'line_total_excl_tax' => 200,
            'line_total_incl_tax' => 200,
        ]);

        if ($withSaleMove) {
            InventoryMove::create([
                'tenant_id' => $ctx['tenant']->id,
                'center_id' => $ctx['center']->id,
                'warehouse_id' => $ctx['warehouse']->id,
                'part_id' => $ctx['part']->id,
                'move_type' => 'sale',
                'qty' => -2,
                'unit_cost' => 50,
                'total_cost' => -100,
                'reference_type' => 'invoice',
                'reference_id' => $invoice->id,
                'created_by' => $ctx['user']->id,
            ]);
        }

        Payment::create([
            'tenant_id' => $ctx['tenant']->id,
            'center_id' => $ctx['center']->id,
            'invoice_id' => $invoice->id,
            'payment_type' => PaymentType::PAYMENT,
            'amount' => 200,
            'method' => 'cash',
            'payment_date' => now(),
            'paid_at' => now(),
            'received_by' => $ctx['user']->id,
        ]);

        return compact('invoice', 'line');
    }

    /**
     * Scenario A (Work Order): return_to_stock=true with failing InventoryService::receipt() rolls back atomically.
     */
    public function test_scenario_a_work_order_credit_note_rolls_back_atomically_when_receipt_fails(): void
    {
        $ctx = $this->createContext();
        $this->actingAs($ctx['user']);
        $woData = $this->createInvoicedWorkOrder($ctx);

        $mockInventory = Mockery::mock(InventoryService::class);
        $mockInventory->shouldReceive('receipt')
            ->once()
            ->andThrow(new RuntimeException('Simulated inventory failure'));
        $this->app->instance(InventoryService::class, $mockInventory);

        $service = app(CreditNoteService::class);

        try {
            $service->createCreditNote($woData['wo'], [
                'parts' => [
                    [
                        'work_order_item_part_id' => $woData['woPart']->id,
                        'return_qty' => 2,
                        'return_to_stock' => true,
                    ],
                ],
                'record_refund' => true,
                'reason' => 'Defective part',
            ], $ctx['user']);
            $this->fail('Expected RuntimeException was not thrown.');
        } catch (RuntimeException $e) {
            $this->assertSame('Simulated inventory failure', $e->getMessage());
        }

        $this->assertSame(0, Invoice::where('type', InvoiceType::CREDIT_NOTE)->count());
        $this->assertSame(0, InventoryMove::where('reference_type', 'credit_note')->count());
        $this->assertSame(1, Payment::where('invoice_id', $woData['invoice']->id)->count());
        $balance = InventoryBalance::where('warehouse_id', $ctx['warehouse']->id)
            ->where('part_id', $ctx['part']->id)
            ->firstOrFail();
        $this->assertEquals(10.0, (float) $balance->qty_on_hand);
    }

    /**
     * Scenario A (Direct Invoice): return_to_stock=true with partial multi-line failure rolls back first line receipt too.
     */
    public function test_scenario_a_direct_invoice_credit_note_rolls_back_all_lines_when_second_receipt_fails(): void
    {
        $ctx = $this->createContext();
        $this->actingAs($ctx['user']);

        $part2 = Part::create([
            'tenant_id' => $ctx['tenant']->id,
            'sku' => 'PART2-'.uniqid(),
            'name_ar' => 'Air Filter',
            'name_en' => 'Air Filter',
            'default_sale_price' => 60,
        ]);
        InventoryBalance::getOrCreate($ctx['warehouse']->id, $part2->id)
            ->update(['qty_on_hand' => 5]);

        $direct = $this->createDirectPartInvoice($ctx);
        $line2 = $direct['invoice']->lines()->create([
            'is_part' => true,
            'part_id' => $part2->id,
            'description' => $part2->name_ar,
            'qty' => 1,
            'unit_price' => 60,
            'discount_amount' => 0,
            'is_taxable' => false,
            'tax_category_code' => 'Z',
            'tax_rate_snapshot' => 0,
            'tax_amount' => 0,
            'line_total_excl_tax' => 60,
            'line_total_incl_tax' => 60,
        ]);

        $realInventory = app(InventoryService::class);
        $partialMock = Mockery::mock(InventoryService::class)->makePartial();
        $callCount = 0;
        $partialMock->shouldReceive('receipt')
            ->twice()
            ->andReturnUsing(function (...$args) use (&$callCount, $realInventory) {
                $callCount++;

                if ($callCount === 1) {
                    return $realInventory->receipt(...$args);
                }

                throw new RuntimeException('Second line receipt failed');
            });
        $this->app->instance(InventoryService::class, $partialMock);

        $service = app(CreditNoteService::class);

        try {
            $service->createCreditNoteForInvoice($direct['invoice'], [
                'parts' => [
                    ['id' => $direct['line']->id, 'return_qty' => 1, 'return_to_stock' => true],
                    ['id' => $line2->id, 'return_qty' => 1, 'return_to_stock' => true],
                ],
                'record_refund' => true,
            ], $ctx['user']);
            $this->fail('Expected RuntimeException was not thrown.');
        } catch (RuntimeException $e) {
            $this->assertSame('Second line receipt failed', $e->getMessage());
        }

        // Line 1's inventory balance & move MUST also be rolled back atomically
        $this->assertSame(0, Invoice::where('type', InvoiceType::CREDIT_NOTE)->count());
        $this->assertSame(0, InventoryMove::where('reference_type', 'credit_note')->count());
        $bal1 = InventoryBalance::where('part_id', $ctx['part']->id)->firstOrFail();
        $bal2 = InventoryBalance::where('part_id', $part2->id)->firstOrFail();
        $this->assertEquals(10.0, (float) $bal1->qty_on_hand);
        $this->assertEquals(5.0, (float) $bal2->qty_on_hand);
    }

    /**
     * Scenario B (Work Order): return_to_stock=true with missing, inactive, soft-deleted, or cross-center warehouse fails with ValidationException.
     */
    public function test_scenario_b_work_order_warehouse_part_rejects_missing_or_invalid_warehouse(): void
    {
        $ctx = $this->createContext();
        $this->actingAs($ctx['user']);

        // Case 1: warehouse_id is null
        $woNull = $this->createInvoicedWorkOrder($ctx, ['warehouse_id' => null]);
        $service = app(CreditNoteService::class);

        try {
            $service->createCreditNote($woNull['wo'], [
                'parts' => [
                    ['work_order_item_part_id' => $woNull['woPart']->id, 'return_qty' => 1, 'return_to_stock' => true],
                ],
            ], $ctx['user']);
            $this->fail('Expected ValidationException for missing warehouse_id.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('warehouse_id', $e->errors());
        }
        $this->assertSame(0, Invoice::where('type', InvoiceType::CREDIT_NOTE)->count());

        // Case 2: warehouse belongs to another center
        $otherCenter = Center::factory()->create(['tenant_id' => $ctx['tenant']->id]);
        $otherCenterWarehouse = Warehouse::create([
            'tenant_id' => $ctx['tenant']->id,
            'center_id' => $otherCenter->id,
            'name' => 'Other Branch Warehouse',
            'is_active' => true,
        ]);
        $woOtherCenter = $this->createInvoicedWorkOrder($ctx, ['warehouse_id' => $otherCenterWarehouse->id]);

        try {
            $service->createCreditNote($woOtherCenter['wo'], [
                'parts' => [
                    ['work_order_item_part_id' => $woOtherCenter['woPart']->id, 'return_qty' => 1, 'return_to_stock' => true],
                ],
            ], $ctx['user']);
            $this->fail('Expected ValidationException for cross-center warehouse.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('warehouse_id', $e->errors());
        }

        // Case 3: warehouse is inactive
        $inactiveWarehouse = Warehouse::create([
            'tenant_id' => $ctx['tenant']->id,
            'center_id' => $ctx['center']->id,
            'name' => 'Inactive Warehouse',
            'is_active' => false,
        ]);
        $woInactive = $this->createInvoicedWorkOrder($ctx, ['warehouse_id' => $inactiveWarehouse->id]);

        try {
            $service->createCreditNote($woInactive['wo'], [
                'parts' => [
                    ['work_order_item_part_id' => $woInactive['woPart']->id, 'return_qty' => 1, 'return_to_stock' => true],
                ],
            ], $ctx['user']);
            $this->fail('Expected ValidationException for inactive warehouse.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('warehouse_id', $e->errors());
        }

        // Case 4: warehouse is soft-deleted
        $deletedWarehouse = Warehouse::create([
            'tenant_id' => $ctx['tenant']->id,
            'center_id' => $ctx['center']->id,
            'name' => 'Deleted Warehouse',
            'is_active' => true,
        ]);
        $deletedWarehouse->delete();
        $woDeleted = $this->createInvoicedWorkOrder($ctx, ['warehouse_id' => $deletedWarehouse->id]);

        try {
            $service->createCreditNote($woDeleted['wo'], [
                'parts' => [
                    ['work_order_item_part_id' => $woDeleted['woPart']->id, 'return_qty' => 1, 'return_to_stock' => true],
                ],
            ], $ctx['user']);
            $this->fail('Expected ValidationException for soft-deleted warehouse.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('warehouse_id', $e->errors());
        }

        $this->assertSame(0, Invoice::where('type', InvoiceType::CREDIT_NOTE)->count());
    }

    /**
     * Scenario B (Direct Invoice): return_to_stock=true when no active non-deleted warehouse exists in center fails with ValidationException.
     */
    public function test_scenario_b_direct_invoice_rejects_when_no_valid_warehouse_exists(): void
    {
        $ctx = $this->createContext();
        $this->actingAs($ctx['user']);
        $direct = $this->createDirectPartInvoice($ctx, false);

        // Soft-delete all warehouses in this center
        Warehouse::where('center_id', $ctx['center']->id)->delete();

        $service = app(CreditNoteService::class);

        try {
            $service->createCreditNoteForInvoice($direct['invoice'], [
                'parts' => [
                    ['id' => $direct['line']->id, 'return_qty' => 1, 'return_to_stock' => true],
                ],
            ], $ctx['user']);
            $this->fail('Expected ValidationException when no active non-deleted warehouse exists.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('warehouse_id', $e->errors());
        }

        $this->assertSame(0, Invoice::where('type', InvoiceType::CREDIT_NOTE)->count());
        $this->assertSame(0, InventoryMove::where('reference_type', 'credit_note')->count());
    }

    /**
     * Scenario C: return_to_stock=false succeeds financially without inventory movement.
     */
    public function test_scenario_c_return_to_stock_false_is_financial_only(): void
    {
        $ctx = $this->createContext();
        $this->actingAs($ctx['user']);
        $woData = $this->createInvoicedWorkOrder($ctx);
        $direct = $this->createDirectPartInvoice($ctx);

        $service = app(CreditNoteService::class);

        $woCn = $service->createCreditNote($woData['wo'], [
            'parts' => [
                ['work_order_item_part_id' => $woData['woPart']->id, 'return_qty' => 1, 'return_to_stock' => false],
            ],
        ], $ctx['user']);

        $directCn = $service->createCreditNoteForInvoice($direct['invoice'], [
            'parts' => [
                ['id' => $direct['line']->id, 'return_qty' => 1, 'return_to_stock' => false],
            ],
        ], $ctx['user']);

        $this->assertSame(InvoiceType::CREDIT_NOTE, $woCn->type);
        $this->assertSame(InvoiceType::CREDIT_NOTE, $directCn->type);
        $this->assertSame(0, InventoryMove::where('reference_type', 'credit_note')->count());
        $balance = InventoryBalance::where('warehouse_id', $ctx['warehouse']->id)
            ->where('part_id', $ctx['part']->id)
            ->firstOrFail();
        $this->assertEquals(10.0, (float) $balance->qty_on_hand);
    }

    /**
     * Scenario D: Non-warehouse parts (external/customer/no part_id) succeed financially even if return_to_stock=true.
     */
    public function test_scenario_d_non_warehouse_parts_skip_inventory_even_when_return_to_stock_true(): void
    {
        $ctx = $this->createContext();
        $this->actingAs($ctx['user']);

        $woExternal = $this->createInvoicedWorkOrder($ctx, [
            'source' => WorkOrderItemPart::SOURCE_EXTERNAL,
            'warehouse_id' => null,
        ]);
        $woCustomer = $this->createInvoicedWorkOrder($ctx, [
            'source' => WorkOrderItemPart::SOURCE_CUSTOMER,
            'part_id' => null,
            'warehouse_id' => null,
        ]);

        $service = app(CreditNoteService::class);

        $cnExt = $service->createCreditNote($woExternal['wo'], [
            'parts' => [
                ['work_order_item_part_id' => $woExternal['woPart']->id, 'return_qty' => 1, 'return_to_stock' => true],
            ],
        ], $ctx['user']);

        $cnCust = $service->createCreditNote($woCustomer['wo'], [
            'parts' => [
                ['work_order_item_part_id' => $woCustomer['woPart']->id, 'return_qty' => 1, 'return_to_stock' => true],
            ],
        ], $ctx['user']);

        $this->assertSame(InvoiceType::CREDIT_NOTE, $cnExt->type);
        $this->assertSame(InvoiceType::CREDIT_NOTE, $cnCust->type);
        $this->assertSame(0, InventoryMove::where('reference_type', 'credit_note')->count());
        $balance = InventoryBalance::where('warehouse_id', $ctx['warehouse']->id)
            ->where('part_id', $ctx['part']->id)
            ->firstOrFail();
        $this->assertEquals(10.0, (float) $balance->qty_on_hand);
    }

    /**
     * Scenario E: Valid warehouse part return succeeds atomically and increments stock.
     */
    public function test_scenario_e_valid_warehouse_return_creates_credit_note_and_restocks_atomically(): void
    {
        $ctx = $this->createContext();
        $this->actingAs($ctx['user']);
        $woData = $this->createInvoicedWorkOrder($ctx);
        $direct = $this->createDirectPartInvoice($ctx);

        $service = app(CreditNoteService::class);

        $woCn = $service->createCreditNote($woData['wo'], [
            'parts' => [
                ['work_order_item_part_id' => $woData['woPart']->id, 'return_qty' => 2, 'return_to_stock' => true],
            ],
            'record_refund' => true,
        ], $ctx['user']);

        $directCn = $service->createCreditNoteForInvoice($direct['invoice'], [
            'parts' => [
                ['id' => $direct['line']->id, 'return_qty' => 1, 'return_to_stock' => true],
            ],
            'record_refund' => true,
        ], $ctx['user']);

        $this->assertSame(InvoiceType::CREDIT_NOTE, $woCn->type);
        $this->assertSame(InvoiceType::CREDIT_NOTE, $directCn->type);
        $this->assertSame(2, InventoryMove::where('reference_type', 'credit_note')->where('move_type', InventoryMove::TYPE_RECEIPT)->count());

        $balance = InventoryBalance::where('warehouse_id', $ctx['warehouse']->id)
            ->where('part_id', $ctx['part']->id)
            ->firstOrFail();
        // 10 + 2 (WO) + 1 (Direct) = 13
        $this->assertEquals(13.0, (float) $balance->qty_on_hand);
    }

    /**
     * Scenario F: Direct Invoice non-part line succeeds financially without inventory movement.
     */
    public function test_scenario_f_direct_invoice_service_line_skips_inventory(): void
    {
        $ctx = $this->createContext();
        $this->actingAs($ctx['user']);

        $invoice = Invoice::create([
            'tenant_id' => $ctx['tenant']->id,
            'center_id' => $ctx['center']->id,
            'customer_id' => $ctx['customer']->id,
            'type' => InvoiceType::INVOICE,
            'invoice_number' => 'DINV-SVC-'.uniqid(),
            'status' => 'issued',
            'payment_status' => 'paid',
            'paid_amount' => 150,
            'subtotal' => 150,
            'discount_total' => 0,
            'tax_enabled_snapshot' => false,
            'tax_rate_snapshot' => 0,
            'total_tax' => 0,
            'total' => 150,
            'issue_date' => now(),
            'supply_date' => now(),
            'issued_at' => now(),
            'created_by' => $ctx['user']->id,
        ]);

        $svcLine = $invoice->lines()->create([
            'is_part' => false,
            'part_id' => null,
            'description' => 'Diagnostic Service',
            'qty' => 1,
            'unit_price' => 150,
            'discount_amount' => 0,
            'is_taxable' => false,
            'tax_category_code' => 'Z',
            'tax_rate_snapshot' => 0,
            'tax_amount' => 0,
            'line_total_excl_tax' => 150,
            'line_total_incl_tax' => 150,
        ]);

        $service = app(CreditNoteService::class);
        $cn = $service->createCreditNoteForInvoice($invoice, [
            'services' => [
                ['id' => $svcLine->id, 'return_amount' => 150],
            ],
        ], $ctx['user']);

        $this->assertSame(InvoiceType::CREDIT_NOTE, $cn->type);
        $this->assertSame(0, InventoryMove::where('reference_type', 'credit_note')->count());
    }
}
