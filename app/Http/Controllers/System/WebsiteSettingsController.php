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
                'website_primary_color' => Setting::get('website.primary_color', '#4f46e5'),
                
                'website_contact_email' => Setting::get('website.contact_email', ''),
                'website_contact_phone' => Setting::get('website.contact_phone', ''),
                'website_whatsapp_number' => Setting::get('website.whatsapp_number', ''),
                
                'website_facebook_url' => Setting::get('website.facebook_url', ''),
                'website_twitter_url' => Setting::get('website.twitter_url', ''),
                'website_instagram_url' => Setting::get('website.instagram_url', ''),
                'website_linkedin_url' => Setting::get('website.linkedin_url', ''),
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
                'landing_hero_title_ar' => Setting::get('landing.hero.title.ar', 'أفضل نظام لإدارة مراكز صيانة السيارات'),
                'landing_hero_title_en' => Setting::get('landing.hero.title.en', 'The Best Workshop Management System'),
                'landing_hero_subtitle_ar' => Setting::get('landing.hero.subtitle.ar', 'حل متكامل لإدارة الورش والمخازن والموظفين بكل سهولة وذكاء'),
                'landing_hero_subtitle_en' => Setting::get('landing.hero.subtitle.en', 'Integrated solution for managing workshops, warehouses, and employees.'),
                'landing_hero_cta_text_ar' => Setting::get('landing.hero.cta_text.ar', 'ابدأ تجربتك المجانية الآن'),
                'landing_hero_cta_text_en' => Setting::get('landing.hero.cta_text.en', 'Start Your Free Trial Now'),
                
                'landing_features_title_ar' => Setting::get('landing.features.title.ar', 'لماذا تختار منصتنا؟'),
                'landing_features_title_en' => Setting::get('landing.features.title.en', 'Why Choose Our Platform?'),
                'landing_features_subtitle_ar' => Setting::get('landing.features.subtitle.ar', 'نقدم لك أفضل الأدوات التقنية لتطوير أعمالك وزيادة أرباحك'),
                'landing_features_subtitle_en' => Setting::get('landing.features.subtitle.en', 'We provide the best tools to grow your business.'),
                
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

                // Lists stored as JSON
                'landing_banners_list' => json_decode(Setting::get('landing.banners.list', '[]'), true),
                'landing_features_list' => json_decode(Setting::get('landing.features.list', '[]'), true),
                'landing_faq_list' => json_decode(Setting::get('landing.faq.list', '[]'), true),
                'landing_testimonials_list' => json_decode(Setting::get('landing.testimonials.list', '[]'), true),
                'landing_stats_list' => json_decode(Setting::get('landing.stats.list', '[]'), true),
                'website_custom_pages' => json_decode(Setting::get('website.custom.pages', '[]'), true),

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

            // Map keys
            $dbKey = str_replace('website_', 'website.', $key);
            if (str_starts_with($key, 'landing_')) {
                $dbKey = str_replace('landing_', 'landing.', $key);
                $dbKey = str_replace('_', '.', $dbKey);
            }
            
            Setting::set($dbKey, $value, str_starts_with($key, 'landing_') ? 'landing' : 'website');
        }

        return back()->with('success', 'تم تحديث إعدادات الموقع بنجاح');
    }
}
