<script setup>
import { ref } from 'vue';
import AppFileUpload from '@/Components/Common/AppFileUpload.vue';
import { useToast } from '@/Composables/useToast';

const props = defineProps({
    form: Object,
    addItem: Function,
    removeItem: Function,
    tab: String,
    uploadImage: Function,
});

const { error: toastError } = useToast();
const uploadingIndex = ref(null);

async function onBannerFileSelect(file, banner, index) {
    if (!file) return;

    if (props.uploadImage) {
        uploadingIndex.value = index;
        try {
            const url = await props.uploadImage('banner_image', file);
            banner.image = url;
        } catch (err) {
            console.error('Banner upload failed', err);
        } finally {
            uploadingIndex.value = null;
        }
    } else {
        banner.image = file;
    }
}
</script>

<template>
    <div class="space-y-10">
        <!-- Header Menu -->
        <div v-if="tab === 'menus'">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h3 class="text-lg leading-6 font-semibold text-gray-900 dark:text-white">
                        {{ $t('tenant_site.lists.header_links') }}
                    </h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                        {{ $t('tenant_site.lists.header_desc') }}
                    </p>
                </div>
                <button
                    @click="addItem('website_header_menu', { label_ar: '', label_en: '', url: '' })"
                    class="inline-flex items-center px-3 py-2 border border-gray-300 dark:border-gray-600 shadow-sm text-sm font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors text-xs py-1.5"
                >
                    {{ $t('system_settings.condition_items.add') }}
                </button>
            </div>
            <div class="space-y-3">
                <div
                    v-for="(item, index) in form.settings.website_header_menu"
                    :key="index"
                    class="p-4 border border-gray-200 dark:border-gray-700 rounded-lg bg-gray-50/50 dark:bg-gray-800/50 flex gap-4 items-start group"
                >
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 flex-1">
                        <div>
                            <label class="text-xs text-gray-500 dark:text-gray-400 mb-1 block">
                                {{ $t('tenant_site.lists.name_arabic') }}
                            </label>
                            <input v-model="item.label_ar" class="input-enterprise" />
                        </div>
                        <div>
                            <label class="text-xs text-gray-500 dark:text-gray-400 mb-1 block">
                                {{ $t('system.website.label_en') }}
                            </label>
                            <input
                                v-model="item.label_en"
                                class="input-enterprise text-left"
                                dir="ltr"
                            />
                        </div>
                        <div>
                            <label class="text-xs text-gray-500 dark:text-gray-400 mb-1 block">
                                {{ $t('tenant_site.lists.path_url') }}
                            </label>
                            <input
                                v-model="item.url"
                                class="input-enterprise text-left font-mono"
                                dir="ltr"
                            />
                        </div>
                    </div>
                    <button
                        @click="removeItem('website_header_menu', index)"
                        class="text-gray-400 hover:text-red-600 mt-6"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                            />
                        </svg>
                    </button>
                </div>
                <div
                    v-if="!form.settings.website_header_menu.length"
                    class="text-center py-8 border-2 border-dashed border-gray-200 dark:border-gray-700 rounded-lg text-gray-500 dark:text-gray-400"
                >
                    {{ $t('tenant_site.lists.empty_add_first') }}
                </div>
            </div>
        </div>

        <!-- Footer Menu -->
        <div v-if="tab === 'menus'" class="pt-6 border-t border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h3 class="text-lg leading-6 font-semibold text-gray-900 dark:text-white">
                        {{ $t('tenant_site.lists.footer_links') }}
                    </h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                        {{ $t('tenant_site.lists.footer_desc') }}
                    </p>
                </div>
                <button
                    @click="addItem('website_footer_menu', { label_ar: '', label_en: '', url: '' })"
                    class="inline-flex items-center px-3 py-2 border border-gray-300 dark:border-gray-600 shadow-sm text-sm font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors text-xs py-1.5"
                >
                    {{ $t('system_settings.condition_items.add') }}
                </button>
            </div>
            <div class="space-y-3">
                <div
                    v-for="(item, index) in form.settings.website_footer_menu"
                    :key="index"
                    class="p-4 border border-gray-200 dark:border-gray-700 rounded-lg bg-gray-50/50 dark:bg-gray-800/50 flex gap-4 items-start group"
                >
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 flex-1">
                        <div>
                            <label class="text-xs text-gray-500 dark:text-gray-400 mb-1 block">
                                {{ $t('tenant_site.lists.name_arabic') }}
                            </label>
                            <input v-model="item.label_ar" class="input-enterprise" />
                        </div>
                        <div>
                            <label class="text-xs text-gray-500 dark:text-gray-400 mb-1 block">
                                {{ $t('system.website.label_en') }}
                            </label>
                            <input
                                v-model="item.label_en"
                                class="input-enterprise text-left"
                                dir="ltr"
                            />
                        </div>
                        <div>
                            <label class="text-xs text-gray-500 dark:text-gray-400 mb-1 block">
                                {{ $t('tenant_site.lists.path_url') }}
                            </label>
                            <input
                                v-model="item.url"
                                class="input-enterprise text-left font-mono"
                                dir="ltr"
                            />
                        </div>
                    </div>
                    <button
                        @click="removeItem('website_footer_menu', index)"
                        class="text-gray-400 hover:text-red-600 mt-6"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                            />
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        <!-- Banners -->
        <div v-if="tab === 'banners'" class="pt-6 border-t border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h3 class="text-lg leading-6 font-semibold text-gray-900 dark:text-white">
                        {{ $t('tenant_site.lists.sliders') }}
                    </h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                        {{ $t('tenant_site.lists.sliders_desc') }}
                    </p>
                </div>
                <button
                    @click="
                        addItem('landing_banners_list', {
                            title_ar: '',
                            title_en: '',
                            image: '',
                            link: '',
                        })
                    "
                    class="inline-flex items-center px-3 py-2 border border-gray-300 dark:border-gray-600 shadow-sm text-sm font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
                >
                    {{ $t('tenant_site.lists.add_slider') }}
                </button>
            </div>
            <div class="space-y-6">
                <div
                    v-for="(banner, index) in form.settings.landing_banners_list"
                    :key="index"
                    class="p-5 border border-gray-200 dark:border-gray-700 rounded-lg bg-gray-50/30 dark:bg-gray-800/30 flex flex-col sm:flex-row gap-6 relative group"
                >
                    <button
                        @click="removeItem('landing_banners_list', index)"
                        class="absolute top-3 left-3 text-gray-400 hover:text-red-600"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M6 18L18 6M6 6l12 12"
                            />
                        </svg>
                    </button>
                    <div class="w-full sm:w-48 flex-shrink-0">
                        <AppFileUpload
                            variant="image"
                            compact-preview
                            :existing-url="typeof banner.image === 'string' ? banner.image : null"
                            :loading="uploadingIndex === index"
                            accept="image/png,image/jpeg,image/webp,image/svg+xml"
                            :max-size-mb="4"
                            :upload-button-text="$t('tenant_site.lists.change_image')"
                            @select="(file) => onBannerFileSelect(file, banner, index)"
                            @invalid="toastError"
                            @remove-existing="banner.image = ''"
                        />
                        <input
                            v-model="banner.image"
                            type="text"
                            :placeholder="$t('system.website.image_url_placeholder')"
                            class="input-enterprise text-xs py-1 font-mono mt-2"
                            dir="ltr"
                        />
                    </div>
                    <div class="flex-1 space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label
                                    class="text-xs text-gray-500 dark:text-gray-400 mb-1 flex justify-between"
                                >
                                    {{ $t('center_settings.address.title') }}
                                    <span class="badge-lang">AR</span>
                                </label>
                                <input v-model="banner.title_ar" class="input-enterprise" />
                            </div>
                            <div>
                                <label
                                    class="text-xs text-gray-500 dark:text-gray-400 mb-1 flex justify-between"
                                    dir="ltr"
                                >
                                    {{ $t('system.website.title_en') }}
                                    <span class="badge-lang">EN</span>
                                </label>
                                <input
                                    v-model="banner.title_en"
                                    class="input-enterprise text-left"
                                    dir="ltr"
                                />
                            </div>
                            <div class="md:col-span-2">
                                <label class="text-xs text-gray-500 dark:text-gray-400 mb-1 block">
                                    {{ $t('tenant_site.lists.target_url') }}
                                </label>
                                <input
                                    v-model="banner.link"
                                    class="input-enterprise text-left font-mono"
                                    dir="ltr"
                                    placeholder="https://"
                                />
                            </div>
                        </div>
                    </div>
                </div>
                <div
                    v-if="!form.settings.landing_banners_list.length"
                    class="text-center py-10 border-2 border-dashed border-gray-200 dark:border-gray-700 rounded-lg text-gray-500 dark:text-gray-400"
                >
                    {{ $t('tenant_site.lists.no_sliders') }}
                </div>
            </div>
        </div>
    </div>
</template>
