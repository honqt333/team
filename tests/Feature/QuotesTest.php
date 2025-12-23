<?php

namespace Tests\Feature;

use App\Models\Center;
use App\Models\Customer;
use App\Models\Quote;
use App\Models\QuoteLine;
use App\Models\Service;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\WorkOrder;
use App\Services\QuoteConversionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class QuotesTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;
    protected Center $center;
    protected User $user;
    protected Customer $customer;
    protected Vehicle $vehicle;
    protected Service $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::factory()->create(['status' => 'active']);
        $this->center = Center::factory()->create(['tenant_id' => $this->tenant->id]);
        $this->user = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'current_center_id' => $this->center->id,
        ]);
        $this->customer = Customer::factory()->create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
        ]);
        $this->vehicle = Vehicle::factory()->create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'customer_id' => $this->customer->id,
        ]);
        $this->service = Service::factory()->create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'base_price' => 100,
            'min_price' => 80,
        ]);

        // Create permissions
        Permission::create(['name' => 'quotes.view', 'guard_name' => 'web']);
        Permission::create(['name' => 'quotes.create', 'guard_name' => 'web']);
        Permission::create(['name' => 'quotes.update', 'guard_name' => 'web']);
        Permission::create(['name' => 'quotes.delete', 'guard_name' => 'web']);
        Permission::create(['name' => 'quotes.approve', 'guard_name' => 'web']);

        $this->user->givePermissionTo([
            'quotes.view',
            'quotes.create',
            'quotes.update',
            'quotes.delete',
            'quotes.approve',
        ]);
    }

    public function test_can_create_quote_with_services(): void
    {
        $this->actingAs($this->user);

        $response = $this->post('/app/quotes', [
            'customer_id' => $this->customer->id,
            'vehicle_id' => $this->vehicle->id,
            'notes' => 'Test quote',
            'lines' => [
                [
                    'service_id' => $this->service->id,
                    'description' => 'Oil Change',
                    'qty' => 1,
                    'unit_price' => 100,
                    'discount_type' => 'none',
                    'discount_value' => null,
                ],
            ],
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('quotes', [
            'customer_id' => $this->customer->id,
            'vehicle_id' => $this->vehicle->id,
            'status' => 'draft',
        ]);

        $quote = Quote::first();
        $this->assertCount(1, $quote->lines);
        $this->assertEquals('Oil Change', $quote->lines->first()->description);
    }

    public function test_approving_quote_converts_to_work_card(): void
    {
        $this->actingAs($this->user);

        $quote = Quote::create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'customer_id' => $this->customer->id,
            'vehicle_id' => $this->vehicle->id,
            'code' => 'QT-000001',
            'status' => Quote::STATUS_DRAFT,
            'created_by' => $this->user->id,
        ]);

        QuoteLine::create([
            'quote_id' => $quote->id,
            'service_id' => $this->service->id,
            'description' => 'Test Service',
            'qty' => 2,
            'unit_price' => 100,
            'base_price_snapshot' => 100,
            'min_price_snapshot' => 80,
        ]);

        $response = $this->post("/app/quotes/{$quote->id}/approve");

        $response->assertRedirect();

        $quote->refresh();
        $this->assertEquals(Quote::STATUS_CONVERTED, $quote->status);
        $this->assertNotNull($quote->approved_at);
        $this->assertNotNull($quote->converted_at);
        $this->assertNotNull($quote->converted_work_order_id);

        $workOrder = WorkOrder::find($quote->converted_work_order_id);
        $this->assertNotNull($workOrder);
        $this->assertCount(1, $workOrder->items);
    }

    public function test_quote_lines_copied_correctly_on_conversion(): void
    {
        $quote = Quote::create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'customer_id' => $this->customer->id,
            'vehicle_id' => $this->vehicle->id,
            'code' => 'QT-000002',
            'status' => Quote::STATUS_APPROVED,
            'approved_at' => now(),
            'approved_by' => $this->user->id,
            'created_by' => $this->user->id,
        ]);

        QuoteLine::create([
            'quote_id' => $quote->id,
            'service_id' => $this->service->id,
            'description' => 'Service A',
            'qty' => 3,
            'unit_price' => 150,
            'base_price_snapshot' => 150,
            'min_price_snapshot' => 100,
            'discount_type' => 'percentage',
            'discount_value' => 10,
        ]);

        $conversionService = app(QuoteConversionService::class);
        $workOrder = $conversionService->convert($quote, $this->user);

        $item = $workOrder->items->first();
        $this->assertEquals('Service A', $item->title);
        $this->assertEquals(3, $item->qty);
        $this->assertEquals(150, $item->unit_price);
        $this->assertEquals(150, $item->base_price_snapshot);
        $this->assertEquals(100, $item->min_price_snapshot);
        $this->assertEquals('percentage', $item->discount_type);
        $this->assertEquals(10, $item->discount_value);
        $this->assertTrue($item->price_locked);
    }

    public function test_rejecting_quote_does_not_create_work_card(): void
    {
        $this->actingAs($this->user);

        $quote = Quote::create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'customer_id' => $this->customer->id,
            'vehicle_id' => $this->vehicle->id,
            'code' => 'QT-000003',
            'status' => Quote::STATUS_DRAFT,
            'created_by' => $this->user->id,
        ]);

        $workOrderCountBefore = WorkOrder::count();

        $response = $this->post("/app/quotes/{$quote->id}/reject");

        $response->assertRedirect();

        $quote->refresh();
        $this->assertEquals(Quote::STATUS_REJECTED, $quote->status);
        $this->assertNotNull($quote->rejected_at);
        $this->assertNull($quote->converted_work_order_id);

        $this->assertEquals($workOrderCountBefore, WorkOrder::count());
    }

    public function test_cannot_approve_without_permission(): void
    {
        $userWithoutPermission = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'current_center_id' => $this->center->id,
        ]);
        $userWithoutPermission->givePermissionTo(['quotes.view', 'quotes.create']);

        $this->actingAs($userWithoutPermission);

        $quote = Quote::create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'customer_id' => $this->customer->id,
            'vehicle_id' => $this->vehicle->id,
            'code' => 'QT-000004',
            'status' => Quote::STATUS_DRAFT,
            'created_by' => $this->user->id,
        ]);

        $response = $this->post("/app/quotes/{$quote->id}/approve");

        $response->assertForbidden();
    }

    public function test_conversion_is_idempotent(): void
    {
        $quote = Quote::create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'customer_id' => $this->customer->id,
            'vehicle_id' => $this->vehicle->id,
            'code' => 'QT-000005',
            'status' => Quote::STATUS_CONVERTED,
            'approved_at' => now(),
            'converted_at' => now(),
            'converted_work_order_id' => 1,
            'created_by' => $this->user->id,
        ]);

        $conversionService = app(QuoteConversionService::class);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Quote has already been converted.');

        $conversionService->convert($quote, $this->user);
    }

    public function test_min_price_validation_on_quote_lines(): void
    {
        $quote = Quote::create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'customer_id' => $this->customer->id,
            'vehicle_id' => $this->vehicle->id,
            'code' => 'QT-000006',
            'status' => Quote::STATUS_DRAFT,
            'created_by' => $this->user->id,
        ]);

        $line = QuoteLine::create([
            'quote_id' => $quote->id,
            'service_id' => $this->service->id,
            'description' => 'Service with min price',
            'qty' => 1,
            'unit_price' => 100,
            'base_price_snapshot' => 100,
            'min_price_snapshot' => 80,
            'discount_type' => 'fixed',
            'discount_value' => 15,
        ]);

        // With 15 discount, final price should be 85 which is above 80 min
        $this->assertEquals(85, $line->final_unit_price);

        // Now try with discount that would go below min price
        $line->discount_value = 25;
        $line->save();

        // PricingHelper should clamp to min_price
        $this->assertGreaterThanOrEqual(80, $line->fresh()->final_unit_price);
    }
}
