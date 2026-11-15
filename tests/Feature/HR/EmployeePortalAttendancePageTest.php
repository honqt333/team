<?php

declare(strict_types=1);

namespace Tests\Feature\HR;

use App\Models\Center;
use App\Models\HR\Attendance;
use App\Models\HR\AttendanceSettings;
use App\Models\HR\Employee;
use App\Models\HR\Shift;
use App\Models\Nationality;
use App\Models\Tenant;
use App\Models\User;
use App\Support\Permissions;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class EmployeePortalAttendancePageTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_employee_portal_attendance_page_loads_with_resolved_shift_and_tenant_isolation(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-15 08:00:00', 'UTC'));

        $tenant = Tenant::factory()->create(['status' => 'active', 'timezone' => 'Asia/Riyadh']);
        $center = Center::factory()->create(['tenant_id' => $tenant->id]);
        $otherCenter = Center::factory()->create(['tenant_id' => $tenant->id]);

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
            'name_ar' => 'الوردية الصباحية',
            'name_en' => 'Morning Shift',
            'start_time' => '08:00:00',
            'end_time' => '16:00:00',
            'is_overnight' => false,
            'is_active' => true,
        ]);

        app(PermissionRegistrar::class)->setPermissionsTeamId($tenant->id);
        $role = Role::firstOrCreate(['name' => 'employee', 'guard_name' => 'web', 'tenant_id' => $tenant->id]);
        Permission::findOrCreate(Permissions::EMPLOYEE_PROFILE_VIEW, 'web');

        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'current_center_id' => $center->id,
            'two_factor_confirmed_at' => now(),
        ]);
        $user->centers()->attach($center->id, ['tenant_id' => $tenant->id]);
        $user->assignRole($role);
        $user->givePermissionTo(Permissions::EMPLOYEE_PROFILE_VIEW);

        $nationality = Nationality::firstOrCreate(
            ['code' => 'SA'],
            ['name_ar' => 'سعودي', 'name_en' => 'Saudi', 'is_active' => true]
        );

        $employee = Employee::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'user_id' => $user->id,
            'employee_number' => 'EMP-PAGE-1',
            'name_ar' => 'موظف الصفحة',
            'name_en' => 'Page Employee',
            'nationality_id' => $nationality->id,
            'hire_date' => '2025-01-01',
            'basic_salary' => 5000,
            'status' => 'active',
            'default_shift_id' => $shift->id,
        ]);

        // Own attendance in current center
        Attendance::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'employee_id' => $employee->id,
            'date' => '2026-11-15',
            'status' => 'present',
            'check_in' => Carbon::parse('08:00'),
            'check_out' => Carbon::parse('16:00'),
        ]);

        // Other employee's attendance in same center (must not appear)
        $otherEmp = Employee::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'employee_number' => 'EMP-OTHER-PAGE',
            'name_ar' => 'موظف آخر',
            'name_en' => 'Other',
            'nationality_id' => $nationality->id,
            'hire_date' => '2025-01-01',
            'basic_salary' => 5000,
            'status' => 'active',
        ]);
        Attendance::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'employee_id' => $otherEmp->id,
            'date' => '2026-11-15',
            'status' => 'present',
            'check_in' => Carbon::parse('09:00'),
        ]);

        // Own historical record in another center (must not leak)
        Attendance::create([
            'tenant_id' => $tenant->id,
            'center_id' => $otherCenter->id,
            'employee_id' => $employee->id,
            'date' => '2026-11-14',
            'status' => 'present',
            'check_in' => Carbon::parse('08:00'),
        ]);

        $this->actingAs($user)
            ->get(route('employee.attendance', ['month' => '2026-11']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('EmployeePortal/Attendance')
                ->has('attendance', 1)
                ->where('attendance.0.shift.id', $shift->id)
                ->where('attendance.0.shift.name_ar', 'الوردية الصباحية')
                ->where('summary.total_hours', 8)
            );
    }

    public function test_overnight_clock_out_selection_priority_and_non_overnight_rejection(): void
    {
        $tenant = Tenant::factory()->create(['status' => 'active', 'timezone' => 'Asia/Riyadh']);
        $center = Center::factory()->create(['tenant_id' => $tenant->id]);

        AttendanceSettings::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'geofence_enabled' => false,
            'grace_period_minutes' => 15,
            'overtime_enabled' => true,
        ]);

        $dayShift = Shift::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'name_ar' => 'نهارية',
            'name_en' => 'Day',
            'start_time' => '08:00:00',
            'end_time' => '16:00:00',
            'is_overnight' => false,
            'is_active' => true,
        ]);

        $nightShift = Shift::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'name_ar' => 'ليلية',
            'name_en' => 'Night',
            'start_time' => '23:00:00',
            'end_time' => '07:00:00',
            'is_overnight' => true,
            'is_active' => true,
        ]);

        app(PermissionRegistrar::class)->setPermissionsTeamId($tenant->id);
        $role = Role::firstOrCreate(['name' => 'employee', 'guard_name' => 'web', 'tenant_id' => $tenant->id]);
        Permission::findOrCreate(Permissions::EMPLOYEE_PROFILE_VIEW, 'web');

        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'current_center_id' => $center->id,
            'two_factor_confirmed_at' => now(),
        ]);
        $user->centers()->attach($center->id, ['tenant_id' => $tenant->id]);
        $user->assignRole($role);
        $user->givePermissionTo(Permissions::EMPLOYEE_PROFILE_VIEW);

        $nationality = Nationality::firstOrCreate(
            ['code' => 'SA'],
            ['name_ar' => 'سعودي', 'name_en' => 'Saudi', 'is_active' => true]
        );

        $employee = Employee::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'user_id' => $user->id,
            'employee_number' => 'EMP-PRIO-1',
            'name_ar' => 'موظف أولوية',
            'name_en' => 'Priority Emp',
            'nationality_id' => $nationality->id,
            'hire_date' => '2025-01-01',
            'basic_salary' => 5000,
            'status' => 'active',
            'default_shift_id' => $dayShift->id,
        ]);

        // Case 1: Yesterday open but NOT overnight shift -> clockOut rejected
        $yesterdayNonOvernight = Attendance::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'employee_id' => $employee->id,
            'date' => '2026-11-14',
            'status' => 'present',
            'check_in' => Carbon::parse('08:00'),
        ]);

        Carbon::setTestNow(Carbon::parse('2026-11-15 04:00:00', 'UTC')); // 07:00 Riyadh
        $this->actingAs($user)
            ->post(route('employee.attendance.clock-out'))
            ->assertRedirect()
            ->assertSessionHas('error', __('hr.attendance.must_check_in_first'));
        $this->assertNull($yesterdayNonOvernight->fresh()->check_out);

        // Case 2: Yesterday overnight open AND today open -> closes today's record, leaves yesterday untouched
        $employee->update(['default_shift_id' => $nightShift->id]);
        $todayOpen = Attendance::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'employee_id' => $employee->id,
            'date' => '2026-11-15',
            'status' => 'present',
            'check_in' => Carbon::parse('06:30'),
        ]);

        $this->actingAs($user)
            ->post(route('employee.attendance.clock-out'))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSame('07:00', $todayOpen->fresh()->check_out?->format('H:i'));
        $this->assertNull($yesterdayNonOvernight->fresh()->check_out);

        // Case 3: Today is closed AND yesterday overnight is open -> already_checked_out, leaves yesterday untouched
        $this->actingAs($user)
            ->post(route('employee.attendance.clock-out'))
            ->assertRedirect()
            ->assertSessionHas('error', __('hr.attendance.already_checked_out'));
        $this->assertNull($yesterdayNonOvernight->fresh()->check_out);
    }

    public function test_overnight_duration_and_boundary_calculations(): void
    {
        $tenant = Tenant::factory()->create(['status' => 'active', 'timezone' => 'Asia/Riyadh']);
        $center = Center::factory()->create(['tenant_id' => $tenant->id]);
        AttendanceSettings::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'geofence_enabled' => false,
            'grace_period_minutes' => 15,
            'overtime_enabled' => true,
        ]);

        $nationality = Nationality::firstOrCreate(
            ['code' => 'SA'],
            ['name_ar' => 'سعودي', 'name_en' => 'Saudi', 'is_active' => true]
        );

        $cases = [
            ['23:00:00', '07:00:00', '23:00', '07:00', 0, 0, 0],
            ['23:55:00', '00:05:00', '23:55', '00:05', 0, 0, 0],
            ['22:00:00', '06:00:00', '22:00', '06:00', 0, 0, 0],
            ['23:00:00', '07:00:00', '23:00', '07:45', 0, 0, 45],
            ['23:00:00', '07:00:00', '23:00', '23:15', 0, 465, 0],
        ];

        foreach ($cases as $idx => [$shiftStart, $shiftEnd, $in, $out, $expLate, $expEarly, $expOvertime]) {
            $shift = Shift::create([
                'tenant_id' => $tenant->id,
                'center_id' => $center->id,
                'name_ar' => 'ليلية '.$idx,
                'name_en' => 'Night '.$idx,
                'start_time' => $shiftStart,
                'end_time' => $shiftEnd,
                'is_overnight' => true,
                'is_active' => true,
            ]);

            $emp = Employee::create([
                'tenant_id' => $tenant->id,
                'center_id' => $center->id,
                'employee_number' => 'EMP-DUR-'.$idx,
                'name_ar' => 'موظف '.$idx,
                'name_en' => 'Emp '.$idx,
                'nationality_id' => $nationality->id,
                'hire_date' => '2025-01-01',
                'basic_salary' => 5000,
                'status' => 'active',
                'default_shift_id' => $shift->id,
            ]);

            $att = Attendance::create([
                'tenant_id' => $tenant->id,
                'center_id' => $center->id,
                'employee_id' => $emp->id,
                'date' => '2026-11-14',
                'status' => 'present',
                'check_in' => Carbon::parse($in),
                'check_out' => Carbon::parse($out),
            ]);
            $att->calculateTimes();
            $att->save();

            $this->assertSame($expLate, $att->late_minutes, "Case {$idx} late_minutes");
            $this->assertSame($expEarly, $att->early_leave_minutes, "Case {$idx} early_leave_minutes");
            $this->assertSame($expOvertime, $att->overtime_minutes, "Case {$idx} overtime_minutes");
            $this->assertGreaterThanOrEqual(0, $att->late_minutes);
            $this->assertGreaterThanOrEqual(0, $att->early_leave_minutes);
            $this->assertGreaterThanOrEqual(0, $att->overtime_minutes);
        }
    }
}
