<script setup>
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

const props = defineProps({
    settings: {
        type: Object,
        required: true,
    },
    canManageScripts: {
        type: Boolean,
        default: false,
    },
});

const DEVICES = [
    { id: 'desktop', icon: '🖥', labelKey: 'system.website.devices.desktop' },
    { id: 'tablet', icon: '📱', labelKey: 'system.website.devices.tablet' },
    { id: 'mobile', icon: '📲', labelKey: 'system.website.devices.mobile' },
];

const activeDevice = ref('desktop');
const isLoading = ref(true);
const iframeRef = ref(null);

const previewUrl = computed(() => {
    return route('public.landing.preview', { preview: 1 });
});

// Map CMS form settings (website_ and landing_ prefixes) to LandingPreview schema
function mapFormSettingsToPreview(s) {
    if (!s) return {};
    const isTrue = (v) => v === '1' || v === 1 || v === true;

    return {
        ...s,
        title_ar: s.website_title_ar ?? '',
        title_en: s.website_title_en ?? '',
        description_ar: s.website_description_ar ?? '',
        description_en: s.website_description_en ?? '',
        keywords: s.website_keywords ?? '',
        logo:
            typeof s.website_logo === 'string'
                ? s.website_logo
                : s.website_logo instanceof File
                  ? URL.createObjectURL(s.website_logo)
                  : '',
        favicon:
            typeof s.website_favicon === 'string'
                ? s.website_favicon
                : s.website_favicon instanceof File
                  ? URL.createObjectURL(s.website_favicon)
                  : '',
        primary_color: s.website_primary_color || '#2563eb',
        contact_email: s.website_contact_email ?? '',
        contact_phone: s.website_contact_phone ?? '',
        whatsapp_number: s.website_whatsapp_number ?? '',
        whatsapp_floating_enabled: isTrue(s.website_whatsapp_floating_enabled),
        whatsapp_floating_number: s.website_whatsapp_floating_number ?? '',
        chatbot_enabled: isTrue(s.website_chatbot_enabled),
        // Raw scripts execute inside the preview iframe. They are only
        // mapped when the current actor is allowed to manage them; the
        // server also blanks them for every other role.
        chatbot_script: props.canManageScripts ? (s.website_chatbot_script ?? '') : '',
        scripts_header: props.canManageScripts ? (s.website_scripts_header ?? '') : '',
        scripts_footer: props.canManageScripts ? (s.website_scripts_footer ?? '') : '',
        announcement_enabled: isTrue(s.website_announcement_enabled),
        announcement_text_ar: s.website_announcement_text_ar ?? '',
        announcement_text_en: s.website_announcement_text_en ?? '',
        announcement_url: s.website_announcement_url ?? '',
        announcement_bg_color: s.website_announcement_bg_color || '#6366f1',
        announcement_text_color: s.website_announcement_text_color || '#ffffff',
        login_button_text_ar: s.website_login_button_text_ar || 'دخول',
        login_button_text_en: s.website_login_button_text_en || 'Login',
        social_links: Array.isArray(s.website_social_links) ? s.website_social_links : [],
        facebook_url: s.website_facebook_url ?? '',
        twitter_url: s.website_twitter_url ?? '',
        instagram_url: s.website_instagram_url ?? '',
        linkedin_url: s.website_linkedin_url ?? '',
        address_ar: s.website_address_ar ?? '',
        address_en: s.website_address_en ?? '',
        header_menu: Array.isArray(s.website_header_menu) ? s.website_header_menu : [],
        footer_menu: Array.isArray(s.website_footer_menu) ? s.website_footer_menu : [],
        footer_about_ar: s.website_footer_about_ar ?? '',
        footer_about_en: s.website_footer_about_en ?? '',
        copyright_text_ar: s.website_copyright_text_ar ?? '',
        copyright_text_en: s.website_copyright_text_en ?? '',
        hero_title_ar: s.landing_hero_title_ar ?? '',
        hero_title_en: s.landing_hero_title_en ?? '',
        hero_subtitle_ar: s.landing_hero_subtitle_ar ?? '',
        hero_subtitle_en: s.landing_hero_subtitle_en ?? '',
        hero_cta_text_ar: s.landing_hero_cta_text_ar ?? '',
        hero_cta_text_en: s.landing_hero_cta_text_en ?? '',
        hero_typed_enabled: isTrue(s.landing_hero_typed_enabled),
        hero_typed_prefix_ar: s.landing_hero_typed_prefix_ar ?? '',
        hero_typed_prefix_en: s.landing_hero_typed_prefix_en ?? '',
        hero_typed_suffix_ar: s.landing_hero_typed_suffix_ar ?? '',
        hero_typed_suffix_en: s.landing_hero_typed_suffix_en ?? '',
        hero_typed_phrases_ar: s.landing_hero_typed_phrases_ar ?? '',
        hero_typed_phrases_en: s.landing_hero_typed_phrases_en ?? '',
        features_title_ar: s.landing_features_title_ar ?? '',
        features_title_en: s.landing_features_title_en ?? '',
        features_subtitle_ar: s.landing_features_subtitle_ar ?? '',
        features_subtitle_en: s.landing_features_subtitle_en ?? '',
        pricing_title_ar: s.landing_pricing_title_ar ?? '',
        pricing_title_en: s.landing_pricing_title_en ?? '',
        pricing_subtitle_ar: s.landing_pricing_subtitle_ar ?? '',
        pricing_subtitle_en: s.landing_pricing_subtitle_en ?? '',
        banners_list: Array.isArray(s.landing_banners_list) ? s.landing_banners_list : [],
        features_list: Array.isArray(s.landing_features_list) ? s.landing_features_list : [],
        faq_list: Array.isArray(s.landing_faq_list) ? s.landing_faq_list : [],
        testimonials_list: Array.isArray(s.landing_testimonials_list)
            ? s.landing_testimonials_list
            : [],
        stats_list: Array.isArray(s.landing_stats_list) ? s.landing_stats_list : [],
        custom_pages: Array.isArray(s.website_custom_pages) ? s.website_custom_pages : [],
        page_terms_ar: s.website_page_terms_ar ?? '',
        page_terms_en: s.website_page_terms_en ?? '',
        page_privacy_ar: s.website_page_privacy_ar ?? '',
        page_privacy_en: s.website_page_privacy_en ?? '',
        maintenance_mode: s.website_maintenance_mode ?? '0',
        maintenance_title_ar: s.website_maintenance_title_ar ?? '',
        maintenance_title_en: s.website_maintenance_title_en ?? '',
        maintenance_message_ar: s.website_maintenance_message_ar ?? '',
        maintenance_message_en: s.website_maintenance_message_en ?? '',
        features_enabled: isTrue(s.landing_features_enabled),
        pricing_enabled: isTrue(s.landing_pricing_enabled),
        stats_enabled: isTrue(s.landing_stats_enabled),
        faq_enabled: isTrue(s.landing_faq_enabled),
        testimonials_enabled: isTrue(s.landing_testimonials_enabled),
        contact_enabled: isTrue(s.landing_contact_enabled),
    };
}

function sendSettingsToIframe() {
    if (!iframeRef.value?.contentWindow) return;
    try {
        const payload = JSON.parse(JSON.stringify(mapFormSettingsToPreview(props.settings)));
        iframeRef.value.contentWindow.postMessage(
            {
                type: 'KHIDMH_PREVIEW_UPDATE',
                settings: payload,
            },
            '*'
        );
    } catch (err) {
        console.error('Failed to postMessage settings to preview iframe:', err);
    }
}

function onIframeLoad() {
    isLoading.value = false;
    sendSettingsToIframe();
}

function reloadIframe() {
    if (!iframeRef.value) return;
    isLoading.value = true;
    iframeRef.value.src = previewUrl.value;
}

watch(
    () => props.settings,
    () => {
        sendSettingsToIframe();
    },
    { deep: true }
);

onMounted(() => {
    // Check if iframe already completed loading
    try {
        if (iframeRef.value?.contentDocument?.readyState === 'complete') {
            isLoading.value = false;
            sendSettingsToIframe();
        }
    } catch (e) {
        // Cross-origin check fallback
    }

    // Safety fallback: ensure spinner never gets stuck
    setTimeout(() => {
        if (isLoading.value) {
            isLoading.value = false;
            sendSettingsToIframe();
        }
    }, 2000);
});

onUnmounted(() => {
    if (iframeRef.value) {
        iframeRef.value.src = '';
    }
});
</script>

<template>
    <div
        class="flex flex-col h-full bg-gray-100 dark:bg-gray-950 border-r md:border-r-0 md:border-l border-gray-200 dark:border-gray-800 select-none"
    >
        <!-- Preview Toolbar -->
        <div
            class="flex items-center justify-between px-3 py-2 border-b border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 flex-shrink-0"
        >
            <!-- Status Badge -->
            <div class="flex items-center gap-2">
                <span
                    class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-100 dark:bg-emerald-950/80 text-emerald-700 dark:text-emerald-400 border border-emerald-300 dark:border-emerald-800"
                >
                    <span class="h-1.5 w-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                    {{ t('system.website.live') }}
                </span>
            </div>

            <!-- Device Switcher -->
            <div
                class="flex items-center gap-1 bg-gray-100 dark:bg-gray-800 rounded-lg p-1 border border-gray-200 dark:border-gray-700"
            >
                <button
                    v-for="d in DEVICES"
                    :key="d.id"
                    @click="activeDevice = d.id"
                    type="button"
                    class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md text-xs font-medium transition-all"
                    :class="
                        activeDevice === d.id
                            ? 'bg-white dark:bg-gray-700 text-action-primary shadow-sm font-bold'
                            : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white'
                    "
                    :title="t(d.labelKey)"
                >
                    <span>{{ d.icon }}</span>
                    <span class="hidden sm:inline">{{ t(d.labelKey) }}</span>
                </button>
            </div>

            <!-- Actions -->
            <div class="flex items-center gap-1">
                <button
                    @click="reloadIframe"
                    type="button"
                    class="p-1.5 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 rounded-md hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors"
                    :title="t('system.website.reload')"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"
                        />
                    </svg>
                </button>
                <a
                    :href="previewUrl"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="p-1.5 text-gray-500 hover:text-action-primary dark:text-gray-400 rounded-md hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors"
                    :title="t('system.website.open_new_tab')"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"
                        />
                    </svg>
                </a>
            </div>
        </div>

        <!-- Preview Viewport -->
        <div
            class="flex-1 overflow-auto p-4 flex items-center justify-center bg-gray-100 dark:bg-gray-950 relative"
        >
            <!-- Loading Indicator -->
            <transition
                enter-active-class="transition-opacity duration-200"
                leave-active-class="transition-opacity duration-200"
                enter-from-class="opacity-0"
                leave-to-class="opacity-0"
            >
                <div
                    v-if="isLoading"
                    class="absolute inset-0 flex items-center justify-center bg-white/90 dark:bg-gray-900/90 z-20 backdrop-blur-sm pointer-events-none"
                >
                    <div class="flex flex-col items-center gap-3 text-gray-600 dark:text-gray-300">
                        <svg
                            class="animate-spin h-8 w-8 text-action-primary"
                            fill="none"
                            viewBox="0 0 24 24"
                        >
                            <circle
                                class="opacity-25"
                                cx="12"
                                cy="12"
                                r="10"
                                stroke="currentColor"
                                stroke-width="4"
                            />
                            <path
                                class="opacity-75"
                                fill="currentColor"
                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                            />
                        </svg>
                        <span class="text-sm font-medium">
                            {{ t('system.website.loading_preview') }}
                        </span>
                    </div>
                </div>
            </transition>

            <!-- Responsive Device Frame -->
            <div
                class="transition-all duration-300 ease-out bg-white dark:bg-gray-900 overflow-hidden"
                :class="{
                    'w-full h-full rounded-none shadow-none': activeDevice === 'desktop',
                    'w-[768px] h-[95%] max-h-[880px] rounded-2xl shadow-2xl border-4 border-gray-800 dark:border-gray-700':
                        activeDevice === 'tablet',
                    'w-[375px] h-[667px] rounded-3xl shadow-2xl border-8 border-gray-800 dark:border-gray-700':
                        activeDevice === 'mobile',
                }"
            >
                <iframe
                    ref="iframeRef"
                    :src="previewUrl"
                    @load="onIframeLoad"
                    class="w-full h-full border-0 bg-white"
                />
            </div>
        </div>
    </div>
</template>
