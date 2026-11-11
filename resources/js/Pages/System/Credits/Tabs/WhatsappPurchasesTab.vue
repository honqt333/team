<template>
    <div class="space-y-6">
        <!-- Stats Cards -->
        <div v-if="stats" class="grid grid-cols-2 lg:grid-cols-5 gap-4">
            <div
                class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700 shadow-sm"
            >
                <p class="text-xs text-gray-500 font-medium">
                    {{ $t('system.credits.total_purchases') }}
                </p>
                <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">
                    {{ stats.total || 0 }}
                </p>
            </div>
            <div
                class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700 shadow-sm"
            >
                <p class="text-xs text-emerald-600 font-medium">{{ $t('common.paid') }}</p>
                <p class="text-2xl font-bold text-emerald-600 mt-1">{{ stats.paid || 0 }}</p>
            </div>
            <div
                class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700 shadow-sm"
            >
                <p class="text-xs text-amber-600 font-medium">{{ $t('common.pending') }}</p>
                <p class="text-2xl font-bold text-amber-600 mt-1">{{ stats.pending || 0 }}</p>
            </div>
            <div
                class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700 shadow-sm"
            >
                <p class="text-xs text-action-primary font-medium">
                    {{ $t('system.credits.revenue') }}
                </p>
                <p class="text-2xl font-bold text-action-primary mt-1">
                    {{ formatCurrency(stats.total_revenue) }}
                </p>
            </div>
            <div
                class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700 shadow-sm"
            >
                <p class="text-xs text-gray-500 font-medium">
                    {{ $t('system.credits.credits_sold') }}
                </p>
                <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">
                    {{ (stats.total_credits_sold || 0).toLocaleString() }}
                </p>
            </div>
        </div>

        <!-- Filter bar -->
        <div
            class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700 shadow-sm flex flex-wrap gap-4"
        >
            <div class="flex-1 min-w-[200px]">
                <input
                    v-model="search"
                    type="text"
                    :placeholder="$t('system.credits.search_purchases')"
                    @keyup.enter="applyFilters"
                    class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm"
                />
            </div>
            <SearchableSelect
                v-model="status"
                :options="statusOptions"
                option-label="label"
                option-value="value"
                compact
                @update:model-value="applyFilters"
            />
            <SearchableSelect
                v-model="gateway"
                :options="gatewayOptions"
                option-label="label"
                option-value="value"
                compact
                @update:model-value="applyFilters"
            />
        </div>

        <!-- Purchases Table -->
        <div
            class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 overflow-hidden shadow-sm"
        >
            <div class="overflow-x-auto">
                <DataTable structured class="w-full text-right text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-700/50 text-gray-600 dark:text-gray-300">
                        <tr>
                            <th class="px-6 py-3.5 font-bold">
                                {{ $t('system.credits.reference') }}
                            </th>
                            <th class="px-6 py-3.5 font-bold">{{ $t('system.credits.tenant') }}</th>
                            <th class="px-6 py-3.5 font-bold">
                                {{ $t('system.credits.package') }}
                            </th>
                            <th class="px-6 py-3.5 font-bold">
                                {{ $t('system.credits.message_count') }}
                            </th>
                            <th class="px-6 py-3.5 font-bold">{{ $t('system.credits.amount') }}</th>
                            <th class="px-6 py-3.5 font-bold">
                                {{ $t('system.credits.gateway') }}
                            </th>
                            <th class="px-6 py-3.5 font-bold">{{ $t('common.status') }}</th>
                            <th class="px-6 py-3.5 font-bold">{{ $t('system.credits.date') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        <tr
                            v-for="item in purchases?.data || []"
                            :key="item.id"
                            class="hover:bg-gray-50 dark:hover:bg-gray-700/40 transition-colors"
                        >
                            <td
                                class="px-6 py-4 font-mono text-xs text-gray-600 dark:text-gray-400"
                            >
                                {{ item.payment_reference || '-' }}
                            </td>
                            <td class="px-6 py-4 font-medium text-gray-900 dark:text-white">
                                {{
                                    item.tenant?.trade_name ||
                                    item.tenant?.name ||
                                    '#' + item.tenant_id
                                }}
                            </td>
                            <td class="px-6 py-4 text-gray-600 dark:text-gray-300">
                                {{
                                    item.package?.name_ar ||
                                    item.package?.name ||
                                    $t('system.credits.custom')
                                }}
                            </td>
                            <td class="px-6 py-4 font-bold text-emerald-600">
                                {{ (item.credits || 0).toLocaleString() }}
                            </td>
                            <td class="px-6 py-4 font-semibold text-gray-900 dark:text-white">
                                {{ formatCurrency(item.amount) }}
                            </td>
                            <td class="px-6 py-4 text-xs">
                                <span
                                    class="px-2 py-0.5 rounded bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300"
                                >
                                    {{ item.payment_gateway || '-' }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <span
                                    :class="getStatusBadgeClass(item.status)"
                                    class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-semibold"
                                >
                                    {{ getStatusLabel(item.status) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-xs text-gray-500 dark:text-gray-400">
                                {{ formatDate(item.created_at) }}
                            </td>
                        </tr>
                        <tr v-if="!purchases?.data?.length">
                            <td colspan="8" class="px-6 py-12 text-center text-gray-500">
                                {{ $t('system.credits.no_purchases') }}
                            </td>
                        </tr>
                    </tbody>
                </DataTable>
            </div>
        </div>
    </div>
</template>

<script setup>
import DataTable from '@/Components/Common/DataTable.vue';
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import SearchableSelect from '@/Components/SearchableSelect.vue';

const { t } = useI18n();

const props = defineProps({
    purchases: { type: Object, default: () => ({ data: [] }) },
    stats: { type: Object, default: () => ({}) },
    filters: { type: Object, default: () => ({}) },
});

const search = ref(props.filters?.search || '');
const status = ref(props.filters?.status || 'all');
const gateway = ref(props.filters?.gateway || 'all');

const statusOptions = computed(() => [
    { value: 'all', label: t('system.credits.all_statuses') },
    { value: 'paid', label: t('common.paid') },
    { value: 'pending', label: t('common.pending') },
    { value: 'failed', label: t('system.credits.failed') },
    { value: 'refunded', label: t('system.credits.refunded') },
]);

const gatewayOptions = computed(() => [
    { value: 'all', label: t('system.credits.gateway') + ': ' + t('common.all') },
    { value: 'manual', label: t('system.credits.add_manual_credit') },
    { value: 'moyasar', label: 'Moyasar' },
    { value: 'bank_transfer', label: t('financial.bank_transfer') },
]);

const formatCurrency = (amount) =>
    new Intl.NumberFormat('ar-SA-u-nu-latn', { style: 'currency', currency: 'SAR' }).format(
        amount || 0
    );

const formatDate = (date) => (date ? new Date(date).toLocaleString('ar-SA-u-nu-latn') : '-');

const getStatusBadgeClass = (s) =>
    ({
        paid: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300',
        pending: 'bg-amber-100 text-amber-700 dark:bg-amber-950/40 dark:text-amber-300',
        failed: 'bg-rose-100 text-rose-700 dark:bg-rose-950/40 dark:text-rose-300',
        refunded: 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300',
    })[s] || 'bg-gray-100 text-gray-700';

const getStatusLabel = (s) =>
    ({
        paid: t('common.paid'),
        pending: t('common.pending'),
        failed: t('system.credits.failed'),
        refunded: t('system.credits.refunded'),
    })[s] || s;

const applyFilters = () => {
    router.get(
        route('system.whatsapp.index'),
        {
            tab: 'purchases',
            search: search.value || undefined,
            status: status.value !== 'all' ? status.value : undefined,
            gateway: gateway.value !== 'all' ? gateway.value : undefined,
        },
        { preserveState: true, preserveScroll: true }
    );
};
</script>
