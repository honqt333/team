<template>
    <Head :title="dealer.name" />
    <AppLayout :title="dealer.name">
        <div class="space-y-6">
            <!-- Top Header Card -->
            <div
                class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700/80 shadow-sm p-5 sm:p-6"
            >
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div class="flex items-start gap-4">
                        <BackButton :href="route('app.dealers.index')" />
                        <div>
                            <div class="flex flex-wrap items-center gap-2.5">
                                <h1 class="text-xl font-black text-gray-900 dark:text-white">
                                    {{ dealer.name }}
                                </h1>
                                <span
                                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-indigo-100 text-indigo-800 dark:bg-indigo-900/40 dark:text-indigo-300"
                                >
                                    {{ $t(`dealers.types.${dealer.type}`) }}
                                </span>
                                <span
                                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold"
                                    :class="
                                        dealer.is_active
                                            ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300'
                                            : 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300'
                                    "
                                >
                                    {{
                                        dealer.is_active
                                            ? $t('dealers.status_active')
                                            : $t('dealers.status_inactive')
                                    }}
                                </span>
                            </div>
                            <p
                                v-if="dealer.name_en"
                                class="text-xs text-gray-500 dark:text-gray-400 mt-1"
                                dir="ltr"
                            >
                                {{ dealer.name_en }}
                            </p>
                            <div
                                class="flex flex-wrap items-center gap-4 mt-2 text-xs text-gray-600 dark:text-gray-400"
                            >
                                <span v-if="dealer.contact_name">{{ dealer.contact_name }}</span>
                                <span v-if="dealer.phone" class="font-mono" dir="ltr">
                                    {{ dealer.phone }}
                                </span>
                                <span v-if="dealer.tax_number" class="font-mono" dir="ltr">
                                    {{ $t('dealers.fields.tax_number') }}: {{ dealer.tax_number }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center gap-2 shrink-0">
                        <button
                            v-if="can('crm.customers.update') || isAnyAdmin()"
                            type="button"
                            @click="showEditModal = true"
                            class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-bold border border-action-primary/30 bg-selection-bg text-selection-text hover:bg-action-primary hover:text-white transition-colors cursor-pointer"
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
                                    d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"
                                />
                            </svg>
                            <span>{{ $t('common.edit') }}</span>
                        </button>

                        <button
                            v-if="can('crm.customers.delete') || isAnyAdmin()"
                            type="button"
                            @click="showDeleteModal = true"
                            class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-bold border border-rose-200 dark:border-rose-800/50 bg-rose-50 dark:bg-rose-900/20 text-rose-700 dark:text-rose-300 hover:bg-rose-600 hover:text-white transition-colors cursor-pointer"
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
                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                                />
                            </svg>
                            <span>{{ $t('common.delete') }}</span>
                        </button>
                    </div>
                </div>

                <!-- Financial Summary Cards -->
                <div
                    class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mt-6 pt-5 border-t border-gray-100 dark:border-gray-700/70"
                >
                    <div
                        class="rounded-xl bg-amber-50/70 dark:bg-amber-950/20 border border-amber-200/70 dark:border-amber-800/40 p-4"
                    >
                        <span class="text-xs font-semibold text-amber-700 dark:text-amber-300">
                            {{ $t('dealers.show.outstanding_balance') }}
                        </span>
                        <div
                            class="mt-1 text-lg font-black font-mono text-amber-900 dark:text-amber-200"
                        >
                            {{ formatMoney(outstandingBalance) }} {{ $t('common.currency') }}
                        </div>
                    </div>

                    <div
                        class="rounded-xl bg-gray-50 dark:bg-gray-900/50 border border-gray-200 dark:border-gray-700 p-4"
                    >
                        <span class="text-xs font-semibold text-gray-500 dark:text-gray-400">
                            {{ $t('dealers.show.credit_limit') }}
                        </span>
                        <div
                            class="mt-1 text-lg font-black font-mono text-gray-900 dark:text-white"
                        >
                            {{
                                dealer.credit_limit
                                    ? `${formatMoney(dealer.credit_limit)} ${$t('common.currency')}`
                                    : '-'
                            }}
                        </div>
                    </div>

                    <div
                        class="rounded-xl bg-gray-50 dark:bg-gray-900/50 border border-gray-200 dark:border-gray-700 p-4"
                    >
                        <span class="text-xs font-semibold text-gray-500 dark:text-gray-400">
                            {{ $t('dealers.show.payment_terms') }}
                        </span>
                        <div
                            class="mt-1 text-lg font-black font-mono text-gray-900 dark:text-white"
                        >
                            {{ dealer.payment_terms_days ?? 30 }} {{ $t('dealers.show.days_unit') }}
                        </div>
                    </div>

                    <div
                        class="rounded-xl bg-indigo-50/70 dark:bg-indigo-950/20 border border-indigo-200/70 dark:border-indigo-800/40 p-4"
                    >
                        <span class="text-xs font-semibold text-indigo-700 dark:text-indigo-300">
                            {{ $t('dealers.show.vehicles_tab') }} /
                            {{ $t('dealers.show.work_orders_tab') }}
                        </span>
                        <div
                            class="mt-1 text-lg font-black font-mono text-indigo-900 dark:text-indigo-200"
                        >
                            {{ dealer.vehicles?.length || 0 }} /
                            {{ dealer.work_orders?.length || 0 }}
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tabs Section -->
            <div
                class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700/80 shadow-sm overflow-hidden"
            >
                <div class="px-5 pt-4 border-b border-gray-100 dark:border-gray-700">
                    <AppTabs v-model="activeTab" :tabs="tabs" variant="underline" sync-url />
                </div>

                <div class="p-5">
                    <!-- Vehicles Tab -->
                    <div v-if="activeTab === 'vehicles'">
                        <DataTable
                            v-if="dealer.vehicles?.length"
                            :columns="vehicleColumns"
                            :data="dealer.vehicles"
                        >
                            <template #cell-plate="{ row }">
                                <Link :href="`/app/vehicles/${row.id}`" class="hover:opacity-80">
                                    <SaudiPlateDisplay :plate="row.plate_number" size="sm" />
                                </Link>
                            </template>
                            <template #cell-model="{ row }">
                                <span class="text-sm font-medium text-gray-900 dark:text-white">
                                    {{ row.make?.name_ar || row.make_other || '' }}
                                    {{ row.model?.name_ar || row.model_other || '' }}
                                    {{ row.year || '' }}
                                </span>
                            </template>
                            <template #cell-customer="{ row }">
                                <span class="text-xs text-gray-700 dark:text-gray-300">
                                    {{ row.customer?.name || '-' }}
                                </span>
                            </template>
                        </DataTable>
                        <p v-else class="text-center py-8 text-sm text-gray-500 dark:text-gray-400">
                            {{ $t('dealers.show.no_vehicles') }}
                        </p>
                    </div>

                    <!-- Work Orders Tab -->
                    <div v-else-if="activeTab === 'work_orders'">
                        <DataTable
                            v-if="dealer.work_orders?.length"
                            :columns="workOrderColumns"
                            :data="dealer.work_orders"
                        >
                            <template #cell-code="{ row }">
                                <Link
                                    :href="`/app/work-orders/${row.id}`"
                                    class="font-mono font-bold text-action-primary hover:underline"
                                >
                                    {{ row.code }}
                                </Link>
                            </template>
                            <template #cell-customer="{ row }">
                                <span class="text-xs text-gray-800 dark:text-gray-200">
                                    {{ row.customer?.name || '-' }}
                                </span>
                            </template>
                            <template #cell-billing="{ row }">
                                <span
                                    class="inline-flex px-2 py-0.5 rounded-full text-[11px] font-bold"
                                    :class="
                                        row.is_paid_by_dealer
                                            ? 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900/40 dark:text-indigo-300'
                                            : 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300'
                                    "
                                >
                                    {{
                                        row.is_paid_by_dealer
                                            ? $t('dealers.show.billed_to_dealer')
                                            : $t('dealers.show.billed_to_customer')
                                    }}
                                </span>
                            </template>
                            <template #cell-total="{ row }">
                                <span class="font-mono font-bold text-gray-900 dark:text-white">
                                    {{ formatMoney(row.total_incl_tax) }}
                                    {{ $t('common.currency') }}
                                </span>
                            </template>
                            <template #cell-balance="{ row }">
                                <span
                                    class="font-mono font-bold text-amber-600 dark:text-amber-400"
                                >
                                    {{ formatMoney(row.balance) }} {{ $t('common.currency') }}
                                </span>
                            </template>
                        </DataTable>
                        <p v-else class="text-center py-8 text-sm text-gray-500 dark:text-gray-400">
                            {{ $t('dealers.show.no_work_orders') }}
                        </p>
                    </div>

                    <!-- Invoices Tab -->
                    <div v-else-if="activeTab === 'invoices'">
                        <DataTable
                            v-if="dealer.invoices?.length"
                            :columns="invoiceColumns"
                            :data="dealer.invoices"
                        >
                            <template #cell-invoice_number="{ row }">
                                <Link
                                    :href="`/app/invoices/${row.id}`"
                                    class="font-mono font-bold text-action-primary hover:underline"
                                >
                                    {{ row.invoice_number }}
                                </Link>
                            </template>
                            <template #cell-billing="{ row }">
                                <span
                                    class="inline-flex px-2 py-0.5 rounded-full text-[11px] font-bold"
                                    :class="
                                        row.is_paid_by_dealer
                                            ? 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900/40 dark:text-indigo-300'
                                            : 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300'
                                    "
                                >
                                    {{
                                        row.is_paid_by_dealer
                                            ? $t('dealers.show.billed_to_dealer')
                                            : $t('dealers.show.billed_to_customer')
                                    }}
                                </span>
                            </template>
                            <template #cell-total_incl_tax="{ row }">
                                <span class="font-mono font-bold">
                                    {{ formatMoney(row.total_incl_tax) }}
                                    {{ $t('common.currency') }}
                                </span>
                            </template>
                            <template #cell-total_paid="{ row }">
                                <span
                                    class="font-mono font-bold text-emerald-600 dark:text-emerald-400"
                                >
                                    {{ formatMoney(row.total_paid) }} {{ $t('common.currency') }}
                                </span>
                            </template>
                        </DataTable>
                        <p v-else class="text-center py-8 text-sm text-gray-500 dark:text-gray-400">
                            {{ $t('dealers.show.no_invoices') }}
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <DealerFormModal
            :show="showEditModal"
            :dealer="dealer"
            :types="types"
            @close="showEditModal = false"
        />

        <ConfirmModal
            :show="showDeleteModal"
            :title="$t('dealers.show.confirm_delete_title')"
            :message="$t('dealers.show.confirm_delete_message')"
            :confirm-text="$t('common.delete')"
            variant="danger"
            @confirm="executeDelete"
            @close="showDeleteModal = false"
        />
    </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import AppLayout from '@/Layouts/AppLayout.vue';
import BackButton from '@/Components/BackButton.vue';
import AppTabs from '@/Components/Common/AppTabs.vue';
import DataTable from '@/Components/Common/DataTable.vue';
import SaudiPlateDisplay from '@/Components/Vehicles/SaudiPlateDisplay.vue';
import ConfirmModal from '@/Components/ConfirmModal.vue';
import DealerFormModal from '@/Components/Dealers/DealerFormModal.vue';
import { usePermission } from '@/Composables/usePermission';

const { t } = useI18n();
const { can, isAnyAdmin } = usePermission();

const props = defineProps({
    dealer: {
        type: Object,
        required: true,
    },
    types: {
        type: Array,
        default: () => ['insurance', 'fleet', 'rental', 'corporate'],
    },
    outstandingBalance: {
        type: [Number, String],
        default: 0,
    },
});

const activeTab = ref('work_orders');
const showEditModal = ref(false);
const showDeleteModal = ref(false);

const tabs = computed(() => [
    {
        id: 'work_orders',
        label: t('dealers.show.work_orders_tab'),
        count: props.dealer.work_orders?.length || 0,
    },
    {
        id: 'invoices',
        label: t('dealers.show.invoices_tab'),
        count: props.dealer.invoices?.length || 0,
    },
    {
        id: 'vehicles',
        label: t('dealers.show.vehicles_tab'),
        count: props.dealer.vehicles?.length || 0,
    },
]);

const vehicleColumns = computed(() => [
    { key: 'plate', label: t('reports.dealers_report.col_vehicle') },
    { key: 'model', label: t('vehicles.form.model') },
    { key: 'customer', label: t('reports.dealers_report.col_customer') },
]);

const workOrderColumns = computed(() => [
    { key: 'code', label: t('reports.dealers_report.col_code') },
    { key: 'customer', label: t('reports.dealers_report.col_customer') },
    { key: 'billing', label: t('reports.dealers_report.col_billing') },
    { key: 'total', label: t('reports.dealers_report.col_due'), align: 'end' },
    { key: 'balance', label: t('reports.dealers_report.col_remaining'), align: 'end' },
]);

const invoiceColumns = computed(() => [
    { key: 'invoice_number', label: t('reports.dealers_report.col_code') },
    { key: 'billing', label: t('reports.dealers_report.col_billing') },
    { key: 'total_incl_tax', label: t('reports.dealers_report.col_due'), align: 'end' },
    { key: 'total_paid', label: t('reports.dealers_report.col_paid'), align: 'end' },
]);

const executeDelete = () => {
    router.delete(route('app.dealers.destroy', props.dealer.id), {
        onFinish: () => {
            showDeleteModal.value = false;
        },
    });
};

const formatMoney = (val) => {
    return new Intl.NumberFormat('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }).format(Number(val) || 0);
};
</script>
