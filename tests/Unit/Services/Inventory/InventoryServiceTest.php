<?php

namespace Tests\Unit\Services\Inventory;

use App\Models\Center;
use App\Models\InventoryBalance;
use App\Models\InventoryMove;
use App\Models\Part;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\WorkOrderPartsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryServiceTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;
    protected Center $center;
    protected Warehouse $warehouse;
    protected Part $part;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::factory()->create();
        $this->center = Center::factory()->forTenant($this->tenant)->create();
        $this->warehouse = Warehouse::factory()->create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
        ]);
        $this->part = Part::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);
        $this->user = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'current_center_id' => $this->center->id,
        ]);
    }

    public function test_inventory_balance_can_be_queried(): void
    {
        $balance = InventoryBalance::factory()->create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'warehouse_id' => $this->warehouse->id,
            'part_id' => $this->part->id,
            'qty_on_hand' => 10,
        ]);

        $this->assertEquals(10, $balance->qty_on_hand);
    }
}
