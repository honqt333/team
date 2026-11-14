<?php

declare(strict_types=1);

namespace Tests\Feature\HR;

use App\Models\Center;
use App\Models\HR\Employee;
use App\Models\HR\EmployeeAssetCustody;
use App\Models\Nationality;
use App\Models\Tenant;
use App\Models\User;
use App\Support\Permissions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class EmployeeCustodyTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    private Center $centerA;

    private Center $centerB;

    private Nationality $nationality;

    private User $authorizedUser;

    private User $unauthorizedUser;

    private Employee $employeeA;

    private Employee $employeeB;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();

        $this->tenant = Tenant::factory()->create();
        $this->centerA = Center::factory()->create(['tenant_id' => $this->tenant->id]);
        $this->centerB = Center::factory()->create(['tenant_id' => $this->tenant->id]);
        $this->nationality = Nationality::firstOrCreate(
            ['name_ar' => 'سعودي'],
            ['name_en' => 'Saudi', 'is_active' => true]
        );

        app(PermissionRegistrar::class)->setPermissionsTeamId($this->tenant->id);
        Permission::firstOrCreate(['name' => Permissions::HR_EMPLOYEES_VIEW, 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => Permissions::HR_EMPLOYEES_UPDATE, 'guard_name' => 'web']);

        $this->authorizedUser = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'current_center_id' => $this->centerA->id,
        ]);
        $this->authorizedUser->centers()->attach($this->centerA->id, ['tenant_id' => $this->tenant->id]);
        $this->authorizedUser->givePermissionTo([
            Permissions::HR_EMPLOYEES_VIEW,
            Permissions::HR_EMPLOYEES_UPDATE,
        ]);

        $this->unauthorizedUser = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'current_center_id' => $this->centerA->id,
        ]);
        $this->unauthorizedUser->centers()->attach($this->centerA->id, ['tenant_id' => $this->tenant->id]);

        $this->employeeA = Employee::create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->centerA->id,
            'employee_number' => 'EMP-CUS-A',
            'name_ar' => 'فني أ',
            'name_en' => 'Tech A',
            'phone' => '0501112233',
            'email' => 'techa@example.com',
            'gender' => 'male',
            'nationality_id' => $this->nationality->id,
            'status' => 'active',
        ]);

        $this->employeeB = Employee::withoutGlobalScopes()->create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->centerB->id,
            'employee_number' => 'EMP-CUS-B',
            'name_ar' => 'فني ب',
            'name_en' => 'Tech B',
            'phone' => '0501112244',
            'email' => 'techb@example.com',
            'gender' => 'male',
            'nationality_id' => $this->nationality->id,
            'status' => 'active',
        ]);
    }

    public function test_unauthorized_user_cannot_issue_custody(): void
    {
        $this->actingAs($this->unauthorizedUser)
            ->post(route('app.hr.employees.custodies.store', $this->employeeA->id), [
                'asset_name' => 'جهاز فحص Autel',
                'category' => 'diagnostic_device',
                'condition_on_issue' => 'good',
                'issued_date' => '2026-11-14',
            ])
            ->assertForbidden();
    }

    public function test_authorized_user_can_issue_return_and_delete_custody(): void
    {
        $this->actingAs($this->authorizedUser)
            ->post(route('app.hr.employees.custodies.store', $this->employeeA->id), [
                'asset_name' => 'جهاز فحص Autel',
                'category' => 'diagnostic_device',
                'serial_number' => 'AUTEL-9988',
                'estimated_value' => 4500,
                'condition_on_issue' => 'new',
                'issued_date' => '2026-11-14',
            ])
            ->assertRedirect();

        $custody = EmployeeAssetCustody::first();
        $this->assertNotNull($custody);
        $this->assertEquals('issued', $custody->status);
        $this->assertEquals('AUTEL-9988', $custody->serial_number);

        $this->actingAs($this->authorizedUser)
            ->put(route('app.hr.employees.custodies.return', [
                'employee' => $this->employeeA->id,
                'custody' => $custody->id,
            ]), [
                'status' => 'returned',
                'returned_date' => '2026-11-15',
            ])
            ->assertRedirect();

        $this->assertEquals('returned', $custody->fresh()->status);

        $this->actingAs($this->authorizedUser)
            ->get(route('app.hr.employees.custodies.print', [
                'employee' => $this->employeeA->id,
                'custody' => $custody->id,
            ]))
            ->assertOk();

        $this->actingAs($this->authorizedUser)
            ->delete(route('app.hr.employees.custodies.destroy', [
                'employee' => $this->employeeA->id,
                'custody' => $custody->id,
            ]))
            ->assertRedirect();

        $this->assertSoftDeleted('hr_employee_asset_custodies', ['id' => $custody->id]);
    }

    public function test_user_cannot_issue_custody_for_other_center_employee(): void
    {
        $this->actingAs($this->authorizedUser)
            ->post(route('app.hr.employees.custodies.store', $this->employeeB->id), [
                'asset_name' => 'حقيبة عدة',
                'category' => 'tool_kit',
                'condition_on_issue' => 'good',
                'issued_date' => '2026-11-14',
            ])
            ->assertForbidden();
    }
}
