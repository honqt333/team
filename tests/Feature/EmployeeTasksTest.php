<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Center;
use App\Models\Customer;
use App\Models\HR\Employee;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\WorkOrder;
use App\Models\WorkOrderItem;
use App\Models\WorkOrderLaborEntry;
use App\Models\WorkOrderPhoto;
use App\Support\Permissions;
use Database\Seeders\PermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class EmployeeTasksTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    private function createTechnicianEmployee(): array
    {
        $this->seed(PermissionsSeeder::class);

        $tenant = Tenant::factory()->create();
        $center = Center::factory()->create(['tenant_id' => $tenant->id]);

        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'current_center_id' => $center->id,
            'two_factor_confirmed_at' => now(),
        ]);
        $user->centers()->attach($center->id, ['tenant_id' => $tenant->id]);

        app(PermissionRegistrar::class)->setPermissionsTeamId($tenant->id);

        $employeeRole = Role::firstOrCreate([
            'tenant_id' => $tenant->id,
            'name' => 'employee',
            'guard_name' => 'web',
        ]);
        $employeeRole->givePermissionTo([
            Permissions::EMPLOYEE_PROFILE_VIEW,
            Permissions::EMPLOYEE_ATTENDANCE_VIEW,
            Permissions::EMPLOYEE_LEAVES_VIEW,
            Permissions::EMPLOYEE_PAYSLIPS_VIEW,
        ]);
        $user->assignRole($employeeRole);

        $employee = Employee::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'user_id' => $user->id,
            'employee_number' => 'EMP-'.uniqid(),
            'name_ar' => 'فني صيانة',
            'name_en' => 'Maintenance Tech',
            'status' => 'active',
        ]);

        $customer = Customer::factory()->create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
        ]);

        $vehicle = Vehicle::factory()->create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'customer_id' => $customer->id,
        ]);

        $workOrder = WorkOrder::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'status' => WorkOrder::STATUS_OPEN,
            'code' => 'WO-TEST-'.uniqid(),
        ]);

        $item = WorkOrderItem::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'work_order_id' => $workOrder->id,
            'title' => 'تغيير زيت وفلتر',
            'status' => WorkOrderItem::STATUS_PENDING,
            'qty' => 1,
            'unit_price' => 150,
            'final_unit_price' => 150,
            'line_total' => 150,
            'total' => 150,
        ]);

        $item->technicians()->attach($user->id);

        return [$tenant, $center, $user, $employee, $workOrder, $item];
    }

    public function test_employee_can_view_tasks_page(): void
    {
        [$tenant, $center, $user, $employee, $workOrder, $item] = $this->createTechnicianEmployee();

        $response = $this->actingAs($user)->get(route('employee.tasks'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('EmployeePortal/MyTasks')
            ->has('workOrders.data', 1)
            ->where('workOrders.data.0.id', $workOrder->id)
            ->has('counts')
            ->where('counts.pending', 1)
        );
    }

    public function test_employee_can_update_assigned_item_status(): void
    {
        [$tenant, $center, $user, $employee, $workOrder, $item] = $this->createTechnicianEmployee();

        $response = $this->actingAs($user)->postJson(
            route('employee.work-orders.items.update-status', [$workOrder, $item]),
            ['status' => WorkOrderItem::STATUS_IN_PROGRESS]
        );

        $response->assertOk();
        $response->assertJsonPath('item.status', WorkOrderItem::STATUS_IN_PROGRESS);
        $this->assertSame(WorkOrderItem::STATUS_IN_PROGRESS, $item->fresh()->status);
    }

    public function test_employee_can_start_pause_and_resume_timer(): void
    {
        [$tenant, $center, $user, $employee, $workOrder, $item] = $this->createTechnicianEmployee();

        // 1. Start Timer
        $response = $this->actingAs($user)->postJson(
            route('employee.work-orders.items.timer.start', [$workOrder, $item])
        );

        $response->assertOk();
        $freshItem = $item->fresh();
        $this->assertNotNull($freshItem->started_at);
        $this->assertNull($freshItem->paused_at);
        $this->assertSame(WorkOrderItem::STATUS_IN_PROGRESS, $freshItem->status);

        // Verify WorkOrderLaborEntry is created and running
        $entry = WorkOrderLaborEntry::where('work_order_item_id', $item->id)
            ->where('user_id', $user->id)
            ->first();
        $this->assertNotNull($entry);
        $this->assertSame(WorkOrderLaborEntry::STATUS_RUNNING, $entry->status);

        // 2. Pause Timer
        $response = $this->actingAs($user)->postJson(
            route('employee.work-orders.items.timer.pause', [$workOrder, $item])
        );

        $response->assertOk();
        $freshItem = $item->fresh();
        $this->assertNotNull($freshItem->paused_at);
        $this->assertSame(WorkOrderLaborEntry::STATUS_PAUSED, $entry->fresh()->status);

        // 3. Resume Timer
        $response = $this->actingAs($user)->postJson(
            route('employee.work-orders.items.timer.resume', [$workOrder, $item])
        );

        $response->assertOk();
        $freshItem = $item->fresh();
        $this->assertNull($freshItem->paused_at);
        $this->assertSame(WorkOrderLaborEntry::STATUS_RUNNING, $entry->fresh()->status);
    }

    public function test_employee_can_save_notes(): void
    {
        [$tenant, $center, $user, $employee, $workOrder, $item] = $this->createTechnicianEmployee();

        $response = $this->actingAs($user)->postJson(
            route('employee.work-orders.items.notes', [$workOrder, $item]),
            ['technician_notes' => 'تم فحص المحرك، مستوى الزيت كان منخفضاً جداً']
        );

        $response->assertOk();
        $this->assertSame('تم فحص المحرك، مستوى الزيت كان منخفضاً جداً', $item->fresh()->technician_notes);

        // Assert note reflected in work_order_item_notes table
        $this->assertDatabaseHas('work_order_item_notes', [
            'work_order_item_id' => $item->id,
            'work_order_id' => $workOrder->id,
            'user_id' => $user->id,
            'content' => 'تم فحص المحرك، مستوى الزيت كان منخفضاً جداً',
        ]);
    }

    public function test_employee_can_save_checklist(): void
    {
        [$tenant, $center, $user, $employee, $workOrder, $item] = $this->createTechnicianEmployee();

        $checklist = [
            ['text' => 'فحص سير التايمنج', 'completed' => true],
            ['text' => 'تغيير فلتر الهواء', 'completed' => false],
        ];

        $response = $this->actingAs($user)->postJson(
            route('employee.work-orders.items.checklist', [$workOrder, $item]),
            ['checklist' => $checklist]
        );

        $response->assertOk();
        $this->assertEquals($checklist, $item->fresh()->checklist);
    }

    public function test_employee_can_upload_and_delete_photos(): void
    {
        [$tenant, $center, $user, $employee, $workOrder, $item] = $this->createTechnicianEmployee();

        $file = UploadedFile::fake()->image('engine_part.jpg');

        $response = $this->actingAs($user)->post(
            route('employee.work-orders.items.media', [$workOrder, $item]),
            [
                'files' => [$file],
                'type' => 'before',
            ]
        );

        $response->assertOk();
        $this->assertDatabaseHas('work_order_photos', [
            'work_order_id' => $workOrder->id,
            'work_order_item_id' => $item->id,
            'type' => 'before',
        ]);

        $photo = WorkOrderPhoto::where('work_order_item_id', $item->id)->first();
        $this->assertNotNull($photo);

        // Delete photo
        $deleteResponse = $this->actingAs($user)->deleteJson(
            route('employee.work-orders.items.photos.delete', [$workOrder, $item, $photo])
        );

        $deleteResponse->assertOk();
        $this->assertDatabaseMissing('work_order_photos', ['id' => $photo->id]);
    }

    public function test_employee_can_update_priority(): void
    {
        [$tenant, $center, $user, $employee, $workOrder, $item] = $this->createTechnicianEmployee();

        $response = $this->actingAs($user)->postJson(
            route('employee.work-orders.items.priority', [$workOrder, $item]),
            ['priority' => 'urgent']
        );

        $response->assertOk();
        $this->assertSame('urgent', $item->fresh()->priority);
    }

    public function test_employee_can_complete_item(): void
    {
        [$tenant, $center, $user, $employee, $workOrder, $item] = $this->createTechnicianEmployee();

        $response = $this->actingAs($user)->postJson(
            route('employee.work-orders.items.complete', [$workOrder, $item]),
            ['confirm' => 'complete']
        );

        $response->assertOk();
        $freshItem = $item->fresh();
        $this->assertSame(WorkOrderItem::STATUS_COMPLETED, $freshItem->status);
        $this->assertNotNull($freshItem->completed_at);
    }

    public function test_multi_technician_completion_flow(): void
    {
        [$tenant, $center, $user1, $employee1, $workOrder, $item] = $this->createTechnicianEmployee();

        // Create second technician user and attach to the same item
        $user2 = User::factory()->create([
            'tenant_id' => $tenant->id,
            'current_center_id' => $center->id,
            'two_factor_confirmed_at' => now(),
        ]);
        $user2->centers()->attach($center->id, ['tenant_id' => $tenant->id]);
        $user2->assignRole('employee');

        Employee::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'user_id' => $user2->id,
            'employee_number' => 'EMP-'.uniqid(),
            'name_ar' => 'فني صيانة 2',
            'name_en' => 'Maintenance Tech 2',
            'status' => 'active',
        ]);

        $item->technicians()->attach($user2->id);

        // Tech 1 completes their part
        $response1 = $this->actingAs($user1)->postJson(
            route('employee.work-orders.items.complete', [$workOrder, $item]),
            ['confirm' => 'complete']
        );

        $response1->assertOk();
        $this->assertFalse($response1->json('all_completed'));

        // Item should STILL be in_progress (or not completed) because user2 hasn't finished
        $freshItem = $item->fresh();
        $this->assertSame(WorkOrderItem::STATUS_IN_PROGRESS, $freshItem->status);
        $this->assertNull($freshItem->completed_at);

        // User 1 pivot completed_at should be set
        $pivot1 = $freshItem->technicians()->where('users.id', $user1->id)->first()->pivot;
        $this->assertNotNull($pivot1->completed_at);

        // User 2 pivot completed_at should still be null
        $pivot2 = $freshItem->technicians()->where('users.id', $user2->id)->first()->pivot;
        $this->assertNull($pivot2->completed_at);

        // Now Tech 2 completes their part
        $response2 = $this->actingAs($user2)->postJson(
            route('employee.work-orders.items.complete', [$workOrder, $item]),
            ['confirm' => 'complete']
        );

        $response2->assertOk();
        $this->assertTrue($response2->json('all_completed'));

        // Now item should be COMPLETED
        $finalItem = $item->fresh();
        $this->assertSame(WorkOrderItem::STATUS_COMPLETED, $finalItem->status);
        $this->assertNotNull($finalItem->completed_at);
    }
}
