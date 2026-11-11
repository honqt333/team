<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Center;
use App\Models\Customer;
use App\Models\Payment;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\WorkOrder;
use App\Models\WorkOrderItem;
use App\Support\Permissions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class DashboardEnhancementsTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_exposes_checked_out_due_active_vehicles_and_working_technicians(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        foreach ([
            Permissions::INVOICES_VIEW,
            Permissions::WORK_ORDERS_VIEW,
            Permissions::WORK_ORDERS_CREATE,
            Permissions::INVENTORY_VIEW,
        ] as $perm) {
            Permission::findOrCreate($perm, 'web');
        }

        $tenant = Tenant::create([
            'name' => 'Dashboard Workshop',
            'slug' => 'dashboard-workshop',
            'country' => 'SA',
            'currency' => 'SAR',
            'timezone' => 'Asia/Riyadh',
            'is_active' => true,
        ]);

        $center = Center::create([
            'tenant_id' => $tenant->id,
            'name' => 'Main Branch',
            'slug' => 'main-branch',
            'is_active' => true,
        ]);

        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'current_center_id' => $center->id,
            'is_active' => true,
        ]);
        $user->centers()->attach($center->id, ['tenant_id' => $tenant->id]);

        app(PermissionRegistrar::class)->setPermissionsTeamId($tenant->id);
        $user->givePermissionTo([
            Permissions::INVOICES_VIEW,
            Permissions::WORK_ORDERS_VIEW,
            Permissions::WORK_ORDERS_CREATE,
            Permissions::INVENTORY_VIEW,
        ]);

        $customer = Customer::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'type' => 'individual',
            'name' => 'Saleh Customer',
            'phone' => '0551234567',
            'whatsapp' => '966551234567',
        ]);

        $vehicle = Vehicle::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'customer_id' => $customer->id,
            'plate_number' => 'KSA 1122',
        ]);

        // 1. Done work order with partial payment -> counted in checkedOutPaymentDue (300 - 100 = 200)
        $doneOrder = WorkOrder::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'code' => 'WO-DONE-DUE',
            'status' => WorkOrder::STATUS_OPEN,
            'entry_date' => now()->toDateString(),
            'tax_enabled_snapshot' => false,
        ]);

        WorkOrderItem::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'work_order_id' => $doneOrder->id,
            'title' => 'Inspection Service',
            'qty' => 1,
            'unit_price' => 300.00,
            'status' => WorkOrderItem::STATUS_COMPLETED,
        ]);

        $doneOrder->status = WorkOrder::STATUS_DONE;
        $doneOrder->save();

        Payment::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'work_order_id' => $doneOrder->id,
            'amount' => 100.00,
            'payment_method' => 'cash',
            'payment_date' => now(),
            'type' => 'payment',
            'received_by' => $user->id,
        ]);

        // 2. Active work order with 1 completed and 1 in_progress item assigned to user
        $activeOrder = WorkOrder::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'code' => 'WO-ACTIVE-1',
            'status' => WorkOrder::STATUS_IN_PROGRESS,
            'entry_date' => now()->toDateString(),
            'total_incl_tax' => 500.00,
        ]);

        WorkOrderItem::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'work_order_id' => $activeOrder->id,
            'title' => 'Oil Change',
            'qty' => 1,
            'unit_price' => 150,
            'status' => WorkOrderItem::STATUS_COMPLETED,
        ]);

        $inProgressItem = WorkOrderItem::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'work_order_id' => $activeOrder->id,
            'title' => 'Brake Pad Replacement',
            'qty' => 1,
            'unit_price' => 350,
            'status' => WorkOrderItem::STATUS_IN_PROGRESS,
        ]);

        $inProgressItem->technicians()->attach($user->id, [
            'assigned_at' => now(),
            'share' => 100,
        ]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->where('checkedOutPaymentDue.count', 1)
            ->where('checkedOutPaymentDue.amount', 200)
            ->has('activeWorkshopVehicles', 1)
            ->where('activeWorkshopVehicles.0.code', 'WO-ACTIVE-1')
            ->where('activeWorkshopVehicles.0.progress_percent', 50)
            ->has('workingTechniciansNow', 1)
            ->where('workingTechniciansNow.0.work_order_code', 'WO-ACTIVE-1')
        );
    }
}
