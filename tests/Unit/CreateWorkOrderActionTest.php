<?php

namespace Tests\Unit;

use App\Actions\WorkOrder\CreateWorkOrderAction;
use App\Models\User;
use App\Models\WorkOrder;
use App\Models\Tenant;
use App\Models\Center;
use App\Models\Customer;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreateWorkOrderActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_a_work_order()
    {
        // Fix: 'slug' is likely required for Center too if it's in fillable
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

        $data = [
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'status' => WorkOrder::STATUS_OPEN,
            'notes' => 'Test notes',
            'items' => [
                [
                    'title' => 'Oil Change',
                    'qty' => 1,
                    'unit_price' => 50,
                ],
            ],
        ];

        $action = new CreateWorkOrderAction();
        $workOrder = $action->execute($user, $data);

        $this->assertInstanceOf(WorkOrder::class, $workOrder);
        $this->assertEquals(WorkOrder::STATUS_OPEN, $workOrder->status);
        $this->assertEquals('Test notes', $workOrder->notes);
        $this->assertCount(1, $workOrder->items);
        $this->assertEquals('Oil Change', $workOrder->items->first()->title);
    }
}
