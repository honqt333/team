<script setup>
import { computed, ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

const props = defineProps({
    receipts: { type: Array, default: () => [] },
    statusCounts: { type: Object, default: () => ({}) },
    filter: {
        type: Object,
        default: () => ({ status: 'all', search: '', per_page: 20 }),
    },
});

const locale = computed(() => document.documentElement.lang || 'ar');

// Local state mirrors props.filter so the inputs are controlled.
// On change we re-fetch via Inertia (preserveScroll keeps the
// user's position in the list).
const status = ref(props.filter.status || 'all');
const search = ref(props.filter.search || '');
const perPage = ref(props.filter.per_page || 20);

watch(
    () => props.filter,
    (f) => {
        status.value = f.status || 'all';
        search.value = f.search || '';
        perPage.value = f.per_page || 20;
    },
    { deep: true }
);

// Debounced search — fires 350ms after the user stops typing
let searchTimer = null;
function onSearchInput() {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => applyFilters(), 350);
}

function applyFilters() {
    router.get(
        '/app/settings/billing',
        {
            receipt_status: status.value,
            receipt_search: search.value,
            receipt_per_page: perPage.value,
        },
        { preserveState: true, preserveScroll: true, replace: true }
    );
}

function clearFilters() {
    status.value = 'all';
    search.value = '';
    applyFilters();
}

const formatDate = (dateStr) => {
    if (!dateStr) return t('system.billing.page.em_dash');
    return new Date(dateStr).toLocaleDateString(
        locale.value === 'ar'
            ? t('system.billing.page.locale_ar')
            : t('system.billing.page.locale_en'),
        {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
        }
    );
};

const formatCurrency = (amount) => {
    return new Intl.NumberFormat(t('system.billing.page.locale_en'), {
        maximumFractionDigits: 2,
    }).format(amount || 0);
};

const noPlanLabel = () => t('system.billing.page.no_plan');

const statusConfig = {
    pending: {
        class: 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300',
    },
    approved: {
        class: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300',
    },
    rejected: {
        class: 'bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-300',
    },
};

// Status chips shown above the table. Counts come from the controller
// so the badges reflect the filtered state of the database, not
// just what's currently rendered.
const statusChips = computed(() => [
    { value: 'all', count: props.statusCounts.all || 0 },
    { value: 'pending', count: props.statusCounts.pending || 0 },
    { value: 'approved', count: props.statusCounts.approved || 0 },
    { value: 'rejected', count: props.statusCounts.rejected || 0 },
]);
</script>

<template>
    <div class="space-y-4">
        <!-- Status filter chips with count badges -->
        <div class="flex flex-wrap items-center gap-2">
            <button
                v-for="chip in statusChips"
                :key="chip.value"
                type="button"
                @click="
                    status = chip.value;
                    applyFilters();
                "
                :class="[
                    'inline-flex items-center gap-1.5 rounded-full px-3 py-1.5 text-xs font-semibold transition-colors',
                    status === chip.value
                        ? 'bg-indigo-600 text-white'
                        : 'bg-slate-100 text-slate-600 hover:bg-slate-200 dark:bg-slate-700/50 dark:text-slate-300 dark:hover:bg-slate-700',
                ]"
            >
                {{ t(`system.billing.page.filter.${chip.value}`) }}
                <span
                    :class="[
                        'inline-flex items-center justify-center min-w-[1.25rem] h-5 rounded-full px-1.5 text-[10px] font-bold',
                        status === chip.value
                            ? 'bg-white/20 text-white'
                            : 'bg-slate-200 text-slate-700 dark:bg-slate-600 dark:text-slate-200',
                    ]"
                >
                    {{ chip.count }}
                </span>
            </button>
        </div>

        <!-- Search box + per-page selector -->
        <div class="flex flex-col sm:flex-row gap-3">
            <div class="relative flex-1">
                <svg
                    class="absolute start-3 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-400"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M21 21l-4.35-4.35M11 19a8 8 0 100-16 8 8 0 000 16z"
                    />
                </svg>
                <input
                    type="search"
                    v-model="search"
                    @input="onSearchInput"
                    :placeholder="t('system.billing.page.filter.search_placeholder')"
                    class="w-full rounded-lg border-slate-200 dark:border-slate-600 dark:bg-slate-700 dark:text-white ps-9 pe-3 py-2 text-sm"
                />
            </div>
            <select
                v-model.number="perPage"
                @change="applyFilters"
                class="rounded-lg border-slate-200 dark:border-slate-600 dark:bg-slate-700 dark:text-white px-3 py-2 text-sm"
            >
                <option :value="10">10</option>
                <option :value="20">20</option>
                <option :value="50">50</option>
                <option :value="100">100</option>
            </select>
        </div>

        <!-- Summary line -->
        <p class="text-xs text-slate-500 dark:text-slate-400">
            {{
                t('system.billing.page.filter.showing_n_of_m', {
                    shown: receipts.length,
                    total: statusCounts[status] || 0,
                })
            }}
        </p>

        <!-- Table OR empty state -->
        <div
            v-if="receipts.length"
            class="overflow-hidden rounded-xl border border-slate-200 dark:border-slate-700"
        >
            <table class="w-full text-sm">
                <thead class="bg-slate-50 dark:bg-slate-900/50">
                    <tr>
                        <th
                            class="px-4 py-2.5 text-start text-xs font-bold uppercase tracking-wider text-slate-500"
                        >
                            {{ t('system.billing.page.receipts_table.date') }}
                        </th>
                        <th
                            class="px-4 py-2.5 text-start text-xs font-bold uppercase tracking-wider text-slate-500"
                        >
                            {{ t('system.billing.page.receipts_table.plan') }}
                        </th>
                        <th
                            class="px-4 py-2.5 text-start text-xs font-bold uppercase tracking-wider text-slate-500"
                        >
                            {{ t('system.billing.page.receipts_table.amount') }}
                        </th>
                        <th
                            class="px-4 py-2.5 text-start text-xs font-bold uppercase tracking-wider text-slate-500"
                        >
                            {{ t('system.billing.page.receipts_table.reference') }}
                        </th>
                        <th
                            class="px-4 py-2.5 text-start text-xs font-bold uppercase tracking-wider text-slate-500"
                        >
                            {{ t('system.billing.page.receipts_table.status') }}
                        </th>
                        <th
                            class="px-4 py-2.5 text-end text-xs font-bold uppercase tracking-wider text-slate-500"
                        >
                            {{ t('system.billing.page.receipts_table.actions') }}
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                    <tr
                        v-for="r in receipts"
                        :key="r.id"
                        class="hover:bg-slate-50/50 dark:hover:bg-slate-800/30"
                    >
                        <td
                            class="px-4 py-2.5 font-mono text-xs text-slate-600 dark:text-slate-300"
                        >
                            {{ formatDate(r.created_at) }}
                        </td>
                        <td class="px-4 py-2.5 text-slate-700 dark:text-slate-200">
                            {{
                                locale === 'ar'
                                    ? r.subscription?.plan?.name_ar || noPlanLabel()
                                    : r.subscription?.plan?.name_en || noPlanLabel()
                            }}
                        </td>
                        <td
                            class="px-4 py-2.5 font-mono font-semibold text-slate-900 dark:text-white"
                        >
                            {{ formatCurrency(r.transfer_amount) }}
                            {{ t('system.billing.page.currency') }}
                        </td>
                        <td
                            class="px-4 py-2.5 font-mono text-xs text-slate-600 dark:text-slate-300"
                        >
                            {{ r.transfer_reference }}
                        </td>
                        <td class="px-4 py-2.5">
                            <span
                                :class="[
                                    'inline-flex items-center gap-1 rounded px-2 py-0.5 text-xs font-semibold',
                                    statusConfig[r.status]?.class ||
                                        'bg-slate-100 text-slate-700 dark:bg-slate-700 dark:text-slate-300',
                                ]"
                            >
                                <span class="h-1.5 w-1.5 rounded-full bg-current opacity-70"></span>
                                {{ t(`system.billing.page.receipts_status.${r.status}`, r.status) }}
                            </span>
                        </td>
                        <td class="px-4 py-2.5 text-end">
                            <a
                                v-if="r.receipt_path"
                                :href="`/storage/${r.receipt_path}`"
                                target="_blank"
                                rel="noopener"
                                class="text-xs font-semibold text-indigo-600 hover:text-indigo-700 dark:text-indigo-400 dark:hover:text-indigo-300"
                            >
                                {{ t('system.billing.page.receipts_view') }}
                            </a>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Empty state when no rows match the filter -->
        <div
            v-else
            class="rounded-xl border border-dashed border-slate-200 dark:border-slate-700 p-8 text-center"
        >
            <svg
                class="mx-auto h-10 w-10 text-slate-300 dark:text-slate-600"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
            >
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="1.5"
                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                />
            </svg>
            <p class="mt-3 text-sm text-slate-500 dark:text-slate-400">
                {{ t('system.billing.page.filter.no_results') }}
            </p>
            <button
                type="button"
                @click="clearFilters"
                class="mt-3 text-xs font-semibold text-indigo-600 hover:text-indigo-700 dark:text-indigo-400 dark:hover:text-indigo-300"
            >
                {{ t('system.billing.page.filter.clear_filters') }}
            </button>
        </div>
    </div>
</template>
