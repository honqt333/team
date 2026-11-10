<script setup>
import AppToggle from '@/Components/Common/AppToggle.vue';

defineProps({ form: Object, tab: String });
</script>

<template>
    <div class="space-y-10">
        <!-- Maintenance Mode -->
        <div v-if="tab === 'maintenance'">
            <div
                class="flex items-start justify-between border-b border-gray-200 dark:border-gray-700 pb-6 mb-8"
            >
                <div>
                    <h3 class="text-lg leading-6 font-semibold text-gray-900 dark:text-white">
                        {{ $t('tenant_site.advanced.system_status') }}
                    </h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        {{ $t('tenant_site.advanced.maintenance_desc') }}
                    </p>
                </div>
                <div class="flex items-center">
                    <AppToggle
                        v-model="form.settings.website_maintenance_mode"
                        true-value="1"
                        false-value="0"
                        color="danger"
                    />
                </div>
            </div>
            <div
                :class="{
                    'opacity-50 pointer-events-none': form.settings.website_maintenance_mode != '1',
                }"
                class="grid grid-cols-1 sm:grid-cols-2 gap-6 transition-opacity"
            >
                <div>
                    <label
                        class="text-sm font-medium text-gray-700 dark:text-gray-300 flex justify-between mb-1"
                    >
                        {{ $t('tenant_site.advanced.maintenance_title') }}
                        <span class="badge-lang">AR</span>
                    </label>
                    <input
                        v-model="form.settings.website_maintenance_title_ar"
                        class="input-enterprise"
                    />
                </div>
                <div>
                    <label
                        class="text-sm font-medium text-gray-700 dark:text-gray-300 flex justify-between mb-1"
                        dir="ltr"
                    >
                        {{ $t('system.website.maintenance_title_en') }}
                        <span class="badge-lang">EN</span>
                    </label>
                    <input
                        v-model="form.settings.website_maintenance_title_en"
                        class="input-enterprise text-left"
                        dir="ltr"
                    />
                </div>
                <div class="sm:col-span-2">
                    <label
                        class="text-sm font-medium text-gray-700 dark:text-gray-300 flex justify-between mb-1"
                    >
                        {{ $t('tenant_site.advanced.explanatory_note') }}
                        <span class="badge-lang">AR</span>
                    </label>
                    <textarea
                        v-model="form.settings.website_maintenance_message_ar"
                        rows="3"
                        class="input-enterprise"
                    ></textarea>
                </div>
                <div class="sm:col-span-2">
                    <label
                        class="text-sm font-medium text-gray-700 dark:text-gray-300 flex justify-between mb-1"
                        dir="ltr"
                    >
                        {{ $t('system.website.maintenance_message_en') }}
                        <span class="badge-lang">EN</span>
                    </label>
                    <textarea
                        v-model="form.settings.website_maintenance_message_en"
                        rows="3"
                        class="input-enterprise text-left"
                        dir="ltr"
                    ></textarea>
                </div>
            </div>
        </div>

        <!-- Footer Info -->
        <div v-if="tab === 'footer'" class="pt-6 border-t border-gray-200 dark:border-gray-700">
            <h3
                class="text-lg leading-6 font-semibold text-gray-900 dark:text-white border-b border-gray-200 dark:border-gray-700 pb-4"
            >
                {{ $t('tenant_site.advanced.footer_info') }}
            </h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 mt-6">
                <div>
                    <label
                        class="text-sm font-medium text-gray-700 dark:text-gray-300 flex justify-between mb-1"
                    >
                        {{ $t('tenant_site.advanced.about') }}
                        <span class="badge-lang">AR</span>
                    </label>
                    <textarea
                        v-model="form.settings.website_footer_about_ar"
                        rows="3"
                        class="input-enterprise"
                    ></textarea>
                </div>
                <div>
                    <label
                        class="text-sm font-medium text-gray-700 dark:text-gray-300 flex justify-between mb-1"
                        dir="ltr"
                    >
                        {{ $t('system.website.footer_about_en') }}
                        <span class="badge-lang">EN</span>
                    </label>
                    <textarea
                        v-model="form.settings.website_footer_about_en"
                        rows="3"
                        class="input-enterprise text-left"
                        dir="ltr"
                    ></textarea>
                </div>
                <div>
                    <label
                        class="text-sm font-medium text-gray-700 dark:text-gray-300 flex justify-between mb-1"
                    >
                        {{ $t('tenant_site.advanced.copyright') }}
                        <span class="badge-lang">AR</span>
                    </label>
                    <input
                        v-model="form.settings.website_copyright_text_ar"
                        class="input-enterprise"
                    />
                </div>
                <div>
                    <label
                        class="text-sm font-medium text-gray-700 dark:text-gray-300 flex justify-between mb-1"
                        dir="ltr"
                    >
                        {{ $t('system.website.copyright_text_en') }}
                        <span class="badge-lang">EN</span>
                    </label>
                    <input
                        v-model="form.settings.website_copyright_text_en"
                        class="input-enterprise text-left"
                        dir="ltr"
                    />
                </div>
            </div>
        </div>

        <!-- Announcement Bar -->
        <div
            v-if="tab === 'announcement'"
            class="pt-6 border-t border-gray-200 dark:border-gray-700"
        >
            <div
                class="flex items-center justify-between border-b border-gray-200 dark:border-gray-700 pb-4"
            >
                <div>
                    <h3 class="text-lg leading-6 font-semibold text-gray-900 dark:text-white">
                        {{ $t('tenant_site.advanced.top_announcement_bar') }}
                    </h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                        {{ $t('tenant_site.advanced.top_announcement_bar_desc') }}
                    </p>
                </div>
                <AppToggle
                    v-model="form.settings.website_announcement_enabled"
                    true-value="1"
                    false-value="0"
                />
            </div>
            <div
                :class="{
                    'opacity-50 pointer-events-none':
                        form.settings.website_announcement_enabled != '1',
                }"
                class="space-y-6 mt-6 transition-opacity"
            >
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                    <div>
                        <label
                            class="text-sm font-medium text-gray-700 dark:text-gray-300 flex justify-between mb-1"
                        >
                            {{ $t('tenant_site.advanced.ad_text') }}
                            <span class="badge-lang">AR</span>
                        </label>
                        <input
                            v-model="form.settings.website_announcement_text_ar"
                            class="input-enterprise"
                            :placeholder="$t('system.website.e.g._20_off_for_a_limited_time')"
                        />
                    </div>
                    <div>
                        <label
                            class="text-sm font-medium text-gray-700 dark:text-gray-300 flex justify-between mb-1"
                            dir="ltr"
                        >
                            {{ $t('system.website.announcement_text_en') }}
                            <span class="badge-lang">EN</span>
                        </label>
                        <input
                            v-model="form.settings.website_announcement_text_en"
                            class="input-enterprise text-left"
                            dir="ltr"
                            placeholder="Example: 20% Discount!"
                        />
                    </div>
                </div>
                <div>
                    <label class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-1 block">
                        {{ $t('tenant_site.advanced.ad_link') }}
                    </label>
                    <input
                        v-model="form.settings.website_announcement_url"
                        class="input-enterprise text-left"
                        dir="ltr"
                        placeholder="https://..."
                    />
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                    <div>
                        <label
                            class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-1 block"
                        >
                            {{ $t('tenant_site.advanced.background_color') }}
                        </label>
                        <div class="flex gap-2">
                            <input
                                type="color"
                                v-model="form.settings.website_announcement_bg_color"
                                class="h-10 w-20 rounded-lg border border-gray-300 dark:border-gray-600 bg-white"
                            />
                            <input
                                v-model="form.settings.website_announcement_bg_color"
                                class="input-enterprise font-mono"
                            />
                        </div>
                    </div>
                    <div>
                        <label
                            class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-1 block"
                        >
                            {{ $t('tenant_site.advanced.text_color') }}
                        </label>
                        <div class="flex gap-2">
                            <input
                                type="color"
                                v-model="form.settings.website_announcement_text_color"
                                class="h-10 w-20 rounded-lg border border-gray-300 dark:border-gray-600 bg-white"
                            />
                            <input
                                v-model="form.settings.website_announcement_text_color"
                                class="input-enterprise font-mono"
                            />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
