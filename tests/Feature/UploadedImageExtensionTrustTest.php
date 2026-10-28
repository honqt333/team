<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Center;
use App\Models\Tenant;
use App\Models\TenantWebsite;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Tests for the extension of an uploaded image (audit finding #40).
 *
 * The name of an uploaded file is chosen by the client, and the logo of
 * the tenant website used to be stored with THAT extension, on the
 * public disk, which the web server serves directly.
 *
 * Laravel blocks a handful of dangerous extensions (`.php`, `.phtml`,
 * `.phar`) by name before the validation rules run, but the check is a
 * list, not a rule: a JPEG that also carries a script and is named
 * `logo.html` passes both the `image` rule and the `mimes` rule — the
 * content is a real JPEG — and used to be written to disk as
 * `logo.html`. The web server then serves it as HTML, and the script
 * runs inside the application's own origin.
 *
 * The extension now comes from the bytes, and SVG is refused.
 */
class UploadedImageExtensionTrustTest extends TestCase
{
    use RefreshDatabase;

    /** A real 1x1 PNG. */
    private const PNG = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=';

    private Tenant $tenant;

    private Center $center;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        Permission::findOrCreate('settings.website.view', 'web');
        Permission::findOrCreate('settings.website.manage', 'web');
        Permission::findOrCreate('settings.company.manage', 'web');

        $this->tenant = Tenant::factory()->create(['status' => 'active']);
        $this->center = Center::factory()->create(['tenant_id' => $this->tenant->id]);
        $this->user = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'current_center_id' => $this->center->id,
        ]);
        $this->user->centers()->attach($this->center->id, ['tenant_id' => $this->tenant->id]);

        app(PermissionRegistrar::class)->setPermissionsTeamId($this->tenant->id);
        $this->user->givePermissionTo([
            'settings.website.view',
            'settings.website.manage',
            'settings.company.manage',
        ]);
    }

    /**
     * A real file on disk, so the mime type is read from the CONTENT —
     * which is what happens with a real upload. `UploadedFile::fake()`
     * guesses the type from the file NAME, and would hide the very
     * attack this test is about: a file named `logo.php` whose content
     * is a real PNG passes the image checks and used to be stored under
     * the name the client chose.
     */
    private function uploadedPng(string $name): UploadedFile
    {
        return $this->uploadedWithContent($name, base64_decode(self::PNG));
    }

    /**
     * A file that IS a valid image for the mime check, and also carries
     * a script — the polyglot that made the client-chosen extension
     * dangerous.
     */
    private function uploadedJpegWithScript(string $name): UploadedFile
    {
        $jpeg = "\xFF\xD8\xFF\xE0\x00\x10JFIF\x00\x01\x01\x00\x00\x01\x00\x01\x00\x00";

        return $this->uploadedWithContent($name, $jpeg.'<script>alert(document.domain)</script>');
    }

    private function uploadedWithContent(string $name, string $content): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'img');
        file_put_contents($path, $content);

        return new UploadedFile($path, $name, null, null, true);
    }

    private function uploadedSvg(string $name): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'img');
        file_put_contents(
            $path,
            '<svg xmlns="http://www.w3.org/2000/svg" width="10" height="10">'
            .'<script>alert(document.domain)</script></svg>'
        );

        return new UploadedFile($path, $name, null, null, true);
    }

    private function websitePayload(): array
    {
        return [
            'site_name' => 'مركز الاختبار',
            'tagline' => 'تجربة',
            'primary_color' => '#abcdef',
            'secondary_color' => '#123456',
            'status' => TenantWebsite::STATUS_DRAFT,
            'theme_key' => 'default',
            'contact_phone' => '0500000000',
            'contact_email' => 'test@example.com',
        ];
    }

    public function test_the_website_logo_is_stored_with_an_extension_taken_from_the_bytes(): void
    {
        $response = $this->actingAsWithTeam($this->user)
            ->put(route('settings.website.update'), $this->websitePayload() + [
                'logo' => $this->uploadedJpegWithScript('logo.html'),
            ]);

        $response->assertRedirect();

        $website = TenantWebsite::where('tenant_id', $this->tenant->id)->first();

        $this->assertNotNull($website->logo_path);
        $this->assertStringEndsWith('.jpg', $website->logo_path, 'The extension must come from the bytes, not from the file name.');
        $this->assertStringNotContainsString('.html', $website->logo_path, 'A file named .html served from the public disk is executed as HTML by the browser.');
        Storage::disk('public')->assertExists($website->logo_path);
    }

    public function test_the_website_logo_refuses_an_svg(): void
    {
        $response = $this->actingAsWithTeam($this->user)
            ->put(route('settings.website.update'), $this->websitePayload() + [
                'logo' => $this->uploadedSvg('logo.svg'),
            ]);

        $response->assertSessionHasErrors('logo');

        $website = TenantWebsite::where('tenant_id', $this->tenant->id)->first();

        $this->assertTrue(
            $website === null || $website->logo_path === null,
            'A refused upload must not leave a logo behind.'
        );
        $this->assertCount(0, Storage::disk('public')->files('tenants/'.$this->tenant->id.'/website'));
    }

    public function test_a_refused_logo_keeps_the_previous_one(): void
    {
        $user = $this->actingAsWithTeam($this->user);

        $user->put(route('settings.website.update'), $this->websitePayload() + [
            'logo' => $this->uploadedPng('logo.png'),
        ]);

        $website = TenantWebsite::where('tenant_id', $this->tenant->id)->first();
        $firstPath = $website->logo_path;

        $this->assertNotNull($firstPath);
        Storage::disk('public')->assertExists($firstPath);

        // A rejected upload must not take the existing logo away.
        $user->put(route('settings.website.update'), $this->websitePayload() + [
            'logo' => $this->uploadedSvg('logo.svg'),
        ])->assertSessionHasErrors('logo');

        $website->refresh();

        $this->assertSame($firstPath, $website->logo_path);
        Storage::disk('public')->assertExists($firstPath);
    }

    private function uploadedPhp(string $name): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'img');
        file_put_contents($path, '<?php echo "pwned"; ?>');

        return new UploadedFile($path, $name, null, null, true);
    }

    public function test_the_company_logo_is_stored_with_an_extension_taken_from_the_bytes(): void
    {
        $response = $this->actingAsWithTeam($this->user)
            ->post(route('settings.company.logo.upload'), [
                'logo' => $this->uploadedJpegWithScript('logo.html'),
            ]);

        $response->assertOk();

        $this->tenant->refresh();

        $this->assertNotNull($this->tenant->logo_path);
        $this->assertStringEndsWith('.jpg', $this->tenant->logo_path);
        $this->assertStringNotContainsString('.html', $this->tenant->logo_path, 'A file named .html served from the public disk is executed as HTML by the browser.');
        Storage::disk('public')->assertExists($this->tenant->logo_path);
    }

    public function test_the_company_logo_refuses_a_file_that_is_not_an_image(): void
    {
        $response = $this->actingAsWithTeam($this->user)
            ->post(route('settings.company.logo.upload'), [
                'logo' => $this->uploadedPhp('logo.png'),
            ]);

        $response->assertSessionHasErrors('logo');
        $this->assertNull($this->tenant->fresh()->logo_path);
    }
}
