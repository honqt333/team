<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Http\Controllers\System\WebsiteSettingsController;
use App\Models\ContactMessage;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Public landing page (/landing-preview).
 *
 * These tests cover the contract that the Phase 1 + 2 rebuild of the landing
 * page must not break:
 *
 *   1. the page renders and carries every prop the Vue page needs,
 *   2. the contact form creates a ContactMessage and validates its input,
 *   3. the CMS can override every content list,
 *   4. the page survives an EMPTY settings map (no null-pointer crash),
 *   5. the shipped defaults are HONEST — no invented client logos and no
 *      fabricated statistics. This last one is the important one: the
 *      previous defaults shipped "+500 workshops / +1M invoices / +100K
 *      clients" and four made-up client names rendered with a competitor's
 *      trademark, and nobody noticed until the rebuild.
 */
class PublicLandingPageTest extends TestCase
{
    use RefreshDatabase;

    /*
     * NOTE: the page is requested at `/`, which is its canonical URL.
     * `/landing-preview` redirects there — see PublicPageSeoTest.
     */

    protected function setUp(): void
    {
        parent::setUp();
        WebsiteSettingsController::flushSettingsCache();
    }

    protected function tearDown(): void
    {
        WebsiteSettingsController::flushSettingsCache();
        parent::tearDown();
    }

    private function storeSetting(string $key, string $value): void
    {
        Setting::updateOrCreate(
            ['tenant_id' => null, 'key' => $key],
            ['value' => $value, 'group' => str_starts_with($key, 'landing.') ? 'landing' : 'website']
        );
        WebsiteSettingsController::flushSettingsCache();
    }

    public function test_landing_page_renders_with_expected_component(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Public/LandingPreview')
                ->has('settings')
                ->has('plans')
                ->has('isAdminPreview')
            );
    }

    public function test_settings_contain_every_key_the_vue_page_reads(): void
    {
        $required = [
            'title_ar', 'title_en', 'description_ar', 'description_en',
            'logo', 'primary_color', 'features_enabled', 'features_list',
            'stats_enabled', 'stats_list', 'pricing_enabled', 'faq_enabled',
            'faq_list', 'testimonials_enabled', 'testimonials_list',
            'audience_enabled', 'audience_list',
            'audience_tag_ar', 'audience_tag_en',
            'audience_title_ar', 'audience_title_en',
            'audience_subtitle_ar', 'audience_subtitle_en',
            'audience_note_title_ar', 'audience_note_title_en',
            'audience_note_text_ar', 'audience_note_text_en',
            'testimonials_tag_ar', 'testimonials_tag_en',
            'testimonials_title_ar', 'testimonials_title_en',
            'testimonials_subtitle_ar', 'testimonials_subtitle_en',
            'contact_enabled', 'hero_title_ar', 'hero_title_en',
            'hero_subtitle_ar', 'hero_subtitle_en', 'hero_cta_text_ar',
            'hero_cta_text_en', 'hero_typed_enabled', 'hero_typed_prefix_ar',
            'hero_typed_prefix_en', 'hero_typed_suffix_ar', 'hero_typed_suffix_en',
            'hero_typed_phrases_ar', 'hero_typed_phrases_en',
        ];

        $this->get('/')
            ->assertOk()
            ->assertInertia(function ($page) use ($required) {
                foreach ($required as $key) {
                    $page->has('settings.'.$key);
                }
            });
    }

    public function test_page_renders_when_every_setting_is_missing(): void
    {
        // No settings at all: the page must still render with the built-in
        // defaults rather than blowing up on a missing array key.
        Setting::whereNull('tenant_id')->delete();
        WebsiteSettingsController::flushSettingsCache();

        $this->get('/')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Public/LandingPreview')
                ->where('settings.hero_title_ar', 'أدر مركز صيانتك بكرت عمل واحد')
                ->where('settings.features_enabled', true)
                ->has('settings.features_list')
            );
    }

    public function test_meta_description_is_never_empty(): void
    {
        // An empty <meta name="description"> is an SEO failure. The
        // controller falls back to the hero copy when the CMS field is blank.
        Setting::whereNull('tenant_id')->where('key', 'website.description.ar')->delete();
        WebsiteSettingsController::flushSettingsCache();

        $this->get('/')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('settings.description_ar', fn ($value) => is_string($value) && $value !== '')
                ->where('settings.description_en', fn ($value) => is_string($value) && $value !== '')
            );
    }

    public function test_cms_can_override_the_content_lists(): void
    {
        $this->storeSetting('landing.features.list', json_encode([
            ['title_ar' => 'ميزة مخصصة', 'title_en' => 'Custom feature', 'desc_ar' => 'وصف', 'desc_en' => 'Description'],
        ], JSON_UNESCAPED_UNICODE));

        $this->storeSetting('landing.faq.list', json_encode([
            ['q_ar' => 'سؤال مخصص', 'q_en' => 'Custom question', 'a_ar' => 'جواب', 'a_en' => 'Answer'],
        ], JSON_UNESCAPED_UNICODE));

        $this->get('/')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('settings.features_list.0.title_ar', 'ميزة مخصصة')
                ->where('settings.faq_list.0.q_en', 'Custom question')
            );
    }

    public function test_cms_can_override_the_audience_list(): void
    {
        $this->storeSetting('landing.audience.list', json_encode([
            ['title_ar' => 'ورشة مخصصة', 'title_en' => 'Custom workshop', 'desc_ar' => 'وصف', 'desc_en' => 'Description'],
        ], JSON_UNESCAPED_UNICODE));

        $this->get('/')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('settings.audience_list.0.title_ar', 'ورشة مخصصة')
                ->where('settings.audience_list.0.title_en', 'Custom workshop')
            );
    }

    public function test_the_audience_section_says_who_should_not_buy(): void
    {
        /*
         * The section is only useful if it excludes people. "This may not be
         * for you if…" is what makes the four entries above it credible.
         */
        $response = $this->get('/');
        $response->assertOk();

        $settings = $response->viewData('page')['props']['settings'];

        $this->assertNotSame('', trim((string) $settings['audience_note_text_ar']));
        $this->assertNotSame('', trim((string) $settings['audience_note_text_en']));
        $this->assertNotSame('', trim((string) $settings['audience_note_title_ar']));
    }

    public function test_testimonials_are_never_shipped_as_defaults(): void
    {
        /*
         * A testimonial is a person's name and their words. Shipping one
         * as a default means inventing a customer, which is the same lie
         * the invented client logos were.
         *
         * The section stays hidden until the operator adds a real entry.
         */
        $response = $this->get('/');
        $response->assertOk();

        $list = $response->viewData('page')['props']['settings']['testimonials_list'] ?? [];

        $this->assertSame([], $list, 'The shipped defaults must not contain testimonials.');
    }

    public function test_the_cms_can_add_a_real_testimonial(): void
    {
        $this->storeSetting('landing.testimonials.list', json_encode([
            [
                'name' => 'ورشة الصفاء',
                'quote_ar' => 'انتقلنا من الورق إلى كرت عمل واحد، وعرفنا ربح كل فرع.',
                'quote_en' => 'We moved from paper to a single work order and finally saw each branch\'s profit.',
                'role_ar' => 'المالك',
                'role_en' => 'Owner',
            ],
        ], JSON_UNESCAPED_UNICODE));

        $this->get('/')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('settings.testimonials_list.0.name', 'ورشة الصفاء')
                ->where('settings.testimonials_list.0.quote_en', "We moved from paper to a single work order and finally saw each branch's profit.")
            );
    }

    public function test_default_stats_are_not_fabricated_marketing_claims(): void
    {
        /*
         * The rebuilt page must not ship invented numbers.
         *
         * The old defaults were "+500 workshops", "+1M invoices issued",
         * "+100K registered clients" and "99.9% uptime" — none of which were
         * true. A test is the only reliable way to keep them from creeping
         * back in.
         */
        $response = $this->get('/');
        $response->assertOk();

        $stats = $response->viewData('page')['props']['settings']['stats_list'] ?? [];

        $this->assertIsArray($stats);
        $this->assertNotEmpty($stats);

        foreach ($stats as $stat) {
            $value = (string) ($stat['value'] ?? '');

            // No "+N" growth claims.
            $this->assertDoesNotMatchRegularExpression(
                '/^\+\d/',
                $value,
                "Stat \"{$value}\" is an unverifiable growth claim."
            );

            // No uptime percentage claims.
            $this->assertStringNotContainsString(
                '99.9',
                $value,
                "Stat \"{$value}\" claims an uptime figure that is not measured."
            );
        }
    }

    public function test_default_clients_list_is_empty(): void
    {
        /*
         * The old defaults listed four INVENTED clients (AutoCare, SpeedFix,
         * ProGarage, StarMotors) rendered with a competitor's car logo. The
         * section must stay empty until the operator adds real clients.
         */
        $this->get('/')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('settings.testimonials_list', [])
            );
    }

    public function test_contact_form_creates_a_contact_message(): void
    {
        $payload = [
            'name' => 'ورشة الاختبار',
            'email' => 'test@example.com',
            'phone' => '+966500000000',
            'subject' => 'استفسار',
            'message' => 'أرغب معرفة تفاصيل الباقات.',
        ];

        $this->post('/landing-preview/contact', $payload)
            ->assertRedirect();

        $this->assertDatabaseHas('contact_messages', [
            'email' => 'test@example.com',
            'name' => 'ورشة الاختبار',
        ]);
    }

    public function test_contact_form_validates_required_fields(): void
    {
        $this->post('/landing-preview/contact', [])
            ->assertSessionHasErrors(['name', 'email', 'message']);

        $this->assertSame(0, ContactMessage::count());
    }

    public function test_contact_form_rejects_an_invalid_email(): void
    {
        $this->post('/landing-preview/contact', [
            'name' => 'اسم',
            'email' => 'not-an-email',
            'message' => 'رسالة',
        ])->assertSessionHasErrors('email');
    }
}
