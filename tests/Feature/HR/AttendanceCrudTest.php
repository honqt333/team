<?php

declare(strict_types=1);

namespace Tests\Feature\HR;

use App\Models\Center;
use App\Models\Department;
use App\Models\HR\Attendance;
use App\Models\HR\Employee;
use App\Models\HR\JobTitle;
use App\Models\Nationality;
use App\Models\Tenant;
use App\Models\User;
use App\Support\Permissions;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class AttendanceCrudTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;

    protected Center $center;

    protected User $user;

    protected JobTitle $jobTitle;

    protected Department $department;

    protected Nationality $nationality;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();

        $this->tenant = Tenant::factory()->create(['status' => 'active']);
        $this->center = Center::factory()->create([
            'tenant_id' => $this->tenant->id,
            'is_active' => true,
        ]);

        app(PermissionRegistrar::class)->setPermissionsTeamId($this->tenant->id);

        $permissions = [
            Permissions::HR_ATTENDANCE_VIEW,
            Permissions::HR_ATTENDANCE_MANAGE,
        ];

        foreach ($permissions as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
        }

        $this->user = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'current_center_id' => $this->center->id,
        ]);

        $this->user->centers()->attach($this->center->id, ['tenant_id' => $this->tenant->id]);
        $this->user->givePermissionTo($permissions);

        $this->department = Department::create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'name_ar' => 'قسم المحاسبة',
            'name_en' => 'Accounting Department',
            'is_active' => true,
        ]);

        $this->jobTitle = JobTitle::create([
            'tenant_id' => $this->tenant->id,
            'name_ar' => 'محاسب عام',
            'name_en' => 'General Accountant',
            'is_active' => true,
        ]);

        $this->nationality = Nationality::firstOrCreate(
            ['name_ar' => 'سعودي'],
            ['name_en' => 'Saudi', 'is_active' => true]
        );
    }

    public function test_unauthorized_user_cannot_view_attendance(): void
    {
        $unauthorizedUser = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'current_center_id' => $this->center->id,
        ]);
        $unauthorizedUser->centers()->attach($this->center->id, ['tenant_id' => $this->tenant->id]);

        $response = $this->actingAs($unauthorizedUser)->get(route('app.hr.attendance.index'));

        $response->assertForbidden();
    }

    public function test_authorized_user_can_view_attendance_index(): void
    {
        Employee::create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'employee_number' => 'EMP-ATT-001',
            'name_ar' => 'سالم الحاضر',
            'name_en' => 'Salem Present',
            'phone' => '0501112233',
            'email' => 'salem@example.com',
            'gender' => 'male',
            'nationality_id' => $this->nationality->id,
            'job_title_id' => $this->jobTitle->id,
            'department_id' => $this->department->id,
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->user)->get(route('app.hr.attendance.index'));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('HR/Attendance/Index')
            ->has('employees', 1)
            ->where('employees.0.name_ar', 'سالم الحاضر')
            ->where('employees.0.employee_number', 'EMP-ATT-001')
            ->has('stats')
            ->where('stats.total', 1)
            ->has('filters')
        );
    }

    public function test_attendance_index_can_search_by_name_and_number(): void
    {
        Employee::create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'employee_number' => 'EMP-111',
            'name_ar' => 'ياسر',
            'name_en' => 'Yasser',
            'phone' => '0501111111',
            'email' => 'yasser@example.com',
            'gender' => 'male',
            'nationality_id' => $this->nationality->id,
            'status' => 'active',
        ]);

        Employee::create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'employee_number' => 'EMP-222',
            'name_ar' => 'طارق',
            'name_en' => 'Tariq',
            'phone' => '0502222222',
            'email' => 'tariq@example.com',
            'gender' => 'male',
            'nationality_id' => $this->nationality->id,
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->user)->get(route('app.hr.attendance.index', ['search' => 'ياسر']));
        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('HR/Attendance/Index')
            ->has('employees', 1)
            ->where('employees.0.name_ar', 'ياسر')
            ->where('stats.total', 2)
        );
    }

    public function test_attendance_index_can_filter_by_status(): void
    {
        $today = Carbon::today()->format('Y-m-d');

        $emp1 = Employee::create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'employee_number' => 'EMP-PRES',
            'name_ar' => 'موظف حاضر',
            'name_en' => 'Present Employee',
            'phone' => '0503333331',
            'email' => 'pres@example.com',
            'gender' => 'male',
            'nationality_id' => $this->nationality->id,
            'status' => 'active',
        ]);

        Attendance::create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'employee_id' => $emp1->id,
            'date' => $today,
            'status' => 'present',
            'check_in' => '08:00:00',
        ]);

        $emp2 = Employee::create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'employee_number' => 'EMP-ABS',
            'name_ar' => 'موظف غائب',
            'name_en' => 'Absent Employee',
            'phone' => '0503333332',
            'email' => 'abs@example.com',
            'gender' => 'male',
            'nationality_id' => $this->nationality->id,
            'status' => 'active',
        ]);

        $responsePresent = $this->actingAs($this->user)->get(route('app.hr.attendance.index', [
            'date' => $today,
            'status' => 'present',
        ]));
        $responsePresent->assertOk();
        $responsePresent->assertInertia(fn (Assert $page) => $page
            ->component('HR/Attendance/Index')
            ->has('employees', 1)
            ->where('employees.0.employee_number', 'EMP-PRES')
            ->where('stats.present', 1)
            ->where('stats.absent', 1)
        );

        $responseAbsent = $this->actingAs($this->user)->get(route('app.hr.attendance.index', [
            'date' => $today,
            'status' => 'absent',
        ]));
        $responseAbsent->assertOk();
        $responseAbsent->assertInertia(fn (Assert $page) => $page
            ->component('HR/Attendance/Index')
            ->has('employees', 1)
            ->where('employees.0.employee_number', 'EMP-ABS')
        );
    }

    public function test_attendance_is_strictly_scoped_to_current_center(): void
    {
        $otherCenter = Center::factory()->create([
            'tenant_id' => $this->tenant->id,
            'is_active' => true,
        ]);

        Employee::create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'employee_number' => 'EMP-CURR',
            'name_ar' => 'موظف فرعي',
            'name_en' => 'Current Branch Emp',
            'phone' => '0504444441',
            'email' => 'curr.branch@example.com',
            'gender' => 'male',
            'nationality_id' => $this->nationality->id,
            'status' => 'active',
        ]);

        Employee::create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $otherCenter->id,
            'employee_number' => 'EMP-OTHER',
            'name_ar' => 'موظف فرع آخر',
            'name_en' => 'Other Branch Emp',
            'phone' => '0504444442',
            'email' => 'other.branch@example.com',
            'gender' => 'male',
            'nationality_id' => $this->nationality->id,
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->user)->get(route('app.hr.attendance.index'));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('HR/Attendance/Index')
            ->has('employees', 1)
            ->where('employees.0.employee_number', 'EMP-CURR')
            ->where('stats.total', 1)
        );
    }

    public function test_authorized_user_can_check_in_and_update_attendance(): void
    {
        $today = Carbon::today()->format('Y-m-d');

        $emp = Employee::create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'employee_number' => 'EMP-CHECK',
            'name_ar' => 'علي التحضير',
            'name_en' => 'Ali Attendance',
            'phone' => '0505555551',
            'email' => 'ali@example.com',
            'gender' => 'male',
            'nationality_id' => $this->nationality->id,
            'status' => 'active',
        ]);

        // Check In
        $response = $this->actingAs($this->user)->post(route('app.hr.attendance.store'), [
            'employee_id' => $emp->id,
            'date' => $today,
            'status' => 'present',
            'check_in' => '08:30',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('hr_attendances', [
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'employee_id' => $emp->id,
            'date' => $today,
            'status' => 'present',
        ]);

        $attendance = Attendance::where('employee_id', $emp->id)->first();
        $this->assertNotNull($attendance);

        // Update / Check Out
        $responseUpdate = $this->actingAs($this->user)->put(route('app.hr.attendance.update', $attendance->id), [
            'status' => 'present',
            'check_out' => '17:00',
            'notes' => 'انصرف بالموعد',
        ]);

        $responseUpdate->assertRedirect();
        $this->assertEquals('17:00', Carbon::parse($attendance->fresh()->check_out)->format('H:i'));
        $this->assertEquals('انصرف بالموعد', $attendance->fresh()->notes);
    }
}
