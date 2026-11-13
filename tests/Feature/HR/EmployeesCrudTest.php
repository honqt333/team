<?php

declare(strict_types=1);

namespace Tests\Feature\HR;

use App\Models\Center;
use App\Models\Department;
use App\Models\HR\Employee;
use App\Models\HR\EmployeeDocument;
use App\Models\HR\JobTitle;
use App\Models\Nationality;
use App\Models\Tenant;
use App\Models\User;
use App\Support\Permissions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class EmployeesCrudTest extends TestCase
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
            Permissions::HR_EMPLOYEES_VIEW,
            Permissions::HR_EMPLOYEES_CREATE,
            Permissions::HR_EMPLOYEES_UPDATE,
            Permissions::HR_EMPLOYEES_DELETE,
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

    public function test_unauthorized_user_cannot_view_employees(): void
    {
        $unauthorizedUser = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'current_center_id' => $this->center->id,
        ]);
        $unauthorizedUser->centers()->attach($this->center->id, ['tenant_id' => $this->tenant->id]);

        $response = $this->actingAs($unauthorizedUser)->get(route('app.hr.employees.index'));

        $response->assertForbidden();
    }

    public function test_authorized_user_can_view_employees_index(): void
    {
        Employee::create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'employee_number' => 'EMP-001',
            'name_ar' => 'أحمد المهندس',
            'name_en' => 'Ahmad Engineer',
            'phone' => '0501234567',
            'email' => 'ahmad@example.com',
            'gender' => 'male',
            'nationality_id' => $this->nationality->id,
            'job_title_id' => $this->jobTitle->id,
            'department_id' => $this->department->id,
            'base_salary' => 8000,
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->user)->get(route('app.hr.employees.index'));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('HR/Employees/Index')
            ->has('employees.data', 1)
            ->where('employees.data.0.employee_number', 'EMP-001')
            ->where('employees.data.0.name_ar', 'أحمد المهندس')
            ->has('counts')
            ->has('filters')
        );
    }

    public function test_employees_can_be_searched_by_name_number_and_phone(): void
    {
        Employee::create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'employee_number' => 'EMP-100',
            'name_ar' => 'سعيد الكاتب',
            'name_en' => 'Saeed Clerk',
            'phone' => '0509988771',
            'email' => 'saeed@example.com',
            'gender' => 'male',
            'nationality_id' => $this->nationality->id,
            'job_title_id' => $this->jobTitle->id,
            'department_id' => $this->department->id,
            'status' => 'active',
        ]);

        Employee::create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'employee_number' => 'EMP-200',
            'name_ar' => 'فهد الفني',
            'name_en' => 'Fahad Tech',
            'phone' => '0501122334',
            'email' => 'fahad@example.com',
            'gender' => 'male',
            'nationality_id' => $this->nationality->id,
            'job_title_id' => $this->jobTitle->id,
            'department_id' => $this->department->id,
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->user)->get(route('app.hr.employees.index', ['search' => 'سعيد']));
        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('HR/Employees/Index')
            ->has('employees.data', 1)
            ->where('employees.data.0.employee_number', 'EMP-100')
        );

        $responsePhone = $this->actingAs($this->user)->get(route('app.hr.employees.index', ['search' => '0501122334']));
        $responsePhone->assertOk();
        $responsePhone->assertInertia(fn (Assert $page) => $page
            ->component('HR/Employees/Index')
            ->has('employees.data', 1)
            ->where('employees.data.0.employee_number', 'EMP-200')
        );
    }

    public function test_employees_can_be_filtered_by_status(): void
    {
        Employee::create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'employee_number' => 'EMP-ACT',
            'name_ar' => 'موظف نشط',
            'name_en' => 'Active Employee',
            'phone' => '0500000001',
            'email' => 'active@example.com',
            'gender' => 'male',
            'nationality_id' => $this->nationality->id,
            'status' => 'active',
        ]);

        Employee::create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'employee_number' => 'EMP-INA',
            'name_ar' => 'موظف موقوف',
            'name_en' => 'Inactive Employee',
            'phone' => '0500000002',
            'email' => 'inactive@example.com',
            'gender' => 'male',
            'nationality_id' => $this->nationality->id,
            'status' => 'inactive',
        ]);

        $responseActive = $this->actingAs($this->user)->get(route('app.hr.employees.index', ['status' => 'active']));
        $responseActive->assertOk();
        $responseActive->assertInertia(fn (Assert $page) => $page
            ->component('HR/Employees/Index')
            ->has('employees.data', 1)
            ->where('employees.data.0.employee_number', 'EMP-ACT')
        );

        $responseInactive = $this->actingAs($this->user)->get(route('app.hr.employees.index', ['status' => 'inactive']));
        $responseInactive->assertOk();
        $responseInactive->assertInertia(fn (Assert $page) => $page
            ->component('HR/Employees/Index')
            ->has('employees.data', 1)
            ->where('employees.data.0.employee_number', 'EMP-INA')
        );
    }

    public function test_employees_can_be_sorted_by_whitelist_columns(): void
    {
        Employee::create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'employee_number' => 'EMP-HIGH',
            'name_ar' => 'موظف أ',
            'name_en' => 'Employee A',
            'phone' => '0500000010',
            'email' => 'a@example.com',
            'gender' => 'male',
            'nationality_id' => $this->nationality->id,
            'base_salary' => 15000,
            'status' => 'active',
        ]);

        Employee::create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'employee_number' => 'EMP-LOW',
            'name_ar' => 'موظف ب',
            'name_en' => 'Employee B',
            'phone' => '0500000020',
            'email' => 'b@example.com',
            'gender' => 'male',
            'nationality_id' => $this->nationality->id,
            'base_salary' => 5000,
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->user)->get(route('app.hr.employees.index', [
            'sort' => 'base_salary',
            'direction' => 'asc',
        ]));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('HR/Employees/Index')
            ->has('employees.data', 2)
            ->where('employees.data.0.employee_number', 'EMP-LOW')
            ->where('employees.data.1.employee_number', 'EMP-HIGH')
        );
    }

    public function test_regular_tenant_user_cannot_see_other_center_employees(): void
    {
        $otherCenter = Center::factory()->create([
            'tenant_id' => $this->tenant->id,
            'is_active' => true,
        ]);

        Employee::create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'employee_number' => 'EMP-CURR',
            'name_ar' => 'موظف المركز الحالي',
            'name_en' => 'Current Center Emp',
            'phone' => '0501111111',
            'email' => 'curr@example.com',
            'gender' => 'male',
            'nationality_id' => $this->nationality->id,
            'status' => 'active',
        ]);

        Employee::create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $otherCenter->id,
            'employee_number' => 'EMP-OTHER',
            'name_ar' => 'موظف مركز آخر',
            'name_en' => 'Other Center Emp',
            'phone' => '0502222222',
            'email' => 'other@example.com',
            'gender' => 'male',
            'nationality_id' => $this->nationality->id,
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->user)->get(route('app.hr.employees.index'));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('HR/Employees/Index')
            ->has('employees.data', 1)
            ->where('employees.data.0.employee_number', 'EMP-CURR')
        );
    }

    public function test_employees_index_surfaces_id_expiry_status_and_unassigned_permissions_indicator(): void
    {
        $expiredDate = now()->subDays(5)->format('Y-m-d');
        $docExpiryDate = now()->addDays(15)->format('Y-m-d');

        // 1. Employee with national_id_expiry (expired) and no user/permissions
        $emp1 = Employee::create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'employee_number' => 'EMP-EXP-1',
            'name_ar' => 'أول موظف هوية منتهية',
            'name_en' => 'Expired ID Emp',
            'phone' => '0505550001',
            'email' => 'exp1@example.com',
            'gender' => 'male',
            'nationality_id' => $this->nationality->id,
            'national_id' => '1010101010',
            'national_id_expiry' => $expiredDate,
            'status' => 'active',
        ]);

        // 2. Employee with ID expiry in EmployeeDocument (expiring soon) and linked user WITH permissions
        $emp2 = Employee::create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'user_id' => $this->user->id,
            'employee_number' => 'EMP-DOC-2',
            'name_ar' => 'ثاني موظف وثيقة هوية',
            'name_en' => 'Doc ID Emp',
            'phone' => '0505550002',
            'email' => 'doc2@example.com',
            'gender' => 'male',
            'nationality_id' => $this->nationality->id,
            'status' => 'active',
        ]);

        EmployeeDocument::create([
            'tenant_id' => $this->tenant->id,
            'employee_id' => $emp2->id,
            'type' => 'id',
            'title' => 'بطاقة الهوية الوطنية',
            'file_path' => 'documents/id.pdf',
            'expiry_date' => $docExpiryDate,
        ]);

        $response = $this->actingAs($this->user)->get(route('app.hr.employees.index', [
            'sort' => 'employee_number',
            'direction' => 'asc',
        ]));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('HR/Employees/Index')
            ->has('employees.data', 2)
            ->where('employees.data.0.employee_number', 'EMP-DOC-2')
            ->where('employees.data.0.id_expiry_date', $docExpiryDate)
            ->where('employees.data.0.id_expiry_status', 'expiring_soon')
            ->where('employees.data.0.has_permissions_configured', true)
            ->where('employees.data.0.no_permissions_set', false)
            ->where('employees.data.1.employee_number', 'EMP-EXP-1')
            ->where('employees.data.1.id_expiry_date', $expiredDate)
            ->where('employees.data.1.id_expiry_status', 'expired')
            ->where('employees.data.1.has_permissions_configured', false)
            ->where('employees.data.1.no_permissions_set', true)
        );
    }
}
