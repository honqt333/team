<?php

declare(strict_types=1);

namespace App\Http\Controllers\System;

use App\Http\Controllers\Controller;
use App\Http\Requests\WebsiteSettingsUpdateRequest;
use App\Models\AdminUser;
use App\Models\Setting;
use App\Models\User;
use App\Services\Media\ImageOptimizer;
use DOMDocument;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class WebsiteSettingsController extends Controller
{
    public const CACHE_KEY = 'system.website_settings';

    /**
     * Strict allowlist of settings keys that can be managed via this controller.
     * Prevents arbitrary key injection into the settings table.
     */
    public const ALLOWED_KEYS = [
        // Visual & Identity
        'website_title_ar',
        'website_title_en',
        'website_description_ar',
        'website_description_en',
        'website_keywords',
        'website_logo',
        'website_favicon',
        'website_primary_color',

        // Contact
        'website_contact_email',
        'website_contact_phone',
        'website_whatsapp_number',
        'website_whatsapp_floating_enabled',
        'website_whatsapp_floating_number',

        // Integrations & Scripts
        'website_chatbot_enabled',
        'website_chatbot_script',
        'website_scripts_header',
        'website_scripts_footer',

        // Announcement Bar
        'website_announcement_enabled',
        'website_announcement_text_ar',
        'website_announcement_text_en',
        'website_announcement_url',
        'website_announcement_bg_color',
        'website_announcement_text_color',

        // Header & Navigation
        'website_login_button_text_ar',
        'website_login_button_text_en',
        'website_header_menu',
        'website_footer_menu',

        // Social & Address
        'website_social_links',
        'website_facebook_url',
        'website_twitter_url',
        'website_instagram_url',
        'website_linkedin_url',
        'website_address_ar',
        'website_address_en',

        // Footer
        'website_footer_about_ar',
        'website_footer_about_en',
        'website_copyright_text_ar',
        'website_copyright_text_en',

        // Landing Hero
        'landing_hero_title_ar',
        'landing_hero_title_en',
        'landing_hero_subtitle_ar',
        'landing_hero_subtitle_en',
        'landing_hero_cta_text_ar',
        'landing_hero_cta_text_en',

        // Hero Typed Animation
        'landing_hero_typed_enabled',
        'landing_hero_typed_prefix_ar',
        'landing_hero_typed_prefix_en',
        'landing_hero_typed_suffix_ar',
        'landing_hero_typed_suffix_en',
        'landing_hero_typed_phrases_ar',
        'landing_hero_typed_phrases_en',

        // Section Headings & Subtitles
        'landing_features_title_ar',
        'landing_features_title_en',
        'landing_features_subtitle_ar',
        'landing_features_subtitle_en',
        'landing_pricing_title_ar',
        'landing_pricing_title_en',
        'landing_pricing_subtitle_ar',
        'landing_pricing_subtitle_en',

        // Section Toggles
        'landing_features_enabled',
        'landing_pricing_enabled',
        'landing_stats_enabled',
        'landing_faq_enabled',
        'landing_testimonials_enabled',
        'landing_contact_enabled',

        // Maintenance Mode
        'website_maintenance_mode',
        'website_maintenance_title_ar',
        'website_maintenance_title_en',
        'website_maintenance_message_ar',
        'website_maintenance_message_en',

        // Lists
        'landing_banners_list',
        'landing_features_list',
        'landing_faq_list',
        'landing_testimonials_list',
        'landing_stats_list',
        'website_custom_pages',

        // Legal Pages
        'website_page_terms_ar',
        'website_page_terms_en',
        'website_page_privacy_ar',
        'website_page_privacy_en',
    ];

    /**
     * Fetch all platform website & landing settings in a single bulk query with caching.
     *
     * @return array<string, string|null>
     */
    public static function getPlatformSettingsMap(): array
    {
        return Cache::remember(self::CACHE_KEY, 3600, function () {
            return Setting::whereNull('tenant_id')
                ->where(function ($query) {
                    $query->where('key', 'like', 'website.%')
                        ->orWhere('key', 'like', 'landing.%');
                })
                ->pluck('value', 'key')
                ->toArray();
        });
    }

    /**
     * Clear the cached settings map.
     */
    public static function flushSettingsCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /**
     * Display the CMS website settings form.
     */
    public function index(Request $request): Response
    {
        $raw = self::getPlatformSettingsMap();
        $canManageScripts = $this->actorCanManageScripts($request->user());

        $get = function (string $dbKey, $default = '') use ($raw) {
            $val = $raw[$dbKey] ?? null;

            return ($val !== null && $val !== '') ? $val : $default;
        };

        $getJson = function (string $dbKey, array $default = []) use ($raw) {
            $val = $raw[$dbKey] ?? null;

            if ($val === null || $val === '') {
                return $default;
            }

            $decoded = json_decode((string) $val, true);

            return is_array($decoded) && ! empty($decoded) ? $decoded : $default;
        };

        return Inertia::render('System/Website/Index', [
            'settings' => [
                'website_title_ar' => $get('website.title.ar', config('app.name_ar', 'خدمة برو').' - نظام إدارة مراكز صيانة السيارات'),
                'website_title_en' => $get('website.title.en', config('app.name', 'Khidmh Pro').' - Workshop Management System'),
                'website_description_ar' => $get('website.description.ar', ''),
                'website_description_en' => $get('website.description.en', ''),
                'website_keywords' => $get('website.keywords', ''),
                'website_logo' => $get('website.logo', ''),
                'website_favicon' => $get('website.favicon', ''),
                'website_primary_color' => $get('website.primary.color', '#2563eb'),

                'website_contact_email' => $get('website.contact.email', ''),
                'website_contact_phone' => $get('website.contact.phone', ''),
                'website_whatsapp_number' => $get('website.whatsapp.number', ''),
                'website_whatsapp_floating_enabled' => $get('website.whatsapp.floating.enabled', '0'),
                'website_whatsapp_floating_number' => $get('website.whatsapp.floating.number', ''),

                'website_chatbot_enabled' => $get('website.chatbot.enabled', '0'),
                // Raw scripts execute on the public page, so only a
                // super admin may read or edit them. Other system
                // admins get empty values and the UI hides the fields.
                'website_chatbot_script' => $canManageScripts ? $get('website.chatbot.script', '') : '',
                'website_scripts_header' => $canManageScripts ? $get('website.scripts.header', '') : '',
                'website_scripts_footer' => $canManageScripts ? $get('website.scripts.footer', '') : '',

                'website_announcement_enabled' => $get('website.announcement.enabled', '0'),
                'website_announcement_text_ar' => $get('website.announcement.text.ar', ''),
                'website_announcement_text_en' => $get('website.announcement.text.en', ''),
                'website_announcement_url' => $get('website.announcement.url', ''),
                'website_announcement_bg_color' => $get('website.announcement.bg.color', '#6366f1'),
                'website_announcement_text_color' => $get('website.announcement.text.color', '#ffffff'),

                'website_login_button_text_ar' => $get('website.login.button.text.ar', 'دخول'),
                'website_login_button_text_en' => $get('website.login.button.text.en', 'Login'),

                'website_social_links' => $getJson('website.social.links', []),
                'website_facebook_url' => $get('website.facebook.url', ''),
                'website_twitter_url' => $get('website.twitter.url', ''),
                'website_instagram_url' => $get('website.instagram.url', ''),
                'website_linkedin_url' => $get('website.linkedin.url', ''),
                'website_address_ar' => $get('website.address.ar', ''),
                'website_address_en' => $get('website.address.en', ''),

                // Lists
                'website_header_menu' => $getJson('website.header.menu', []),
                'website_footer_menu' => $getJson('website.footer.menu', []),
                'website_footer_about_ar' => $get('website.footer.about.ar', ''),
                'website_footer_about_en' => $get('website.footer.about.en', ''),
                'website_copyright_text_ar' => $get('website.copyright.text.ar', '© '.date('Y').' '.config('app.name_ar', 'خدمة برو').'. جميع الحقوق محفوظة.'),
                'website_copyright_text_en' => $get('website.copyright.text.en', '© '.date('Y').' '.config('app.name', 'Khidmh Pro').'. All rights reserved.'),

                // Landing Page Content
                'landing_hero_title_ar' => $get('landing.hero.title.ar', 'مرحباً بك في عصر الإدارة الذكية للورش'),
                'landing_hero_title_en' => $get('landing.hero.title.en', 'Welcome to the Era of Smart Workshop Management'),
                'landing_hero_subtitle_ar' => $get('landing.hero.subtitle.ar', 'النظام الأول المعتمد لإدارة مراكز صيانة السيارات. فحص رقمي، فواتير إلكترونية، إدارة المخزون، ونقاط البيع في منصة واحدة متكاملة.'),
                'landing_hero_subtitle_en' => $get('landing.hero.subtitle.en', 'The certified #1 system for car workshop management. Digital inspections, e-invoices, inventory, and POS in one integrated platform.'),
                'landing_hero_cta_text_ar' => $get('landing.hero.cta.text.ar', 'اشترك الآن مجاناً'),
                'landing_hero_cta_text_en' => $get('landing.hero.cta.text.en', 'Start Your Free Trial Now'),

                // Hero Typed Animation
                'landing_hero_typed_enabled' => $get('landing.hero.typed.enabled', '1'),
                'landing_hero_typed_prefix_ar' => $get('landing.hero.typed.prefix.ar', 'أدر'),
                'landing_hero_typed_prefix_en' => $get('landing.hero.typed.prefix.en', 'Elevate'),
                'landing_hero_typed_suffix_ar' => $get('landing.hero.typed.suffix.ar', 'بذكاء حقيقي'),
                'landing_hero_typed_suffix_en' => $get('landing.hero.typed.suffix.en', 'With Real Intelligence'),
                'landing_hero_typed_phrases_ar' => $get('landing.hero.typed.phrases.ar', 'مركزك الآن|أرباحك اليوم|فريقك معك|مستقبلك هنا'),
                'landing_hero_typed_phrases_en' => $get('landing.hero.typed.phrases.en', 'Your Workshop|Your Revenue|Your Team|Your Future'),

                'landing_features_title_ar' => $get('landing.features.title.ar', 'لماذا نظام '.config('app.name_ar', 'خدمة برو').' هو خيارك الأفضل؟'),
                'landing_features_title_en' => $get('landing.features.title.en', 'Why '.config('app.name', 'Khidmh Pro').' is Your Best Choice?'),
                'landing_features_subtitle_ar' => $get('landing.features.subtitle.ar', 'بنينا النظام خصيصاً ليناسب احتياجات مراكز الصيانة، لنوفر لك أدوات تقنية متطورة تزيد من كفاءة العمل.'),
                'landing_features_subtitle_en' => $get('landing.features.subtitle.en', 'We built the system specifically for workshop needs, providing advanced tech tools to boost efficiency.'),

                'landing_pricing_title_ar' => $get('landing.pricing.title.ar', 'خطط وأسعار مرنة تناسب احتياجاتك'),
                'landing_pricing_title_en' => $get('landing.pricing.title.en', 'Flexible Pricing Plans'),
                'landing_pricing_subtitle_ar' => $get('landing.pricing.subtitle.ar', 'اختر الباقة التي تناسب حجم مركزك وابدأ مسار النجاح معنا'),
                'landing_pricing_subtitle_en' => $get('landing.pricing.subtitle.en', 'Choose the plan that fits your business size.'),

                // Maintenance Mode
                'website_maintenance_mode' => $get('website.maintenance.mode', '0'),
                'website_maintenance_title_ar' => $get('website.maintenance.title.ar', 'الموقع تحت الصيانة'),
                'website_maintenance_title_en' => $get('website.maintenance.title.en', 'Site Under Maintenance'),
                'website_maintenance_message_ar' => $get('website.maintenance.message.ar', 'نعمل حالياً على تحسين تجربتكم، سنعود قريباً.'),
                'website_maintenance_message_en' => $get('website.maintenance.message.en', 'We are currently improving your experience, we will be back soon.'),

                // Lists stored as JSON (with rich defaults)
                'landing_banners_list' => $getJson('landing.banners.list', []),
                'landing_features_list' => $getJson('landing.features.list', [
                    ['title_ar' => 'فحص فني رقمي', 'title_en' => 'Digital Inspection', 'desc_ar' => 'نماذج فحص رقمية احترافية مدعومة بالصور وإرسالها للعميل للموافقة.', 'desc_en' => 'Professional digital inspection templates with photos, sent to clients for approval.'],
                    ['title_ar' => 'إدارة شاملة للمخزون', 'title_en' => 'Comprehensive Inventory', 'desc_ar' => 'تتبع قطع الغيار، فواتير المشتريات، وتنبيهات النواقص لحظة بلحظة.', 'desc_en' => 'Track spare parts, purchase invoices, and shortage alerts instantly.'],
                    ['title_ar' => 'فواتير إلكترونية معتمدة', 'title_en' => 'Certified E-Invoices', 'desc_ar' => 'متوافق تماماً مع متطلبات هيئة الزكاة والدخل، مع دعم ضريبة القيمة المضافة.', 'desc_en' => 'Fully compliant with ZATCA requirements, with VAT support.'],
                    ['title_ar' => 'نقاط البيع السريعة', 'title_en' => 'Fast POS', 'desc_ar' => 'شاشة نقاط بيع سريعة لإصدار الفواتير الفورية بخطوات بسيطة.', 'desc_en' => 'Fast POS screen to issue instant invoices with simple steps.'],
                ]),
                'landing_faq_list' => $getJson('landing.faq.list', [
                    ['q_ar' => 'هل النظام معتمد من هيئة الزكاة والدخل؟', 'q_en' => 'Is the system ZATCA certified?', 'a_ar' => 'نعم، النظام معتمد بالكامل ويدعم الفوترة الإلكترونية المرحلة الأولى والثانية.', 'a_en' => 'Yes, the system is fully certified and supports e-invoicing phases 1 & 2.'],
                    ['q_ar' => 'هل يمكنني إدارة أكثر من فرع؟', 'q_en' => 'Can I manage multiple branches?', 'a_ar' => 'نعم، يدعم النظام إدارة فروع متعددة بصلاحيات منفصلة لكل فرع ومخزون مستقل.', 'a_en' => 'Yes, the system supports managing multiple branches with separate roles and inventory.'],
                    ['q_ar' => 'ماذا يحدث إذا انقطع الإنترنت؟', 'q_en' => 'What if the internet disconnects?', 'a_ar' => 'النظام سحابي ويتطلب اتصال بالإنترنت، ولكننا نستخدم خوادم مستقرة تضمن استمرار العمل.', 'a_en' => 'The system is cloud-based and requires internet, but we use stable servers to ensure uptime.'],
                ]),
                'landing_testimonials_list' => $getJson('landing.testimonials.list', []),
                /*
                 * Stats.
                 *
                 * These used to ship as "+500 workshops / +1M invoices /
                 * +100K clients / 99.9% uptime". None of them were true, and
                 * the database holds 4 tenants and 9 work orders, so the
                 * numbers were not even close.
                 *
                 * The defaults below are honest: they describe what the
                 * product actually does, not how many customers it allegedly
                 * has. Real customer counts belong here only once they can be
                 * read from the database.
                 */
                'landing_stats_list' => $getJson('landing.stats.list', [
                    ['value' => 'ZATCA', 'label_ar' => 'فوترة إلكترونية متكاملة مع هيئة الزكاة والدخل', 'label_en' => 'E-invoicing integrated with ZATCA'],
                    ['value' => 'SAR', 'label_ar' => 'ضريبة القيمة المضافة محسوبة على كل فاتورة', 'label_en' => 'VAT calculated on every invoice'],
                    ['value' => '24/7', 'label_ar' => 'وصول سحابي من أي جهاز أو فرع', 'label_en' => 'Cloud access from any device or branch'],
                    ['value' => '1', 'label_ar' => 'منصة واحدة لإدارة الفروع والمخزون والعملاء', 'label_en' => 'One platform for branches, stock and customers'],
                ]),
                'website_custom_pages' => $getJson('website.custom.pages', []),

                // Section Toggles
                'landing_features_enabled' => $get('landing.features.enabled', '1'),
                'landing_pricing_enabled' => $get('landing.pricing.enabled', '1'),
                'landing_stats_enabled' => $get('landing.stats.enabled', '1'),
                'landing_faq_enabled' => $get('landing.faq.enabled', '1'),
                'landing_testimonials_enabled' => $get('landing.testimonials.enabled', '1'),
                'landing_contact_enabled' => $get('landing.contact.enabled', '1'),

                // Legal Pages
                'website_page_terms_ar' => $get('website.page.terms.ar', ''),
                'website_page_terms_en' => $get('website.page.terms.en', ''),
                'website_page_privacy_ar' => $get('website.page.privacy.ar', ''),
                'website_page_privacy_en' => $get('website.page.privacy.en', ''),
            ],
            'canManageScripts' => $canManageScripts,
        ]);
    }

    /**
     * Update website and landing page settings securely with allowlist enforcement.
     */
    public function update(WebsiteSettingsUpdateRequest $request)
    {
        $submitted = $request->input('settings', []);
        $allowedLookup = array_flip(self::ALLOWED_KEYS);

        $user = $request->user();
        $isSuperAdmin = $this->actorCanManageScripts($user);

        $restrictedScriptKeys = [
            'website_scripts_header',
            'website_scripts_footer',
            'website_chatbot_script',
        ];

        foreach ($submitted as $key => $value) {
            // Strictly reject / ignore any key not present in the allowlist
            if (! isset($allowedLookup[$key])) {
                continue;
            }

            // High-risk raw script injection is restricted to super administrators
            if (in_array($key, $restrictedScriptKeys, true) && ! $isSuperAdmin) {
                continue;
            }

            // Encode arrays to JSON strings
            if (is_array($value)) {
                $value = json_encode($value, JSON_UNESCAPED_UNICODE);
            } elseif ($value === null) {
                $value = '';
            }

            // Convert website_title_ar -> website.title.ar
            $dbKey = str_replace('_', '.', $key);
            $group = str_starts_with($dbKey, 'landing.') ? 'landing' : 'website';

            Setting::set($dbKey, (string) $value, $group);
        }

        // Invalidate settings cache
        self::flushSettingsCache();

        // Audit Trail: Log action if triggered by an AdminUser
        if ($user instanceof AdminUser) {
            $user->logActivity('update', 'WebsiteSettings', null, [
                'updated_keys' => array_keys($submitted),
                'ip' => $request->ip(),
            ]);
        }

        return back()->with('success', __('messages.system.website.saved'));
    }

    /**
     * Upload an image (logo, favicon, or banner) and return its public URL.
     * Called via dedicated AJAX endpoint to avoid multipart/form-data issues
     * and ensure strict MIME / SVG security validation.
     */
    public function uploadImage(Request $request): JsonResponse
    {
        $request->validate([
            'key' => 'required|string|in:website_logo,website_favicon,banner_image',
            'image' => 'required|file|mimes:png,jpg,jpeg,webp,svg|max:4096',
        ]);

        $file = $request->file('image');

        // SVG security inspection against Stored XSS
        $extension = strtolower($file->getClientOriginalExtension());
        $mime = $file->getClientMimeType();

        if ($extension === 'svg' || str_contains($mime, 'svg')) {
            $content = (string) file_get_contents($file->getRealPath());

            if (! $this->isSafeSvg($content)) {
                return response()->json(['message' => __('messages.system.svg_unsafe')], 422);
            }
        }

        $folder = $request->key === 'banner_image' ? 'website/banners' : 'website';
        $path = $file->store($folder, 'public');
        $url = asset('storage/'.$path);

        /*
         * The favicon and the logo are special: they are downloaded on
         * EVERY page of the application, and they were stored at whatever
         * size they were uploaded at — the favicon in the database was a
         * 68 KB PNG for an icon that renders at 16 px.
         *
         * They are rewritten in place, under the same path, so the URL
         * stored in the settings does not change.
         */
        if ($request->key === 'website_favicon' || $request->key === 'website_logo') {
            $disk = Storage::disk('public');
            $absolute = $disk->path($path);

            if (is_file($absolute)) {
                $binary = (string) file_get_contents($absolute);

                $optimised = $request->key === 'website_favicon'
                    ? ImageOptimizer::favicon($binary)
                    : ImageOptimizer::logo($binary);

                if ($optimised->binary !== $binary && $optimised->binary !== '') {
                    file_put_contents($absolute, $optimised->binary);
                }
            }
        }

        // Immediate persistence for logo & favicon
        if ($request->key === 'website_logo') {
            Setting::set('website.logo', $url, 'website');
            self::flushSettingsCache();
        } elseif ($request->key === 'website_favicon') {
            Setting::set('website.favicon', $url, 'website');
            self::flushSettingsCache();
        }

        // Audit Trail: Log image upload
        $user = $request->user();

        if ($user instanceof AdminUser) {
            $user->logActivity('upload', 'WebsiteSettings', null, [
                'key' => $request->key,
                'url' => $url,
                'ip' => $request->ip(),
            ]);
        }

        return response()->json(['url' => $url]);
    }

    /**
     * Raw scripts may only be read, previewed or saved by the highest
     * platform role. Everyone else can still manage the rest of the
     * website settings.
     */
    public function actorCanManageScripts(?Authenticatable $user): bool
    {
        return ($user instanceof AdminUser && $user->isSuperAdmin())
            || ($user instanceof User && (bool) ($user->is_system_admin ?? false));
    }

    /**
     * Strictly validate an uploaded SVG against XSS, event handlers,
     * foreignObject/script/animate elements, and external entity injection.
     */
    private function isSafeSvg(string $content): bool
    {
        if ($content === '' || preg_match('/<!(?:DOCTYPE|ENTITY)/i', $content)) {
            return false;
        }

        $dom = new DOMDocument;
        $previousErrorSetting = libxml_use_internal_errors(true);

        try {
            $loaded = $dom->loadXML($content, LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING);
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previousErrorSetting);
        }

        if (! $loaded || ! $dom->documentElement || strtolower($dom->documentElement->localName) !== 'svg') {
            return false;
        }

        $allowedTags = [
            'svg', 'g', 'path', 'circle', 'rect', 'line', 'polyline', 'polygon',
            'ellipse', 'defs', 'lineargradient', 'radialgradient', 'stop',
            'title', 'desc', 'clippath', 'mask', 'pattern', 'symbol', 'use',
            'text', 'tspan',
        ];

        $elements = $dom->getElementsByTagName('*');

        foreach ($elements as $element) {
            if (! in_array(strtolower($element->localName), $allowedTags, true)) {
                return false;
            }

            if (! $element->hasAttributes()) {
                continue;
            }

            foreach ($element->attributes as $attribute) {
                $name = strtolower($attribute->nodeName);
                $localName = strtolower($attribute->localName);
                $value = trim($attribute->nodeValue);

                if (str_starts_with($localName, 'on') || str_starts_with($name, 'on')) {
                    return false;
                }

                if ($localName === 'href' && $value !== '' && ! str_starts_with($value, '#')) {
                    return false;
                }

                if ($localName === 'style' && preg_match('/(?:url\s*\(|expression\s*\(|javascript:|data:)/i', $value)) {
                    return false;
                }
            }
        }

        return true;
    }
}
