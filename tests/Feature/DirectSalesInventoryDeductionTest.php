<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Center;
use App\Models\Customer;
use App\Models\InventoryBalance;
use App\Models\InventoryMove;
use App\Models\InventoryUnit;
use App\Models\Invoice;
use App\Models\Part;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class DirectSalesInventoryDeductionTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Tenant $tenant;

    protected Center $center;

    protected Warehouse $warehouse;

    protected Part $part;

    protected Customer $customer;

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

        $this->user = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'current_center_id' => $this->center->id,
        ]);
        $this->user->centers()->attach($this->center->id, ['tenant_id' => $this->tenant->id]);

        // Permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();
        Permission::findOrCreate('invoices.create', 'web');
        Permission::findOrCreate('inventory.view', 'web');
        app()[PermissionRegistrar::class]->setPermissionsTeamId($this->tenant->id);

        $this->user->givePermissionTo('invoices.create');
        $this->user->givePermissionTo('inventory.view');
        $this->user->refresh();

        $unit = InventoryUnit::create([
            'tenant_id' => $this->tenant->id,
            'name_ar' => 'حبة',
            'name_en' => 'Piece',
            'is_active' => true,
        ]);

        $this->part = Part::create([
            'tenant_id' => $this->tenant->id,
            'sku' => 'BRAKE-PAD-01',
            'name_ar' => 'فحمات فرامل',
            'name_en' => 'Brake Pads',
            'unit_id' => $unit->id,
            'is_active' => true,
            'default_sale_price' => 150.00,
        ]);

        // Starting stock: 10
        InventoryBalance::create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'warehouse_id' => $this->warehouse->id,
            'part_id' => $this->part->id,
            'qty_on_hand' => 10.00,
            'wac_cost' => 80.00,
        ]);

        $this->customer = Customer::factory()->create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
        ]);
    }

    /** @test */
    public function direct_sale_deducts_inventory_and_creates_sale_move()
    {
        $this->actingAs($this->user);

        $response = $this->post(route('app.purchasing.sales-invoices.store'), [
            'customer_id' => $this->customer->id,
            'issue_date' => now()->format('Y-m-d'),
            'items' => [
                [
                    'part_id' => $this->part->id,
                    'warehouse_id' => $this->warehouse->id,
                    'source' => 'warehouse',
                    'name' => $this->part->name_ar,
                    'qty' => 3,
                    'unit_price' => 150,
                    'discount' => 0,
                    'tax_amount' => 67.5,
                    'total' => 450,
                    'grand_total' => 517.5,
                ],
            ],
            'payments' => [
                [
                    'amount' => 517.5,
                    'payment_method' => 'cash',
                    'payment_date' => now()->format('Y-m-d'),
                ],
            ],
        ]);

        $response->assertSessionHasNoErrors();

        // 1. Check stock is deducted from 10 to 7
        $balance = InventoryBalance::query()
            ->withoutGlobalScopes()
            ->where('warehouse_id', $this->warehouse->id)
            ->where('part_id', $this->part->id)
            ->first();

        $this->assertEquals(7.00, (float) $balance->qty_on_hand);

        // 2. Check inventory move is recorded
        $move = InventoryMove::query()
            ->where('warehouse_id', $this->warehouse->id)
            ->where('part_id', $this->part->id)
            ->where('move_type', InventoryMove::TYPE_SALE)
            ->first();

        $this->assertNotNull($move);
        $this->assertEquals(-3.00, (float) $move->qty);
        $this->assertEquals(7.00, (float) $move->balance_after);
        $this->assertEquals(Invoice::class, $move->reference_type);

        // 3. Check invoice status
        $invoice = Invoice::where('customer_id', $this->customer->id)->first();
        $this->assertNotNull($invoice);
        $this->assertEquals('paid', $invoice->payment_status);
        $this->assertEquals($invoice->id, $move->reference_id);
    }

    /** @test */
    public function direct_sale_fails_if_stock_is_insufficient_and_negative_not_allowed()
    {
        $this->actingAs($this->user);

        // Attempt to sell 15 when only 10 is on hand
        $response = $this->post(route('app.purchasing.sales-invoices.store'), [
            'customer_id' => $this->customer->id,
            'issue_date' => now()->format('Y-m-d'),
            'items' => [
                [
                    'part_id' => $this->part->id,
                    'warehouse_id' => $this->warehouse->id,
                    'source' => 'warehouse',
                    'name' => $this->part->name_ar,
                    'qty' => 15,
                    'unit_price' => 150,
                    'discount' => 0,
                    'tax_amount' => 337.5,
                    'total' => 2250,
                    'grand_total' => 2587.5,
                ],
            ],
        ]);

        $response->assertSessionHasErrors(['items']);

        // Verify stock is still 10
        $balance = InventoryBalance::query()
            ->withoutGlobalScopes()
            ->where('warehouse_id', $this->warehouse->id)
            ->where('part_id', $this->part->id)
            ->first();

        $this->assertEquals(10.00, (float) $balance->qty_on_hand);
        $this->assertEquals(0, Invoice::count());
    }

    public function test_direct_sale_creates_payment_with_reference_and_correct_method(): void
    {
        $this->actingAs($this->user);

        $response = $this->post(route('app.purchasing.sales-invoices.store'), [
            'customer_id' => $this->customer->id,
            'issue_date' => now()->format('Y-m-d'),
            'items' => [
                [
                    'part_id' => $this->part->id,
                    'warehouse_id' => $this->warehouse->id,
                    'source' => 'warehouse',
                    'name' => $this->part->name_ar,
                    'qty' => 1,
                    'unit_price' => 150,
                    'discount' => 0,
                    'tax_amount' => 22.5,
                    'total' => 150,
                    'grand_total' => 172.5,
                ],
            ],
            'payments' => [
                [
                    'amount' => 172.5,
                    'payment_method' => 'mada',
                    'payment_date' => now()->format('Y-m-d'),
                    'reference' => 'REC-998877',
                    'notes' => 'الدفع عبر مدى',
                ],
            ],
        ]);

        $response->assertSessionHasNoErrors();

        $invoice = Invoice::where('customer_id', $this->customer->id)->latest('id')->first();
        $this->assertNotNull($invoice);
        $this->assertEquals('paid', $invoice->payment_status);
        $this->assertEquals(172.5, (float) $invoice->total_paid);

        $payment = $invoice->payments()->first();
        $this->assertNotNull($payment);
        $this->assertEquals('mada', $payment->payment_method);
        $this->assertEquals('REC-998877', $payment->reference);
        $this->assertEquals('الدفع عبر مدى', $payment->notes);
        $this->assertEquals(172.5, (float) $payment->amount);
    }
}
