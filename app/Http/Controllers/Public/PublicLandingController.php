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

        return Inertia::render('Public/LandingPreview', [
            'settings' => $this->getWebsiteSettings(),
            'plans' => Plan::where('is_active', true)->orderBy('sort_order')->get(),
            'isAdminPreview' => $isAdminPreview,
        ]);
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
            'title_ar' => $get('website.title.ar', 'Carag - نظام إدارة مراكز صيانة السيارات'),
            'title_en' => $get('website.title.en', 'Carag - Car Workshop Management System'),
            'description_ar' => $get('website.description.ar', ''),
            'description_en' => $get('website.description.en', ''),
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
            'copyright_text_ar' => $get('website.copyright.text.ar', '© '.date('Y').' Carag. جميع الحقوق محفوظة.'),
            'copyright_text_en' => $get('website.copyright.text.en', '© '.date('Y').' Carag. All rights reserved.'),

            // Landing Page Content
            'hero_title_ar' => $get('landing.hero.title.ar', 'مرحباً بك في عصر الإدارة الذكية للورش'),
            'hero_title_en' => $get('landing.hero.title.en', 'Welcome to the Era of Smart Workshop Management'),
            'hero_subtitle_ar' => $get('landing.hero.subtitle.ar', 'النظام الأول المعتمد لإدارة مراكز صيانة السيارات. فحص رقمي، فواتير إلكترونية، إدارة المخزون، ونقاط البيع في منصة واحدة متكاملة.'),
            'hero_subtitle_en' => $get('landing.hero.subtitle.en', 'The certified #1 system for car workshop management. Digital inspections, e-invoices, inventory, and POS in one integrated platform.'),
            'hero_cta_text_ar' => $get('landing.hero.cta.text.ar', 'اشترك الآن مجاناً'),
            'hero_cta_text_en' => $get('landing.hero.cta.text.en', 'Start Your Free Trial Now'),

            // Hero Typed Animation
            'hero_typed_enabled' => $get('landing.hero.typed.enabled', '1') === '1',
            'hero_typed_prefix_ar' => $get('landing.hero.typed.prefix.ar', 'أدر'),
            'hero_typed_prefix_en' => $get('landing.hero.typed.prefix.en', 'Elevate'),
            'hero_typed_suffix_ar' => $get('landing.hero.typed.suffix.ar', 'بذكاء حقيقي'),
            'hero_typed_suffix_en' => $get('landing.hero.typed.suffix.en', 'With Real Intelligence'),
            'hero_typed_phrases_ar' => $get('landing.hero.typed.phrases.ar', 'مركزك الآن|أرباحك اليوم|فريقك معك|مستقبلك هنا'),
            'hero_typed_phrases_en' => $get('landing.hero.typed.phrases.en', 'Your Workshop|Your Revenue|Your Team|Your Future'),

            'features_title_ar' => $get('landing.features.title.ar', 'لماذا نظام Carag هو خيارك الأفضل؟'),
            'features_title_en' => $get('landing.features.title.en', 'Why Carag is Your Best Choice?'),
            'features_subtitle_ar' => $get('landing.features.subtitle.ar', 'بنينا النظام خصيصاً ليناسب احتياجات مراكز الصيانة، لنوفر لك أدوات تقنية متطورة تزيد من كفاءة العمل.'),
            'features_subtitle_en' => $get('landing.features.subtitle.en', 'We built the system specifically for workshop needs, providing advanced tech tools to boost efficiency.'),

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
                ['title_ar' => 'فحص فني رقمي', 'title_en' => 'Digital Inspection', 'desc_ar' => 'نماذج فحص رقمية احترافية مدعومة بالصور وإرسالها للعميل للموافقة.', 'desc_en' => 'Professional digital inspection templates with photos, sent to clients for approval.'],
                ['title_ar' => 'إدارة شاملة للمخزون', 'title_en' => 'Comprehensive Inventory', 'desc_ar' => 'تتبع قطع الغيار، فواتير المشتريات، وتنبيهات النواقص لحظة بلحظة.', 'desc_en' => 'Track spare parts, purchase invoices, and shortage alerts instantly.'],
                ['title_ar' => 'فواتير إلكترونية معتمدة', 'title_en' => 'Certified E-Invoices', 'desc_ar' => 'متوافق تماماً مع متطلبات هيئة الزكاة والدخل، مع دعم ضريبة القيمة المضافة.', 'desc_en' => 'Fully compliant with ZATCA requirements, with VAT support.'],
                ['title_ar' => 'نقاط البيع السريعة', 'title_en' => 'Fast POS', 'desc_ar' => 'شاشة نقاط بيع سريعة لإصدار الفواتير الفورية بخطوات بسيطة.', 'desc_en' => 'Fast POS screen to issue instant invoices with simple steps.'],
            ]),
            'faq_list' => $getJson('landing.faq.list', [
                ['q_ar' => 'هل النظام معتمد من هيئة الزكاة والدخل؟', 'q_en' => 'Is the system ZATCA certified?', 'a_ar' => 'نعم، النظام معتمد بالكامل ويدعم الفوترة الإلكترونية المرحلة الأولى والثانية.', 'a_en' => 'Yes, the system is fully certified and supports e-invoicing phases 1 & 2.'],
                ['q_ar' => 'هل يمكنني إدارة أكثر من فرع؟', 'q_en' => 'Can I manage multiple branches?', 'a_ar' => 'نعم، يدعم النظام إدارة فروع متعددة بصلاحيات منفصلة لكل فرع ومخزون مستقل.', 'a_en' => 'Yes, the system supports managing multiple branches with separate roles and inventory.'],
                ['q_ar' => 'ماذا يحدث إذا انقطع الإنترنت؟', 'q_en' => 'What if the internet disconnects?', 'a_ar' => 'النظام سحابي ويتطلب اتصال بالإنترنت، ولكننا نستخدم خوادم مستقرة تضمن استمرار العمل.', 'a_en' => 'The system is cloud-based and requires internet, but we use stable servers to ensure uptime.'],
            ]),
            'testimonials_list' => $getJson('landing.testimonials.list', [
                ['name' => 'AutoCare', 'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/4/44/BMW.svg'],
                ['name' => 'SpeedFix', 'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/a/a1/Volkswagen_Logo_till_1995.svg'],
                ['name' => 'ProGarage', 'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/9/90/Mercedes-Logo.svg'],
                ['name' => 'StarMotors', 'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/1/12/Suzuki_logo_2.svg'],
            ]),
            'stats_list' => $getJson('landing.stats.list', [
                ['value' => '+500', 'label_ar' => 'ورشة وصناعية', 'label_en' => 'Workshops'],
                ['value' => '+1M', 'label_ar' => 'فاتورة مصدرة', 'label_en' => 'Invoices Issued'],
                ['value' => '+100K', 'label_ar' => 'عميل مسجل', 'label_en' => 'Registered Clients'],
                ['value' => '%99.9', 'label_ar' => 'استقرار النظام', 'label_en' => 'Uptime'],
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
