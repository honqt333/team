<script setup>
import { computed } from 'vue';
import AppInput from '@/Components/App/AppInput.vue';
import AppTextarea from '@/Components/App/AppTextarea.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';

defineProps({ form: Object, addItem: Function, removeItem: Function });

const platformOptions = computed(() => [
    { value: 'facebook', label: 'Facebook' },
    { value: 'twitter', label: 'X (Twitter)' },
    { value: 'instagram', label: 'Instagram' },
    { value: 'linkedin', label: 'LinkedIn' },
    { value: 'youtube', label: 'YouTube' },
    { value: 'tiktok', label: 'TikTok' },
    { value: 'snapchat', label: 'Snapchat' },
    { value: 'whatsapp', label: 'WhatsApp' },
    { value: 'telegram', label: 'Telegram' },
    { value: 'custom', label: 'Custom' },
]);
</script>

<template>
    <div class="space-y-10">
        <!-- SEO -->
        <div>
            <h3
                class="text-lg leading-6 font-semibold text-gray-900 dark:text-white border-b border-gray-200 dark:border-gray-700 pb-4 mb-6"
            >
                {{ $t('tenant_site.seo.seo_title') }}
            </h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                <div>
                    <label
                        class="text-sm font-medium text-gray-700 dark:text-gray-300 flex justify-between mb-1"
                    >
                        {{ $t('tenant_site.seo.meta_description') }}
                        <span class="badge-lang">AR</span>
                    </label>
                    <AppTextarea
                        v-model="form.settings.website_description_ar"
                        :rows="3"
                        placeholder="Meta Description"
                    />
                </div>
                <div>
                    <label
                        class="text-sm font-medium text-gray-700 dark:text-gray-300 flex justify-between mb-1"
                        dir="ltr"
                    >
                        Meta Description
                        <span class="badge-lang">EN</span>
                    </label>
                    <AppTextarea
                        v-model="form.settings.website_description_en"
                        :rows="3"
                        class="text-left"
                        dir="ltr"
                    />
                </div>
                <div class="sm:col-span-2">
                    <AppInput
                        v-model="form.settings.website_keywords"
                        type="text"
                        :label="$t('tenant_site.seo.keywords')"
                        :placeholder="$t('system.website.keyword_another_keyword_keywords')"
                    />
                </div>
            </div>
        </div>

        <!-- Contact Info -->
        <div class="pt-8 border-t border-gray-200 dark:border-gray-700">
            <h3 class="text-lg leading-6 font-semibold text-gray-900 dark:text-white mb-6">
                {{ $t('tenant_site.seo.contact_details') }}
            </h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                <AppInput
                    v-model="form.settings.website_contact_email"
                    type="email"
                    :label="$t('center_settings.contact.email')"
                    class="text-left font-mono"
                    dir="ltr"
                />
                <AppInput
                    v-model="form.settings.website_contact_phone"
                    type="text"
                    :label="$t('center_settings.contact.phone')"
                    class="text-left font-mono"
                    dir="ltr"
                />
                <AppInput
                    v-model="form.settings.website_whatsapp_number"
                    type="text"
                    :label="$t('tenant_site.seo.whatsapp_number')"
                    class="text-left font-mono"
                    dir="ltr"
                    placeholder="+966"
                />
                <div class="sm:col-span-2 lg:col-span-3 grid grid-cols-1 sm:grid-cols-2 gap-6">
                    <div>
                        <label
                            class="text-sm font-medium text-gray-700 dark:text-gray-300 flex justify-between mb-1"
                        >
                            {{ $t('tenant_site.seo.headquarters_address') }}
                            <span class="badge-lang">AR</span>
                        </label>
                        <AppInput v-model="form.settings.website_address_ar" type="text" />
                    </div>
                    <div>
                        <label
                            class="text-sm font-medium text-gray-700 dark:text-gray-300 flex justify-between mb-1"
                            dir="ltr"
                        >
                            Address
                            <span class="badge-lang">EN</span>
                        </label>
                        <AppInput
                            v-model="form.settings.website_address_en"
                            type="text"
                            class="text-left"
                            dir="ltr"
                        />
                    </div>
                </div>
            </div>
        </div>

        <!-- Social Links -->
        <div class="pt-8 border-t border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-lg leading-6 font-semibold text-gray-900 dark:text-white">
                    {{ $t('tenant_site.seo.social_networks') }}
                </h3>
                <button
                    @click="addItem('website_social_links', { platform: 'facebook', url: '' })"
                    class="inline-flex items-center px-3 py-2 border border-gray-300 dark:border-gray-600 shadow-sm text-sm font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors text-xs py-1.5"
                >
                    {{ $t('tenant_site.seo.add_network') }}
                </button>
            </div>
            <div class="space-y-4">
                <div
                    v-for="(link, index) in form.settings.website_social_links"
                    :key="index"
                    class="p-4 border border-gray-200 dark:border-gray-700 rounded-xl bg-gray-50/30 dark:bg-gray-800/30 group"
                >
                    <div class="grid grid-cols-1 sm:grid-cols-12 gap-4 items-end">
                        <div class="sm:col-span-3">
                            <label
                                class="text-[10px] uppercase font-bold text-gray-500 dark:text-gray-400 mb-1 block"
                            >
                                {{ $t('tenant_site.seo.platform') }}
                            </label>
                            <SearchableSelect
                                v-model="link.platform"
                                :options="platformOptions"
                                option-label="label"
                                option-value="value"
                                compact
                            />
                        </div>
                        <div class="sm:col-span-8">
                            <AppInput
                                v-model="link.url"
                                type="url"
                                :label="$t('tenant_site.seo.full_url')"
                                class="text-left font-mono"
                                dir="ltr"
                                placeholder="https://..."
                            />
                        </div>
                        <div class="sm:col-span-1 flex justify-end pb-1">
                            <button
                                @click="removeItem('website_social_links', index)"
                                class="p-2 text-gray-400 hover:text-red-600 transition-colors"
                            >
                                <svg
                                    class="w-5 h-5"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
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
                <div
                    v-if="
                        !form.settings.website_social_links ||
                        !form.settings.website_social_links.length
                    "
                    class="text-center py-10 border-2 border-dashed border-gray-200 dark:border-gray-700 rounded-xl text-gray-500 dark:text-gray-400"
                >
                    {{ $t('tenant_site.seo.no_social_links') }}
                </div>
            </div>
        </div>
    </div>
</template>
