<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Center;
use App\Models\Customer;
use App\Models\Part;
use App\Models\Quote;
use App\Models\QuoteLine;
use App\Models\QuotePart;
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

class PartServiceLinkAndPackagePriceTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;

    protected Center $center;

    protected User $user;

    protected Warehouse $warehouse;

    protected Part $part;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::factory()->create(['status' => 'active']);
        $this->center = Center::factory()->create(['tenant_id' => $this->tenant->id]);
        $this->user = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'current_center_id' => $this->center->id,
        ]);
        $this->user->centers()->attach($this->center->id, ['tenant_id' => $this->tenant->id]);

        $this->warehouse = Warehouse::factory()->create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'is_default' => true,
        ]);

        $this->part = Part::factory()->create([
            'tenant_id' => $this->tenant->id,
            'default_sale_price' => 150.00,
            'min_sale_price' => 100.00,
        ]);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $permissions = [
            Permissions::WORK_ORDERS_UPDATE,
            Permissions::QUOTES_VIEW,
            Permissions::QUOTES_UPDATE,
            Permissions::QUOTES_DELETE,
        ];

        foreach ($permissions as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
        }
        app(PermissionRegistrar::class)->setPermissionsTeamId($this->tenant->id);
        $this->user->givePermissionTo($permissions);
    }

    public function test_work_order_part_unlinking_service_closes_include_in_package(): void
    {
        $customer = Customer::factory()->create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
        ]);
        $vehicle = Vehicle::factory()->create([
            'tenant_id' => $this->tenant->id,
            'customer_id' => $customer->id,
        ]);
        $workOrder = WorkOrder::factory()->create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'status' => WorkOrder::STATUS_OPEN,
        ]);

        $item = WorkOrderItem::create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'work_order_id' => $workOrder->id,
            'title' => 'Test Service',
            'qty' => 1,
            'unit_price' => 200.00,
            'min_price_snapshot' => 0.00,
            'base_price_snapshot' => 200.00,
            'final_unit_price' => 200.00,
            'line_total' => 200.00,
        ]);

        $workOrderPart = WorkOrderItemPart::create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'work_order_id' => $workOrder->id,
            'work_order_item_id' => $item->id,
            'part_id' => $this->part->id,
            'warehouse_id' => $this->warehouse->id,
            'name' => 'Test Part',
            'source' => 'warehouse',
            'qty' => 1,
            'unit_price' => 0.00,
            'discount' => 0.00,
            'include_in_package' => true,
            'hide_on_print' => true,
            'status' => WorkOrderItemPart::STATUS_PENDING,
        ]);

        $this->actingAsWithTeam($this->user);
        $response = $this->put(
            route('work-orders.parts.update', ['workOrderPart' => $workOrderPart->id]),
            [
                'work_order_item_id' => null, // unlinking service
                'source' => 'warehouse',
                'name' => 'Test Part',
                'part_id' => $this->part->id,
                'warehouse_id' => $this->warehouse->id,
                'qty' => 1,
                'unit_price' => 150.00,
                'discount' => 0,
                'include_in_package' => true, // Even if requested, should be forced false
                'hide_on_print' => true,
            ]
        );

        $response->assertSessionHasNoErrors();
        $workOrderPart->refresh();

        $this->assertNull($workOrderPart->work_order_item_id);
        $this->assertFalse($workOrderPart->include_in_package);
        $this->assertFalse($workOrderPart->hide_on_print);
        $this->assertEquals(150.00, (float) $workOrderPart->unit_price);
    }

    public function test_quote_part_unlinking_service_closes_include_in_package(): void
    {
        $customer = Customer::factory()->create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
        ]);
        $vehicle = Vehicle::factory()->create([
            'tenant_id' => $this->tenant->id,
            'customer_id' => $customer->id,
        ]);
        $quote = Quote::create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'created_by' => $this->user->id,
            'code' => 'QT-000001',
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'status' => Quote::STATUS_DRAFT,
            'valid_until' => now()->addDays(7)->format('Y-m-d'),
        ]);

        $line = QuoteLine::create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'quote_id' => $quote->id,
            'description' => 'Test Service Line',
            'qty' => 1,
            'unit_price' => 100.00,
            'min_price_snapshot' => 0.00,
            'base_price_snapshot' => 100.00,
        ]);

        $quotePart = QuotePart::create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'quote_id' => $quote->id,
            'quote_line_id' => $line->id,
            'part_id' => $this->part->id,
            'source' => 'warehouse',
            'name' => 'Quote Part',
            'qty' => 1,
            'unit_price' => 0.00,
            'discount' => 0.00,
            'include_in_package' => true,
            'hide_on_print' => true,
        ]);

        $this->actingAsWithTeam($this->user);
        $response = $this->put(
            route('app.quotes.parts.update', ['quote' => $quote->id, 'quotePart' => $quotePart->id]),
            [
                'quote_line_id' => null, // unlinking service
                'source' => 'warehouse',
                'name' => 'Quote Part',
                'part_id' => $this->part->id,
                'qty' => 1,
                'unit_price' => 150.00,
                'discount' => 0,
                'include_in_package' => true, // Should be forced false because quote_line_id is null
                'hide_on_print' => true,
            ]
        );

        $response->assertSessionHasNoErrors();
        $quotePart->refresh();

        $this->assertNull($quotePart->quote_line_id);
        $this->assertFalse($quotePart->include_in_package);
        $this->assertFalse($quotePart->hide_on_print);
        $this->assertEquals(150.00, (float) $quotePart->unit_price);
    }

    public function test_deleting_quote_service_line_resets_included_parts_and_restores_price(): void
    {
        $customer = Customer::factory()->create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
        ]);
        $vehicle = Vehicle::factory()->create([
            'tenant_id' => $this->tenant->id,
            'customer_id' => $customer->id,
        ]);
        $quote = Quote::create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'created_by' => $this->user->id,
            'code' => 'QT-000002',
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'status' => Quote::STATUS_DRAFT,
            'valid_until' => now()->addDays(7)->format('Y-m-d'),
        ]);

        $line = QuoteLine::create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'quote_id' => $quote->id,
            'description' => 'Service to be deleted',
            'qty' => 1,
            'unit_price' => 300.00,
            'min_price_snapshot' => 0.00,
            'base_price_snapshot' => 300.00,
        ]);

        $quotePart = QuotePart::create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'quote_id' => $quote->id,
            'quote_line_id' => $line->id,
            'part_id' => $this->part->id,
            'source' => 'warehouse',
            'name' => 'Package Part',
            'qty' => 1,
            'unit_price' => 0.00,
            'discount' => 0.00,
            'include_in_package' => true,
            'hide_on_print' => true,
        ]);

        $this->actingAsWithTeam($this->user);
        $response = $this->delete(
            route('app.quotes.services.destroy', ['quote' => $quote->id, 'line' => $line->id])
        );

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseMissing('quote_lines', ['id' => $line->id]);

        $quotePart->refresh();
        $this->assertNull($quotePart->quote_line_id);
        $this->assertFalse($quotePart->include_in_package);
        $this->assertFalse($quotePart->hide_on_print);
        $this->assertEquals(150.00, (float) $quotePart->unit_price);
    }
}
