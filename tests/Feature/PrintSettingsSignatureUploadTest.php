<?php

namespace Tests\Feature;

use App\Models\Center;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use App\Support\Permissions;
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
        $this->seed(\Database\Seeders\PermissionsSeeder::class);

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

    /**
     * Helper: build an SVG file with arbitrary body content. Used by the
     * XSS-hardening tests to inject malicious markup into a real SVG
     * envelope (so the file passes the MIME check but should fail the
     * XSS validator).
     */
    private function makeSvgWithBody(string $body, string $name = 'evil.svg'): UploadedFile
    {
        $tmp = tempnam(sys_get_temp_dir(), 'sig_xss_');
        $xml = '<?xml version="1.0" encoding="UTF-8"?><svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="20" height="20">'.$body.'</svg>';
        file_put_contents($tmp, $xml);

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
    // SVG XSS hardening tests
    //
    // Each test loads an SVG envelope with a specific malicious payload
    // and asserts that the validator rejects it (422). The valid-PNG
    // happy path is unchanged — these tests only prove the new SVG
    // content validator does what it says.
    // ---------------------------------------------------------------

    public function test_svg_with_script_tag_is_rejected(): void
    {
        $evil = $this->makeSvgWithBody('<script>alert(1)</script><rect width="20" height="20" fill="#000"/>');

        $response = $this->actingAs($this->user)
            ->postJson(route('settings.print.signatures.store'), [
                'signature' => $evil,
                'name' => 'has script',
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['signature']);

        $files = Storage::disk('public')->allFiles('tenants/'.$this->tenant->id.'/signatures');
        $this->assertEmpty($files, 'malicious SVG must not be written to disk');
    }

    public function test_svg_with_foreign_object_is_rejected(): void
    {
        $evil = $this->makeSvgWithBody('<foreignObject width="20" height="20"><body xmlns="http://www.w3.org/1999/xhtml"><script>alert(1)</script></body></foreignObject>');

        $response = $this->actingAs($this->user)
            ->postJson(route('settings.print.signatures.store'), [
                'signature' => $evil,
                'name' => 'has foreignObject',
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['signature']);
    }

    public function test_svg_with_javascript_uri_is_rejected(): void
    {
        $evil = $this->makeSvgWithBody('<a xlink:href="javascript:alert(1)"><rect width="20" height="20" fill="#000"/></a>');

        $response = $this->actingAs($this->user)
            ->postJson(route('settings.print.signatures.store'), [
                'signature' => $evil,
                'name' => 'has javascript:',
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['signature']);
    }

    public function test_svg_with_event_handler_attribute_is_rejected(): void
    {
        // onload= is the classic SVG XSS vector. We test several forms
        // to make sure the regex catches all of them.
        $cases = [
            '<rect onload="alert(1)" width="20" height="20" fill="#000"/>',
            '<rect width="20" height="20" fill="#000" onerror="alert(1)"/>',
            '<rect onclick = "alert(1)" width="20" height="20" fill="#000"/>',
            '<rect onbegin="alert(1)" width="20" height="20" fill="#000"/>',
            '<g onmouseover="alert(1)"><rect width="20" height="20" fill="#000"/></g>',
        ];

        foreach ($cases as $i => $body) {
            $evil = $this->makeSvgWithBody($body);
            $response = $this->actingAs($this->user)
                ->postJson(route('settings.print.signatures.store'), [
                    'signature' => $evil,
                    'name' => "case {$i}",
                ]);

            $message = "case {$i} should be rejected: {$body}";
            $response->assertStatus(422);
            $response->assertJsonValidationErrors(['signature']);
            $this->assertTrue(true, $message);
        }
    }

    public function test_svg_with_data_text_html_uri_is_rejected(): void
    {
        $evil = $this->makeSvgWithBody('<image xlink:href="data:text/html;base64,PHNjcmlwdD5hbGVydCgxKTwvc2NyaXB0Pg==" width="20" height="20"/>');

        $response = $this->actingAs($this->user)
            ->postJson(route('settings.print.signatures.store'), [
                'signature' => $evil,
                'name' => 'data uri',
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['signature']);
    }

    public function test_svg_with_iframe_or_embed_is_rejected(): void
    {
        $cases = [
            '<iframe src="javascript:alert(1)"></iframe>',
            '<embed src="https://evil.example.com/payload.swf"/>',
            '<object data="https://evil.example.com/payload"></object>',
            '<handler xmlns="http://www.w3.org/2000/svg" type="application/ecmascript" ev:event="load">alert(1)</handler>',
            '<listener xmlns="http://www.w3.org/2001/xml-events" event="load" handler="#x"/>',
        ];

        foreach ($cases as $i => $body) {
            $evil = $this->makeSvgWithBody($body);
            $response = $this->actingAs($this->user)
                ->postJson(route('settings.print.signatures.store'), [
                    'signature' => $evil,
                    'name' => "case {$i}",
                ]);

            $message = "case {$i} should be rejected: {$body}";
            $response->assertStatus(422);
            $response->assertJsonValidationErrors(['signature']);
            $this->assertTrue(true, $message);
        }
    }

    public function test_svg_with_external_entity_dtd_is_rejected(): void
    {
        $evil = $this->makeSvgWithBody('<!DOCTYPE svg [ <!ENTITY xxe SYSTEM "file:///etc/passwd"> ]><rect width="20" height="20" fill="#000"/>');

        $response = $this->actingAs($this->user)
            ->postJson(route('settings.print.signatures.store'), [
                'signature' => $evil,
                'name' => 'has DTD',
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['signature']);
    }

    public function test_svg_with_xslt_stylesheet_directive_is_rejected(): void
    {
        $tmp = tempnam(sys_get_temp_dir(), 'sig_xslt_');
        file_put_contents(
            $tmp,
            '<?xml version="1.0" encoding="UTF-8"?><?xml-stylesheet type="text/xsl" href="https://evil.example.com/payload.xsl"?><svg xmlns="http://www.w3.org/2000/svg" width="20" height="20"><rect width="20" height="20" fill="#000"/></svg>'
        );
        $evil = new UploadedFile($tmp, 'xslt.svg', 'image/svg+xml', null, true);

        $response = $this->actingAs($this->user)
            ->postJson(route('settings.print.signatures.store'), [
                'signature' => $evil,
                'name' => 'has xslt',
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['signature']);
    }

    public function test_clean_svg_with_paths_and_curves_still_uploads(): void
    {
        // This is the "happy path" for SVG: a signature made of stroke
        // and path elements. The new validator must NOT reject this.
        $tmp = tempnam(sys_get_temp_dir(), 'sig_clean_');
        file_put_contents(
            $tmp,
            '<?xml version="1.0" encoding="UTF-8"?>'.
            '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 200 80">'.
            '<path d="M10 60 Q 50 10 100 60 T 190 60" stroke="#000" stroke-width="3" fill="none"/>'.
            '<circle cx="100" cy="40" r="3" fill="#000"/>'.
            '</svg>'
        );
        $clean = new UploadedFile($tmp, 'clean.svg', 'image/svg+xml', null, true);

        $response = $this->actingAs($this->user)
            ->postJson(route('settings.print.signatures.store'), [
                'signature' => $clean,
                'name' => 'clean vector',
            ]);

        $response->assertOk();
        $response->assertJsonFragment(['name' => 'clean vector']);
        $response->assertJsonFragment(['mime_type' => 'image/svg+xml']);
    }
}
