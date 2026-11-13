<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\PaymentType;
use App\Models\Center;
use App\Models\Customer;
use App\Models\HR\Employee;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\WorkOrder;
use App\Models\WorkOrderItem;
use App\Models\WorkOrderLaborEntry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class WorkOrderFixesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        foreach ([
            'crm.work_orders.view',
            'crm.work_orders.create',
            'crm.work_orders.update',
            'crm.work_orders.delete',
            'crm.payments.create',
            'crm.payments.update',
            'crm.customers.view',
            'crm.vehicles.view',
        ] as $permission) {
            Permission::findOrCreate($permission, 'web');
        }
    }

    protected function createTenantContext(array $permissions = [], ?string $roleName = null): array
    {
        $tenant = Tenant::factory()->create();
        $center = Center::factory()->create(['tenant_id' => $tenant->id]);

        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'current_center_id' => $center->id,
        ]);
        $user->centers()->attach($center->id, ['tenant_id' => $tenant->id]);

        app(PermissionRegistrar::class)->setPermissionsTeamId($tenant->id);

        if ($roleName !== null) {
            $role = Role::firstOrCreate([
                'name' => $roleName,
                'guard_name' => 'web',
                'tenant_id' => $tenant->id,
            ]);
            $user->assignRole($role);
        }

        foreach ($permissions as $permission) {
            $user->givePermissionTo($permission);
        }

        $customer = Customer::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'type' => 'individual',
            'name' => 'Test Customer',
            'phone' => '050'.random_int(1000000, 9999999),
        ]);

        $vehicle = Vehicle::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'customer_id' => $customer->id,
            'plate_number' => strtoupper(Str::random(3)).' '.random_int(1000, 9999),
        ]);

        $user->refresh();

        return [$tenant, $center, $user, $customer, $vehicle];
    }

    public function test_bad_debt_is_rejected_on_open_or_non_invoiced_work_order_and_allowed_on_deferred_invoice(): void
    {
        [, $center, $user, $customer, $vehicle] = $this->createTenantContext([
            'crm.work_orders.view',
            'crm.work_orders.update',
            'crm.payments.create',
        ]);

        $openWorkOrder = WorkOrder::create([
            'tenant_id' => $user->tenant_id,
            'center_id' => $center->id,
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'code' => 'WO-BD-001',
            'status' => WorkOrder::STATUS_OPEN,
        ]);

        WorkOrderItem::create([
            'work_order_id' => $openWorkOrder->id,
            'tenant_id' => $user->tenant_id,
            'center_id' => $center->id,
            'title' => 'Engine Repair',
            'qty' => 1,
            'unit_price' => 500,
            'status' => WorkOrderItem::STATUS_COMPLETED,
        ]);

        // Rejected on open work order without invoice
        $this->actingAs($user)
            ->post(route('work-orders.payments.store', $openWorkOrder->id), [
                'type' => 'bad_debt',
                'amount' => 100,
                'payment_date' => now()->toDateString(),
            ])
            ->assertRedirect()
            ->assertSessionHas('error', __('payments.bad_debt_credit_invoices_only'));

        // Still rejected when status is done if no invoice exists
        $openWorkOrder->update(['status' => WorkOrder::STATUS_DONE]);

        $this->actingAs($user)
            ->post(route('work-orders.payments.store', $openWorkOrder->id), [
                'type' => 'bad_debt',
                'amount' => 100,
                'payment_date' => now()->toDateString(),
            ])
            ->assertRedirect()
            ->assertSessionHas('error', __('payments.bad_debt_credit_invoices_only'));

        $this->assertDatabaseMissing('payments', [
            'work_order_id' => $openWorkOrder->id,
            'type' => 'bad_debt',
        ]);

        // Allowed once work order is done and has a deferred/valid invoice (without payment_method)
        Invoice::create([
            'tenant_id' => $user->tenant_id,
            'center_id' => $center->id,
            'customer_id' => $customer->id,
            'work_order_id' => $openWorkOrder->id,
            'invoice_number' => 'INV-BD-001',
            'type' => 'invoice',
            'status' => 'valid',
            'issue_date' => now(),
            'supply_date' => now(),
            'due_date' => now()->addDays(15),
            'total_excl_tax' => 434.78,
            'total_tax' => 65.22,
            'total_incl_tax' => 500.00,
            'total_paid' => 0,
            'payment_status' => 'unpaid',
        ]);

        $this->actingAs($user)
            ->postJson(route('work-orders.payments.store', $openWorkOrder->id), [
                'type' => 'bad_debt',
                'amount' => 100,
                'payment_date' => now()->toDateString(),
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('payments', [
            'work_order_id' => $openWorkOrder->id,
            'type' => 'bad_debt',
            'payment_method' => 'credit',
            'amount' => 100,
        ]);
    }

    public function test_fuel_level_and_svg_damage_marks_persist_across_store_update_and_condition(): void
    {
        [, , $user, $customer, $vehicle] = $this->createTenantContext([
            'crm.work_orders.view',
            'crm.work_orders.create',
            'crm.work_orders.update',
        ]);

        // 1. Store with fuel_level = 75
        $this->actingAs($user)
            ->post(route('work-orders.store'), [
                'customer_id' => $customer->id,
                'vehicle_id' => $vehicle->id,
                'fuel_level' => 75,
                'damage_marks' => [
                    ['x' => 350, 'y' => 240, 'color' => 'red', 'description' => 'Rear scratch'],
                ],
            ])
            ->assertRedirect();

        $workOrder = WorkOrder::query()->latest('id')->firstOrFail();
        $this->assertSame(75.0, (float) $workOrder->fuel_level);
        $this->assertDatabaseHas('work_order_damage_marks', [
            'work_order_id' => $workOrder->id,
            'x' => 350,
            'y' => 240,
        ]);

        // 2. Update with fuel_level = 25
        $this->actingAs($user)
            ->put(route('work-orders.update', $workOrder->id), [
                'customer_id' => $customer->id,
                'vehicle_id' => $vehicle->id,
                'fuel_level' => 25,
            ])
            ->assertRedirect();

        $this->assertSame(25.0, (float) $workOrder->fresh()->fuel_level);

        // 3. Inline condition update with full SVG bounds (0..400, 0..300) and fuel_level = 100
        $this->actingAs($user)
            ->putJson(route('app.work-orders.update-condition', $workOrder->id), [
                'fuel_level' => 100,
                'damage_marks' => [
                    ['x' => 395, 'y' => 295, 'color' => 'blue', 'description' => 'Quarter panel dent'],
                ],
            ])
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertSame(100.0, (float) $workOrder->fresh()->fuel_level);
        $this->assertDatabaseHas('work_order_damage_marks', [
            'work_order_id' => $workOrder->id,
            'x' => 395,
            'y' => 295,
            'description' => 'Quarter panel dent',
        ]);
    }

    public function test_print_services_for_technician_includes_localized_technician_name(): void
    {
        [$tenant, $center, $admin, $customer, $vehicle] = $this->createTenantContext([
            'crm.work_orders.view',
            'crm.work_orders.update',
        ]);

        $tech = User::factory()->create([
            'tenant_id' => $tenant->id,
            'current_center_id' => $center->id,
            'name' => 'fahad_user',
        ]);
        $tech->centers()->attach($center->id, ['tenant_id' => $tenant->id]);

        Employee::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'user_id' => $tech->id,
            'employee_number' => 'EMP-9001',
            'name_ar' => 'فهد الدوسري',
            'name_en' => 'Fahad Al-Dosari',
            'status' => 'active',
        ]);

        $workOrder = WorkOrder::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'code' => 'WO-PRT-001',
            'status' => WorkOrder::STATUS_OPEN,
        ]);

        $itemAssigned = WorkOrderItem::create([
            'work_order_id' => $workOrder->id,
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'title' => 'Engine Tune-Up',
            'qty' => 1,
            'unit_price' => 200,
            'status' => WorkOrderItem::STATUS_PENDING,
        ]);
        $itemAssigned->technicians()->attach($tech->id, ['assigned_at' => now(), 'share' => 100]);

        WorkOrderItem::create([
            'work_order_id' => $workOrder->id,
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'title' => 'Other Service',
            'qty' => 1,
            'unit_price' => 100,
            'status' => WorkOrderItem::STATUS_PENDING,
        ]);

        $response = $this->actingAs($admin)
            ->get(route('work-orders.print.services', [
                'workOrder' => $workOrder->id,
                'technician_id' => $tech->id,
            ]));

        $response->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('WorkOrders/Print/Services')
                ->where('printedTechnician.id', $tech->id)
                ->where('printedTechnician.name_ar', 'فهد الدوسري')
                ->where('printedTechnician.name_en', 'Fahad Al-Dosari')
                ->has('workOrder.items', 1)
                ->where('workOrder.items.0.id', $itemAssigned->id)
            );
    }

    public function test_admin_can_start_timer_for_assigned_technician_while_non_admin_cannot_start_for_others(): void
    {
        [$tenant, $center, $admin, $customer, $vehicle] = $this->createTenantContext(
            ['crm.work_orders.view', 'crm.work_orders.update'],
            'super_admin'
        );

        $tech = User::factory()->create([
            'tenant_id' => $tenant->id,
            'current_center_id' => $center->id,
        ]);
        $tech->centers()->attach($center->id, ['tenant_id' => $tenant->id]);

        $otherTech = User::factory()->create([
            'tenant_id' => $tenant->id,
            'current_center_id' => $center->id,
        ]);
        $otherTech->centers()->attach($center->id, ['tenant_id' => $tenant->id]);

        app(PermissionRegistrar::class)->setPermissionsTeamId($tenant->id);
        $otherTech->givePermissionTo(['crm.work_orders.view', 'crm.work_orders.update']);

        $workOrder = WorkOrder::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'code' => 'WO-TMR-001',
            'status' => WorkOrder::STATUS_OPEN,
        ]);

        $item = WorkOrderItem::create([
            'work_order_id' => $workOrder->id,
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'title' => 'Brake Service',
            'qty' => 1,
            'unit_price' => 150,
            'status' => WorkOrderItem::STATUS_PENDING,
        ]);
        $item->technicians()->attach($tech->id, ['assigned_at' => now(), 'share' => 100]);

        // Non-admin technician cannot start timer in another technician's name
        $this->actingAs($otherTech)
            ->postJson("/app/work-orders/{$workOrder->id}/items/{$item->id}/labor-entries/start", [
                'technician_id' => $tech->id,
            ])
            ->assertStatus(403);

        // Admin starts timer for the assigned technician
        $this->actingAs($admin)
            ->postJson("/app/work-orders/{$workOrder->id}/items/{$item->id}/labor-entries/start", [
                'technician_id' => $tech->id,
            ])
            ->assertStatus(200);

        $this->assertDatabaseHas('work_order_labor_entries', [
            'work_order_id' => $workOrder->id,
            'work_order_item_id' => $item->id,
            'user_id' => $tech->id,
            'status' => WorkOrderLaborEntry::STATUS_RUNNING,
        ]);

        $this->assertSame(WorkOrderItem::STATUS_IN_PROGRESS, $item->fresh()->status);
        $this->assertSame(WorkOrder::STATUS_IN_PROGRESS, $workOrder->fresh()->status);

        // WorkOrders/Show passes serverNow prop for clock-skew-safe live timers
        $this->actingAs($admin)
            ->get(route('work-orders.show', $workOrder->id))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('WorkOrders/Show')
                ->has('serverNow')
                ->has('laborEntries', 1)
            );
    }

    public function test_work_order_cannot_close_with_excess_payment_until_refunded(): void
    {
        [$tenant, $center, $user, $customer, $vehicle] = $this->createTenantContext([
            'crm.work_orders.view',
            'crm.work_orders.update',
            'crm.payments.create',
        ]);

        $workOrder = WorkOrder::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'code' => 'WO-EXC-001',
            'status' => WorkOrder::STATUS_IN_PROGRESS,
        ]);

        WorkOrderItem::create([
            'work_order_id' => $workOrder->id,
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'title' => 'Oil Change',
            'qty' => 1,
            'unit_price' => 200,
            'status' => WorkOrderItem::STATUS_COMPLETED,
        ]);

        // Seed an excess payment (350 > 200, e.g. from reduced/removed items after payment)
        Payment::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'work_order_id' => $workOrder->id,
            'type' => PaymentType::PAYMENT,
            'payment_method' => 'cash',
            'amount' => 350,
            'payment_date' => now()->toDateString(),
            'created_by' => $user->id,
        ]);

        $workOrder->refresh();
        $this->assertTrue($workOrder->hasExcessPayment());
        $this->assertSame(150.0, $workOrder->excess_paid);

        // Cannot complete/close while excess payment exists (complete endpoint + update request + model)
        $this->actingAs($user)
            ->post(route('work-orders.complete', $workOrder->id), [
                'exit_date' => now()->toDateString(),
            ])
            ->assertRedirect(route('work-orders.show', $workOrder->id))
            ->assertSessionHas('error', __('messages.cannot_complete_excess_payments'));

        $this->actingAs($user)
            ->put(route('work-orders.update', $workOrder->id), [
                'customer_id' => $customer->id,
                'vehicle_id' => $vehicle->id,
                'status' => WorkOrder::STATUS_DONE,
            ])
            ->assertSessionHasErrors(['status']);

        $this->assertFalse($workOrder->fresh()->markAsCompleted(now()));
        $this->assertNotSame(WorkOrder::STATUS_DONE, $workOrder->fresh()->status);

        // Refund cannot exceed total net cash paid
        $this->actingAs($user)
            ->post(route('work-orders.payments.store', $workOrder->id), [
                'type' => 'refund',
                'payment_method' => 'cash',
                'amount' => 400,
                'payment_date' => now()->toDateString(),
            ])
            ->assertRedirect()
            ->assertSessionHas('error', __('payments.amount_exceeds_paid'));

        // Refund the 150 excess to settle dues
        $this->actingAs($user)
            ->post(route('work-orders.payments.store', $workOrder->id), [
                'type' => 'refund',
                'payment_method' => 'cash',
                'amount' => 150,
                'payment_date' => now()->toDateString(),
            ])
            ->assertRedirect();

        $workOrder->refresh();
        $this->assertFalse($workOrder->hasExcessPayment());
        $this->assertSame(0.0, $workOrder->excess_paid);

        // Now closing/completing succeeds cleanly
        $this->actingAs($user)
            ->post(route('work-orders.complete', $workOrder->id), [
                'exit_date' => now()->toDateString(),
            ])
            ->assertRedirect();

        $this->assertSame(WorkOrder::STATUS_DONE, $workOrder->fresh()->status);
    }
}
