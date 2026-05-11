<?php

namespace App\Http\Controllers\System;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;
use Inertia\Inertia;

class WebsiteSettingsController extends Controller
{
    public function index()
    {
        return Inertia::render('System/Website/Index', [
            'settings' => [
                'website_title_ar' => Setting::get('website.title.ar', 'Carag - نظام إدارة مراكز صيانة السيارات'),
                'website_title_en' => Setting::get('website.title.en', 'Carag - Car Workshop Management System'),
                'website_description_ar' => Setting::get('website.description.ar', ''),
                'website_description_en' => Setting::get('website.description.en', ''),
                'website_keywords' => Setting::get('website.keywords', ''),
                'website_logo' => Setting::get('website.logo', ''),
                'website_favicon' => Setting::get('website.favicon', ''),
                'website_primary_color' => Setting::get('website.primary.color', '#4f46e5'),
                
                'website_contact_email' => Setting::get('website.contact.email', ''),
                'website_contact_phone' => Setting::get('website.contact.phone', ''),
                'website_whatsapp_number' => Setting::get('website.whatsapp.number', ''),
                
                'website_whatsapp_floating_enabled' => Setting::get('website.whatsapp.floating.enabled', '0'),
                'website_whatsapp_floating_number' => Setting::get('website.whatsapp.floating.number', ''),
                
                'website_chatbot_enabled' => Setting::get('website.chatbot.enabled', '0'),
                'website_chatbot_script' => Setting::get('website.chatbot.script', ''),
                
                'website_scripts_header' => Setting::get('website.scripts.header', ''),
                'website_scripts_footer' => Setting::get('website.scripts.footer', ''),

                'website_announcement_enabled' => Setting::get('website.announcement.enabled', '0'),
                'website_announcement_text_ar' => Setting::get('website.announcement.text.ar', ''),
                'website_announcement_text_en' => Setting::get('website.announcement.text.en', ''),
                'website_announcement_url' => Setting::get('website.announcement.url', ''),
                'website_announcement_bg_color' => Setting::get('website.announcement.bg.color', '#6366f1'),
                'website_announcement_text_color' => Setting::get('website.announcement.text.color', '#ffffff'),

                'website_login_button_text_ar' => Setting::get('website.login.button.text.ar', 'دخول'),
                'website_login_button_text_en' => Setting::get('website.login.button.text.en', 'Login'),

                'website_facebook_url' => Setting::get('website.facebook.url', ''),
                'website_twitter_url' => Setting::get('website.twitter.url', ''),
                'website_instagram_url' => Setting::get('website.instagram.url', ''),
                'website_linkedin_url' => Setting::get('website.linkedin.url', ''),
                'website_address_ar' => Setting::get('website.address.ar', ''),
                'website_address_en' => Setting::get('website.address.en', ''),

                // Lists
                'website_header_menu' => json_decode(Setting::get('website.header.menu', '[]'), true),
                'website_footer_menu' => json_decode(Setting::get('website.footer.menu', '[]'), true),
                
                'website_footer_about_ar' => Setting::get('website.footer.about.ar', ''),
                'website_footer_about_en' => Setting::get('website.footer.about.en', ''),
                'website_copyright_text_ar' => Setting::get('website.copyright.text.ar', '© ' . date('Y') . ' Carag. جميع الحقوق محفوظة.'),
                'website_copyright_text_en' => Setting::get('website.copyright.text.en', '© ' . date('Y') . ' Carag. All rights reserved.'),

                // Landing Page Content
                'landing_hero_title_ar' => Setting::get('landing.hero.title.ar', 'مرحباً بك في عصر الإدارة الذكية للورش'),
                'landing_hero_title_en' => Setting::get('landing.hero.title.en', 'Welcome to the Era of Smart Workshop Management'),
                'landing_hero_subtitle_ar' => Setting::get('landing.hero.subtitle.ar', 'النظام الأول المعتمد لإدارة مراكز صيانة السيارات. فحص رقمي، فواتير إلكترونية، إدارة المخزون، ونقاط البيع في منصة واحدة متكاملة.'),
                'landing_hero_subtitle_en' => Setting::get('landing.hero.subtitle.en', 'The certified #1 system for car workshop management. Digital inspections, e-invoices, inventory, and POS in one integrated platform.'),
                'landing_hero_cta_text_ar' => Setting::get('landing.hero.cta_text.ar', 'اشترك الآن مجاناً'),
                'landing_hero_cta_text_en' => Setting::get('landing.hero.cta_text.en', 'Start Your Free Trial Now'),
                
                'landing_features_title_ar' => Setting::get('landing.features.title.ar', 'لماذا نظام Carag هو خيارك الأفضل؟'),
                'landing_features_title_en' => Setting::get('landing.features.title.en', 'Why Carag is Your Best Choice?'),
                'landing_features_subtitle_ar' => Setting::get('landing.features.subtitle.ar', 'بنينا النظام خصيصاً ليناسب احتياجات مراكز الصيانة، لنوفر لك أدوات تقنية متطورة تزيد من كفاءة العمل.'),
                'landing_features_subtitle_en' => Setting::get('landing.features.subtitle.en', 'We built the system specifically for workshop needs, providing advanced tech tools to boost efficiency.'),
                
                'landing_pricing_title_ar' => Setting::get('landing.pricing.title.ar', 'خطط وأسعار مرنة تناسب احتياجاتك'),
                'landing_pricing_title_en' => Setting::get('landing.pricing.title.en', 'Flexible Pricing Plans'),
                'landing_pricing_subtitle_ar' => Setting::get('landing.pricing.subtitle.ar', 'اختر الباقة التي تناسب حجم مركزك وابدأ مسار النجاح معنا'),
                'landing_pricing_subtitle_en' => Setting::get('landing.pricing.subtitle.en', 'Choose the plan that fits your business size.'),

                // Maintenance Mode
                'website_maintenance_mode' => Setting::get('website.maintenance.mode', '0'),
                'website_maintenance_title_ar' => Setting::get('website.maintenance.title.ar', 'الموقع تحت الصيانة'),
                'website_maintenance_title_en' => Setting::get('website.maintenance.title.en', 'Site Under Maintenance'),
                'website_maintenance_message_ar' => Setting::get('website.maintenance.message.ar', 'نعمل حالياً على تحسين تجربتكم، سنعود قريباً.'),
                'website_maintenance_message_en' => Setting::get('website.maintenance.message.en', 'We are currently improving your experience, we will be back soon.'),

                // Lists stored as JSON (with rich defaults)
                'landing_banners_list' => json_decode(Setting::get('landing.banners.list', '[]'), true) ?: [],
                'landing_features_list' => json_decode(Setting::get('landing.features.list', '[]'), true) ?: [
                    ['title_ar' => 'فحص فني رقمي', 'title_en' => 'Digital Inspection', 'desc_ar' => 'نماذج فحص رقمية احترافية مدعومة بالصور وإرسالها للعميل للموافقة.', 'desc_en' => 'Professional digital inspection templates with photos, sent to clients for approval.'],
                    ['title_ar' => 'إدارة شاملة للمخزون', 'title_en' => 'Comprehensive Inventory', 'desc_ar' => 'تتبع قطع الغيار، فواتير المشتريات، وتنبيهات النواقص لحظة بلحظة.', 'desc_en' => 'Track spare parts, purchase invoices, and shortage alerts instantly.'],
                    ['title_ar' => 'فواتير إلكترونية معتمدة', 'title_en' => 'Certified E-Invoices', 'desc_ar' => 'متوافق تماماً مع متطلبات هيئة الزكاة والدخل، مع دعم ضريبة القيمة المضافة.', 'desc_en' => 'Fully compliant with ZATCA requirements, with VAT support.'],
                    ['title_ar' => 'نقاط البيع السريعة', 'title_en' => 'Fast POS', 'desc_ar' => 'شاشة نقاط بيع سريعة لإصدار الفواتير الفورية بخطوات بسيطة.', 'desc_en' => 'Fast POS screen to issue instant invoices with simple steps.']
                ],
                'landing_faq_list' => json_decode(Setting::get('landing.faq.list', '[]'), true) ?: [
                    ['q_ar' => 'هل النظام معتمد من هيئة الزكاة والدخل؟', 'q_en' => 'Is the system ZATCA certified?', 'a_ar' => 'نعم، النظام معتمد بالكامل ويدعم الفوترة الإلكترونية المرحلة الأولى والثانية.', 'a_en' => 'Yes, the system is fully certified and supports e-invoicing phases 1 & 2.'],
                    ['q_ar' => 'هل يمكنني إدارة أكثر من فرع؟', 'q_en' => 'Can I manage multiple branches?', 'a_ar' => 'نعم، يدعم النظام إدارة فروع متعددة بصلاحيات منفصلة لكل فرع ومخزون مستقل.', 'a_en' => 'Yes, the system supports managing multiple branches with separate roles and inventory.'],
                    ['q_ar' => 'ماذا يحدث إذا انقطع الإنترنت؟', 'q_en' => 'What if the internet disconnects?', 'a_ar' => 'النظام سحابي ويتطلب اتصال بالإنترنت، ولكننا نستخدم خوادم مستقرة تضمن استمرار العمل.', 'a_en' => 'The system is cloud-based and requires internet, but we use stable servers to ensure uptime.']
                ],
                'landing_testimonials_list' => json_decode(Setting::get('landing.testimonials.list', '[]'), true) ?: [
                    ['name' => 'AutoCare', 'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/4/44/BMW.svg'],
                    ['name' => 'SpeedFix', 'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/a/a1/Volkswagen_Logo_till_1995.svg'],
                    ['name' => 'ProGarage', 'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/9/90/Mercedes-Logo.svg'],
                    ['name' => 'StarMotors', 'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/1/12/Suzuki_logo_2.svg'],
                ],
                'landing_stats_list' => json_decode(Setting::get('landing.stats.list', '[]'), true) ?: [
                    ['value' => '+500', 'label_ar' => 'ورشة وصناعية', 'label_en' => 'Workshops'],
                    ['value' => '+1M', 'label_ar' => 'فاتورة مصدرة', 'label_en' => 'Invoices Issued'],
                    ['value' => '+100K', 'label_ar' => 'عميل مسجل', 'label_en' => 'Registered Clients'],
                    ['value' => '%99.9', 'label_ar' => 'استقرار النظام', 'label_en' => 'Uptime']
                ],
                'website_custom_pages' => json_decode(Setting::get('website.custom.pages', '[]'), true),

                // Section Toggles
                'landing_features_enabled' => Setting::get('landing.features.enabled', '1'),
                'landing_pricing_enabled' => Setting::get('landing.pricing.enabled', '1'),
                'landing_stats_enabled' => Setting::get('landing.stats.enabled', '1'),
                'landing_faq_enabled' => Setting::get('landing.faq.enabled', '1'),
                'landing_testimonials_enabled' => Setting::get('landing.testimonials.enabled', '1'),
                'landing_contact_enabled' => Setting::get('landing.contact.enabled', '1'),

                // Legal Pages
                'website_page_terms_ar' => Setting::get('website.page.terms.ar', ''),
                'website_page_terms_en' => Setting::get('website.page.terms.en', ''),
                'website_page_privacy_ar' => Setting::get('website.page.privacy.ar', ''),
                'website_page_privacy_en' => Setting::get('website.page.privacy.en', ''),
            ],
        ]);
    }

    public function update(Request $request)
    {
        $request->validate([
            'settings' => 'required|array',
        ]);

        foreach ($request->settings as $key => $value) {
            // Handle Direct File Uploads (Logo, Favicon)
            if ($request->hasFile("settings.$key")) {
                $file = $request->file("settings.$key");
                $path = $file->store('website', 'public');
                $value = asset('storage/' . $path);
            }

            // Handle Files inside Lists (Banners, etc.)
            if ($key === 'landing_banners_list' && is_array($value)) {
                foreach ($value as $index => &$item) {
                    if ($request->hasFile("settings.landing_banners_list.$index.image")) {
                        $file = $request->file("settings.landing_banners_list.$index.image");
                        $path = $file->store('website/banners', 'public');
                        $item['image'] = asset('storage/' . $path);
                    }
                }
            }

            // Encode arrays to JSON strings
            if (is_array($value)) {
                $value = json_encode($value);
            }

            // Unified Key Mapping Logic
            // Converts website_title_ar -> website.title.ar
            // Converts landing_hero_cta_text_ar -> landing.hero.cta.text.ar
            $dbKey = str_replace('_', '.', $key);
            
            // Special cases where we want to keep some parts together if needed?
            // Actually, in this system, dots are the universal separator.
            
            $group = str_starts_with($dbKey, 'landing.') ? 'landing' : 'website';
            
            Setting::set($dbKey, $value, $group);
        }

        return back()->with('success', 'تم تحديث إعدادات الموقع بنجاح');
    }
}
