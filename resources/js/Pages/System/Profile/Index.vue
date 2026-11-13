<script setup>
import SelectableCard from '@/Components/Common/SelectableCard.vue';
import { useI18n } from 'vue-i18n';
const { t } = useI18n();

import { ref, computed } from 'vue';
import { useForm, usePage, router } from '@inertiajs/vue3';
import SystemLayout from '@/Layouts/SystemLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import AppTabs from '@/Components/Common/AppTabs.vue';
import AppInput from '@/Components/App/AppInput.vue';
import TwoFactorManageSection from '@/Components/System/TwoFactorManageSection.vue';
import { useToast } from '@/Composables/useToast';

const { success } = useToast();
const page = usePage();
const admin = computed(() => page.props.admin);
const security = computed(() => page.props.security || {});

// Active Tab
const activeTab = ref('profile');
const tabs = computed(() => [
    { id: 'profile', label: t('employee_portal.profile') },
    { id: 'password', label: t('auth.password') },
    { id: 'security', label: t('security.tab_two_factor') },
]);

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
const codeSent = ref(false);
const selectedMethod = ref('email');

const sendCodeForm = useForm({
    method: 'email',
});
const enable2FAForm = useForm({ code: '' });

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
            <AppTabs v-model="activeTab" :tabs="tabs" variant="pills" sync-url>
                <template #icon-profile>
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"
                        />
                    </svg>
                </template>
                <template #icon-password>
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"
                        />
                    </svg>
                </template>
                <template #icon-security>
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"
                        />
                    </svg>
                </template>
            </AppTabs>

            <!-- Profile Tab -->
            <div
                v-if="activeTab === 'profile'"
                class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-sm border border-gray-200 dark:border-gray-700"
            >
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-6">
                    {{ $t('profile.info.title') }}
                </h3>

                <form @submit.prevent="updateProfile" class="space-y-6 max-w-xl">
                    <AppInput
                        type="text"
                        v-model="profileForm.name"
                        :label="$t('common.name')"
                        :error="profileForm.errors.name"
                        required
                    />

                    <AppInput
                        type="email"
                        v-model="profileForm.email"
                        :label="$t('center_settings.contact.email')"
                        :error="profileForm.errors.email"
                        required
                        dir="ltr"
                    />

                    <AppInput
                        type="tel"
                        v-model="profileForm.phone"
                        :label="$t('center_settings.contact.phone')"
                        :hint="$t('profile.sms_2fa_required')"
                        :error="profileForm.errors.phone"
                        dir="ltr"
                        placeholder="05xxxxxxxx"
                    />

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
                    <AppInput
                        type="password"
                        v-model="passwordForm.current_password"
                        :label="$t('company_profile.admin_user.current_password')"
                        :error="passwordForm.errors.current_password"
                        required
                    />

                    <AppInput
                        type="password"
                        v-model="passwordForm.password"
                        :label="$t('company_profile.admin_user.new_password')"
                        :error="passwordForm.errors.password"
                        required
                    />

                    <AppInput
                        type="password"
                        v-model="passwordForm.password_confirmation"
                        :label="$t('auth.confirm_password')"
                        required
                    />

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
                                    <div class="w-40">
                                        <AppInput
                                            type="text"
                                            v-model="enable2FAForm.code"
                                            maxlength="6"
                                            placeholder="000000"
                                            :error="enable2FAForm.errors.code"
                                            class="text-center text-2xl tracking-wider font-mono"
                                            dir="ltr"
                                            inputmode="numeric"
                                            autofocus
                                        />
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
                <TwoFactorManageSection
                    v-else
                    :recovery-codes="security.recoveryCodes || []"
                    :disable-url="route('system.2fa.disable')"
                    :regenerate-url="route('system.2fa.regenerate')"
                    :disable-success-message="$t('security.two_factor_disabled_success')"
                    :regenerate-success-message="$t('security.recovery_codes_created')"
                />
            </div>
        </div>
    </SystemLayout>
</template>
