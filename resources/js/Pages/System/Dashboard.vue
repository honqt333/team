<template>
    <SystemLayout>
        <div class="space-y-6">
            <!-- Welcome Header -->
            <div
                class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-action-primary via-purple-600 to-pink-600 p-8 shadow-lg"
            >
                <div class="relative">
                    <!-- Decorative blobs -->
                    <div
                        class="absolute -top-10 -right-10 w-32 h-32 bg-white/5 rounded-full blur-2xl pointer-events-none"
                    />
                    <div
                        class="absolute -bottom-8 -left-8 w-24 h-24 bg-pink-400/10 rounded-full blur-2xl pointer-events-none"
                    />
                    <div
                        class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-48 h-16 bg-white/3 rounded-full blur-3xl pointer-events-none"
                    />

                    <!-- Grid pattern overlay -->
                    <div
                        class="absolute inset-0 opacity-5 pointer-events-none"
                        style="
                            background-image:
                                linear-gradient(rgba(255, 255, 255, 0.3) 1px, transparent 1px),
                                linear-gradient(
                                    90deg,
                                    rgba(255, 255, 255, 0.3) 1px,
                                    transparent 1px
                                );
                            background-size: 32px 32px;
                        "
                    />

                    <div
                        class="relative flex flex-col sm:flex-row sm:items-center justify-between gap-4 px-6 py-4"
                    >
                        <!-- Left: Greeting -->
                        <div class="flex items-center gap-4">
                            <!-- Avatar ring -->
                            <div
                                class="hidden sm:flex w-14 h-14 rounded-2xl bg-white/15 ring-2 ring-white/30 items-center justify-center flex-shrink-0 shadow-lg"
                            >
                                <svg
                                    class="w-6 h-6 text-white"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M12 12c2.21 0 4-1.79 4-4h-2c0 1.1.9 2 2 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2c0-1.1.9-2 2-2h2c0 2.21 1.79 4 4 4z"
                                    />
                                </svg>
                            </div>
                            <div>
                                <p class="text-white/80 text-sm font-medium">
                                    {{ $t('system.dashboard.welcome') }}
                                </p>
                                <h1 class="text-white text-2xl font-bold tracking-tight">
                                    {{ $t('system.dashboard.title') }}
                                </h1>
                                <p class="text-white/65 text-xs mt-0.5">
                                    {{ $t('system.dashboard.subtitle_short') }}
                                </p>
                            </div>
                        </div>

                        <!-- Right: Date -->
                        <div class="flex items-center gap-3 flex-shrink-0">
                            <!-- Date badge -->
                            <div
                                class="hidden sm:flex items-center gap-2 bg-white/10 backdrop-blur-sm text-white/80 text-xs font-medium px-3 py-2 rounded-xl border border-white/20"
                            >
                                <svg
                                    class="w-3.5 h-3.5 text-white/80 flex-shrink-0"
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
                                <span class="capitalize">{{ currentDate }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Stats Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <div
                    v-for="stat in statCards"
                    :key="stat.key"
                    class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 p-5 transition-shadow hover:shadow-md"
                >
                    <div class="flex items-center gap-4">
                        <div
                            :class="stat.iconBg"
                            class="w-12 h-12 rounded-xl flex items-center justify-center"
                        >
                            <svg
                                class="w-6 h-6"
                                :class="stat.iconColor"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    :d="stat.iconPath"
                                />
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                {{ stat.label }}
                            </p>
                            <p class="text-2xl font-bold" :class="stat.valueColor">
                                {{ stat.value }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Provider Balances -->
            <div
                class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 p-6"
            >
                <template v-if="integrations?.length">
                    <div class="flex items-center justify-between mb-5">
                        <div class="flex items-center gap-4">
                            <div
                                class="w-12 h-12 rounded-xl bg-gradient-to-br from-blue-500 to-cyan-500 flex items-center justify-center"
                            >
                                <svg
                                    class="w-6 h-6 text-white"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"
                                    />
                                </svg>
                            </div>
                            <div>
                                <h2 class="text-xl font-semibold text-gray-900 dark:text-white">
                                    {{ $t('system.dashboard.provider_balances.title') }}
                                </h2>
                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                    {{ $t('system.dashboard.provider_balances.subtitle') }}
                                </p>
                            </div>
                        </div>
                        <button
                            @click="refreshAllBalances"
                            :disabled="isRefreshing"
                            class="inline-flex items-center gap-2 px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-sm font-medium disabled:opacity-50 transition-colors"
                        >
                            <svg
                                :class="{ 'animate-spin': isRefreshing }"
                                class="w-5 h-5"
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
                            {{ $t('system.dashboard.provider_balances.refresh') }}
                        </button>
                    </div>

                    <div class="divide-y divide-gray-200 dark:divide-gray-700">
                        <div
                            v-for="integration in integrations"
                            :key="integration.id"
                            class="flex items-center py-4"
                            :class="
                                getProviderCardClass(integration.provider) +
                                ' bg-gray-50 dark:bg-gray-900/20'
                            "
                        >
                            <div class="flex items-center gap-4 flex-shrink-0">
                                <div class="w-10 h-10 rounded-xl flex items-center justify-center">
                                    <span class="text-2xl">
                                        {{ getProviderIcon(integration.type) }}
                                    </span>
                                </div>
                                <div>
                                    <p class="font-medium text-gray-900 dark:text-white">
                                        {{ integration.name_ar }}
                                    </p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">
                                        {{
                                            integration.type === 'sms'
                                                ? $t('system.dashboard.provider_balances.sms_label')
                                                : $t(
                                                      'system.dashboard.provider_balances.whatsapp_label'
                                                  )
                                        }}
                                    </p>
                                </div>
                            </div>

                            <div class="flex-1"></div>

                            <div class="flex items-center gap-4 text-end">
                                <div class="flex items-center gap-3">
                                    <p class="text-xs text-gray-500 dark:text-gray-400">
                                        {{
                                            $t(
                                                'system.dashboard.provider_balances.remaining_balance'
                                            )
                                        }}
                                    </p>
                                    <p
                                        class="text-2xl font-bold"
                                        :class="
                                            getBalanceTextClass(providerBalances[integration.id])
                                        "
                                    >
                                        {{
                                            providerBalances[integration.id]?.balance ??
                                            $t('common.na')
                                        }}
                                    </p>
                                </div>
                                <div class="flex items-center gap-2">
                                    <button
                                        @click="fetchProviderBalance(integration)"
                                        :disabled="providerLoading[integration.id]"
                                        class="p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors disabled:opacity-50"
                                    >
                                        <svg
                                            :class="{
                                                'animate-spin': providerLoading[integration.id],
                                            }"
                                            class="w-4 h-4 text-gray-500"
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
                                    </button>
                                    <a
                                        :href="`/system/integrations/${integration.id}`"
                                        class="text-sm text-blue-600 dark:text-blue-400 hover:underline"
                                    >
                                        {{ $t('system.dashboard.provider_balances.settings') }}
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>
                <template v-else>
                    <div class="text-center py-8">
                        <p class="text-gray-500 dark:text-gray-400">
                            {{ $t('system.dashboard.provider_balances.no_providers') }}
                        </p>
                    </div>
                </template>
            </div>

            <!-- Content Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Recent Registrations -->
                <div
                    class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden"
                >
                    <div
                        class="px-6 py-5 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between"
                    >
                        <div>
                            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
                                {{ $t('system.dashboard.recent_registrations') }}
                            </h2>
                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                {{ $t('system.dashboard.last_7_days') }}
                            </p>
                        </div>
                        <Link
                            href="/system/tenants"
                            class="text-sm font-medium text-action-primary hover:text-selection-text flex items-center gap-2 transition-colors"
                        >
                            {{ $t('common.view_all') }}
                            <svg
                                class="w-4 h-4 rotate-180"
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
                        </Link>
                    </div>
                    <div class="divide-y divide-gray-200 dark:divide-gray-700">
                        <div
                            v-for="tenant in recentRegistrations"
                            :key="tenant.id"
                            class="px-6 py-4 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors"
                        >
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-4">
                                    <div
                                        class="w-10 h-10 rounded-lg bg-gray-100 dark:bg-gray-700 flex items-center justify-center text-gray-600 dark:text-gray-300 font-bold text-sm"
                                    >
                                        {{
                                            (tenant.trade_name || tenant.name)
                                                ?.charAt(0)
                                                ?.toUpperCase()
                                        }}
                                    </div>
                                    <div>
                                        <p class="font-medium text-gray-900 dark:text-white">
                                            {{
                                                tenant.trade_name || tenant.name || $t('common.na')
                                            }}
                                        </p>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">
                                            {{ formatDate(tenant.created_at) }}
                                        </p>
                                    </div>
                                </div>
                                <span
                                    :class="getStatusClass(tenant.status)"
                                    class="inline-flex px-3 py-1.5 rounded-full text-xs font-medium"
                                >
                                    {{ getStatusLabel(tenant.status) }}
                                </span>
                            </div>
                        </div>
                        <div
                            v-if="!recentRegistrations?.length"
                            class="px-6 py-8 text-center text-gray-500 dark:text-gray-400"
                        >
                            {{ $t('system.dashboard.no_recent_registrations') }}
                        </div>
                    </div>
                </div>

                <!-- Trial Ending Soon -->
                <div
                    class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden"
                >
                    <div
                        class="px-6 py-5 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between"
                    >
                        <div>
                            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
                                {{ $t('system.dashboard.trial_ending_soon') }}
                            </h2>
                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                {{ $t('system.dashboard.in_7_days') }}
                            </p>
                        </div>
                    </div>
                    <div class="divide-y divide-gray-200 dark:divide-gray-700">
                        <div
                            v-for="tenant in trialEndingSoon"
                            :key="tenant.id"
                            class="px-6 py-4 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors"
                        >
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-4">
                                    <div
                                        class="w-10 h-10 rounded-lg bg-amber-100 dark:bg-amber-900/50 flex items-center justify-center text-amber-600 dark:text-amber-400"
                                    >
                                        ⚡
                                    </div>
                                    <div>
                                        <p class="font-medium text-gray-900 dark:text-white">
                                            {{
                                                tenant.trade_name || tenant.name || $t('common.na')
                                            }}
                                        </p>
                                        <p class="text-sm text-amber-600 dark:text-amber-400">
                                            {{
                                                $t('system.dashboard.trial_ends', {
                                                    date: formatDate(tenant.trial_ends_at),
                                                })
                                            }}
                                        </p>
                                    </div>
                                </div>
                                <Link
                                    :href="`/system/tenants/${tenant.id}`"
                                    class="inline-flex items-center px-4 py-2 rounded-lg bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 text-sm transition-colors"
                                >
                                    {{ $t('system.dashboard.view') }}
                                </Link>
                            </div>
                        </div>
                        <div
                            v-if="!trialEndingSoon?.length"
                            class="px-6 py-8 text-center text-gray-500 dark:text-gray-400"
                        >
                            {{ $t('system.dashboard.no_trial_ending_soon') }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </SystemLayout>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue';
import { useI18n } from 'vue-i18n';
import { Link, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import SystemLayout from '@/Layouts/SystemLayout.vue';
import { useFormatters } from '@/Composables/useFormatters';

const { t } = useI18n();
const { formatDate } = useFormatters();
const page = usePage();

const props = defineProps({
    stats: Object,
    recentRegistrations: Array,
    trialEndingSoon: Array,
    integrations: Array,
});

const currentDate = computed(() => {
    return new Date().toLocaleDateString(page.props.locale === 'en' ? 'en-US' : 'ar-SA', {
        weekday: 'long',
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    });
});

// Balance state
const providerBalances = reactive({});
const providerLoading = reactive({});
const providerErrors = reactive({});
const isRefreshing = ref(false);

// Stat cards: extracted to a computed list so the template doesn't
// repeat the same card 4× with only label/value/icon swapped.
const statCards = computed(() => [
    {
        key: 'total_tenants',
        label: t('system.dashboard.total_tenants'),
        value: props.stats?.total_tenants ?? 0,
        valueColor: 'text-gray-900 dark:text-white',
        iconBg: 'bg-selection-bg',
        iconColor: 'text-action-primary',
        iconPath:
            'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4',
    },
    {
        key: 'active',
        label: t('system.dashboard.active'),
        value: props.stats?.active_tenants ?? 0,
        valueColor: 'text-emerald-600 dark:text-emerald-400',
        iconBg: 'bg-emerald-100 dark:bg-emerald-900/50',
        iconColor: 'text-emerald-600 dark:text-emerald-400',
        iconPath: 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
    },
    {
        key: 'trial',
        label: t('system.dashboard.trial'),
        value: props.stats?.trial_tenants ?? 0,
        valueColor: 'text-amber-600 dark:text-amber-400',
        iconBg: 'bg-amber-100 dark:bg-amber-900/50',
        iconColor: 'text-amber-600 dark:text-amber-400',
        iconPath: 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z',
    },
    {
        key: 'suspended',
        label: t('system.dashboard.suspended'),
        value: props.stats?.suspended_tenants ?? 0,
        valueColor: 'text-red-600 dark:text-red-400',
        iconBg: 'bg-red-100 dark:bg-red-900/50',
        iconColor: 'text-red-600 dark:text-red-400',
        iconPath:
            'M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636',
    },
]);

const getStatusClass = (status) => {
    const classes = {
        active: 'bg-emerald-100 dark:bg-emerald-900/50 text-emerald-700 dark:text-emerald-400',
        trial: 'bg-amber-100 dark:bg-amber-900/50 text-amber-700 dark:text-amber-400',
        suspended: 'bg-red-100 dark:bg-red-900/50 text-red-700 dark:text-red-400',
    };
    return classes[status] || 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-400';
};

const getStatusLabel = (status) => {
    const known = ['active', 'trial', 'suspended'];
    if (known.includes(status)) {
        return t(`system.dashboard.${status}`);
    }
    return status || t('common.na');
};

const getProviderIcon = (type) => {
    return type === 'sms' ? '📱' : '💬';
};

const getProviderCardClass = (provider) => {
    const classes = {
        authentica:
            'bg-gradient-to-br from-emerald-50 to-teal-50 dark:from-emerald-900/20 dark:to-teal-900/20',
        unifonic: 'bg-gradient-to-br from-blue-50 to-action-primary dark:from-blue-900/20',
        twilio: 'bg-gradient-to-br from-red-50 to-pink-50 dark:from-red-900/20 dark:to-pink-900/20',
        whatsapp_cloud:
            'bg-gradient-to-br from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20',
    };
    return classes[provider] || 'bg-gray-50 dark:bg-gray-900/20';
};

const getBalanceTextClass = (balanceData) => {
    if (!balanceData?.balance) return 'text-gray-400';
    const balance = parseFloat(balanceData.balance);
    if (balance <= 10) return 'text-red-600 dark:text-red-400';
    if (balance <= 50) return 'text-amber-600 dark:text-amber-400';
    return 'text-emerald-600 dark:text-emerald-400';
};

const fetchProviderBalance = async (integration) => {
    providerLoading[integration.id] = true;
    providerErrors[integration.id] = null;

    try {
        const response = await axios.get(`/system/integrations/${integration.id}/balance`);
        const data = response.data;

        if (data.success) {
            providerBalances[integration.id] = data;
        } else {
            providerErrors[integration.id] =
                data.message || t('system.dashboard.provider_balances.fetch_failed');
        }
    } catch (error) {
        providerErrors[integration.id] =
            error.response?.data?.message || t('system.dashboard.provider_balances.fetch_error');
    } finally {
        providerLoading[integration.id] = false;
    }
};

const refreshAllBalances = async () => {
    isRefreshing.value = true;

    const promises =
        props.integrations?.map((integration) => fetchProviderBalance(integration)) || [];
    await Promise.all(promises);

    isRefreshing.value = false;
};

// Auto-fetch balances on mount
onMounted(() => {
    if (props.integrations?.length) {
        refreshAllBalances();
    }
});
</script>
