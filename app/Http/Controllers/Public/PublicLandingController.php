<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Http\Controllers\System\WebsiteSettingsController;
use App\Models\Billing\Plan;
use App\Models\ContactMessage;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PublicLandingController extends Controller
{
    /**
     * Display the landing page preview.
     */
    public function preview(Request $request)
    {
        $isSystemAdmin = (auth('admin')->check())
            || (auth()->check() && (bool) (auth()->user()->is_system_admin ?? false));

        $isAdminPreview = $isSystemAdmin && $request->has('preview');

        /*
         * The page is reachable at both `/` and `/landing-preview`, and both
         * serve the SAME content. That is duplicate content: the search
         * engines have to pick one, and the ranking signal is split
         * between them.
         *
         * The canonical URL points at `/`, so a plain visit to
         * `/landing-preview` is redirected there instead. The `?preview=1`
         * variant is kept as-is: that is the CMS live-preview iframe, and
         * it must not bounce the operator back to the homepage.
         */
        if (! $request->has('preview') && $request->is('landing-preview')) {
            return redirect('/');
        }

        $settings = $this->getWebsiteSettings();

        return Inertia::render('Public/LandingPreview', [
            'settings' => $settings,
            'plans' => Plan::where('is_active', true)->orderBy('sort_order')->get(),
            'isAdminPreview' => $isAdminPreview,
            /*
             * The canonical URL.
             *
             * The page is reachable at both `/` and `/landing-preview`. The
             * canonical URL points at `/` so the search engines index the
             * one that is meant to be the homepage.
             */
            'canonicalUrl' => url('/'),
            /*
             * Structured data.
             *
             * `SoftwareApplication` describes the product and `FAQPage`
             * exposes the questions already rendered on the page. The FAQ
             * markup is what can earn a rich result in search.
             */
            'jsonLd' => $this->buildJsonLd($settings, url('/')),
        ]);
    }

    /**
     * Build the schema.org JSON-LD graph for the landing page.
     *
     * @param array<string, mixed> $settings
     * @return array<string, mixed>
     */
    private function buildJsonLd(array $settings, string $url): array
    {
        $graph = [
            [
                '@context' => 'https://schema.org',
                '@type' => 'SoftwareApplication',
                'name' => $settings['title_ar'] ?? config('app.name'),
                'url' => $url,
                'applicationCategory' => 'BusinessApplication',
                'operatingSystem' => 'Web',
                'inLanguage' => ['ar', 'en'],
            ],
        ];

        /*
         * The FAQ block is only emitted when there ARE questions. An empty
         * FAQPage is invalid structured data and search engines penalise
         * it, so it is omitted rather than emitted empty.
         */
        $faq = [];

        foreach (($settings['faq_list'] ?? []) as $item) {
            $question = $item['q_ar'] ?? '';
            $answer = $item['a_ar'] ?? '';

            if ($question === '' || $answer === '') {
                continue;
            }

            $faq[] = [
                '@type' => 'Question',
                'name' => $question,
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text' => $answer,
                ],
            ];
        }

        if ($faq !== []) {
            $graph[] = [
                '@context' => 'https://schema.org',
                '@type' => 'FAQPage',
                'mainEntity' => $faq,
            ];
        }

        return count($graph) === 1 ? $graph[0] : ['@graph' => $graph];
    }

    /**
     * Display a custom published page by slug.
     */
    public function showCustomPage(string $slug, Request $request)
    {
        $settings = $this->getWebsiteSettings();
        $customPages = $settings['custom_pages'] ?? [];

        $page = null;

        foreach ($customPages as $p) {
            if (isset($p['slug']) && trim((string) $p['slug'], '/') === trim($slug, '/')) {
                $page = $p;
                break;
            }
        }

        if (! $page) {
            abort(404, 'الصفحة المطلوبة غير موجودة');
        }

        return Inertia::render('Public/CustomPage', [
            'page' => $page,
            'settings' => $settings,
        ]);
    }

    /**
     * Handle the contact form submission.
     */
    public function submitContact(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:25',
            'subject' => 'nullable|string|max:255',
            'message' => 'required|string|max:2000',
        ]);

        ContactMessage::create($validated);

        return back()->with('success', app()->getLocale() === 'ar' ? 'تم استلام رسالتك بنجاح، سنتواصل معك قريباً!' : 'Message received successfully, we will contact you soon!');
    }

    /**
     * Get all website settings formatted for the landing page.
     * Fetched via bulk-cached settings map to eliminate N+1 queries.
     */
    private function getWebsiteSettings(): array
    {
        $raw = WebsiteSettingsController::getPlatformSettingsMap();

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

        return [
            'title_ar' => $get('website.title.ar', config('app.name_ar', 'خدمة برو').' - نظام إدارة مراكز صيانة السيارات'),
            'title_en' => $get('website.title.en', config('app.name', 'Khidmh Pro').' - Workshop Management System'),
            // A meta description is required by SEO and by the social-share
            // preview. When the CMS field is empty we fall back to the hero
            // copy, which is the closest honest summary of the page.
            'description_ar' => $get(
                'website.description.ar',
                $get(
                    'landing.hero.subtitle.ar',
                    'نظام سحابي لإدارة مراكز صيانة السيارات: فحص رقمي، فواتير إلكترونية معتمدة، إدارة مخزون، ونقاط بيع في منصة واحدة.'
                )
            ),
            'description_en' => $get(
                'website.description.en',
                $get(
                    'landing.hero.subtitle.en',
                    'Cloud software for car workshops: digital inspections, certified e-invoicing, inventory and point of sale in one platform.'
                )
            ),
            'keywords' => $get('website.keywords', ''),
            'logo' => $get('website.logo', ''),
            'favicon' => $get('website.favicon', ''),
            'primary_color' => $get('website.primary.color', '#4f46e5'),

            'contact_email' => $get('website.contact.email', ''),
            'contact_phone' => $get('website.contact.phone', ''),
            'whatsapp_number' => $get('website.whatsapp.number', ''),
            'whatsapp_floating_enabled' => $get('website.whatsapp.floating.enabled', '0') === '1',
            'whatsapp_floating_number' => $get('website.whatsapp.floating.number', ''),

            'chatbot_enabled' => $get('website.chatbot.enabled', '0') === '1',
            'chatbot_script' => $get('website.chatbot.script', ''),

            'scripts_header' => $get('website.scripts.header', ''),
            'scripts_footer' => $get('website.scripts.footer', ''),

            'announcement_enabled' => $get('website.announcement.enabled', '0') === '1',
            'announcement_text_ar' => $get('website.announcement.text.ar', ''),
            'announcement_text_en' => $get('website.announcement.text.en', ''),
            'announcement_url' => $get('website.announcement.url', ''),
            'announcement_bg_color' => $get('website.announcement.bg.color', '#6366f1'),
            'announcement_text_color' => $get('website.announcement.text.color', '#ffffff'),

            'login_button_text_ar' => $get('website.login.button.text.ar', 'دخول'),
            'login_button_text_en' => $get('website.login.button.text.en', 'Login'),

            'social_links' => $getJson('website.social.links', []),

            'facebook_url' => $get('website.facebook.url', ''),
            'twitter_url' => $get('website.twitter.url', ''),
            'instagram_url' => $get('website.instagram.url', ''),
            'linkedin_url' => $get('website.linkedin.url', ''),
            'address_ar' => $get('website.address.ar', ''),
            'address_en' => $get('website.address.en', ''),

            'header_menu' => $getJson('website.header.menu', []),
            'footer_menu' => $getJson('website.footer.menu', []),

            'footer_about_ar' => $get('website.footer.about.ar', ''),
            'footer_about_en' => $get('website.footer.about.en', ''),
            'copyright_text_ar' => $get('website.copyright.text.ar', '© '.date('Y').' '.config('app.name_ar', 'خدمة برو').'. جميع الحقوق محفوظة.'),
            'copyright_text_en' => $get('website.copyright.text.en', '© '.date('Y').' '.config('app.name', 'Khidmh Pro').'. All rights reserved.'),

            // Landing Page Content
            /*
             * Hero copy.
             *
             * The old headline was "مرحباً بك في عصر الإدارة الذكية للورش" /
             * "Welcome to the Era of Smart Workshop Management" — the
             * "Welcome to the era of X" construction is the single most
             * over-used SaaS headline on the internet, and the subtitle
             * claimed "النظام الأول المعتمد" (the certified #1 system) with
             * nothing behind it.
             *
             * The new copy says what the product does and who it is for.
             */
            'hero_title_ar' => $get('landing.hero.title.ar', 'أدر مركز صيانتك بكرت عمل واحد'),
            'hero_title_en' => $get('landing.hero.title.en', 'Run your workshop on a single work order'),
            'hero_subtitle_ar' => $get('landing.hero.subtitle.ar', 'من سحب السيارة وحتى تسليمها: الفحص، قطع الغيار، الأجور، والفاتورة الإلكترونية في مكان واحد — مع فروع متعددة ومخزون مستقل لكل فرع.'),
            'hero_subtitle_en' => $get('landing.hero.subtitle.en', 'From vehicle intake to handover: inspection, parts, labour and e-invoicing in one place — with multiple branches and independent stock per branch.'),
            'hero_cta_text_ar' => $get('landing.hero.cta.text.ar', 'ابدأ تجربة مجانية'),
            'hero_cta_text_en' => $get('landing.hero.cta.text.en', 'Start Free Trial'),

            // Hero Typed Animation
            'hero_typed_enabled' => $get('landing.hero.typed.enabled', '1') === '1',
            'hero_typed_prefix_ar' => $get('landing.hero.typed.prefix.ar', 'من استلام السيارة'),
            'hero_typed_prefix_en' => $get('landing.hero.typed.prefix.en', 'From intake'),
            'hero_typed_suffix_ar' => $get('landing.hero.typed.suffix.ar', 'في كرت واحد'),
            'hero_typed_suffix_en' => $get('landing.hero.typed.suffix.en', 'in one work order'),
            'hero_typed_phrases_ar' => $get('landing.hero.typed.phrases.ar', 'الفحص والتشخيص|قطع الغيار والأجور|الفاتورة الإلكترونية|تسليم السيارة'),
            'hero_typed_phrases_en' => $get('landing.hero.typed.phrases.en', 'Inspection & diagnosis|Spare parts & labour|E-invoice|Handing the car back'),

            'features_title_ar' => $get('landing.features.title.ar', 'كل ما يحتاجه مركز الصيانة في نظام واحد'),
            'features_title_en' => $get('landing.features.title.en', 'Everything a workshop needs, in one system'),
            'features_subtitle_ar' => $get('landing.features.subtitle.ar', 'أدوات مبنية على طريقة عمل الورش فعلياً: من سحب السيارة والفحص حتى الفاتورة والتقرير الشهري.'),
            'features_subtitle_en' => $get('landing.features.subtitle.en', 'Built around how workshops actually work: from intake and inspection to invoice and monthly report.'),

            'pricing_title_ar' => $get('landing.pricing.title.ar', 'خطط وأسعار مرنة تناسب احتياجاتك'),
            'pricing_title_en' => $get('landing.pricing.title.en', 'Flexible Pricing Plans'),
            'pricing_subtitle_ar' => $get('landing.pricing.subtitle.ar', 'اختر الباقة التي تناسب حجم مركزك وابدأ مسار النجاح معنا'),
            'pricing_subtitle_en' => $get('landing.pricing.subtitle.en', 'Choose the plan that fits your business size.'),

            // Maintenance Mode
            'maintenance_mode' => $get('website.maintenance.mode', '0'),
            'maintenance_title_ar' => $get('website.maintenance.title.ar', 'الموقع تحت الصيانة'),
            'maintenance_title_en' => $get('website.maintenance.title.en', 'Site Under Maintenance'),
            'maintenance_message_ar' => $get('website.maintenance.message.ar', 'نعمل حالياً على تحسين تجربتكم، سنعود قريباً.'),
            'maintenance_message_en' => $get('website.maintenance.message.en', 'We are currently improving your experience, we will be back soon.'),

            // Lists stored as JSON (with rich defaults if empty)
            'banners_list' => $getJson('landing.banners.list', []),
            'features_list' => $getJson('landing.features.list', [
                ['title_ar' => 'كرت عمل واحد لكل سيارة', 'title_en' => 'One work order per vehicle', 'desc_ar' => 'من استلام السيارة حتى تسليمها: فحص، قطع غيار، اجور، وفاتورة — كلها في كرت واحد يعرضه للعميل بالتفصيل.', 'desc_en' => 'From intake to handover: inspection, parts, labour and invoice all live in one work order the customer can follow in detail.'],
                ['title_ar' => 'مخزون قطع الغيار', 'title_en' => 'Spare parts inventory', 'desc_ar' => 'كل قطعة تدخل أو تخرج من المخزون تُسجَّل تلقائياً مع تنبيه فوري عند بلوغ حد إعادة الطلب.', 'desc_en' => 'Every part moving in or out is recorded automatically, with an immediate alert when it hits its reorder threshold.'],
                ['title_ar' => 'فوترة إلكترونية متوافقة مع الزكاة والدخل', 'title_en' => 'ZATCA-compliant e-invoicing', 'desc_ar' => 'فواتير بضريبة القيمة المضافة بثلاث مراحل: اشتراطية، مبسطة، وربط مع هيئة الزكاة والدخل.', 'desc_en' => 'VAT invoices across all three phases: standard, simplified, and full ZATCA integration.'],
                ['title_ar' => 'فروع متعددة بصلاحيات مستقلة', 'title_en' => 'Multiple branches, separate permissions', 'desc_ar' => 'كل فرع له مخزونه وأسعاره ومستخدموه، مع تقارير موحدة على مستوى المنشأة.', 'desc_en' => 'Each branch keeps its own stock, pricing and staff, while the business sees one consolidated set of reports.'],
                ['title_ar' => 'ساعات عمل محسوبة', 'title_en' => 'Tracked labour hours', 'desc_ar' => 'تسجيل وقت الفني على كل بند في كرت العمل، مع حساب أجر الساعة تلقائياً في الفاتورة.', 'desc_en' => 'Technician time is logged per work-order line, and the hourly charge is calculated into the invoice automatically.'],
                ['title_ar' => 'تقارير أرباح لكل فرع', 'title_en' => 'Profit reports per branch', 'desc_ar' => 'مقارنة الإيرادات والتكاليف وهوامش الربح لكل فرع ولكل نوع خدمة على فترات محددة.', 'desc_en' => 'Compare revenue, cost and profit margin for every branch and every service type over any period.'],
            ]),
            'faq_list' => $getJson('landing.faq.list', [
                ['q_ar' => 'هل النظام يدعم الفوترة الإلكترونية؟', 'q_en' => 'Does the system support e-invoicing?', 'a_ar' => 'نعم، النظام مربوط بتهيئة الزكاة والدخل ويدعم مرحلتي الفوترة الإلكترونية، مع إعداد ضريبة القيمة المضافة لكل مركز.', 'a_en' => 'Yes. The system integrates with ZATCA and supports both e-invoicing phases, with per-workshop VAT settings.'],
                ['q_ar' => 'هل يمكنني إدارة أكثر من فرع؟', 'q_en' => 'Can I manage multiple branches?', 'a_ar' => 'نعم، يدعم النظام إدارة فروع متعددة بصلاحيات منفصلة لكل فرع ومخزون مستقل.', 'a_en' => 'Yes, the system supports managing multiple branches with separate roles and inventory.'],
                ['q_ar' => 'ماذا يحدث إذا انقطع الإنترنت؟', 'q_en' => 'What if the internet disconnects?', 'a_ar' => 'النظام سحابي ويتطلب اتصالاً بالإنترنت. عند انقطاع الاتصال يتم حفظ عملك محلياً في المتصفح حتى عودة الاتصال، دون فقدان بيانات كروت العمل.', 'a_en' => 'The system is cloud-based and requires an internet connection. During an outage your work is kept locally in the browser and synced when the connection returns, with no work-order data lost.'],
                ['q_ar' => 'كم يستغرق تشغيل النظام في مركزي؟', 'q_en' => 'How long does it take to go live?', 'a_ar' => 'يوم واحد لإدخال السيارات والعملاء، ثم إعداد القوالب والأسعار. التدريب على النظام متاح عن بُعد أو في الموقع حسب الباقة.', 'a_en' => 'One day to import your vehicles and customers, then to configure templates and pricing. Training is available remotely or on-site depending on your plan.'],
            ]),
            /*
             * Client logos.
             *
             * The previous defaults pointed at four INVENTED workshops
             * ("AutoCare", "SpeedFix", "ProGarage", "StarMotors") rendered with
             * random Wikimedia car logos (BMW, Volkswagen, Mercedes, Suzuki).
             * That is a false claim to a visitor, and it displayed a
             * competitor's trademark to make it.
             *
             * The list is now empty by default: the section stays hidden
             * until the operator adds logos of real clients. The landing page
             * component also filters out any entry missing a name or URL.
             */
            'testimonials_list' => $getJson('landing.testimonials.list', []),
            /*
             * Stats.
             *
             * These used to ship as "+500 workshops / +1M invoices / +100K
             * clients / 99.9% uptime". None of them were true — the platform
             * had 4 tenants and 9 work orders. The defaults below describe
             * what the product does instead of inventing a customer count.
             */
            'stats_list' => $getJson('landing.stats.list', [
                ['value' => 'ZATCA', 'label_ar' => 'فوترة إلكترونية متكاملة مع هيئة الزكاة والدخل', 'label_en' => 'E-invoicing integrated with ZATCA'],
                ['value' => 'SAR', 'label_ar' => 'ضريبة القيمة المضافة محسوبة على كل فاتورة', 'label_en' => 'VAT calculated on every invoice'],
                ['value' => '24/7', 'label_ar' => 'وصول سحابي من أي جهاز أو فرع', 'label_en' => 'Cloud access from any device or branch'],
                ['value' => '1', 'label_ar' => 'منصة واحدة لإدارة الفروع والمخزون والعملاء', 'label_en' => 'One platform for branches, stock and customers'],
            ]),
            'custom_pages' => $getJson('website.custom.pages', []),

            // Toggles
            'features_enabled' => $get('landing.features.enabled', '1') === '1',
            'pricing_enabled' => $get('landing.pricing.enabled', '1') === '1',
            'stats_enabled' => $get('landing.stats.enabled', '1') === '1',
            'faq_enabled' => $get('landing.faq.enabled', '1') === '1',
            'testimonials_enabled' => $get('landing.testimonials.enabled', '1') === '1',
            'contact_enabled' => $get('landing.contact.enabled', '1') === '1',

            // Legal Pages
            'page_terms_ar' => $get('website.page.terms.ar', ''),
            'page_terms_en' => $get('website.page.terms.en', ''),
            'page_privacy_ar' => $get('website.page.privacy.ar', ''),
            'page_privacy_en' => $get('website.page.privacy.en', ''),
        ];
    }
}
