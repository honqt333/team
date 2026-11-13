<script setup>
import AppToggle from '@/Components/Common/AppToggle.vue';
import AppInput from '@/Components/App/AppInput.vue';
import AppTextarea from '@/Components/App/AppTextarea.vue';

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
                    <AppInput v-model="form.settings.website_maintenance_title_ar" type="text" />
                </div>
                <div>
                    <label
                        class="text-sm font-medium text-gray-700 dark:text-gray-300 flex justify-between mb-1"
                        dir="ltr"
                    >
                        {{ $t('system.website.maintenance_title_en') }}
                        <span class="badge-lang">EN</span>
                    </label>
                    <AppInput
                        v-model="form.settings.website_maintenance_title_en"
                        type="text"
                        class="text-left"
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
                    <AppTextarea v-model="form.settings.website_maintenance_message_ar" :rows="3" />
                </div>
                <div class="sm:col-span-2">
                    <label
                        class="text-sm font-medium text-gray-700 dark:text-gray-300 flex justify-between mb-1"
                        dir="ltr"
                    >
                        {{ $t('system.website.maintenance_message_en') }}
                        <span class="badge-lang">EN</span>
                    </label>
                    <AppTextarea
                        v-model="form.settings.website_maintenance_message_en"
                        :rows="3"
                        class="text-left"
                        dir="ltr"
                    />
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
                    <AppTextarea v-model="form.settings.website_footer_about_ar" :rows="3" />
                </div>
                <div>
                    <label
                        class="text-sm font-medium text-gray-700 dark:text-gray-300 flex justify-between mb-1"
                        dir="ltr"
                    >
                        {{ $t('system.website.footer_about_en') }}
                        <span class="badge-lang">EN</span>
                    </label>
                    <AppTextarea
                        v-model="form.settings.website_footer_about_en"
                        :rows="3"
                        class="text-left"
                        dir="ltr"
                    />
                </div>
                <div>
                    <label
                        class="text-sm font-medium text-gray-700 dark:text-gray-300 flex justify-between mb-1"
                    >
                        {{ $t('tenant_site.advanced.copyright') }}
                        <span class="badge-lang">AR</span>
                    </label>
                    <AppInput v-model="form.settings.website_copyright_text_ar" type="text" />
                </div>
                <div>
                    <label
                        class="text-sm font-medium text-gray-700 dark:text-gray-300 flex justify-between mb-1"
                        dir="ltr"
                    >
                        {{ $t('system.website.copyright_text_en') }}
                        <span class="badge-lang">EN</span>
                    </label>
                    <AppInput
                        v-model="form.settings.website_copyright_text_en"
                        type="text"
                        class="text-left"
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
                        <AppInput
                            v-model="form.settings.website_announcement_text_ar"
                            type="text"
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
                        <AppInput
                            v-model="form.settings.website_announcement_text_en"
                            type="text"
                            class="text-left"
                            dir="ltr"
                            placeholder="Example: 20% Discount!"
                        />
                    </div>
                </div>
                <AppInput
                    v-model="form.settings.website_announcement_url"
                    type="url"
                    :label="$t('tenant_site.advanced.ad_link')"
                    class="text-left"
                    dir="ltr"
                    placeholder="https://..."
                />
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                    <div>
                        <label
                            class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-1 block"
                        >
                            {{ $t('tenant_site.advanced.background_color') }}
                        </label>
                        <div class="flex gap-2 items-center">
                            <input
                                type="color"
                                v-model="form.settings.website_announcement_bg_color"
                                class="h-10 w-20 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800"
                            />
                            <div class="flex-1">
                                <AppInput
                                    v-model="form.settings.website_announcement_bg_color"
                                    type="text"
                                    class="font-mono"
                                    dir="ltr"
                                />
                            </div>
                        </div>
                    </div>
                    <div>
                        <label
                            class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-1 block"
                        >
                            {{ $t('tenant_site.advanced.text_color') }}
                        </label>
                        <div class="flex gap-2 items-center">
                            <input
                                type="color"
                                v-model="form.settings.website_announcement_text_color"
                                class="h-10 w-20 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800"
                            />
                            <div class="flex-1">
                                <AppInput
                                    v-model="form.settings.website_announcement_text_color"
                                    type="text"
                                    class="font-mono"
                                    dir="ltr"
                                />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
