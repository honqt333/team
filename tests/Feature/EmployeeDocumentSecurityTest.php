<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Center;
use App\Models\HR\Employee;
use App\Models\HR\EmployeeDocument;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use App\Support\Permissions;
use Database\Seeders\PermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Security tests for the employee document upload / storage / download
 * path.
 *
 * THE DEFECTS THIS COVERS
 *
 * 1. No file-type restriction. The upload was validated as
 *    `file|max:10240` only, and Laravel keeps the client-supplied
 *    extension, so a file named "shell.php" was stored as .php on the
 *    'public' disk.
 *
 * 2. Public disk. 'public' is symlinked to public/storage and served
 *    directly by Apache. public/.htaccess only rewrites to index.php
 *    when the target is NOT a real file (`RewriteCond %{REQUEST_FILENAME}
 *    !-f`), so an existing .php file under public/storage/ is executed
 *    by the web server, bypassing Laravel completely — no session, no
 *    permission check.
 *
 * 3. Unauthenticated delivery. The UI linked straight to
 *    `/storage/{path}`, so the only thing standing between the file and
 *    the public was knowing (or guessing) the URL.
 *
 * 4. No authorization. The controller carried a
 *    `@bypass-authorization-scanner` annotation and performed no
 *    authorize() call, so any authenticated user in the tenant could
 *    upload, read and delete every employee's documents.
 *
 * The fix moves the files to the private 'local' disk, restricts the
 * accepted mime types, and serves every read through an authorized
 * download action.
 */
class EmployeeDocumentSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        Storage::fake('public');
    }

    /**
     * Create a tenant + center + user with the given HR permissions.
     *
     * @param list<string> $permissions
     */
    private function makeUser(array $permissions = [], ?Tenant $tenant = null): array
    {
        $this->seed(PermissionsSeeder::class);

        $tenant ??= Tenant::factory()->create();
        $center = Center::factory()->create(['tenant_id' => $tenant->id]);

        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'current_center_id' => $center->id,
            'two_factor_confirmed_at' => now(),
        ]);
        $user->centers()->attach($center->id, ['tenant_id' => $tenant->id]);

        app(PermissionRegistrar::class)->setPermissionsTeamId($tenant->id);

        $role = Role::firstOrCreate([
            'tenant_id' => $tenant->id,
            'name' => 'hr_manager_'.uniqid(),
            'guard_name' => 'web',
        ]);

        foreach ($permissions as $permission) {
            $role->givePermissionTo($permission);
        }

        $user->assignRole($role);

        return [$tenant, $center, $user];
    }

    private function makeEmployee(Tenant $tenant, Center $center): Employee
    {
        return Employee::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'employee_number' => 'EMP-'.uniqid(),
            'name_ar' => 'موظف اختبار',
            'name_en' => 'Test Employee',
            'status' => 'active',
        ]);
    }

    // ─────────────────────────────────────────────────────────────────
    // 1. File type restriction
    // ─────────────────────────────────────────────────────────────────

    public function test_upload_rejects_a_php_file(): void
    {
        [$tenant, $center, $user] = $this->makeUser([
            Permissions::HR_EMPLOYEES_VIEW,
            Permissions::HR_EMPLOYEES_UPDATE,
        ]);
        $employee = $this->makeEmployee($tenant, $center);

        $response = $this->actingAs($user)->post(
            route('app.hr.employees.documents.store', $employee),
            [
                'title' => 'Malicious',
                'type' => 'other',
                'file' => UploadedFile::fake()->create('shell.php', 10, 'application/x-php'),
            ]
        );

        $response->assertSessionHasErrors('file');

        $this->assertSame(
            0,
            EmployeeDocument::query()->count(),
            'No document row may be created for a rejected upload.'
        );
    }

    public function test_upload_rejects_an_html_file(): void
    {
        [$tenant, $center, $user] = $this->makeUser([
            Permissions::HR_EMPLOYEES_VIEW,
            Permissions::HR_EMPLOYEES_UPDATE,
        ]);
        $employee = $this->makeEmployee($tenant, $center);

        $response = $this->actingAs($user)->post(
            route('app.hr.employees.documents.store', $employee),
            [
                'title' => 'XSS',
                'type' => 'other',
                'file' => UploadedFile::fake()->create('payload.html', 10, 'text/html'),
            ]
        );

        $response->assertSessionHasErrors('file');
        $this->assertSame(0, EmployeeDocument::query()->count());
    }

    public function test_upload_accepts_a_pdf_and_stores_it_on_the_private_disk(): void
    {
        [$tenant, $center, $user] = $this->makeUser([
            Permissions::HR_EMPLOYEES_VIEW,
            Permissions::HR_EMPLOYEES_UPDATE,
        ]);
        $employee = $this->makeEmployee($tenant, $center);

        $response = $this->actingAs($user)->post(
            route('app.hr.employees.documents.store', $employee),
            [
                'title' => 'Iqama',
                'type' => 'id',
                'file' => UploadedFile::fake()->create('iqama.pdf', 20, 'application/pdf'),
            ]
        );

        $response->assertSessionHasNoErrors();

        $document = EmployeeDocument::query()->first();
        $this->assertNotNull($document, 'A valid PDF upload must create a document row.');
        $this->assertSame('Iqama', $document->title);

        // The file must live on the private disk, NOT the public one.
        Storage::disk('local')->assertExists($document->file_path);
        Storage::disk('public')->assertMissing($document->file_path);

        // And the stored path must be tenant-scoped.
        $this->assertStringContainsString(
            'employee-documents/'.$tenant->id.'/'.$employee->id,
            $document->file_path,
            'Employee documents must be stored under a tenant-scoped path.'
        );
    }

    // ─────────────────────────────────────────────────────────────────
    // 2. Authorization
    // ─────────────────────────────────────────────────────────────────

    public function test_upload_is_forbidden_without_the_update_permission(): void
    {
        [$tenant, $center, $user] = $this->makeUser([
            Permissions::HR_EMPLOYEES_VIEW,
        ]);
        $employee = $this->makeEmployee($tenant, $center);

        $response = $this->actingAs($user)->post(
            route('app.hr.employees.documents.store', $employee),
            [
                'title' => 'Nope',
                'type' => 'other',
                'file' => UploadedFile::fake()->create('doc.pdf', 10, 'application/pdf'),
            ]
        );

        $response->assertForbidden();
        $this->assertSame(0, EmployeeDocument::query()->count());
    }

    public function test_download_is_forbidden_without_the_view_permission(): void
    {
        [$tenant, $center, $user] = $this->makeUser([]);
        $employee = $this->makeEmployee($tenant, $center);

        $document = EmployeeDocument::create([
            'tenant_id' => $tenant->id,
            'employee_id' => $employee->id,
            'title' => 'Secret',
            'type' => 'id',
            'file_path' => 'employee-documents/'.$tenant->id.'/'.$employee->id.'/secret.pdf',
        ]);

        $response = $this->actingAs($user)->get(
            route('app.hr.documents.download', $document)
        );

        $response->assertForbidden();
    }

    public function test_download_is_forbidden_for_a_user_from_another_tenant(): void
    {
        [$tenant, $center, $user] = $this->makeUser([
            Permissions::HR_EMPLOYEES_VIEW,
        ]);
        $employee = $this->makeEmployee($tenant, $center);

        $document = EmployeeDocument::create([
            'tenant_id' => $tenant->id,
            'employee_id' => $employee->id,
            'title' => 'Secret',
            'type' => 'id',
            'file_path' => 'employee-documents/'.$tenant->id.'/'.$employee->id.'/secret.pdf',
        ]);

        // A second tenant, with the same permissions, must not be able
        // to download the first tenant's document.
        [, , $otherUser] = $this->makeUser([Permissions::HR_EMPLOYEES_VIEW]);

        $response = $this->actingAs($otherUser)->get(
            route('app.hr.documents.download', $document)
        );

        // The TenantScoped global scope hides the row from the other
        // tenant, so route-model binding never resolves it and the
        // request 404s before the policy even runs. That is the
        // stronger outcome: the response does not reveal that the
        // document exists. If the scope is ever removed, the policy
        // still rejects the request with a 403 — either way the file
        // is never served.
        $this->assertTrue(
            $response->status() === 404 || $response->status() === 403,
            'A cross-tenant download must not succeed. Got status '.$response->status()
        );

        $this->assertStringNotContainsString(
            '%PDF',
            (string) $response->getContent(),
            'A cross-tenant download must never return the file content.'
        );
    }

    public function test_delete_is_forbidden_without_the_delete_permission(): void
    {
        [$tenant, $center, $user] = $this->makeUser([
            Permissions::HR_EMPLOYEES_VIEW,
            Permissions::HR_EMPLOYEES_UPDATE,
        ]);
        $employee = $this->makeEmployee($tenant, $center);

        $document = EmployeeDocument::create([
            'tenant_id' => $tenant->id,
            'employee_id' => $employee->id,
            'title' => 'Keep me',
            'type' => 'id',
            'file_path' => 'employee-documents/'.$tenant->id.'/'.$employee->id.'/keep.pdf',
        ]);

        $response = $this->actingAs($user)->delete(
            route('app.hr.documents.destroy', $document)
        );

        $response->assertForbidden();
        $this->assertNotNull(EmployeeDocument::query()->find($document->id));
    }

    // ─────────────────────────────────────────────────────────────────
    // 3. Authorized delivery
    // ─────────────────────────────────────────────────────────────────

    public function test_authorized_download_streams_the_file_as_an_attachment_with_nosniff(): void
    {
        [$tenant, $center, $user] = $this->makeUser([
            Permissions::HR_EMPLOYEES_VIEW,
        ]);
        $employee = $this->makeEmployee($tenant, $center);

        $path = 'employee-documents/'.$tenant->id.'/'.$employee->id.'/iqama.pdf';
        Storage::disk('local')->put($path, '%PDF-1.4 fake');

        $document = EmployeeDocument::create([
            'tenant_id' => $tenant->id,
            'employee_id' => $employee->id,
            'title' => 'Iqama',
            'type' => 'id',
            'file_path' => $path,
        ]);

        $response = $this->actingAs($user)->get(
            route('app.hr.documents.download', $document)
        );

        $response->assertOk();
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $this->assertStringContainsString(
            'attachment',
            (string) $response->headers->get('Content-Disposition'),
            'The document must be delivered as an attachment, never rendered inline.'
        );
    }

    public function test_download_returns_404_when_the_stored_file_is_missing(): void
    {
        [$tenant, $center, $user] = $this->makeUser([
            Permissions::HR_EMPLOYEES_VIEW,
        ]);
        $employee = $this->makeEmployee($tenant, $center);

        $document = EmployeeDocument::create([
            'tenant_id' => $tenant->id,
            'employee_id' => $employee->id,
            'title' => 'Ghost',
            'type' => 'id',
            'file_path' => 'employee-documents/'.$tenant->id.'/'.$employee->id.'/ghost.pdf',
        ]);

        $response = $this->actingAs($user)->get(
            route('app.hr.documents.download', $document)
        );

        $response->assertNotFound();
    }

    public function test_deleting_a_document_also_removes_the_stored_file(): void
    {
        [$tenant, $center, $user] = $this->makeUser([
            Permissions::HR_EMPLOYEES_DELETE,
        ]);
        $employee = $this->makeEmployee($tenant, $center);

        $path = 'employee-documents/'.$tenant->id.'/'.$employee->id.'/bye.pdf';
        Storage::disk('local')->put($path, 'content');

        $document = EmployeeDocument::create([
            'tenant_id' => $tenant->id,
            'employee_id' => $employee->id,
            'title' => 'Bye',
            'type' => 'id',
            'file_path' => $path,
        ]);

        $response = $this->actingAs($user)->delete(
            route('app.hr.documents.destroy', $document)
        );

        $response->assertSessionHasNoErrors();
        Storage::disk('local')->assertMissing($path);
    }
}
