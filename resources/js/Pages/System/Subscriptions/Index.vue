<template>
    <SystemLayout>
        <div class="space-y-6">
            <!-- Page Header -->
            <PageHeader
                :title="$t('system.subscriptions.title')"
                :subtitle="$t('system.subscriptions.subtitle')"
                :totalCount="subscriptions?.total != null ? toEnglish(subscriptions.total) : null"
                :countLabel="$t('system.subscriptions.title')"
            >
                <template #icon>
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"
                        />
                    </svg>
                </template>

                <template #actions>
                    <button
                        @click="openModal()"
                        class="inline-flex items-center gap-2 px-4 py-2.5 bg-action-primary hover:bg-action-primary-hover text-white rounded-xl text-sm font-semibold transition-all shadow-md hover:shadow-focus-ring-soft active:scale-95"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M12 4v16m8-8H4"
                            />
                        </svg>
                        {{ $t('system.subscriptions.create') }}
                    </button>
                </template>
            </PageHeader>

            <!-- Stats Cards -->
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4">
                <div
                    v-for="stat in statCards"
                    :key="stat.key"
                    class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700 transition-shadow hover:shadow-sm"
                >
                    <div class="flex items-center justify-between">
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">
                            {{ stat.label }}
                        </p>
                        <div
                            :class="stat.iconBg"
                            class="flex items-center justify-center w-9 h-9 rounded-lg"
                        >
                            <svg
                                class="w-5 h-5"
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
                    </div>
                    <p class="mt-2 text-2xl font-bold" :class="stat.valueColor">
                        {{ stat.value }}
                    </p>
                </div>
            </div>

            <!-- Filters -->
            <div
                class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700"
            >
                <div class="flex flex-wrap gap-4">
                    <div class="flex-1 min-w-[200px]">
                        <AppInput
                            v-model="tableFilters.search"
                            type="search"
                            :placeholder="$t('system.subscriptions.search_placeholder')"
                            @enter="applyFilters"
                        />
                    </div>
                    <SearchableSelect
                        v-model="tableFilters.status"
                        :options="statusFilterOptions"
                        option-label="label"
                        option-value="value"
                        :placeholder="$t('system.subscriptions.all_statuses')"
                        compact
                        @update:model-value="applyFilters"
                    />
                    <SearchableSelect
                        v-model="tableFilters.plan_id"
                        :options="planFilterOptions"
                        option-label="label"
                        option-value="value"
                        :placeholder="$t('system.subscriptions.all_plans')"
                        compact
                        @update:model-value="applyFilters"
                    />
                </div>
            </div>

            <!-- Subscriptions Table -->
            <div
                class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden"
            >
                <div class="overflow-x-auto">
                    <DataTable structured class="w-full">
                        <thead class="bg-gray-50 dark:bg-gray-700/50">
                            <tr>
                                <th
                                    class="px-6 py-4 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider align-middle"
                                >
                                    {{ $t('system.subscriptions.tenant_label') }}
                                </th>
                                <th
                                    class="px-6 py-4 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider align-middle"
                                >
                                    {{ $t('system.subscriptions.plan_label') }}
                                </th>
                                <th
                                    class="px-6 py-4 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider align-middle text-center"
                                >
                                    {{ $t('system.subscriptions.billing_cycle') }}
                                </th>
                                <th
                                    class="px-6 py-4 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider align-middle text-center"
                                >
                                    {{ $t('system.tenants.status') }}
                                </th>
                                <th
                                    class="px-6 py-4 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider align-middle"
                                >
                                    {{ $t('system.subscriptions.ends_at') }}
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
                                v-for="sub in subscriptions.data"
                                :key="sub.id"
                                class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors"
                            >
                                <td class="px-6 py-4 align-middle">
                                    <div>
                                        <p class="font-medium text-gray-900 dark:text-white">
                                            {{
                                                sub.tenant?.trade_name ||
                                                sub.tenant?.name ||
                                                $t('common.na')
                                            }}
                                        </p>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">
                                            #{{ sub.tenant?.id }}
                                        </p>
                                    </div>
                                </td>
                                <td class="px-6 py-4 align-middle">
                                    <span
                                        class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium bg-selection-bg text-selection-text"
                                    >
                                        {{ sub.plan?.name_ar || $t('common.na') }}
                                    </span>
                                </td>
                                <td
                                    class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400 align-middle text-center"
                                >
                                    {{ $t('system.subscriptions.yearly') }}
                                </td>
                                <td class="px-6 py-4 align-middle text-center">
                                    <span
                                        :class="getStatusClass(sub.status)"
                                        class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium"
                                    >
                                        {{ getStatusLabel(sub.status) }}
                                    </span>
                                </td>
                                <td
                                    class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400 align-middle"
                                >
                                    {{ formatDate(sub.ends_at) }}
                                </td>
                                <td class="px-6 py-4 align-middle text-center">
                                    <Link
                                        :href="`/system/subscriptions/${sub.id}`"
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
                    v-if="!subscriptions.data?.length"
                    :title="$t('system.subscriptions.no_subscriptions')"
                    class="rounded-none border-0"
                />

                <!-- Pagination -->
                <AppPagination
                    :links="subscriptions.links || []"
                    :from="subscriptions.from"
                    :to="subscriptions.to"
                    :total="subscriptions.total"
                />
            </div>

            <!-- Create Modal -->
            <BaseModal
                :show="showModal"
                :title="$t('system.subscriptions.create_title')"
                size="lg"
                overflow-visible
                @close="closeModal"
            >
                <!-- Form Body -->
                <form @submit.prevent="submit" class="space-y-5">
                    <!-- Tenant Selection with Search -->
                    <div>
                        <label
                            class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"
                        >
                            {{ $t('system.subscriptions.create_modal.tenant_label') }}
                        </label>
                        <div class="relative">
                            <AppInput
                                v-model="tenantSearch"
                                type="text"
                                :placeholder="$t('system.subscriptions.tenant_search_placeholder')"
                                @focus="showTenantDropdown = true"
                            />
                            <div
                                v-if="showTenantDropdown && filteredTenants.length > 0"
                                class="absolute z-10 w-full mt-1 bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg shadow-lg max-h-60 overflow-y-auto"
                            >
                                <div
                                    v-for="tenant in filteredTenants"
                                    :key="tenant.id"
                                    @click="selectTenant(tenant)"
                                    class="px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-600 cursor-pointer flex justify-between items-center"
                                >
                                    <div>
                                        <p class="font-medium text-gray-900 dark:text-white">
                                            {{
                                                tenant.trade_name || tenant.name || $t('common.na')
                                            }}
                                        </p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">
                                            #{{ tenant.id }}
                                        </p>
                                    </div>
                                    <svg
                                        v-if="form.tenant_id === tenant.id"
                                        class="w-5 h-5 text-action-primary"
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
                                </div>
                            </div>
                        </div>
                        <p
                            v-if="form.errors.tenant_id"
                            class="mt-1 text-sm text-red-600 dark:text-red-400"
                        >
                            {{ form.errors.tenant_id }}
                        </p>
                        <p
                            v-if="form.tenant_id"
                            class="mt-1 text-sm text-emerald-600 dark:text-emerald-400 flex items-center gap-1"
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
                                    d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
                                />
                            </svg>
                            {{
                                $t('system.subscriptions.create_modal.selected_tenant', {
                                    name: getSelectedTenantName() || $t('common.na'),
                                })
                            }}
                        </p>
                    </div>

                    <!-- Plan Selection -->
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <SearchableSelect
                                v-model="form.plan_id"
                                :options="planOptions"
                                option-label="label"
                                option-value="value"
                                :label="$t('system.subscriptions.plan_label')"
                                required
                                :error="form.errors.plan_id"
                            />
                        </div>
                        <div>
                            <SearchableSelect
                                v-model="form.billing_cycle"
                                :options="formBillingCycleOptions"
                                option-label="label"
                                option-value="value"
                                :label="$t('system.subscriptions.create_modal.billing_cycle_label')"
                                required
                                :error="form.errors.billing_cycle"
                            />
                        </div>
                    </div>

                    <!-- Price Preview -->
                    <div
                        v-if="selectedPlan"
                        class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-4 border border-gray-200 dark:border-gray-600 flex justify-between items-center"
                    >
                        <span class="font-medium text-gray-700 dark:text-gray-300">
                            {{ $t('system.subscriptions.create_modal.subscription_price') }}
                        </span>
                        <span class="text-xl font-bold text-gray-900 dark:text-white">
                            {{ selectedPlan.price_yearly }}
                            {{ $t('system.subscriptions.create_modal.currency') }}
                        </span>
                    </div>

                    <!-- Settlement & Manual Payment (paid plans) -->
                    <div
                        v-if="
                            selectedPlan &&
                            selectedPlan.slug !== 'trial' &&
                            Number(selectedPlan.price_yearly) > 0
                        "
                        class="space-y-3 pt-2 border-t border-gray-200 dark:border-gray-700"
                    >
                        <SearchableSelect
                            v-model="form.settlement_type"
                            :options="settlementOptions"
                            option-label="label"
                            option-value="value"
                            :label="$t('system.subscriptions.settlement_type')"
                        />
                        <div
                            v-if="form.settlement_type === 'paid_now'"
                            class="grid grid-cols-1 sm:grid-cols-2 gap-3"
                        >
                            <SearchableSelect
                                v-model="form.payment_method"
                                :options="paymentMethodOptions"
                                option-label="label"
                                option-value="value"
                                :label="$t('system.subscriptions.payment_method')"
                            />
                            <AppInput
                                v-model.number="form.paid_amount"
                                type="number"
                                min="0"
                                step="0.01"
                                :label="$t('system.subscriptions.paid_amount')"
                                dir="ltr"
                            />
                            <div class="sm:col-span-2">
                                <AppInput
                                    v-model="form.payment_reference"
                                    type="text"
                                    :label="$t('system.subscriptions.payment_reference')"
                                />
                            </div>
                        </div>
                    </div>

                    <!-- Promo Code -->
                    <AppInput
                        v-model="form.promo_code"
                        type="text"
                        :label="$t('system.subscriptions.promo_code_label')"
                        :placeholder="$t('system.subscriptions.promo_code_placeholder')"
                        :error="form.errors.promo_code"
                        dir="ltr"
                        class="font-mono uppercase"
                    />
                </form>

                <template #footer>
                    <div class="flex justify-end gap-3 w-full">
                        <button
                            @click="closeModal"
                            type="button"
                            class="px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg"
                        >
                            {{ $t('system.actions.cancel') }}
                        </button>
                        <button
                            @click="submit"
                            :disabled="form.processing || !form.tenant_id"
                            class="px-5 py-2 bg-action-primary hover:bg-action-primary-hover text-white rounded-lg font-medium disabled:opacity-50"
                        >
                            {{
                                form.processing
                                    ? $t('system.subscriptions.create_modal.submitting')
                                    : $t('system.subscriptions.create_modal.submit')
                            }}
                        </button>
                    </div>
                </template>
            </BaseModal>
        </div>
    </SystemLayout>
</template>

<script setup>
import DataTable from '@/Components/Common/DataTable.vue';
import { ref, computed, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import { Link, useForm } from '@inertiajs/vue3';
import SystemLayout from '@/Layouts/SystemLayout.vue';
import AppInput from '@/Components/App/AppInput.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import PageHeader from '@/Components/PageHeader.vue';
import BaseModal from '@/Components/BaseModal.vue';
import AppEmptyState from '@/Components/Common/AppEmptyState.vue';
import AppPagination from '@/Components/Common/AppPagination.vue';
import { useNumberFormat } from '@/Composables/useNumberFormat';
import { useServerTable } from '@/Composables/useServerTable';
import { useFormatters } from '@/Composables/useFormatters';

const { t } = useI18n();
const { toEnglish } = useNumberFormat();
const { formatDate } = useFormatters();

const statusFilterOptions = computed(() => [
    { value: 'all', label: t('system.subscriptions.all_statuses') },
    { value: 'active', label: t('system.status.active') },
    { value: 'trial', label: t('system.status.trial') },
    { value: 'pending_payment', label: t('system.subscriptions.pending_payment') },
    { value: 'cancelled', label: t('system.status.cancelled') },
    { value: 'expired', label: t('system.status.expired') },
]);

const planFilterOptions = computed(() => [
    { value: '', label: t('system.subscriptions.all_plans') },
    ...(props.plans || []).map((p) => ({ value: p.id, label: p.name_ar })),
]);

const planOptions = computed(() =>
    (props.plans || []).map((p) => ({ value: p.id, label: p.name_ar }))
);

const formBillingCycleOptions = computed(() => [
    { value: 'yearly', label: t('subscription.yearly_12_months') },
]);

const props = defineProps({
    subscriptions: Object,
    plans: Array,
    filters: Object,
    stats: Object,
    eligibleTenants: Array,
});

// Stat cards: extracted to a computed list so the template doesn't
// repeat the same card 5× with only label/value/color swapped.
const statCards = computed(() => [
    {
        key: 'total',
        label: t('system.subscriptions.stats.total'),
        value: props.stats?.total ?? 0,
        valueColor: 'text-gray-900 dark:text-white',
        iconBg: 'bg-gray-100 dark:bg-gray-700',
        iconColor: 'text-gray-500 dark:text-gray-400',
        iconPath:
            'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2',
    },
    {
        key: 'active',
        label: t('system.status.active'),
        value: props.stats?.active ?? 0,
        valueColor: 'text-emerald-600 dark:text-emerald-400',
        iconBg: 'bg-emerald-100 dark:bg-emerald-900/30',
        iconColor: 'text-emerald-600 dark:text-emerald-400',
        iconPath: 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
    },
    {
        key: 'trial',
        label: t('system.status.trial'),
        value: props.stats?.trial ?? 0,
        valueColor: 'text-amber-600 dark:text-amber-400',
        iconBg: 'bg-amber-100 dark:bg-amber-900/30',
        iconColor: 'text-amber-600 dark:text-amber-400',
        iconPath: 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z',
    },
    {
        key: 'cancelled',
        label: t('system.status.cancelled'),
        value: props.stats?.cancelled ?? 0,
        valueColor: 'text-red-600 dark:text-red-400',
        iconBg: 'bg-red-100 dark:bg-red-900/30',
        iconColor: 'text-red-600 dark:text-red-400',
        iconPath: 'M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z',
    },
    {
        key: 'expired',
        label: t('system.status.expired'),
        value: props.stats?.expired ?? 0,
        valueColor: 'text-gray-500 dark:text-gray-400',
        iconBg: 'bg-gray-100 dark:bg-gray-700',
        iconColor: 'text-gray-500 dark:text-gray-400',
        iconPath: 'M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
    },
]);

const { filters: tableFilters, applyFilters } = useServerTable({
    routeUrl: '/system/subscriptions',
    initialFilters: {
        search: props.filters?.search || '',
        status: props.filters?.status || 'all',
        plan_id: props.filters?.plan_id ? Number(props.filters.plan_id) : '',
    },
});

// Modal Logic
const showModal = ref(false);
const tenantSearch = ref('');
const showTenantDropdown = ref(false);
const form = useForm({
    tenant_id: '',
    plan_id: '',
    billing_cycle: 'yearly',
    promo_code: '',
    settlement_type: 'paid_now',
    payment_method: 'cash',
    paid_amount: 0,
    payment_reference: '',
});

const settlementOptions = computed(() => [
    { value: 'paid_now', label: t('system.subscriptions.settlement_paid_now') },
    { value: 'pending_invoice', label: t('system.subscriptions.settlement_pending_invoice') },
    { value: 'complimentary', label: t('system.subscriptions.settlement_complimentary') },
]);

const paymentMethodOptions = computed(() => [
    { value: 'cash', label: t('system.invoices.methods.cash') },
    { value: 'pos', label: t('system.invoices.methods.pos') },
    { value: 'bank_transfer', label: t('system.invoices.methods.bank_transfer') },
    { value: 'manual', label: t('system.invoices.methods.manual') },
]);

const openModal = () => {
    form.reset();
    form.billing_cycle = 'yearly';
    form.settlement_type = 'paid_now';
    form.payment_method = 'cash';
    form.paid_amount = 0;
    tenantSearch.value = '';
    showTenantDropdown.value = false;
    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
};

// Filter Tenants
const filteredTenants = computed(() => {
    if (!tenantSearch.value) return props.eligibleTenants || [];
    const query = tenantSearch.value.toLowerCase();
    return (props.eligibleTenants || []).filter(
        (tenant) =>
            (tenant.name && tenant.name.toLowerCase().includes(query)) ||
            (tenant.trade_name && tenant.trade_name.toLowerCase().includes(query)) ||
            tenant.id.toString().includes(query)
    );
});

const selectTenant = (tenant) => {
    form.tenant_id = tenant.id;
    tenantSearch.value = tenant.trade_name || tenant.name;
    showTenantDropdown.value = false;
};

const getSelectedTenantName = () => {
    const tenant = (props.eligibleTenants || []).find((t) => t.id === form.tenant_id);
    return tenant ? tenant.trade_name || tenant.name : '';
};

// Plan Logic
const selectedPlan = computed(() => {
    return props.plans.find((p) => p.id === form.plan_id);
});

watch(selectedPlan, (plan) => {
    if (plan) {
        form.paid_amount = Number(plan.price_yearly || 0);
    }
});

const submit = () => {
    form.post('/system/subscriptions', {
        onSuccess: () => closeModal(),
    });
};

const getStatusClass = (status) => {
    const classes = {
        active: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400',
        trial: 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
        trialing: 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
        cancelled: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
        expired: 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400',
        pending: 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
        pending_payment: 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
    };
    return classes[status] || classes.expired;
};

const getStatusLabel = (status) => {
    const labels = {
        active: t('system.status.active'),
        trial: t('system.status.trial'),
        trialing: t('system.status.trial'),
        cancelled: t('system.status.cancelled'),
        expired: t('system.status.expired'),
        pending: t('system.status.pending'),
        pending_payment: t('system.subscriptions.pending_payment'),
    };
    return labels[status] || status || t('common.na');
};
</script>

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
