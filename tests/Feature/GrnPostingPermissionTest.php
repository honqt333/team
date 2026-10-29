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
 * Tests for the permission required to post a goods received note
 * (audit finding #6).
 *
 * Creating a GRN only requires POS_VIEW — the ability to look at the
 * purchase order. Posting it is a different act: it writes the
 * inventory movements, credits the ordered quantity and creates the
 * purchase invoice. The policy asks for INVENTORY_MOVES_CREATE for
 * that.
 *
 * The create endpoint accepted a `post_now` flag and posted the note
 * straight from there, without the posting check. A user who could
 * only read purchase orders could therefore move stock and create a
 * purchase invoice through the create form.
 */
class GrnPostingPermissionTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    private Center $center;

    private Warehouse $warehouse;

    private Part $part;

    private Supplier $supplier;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        Permission::findOrCreate(Permissions::POS_VIEW, 'web');
        Permission::findOrCreate(Permissions::INVENTORY_MOVES_CREATE, 'web');

        $this->tenant = Tenant::factory()->create();
        $this->center = Center::factory()->create(['tenant_id' => $this->tenant->id]);

        $this->warehouse = Warehouse::factory()->create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'is_default' => true,
        ]);

        $this->part = Part::factory()->create([
            'tenant_id' => $this->tenant->id,
            'sku' => 'GRN-PERM-PART',
        ]);

        $this->supplier = Supplier::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Supplier',
            'name_ar' => 'مورد',
        ]);
    }

    /**
     * A user with the given permissions only.
     */
    private function userWith(array $permissions): User
    {
        $user = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'current_center_id' => $this->center->id,
        ]);

        $user->centers()->attach($this->center->id, ['tenant_id' => $this->tenant->id]);

        app(PermissionRegistrar::class)->setPermissionsTeamId($this->tenant->id);
        $user->givePermissionTo($permissions);
        $user->refresh();

        return $user;
    }

    private function purchaseOrder(): PurchaseOrder
    {
        return PurchaseOrder::create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'supplier_id' => $this->supplier->id,
            'warehouse_id' => $this->warehouse->id,
            'code' => 'PO-PERM-'.uniqid(),
            'status' => PurchaseOrder::STATUS_SENT,
            'order_date' => now()->toDateString(),
            'total' => 100,
        ]);
    }

    private function orderLine(PurchaseOrder $po): PurchaseOrderItem
    {
        return PurchaseOrderItem::create([
            'purchase_order_id' => $po->id,
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'part_id' => $this->part->id,
            'qty_ordered' => 10,
            'qty_received' => 0,
            'unit_cost' => 10,
            'tax_rate' => 15,
            'line_subtotal' => 100,
            'line_tax' => 15,
            'line_total' => 115,
        ]);
    }

    private function payload(PurchaseOrderItem $line): array
    {
        return [
            'warehouse_id' => $this->warehouse->id,
            'received_date' => now()->toDateString(),
            'items' => [
                [
                    'purchase_order_item_id' => $line->id,
                    'part_id' => $this->part->id,
                    'qty_received' => 5,
                    'unit_cost' => 10,
                ],
            ],
        ];
    }

    public function test_a_user_who_may_only_view_purchase_orders_cannot_post_through_the_create_form(): void
    {
        $user = $this->userWith([Permissions::POS_VIEW]);

        $po = $this->purchaseOrder();
        $line = $this->orderLine($po);

        $response = $this->actingAs($user)
            ->post('/app/purchasing/orders/'.$po->id.'/receive', $this->payload($line) + ['post_now' => true]);

        $response->assertForbidden();

        $grn = GoodsReceivedNote::where('purchase_order_id', $po->id)->firstOrFail();

        // The note may exist — creating it was allowed — but it must
        // not have been posted: no stock movement, no credited
        // quantity, no invoice.
        $this->assertNotSame(GoodsReceivedNote::STATUS_POSTED, $grn->status);
        $this->assertSame(0, (int) $line->fresh()->qty_received);
        $this->assertDatabaseCount('inventory_moves', 0);
    }

    public function test_a_user_who_may_post_can_still_post_through_the_create_form(): void
    {
        $user = $this->userWith([Permissions::POS_VIEW, Permissions::INVENTORY_MOVES_CREATE]);

        $po = $this->purchaseOrder();
        $line = $this->orderLine($po);

        $response = $this->actingAs($user)
            ->post('/app/purchasing/orders/'.$po->id.'/receive', $this->payload($line) + ['post_now' => true]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        $grn = GoodsReceivedNote::where('purchase_order_id', $po->id)->firstOrFail();

        $this->assertSame(GoodsReceivedNote::STATUS_POSTED, $grn->status);
        $this->assertSame(5, (int) $line->fresh()->qty_received);
        $this->assertDatabaseHas('inventory_moves', [
            'part_id' => $this->part->id,
            'warehouse_id' => $this->warehouse->id,
        ]);
    }

    public function test_a_user_who_may_only_view_purchase_orders_may_still_create_a_draft_note(): void
    {
        $user = $this->userWith([Permissions::POS_VIEW]);

        $po = $this->purchaseOrder();
        $line = $this->orderLine($po);

        $response = $this->actingAs($user)
            ->post('/app/purchasing/orders/'.$po->id.'/receive', $this->payload($line));

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        $grn = GoodsReceivedNote::where('purchase_order_id', $po->id)->firstOrFail();

        $this->assertSame(GoodsReceivedNote::STATUS_DRAFT, $grn->status);
        $this->assertDatabaseCount('inventory_moves', 0);
    }

    public function test_the_separate_post_action_still_refuses_a_user_without_the_posting_permission(): void
    {
        $user = $this->userWith([Permissions::POS_VIEW]);

        $po = $this->purchaseOrder();
        $line = $this->orderLine($po);

        // Create the draft note first (allowed for this user).
        $this->actingAs($user)
            ->post('/app/purchasing/orders/'.$po->id.'/receive', $this->payload($line))
            ->assertSessionHasNoErrors();

        $grn = GoodsReceivedNote::where('purchase_order_id', $po->id)->firstOrFail();

        $response = $this->actingAs($user)->post('/app/purchasing/grn/'.$grn->id.'/post');

        $response->assertForbidden();
        $this->assertNotSame(GoodsReceivedNote::STATUS_POSTED, $grn->fresh()->status);
        $this->assertDatabaseCount('inventory_moves', 0);
    }
}
