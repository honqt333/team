<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class WebsiteSettingsUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return [
            'settings' => 'required|array',

            // Images — stored as URLs after upload, validated as string paths/URLs here
            'settings.website_logo' => 'nullable|string|max:2048',
            'settings.website_favicon' => 'nullable|string|max:2048',

            // Text fields with length caps
            'settings.website_title_ar' => 'nullable|string|max:500',
            'settings.website_title_en' => 'nullable|string|max:500',
            'settings.website_description_ar' => 'nullable|string|max:2000',
            'settings.website_description_en' => 'nullable|string|max:2000',
            'settings.website_keywords' => 'nullable|string|max:1000',

            // Contact
            'settings.website_contact_email' => 'nullable|email|max:255',
            'settings.website_contact_phone' => 'nullable|string|max:50',
            'settings.website_whatsapp_number' => 'nullable|string|max:50',

            // Colors (hex)
            'settings.website_primary_color' => ['nullable', 'string', 'max:20', 'regex:/^#([0-9a-fA-F]{6}|[0-9a-fA-F]{3})$/'],
            'settings.website_announcement_bg_color' => ['nullable', 'string', 'max:20', 'regex:/^#([0-9a-fA-F]{6}|[0-9a-fA-F]{3})$/'],
            'settings.website_announcement_text_color' => ['nullable', 'string', 'max:20', 'regex:/^#([0-9a-fA-F]{6}|[0-9a-fA-F]{3})$/'],

            // URLs and Links (supports full URLs and internal paths/anchors)
            'settings.website_announcement_url' => 'nullable|string|max:2048',
            'settings.website_facebook_url' => 'nullable|string|max:2048',
            'settings.website_twitter_url' => 'nullable|string|max:2048',
            'settings.website_instagram_url' => 'nullable|string|max:2048',
            'settings.website_linkedin_url' => 'nullable|string|max:2048',

            // Scripts — allow HTML but cap size to prevent abuse
            'settings.website_scripts_header' => 'nullable|string|max:100000',
            'settings.website_scripts_footer' => 'nullable|string|max:100000',
            'settings.website_chatbot_script' => 'nullable|string|max:100000',

            // Boolean toggles (stored as 0/1 strings)
            'settings.website_announcement_enabled' => 'nullable|in:0,1',
            'settings.website_chatbot_enabled' => 'nullable|in:0,1',
            'settings.website_maintenance_mode' => 'nullable|in:0,1',
            'settings.website_whatsapp_floating_enabled' => 'nullable|in:0,1',

            // Landing page section toggles
            'settings.landing_features_enabled' => 'nullable|in:0,1',
            'settings.landing_pricing_enabled' => 'nullable|in:0,1',
            'settings.landing_stats_enabled' => 'nullable|in:0,1',
            'settings.landing_faq_enabled' => 'nullable|in:0,1',
            'settings.landing_testimonials_enabled' => 'nullable|in:0,1',
            'settings.landing_contact_enabled' => 'nullable|in:0,1',

            // Typed animation toggles
            'settings.landing_hero_typed_enabled' => 'nullable|in:0,1',

            // List fields
            'settings.landing_banners_list' => 'nullable|array',
            'settings.landing_features_list' => 'nullable|array',
            'settings.landing_faq_list' => 'nullable|array',
            'settings.landing_testimonials_list' => 'nullable|array',
            'settings.landing_stats_list' => 'nullable|array',
            'settings.website_custom_pages' => 'nullable|array',
            'settings.website_social_links' => 'nullable|array',
            'settings.website_header_menu' => 'nullable|array',
            'settings.website_footer_menu' => 'nullable|array',

            // Hero text
            'settings.landing_hero_title_ar' => 'nullable|string|max:500',
            'settings.landing_hero_title_en' => 'nullable|string|max:500',
            'settings.landing_hero_subtitle_ar' => 'nullable|string|max:2000',
            'settings.landing_hero_subtitle_en' => 'nullable|string|max:2000',
            'settings.landing_hero_cta_text_ar' => 'nullable|string|max:200',
            'settings.landing_hero_cta_text_en' => 'nullable|string|max:200',
            'settings.landing_hero_typed_prefix_ar' => 'nullable|string|max:100',
            'settings.landing_hero_typed_prefix_en' => 'nullable|string|max:100',
            'settings.landing_hero_typed_suffix_ar' => 'nullable|string|max:100',
            'settings.landing_hero_typed_suffix_en' => 'nullable|string|max:100',
            'settings.landing_hero_typed_phrases_ar' => 'nullable|string|max:2000',
            'settings.landing_hero_typed_phrases_en' => 'nullable|string|max:2000',

            // Features section
            'settings.landing_features_title_ar' => 'nullable|string|max:500',
            'settings.landing_features_title_en' => 'nullable|string|max:500',
            'settings.landing_features_subtitle_ar' => 'nullable|string|max:2000',
            'settings.landing_features_subtitle_en' => 'nullable|string|max:2000',

            // Pricing section
            'settings.landing_pricing_title_ar' => 'nullable|string|max:500',
            'settings.landing_pricing_title_en' => 'nullable|string|max:500',
            'settings.landing_pricing_subtitle_ar' => 'nullable|string|max:2000',
            'settings.landing_pricing_subtitle_en' => 'nullable|string|max:2000',

            // Maintenance
            'settings.website_maintenance_title_ar' => 'nullable|string|max:500',
            'settings.website_maintenance_title_en' => 'nullable|string|max:500',
            'settings.website_maintenance_message_ar' => 'nullable|string|max:5000',
            'settings.website_maintenance_message_en' => 'nullable|string|max:5000',

            // Announcement
            'settings.website_announcement_text_ar' => 'nullable|string|max:500',
            'settings.website_announcement_text_en' => 'nullable|string|max:500',

            // Footer
            'settings.website_footer_about_ar' => 'nullable|string|max:2000',
            'settings.website_footer_about_en' => 'nullable|string|max:2000',
            'settings.website_copyright_text_ar' => 'nullable|string|max:200',
            'settings.website_copyright_text_en' => 'nullable|string|max:200',

            // Login button
            'settings.website_login_button_text_ar' => 'nullable|string|max:100',
            'settings.website_login_button_text_en' => 'nullable|string|max:100',

            // Address
            'settings.website_address_ar' => 'nullable|string|max:1000',
            'settings.website_address_en' => 'nullable|string|max:1000',

            // Social floating
            'settings.website_whatsapp_floating_number' => 'nullable|string|max:50',

            // Legal pages
            'settings.website_page_terms_ar' => 'nullable|string|max:50000',
            'settings.website_page_terms_en' => 'nullable|string|max:50000',
            'settings.website_page_privacy_ar' => 'nullable|string|max:50000',
            'settings.website_page_privacy_en' => 'nullable|string|max:50000',
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'settings.required' => 'الإعدادات مطلوبة',
            'settings.website_contact_email.email' => 'البريد الإلكتروني غير صالح',
            'settings.website_announcement_url.url' => 'الرابط غير صالح',
            'settings.*.regex' => 'الصيغة غير صالحة',
            'settings.*.max' => 'القيمة المدخلة طويلة جداً',
        ];
    }
}
