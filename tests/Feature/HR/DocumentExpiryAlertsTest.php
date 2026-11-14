<?php

declare(strict_types=1);

namespace Tests\Feature\HR;

use App\Models\Center;
use App\Models\HR\Employee;
use App\Models\HR\EmployeeDocument;
use App\Models\InternalNotification;
use App\Models\Nationality;
use App\Models\Tenant;
use App\Models\User;
use App\Support\Permissions;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class DocumentExpiryAlertsTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    private Tenant $otherTenant;

    private Center $center;

    private User $hrManager;

    private User $regularUser;

    private Employee $employee;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();

        $this->tenant = Tenant::factory()->create();
        $this->otherTenant = Tenant::factory()->create();
        $this->center = Center::factory()->create(['tenant_id' => $this->tenant->id]);
        $nationality = Nationality::firstOrCreate(
            ['name_ar' => 'سعودي'],
            ['name_en' => 'Saudi', 'is_active' => true]
        );

        app(PermissionRegistrar::class)->setPermissionsTeamId($this->tenant->id);
        Permission::firstOrCreate(['name' => Permissions::HR_EMPLOYEES_VIEW, 'guard_name' => 'web']);

        $this->hrManager = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'current_center_id' => $this->center->id,
            'is_active' => true,
        ]);
        $this->hrManager->givePermissionTo(Permissions::HR_EMPLOYEES_VIEW);

        $this->regularUser = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'current_center_id' => $this->center->id,
            'is_active' => true,
        ]);

        $this->employee = Employee::create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'employee_number' => 'EMP-EXP-01',
            'name_ar' => 'موظف تجريبي',
            'name_en' => 'Test Employee',
            'phone' => '0501112233',
            'email' => 'emp-exp-01@example.com',
            'gender' => 'male',
            'nationality_id' => $nationality->id,
            'status' => 'active',
            'national_id_expiry' => Carbon::today()->addDays(30)->toDateString(),
            'contract_end_date' => Carbon::today()->addDays(7)->toDateString(),
            'passport_expiry' => Carbon::today()->addDays(120)->toDateString(),
            'insurance_expiry' => null,
        ]);

        EmployeeDocument::create([
            'tenant_id' => $this->tenant->id,
            'employee_id' => $this->employee->id,
            'type' => 'license',
            'title' => 'Driving License',
            'file_path' => 'hr/docs/license.pdf',
            'expiry_date' => Carbon::today()->addDays(60)->toDateString(),
        ]);
    }

    public function test_dispatches_alerts_for_60_30_7_day_thresholds_to_hr_users_only(): void
    {
        $otherCenter = Center::factory()->create(['tenant_id' => $this->otherTenant->id]);
        $otherEmployee = Employee::create([
            'tenant_id' => $this->otherTenant->id,
            'center_id' => $otherCenter->id,
            'employee_number' => 'EMP-EXP-02',
            'name_ar' => 'موظف آخر',
            'name_en' => 'Other Employee',
            'phone' => '0501112244',
            'email' => 'emp-exp-02@example.com',
            'gender' => 'male',
            'nationality_id' => $this->employee->nationality_id,
            'status' => 'active',
            'national_id_expiry' => Carbon::today()->addDays(30)->toDateString(),
        ]);

        app(PermissionRegistrar::class)->setPermissionsTeamId($this->otherTenant->id);
        $otherHr = User::factory()->create([
            'tenant_id' => $this->otherTenant->id,
            'current_center_id' => $otherCenter->id,
            'is_active' => true,
        ]);
        $otherHr->givePermissionTo(Permissions::HR_EMPLOYEES_VIEW);

        $this->artisan('hr:send-expiry-alerts', ['--tenant' => $this->tenant->id])
            ->assertExitCode(0);

        $hrNotifications = InternalNotification::withoutGlobalScopes()
            ->where('tenant_id', $this->tenant->id)
            ->where('user_id', $this->hrManager->id)
            ->where('type', 'hr.expiry_alert')
            ->get();

        $this->assertCount(3, $hrNotifications);
        $fields = $hrNotifications->pluck('data.field')->sort()->values()->all();
        $this->assertEquals(['contract_end_date', 'document:1', 'national_id_expiry'], $fields);

        $this->assertEquals(
            0,
            InternalNotification::withoutGlobalScopes()
                ->where('user_id', $this->regularUser->id)
                ->where('type', 'hr.expiry_alert')
                ->count()
        );

        $this->assertEquals(
            0,
            InternalNotification::withoutGlobalScopes()
                ->where('tenant_id', $this->otherTenant->id)
                ->where('user_id', $otherHr->id)
                ->where('type', 'hr.expiry_alert')
                ->count()
        );
    }

    public function test_alerts_are_deduplicated_on_repeat_runs_and_skip_inactive_employees(): void
    {
        $inactiveEmployee = Employee::create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'employee_number' => 'EMP-EXP-03',
            'name_ar' => 'موظف منتهي',
            'name_en' => 'Inactive Employee',
            'phone' => '0501112255',
            'email' => 'emp-exp-03@example.com',
            'gender' => 'male',
            'nationality_id' => $this->employee->nationality_id,
            'status' => 'terminated',
            'national_id_expiry' => Carbon::today()->addDays(7)->toDateString(),
        ]);

        $this->artisan('hr:send-expiry-alerts')->assertExitCode(0);
        $this->artisan('hr:send-expiry-alerts')->assertExitCode(0);

        $count = InternalNotification::withoutGlobalScopes()
            ->where('tenant_id', $this->tenant->id)
            ->where('user_id', $this->hrManager->id)
            ->where('type', 'hr.expiry_alert')
            ->count();

        $this->assertEquals(3, $count);
        $this->assertEquals(
            0,
            InternalNotification::withoutGlobalScopes()
                ->where('data->employee_id', $inactiveEmployee->id)
                ->count()
        );
    }
}
