<script setup>
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import SystemLayout from '@/Layouts/SystemLayout.vue';
import { useToast } from '@/Composables/useToast';

const props = defineProps({
    settings: Object,
});

const { success } = useToast();
const activeTab = ref('general');

const tabs = [
    { id: 'general', name: 'الهوية البصرية', icon: 'M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z' },
    { id: 'menus', name: 'قوائم التصفح', icon: 'M4 6h16M4 12h16M4 18h16' },
    { id: 'banners', name: 'البنرات الإعلانية', icon: 'M7 4v16M17 4v16M3 8h4m10 0h4M3 12h18M3 16h4m10 0h4M4 20h16a1 1 0 001-1V5a1 1 0 00-1-1H4a1 1 0 00-1 1v14a1 1 0 001 1z' },
    { id: 'landing', name: 'الواجهة الرئيسية', icon: 'M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z' },
    { id: 'features', name: 'المميزات التنافسية', icon: 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z' },
    { id: 'pricing', name: 'خطط الأسعار', icon: 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1c-1.11 0-2.08.402-2.599 1M12 8V7m0 11c-1.11 0-2.08-.402-2.599-1M12 18v1m0-1c1.11 0 2.08-.402 2.599-1' },
    { id: 'stats', name: 'مؤشرات الأداء', icon: 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z' },
    { id: 'testimonials', name: 'عملائنا (الشركاء)', icon: 'M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z' },
    { id: 'faq', name: 'الأسئلة الشائعة', icon: 'M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z' },
    { id: 'contact', name: 'نموذج التواصل', icon: 'M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z' },
    { id: 'pages', name: 'الصفحات الثابتة', icon: 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z' },
    { id: 'maintenance', name: 'وضع الصيانة', icon: 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z' },
    { id: 'footer', name: 'التذييل (Footer)', icon: 'M12 15l-4-4h8l-4 4z' },
    { id: 'announcement', name: 'شريط الإعلانات', icon: 'M11 5.882V19.297A2.497 2.497 0 0012.5 22.5a2.497 2.497 0 002.5-2.5 2.497 2.497 0 00-1.5-2.297V5.882c2.556.19 4.5 2.307 4.5 4.868v.25c0 2.69 2.19 4.88 4.88 4.88.04 0 .08 0 .12-.005V13.88c-1.586 0-2.88-1.294-2.88-2.88v-.25c0-4.14-3.36-7.5-7.5-7.5s-7.5 3.36-7.5 7.5v.25c0 1.586-1.294 2.88-2.88 2.88v1.995c.04.004.08.005.12.005 2.69 0 4.88-2.19 4.88-4.88v-.25c0-2.561 1.944-4.678 4.5-4.868z' },
    { id: 'integrations', name: 'التكاملات والربط', icon: 'M13 10V3L4 14h7v7l9-11h-7z' },
    { id: 'seo_social', name: 'SEO وبيانات التواصل', icon: 'M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1' },
];

const form = useForm({
    settings: {
        website_title_ar: props.settings.website_title_ar, website_title_en: props.settings.website_title_en,
        website_description_ar: props.settings.website_description_ar, website_description_en: props.settings.website_description_en,
        website_keywords: props.settings.website_keywords, website_logo: props.settings.website_logo,
        website_favicon: props.settings.website_favicon, website_primary_color: props.settings.website_primary_color,
        
        website_maintenance_mode: props.settings.website_maintenance_mode,
        website_maintenance_title_ar: props.settings.website_maintenance_title_ar, website_maintenance_title_en: props.settings.website_maintenance_title_en,
        website_maintenance_message_ar: props.settings.website_maintenance_message_ar, website_maintenance_message_en: props.settings.website_maintenance_message_en,

        website_header_menu: props.settings.website_header_menu || [],
        website_footer_menu: props.settings.website_footer_menu || [],
        landing_banners_list: props.settings.landing_banners_list || [],
        landing_features_list: props.settings.landing_features_list || [],
        landing_faq_list: props.settings.landing_faq_list || [],
        landing_testimonials_list: props.settings.landing_testimonials_list || [],
        landing_stats_list: props.settings.landing_stats_list || [],
        website_custom_pages: props.settings.website_custom_pages || [],

        website_page_terms_ar: props.settings.website_page_terms_ar, website_page_terms_en: props.settings.website_page_terms_en,
        website_page_privacy_ar: props.settings.website_page_privacy_ar, website_page_privacy_en: props.settings.website_page_privacy_en,
        
        website_footer_about_ar: props.settings.website_footer_about_ar, website_footer_about_en: props.settings.website_footer_about_en,
        website_copyright_text_ar: props.settings.website_copyright_text_ar, website_copyright_text_en: props.settings.website_copyright_text_en,

        landing_hero_title_ar: props.settings.landing_hero_title_ar, landing_hero_title_en: props.settings.landing_hero_title_en,
        landing_hero_subtitle_ar: props.settings.landing_hero_subtitle_ar, landing_hero_subtitle_en: props.settings.landing_hero_subtitle_en,
        landing_hero_cta_text_ar: props.settings.landing_hero_cta_text_ar, landing_hero_cta_text_en: props.settings.landing_hero_cta_text_en,
        
        landing_features_title_ar: props.settings.landing_features_title_ar, landing_features_title_en: props.settings.landing_features_title_en,
        landing_features_subtitle_ar: props.settings.landing_features_subtitle_ar, landing_features_subtitle_en: props.settings.landing_features_subtitle_en,

        landing_pricing_title_ar: props.settings.landing_pricing_title_ar, landing_pricing_title_en: props.settings.landing_pricing_title_en,
        landing_pricing_subtitle_ar: props.settings.landing_pricing_subtitle_ar, landing_pricing_subtitle_en: props.settings.landing_pricing_subtitle_en,

        landing_features_enabled: props.settings.landing_features_enabled,
        landing_pricing_enabled: props.settings.landing_pricing_enabled,
        landing_stats_enabled: props.settings.landing_stats_enabled,
        landing_faq_enabled: props.settings.landing_faq_enabled,
        landing_testimonials_enabled: props.settings.landing_testimonials_enabled,
        landing_contact_enabled: props.settings.landing_contact_enabled,

        website_contact_email: props.settings.website_contact_email, website_contact_phone: props.settings.website_contact_phone,
        website_whatsapp_number: props.settings.website_whatsapp_number, website_address_ar: props.settings.website_address_ar,
        website_address_en: props.settings.website_address_en, website_facebook_url: props.settings.website_facebook_url,
        website_twitter_url: props.settings.website_twitter_url, website_instagram_url: props.settings.website_instagram_url,
        website_linkedin_url: props.settings.website_linkedin_url,

        website_whatsapp_floating_enabled: props.settings.website_whatsapp_floating_enabled,
        website_whatsapp_floating_number: props.settings.website_whatsapp_floating_number,
        website_chatbot_enabled: props.settings.website_chatbot_enabled,
        website_chatbot_script: props.settings.website_chatbot_script,
        website_scripts_header: props.settings.website_scripts_header,
        website_scripts_footer: props.settings.website_scripts_footer,

        website_announcement_enabled: props.settings.website_announcement_enabled,
        website_announcement_text_ar: props.settings.website_announcement_text_ar,
        website_announcement_text_en: props.settings.website_announcement_text_en,
        website_announcement_url: props.settings.website_announcement_url,
        website_announcement_bg_color: props.settings.website_announcement_bg_color,
        website_announcement_text_color: props.settings.website_announcement_text_color,

        website_login_button_text_ar: props.settings.website_login_button_text_ar,
        website_login_button_text_en: props.settings.website_login_button_text_en,
    }
});

const addItem = (listKey, defaults) => { form.settings[listKey].push({ ...defaults }); };
const removeItem = (listKey, index) => { form.settings[listKey].splice(index, 1); };

const submit = () => {
    form.put(route('system.settings.website.update'), {
        preserveScroll: true,
        onSuccess: () => success('تم حفظ التغييرات بنجاح'),
    });
};
</script>

<template>
    <SystemLayout title="إدارة منصة الويب">
        <div class="max-w-screen-2xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
            
            <!-- CRM Header -->
            <div class="md:flex md:items-center md:justify-between mb-8 pb-5 border-b border-gray-200 dark:border-gray-700">
                <div class="flex-1 min-w-0">
                    <h2 class="text-2xl font-bold leading-7 text-gray-900 dark:text-white sm:text-3xl sm:truncate font-premium">
                        إعدادات النظام (Website CMS)
                    </h2>
                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                        تحكم مركزي في المحتوى، الهوية البصرية، والسياسات الخاصة بالموقع العام.
                    </p>
                </div>
                <div class="mt-4 flex md:mt-0 gap-3">
                    <a :href="route('public.landing.preview')" target="_blank" class="btn-outline flex items-center">
                        <svg class="w-4 h-4 ml-2 -mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                        معاينة الموقع
                    </a>
                    <button @click="submit" :disabled="form.processing" class="btn-enterprise">
                        <svg v-if="!form.processing" class="w-4 h-4 ml-2 -mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        <svg v-else class="w-4 h-4 ml-2 -mr-1 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                        حفظ التعديلات
                    </button>
                </div>
            </div>

            <!-- CRM Layout -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden flex flex-col md:flex-row min-h-[75vh]">
                
                <!-- Sidebar Nav -->
                <div class="w-full md:w-64 bg-gray-50 dark:bg-gray-900 border-l border-gray-200 dark:border-gray-700 flex-shrink-0">
                    <nav class="space-y-0.5 p-3">
                        <button v-for="tab in tabs" :key="tab.id" @click="activeTab = tab.id"
                            :class="[
                                activeTab === tab.id 
                                    ? 'bg-indigo-50 border-indigo-600 text-indigo-700 dark:bg-indigo-900/50 dark:text-indigo-300' 
                                    : 'border-transparent text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800 hover:text-gray-900 dark:hover:text-white',
                                'group border-r-4 px-4 py-3.5 w-full flex items-center text-sm font-medium transition-colors text-right rounded-l-lg'
                            ]">
                            <svg :class="[activeTab === tab.id ? 'text-indigo-600 dark:text-indigo-400' : 'text-gray-400 group-hover:text-gray-500', 'flex-shrink-0 ml-3 h-5 w-5']" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="tab.icon"/>
                            </svg>
                            <span class="truncate">{{ tab.name }}</span>
                        </button>
                    </nav>
                </div>

                <!-- Main Content Area -->
                <div class="flex-1 p-6 md:p-10 lg:p-12 overflow-y-auto max-h-[75vh] custom-scrollbar bg-white dark:bg-gray-800">
                    <div class="max-w-4xl mx-auto space-y-12 animate-fade-in pb-12">
                        
                        <!-- 1. الهوية البصرية -->
                        <div v-if="activeTab === 'general'">
                            <h3 class="text-lg leading-6 font-semibold text-gray-900 dark:text-white mb-6">الهوية البصرية والعلامة التجارية</h3>
                            <div class="grid grid-cols-1 gap-y-8 gap-x-6 sm:grid-cols-6">
                                
                                <div class="sm:col-span-6 border border-gray-200 dark:border-gray-700 rounded-lg p-5">
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-4">شعار المنصة الرئيسي</label>
                                    <div class="flex flex-col sm:flex-row gap-6 items-start">
                                        <div class="w-40 h-24 border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg flex items-center justify-center overflow-hidden bg-gray-50 dark:bg-gray-900">
                                            <img v-if="form.settings.website_logo" :src="typeof form.settings.website_logo === 'string' ? form.settings.website_logo : URL.createObjectURL(form.settings.website_logo)" class="max-h-full object-contain" />
                                            <svg v-else class="w-8 h-8 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                        </div>
                                        <div class="space-y-3 flex-1 w-full">
                                            <input type="file" @input="form.settings.website_logo = $event.target.files[0]" class="hidden" ref="logoInput" accept="image/*" />
                                            <button @click="$refs.logoInput.click()" type="button" class="btn-outline w-full sm:w-auto">تصفح الملفات</button>
                                            <p class="text-xs text-gray-500">مستحسن: PNG بخلفية شفافة (أقصى حجم 2MB)</p>
                                            <input v-model="form.settings.website_logo" type="text" placeholder="رابط URL الشعار (بديل)" class="input-enterprise text-left" dir="ltr" />
                                        </div>
                                    </div>
                                </div>

                                <div class="sm:col-span-3">
                                    <label class="flex justify-between text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                        <span>اسم الموقع</span> <span class="badge-lang">AR</span>
                                    </label>
                                    <input v-model="form.settings.website_title_ar" type="text" class="input-enterprise" />
                                </div>
                                <div class="sm:col-span-3">
                                    <label class="flex justify-between text-sm font-medium text-gray-700 dark:text-gray-300 mb-1" dir="ltr">
                                        <span>Site Title</span> <span class="badge-lang">EN</span>
                                    </label>
                                    <input v-model="form.settings.website_title_en" type="text" class="input-enterprise text-left" dir="ltr" />
                                </div>

                                <div class="sm:col-span-3">
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">أيقونة المتصفح (Favicon)</label>
                                    <div class="flex gap-3">
                                        <div class="w-10 h-10 border border-gray-300 rounded-lg flex items-center justify-center overflow-hidden bg-gray-50">
                                            <img v-if="form.settings.website_favicon" :src="typeof form.settings.website_favicon === 'string' ? form.settings.website_favicon : URL.createObjectURL(form.settings.website_favicon)" class="w-full h-full object-contain" />
                                        </div>
                                        <input type="file" @input="form.settings.website_favicon = $event.target.files[0]" class="hidden" ref="faviconInput" accept="image/*" />
                                        <button @click="$refs.faviconInput.click()" type="button" class="btn-outline flex-1">تحديث الأيقونة</button>
                                    </div>
                                </div>

                                <div class="sm:col-span-3">
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">لون العلامة التجارية</label>
                                    <div class="flex gap-2">
                                        <input v-model="form.settings.website_primary_color" type="color" class="h-10 w-12 p-0.5 rounded-lg border border-gray-300 cursor-pointer" />
                                        <input v-model="form.settings.website_primary_color" type="text" class="input-enterprise flex-1 font-mono text-left" dir="ltr" />
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Add other CRM styled sections here following the same pattern -->
                        <!-- 2. Menus -->
                        <div v-if="activeTab === 'menus'" class="space-y-10">
                            <div>
                                <div class="flex items-center justify-between mb-4">
                                    <h3 class="text-lg leading-6 font-semibold text-gray-900 dark:text-white">قائمة التنقل الرئيسية (Header)</h3>
                                    <button @click="addItem('website_header_menu', { label_ar: '', label_en: '', url: '' })" class="btn-outline text-xs py-1.5">إضافة عنصر</button>
                                </div>
                                <div class="space-y-3">
                                    <div v-for="(item, index) in form.settings.website_header_menu" :key="index" class="p-4 border border-gray-200 dark:border-gray-700 rounded-lg bg-gray-50/50 dark:bg-gray-800/50 flex gap-4 items-start group">
                                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 flex-1">
                                            <div><label class="text-xs text-gray-500 mb-1 block">الاسم (AR)</label><input v-model="item.label_ar" class="input-enterprise" /></div>
                                            <div><label class="text-xs text-gray-500 mb-1 block">Label (EN)</label><input v-model="item.label_en" class="input-enterprise text-left" dir="ltr" /></div>
                                            <div><label class="text-xs text-gray-500 mb-1 block">المسار (URL)</label><input v-model="item.url" class="input-enterprise text-left font-mono" dir="ltr" /></div>
                                        </div>
                                        <button @click="removeItem('website_header_menu', index)" class="text-gray-400 hover:text-red-600 mt-6"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg></button>
                                    </div>
                                    <div v-if="!form.settings.website_header_menu.length" class="text-center py-6 border-2 border-dashed rounded-lg text-gray-500">القائمة فارغة</div>
                                </div>
                            </div>

                            <div class="pt-6 border-t border-gray-200 dark:border-gray-700">
                                <div class="flex items-center justify-between mb-4">
                                    <h3 class="text-lg leading-6 font-semibold text-gray-900 dark:text-white">روابط التذييل (Footer)</h3>
                                    <button @click="addItem('website_footer_menu', { label_ar: '', label_en: '', url: '' })" class="btn-outline text-xs py-1.5">إضافة عنصر</button>
                                </div>
                                <div class="space-y-3">
                                    <div v-for="(item, index) in form.settings.website_footer_menu" :key="index" class="p-4 border border-gray-200 dark:border-gray-700 rounded-lg bg-gray-50/50 dark:bg-gray-800/50 flex gap-4 items-start group">
                                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 flex-1">
                                            <div><label class="text-xs text-gray-500 mb-1 block">الاسم (AR)</label><input v-model="item.label_ar" class="input-enterprise" /></div>
                                            <div><label class="text-xs text-gray-500 mb-1 block">Label (EN)</label><input v-model="item.label_en" class="input-enterprise text-left" dir="ltr" /></div>
                                            <div><label class="text-xs text-gray-500 mb-1 block">المسار (URL)</label><input v-model="item.url" class="input-enterprise text-left font-mono" dir="ltr" /></div>
                                        </div>
                                        <button @click="removeItem('website_footer_menu', index)" class="text-gray-400 hover:text-red-600 mt-6"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg></button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- 3. Banners -->
                        <div v-if="activeTab === 'banners'">
                            <div class="flex items-center justify-between mb-6">
                                <h3 class="text-lg leading-6 font-semibold text-gray-900 dark:text-white">إدارة اللوحات الإعلانية (Sliders)</h3>
                                <button @click="addItem('landing_banners_list', { title_ar: '', title_en: '', image: '', link: '' })" class="btn-outline">إضافة لوحة جديدة</button>
                            </div>
                            <div class="space-y-6">
                                <div v-for="(banner, index) in form.settings.landing_banners_list" :key="index" class="p-5 border border-gray-200 dark:border-gray-700 rounded-lg bg-gray-50/30 flex flex-col sm:flex-row gap-6 relative group">
                                    <button @click="removeItem('landing_banners_list', index)" class="absolute top-3 left-3 text-gray-400 hover:text-red-600"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
                                    
                                    <div class="w-full sm:w-48 flex-shrink-0">
                                        <div class="w-full h-28 bg-gray-100 dark:bg-gray-800 border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg flex items-center justify-center overflow-hidden mb-2 relative group/img">
                                            <img v-if="banner.image" :src="typeof banner.image === 'string' ? banner.image : URL.createObjectURL(banner.image)" class="w-full h-full object-cover" />
                                            <svg v-else class="w-8 h-8 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                        </div>
                                        <input type="file" @input="banner.image = $event.target.files[0]" class="hidden" :ref="'bannerInput' + index" accept="image/*" />
                                        <button @click="$refs['bannerInput' + index][0].click()" type="button" class="text-xs text-indigo-600 font-medium hover:text-indigo-800 w-full text-center">تغيير الصورة</button>
                                    </div>
                                    
                                    <div class="flex-1 space-y-4">
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                            <div><label class="text-xs text-gray-500 mb-1 flex justify-between">العنوان <span class="badge-lang">AR</span></label><input v-model="banner.title_ar" class="input-enterprise" /></div>
                                            <div><label class="text-xs text-gray-500 mb-1 flex justify-between" dir="ltr">Title <span class="badge-lang">EN</span></label><input v-model="banner.title_en" class="input-enterprise text-left" dir="ltr" /></div>
                                            <div class="md:col-span-2"><label class="text-xs text-gray-500 mb-1 block">رابط الاستهداف (URL)</label><input v-model="banner.link" class="input-enterprise text-left font-mono" dir="ltr" placeholder="https://" /></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- 4. Landing Content -->
                        <div v-if="activeTab === 'landing'" class="space-y-8">
                            <h3 class="text-lg leading-6 font-semibold text-gray-900 dark:text-white border-b pb-4">محتوى الواجهة الرئيسية (Hero Section)</h3>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                                <div><label class="text-sm font-medium text-gray-700 flex justify-between mb-1">العنوان التسويقي <span class="badge-lang">AR</span></label><input v-model="form.settings.landing_hero_title_ar" class="input-enterprise" /></div>
                                <div><label class="text-sm font-medium text-gray-700 flex justify-between mb-1" dir="ltr">Hero Title <span class="badge-lang">EN</span></label><input v-model="form.settings.landing_hero_title_en" class="input-enterprise text-left" dir="ltr" /></div>
                                
                                <div><label class="text-sm font-medium text-gray-700 flex justify-between mb-1">النص التعريفي <span class="badge-lang">AR</span></label><textarea v-model="form.settings.landing_hero_subtitle_ar" rows="3" class="input-enterprise"></textarea></div>
                                <div><label class="text-sm font-medium text-gray-700 flex justify-between mb-1" dir="ltr">Hero Subtitle <span class="badge-lang">EN</span></label><textarea v-model="form.settings.landing_hero_subtitle_en" rows="3" class="input-enterprise text-left" dir="ltr"></textarea></div>
                                
                                <div><label class="text-sm font-medium text-gray-700 flex justify-between mb-1">زر الإجراء (CTA) <span class="badge-lang">AR</span></label><input v-model="form.settings.landing_hero_cta_text_ar" class="input-enterprise" /></div>
                                <div><label class="text-sm font-medium text-gray-700 flex justify-between mb-1" dir="ltr">CTA Button <span class="badge-lang">EN</span></label><input v-model="form.settings.landing_hero_cta_text_en" class="input-enterprise text-left" dir="ltr" /></div>

                                <div class="col-span-full grid grid-cols-1 sm:grid-cols-2 gap-6 pt-4 border-t border-dashed border-gray-200 dark:border-gray-700">
                                    <div><label class="text-sm font-medium text-gray-700 flex justify-between mb-1">نص زر تسجيل الدخول <span class="badge-lang">AR</span></label><input v-model="form.settings.website_login_button_text_ar" class="input-enterprise" /></div>
                                    <div><label class="text-sm font-medium text-gray-700 flex justify-between mb-1" dir="ltr">Login Button Text <span class="badge-lang">EN</span></label><input v-model="form.settings.website_login_button_text_en" class="input-enterprise text-left" dir="ltr" /></div>
                                </div>
                            </div>
                        </div>

                        <!-- 5. Features -->
                        <div v-if="activeTab === 'features'" class="space-y-8">
                            <div class="flex items-center justify-between bg-white dark:bg-gray-800 p-4 border rounded-lg shadow-sm">
                                <div>
                                    <h4 class="text-sm font-bold text-gray-900 dark:text-white">تفعيل قسم المميزات</h4>
                                    <p class="text-xs text-gray-500">إظهار أو إخفاء هذا القسم من الواجهة الرئيسية.</p>
                                </div>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" v-model="form.settings.landing_features_enabled" true-value="1" false-value="0" class="sr-only peer">
                                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-indigo-300 dark:peer-focus:ring-indigo-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-indigo-600"></div>
                                </label>
                            </div>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 border-b pb-8">
                                <div><label class="text-sm font-medium text-gray-700 flex justify-between mb-1">عنوان القسم <span class="badge-lang">AR</span></label><input v-model="form.settings.landing_features_title_ar" class="input-enterprise" /></div>
                                <div><label class="text-sm font-medium text-gray-700 flex justify-between mb-1" dir="ltr">Section Title <span class="badge-lang">EN</span></label><input v-model="form.settings.landing_features_title_en" class="input-enterprise text-left" dir="ltr" /></div>
                                <div><label class="text-sm font-medium text-gray-700 flex justify-between mb-1">وصف القسم <span class="badge-lang">AR</span></label><input v-model="form.settings.landing_features_subtitle_ar" class="input-enterprise" /></div>
                                <div><label class="text-sm font-medium text-gray-700 flex justify-between mb-1" dir="ltr">Section Subtitle <span class="badge-lang">EN</span></label><input v-model="form.settings.landing_features_subtitle_en" class="input-enterprise text-left" dir="ltr" /></div>
                            </div>

                            <div class="flex items-center justify-between mt-6">
                                <h3 class="text-lg leading-6 font-semibold text-gray-900 dark:text-white">قائمة المميزات</h3>
                                <button @click="addItem('landing_features_list', { title_ar: '', title_en: '', desc_ar: '', desc_en: '' })" class="btn-outline">إضافة ميزة</button>
                            </div>
                            <div class="space-y-4">
                                <div v-for="(feature, index) in form.settings.landing_features_list" :key="index" class="p-5 border border-gray-200 rounded-lg bg-gray-50 flex gap-4">
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 flex-1">
                                        <div><label class="text-xs text-gray-500 mb-1 flex justify-between">الميزة <span class="badge-lang">AR</span></label><input v-model="feature.title_ar" class="input-enterprise" /></div>
                                        <div><label class="text-xs text-gray-500 mb-1 flex justify-between" dir="ltr">Feature <span class="badge-lang">EN</span></label><input v-model="feature.title_en" class="input-enterprise text-left" dir="ltr" /></div>
                                        <div><label class="text-xs text-gray-500 mb-1 flex justify-between">التفاصيل <span class="badge-lang">AR</span></label><textarea v-model="feature.desc_ar" rows="2" class="input-enterprise"></textarea></div>
                                        <div><label class="text-xs text-gray-500 mb-1 flex justify-between" dir="ltr">Details <span class="badge-lang">EN</span></label><textarea v-model="feature.desc_en" rows="2" class="input-enterprise text-left" dir="ltr"></textarea></div>
                                    </div>
                                    <button @click="removeItem('landing_features_list', index)" class="text-gray-400 hover:text-red-600"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg></button>
                                </div>
                            </div>
                        </div>

                        <!-- 6. Pricing & 7. Stats & 8. Testimonials & 9. FAQ -->
                        <!-- Using similar CRM pattern -->
                        <div v-if="activeTab === 'pricing'" class="space-y-8">
                            <div class="flex items-center justify-between bg-white dark:bg-gray-800 p-4 border rounded-lg shadow-sm">
                                <div>
                                    <h4 class="text-sm font-bold text-gray-900 dark:text-white">تفعيل قسم الباقات والأسعار</h4>
                                    <p class="text-xs text-gray-500">إظهار أو إخفاء خطط الأسعار من الواجهة الرئيسية.</p>
                                </div>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" v-model="form.settings.landing_pricing_enabled" true-value="1" false-value="0" class="sr-only peer">
                                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-indigo-300 dark:peer-focus:ring-indigo-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-indigo-600"></div>
                                </label>
                            </div>
                             <h3 class="text-lg leading-6 font-semibold text-gray-900 border-b pb-4">إعدادات قسم الباقات</h3>
                             <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                                <div><label class="text-sm font-medium text-gray-700 flex justify-between mb-1">عنوان القسم <span class="badge-lang">AR</span></label><input v-model="form.settings.landing_pricing_title_ar" class="input-enterprise" /></div>
                                <div><label class="text-sm font-medium text-gray-700 flex justify-between mb-1" dir="ltr">Title <span class="badge-lang">EN</span></label><input v-model="form.settings.landing_pricing_title_en" class="input-enterprise text-left" dir="ltr" /></div>
                                <div><label class="text-sm font-medium text-gray-700 flex justify-between mb-1">النص التعريفي <span class="badge-lang">AR</span></label><textarea v-model="form.settings.landing_pricing_subtitle_ar" rows="2" class="input-enterprise"></textarea></div>
                                <div><label class="text-sm font-medium text-gray-700 flex justify-between mb-1" dir="ltr">Subtitle <span class="badge-lang">EN</span></label><textarea v-model="form.settings.landing_pricing_subtitle_en" rows="2" class="input-enterprise text-left" dir="ltr"></textarea></div>
                            </div>
                            <p class="text-sm text-gray-500 bg-blue-50 p-3 rounded-lg border border-blue-100 flex items-center gap-2">
                                <svg class="w-5 h-5 text-blue-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/></svg>
                                الباقات والأسعار الفعلية يتم جلبها آلياً من نظام الاشتراكات في النظام.
                            </p>
                        </div>

                        <div v-if="activeTab === 'stats'" class="space-y-6">
                            <div class="flex items-center justify-between bg-white dark:bg-gray-800 p-4 border rounded-lg shadow-sm">
                                <div>
                                    <h4 class="text-sm font-bold text-gray-900 dark:text-white">تفعيل قسم مؤشرات الأداء</h4>
                                    <p class="text-xs text-gray-500">إظهار أو إخفاء الأرقام والإحصائيات من الواجهة الرئيسية.</p>
                                </div>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" v-model="form.settings.landing_stats_enabled" true-value="1" false-value="0" class="sr-only peer">
                                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-indigo-300 dark:peer-focus:ring-indigo-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-indigo-600"></div>
                                </label>
                            </div>
                            <div class="flex items-center justify-between mb-6 border-t pt-6">
                                <h3 class="text-lg leading-6 font-semibold text-gray-900">إدارة المؤشرات</h3>
                                <button @click="addItem('landing_stats_list', { label_ar: '', label_en: '', value: '' })" class="btn-outline">إضافة مؤشر</button>
                            </div>
                            <div class="space-y-3">
                                <div v-for="(stat, index) in form.settings.landing_stats_list" :key="index" class="p-4 border border-gray-200 rounded-lg flex gap-4 items-center bg-gray-50">
                                    <div class="w-32"><label class="text-xs text-gray-500 block mb-1">الرقم/القيمة</label><input v-model="stat.value" placeholder="100+" class="input-enterprise font-mono text-center" dir="ltr" /></div>
                                    <div class="flex-1"><label class="text-xs text-gray-500 block mb-1 flex justify-between">الوصف <span class="badge-lang">AR</span></label><input v-model="stat.label_ar" class="input-enterprise" /></div>
                                    <div class="flex-1"><label class="text-xs text-gray-500 block mb-1 flex justify-between" dir="ltr">Label <span class="badge-lang">EN</span></label><input v-model="stat.label_en" class="input-enterprise text-left" dir="ltr" /></div>
                                    <button @click="removeItem('landing_stats_list', index)" class="text-gray-400 hover:text-red-600 mt-5"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg></button>
                                </div>
                            </div>
                        </div>

                        <!-- 8. Testimonials (Clients) -->
                        <div v-if="activeTab === 'testimonials'" class="space-y-6">
                            <div class="flex items-center justify-between bg-white dark:bg-gray-800 p-4 border rounded-lg shadow-sm">
                                <div>
                                    <h4 class="text-sm font-bold text-gray-900 dark:text-white">تفعيل قسم عملائنا (شركاء النجاح)</h4>
                                    <p class="text-xs text-gray-500">إظهار أو إخفاء شعارات الشركات والورش الموثوقة.</p>
                                </div>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" v-model="form.settings.landing_testimonials_enabled" true-value="1" false-value="0" class="sr-only peer">
                                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-indigo-300 dark:peer-focus:ring-indigo-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-indigo-600"></div>
                                </label>
                            </div>
                            <div class="flex items-center justify-between mb-6 border-t pt-6">
                                <h3 class="text-lg leading-6 font-semibold text-gray-900">إدارة شعارات العملاء</h3>
                                <button @click="addItem('landing_testimonials_list', { name: '', logo_url: '' })" class="btn-outline">إضافة عميل</button>
                            </div>
                            <div class="space-y-3">
                                <div v-for="(client, index) in form.settings.landing_testimonials_list" :key="index" class="p-4 border border-gray-200 rounded-lg flex gap-4 items-center bg-gray-50">
                                    <div class="flex-1"><label class="text-xs text-gray-500 block mb-1">اسم العميل/الشركة</label><input v-model="client.name" class="input-enterprise" /></div>
                                    <div class="flex-1"><label class="text-xs text-gray-500 block mb-1">رابط الشعار (صورة)</label><input v-model="client.logo_url" class="input-enterprise text-left font-mono" dir="ltr" placeholder="https://" /></div>
                                    <button @click="removeItem('landing_testimonials_list', index)" class="text-gray-400 hover:text-red-600 mt-5"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg></button>
                                </div>
                            </div>
                        </div>

                        <!-- 9. FAQ -->
                        <div v-if="activeTab === 'faq'" class="space-y-6">
                            <div class="flex items-center justify-between bg-white dark:bg-gray-800 p-4 border rounded-lg shadow-sm">
                                <div>
                                    <h4 class="text-sm font-bold text-gray-900 dark:text-white">تفعيل قسم الأسئلة الشائعة</h4>
                                    <p class="text-xs text-gray-500">إظهار أو إخفاء قسم الأسئلة المتكررة من الواجهة الرئيسية.</p>
                                </div>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" v-model="form.settings.landing_faq_enabled" true-value="1" false-value="0" class="sr-only peer">
                                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-indigo-300 dark:peer-focus:ring-indigo-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-indigo-600"></div>
                                </label>
                            </div>
                            <div class="flex items-center justify-between mb-6 border-t pt-6">
                                <h3 class="text-lg leading-6 font-semibold text-gray-900">إدارة الأسئلة الشائعة</h3>
                                <button @click="addItem('landing_faq_list', { q_ar: '', a_ar: '', q_en: '', a_en: '' })" class="btn-outline">إضافة سؤال</button>
                            </div>
                            <div class="space-y-4">
                                <div v-for="(faq, index) in form.settings.landing_faq_list" :key="index" class="p-4 border border-gray-200 rounded-lg bg-gray-50 relative group">
                                    <button @click="removeItem('landing_faq_list', index)" class="absolute top-4 left-4 text-gray-400 hover:text-red-600"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg></button>
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                        <div><label class="text-xs text-gray-500 mb-1 flex justify-between">السؤال <span class="badge-lang">AR</span></label><input v-model="faq.q_ar" class="input-enterprise" /></div>
                                        <div><label class="text-xs text-gray-500 mb-1 flex justify-between" dir="ltr">Question <span class="badge-lang">EN</span></label><input v-model="faq.q_en" class="input-enterprise text-left" dir="ltr" /></div>
                                        <div><label class="text-xs text-gray-500 mb-1 flex justify-between">الإجابة <span class="badge-lang">AR</span></label><textarea v-model="faq.a_ar" rows="3" class="input-enterprise"></textarea></div>
                                        <div><label class="text-xs text-gray-500 mb-1 flex justify-between" dir="ltr">Answer <span class="badge-lang">EN</span></label><textarea v-model="faq.a_en" rows="3" class="input-enterprise text-left" dir="ltr"></textarea></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- 10. Contact Us Form Toggle -->
                        <div v-if="activeTab === 'contact'" class="space-y-6">
                            <div class="flex items-center justify-between bg-white dark:bg-gray-800 p-4 border rounded-lg shadow-sm">
                                <div>
                                    <h4 class="text-sm font-bold text-gray-900 dark:text-white">تفعيل نموذج التواصل</h4>
                                    <p class="text-xs text-gray-500">إظهار أو إخفاء قسم "تواصل معنا" من الواجهة الرئيسية.</p>
                                </div>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" v-model="form.settings.landing_contact_enabled" true-value="1" false-value="0" class="sr-only peer">
                                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-indigo-300 dark:peer-focus:ring-indigo-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-indigo-600"></div>
                                </label>
                            </div>
                            <div class="p-6 bg-blue-50 border-l-4 border-blue-500 rounded-lg">
                                <h3 class="font-bold text-blue-800 mb-2">إعدادات بريد الاستقبال</h3>
                                <p class="text-sm text-blue-700 mb-4">يتم إرسال رسائل الزوار القادمة من نموذج التواصل إلى البريد الإلكتروني المحدد في تبويب (SEO وبيانات التواصل). يرجى التأكد من إضافة بريد صالح هناك.</p>
                            </div>
                        </div>

                        <!-- 11. Pages -->
                        <div v-if="activeTab === 'pages'" class="space-y-10">
                            <div>
                                <h3 class="text-lg leading-6 font-semibold text-gray-900 border-b pb-4 mb-6">الوثائق القانونية</h3>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                                    <div><label class="text-sm font-medium text-gray-700 flex justify-between mb-1">الشروط والأحكام <span class="badge-lang">AR</span></label><textarea v-model="form.settings.website_page_terms_ar" rows="6" class="input-enterprise"></textarea></div>
                                    <div><label class="text-sm font-medium text-gray-700 flex justify-between mb-1" dir="ltr">Terms & Conditions <span class="badge-lang">EN</span></label><textarea v-model="form.settings.website_page_terms_en" rows="6" class="input-enterprise text-left" dir="ltr"></textarea></div>
                                    <div><label class="text-sm font-medium text-gray-700 flex justify-between mb-1">سياسة الخصوصية <span class="badge-lang">AR</span></label><textarea v-model="form.settings.website_page_privacy_ar" rows="6" class="input-enterprise"></textarea></div>
                                    <div><label class="text-sm font-medium text-gray-700 flex justify-between mb-1" dir="ltr">Privacy Policy <span class="badge-lang">EN</span></label><textarea v-model="form.settings.website_page_privacy_en" rows="6" class="input-enterprise text-left" dir="ltr"></textarea></div>
                                </div>
                            </div>

                            <div class="pt-8 border-t border-gray-200">
                                <div class="flex items-center justify-between mb-6">
                                    <h3 class="text-lg leading-6 font-semibold text-gray-900">إنشاء صفحات فرعية</h3>
                                    <button @click="addItem('website_custom_pages', { title_ar: '', title_en: '', slug: '', content_ar: '', content_en: '' })" class="btn-outline">إضافة صفحة جديدة</button>
                                </div>
                                <div class="space-y-6">
                                    <div v-for="(page, index) in form.settings.website_custom_pages" :key="index" class="p-6 border border-gray-200 rounded-lg bg-gray-50 relative group">
                                        <button @click="removeItem('website_custom_pages', index)" class="absolute top-4 left-4 text-gray-400 hover:text-red-600"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg></button>
                                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                            <div class="sm:col-span-2"><label class="text-xs text-gray-500 mb-1 block">رابط الصفحة (Slug)</label><div class="flex rounded-md shadow-sm"><span class="inline-flex items-center px-3 rounded-r-md border border-l-0 border-gray-300 bg-gray-50 text-gray-500 sm:text-sm" dir="ltr">/page/</span><input v-model="page.slug" class="flex-1 min-w-0 block w-full px-3 py-2 rounded-none rounded-l-md sm:text-sm border-gray-300 focus:ring-indigo-500 focus:border-indigo-500 text-left font-mono" dir="ltr" placeholder="about-us" /></div></div>
                                            <div><label class="text-xs text-gray-500 mb-1 flex justify-between">عنوان الصفحة <span class="badge-lang">AR</span></label><input v-model="page.title_ar" class="input-enterprise" /></div>
                                            <div><label class="text-xs text-gray-500 mb-1 flex justify-between" dir="ltr">Page Title <span class="badge-lang">EN</span></label><input v-model="page.title_en" class="input-enterprise text-left" dir="ltr" /></div>
                                            <div><label class="text-xs text-gray-500 mb-1 flex justify-between">المحتوى <span class="badge-lang">AR</span></label><textarea v-model="page.content_ar" rows="5" class="input-enterprise"></textarea></div>
                                            <div><label class="text-xs text-gray-500 mb-1 flex justify-between" dir="ltr">Content <span class="badge-lang">EN</span></label><textarea v-model="page.content_en" rows="5" class="input-enterprise text-left" dir="ltr"></textarea></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- 11. Maintenance Mode -->
                        <div v-if="activeTab === 'maintenance'">
                            <div class="flex items-start justify-between border-b pb-6 mb-8">
                                <div>
                                    <h3 class="text-lg leading-6 font-semibold text-gray-900">حالة النظام (وضعية الصيانة)</h3>
                                    <p class="mt-1 text-sm text-gray-500">منع الوصول إلى الموقع العام وعرض شاشة الصيانة المخصصة.</p>
                                </div>
                                <div class="flex items-center">
                                    <button @click="form.settings.website_maintenance_mode = form.settings.website_maintenance_mode == '1' ? '0' : '1'" type="button" :class="[form.settings.website_maintenance_mode == '1' ? 'bg-red-600' : 'bg-gray-200', 'relative inline-flex flex-shrink-0 h-6 w-11 border-2 border-transparent rounded-full cursor-pointer transition-colors ease-in-out duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500']">
                                        <span class="sr-only">Toggle Maintenance</span>
                                        <span aria-hidden="true" :class="[form.settings.website_maintenance_mode == '1' ? '-translate-x-5' : 'translate-x-0', 'pointer-events-none inline-block h-5 w-5 rounded-full bg-white shadow transform ring-0 transition ease-in-out duration-200']"></span>
                                    </button>
                                </div>
                            </div>

                            <div :class="{'opacity-50 pointer-events-none': form.settings.website_maintenance_mode != '1'}" class="grid grid-cols-1 sm:grid-cols-2 gap-6 transition-opacity">
                                <div><label class="text-sm font-medium text-gray-700 flex justify-between mb-1">عنوان رسالة الصيانة <span class="badge-lang">AR</span></label><input v-model="form.settings.website_maintenance_title_ar" class="input-enterprise" /></div>
                                <div><label class="text-sm font-medium text-gray-700 flex justify-between mb-1" dir="ltr">Maintenance Title <span class="badge-lang">EN</span></label><input v-model="form.settings.website_maintenance_title_en" class="input-enterprise text-left" dir="ltr" /></div>
                                <div class="sm:col-span-2"><label class="text-sm font-medium text-gray-700 flex justify-between mb-1">الرسالة التوضيحية <span class="badge-lang">AR</span></label><textarea v-model="form.settings.website_maintenance_message_ar" rows="3" class="input-enterprise"></textarea></div>
                                <div class="sm:col-span-2"><label class="text-sm font-medium text-gray-700 flex justify-between mb-1" dir="ltr">Explanatory Message <span class="badge-lang">EN</span></label><textarea v-model="form.settings.website_maintenance_message_en" rows="3" class="input-enterprise text-left" dir="ltr"></textarea></div>
                            </div>
                        </div>

                        <!-- 12. Footer -->
                        <div v-if="activeTab === 'footer'" class="space-y-8">
                            <h3 class="text-lg leading-6 font-semibold text-gray-900 border-b pb-4">معلومات التذييل</h3>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                                <div><label class="text-sm font-medium text-gray-700 flex justify-between mb-1">نبذة قصيرة (About) <span class="badge-lang">AR</span></label><textarea v-model="form.settings.website_footer_about_ar" rows="3" class="input-enterprise"></textarea></div>
                                <div><label class="text-sm font-medium text-gray-700 flex justify-between mb-1" dir="ltr">Short About <span class="badge-lang">EN</span></label><textarea v-model="form.settings.website_footer_about_en" rows="3" class="input-enterprise text-left" dir="ltr"></textarea></div>
                                <div><label class="text-sm font-medium text-gray-700 flex justify-between mb-1">حقوق النشر <span class="badge-lang">AR</span></label><input v-model="form.settings.website_copyright_text_ar" class="input-enterprise" /></div>
                                <div><label class="text-sm font-medium text-gray-700 flex justify-between mb-1" dir="ltr">Copyrights <span class="badge-lang">EN</span></label><input v-model="form.settings.website_copyright_text_en" class="input-enterprise text-left" dir="ltr" /></div>
                            </div>
                        </div>

                        <!-- 13. Announcement Bar -->
                        <div v-if="activeTab === 'announcement'" class="space-y-8">
                            <div class="flex items-center justify-between border-b pb-4">
                                <h3 class="text-lg leading-6 font-semibold text-gray-900">شريط الإعلانات العلوي</h3>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" v-model="form.settings.website_announcement_enabled" true-value="1" false-value="0" class="sr-only peer">
                                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-indigo-300 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-indigo-600"></div>
                                </label>
                            </div>
                            
                            <div :class="{'opacity-50 pointer-events-none': form.settings.website_announcement_enabled != '1'}" class="space-y-6 transition-opacity">
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                                    <div>
                                        <label class="text-sm font-medium text-gray-700 flex justify-between mb-1">نص الإعلان <span class="badge-lang">AR</span></label>
                                        <input v-model="form.settings.website_announcement_text_ar" class="input-enterprise" placeholder="مثال: خصم 20% لفترة محدودة!" />
                                    </div>
                                    <div>
                                        <label class="text-sm font-medium text-gray-700 flex justify-between mb-1" dir="ltr">Announcement Text <span class="badge-lang">EN</span></label>
                                        <input v-model="form.settings.website_announcement_text_en" class="input-enterprise text-left" dir="ltr" placeholder="Example: 20% Discount for limited time!" />
                                    </div>
                                </div>
                                <div>
                                    <label class="text-sm font-medium text-gray-700 mb-1 block">رابط الإعلان (اختياري)</label>
                                    <input v-model="form.settings.website_announcement_url" class="input-enterprise text-left" dir="ltr" placeholder="https://..." />
                                </div>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                                    <div>
                                        <label class="text-sm font-medium text-gray-700 mb-1 block">لون الخلفية</label>
                                        <div class="flex gap-2">
                                            <input type="color" v-model="form.settings.website_announcement_bg_color" class="h-10 w-20 rounded-lg border-gray-300" />
                                            <input v-model="form.settings.website_announcement_bg_color" class="input-enterprise font-mono" />
                                        </div>
                                    </div>
                                    <div>
                                        <label class="text-sm font-medium text-gray-700 mb-1 block">لون النص</label>
                                        <div class="flex gap-2">
                                            <input type="color" v-model="form.settings.website_announcement_text_color" class="h-10 w-20 rounded-lg border-gray-300" />
                                            <input v-model="form.settings.website_announcement_text_color" class="input-enterprise font-mono" />
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- 13. Integrations -->
                        <div v-if="activeTab === 'integrations'" class="space-y-12">
                            <div class="border border-green-100 bg-green-50/30 rounded-xl p-6">
                                <div class="flex items-center justify-between mb-6">
                                    <div>
                                        <h3 class="text-lg font-bold text-green-900 flex items-center gap-2">
                                            <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.067 2.877 1.215 3.076.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.615 11.615 0 0012.03 0C5.399 0 .007 5.391 0 12.02c0 2.119.554 4.188 1.606 6.006L0 24l6.135-1.61a11.604 11.604 0 005.891 1.607h.005c6.632 0 12.023-5.391 12.027-12.021a11.615 11.615 0 00-3.644-8.283z"/></svg>
                                            زر الواتساب العائم
                                        </h3>
                                        <p class="text-xs text-green-700">تفعيل زر دردشة سريع يظهر أسفل الصفحة.</p>
                                    </div>
                                    <label class="relative inline-flex items-center cursor-pointer">
                                        <input type="checkbox" v-model="form.settings.website_whatsapp_floating_enabled" true-value="1" false-value="0" class="sr-only peer">
                                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-green-300 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-green-600"></div>
                                    </label>
                                </div>
                                <div :class="{'opacity-50 pointer-events-none': form.settings.website_whatsapp_floating_enabled != '1'}" class="grid grid-cols-1 gap-4 transition-opacity">
                                    <label class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-1 block">رقم الواتساب المستهدف</label>
                                    <input v-model="form.settings.website_whatsapp_floating_number" class="input-enterprise text-left font-mono" dir="ltr" placeholder="+966" />
                                    <p class="text-[10px] text-gray-500">ملاحظة: تأكد من إضافة رمز الدولة (مثال: 9665xxxxxxxx).</p>
                                </div>
                            </div>

                            <div class="border border-indigo-100 bg-indigo-50/30 rounded-xl p-6">
                                <div class="flex items-center justify-between mb-6">
                                    <div>
                                        <h3 class="text-lg font-bold text-indigo-900 flex items-center gap-2">
                                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/></svg>
                                            تكامل الشات بوت (Chatbot)
                                        </h3>
                                        <p class="text-xs text-indigo-700">ربط أنظمة الدردشة مثل Tawk.to, Intercom, Zendesk.</p>
                                    </div>
                                    <label class="relative inline-flex items-center cursor-pointer">
                                        <input type="checkbox" v-model="form.settings.website_chatbot_enabled" true-value="1" false-value="0" class="sr-only peer">
                                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-indigo-300 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-indigo-600"></div>
                                    </label>
                                </div>
                                <div :class="{'opacity-50 pointer-events-none': form.settings.website_chatbot_enabled != '1'}" class="grid grid-cols-1 gap-4 transition-opacity">
                                    <label class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-1 block">كود التكامل (Widget Script)</label>
                                    <textarea v-model="form.settings.website_chatbot_script" rows="8" class="input-enterprise font-mono text-left" dir="ltr" placeholder="<!-- Paste your script code here -->"></textarea>
                                    <p class="text-[10px] text-gray-500">تحذير: قم بلصق الكود البرمجي كما هو من مزود الخدمة. تأكد من صحة الكود لتجنب أخطاء برمجية في الموقع.</p>
                                </div>
                            </div>

                            <div class="border border-gray-100 bg-gray-50/30 rounded-xl p-6">
                                <h3 class="text-lg font-bold text-gray-900 mb-6 flex items-center gap-2">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/></svg>
                                    أكواد إضافية (Custom Scripts)
                                </h3>
                                <div class="space-y-6">
                                    <div>
                                        <label class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-1 block">سكريبتات رأس الصفحة (Header Scripts)</label>
                                        <textarea v-model="form.settings.website_scripts_header" rows="5" class="input-enterprise font-mono text-left" dir="ltr" placeholder="<!-- Google Analytics, Facebook Pixel, etc. -->"></textarea>
                                        <p class="text-[10px] text-gray-500">تظهر هذه الأكواد داخل وسم <span class="font-mono">&lt;head&gt;</span>.</p>
                                    </div>
                                    <div>
                                        <label class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-1 block">سكريبتات تذييل الصفحة (Footer Scripts)</label>
                                        <textarea v-model="form.settings.website_scripts_footer" rows="5" class="input-enterprise font-mono text-left" dir="ltr" placeholder="<!-- Tracking pixels, custom widgets, etc. -->"></textarea>
                                        <p class="text-[10px] text-gray-500">تظهر هذه الأكواد قبل إغلاق وسم <span class="font-mono">&lt;/body&gt;</span>.</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- 13. SEO & Social -->
                        <div v-if="activeTab === 'seo_social'" class="space-y-10">
                            <div>
                                <h3 class="text-lg leading-6 font-semibold text-gray-900 border-b pb-4 mb-6">تحسين محركات البحث (SEO)</h3>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                                    <div><label class="text-sm font-medium text-gray-700 flex justify-between mb-1">الوصف التعريفي للبحث <span class="badge-lang">AR</span></label><textarea v-model="form.settings.website_description_ar" rows="3" class="input-enterprise" placeholder="Meta Description"></textarea></div>
                                    <div><label class="text-sm font-medium text-gray-700 flex justify-between mb-1" dir="ltr">Meta Description <span class="badge-lang">EN</span></label><textarea v-model="form.settings.website_description_en" rows="3" class="input-enterprise text-left" dir="ltr"></textarea></div>
                                    <div class="sm:col-span-2"><label class="text-sm font-medium text-gray-700 mb-1 block">الكلمات المفتاحية (Keywords)</label><input v-model="form.settings.website_keywords" class="input-enterprise" placeholder="كلمة, كلمة اخرى, keywords" /></div>
                                </div>
                            </div>

                            <div class="pt-8 border-t border-gray-200">
                                <h3 class="text-lg leading-6 font-semibold text-gray-900 mb-6">بيانات التواصل المباشر</h3>
                                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                                    <div><label class="text-sm font-medium text-gray-700 mb-1 block">البريد الإلكتروني</label><input v-model="form.settings.website_contact_email" type="email" class="input-enterprise text-left font-mono" dir="ltr" /></div>
                                    <div><label class="text-sm font-medium text-gray-700 mb-1 block">رقم الهاتف</label><input v-model="form.settings.website_contact_phone" class="input-enterprise text-left font-mono" dir="ltr" /></div>
                                    <div><label class="text-sm font-medium text-gray-700 mb-1 block">رقم الواتساب</label><input v-model="form.settings.website_whatsapp_number" class="input-enterprise text-left font-mono" dir="ltr" placeholder="+966" /></div>
                                    <div class="sm:col-span-2 lg:col-span-3 grid grid-cols-1 sm:grid-cols-2 gap-6">
                                        <div><label class="text-sm font-medium text-gray-700 flex justify-between mb-1">عنوان المقر <span class="badge-lang">AR</span></label><input v-model="form.settings.website_address_ar" class="input-enterprise" /></div>
                                        <div><label class="text-sm font-medium text-gray-700 flex justify-between mb-1" dir="ltr">Office Address <span class="badge-lang">EN</span></label><input v-model="form.settings.website_address_en" class="input-enterprise text-left" dir="ltr" /></div>
                                    </div>
                                </div>
                            </div>

                            <div class="pt-8 border-t border-gray-200">
                                <h3 class="text-lg leading-6 font-semibold text-gray-900 mb-6">الشبكات الاجتماعية</h3>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                                    <div v-for="plat in ['facebook', 'twitter', 'instagram', 'linkedin']" :key="plat">
                                        <label class="text-sm font-medium text-gray-700 mb-1 block capitalize">{{ plat }} URL</label>
                                        <div class="mt-1 flex rounded-md shadow-sm">
                                            <span class="inline-flex items-center px-3 rounded-l-md border border-r-0 border-gray-300 bg-gray-50 text-gray-500 sm:text-sm">
                                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24" v-if="plat==='facebook'"><path d="M22 12c0-5.523-4.477-10-10-10S2 6.477 2 12c0 4.991 3.657 9.128 8.438 9.878v-6.987h-2.54V12h2.54V9.797c0-2.506 1.492-3.89 3.777-3.89 1.094 0 2.238.195 2.238.195v2.46h-1.26c-1.243 0-1.63.771-1.63 1.562V12h2.773l-.443 2.89h-2.33v6.988C18.343 21.128 22 16.991 22 12z" /></svg>
                                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24" v-if="plat==='twitter'"><path d="M8.29 20.251c7.547 0 11.675-6.253 11.675-11.675 0-.178 0-.355-.012-.53A8.348 8.348 0 0022 5.92a8.19 8.19 0 01-2.357.646 4.118 4.118 0 001.804-2.27 8.224 8.224 0 01-2.605.996 4.107 4.107 0 00-6.993 3.743 11.65 11.65 0 01-8.457-4.287 4.106 4.106 0 001.27 5.477A4.072 4.072 0 012.8 9.713v.052a4.105 4.105 0 003.292 4.022 4.095 4.095 0 01-1.853.07 4.108 4.108 0 003.834 2.85A8.233 8.233 0 012 18.407a11.616 11.616 0 006.29 1.84" /></svg>
                                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24" v-if="plat==='instagram'"><path fill-rule="evenodd" d="M12.315 2c2.43 0 2.784.013 3.808.06 1.064.049 1.791.218 2.427.465a4.902 4.902 0 011.772 1.153 4.902 4.902 0 011.153 1.772c.247.636.416 1.363.465 2.427.048 1.067.06 1.407.06 4.123v.08c0 2.643-.012 2.987-.06 4.043-.049 1.064-.218 1.791-.465 2.427a4.902 4.902 0 01-1.153 1.772 4.902 4.902 0 01-1.772 1.153c-.636.247-1.363.416-2.427.465-1.067.048-1.407.06-4.123.06h-.08c-2.643 0-2.987-.012-4.043-.06-1.064-.049-1.791-.218-2.427-.465a4.902 4.902 0 01-1.772-1.153 4.902 4.902 0 01-1.153-1.772c-.247-.636-.416-1.363-.465-2.427-.047-1.024-.06-1.379-.06-3.808v-.63c0-2.43.013-2.784.06-3.808.049-1.064.218-1.791.465-2.427a4.902 4.902 0 011.153-1.772A4.902 4.902 0 015.45 2.525c.636-.247 1.363-.416 2.427-.465C8.901 2.013 9.256 2 11.685 2h.63zm-.081 1.802h-.468c-2.456 0-2.784.011-3.807.058-.975.045-1.504.207-1.857.344-.467.182-.8.398-1.15.748-.35.35-.566.683-.748 1.15-.137.353-.3.882-.344 1.857-.047 1.023-.058 1.351-.058 3.807v.468c0 2.456.011 2.784.058 3.807.045.975.207 1.504.344 1.857.182.466.399.8.748 1.15.35.35.683.566 1.15.748.353.137.882.3 1.857.344 1.054.048 1.37.058 4.041.058h.08c2.597 0 2.917-.01 3.96-.058.976-.045 1.505-.207 1.858-.344.466-.182.8-.398 1.15-.748.35-.35.566-.683.748-1.15.137-.353.3-.882.344-1.857.048-1.055.058-1.37.058-4.041v-.08c0-2.597-.01-2.917-.058-3.96-.045-.976-.207-1.505-.344-1.858a3.097 3.097 0 00-.748-1.15 3.098 3.098 0 00-1.15-.748c-.353-.137-.882-.3-1.857-.344-1.023-.047-1.351-.058-3.807-.058zM12 6.865a5.135 5.135 0 110 10.27 5.135 5.135 0 010-10.27zm0 1.802a3.333 3.333 0 100 6.666 3.333 3.333 0 000-6.666zm5.338-3.205a1.2 1.2 0 110 2.4 1.2 1.2 0 010-2.4z" clip-rule="evenodd" /></svg>
                                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24" v-if="plat==='linkedin'"><path fill-rule="evenodd" d="M19 0h-14c-2.761 0-5 2.239-5 5v14c0 2.761 2.239 5 5 5h14c2.762 0 5-2.239 5-5v-14c0-2.761-2.238-5-5-5zm-11 19h-3v-11h3v11zm-1.5-12.268c-.966 0-1.75-.79-1.75-1.764s.784-1.764 1.75-1.764 1.75.79 1.75 1.764-.783 1.764-1.75 1.764zm13.5 12.268h-3v-5.604c0-3.368-4-3.113-4 0v5.604h-3v-11h3v1.765c1.396-2.586 7-2.777 7 2.476v6.759z" clip-rule="evenodd" /></svg>
                                            </span>
                                            <input v-model="form.settings[`website_${plat}_url`]" class="flex-1 min-w-0 block w-full px-3 py-2 rounded-none rounded-r-md sm:text-sm border-gray-300 focus:ring-indigo-500 focus:border-indigo-500 text-left font-mono" dir="ltr" placeholder="https://" />
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </SystemLayout>
</template>

<style scoped>
.font-premium { font-family: 'Outfit', 'Inter', 'Cairo', sans-serif; }

.input-enterprise {
    @apply mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm py-2 px-3 bg-white dark:bg-gray-700 dark:border-gray-600 dark:text-white transition-colors duration-200;
}
textarea.input-enterprise {
    @apply py-3;
}

.btn-enterprise { 
    @apply inline-flex justify-center items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed; 
}
.btn-outline { 
    @apply inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-all duration-200 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-700; 
}

.badge-lang {
    @apply inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold tracking-wider uppercase bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300 border border-gray-200 dark:border-gray-600;
}

.animate-fade-in { animation: fadeIn 0.3s ease-out; }
@keyframes fadeIn { from { opacity: 0; transform: translateY(5px); } to { opacity: 1; transform: translateY(0); } }

/* Minimal sleek scrollbar for CRM feel */
.custom-scrollbar::-webkit-scrollbar { width: 6px; }
.custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: #d1d5db; border-radius: 20px; }
.custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #9ca3af; }
.dark .custom-scrollbar::-webkit-scrollbar-thumb { background: #4b5563; }
.dark .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #6b7280; }
</style>
