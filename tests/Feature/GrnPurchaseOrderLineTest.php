<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Center;
use App\Models\GoodsReceivedNote;
use App\Models\Part;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Supplier;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Warehouse;
use App\Support\Permissions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Tests for the link between a goods received note and the purchase
 * order line it receives (audit finding #7).
 *
 * The GRN form used to accept any `purchase_order_item_id` that
 * existed and any `part_id` that existed. The service loaded the
 * line by its own id — without checking that it belonged to the
 * purchase order in the URL — and stored the request's part_id
 * separately.
 *
 * The effect was that the stock that arrived and the ordered
 * quantity that was credited could describe two different parts, on
 * two different orders: posting the GRN received stock for the
 * request's part while incrementing `qty_received` on somebody
 * else's line.
 */
class GrnPurchaseOrderLineTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    private Center $center;

    private User $user;

    private Supplier $supplier;

    private Warehouse $warehouse;

    private Part $part;

    private Part $otherPart;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        Permission::findOrCreate(Permissions::POS_VIEW, 'web');
        Permission::findOrCreate(Permissions::INVENTORY_MOVES_CREATE, 'web');

        $this->tenant = Tenant::factory()->create();
        $this->center = Center::factory()->create(['tenant_id' => $this->tenant->id]);

        $this->user = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'current_center_id' => $this->center->id,
        ]);
        $this->user->centers()->attach($this->center->id, ['tenant_id' => $this->tenant->id]);

        app(PermissionRegistrar::class)->setPermissionsTeamId($this->tenant->id);
        $this->user->givePermissionTo([Permissions::POS_VIEW, Permissions::INVENTORY_MOVES_CREATE]);
        $this->user->refresh();

        $this->supplier = Supplier::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Supplier',
            'name_ar' => 'مورد',
        ]);

        $this->warehouse = Warehouse::factory()->create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'is_default' => true,
        ]);

        $this->part = Part::factory()->create([
            'tenant_id' => $this->tenant->id,
            'sku' => 'GRN-PART-1',
        ]);
        $this->otherPart = Part::factory()->create([
            'tenant_id' => $this->tenant->id,
            'sku' => 'GRN-PART-2',
        ]);
    }

    private function purchaseOrder(string $status = PurchaseOrder::STATUS_SENT): PurchaseOrder
    {
        return PurchaseOrder::create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'supplier_id' => $this->supplier->id,
            'warehouse_id' => $this->warehouse->id,
            'code' => 'PO-'.uniqid(),
            'status' => $status,
            'order_date' => now()->toDateString(),
            'total' => 100,
        ]);
    }

    private function orderLine(PurchaseOrder $po, Part $part, float $qty = 10.0): PurchaseOrderItem
    {
        return PurchaseOrderItem::create([
            'purchase_order_id' => $po->id,
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'part_id' => $part->id,
            'qty_ordered' => $qty,
            'qty_received' => 0,
            'unit_cost' => 10,
            'tax_rate' => 15,
            'line_subtotal' => $qty * 10,
            'line_tax' => $qty * 10 * 0.15,
            'line_total' => $qty * 10 * 1.15,
        ]);
    }

    private function payload(PurchaseOrderItem $line, ?Part $part = null, float $qty = 5.0): array
    {
        return [
            'warehouse_id' => $this->warehouse->id,
            'received_date' => now()->toDateString(),
            'items' => [
                [
                    'purchase_order_item_id' => $line->id,
                    'part_id' => ($part ?? $this->part)->id,
                    'qty_received' => $qty,
                    'unit_cost' => 10,
                ],
            ],
        ];
    }

    public function test_a_grn_can_be_created_against_a_line_of_its_own_order(): void
    {
        $po = $this->purchaseOrder();
        $line = $this->orderLine($po, $this->part);

        $response = $this->actingAs($this->user)
            ->post('/app/purchasing/orders/'.$po->id.'/receive', $this->payload($line));

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        $grn = GoodsReceivedNote::where('purchase_order_id', $po->id)->firstOrFail();

        $this->assertCount(1, $grn->items);
        $this->assertSame($line->id, $grn->items->first()->purchase_order_item_id);
        $this->assertSame($this->part->id, $grn->items->first()->part_id);
    }

    public function test_a_line_of_another_purchase_order_is_refused(): void
    {
        $po = $this->purchaseOrder();
        $otherPo = $this->purchaseOrder();

        $line = $this->orderLine($otherPo, $this->part);

        $response = $this->actingAs($this->user)
            ->post('/app/purchasing/orders/'.$po->id.'/receive', $this->payload($line));

        $response->assertSessionHasErrors();
        $this->assertDatabaseCount('goods_received_notes', 0);
        $this->assertDatabaseCount('grn_items', 0);
    }

    public function test_a_line_of_another_tenant_is_refused(): void
    {
        $po = $this->purchaseOrder();

        $otherTenant = Tenant::factory()->create();
        $otherCenter = Center::factory()->create(['tenant_id' => $otherTenant->id]);
        $otherPo = PurchaseOrder::create([
            'tenant_id' => $otherTenant->id,
            'center_id' => $otherCenter->id,
            'supplier_id' => $this->supplier->id,
            'warehouse_id' => $this->warehouse->id,
            'code' => 'PO-FOREIGN-'.uniqid(),
            'status' => PurchaseOrder::STATUS_SENT,
            'order_date' => now()->toDateString(),
            'total' => 100,
        ]);
        $foreignLine = $this->orderLine($otherPo, $this->part);

        $response = $this->actingAs($this->user)
            ->post('/app/purchasing/orders/'.$po->id.'/receive', $this->payload($foreignLine));

        $response->assertSessionHasErrors();
        $this->assertDatabaseCount('goods_received_notes', 0);
    }

    public function test_a_part_that_does_not_match_the_line_is_refused(): void
    {
        $po = $this->purchaseOrder();
        $line = $this->orderLine($po, $this->part);

        // The request asks for a different part than the one ordered.
        $response = $this->actingAs($this->user)
            ->post('/app/purchasing/orders/'.$po->id.'/receive', $this->payload($line, $this->otherPart));

        $response->assertSessionHasErrors();
        $this->assertDatabaseCount('goods_received_notes', 0);
        $this->assertDatabaseCount('grn_items', 0);
    }

    public function test_a_warehouse_of_another_tenant_is_refused(): void
    {
        $po = $this->purchaseOrder();
        $line = $this->orderLine($po, $this->part);

        $otherTenant = Tenant::factory()->create();
        $otherCenter = Center::factory()->create(['tenant_id' => $otherTenant->id]);
        $foreignWarehouse = Warehouse::factory()->create([
            'tenant_id' => $otherTenant->id,
            'center_id' => $otherCenter->id,
        ]);

        $payload = $this->payload($line);
        $payload['warehouse_id'] = $foreignWarehouse->id;

        $response = $this->actingAs($this->user)
            ->post('/app/purchasing/orders/'.$po->id.'/receive', $payload);

        $response->assertSessionHasErrors('warehouse_id');
        $this->assertDatabaseCount('goods_received_notes', 0);
    }

    public function test_receiving_more_than_the_pending_quantity_is_still_refused(): void
    {
        $po = $this->purchaseOrder();
        $line = $this->orderLine($po, $this->part, 10.0);

        $response = $this->actingAs($this->user)
            ->post('/app/purchasing/orders/'.$po->id.'/receive', $this->payload($line, null, 50.0));

        $response->assertSessionHasErrors();
        $this->assertDatabaseCount('goods_received_notes', 0);
    }
}
