<script setup>
import DataTable from '@/Components/Common/DataTable.vue';
import SelectableCard from '@/Components/Common/SelectableCard.vue';
import AppTabs from '@/Components/Common/AppTabs.vue';
import { useI18n } from 'vue-i18n';
const { t } = useI18n();

import { ref, computed } from 'vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import { useToast } from '@/Composables/useToast';
import { useConfirm } from '@/Composables/useConfirm';
import { useFormScrollToError } from '@/Composables/useFormScrollToError';
import { useNumberFormat } from '@/Composables/useNumberFormat';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import BaseModal from '@/Components/BaseModal.vue';
import AppToggle from '@/Components/Common/AppToggle.vue';
import AppInput from '@/Components/App/AppInput.vue';

const props = defineProps({
    mustVerifyEmail: Boolean,
    status: String,
    isEnabled: Boolean,
    isEnforced: Boolean,
    recoveryCodes: Array,
    smsEnabled: Boolean,
    currentMethod: String,
    employeeDetails: Object,
    roles: Array,
    isFullAccess: Boolean,
    permissions: Array,
    preferences: Object,
    signInHistory: Array,
});

const { success, error } = useToast();
const { confirm } = useConfirm();
const { scrollToError } = useFormScrollToError();
const { toEnglish } = useNumberFormat();
const page = usePage();
const user = computed(() => page.props.auth.user);

const profileTabMap = {
    name: 'profile',
    email: 'profile',
    photo: 'profile',
    layout: 'preferences',
    open_whatsapp: 'preferences',
    whatsapp_sharing_preference: 'preferences',
    hide_floating_button: 'preferences',
    hide_floating_contact: 'preferences',
    current_password: 'password',
    password: 'password',
    password_confirmation: 'password',
};

// Active Tab
const activeTab = ref('profile');

const tabs = computed(() => [
    {
        id: 'profile',
        label: t('profile.tabs.info'),
        count: tabErrorCount.value.profile > 0 ? toEnglish(tabErrorCount.value.profile) : null,
    },
    {
        id: 'preferences',
        label: t('profile.tabs.preferences'),
        count:
            tabErrorCount.value.preferences > 0 ? toEnglish(tabErrorCount.value.preferences) : null,
    },
    {
        id: 'sign_in_history',
        label: t('profile.tabs.sign_in_history'),
        count: props.signInHistory?.length ? toEnglish(props.signInHistory.length) : null,
    },
    {
        id: 'security',
        label: t('profile.tabs.security'),
        count: props.isEnabled ? '2FA' : null,
    },
    {
        id: 'password',
        label: t('profile.tabs.password'),
        count: tabErrorCount.value.password > 0 ? toEnglish(tabErrorCount.value.password) : null,
    },
]);

const signInColumns = computed(() => [
    { key: 'index', label: '#', align: 'center', width: 'w-12' },
    { key: 'formatted_date', label: t('profile.sign_in_history.date') },
    { key: 'ip_address', label: t('profile.sign_in_history.ip'), align: 'center' },
    { key: 'device', label: t('profile.sign_in_history.device') },
    { key: 'diff_for_humans', label: t('profile.sign_in_history.activity'), align: 'center' },
]);

// Permissions Modal
const showPermissionsModal = ref(false);
const permissionSearchQuery = ref('');

const filteredPermissions = computed(() => {
    if (!props.permissions) return [];
    if (!permissionSearchQuery.value.trim()) return props.permissions;
    const query = permissionSearchQuery.value.toLowerCase().trim();
    return props.permissions.filter(
        (p) =>
            p.name.toLowerCase().includes(query) ||
            (p.label && p.label.toLowerCase().includes(query))
    );
});

// Profile Form
const profileForm = useForm({
    name: user.value.name,
    email: user.value.email,
});

const uploadPhoto = (event) => {
    const file = event.target.files[0];
    if (!file) return;

    photoForm.photo = file;
    photoForm.post(route('app.profile.photo.update'), {
        preserveScroll: true,
        onSuccess: () => {
            success(t('profile.photo.success'));
            photoForm.reset();
        },
        onError: (errors) => {
            if (errors.photo) error(errors.photo);
        },
    });
};

const photoForm = useForm({
    photo: null,
});

const tabErrorCount = computed(() => ({
    profile: Object.keys(profileForm.errors).length,
    password: Object.keys(passwordForm.errors).length,
    preferences: Object.keys(preferencesForm.errors).length,
}));

const updateProfile = () => {
    profileForm.patch(route('app.profile.update'), {
        preserveScroll: true,
        onSuccess: () => success(t('profile.updated_success')),
        onError: (errors) => {
            scrollToError(errors, {
                tabMap: profileTabMap,
                onSwitchTab: (k) => (activeTab.value = k),
            });
        },
    });
};

// Password Form
const passwordForm = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

const updatePassword = () => {
    passwordForm.put(route('password.update'), {
        preserveScroll: true,
        onSuccess: () => {
            passwordForm.reset();
            success(t('security.password_changed_success'));
        },
        onError: (errors) => {
            scrollToError(errors, {
                tabMap: profileTabMap,
                onSwitchTab: (k) => (activeTab.value = k),
            });
        },
    });
};

// Preferences Form
const preferencesForm = useForm({
    layout: props.preferences?.layout || 'side',
    open_whatsapp: props.preferences?.open_whatsapp || 'app',
    whatsapp_sharing_preference: props.preferences?.whatsapp_sharing_preference || 'form',
    hide_floating_button: Boolean(props.preferences?.hide_floating_button),
    hide_floating_contact: Boolean(props.preferences?.hide_floating_contact),
});

const savePreferences = () => {
    preferencesForm.post(route('app.profile.preferences'), {
        preserveScroll: true,
        onSuccess: () => {
            if (typeof window !== 'undefined') {
                localStorage.setItem('user_nav_layout', preferencesForm.layout);
            }
            success(t('profile.preferences.saved_success'));
        },
        onError: (errors) => {
            scrollToError(errors, {
                tabMap: profileTabMap,
                onSwitchTab: (k) => (activeTab.value = k),
            });
        },
    });
};

// Clear Application Cache
const clearAppCache = async () => {
    const confirmed = await confirm({
        title: t('profile.cache.confirm_title'),
        message: t('profile.cache.confirm_message'),
        confirmText: t('profile.cache.clear_button'),
        cancelText: t('common.cancel'),
        type: 'warning',
    });

    if (confirmed) {
        try {
            localStorage.clear();
            sessionStorage.clear();

            if ('caches' in window) {
                const cacheNames = await caches.keys();
                await Promise.all(cacheNames.map((name) => caches.delete(name)));
            }

            success(t('profile.cache.cleared_success'));

            setTimeout(() => {
                window.location.reload();
            }, 600);
        } catch {
            window.location.reload();
        }
    }
};

// 2FA
const showDisableModal = ref(false);
const showRegenModal = ref(false);
const codeSent = ref(false);
const selectedMethod = ref('email');

const sendCodeForm = useForm({ method: 'email' });
const codeForm = useForm({ code: '' });
const disableForm = useForm({ password: '' });
const regenForm = useForm({ password: '' });

const sendCode = () => {
    sendCodeForm.method = selectedMethod.value;
    sendCodeForm.post(route('app.2fa.send-code'), {
        onSuccess: () => (codeSent.value = true),
    });
};

const enable2FA = () =>
    codeForm.post(route('app.security.2fa.enable'), {
        preserveScroll: true,
        onSuccess: () => {
            codeSent.value = false;
            codeForm.reset();
            success(t('security.two_factor_enabled_success'));
        },
        onError: () => codeForm.reset(),
    });

const disable2FA = () =>
    disableForm.post(route('app.security.2fa.disable'), {
        onSuccess: () => {
            showDisableModal.value = false;
            success(t('security.two_factor_disabled_success'));
        },
    });

const regenerateCodes = () =>
    regenForm.post(route('app.security.2fa.regenerate'), {
        onSuccess: () => {
            showRegenModal.value = false;
            success(t('security.recovery_codes_created'));
        },
    });
</script>

<template>
    <AppLayout>
        <Head :title="$t('profile.title')" />

        <div class="space-y-6">
            <!-- Header Section with Integrated Tabs -->
            <PageHeader :title="user.name">
                <template #icon>
                    <!-- Photo Display & Upload Overlay -->
                    <div
                        class="relative w-full h-full rounded-2xl overflow-hidden group flex items-center justify-center cursor-pointer select-none"
                    >
                        <img
                            v-if="user.photo_url"
                            :src="user.photo_url"
                            :alt="user.name"
                            class="w-full h-full object-cover"
                        />
                        <span v-else class="text-white text-2xl font-black">
                            {{ user.name?.charAt(0).toUpperCase() }}
                        </span>

                        <!-- Upload Overlay -->
                        <div
                            v-if="user.can_update_photo"
                            @click="$refs.photoInput.click()"
                            class="absolute inset-0 bg-black/60 backdrop-blur-[2px] flex flex-col items-center justify-center opacity-0 group-hover:opacity-100 transition-all duration-300"
                            :title="$t('profile.photo.update')"
                        >
                            <svg
                                class="w-5 h-5 text-white mb-1"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"
                                />
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"
                                />
                            </svg>
                            <span class="text-[10px] text-white/90 font-medium">
                                {{ $t('profile.photo.update') }}
                            </span>
                        </div>

                        <!-- Hidden Input -->
                        <input
                            type="file"
                            ref="photoInput"
                            class="hidden"
                            accept="image/*"
                            @change="uploadPhoto"
                        />

                        <!-- Loading State -->
                        <div
                            v-if="photoForm.processing"
                            class="absolute inset-0 bg-white/70 dark:bg-gray-800/70 flex items-center justify-center"
                        >
                            <div
                                class="w-6 h-6 border-2 border-action-primary border-t-transparent rounded-full animate-spin"
                            ></div>
                        </div>
                    </div>
                </template>

                <template #identity>
                    <div class="flex flex-wrap items-center gap-2.5 mt-1.5">
                        <!-- Email Pill -->
                        <span
                            class="inline-flex items-center gap-1.5 text-xs font-medium text-gray-600 dark:text-gray-300 px-3 py-1 bg-gray-100/80 dark:bg-gray-700/60 rounded-xl"
                        >
                            <svg
                                class="w-3.5 h-3.5 text-gray-400"
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
                            {{ user.email }}
                        </span>

                        <!-- Phone Pill -->
                        <span
                            v-if="user.phone"
                            dir="ltr"
                            class="inline-flex items-center gap-1.5 text-xs font-mono font-medium text-gray-600 dark:text-gray-300 px-3 py-1 bg-gray-100/80 dark:bg-gray-700/60 rounded-xl"
                        >
                            <svg
                                class="w-3.5 h-3.5 text-gray-400"
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
                            {{ user.phone }}
                        </span>

                        <!-- Job Title & Department Pill -->
                        <span
                            v-if="employeeDetails?.job_title"
                            class="inline-flex items-center gap-1.5 text-xs font-semibold px-3 py-1 rounded-xl bg-selection-bg text-selection-text border border-action-primary/25"
                        >
                            <svg
                                class="w-3.5 h-3.5 text-action-primary"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"
                                />
                            </svg>
                            {{ employeeDetails.job_title }}
                            <span v-if="employeeDetails.department" class="opacity-70">
                                ({{ employeeDetails.department }})
                            </span>
                        </span>

                        <!-- HR Photo Locked Badge -->
                        <span
                            v-if="!user.can_update_photo"
                            class="inline-flex items-center gap-1 px-2.5 py-1 bg-amber-50 dark:bg-amber-900/20 text-amber-600 dark:text-amber-400 border border-amber-200 dark:border-amber-800/50 rounded-xl text-xs font-medium"
                        >
                            <svg
                                class="w-3 h-3"
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
                            {{ $t('profile.photo.hr_locked') }}
                        </span>

                        <!-- Roles & Permissions Badge -->
                        <span
                            v-if="isFullAccess"
                            class="inline-flex items-center gap-1.5 px-3 py-1 rounded-xl text-xs font-semibold bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-300 border border-emerald-200/60 dark:border-emerald-800/50"
                        >
                            <svg
                                class="w-3.5 h-3.5 text-emerald-600"
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
                            {{ $t('profile.header.full_access') }}
                        </span>
                        <template v-else-if="roles && roles.length > 0">
                            <span
                                v-for="role in roles"
                                :key="role.id"
                                class="inline-flex items-center gap-1.5 px-3 py-1 rounded-xl text-xs font-semibold bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-200"
                            >
                                {{ role.label }}
                            </span>
                            <button
                                type="button"
                                @click="showPermissionsModal = true"
                                class="inline-flex items-center gap-1 px-3 py-1 rounded-xl text-xs font-semibold bg-blue-50 hover:bg-blue-100 dark:bg-blue-900/30 dark:hover:bg-blue-900/50 text-blue-700 dark:text-blue-300 border border-blue-200/60 dark:border-blue-800/50 transition-colors"
                            >
                                <svg
                                    class="w-3.5 h-3.5 text-blue-500"
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
                                {{ $t('profile.header.view_permissions') }}
                            </button>
                        </template>
                    </div>
                </template>

                <template #actions>
                    <div class="flex items-center gap-2">
                        <!-- Quick Clear Cache Action -->
                        <button
                            type="button"
                            @click="clearAppCache"
                            class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-semibold text-amber-700 dark:text-amber-300 bg-amber-50 hover:bg-amber-100 dark:bg-amber-900/30 dark:hover:bg-amber-900/50 border border-amber-200 dark:border-amber-800/50 rounded-xl transition-all shadow-sm"
                            :title="$t('profile.cache.clear_button')"
                        >
                            <svg
                                class="w-4 h-4 text-amber-600"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"
                                />
                            </svg>
                            <span>{{ $t('profile.cache.clear_button') }}</span>
                        </button>
                    </div>
                </template>

                <!-- Integrated Modern Tabs Navigation inside PageHeader filters slot -->
                <template #filters>
                    <AppTabs v-model="activeTab" :tabs="tabs" variant="pills">
                        <template #icon-profile>
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
                                    d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"
                                />
                            </svg>
                        </template>
                        <template #icon-preferences>
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
                                    d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"
                                />
                            </svg>
                        </template>
                        <template #icon-sign_in_history>
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
                                    d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"
                                />
                            </svg>
                        </template>
                        <template #icon-security>
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
                                    d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"
                                />
                            </svg>
                        </template>
                        <template #icon-password>
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
                                    d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"
                                />
                            </svg>
                        </template>
                    </AppTabs>
                </template>
            </PageHeader>

            <!-- Main Content Container -->
            <div
                class="bg-white dark:bg-gray-800 rounded-3xl shadow-sm border border-gray-100 dark:border-gray-700/60 overflow-hidden"
            >
                <div class="p-6 sm:p-8">
                    <!-- Tab 1: Profile Info -->
                    <div v-if="activeTab === 'profile'" class="space-y-6">
                        <div class="border-b border-gray-100 dark:border-gray-700 pb-5">
                            <h3 class="text-lg font-bold text-gray-900 dark:text-white">
                                {{ $t('profile.info.title') }}
                            </h3>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                {{ $t('profile.information_subtitle') }}
                            </p>
                        </div>

                        <form @submit.prevent="updateProfile" class="space-y-6 max-w-xl">
                            <div>
                                <label
                                    class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1.5"
                                >
                                    {{ $t('profile.info.name') }}
                                </label>
                                <div class="relative rounded-xl shadow-sm">
                                    <div
                                        class="absolute inset-y-0 start-0 ps-3.5 flex items-center pointer-events-none text-gray-400"
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
                                                d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"
                                            />
                                        </svg>
                                    </div>
                                    <input
                                        type="text"
                                        name="name"
                                        v-model="profileForm.name"
                                        class="w-full ps-10 rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700/80 dark:text-white focus:ring-2 focus:ring-focus-ring focus:border-action-primary text-sm"
                                        required
                                    />
                                </div>
                                <p
                                    v-if="profileForm.errors.name"
                                    class="mt-1.5 text-xs text-red-500 font-medium"
                                >
                                    {{ profileForm.errors.name }}
                                </p>
                            </div>

                            <div>
                                <label
                                    class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1.5"
                                >
                                    {{ $t('profile.info.email') }}
                                </label>
                                <div class="relative rounded-xl shadow-sm">
                                    <div
                                        class="absolute inset-y-0 start-0 ps-3.5 flex items-center pointer-events-none text-gray-400"
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
                                                d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"
                                            />
                                        </svg>
                                    </div>
                                    <input
                                        type="email"
                                        name="email"
                                        v-model="profileForm.email"
                                        class="w-full ps-10 rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700/80 dark:text-white focus:ring-2 focus:ring-focus-ring focus:border-action-primary text-sm"
                                        required
                                    />
                                </div>
                                <p
                                    v-if="profileForm.errors.email"
                                    class="mt-1.5 text-xs text-red-500 font-medium"
                                >
                                    {{ profileForm.errors.email }}
                                </p>
                            </div>

                            <div>
                                <label
                                    class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1.5"
                                >
                                    {{ $t('profile.info.phone') }}
                                </label>
                                <div class="relative rounded-xl shadow-sm">
                                    <div
                                        class="absolute inset-y-0 start-0 ps-3.5 flex items-center pointer-events-none text-gray-400"
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
                                                d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"
                                            />
                                        </svg>
                                    </div>
                                    <input
                                        type="tel"
                                        :value="user.phone"
                                        disabled
                                        dir="ltr"
                                        class="w-full ps-10 rounded-xl border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/50 text-gray-500 dark:text-gray-400 font-mono text-sm cursor-not-allowed"
                                    />
                                </div>
                                <p class="mt-1.5 text-xs text-gray-400 dark:text-gray-500">
                                    {{ $t('profile.info.phone_hint') }}
                                </p>
                            </div>

                            <div class="pt-2">
                                <PrimaryButton
                                    type="submit"
                                    :disabled="profileForm.processing"
                                    :loading="profileForm.processing"
                                >
                                    {{ $t('profile.info.save_changes') }}
                                </PrimaryButton>
                            </div>
                        </form>
                    </div>

                    <!-- Tab 2: Preferences -->
                    <div v-if="activeTab === 'preferences'" class="space-y-8">
                        <div class="border-b border-gray-100 dark:border-gray-700 pb-5">
                            <h3 class="text-lg font-bold text-gray-900 dark:text-white">
                                {{ $t('profile.preferences.title') }}
                            </h3>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                {{ $t('profile.preferences.subtitle') }}
                            </p>
                        </div>

                        <form @submit.prevent="savePreferences" class="space-y-8 max-w-2xl">
                            <!-- Navigation Layout Selection -->
                            <div
                                class="space-y-3 pb-6 border-b border-gray-100 dark:border-gray-700"
                            >
                                <div>
                                    <h4 class="text-sm font-bold text-gray-900 dark:text-white">
                                        {{ $t('profile.preferences.layout') }}
                                    </h4>
                                    <p class="text-xs text-content-secondary mt-0.5">
                                        {{ $t('profile.preferences.layout_hint') }}
                                    </p>
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-1">
                                    <!-- Side Nav Card -->
                                    <SelectableCard
                                        type="radio"
                                        name="preferencesForm.layout"
                                        v-model="preferencesForm.layout"
                                        value="side"
                                    >
                                        <div class="flex items-center gap-3.5">
                                            <div
                                                :class="[
                                                    'w-10 h-10 rounded-xl flex items-center justify-center shrink-0 transition-colors',
                                                    preferencesForm.layout === 'side'
                                                        ? 'bg-action-primary text-action-on shadow-sm'
                                                        : 'bg-surface-muted text-text-secondary',
                                                ]"
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
                                                        d="M4 6h16M4 12h16M4 18h7"
                                                    />
                                                </svg>
                                            </div>
                                            <div>
                                                <span
                                                    class="block text-sm font-bold text-text-primary"
                                                >
                                                    {{ $t('profile.preferences.layout_side') }}
                                                </span>
                                                <span class="block text-xs text-text-secondary">
                                                    {{ $t('profile.preferences.layout_side_desc') }}
                                                </span>
                                            </div>
                                        </div>
                                    </SelectableCard>

                                    <!-- Top Nav Card -->
                                    <SelectableCard
                                        type="radio"
                                        name="preferencesForm.layout"
                                        v-model="preferencesForm.layout"
                                        value="top"
                                    >
                                        <div class="flex items-center gap-3.5">
                                            <div
                                                :class="[
                                                    'w-10 h-10 rounded-xl flex items-center justify-center shrink-0 transition-colors',
                                                    preferencesForm.layout === 'top'
                                                        ? 'bg-action-primary text-action-on shadow-sm'
                                                        : 'bg-surface-muted text-text-secondary',
                                                ]"
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
                                                        d="M4 6h16M4 10h16M4 14h16M4 18h16"
                                                    />
                                                </svg>
                                            </div>
                                            <div>
                                                <span
                                                    class="block text-sm font-bold text-text-primary"
                                                >
                                                    {{ $t('profile.preferences.layout_top') }}
                                                </span>
                                                <span class="block text-xs text-text-secondary">
                                                    {{ $t('profile.preferences.layout_top_desc') }}
                                                </span>
                                            </div>
                                        </div>
                                    </SelectableCard>
                                </div>
                            </div>

                            <!-- WhatsApp Settings -->
                            <div
                                class="space-y-4 pb-6 border-b border-gray-100 dark:border-gray-700"
                            >
                                <div>
                                    <h4
                                        class="text-sm font-bold text-gray-900 dark:text-white flex items-center gap-2"
                                    >
                                        <svg
                                            class="w-4 h-4 text-emerald-500"
                                            fill="currentColor"
                                            viewBox="0 0 24 24"
                                        >
                                            <path
                                                d="M12.031 6.172c-3.181 0-5.767 2.586-5.768 5.766-.001 1.298.38 2.27 1.019 3.287l-.582 2.128 2.182-.573c.978.58 1.911.928 3.145.929 3.178 0 5.767-2.587 5.768-5.766.001-3.187-2.575-5.771-5.764-5.771zm3.392 8.244c-.144.405-.837.774-1.17.824-.299.045-.677.063-1.092-.069-.252-.08-.575-.187-.988-.365-1.739-.751-2.874-2.502-2.961-2.617-.087-.116-.708-.94-.708-1.793s.448-1.273.607-1.446c.159-.173.346-.217.462-.217l.332.006c.106.005.249-.04.39.298.144.347.491 1.2.534 1.287.043.087.072.188.014.304-.058.116-.087.188-.173.289l-.26.304c-.087.086-.177.18-.076.354.101.174.449.741.964 1.201.662.591 1.221.774 1.394.86.173.086.275.072.376-.043.101-.116.433-.506.549-.68.116-.173.231-.144.39-.086s1.011.477 1.184.564.289.13.332.202c.045.072.045.419-.1.824z"
                                            />
                                        </svg>
                                        {{ $t('profile.preferences.whatsapp_section') }}
                                    </h4>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                        {{ $t('profile.preferences.whatsapp_open_via') }}
                                    </p>
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                                    <SelectableCard
                                        type="radio"
                                        name="preferencesForm.open_whatsapp"
                                        v-model="preferencesForm.open_whatsapp"
                                        value="app"
                                    >
                                        <div class="flex items-center gap-3">
                                            <div
                                                :class="[
                                                    'w-8 h-8 rounded-lg flex items-center justify-center shrink-0 transition-colors',
                                                    preferencesForm.open_whatsapp === 'app'
                                                        ? 'bg-success text-white'
                                                        : 'bg-surface-muted text-text-secondary',
                                                ]"
                                            >
                                                <svg
                                                    class="w-4 h-4"
                                                    fill="currentColor"
                                                    viewBox="0 0 24 24"
                                                >
                                                    <path
                                                        d="M12.031 6.172c-3.181 0-5.767 2.586-5.768 5.766-.001 1.298.38 2.27 1.019 3.287l-.582 2.128 2.182-.573c.978.58 1.911.928 3.145.929 3.178 0 5.767-2.587 5.768-5.766.001-3.187-2.575-5.771-5.764-5.771zm3.392 8.244c-.144.405-.837.774-1.17.824-.299.045-.677.063-1.092-.069-.252-.08-.575-.187-.988-.365-1.739-.751-2.874-2.502-2.961-2.617-.087-.116-.708-.94-.708-1.793s.448-1.273.607-1.446c.159-.173.346-.217.462-.217l.332.006c.106.005.249-.04.39.298.144.347.491 1.2.534 1.287.043.087.072.188.014.304-.058.116-.087.188-.173.289l-.26.304c-.087.086-.177.18-.076.354.101.174.449.741.964 1.201.662.591 1.221.774 1.394.86.173.086.275.072.376-.043.101-.116.433-.506.549-.68.116-.173.231-.144.39-.086s1.011.477 1.184.564.289.13.332.202c.045.072.045.419-.1.824z"
                                                    />
                                                </svg>
                                            </div>
                                            <span class="text-xs font-bold text-text-primary">
                                                {{ $t('profile.preferences.whatsapp_app') }}
                                            </span>
                                        </div>
                                    </SelectableCard>

                                    <SelectableCard
                                        type="radio"
                                        name="preferencesForm.open_whatsapp"
                                        v-model="preferencesForm.open_whatsapp"
                                        value="web"
                                    >
                                        <div class="flex items-center gap-3">
                                            <div
                                                :class="[
                                                    'w-8 h-8 rounded-lg flex items-center justify-center shrink-0 transition-colors',
                                                    preferencesForm.open_whatsapp === 'web'
                                                        ? 'bg-success text-white'
                                                        : 'bg-surface-muted text-text-secondary',
                                                ]"
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
                                                        d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"
                                                    />
                                                </svg>
                                            </div>
                                            <span class="text-xs font-bold text-text-primary">
                                                {{ $t('profile.preferences.whatsapp_web') }}
                                            </span>
                                        </div>
                                    </SelectableCard>
                                </div>

                                <!-- WhatsApp Sharing Confirmation Mode -->
                                <div class="pt-2">
                                    <p class="text-xs font-semibold text-text-secondary mb-2">
                                        {{ $t('profile.preferences.whatsapp_sharing') }}
                                    </p>
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                        <SelectableCard
                                            type="radio"
                                            name="preferencesForm.whatsapp_sharing_preference"
                                            v-model="preferencesForm.whatsapp_sharing_preference"
                                            value="form"
                                        >
                                            <span class="text-xs font-medium text-text-primary">
                                                {{ $t('profile.preferences.whatsapp_share_form') }}
                                            </span>
                                        </SelectableCard>

                                        <SelectableCard
                                            type="radio"
                                            name="preferencesForm.whatsapp_sharing_preference"
                                            v-model="preferencesForm.whatsapp_sharing_preference"
                                            value="direct"
                                        >
                                            <span class="text-xs font-medium text-text-primary">
                                                {{
                                                    $t('profile.preferences.whatsapp_share_direct')
                                                }}
                                            </span>
                                        </SelectableCard>
                                    </div>
                                </div>
                            </div>

                            <!-- Floating Buttons with iOS Toggle Switches -->
                            <div
                                class="space-y-4 pb-6 border-b border-gray-100 dark:border-gray-700"
                            >
                                <div>
                                    <h4 class="text-sm font-bold text-gray-900 dark:text-white">
                                        {{ $t('profile.preferences.floating_section') }}
                                    </h4>
                                    <p class="text-xs text-content-secondary mt-0.5">
                                        {{ $t('profile.preferences.floating_hint') }}
                                    </p>
                                </div>

                                <div class="space-y-3">
                                    <div
                                        class="flex items-center justify-between p-3.5 rounded-2xl bg-gray-50/80 dark:bg-gray-800/50 border border-gray-100 dark:border-gray-700"
                                    >
                                        <div class="flex items-center gap-3">
                                            <div
                                                class="w-8 h-8 rounded-lg bg-selection-bg text-action-primary flex items-center justify-center shrink-0"
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
                                                        d="M12 4v16m8-8H4"
                                                    />
                                                </svg>
                                            </div>
                                            <span
                                                class="text-xs font-semibold text-gray-800 dark:text-gray-200"
                                            >
                                                {{ $t('profile.preferences.hide_floating_button') }}
                                            </span>
                                        </div>
                                        <AppToggle v-model="preferencesForm.hide_floating_button" />
                                    </div>

                                    <div
                                        class="flex items-center justify-between p-3.5 rounded-2xl bg-gray-50/80 dark:bg-gray-800/50 border border-gray-100 dark:border-gray-700"
                                    >
                                        <div class="flex items-center gap-3">
                                            <div
                                                class="w-8 h-8 rounded-lg bg-emerald-100 dark:bg-emerald-900/40 text-emerald-600 dark:text-emerald-400 flex items-center justify-center shrink-0"
                                            >
                                                <svg
                                                    class="w-4 h-4"
                                                    fill="currentColor"
                                                    viewBox="0 0 24 24"
                                                >
                                                    <path
                                                        d="M12.031 6.172c-3.181 0-5.767 2.586-5.768 5.766-.001 1.298.38 2.27 1.019 3.287l-.582 2.128 2.182-.573c.978.58 1.911.928 3.145.929 3.178 0 5.767-2.587 5.768-5.766.001-3.187-2.575-5.771-5.764-5.771zm3.392 8.244c-.144.405-.837.774-1.17.824-.299.045-.677.063-1.092-.069-.252-.08-.575-.187-.988-.365-1.739-.751-2.874-2.502-2.961-2.617-.087-.116-.708-.94-.708-1.793s.448-1.273.607-1.446c.159-.173.346-.217.462-.217l.332.006c.106.005.249-.04.39.298.144.347.491 1.2.534 1.287.043.087.072.188.014.304-.058.116-.087.188-.173.289l-.26.304c-.087.086-.177.18-.076.354.101.174.449.741.964 1.201.662.591 1.221.774 1.394.86.173.086.275.072.376-.043.101-.116.433-.506.549-.68.116-.173.231-.144.39-.086s1.011.477 1.184.564.289.13.332.202c.045.072.045.419-.1.824z"
                                                    />
                                                </svg>
                                            </div>
                                            <span
                                                class="text-xs font-semibold text-gray-800 dark:text-gray-200"
                                            >
                                                {{
                                                    $t('profile.preferences.hide_floating_contact')
                                                }}
                                            </span>
                                        </div>
                                        <AppToggle
                                            v-model="preferencesForm.hide_floating_contact"
                                        />
                                    </div>
                                </div>
                            </div>

                            <!-- Save Preferences -->
                            <div>
                                <PrimaryButton
                                    type="submit"
                                    :disabled="preferencesForm.processing"
                                    :loading="preferencesForm.processing"
                                >
                                    {{ $t('profile.preferences.save') }}
                                </PrimaryButton>
                            </div>
                        </form>
                    </div>

                    <!-- Tab 3: Sign-in History -->
                    <div v-if="activeTab === 'sign_in_history'" class="space-y-6">
                        <div class="border-b border-gray-100 dark:border-gray-700 pb-5">
                            <h3 class="text-lg font-bold text-gray-900 dark:text-white">
                                {{ $t('profile.sign_in_history.title') }}
                            </h3>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                {{ $t('profile.sign_in_history.subtitle') }}
                            </p>
                        </div>

                        <!-- Sessions Table -->
                        <DataTable
                            :items="signInHistory || []"
                            :columns="signInColumns"
                            :empty-title="$t('profile.sign_in_history.no_records')"
                        >
                            <template #cell-index="{ item }">
                                <span class="text-content-muted font-mono text-xs">
                                    {{ toEnglish(item.index) }}
                                </span>
                            </template>

                            <template #cell-formatted_date="{ item }">
                                <div
                                    class="flex items-center gap-2 font-semibold whitespace-nowrap text-xs sm:text-sm text-content-primary"
                                >
                                    <svg
                                        class="w-4 h-4 text-content-muted shrink-0"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"
                                        />
                                    </svg>
                                    <span>{{ toEnglish(item.formatted_date) }}</span>
                                </div>
                            </template>

                            <template #cell-ip_address="{ item }">
                                <span
                                    class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-mono font-medium bg-surface-secondary text-content-secondary"
                                >
                                    {{ item.ip_address }}
                                </span>
                            </template>

                            <template #cell-device="{ item }">
                                <div
                                    class="flex items-center gap-2 text-xs sm:text-sm text-content-secondary"
                                >
                                    <svg
                                        class="w-4 h-4 text-action-primary shrink-0"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"
                                        />
                                    </svg>
                                    <span>{{ item.device }}</span>
                                </div>
                            </template>

                            <template #cell-diff_for_humans="{ item }">
                                <span
                                    class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-medium bg-success-soft text-success"
                                >
                                    <span class="w-1.5 h-1.5 rounded-full bg-success"></span>
                                    {{ item.diff_for_humans }}
                                </span>
                            </template>
                        </DataTable>
                    </div>

                    <!-- Tab 4: Security (2FA) -->
                    <div v-if="activeTab === 'security'" class="space-y-6">
                        <div
                            class="border-b border-gray-100 dark:border-gray-700 pb-5 flex items-center justify-between"
                        >
                            <div>
                                <h3 class="text-lg font-bold text-gray-900 dark:text-white">
                                    {{ $t('system.tenants.show.two_factor') }}
                                </h3>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                    {{ $t('security.add_extra_layer') }}
                                </p>
                            </div>
                            <div
                                v-if="isEnforced"
                                class="px-3 py-1 bg-amber-50 dark:bg-amber-950/40 text-amber-700 dark:text-amber-300 border border-amber-200/60 dark:border-amber-800/50 rounded-xl text-xs font-bold"
                            >
                                {{ $t('common.required_label') }}
                            </div>
                        </div>

                        <!-- 2FA Not Enabled -->
                        <div v-if="!isEnabled" class="space-y-6 max-w-xl">
                            <div
                                class="flex items-center gap-4 p-4 bg-amber-50/60 dark:bg-amber-950/20 rounded-2xl border border-amber-200/70 dark:border-amber-800/50"
                            >
                                <div
                                    class="w-10 h-10 rounded-xl bg-amber-100 dark:bg-amber-900/40 flex items-center justify-center shrink-0"
                                >
                                    <svg
                                        class="w-5 h-5 text-amber-600 dark:text-amber-400"
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
                                    <h4 class="text-sm font-bold text-gray-900 dark:text-white">
                                        {{ $t('security.two_factor_disabled') }}
                                    </h4>
                                    <p class="text-xs text-gray-600 dark:text-gray-400 mt-0.5">
                                        {{ $t('security.enable_to_protect') }}
                                    </p>
                                </div>
                            </div>

                            <!-- Step 1: Select Method -->
                            <div v-if="!codeSent" class="space-y-4">
                                <div class="space-y-3">
                                    <SelectableCard
                                        type="radio"
                                        name="selectedMethod"
                                        v-model="selectedMethod"
                                        value="email"
                                    >
                                        <span class="block text-sm font-bold text-text-primary">
                                            {{ $t('center_settings.contact.email') }}
                                        </span>
                                        <span class="block text-xs text-text-secondary">
                                            {{ user.email }}
                                        </span>
                                    </SelectableCard>

                                    <SelectableCard
                                        v-if="smsEnabled && user.phone"
                                        type="radio"
                                        name="selectedMethod"
                                        v-model="selectedMethod"
                                        value="sms"
                                    >
                                        <span class="block text-sm font-bold text-text-primary">
                                            {{ $t('security.text_messages_sms') }}
                                        </span>
                                        <span
                                            class="block text-xs text-text-secondary font-mono"
                                            dir="ltr"
                                        >
                                            {{ user.phone }}
                                        </span>
                                    </SelectableCard>
                                </div>

                                <PrimaryButton
                                    @click="sendCode"
                                    :disabled="sendCodeForm.processing"
                                    :loading="sendCodeForm.processing"
                                >
                                    {{ $t('security.send_verification_code') }}
                                </PrimaryButton>
                            </div>

                            <!-- Step 2: Enter Code -->
                            <div v-else class="space-y-4">
                                <p class="text-sm text-gray-600 dark:text-gray-300">
                                    {{ $t('security.enter_code_sent_to_address') }}
                                    <strong class="text-action-primary">
                                        {{
                                            selectedMethod === 'sms'
                                                ? $t('security.your_phone')
                                                : $t('security.your_email')
                                        }}
                                    </strong>
                                </p>
                                <form @submit.prevent="enable2FA" class="flex items-start gap-3">
                                    <div>
                                        <input
                                            type="text"
                                            v-model="codeForm.code"
                                            maxlength="6"
                                            placeholder="000000"
                                            class="w-40 text-center text-2xl tracking-widest rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white font-mono focus:ring-2 focus:ring-focus-ring"
                                            dir="ltr"
                                            inputmode="numeric"
                                            autofocus
                                        />
                                        <p
                                            v-if="codeForm.errors.code"
                                            class="text-red-500 text-xs mt-1.5 font-medium"
                                        >
                                            {{ codeForm.errors.code }}
                                        </p>
                                    </div>
                                    <button
                                        type="submit"
                                        :disabled="
                                            codeForm.processing || codeForm.code.length !== 6
                                        "
                                        class="px-6 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl font-bold text-sm h-[46px] transition-colors disabled:opacity-50"
                                    >
                                        {{ $t('common.activate') }}
                                    </button>
                                </form>
                                <button
                                    @click="sendCode"
                                    :disabled="sendCodeForm.processing"
                                    class="text-xs text-action-primary hover:text-selection-text font-semibold"
                                >
                                    {{ $t('customer_portal.verify.resend') }}
                                </button>
                            </div>
                        </div>

                        <!-- 2FA Enabled State -->
                        <div v-else class="space-y-6 max-w-2xl">
                            <div
                                class="flex items-center gap-4 p-4 bg-emerald-50/60 dark:bg-emerald-950/20 rounded-2xl border border-emerald-200/70 dark:border-emerald-800/50"
                            >
                                <div
                                    class="w-10 h-10 rounded-xl bg-emerald-100 dark:bg-emerald-900/40 flex items-center justify-center shrink-0"
                                >
                                    <svg
                                        class="w-5 h-5 text-emerald-600 dark:text-emerald-400"
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
                                <div>
                                    <h4 class="text-sm font-bold text-gray-900 dark:text-white">
                                        {{ $t('security.two_factor_enabled') }}
                                    </h4>
                                    <p class="text-xs text-gray-600 dark:text-gray-400 mt-0.5">
                                        {{ $t('security.code_sent_to_address') }}
                                        {{
                                            currentMethod === 'sms'
                                                ? $t('security.your_phone')
                                                : $t('security.your_email')
                                        }}
                                        {{ $t('security.when_signing_in') }}
                                    </p>
                                </div>
                            </div>

                            <!-- Recovery Codes -->
                            <div
                                v-if="recoveryCodes"
                                class="p-5 bg-gray-50/80 dark:bg-gray-800/50 rounded-2xl border border-gray-200 dark:border-gray-700"
                            >
                                <div class="flex items-center justify-between mb-3">
                                    <h4 class="text-sm font-bold text-gray-900 dark:text-white">
                                        {{ $t('security.recovery_codes') }}
                                    </h4>
                                    <button
                                        @click="showRegenModal = true"
                                        class="text-xs text-action-primary hover:text-selection-text font-semibold"
                                    >
                                        {{ $t('security.new_codes') }}
                                    </button>
                                </div>
                                <p class="text-xs text-gray-500 mb-3">
                                    {{ $t('security.keep_codes_safe_short') }}
                                </p>
                                <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                                    <code
                                        v-for="code in recoveryCodes"
                                        :key="code"
                                        class="text-xs bg-white dark:bg-gray-800 px-3 py-2 rounded-xl text-center font-mono border border-gray-200 dark:border-gray-700 shadow-sm"
                                    >
                                        {{ code }}
                                    </code>
                                </div>
                            </div>

                            <!-- Disable Button -->
                            <div v-if="!isEnforced" class="pt-2">
                                <button
                                    @click="showDisableModal = true"
                                    class="px-4 py-2 bg-red-50 hover:bg-red-100 text-red-700 dark:bg-red-950/30 dark:hover:bg-red-950/50 dark:text-red-300 rounded-xl text-xs font-bold border border-red-200 dark:border-red-800/50 transition-colors"
                                >
                                    {{ $t('security.disable_two_factor') }}
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Tab 5: Password -->
                    <div v-if="activeTab === 'password'" class="space-y-6">
                        <div class="border-b border-gray-100 dark:border-gray-700 pb-5">
                            <h3 class="text-lg font-bold text-gray-900 dark:text-white">
                                {{ $t('company_profile.admin_user.change_password') }}
                            </h3>
                            <p class="text-xs text-content-secondary mt-1">
                                {{ $t('profile.preferences.password_hint') }}
                            </p>
                        </div>

                        <form @submit.prevent="updatePassword" class="space-y-6 max-w-xl">
                            <div>
                                <label
                                    class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1.5"
                                >
                                    {{ $t('company_profile.admin_user.current_password') }}
                                </label>
                                <div class="relative rounded-xl shadow-sm">
                                    <div
                                        class="absolute inset-y-0 start-0 ps-3.5 flex items-center pointer-events-none text-gray-400"
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
                                                d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"
                                            />
                                        </svg>
                                    </div>
                                    <input
                                        type="password"
                                        name="current_password"
                                        v-model="passwordForm.current_password"
                                        class="w-full ps-10 rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700/80 dark:text-white focus:ring-2 focus:ring-focus-ring text-sm"
                                        required
                                    />
                                </div>
                                <p
                                    v-if="passwordForm.errors.current_password"
                                    class="mt-1.5 text-xs text-red-500 font-medium"
                                >
                                    {{ passwordForm.errors.current_password }}
                                </p>
                            </div>

                            <div>
                                <label
                                    class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1.5"
                                >
                                    {{ $t('company_profile.admin_user.new_password') }}
                                </label>
                                <div class="relative rounded-xl shadow-sm">
                                    <div
                                        class="absolute inset-y-0 start-0 ps-3.5 flex items-center pointer-events-none text-gray-400"
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
                                                d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"
                                            />
                                        </svg>
                                    </div>
                                    <input
                                        type="password"
                                        name="password"
                                        v-model="passwordForm.password"
                                        class="w-full ps-10 rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700/80 dark:text-white focus:ring-2 focus:ring-focus-ring text-sm"
                                        required
                                    />
                                </div>
                                <p
                                    v-if="passwordForm.errors.password"
                                    class="mt-1.5 text-xs text-red-500 font-medium"
                                >
                                    {{ passwordForm.errors.password }}
                                </p>
                            </div>

                            <div>
                                <label
                                    class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1.5"
                                >
                                    {{ $t('auth.confirm_password') }}
                                </label>
                                <div class="relative rounded-xl shadow-sm">
                                    <div
                                        class="absolute inset-y-0 start-0 ps-3.5 flex items-center pointer-events-none text-gray-400"
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
                                                d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"
                                            />
                                        </svg>
                                    </div>
                                    <input
                                        type="password"
                                        name="password_confirmation"
                                        v-model="passwordForm.password_confirmation"
                                        class="w-full ps-10 rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700/80 dark:text-white focus:ring-2 focus:ring-focus-ring text-sm"
                                        required
                                    />
                                </div>
                            </div>

                            <div class="pt-2">
                                <PrimaryButton
                                    type="submit"
                                    :disabled="passwordForm.processing"
                                    :loading="passwordForm.processing"
                                >
                                    {{ $t('security.update_password') }}
                                </PrimaryButton>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Permissions / Module Actions Modal -->
            <BaseModal :show="showPermissionsModal" @close="showPermissionsModal = false" size="lg">
                <template #title>
                    <div class="flex items-center gap-2">
                        <svg
                            class="w-5 h-5 text-action-primary"
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
                        <span>{{ $t('profile.permissions_modal.title') }}</span>
                    </div>
                </template>

                <div class="space-y-4">
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        {{ $t('profile.permissions_modal.subtitle') }}
                    </p>

                    <div
                        v-if="isFullAccess"
                        class="p-4 bg-emerald-50 dark:bg-emerald-950/20 border border-emerald-200 dark:border-emerald-800 rounded-2xl text-emerald-800 dark:text-emerald-300 text-sm"
                    >
                        {{ $t('profile.permissions_modal.all_permissions') }}
                    </div>

                    <template v-else>
                        <!-- Search Box -->
                        <AppInput
                            v-model="permissionSearchQuery"
                            type="text"
                            :placeholder="$t('profile.permissions_modal.search_placeholder')"
                        />

                        <!-- Permissions Grid -->
                        <div class="max-h-96 overflow-y-auto space-y-2 pr-1">
                            <div
                                v-for="permission in filteredPermissions"
                                :key="permission.id || permission.name"
                                class="flex items-center gap-2.5 p-3 rounded-xl bg-gray-50 dark:bg-gray-800 border border-gray-100 dark:border-gray-700 text-sm"
                            >
                                <svg
                                    class="w-4 h-4 text-emerald-600 shrink-0"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M5 13l4 4L19 7"
                                    />
                                </svg>
                                <div class="flex-1 min-w-0">
                                    <span
                                        class="font-semibold text-gray-900 dark:text-white text-xs sm:text-sm block truncate"
                                    >
                                        {{ permission.label || permission.name }}
                                    </span>
                                    <span
                                        v-if="
                                            permission.label && permission.name !== permission.label
                                        "
                                        class="text-[11px] text-gray-400 font-mono block truncate"
                                    >
                                        {{ permission.name }}
                                    </span>
                                </div>
                            </div>

                            <div
                                v-if="filteredPermissions.length === 0"
                                class="py-8 text-center text-gray-400 text-xs"
                            >
                                {{ $t('profile.permissions_modal.no_matches') }}
                            </div>
                        </div>
                    </template>
                </div>

                <template #footer>
                    <div class="flex justify-end">
                        <SecondaryButton @click="showPermissionsModal = false">
                            {{ $t('profile.permissions_modal.close') }}
                        </SecondaryButton>
                    </div>
                </template>
            </BaseModal>

            <!-- Disable 2FA Modal -->
            <BaseModal
                :show="showDisableModal"
                :title="$t('security.confirm_disable_two_factor')"
                size="sm"
                @close="showDisableModal = false"
            >
                <form @submit.prevent="disable2FA">
                    <div class="mb-4">
                        <AppInput
                            v-model="disableForm.password"
                            type="password"
                            :label="$t('security.enter_password_to_confirm')"
                            required
                        />
                    </div>
                    <div class="flex gap-3">
                        <DangerButton
                            type="submit"
                            :disabled="disableForm.processing"
                            class="flex-1"
                        >
                            {{ $t('security.deactivate') }}
                        </DangerButton>
                        <SecondaryButton type="button" @click="showDisableModal = false">
                            {{ $t('system.company_subscription.history.confirm_modal.go_back') }}
                        </SecondaryButton>
                    </div>
                </form>
            </BaseModal>

            <!-- Regenerate Codes Modal -->
            <BaseModal
                :show="showRegenModal"
                :title="$t('security.new_recovery_codes')"
                size="sm"
                @close="showRegenModal = false"
            >
                <p class="text-xs text-gray-500 mb-4">
                    {{ $t('security.old_codes_cancelled') }}
                </p>
                <form @submit.prevent="regenerateCodes">
                    <div class="mb-4">
                        <AppInput
                            v-model="regenForm.password"
                            type="password"
                            :label="$t('security.enter_password')"
                            required
                        />
                    </div>
                    <div class="flex gap-3">
                        <PrimaryButton
                            type="submit"
                            :disabled="regenForm.processing"
                            class="flex-1"
                        >
                            {{ $t('security.new_codes') }}
                        </PrimaryButton>
                        <SecondaryButton type="button" @click="showRegenModal = false">
                            {{ $t('common.cancel') }}
                        </SecondaryButton>
                    </div>
                </form>
            </BaseModal>
        </div>
    </AppLayout>
</template>

<style scoped>
.modal-enter-active,
.modal-leave-active {
    transition: opacity 0.2s ease;
}

.modal-enter-from,
.modal-leave-to {
    opacity: 0;
}
</style>
