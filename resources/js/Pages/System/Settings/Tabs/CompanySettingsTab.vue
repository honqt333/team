<template>
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Left column: form -->
        <form
            @submit.prevent="submit"
            class="lg:col-span-2 space-y-6"
            enctype="multipart/form-data"
        >
            <!-- Brand & Logo -->
            <div
                class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm border border-gray-200 dark:border-gray-700"
            >
                <div class="mb-5">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
                        {{ t('system.company_settings.sections.branding.title') }}
                    </h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                        {{ t('system.company_settings.sections.branding.subtitle') }}
                    </p>
                </div>

                <div class="flex flex-col sm:flex-row gap-6 items-start">
                    <!-- Logo preview -->
                    <div
                        class="relative w-40 h-40 border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg flex items-center justify-center overflow-hidden bg-gray-50 dark:bg-gray-900 shrink-0"
                    >
                        <img
                            v-if="logoPreview"
                            :src="logoPreview"
                            class="max-w-full max-h-full object-contain"
                            alt="Company logo"
                        />
                        <div
                            v-else
                            class="flex flex-col items-center justify-center text-center px-2"
                        >
                            <svg
                                class="w-10 h-10 text-gray-300 dark:text-gray-600"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"
                                />
                            </svg>
                            <p class="text-xs text-gray-400 dark:text-gray-500 mt-2">
                                {{ t('system.company_settings.sections.branding.no_logo') }}
                            </p>
                        </div>

                        <div
                            v-if="uploading"
                            class="absolute inset-0 bg-white/80 dark:bg-gray-900/80 flex items-center justify-center"
                        >
                            <svg
                                class="animate-spin w-6 h-6 text-action-primary"
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
                                    d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"
                                ></path>
                            </svg>
                        </div>
                    </div>

                    <div class="flex-1 w-full space-y-3">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            {{ t('system.company_settings.sections.branding.logo_label') }}
                        </label>

                        <input
                            type="file"
                            ref="logoInput"
                            @change="onLogoSelect"
                            accept="image/png,image/jpeg,image/webp,image/svg+xml"
                            class="hidden"
                        />
                        <div class="flex flex-wrap gap-2">
                            <button
                                type="button"
                                @click="logoInput.click()"
                                :disabled="uploading"
                                class="inline-flex items-center gap-2 px-4 py-2 bg-action-primary hover:bg-action-primary-hover disabled:opacity-50 text-white text-sm font-medium rounded-lg transition-colors"
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
                                        d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"
                                    />
                                </svg>
                                {{
                                    logoPreview
                                        ? t('system.company_settings.sections.branding.replace')
                                        : t('system.company_settings.sections.branding.upload')
                                }}
                            </button>
                            <button
                                v-if="logoPreview && !uploading"
                                type="button"
                                @click="confirmRemoveLogo"
                                class="inline-flex items-center gap-2 px-4 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200 text-sm font-medium rounded-lg transition-colors"
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
                                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                                    />
                                </svg>
                                {{ t('system.company_settings.sections.branding.remove') }}
                            </button>
                        </div>
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            {{ t('system.company_settings.sections.branding.logo_hint') }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Stamp / Signature (Phase 6+ — appears on PDF invoices) -->
            <div
                class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm border border-gray-200 dark:border-gray-700"
            >
                <div class="mb-5">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
                        {{ t('system.company_settings.sections.stamp.title') }}
                    </h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                        {{ t('system.company_settings.sections.stamp.subtitle') }}
                    </p>
                </div>

                <div class="flex flex-col sm:flex-row gap-6 items-start">
                    <!-- Stamp preview -->
                    <div
                        class="relative w-40 h-40 border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg flex items-center justify-center overflow-hidden bg-gray-50 dark:bg-gray-900 shrink-0"
                    >
                        <img
                            v-if="stampPreview"
                            :src="stampPreview"
                            class="max-w-full max-h-full object-contain"
                            alt="Company stamp"
                        />
                        <div
                            v-else
                            class="flex flex-col items-center justify-center text-center px-2"
                        >
                            <svg
                                class="w-10 h-10 text-gray-300 dark:text-gray-600"
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
                            <p class="text-xs text-gray-400 dark:text-gray-500 mt-2">
                                {{ t('system.company_settings.sections.stamp.no_stamp') }}
                            </p>
                        </div>

                        <div
                            v-if="uploadingStamp"
                            class="absolute inset-0 bg-white/80 dark:bg-gray-900/80 flex items-center justify-center"
                        >
                            <svg
                                class="animate-spin w-6 h-6 text-action-primary"
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
                                    d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"
                                ></path>
                            </svg>
                        </div>
                    </div>

                    <div class="flex-1 w-full space-y-3">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            {{ t('system.company_settings.sections.stamp.stamp_label') }}
                        </label>

                        <input
                            type="file"
                            ref="stampInput"
                            @change="onStampSelect"
                            accept="image/png,image/jpeg,image/webp,image/svg+xml"
                            class="hidden"
                        />
                        <div class="flex flex-wrap gap-2">
                            <button
                                type="button"
                                @click="stampInput.click()"
                                :disabled="uploadingStamp"
                                class="inline-flex items-center gap-2 px-4 py-2 bg-action-primary hover:bg-action-primary-hover disabled:opacity-50 text-white text-sm font-medium rounded-lg transition-colors"
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
                                        d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"
                                    />
                                </svg>
                                {{
                                    stampPreview
                                        ? t('system.company_settings.sections.branding.replace')
                                        : t('system.company_settings.sections.branding.upload')
                                }}
                            </button>
                            <button
                                v-if="stampPreview && !uploadingStamp"
                                type="button"
                                @click="confirmRemoveStamp"
                                class="inline-flex items-center gap-2 px-4 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200 text-sm font-medium rounded-lg transition-colors"
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
                                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                                    />
                                </svg>
                                {{ t('system.company_settings.sections.branding.remove') }}
                            </button>
                        </div>
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            {{ t('system.company_settings.sections.stamp.stamp_hint') }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Legal & Tax -->
            <div
                class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm border border-gray-200 dark:border-gray-700"
            >
                <div class="mb-5">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
                        {{ t('system.company_settings.sections.legal.title') }}
                    </h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                        {{ t('system.company_settings.sections.legal.subtitle') }}
                    </p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label
                            class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"
                        >
                            {{ t('system.company_settings.sections.legal.legal_name_ar') }}
                        </label>
                        <input
                            type="text"
                            v-model="form.company_legal_name"
                            class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm focus:ring-focus-ring focus:border-action-primary"
                        />
                    </div>
                    <div>
                        <label
                            class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"
                        >
                            {{ t('system.company_settings.sections.legal.legal_name_en') }}
                        </label>
                        <input
                            type="text"
                            v-model="form.company_legal_name_en"
                            dir="ltr"
                            class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm focus:ring-focus-ring focus:border-action-primary"
                        />
                    </div>
                    <div>
                        <label
                            class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"
                        >
                            {{ t('system.company_settings.sections.legal.vat_number') }}
                        </label>
                        <input
                            type="text"
                            v-model="form.company_vat_number"
                            dir="ltr"
                            maxlength="15"
                            class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm font-mono focus:ring-focus-ring focus:border-action-primary"
                        />
                    </div>
                    <div>
                        <label
                            class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"
                        >
                            {{ t('system.company_settings.sections.legal.cr_number') }}
                        </label>
                        <input
                            type="text"
                            v-model="form.company_cr_number"
                            dir="ltr"
                            class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm focus:ring-focus-ring focus:border-action-primary"
                        />
                    </div>
                </div>
            </div>

            <!-- Contact -->
            <div
                class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm border border-gray-200 dark:border-gray-700"
            >
                <div class="mb-5">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
                        {{ t('system.company_settings.sections.contact.title') }}
                    </h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                        {{ t('system.company_settings.sections.contact.subtitle') }}
                    </p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label
                            class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"
                        >
                            {{ t('system.company_settings.sections.contact.phone') }}
                        </label>
                        <input
                            type="tel"
                            v-model="form.company_phone"
                            dir="ltr"
                            class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm focus:ring-focus-ring focus:border-action-primary"
                        />
                    </div>
                    <div>
                        <label
                            class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"
                        >
                            {{ t('system.company_settings.sections.contact.support_email') }}
                        </label>
                        <input
                            type="email"
                            v-model="form.company_email"
                            dir="ltr"
                            class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm focus:ring-focus-ring focus:border-action-primary"
                        />
                    </div>
                    <div class="md:col-span-2">
                        <label
                            class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"
                        >
                            {{ t('system.company_settings.sections.contact.website') }}
                        </label>
                        <input
                            type="url"
                            v-model="form.company_website"
                            dir="ltr"
                            class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm focus:ring-focus-ring focus:border-action-primary"
                        />
                    </div>
                </div>
            </div>

            <!-- Subscription VAT -->
            <div
                class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm border border-gray-200 dark:border-gray-700"
            >
                <div class="mb-5">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
                        {{ t('system.company_settings.sections.vat.title') }}
                    </h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                        {{ t('system.company_settings.sections.vat.subtitle') }}
                    </p>
                </div>

                <label class="flex items-center gap-2 cursor-pointer mb-4">
                    <AppToggle v-model="form.subscription_vat_enabled" />
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                        {{ t('system.company_settings.sections.vat.enable') }}
                    </span>
                </label>

                <div
                    v-if="form.subscription_vat_enabled"
                    class="grid grid-cols-1 md:grid-cols-2 gap-4 pl-7"
                >
                    <div>
                        <label
                            class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"
                        >
                            {{ t('system.company_settings.sections.vat.rate') }}
                        </label>
                        <input
                            type="number"
                            v-model.number="form.subscription_vat_rate"
                            min="0"
                            max="100"
                            step="0.01"
                            dir="ltr"
                            class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm focus:ring-focus-ring focus:border-action-primary"
                        />
                    </div>
                    <div>
                        <label
                            class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"
                        >
                            {{ t('system.company_settings.sections.vat.method') }}
                        </label>
                        <SearchableSelect
                            v-model="form.subscription_vat_inclusive"
                            :options="vatMethodOptions"
                            option-label="label"
                            option-value="value"
                        />
                    </div>
                    <p class="md:col-span-2 text-xs text-gray-500 dark:text-gray-400">
                        {{ t('system.company_settings.sections.vat.hint_enabled') }}
                    </p>
                </div>
            </div>

            <!-- Save bar -->
            <div
                class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm border border-gray-200 dark:border-gray-700 flex items-center justify-between"
            >
                <p
                    v-if="form.recentlySuccessful"
                    class="text-sm text-emerald-600 dark:text-emerald-400 flex items-center gap-1.5"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M5 13l4 4L19 7"
                        />
                    </svg>
                    {{ t('system.company_settings.actions.saved') }}
                </p>
                <span v-else></span>
                <button
                    type="submit"
                    :disabled="form.processing"
                    class="px-6 py-2.5 bg-action-primary hover:bg-action-primary-hover disabled:opacity-50 text-white rounded-lg font-medium transition-colors inline-flex items-center gap-2"
                >
                    <svg
                        v-if="form.processing"
                        class="animate-spin w-4 h-4"
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
                            d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"
                        ></path>
                    </svg>
                    {{
                        form.processing
                            ? t('system.company_settings.actions.saving')
                            : t('system.company_settings.actions.save')
                    }}
                </button>
            </div>
        </form>

        <!-- Right column: live invoice preview -->
        <aside class="lg:col-span-1">
            <div
                class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 sticky top-6"
            >
                <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                    <h2
                        class="text-sm font-semibold text-gray-900 dark:text-white uppercase tracking-wider"
                    >
                        {{ t('system.company_settings.sections.preview.title') }}
                    </h2>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                        {{ t('system.company_settings.sections.preview.subtitle') }}
                    </p>
                </div>

                <div class="p-6">
                    <div
                        class="border border-gray-200 dark:border-gray-700 rounded-lg p-5 bg-gray-50 dark:bg-gray-900/50"
                    >
                        <!-- Issuer block -->
                        <div
                            class="flex items-start gap-3 pb-4 border-b border-gray-200 dark:border-gray-700"
                        >
                            <div
                                class="w-14 h-14 rounded-md bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 flex items-center justify-center overflow-hidden shrink-0"
                            >
                                <img
                                    v-if="logoPreview"
                                    :src="logoPreview"
                                    class="max-w-full max-h-full object-contain"
                                    alt="Logo"
                                />
                                <span v-else class="text-xs font-bold text-gray-400">
                                    {{ (form.company_legal_name || 'A')[0] }}
                                </span>
                            </div>
                            <div class="min-w-0 flex-1">
                                <p
                                    class="text-sm font-semibold text-gray-900 dark:text-white truncate"
                                >
                                    {{ form.company_legal_name || '—' }}
                                </p>
                                <p
                                    v-if="form.company_legal_name_en"
                                    class="text-xs text-gray-500 dark:text-gray-400 truncate"
                                    dir="ltr"
                                >
                                    {{ form.company_legal_name_en }}
                                </p>
                                <div
                                    v-if="form.company_vat_number || form.company_cr_number"
                                    class="mt-1.5 text-[10px] text-gray-500 dark:text-gray-400 space-y-0.5 font-mono"
                                    dir="ltr"
                                >
                                    <p v-if="form.company_vat_number">
                                        VAT:
                                        {{ form.company_vat_number }}
                                    </p>
                                    <p v-if="form.company_cr_number">
                                        CR:
                                        {{ form.company_cr_number }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Sample line items with live VAT -->
                        <div class="pt-4 space-y-1.5 text-[11px] text-gray-600 dark:text-gray-300">
                            <div class="flex items-center justify-between">
                                <span class="truncate">Subscription Plan (sample)</span>
                                <span class="font-mono" dir="ltr">100.00</span>
                            </div>
                            <template v-if="form.subscription_vat_enabled">
                                <div class="flex items-center justify-between text-gray-400">
                                    <span>
                                        {{ t('system.company_settings.sections.vat.preview_net') }}
                                    </span>
                                    <span class="font-mono" dir="ltr">
                                        {{ vatPreview.net }}
                                    </span>
                                </div>
                                <div class="flex items-center justify-between text-gray-400">
                                    <span>
                                        {{ t('system.company_settings.sections.vat.preview_vat') }}
                                        ({{ form.subscription_vat_rate }}%)
                                    </span>
                                    <span class="font-mono" dir="ltr">
                                        {{ vatPreview.vat }}
                                    </span>
                                </div>
                                <div
                                    class="flex items-center justify-between font-semibold text-gray-700 dark:text-gray-200 pt-1.5 border-t border-gray-200 dark:border-gray-700"
                                >
                                    <span>
                                        {{
                                            t('system.company_settings.sections.vat.preview_total')
                                        }}
                                    </span>
                                    <span class="font-mono" dir="ltr">
                                        {{ vatPreview.total }}
                                    </span>
                                </div>
                            </template>
                        </div>

                        <!-- Contact block -->
                        <div
                            class="pt-4 mt-4 border-t border-gray-200 dark:border-gray-700 space-y-1 text-[11px] text-gray-500 dark:text-gray-400"
                        >
                            <p
                                v-if="form.company_phone"
                                class="flex items-center gap-1.5"
                                dir="ltr"
                            >
                                <span>📞</span>
                                <span>{{ form.company_phone }}</span>
                            </p>
                            <p
                                v-if="form.company_email"
                                class="flex items-center gap-1.5"
                                dir="ltr"
                            >
                                <span>✉️</span>
                                <span>{{ form.company_email }}</span>
                            </p>
                            <p
                                v-if="form.company_website"
                                class="flex items-center gap-1.5"
                                dir="ltr"
                            >
                                <span>🌐</span>
                                <span>{{ form.company_website }}</span>
                            </p>
                            <p
                                v-if="
                                    !form.company_phone &&
                                    !form.company_email &&
                                    !form.company_website
                                "
                                class="italic text-gray-400"
                            >
                                —
                            </p>
                        </div>
                    </div>

                    <p class="text-[11px] text-gray-400 dark:text-gray-500 mt-3 text-center">
                        {{ t('system.company_settings.sections.preview.from_label') }}
                    </p>
                </div>
            </div>
        </aside>
    </div>
</template>

<script setup>
import AppToggle from '@/Components/Common/AppToggle.vue';
import { useI18n } from 'vue-i18n';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import { useCompanySettingsForm } from '@/Composables/useCompanySettingsForm';

const { t } = useI18n();

const props = defineProps({
    settings: Object,
});

const {
    form,
    vatMethodOptions,
    vatPreview,
    logoInput,
    uploading,
    logoPreview,
    stampInput,
    uploadingStamp,
    stampPreview,
    onLogoSelect,
    confirmRemoveLogo,
    onStampSelect,
    confirmRemoveStamp,
    submit,
} = useCompanySettingsForm(props);
</script>
