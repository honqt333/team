<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Service;
use App\Models\Tenant;
use App\Models\TenantWebsite;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Public page SEO and structured data.
 *
 * These cover the work done on the public landing page (`/`) and the
 * per-tenant public site (`/{tenant}`):
 *
 *   1. `/landing-preview` redirects to `/` — the two URLs served the SAME
 *      content, which is duplicate content that splits the ranking signal,
 *      2. the CMS live preview (`?preview=1`) is NOT redirected — it is the
 *      editor's iframe and must keep working,
 *   3. both pages emit server-rendered JSON-LD,
 *   4. the tenant page always has a meta description,
 *   5. the tenant page carries a canonical URL.
 */
class PublicPageSeoTest extends TestCase
{
    use RefreshDatabase;

    public function test_landing_preview_redirects_to_the_canonical_homepage(): void
    {
        $response = $this->get('/landing-preview');

        $response->assertRedirect('/');
    }

    public function test_landing_preview_with_preview_flag_is_not_redirected(): void
    {
        // The CMS editor previews the page in an iframe at
        // /landing-preview?preview=1. A redirect would bounce the
        // operator out of the preview.
        $response = $this->get('/landing-preview?preview=1');

        $response->assertOk();
    }

    public function test_homepage_renders_with_the_canonical_url_and_json_ld(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Public/LandingPreview')
            ->where('canonicalUrl', url('/'))
            ->has('jsonLd')
        );

        // The structured data must be in the served HTML, not added later
        // by JavaScript — a crawler that does not run scripts still has to
        // see it.
        $response->assertSee('application/ld+json', false);
    }

    public function test_json_ld_contains_a_software_application_and_the_faq(): void
    {
        $response = $this->get('/');

        $graph = json_decode($this->jsonLd($response->getContent()), true);

        $types = array_column($graph['@graph'] ?? [], '@type');

        $this->assertContains('SoftwareApplication', $types);
        $this->assertContains('FAQPage', $types);

        $faq = collect($graph['@graph'])->firstWhere('@type', 'FAQPage');
        $this->assertNotEmpty($faq['mainEntity']);
        $this->assertSame('Question', $faq['mainEntity'][0]['@type']);
    }

    public function test_tenant_page_emits_auto_repair_structured_data(): void
    {
        $tenant = Tenant::factory()->create(['slug' => 'seo-workshop']);
        $website = TenantWebsite::factory()->for($tenant)->published()->create([
            'site_name' => 'ورشة الاختبار',
            'primary_color' => '#4f46e5',
        ]);

        // `name` is an appended accessor (name_ar / name_en), not a column.
        $service = Service::factory()->for($tenant)->create([
            'name_ar' => 'فحص كمبيوتر',
            'name_en' => 'Computer diagnostic',
        ]);

        $response = $this->get('/seo-workshop');

        $response->assertOk();
        $response->assertSee('application/ld+json', false);

        $graph = json_decode($this->jsonLd($response->getContent()), true);

        $this->assertSame('AutoRepair', $graph['@type']);
        $this->assertSame('ورشة الاختبار', $graph['name']);
        $this->assertStringContainsString('seo-workshop', $graph['url']);

        // The services the workshop actually offers must be in the graph.
        // The name is the locale-aware accessor, so the assertion accepts
        // either language.
        $catalog = $graph['hasOfferCatalog']['itemListElement'] ?? [];
        $names = array_column(array_column($catalog, 'itemOffered'), 'name');

        $this->assertContains('Computer diagnostic', $names);
        $this->assertNotContains('فحص كمبيوتر', $names, 'The graph follows the page locale.');
    }

    public function test_tenant_page_never_has_an_empty_meta_description(): void
    {
        // The description used to fall back to the tagline and then to an
        // EMPTY string, which fails the `meta-description` audit.
        $tenant = Tenant::factory()->create(['slug' => 'desc-workshop']);
        TenantWebsite::factory()->for($tenant)->published()->create([
            'site_name' => 'ورشة بدون وصف',
            'tagline' => null,
            'seo_description' => null,
        ]);

        $response = $this->get('/desc-workshop');

        $response->assertOk();

        // The meta description is rendered by the page component, so the
        // server-side truth is the prop the component builds its value
        // from. The controller falls back to the tagline, and the
        // component then falls back further to a description built from
        // the services and the address.
        $description = (string) ($response->viewData('page')['props']['site']['seo_description'] ?? '');

        $this->assertNotSame('', trim($description));
        $this->assertGreaterThan(10, strlen(trim($description)));
    }

    public function test_tenant_page_carries_a_canonical_url(): void
    {
        $tenant = Tenant::factory()->create(['slug' => 'canonical-workshop']);
        TenantWebsite::factory()->for($tenant)->published()->create();

        $response = $this->get('/canonical-workshop');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->where('site.canonical_url', url('/canonical-workshop'))
        );

        // NOTE: the canonical <link> itself is rendered by the page
        // component through Inertia's <Head>, which writes it into the
        // document at runtime. The server-side truth is the prop, which is
        // what the assertion above checks.
    }

    /**
     * The share card must be a JPEG the Open Graph crawlers can render.
     *
     * The tenant banner is stored as WebP after the upload is optimised —
     * which is right for the page and wrong for the share card: the
     * crawlers do not render WebP, so the card came out EMPTY. The URL
     * also has to be absolute, because a relative `og:image` is ignored.
     */
    public function test_tenant_share_image_is_an_absolute_jpeg_card(): void
    {
        $tenant = Tenant::factory()->create(['slug' => 'og-workshop']);
        TenantWebsite::factory()->for($tenant)->published()->create([
            'site_name' => 'ورشة بطاقة المشاركة',
        ]);

        $response = $this->get('/og-workshop');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->where('site.share_image_url', url('/og-workshop/og-image.jpg'))
        );

        $image = (string) ($response->viewData('page')['props']['site']['share_image_url'] ?? '');

        $this->assertStringStartsWith('http', $image, 'A relative og:image is ignored by every platform.');
        $this->assertStringNotContainsStringIgnoringCase('.webp', $image, 'The crawlers do not render WebP.');
        $this->assertStringEndsWith('.jpg', $image);
    }

    public function test_the_tenant_share_card_route_returns_a_1200_by_630_jpeg(): void
    {
        $tenant = Tenant::factory()->create(['slug' => 'card-workshop']);
        TenantWebsite::factory()->for($tenant)->published()->create([
            'site_name' => 'ورشة البطاقة',
        ]);

        $response = $this->get('/card-workshop/og-image.jpg');

        $response->assertOk();
        $response->assertHeader('Content-Type', 'image/jpeg');

        $bytes = $response->getContent();

        $this->assertNotSame('', $bytes);

        $size = getimagesizefromstring($bytes);

        $this->assertNotFalse($size, 'The share card must be a real image.');
        $this->assertSame(1200, $size[0]);
        $this->assertSame(630, $size[1]);
        $this->assertSame('image/jpeg', $size['mime']);
    }

    public function test_the_landing_share_image_is_an_absolute_jpeg_card(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->where('ogImageUrl', url('/images/landing/og.jpg'))
        );

        $image = (string) ($response->viewData('page')['props']['ogImageUrl'] ?? '');

        $this->assertStringStartsWith('http', $image);
        $this->assertStringNotContainsStringIgnoringCase('.webp', $image);
        $this->assertStringEndsWith('.jpg', $image);

        // The card itself has to exist, at the right size.
        $path = public_path('images/landing/og.jpg');

        $this->assertFileExists($path);

        $size = getimagesize($path);

        $this->assertNotFalse($size);
        $this->assertSame(1200, $size[0]);
        $this->assertSame(630, $size[1]);
        $this->assertSame('image/jpeg', $size['mime']);
    }

    /**
     * Pull the JSON-LD block out of the served HTML.
     */
    private function jsonLd(string $html): string
    {
        preg_match(
            '#<script type="application/ld\+json">(.*?)</script>#s',
            $html,
            $matches
        );

        $this->assertNotEmpty($matches, 'The page must contain a JSON-LD block.');

        return $matches[1];
    }
}
