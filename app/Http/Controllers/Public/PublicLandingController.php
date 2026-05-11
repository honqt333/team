<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Models\Billing\Plan;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PublicLandingController extends Controller
{
    /**
     * Display the landing page preview.
     */
    public function preview()
    {
        return Inertia::render('Public/LandingPreview', [
            'settings' => $this->getWebsiteSettings(),
            'plans' => Plan::where('is_active', true)->orderBy('sort_order')->get(),
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

        \App\Models\ContactMessage::create($validated);

        return back()->with('success', app()->getLocale() === 'ar' ? 'تم استلام رسالتك بنجاح، سنتواصل معك قريباً!' : 'Message received successfully, we will contact you soon!');
    }

    /**
     * Get all website settings formatted for the landing page.
     */
    private function getWebsiteSettings()
    {
        return [
            'title_ar' => Setting::get('website.title.ar', 'Carag - نظام إدارة مراكز صيانة السيارات'),
            'title_en' => Setting::get('website.title.en', 'Carag - Car Workshop Management System'),
            'description_ar' => Setting::get('website.description.ar', ''),
            'description_en' => Setting::get('website.description.en', ''),
            'keywords' => Setting::get('website.keywords', ''),
            'logo' => Setting::get('website.logo', ''),
            'favicon' => Setting::get('website.favicon', ''),
            'primary_color' => Setting::get('website.primary.color', '#4f46e5'),
            
            'contact_email' => Setting::get('website.contact.email', ''),
            'contact_phone' => Setting::get('website.contact.phone', ''),
            'whatsapp_number' => Setting::get('website.whatsapp.number', ''),
            'whatsapp_floating_enabled' => Setting::get('website.whatsapp.floating.enabled', '0') === '1',
            'whatsapp_floating_number' => Setting::get('website.whatsapp.floating.number', ''),
            
            'chatbot_enabled' => Setting::get('website.chatbot.enabled', '0') === '1',
            'chatbot_script' => Setting::get('website.chatbot.script', ''),
            
            'scripts_header' => Setting::get('website.scripts.header', ''),
            'scripts_footer' => Setting::get('website.scripts.footer', ''),
            
            'announcement_enabled' => Setting::get('website.announcement.enabled', '0') === '1',
            'announcement_text_ar' => Setting::get('website.announcement.text.ar', ''),
            'announcement_text_en' => Setting::get('website.announcement.text.en', ''),
            'announcement_url' => Setting::get('website.announcement.url', ''),
            'announcement_bg_color' => Setting::get('website.announcement.bg.color', '#6366f1'),
            'announcement_text_color' => Setting::get('website.announcement.text.color', '#ffffff'),
            
            'login_button_text_ar' => Setting::get('website.login.button.text.ar', 'دخول'),
            'login_button_text_en' => Setting::get('website.login.button.text.en', 'Login'),
            
            'facebook_url' => Setting::get('website.facebook.url', ''),
            'twitter_url' => Setting::get('website.twitter.url', ''),
            'instagram_url' => Setting::get('website.instagram.url', ''),
            'linkedin_url' => Setting::get('website.linkedin.url', ''),
            'address_ar' => Setting::get('website.address.ar', ''),
            'address_en' => Setting::get('website.address.en', ''),

            'header_menu' => json_decode(Setting::get('website.header.menu', '[]'), true),
            'footer_menu' => json_decode(Setting::get('website.footer.menu', '[]'), true),
            
            'footer_about_ar' => Setting::get('website.footer.about.ar', ''),
            'footer_about_en' => Setting::get('website.footer.about.en', ''),
            'copyright_text_ar' => Setting::get('website.copyright.text.ar', '© ' . date('Y') . ' Carag. جميع الحقوق محفوظة.'),
            'copyright_text_en' => Setting::get('website.copyright.text.en', '© ' . date('Y') . ' Carag. All rights reserved.'),

            // Landing Page Content
            'hero_title_ar' => Setting::get('landing.hero.title.ar', 'مرحباً بك في عصر الإدارة الذكية للورش'),
            'hero_title_en' => Setting::get('landing.hero.title.en', 'Welcome to the Era of Smart Workshop Management'),
            'hero_subtitle_ar' => Setting::get('landing.hero.subtitle.ar', 'النظام الأول المعتمد لإدارة مراكز صيانة السيارات. فحص رقمي، فواتير إلكترونية، إدارة المخزون، ونقاط البيع في منصة واحدة متكاملة.'),
            'hero_subtitle_en' => Setting::get('landing.hero.subtitle.en', 'The certified #1 system for car workshop management. Digital inspections, e-invoices, inventory, and POS in one integrated platform.'),
            'hero_cta_text_ar' => Setting::get('landing.hero.cta.text.ar', 'اشترك الآن مجاناً'),
            'hero_cta_text_en' => Setting::get('landing.hero.cta.text.en', 'Start Your Free Trial Now'),
            
            'features_title_ar' => Setting::get('landing.features.title.ar', 'لماذا نظام Carag هو خيارك الأفضل؟'),
            'features_title_en' => Setting::get('landing.features.title.en', 'Why Carag is Your Best Choice?'),
            'features_subtitle_ar' => Setting::get('landing.features.subtitle.ar', 'بنينا النظام خصيصاً ليناسب احتياجات مراكز الصيانة، لنوفر لك أدوات تقنية متطورة تزيد من كفاءة العمل.'),
            'features_subtitle_en' => Setting::get('landing.features.subtitle.en', 'We built the system specifically for workshop needs, providing advanced tech tools to boost efficiency.'),
            
            'pricing_title_ar' => Setting::get('landing.pricing.title.ar', 'خطط وأسعار مرنة تناسب احتياجاتك'),
            'pricing_title_en' => Setting::get('landing.pricing.title.en', 'Flexible Pricing Plans'),
            'pricing_subtitle_ar' => Setting::get('landing.pricing.subtitle.ar', 'اختر الباقة التي تناسب حجم مركزك وابدأ مسار النجاح معنا'),
            'pricing_subtitle_en' => Setting::get('landing.pricing.subtitle.en', 'Choose the plan that fits your business size.'),

            // Maintenance Mode
            'maintenance_mode' => Setting::get('website.maintenance.mode', '0'),
            'maintenance_title_ar' => Setting::get('website.maintenance.title.ar', 'الموقع تحت الصيانة'),
            'maintenance_title_en' => Setting::get('website.maintenance.title.en', 'Site Under Maintenance'),
            'maintenance_message_ar' => Setting::get('website.maintenance.message.ar', 'نعمل حالياً على تحسين تجربتكم، سنعود قريباً.'),
            'maintenance_message_en' => Setting::get('website.maintenance.message.en', 'We are currently improving your experience, we will be back soon.'),

            // Lists stored as JSON (with rich defaults if empty)
            'banners_list' => json_decode(Setting::get('landing.banners.list', '[]'), true) ?: [],
            'features_list' => json_decode(Setting::get('landing.features.list', '[]'), true) ?: [
                ['title_ar' => 'فحص فني رقمي', 'title_en' => 'Digital Inspection', 'desc_ar' => 'نماذج فحص رقمية احترافية مدعومة بالصور وإرسالها للعميل للموافقة.', 'desc_en' => 'Professional digital inspection templates with photos, sent to clients for approval.'],
                ['title_ar' => 'إدارة شاملة للمخزون', 'title_en' => 'Comprehensive Inventory', 'desc_ar' => 'تتبع قطع الغيار، فواتير المشتريات، وتنبيهات النواقص لحظة بلحظة.', 'desc_en' => 'Track spare parts, purchase invoices, and shortage alerts instantly.'],
                ['title_ar' => 'فواتير إلكترونية معتمدة', 'title_en' => 'Certified E-Invoices', 'desc_ar' => 'متوافق تماماً مع متطلبات هيئة الزكاة والدخل، مع دعم ضريبة القيمة المضافة.', 'desc_en' => 'Fully compliant with ZATCA requirements, with VAT support.'],
                ['title_ar' => 'نقاط البيع السريعة', 'title_en' => 'Fast POS', 'desc_ar' => 'شاشة نقاط بيع سريعة لإصدار الفواتير الفورية بخطوات بسيطة.', 'desc_en' => 'Fast POS screen to issue instant invoices with simple steps.']
            ],
            'faq_list' => json_decode(Setting::get('landing.faq.list', '[]'), true) ?: [
                ['q_ar' => 'هل النظام معتمد من هيئة الزكاة والدخل؟', 'q_en' => 'Is the system ZATCA certified?', 'a_ar' => 'نعم، النظام معتمد بالكامل ويدعم الفوترة الإلكترونية المرحلة الأولى والثانية.', 'a_en' => 'Yes, the system is fully certified and supports e-invoicing phases 1 & 2.'],
                ['q_ar' => 'هل يمكنني إدارة أكثر من فرع؟', 'q_en' => 'Can I manage multiple branches?', 'a_ar' => 'نعم، يدعم النظام إدارة فروع متعددة بصلاحيات منفصلة لكل فرع ومخزون مستقل.', 'a_en' => 'Yes, the system supports managing multiple branches with separate roles and inventory.'],
                ['q_ar' => 'ماذا يحدث إذا انقطع الإنترنت؟', 'q_en' => 'What if the internet disconnects?', 'a_ar' => 'النظام سحابي ويتطلب اتصال بالإنترنت، ولكننا نستخدم خوادم مستقرة تضمن استمرار العمل.', 'a_en' => 'The system is cloud-based and requires internet, but we use stable servers to ensure uptime.']
            ],
            'testimonials_list' => json_decode(Setting::get('landing.testimonials.list', '[]'), true) ?: [
                ['name' => 'AutoCare', 'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/4/44/BMW.svg'],
                ['name' => 'SpeedFix', 'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/a/a1/Volkswagen_Logo_till_1995.svg'],
                ['name' => 'ProGarage', 'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/9/90/Mercedes-Logo.svg'],
                ['name' => 'StarMotors', 'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/1/12/Suzuki_logo_2.svg'],
            ],
            'stats_list' => json_decode(Setting::get('landing.stats.list', '[]'), true) ?: [
                ['value' => '+500', 'label_ar' => 'ورشة وصناعية', 'label_en' => 'Workshops'],
                ['value' => '+1M', 'label_ar' => 'فاتورة مصدرة', 'label_en' => 'Invoices Issued'],
                ['value' => '+100K', 'label_ar' => 'عميل مسجل', 'label_en' => 'Registered Clients'],
                ['value' => '%99.9', 'label_ar' => 'استقرار النظام', 'label_en' => 'Uptime']
            ],
            'custom_pages' => json_decode(Setting::get('website.custom.pages', '[]'), true),

            // Toggles
            'features_enabled' => Setting::get('landing.features.enabled', '1') === '1',
            'pricing_enabled' => Setting::get('landing.pricing.enabled', '1') === '1',
            'stats_enabled' => Setting::get('landing.stats.enabled', '1') === '1',
            'faq_enabled' => Setting::get('landing.faq.enabled', '1') === '1',
            'testimonials_enabled' => Setting::get('landing.testimonials.enabled', '1') === '1',
            'contact_enabled' => Setting::get('landing.contact.enabled', '1') === '1',

            // Legal Pages
            'page_terms_ar' => Setting::get('website.page.terms.ar', ''),
            'page_terms_en' => Setting::get('website.page.terms.en', ''),
            'page_privacy_ar' => Setting::get('website.page.privacy.ar', ''),
            'page_privacy_en' => Setting::get('website.page.privacy.en', ''),
        ];
    }
}
