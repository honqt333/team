<?php

declare(strict_types=1);

namespace Tests\Feature\HR;

use App\Models\Center;
use App\Models\HR\Attendance;
use App\Models\HR\AttendanceCorrectionRequest;
use App\Models\HR\AttendanceSettings;
use App\Models\HR\Employee;
use App\Models\Nationality;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use App\Support\Permissions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class GeofenceAttendanceAndCorrectionTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    private Center $center;

    private User $hrManager;

    private User $employeeUser;

    private Employee $employee;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();

        $this->tenant = Tenant::factory()->create();
        $this->center = Center::factory()->create(['tenant_id' => $this->tenant->id]);
        $nationality = Nationality::firstOrCreate(
            ['name_ar' => 'سعودي'],
            ['name_en' => 'Saudi', 'is_active' => true]
        );

        app(PermissionRegistrar::class)->setPermissionsTeamId($this->tenant->id);
        Permission::firstOrCreate(['name' => Permissions::HR_ATTENDANCE_VIEW, 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => Permissions::HR_ATTENDANCE_MANAGE, 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => Permissions::EMPLOYEE_PROFILE_VIEW, 'guard_name' => 'web']);

        $this->hrManager = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'current_center_id' => $this->center->id,
        ]);
        $this->hrManager->centers()->attach($this->center->id, ['tenant_id' => $this->tenant->id]);
        $this->hrManager->givePermissionTo([
            Permissions::HR_ATTENDANCE_VIEW,
            Permissions::HR_ATTENDANCE_MANAGE,
        ]);

        $employeeRole = Role::firstOrCreate([
            'name' => 'employee',
            'guard_name' => 'web',
            'tenant_id' => $this->tenant->id,
        ]);

        $this->employeeUser = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'current_center_id' => $this->center->id,
        ]);
        $this->employeeUser->centers()->attach($this->center->id, ['tenant_id' => $this->tenant->id]);
        $this->employeeUser->assignRole($employeeRole);
        $this->employeeUser->givePermissionTo([Permissions::EMPLOYEE_PROFILE_VIEW]);

        $this->employee = Employee::create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'user_id' => $this->employeeUser->id,
            'employee_number' => 'EMP-GEO-01',
            'name_ar' => 'فني بوابة',
            'name_en' => 'Portal Tech',
            'phone' => '0505556677',
            'email' => 'portaltech@example.com',
            'gender' => 'male',
            'nationality_id' => $nationality->id,
            'status' => 'active',
        ]);

        // Configure branch geofence (Riyadh coordinates, 150m radius)
        $settings = AttendanceSettings::getForCenter($this->center->id);
        $settings->update([
            'geofence_enabled' => true,
            'geofence_latitude' => 24.7136000,
            'geofence_longitude' => 46.6753000,
            'geofence_radius_meters' => 150,
        ]);
    }

    public function test_geofence_blocks_outside_clock_in_and_allows_inside_radius(): void
    {
        // ~1.1 km away -> rejected
        $this->actingAs($this->employeeUser)
            ->post(route('employee.attendance.clock-in'), [
                'latitude' => 24.7236000,
                'longitude' => 46.6753000,
            ])
            ->assertSessionHasErrors('location');

        $this->assertDatabaseCount('hr_attendances', 0);

        // ~11 meters away -> allowed
        $this->actingAs($this->employeeUser)
            ->post(route('employee.attendance.clock-in'), [
                'latitude' => 24.7137000,
                'longitude' => 46.6753000,
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertDatabaseCount('hr_attendances', 1);
        $attendance = Attendance::firstOrFail();
        $this->assertNotNull($attendance->check_in);
    }

    public function test_employee_can_submit_correction_and_hr_manager_can_approve_updating_attendance(): void
    {
        $this->actingAs($this->employeeUser)
            ->post(route('employee.attendance.corrections.store'), [
                'date' => '2026-11-10',
                'requested_check_in' => '08:00',
                'requested_check_out' => '17:00',
                'reason' => 'نسيان تسجيل البصمة صباحاً',
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $correction = AttendanceCorrectionRequest::firstOrFail();
        $this->assertEquals(AttendanceCorrectionRequest::STATUS_PENDING, $correction->status);

        // Unauthorized employee cannot self-approve
        $this->actingAs($this->employeeUser)
            ->put(route('app.hr.attendance.corrections.approve', $correction->id))
            ->assertForbidden();

        // HR manager approves -> creates/updates Attendance record
        $this->actingAs($this->hrManager)
            ->put(route('app.hr.attendance.corrections.approve', $correction->id))
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertEquals(AttendanceCorrectionRequest::STATUS_APPROVED, $correction->fresh()->status);

        $attendance = Attendance::where('employee_id', $this->employee->id)
            ->whereDate('date', '2026-11-10')
            ->firstOrFail();
        $this->assertEquals('present', $attendance->status);
        $this->assertEquals('08:00', $attendance->check_in?->format('H:i'));
        $this->assertEquals('17:00', $attendance->check_out?->format('H:i'));
    }
}
