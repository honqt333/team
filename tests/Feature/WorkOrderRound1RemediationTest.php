<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Center;
use App\Models\Customer;
use App\Models\Service;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\WorkOrder;
use App\Models\WorkOrderItem;
use App\Models\WorkOrderLaborEntry;
use App\Support\Permissions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class WorkOrderRound1RemediationTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    private Center $center;

    private User $authorizedUser;

    private User $unauthorizedUser;

    private Customer $customer;

    private Vehicle $vehicle;

    private Service $service;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach ([
            Permissions::WORK_ORDERS_VIEW,
            Permissions::WORK_ORDERS_CREATE,
            Permissions::WORK_ORDERS_UPDATE,
        ] as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $this->tenant = Tenant::factory()->create(['timezone' => 'Asia/Riyadh']);
        $this->center = Center::factory()->create(['tenant_id' => $this->tenant->id]);
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
        ]);

        $this->authorizedUser = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'current_center_id' => $this->center->id,
        ]);
        $this->authorizedUser->centers()->attach($this->center->id, ['tenant_id' => $this->tenant->id]);

        app(PermissionRegistrar::class)->setPermissionsTeamId($this->tenant->id);
        $this->authorizedUser->givePermissionTo([
            Permissions::WORK_ORDERS_VIEW,
            Permissions::WORK_ORDERS_CREATE,
            Permissions::WORK_ORDERS_UPDATE,
        ]);
        $this->authorizedUser->refresh();

        $this->unauthorizedUser = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'current_center_id' => $this->center->id,
        ]);
        $this->unauthorizedUser->centers()->attach($this->center->id, ['tenant_id' => $this->tenant->id]);
        $this->unauthorizedUser->givePermissionTo([Permissions::WORK_ORDERS_VIEW]);
        $this->unauthorizedUser->refresh();
    }

    private function createWorkOrder(string $status = WorkOrder::STATUS_OPEN): WorkOrder
    {
        return WorkOrder::factory()->create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'customer_id' => $this->customer->id,
            'vehicle_id' => $this->vehicle->id,
            'status' => $status,
        ]);
    }

    private function validPngDataUri(): string
    {
        // Minimal 1x1 transparent PNG
        $png = base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII='
        );

        return 'data:image/png;base64,'.base64_encode($png);
    }

    // -------------------------------------------------------------------------
    // AUD-001: Generic update must not change status or closed_at
    // -------------------------------------------------------------------------

    public function test_generic_update_allows_notes_without_changing_status(): void
    {
        $workOrder = $this->createWorkOrder(WorkOrder::STATUS_OPEN);

        $this->actingAs($this->authorizedUser)
            ->put('/app/work-orders/'.$workOrder->id, [
                'customer_id' => $this->customer->id,
                'vehicle_id' => $this->vehicle->id,
                'notes' => 'Updated safely',
            ])
            ->assertRedirect();

        $fresh = $workOrder->fresh();
        $this->assertSame(WorkOrder::STATUS_OPEN, $fresh->status);
        $this->assertNull($fresh->closed_at);
        $this->assertSame('Updated safely', $fresh->notes);
    }

    public function test_generic_update_rejects_status_field_with_validation_error(): void
    {
        $workOrder = $this->createWorkOrder(WorkOrder::STATUS_OPEN);

        $this->actingAs($this->authorizedUser)
            ->put('/app/work-orders/'.$workOrder->id, [
                'customer_id' => $this->customer->id,
                'vehicle_id' => $this->vehicle->id,
                'status' => WorkOrder::STATUS_DONE,
            ])
            ->assertSessionHasErrors('status');

        $fresh = $workOrder->fresh();
        $this->assertSame(WorkOrder::STATUS_OPEN, $fresh->status);
        $this->assertNull($fresh->closed_at);
    }

    // -------------------------------------------------------------------------
    // AUD-002: WorkOrderSignatureController security & authorization
    // -------------------------------------------------------------------------

    public function test_signature_rejects_svg_and_does_not_write_file(): void
    {
        Storage::fake('public');
        $workOrder = $this->createWorkOrder();

        $svgPayload = 'data:image/svg+xml;base64,'.base64_encode('<svg onload="alert(1)"></svg>');

        $this->actingAs($this->authorizedUser)
            ->post('/app/work-orders/'.$workOrder->id.'/signatures', [
                'type' => 'reception',
                'signature' => $svgPayload,
            ])
            ->assertSessionHasErrors('signature');

        $this->assertNull($workOrder->fresh()->reception_signature);
        $this->assertEmpty(Storage::disk('public')->allFiles());
    }

    public function test_signature_rejects_non_image_or_spoofed_png_and_oversized_payload(): void
    {
        Storage::fake('public');
        $workOrder = $this->createWorkOrder();

        // Spoofed PNG header with plain text bytes
        $fakePng = 'data:image/png;base64,'.base64_encode('not a real png');
        $this->actingAs($this->authorizedUser)
            ->post('/app/work-orders/'.$workOrder->id.'/signatures', [
                'type' => 'reception',
                'signature' => $fakePng,
            ])
            ->assertSessionHasErrors('signature');

        // Oversized payload (> 512 KB)
        $oversized = 'data:image/png;base64,'.str_repeat('A', 750000);
        $this->actingAs($this->authorizedUser)
            ->post('/app/work-orders/'.$workOrder->id.'/signatures', [
                'type' => 'reception',
                'signature' => $oversized,
            ])
            ->assertSessionHasErrors('signature');

        $this->assertEmpty(Storage::disk('public')->allFiles());
    }

    public function test_signature_accepts_valid_png_for_authorized_user(): void
    {
        Storage::fake('public');
        $workOrder = $this->createWorkOrder();

        $this->actingAs($this->authorizedUser)
            ->post('/app/work-orders/'.$workOrder->id.'/signatures', [
                'type' => 'reception',
                'signature' => $this->validPngDataUri(),
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $path = $workOrder->fresh()->reception_signature;
        $this->assertNotNull($path);
        $this->assertStringEndsWith('.png', $path);
        Storage::disk('public')->assertExists($path);
    }

    public function test_delivery_signature_is_allowed_on_done_work_order(): void
    {
        Storage::fake('public');
        $workOrder = $this->createWorkOrder(WorkOrder::STATUS_DONE);

        $this->actingAs($this->authorizedUser)
            ->post('/app/work-orders/'.$workOrder->id.'/signatures', [
                'type' => 'delivery',
                'signature' => $this->validPngDataUri(),
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertNotNull($workOrder->fresh()->delivery_signature);
    }

    public function test_signature_returns_403_for_unauthorized_user_and_cross_tenant(): void
    {
        Storage::fake('public');
        $workOrder = $this->createWorkOrder();

        $this->actingAs($this->unauthorizedUser)
            ->post('/app/work-orders/'.$workOrder->id.'/signatures', [
                'type' => 'reception',
                'signature' => $this->validPngDataUri(),
            ])
            ->assertStatus(403);

        // Cross-tenant user with full permissions in Tenant B
        $tenantB = Tenant::factory()->create();
        $centerB = Center::factory()->create(['tenant_id' => $tenantB->id]);
        $userB = User::factory()->create([
            'tenant_id' => $tenantB->id,
            'current_center_id' => $centerB->id,
        ]);
        $userB->centers()->attach($centerB->id, ['tenant_id' => $tenantB->id]);
        app(PermissionRegistrar::class)->setPermissionsTeamId($tenantB->id);
        $userB->givePermissionTo([Permissions::WORK_ORDERS_UPDATE]);
        $userB->refresh();

        $crossTenantResponse = $this->actingAs($userB)
            ->post('/app/work-orders/'.$workOrder->id.'/signatures', [
                'type' => 'reception',
                'signature' => $this->validPngDataUri(),
            ]);

        $this->assertTrue(
            in_array($crossTenantResponse->getStatusCode(), [403, 404], true),
            'Cross-tenant access must be denied with 403 or 404.'
        );

        $this->assertEmpty(Storage::disk('public')->allFiles());
    }

    // -------------------------------------------------------------------------
    // AUD-003: Item updates preserve identity & labor, reject foreign/protected deletes
    // -------------------------------------------------------------------------

    public function test_updating_item_by_id_preserves_identity_and_labor_entries(): void
    {
        $workOrder = $this->createWorkOrder(WorkOrder::STATUS_IN_PROGRESS);

        $item = $workOrder->items()->create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'service_id' => $this->service->id,
            'title' => 'Original Title',
            'qty' => 1,
            'unit_price' => 100,
            'status' => WorkOrderItem::STATUS_IN_PROGRESS,
        ]);

        $labor = WorkOrderLaborEntry::create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'work_order_id' => $workOrder->id,
            'work_order_item_id' => $item->id,
            'user_id' => $this->authorizedUser->id,
            'started_at' => now()->subMinutes(10),
            'resumed_at' => now()->subMinutes(10),
            'paused_duration_seconds' => 0,
            'status' => WorkOrderLaborEntry::STATUS_RUNNING,
        ]);

        $this->actingAs($this->authorizedUser)
            ->put('/app/work-orders/'.$workOrder->id, [
                'customer_id' => $this->customer->id,
                'vehicle_id' => $this->vehicle->id,
                'items' => [
                    [
                        'id' => $item->id,
                        'title' => 'Updated Title',
                        'qty' => 1,
                        'unit_price' => 150,
                    ],
                ],
            ])
            ->assertRedirect();

        $this->assertSame('Updated Title', $item->fresh()->title);
        $this->assertEquals(150.0, (float) $item->fresh()->unit_price);
        $this->assertNotNull($labor->fresh());
    }

    public function test_cannot_delete_item_with_labor_entries_via_generic_update(): void
    {
        $workOrder = $this->createWorkOrder(WorkOrder::STATUS_IN_PROGRESS);

        $item = $workOrder->items()->create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'service_id' => $this->service->id,
            'title' => 'Protected Item',
            'qty' => 1,
            'unit_price' => 100,
            'status' => WorkOrderItem::STATUS_IN_PROGRESS,
        ]);

        WorkOrderLaborEntry::create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'work_order_id' => $workOrder->id,
            'work_order_item_id' => $item->id,
            'user_id' => $this->authorizedUser->id,
            'started_at' => now()->subMinutes(5),
            'resumed_at' => now()->subMinutes(5),
            'paused_duration_seconds' => 0,
            'status' => WorkOrderLaborEntry::STATUS_RUNNING,
        ]);

        $this->actingAs($this->authorizedUser)
            ->put('/app/work-orders/'.$workOrder->id, [
                'customer_id' => $this->customer->id,
                'vehicle_id' => $this->vehicle->id,
                'items' => [],
            ])
            ->assertSessionHasErrors('items');

        $this->assertNotNull($item->fresh());
    }

    public function test_cannot_attach_item_from_another_work_order(): void
    {
        $workOrderA = $this->createWorkOrder();
        $workOrderB = $this->createWorkOrder();

        $foreignItem = $workOrderB->items()->create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'service_id' => $this->service->id,
            'title' => 'Foreign Item',
            'qty' => 1,
            'unit_price' => 200,
        ]);

        $this->actingAs($this->authorizedUser)
            ->put('/app/work-orders/'.$workOrderA->id, [
                'customer_id' => $this->customer->id,
                'vehicle_id' => $this->vehicle->id,
                'items' => [
                    [
                        'id' => $foreignItem->id,
                        'title' => 'Hijacked',
                        'qty' => 1,
                        'unit_price' => 200,
                    ],
                ],
            ])
            ->assertSessionHasErrors('items.0.id');

        $this->assertSame($workOrderB->id, $foreignItem->fresh()->work_order_id);
    }
}
