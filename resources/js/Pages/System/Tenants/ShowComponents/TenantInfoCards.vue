<template>
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main Info Column -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Company Info -->
            <div
                class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-sm border border-gray-200 dark:border-gray-700"
            >
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                    {{ $t('system.tenants.show.company_info') }}
                </h2>
                <dl class="grid grid-cols-2 gap-4">
                    <div>
                        <dt class="text-sm text-gray-500 dark:text-gray-400">
                            {{ $t('system.tenants.trade_name') }}
                        </dt>
                        <dd class="text-gray-900 dark:text-white font-medium">
                            {{ tenant.trade_name || $t('common.na') }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm text-gray-500 dark:text-gray-400">
                            {{ $t('system.tenants.legal_name') }}
                        </dt>
                        <dd class="text-gray-900 dark:text-white font-medium">
                            {{ tenant.legal_name || $t('common.na') }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm text-gray-500 dark:text-gray-400">
                            {{ $t('system.tenants.owner') }}
                        </dt>
                        <dd class="text-gray-900 dark:text-white font-medium">
                            {{ tenant.owner_name || $t('common.na') }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm text-gray-500 dark:text-gray-400">
                            {{ $t('system.tenants.show.cr_number') }}
                        </dt>
                        <dd class="text-gray-900 dark:text-white font-medium" dir="ltr">
                            {{ tenant.cr_number || $t('common.na') }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm text-gray-500 dark:text-gray-400">
                            {{ $t('system.tenants.show.vat_number') }}
                        </dt>
                        <dd class="text-gray-900 dark:text-white font-medium" dir="ltr">
                            {{ tenant.vat_number || $t('common.na') }}
                        </dd>
                    </div>
                </dl>
            </div>

            <!-- Recent Work Orders -->
            <div
                class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-sm border border-gray-200 dark:border-gray-700"
            >
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                    {{ $t('system.tenants.show.recent_work_orders') }}
                </h2>
                <div v-if="recentWorkOrders?.length" class="space-y-3">
                    <div
                        v-for="wo in recentWorkOrders"
                        :key="wo.id"
                        class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg"
                    >
                        <div>
                            <p class="font-medium text-gray-900 dark:text-white">#{{ wo.code }}</p>
                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                {{ wo.vehicle?.customer?.name || $t('common.na') }}
                            </p>
                        </div>
                        <div class="text-end">
                            <span
                                :class="getWorkOrderStatusClass(wo.status)"
                                class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium"
                            >
                                {{ getWorkOrderStatusLabel(wo.status) }}
                            </span>
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                {{ formatDate(wo.created_at) }}
                            </p>
                        </div>
                    </div>
                </div>
                <p v-else class="py-4 text-center text-sm text-gray-500 dark:text-gray-400">
                    {{ $t('system.tenants.show.no_work_orders') }}
                </p>
            </div>

            <!-- Centers -->
            <div
                class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-sm border border-gray-200 dark:border-gray-700"
            >
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                    {{
                        $t('system.tenants.show.centers_count', {
                            count: tenant.centers?.length || 0,
                        })
                    }}
                </h2>
                <div class="space-y-3">
                    <div
                        v-for="center in tenant.centers"
                        :key="center.id"
                        class="p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl"
                    >
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="font-medium text-gray-900 dark:text-white">
                                    {{ center.name }}
                                </p>
                                <p
                                    v-if="center.phone || center.email"
                                    class="text-sm text-gray-500 dark:text-gray-400"
                                >
                                    {{ center.phone || center.email }}
                                </p>
                            </div>
                            <span
                                v-if="center.is_main"
                                class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400"
                            >
                                {{ $t('system.tenants.show.main') }}
                            </span>
                        </div>
                    </div>
                    <p
                        v-if="!tenant.centers?.length"
                        class="py-4 text-center text-sm text-gray-500 dark:text-gray-400"
                    >
                        {{ $t('system.tenants.show.no_centers') }}
                    </p>
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Status Card -->
            <div
                class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-sm border border-gray-200 dark:border-gray-700"
            >
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                    {{ $t('system.tenants.show.status_card') }}
                </h2>
                <dl class="space-y-4">
                    <div>
                        <dt class="text-sm text-gray-500 dark:text-gray-400">
                            {{ $t('system.tenants.show.current_status') }}
                        </dt>
                        <dd class="mt-1">
                            <span
                                :class="getStatusClass(tenant.status)"
                                class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium"
                            >
                                {{ getStatusLabel(tenant.status) }}
                            </span>
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm text-gray-500 dark:text-gray-400">
                            {{ $t('system.tenants.registration_date') }}
                        </dt>
                        <dd class="text-gray-900 dark:text-white font-medium">
                            {{ formatDate(tenant.created_at) }}
                        </dd>
                    </div>
                    <div v-if="tenant.trial_ends_at">
                        <dt class="text-sm text-gray-500 dark:text-gray-400">
                            {{ $t('system.tenants.show.trial_ends') }}
                        </dt>
                        <dd class="text-gray-900 dark:text-white font-medium">
                            {{ formatDate(tenant.trial_ends_at) }}
                        </dd>
                    </div>
                    <div v-if="tenant.suspended_at">
                        <dt class="text-sm text-gray-500 dark:text-gray-400">
                            {{ $t('system.tenants.show.suspended_at') }}
                        </dt>
                        <dd class="font-medium text-red-600 dark:text-red-400">
                            {{ formatDate(tenant.suspended_at) }}
                        </dd>
                    </div>
                    <div v-if="tenant.suspension_reason">
                        <dt class="text-sm text-gray-500 dark:text-gray-400">
                            {{ $t('system.tenants.show.suspension_reason') }}
                        </dt>
                        <dd class="text-red-600 dark:text-red-400">
                            {{ tenant.suspension_reason }}
                        </dd>
                    </div>
                </dl>
            </div>

            <!-- Subscription -->
            <div
                v-if="tenant.subscriptions?.length"
                class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-sm border border-gray-200 dark:border-gray-700"
            >
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                    {{ $t('system.tenants.show.subscription') }}
                </h2>
                <div v-for="sub in tenant.subscriptions" :key="sub.id" class="space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="text-gray-700 dark:text-gray-300">
                            {{ $t('system.tenants.show.plan') }}
                        </span>
                        <span class="font-medium text-indigo-600 dark:text-indigo-400">
                            {{ sub.plan?.name_ar }}
                        </span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-gray-700 dark:text-gray-300">
                            {{ $t('system.tenants.show.billing_cycle') }}
                        </span>
                        <span class="font-medium text-gray-900 dark:text-white">
                            {{ $t(`system.tenants.show.${sub.billing_cycle}`) }}
                        </span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-gray-700 dark:text-gray-300">
                            {{ $t('system.tenants.show.ends_at') }}
                        </span>
                        <span class="font-medium text-gray-900 dark:text-white">
                            {{ formatDate(sub.ends_at) }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Contact -->
            <div
                class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-sm border border-gray-200 dark:border-gray-700"
            >
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                    {{ $t('system.tenants.show.contact') }}
                </h2>
                <dl class="space-y-3">
                    <div>
                        <dt class="text-sm text-gray-500 dark:text-gray-400">
                            {{ $t('system.tenants.phone') }}
                        </dt>
                        <dd class="font-medium text-gray-900 dark:text-white" dir="ltr">
                            {{ tenant.phone || $t('common.na') }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm text-gray-500 dark:text-gray-400">
                            {{ $t('system.tenants.email') }}
                        </dt>
                        <dd class="font-medium text-gray-900 dark:text-white">
                            {{ tenant.email || $t('common.na') }}
                        </dd>
                    </div>
                </dl>
            </div>

            <!-- Security Settings -->
            <div
                class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-sm border border-gray-200 dark:border-gray-700"
            >
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
                        {{ $t('system.tenants.show.security') }}
                    </h2>
                    <button
                        @click="$emit('security')"
                        class="inline-flex items-center gap-1 text-sm font-medium text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-300"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"
                            />
                        </svg>
                        {{ $t('system.actions.edit') }}
                    </button>
                </div>
                <dl class="space-y-3">
                    <div>
                        <dt class="text-sm text-gray-500 dark:text-gray-400">
                            {{ $t('system.tenants.show.two_factor') }}
                        </dt>
                        <dd class="mt-1">
                            <span
                                :class="
                                    tenant.two_factor_enabled
                                        ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400'
                                        : 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-400'
                                "
                                class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium"
                            >
                                {{
                                    $t(
                                        tenant.two_factor_enabled
                                            ? 'system.tenants.show.two_factor_enabled'
                                            : 'system.tenants.show.two_factor_disabled'
                                    )
                                }}
                            </span>
                        </dd>
                    </div>
                    <div v-if="tenant.two_factor_enabled">
                        <dt class="text-sm text-gray-500 dark:text-gray-400">
                            {{ $t('system.tenants.show.enforcement_level') }}
                        </dt>
                        <dd class="mt-1 text-gray-900 dark:text-white font-medium">
                            {{ getEnforcementLabel(tenant.two_factor_enforcement) }}
                        </dd>
                    </div>
                </dl>
            </div>
        </div>
    </div>
</template>

<script setup>
import { useI18n } from 'vue-i18n';

defineProps({
    tenant: { type: Object, required: true },
    recentWorkOrders: { type: Array, default: () => [] },
});

defineEmits(['security']);

const { t, locale } = useI18n();

const formatDate = (date) => {
    if (!date) return t('common.na');
    const loc = locale.value === 'ar' ? 'ar-SA-u-nu-latn' : 'en-US';
    return new Date(date).toLocaleDateString(loc);
};

const getStatusClass = (status) => {
    const classes = {
        active: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400',
        trial: 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
        suspended: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
    };
    return classes[status] || 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-400';
};

const getStatusLabel = (status) => {
    const known = ['active', 'trial', 'suspended'];
    if (known.includes(status)) {
        return t(`system.status.${status}`);
    }
    return status || t('common.na');
};

const getWorkOrderStatusClass = (status) => {
    const classes = {
        open: 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
        in_progress: 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
        completed: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400',
        invoiced: 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400',
    };
    return classes[status] || 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-400';
};

const getWorkOrderStatusLabel = (status) => {
    const labels = {
        open: t('system.work_orders.status.open'),
        in_progress: t('system.work_orders.status.in_progress'),
        completed: t('system.work_orders.status.completed'),
        invoiced: t('system.work_orders.status.invoiced'),
    };
    return labels[status] || status || t('common.na');
};

const getEnforcementLabel = (value) => {
    const key = `system.tenants.show.modals.enforcement_${value}`;
    return t(key) || value || t('common.na');
};
</script>
