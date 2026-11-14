<template>
    <BaseModal :show="show" @close="handleClose" size="2xl" scroll-entire>
        <template #title>
            <div class="flex items-center gap-3">
                <div
                    class="w-10 h-10 rounded-xl bg-action-primary hover:bg-action-primary-hover flex items-center justify-center"
                >
                    <svg
                        class="w-5 h-5 text-white"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"
                        />
                    </svg>
                </div>
                {{ customer ? $t('customers.edit.title') : $t('customers.create.title') }}
            </div>
        </template>

        <form @submit.prevent="submitForm" class="space-y-5">
            <!-- Type Selection -->
            <div data-field="type">
                <SearchableSelect
                    v-model="form.type"
                    :options="typeOptions"
                    option-label="label"
                    option-value="value"
                    :label="$t('customers.form.type')"
                    :placeholder="$t('common.choose')"
                    required
                    class="w-full"
                    :error="form.errors.type"
                />
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <!-- Name -->
                <FormField
                    v-slot="field"
                    :class="['company', 'government'].includes(form.type) ? '' : 'md:col-span-2'"
                    :label="$t('customers.form.name')"
                    :error="form.errors.name"
                    required
                >
                    <AppInput
                        :id="field.id"
                        :aria-describedby="field.describedBy"
                        :invalid="field.hasError"
                        name="name"
                        v-model="form.name"
                        class="w-full"
                        required
                    />
                </FormField>

                <FormField
                    v-slot="field"
                    v-if="['company', 'government'].includes(form.type)"
                    :label="$t('customers.form.contact_name')"
                    :error="form.errors.contact_name"
                    required
                >
                    <AppInput
                        :id="field.id"
                        :aria-describedby="field.describedBy"
                        :invalid="field.hasError"
                        name="contact_name"
                        v-model="form.contact_name"
                        class="w-full"
                        required
                    />
                </FormField>

                <div
                    v-if="form.type === 'company'"
                    class="md:col-span-2 grid grid-cols-1 md:grid-cols-3 gap-4"
                >
                    <FormField
                        v-slot="field"
                        :label="$t('customers.form.tax_number')"
                        :error="form.errors.tax_number"
                    >
                        <AppInput
                            :id="field.id"
                            :aria-describedby="field.describedBy"
                            :invalid="field.hasError"
                            name="tax_number"
                            v-model="form.tax_number"
                            :placeholder="$t('customers.form.tax_placeholder')"
                            class="w-full"
                        />
                    </FormField>

                    <FormField
                        v-slot="field"
                        :label="$t('customers.form.credit_limit')"
                        :error="form.errors.credit_limit"
                    >
                        <AppInput
                            :id="field.id"
                            :aria-describedby="field.describedBy"
                            :invalid="field.hasError"
                            type="number"
                            name="credit_limit"
                            step="0.01"
                            min="0"
                            v-model="form.credit_limit"
                            :placeholder="$t('customers.form.credit_limit_placeholder')"
                            class="w-full"
                        />
                    </FormField>

                    <FormField
                        v-slot="field"
                        :label="$t('customers.form.payment_terms')"
                        :error="form.errors.payment_terms_days"
                    >
                        <div class="relative">
                            <AppInput
                                :id="field.id"
                                :aria-describedby="field.describedBy"
                                :invalid="field.hasError"
                                type="number"
                                name="payment_terms_days"
                                min="0"
                                v-model="form.payment_terms_days"
                                placeholder="0"
                                class="w-full pe-12"
                            />
                            <span
                                class="absolute inset-y-0 end-3 flex items-center text-xs text-text-muted pointer-events-none"
                            >
                                {{ $t('common.days') }}
                            </span>
                        </div>
                    </FormField>
                </div>

                <div>
                    <label
                        class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5"
                    >
                        {{ $t('customers.form.phone') }}
                        <span class="text-red-500">*</span>
                    </label>
                    <div data-field="phone">
                        <vue-tel-input
                            v-model="form.phone"
                            @on-input="(value) => onPhoneInput('phone', value)"
                            @blur="checkPhoneExistence"
                            :inputOptions="{ placeholder: '05xxxxxxxx' }"
                            mode="international"
                            :validCharactersOnly="false"
                            class="w-full text-sm rounded-xl bg-white dark:bg-gray-900 focus-within:ring-2 focus-within:border-transparent transition-all ltr"
                            :class="
                                form.errors.phone
                                    ? 'error-border'
                                    : !form.phone
                                      ? 'required-border'
                                      : 'border-gray-300 dark:border-gray-600'
                            "
                        ></vue-tel-input>
                    </div>
                    <p
                        v-if="form.errors.phone"
                        class="mt-1.5 text-sm text-red-600 dark:text-red-400"
                    >
                        {{ form.errors.phone }}
                    </p>
                </div>

                <!-- WhatsApp -->
                <div>
                    <div class="flex items-center justify-between mb-1.5">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            {{ $t('customers.form.whatsapp') }}
                        </label>
                        <button
                            v-if="
                                form.phone &&
                                normalizePhone(form.phone) !== normalizePhone(form.whatsapp)
                            "
                            type="button"
                            @click="form.whatsapp = form.phone"
                            class="text-xs text-action-primary hover:text-selection-text transition-colors"
                        >
                            {{ $t('customers.form.copy_from_phone') }}
                        </button>
                    </div>
                    <div data-field="whatsapp">
                        <vue-tel-input
                            v-model="form.whatsapp"
                            @on-input="(value) => onPhoneInput('whatsapp', value)"
                            :inputOptions="{ placeholder: '05xxxxxxxx' }"
                            mode="international"
                            :validCharactersOnly="false"
                            class="w-full text-sm rounded-xl bg-white dark:bg-gray-900 focus-within:ring-2 focus-within:border-transparent transition-all ltr"
                            :class="
                                form.errors.whatsapp
                                    ? 'error-border'
                                    : 'border-gray-300 dark:border-gray-600'
                            "
                        ></vue-tel-input>
                    </div>
                    <p
                        v-if="form.errors.whatsapp"
                        class="mt-1.5 text-sm text-red-600 dark:text-red-400"
                    >
                        {{ form.errors.whatsapp }}
                    </p>
                </div>

                <FormField
                    v-slot="field"
                    class="md:col-span-2"
                    :label="$t('customers.form.email')"
                    :error="form.errors.email"
                >
                    <AppInput
                        :id="field.id"
                        :aria-describedby="field.describedBy"
                        :invalid="field.hasError"
                        type="email"
                        name="email"
                        v-model="form.email"
                        class="w-full"
                    />
                </FormField>

                <!-- Quick Actions -->
                <div class="md:col-span-2">
                    <div class="grid grid-cols-3 gap-3">
                        <button
                            type="button"
                            class="flex items-center justify-center gap-2 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 px-3 py-2.5 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-800 transition disabled:opacity-40 disabled:cursor-not-allowed"
                            :disabled="!form.phone"
                            @click="quickCall"
                        >
                            <span>📞</span>
                            <span>{{ $t('customers.quick.call') }}</span>
                        </button>

                        <button
                            type="button"
                            class="flex items-center justify-center gap-2 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 px-3 py-2.5 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-800 transition disabled:opacity-40 disabled:cursor-not-allowed"
                            :disabled="!form.whatsapp"
                            @click="quickWhatsApp"
                        >
                            <span>💬</span>
                            <span>{{ $t('customers.quick.whatsapp') }}</span>
                        </button>

                        <button
                            type="button"
                            class="flex items-center justify-center gap-2 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 px-3 py-2.5 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-800 transition disabled:opacity-40 disabled:cursor-not-allowed"
                            :disabled="!form.email"
                            @click="quickEmail"
                        >
                            <span>✉️</span>
                            <span>{{ $t('customers.quick.email') }}</span>
                        </button>
                    </div>
                </div>

                <!-- Notes -->
                <FormField
                    class="md:col-span-2"
                    :label="$t('customers.form.notes')"
                    :error="form.errors.notes"
                >
                    <textarea
                        name="notes"
                        v-model="form.notes"
                        rows="2"
                        class="w-full px-4 py-2.5 border border-border dark:border-gray-600 rounded-xl bg-surface dark:bg-gray-900 text-text-primary dark:text-gray-100 focus:ring-2 focus:ring-focus-ring focus:border-action-primary resize-none transition-all"
                    ></textarea>
                </FormField>
            </div>

            <!-- Address Section -->
            <div class="border-t border-gray-200 dark:border-gray-700 pt-5">
                <div class="flex items-center justify-between mb-4">
                    <h3
                        class="text-sm font-semibold text-gray-900 dark:text-white flex items-center gap-2"
                    >
                        <svg
                            class="w-4 h-4 text-gray-500"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"
                            />
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"
                            />
                        </svg>
                        {{ $t('customers.form.address') }}
                    </h3>
                    <div class="flex items-center gap-2">
                        <button
                            type="button"
                            @click="fetchLocation"
                            :disabled="isLocating"
                            class="flex items-center gap-1.5 px-2.5 py-1.5 text-xs font-medium text-action-primary hover:bg-selection-bg rounded-lg transition-colors disabled:opacity-50"
                            :title="$t('customers.form.locate_me')"
                        >
                            <svg
                                class="w-3.5 h-3.5"
                                :class="{ 'animate-spin': isLocating }"
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
                            {{ isLocating ? $t('common.loading') : $t('customers.form.locate_me') }}
                        </button>
                        <button
                            type="button"
                            @click="clearAddressFields"
                            class="flex items-center gap-1.5 px-2.5 py-1.5 text-xs font-medium text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-colors"
                            :title="$t('customers.form.clear_address')"
                        >
                            <svg
                                class="w-3.5 h-3.5"
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
                            {{ $t('customers.form.clear_address') }}
                        </button>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                    <!-- Map Container -->
                    <div class="order-1 lg:order-2">
                        <div
                            ref="mapContainer"
                            class="w-full h-[260px] rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-100 dark:bg-gray-900 overflow-hidden"
                        >
                            <div
                                v-if="!mapReady"
                                class="h-full flex flex-col items-center justify-center text-center p-4"
                            >
                                <svg
                                    class="w-8 h-8 text-gray-400 mb-2 animate-pulse"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="1.5"
                                        d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l5.447 2.724A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"
                                    />
                                </svg>
                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                    {{ $t('common.loading') }}
                                </p>
                            </div>
                        </div>
                        <p class="mt-2 text-xs text-gray-500 dark:text-gray-400 text-center">
                            {{ $t('customers.map.click_hint') }}
                        </p>
                    </div>

                    <!-- Address Fields -->
                    <div class="order-2 lg:order-1 space-y-3">
                        <!-- Address Line -->
                        <div>
                            <label
                                class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1"
                            >
                                {{ $t('customers.form.address_line') }}
                            </label>
                            <textarea
                                name="address_line"
                                v-model="form.address_line"
                                rows="2"
                                :placeholder="$t('customers.form.address_placeholder')"
                                class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-focus-ring focus:border-transparent resize-none transition-all"
                            ></textarea>
                        </div>

                        <!-- Building + Postal + Zip -->
                        <div class="grid grid-cols-3 gap-2">
                            <div>
                                <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">
                                    {{ $t('customers.form.building_number') }}
                                </label>
                                <input
                                    type="text"
                                    name="building_number"
                                    v-model="form.building_number"
                                    class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-focus-ring focus:border-transparent transition-all"
                                />
                            </div>
                            <div>
                                <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">
                                    {{ $t('customers.form.postal_code') }}
                                </label>
                                <input
                                    type="text"
                                    name="postal_code"
                                    v-model="form.postal_code"
                                    class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-focus-ring focus:border-transparent transition-all"
                                />
                            </div>
                            <div>
                                <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">
                                    {{ $t('customers.form.district') }}
                                </label>
                                <input
                                    type="text"
                                    name="district"
                                    v-model="form.district"
                                    class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-focus-ring focus:border-transparent transition-all"
                                />
                            </div>
                        </div>

                        <!-- City + Region -->
                        <div class="grid grid-cols-2 gap-2">
                            <div>
                                <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">
                                    {{ $t('customers.form.city') }}
                                </label>
                                <input
                                    type="text"
                                    name="city"
                                    v-model="form.city"
                                    class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-focus-ring focus:border-transparent transition-all"
                                />
                            </div>
                            <div>
                                <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">
                                    {{ $t('customers.form.region') }}
                                </label>
                                <input
                                    type="text"
                                    name="region"
                                    v-model="form.region"
                                    class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-focus-ring focus:border-transparent transition-all"
                                />
                            </div>
                        </div>

                        <!-- Country -->
                        <div>
                            <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">
                                {{ $t('customers.form.country') }}
                            </label>
                            <input
                                type="text"
                                name="country"
                                v-model="form.country"
                                :placeholder="$t('customers.form.country_placeholder')"
                                class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-focus-ring focus:border-transparent transition-all"
                            />
                        </div>
                    </div>
                </div>
            </div>
        </form>

        <template #footer>
            <div class="flex items-center justify-between w-full">
                <div>
                    <span
                        v-if="Object.keys(form.errors).length > 0"
                        class="text-xs text-red-500 font-medium inline-flex items-center gap-1.5"
                    >
                        <svg class="w-4 h-4 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path
                                fill-rule="evenodd"
                                d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z"
                                clip-rule="evenodd"
                            />
                        </svg>
                        {{
                            $t('common.form_has_errors', { count: Object.keys(form.errors).length })
                        }}
                    </span>
                </div>
                <div class="flex items-center gap-3">
                    <button
                        type="button"
                        @click="$emit('close')"
                        class="px-5 py-2.5 text-xs font-bold text-text-secondary hover:bg-surface-secondary border border-border rounded-xl transition-colors cursor-pointer"
                    >
                        {{ $t('common.cancel') }}
                    </button>
                    <button
                        type="button"
                        @click="submitForm"
                        :disabled="form.processing"
                        class="inline-flex items-center justify-center gap-2 px-6 py-2.5 text-xs font-bold text-white bg-action-primary hover:bg-action-primary-hover rounded-xl shadow-xs transition-all disabled:opacity-50 cursor-pointer"
                    >
                        <svg
                            v-if="form.processing"
                            class="w-4 h-4 animate-spin -ml-1 mr-2 text-white"
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
                        {{ form.processing ? $t('common.loading') : $t('customers.form.submit') }}
                    </button>
                </div>
            </div>
        </template>
    </BaseModal>

    <!-- Duplicate Customer Alert Modal -->
    <BaseModal :show="showDuplicateModal" @close="showDuplicateModal = false" size="md">
        <template #title>
            <div class="flex items-center gap-3 text-amber-600 dark:text-amber-400">
                <span class="text-2xl">⚠️</span>
                <span>
                    {{ $t('customers.validation.phone_taken_title') }}
                </span>
            </div>
        </template>

        <div class="space-y-4 p-1">
            <p class="text-sm text-gray-600 dark:text-gray-400 leading-relaxed">
                {{ $t('customers.validation.phone_taken_desc') }}
            </p>

            <div
                v-if="duplicateCustomer"
                @click="navigateToDuplicateCustomer"
                class="group relative bg-white dark:bg-gray-800 rounded-2xl border-2 border-dashed border-gray-200 dark:border-gray-700 hover:border-action-primary hover:shadow-xl hover:-translate-y-1 cursor-pointer transition-all duration-300 overflow-hidden p-4"
            >
                <!-- Card Content -->
                <div class="relative z-10 flex items-center gap-4">
                    <div
                        class="w-12 h-12 rounded-xl bg-selection-bg text-action-primary flex items-center justify-center shrink-0"
                    >
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"
                            />
                        </svg>
                    </div>
                    <div class="flex-1 min-w-0">
                        <h4
                            class="text-base font-bold text-gray-900 dark:text-white truncate group-hover:text-action-primary transition-colors"
                        >
                            {{ duplicateCustomer.name }}
                        </h4>
                        <div
                            class="flex flex-wrap gap-x-3 gap-y-1 mt-1 text-xs text-gray-500 dark:text-gray-400"
                        >
                            <span class="font-mono" dir="ltr">
                                📞 {{ duplicateCustomer.phone }}
                            </span>
                            <span v-if="duplicateCustomer.email">
                                ✉️ {{ duplicateCustomer.email }}
                            </span>
                            <span v-if="duplicateCustomer.tax_number">
                                📋 {{ $t('customers.form.tax_number') }}:
                                {{ duplicateCustomer.tax_number }}
                            </span>
                        </div>
                    </div>
                    <div
                        class="text-action-primary opacity-0 group-hover:opacity-100 transition-opacity shrink-0"
                    >
                        <svg
                            class="w-5 h-5 rtl:rotate-180"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M9 5l7 7-7 7"
                            />
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        <template #footer>
            <div class="flex justify-end">
                <button
                    type="button"
                    @click="showDuplicateModal = false"
                    class="px-5 py-2.5 text-xs font-bold text-text-secondary hover:bg-surface-secondary border border-border rounded-xl transition-colors cursor-pointer"
                >
                    {{ $t('common.cancel') }}
                </button>
            </div>
        </template>
    </BaseModal>
</template>

<script setup>
import { ref, computed, watch, onMounted, onUnmounted, nextTick } from 'vue';
import { useForm, router, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import { useI18n } from 'vue-i18n';
import { useConfirm } from '@/Composables/useConfirm';
import { useFormScrollToError } from '@/Composables/useFormScrollToError';
import { useNumberFormat } from '@/Composables/useNumberFormat';
import BaseModal from '@/Components/BaseModal.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import FormField from '@/Components/Common/FormField.vue';
import AppInput from '@/Components/App/AppInput.vue';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';

// Fix Leaflet default marker icon issue
delete L.Icon.Default.prototype._getIconUrl;
L.Icon.Default.mergeOptions({
    iconRetinaUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/images/marker-icon-2x.png',
    iconUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/images/marker-icon.png',
    shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/images/marker-shadow.png',
});

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    customer: {
        type: Object,
        default: null,
    },
});

const emit = defineEmits(['close', 'saved']);
const { t, locale } = useI18n(); // Access t function and current locale
const { scrollToError } = useFormScrollToError();
const { toEnglish } = useNumberFormat();
const page = usePage();

const duplicateCustomer = ref(null);
const showDuplicateModal = ref(false);
const checkingPhone = ref(false);

const mapContainer = ref(null);
const mapReady = ref(false);
const geocodingLoading = ref(false);
const isLocating = ref(false);

let map = null;
let marker = null;

// Default location: use tenant/center location or fallback to Riyadh
const defaultLat = computed(() => {
    return page.props.auth?.center?.lat || page.props.tenant?.lat || 24.7136;
});
const defaultLng = computed(() => {
    return page.props.auth?.center?.lng || page.props.tenant?.lng || 46.6753;
});

const { confirm } = useConfirm();
const isDirty = ref(false);
const initialFormData = ref(null);
const isConfirming = ref(false);

const form = useForm({
    type: null,
    name: '',
    contact_name: '',
    phone: '',
    whatsapp: '',
    email: '',
    notes: '',
    tax_number: '',
    credit_limit: null,
    payment_terms_days: 0,
    address_line: '',
    building_number: '',
    postal_code: '',
    district: '',
    city: '',
    region: '',
    country: '',
    lat: null,
    lng: null,
});

const typeOptions = computed(() => [
    { value: 'individual', label: t('customers.type.individual') },
    { value: 'company', label: t('customers.type.company') },
    { value: 'government', label: t('customers.type.government') },
]);

// Initialize form when customer prop changes (for edit mode)
watch(
    () => props.customer,
    (newCustomer) => {
        if (newCustomer) {
            form.type = newCustomer.type || 'individual';
            form.name = newCustomer.name || '';
            form.contact_name = newCustomer.contact_name || '';
            form.phone = toEnglish(newCustomer.phone || '');
            form.whatsapp = toEnglish(newCustomer.whatsapp || '');
            form.email = newCustomer.email || '';
            form.notes = newCustomer.notes || '';
            form.tax_number = toEnglish(newCustomer.tax_number || '');
            form.credit_limit =
                newCustomer.credit_limit !== undefined && newCustomer.credit_limit !== null
                    ? toEnglish(newCustomer.credit_limit)
                    : null;
            form.payment_terms_days =
                newCustomer.payment_terms_days !== undefined &&
                newCustomer.payment_terms_days !== null
                    ? Number(toEnglish(newCustomer.payment_terms_days)) || 0
                    : 0;
            form.address_line = newCustomer.address_line || '';
            form.building_number = toEnglish(newCustomer.building_number || '');
            form.postal_code = toEnglish(newCustomer.postal_code || '');
            form.district = newCustomer.district || '';
            form.city = newCustomer.city || '';
            form.region = newCustomer.region || '';
            form.country = newCustomer.country || '';
            form.lat = newCustomer.lat || null;
            form.lng = newCustomer.lng || null;
        }
    },
    { immediate: true }
);

async function fetchLocation() {
    isLocating.value = true;

    const useIpFallback = async () => {
        try {
            const response = await fetch('https://ipapi.co/json/');
            if (!response.ok) throw new Error('IP geolocation failed');
            const data = await response.json();
            if (data && data.latitude && data.longitude) {
                const lat = parseFloat(data.latitude);
                const lng = parseFloat(data.longitude);

                form.lat = parseFloat(lat.toFixed(7));
                form.lng = parseFloat(lng.toFixed(7));

                if (map) {
                    setMarker(lat, lng, true);
                } else {
                    reverseGeocode(form.lat, form.lng, true);
                }
                return;
            }
        } catch (err) {
            console.warn('[CustomerFormModal] IP Geolocation failed:', err.message);
        }

        // Final fallback: just use default lat/lng
        isLocating.value = false;
        nextTick(() => {
            initialFormData.value = JSON.stringify(form.data());
            isDirty.value = false;
        });
    };

    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(
            (position) => {
                const { latitude, longitude } = position.coords;

                form.lat = parseFloat(latitude.toFixed(7));
                form.lng = parseFloat(longitude.toFixed(7));

                if (map) {
                    setMarker(latitude, longitude, true);
                } else {
                    reverseGeocode(form.lat, form.lng, true);
                }
            },
            (error) => {
                console.warn('[CustomerFormModal] Browser Geolocation error:', error.message);
                useIpFallback();
            },
            { enableHighAccuracy: false, timeout: 6000, maximumAge: 60000 }
        );
    } else {
        useIpFallback();
    }
}

// Handle modal open/close
watch(
    () => props.show,
    async (open) => {
        if (open) {
            duplicateCustomer.value = null;
            showDuplicateModal.value = false;
            if (!props.customer) {
                form.reset();
                fetchLocation();
            }
            await nextTick();

            // Ensure previous map is destroyed before creating new one
            destroyMap();

            // Wait for modal transition and DOM to be ready, then initialize map with retries
            const tryInitMap = (attempts = 0) => {
                if (mapContainer.value) {
                    initMap();
                    setTimeout(() => {
                        if (map) map.invalidateSize();
                        // Only set initialFormData here if we are not still locating
                        if (!isLocating.value) {
                            initialFormData.value = JSON.stringify(form.data());
                            isDirty.value = false;
                        }
                    }, 100);
                } else if (attempts < 10) {
                    // Retry after 100ms, up to 10 times (1 second total)
                    setTimeout(() => tryInitMap(attempts + 1), 100);
                } else {
                    console.warn('[CustomerFormModal] Map container not found after retries');
                }
            };

            // Start trying after modal animation (300ms)
            setTimeout(() => tryInitMap(), 300);
        } else {
            destroyMap();
        }
    }
);

// Track form changes
watch(
    () => form.data(),
    (newData) => {
        if (initialFormData.value && !isLocating.value) {
            const currentData = JSON.stringify(newData);
            const isChanged = currentData !== initialFormData.value;
            isDirty.value = isChanged;
        }
    },
    { deep: true }
);

// Auto-copy phone to whatsapp
watch(
    () => form.phone,
    (newPhone, oldPhone) => {
        const cleanWhatsapp = normalizePhone(form.whatsapp);
        const cleanOldPhone = normalizePhone(oldPhone);

        // Sync if whatsapp is empty OR if it matched the previous phone number
        if (!form.whatsapp || cleanWhatsapp === cleanOldPhone) {
            form.whatsapp = newPhone || '';
        }
    }
);

// Clear validation errors when inputs change
watch(
    () => form.name,
    () => {
        if (form.errors.name) form.clearErrors('name');
    }
);
watch(
    () => form.contact_name,
    () => {
        if (form.errors.contact_name) form.clearErrors('contact_name');
    }
);
watch(
    () => form.type,
    () => {
        if (form.errors.type) form.clearErrors('type');
    }
);
watch(
    () => form.phone,
    () => {
        if (form.errors.phone) form.clearErrors('phone');
    }
);
watch(
    () => form.whatsapp,
    () => {
        if (form.errors.whatsapp) form.clearErrors('whatsapp');
    }
);
watch(
    () => form.email,
    () => {
        if (form.errors.email) form.clearErrors('email');
    }
);
watch(
    () => form.tax_number,
    (newVal) => {
        if (newVal) {
            const converted = convertArabicToEnglish(newVal).replace(/[^\d]/g, '').slice(0, 15);
            if (converted !== newVal) {
                form.tax_number = converted;
            }
        }
        if (form.errors.tax_number) form.clearErrors('tax_number');
    }
);

function initMap() {
    if (!mapContainer.value || map) return;

    const startLat = form.lat || defaultLat.value;
    const startLng = form.lng || defaultLng.value;

    map = L.map(mapContainer.value).setView([startLat, startLng], 13);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors',
    }).addTo(map);

    // Add initial marker if we have coordinates
    if (form.lat && form.lng) {
        marker = L.marker([form.lat, form.lng], { draggable: true }).addTo(map);
        marker.on('dragend', onMarkerDrag);
    }

    // Handle map clicks
    map.on('click', onMapClick);

    mapReady.value = true;
}

function resetForm() {
    form.reset();
    isDirty.value = false;
    initialFormData.value = null;
}

async function handleClose() {
    if (isConfirming.value) return;

    if (isDirty.value) {
        isConfirming.value = true;
        try {
            const confirmed = await confirm({
                title: t('common.unsaved_changes'),
                message: t('common.unsaved_changes_message'),
                confirmText: t('common.yes_close'),
                cancelText: t('common.cancel'),
                type: 'warning',
            });

            if (!confirmed) {
                return;
            }
        } finally {
            isConfirming.value = false;
        }
    }

    resetForm();
    emit('close');
}

function destroyMap() {
    if (map) {
        map.off(); // Remove all event listeners
        map.remove();
        map = null;
        marker = null;
        mapReady.value = false;
    }
}

function onMapClick(e) {
    const { lat, lng } = e.latlng;
    setMarker(lat, lng);
}

function onMarkerDrag(e) {
    const { lat, lng } = e.target.getLatLng();
    form.lat = parseFloat(lat.toFixed(7));
    form.lng = parseFloat(lng.toFixed(7));
    reverseGeocode(form.lat, form.lng);
}

function setMarker(lat, lng, isInit = false) {
    form.lat = parseFloat(lat.toFixed(7));
    form.lng = parseFloat(lng.toFixed(7));

    if (marker) {
        marker.setLatLng([lat, lng]);
    } else {
        marker = L.marker([lat, lng], { draggable: true }).addTo(map);
        marker.on('dragend', onMarkerDrag);
    }

    map.panTo([lat, lng]);

    // Reverse geocode to fill address fields
    reverseGeocode(form.lat, form.lng, isInit);
}

// Reverse geocoding using OpenStreetMap Nominatim
async function reverseGeocode(lat, lng, isInit = false) {
    if (geocodingLoading.value) return;

    geocodingLoading.value = true;

    try {
        const lang = locale.value === 'ar' ? 'ar' : 'en';
        const response = await fetch(
            `https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}&addressdetails=1&accept-language=${lang}`,
            {
                headers: {
                    'User-Agent': 'KhidmhPro-App/1.0',
                },
            }
        );

        if (!response.ok) throw new Error('Geocoding failed');

        const data = await response.json();

        if (data && data.address) {
            const addr = data.address;

            // Fill form fields from geocoding response
            form.country = addr.country || form.country || '';
            form.region = addr.state || addr.region || form.region || '';
            form.city =
                addr.city || addr.town || addr.village || addr.municipality || form.city || '';
            form.district =
                addr.suburb ||
                addr.neighbourhood ||
                addr.quarter ||
                addr.district ||
                form.district ||
                '';

            // Extraction helpers for numeric fields (e.g. building_number and postal_code)
            const toEnglishDigits = (str) => {
                if (!str) return '';
                return str
                    .replace(/[٠-٩]/g, (d) => '٠١٢٣٤٥٦٧٨٩'.indexOf(d))
                    .replace(/[۰-۹]/g, (d) => '۰۱۲۳۴۵۶۷۸۹'.indexOf(d));
            };

            const cleanDisplayName = toEnglishDigits(data.display_name || '');
            const numbers = cleanDisplayName.match(/\d+/g) || [];

            // 1. Building number
            let bldNum = addr.house_number || '';
            bldNum = toEnglishDigits(bldNum).replace(/\D/g, ''); // Keep only digits
            if (bldNum.length !== 4) {
                bldNum = '';
            }
            if (!bldNum) {
                // Find first 4-digit sequence
                const fourDigit = numbers.find((n) => n.length === 4);
                if (fourDigit) {
                    bldNum = fourDigit;
                }
            }
            form.building_number = bldNum || form.building_number || '';

            // 2. Postal code
            let postCode = addr.postcode || '';
            postCode = toEnglishDigits(postCode).replace(/\D/g, ''); // Keep only digits
            if (postCode.length !== 5) {
                postCode = '';
            }
            if (!postCode) {
                // Find first 5-digit sequence
                const fiveDigit = numbers.find((n) => n.length === 5);
                if (fiveDigit) {
                    postCode = fiveDigit;
                }
            }
            form.postal_code = postCode || form.postal_code || '';

            // Build address line from available info
            const addressParts = [];
            if (addr.road) addressParts.push(addr.road);
            if (addr.neighbourhood && addr.neighbourhood !== form.district)
                addressParts.push(addr.neighbourhood);

            // Always update address_line with formatted address
            form.address_line =
                addressParts.length > 0
                    ? addressParts.join('، ')
                    : data.display_name?.split(',').slice(0, 2).join('،') || '';
        }
    } catch (error) {
        console.warn('Reverse geocoding failed:', error);
    } finally {
        geocodingLoading.value = false;
        if (isInit) {
            isLocating.value = false;
            nextTick(() => {
                initialFormData.value = JSON.stringify(form.data());
                isDirty.value = false;
            });
        }
    }
}

function updateMarkerFromInputs() {
    const lat = parseFloat(form.lat);
    const lng = parseFloat(form.lng);

    if (!isNaN(lat) && !isNaN(lng) && map) {
        if (marker) {
            marker.setLatLng([lat, lng]);
        } else {
            marker = L.marker([lat, lng], { draggable: true }).addTo(map);
            marker.on('dragend', onMarkerDrag);
        }
        map.setView([lat, lng], 15);
    }
}

// Clear all address fields
function clearAddressFields() {
    form.address_line = '';
    form.building_number = '';
    form.postal_code = '';
    form.district = '';
    form.city = '';
    form.region = '';
    form.country = '';
    form.lat = null;
    form.lng = null;

    // Remove marker from map
    if (marker && map) {
        map.removeLayer(marker);
        marker = null;
    }
}

const checkPhoneExistence = async () => {
    if (props.customer || !form.phone?.trim()) {
        return;
    }

    checkingPhone.value = true;
    try {
        const response = await axios.get(route('customers.check-phone', { phone: form.phone }));
        if (response.data.exists) {
            duplicateCustomer.value = response.data.customer;
            showDuplicateModal.value = true;
            form.errors.phone = t('customers.validation.phone_taken');
        } else {
            duplicateCustomer.value = null;
            if (form.errors.phone === t('customers.validation.phone_taken')) {
                form.errors.phone = null;
            }
        }
    } catch (err) {
        console.error('Error checking phone existence:', err);
    } finally {
        checkingPhone.value = false;
    }
};

const navigateToDuplicateCustomer = () => {
    if (duplicateCustomer.value) {
        showDuplicateModal.value = false;
        emit('close');
        router.visit(route('customers.show', duplicateCustomer.value.id));
    }
};

function submitForm() {
    if (!props.customer && duplicateCustomer.value) {
        showDuplicateModal.value = true;
        return;
    }

    if (
        form.tax_number &&
        form.tax_number.trim() !== '' &&
        !/^\d{15}$/.test(form.tax_number.trim())
    ) {
        form.setError('tax_number', t('validation.tax_number_invalid'));
        scrollToError({ tax_number: t('validation.tax_number_invalid') });
        return;
    }

    const url = props.customer ? `/app/customers/${props.customer.id}` : '/app/customers';

    const method = props.customer ? 'put' : 'post';

    form[method](url, {
        preserveScroll: true,
        onSuccess: (page) => {
            // Laravel sends the customer via with('customer', $customer)
            // which Inertia makes available in page.props or page.props.flash
            const savedCustomer =
                page.props.customer || page.props.flash?.customer || props.customer || form.data();

            form.reset();
            emit('saved', savedCustomer);
            emit('close');
        },
        onError: (errors) => {
            scrollToError(errors);
        },
    });
}

// Helper to convert Arabic numerals to English
function convertArabicToEnglish(value) {
    if (!value) return value;
    const arabic = '٠١٢٣٤٥٦٧٨٩';
    return value.replace(/[٠-٩]/g, (d) => arabic.indexOf(d));
}

// Quick actions
function normalizePhone(p) {
    return convertArabicToEnglish(p || '').replace(/[^\d+]/g, '');
}

// Handle phone input to convert numerals real-time and remove spaces
function onPhoneInput(type, value) {
    const converted = convertArabicToEnglish(value);
    // Remove spaces that vue-tel-input adds
    const normalized = converted.replace(/\s/g, '');

    if (value !== normalized) {
        form[type] = normalized;
    }
}

function quickCall() {
    if (!form.phone) return;
    window.open(`tel:${normalizePhone(form.phone)}`, '_self');
}

function quickWhatsApp() {
    if (!form.whatsapp) return;
    const phone = normalizePhone(form.whatsapp).replace(/^\+/, '');
    window.open(`https://wa.me/${phone}`, '_blank');
}

function quickEmail() {
    if (!form.email) return;
    window.open(`mailto:${form.email}`, '_self');
}

onUnmounted(() => {
    destroyMap();
});
</script>

<style>
/* Leaflet RTL fixes */
.leaflet-container {
    z-index: 0;
}

/* Vue Tel Input Customization */
.vue-tel-input {
    border-radius: 0.75rem !important;
    border-color: #d1d5db !important; /* gray-300 */
}
.vue-tel-input.required-border {
    border-color: #fca5a5 !important;
}
.dark .vue-tel-input.required-border {
    border-color: #991b1b !important;
}
.vue-tel-input.error-border {
    border-color: #ef4444 !important;
}
.vue-tel-input.error-border:focus-within {
    box-shadow: 0 0 0 2px #ef4444 !important;
    border-color: transparent !important;
}
.dark .vue-tel-input {
    border-color: #4b5563 !important; /* gray-600 */
    background-color: #111827 !important; /* gray-900 */
}
.vue-tel-input:focus-within {
    box-shadow: 0 0 0 2px var(--color-focus-ring) !important; /* ring-focus-ring */
    border-color: transparent !important;
}
.vti__input {
    background-color: transparent !important;
    border-radius: 0 0.75rem 0.75rem 0 !important;
}
.dark .vti__input {
    color: #f3f4f6 !important; /* gray-100 */
}
.vti__dropdown {
    border-radius: 0.75rem 0 0 0.75rem !important;
}
.dark .vti__dropdown-list {
    background-color: #1f2937 !important; /* gray-800 */
    border-color: #374151 !important; /* gray-700 */
}
.dark .vti__dropdown-item.highlighted {
    background-color: #374151 !important; /* gray-700 */
}
.dark .vti__dropdown-item strong,
.dark .vti__dropdown-item span {
    color: #f3f4f6 !important; /* gray-100 */
}
</style>
