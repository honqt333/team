<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Center;
use App\Models\Department;
use App\Models\Service;
use App\Models\Setting;
use App\Models\Tenant;
use App\Models\User;
use App\Models\VehicleConditionCategory;
use App\Models\VehicleConditionItem;
use App\Support\Permissions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class ServicesIndexCrudTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;

    protected Center $center;

    protected Center $otherCenter;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::factory()->create(['status' => 'active']);
        $this->center = Center::factory()->create([
            'tenant_id' => $this->tenant->id,
            'is_active' => true,
        ]);
        $this->otherCenter = Center::factory()->create([
            'tenant_id' => $this->tenant->id,
            'is_active' => true,
        ]);

        app(PermissionRegistrar::class)->setPermissionsTeamId($this->tenant->id);

        $permissions = [
            Permissions::SERVICES_VIEW,
            Permissions::SERVICES_CREATE,
            Permissions::SERVICES_UPDATE,
            Permissions::SERVICES_DELETE,
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

    public function test_unauthorized_user_cannot_view_services(): void
    {
        $unauthorizedUser = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'current_center_id' => $this->center->id,
        ]);
        $unauthorizedUser->centers()->attach($this->center->id, ['tenant_id' => $this->tenant->id]);

        $response = $this->actingAs($unauthorizedUser)->get(route('services.index'));

        $response->assertForbidden();
    }

    public function test_authorized_user_can_view_services_index(): void
    {
        $department = Department::create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'name_ar' => 'قسم الميكانيكا',
            'name_en' => 'Mechanics Dept',
            'is_active' => true,
        ]);

        Service::create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'department_id' => $department->id,
            'name_ar' => 'تغيير زيت',
            'name_en' => 'Oil Change',
            'base_price' => 150,
            'type' => Service::TYPE_INTERNAL,
            'is_active' => true,
        ]);

        Service::create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'department_id' => null,
            'name_ar' => 'خدمة عامة',
            'name_en' => 'General Service',
            'base_price' => 200,
            'type' => Service::TYPE_INTERNAL,
            'is_active' => true,
        ]);

        Service::create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'name_ar' => 'باقة الصيانة الدورية',
            'name_en' => 'Periodic Maintenance Package',
            'base_price' => 500,
            'type' => Service::TYPE_PACKAGE,
            'is_active' => true,
        ]);

        $category = VehicleConditionCategory::create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'name_ar' => 'الهيكل الخارجي',
            'name_en' => 'Exterior Body',
            'is_active' => true,
        ]);

        VehicleConditionItem::create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'category_id' => $category->id,
            'name_ar' => 'الصدام الأمامي',
            'name_en' => 'Front Bumper',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->user)->get(route('services.index'));

        $response->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Services/Index')
                ->has('departments', 1)
                ->where('departments.0.name_ar', 'قسم الميكانيكا')
                ->has('unassignedServices', 1)
                ->where('unassignedServices.0.name_ar', 'خدمة عامة')
                ->has('packages', 1)
                ->where('packages.0.name_ar', 'باقة الصيانة الدورية')
                ->has('conditionCategories', 1)
                ->where('conditionCategories.0.name_ar', 'الهيكل الخارجي')
            );
    }

    public function test_services_and_packages_are_center_scoped(): void
    {
        // Service in current center
        Service::create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'name_ar' => 'خدمة فرع الرياض',
            'name_en' => 'Riyadh Service',
            'base_price' => 100,
            'type' => Service::TYPE_INTERNAL,
            'is_active' => true,
        ]);

        // Service in another center
        Service::create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->otherCenter->id,
            'name_ar' => 'خدمة فرع جدة',
            'name_en' => 'Jeddah Service',
            'base_price' => 120,
            'type' => Service::TYPE_INTERNAL,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->user)->get(route('services.index'));

        $response->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Services/Index')
                ->has('unassignedServices', 1)
                ->where('unassignedServices.0.name_ar', 'خدمة فرع الرياض')
                ->has('otherBranchesServices', 1)
                ->where('otherBranchesServices.0.name_ar', 'خدمة فرع جدة')
            );
    }

    public function test_authorized_user_can_toggle_service_active_status(): void
    {
        $service = Service::create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'name_ar' => 'فحص كمبيوتر',
            'name_en' => 'Computer Diagnostic',
            'base_price' => 100,
            'type' => Service::TYPE_INTERNAL,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->user)
            ->patch("/app/services/{$service->id}/toggle-active");

        $response->assertRedirect();
        $this->assertFalse($service->fresh()->is_active);

        // Toggle back
        $response = $this->actingAs($this->user)
            ->patch("/app/services/{$service->id}/toggle-active");

        $response->assertRedirect();
        $this->assertTrue($service->fresh()->is_active);
    }

    public function test_authorized_user_can_toggle_inspections_setting(): void
    {
        $response = $this->actingAs($this->user)
            ->post('/app/services/toggle-inspections-setting');

        $response->assertRedirect();

        $this->assertSame(
            'false',
            Setting::forTenant($this->tenant->id, 'enable_systematic_inspections', 'true')
        );
    }
}
