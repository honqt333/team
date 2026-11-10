<?php

declare(strict_types=1);
use App\Http\Controllers\HealthController;
use App\Models\Center;
use App\Models\Customer;
use App\Models\HR\BiometricDevice;
use App\Models\HR\Employee;
use App\Models\HR\PayrollItem;
use App\Models\HR\PayrollRun;
use App\Models\Invoice;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Backup\TenantBackupService;
use App\Services\InvoiceService;
use App\Services\Reports\TaxPeriodService;
use App\Support\Permissions;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class AuditEvidenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_invitation_can_be_replayed_after_activation(): void
    {
        $user = User::factory()->create(['email_verified_at' => null]);
        $url = URL::temporarySignedRoute('invitations.accept', now()->addDays(3), ['user' => $user->id]);
        $this->post($url, ['password' => 'InitialPass123!', 'password_confirmation' => 'InitialPass123!'])->assertRedirect();
        auth()->logout();
        $this->post($url, ['password' => 'ReplayedPass123!', 'password_confirmation' => 'ReplayedPass123!'])->assertRedirect();
        $this->assertTrue(Hash::check('ReplayedPass123!', $user->fresh()->password));
    }

    public function test_readiness_error_path_calls_missing_method(): void
    {
        DB::shouldReceive('connection')->andThrow(new RuntimeException('simulated outage'));
        $this->expectException(Error::class);
        $this->expectExceptionMessage('sanitiseError');
        app(HealthController::class)->ready();
    }

    public function test_user_creation_accepts_foreign_center(): void
    {
        $tenant = Tenant::factory()->create();
        $center = Center::factory()->create(['tenant_id' => $tenant->id]);
        $foreign = Center::factory()->create();
        app(PermissionRegistrar::class)->setPermissionsTeamId($tenant->id);
        $actor = User::factory()->create(['tenant_id' => $tenant->id, 'current_center_id' => $center->id, 'is_active' => true]);
        $actor->centers()->attach($center->id, ['tenant_id' => $tenant->id]);
        $actor->givePermissionTo(Permissions::USERS_CREATE);
        $this->actingAs($actor)->post(route('settings.users.store'), ['name' => 'Audit User', 'email' => 'audit-user@example.test', 'password' => 'TestPass123!', 'password_confirmation' => 'TestPass123!', 'centers' => [$foreign->id]])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('users', ['email' => 'audit-user@example.test', 'tenant_id' => $tenant->id, 'current_center_id' => $foreign->id]);
    }

    public function test_user_manager_can_assign_super_admin_role(): void
    {
        $tenant = Tenant::factory()->create();
        $center = Center::factory()->create(['tenant_id' => $tenant->id]);
        app(PermissionRegistrar::class)->setPermissionsTeamId($tenant->id);
        $actor = User::factory()->create(['tenant_id' => $tenant->id, 'current_center_id' => $center->id, 'is_active' => true]);
        $actor->centers()->attach($center->id, ['tenant_id' => $tenant->id]);
        $actor->givePermissionTo(Permissions::USERS_CREATE);
        $role = Role::where('name', 'super_admin')->where('tenant_id', $tenant->id)->firstOrFail();
        $this->actingAs($actor)->post(route('settings.users.store'), ['name' => 'Audit Elevated', 'email' => 'audit-elevated@example.test', 'password' => 'TestPass123!', 'password_confirmation' => 'TestPass123!', 'centers' => [$center->id], 'role_id' => $role->id])->assertSessionHasNoErrors();
        $created = User::where('email', 'audit-elevated@example.test')->firstOrFail();
        $this->assertTrue($created->hasRole('super_admin'));
    }

    public function test_stale_draft_can_reissue_same_invoice_with_new_number(): void
    {
        $tenant = Tenant::factory()->create();
        $center = Center::factory()->create(['tenant_id' => $tenant->id]);
        $customer = Customer::factory()->create(['tenant_id' => $tenant->id, 'center_id' => $center->id]);
        $invoice = Invoice::factory()->create(['tenant_id' => $tenant->id, 'center_id' => $center->id, 'customer_id' => $customer->id, 'work_order_id' => null]);
        $stale = Invoice::findOrFail($invoice->id);
        $service = app(InvoiceService::class);
        $firstNumber = $service->issueInvoice($invoice)->invoice_number;
        $secondNumber = $service->issueInvoice($stale)->invoice_number;
        $this->assertNotSame($firstNumber, $secondNumber);
        $this->assertSame($secondNumber, $invoice->fresh()->invoice_number);
    }

    public function test_biometric_device_can_record_other_center_employee(): void
    {
        $tenant = Tenant::factory()->create();
        $center = Center::factory()->create(['tenant_id' => $tenant->id]);
        $other = Center::factory()->create(['tenant_id' => $tenant->id]);
        $device = BiometricDevice::create(['tenant_id' => $tenant->id, 'center_id' => $center->id, 'name' => 'Audit Reader', 'device_type' => 'zkteco']);
        $employee = Employee::create(['tenant_id' => $tenant->id, 'center_id' => $other->id, 'name_ar' => 'Audit Employee', 'employee_number' => 'AUDIT-OTHER-001']);
        $this->withToken($device->api_token)->postJson('/api/v1/attendance/clock', ['employee_identifier' => $employee->employee_number, 'action' => 'check_in'])->assertOk();
        $this->assertDatabaseHas('hr_attendances', ['tenant_id' => $tenant->id, 'center_id' => $center->id, 'employee_id' => $employee->id]);
    }

    public function test_backup_reports_completed_when_storage_put_fails(): void
    {
        $tenant = Tenant::factory()->create();
        $disk = Mockery::mock(Filesystem::class);
        $disk->shouldReceive('put')->once()->andReturn(false);
        Storage::shouldReceive('disk')->with('local')->andReturn($disk);
        $backup = app(TenantBackupService::class)->createBackup($tenant, 'Audit failed write');
        $this->assertSame('completed', $backup->status);
    }

    public function test_tax_period_creation_in_second_center_deletes_first_draft(): void
    {
        $tenant = Tenant::factory()->create();
        $a = Center::factory()->create(['tenant_id' => $tenant->id]);
        $b = Center::factory()->create(['tenant_id' => $tenant->id]);
        $user = User::factory()->create(['tenant_id' => $tenant->id, 'current_center_id' => $a->id]);
        $service = app(TaxPeriodService::class);
        $first = $service->createPeriod($tenant->id, '2026-Q1', [], $user->id, $a->id);
        $second = $service->createPeriod($tenant->id, '2026-Q1', [], $user->id, $b->id);
        $this->assertNotNull($first->fresh()->deleted_at);
        $this->assertSame($b->id, $second->center_id);
    }

    public function test_payroll_item_from_paid_run_can_be_deleted_via_draft_run(): void
    {
        $tenant = Tenant::factory()->create();
        $center = Center::factory()->create(['tenant_id' => $tenant->id]);
        app(PermissionRegistrar::class)->setPermissionsTeamId($tenant->id);
        $user = User::factory()->create(['tenant_id' => $tenant->id, 'current_center_id' => $center->id, 'is_active' => true]);
        $user->centers()->attach($center->id, ['tenant_id' => $tenant->id]);
        $user->givePermissionTo(Permissions::HR_PAYROLL_UPDATE);
        $employee = Employee::create(['tenant_id' => $tenant->id, 'center_id' => $center->id, 'name_ar' => 'Audit Employee', 'employee_number' => 'AUDIT-PAY-001']);
        $data = ['tenant_id' => $tenant->id, 'center_id' => $center->id, 'period_start' => '2026-01-01', 'period_end' => '2026-01-31', 'created_by' => $user->id];
        $draft = PayrollRun::create($data + ['status' => 'draft']);
        $paid = PayrollRun::create(array_merge($data, ['period_start' => '2026-02-01', 'period_end' => '2026-02-28', 'status' => 'paid']));
        $item = PayrollItem::create(['tenant_id' => $tenant->id, 'center_id' => $center->id, 'payroll_run_id' => $paid->id, 'employee_id' => $employee->id, 'base_salary' => 100, 'gosi_amount' => 0, 'net_salary' => 100]);
        $this->actingAs($user)->delete('/app/hr/payroll/'.$draft->id.'/items/'.$item->id)->assertRedirect();
        $this->assertDatabaseMissing('hr_payroll_items',['id' => $item->id]);
    }
}
