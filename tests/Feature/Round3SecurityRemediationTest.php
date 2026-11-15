<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\AdminUser;
use App\Models\Billing\Plan;
use App\Models\Center;
use App\Models\Customer;
use App\Models\HR\Allowance;
use App\Models\HR\BiometricDevice;
use App\Models\HR\Deduction;
use App\Models\HR\Employee;
use App\Models\HR\OtherPayment;
use App\Models\Quote;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleColor;
use App\Models\VehicleConditionCategory;
use App\Models\VehicleConditionItem;
use App\Models\VehicleMake;
use App\Models\VehicleModel;
use App\Support\Permissions;
use App\Support\TenancyContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class Round3SecurityRemediationTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    private Center $center;

    private User $authorizedUser;

    private User $unprivilegedUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::factory()->create(['status' => 'active']);
        $this->center = Center::factory()->create(['tenant_id' => $this->tenant->id]);

        $this->authorizedUser = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'current_center_id' => $this->center->id,
            'two_factor_confirmed_at' => now(),
        ]);
        $this->authorizedUser->centers()->attach($this->center->id, ['tenant_id' => $this->tenant->id]);

        $this->unprivilegedUser = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'current_center_id' => $this->center->id,
            'two_factor_confirmed_at' => now(),
        ]);
        $this->unprivilegedUser->centers()->attach($this->center->id, ['tenant_id' => $this->tenant->id]);

        app(PermissionRegistrar::class)->setPermissionsTeamId($this->tenant->id);

        foreach ([
            Permissions::HR_EMPLOYEES_VIEW,
            Permissions::HR_EMPLOYEES_UPDATE,
            Permissions::HR_PAYMENTS_APPROVE,
            Permissions::VEHICLE_SETTINGS_VIEW,
            Permissions::VEHICLE_SETTINGS_MANAGE,
            Permissions::COMPANY_MANAGE,
        ] as $permission) {
            Permission::findOrCreate($permission, 'web');
            $this->authorizedUser->givePermissionTo($permission);
        }
    }

    public function test_aud_004_cross_tenant_allowances_and_deductions_are_rejected(): void
    {
        $otherTenant = Tenant::factory()->create(['status' => 'active']);
        $otherCenter = Center::factory()->create(['tenant_id' => $otherTenant->id]);

        $employee = Employee::create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'employee_number' => 'EMP-R3-001',
            'name_ar' => 'موظف الاختبار',
            'name_en' => 'Test Employee',
        ]);

        $ownAllowance = Allowance::create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'name_ar' => 'بدل سكن',
            'name_en' => 'Housing Allowance',
            'type' => 'fixed',
            'default_amount' => 500,
            'is_active' => true,
        ]);

        $foreignAllowance = Allowance::create([
            'tenant_id' => $otherTenant->id,
            'center_id' => $otherCenter->id,
            'name_ar' => 'بدل أجنبي',
            'name_en' => 'Foreign Allowance',
            'type' => 'fixed',
            'default_amount' => 999,
            'is_active' => true,
        ]);

        $foreignDeduction = Deduction::create([
            'tenant_id' => $otherTenant->id,
            'center_id' => $otherCenter->id,
            'name_ar' => 'خصم أجنبي',
            'name_en' => 'Foreign Deduction',
            'type' => 'fixed',
            'default_amount' => 300,
            'is_active' => true,
        ]);

        $this->actingAs($this->authorizedUser)
            ->put("/app/hr/employees/{$employee->id}/allowances", [
                'allowances' => [
                    ['id' => $foreignAllowance->id, 'custom_amount' => 1000, 'period_type' => 'indefinite'],
                ],
            ])
            ->assertSessionHasErrors('allowances.0.id');

        $this->actingAs($this->authorizedUser)
            ->put("/app/hr/employees/{$employee->id}/deductions", [
                'deductions' => [
                    ['id' => $foreignDeduction->id, 'custom_amount' => 250, 'period_type' => 'indefinite'],
                ],
            ])
            ->assertSessionHasErrors('deductions.0.id');

        $this->actingAs($this->authorizedUser)
            ->put("/app/hr/employees/{$employee->id}/allowances", [
                'allowances' => [
                    ['id' => $ownAllowance->id, 'custom_amount' => 500, 'period_type' => 'indefinite'],
                ],
            ])
            ->assertSessionHasNoErrors();
    }

    public function test_aud_005_other_payment_lifecycle_is_enforced(): void
    {
        $employee = Employee::create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'employee_number' => 'EMP-R3-002',
            'name_ar' => 'موظف الدفعات',
            'name_en' => 'Payments Employee',
        ]);

        $payment = OtherPayment::create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'employee_id' => $employee->id,
            'type' => 'bonus',
            'title' => 'Performance Bonus',
            'description' => 'Performance Bonus',
            'amount' => 1200,
            'payment_date' => now()->toDateString(),
            'status' => OtherPayment::STATUS_PENDING,
        ]);

        // Unprivileged user gets 403 on both approve and pay
        $this->actingAs($this->unprivilegedUser)
            ->putJson("/app/hr/payroll/other-payments/{$payment->id}/approve")
            ->assertStatus(403);
        $this->actingAs($this->unprivilegedUser)
            ->putJson("/app/hr/payroll/other-payments/{$payment->id}/pay")
            ->assertStatus(403);

        // Pending -> Paid directly is blocked (422 JSON and 302 web)
        $this->actingAs($this->authorizedUser)
            ->putJson("/app/hr/payroll/other-payments/{$payment->id}/pay")
            ->assertStatus(422);
        $this->assertSame(OtherPayment::STATUS_PENDING, $payment->fresh()->status);

        $this->actingAs($this->authorizedUser)
            ->put("/app/hr/payroll/other-payments/{$payment->id}/pay")
            ->assertStatus(302);
        $this->assertSame(OtherPayment::STATUS_PENDING, $payment->fresh()->status);

        // Pending -> Approved succeeds
        $this->actingAs($this->authorizedUser)
            ->putJson("/app/hr/payroll/other-payments/{$payment->id}/approve")
            ->assertOk();
        $this->assertSame(OtherPayment::STATUS_APPROVED, $payment->fresh()->status);

        // Approved -> Paid succeeds
        $this->actingAs($this->authorizedUser)
            ->putJson("/app/hr/payroll/other-payments/{$payment->id}/pay")
            ->assertOk();
        $this->assertSame(OtherPayment::STATUS_PAID, $payment->fresh()->status);

        // Duplicate Paid -> Paid is rejected
        $this->actingAs($this->authorizedUser)
            ->putJson("/app/hr/payroll/other-payments/{$payment->id}/pay")
            ->assertStatus(422);
    }

    public function test_aud_006_settings_and_subscription_controllers_require_permissions(): void
    {
        $make = VehicleMake::forceCreate([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'name_ar' => 'تويوتا',
            'name_en' => 'Toyota',
            'source' => 'center',
            'is_active' => true,
        ]);
        $model = VehicleModel::forceCreate([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'make_id' => $make->id,
            'name_ar' => 'كامري',
            'name_en' => 'Camry',
            'source' => 'center',
            'is_active' => true,
        ]);
        $color = VehicleColor::forceCreate([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'name_ar' => 'أبيض',
            'name_en' => 'White',
            'source' => 'center',
            'is_active' => true,
        ]);
        $category = VehicleConditionCategory::forceCreate([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'name_ar' => 'الهيكل',
            'name_en' => 'Body',
            'source' => 'center',
            'is_active' => true,
        ]);
        $item = VehicleConditionItem::forceCreate([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'category_id' => $category->id,
            'name_ar' => 'المصد',
            'name_en' => 'Bumper',
            'source' => 'center',
            'is_active' => true,
        ]);
        $plan = Plan::create([
            'name' => 'Starter',
            'name_ar' => 'أساسية',
            'name_en' => 'Starter',
            'slug' => 'starter-r3-'.uniqid(),
            'price_monthly' => 100,
            'price_yearly' => 1000,
            'is_active' => true,
        ]);

        // Unprivileged user gets 403 across all 7 controllers
        $this->actingAs($this->unprivilegedUser);
        $this->get('/app/settings/makes')->assertStatus(403);
        $this->post('/app/settings/makes', ['name_ar' => 'أ', 'name_en' => 'A'])->assertStatus(403);
        $this->put("/app/settings/makes/{$make->id}", ['name_ar' => 'ب'])->assertStatus(403);
        $this->get('/app/settings/models')->assertStatus(403);
        $this->post('/app/settings/models', ['make_id' => $make->id, 'name_ar' => 'أ', 'name_en' => 'A'])->assertStatus(403);
        $this->put("/app/settings/models/{$model->id}", ['make_id' => $make->id, 'name_ar' => 'ب'])->assertStatus(403);
        $this->get('/app/settings/colors')->assertStatus(403);
        $this->post('/app/settings/colors', ['name_ar' => 'أ', 'name_en' => 'A'])->assertStatus(403);
        $this->put("/app/settings/colors/{$color->id}", ['name_ar' => 'ب'])->assertStatus(403);
        $this->post('/app/settings/condition-categories', ['name_ar' => 'أ', 'name_en' => 'A'])->assertStatus(403);
        $this->put("/app/settings/condition-categories/{$category->id}", ['name_ar' => 'ب'])->assertStatus(403);
        $this->get('/app/settings/condition-items')->assertStatus(403);
        $this->post('/app/settings/condition-items', ['category_id' => $category->id, 'name_ar' => 'أ', 'name_en' => 'A'])->assertStatus(403);
        $this->put("/app/settings/condition-items/{$item->id}", ['category_id' => $category->id, 'name_ar' => 'ب'])->assertStatus(403);
        $this->get('/app/settings/income-categories')->assertStatus(403);
        $this->post('/app/settings/income-categories', ['name_ar' => 'أ', 'name_en' => 'A', 'transaction_type' => 'revenue'])->assertStatus(403);
        $this->postJson('/app/settings/billing/calculate', ['plan_id' => $plan->id])->assertStatus(403);
        $this->postJson('/app/settings/billing/validate-promo', ['code' => 'TEST'])->assertStatus(403);

        // Authorized user succeeds
        $this->actingAs($this->authorizedUser);
        $this->get('/app/settings/makes')->assertOk();
        $this->get('/app/settings/income-categories')->assertOk();
        $this->postJson('/app/settings/billing/calculate', ['plan_id' => $plan->id])->assertOk();
    }

    public function test_aud_007_biometric_api_enforces_tenant_status_timestamp_window_and_replay(): void
    {
        $suspendedTenant = Tenant::factory()->create([
            'status' => 'suspended',
            'suspended_at' => now(),
        ]);
        $suspendedCenter = Center::factory()->create(['tenant_id' => $suspendedTenant->id]);
        $suspendedDevice = BiometricDevice::create([
            'tenant_id' => $suspendedTenant->id,
            'center_id' => $suspendedCenter->id,
            'name' => 'Suspended Reader',
            'device_type' => 'zkteco',
        ]);
        Employee::create([
            'tenant_id' => $suspendedTenant->id,
            'center_id' => $suspendedCenter->id,
            'employee_number' => 'EMP-SUSP-1',
            'name_ar' => 'موظف موقوف',
            'name_en' => 'Suspended Emp',
        ]);

        // Suspended tenant device rejected with 403
        $this->withHeader('Authorization', 'Bearer '.$suspendedDevice->api_token)
            ->postJson('/api/v1/attendance/clock', [
                'employee_identifier' => 'EMP-SUSP-1',
                'action' => 'check_in',
            ])
            ->assertStatus(403);

        // Active tenant device
        $activeDevice = BiometricDevice::create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'name' => 'Active Reader',
            'device_type' => 'zkteco',
        ]);
        $employee = Employee::create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'employee_number' => 'EMP-ACT-1',
            'name_ar' => 'موظف نشط',
            'name_en' => 'Active Emp',
        ]);

        // Out-of-window timestamp rejected with 422
        $this->withHeader('Authorization', 'Bearer '.$activeDevice->api_token)
            ->postJson('/api/v1/attendance/clock', [
                'employee_identifier' => 'EMP-ACT-1',
                'action' => 'check_in',
                'timestamp' => now('UTC')->subDays(30)->toIso8601String(),
            ])
            ->assertStatus(422)
            ->assertJsonPath('error', 'timestamp_out_of_window');

        // Valid timestamp succeeds
        $validTs = now('UTC')->subMinutes(5)->toIso8601String();
        $this->withHeader('Authorization', 'Bearer '.$activeDevice->api_token)
            ->postJson('/api/v1/attendance/clock', [
                'employee_identifier' => 'EMP-ACT-1',
                'action' => 'check_in',
                'timestamp' => $validTs,
            ])
            ->assertOk();

        $this->assertDatabaseHas('hr_attendances', [
            'tenant_id' => $this->tenant->id,
            'employee_id' => $employee->id,
            'status' => 'present',
        ]);

        // Replay / duplicate check-in rejected with 409
        $this->withHeader('Authorization', 'Bearer '.$activeDevice->api_token)
            ->postJson('/api/v1/attendance/clock', [
                'employee_identifier' => 'EMP-ACT-1',
                'action' => 'check_in',
                'timestamp' => $validTs,
            ])
            ->assertStatus(409)
            ->assertJsonPath('error', 'duplicate_attendance_event');
    }

    public function test_aud_008_public_quote_blocks_suspended_tenants(): void
    {
        $suspendedTenant = Tenant::factory()->create([
            'status' => 'suspended',
            'suspended_at' => now(),
        ]);
        $suspendedCenter = Center::factory()->create(['tenant_id' => $suspendedTenant->id]);
        $customer = Customer::factory()->create(['tenant_id' => $suspendedTenant->id]);
        $vehicle = Vehicle::factory()->create([
            'tenant_id' => $suspendedTenant->id,
            'customer_id' => $customer->id,
        ]);

        $quote = Quote::forceCreate([
            'uuid' => (string) Str::uuid(),
            'tenant_id' => $suspendedTenant->id,
            'center_id' => $suspendedCenter->id,
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'code' => 'QT-R3-001',
            'status' => 'sent',
            'subtotal' => 500,
            'total' => 575,
        ]);

        $this->get("/view/quote/{$quote->uuid}")->assertStatus(403);

        $this->post("/view/quote/{$quote->uuid}/approve", [
            'accepted_terms' => true,
        ])->assertStatus(302)->assertSessionHas('error');

        $this->post("/view/quote/{$quote->uuid}/reject", [
            'rejection_reason' => 'Too expensive',
        ])->assertStatus(302)->assertSessionHas('error');

        $this->assertSame('sent', $quote->fresh()->status);
    }

    public function test_aud_009_security_headers_are_applied_to_responses(): void
    {
        $response = $this->get('/login');

        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $this->assertNotNull($response->headers->get('Content-Security-Policy-Report-Only'));
        $this->assertNull($response->headers->get('Strict-Transport-Security'));
    }

    public function test_aud_010_tenancy_context_returns_null_on_system_routes(): void
    {
        $admin = AdminUser::create([
            'name' => 'System Admin',
            'email' => 'sysadmin-r3@example.com',
            'password' => bcrypt('password'),
            'role' => 'super_admin',
            'is_active' => true,
        ]);

        $this->actingAs($admin, 'admin');
        $this->app->instance('request', Request::create('/system/dashboard', 'GET'));

        $this->assertNull(TenancyContext::user());
        $this->assertNull(TenancyContext::centerId());

        // Even if a tenant User is logged in, /system/* must return null centerId
        $this->actingAs($this->authorizedUser);
        $this->app->instance('request', Request::create('/system/tenants', 'GET'));
        $this->assertNull(TenancyContext::centerId());

        // On /app/*, a tenant User resolves its current_center_id
        $this->app->instance('request', Request::create('/app/dashboard', 'GET'));
        $this->assertSame($this->center->id, TenancyContext::centerId());
    }
}
