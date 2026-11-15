<?php

declare(strict_types=1);

namespace Tests\Feature\HR;

use App\Models\Center;
use App\Models\CompanyTransaction;
use App\Models\Customer;
use App\Models\HR\Attendance;
use App\Models\HR\AttendanceCorrectionRequest;
use App\Models\HR\AttendanceSettings;
use App\Models\HR\Employee;
use App\Models\HR\Shift;
use App\Models\IncomeCategory;
use App\Models\Invoice;
use App\Models\Nationality;
use App\Models\Tenant;
use App\Models\User;
use App\Support\Permissions;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class AttendanceTimeStorageVerificationTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function createTenantAndEmployee(string $timezone = 'Asia/Riyadh'): array
    {
        $tenant = Tenant::factory()->create([
            'status' => 'active',
            'timezone' => $timezone,
        ]);
        $center = Center::factory()->create([
            'tenant_id' => $tenant->id,
            'is_active' => true,
        ]);

        $manager = User::factory()->create([
            'tenant_id' => $tenant->id,
            'current_center_id' => $center->id,
            'two_factor_confirmed_at' => now(),
        ]);
        $manager->centers()->attach($center->id, ['tenant_id' => $tenant->id]);

        $empUser = User::factory()->create([
            'tenant_id' => $tenant->id,
            'current_center_id' => $center->id,
            'two_factor_confirmed_at' => now(),
        ]);
        $empUser->centers()->attach($center->id, ['tenant_id' => $tenant->id]);

        app(PermissionRegistrar::class)->setPermissionsTeamId($tenant->id);

        foreach ([
            Permissions::HR_ATTENDANCE_VIEW,
            Permissions::HR_ATTENDANCE_MANAGE,
            Permissions::EMPLOYEE_PROFILE_VIEW,
            Permissions::INVOICES_CREATE,
        ] as $perm) {
            Permission::findOrCreate($perm, 'web');
        }
        $manager->givePermissionTo([
            Permissions::HR_ATTENDANCE_VIEW,
            Permissions::HR_ATTENDANCE_MANAGE,
            Permissions::INVOICES_CREATE,
        ]);
        $empUser->givePermissionTo(Permissions::EMPLOYEE_PROFILE_VIEW);

        $nationality = Nationality::firstOrCreate(
            ['code' => 'SA'],
            ['name_ar' => 'سعودي', 'name_en' => 'Saudi', 'is_active' => true]
        );

        $employee = Employee::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'user_id' => $empUser->id,
            'employee_number' => 'EMP-TIME-'.uniqid(),
            'name_ar' => 'موظف اختبار الوقت',
            'name_en' => 'Time Verify Employee',
            'phone' => '0501112233',
            'email' => 'time-'.uniqid().'@example.test',
            'gender' => 'male',
            'nationality_id' => $nationality->id,
            'status' => 'active',
        ]);

        AttendanceSettings::getForCenter($center->id)->update(['geofence_enabled' => false]);

        return compact('tenant', 'center', 'manager', 'empUser', 'employee');
    }

    public function test_raw_sql_and_eloquent_storage_for_time_values_and_updates(): void
    {
        ['tenant' => $tenant, 'center' => $center, 'employee' => $employee] = $this->createTenantAndEmployee('Asia/Riyadh');

        $samples = ['08:30', '12:00', '23:55', '00:05', null];

        foreach ($samples as $idx => $timeValue) {
            $date = sprintf('2026-11-%02d', $idx + 1);

            $attendance = Attendance::create([
                'tenant_id' => $tenant->id,
                'center_id' => $center->id,
                'employee_id' => $employee->id,
                'date' => $date,
                'status' => 'present',
                'check_in' => $timeValue !== null ? Carbon::parse($timeValue) : null,
                'check_out' => null,
            ]);

            $raw = DB::table('hr_attendances')->where('id', $attendance->id)->value('check_in');
            $eloquent = $attendance->fresh();

            if ($timeValue === null) {
                $this->assertNull($raw);
                $this->assertNull($eloquent->check_in);
            } else {
                // Eloquent datetime:H:i cast stores 'Y-m-d H:i:s' via fromDateTime(),
                // and preserves exact H:i when read back through Eloquent.
                $this->assertSame($timeValue, Carbon::parse($raw)->format('H:i'));
                $this->assertSame($timeValue, $eloquent->check_in?->format('H:i'));
            }

            // Updating an unrelated field must not alter check_in or check_out
            $eloquent->update(['notes' => 'ملاحظة محدثة']);
            $rawAfterUpdate = DB::table('hr_attendances')->where('id', $attendance->id)->value('check_in');
            $this->assertSame($raw, $rawAfterUpdate);
            $this->assertSame(
                $timeValue,
                $eloquent->fresh()->check_in?->format('H:i')
            );
        }
    }

    public function test_attendance_correction_handles_partial_nulls_and_overnight_shifts_across_timezones(): void
    {
        foreach (['Asia/Riyadh', 'UTC', 'America/New_York'] as $tz) {
            ['tenant' => $tenant, 'center' => $center, 'manager' => $manager, 'employee' => $employee] = $this->createTenantAndEmployee($tz);

            $shift = Shift::create([
                'tenant_id' => $tenant->id,
                'center_id' => $center->id,
                'name' => 'Night Shift',
                'name_ar' => 'وردية ليلية',
                'start_time' => '22:00:00',
                'end_time' => '06:00:00',
                'is_overnight' => true,
                'is_active' => true,
            ]);
            $employee->update(['shift_id' => $shift->id]);

            // Seed existing attendance with 22:00 check_in and 05:30 check_out
            Attendance::create([
                'tenant_id' => $tenant->id,
                'center_id' => $center->id,
                'employee_id' => $employee->id,
                'date' => '2026-11-10',
                'status' => 'present',
                'check_in' => Carbon::parse('22:00'),
                'check_out' => Carbon::parse('05:30'),
            ]);

            // 1. Correct ONLY check_in (leave requested_check_out = null) -> check_out must remain 05:30
            $reqInOnly = AttendanceCorrectionRequest::create([
                'tenant_id' => $tenant->id,
                'center_id' => $center->id,
                'employee_id' => $employee->id,
                'date' => '2026-11-10',
                'requested_check_in' => '22:15',
                'requested_check_out' => null,
                'reason' => 'تصحيح وقت الدخول فقط',
                'status' => AttendanceCorrectionRequest::STATUS_PENDING,
            ]);

            $this->actingAs($manager)
                ->put(route('app.hr.attendance.corrections.approve', $reqInOnly->id))
                ->assertRedirect()
                ->assertSessionHasNoErrors();

            $att1 = Attendance::where('employee_id', $employee->id)->whereDate('date', '2026-11-10')->firstOrFail();
            $this->assertSame('22:15', $att1->check_in?->format('H:i'));
            $this->assertSame('05:30', $att1->check_out?->format('H:i'));

            // 2. Correct ONLY check_out (leave requested_check_in = null) -> check_in must remain 22:15
            $reqOutOnly = AttendanceCorrectionRequest::create([
                'tenant_id' => $tenant->id,
                'center_id' => $center->id,
                'employee_id' => $employee->id,
                'date' => '2026-11-10',
                'requested_check_in' => null,
                'requested_check_out' => '00:05',
                'reason' => 'تصحيح وقت الخروج بعد منتصف الليل',
                'status' => AttendanceCorrectionRequest::STATUS_PENDING,
            ]);

            $this->actingAs($manager)
                ->put(route('app.hr.attendance.corrections.approve', $reqOutOnly->id))
                ->assertRedirect()
                ->assertSessionHasNoErrors();

            $att2 = $att1->fresh();
            $this->assertSame('22:15', $att2->check_in?->format('H:i'));
            $this->assertSame('00:05', $att2->check_out?->format('H:i'));

            // 3. New day with ONLY check_in (check_out remains null, never Carbon::parse(null))
            $reqNewDay = AttendanceCorrectionRequest::create([
                'tenant_id' => $tenant->id,
                'center_id' => $center->id,
                'employee_id' => $employee->id,
                'date' => '2026-11-11',
                'requested_check_in' => '23:55',
                'requested_check_out' => null,
                'reason' => 'دخول فقط بدون خروج',
                'status' => AttendanceCorrectionRequest::STATUS_PENDING,
            ]);

            $this->actingAs($manager)
                ->put(route('app.hr.attendance.corrections.approve', $reqNewDay->id))
                ->assertRedirect()
                ->assertSessionHasNoErrors();

            $att3 = Attendance::where('employee_id', $employee->id)->whereDate('date', '2026-11-11')->firstOrFail();
            $this->assertSame('23:55', $att3->check_in?->format('H:i'));
            $this->assertNull($att3->check_out);
            $this->assertNull(DB::table('hr_attendances')->where('id', $att3->id)->value('check_out'));
        }
    }

    public function test_company_transaction_approved_at_stores_exact_date_across_timezones(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-14 23:30:00', 'UTC'));

        ['tenant' => $tenant, 'center' => $center, 'manager' => $manager] = $this->createTenantAndEmployee('Asia/Riyadh');

        $category = IncomeCategory::create([
            'tenant_id' => $tenant->id,
            'name' => 'إيرادات',
            'name_ar' => 'إيرادات',
            'name_en' => 'Revenue',
            'transaction_type' => 'revenue',
            'is_active' => true,
        ]);

        $customer = Customer::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'name' => 'عميل التحقق',
            'phone' => '0508889999',
            'tax_number' => '311111111111113',
        ]);

        $tx = CompanyTransaction::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'title' => 'معاملة تحقق التاريخ',
            'transaction_date' => '2026-11-10',
            'transaction_type' => 'revenue',
            'income_category_id' => $category->id,
            'amount' => 100.00,
            'is_taxable' => true,
            'tax_amount' => 15.00,
            'total_amount' => 115.00,
            'contact_type' => 'customer',
            'contact_id' => $customer->id,
            'status' => 'draft',
            'updated_by' => $manager->id,
        ]);

        $this->actingAs($manager)
            ->post(route('settings.company.transactions.approve', $tx->id), [
                'approved_at' => '2026-11-12',
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $rawApprovedAt = DB::table('company_transactions')->where('id', $tx->id)->value('approved_at');
        $tx->refresh();
        $invoice = Invoice::findOrFail($tx->invoice_id);

        $this->assertSame('2026-11-12', Carbon::parse($rawApprovedAt)->format('Y-m-d'));
        $this->assertSame('2026-11-12', $tx->approved_at?->format('Y-m-d'));
        $this->assertSame('2026-11-12', $invoice->issue_date?->format('Y-m-d'));
    }
}
