<template>
    <Head :title="$t('dealers.title')" />
    <AppLayout :title="$t('dealers.title')">
        <div class="space-y-6">
            <PageHeader
                :title="$t('dealers.title')"
                :subtitle="$t('dealers.subtitle')"
                :totalCount="dealers?.total ?? null"
                :countLabel="$t('dealers.total_count')"
            >
                <template #icon>
                    <svg
                        class="w-8 h-8 text-white"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"
                        />
                    </svg>
                </template>

                <template #actions>
                    <div class="flex items-center gap-3">
                        <div
                            class="flex items-center gap-1.5 p-1 bg-surface-muted rounded-xl border border-border"
                        >
                            <Link
                                :href="route('app.reports.dealers')"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold text-text-secondary hover:text-action-primary hover:bg-surface transition-colors"
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
                                        d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                                    />
                                </svg>
                                <span>{{ $t('reports.partners') }}</span>
                            </Link>
                        </div>

                        <button
                            v-if="can('crm.customers.create') || isAnyAdmin()"
                            type="button"
                            @click="openCreateModal"
                            class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-action-primary hover:bg-action-primary-hover text-white text-xs font-bold shadow-lg shadow-action-primary/25 transition-all cursor-pointer"
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
                            <span>{{ $t('dealers.add') }}</span>
                        </button>
                    </div>
                </template>
            </PageHeader>

            <!-- Filters Bar -->
            <div
                class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700/80 shadow-sm p-4"
            >
                <div class="grid grid-cols-1 md:grid-cols-12 gap-3 items-center">
                    <div class="md:col-span-5 relative">
                        <input
                            v-model="filterForm.search"
                            type="text"
                            :placeholder="$t('dealers.search_placeholder')"
                            class="w-full ps-10 pe-4 py-2.5 text-sm rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-focus-ring focus:border-action-primary"
                            @input="debouncedFilter"
                        />
                        <div
                            class="absolute inset-y-0 start-0 flex items-center ps-3 pointer-events-none text-gray-400"
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
                    </div>

                    <div class="md:col-span-3">
                        <SearchableSelect
                            v-model="filterForm.type"
                            :options="typeFilterOptions"
                            option-label="label"
                            option-value="id"
                            :placeholder="$t('dealers.all_types')"
                            teleport
                            @update:modelValue="applyFilters"
                        />
                    </div>

                    <div class="md:col-span-3">
                        <SearchableSelect
                            v-model="filterForm.status"
                            :options="statusFilterOptions"
                            option-label="label"
                            option-value="id"
                            :placeholder="$t('dealers.all_statuses')"
                            teleport
                            @update:modelValue="applyFilters"
                        />
                    </div>

                    <div class="md:col-span-1 flex justify-end">
                        <button
                            v-if="filterForm.search || filterForm.type || filterForm.status"
                            type="button"
                            @click="resetFilters"
                            :title="$t('common.reset_filters')"
                            class="p-2.5 rounded-xl border border-gray-200 dark:border-gray-700 text-gray-500 hover:text-gray-800 dark:hover:text-white hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors cursor-pointer"
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
                </div>
            </div>

            <!-- Data Table -->
            <div v-if="dealers?.data?.length" class="space-y-4">
                <DataTable
                    :columns="columns"
                    :data="dealers"
                    :sort-by="filters.sort || 'id'"
                    :sort-direction="filters.direction || 'desc'"
                    @sort="handleSort"
                >
                    <template #cell-name="{ row }">
                        <div class="flex flex-col">
                            <Link
                                :href="route('app.dealers.show', row.id)"
                                class="font-bold text-gray-900 dark:text-white hover:text-action-primary transition-colors"
                            >
                                {{ row.name }}
                            </Link>
                            <span
                                v-if="row.name_en"
                                class="text-xs text-gray-500 dark:text-gray-400"
                                dir="ltr"
                            >
                                {{ row.name_en }}
                            </span>
                        </div>
                    </template>

                    <template #cell-type="{ row }">
                        <span
                            class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold"
                            :class="getTypeBadgeClass(row.type)"
                        >
                            {{ $t(`dealers.types.${row.type}`) }}
                        </span>
                    </template>

                    <template #cell-contact="{ row }">
                        <div class="flex flex-col text-xs space-y-0.5">
                            <span
                                v-if="row.contact_name"
                                class="font-semibold text-gray-800 dark:text-gray-200"
                            >
                                {{ row.contact_name }}
                            </span>
                            <span
                                v-if="row.phone"
                                class="font-mono text-gray-500 dark:text-gray-400"
                                dir="ltr"
                            >
                                {{ row.phone }}
                            </span>
                        </div>
                    </template>

                    <template #cell-tax_number="{ row }">
                        <span class="font-mono text-xs text-gray-600 dark:text-gray-300" dir="ltr">
                            {{ row.tax_number || '-' }}
                        </span>
                    </template>

                    <template #cell-vehicles_count="{ row }">
                        <span class="font-mono font-bold text-gray-700 dark:text-gray-200">
                            {{ row.vehicles_count ?? 0 }}
                        </span>
                    </template>

                    <template #cell-work_orders_count="{ row }">
                        <span class="font-mono font-bold text-gray-700 dark:text-gray-200">
                            {{ row.work_orders_count ?? 0 }}
                        </span>
                    </template>

                    <template #cell-outstanding_balance="{ row }">
                        <span
                            class="font-mono font-bold"
                            :class="
                                Number(row.outstanding_balance) > 0
                                    ? 'text-amber-600 dark:text-amber-400'
                                    : 'text-emerald-600 dark:text-emerald-400'
                            "
                        >
                            {{ formatMoney(row.outstanding_balance) }} {{ $t('common.currency') }}
                        </span>
                    </template>

                    <template #cell-is_active="{ row }">
                        <AppToggle
                            :model-value="Boolean(row.is_active)"
                            size="sm"
                            color="success"
                            :disabled="!(can('crm.customers.update') || isAnyAdmin())"
                            @update:modelValue="toggleDealerStatus(row)"
                        />
                    </template>

                    <template #cell-actions="{ row }">
                        <div class="flex items-center justify-end gap-1.5">
                            <Link
                                :href="route('app.dealers.show', row.id)"
                                :title="$t('common.view')"
                                class="p-2 rounded-lg text-gray-500 hover:text-action-primary hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
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
                            <button
                                v-if="can('crm.customers.update') || isAnyAdmin()"
                                type="button"
                                @click="openEditModal(row)"
                                :title="$t('common.edit')"
                                class="p-2 rounded-lg text-gray-500 hover:text-indigo-600 hover:bg-indigo-50 dark:hover:bg-indigo-900/30 transition-colors cursor-pointer"
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
                            </button>
                            <button
                                v-if="can('crm.customers.delete') || isAnyAdmin()"
                                type="button"
                                @click="confirmDeleteDealer(row)"
                                :title="$t('common.delete')"
                                class="p-2 rounded-lg text-gray-500 hover:text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-900/30 transition-colors cursor-pointer"
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
                            </button>
                        </div>
                    </template>
                </DataTable>

                <AppPagination
                    :links="dealers.links || []"
                    :from="dealers.from"
                    :to="dealers.to"
                    :total="dealers.total"
                />
            </div>

            <!-- Empty State -->
            <div
                v-else
                class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700/80 p-12 text-center"
            >
                <div
                    class="w-16 h-16 mx-auto rounded-full bg-indigo-50 dark:bg-indigo-950/40 text-indigo-500 flex items-center justify-center mb-4"
                >
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5"
                        />
                    </svg>
                </div>
                <h3 class="text-base font-bold text-gray-900 dark:text-white mb-1">
                    {{ $t('dealers.empty_title') }}
                </h3>
                <p class="text-xs text-gray-500 dark:text-gray-400 max-w-sm mx-auto mb-5">
                    {{ $t('dealers.empty_hint') }}
                </p>
                <button
                    v-if="can('crm.customers.create') || isAnyAdmin()"
                    type="button"
                    @click="openCreateModal"
                    class="px-4 py-2.5 rounded-xl bg-action-primary hover:bg-action-primary-hover text-white text-xs font-bold transition-colors cursor-pointer"
                >
                    {{ $t('dealers.add') }}
                </button>
            </div>
        </div>

        <DealerFormModal
            :show="showFormModal"
            :dealer="selectedDealer"
            :types="types"
            @close="showFormModal = false"
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
import { ref, reactive, computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import DataTable from '@/Components/Common/DataTable.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import AppToggle from '@/Components/Common/AppToggle.vue';
import AppPagination from '@/Components/Common/AppPagination.vue';
import ConfirmModal from '@/Components/ConfirmModal.vue';
import DealerFormModal from '@/Components/Dealers/DealerFormModal.vue';
import { usePermission } from '@/Composables/usePermission';

const { t } = useI18n();
const { can, isAnyAdmin } = usePermission();

const props = defineProps({
    dealers: {
        type: Object,
        required: true,
    },
    types: {
        type: Array,
        default: () => ['insurance', 'fleet', 'rental', 'corporate'],
    },
    filters: {
        type: Object,
        default: () => ({}),
    },
});

const filterForm = reactive({
    search: props.filters.search || '',
    type: props.filters.type || '',
    status: props.filters.status || '',
    sort: props.filters.sort || '',
    direction: props.filters.direction || 'desc',
});

const showFormModal = ref(false);
const selectedDealer = ref(null);
const showDeleteModal = ref(false);
const dealerToDelete = ref(null);

const typeFilterOptions = computed(() => [
    { id: '', label: t('dealers.all_types'), name: t('dealers.all_types') },
    ...props.types.map((typeKey) => ({
        id: typeKey,
        label: t(`dealers.types.${typeKey}`),
        name: t(`dealers.types.${typeKey}`),
    })),
]);

const statusFilterOptions = computed(() => [
    { id: '', label: t('dealers.all_statuses'), name: t('dealers.all_statuses') },
    { id: 'active', label: t('dealers.status_active'), name: t('dealers.status_active') },
    { id: 'inactive', label: t('dealers.status_inactive'), name: t('dealers.status_inactive') },
]);

const columns = computed(() => [
    { key: 'name', label: t('dealers.columns.name'), sortable: true },
    { key: 'type', label: t('dealers.columns.type'), sortable: true },
    { key: 'contact', label: t('dealers.columns.contact') },
    { key: 'tax_number', label: t('dealers.columns.tax_number') },
    { key: 'vehicles_count', label: t('dealers.columns.vehicles_count'), align: 'center' },
    { key: 'work_orders_count', label: t('dealers.columns.work_orders_count'), align: 'center' },
    { key: 'outstanding_balance', label: t('dealers.columns.outstanding_balance'), align: 'end' },
    { key: 'is_active', label: t('dealers.columns.status'), sortable: true, align: 'center' },
    { key: 'actions', label: t('dealers.columns.actions'), align: 'end' },
]);

let searchTimeout = null;
const debouncedFilter = () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => applyFilters(), 300);
};

const applyFilters = () => {
    router.get(
        route('app.dealers.index'),
        {
            search: filterForm.search || undefined,
            type: filterForm.type || undefined,
            status: filterForm.status || undefined,
            sort: filterForm.sort || undefined,
            direction: filterForm.sort ? filterForm.direction : undefined,
        },
        { preserveState: true, preserveScroll: true }
    );
};

const resetFilters = () => {
    filterForm.search = '';
    filterForm.type = '';
    filterForm.status = '';
    filterForm.sort = '';
    filterForm.direction = 'desc';
    applyFilters();
};

const handleSort = ({ key, direction }) => {
    filterForm.sort = key;
    filterForm.direction = direction;
    applyFilters();
};

const openCreateModal = () => {
    selectedDealer.value = null;
    showFormModal.value = true;
};

const openEditModal = (dealer) => {
    selectedDealer.value = dealer;
    showFormModal.value = true;
};

const toggleDealerStatus = (dealer) => {
    router.patch(route('app.dealers.toggle', dealer.id), {}, { preserveScroll: true });
};

const confirmDeleteDealer = (dealer) => {
    dealerToDelete.value = dealer;
    showDeleteModal.value = true;
};

const executeDelete = () => {
    if (!dealerToDelete.value) return;
    router.delete(route('app.dealers.destroy', dealerToDelete.value.id), {
        preserveScroll: true,
        onFinish: () => {
            showDeleteModal.value = false;
            dealerToDelete.value = null;
        },
    });
};

const formatMoney = (val) => {
    return new Intl.NumberFormat('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }).format(Number(val) || 0);
};

const getTypeBadgeClass = (type) => {
    switch (type) {
        case 'insurance':
            return 'bg-sky-100 text-sky-800 dark:bg-sky-900/40 dark:text-sky-300';
        case 'fleet':
            return 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900/40 dark:text-indigo-300';
        case 'rental':
            return 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300';
        default:
            return 'bg-purple-100 text-purple-800 dark:bg-purple-900/40 dark:text-purple-300';
    }
};
</script>
