<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Center;
use App\Models\Department;
use App\Models\Service;
use App\Models\Tenant;
use App\Models\User;
use App\Support\Permissions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class DepartmentsCrudTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;

    protected Center $center;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::factory()->create(['status' => 'active']);
        $this->center = Center::factory()->create([
            'tenant_id' => $this->tenant->id,
            'is_active' => true,
        ]);

        app(PermissionRegistrar::class)->setPermissionsTeamId($this->tenant->id);

        $permissions = [
            Permissions::DEPARTMENTS_VIEW,
            Permissions::DEPARTMENTS_MANAGE,
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
    }

    public function test_unauthorized_user_cannot_view_departments(): void
    {
        $unauthorizedUser = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'current_center_id' => $this->center->id,
        ]);
        $unauthorizedUser->centers()->attach($this->center->id, ['tenant_id' => $this->tenant->id]);

        $response = $this->actingAs($unauthorizedUser)->get(route('departments.index'));

        $response->assertForbidden();
    }

    public function test_authorized_user_can_view_departments_index(): void
    {
        Department::create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'name_ar' => 'قسم الميكانيكا',
            'name_en' => 'Mechanics Department',
            'description' => 'إصلاحات عامة',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->user)->get(route('departments.index'));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Departments/Index')
            ->has('departments.data', 1)
            ->where('departments.data.0.name_ar', 'قسم الميكانيكا')
            ->where('departments.data.0.name_en', 'Mechanics Department')
            ->has('filters')
        );
    }

    public function test_departments_can_be_searched_by_arabic_and_english_name(): void
    {
        Department::create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'name_ar' => 'قسم الكهرباء',
            'name_en' => 'Electrical Department',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        Department::create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'name_ar' => 'قسم السمكرة والدهان',
            'name_en' => 'Bodywork Department',
            'is_active' => true,
            'sort_order' => 2,
        ]);

        $response = $this->actingAs($this->user)->get(route('departments.index', ['search' => 'الكهرباء']));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Departments/Index')
            ->has('departments.data', 1)
            ->where('departments.data.0.name_ar', 'قسم الكهرباء')
        );

        $responseEn = $this->actingAs($this->user)->get(route('departments.index', ['search' => 'Bodywork']));
        $responseEn->assertOk();
        $responseEn->assertInertia(fn (Assert $page) => $page
            ->component('Departments/Index')
            ->has('departments.data', 1)
            ->where('departments.data.0.name_en', 'Bodywork Department')
        );
    }

    public function test_departments_can_be_filtered_by_status(): void
    {
        Department::create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'name_ar' => 'قسم نشط',
            'name_en' => 'Active Dept',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        Department::create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'name_ar' => 'قسم معطل',
            'name_en' => 'Inactive Dept',
            'is_active' => false,
            'sort_order' => 2,
        ]);

        $responseActive = $this->actingAs($this->user)->get(route('departments.index', ['status' => 'active']));
        $responseActive->assertOk();
        $responseActive->assertInertia(fn (Assert $page) => $page
            ->component('Departments/Index')
            ->has('departments.data', 1)
            ->where('departments.data.0.name_ar', 'قسم نشط')
        );

        $responseInactive = $this->actingAs($this->user)->get(route('departments.index', ['status' => 'inactive']));
        $responseInactive->assertOk();
        $responseInactive->assertInertia(fn (Assert $page) => $page
            ->component('Departments/Index')
            ->has('departments.data', 1)
            ->where('departments.data.0.name_ar', 'قسم معطل')
        );
    }

    public function test_departments_can_be_sorted_by_whitelist_columns(): void
    {
        Department::create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'name_ar' => 'قسم أ',
            'name_en' => 'Dept A',
            'is_active' => true,
            'sort_order' => 10,
        ]);

        Department::create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'name_ar' => 'قسم ب',
            'name_en' => 'Dept B',
            'is_active' => true,
            'sort_order' => 5,
        ]);

        $response = $this->actingAs($this->user)->get(route('departments.index', [
            'sort' => 'sort_order',
            'direction' => 'asc',
        ]));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Departments/Index')
            ->has('departments.data', 2)
            ->where('departments.data.0.sort_order', 5)
            ->where('departments.data.1.sort_order', 10)
        );
    }

    public function test_departments_are_strictly_scoped_to_current_center(): void
    {
        $otherCenter = Center::factory()->create([
            'tenant_id' => $this->tenant->id,
            'is_active' => true,
        ]);

        Department::create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'name_ar' => 'قسم المركز الحالي',
            'name_en' => 'Current Center Dept',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        Department::create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $otherCenter->id,
            'name_ar' => 'قسم مركز آخر',
            'name_en' => 'Other Center Dept',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $response = $this->actingAs($this->user)->get(route('departments.index'));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Departments/Index')
            ->has('departments.data', 1)
            ->where('departments.data.0.name_ar', 'قسم المركز الحالي')
        );
    }

    public function test_authorized_user_can_toggle_department_status(): void
    {
        $dept = Department::create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'name_ar' => 'قسم الفحص',
            'name_en' => 'Inspection Dept',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $response = $this->actingAs($this->user)->patch("/app/departments/{$dept->id}/toggle-active");

        $response->assertRedirect();
        $this->assertFalse($dept->fresh()->is_active);

        $response2 = $this->actingAs($this->user)->patch("/app/departments/{$dept->id}/toggle-active");
        $response2->assertRedirect();
        $this->assertTrue($dept->fresh()->is_active);
    }

    public function test_cannot_delete_department_that_has_services(): void
    {
        $dept = Department::create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'name_ar' => 'قسم الصيانة',
            'name_en' => 'Maintenance Dept',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        Service::create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'department_id' => $dept->id,
            'name_ar' => 'تغيير زيت',
            'name_en' => 'Oil Change',
            'base_price' => 100,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->user)->delete(route('departments.destroy', $dept->id));

        $response->assertSessionHasErrors(['delete']);
        $this->assertDatabaseHas('departments', ['id' => $dept->id]);
    }
}
