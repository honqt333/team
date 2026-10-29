<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\InvoiceType;
use App\Models\Center;
use App\Models\Customer;
use App\Models\InventoryBalance;
use App\Models\InventoryUnit;
use App\Models\Invoice;
use App\Models\Part;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Warehouse;
use App\Support\Permissions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class DirectSalesReturnTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    private Center $center;

    private User $user;

    private Customer $customer;

    private Warehouse $warehouse;

    private Part $part;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::factory()->create();
        $this->center = Center::factory()->create(['tenant_id' => $this->tenant->id]);
        $this->warehouse = Warehouse::factory()->create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'is_default' => true,
        ]);

        $this->app->make(PermissionRegistrar::class)->forgetCachedPermissions();
        Permission::findOrCreate(Permissions::INVOICES_VIEW, 'web');
        Permission::findOrCreate(Permissions::INVOICES_CREATE, 'web');
        Permission::findOrCreate('inventory.view', 'web');

        $this->user = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'current_center_id' => $this->center->id,
        ]);
        $this->user->centers()->attach($this->center->id, ['tenant_id' => $this->tenant->id]);

        $this->app->make(PermissionRegistrar::class)->setPermissionsTeamId($this->tenant->id);
        $this->user->givePermissionTo([Permissions::INVOICES_VIEW, Permissions::INVOICES_CREATE, 'inventory.view']);

        $this->customer = Customer::factory()->create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
        ]);

        $unit = InventoryUnit::create([
            'tenant_id' => $this->tenant->id,
            'name_ar' => 'حبة',
            'name_en' => 'Piece',
            'is_active' => true,
        ]);

        $this->part = Part::create([
            'tenant_id' => $this->tenant->id,
            'sku' => 'FILTER-OIL-01',
            'name_ar' => 'فلتر زيت أصلي',
            'name_en' => 'Oil Filter OEM',
            'unit_id' => $unit->id,
            'is_active' => true,
            'default_sale_price' => 50.00,
            'cost_price' => 25.00,
        ]);

        // Starting stock: 10
        InventoryBalance::create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'warehouse_id' => $this->warehouse->id,
            'part_id' => $this->part->id,
            'qty_on_hand' => 10.00,
            'wac_cost' => 25.00,
        ]);
    }

    public function test_can_get_summary_and_create_return_credit_note_for_direct_sales_invoice(): void
    {
        $this->actingAs($this->user);

        // 1. Create a direct sales invoice with 5 pieces sold (stock goes 10 -> 5)
        $saleResponse = $this->post(route('app.purchasing.sales-invoices.store'), [
            'customer_id' => $this->customer->id,
            'issue_date' => now()->format('Y-m-d'),
            'items' => [
                [
                    'part_id' => $this->part->id,
                    'warehouse_id' => $this->warehouse->id,
                    'source' => 'warehouse',
                    'name' => $this->part->name_ar,
                    'qty' => 5,
                    'unit_price' => 50,
                    'discount' => 0,
                    'tax_amount' => 37.5,
                    'total' => 250,
                    'grand_total' => 287.5,
                ],
            ],
            'payments' => [
                [
                    'amount' => 287.5,
                    'payment_method' => 'cash',
                    'payment_date' => now()->format('Y-m-d'),
                ],
            ],
        ]);

        $saleResponse->assertSessionHasNoErrors();

        $invoice = Invoice::where('customer_id', $this->customer->id)->latest('id')->first();
        $this->assertNotNull($invoice);
        $this->assertNull($invoice->work_order_id);
        $this->assertEquals(287.5, (float) $invoice->total_incl_tax);
        $this->assertEquals('paid', $invoice->payment_status);

        // Check stock deducted to 5
        $balance = InventoryBalance::where('part_id', $this->part->id)->where('warehouse_id', $this->warehouse->id)->first();
        $this->assertEquals(5.00, (float) $balance->qty_on_hand);

        // 2. Fetch credit note summary for this direct sales invoice
        $summaryResponse = $this->getJson(route('app.invoices.credit-note.summary', $invoice->id));
        $summaryResponse->assertOk();
        $summaryResponse->assertJson([
            'is_possible' => true,
            'parts' => [
                [
                    'name' => 'فلتر زيت أصلي',
                    'original_qty' => 5,
                    'refundable_qty' => 5,
                ],
            ],
        ]);

        $partSummary = $summaryResponse->json('parts.0');

        // 3. Return 2 pieces with restock to warehouse and immediate cash refund
        $storeResponse = $this->postJson(route('app.invoices.credit-note.store', $invoice->id), [
            'parts' => [
                [
                    'id' => $partSummary['id'],
                    'return_qty' => 2,
                    'return_to_stock' => true,
                ],
            ],
            'return_reason' => 'إرجاع فائض من العميل',
            'record_refund' => true,
            'refund_payment_method' => 'cash',
        ]);

        $storeResponse->assertOk();
        $storeResponse->assertJson(['success' => true]);

        // 4. Verify Credit Note was created
        $creditNote = Invoice::where('original_invoice_id', $invoice->id)
            ->where('type', InvoiceType::CREDIT_NOTE)
            ->first();

        $this->assertNotNull($creditNote);
        $this->assertNull($creditNote->work_order_id);
        $this->assertEquals($this->customer->id, $creditNote->customer_id);
        // 2 pieces * 50 = 100 excl tax + 15% tax = 115 incl tax
        $this->assertEquals(115.00, (float) $creditNote->total_incl_tax);
        $this->assertEquals('paid', $creditNote->payment_status);

        // Verify credit note line links to original invoice line
        $creditLine = $creditNote->lines()->first();
        $this->assertNotNull($creditLine);
        $this->assertEquals($partSummary['id'], $creditLine->original_invoice_line_id);
        $this->assertEquals(2.0, (float) $creditLine->qty);

        // 5. Verify Stock was restocked: 5 + 2 = 7
        $balance->refresh();
        $this->assertEquals(7.00, (float) $balance->qty_on_hand);

        // 6. Verify subsequent summary: remaining refundable qty is now 3
        $secondSummary = $this->getJson(route('app.invoices.credit-note.summary', $invoice->id));
        $secondSummary->assertOk();
        $secondSummary->assertJson([
            'is_possible' => true,
            'parts' => [
                [
                    'returned_qty' => 2,
                    'refundable_qty' => 3,
                ],
            ],
        ]);
    }

    public function test_direct_sales_return_with_debt_offset_and_deferred_credit_note(): void
    {
        $this->actingAs($this->user);

        // 1. Create unpaid direct sales invoice for 4 pieces (200 + 15% tax = 230 total)
        $this->post(route('app.purchasing.sales-invoices.store'), [
            'customer_id' => $this->customer->id,
            'issue_date' => now()->format('Y-m-d'),
            'items' => [
                [
                    'part_id' => $this->part->id,
                    'warehouse_id' => $this->warehouse->id,
                    'source' => 'warehouse',
                    'name' => $this->part->name_ar,
                    'qty' => 4,
                    'unit_price' => 50,
                    'discount' => 0,
                    'tax_amount' => 30,
                    'total' => 200,
                    'grand_total' => 230,
                ],
            ],
            // Unpaid (no payments)
        ]);

        $invoice = Invoice::where('customer_id', $this->customer->id)->latest('id')->first();
        $this->assertEquals('unpaid', $invoice->payment_status);
        $this->assertEquals(230.00, (float) $invoice->balance);

        $summary = $this->getJson(route('app.invoices.credit-note.summary', $invoice->id))->json();
        $partId = $summary['parts'][0]['id'];

        // 2. Return 2 pieces (value 115) with deferred mode (no cash refund)
        // Since original invoice has 230 unpaid debt, 115 is automatically adjusted against debt!
        $response = $this->postJson(route('app.invoices.credit-note.store', $invoice->id), [
            'parts' => [
                [
                    'id' => $partId,
                    'return_qty' => 2,
                    'return_to_stock' => true,
                ],
            ],
            'return_reason' => 'تسوية وإرجاع',
            'record_refund' => false,
        ]);

        $response->assertOk();
        $this->assertEquals(115.00, (float) $response->json('debt_offset'));
        $this->assertEquals(0.00, (float) $response->json('net_refund_due'));

        $creditNote = Invoice::where('original_invoice_id', $invoice->id)->latest('id')->first();
        $this->assertNotNull($creditNote);
        $this->assertEquals(115.00, (float) $creditNote->total_incl_tax);
        // Debt adjusted completely offsets the 115, so credit note is paid
        $this->assertEquals('paid', $creditNote->payment_status);

        // Next summary shows remaining balance is reduced by the credit note
        $updatedSummary = $this->getJson(route('app.invoices.credit-note.summary', $invoice->id))->json();
        $this->assertEquals(115.00, (float) $updatedSummary['original_invoice']['remaining']);
        $this->assertEquals(2, $updatedSummary['parts'][0]['remaining_refundable_qty']);
    }
}
