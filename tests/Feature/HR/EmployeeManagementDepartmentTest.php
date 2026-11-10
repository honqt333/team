<?php

declare(strict_types=1);

namespace Tests\Feature\HR;

use App\Models\Center;
use App\Models\Department;
use App\Models\HR\Employee;
use App\Models\HR\JobTitle;
use App\Models\Nationality;
use App\Models\Tenant;
use App\Models\User;
use App\Support\Permissions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class EmployeeManagementDepartmentTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;

    protected Center $center;

    protected User $tenantAdmin;

    protected Department $managementDept;

    protected JobTitle $jobTitle;

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
            Permissions::HR_EMPLOYEES_VIEW,
            Permissions::HR_EMPLOYEES_CREATE,
            Permissions::HR_EMPLOYEES_UPDATE,
            Permissions::HR_EMPLOYEES_DELETE,
        ];

        foreach ($permissions as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
        }

        $this->tenantAdmin = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'current_center_id' => $this->center->id,
        ]);

        $this->tenantAdmin->centers()->attach($this->center->id, ['tenant_id' => $this->tenant->id]);
        $this->tenantAdmin->givePermissionTo($permissions);

        $this->managementDept = Department::create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'name_ar' => 'الإدارة العامة',
            'name_en' => 'General Management',
            'is_active' => true,
        ]);

        $this->jobTitle = JobTitle::create([
            'tenant_id' => $this->tenant->id,
            'name_ar' => 'مدير مالي',
            'name_en' => 'Financial Manager',
            'is_active' => true,
        ]);

        $this->nationality = Nationality::firstOrCreate(
            ['name_ar' => 'سعودي'],
            ['name_en' => 'Saudi', 'is_active' => true]
        );
    }

    public function test_tenant_admin_without_super_admin_can_create_employee_in_management_department(): void
    {
        $payload = [
            'name_ar' => 'محمد الإداري',
            'name_en' => 'Mohammed Admin',
            'phone' => '0551122334',
            'email' => 'mohammed.admin@example.com',
            'gender' => 'male',
            'nationality_id' => $this->nationality->id,
            'job_title_id' => $this->jobTitle->id,
            'department_id' => $this->managementDept->id,
            'center_id' => null, // HQ management
        ];

        $response = $this->actingAs($this->tenantAdmin)
            ->post(route('app.hr.employees.store'), $payload);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('hr_employees', [
            'tenant_id' => $this->tenant->id,
            'name_ar' => 'محمد الإداري',
            'department_id' => $this->managementDept->id,
            'center_id' => null,
        ]);
    }

    public function test_tenant_admin_can_update_employee_department_to_management(): void
    {
        $otherDept = Department::create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'name_ar' => 'الورشة',
            'name_en' => 'Workshop',
            'is_active' => true,
        ]);

        $employee = Employee::create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'employee_number' => 'EMP-TEST-001',
            'name_ar' => 'خالد',
            'name_en' => 'Khaled',
            'phone' => '0559988776',
            'email' => 'khaled@example.com',
            'gender' => 'male',
            'nationality_id' => $this->nationality->id,
            'job_title_id' => $this->jobTitle->id,
            'department_id' => $otherDept->id,
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->tenantAdmin)
            ->put(route('app.hr.employees.update', $employee), [
                'name_ar' => 'خالد تم ترقيته',
                'name_en' => 'Khaled Promoted',
                'email' => 'khaled@example.com',
                'phone' => '0559988776',
                'department_id' => $this->managementDept->id,
                'status' => 'active',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('hr_employees', [
            'id' => $employee->id,
            'department_id' => $this->managementDept->id,
        ]);
    }

    public function test_management_employee_without_center_id_is_listed_for_tenant_users(): void
    {
        $hqEmployee = Employee::create([
            'tenant_id' => $this->tenant->id,
            'center_id' => null,
            'employee_number' => 'EMP-HQ-001',
            'name_ar' => 'عبدالله المدير العام',
            'name_en' => 'Abdullah GM',
            'phone' => '0554433221',
            'email' => 'abdullah.gm@example.com',
            'gender' => 'male',
            'nationality_id' => $this->nationality->id,
            'job_title_id' => $this->jobTitle->id,
            'department_id' => $this->managementDept->id,
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->tenantAdmin)
            ->get(route('app.hr.employees.index'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('HR/Employees/Index')
            ->has('employees.data', 1)
            ->where('employees.data.0.id', $hqEmployee->id)
        );
    }
}
