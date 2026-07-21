<?php

declare(strict_types=1);

namespace Tests\Feature\System;

use App\Models\AdminUser;
use App\Models\Center;
use App\Models\Customer;
use App\Models\Tenant;
use App\Models\TenantBackup;
use App\Services\Backup\TenantBackupService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TenantBackupTest extends TestCase
{
    use RefreshDatabase;

    protected AdminUser $superAdmin;

    protected AdminUser $regularAdmin;

    protected Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');

        $this->superAdmin = AdminUser::create([
            'name' => 'Super Admin',
            'email' => 'super@example.com',
            'password' => Hash::make('password'),
            'role' => 'super_admin',
            'is_active' => true,
        ]);

        $this->regularAdmin = AdminUser::create([
            'name' => 'Regular Admin',
            'email' => 'regular@example.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'is_active' => true,
        ]);

        $this->tenant = Tenant::create([
            'name' => 'Acme Workshop',
            'slug' => 'acme',
            'trade_name' => 'Acme',
        ]);
    }

    public function test_super_admin_can_create_backup(): void
    {
        $center = Center::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Main Center',
            'name_ar' => 'المركز الرئيسي',
            'slug' => 'main-center',
            'is_main' => true,
        ]);

        $customer = Customer::create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $center->id,
            'name' => 'John Doe',
            'phone' => '0500000001',
        ]);

        $service = app(TenantBackupService::class);

        $backup = $service->createBackup(
            $this->tenant,
            'Test backup',
            TenantBackup::TYPE_MANUAL,
            $this->superAdmin->id
        );

        $this->assertEquals(TenantBackup::STATUS_COMPLETED, $backup->status);
        $this->assertNotNull($backup->path);
        $this->assertGreaterThan(0, $backup->size_bytes);
        $this->assertNotNull($backup->checksum);
        Storage::disk('local')->assertExists($backup->path);

        // Manifest records customer table capture
        $manifest = $backup->manifest;
        $this->assertIsArray($manifest);
        $customerEntry = collect($manifest)->firstWhere('table', 'customers');
        $this->assertNotNull($customerEntry);
        $this->assertEquals(1, $customerEntry['rows']);
    }

    public function test_backup_artifact_is_valid_gzipped_json(): void
    {
        $service = app(TenantBackupService::class);
        $backup = $service->createBackup(
            $this->tenant,
            'Inspect me',
            TenantBackup::TYPE_MANUAL
        );

        $raw = Storage::disk('local')->get($backup->path);
        $json = gzdecode($raw);
        $this->assertNotFalse($json, 'Backup should be valid gzipped JSON');

        $payload = json_decode($json, true);
        $this->assertIsArray($payload);
        $this->assertEquals($this->tenant->id, $payload['tenant']['id']);
        $this->assertArrayHasKey('tables', $payload);
        $this->assertArrayHasKey('tenants', $payload['tables']);
    }

    public function test_regular_admin_cannot_create_backup(): void
    {
        $response = $this->actingAs($this->regularAdmin, 'admin')
            ->post("/system/tenants/{$this->tenant->id}/backups", [
                'name' => 'Forbidden attempt',
            ]);

        $response->assertStatus(403);
        $this->assertDatabaseCount('tenant_backups', 0);
    }

    public function test_backup_records_audit_log_on_creation(): void
    {
        $response = $this->actingAs($this->superAdmin, 'admin')
            ->post("/system/tenants/{$this->tenant->id}/backups", [
                'name' => 'Test audit',
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('admin_activity_logs', [
            'admin_user_id' => $this->superAdmin->id,
            'action' => 'backup_create',
            'model_type' => Tenant::class,
            'model_id' => $this->tenant->id,
        ]);
    }

    public function test_regular_admin_cannot_access_backups_index(): void
    {
        $response = $this->actingAs($this->regularAdmin, 'admin')
            ->get('/system/backups');

        $response->assertStatus(403);
    }

    public function test_super_admin_can_list_backups(): void
    {
        $service = app(TenantBackupService::class);
        $service->createBackup($this->tenant, 'B1', TenantBackup::TYPE_MANUAL);

        $response = $this->actingAs($this->superAdmin, 'admin')
            ->get('/system/backups');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('System/Backups/Index'));
    }

    public function test_super_admin_can_download_backup(): void
    {
        $service = app(TenantBackupService::class);
        $backup = $service->createBackup($this->tenant, 'Downloadable', TenantBackup::TYPE_MANUAL);

        $response = $this->actingAs($this->superAdmin, 'admin')
            ->get("/system/backups/{$backup->id}/download");

        $response->assertStatus(200);
        $this->assertStringContainsString('attachment', $response->headers->get('content-disposition') ?? '');
    }

    public function test_super_admin_can_delete_backup(): void
    {
        $service = app(TenantBackupService::class);
        $backup = $service->createBackup($this->tenant, 'Disposable', TenantBackup::TYPE_MANUAL);

        $this->actingAs($this->superAdmin, 'admin')
            ->delete("/system/backups/{$backup->id}")
            ->assertRedirect();

        $this->assertDatabaseMissing('tenant_backups', ['id' => $backup->id]);
        Storage::disk('local')->assertMissing($backup->path);
    }

    public function test_tenant_destroy_creates_pre_delete_backup(): void
    {
        // Seed some data so the backup captures actual rows
        $center = Center::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Main Center',
            'name_ar' => 'المركز الرئيسي',
            'slug' => 'main-center-2',
        ]);
        Customer::create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $center->id,
            'name' => 'Jane',
            'phone' => '0500000002',
        ]);

        $response = $this->actingAs($this->superAdmin, 'admin')
            ->delete("/system/tenants/{$this->tenant->id}", [
                'confirmation' => $this->tenant->name,
            ]);

        // The pre-delete hook should write a row to tenant_backups
        // BEFORE the destroy transaction runs. The FK is nullOnDelete
        // (so the backup survives the tenant forceDelete), but the
        // type column still tells us it's a pre_delete record. We
        // match by type + status + name pattern because tenant_id
        // will be NULL after the tenant is gone.
        $this->assertDatabaseHas('tenant_backups', [
            'type' => TenantBackup::TYPE_PRE_DELETE,
            'status' => TenantBackup::STATUS_COMPLETED,
        ]);
        // A backup with status=completed and size>0 must exist
        $this->assertDatabaseCount('tenant_backups', 1);
        $backup = TenantBackup::first();
        $this->assertNotNull($backup->path);
        $this->assertGreaterThan(0, $backup->size_bytes);
        $this->assertStringContainsString('Pre-delete', $backup->name);

        $response->assertRedirect();
        // The tenant itself is gone after destroy
        $this->assertDatabaseMissing('tenants', ['id' => $this->tenant->id]);
    }
}
