<script setup>
import { ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import { useForm } from '@inertiajs/vue3';
import SystemLayout from '@/Layouts/SystemLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import { useToast } from '@/Composables/useToast';
import PreviewPane from '@/Components/System/Website/PreviewPane.vue';
import GeneralTab from './Tabs/GeneralTab.vue';
import ListsTab from './Tabs/ListsTab.vue';
import LandingTab from './Tabs/LandingTab.vue';
import PagesTab from './Tabs/PagesTab.vue';
import AdvancedTab from './Tabs/AdvancedTab.vue';
import IntegrationsTab from './Tabs/IntegrationsTab.vue';
import SeoSocialTab from './Tabs/SeoSocialTab.vue';

const { t } = useI18n();

const props = defineProps({
    settings: Object,
    canManageScripts: { type: Boolean, default: false },
});
const { success, error } = useToast();
const activeTab = ref('general');
const showPreview = ref(window.innerWidth >= 1024);
const saveStatus = ref('idle'); // 'idle', 'saving', 'saved', 'error'
const hasChanges = ref(false);

// Each tab has an id (data attribute), a translation key, and an icon
// path. The icon path stays as a static SVG path; only the label
// comes from i18n so the rest of the page composition is untouched.
const tabs = [
    { id: 'general', key: 'general' },
    { id: 'menus', key: 'menus' },
    { id: 'banners', key: 'banners' },
    { id: 'landing', key: 'landing' },
    { id: 'features', key: 'features' },
    { id: 'pricing', key: 'pricing' },
    { id: 'stats', key: 'stats' },
    { id: 'testimonials', key: 'testimonials' },
    { id: 'faq', key: 'faq' },
    { id: 'contact', key: 'contact' },
    { id: 'pages', key: 'pages' },
    { id: 'maintenance', key: 'maintenance' },
    { id: 'footer', key: 'footer' },
    { id: 'announcement', key: 'announcement' },
    { id: 'integrations', key: 'integrations' },
    { id: 'seo_social', key: 'seo_social' },
];

const tabIcons = {
    general:
        'M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z',
    menus: 'M4 6h16M4 12h16M4 18h16',
    banners:
        'M7 4v16M17 4v16M3 8h4m10 0h4M3 12h18M3 16h4m10 0h4M4 20h16a1 1 0 001-1V5a1 1 0 00-1-1H4a1 1 0 00-1 1v14a1 1 0 001 1z',
    landing:
        'M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z',
    features:
        'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z',
    pricing:
        'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1c-1.11 0-2.08.402-2.599 1M12 8V7m0 11c-1.11 0-2.08-.402-2.599-1M12 18v1m0-1c1.11 0 2.08-.402 2.599-1',
    stats: 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z',
    testimonials:
        'M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z',
    faq: 'M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
    contact:
        'M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z',
    pages: 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
    maintenance:
        'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z',
    footer: 'M12 15l-4-4h8l-4 4z',
    announcement:
        'M11 5.882V19.297A2.497 2.497 0 0012.5 22.5a2.497 2.497 0 002.5-2.5 2.497 2.497 0 00-1.5-2.297V5.882c2.556.19 4.5 2.307 4.5 4.868v.25c0 2.69 2.19 4.88 4.88 4.88.04 0 .08 0 .12-.005V13.88c-1.586 0-2.88-1.294-2.88-2.88v-.25c0-4.14-3.36-7.5-7.5-7.5s-7.5 3.36-7.5 7.5v.25c0 1.586-1.294 2.88-2.88 2.88v1.995c.04.004.08.005.12.005 2.69 0 4.88-2.19 4.88-4.88v-.25c0-2.561 1.944-4.678 4.5-4.868z',
    integrations: 'M13 10V3L4 14h7v7l9-11h-7z',
    seo_social:
        'M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1',
};

const listKeys = [
    'website_header_menu',
    'website_footer_menu',
    'landing_banners_list',
    'landing_features_list',
    'landing_faq_list',
    'landing_testimonials_list',
    'landing_stats_list',
    'website_custom_pages',
    'website_social_links',
];

function initSettings(src) {
    const out = {};
    for (const key of Object.keys(src)) {
        out[key] = listKeys.includes(key) ? src[key] || [] : src[key];
    }
    return out;
}

const form = useForm({ settings: initSettings(props.settings) });

const addItem = (listKey, defaults) => {
    form.settings[listKey].push({ ...defaults });
};
const removeItem = (listKey, index) => {
    form.settings[listKey].splice(index, 1);
};

// Upload an image (logo, favicon, or banner) via dedicated endpoint, returns the URL
async function uploadImage(key, file) {
    const fd = new FormData();
    fd.append('key', key);
    fd.append('image', file);
    const res = await fetch(route('system.settings.website.upload-image'), {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
            Accept: 'application/json',
        },
        body: fd,
    });
    if (!res.ok) {
        const errorData = await res.json().catch(() => ({}));
        throw new Error(errorData.message || 'Upload failed');
    }
    const json = await res.json();
    return json.url;
}

const submit = async () => {
    // 1. Upload any pending file fields first, replace with URLs
    const imageKeys = ['website_logo', 'website_favicon'];
    for (const key of imageKeys) {
        if (typeof window !== 'undefined' && form.settings[key] instanceof window.File) {
            try {
                form.settings[key] = await uploadImage(key, form.settings[key]);
            } catch (e) {
                console.error('Image upload failed', e);
                error(t('system.website.upload_error'));
                return;
            }
        }
    }

    // 2. Submit the rest as regular JSON via PUT
    saveStatus.value = 'saving';
    form.put(route('system.settings.website.update'), {
        preserveScroll: true,
        onSuccess: () => {
            saveStatus.value = 'saved';
            hasChanges.value = false;
            success(t('system.website.saved'));
            setTimeout(() => {
                saveStatus.value = 'idle';
            }, 3000);
        },
        onError: () => {
            saveStatus.value = 'error';
            error(t('system.website.save_error'));
        },
    });
};

const togglePreview = () => {
    showPreview.value = !showPreview.value;
};

// Watch for unsaved changes
watch(
    () => form.settings,
    () => {
        hasChanges.value = true;
    },
    { deep: true }
);
</script>

<template>
    <SystemLayout :title="t('system.website.title')">
        <div class="space-y-6">
            <!-- Page Header -->
            <PageHeader
                :title="t('system.website.header_title')"
                :subtitle="t('system.website.header_subtitle')"
            >
                <template #icon>
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"
                        />
                    </svg>
                </template>

                <template #actions>
                    <!-- Save Status Indicator -->
                    <div
                        v-if="saveStatus !== 'idle'"
                        class="flex items-center gap-2 px-3 py-1.5 rounded-xl text-sm"
                    >
                        <svg
                            v-if="saveStatus === 'saving'"
                            class="animate-spin h-4 w-4 text-action-primary"
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
                        <svg
                            v-else-if="saveStatus === 'saved'"
                            class="h-4 w-4 text-emerald-600"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M5 13l4 4L19 7"
                            />
                        </svg>
                        <svg
                            v-else
                            class="h-4 w-4 text-rose-600"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M6 18L18 6M6 6l12 12"
                            />
                        </svg>
                        <span
                            :class="{
                                'text-action-primary': saveStatus === 'saving',
                                'text-emerald-600 dark:text-emerald-400': saveStatus === 'saved',
                                'text-rose-600 dark:text-rose-400': saveStatus === 'error',
                            }"
                        >
                            {{
                                saveStatus === 'saving'
                                    ? t('system.website.saving')
                                    : saveStatus === 'saved'
                                      ? t('system.website.saved')
                                      : t('system.website.save_error')
                            }}
                        </span>
                    </div>

                    <!-- Unsaved Changes Indicator -->
                    <div
                        v-if="hasChanges && saveStatus === 'idle'"
                        class="flex items-center gap-2 px-3 py-1.5 bg-amber-50 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300 rounded-xl text-sm"
                    >
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"
                            />
                        </svg>
                        {{ t('system.website.unsaved_changes') }}
                    </div>

                    <!-- Glass Toolbar -->
                    <div
                        class="flex items-center gap-1.5 p-1.5 bg-gray-50/50 dark:bg-gray-900/50 backdrop-blur-md rounded-2xl border border-gray-200/50 dark:border-gray-700/50 shadow-inner"
                    >
                        <button
                            @click="togglePreview"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm font-medium rounded-xl transition-all shadow-sm"
                            :class="
                                showPreview
                                    ? 'bg-selection-bg text-action-primary'
                                    : 'text-gray-700 dark:text-gray-300 hover:text-action-primary hover:bg-white dark:hover:bg-gray-800'
                            "
                        >
                            <svg
                                class="w-4 h-4"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"
                                />
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"
                                />
                            </svg>
                            <span>{{ t('system.website.preview_toggle') }}</span>
                        </button>

                        <a
                            :href="route('public.landing.preview', { preview: 1 })"
                            target="_blank"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm font-medium text-gray-700 dark:text-gray-300 hover:text-action-primary hover:bg-white dark:hover:bg-gray-800 rounded-xl transition-all shadow-sm"
                        >
                            <svg
                                class="w-4 h-4"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"
                                />
                            </svg>
                            <span>{{ t('system.website.preview') }}</span>
                        </a>
                    </div>

                    <!-- CTA Button -->
                    <button
                        @click="submit"
                        :disabled="form.processing"
                        class="inline-flex items-center gap-2 px-5 py-2.5 bg-action-primary hover:bg-action-primary-hover text-white rounded-xl text-sm font-semibold transition-all shadow-md hover:shadow-focus-ring-soft active:scale-95 disabled:opacity-50"
                    >
                        <svg
                            v-if="!form.processing"
                            class="w-4 h-4"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M5 13l4 4L19 7"
                            />
                        </svg>
                        <svg
                            v-else
                            class="w-4 h-4 animate-spin"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <circle
                                class="opacity-25"
                                cx="12"
                                cy="12"
                                r="10"
                                stroke="currentColor"
                                stroke-width="4"
                            ></circle>
                            <path
                                class="opacity-75"
                                fill="currentColor"
                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                            ></path>
                        </svg>
                        <span>
                            {{
                                form.processing
                                    ? t('system.website.saving')
                                    : t('system.website.save')
                            }}
                        </span>
                    </button>
                </template>
            </PageHeader>

            <!-- Layout -->
            <div
                class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden flex flex-col md:flex-row min-h-[75vh]"
            >
                <!-- Sidebar -->
                <div
                    class="w-full md:w-64 bg-gray-50 dark:bg-gray-900 border-l border-gray-200 dark:border-gray-700 flex-shrink-0"
                >
                    <nav class="space-y-0.5 p-3">
                        <button
                            v-for="tab in tabs"
                            :key="tab.id"
                            @click="activeTab = tab.id"
                            :class="[
                                activeTab === tab.id
                                    ? 'bg-selection-bg border-action-primary text-selection-text'
                                    : 'border-transparent text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800 hover:text-gray-900 dark:hover:text-white',
                                'group border-r-4 px-4 py-3.5 w-full flex items-center text-sm font-medium transition-colors text-right rounded-l-lg',
                            ]"
                        >
                            <svg
                                :class="[
                                    activeTab === tab.id
                                        ? 'text-action-primary'
                                        : 'text-gray-400 group-hover:text-gray-500',
                                    'flex-shrink-0 ml-3 h-5 w-5',
                                ]"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    :d="tabIcons[tab.key]"
                                />
                            </svg>
                            <span class="truncate">{{ t(`system.website.tabs.${tab.key}`) }}</span>
                        </button>
                    </nav>
                </div>

                <!-- Main Content Area -->
                <div class="flex-1 flex flex-col md:flex-row">
                    <!-- Form Content -->
                    <div
                        class="flex-1 p-6 md:p-10 lg:p-12 overflow-y-auto max-h-[75vh] custom-scrollbar bg-white dark:bg-gray-800"
                    >
                        <div class="max-w-4xl mx-auto space-y-12 animate-fade-in pb-12">
                            <GeneralTab
                                v-if="activeTab === 'general'"
                                :form="form"
                                :uploadImage="uploadImage"
                            />

                            <ListsTab
                                v-if="activeTab === 'menus' || activeTab === 'banners'"
                                :form="form"
                                :addItem="addItem"
                                :removeItem="removeItem"
                                :tab="activeTab"
                                :uploadImage="uploadImage"
                            />

                            <LandingTab
                                v-if="
                                    activeTab === 'landing' ||
                                    activeTab === 'features' ||
                                    activeTab === 'pricing' ||
                                    activeTab === 'stats' ||
                                    activeTab === 'testimonials' ||
                                    activeTab === 'faq' ||
                                    activeTab === 'contact'
                                "
                                :form="form"
                                :addItem="addItem"
                                :removeItem="removeItem"
                                :tab="activeTab"
                            />

                            <PagesTab
                                v-if="activeTab === 'pages'"
                                :form="form"
                                :addItem="addItem"
                                :removeItem="removeItem"
                            />

                            <AdvancedTab
                                v-if="
                                    activeTab === 'maintenance' ||
                                    activeTab === 'footer' ||
                                    activeTab === 'announcement'
                                "
                                :form="form"
                                :tab="activeTab"
                            />

                            <IntegrationsTab
                                v-if="activeTab === 'integrations'"
                                :form="form"
                                :can-manage-scripts="props.canManageScripts"
                            />

                            <SeoSocialTab
                                v-if="activeTab === 'seo_social'"
                                :form="form"
                                :addItem="addItem"
                                :removeItem="removeItem"
                            />
                        </div>
                    </div>

                    <!-- Live Preview Pane (Responsive) -->
                    <div
                        v-if="showPreview"
                        class="flex-1 min-w-0 border-t md:border-t-0 md:border-l border-gray-200 dark:border-gray-700"
                        style="height: 75vh"
                    >
                        <PreviewPane
                            :settings="form.settings"
                            :can-manage-scripts="props.canManageScripts"
                        />
                    </div>
                </div>
            </div>

            <!-- Truly Floating Bottom Action Bar via Teleport -->
            <Teleport to="body">
                <Transition
                    enter-active-class="transition duration-300 ease-out"
                    enter-from-class="transform translate-y-16 opacity-0"
                    enter-to-class="transform translate-y-0 opacity-100"
                    leave-active-class="transition duration-200 ease-in"
                    leave-from-class="transform translate-y-0 opacity-100"
                    leave-to-class="transform translate-y-16 opacity-0"
                >
                    <div
                        class="fixed bottom-6 start-4 end-4 md:start-auto md:end-8 z-50 px-4 py-3 bg-white/95 dark:bg-gray-900/95 backdrop-blur-md border border-gray-200/80 dark:border-gray-700/80 shadow-2xl rounded-2xl flex items-center justify-between gap-4 md:min-w-[400px]"
                    >
                        <div
                            class="flex items-center gap-2.5 text-xs text-gray-600 dark:text-gray-300 min-w-0"
                        >
                            <span
                                class="inline-block w-2.5 h-2.5 rounded-full shrink-0"
                                :class="
                                    hasChanges
                                        ? 'bg-amber-500 animate-pulse'
                                        : saveStatus === 'saved'
                                          ? 'bg-emerald-500'
                                          : 'bg-action-primary'
                                "
                            ></span>
                            <span class="truncate font-medium">
                                {{
                                    hasChanges
                                        ? t('system.website.unsaved_changes')
                                        : saveStatus === 'saved'
                                          ? t('system.website.saved')
                                          : t('system.website.save_all_hint')
                                }}
                            </span>
                        </div>
                        <div class="flex items-center gap-2 shrink-0">
                            <button
                                type="button"
                                @click="submit"
                                :disabled="form.processing"
                                class="inline-flex items-center gap-2 px-5 py-2.5 bg-action-primary hover:bg-action-primary-hover text-white rounded-xl text-sm font-semibold transition-all shadow-md hover:shadow-focus-ring-soft active:scale-95 disabled:opacity-50 cursor-pointer"
                            >
                                <svg
                                    v-if="!form.processing"
                                    class="w-4 h-4"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M5 13l4 4L19 7"
                                    />
                                </svg>
                                <svg
                                    v-else
                                    class="w-4 h-4 animate-spin"
                                    fill="none"
                                    stroke="currentColor"
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
                                        d="M4 12a8 8 0 018-8v8H4z"
                                    />
                                </svg>
                                <span>
                                    {{
                                        form.processing
                                            ? t('system.website.saving')
                                            : t('system.website.save')
                                    }}
                                </span>
                            </button>
                        </div>
                    </div>
                </Transition>
            </Teleport>
        </div>
    </SystemLayout>
</template>

<!-- Shared utility classes (unscoped to reach child tab components) -->
<style>
.input-enterprise {
    @apply mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-action-primary focus:ring-focus-ring sm:text-sm py-2 px-3 bg-white dark:bg-gray-700 dark:text-white transition-colors duration-200;
}
textarea.input-enterprise {
    @apply py-3;
}

.btn-enterprise {
    @apply inline-flex justify-center items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-action-primary hover:bg-action-primary-hover focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-focus-ring transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed;
}
.btn-outline {
    @apply inline-flex items-center px-3 py-2 border border-gray-300 dark:border-gray-600 shadow-sm text-sm font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 transition-all duration-200;
}

.badge-lang {
    @apply inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold tracking-wider uppercase bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 border border-gray-200 dark:border-gray-600;
}
</style>

<style scoped>
.animate-fade-in {
    animation: fadeIn 0.3s ease-out;
}
@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(5px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.custom-scrollbar::-webkit-scrollbar {
    width: 6px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background: #d1d5db;
    border-radius: 20px;
}
.custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background: #9ca3af;
}
.dark .custom-scrollbar::-webkit-scrollbar-thumb {
    background: #4b5563;
}
.dark .custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background: #6b7280;
}
</style>
