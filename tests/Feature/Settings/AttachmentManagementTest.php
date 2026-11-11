<?php

declare(strict_types=1);

namespace Tests\Feature\Settings;

use App\Models\Center;
use App\Models\HR\Employee;
use App\Models\HR\EmployeeDocument;
use App\Models\PurchaseInvoice;
use App\Models\Role;
use App\Models\Supplier;
use App\Models\Tenant;
use App\Models\User;
use App\Models\WorkOrder;
use App\Models\WorkOrderAttachment;
use App\Models\WorkOrderPhoto;
use App\Services\TenantStorageService;
use App\Support\Permissions;
use Database\Seeders\PermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class AttachmentManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        Storage::fake('public');
    }

    /**
     * @param list<string> $permissions
     * @return array{0: Tenant, 1: Center, 2: User}
     */
    private function makeUser(array $permissions = [], ?Tenant $tenant = null): array
    {
        $this->seed(PermissionsSeeder::class);

        $tenant ??= Tenant::factory()->create(['status' => 'active']);
        $center = Center::factory()->create([
            'tenant_id' => $tenant->id,
            'is_active' => true,
        ]);

        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'current_center_id' => $center->id,
            'is_active' => true,
            'two_factor_confirmed_at' => now(),
        ]);
        $user->centers()->attach($center->id, ['tenant_id' => $tenant->id]);

        app(PermissionRegistrar::class)->setPermissionsTeamId($tenant->id);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        if ($permissions !== []) {
            $role = Role::firstOrCreate([
                'tenant_id' => $tenant->id,
                'name' => 'attachments_role_'.uniqid(),
                'guard_name' => 'web',
            ]);
            $role->syncPermissions($permissions);
            $user->assignRole($role);
        }

        return [$tenant, $center, $user];
    }

    public function test_unauthorized_user_cannot_view_download_or_delete_system_attachments(): void
    {
        [$tenant, $center, $viewerWithoutPermission] = $this->makeUser([]);

        $workOrder = WorkOrder::factory()->create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
        ]);
        Storage::disk('public')->put('wo/report.pdf', 'pdf-content');
        $attachment = WorkOrderAttachment::create([
            'tenant_id' => $tenant->id,
            'work_order_id' => $workOrder->id,
            'file_name' => 'report.pdf',
            'path' => 'wo/report.pdf',
            'file_type' => 'application/pdf',
            'file_size' => 11,
            'user_id' => $viewerWithoutPermission->id,
        ]);

        // Storage tab remains accessible for quota visibility, but attachment data & flags are withheld.
        $this->actingAs($viewerWithoutPermission)
            ->get(route('settings.storage'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Settings/System/Index')
                ->where('activeSection', 'storage')
                ->where('canViewAttachments', false)
                ->where('attachments', null)
            );

        // Standalone attachments page, download, and delete strictly require permissions.
        $this->get(route('settings.attachments.index'))->assertForbidden();
        $this->get(route('settings.attachments.download', ['source' => 'work_order_attachment', 'id' => $attachment->id]))->assertForbidden();
        $this->delete(route('settings.attachments.destroy', ['source' => 'work_order_attachment', 'id' => $attachment->id]))->assertForbidden();

        // User with view-only permission can inspect & download, but still receives 403 on delete.
        [, , $viewOnlyUser] = $this->makeUser([Permissions::ATTACHMENTS_VIEW], $tenant);
        // Attach viewOnlyUser to the same center as the work order so center scope matches
        $viewOnlyUser->centers()->attach($center->id, ['tenant_id' => $tenant->id]);
        $viewOnlyUser->update(['current_center_id' => $center->id]);

        $this->actingAs($viewOnlyUser)
            ->get(route('settings.attachments.index'))
            ->assertOk();

        $this->delete(route('settings.attachments.destroy', ['source' => 'work_order_attachment', 'id' => $attachment->id]))
            ->assertForbidden();
    }

    public function test_authorized_user_can_list_filter_and_download_attachments_across_sources(): void
    {
        [$tenant, $center, $user] = $this->makeUser([
            Permissions::ATTACHMENTS_VIEW,
            Permissions::ATTACHMENTS_DELETE,
        ]);

        $workOrder = WorkOrder::factory()->create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
        ]);

        Storage::disk('public')->put('wo/attachment-1.pdf', 'wo-bytes');
        WorkOrderAttachment::create([
            'tenant_id' => $tenant->id,
            'work_order_id' => $workOrder->id,
            'file_name' => 'inspection-checklist.pdf',
            'path' => 'wo/attachment-1.pdf',
            'file_type' => 'application/pdf',
            'file_size' => 8,
            'user_id' => $user->id,
        ]);

        $employee = Employee::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'employee_number' => 'EMP-100',
            'name_ar' => 'سعد الحربي',
            'name_en' => 'Saad',
            'status' => 'active',
        ]);
        Storage::disk('local')->put("employee-documents/{$tenant->id}/{$employee->id}/iqama.pdf", 'iqama-data');
        $doc = EmployeeDocument::create([
            'tenant_id' => $tenant->id,
            'employee_id' => $employee->id,
            'type' => 'id',
            'title' => 'Employee Iqama',
            'file_path' => "employee-documents/{$tenant->id}/{$employee->id}/iqama.pdf",
        ]);

        $this->actingAs($user)
            ->get(route('settings.storage'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Settings/System/Index')
                ->where('activeSection', 'storage')
                ->where('canViewAttachments', true)
                ->where('canDeleteAttachments', true)
                ->where('attachmentSummary.total_count', 2)
                ->has('attachments.data', 2)
            );

        // Filter by source on standalone page
        $this->get(route('settings.attachments.index', ['source' => 'employee_document', 'search' => 'Iqama']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Settings/System/Attachments')
                ->has('attachments.data', 1)
                ->where('attachments.data.0.source', 'employee_document')
            );

        // Download private employee document safely with nosniff headers
        $download = $this->get(route('settings.attachments.download', ['source' => 'employee_document', 'id' => $doc->id]));
        $download->assertOk();
        $this->assertSame('nosniff', $download->headers->get('X-Content-Type-Options'));
    }

    public function test_deleting_attachments_removes_file_updates_quota_cache_and_preserves_tenant_isolation(): void
    {
        [$tenantA, $centerA, $userA] = $this->makeUser([
            Permissions::ATTACHMENTS_VIEW,
            Permissions::ATTACHMENTS_DELETE,
        ]);
        [$tenantB, $centerB] = $this->makeUser([]);

        $workOrderA = WorkOrder::factory()->create([
            'tenant_id' => $tenantA->id,
            'center_id' => $centerA->id,
        ]);
        Storage::disk('public')->put('wo/photo-a.jpg', str_repeat('a', 2048));
        $photoA = WorkOrderPhoto::create([
            'tenant_id' => $tenantA->id,
            'center_id' => $centerA->id,
            'work_order_id' => $workOrderA->id,
            'path' => 'wo/photo-a.jpg',
            'type' => 'before',
        ]);

        $supplier = Supplier::create([
            'tenant_id' => $tenantA->id,
            'center_id' => $centerA->id,
            'name' => 'مورد قطع الغيار',
            'phone' => '0501234567',
            'type' => 'company',
            'is_active' => true,
        ]);
        Storage::disk('public')->put('purchases/invoices/inv.pdf', 'invoice-file');
        $invoiceA = PurchaseInvoice::create([
            'tenant_id' => $tenantA->id,
            'center_id' => $centerA->id,
            'supplier_id' => $supplier->id,
            'code' => 'PINV-A1',
            'invoice_number' => 'INV-A1',
            'issue_date' => now(),
            'due_date' => now()->addDays(7),
            'status' => PurchaseInvoice::STATUS_OPEN,
            'subtotal' => 100,
            'tax_amount' => 15,
            'discount_amount' => 0,
            'total' => 115,
            'balance' => 115,
            'attachment_path' => 'purchases/invoices/inv.pdf',
        ]);

        // Cross-tenant record in tenant B must not be accessible or deletable by userA
        $workOrderB = WorkOrder::factory()->create([
            'tenant_id' => $tenantB->id,
            'center_id' => $centerB->id,
        ]);
        Storage::disk('public')->put('wo/secret-b.pdf', 'secret-b');
        $attachmentB = WorkOrderAttachment::create([
            'tenant_id' => $tenantB->id,
            'work_order_id' => $workOrderB->id,
            'file_name' => 'secret-b.pdf',
            'path' => 'wo/secret-b.pdf',
            'file_type' => 'application/pdf',
            'file_size' => 8,
        ]);

        // Prime Tenant A storage cache
        $initialUsed = TenantStorageService::getUsedStorageBytes($tenantA);
        $this->assertGreaterThan(0, $initialUsed);

        $this->actingAs($userA);

        // Tenant B file download & delete must 404
        $this->get(route('settings.attachments.download', ['source' => 'work_order_attachment', 'id' => $attachmentB->id]))
            ->assertNotFound();
        $this->delete(route('settings.attachments.destroy', ['source' => 'work_order_attachment', 'id' => $attachmentB->id]))
            ->assertNotFound();
        $this->assertDatabaseHas('work_order_attachments', ['id' => $attachmentB->id]);

        // Delete WorkOrderPhoto for Tenant A
        $this->delete(route('settings.attachments.destroy', ['source' => 'work_order_photo', 'id' => $photoA->id]))
            ->assertRedirect();

        $this->assertDatabaseMissing('work_order_photos', ['id' => $photoA->id]);
        Storage::disk('public')->assertMissing('wo/photo-a.jpg');
        $this->assertSame(0, TenantStorageService::getUsedStorageBytes($tenantA));

        // Delete PurchaseInvoice attachment cleanly without deleting invoice itself
        $this->delete(route('settings.attachments.destroy', ['source' => 'purchase_invoice', 'id' => $invoiceA->id]))
            ->assertRedirect();

        $this->assertNull($invoiceA->fresh()->attachment_path);
        $this->assertDatabaseHas('purchase_invoices', ['id' => $invoiceA->id, 'deleted_at' => null]);
        Storage::disk('public')->assertMissing('purchases/invoices/inv.pdf');
    }
}
