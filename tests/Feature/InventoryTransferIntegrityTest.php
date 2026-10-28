<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Center;
use App\Models\InventoryBalance;
use App\Models\InventoryTransfer;
use App\Models\InventoryTransferItem;
use App\Models\Part;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\Inventory\InventoryService;
use App\Support\Permissions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Tests for the integrity of an inventory transfer (audit findings #38
 * and #39).
 *
 * #38 — removeItem() authorized the transfer in the URL and then
 * deleted an InventoryTransferItem by its own id, without checking
 * that the item belonged to that transfer. A draft transfer could
 * therefore delete an item of a transfer that had already been sent,
 * which cuts the settlement trail between the issue move and the
 * receipt move.
 *
 * #39 — the receive endpoint accepted any received_qtys.* value >= 0.
 * The service posted it as a full receipt move and marked the transfer
 * received, so sending 2 and receiving 200 created stock that never
 * left the source warehouse.
 */
class InventoryTransferIntegrityTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    private Center $center;

    private User $user;

    private Warehouse $fromWarehouse;

    private Warehouse $toWarehouse;

    private Part $part;

    private InventoryService $service;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        Permission::findOrCreate(Permissions::INVENTORY_MOVES_CREATE, 'web');
        Permission::findOrCreate(Permissions::INVENTORY_MOVES_VIEW, 'web');

        $this->tenant = Tenant::factory()->create();
        $this->center = Center::factory()->create(['tenant_id' => $this->tenant->id]);

        $this->user = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'current_center_id' => $this->center->id,
        ]);
        $this->user->centers()->attach($this->center->id, ['tenant_id' => $this->tenant->id]);

        app(PermissionRegistrar::class)->setPermissionsTeamId($this->tenant->id);
        $this->user->givePermissionTo([
            Permissions::INVENTORY_MOVES_CREATE,
            Permissions::INVENTORY_MOVES_VIEW,
        ]);
        $this->user->refresh();

        $this->fromWarehouse = Warehouse::factory()->create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'is_default' => true,
        ]);
        $this->toWarehouse = Warehouse::factory()->create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'is_default' => false,
        ]);
        $this->part = Part::factory()->create([
            'tenant_id' => $this->tenant->id,
            'sku' => 'TRF-001',
        ]);

        $this->service = app(InventoryService::class);
    }

    private function makeTransfer(string $status = InventoryTransfer::STATUS_DRAFT, float $qty = 5.0): InventoryTransfer
    {
        return InventoryTransfer::create([
            'tenant_id' => $this->tenant->id,
            'from_warehouse_id' => $this->fromWarehouse->id,
            'to_warehouse_id' => $this->toWarehouse->id,
            'code' => 'TRF-'.uniqid(),
            'status' => $status,
            'created_by' => $this->user->id,
        ]);
    }

    private function addItem(InventoryTransfer $transfer, float $qty = 5.0): InventoryTransferItem
    {
        return InventoryTransferItem::create([
            'inventory_transfer_id' => $transfer->id,
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'part_id' => $this->part->id,
            'qty_requested' => $qty,
        ]);
    }

    /**
     * #38 — an item of another transfer cannot be deleted through a
     * draft transfer.
     */
    public function test_an_item_of_another_transfer_cannot_be_deleted(): void
    {
        $draftTransfer = $this->makeTransfer();
        $sentTransfer = $this->makeTransfer(InventoryTransfer::STATUS_SENT);

        $draftItem = $this->addItem($draftTransfer);
        $sentItem = $this->addItem($sentTransfer);

        $response = $this->actingAs($this->user)
            ->delete('/app/inventory/transfers/'.$draftTransfer->id.'/items/'.$sentItem->id);

        // 404: the item is simply not part of that transfer.
        $response->assertNotFound();

        $this->assertDatabaseHas('inventory_transfer_items', ['id' => $sentItem->id]);
        $this->assertDatabaseHas('inventory_transfer_items', ['id' => $draftItem->id]);
    }

    /**
     * #38 — the item of the transfer in the URL is still removable.
     */
    public function test_the_item_of_the_transfer_in_the_url_can_be_deleted(): void
    {
        $transfer = $this->makeTransfer();
        $item = $this->addItem($transfer);

        $response = $this->actingAs($this->user)
            ->delete('/app/inventory/transfers/'.$transfer->id.'/items/'.$item->id);

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();

        $this->assertDatabaseMissing('inventory_transfer_items', ['id' => $item->id]);
    }

    /**
     * #39 — receiving more than the quantity sent is refused, and
     * nothing moves.
     */
    public function test_receiving_more_than_the_quantity_sent_is_refused(): void
    {
        $transfer = $this->makeTransfer();
        $item = $this->addItem($transfer, 2.0);

        // Put stock in the source warehouse so the transfer can be sent.
        $this->service->receipt(
            warehouseId: $this->fromWarehouse->id,
            partId: $this->part->id,
            qty: 10,
            unitCost: 10.0,
            userId: $this->user->id
        );

        $this->service->sendTransfer($transfer, $this->user->id);
        $transfer->refresh();

        try {
            $this->service->receiveTransfer($transfer, [$item->id => 200.0], $this->user->id);
            $this->fail('Receiving more than was sent must be refused.');
        } catch (ValidationException $e) {
            $this->assertNotEmpty($e->errors());
        }

        // The destination balance must not have moved.
        $destinationBalance = InventoryBalance::where('warehouse_id', $this->toWarehouse->id)
            ->where('part_id', $this->part->id)
            ->first();

        $this->assertNull(
            $destinationBalance,
            'No stock may be created in the destination for an over-receipt.'
        );

        $transfer->refresh();
        $this->assertSame(InventoryTransfer::STATUS_SENT, $transfer->status);

        $item->refresh();
        $this->assertEquals(0, (float) $item->qty_received);
    }

    /**
     * #39 — receiving exactly the quantity sent works.
     */
    public function test_receiving_the_full_sent_quantity_works(): void
    {
        $transfer = $this->makeTransfer();
        $item = $this->addItem($transfer, 2.0);

        $this->service->receipt(
            warehouseId: $this->fromWarehouse->id,
            partId: $this->part->id,
            qty: 10,
            unitCost: 10.0,
            userId: $this->user->id
        );

        $this->service->sendTransfer($transfer, $this->user->id);
        $transfer->refresh();

        $this->service->receiveTransfer($transfer, [$item->id => 2.0], $this->user->id);

        $item->refresh();
        $this->assertEquals(2, (float) $item->qty_received);

        $transfer->refresh();
        $this->assertSame(InventoryTransfer::STATUS_RECEIVED, $transfer->status);

        $destinationBalance = InventoryBalance::where('warehouse_id', $this->toWarehouse->id)
            ->where('part_id', $this->part->id)
            ->first();

        $this->assertNotNull($destinationBalance);
        $this->assertEquals(2, (float) $destinationBalance->qty_on_hand);
    }

    /**
     * #39 — a partial receipt is allowed and records the real amount.
     */
    public function test_a_partial_receipt_is_allowed(): void
    {
        $transfer = $this->makeTransfer();
        $item = $this->addItem($transfer, 5.0);

        $this->service->receipt(
            warehouseId: $this->fromWarehouse->id,
            partId: $this->part->id,
            qty: 10,
            unitCost: 10.0,
            userId: $this->user->id
        );

        $this->service->sendTransfer($transfer, $this->user->id);
        $transfer->refresh();

        $this->service->receiveTransfer($transfer, [$item->id => 3.0], $this->user->id);

        $item->refresh();
        $this->assertEquals(3, (float) $item->qty_received);
    }

    /**
     * #39 — a quantity that belongs to no item of this transfer is
     * refused.
     */
    public function test_a_quantity_for_an_unknown_item_is_refused(): void
    {
        $transfer = $this->makeTransfer();
        $this->addItem($transfer, 2.0);

        $this->service->receipt(
            warehouseId: $this->fromWarehouse->id,
            partId: $this->part->id,
            qty: 10,
            unitCost: 10.0,
            userId: $this->user->id
        );

        $this->service->sendTransfer($transfer, $this->user->id);
        $transfer->refresh();

        $this->expectException(ValidationException::class);

        $this->service->receiveTransfer($transfer, [999999 => 1.0], $this->user->id);
    }

    /**
     * A negative received quantity is refused.
     */
    public function test_a_negative_received_quantity_is_refused(): void
    {
        $transfer = $this->makeTransfer();
        $item = $this->addItem($transfer, 2.0);

        $this->service->receipt(
            warehouseId: $this->fromWarehouse->id,
            partId: $this->part->id,
            qty: 10,
            unitCost: 10.0,
            userId: $this->user->id
        );

        $this->service->sendTransfer($transfer, $this->user->id);
        $transfer->refresh();

        $this->expectException(ValidationException::class);

        $this->service->receiveTransfer($transfer, [$item->id => -5.0], $this->user->id);
    }

    /**
     * The receive endpoint itself refuses an over-receipt.
     */
    public function test_the_receive_endpoint_refuses_an_over_receipt(): void
    {
        $transfer = $this->makeTransfer();
        $item = $this->addItem($transfer, 2.0);

        $this->service->receipt(
            warehouseId: $this->fromWarehouse->id,
            partId: $this->part->id,
            qty: 10,
            unitCost: 10.0,
            userId: $this->user->id
        );

        $this->service->sendTransfer($transfer, $this->user->id);
        $transfer->refresh();

        $response = $this->actingAs($this->user)
            ->post('/app/inventory/transfers/'.$transfer->id.'/receive', [
                'received_qtys' => [(string) $item->id => 500],
            ]);

        $response->assertSessionHasErrors();

        $destinationBalance = InventoryBalance::where('warehouse_id', $this->toWarehouse->id)
            ->where('part_id', $this->part->id)
            ->first();

        $this->assertNull($destinationBalance);
    }

    /**
     * A part of another tenant cannot be added to a transfer.
     */
    public function test_a_part_of_another_tenant_cannot_be_added(): void
    {
        $transfer = $this->makeTransfer();

        $otherTenant = Tenant::factory()->create();
        $otherPart = Part::factory()->create([
            'tenant_id' => $otherTenant->id,
            'sku' => 'FOREIGN-001',
        ]);

        $response = $this->actingAs($this->user)
            ->post('/app/inventory/transfers/'.$transfer->id.'/items', [
                'part_id' => $otherPart->id,
                'qty_requested' => 1,
            ]);

        $response->assertSessionHasErrors('part_id');
        $this->assertDatabaseCount('inventory_transfer_items', 0);
    }
}
