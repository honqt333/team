<template>
    <AppLayout :title="$t('hr.payroll.title')">
        <div class="space-y-6">
            <!-- Header Section -->
            <PageHeader
                :title="$t('hr.payroll.title')"
                :subtitle="$t('hr.payroll.subtitle')"
                :totalCount="toEnglish(payrollRuns?.total || 0)"
                :countLabel="$t('hr.payroll.title')"
            >
                <template #back>
                    <BackButton :href="route('app.hr.index')" />
                </template>

                <template #icon>
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"
                        />
                    </svg>
                </template>

                <template #actions>
                    <div class="flex items-center gap-3">
                        <!-- Toolbar (View Mode Toggle) -->
                        <div
                            v-if="activeTab === 'payroll'"
                            class="flex items-center gap-1.5 p-1.5 bg-surface-secondary/80 dark:bg-surface-secondary/40 backdrop-blur-md rounded-2xl border border-border-subtle shadow-inner"
                        >
                            <AppViewModeToggle v-model="viewMode" storage-key="payroll_view_mode" />
                        </div>

                        <!-- CTA Button (Anchored Outer Left in RTL) -->
                        <button
                            v-if="can('hr.payroll.create') || isAnyAdmin()"
                            @click="showGenerateModal = true"
                            type="button"
                            class="flex items-center justify-center gap-2 px-5 py-2.5 bg-action-primary hover:bg-action-primary-hover text-white rounded-xl font-bold shadow-xs transition-colors cursor-pointer"
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
                                    stroke-width="2.5"
                                    d="M12 4v16m8-8H4"
                                />
                            </svg>
                            <span class="text-sm">
                                {{ $t('hr.payroll.generate') }}
                            </span>
                        </button>
                    </div>
                </template>

                <template #filters>
                    <!-- Main Tabs (Monthly Salaries vs Other Payments) -->
                    <div class="w-full">
                        <AppTabs
                            :model-value="activeTab"
                            :tabs="mainTabs"
                            variant="underline"
                            @update:model-value="handleMainTabChange"
                        />
                    </div>
                </template>
            </PageHeader>

            <!-- TAB 1: PAYROLL RUNS -->
            <div v-if="activeTab === 'payroll'" class="space-y-6">
                <!-- Filters & Search Bar -->
                <div class="flex flex-col md:flex-row items-center gap-3 no-print">
                    <!-- Status Filter Tabs -->
                    <div class="w-full md:w-auto">
                        <AppTabs
                            :model-value="tableFilters.status"
                            :tabs="statusTabs"
                            variant="pills"
                            size="sm"
                            @update:model-value="handleStatusChange"
                        />
                    </div>

                    <!-- Search Input -->
                    <div class="relative group flex-1 w-full">
                        <div
                            class="absolute inset-y-0 start-0 ps-4 flex items-center pointer-events-none text-content-muted group-focus-within:text-action-primary transition-colors"
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
                                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
                                />
                            </svg>
                        </div>
                        <input
                            v-model="tableFilters.search"
                            type="text"
                            :placeholder="$t('common.search')"
                            class="w-full ps-11 pe-10 py-2.5 bg-surface-primary dark:bg-surface-secondary/50 border border-border-subtle rounded-xl text-sm text-content-primary placeholder-content-muted focus:outline-hidden focus:ring-2 focus:ring-focus-ring focus:border-transparent transition-all shadow-2xs"
                            @keyup.enter="applyFilters"
                        />
                        <button
                            v-if="tableFilters.search"
                            @click="
                                tableFilters.search = '';
                                applyFilters();
                            "
                            type="button"
                            class="absolute inset-y-0 end-0 pe-3 flex items-center text-content-muted hover:text-content-primary transition-colors cursor-pointer"
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
                                    d="M6 18L18 6M6 6l12 12"
                                />
                            </svg>
                        </button>
                    </div>

                    <!-- Reset Filter Button -->
                    <button
                        v-if="hasActiveFilters"
                        @click="handleResetFilters"
                        type="button"
                        class="p-2.5 text-content-secondary hover:text-action-primary bg-surface-primary dark:bg-surface-secondary/50 hover:bg-surface-secondary border border-border-subtle rounded-xl transition-colors cursor-pointer shadow-2xs shrink-0"
                        :title="$t('common.reset_filter')"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"
                            />
                        </svg>
                    </button>
                </div>

                <!-- Empty State -->
                <div v-if="!payrollRuns.data || payrollRuns.data.length === 0" class="py-8">
                    <AppEmptyState
                        :title="$t('hr.payroll.no_payrolls')"
                        :description="$t('hr.payroll.empty_hint')"
                        :action-text="
                            can('hr.payroll.create') || isAnyAdmin()
                                ? $t('hr.payroll.generate')
                                : ''
                        "
                        @action="showGenerateModal = true"
                    />
                </div>

                <!-- GRID VIEW -->
                <div v-else-if="viewMode === 'grid'" class="space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
                        <div
                            v-for="run in payrollRuns.data"
                            :key="run.id"
                            class="bg-surface-primary dark:bg-surface-secondary/40 rounded-2xl border border-border-subtle p-5 shadow-2xs hover:shadow-md hover:border-action-primary/30 transition-all flex flex-col justify-between group relative overflow-hidden"
                        >
                            <!-- Top row: Month/Period + Status -->
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <h3
                                        class="text-base font-bold text-content-primary group-hover:text-action-primary transition-colors"
                                    >
                                        {{ formatPeriod(run.period_start, run.period_end) }}
                                    </h3>
                                    <p
                                        v-if="run.payment_date"
                                        class="text-xs text-content-muted mt-0.5"
                                    >
                                        {{ $t('hr.payroll.payment_date') }}:
                                        {{ formatDate(run.payment_date) }}
                                    </p>
                                </div>
                                <span
                                    :class="[
                                        'px-2.5 py-1 rounded-full text-[11px] font-bold border shadow-2xs shrink-0',
                                        getStatusClass(run.status),
                                    ]"
                                >
                                    {{ $t(`hr.payroll.statuses.${run.status}`) }}
                                </span>
                            </div>

                            <!-- Middle summary box -->
                            <div
                                class="my-4 py-3 px-4 bg-surface-secondary/50 rounded-xl border border-border-subtle flex items-center justify-between"
                            >
                                <div>
                                    <div class="text-[11px] text-content-muted">
                                        {{ $t('hr.payroll.employees_count') }}
                                    </div>
                                    <div
                                        class="text-sm font-bold font-mono text-content-primary mt-0.5"
                                    >
                                        {{ toEnglish(run.items_count || 0) }}
                                        {{ $t('hr.employees.employee') }}
                                    </div>
                                </div>
                                <div class="text-end">
                                    <div class="text-[11px] text-content-muted">
                                        {{ $t('hr.payroll.total_net') }}
                                    </div>
                                    <div class="text-sm font-bold font-mono text-success mt-0.5">
                                        {{ currency(run.items_sum_net_salary || 0) }}
                                    </div>
                                </div>
                            </div>

                            <!-- Bottom row: Creator info + Actions -->
                            <div
                                class="pt-3 border-t border-border-subtle flex items-center justify-between"
                            >
                                <div class="flex items-center gap-2 min-w-0" v-if="run.created_by">
                                    <div
                                        class="w-6 h-6 rounded-full bg-surface-secondary border border-border-subtle flex items-center justify-center text-[10px] font-bold text-content-secondary shrink-0"
                                    >
                                        {{ run.created_by?.name?.charAt(0) }}
                                    </div>
                                    <span class="text-xs text-content-muted truncate">
                                        {{ run.created_by?.name }}
                                    </span>
                                </div>
                                <div v-else class="text-xs text-content-muted">—</div>

                                <div class="flex items-center gap-1.5 shrink-0">
                                    <Link
                                        :href="route('app.hr.payroll.show', run.id)"
                                        class="p-2 text-content-muted hover:text-action-primary hover:bg-surface-secondary rounded-lg transition-colors cursor-pointer"
                                        :title="$t('common.view')"
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
                                    </Link>
                                    <a
                                        :href="route('app.hr.payroll.print', run.id)"
                                        target="_blank"
                                        class="p-2 text-content-muted hover:text-action-primary hover:bg-surface-secondary rounded-lg transition-colors cursor-pointer"
                                        :title="$t('common.print')"
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
                                                d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"
                                            />
                                        </svg>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Grid Pagination -->
                    <AppPagination
                        :links="payrollRuns.links"
                        :from="payrollRuns.from"
                        :to="payrollRuns.to"
                        :total="payrollRuns.total"
                    />
                </div>

                <!-- TABLE VIEW -->
                <div v-else class="space-y-4">
                    <DataTable
                        :data="payrollRuns"
                        :columns="columns"
                        :sort-column="sortColumn"
                        :sort-direction="sortDirection"
                        :loading="isLoading"
                        @sort="toggleSort"
                    >
                        <!-- Period Slot -->
                        <template #cell-period_start="{ item }">
                            <div>
                                <div class="font-bold text-content-primary">
                                    {{ formatPeriod(item.period_start, item.period_end) }}
                                </div>
                                <div class="text-xs text-content-muted" v-if="item.payment_date">
                                    {{ $t('hr.payroll.payment_date') }}:
                                    {{ formatDate(item.payment_date) }}
                                </div>
                            </div>
                        </template>

                        <!-- Employees Count Slot -->
                        <template #cell-items_count="{ item }">
                            <div class="flex items-center justify-center gap-1">
                                <span class="font-bold font-mono text-content-primary">
                                    {{ toEnglish(item.items_count || 0) }}
                                </span>
                                <span class="text-xs text-content-muted">
                                    {{ $t('hr.employees.employee') }}
                                </span>
                            </div>
                        </template>

                        <!-- Total Net Slot -->
                        <template #cell-items_sum_net_salary="{ item }">
                            <span class="font-bold font-mono text-success">
                                {{ currency(item.items_sum_net_salary || 0) }}
                            </span>
                        </template>

                        <!-- Status Slot -->
                        <template #cell-status="{ item }">
                            <span
                                :class="[
                                    'px-2.5 py-1 rounded-full text-[11px] font-bold border shadow-2xs',
                                    getStatusClass(item.status),
                                ]"
                            >
                                {{ $t(`hr.payroll.statuses.${item.status}`) }}
                            </span>
                        </template>

                        <!-- Created By Slot -->
                        <template #cell-created_by="{ item }">
                            <div
                                class="flex items-center justify-center gap-2"
                                v-if="item.created_by"
                            >
                                <div
                                    class="w-7 h-7 rounded-full bg-surface-secondary border border-border-subtle flex items-center justify-center text-[10px] font-bold text-content-secondary shrink-0"
                                >
                                    {{ item.created_by?.name?.charAt(0) }}
                                </div>
                                <span
                                    class="text-xs text-content-secondary font-medium truncate max-w-[120px]"
                                >
                                    {{ item.created_by?.name }}
                                </span>
                            </div>
                            <span v-else class="text-xs text-content-muted">—</span>
                        </template>

                        <!-- Actions Slot -->
                        <template #cell-actions="{ item }">
                            <div class="flex items-center justify-end gap-1.5">
                                <Link
                                    :href="route('app.hr.payroll.show', item.id)"
                                    class="p-2 text-content-muted hover:text-action-primary hover:bg-surface-secondary rounded-lg transition-colors cursor-pointer"
                                    :title="$t('common.view')"
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
                                </Link>
                                <a
                                    :href="route('app.hr.payroll.print', item.id)"
                                    target="_blank"
                                    class="p-2 text-content-muted hover:text-action-primary hover:bg-surface-secondary rounded-lg transition-colors cursor-pointer"
                                    :title="$t('common.print')"
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
                                            d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"
                                        />
                                    </svg>
                                </a>
                            </div>
                        </template>
                    </DataTable>
                </div>
            </div>

            <!-- TAB 2: OTHER PAYMENTS -->
            <div v-if="activeTab === 'other_payments'">
                <OtherPaymentsTab
                    :payments="otherPaymentsData"
                    :employees="employees"
                    :centers="centers"
                    :loading="loadingOtherPayments"
                    :selectedCenter="selectedCenter"
                    @refresh="fetchOtherPayments"
                    @center-change="onCenterChange"
                    @page-update="(data) => (otherPaymentsData = data)"
                />
            </div>
        </div>

        <!-- Generate Modal -->
        <BaseModal
            :show="showGenerateModal"
            @close="showGenerateModal = false"
            :title="$t('hr.payroll.generate')"
        >
            <form @submit.prevent="generatePayroll" class="space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-content-secondary mb-1.5">
                            {{ $t('hr.payroll.period_start') }}
                        </label>
                        <CustomDatePicker v-model="generateForm.period_start" />
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-content-secondary mb-1.5">
                            {{ $t('hr.payroll.period_end') }}
                        </label>
                        <CustomDatePicker v-model="generateForm.period_end" />
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-content-secondary mb-1.5">
                        {{ $t('hr.payroll.payment_date') }}
                    </label>
                    <CustomDatePicker v-model="generateForm.payment_date" />
                </div>
                <div class="flex justify-end gap-3 pt-4 border-t border-border-subtle">
                    <button
                        type="button"
                        @click="showGenerateModal = false"
                        class="px-4 py-2 bg-surface-secondary text-content-secondary hover:text-content-primary rounded-xl text-sm font-semibold transition-colors cursor-pointer"
                    >
                        {{ $t('common.cancel') }}
                    </button>
                    <button
                        type="submit"
                        :disabled="generating"
                        class="px-5 py-2 bg-action-primary hover:bg-action-primary-hover text-white rounded-xl text-sm font-bold shadow-xs transition-colors cursor-pointer disabled:opacity-50"
                    >
                        {{ generating ? $t('common.processing') : $t('hr.payroll.generate') }}
                    </button>
                </div>
            </form>
        </BaseModal>
    </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { Link, router } from '@inertiajs/vue3';
import axios from 'axios';

import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import BackButton from '@/Components/BackButton.vue';
import DataTable from '@/Components/Common/DataTable.vue';
import AppPagination from '@/Components/Common/AppPagination.vue';
import AppEmptyState from '@/Components/Common/AppEmptyState.vue';
import AppViewModeToggle from '@/Components/Common/AppViewModeToggle.vue';
import AppTabs from '@/Components/Common/AppTabs.vue';
import CustomDatePicker from '@/Components/CustomDatePicker.vue';
import BaseModal from '@/Components/BaseModal.vue';
import OtherPaymentsTab from './Tabs/OtherPaymentsTab.vue';

import { useToast } from '@/Composables/useToast';
import { usePermission } from '@/Composables/usePermission';
import { useServerTable } from '@/Composables/useServerTable';
import { useNumberFormat } from '@/Composables/useNumberFormat';

const { t } = useI18n();
const { success } = useToast();
const { can, isAnyAdmin } = usePermission();
const { toEnglish, formatMoney } = useNumberFormat();

const props = defineProps({
    payrollRuns: Object,
    employees: Array,
    centers: Array,
    filters: Object,
});

const activeTab = ref('payroll');
const viewMode = ref('list');
const showGenerateModal = ref(false);
const generating = ref(false);

const otherPaymentsData = ref({});
const loadingOtherPayments = ref(false);
const selectedCenter = ref('');

// Main navigation tabs
const mainTabs = computed(() => [
    {
        id: 'payroll',
        label: t('hr.payroll.monthly_salaries'),
        count: props.payrollRuns?.total,
    },
    {
        id: 'other_payments',
        label: t('hr.financial.other_payments.title'),
    },
]);

function handleMainTabChange(tabId) {
    activeTab.value = tabId;
    if (tabId === 'other_payments') {
        fetchOtherPayments(false);
    }
}

// Status tabs for payroll runs
const statusTabs = computed(() => [
    { id: '', label: t('common.all') },
    { id: 'draft', label: t('hr.payroll.statuses.draft') },
    { id: 'approved', label: t('hr.payroll.statuses.approved') },
    { id: 'paid', label: t('hr.payroll.statuses.paid') },
]);

// Server table composable
const {
    filters: tableFilters,
    sortColumn,
    sortDirection,
    isLoading,
    applyFilters,
    toggleSort,
    resetFilters,
} = useServerTable({
    routeUrl: () => route('app.hr.payroll.index'),
    initialFilters: {
        search: props.filters?.search || '',
        status: props.filters?.status || '',
    },
    initialSort: {
        column: props.filters?.sort || 'period_start',
        direction: props.filters?.direction || 'desc',
    },
});

const hasActiveFilters = computed(() => {
    return !!(tableFilters.search || tableFilters.status);
});

function handleStatusChange(statusId) {
    tableFilters.status = statusId;
    applyFilters();
}

function handleResetFilters() {
    resetFilters();
}

// Table columns
const columns = computed(() => [
    {
        key: 'period_start',
        label: t('hr.payroll.period'),
        sortable: true,
    },
    {
        key: 'items_count',
        label: t('hr.payroll.employees_count'),
        sortable: true,
        align: 'center',
    },
    {
        key: 'items_sum_net_salary',
        label: t('hr.payroll.total_net'),
        sortable: true,
        align: 'center',
    },
    {
        key: 'status',
        label: t('hr.payroll.status'),
        sortable: true,
        align: 'center',
    },
    {
        key: 'created_by',
        label: t('common.created_by'),
        sortable: false,
        align: 'center',
    },
    {
        key: 'actions',
        label: t('common.actions'),
        sortable: false,
        align: 'end',
    },
]);

function onCenterChange(centerId) {
    selectedCenter.value = centerId;
    fetchOtherPayments(true);
}

const generateForm = ref({
    period_start: getFirstDayOfMonth(),
    period_end: getLastDayOfMonth(),
    payment_date: '',
});

async function fetchOtherPayments(force = false) {
    activeTab.value = 'other_payments';
    try {
        if (!force && otherPaymentsData.value.data) {
            return;
        }

        loadingOtherPayments.value = true;
        const response = await axios.get(route('app.hr.payroll.other-payments.index'), {
            params: { center_id: selectedCenter.value },
        });
        otherPaymentsData.value = response.data;
    } catch (e) {
        console.error('[DEBUG] Failed to fetch other payments', e);
    } finally {
        loadingOtherPayments.value = false;
    }
}

function getFirstDayOfMonth() {
    const now = new Date();
    return new Date(now.getFullYear(), now.getMonth(), 1).toISOString().split('T')[0];
}

function getLastDayOfMonth() {
    const now = new Date();
    return new Date(now.getFullYear(), now.getMonth() + 1, 0).toISOString().split('T')[0];
}

function generatePayroll() {
    generating.value = true;
    router.post(route('app.hr.payroll.generate'), generateForm.value, {
        onSuccess: () => {
            showGenerateModal.value = false;
            success(t('hr.payroll.generated_successfully'));
        },
        onFinish: () => {
            generating.value = false;
        },
    });
}

function formatPeriod(start, end) {
    if (!start) return '—';
    const startDate = new Date(start);
    return startDate.toLocaleDateString('ar-SA-u-nu-latn', { month: 'long', year: 'numeric' });
}

function formatDate(dateStr) {
    if (!dateStr) return '—';
    return new Date(dateStr).toLocaleDateString('en-CA');
}

function currency(amount) {
    return formatMoney(amount);
}

function getStatusClass(status) {
    switch (status) {
        case 'draft':
            return 'bg-warning-soft text-warning border-warning/20';
        case 'approved':
            return 'bg-info-soft text-info border-info/20';
        case 'paid':
            return 'bg-success-soft text-success border-success/20';
        default:
            return 'bg-surface-secondary text-content-muted border-border-subtle';
    }
}
</script>
