<template>
    <div class="space-y-6">
        <!-- Logo + Company Summary Row -->
        <div
            class="flex flex-col md:flex-row items-start gap-6 p-5 rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50"
        >
            <!-- Logo Section -->
            <div class="flex-shrink-0 w-full md:w-80">
                <AppFileUpload
                    variant="image"
                    :label="$t('company_profile.logo.title')"
                    :hint="$t('company_profile.logo.hint')"
                    :existing-url="logoPreview || profile?.logo_url || ''"
                    :loading="uploadingLogo"
                    :upload-button-text="$t('company_profile.logo.upload')"
                    :reset-button-text="$t('company_profile.logo.remove')"
                    accept="image/png,image/jpeg,image/webp"
                    :max-size-mb="2"
                    @select="(file) => $emit('upload-logo', { target: { files: [file] } })"
                    @remove-existing="$emit('delete-logo')"
                />
            </div>

            <!-- Company Summary -->
            <div class="flex-1 w-full md:w-auto">
                <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-4">
                    {{ profile?.trade_name || profile?.legal_name || $t('nav.brand') }}
                </h3>

                <div class="space-y-3 text-sm">
                    <div class="flex items-center gap-3 text-gray-600 dark:text-gray-400">
                        <svg
                            class="w-5 h-5 flex-shrink-0 text-gray-400"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14"
                            />
                        </svg>
                        <span dir="ltr" class="font-mono">#{{ tenantId }}</span>
                    </div>

                    <div
                        v-if="profile?.cr_number"
                        class="flex items-center gap-3 text-gray-600 dark:text-gray-400"
                    >
                        <svg
                            class="w-5 h-5 flex-shrink-0 text-gray-400"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                            />
                        </svg>
                        <span dir="ltr">{{ profile.cr_number }}</span>
                    </div>

                    <div
                        v-if="profile?.owner_name"
                        class="flex items-center gap-3 text-gray-600 dark:text-gray-400"
                    >
                        <svg
                            class="w-5 h-5 flex-shrink-0 text-gray-400"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"
                            />
                        </svg>
                        <span>{{ profile.owner_name }}</span>
                    </div>

                    <div
                        v-if="contact?.phone"
                        class="flex items-center gap-3 text-gray-600 dark:text-gray-400"
                    >
                        <svg
                            class="w-5 h-5 flex-shrink-0 text-gray-400"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"
                            />
                        </svg>
                        <span dir="ltr">{{ contact.phone }}</span>
                    </div>

                    <div
                        v-if="contact?.email"
                        class="flex items-center gap-3 text-gray-600 dark:text-gray-400"
                    >
                        <svg
                            class="w-5 h-5 flex-shrink-0 text-gray-400"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207"
                            />
                        </svg>
                        <span dir="ltr">{{ contact.email }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Entity Profile Fields -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    {{ $t('company_profile.profile.legal_name') }}
                    <span class="text-red-500">*</span>
                </label>
                <input
                    v-model="form.profile.legal_name"
                    name="legal_name"
                    type="text"
                    :class="[
                        'w-full px-4 py-2.5 rounded-lg border bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-focus-ring focus:border-transparent',
                        fieldErrors.legal_name
                            ? 'border-red-500'
                            : 'border-gray-300 dark:border-gray-600',
                    ]"
                />
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    {{ $t('company_profile.profile.legal_name_en') }}
                    <span class="text-red-500">*</span>
                </label>
                <input
                    v-model="form.profile.legal_name_en"
                    name="legal_name_en"
                    type="text"
                    dir="ltr"
                    :class="[
                        'w-full px-4 py-2.5 rounded-lg border bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-focus-ring focus:border-transparent',
                        fieldErrors.legal_name_en
                            ? 'border-red-500'
                            : 'border-gray-300 dark:border-gray-600',
                    ]"
                />
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    {{ $t('company_profile.profile.trade_name') }}
                    <span class="text-red-500">*</span>
                </label>
                <input
                    v-model="form.profile.trade_name"
                    name="trade_name"
                    type="text"
                    :class="[
                        'w-full px-4 py-2.5 rounded-lg border bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-focus-ring focus:border-transparent',
                        fieldErrors.trade_name
                            ? 'border-red-500'
                            : 'border-gray-300 dark:border-gray-600',
                    ]"
                />
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    {{ $t('company_profile.profile.owner_name') }}
                    <span class="text-red-500">*</span>
                </label>
                <input
                    v-model="form.profile.owner_name"
                    name="owner_name"
                    type="text"
                    :class="[
                        'w-full px-4 py-2.5 rounded-lg border bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-focus-ring focus:border-transparent',
                        fieldErrors.owner_name
                            ? 'border-red-500'
                            : 'border-gray-300 dark:border-gray-600',
                    ]"
                />
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    {{ $t('company_profile.profile.cr_number') }}
                </label>
                <input
                    v-model="form.profile.cr_number"
                    name="cr_number"
                    type="text"
                    maxlength="20"
                    class="w-full px-4 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-focus-ring focus:border-transparent"
                />
            </div>
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    {{ $t('company_profile.profile.iban') }}
                </label>
                <input
                    v-model="form.profile.iban"
                    name="iban"
                    type="text"
                    maxlength="34"
                    dir="ltr"
                    placeholder="SA..."
                    class="w-full px-4 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-focus-ring focus:border-transparent font-mono"
                />
            </div>
        </div>

        <!-- System Region & Currency -->
        <div
            class="grid grid-cols-1 md:grid-cols-2 gap-6 pt-4 border-t border-gray-200 dark:border-gray-700"
        >
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    {{ $t('company_profile.profile.country') }}
                </label>
                <SearchableSelect
                    v-model="form.profile.country"
                    :options="countryOptions"
                    option-label="label"
                    option-value="value"
                    :placeholder="$t('company_profile.profile.country')"
                />
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    {{ $t('company_profile.profile.currency') }}
                </label>
                <SearchableSelect
                    v-model="form.profile.currency"
                    :options="currencyOptions"
                    option-label="label"
                    option-value="value"
                    :placeholder="$t('company_profile.profile.currency')"
                />
            </div>
        </div>

        <!-- Sticky Action Bar for Profile Section -->
        <div
            class="sticky bottom-0 z-20 mt-8 -mx-5 -mb-6 p-4 bg-white/95 dark:bg-gray-800/95 backdrop-blur-md border-t border-gray-200 dark:border-gray-700 flex items-center justify-between gap-4 flex-wrap rounded-b-xl shadow-lg"
        >
            <div class="flex items-center gap-3">
                <span
                    v-if="Object.keys(fieldErrors).length > 0"
                    class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg text-xs font-bold bg-red-50 dark:bg-red-900/30 text-red-600 dark:text-red-400 border border-red-200 dark:border-red-800/50"
                >
                    <svg
                        class="w-4 h-4 text-red-500 animate-pulse"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"
                        />
                    </svg>
                    {{ $t('common.form_has_errors', { count: Object.keys(fieldErrors).length }) }}
                </span>
                <span v-else class="text-xs text-gray-400 dark:text-gray-500">
                    {{ $t('common.unsaved_changes') }}
                </span>
            </div>

            <div class="flex items-center gap-3">
                <button
                    @click="$emit('save-section', 'profile')"
                    :disabled="saving"
                    class="inline-flex items-center gap-2 px-6 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-sm rounded-xl transition-all shadow-md shadow-emerald-500/20 active:scale-95 disabled:opacity-50"
                >
                    <svg
                        v-if="saving"
                        class="animate-spin -ml-1 mr-2 h-4 w-4 text-white"
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
                        ></circle>
                        <path
                            class="opacity-75"
                            fill="currentColor"
                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                        ></path>
                    </svg>
                    {{ saving ? $t('common.loading') : $t('common.save') }}
                </button>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import AppFileUpload from '@/Components/Common/AppFileUpload.vue';

const { t } = useI18n();

const countryOptions = computed(() => [
    { value: 'SA', label: `🇸🇦 ${t('company_profile.profile.saudi_arabia')} (SA)` },
    { value: 'AE', label: t('company_profile.country.ae') },
    { value: 'KW', label: t('company_profile.country.kw') },
    { value: 'BH', label: t('company_profile.country.bh') },
    { value: 'OM', label: t('company_profile.country.om') },
    { value: 'QA', label: t('company_profile.country.qa') },
    { value: 'EG', label: t('company_profile.country.eg') },
    { value: 'JO', label: t('company_profile.country.jo') },
]);

const currencyOptions = computed(() => [
    { value: 'SAR', label: t('company_profile.currency.sar') },
    { value: 'AED', label: t('company_profile.currency.aed') },
    { value: 'KWD', label: t('company_profile.currency.kwd') },
    { value: 'BHD', label: t('company_profile.currency.bhd') },
    { value: 'OMR', label: t('company_profile.currency.omr') },
    { value: 'QAR', label: t('company_profile.currency.qar') },
    { value: 'EGP', label: t('company_profile.currency.egp') },
    { value: 'JOD', label: t('company_profile.currency.jod') },
    { value: 'USD', label: 'USD - US Dollar' },
    { value: 'EUR', label: 'EUR - Euro' },
]);

const props = defineProps({
    profile: Object,
    contact: Object,
    address: Object,
    tenantId: [String, Number],
    form: Object,
    fieldErrors: { type: Object, default: () => ({}) },
    logoPreview: String,
    uploadingLogo: Boolean,
    saving: Boolean,
});

defineEmits(['upload-logo', 'delete-logo', 'save-section']);
</script>
