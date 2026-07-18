<?php

namespace Tests\Feature;

use App\Models\Center;
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

class PrintSettingsSignatureUploadTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Tenant $tenant;

    protected Center $center;

    protected function setUp(): void
    {
        parent::setUp();

        // Storage fake — we never want the test run to write to the real
        // public disk. The controller calls Storage::disk('public'), which
        // is intercepted by Storage::fake('public').
        Storage::fake('public');

        // Seed all permissions so the COMPANY_MANAGE permission exists
        // for the user role assignment.
        $this->seed(PermissionsSeeder::class);

        $this->tenant = Tenant::factory()->create();
        $this->center = Center::factory()->create(['tenant_id' => $this->tenant->id]);

        $this->user = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'current_center_id' => $this->center->id,
        ]);
        $this->user->centers()->attach($this->center->id, ['tenant_id' => $this->tenant->id]);

        // Permissions are scoped per-tenant via Spatie's team feature.
        app(PermissionRegistrar::class)->setPermissionsTeamId($this->tenant->id);

        // Grant the user the COMPANY_MANAGE permission by creating and
        // assigning a super_admin role for the tenant. This mirrors the
        // production bootstrap in TenantSetupService::seedRolesForTenant.
        $role = Role::firstOrCreate(
            [
                'name' => 'super_admin',
                'guard_name' => 'web',
                'tenant_id' => $this->tenant->id,
            ],
            [
                'label_ar' => 'مدير عام',
                'label_en' => 'Super Admin',
                'description' => 'kitchen sink',
            ]
        );
        $role->syncPermissions([Permissions::COMPANY_MANAGE]);
        $this->user->assignRole($role);

        // Reload user with permissions for the request.
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->user->refresh();
    }

    /**
     * Helper: build a real PNG file from a 1x1 transparent pixel so the
     * validator's `image` rule is satisfied (which uses getimagesize()
     * under the hood, not just the client-supplied MIME).
     */
    private function makeValidPng(string $name = 'signature.png'): UploadedFile
    {
        $tmp = tempnam(sys_get_temp_dir(), 'sig_png_');
        $im = imagecreatetruecolor(8, 8);
        imagepng($im, $tmp);
        imagedestroy($im);

        return new UploadedFile($tmp, $name, 'image/png', null, true);
    }

    /**
     * Helper: build a real SVG file. SVG has a text payload, no binary
     * header, so the validator's `image` rule passes (fileinfo
     * identifies it as image/svg+xml).
     */
    private function makeValidSvg(string $name = 'signature.svg'): UploadedFile
    {
        $tmp = tempnam(sys_get_temp_dir(), 'sig_svg_');
        file_put_contents(
            $tmp,
            '<?xml version="1.0" encoding="UTF-8"?><svg xmlns="http://www.w3.org/2000/svg" width="20" height="20"><rect width="20" height="20" fill="#000"/></svg>'
        );

        return new UploadedFile($tmp, $name, 'image/svg+xml', null, true);
    }

    public function test_user_can_upload_valid_png_signature(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson(route('settings.print.signatures.store'), [
                'signature' => $this->makeValidPng(),
                'name' => 'CEO signature',
            ]);

        $response->assertOk();
        $response->assertJsonStructure([
            'id',
            'url',
            'name',
            'uploaded_at',
            'size',
            'mime_type',
        ]);
        $response->assertJsonFragment(['name' => 'CEO signature']);

        // Response URL must point at the public disk.
        $payload = $response->json();
        $this->assertStringContainsString('/storage/tenants/'.$this->tenant->id.'/signatures/', $payload['url']);

        // File actually exists on the (faked) public disk.
        $path = 'tenants/'.$this->tenant->id.'/signatures/'.$payload['id'].'.png';
        Storage::disk('public')->assertExists($path);
    }

    public function test_user_can_upload_valid_svg_signature(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson(route('settings.print.signatures.store'), [
                'signature' => $this->makeValidSvg(),
                'name' => 'Vector seal',
                'document_type' => 'invoice',
            ]);

        $response->assertOk();
        $response->assertJsonFragment(['name' => 'Vector seal']);
        $response->assertJsonFragment(['document_type' => 'invoice']);

        // The signature was also appended to the tenant's print_settings JSON.
        $this->tenant->refresh();
        $signatures = $this->tenant->print_settings['documents']['invoice']['signatures'] ?? [];
        $this->assertCount(1, $signatures);
        $this->assertSame('Vector seal', $signatures[0]['name']);
        $this->assertSame('invoice', $signatures[0]['document_type']);
    }

    public function test_upload_rejects_5mb_file(): void
    {
        // 5MB > 2MB cap. The form request `max:2048` is in KB.
        $big = UploadedFile::fake()->create('huge.png', 5120, 'image/png');

        $response = $this->actingAs($this->user)
            ->postJson(route('settings.print.signatures.store'), [
                'signature' => $big,
                'name' => 'too big',
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['signature']);

        // Nothing should have been written to storage.
        $files = Storage::disk('public')->allFiles('tenants/'.$this->tenant->id.'/signatures');
        $this->assertEmpty($files);
    }

    public function test_upload_rejects_executable_file(): void
    {
        $exe = UploadedFile::fake()->create('malware.exe', 10, 'application/x-msdownload');

        $response = $this->actingAs($this->user)
            ->postJson(route('settings.print.signatures.store'), [
                'signature' => $exe,
                'name' => 'not an image',
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['signature']);
    }

    public function test_tenant_isolation_prevents_writing_to_other_tenants_directory(): void
    {
        // Create a SECOND tenant + user to act as the attacker. The
        // attacker tries to upload but the file MUST be saved under their
        // own tenant_id, never the original tenant's id.
        $otherTenant = Tenant::factory()->create();
        $otherCenter = Center::factory()->create(['tenant_id' => $otherTenant->id]);
        $attacker = User::factory()->create([
            'tenant_id' => $otherTenant->id,
            'current_center_id' => $otherCenter->id,
        ]);
        $attacker->centers()->attach($otherCenter->id, ['tenant_id' => $otherTenant->id]);

        app(PermissionRegistrar::class)->setPermissionsTeamId($otherTenant->id);
        $attackerRole = Role::firstOrCreate(
            [
                'name' => 'super_admin',
                'guard_name' => 'web',
                'tenant_id' => $otherTenant->id,
            ],
            [
                'label_ar' => 'مدير',
                'label_en' => 'Super',
                'description' => 'x',
            ]
        );
        $attackerRole->syncPermissions([Permissions::COMPANY_MANAGE]);
        $attacker->assignRole($attackerRole);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $attacker->refresh();

        $response = $this->actingAs($attacker)
            ->postJson(route('settings.print.signatures.store'), [
                'signature' => $this->makeValidPng('evil.png'),
                'name' => 'steal',
            ]);

        $response->assertOk();

        // Attacker is successful for their OWN tenant. Now assert
        //   (a) file written under the attacker tenant id
        //   (b) NO file was written under the original tenant id
        $payload = $response->json();
        $this->assertStringContainsString('/storage/tenants/'.$otherTenant->id.'/signatures/', $payload['url']);

        $originalTenantFiles = Storage::disk('public')->allFiles('tenants/'.$this->tenant->id.'/signatures');
        $attackerFiles = Storage::disk('public')->allFiles('tenants/'.$otherTenant->id.'/signatures');

        $this->assertEmpty($originalTenantFiles, 'attacker must NOT have written into the victim tenant directory');
        $this->assertNotEmpty($attackerFiles, 'attacker upload should land in their own tenant directory');
    }

    // ---------------------------------------------------------------
    // Bilingual name flow (name_ar + name_en)
    //
    // Print templates render sig.name_ar and sig.name_en directly,
    // so the FormRequest + controller must accept and persist both
    // labels. These tests pin the contract that:
    //   - The API rejects requests with neither name_ar nor name_en
    //     (the legacy `name` field is still accepted as a fallback).
    //   - The response always contains both name_ar and name_en.
    //   - The persisted print_settings JSON stores both fields.
    // ---------------------------------------------------------------

    public function test_upload_accepts_both_name_ar_and_name_en(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson(route('settings.print.signatures.store'), [
                'signature' => $this->makeValidPng(),
                'name_ar' => 'توقيع المدير',
                'name_en' => 'Manager Signature',
                'document_type' => 'invoice',
            ]);

        $response->assertOk();
        $response->assertJsonFragment(['name_ar' => 'توقيع المدير']);
        $response->assertJsonFragment(['name_en' => 'Manager Signature']);

        // The persisted JSON must contain both fields so the template
        // can render `isRtl ? sig.name_ar : sig.name_en` without nulls.
        $this->tenant->refresh();
        $signatures = $this->tenant->print_settings['documents']['invoice']['signatures'] ?? [];
        $this->assertCount(1, $signatures);
        $this->assertSame('توقيع المدير', $signatures[0]['name_ar']);
        $this->assertSame('Manager Signature', $signatures[0]['name_en']);
    }

    public function test_upload_rejects_request_with_no_name_at_all(): void
    {
        // Neither name_ar nor name_en nor legacy name — must fail.
        $response = $this->actingAs($this->user)
            ->postJson(route('settings.print.signatures.store'), [
                'signature' => $this->makeValidPng(),
                'document_type' => 'invoice',
            ]);

        $response->assertStatus(422);
        // The error must be attached to a name-related field. Laravel will
        // surface this on `name` (the first field declared in rules).
        $response->assertJsonValidationErrors(['name']);
    }

    public function test_upload_accepts_legacy_single_name_as_fallback(): void
    {
        // Older clients (and curl smoke tests) still send only `name`.
        // The FormRequest must accept it via required_without_all, and
        // the controller must copy it into both name_ar and name_en
        // slots so the template never receives an empty pair.
        $response = $this->actingAs($this->user)
            ->postJson(route('settings.print.signatures.store'), [
                'signature' => $this->makeValidPng(),
                'name' => 'Legacy Single Name',
            ]);

        $response->assertOk();
        $response->assertJsonFragment(['name_ar' => 'Legacy Single Name']);
        $response->assertJsonFragment(['name_en' => 'Legacy Single Name']);
    }

    // ---------------------------------------------------------------
    // BUG #1 — array nesting on subsequent POST after PUT
    //
    // The save flow in the index page submits the full `documents` tree
    // as a flat array of signature objects. If a follow-up upload
    // then appended via the existing `$signatures[] = $payload` line,
    // the saved shape became a nested list `[[sig1, sig2], sig3]`.
    // The print template then iterates the outer array and renders
    // arrays instead of signatures, silently breaking the invoice.
    // The fix in appendSignatureToPrintSettings() now flattens both
    // shapes before pushing.
    // ---------------------------------------------------------------

    public function test_subsequent_upload_after_put_saves_does_not_nest_signatures(): void
    {
        // Simulate the Inertia save flow: client sends a flat array
        // of three signatures via PUT /app/settings/system. We write
        // that shape directly here (this test focuses on the POST
        // follow-up, not the PUT itself).
        $this->tenant->print_settings = [
            'documents' => [
                'invoice' => [
                    'signatures' => [
                        ['id' => 'a-uuid', 'name_ar' => 'توقيع 1', 'name_en' => 'Sig 1', 'url' => 'x'],
                        ['id' => 'b-uuid', 'name_ar' => 'توقيع 2', 'name_en' => 'Sig 2', 'url' => 'y'],
                    ],
                ],
            ],
            'visual' => ['active_template' => 'TemplateDefaultA4'],
        ];
        $this->tenant->save();

        // Now follow up with a regular upload. The controller must
        // detect the already-saved array and append, NOT wrap.
        $response = $this->actingAs($this->user)
            ->postJson(route('settings.print.signatures.store'), [
                'signature' => $this->makeValidPng(),
                'name_ar' => 'توقيع 3',
                'name_en' => 'Sig 3',
                'document_type' => 'invoice',
            ]);

        $response->assertOk();

        $this->tenant->refresh();
        $sigs = $this->tenant->print_settings['documents']['invoice']['signatures'] ?? [];

        // Must be a flat list of three, not a nested list.
        $this->assertIsList($sigs, 'signatures must be a flat list');
        $this->assertCount(3, $sigs);
        foreach ($sigs as $sig) {
            $this->assertIsArray($sig);
            $this->assertArrayHasKey('id', $sig, 'each entry must be a signature object with id');
            $this->assertArrayHasKey('name_ar', $sig);
            $this->assertArrayHasKey('name_en', $sig);
        }
    }

    public function test_upload_with_corrupted_nested_signatures_recovers_to_flat(): void
    {
        // Simulate a tenant whose print_settings already contains a
        // nested signatures array (left over from the bug above).
        // The next upload must heal the shape so the template
        // recovers.
        $this->tenant->print_settings = [
            'documents' => [
                'invoice' => [
                    'signatures' => [
                        // nested — array of arrays
                        [
                            ['id' => 'a-uuid', 'name_ar' => 'x', 'name_en' => 'x', 'url' => 'x'],
                            ['id' => 'b-uuid', 'name_ar' => 'y', 'name_en' => 'y', 'url' => 'y'],
                        ],
                    ],
                ],
            ],
            'visual' => ['active_template' => 'TemplateDefaultA4'],
        ];
        $this->tenant->save();

        $response = $this->actingAs($this->user)
            ->postJson(route('settings.print.signatures.store'), [
                'signature' => $this->makeValidPng(),
                'name_ar' => 'توقيع 3',
                'name_en' => 'Sig 3',
                'document_type' => 'invoice',
            ]);

        $response->assertOk();
        $this->tenant->refresh();
        $sigs = $this->tenant->print_settings['documents']['invoice']['signatures'] ?? [];

        // After healing: 3 flat signature objects.
        $this->assertIsList($sigs);
        $this->assertCount(3, $sigs);
    }

    // ---------------------------------------------------------------
    // BUG #2 — DELETE endpoint
    // ---------------------------------------------------------------

    public function test_can_delete_uploaded_signature(): void
    {
        // Upload first.
        $upload = $this->actingAs($this->user)
            ->postJson(route('settings.print.signatures.store'), [
                'signature' => $this->makeValidPng(),
                'name_ar' => 'توقيع للحذف',
                'name_en' => 'Doomed Signature',
                'document_type' => 'invoice',
            ]);
        $upload->assertOk();
        $sigId = $upload->json('id');

        $this->tenant->refresh();
        $this->assertCount(1, $this->tenant->print_settings['documents']['invoice']['signatures'] ?? []);

        // Delete.
        $delete = $this->actingAs($this->user)
            ->deleteJson(route('settings.print.signatures.destroy', $sigId));

        $delete->assertNoContent(); // 204
        $this->tenant->refresh();
        $this->assertCount(0, $this->tenant->print_settings['documents']['invoice']['signatures'] ?? []);
    }

    public function test_delete_missing_signature_returns_404(): void
    {
        $response = $this->actingAs($this->user)
            ->deleteJson(route('settings.print.signatures.destroy', '00000000-0000-0000-0000-000000000000'));
        $response->assertNotFound();
    }

    // ---------------------------------------------------------------
    // BUG #4 — SVG XSS via data:image URI
    //
    // <image xlink:href="data:image/png;base64,..."> is a legitimate
    // way to embed raster data in SVG, BUT it can also be used to
    // embed a base64-encoded HTML payload that the browser would
    // render in the same origin as the signature. We deny any data:
    // URI in the SVG content to be safe.
    // ---------------------------------------------------------------

    public function test_svg_with_data_image_uri_is_rejected(): void
    {
        $svg = '<?xml version="1.0"?><svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><image xlink:href="data:image/png;base64,abc" width="10" height="10"/></svg>';

        $tmp = tempnam(sys_get_temp_dir(), 'sig_xss_');
        file_put_contents($tmp, $svg);
        $file = new UploadedFile($tmp, 'xss.svg', 'image/svg+xml', null, true);

        $response = $this->actingAs($this->user)
            ->postJson(route('settings.print.signatures.store'), [
                'signature' => $file,
                'name_ar' => 'هجوم',
                'name_en' => 'attack',
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['signature']);
    }
}
