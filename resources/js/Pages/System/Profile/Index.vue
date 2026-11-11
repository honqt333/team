<script setup>
import SelectableCard from '@/Components/Common/SelectableCard.vue';
import { useI18n } from 'vue-i18n';
const { t } = useI18n();

import { ref, computed } from 'vue';
import { useForm, usePage, router } from '@inertiajs/vue3';
import SystemLayout from '@/Layouts/SystemLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import { useToast } from '@/Composables/useToast';

const { success } = useToast();
const page = usePage();
const admin = computed(() => page.props.admin);
const security = computed(() => page.props.security || {});

// Active Tab
const activeTab = ref('profile');

// Profile Form
const profileForm = useForm({
    name: admin.value.name,
    email: admin.value.email,
    phone: admin.value.phone || '',
});

const updateProfile = () => {
    profileForm.put(route('system.profile.update'), {
        preserveScroll: true,
        onSuccess: () => success(t('profile.updated_success')),
    });
};

// Password Form
const passwordForm = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

const updatePassword = () => {
    passwordForm.put(route('system.profile.password'), {
        preserveScroll: true,
        onSuccess: () => {
            passwordForm.reset();
            success(t('security.password_changed_success'));
        },
    });
};

// 2FA Forms
const showDisableModal = ref(false);
const showRegenModal = ref(false);
const codeSent = ref(false);
const selectedMethod = ref('email');

const sendCodeForm = useForm({
    method: 'email',
});
const enable2FAForm = useForm({ code: '' });
const disable2FAForm = useForm({ password: '' });
const regenForm = useForm({ password: '' });

const sendCode = () => {
    sendCodeForm.method = selectedMethod.value;
    sendCodeForm.post(route('system.2fa.send-code'), {
        preserveScroll: true,
        onSuccess: () => {
            codeSent.value = true;
            success(t('security.code_sent_success'));
        },
    });
};

const enable2FA = () =>
    enable2FAForm.post(route('system.2fa.enable'), {
        preserveScroll: true,
        onError: () => enable2FAForm.reset(),
        onSuccess: () => success(t('security.two_factor_enabled_success')),
    });

const disable2FA = () =>
    disable2FAForm.post(route('system.2fa.disable'), {
        preserveScroll: true,
        onSuccess: () => {
            showDisableModal.value = false;
            success(t('security.two_factor_disabled_success'));
        },
    });

const regenerateCodes = () =>
    regenForm.post(route('system.2fa.regenerate'), {
        preserveScroll: true,
        onSuccess: () => {
            showRegenModal.value = false;
            success(t('security.recovery_codes_created'));
        },
    });
</script>

<template>
    <SystemLayout>
        <div class="max-w-4xl mx-auto space-y-6">
            <!-- Header -->
            <PageHeader :title="admin.name" :subtitle="admin.email">
                <template #icon>
                    <span class="text-xl font-bold">
                        {{ admin.name?.charAt(0).toUpperCase() }}
                    </span>
                </template>
                <template #badge>
                    <span
                        :class="{
                            'bg-purple-100 text-purple-700 border-purple-200':
                                admin.role === 'super_admin',
                            'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300 border-blue-200 dark:border-blue-800':
                                admin.role === 'admin',
                            'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300 border-gray-200 dark:border-gray-700':
                                admin.role === 'support',
                        }"
                        class="inline-block px-2.5 py-0.5 rounded-full text-xs font-semibold border"
                    >
                        {{
                            {
                                super_admin: $t('system.profile.system_admin'),
                                admin: $t('system.profile.admin'),
                                support: $t('system.profile.support'),
                            }[admin.role]
                        }}
                    </span>
                </template>
            </PageHeader>

            <!-- Tabs -->
            <div
                class="bg-white dark:bg-gray-800 rounded-2xl p-2 shadow-sm border border-gray-200 dark:border-gray-700"
            >
                <div class="flex items-center gap-1 flex-wrap">
                    <button
                        @click="activeTab = 'profile'"
                        :class="[
                            'flex items-center gap-2 px-5 py-3 rounded-xl text-sm font-medium transition-all',
                            activeTab === 'profile'
                                ? 'bg-action-primary hover:bg-action-primary-hover text-white shadow-lg shadow-focus-ring-soft'
                                : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700',
                        ]"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"
                            />
                        </svg>
                        {{ $t('employee_portal.profile') }}
                    </button>
                    <button
                        @click="activeTab = 'password'"
                        :class="[
                            'flex items-center gap-2 px-5 py-3 rounded-xl text-sm font-medium transition-all',
                            activeTab === 'password'
                                ? 'bg-action-primary hover:bg-action-primary-hover text-white shadow-lg shadow-focus-ring-soft'
                                : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700',
                        ]"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"
                            />
                        </svg>
                        {{ $t('auth.password') }}
                    </button>
                    <button
                        @click="activeTab = 'security'"
                        :class="[
                            'flex items-center gap-2 px-5 py-3 rounded-xl text-sm font-medium transition-all',
                            activeTab === 'security'
                                ? 'bg-action-primary hover:bg-action-primary-hover text-white shadow-lg shadow-focus-ring-soft'
                                : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700',
                        ]"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"
                            />
                        </svg>
                        {{ $t('security.tab_two_factor') }}
                    </button>
                </div>
            </div>

            <!-- Profile Tab -->
            <div
                v-if="activeTab === 'profile'"
                class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-sm border border-gray-200 dark:border-gray-700"
            >
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-6">
                    {{ $t('profile.info.title') }}
                </h3>

                <form @submit.prevent="updateProfile" class="space-y-6 max-w-xl">
                    <div>
                        <label
                            class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"
                        >
                            {{ $t('common.name') }}
                        </label>
                        <input
                            type="text"
                            v-model="profileForm.name"
                            class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-focus-ring focus:border-action-primary"
                            required
                        />
                        <p v-if="profileForm.errors.name" class="mt-1 text-sm text-red-500">
                            {{ profileForm.errors.name }}
                        </p>
                    </div>

                    <div>
                        <label
                            class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"
                        >
                            {{ $t('center_settings.contact.email') }}
                        </label>
                        <input
                            type="email"
                            v-model="profileForm.email"
                            class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-focus-ring focus:border-action-primary"
                            required
                            dir="ltr"
                        />
                        <p v-if="profileForm.errors.email" class="mt-1 text-sm text-red-500">
                            {{ profileForm.errors.email }}
                        </p>
                    </div>

                    <div>
                        <label
                            class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"
                        >
                            {{ $t('center_settings.contact.phone') }}
                        </label>
                        <input
                            type="tel"
                            v-model="profileForm.phone"
                            class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-focus-ring focus:border-action-primary"
                            dir="ltr"
                            placeholder="05xxxxxxxx"
                        />
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                            {{ $t('profile.sms_2fa_required') }}
                        </p>
                        <p v-if="profileForm.errors.phone" class="mt-1 text-sm text-red-500">
                            {{ profileForm.errors.phone }}
                        </p>
                    </div>

                    <div class="flex items-center gap-4">
                        <button
                            type="submit"
                            :disabled="profileForm.processing"
                            class="px-6 py-2.5 bg-action-primary hover:bg-action-primary-hover text-white rounded-lg font-medium disabled:opacity-50 transition-colors"
                        >
                            {{ $t('common.save_changes') }}
                        </button>
                    </div>
                </form>
            </div>

            <!-- Password Tab -->
            <div
                v-if="activeTab === 'password'"
                class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-sm border border-gray-200 dark:border-gray-700"
            >
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-6">
                    {{ $t('company_profile.admin_user.change_password') }}
                </h3>

                <form @submit.prevent="updatePassword" class="space-y-6 max-w-xl">
                    <div>
                        <label
                            class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"
                        >
                            {{ $t('company_profile.admin_user.current_password') }}
                        </label>
                        <input
                            type="password"
                            v-model="passwordForm.current_password"
                            class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-focus-ring focus:border-action-primary"
                            required
                        />
                        <p
                            v-if="passwordForm.errors.current_password"
                            class="mt-1 text-sm text-red-500"
                        >
                            {{ passwordForm.errors.current_password }}
                        </p>
                    </div>

                    <div>
                        <label
                            class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"
                        >
                            {{ $t('company_profile.admin_user.new_password') }}
                        </label>
                        <input
                            type="password"
                            v-model="passwordForm.password"
                            class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-focus-ring focus:border-action-primary"
                            required
                        />
                        <p v-if="passwordForm.errors.password" class="mt-1 text-sm text-red-500">
                            {{ passwordForm.errors.password }}
                        </p>
                    </div>

                    <div>
                        <label
                            class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"
                        >
                            {{ $t('auth.confirm_password') }}
                        </label>
                        <input
                            type="password"
                            v-model="passwordForm.password_confirmation"
                            class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-focus-ring focus:border-action-primary"
                            required
                        />
                    </div>

                    <button
                        type="submit"
                        :disabled="passwordForm.processing"
                        class="px-6 py-2.5 bg-action-primary hover:bg-action-primary-hover text-white rounded-lg font-medium disabled:opacity-50 transition-colors"
                    >
                        {{ $t('security.update_password') }}
                    </button>
                </form>
            </div>

            <!-- Security (2FA) Tab -->
            <div v-if="activeTab === 'security'" class="space-y-6">
                <!-- Not Enabled State -->
                <div
                    v-if="!security.isEnabled"
                    class="bg-white dark:bg-gray-800 rounded-2xl p-6 border border-gray-200 dark:border-gray-700 shadow-sm"
                >
                    <div class="flex items-center gap-4 mb-6">
                        <div
                            class="w-12 h-12 rounded-full bg-amber-100 dark:bg-amber-900/30 flex items-center justify-center"
                        >
                            <svg
                                class="w-6 h-6 text-amber-600"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"
                                />
                            </svg>
                        </div>
                        <div>
                            <h3 class="font-semibold text-gray-900 dark:text-white text-lg">
                                {{ $t('security.two_factor_disabled') }}
                            </h3>
                            <p class="text-sm text-gray-500">
                                {{ $t('security.enable_protect_account') }}
                            </p>
                        </div>
                    </div>

                    <div class="max-w-xl space-y-6">
                        <!-- Step 1: Send Code -->
                        <div v-if="!codeSent">
                            <p class="text-gray-600 dark:text-gray-300 mb-4">
                                {{ $t('security.code_valid_10_minutes') }}
                            </p>

                            <!-- Method Selection -->
                            <div class="mb-6 space-y-3">
                                <SelectableCard
                                    type="radio"
                                    name="selectedMethod"
                                    v-model="selectedMethod"
                                    value="email"
                                    class="flex items-center p-3 border border-gray-200 dark:border-gray-700 rounded-lg cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors"
                                >
                                    <span class="ms-3 flex-1">
                                        <span
                                            class="block text-sm font-medium text-gray-900 dark:text-white"
                                        >
                                            {{ $t('center_settings.contact.email') }}
                                        </span>
                                        <span class="block text-sm text-gray-500">
                                            {{ $t('security.send_code_to') }} {{ admin.email }}
                                        </span>
                                    </span>
                                </SelectableCard>

                                <SelectableCard
                                    type="radio"
                                    name="selectedMethod"
                                    v-model="selectedMethod"
                                    value="sms"
                                    v-if="security.smsEnabled"
                                    class="flex items-center p-3 border border-gray-200 dark:border-gray-700 rounded-lg cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors"
                                >
                                    <span class="ms-3 flex-1">
                                        <span
                                            class="block text-sm font-medium text-gray-900 dark:text-white"
                                        >
                                            {{ $t('security.text_messages_sms') }}
                                        </span>
                                        <span class="block text-sm text-gray-500">
                                            {{ $t('security.send_code_to') }}
                                            {{ admin.phone || $t('security.registered_phone') }}
                                        </span>
                                    </span>
                                </SelectableCard>
                            </div>

                            <button
                                @click="sendCode"
                                :disabled="sendCodeForm.processing"
                                class="flex items-center gap-2 px-6 py-2.5 bg-action-primary hover:bg-action-primary-hover text-white rounded-lg font-medium disabled:opacity-50"
                            >
                                <svg
                                    v-if="!sendCodeForm.processing"
                                    class="w-5 h-5"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"
                                    />
                                </svg>
                                <span v-else class="loading loading-spinner loading-sm"></span>
                                {{ $t('security.send_verification_code') }}
                            </button>

                            <p v-if="$page.props.errors?.method" class="mt-2 text-sm text-red-500">
                                {{ $page.props.errors.method }}
                            </p>
                        </div>

                        <!-- Step 2: Verify Code -->
                        <div v-else>
                            <h4 class="font-medium text-gray-900 dark:text-white mb-3">
                                {{ $t('security.enter_code_sent_to') }}
                                {{
                                    selectedMethod === 'sms'
                                        ? $t('security.your_phone')
                                        : $t('security.your_email')
                                }}
                            </h4>
                            <form @submit.prevent="enable2FA" class="space-y-4">
                                <div class="flex items-start gap-3">
                                    <div class="relative">
                                        <input
                                            type="text"
                                            v-model="enable2FAForm.code"
                                            maxlength="6"
                                            placeholder="000000"
                                            class="w-40 text-center text-2xl tracking-wider rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white font-mono focus:ring-focus-ring focus:border-action-primary"
                                            dir="ltr"
                                            inputmode="numeric"
                                            autofocus
                                        />
                                        <p
                                            v-if="enable2FAForm.errors.code"
                                            class="text-red-500 text-sm mt-1 absolute right-0 w-60"
                                        >
                                            {{ enable2FAForm.errors.code }}
                                        </p>
                                    </div>
                                    <button
                                        type="submit"
                                        :disabled="
                                            enable2FAForm.processing ||
                                            enable2FAForm.code.length !== 6
                                        "
                                        class="px-6 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg font-medium disabled:opacity-50 h-[46px]"
                                    >
                                        {{ $t('common.activate') }}
                                    </button>
                                </div>

                                <div class="text-sm">
                                    <span class="text-gray-500">
                                        {{ $t('security.did_not_get_the_code') }}
                                    </span>
                                    <button
                                        type="button"
                                        @click="sendCode"
                                        :disabled="sendCodeForm.processing"
                                        class="text-action-primary hover:text-selection-text font-medium"
                                    >
                                        {{ $t('security.resend') }}
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Enabled State -->
                <div v-else class="space-y-6">
                    <div
                        class="bg-white dark:bg-gray-800 rounded-2xl p-6 border border-gray-200 dark:border-gray-700 shadow-sm"
                    >
                        <div class="flex items-center gap-4">
                            <div
                                class="w-12 h-12 rounded-full bg-emerald-100 dark:bg-emerald-900/30 flex items-center justify-center"
                            >
                                <svg
                                    class="w-6 h-6 text-emerald-600"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"
                                    />
                                </svg>
                            </div>
                            <div class="flex-1">
                                <h3 class="font-semibold text-gray-900 dark:text-white text-lg">
                                    {{ $t('security.two_factor_enabled') }}
                                </h3>
                                <p class="text-sm text-gray-500">
                                    {{ $t('security.account_protected') }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Recovery Codes -->
                    <div
                        class="bg-white dark:bg-gray-800 rounded-2xl p-6 border border-gray-200 dark:border-gray-700 shadow-sm"
                    >
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="font-semibold text-gray-900 dark:text-white">
                                {{ $t('security.recovery_codes') }}
                            </h3>
                            <button
                                @click="showRegenModal = true"
                                class="text-sm text-action-primary hover:text-selection-text"
                            >
                                {{ $t('security.new_codes') }}
                            </button>
                        </div>
                        <p class="text-sm text-gray-500 mb-4">
                            {{ $t('security.keep_codes_safe') }}
                        </p>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-2">
                            <code
                                v-for="code in security.recoveryCodes"
                                :key="code"
                                class="text-sm bg-gray-100 dark:bg-gray-700 px-3 py-2 rounded text-center font-mono text-gray-800 dark:text-gray-300"
                            >
                                {{ code }}
                            </code>
                        </div>
                    </div>

                    <!-- Disable 2FA -->
                    <div
                        class="bg-white dark:bg-gray-800 rounded-2xl p-6 border border-red-200 dark:border-red-900/30 shadow-sm"
                    >
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="font-semibold text-gray-900 dark:text-white">
                                    {{ $t('security.disable_two_factor') }}
                                </h3>
                                <p class="text-sm text-gray-500">
                                    {{ $t('security.this_reduces_security') }}
                                </p>
                            </div>
                            <button
                                @click="showDisableModal = true"
                                class="px-4 py-2 bg-red-100 text-red-700 rounded-lg text-sm font-medium hover:bg-red-200 transition-colors"
                            >
                                {{ $t('security.deactivate') }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modals for 2FA -->
            <!-- Disable Modal -->
            <Transition name="modal">
                <div v-if="showDisableModal" class="fixed inset-0 z-50 overflow-y-auto">
                    <div class="flex min-h-full items-center justify-center p-4">
                        <div
                            class="fixed inset-0 bg-black/50"
                            @click="showDisableModal = false"
                        ></div>
                        <div
                            class="relative bg-white dark:bg-gray-800 rounded-2xl p-6 w-full max-w-md shadow-xl"
                        >
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                                {{ $t('security.confirm_disable_two_factor') }}
                            </h3>
                            <form @submit.prevent="disable2FA">
                                <div class="mb-4">
                                    <label
                                        class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"
                                    >
                                        {{ $t('security.enter_password_to_confirm') }}
                                    </label>
                                    <input
                                        type="password"
                                        v-model="disable2FAForm.password"
                                        required
                                        class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                    />
                                </div>
                                <div class="flex gap-3">
                                    <button
                                        type="submit"
                                        :disabled="disable2FAForm.processing"
                                        class="flex-1 px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg font-medium disabled:opacity-50"
                                    >
                                        {{ $t('security.deactivate') }}
                                    </button>
                                    <button
                                        type="button"
                                        @click="showDisableModal = false"
                                        class="px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg"
                                    >
                                        {{
                                            $t(
                                                'system.company_subscription.history.confirm_modal.go_back'
                                            )
                                        }}
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </Transition>

            <!-- Regenerate Modal -->
            <Transition name="modal">
                <div v-if="showRegenModal" class="fixed inset-0 z-50 overflow-y-auto">
                    <div class="flex min-h-full items-center justify-center p-4">
                        <div
                            class="fixed inset-0 bg-black/50"
                            @click="showRegenModal = false"
                        ></div>
                        <div
                            class="relative bg-white dark:bg-gray-800 rounded-2xl p-6 w-full max-w-md shadow-xl"
                        >
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                                {{ $t('security.new_recovery_codes') }}
                            </h3>
                            <p class="text-sm text-gray-500 mb-4">
                                {{ $t('security.old_codes_cancelled') }}
                            </p>
                            <form @submit.prevent="regenerateCodes">
                                <div class="mb-4">
                                    <label
                                        class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"
                                    >
                                        {{ $t('security.enter_password') }}
                                    </label>
                                    <input
                                        type="password"
                                        v-model="regenForm.password"
                                        required
                                        class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                    />
                                </div>
                                <div class="flex gap-3">
                                    <button
                                        type="submit"
                                        :disabled="regenForm.processing"
                                        class="flex-1 px-4 py-2 bg-action-primary hover:bg-action-primary-hover text-white rounded-lg font-medium disabled:opacity-50"
                                    >
                                        {{ $t('security.new_codes') }}
                                    </button>
                                    <button
                                        type="button"
                                        @click="showRegenModal = false"
                                        class="px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg"
                                    >
                                        {{ $t('common.cancel') }}
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </Transition>
        </div>
    </SystemLayout>
</template>
