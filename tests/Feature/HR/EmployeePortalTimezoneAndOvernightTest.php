<?php

declare(strict_types=1);

namespace Tests\Feature\HR;

use App\Models\Center;
use App\Models\HR\Attendance;
use App\Models\HR\AttendanceCorrectionRequest;
use App\Models\HR\AttendanceSettings;
use App\Models\HR\Employee;
use App\Models\HR\EmployeeShift;
use App\Models\HR\Shift;
use App\Models\Nationality;
use App\Models\Tenant;
use App\Models\User;
use App\Support\Permissions;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class EmployeePortalTimezoneAndOvernightTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    /**
     * @return array{tenant: Tenant, center: Center, user: User, employee: Employee, shift: Shift}
     */
    private function createPortalEmployee(
        string $timezone,
        string $shiftStart,
        string $shiftEnd,
        bool $isOvernight = false,
        ?int $weeklyDayOfWeek = null
    ): array {
        $tenant = Tenant::factory()->create([
            'status' => 'active',
            'timezone' => $timezone,
        ]);
        $center = Center::factory()->create(['tenant_id' => $tenant->id]);

        AttendanceSettings::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'geofence_enabled' => false,
            'grace_period_minutes' => 15,
            'overtime_enabled' => true,
        ]);

        $shift = Shift::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'name_ar' => 'وردية اختبار',
            'name_en' => 'Test Shift',
            'start_time' => $shiftStart,
            'end_time' => $shiftEnd,
            'is_overnight' => $isOvernight,
            'is_active' => true,
        ]);

        app(PermissionRegistrar::class)->setPermissionsTeamId($tenant->id);
        $employeeRole = Role::firstOrCreate([
            'name' => 'employee',
            'guard_name' => 'web',
            'tenant_id' => $tenant->id,
        ]);
        Permission::findOrCreate(Permissions::EMPLOYEE_PROFILE_VIEW, 'web');

        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'current_center_id' => $center->id,
            'two_factor_confirmed_at' => now(),
        ]);
        $user->centers()->attach($center->id, ['tenant_id' => $tenant->id]);
        $user->assignRole($employeeRole);
        $user->givePermissionTo(Permissions::EMPLOYEE_PROFILE_VIEW);

        $nationality = Nationality::firstOrCreate(
            ['code' => 'SA'],
            ['name_ar' => 'سعودي', 'name_en' => 'Saudi', 'is_active' => true]
        );

        $employee = Employee::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'user_id' => $user->id,
            'employee_number' => 'EMP-'.$tenant->id.'-'.uniqid(),
            'name_ar' => 'موظف بوابة',
            'name_en' => 'Portal Employee',
            'nationality_id' => $nationality->id,
            'hire_date' => '2025-01-01',
            'basic_salary' => 5000,
            'status' => 'active',
            'default_shift_id' => $weeklyDayOfWeek === null ? $shift->id : null,
        ]);

        if ($weeklyDayOfWeek !== null) {
            EmployeeShift::create([
                'tenant_id' => $tenant->id,
                'employee_id' => $employee->id,
                'shift_id' => $shift->id,
                'day_of_week' => $weeklyDayOfWeek,
                'date' => null,
            ]);
        }

        return compact('tenant', 'center', 'user', 'employee', 'shift');
    }

    public function test_riyadh_late_utc_evening_records_next_local_day_time_shift_and_lateness(): void
    {
        // 2026-11-14 23:30 UTC (Saturday) == 2026-11-15 02:30 Asia/Riyadh (Sunday, dayOfWeek = 0)
        Carbon::setTestNow(Carbon::parse('2026-11-14 23:30:00', 'UTC'));

        $env = $this->createPortalEmployee('Asia/Riyadh', '02:00:00', '10:00:00', false, 0);

        $this->actingAs($env['user'])
            ->post(route('employee.attendance.clock-in'))
            ->assertRedirect()
            ->assertSessionHas('success');

        $attendance = Attendance::withoutGlobalScopes()
            ->where('employee_id', $env['employee']->id)
            ->firstOrFail();

        $this->assertSame('2026-11-15', $attendance->date->format('Y-m-d'));
        $this->assertSame('02:30', $attendance->check_in?->format('H:i'));
        $this->assertSame($env['shift']->id, $env['employee']->getShiftForDate($attendance->date)?->id);
        $this->assertSame(30, $attendance->late_minutes);

        // Clock out later on the same Riyadh day (2026-11-15 07:00 UTC == 10:00 Riyadh)
        Carbon::setTestNow(Carbon::parse('2026-11-15 07:00:00', 'UTC'));

        $this->actingAs($env['user'])
            ->post(route('employee.attendance.clock-out'))
            ->assertRedirect()
            ->assertSessionHas('success');

        $attendance->refresh();
        $this->assertSame('10:00', $attendance->check_out?->format('H:i'));
        $this->assertSame(0, $attendance->early_leave_minutes);
    }

    public function test_new_york_early_utc_morning_records_previous_local_day_time_and_shift(): void
    {
        // 2026-11-15 03:30 UTC (Sunday) == 2026-11-14 22:30 America/New_York (Saturday, dayOfWeek = 6)
        Carbon::setTestNow(Carbon::parse('2026-11-15 03:30:00', 'UTC'));

        $env = $this->createPortalEmployee('America/New_York', '22:00:00', '06:00:00', true, 6);

        $this->actingAs($env['user'])
            ->post(route('employee.attendance.clock-in'))
            ->assertRedirect()
            ->assertSessionHas('success');

        $attendance = Attendance::withoutGlobalScopes()
            ->where('employee_id', $env['employee']->id)
            ->firstOrFail();

        $this->assertSame('2026-11-14', $attendance->date->format('Y-m-d'));
        $this->assertSame('22:30', $attendance->check_in?->format('H:i'));
        $this->assertSame($env['shift']->id, $env['employee']->getShiftForDate($attendance->date)?->id);
        $this->assertSame(30, $attendance->late_minutes);
    }

    public function test_overnight_shift_clock_out_closes_previous_day_record_and_calculates_hours_and_overtime(): void
    {
        // 23:00-07:00 overnight shift in Asia/Riyadh
        $env = $this->createPortalEmployee('Asia/Riyadh', '23:00:00', '07:00:00', true);

        // Clock in at 2026-11-14 23:00 Riyadh (20:00 UTC)
        Carbon::setTestNow(Carbon::parse('2026-11-14 20:00:00', 'UTC'));
        $this->actingAs($env['user'])
            ->post(route('employee.attendance.clock-in'))
            ->assertRedirect()
            ->assertSessionHas('success');

        // Clock out at 2026-11-15 07:30 Riyadh (04:30 UTC) -> 30 min overtime, 8.5 hours worked
        Carbon::setTestNow(Carbon::parse('2026-11-15 04:30:00', 'UTC'));
        $this->actingAs($env['user'])
            ->post(route('employee.attendance.clock-out'))
            ->assertRedirect()
            ->assertSessionHas('success');

        $attendance = Attendance::withoutGlobalScopes()
            ->where('employee_id', $env['employee']->id)
            ->firstOrFail();

        $this->assertSame('2026-11-14', $attendance->date->format('Y-m-d'));
        $this->assertSame('23:00', $attendance->check_in?->format('H:i'));
        $this->assertSame('07:30', $attendance->check_out?->format('H:i'));
        $this->assertSame(0, $attendance->late_minutes);
        $this->assertSame(0, $attendance->early_leave_minutes);
        $this->assertSame(30, $attendance->overtime_minutes);

        // Verify worked hours across midnight (23:00 -> 07:30 = 8.5 hours)
        $checkIn = Carbon::parse($attendance->check_in);
        $checkOut = Carbon::parse($attendance->check_out);

        if ($checkOut->lt($checkIn)) {
            $checkOut->addDay();
        }
        $this->assertSame(8.5, round($checkIn->diffInMinutes($checkOut) / 60, 1));
    }

    public function test_clock_out_security_and_isolation_guards(): void
    {
        $envA = $this->createPortalEmployee('Asia/Riyadh', '23:00:00', '07:00:00', true);
        $envB = $this->createPortalEmployee('Asia/Riyadh', '23:00:00', '07:00:00', true);

        // Create another employee in the same tenant & center with an open overnight shift yesterday
        $otherUserSameCenter = User::factory()->create([
            'tenant_id' => $envA['tenant']->id,
            'current_center_id' => $envA['center']->id,
            'two_factor_confirmed_at' => now(),
        ]);
        $otherEmpSameCenter = Employee::create([
            'tenant_id' => $envA['tenant']->id,
            'center_id' => $envA['center']->id,
            'user_id' => $otherUserSameCenter->id,
            'employee_number' => 'EMP-OTHER-1',
            'name_ar' => 'موظف آخر',
            'name_en' => 'Other Emp',
            'nationality_id' => $envA['employee']->nationality_id,
            'hire_date' => '2025-01-01',
            'basic_salary' => 5000,
            'status' => 'active',
            'default_shift_id' => $envA['shift']->id,
        ]);

        // Open overnight record for other employee in same center (2026-11-14)
        $otherOpen = Attendance::create([
            'tenant_id' => $envA['tenant']->id,
            'center_id' => $envA['center']->id,
            'employee_id' => $otherEmpSameCenter->id,
            'date' => '2026-11-14',
            'status' => 'present',
            'check_in' => Carbon::parse('23:00'),
        ]);

        // Open overnight record for envB (other tenant)
        $otherTenantOpen = Attendance::create([
            'tenant_id' => $envB['tenant']->id,
            'center_id' => $envB['center']->id,
            'employee_id' => $envB['employee']->id,
            'date' => '2026-11-14',
            'status' => 'present',
            'check_in' => Carbon::parse('23:00'),
        ]);

        // Old unclosed record (2 days old) for envA employee
        $oldOpen = Attendance::create([
            'tenant_id' => $envA['tenant']->id,
            'center_id' => $envA['center']->id,
            'employee_id' => $envA['employee']->id,
            'date' => '2026-11-13',
            'status' => 'present',
            'check_in' => Carbon::parse('23:00'),
        ]);

        // Record in another center for envA employee (not current center)
        $otherCenter = Center::factory()->create(['tenant_id' => $envA['tenant']->id]);
        $otherCenterOpen = Attendance::create([
            'tenant_id' => $envA['tenant']->id,
            'center_id' => $otherCenter->id,
            'employee_id' => $envA['employee']->id,
            'date' => '2026-11-14',
            'status' => 'present',
            'check_in' => Carbon::parse('23:00'),
        ]);

        // On 2026-11-15 07:00 Riyadh (04:00 UTC), envA employee has NO valid open record in their center for 2026-11-14 or 2026-11-15
        Carbon::setTestNow(Carbon::parse('2026-11-15 04:00:00', 'UTC'));
        $this->actingAs($envA['user'])
            ->post(route('employee.attendance.clock-out'))
            ->assertRedirect()
            ->assertSessionHas('error', __('hr.attendance.must_check_in_first'));

        // None of the other records were touched
        $this->assertNull($otherOpen->fresh()->check_out);
        $this->assertNull($otherTenantOpen->fresh()->check_out);
        $this->assertNull($oldOpen->fresh()->check_out);
        $this->assertNull($otherCenterOpen->fresh()->check_out);

        // Duplicate clock-out on same-day closed record is blocked
        Attendance::create([
            'tenant_id' => $envA['tenant']->id,
            'center_id' => $envA['center']->id,
            'employee_id' => $envA['employee']->id,
            'date' => '2026-11-15',
            'status' => 'present',
            'check_in' => Carbon::parse('08:00'),
            'check_out' => Carbon::parse('16:00'),
        ]);

        $this->actingAs($envA['user'])
            ->post(route('employee.attendance.clock-out'))
            ->assertRedirect()
            ->assertSessionHas('error', __('hr.attendance.already_checked_out'));
    }

    public function test_correction_request_validates_date_against_tenant_local_today(): void
    {
        // 2026-11-14 23:30 UTC == 2026-11-15 02:30 Asia/Riyadh
        Carbon::setTestNow(Carbon::parse('2026-11-14 23:30:00', 'UTC'));
        $riyadh = $this->createPortalEmployee('Asia/Riyadh', '08:00:00', '16:00:00');

        // Accepts Riyadh's local today (2026-11-15) even though UTC is 2026-11-14
        $this->actingAs($riyadh['user'])
            ->post(route('employee.attendance.corrections.store'), [
                'date' => '2026-11-15',
                'requested_check_in' => '08:05',
                'reason' => 'Forgot to clock in',
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('hr_attendance_correction_requests', [
            'employee_id' => $riyadh['employee']->id,
            'date' => '2026-11-15',
        ]);

        // Rejects Riyadh's future date (2026-11-16)
        $this->actingAs($riyadh['user'])
            ->post(route('employee.attendance.corrections.store'), [
                'date' => '2026-11-16',
                'requested_check_in' => '08:05',
                'reason' => 'Future date',
            ])
            ->assertSessionHasErrors('date');

        // 2026-11-15 03:30 UTC == 2026-11-14 22:30 America/New_York
        Carbon::setTestNow(Carbon::parse('2026-11-15 03:30:00', 'UTC'));
        $ny = $this->createPortalEmployee('America/New_York', '08:00:00', '16:00:00');

        // Rejects UTC today (2026-11-15) because it is tomorrow in New York
        $this->actingAs($ny['user'])
            ->post(route('employee.attendance.corrections.store'), [
                'date' => '2026-11-15',
                'requested_check_in' => '08:05',
                'reason' => 'UTC today is NY tomorrow',
            ])
            ->assertSessionHasErrors('date');

        // Accepts New York's local today (2026-11-14)
        $this->actingAs($ny['user'])
            ->post(route('employee.attendance.corrections.store'), [
                'date' => '2026-11-14',
                'requested_check_in' => '08:05',
                'reason' => 'NY today',
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertSame(1, AttendanceCorrectionRequest::withoutGlobalScopes()->where('employee_id', $ny['employee']->id)->count());
    }
}
