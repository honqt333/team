<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Center;
use App\Models\Customer;
use App\Models\Tenant;
use App\Models\Vehicle;
use App\Models\WorkOrder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Regression test for the duplicate-code bug:
 *   SQLSTATE[23000]: Duplicate entry 'X-Y-WO-000001' for key
 *   work_orders.work_orders_tenant_id_center_id_code_unique
 *
 * The old generateCode used orderByDesc('id')->first() and trusted the
 * trailing number on that single record. If the most-recently-created row
 * had a code like 'WO-TEST-001' (test seed) or any other non-sequential
 * value, the next call would emit 'WO-000001' — colliding with the
 * original seeded record.
 */
class WorkOrderCodeGenerationTest extends TestCase
{
    use RefreshDatabase;

    private function makeCenterAndCustomer(): array
    {
        $tenant = Tenant::create(['name' => 'T1', 'slug' => 't1']);
        $center = Center::create(['tenant_id' => $tenant->id, 'name' => 'C1', 'slug' => 'c1']);
        $customer = Customer::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'name' => 'Cust1',
            'phone' => '1234567890',
        ]);
        $vehicle = Vehicle::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'customer_id' => $customer->id,
            'plate_number' => 'ABC123',
            'make' => 'Toyota',
            'model' => 'Camry',
            'year' => 2024,
        ]);

        return [$tenant, $center, $customer, $vehicle];
    }

    private function makeWo(string $code, int $tenantId, int $centerId, int $customerId, int $vehicleId): WorkOrder
    {
        return WorkOrder::create([
            'tenant_id' => $tenantId,
            'center_id' => $centerId,
            'customer_id' => $customerId,
            'vehicle_id' => $vehicleId,
            'code' => $code,
            'status' => 'open',
            'currency_code' => 'SAR',
        ]);
    }

    /** @test */
    public function first_generated_code_is_wo_000001_when_no_existing_codes(): void
    {
        [$tenant, $center, $customer, $vehicle] = $this->makeCenterAndCustomer();

        $code = WorkOrder::generateCode($tenant->id, $center->id);
        $this->assertSame('WO-000001', $code);
    }

    /** @test */
    public function next_code_after_sequential_codes_uses_highest_plus_one(): void
    {
        [$tenant, $center, $customer, $vehicle] = $this->makeCenterAndCustomer();
        $this->makeWo('WO-000001', $tenant->id, $center->id, $customer->id, $vehicle->id);
        $this->makeWo('WO-000002', $tenant->id, $center->id, $customer->id, $vehicle->id);
        $this->makeWo('WO-000003', $tenant->id, $center->id, $customer->id, $vehicle->id);

        $code = WorkOrder::generateCode($tenant->id, $center->id);
        $this->assertSame('WO-000004', $code);
    }

    /** @test */
    public function non_sequential_codes_do_not_break_generation(): void
    {
        // Simulate a tenant whose existing work orders have non-sequential
        // codes (e.g. test seeds, manually imported data). The next code
        // must still be unique.
        [$tenant, $center, $customer, $vehicle] = $this->makeCenterAndCustomer();
        $this->makeWo('WO-TEST-001', $tenant->id, $center->id, $customer->id, $vehicle->id);
        $this->makeWo('WO-IMPORT-9', $tenant->id, $center->id, $customer->id, $vehicle->id);
        $this->makeWo('WO-000001', $tenant->id, $center->id, $customer->id, $vehicle->id);

        $code = WorkOrder::generateCode($tenant->id, $center->id);
        $this->assertSame('WO-000002', $code);
    }

    /** @test */
    public function codes_from_other_centers_do_not_collide(): void
    {
        [$tenant, $center, $customer, $vehicle] = $this->makeCenterAndCustomer();
        $otherCenter = Center::create(['tenant_id' => $tenant->id, 'name' => 'C2', 'slug' => 'c2']);
        $this->makeWo('WO-000001', $tenant->id, $center->id, $customer->id, $vehicle->id);
        $this->makeWo('WO-000001', $tenant->id, $otherCenter->id, $customer->id, $vehicle->id);

        $code = WorkOrder::generateCode($tenant->id, $center->id);
        $this->assertSame('WO-000002', $code);

        $code2 = WorkOrder::generateCode($tenant->id, $otherCenter->id);
        $this->assertSame('WO-000002', $code2);
    }

    /** @test */
    public function consecutive_generate_calls_never_emit_duplicates(): void
    {
        [$tenant, $center, $customer, $vehicle] = $this->makeCenterAndCustomer();

        $first = WorkOrder::generateCode($tenant->id, $center->id);
        $this->makeWo($first, $tenant->id, $center->id, $customer->id, $vehicle->id);

        $second = WorkOrder::generateCode($tenant->id, $center->id);
        $this->assertNotSame($first, $second);
        $this->assertSame('WO-000002', $second);
    }
}
