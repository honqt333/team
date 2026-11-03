<template>
    <SystemLayout>
        <div class="max-w-4xl mx-auto py-6">
            <!-- Header -->
            <div class="mb-8">
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                    {{ t('system.payment_settings.title') }}
                </h1>
                <p class="text-gray-500 dark:text-gray-400">
                    {{ t('system.payment_settings.subtitle') }}
                </p>
            </div>

            <form @submit.prevent="submit" class="space-y-6">
                <!-- Default Gateway -->
                <div
                    class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm border border-gray-200 dark:border-gray-700"
                >
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                        {{ t('system.payment_settings.default_gateway') }}
                    </h2>
                    <SearchableSelect
                        v-model="form.default_gateway"
                        :options="gatewayOptions"
                        option-label="label"
                        option-value="value"
                    />
                </div>

                <!-- Payment Methods -->
                <div
                    class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm border border-gray-200 dark:border-gray-700"
                >
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                        {{ t('system.payment_settings.payment_methods.title') }}
                    </h2>
                    <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
                        <label
                            v-for="method in paymentMethodToggles"
                            :key="method.key"
                            class="flex items-center gap-2 cursor-pointer"
                        >
                            <input
                                type="checkbox"
                                v-model="form[method.field]"
                                class="rounded border-gray-300 text-indigo-600"
                            />
                            <span class="text-gray-700 dark:text-gray-300">
                                {{ t(method.labelKey) }}
                            </span>
                        </label>
                    </div>
                </div>

                <!-- Moyasar -->
                <div
                    class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm border border-gray-200 dark:border-gray-700"
                >
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
                            {{ t('system.payment_settings.gateway.moyasar') }}
                        </h2>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input
                                type="checkbox"
                                v-model="form.moyasar.enabled"
                                class="rounded border-gray-300 text-indigo-600"
                            />
                            <span class="text-sm text-gray-600 dark:text-gray-400">
                                {{ t('system.payment_settings.gateway.enabled') }}
                            </span>
                        </label>
                    </div>
                    <div v-if="form.moyasar.enabled" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label
                                class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"
                            >
                                {{ t('system.payment_settings.gateway.publishable_key') }}
                            </label>
                            <input
                                type="text"
                                v-model="form.moyasar.publishable_key"
                                placeholder="pk_..."
                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm"
                                dir="ltr"
                            />
                        </div>
                        <div>
                            <label
                                class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"
                            >
                                {{ t('system.payment_settings.gateway.secret_key') }}
                            </label>
                            <input
                                type="password"
                                v-model="form.moyasar.secret_key"
                                :placeholder="settings.moyasar?.secret_key_masked || 'sk_...'"
                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm"
                                dir="ltr"
                            />
                            <p class="text-xs text-gray-500 mt-1">
                                {{ t('system.payment_settings.placeholders.secret_keep') }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Tap -->
                <div
                    class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm border border-gray-200 dark:border-gray-700"
                >
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
                            {{ t('system.payment_settings.gateway.tap') }}
                        </h2>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input
                                type="checkbox"
                                v-model="form.tap.enabled"
                                class="rounded border-gray-300 text-indigo-600"
                            />
                            <span class="text-sm text-gray-600 dark:text-gray-400">
                                {{ t('system.payment_settings.gateway.enabled') }}
                            </span>
                        </label>
                    </div>
                    <div v-if="form.tap.enabled" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label
                                class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"
                            >
                                {{ t('system.payment_settings.gateway.public_key') }}
                            </label>
                            <input
                                type="text"
                                v-model="form.tap.public_key"
                                placeholder="pk_..."
                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm"
                                dir="ltr"
                            />
                        </div>
                        <div>
                            <label
                                class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"
                            >
                                {{ t('system.payment_settings.gateway.secret_key') }}
                            </label>
                            <input
                                type="password"
                                v-model="form.tap.secret_key"
                                :placeholder="settings.tap?.secret_key_masked || 'sk_...'"
                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm"
                                dir="ltr"
                            />
                            <p class="text-xs text-gray-500 mt-1">
                                {{ t('system.payment_settings.placeholders.secret_keep') }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- PayTabs -->
                <div
                    class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm border border-gray-200 dark:border-gray-700"
                >
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
                                {{ t('system.payment_settings.paytabs.title') }}
                            </h2>
                            <p class="text-xs text-gray-500">
                                {{ t('system.payment_settings.paytabs.subtitle') }}
                            </p>
                        </div>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input
                                type="checkbox"
                                v-model="form.paytabs.enabled"
                                class="rounded border-gray-300 text-indigo-600"
                            />
                            <span class="text-sm text-gray-600 dark:text-gray-400">
                                {{ t('system.payment_settings.gateway.enabled') }}
                            </span>
                        </label>
                    </div>
                    <div v-if="form.paytabs.enabled" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label
                                class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"
                            >
                                {{ t('system.payment_settings.gateway.profile_id') }}
                            </label>
                            <input
                                type="text"
                                v-model="form.paytabs.profile_id"
                                placeholder="12345"
                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm"
                                dir="ltr"
                            />
                        </div>
                        <div>
                            <label
                                class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"
                            >
                                {{ t('system.payment_settings.gateway.server_key') }}
                            </label>
                            <input
                                type="password"
                                v-model="form.paytabs.server_key"
                                :placeholder="settings.paytabs?.server_key_masked || 'srv_...'"
                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm"
                                dir="ltr"
                            />
                            <p class="text-xs text-gray-500 mt-1">
                                {{ t('system.payment_settings.placeholders.secret_keep') }}
                            </p>
                        </div>
                        <div>
                            <label
                                class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"
                            >
                                {{ t('system.payment_settings.gateway.client_key') }}
                            </label>
                            <input
                                type="text"
                                v-model="form.paytabs.client_key"
                                placeholder="client_..."
                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm"
                                dir="ltr"
                            />
                        </div>
                    </div>
                </div>

                <!-- Bank Transfer -->
                <div
                    class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm border border-gray-200 dark:border-gray-700"
                >
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
                            {{ t('system.payment_settings.bank_transfer.title') }}
                        </h2>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input
                                type="checkbox"
                                v-model="form.bank_transfer_enabled"
                                class="rounded border-gray-300 text-indigo-600"
                            />
                            <span class="text-sm text-gray-600 dark:text-gray-400">
                                {{ t('system.payment_settings.gateway.enabled') }}
                            </span>
                        </label>
                    </div>

                    <div v-if="form.bank_transfer_enabled" class="space-y-4">
                        <!-- Bank Accounts -->
                        <div>
                            <div class="flex items-center justify-between mb-2">
                                <label
                                    class="block text-sm font-medium text-gray-700 dark:text-gray-300"
                                >
                                    {{ t('system.payment_settings.bank_transfer.accounts_title') }}
                                </label>
                                <button
                                    type="button"
                                    @click="addBankAccount"
                                    class="text-sm text-indigo-600 hover:text-indigo-700"
                                >
                                    {{ t('system.payment_settings.bank_transfer.add_account') }}
                                </button>
                            </div>

                            <div
                                v-for="(account, index) in form.bank_accounts"
                                :key="index"
                                class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-4 mb-3"
                            >
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                    <input
                                        type="text"
                                        v-model="account.bank_name"
                                        :placeholder="
                                            t('system.payment_settings.bank_transfer.bank_name')
                                        "
                                        class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm"
                                    />
                                    <input
                                        type="text"
                                        v-model="account.account_name"
                                        :placeholder="
                                            t('system.payment_settings.bank_transfer.account_name')
                                        "
                                        class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm"
                                    />
                                    <input
                                        type="text"
                                        v-model="account.iban"
                                        :placeholder="
                                            t('system.payment_settings.bank_transfer.iban')
                                        "
                                        class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm"
                                        dir="ltr"
                                    />
                                    <div class="flex gap-2">
                                        <input
                                            type="text"
                                            v-model="account.account_number"
                                            :placeholder="
                                                t(
                                                    'system.payment_settings.bank_transfer.account_number'
                                                )
                                            "
                                            class="flex-1 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm"
                                            dir="ltr"
                                        />
                                        <button
                                            type="button"
                                            @click="removeBankAccount(index)"
                                            class="px-3 text-red-500 hover:text-red-700"
                                            :title="t('system.actions.delete')"
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
                        </div>

                        <!-- Instructions -->
                        <div>
                            <label
                                class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"
                            >
                                {{ t('system.payment_settings.bank_transfer.instructions_title') }}
                            </label>
                            <textarea
                                v-model="form.bank_transfer_instructions"
                                rows="3"
                                :placeholder="
                                    t(
                                        'system.payment_settings.bank_transfer.instructions_placeholder'
                                    )
                                "
                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm"
                            ></textarea>
                        </div>
                    </div>
                </div>

                <!-- BNPL Section Header -->
                <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-2">
                        {{ t('system.payment_settings.bnpl.title') }}
                    </h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
                        {{ t('system.payment_settings.bnpl.subtitle') }}
                    </p>
                </div>

                <!-- Tabby -->
                <div
                    class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm border border-gray-200 dark:border-gray-700"
                >
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center gap-3">
                            <div
                                class="w-10 h-10 bg-blue-100 dark:bg-blue-900/30 rounded-lg flex items-center justify-center"
                            >
                                <span class="text-blue-600 font-bold text-sm">T</span>
                            </div>
                            <div>
                                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
                                    {{ t('system.payment_settings.gateway.tabby') }}
                                </h2>
                                <p class="text-xs text-gray-500">
                                    {{ t('system.payment_settings.gateway.tabby_tagline') }}
                                </p>
                            </div>
                        </div>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input
                                type="checkbox"
                                v-model="form.tabby.enabled"
                                class="rounded border-gray-300 text-indigo-600"
                            />
                            <span class="text-sm text-gray-600 dark:text-gray-400">
                                {{ t('system.payment_settings.gateway.enabled') }}
                            </span>
                        </label>
                    </div>
                    <div v-if="form.tabby.enabled" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label
                                class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"
                            >
                                {{ t('system.payment_settings.gateway.public_key') }}
                            </label>
                            <input
                                type="text"
                                v-model="form.tabby.public_key"
                                placeholder="pk_..."
                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm"
                                dir="ltr"
                            />
                        </div>
                        <div>
                            <label
                                class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"
                            >
                                {{ t('system.payment_settings.gateway.secret_key') }}
                            </label>
                            <input
                                type="password"
                                v-model="form.tabby.secret_key"
                                :placeholder="settings.tabby?.secret_key_masked || 'sk_...'"
                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm"
                                dir="ltr"
                            />
                            <p class="text-xs text-gray-500 mt-1">
                                {{ t('system.payment_settings.placeholders.secret_keep') }}
                            </p>
                        </div>
                        <div>
                            <label
                                class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"
                            >
                                {{ t('system.payment_settings.gateway.merchant_code') }}
                            </label>
                            <input
                                type="text"
                                v-model="form.tabby.merchant_code"
                                placeholder="merchant_xxx"
                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm"
                                dir="ltr"
                            />
                        </div>
                    </div>
                </div>

                <!-- Tamara -->
                <div
                    class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm border border-gray-200 dark:border-gray-700"
                >
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center gap-3">
                            <div
                                class="w-10 h-10 bg-pink-100 dark:bg-pink-900/30 rounded-lg flex items-center justify-center"
                            >
                                <span class="text-pink-600 font-bold text-sm">T</span>
                            </div>
                            <div>
                                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
                                    {{ t('system.payment_settings.gateway.tamara') }}
                                </h2>
                                <p class="text-xs text-gray-500">
                                    {{ t('system.payment_settings.gateway.tamara_tagline') }}
                                </p>
                            </div>
                        </div>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input
                                type="checkbox"
                                v-model="form.tamara.enabled"
                                class="rounded border-gray-300 text-indigo-600"
                            />
                            <span class="text-sm text-gray-600 dark:text-gray-400">
                                {{ t('system.payment_settings.gateway.enabled') }}
                            </span>
                        </label>
                    </div>
                    <div v-if="form.tamara.enabled" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label
                                class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"
                            >
                                {{ t('system.payment_settings.gateway.api_token') }}
                            </label>
                            <input
                                type="password"
                                v-model="form.tamara.api_token"
                                :placeholder="settings.tamara?.api_token_masked || 'token_...'"
                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm"
                                dir="ltr"
                            />
                            <p class="text-xs text-gray-500 mt-1">
                                {{ t('system.payment_settings.placeholders.secret_keep') }}
                            </p>
                        </div>
                        <div>
                            <label
                                class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"
                            >
                                {{ t('system.payment_settings.gateway.notification_token') }}
                            </label>
                            <input
                                type="password"
                                v-model="form.tamara.notification_token"
                                :placeholder="
                                    settings.tamara?.notification_token_masked || 'notif_...'
                                "
                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm"
                                dir="ltr"
                            />
                        </div>
                    </div>
                </div>

                <!-- Company Info + Subscription VAT — moved to CompanySettings -->

                <!-- Bank Transfer Extra Controls -->
                <div
                    v-if="form.bank_transfer_enabled"
                    class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm border border-gray-200 dark:border-gray-700"
                >
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-1">
                        {{ t('system.payment_settings.bank_transfer.extras_title') }}
                    </h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
                        {{ t('system.payment_settings.bank_transfer.extras_subtitle') }}
                    </p>
                    <div class="space-y-4">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input
                                type="checkbox"
                                v-model="form.bank_transfer_require_receipt"
                                class="rounded border-gray-300 text-indigo-600"
                            />
                            <span class="text-gray-700 dark:text-gray-300 font-medium">
                                {{ t('system.payment_settings.bank_transfer.require_receipt') }}
                            </span>
                        </label>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 pl-7">
                            <div>
                                <label
                                    class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"
                                >
                                    {{ t('system.payment_settings.bank_transfer.max_days') }}
                                </label>
                                <input
                                    type="number"
                                    v-model.number="form.bank_transfer_max_days"
                                    min="1"
                                    max="90"
                                    dir="ltr"
                                    class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm"
                                />
                            </div>
                            <div>
                                <label
                                    class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"
                                >
                                    {{ t('system.payment_settings.bank_transfer.min_amount') }}
                                </label>
                                <input
                                    type="number"
                                    v-model.number="form.bank_transfer_min_amount"
                                    min="0"
                                    step="0.01"
                                    dir="ltr"
                                    class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm"
                                />
                            </div>
                        </div>
                        <div>
                            <label
                                class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"
                            >
                                {{
                                    t(
                                        'system.payment_settings.bank_transfer.receipt_instructions_title'
                                    )
                                }}
                            </label>
                            <textarea
                                v-model="form.bank_transfer_receipt_instructions"
                                rows="3"
                                :placeholder="
                                    t(
                                        'system.payment_settings.bank_transfer.receipt_instructions_placeholder'
                                    )
                                "
                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm"
                            ></textarea>
                        </div>
                    </div>
                </div>

                <!-- Submit -->
                <div class="flex justify-end">
                    <button type="submit" :disabled="form.processing" class="btn-primary">
                        {{
                            form.processing
                                ? t('system.payment_settings.actions.saving')
                                : t('system.payment_settings.actions.save')
                        }}
                    </button>
                </div>
            </form>
        </div>
    </SystemLayout>
</template>

<script setup>
import { computed } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import SystemLayout from '@/Layouts/SystemLayout.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';

const { t } = useI18n();

const props = defineProps({
    settings: Object,
});

const gatewayOptions = computed(() => [
    { value: 'moyasar', label: t('system.payment_settings.gateway.moyasar') },
    { value: 'tap', label: t('system.payment_settings.gateway.tap') },
    { value: 'paytabs', label: t('system.payment_settings.gateway.paytabs') },
    { value: 'tabby', label: t('system.payment_settings.gateway.tabby') },
    { value: 'tamara', label: t('system.payment_settings.gateway.tamara') },
    { value: 'bank_transfer', label: t('system.payment_settings.gateway.bank_transfer') },
]);

const paymentMethodToggles = [
    { field: 'mada_enabled', labelKey: 'system.payment_settings.payment_methods.mada' },
    { field: 'visa_enabled', labelKey: 'system.payment_settings.payment_methods.visa' },
    { field: 'mastercard_enabled', labelKey: 'system.payment_settings.payment_methods.mastercard' },
    { field: 'applepay_enabled', labelKey: 'system.payment_settings.payment_methods.apple_pay' },
    { field: 'stcpay_enabled', labelKey: 'system.payment_settings.payment_methods.stc_pay' },
];

const form = useForm({
    default_gateway: props.settings?.default_gateway || 'moyasar',
    moyasar: {
        enabled: props.settings?.moyasar?.enabled || false,
        publishable_key: props.settings?.moyasar?.publishable_key || '',
        secret_key: '',
    },
    tap: {
        enabled: props.settings?.tap?.enabled || false,
        public_key: props.settings?.tap?.public_key || '',
        secret_key: '',
    },
    paytabs: {
        enabled: props.settings?.paytabs?.enabled || false,
        profile_id: props.settings?.paytabs?.profile_id || '',
        server_key: '',
        client_key: props.settings?.paytabs?.client_key || '',
    },
    tabby: {
        enabled: props.settings?.tabby?.enabled || false,
        public_key: props.settings?.tabby?.public_key || '',
        secret_key: '',
        merchant_code: props.settings?.tabby?.merchant_code || '',
    },
    tamara: {
        enabled: props.settings?.tamara?.enabled || false,
        api_token: '',
        notification_token: '',
    },
    bank_transfer_enabled: props.settings?.bank_transfer_enabled || false,
    bank_accounts: props.settings?.bank_accounts || [],
    bank_transfer_instructions: props.settings?.bank_transfer_instructions || '',
    mada_enabled: props.settings?.mada_enabled ?? true,
    visa_enabled: props.settings?.visa_enabled ?? true,
    mastercard_enabled: props.settings?.mastercard_enabled ?? true,
    applepay_enabled: props.settings?.applepay_enabled ?? true,
    stcpay_enabled: props.settings?.stcpay_enabled ?? false,
});

const addBankAccount = () => {
    form.bank_accounts.push({
        bank_name: '',
        account_name: '',
        iban: '',
        account_number: '',
    });
};

const removeBankAccount = (index) => {
    form.bank_accounts.splice(index, 1);
};

const submit = () => {
    form.put('/system/settings/payment');
};
</script>
