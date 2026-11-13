<template>
    <SystemLayout>
        <div class="space-y-6">
            <!-- Page Header -->
            <PageHeader
                :title="$t('system.tenants.title')"
                :subtitle="$t('system.tenants.subtitle')"
            >
                <template #icon>
                    <div
                        class="w-10 h-10 rounded-xl bg-action-primary hover:bg-action-primary-hover flex items-center justify-center text-white shadow-md shadow-focus-ring-soft"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"
                            />
                        </svg>
                    </div>
                </template>
                <template #badge>
                    <span
                        v-if="tenants?.total !== undefined"
                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-selection-bg text-selection-text border border-action-primary/25"
                    >
                        {{ tenants.total }}
                    </span>
                </template>
            </PageHeader>

            <!-- Filters -->
            <div
                class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm border border-gray-200 dark:border-gray-700"
            >
                <div class="flex flex-wrap gap-4">
                    <!-- Search -->
                    <div class="flex-1 min-w-[200px]">
                        <AppInput
                            v-model="tableFilters.search"
                            type="search"
                            :placeholder="$t('system.tenants.search_placeholder')"
                            @enter="applyFilters"
                        />
                    </div>

                    <!-- Status Filter -->
                    <SearchableSelect
                        v-model="tableFilters.status"
                        :options="statusOptions"
                        option-label="label"
                        option-value="value"
                        :placeholder="$t('system.tenants.all_statuses')"
                        compact
                        @update:model-value="applyFilters"
                    />

                    <!-- Plan Filter -->
                    <SearchableSelect
                        v-model="tableFilters.plan_id"
                        :options="planOptions"
                        option-label="label"
                        option-value="value"
                        :placeholder="$t('system.tenants.all_packages')"
                        compact
                        @update:model-value="applyFilters"
                    />
                </div>
            </div>

            <!-- Tenants Table -->
            <div
                class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden"
            >
                <div class="overflow-x-auto">
                    <DataTable structured class="w-full">
                        <thead class="bg-gray-50 dark:bg-gray-700/50">
                            <tr>
                                <th
                                    class="px-6 py-4 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider align-middle text-center"
                                >
                                    {{ $t('system.tenants.id') }}
                                </th>
                                <th
                                    class="px-6 py-4 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider align-middle"
                                >
                                    {{ $t('system.tenants.trade_name') }}
                                </th>
                                <th
                                    class="px-6 py-4 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider align-middle"
                                >
                                    {{ $t('system.tenants.current_plan') }}
                                </th>
                                <th
                                    class="px-6 py-4 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider align-middle"
                                >
                                    {{ $t('system.tenants.phone') }}
                                </th>
                                <th
                                    class="px-6 py-4 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider align-middle text-center"
                                >
                                    {{ $t('system.tenants.centers') }}
                                </th>
                                <th
                                    class="px-6 py-4 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider align-middle text-center"
                                >
                                    {{ $t('system.tenants.users') }}
                                </th>
                                <th
                                    class="px-6 py-4 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider align-middle text-center"
                                >
                                    {{ $t('system.tenants.status') }}
                                </th>
                                <th
                                    class="px-6 py-4 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider align-middle"
                                >
                                    {{ $t('system.tenants.registration_date') }}
                                </th>
                                <th
                                    class="px-6 py-4 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider align-middle text-center"
                                >
                                    {{ $t('system.backups.col.actions') }}
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            <tr
                                v-for="tenant in tenants.data"
                                :key="tenant.id"
                                class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors"
                            >
                                <td
                                    class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400 align-middle text-center"
                                >
                                    {{ tenant.id }}
                                </td>
                                <td class="px-6 py-4 align-middle">
                                    <div class="font-medium text-gray-900 dark:text-white">
                                        {{ tenant.trade_name || tenant.name || $t('common.na') }}
                                    </div>
                                    <div
                                        v-if="tenant.legal_name"
                                        class="text-sm text-gray-500 dark:text-gray-400"
                                    >
                                        {{ tenant.legal_name }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 align-middle">
                                    <div v-if="tenant.latest_subscription?.plan" class="space-y-1">
                                        <div
                                            class="flex items-center gap-1.5 font-semibold text-sm text-gray-900 dark:text-white"
                                        >
                                            <span
                                                class="inline-block w-2 h-2 rounded-full"
                                                :class="
                                                    tenant.latest_subscription.status === 'active'
                                                        ? 'bg-emerald-500'
                                                        : ['trial', 'trialing'].includes(
                                                                tenant.latest_subscription.status
                                                            )
                                                          ? 'bg-amber-500'
                                                          : 'bg-gray-400'
                                                "
                                            ></span>
                                            {{
                                                tenant.latest_subscription.plan.name_ar ||
                                                tenant.latest_subscription.plan.name_en
                                            }}
                                        </div>
                                        <div class="text-xs text-gray-500 flex items-center gap-2">
                                            <span>
                                                {{ $t('system.plans.yearly') }}
                                            </span>
                                            <span class="text-gray-300 dark:text-gray-600">•</span>
                                            <span
                                                :class="
                                                    tenant.latest_subscription.status === 'active'
                                                        ? 'text-emerald-600 dark:text-emerald-400'
                                                        : [
                                                                'trial',
                                                                'trialing',
                                                                'pending',
                                                                'pending_payment',
                                                            ].includes(
                                                                tenant.latest_subscription.status
                                                            )
                                                          ? 'text-amber-600 dark:text-amber-400'
                                                          : 'text-rose-500 dark:text-rose-400'
                                                "
                                            >
                                                {{
                                                    getSubscriptionStatusLabel(
                                                        tenant.latest_subscription.status
                                                    )
                                                }}
                                            </span>
                                        </div>
                                    </div>
                                    <span v-else class="text-sm text-gray-400 italic">
                                        {{ $t('system.tenants.no_plan') }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 align-middle">
                                    <div
                                        v-if="tenant.phone"
                                        class="text-sm text-gray-900 dark:text-white"
                                        dir="ltr"
                                    >
                                        {{ tenant.phone }}
                                    </div>
                                    <div
                                        v-if="tenant.email"
                                        class="text-sm text-gray-500 dark:text-gray-400"
                                    >
                                        {{ tenant.email }}
                                    </div>
                                    <span
                                        v-if="!tenant.phone && !tenant.email"
                                        class="text-sm text-gray-400"
                                    >
                                        {{ $t('common.na') }}
                                    </span>
                                </td>
                                <td
                                    class="px-6 py-4 text-sm text-gray-900 dark:text-white align-middle text-center"
                                >
                                    {{ tenant.centers_count }}
                                </td>
                                <td
                                    class="px-6 py-4 text-sm text-gray-900 dark:text-white align-middle text-center"
                                >
                                    {{ tenant.users_count }}
                                </td>
                                <td class="px-6 py-4 align-middle text-center">
                                    <span
                                        :class="getStatusClass(tenant.status)"
                                        class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium"
                                    >
                                        {{ getStatusLabel(tenant.status) }}
                                    </span>
                                </td>
                                <td
                                    class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400 align-middle"
                                >
                                    {{ formatDate(tenant.created_at) }}
                                </td>
                                <td class="px-6 py-4 align-middle text-center">
                                    <Link
                                        :href="`/system/tenants/${tenant.id}`"
                                        class="inline-flex items-center gap-1 px-3 py-1.5 text-sm font-medium text-action-primary hover:text-selection-text hover:bg-selection-bg rounded-lg transition-colors"
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
                                                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"
                                            />
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"
                                            />
                                        </svg>
                                        {{ $t('system.actions.view') }}
                                    </Link>
                                </td>
                            </tr>
                        </tbody>
                    </DataTable>
                </div>

                <!-- Empty State -->
                <AppEmptyState
                    v-if="!tenants.data?.length"
                    :title="$t('system.tenants.no_tenants')"
                    class="rounded-none border-0"
                />

                <!-- Pagination -->
                <AppPagination
                    :links="tenants.links || []"
                    :from="tenants.from"
                    :to="tenants.to"
                    :total="tenants.total"
                />
            </div>
        </div>
    </SystemLayout>
</template>

<script setup>
import DataTable from '@/Components/Common/DataTable.vue';
import AppEmptyState from '@/Components/Common/AppEmptyState.vue';
import AppPagination from '@/Components/Common/AppPagination.vue';
import AppInput from '@/Components/App/AppInput.vue';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { Link } from '@inertiajs/vue3';
import SystemLayout from '@/Layouts/SystemLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import { useServerTable } from '@/Composables/useServerTable';

const { t } = useI18n();

const props = defineProps({
    tenants: Object,
    filters: Object,
    plans: { type: Array, default: () => [] },
});

const { filters: tableFilters, applyFilters } = useServerTable({
    routeUrl: '/system/tenants',
    initialFilters: {
        search: props.filters?.search || '',
        status: props.filters?.status || '',
        plan_id: props.filters?.plan_id ? Number(props.filters.plan_id) : '',
    },
});

const statusOptions = computed(() => [
    { value: '', label: t('system.tenants.all_statuses') },
    { value: 'active', label: t('system.status.active') },
    { value: 'trial', label: t('system.status.trial') },
    { value: 'suspended', label: t('system.status.suspended') },
]);

const planOptions = computed(() => [
    { value: '', label: t('system.subscriptions.all_plans') },
    ...props.plans.map((p) => ({
        value: p.id,
        label: p.name_ar || p.name_en,
    })),
]);

const formatDate = (date) => {
    if (!date) return t('common.na');
    return new Date(date).toLocaleDateString('ar-SA-u-nu-latn');
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
    const known = ['active', 'trial', 'suspended', 'expired', 'cancelled', 'pending'];
    if (known.includes(status)) {
        return t(`system.status.${status}`);
    }
    return status || t('common.na');
};

const getSubscriptionStatusLabel = (status) => {
    if (status === 'trialing') return t('system.status.trial');
    if (status === 'pending_payment') return t('system.subscriptions.pending_payment');
    return getStatusLabel(status);
};
</script>
