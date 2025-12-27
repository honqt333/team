<?php

namespace Tests\Unit;

use App\Actions\WorkOrder\UpdateWorkOrderAction;
use App\Models\User;
use App\Models\WorkOrder;
use App\Models\Tenant;
use App\Models\Center;
use App\Models\Customer;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpdateWorkOrderActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_updates_a_work_order()
    {
        $tenant = Tenant::create(['name' => 'Test Tenant', 'slug' => 'test-tenant']);
        
        $center = Center::create([
            'tenant_id' => $tenant->id, 
            'name' => 'Test Center', 
            'slug' => 'test-center',
            'is_active' => true
        ]);

        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'tenant_id' => $tenant->id,
            'current_center_id' => $center->id,
        ]);

        $customer = Customer::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'name' => 'Test Customer',
            'phone' => '1234567890'
        ]);
        
        $vehicle = Vehicle::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'customer_id' => $customer->id,
            'plate_number' => 'ABC 1234',
            'make_other' => 'Toyota',
            'model_other' => 'Camry',
            'year' => 2020
        ]);

        $workOrder = WorkOrder::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'code' => 'WO-000001',
            'status' => WorkOrder::STATUS_OPEN,
            'notes' => 'Original notes',
            'opened_at' => now(),
        ]);

        $data = [
            'status' => WorkOrder::STATUS_DONE,
            'notes' => 'Updated notes',
            'items' => [
                [
                    'title' => 'New Service',
                    'qty' => 2,
                    'unit_price' => 100,
                ],
            ],
        ];

        $action = new UpdateWorkOrderAction();
        $updatedWorkOrder = $action->execute($workOrder, $user, $data);

        $this->assertEquals(WorkOrder::STATUS_DONE, $updatedWorkOrder->status);
        $this->assertEquals('Updated notes', $updatedWorkOrder->notes);
        $this->assertNotNull($updatedWorkOrder->closed_at);
        $this->assertCount(1, $updatedWorkOrder->items);
        $this->assertEquals('New Service', $updatedWorkOrder->items->first()->title);
    }
}
