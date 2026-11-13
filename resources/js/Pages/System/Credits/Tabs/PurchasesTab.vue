<template>
    <div class="space-y-6">
        <!-- Stats Cards (shown when stats available) -->
        <div v-if="stats" class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div
                class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700 shadow-sm"
            >
                <p class="text-xs text-gray-500 font-medium">
                    {{ $t('system.credits.revenue') }}
                </p>
                <p class="text-2xl font-bold text-emerald-600 mt-1">
                    {{ formatMoney(stats.total_revenue || 0) }} {{ $t('common.currency_sar') }}
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
            <div
                class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700 shadow-sm"
            >
                <p class="text-xs text-gray-500 font-medium">
                    {{ $t('system.credits.total_purchases') }}
                </p>
                <p class="text-2xl font-bold text-blue-600 mt-1">{{ purchases?.total || 0 }}</p>
            </div>
        </div>

        <!-- Filters -->
        <div class="flex flex-wrap items-center gap-4">
            <div class="flex-1 min-w-[200px] max-w-sm">
                <AppInput
                    v-model="search"
                    type="text"
                    :placeholder="$t('system.credits.search_workshop')"
                    @enter="applyFilters"
                />
            </div>
            <div class="w-56">
                <SearchableSelect
                    v-model="status"
                    :options="statusOptions"
                    option-label="label"
                    option-value="value"
                    :placeholder="$t('system.credits.all_statuses')"
                    compact
                    @update:model-value="applyFilters"
                />
            </div>
        </div>

        <!-- Table -->
        <div
            class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden"
        >
            <DataTable structured class="w-full">
                <thead class="bg-gray-50 dark:bg-gray-700/50">
                    <tr>
                        <th
                            class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase"
                        >
                            {{ $t('system.credits.transaction_number') }}
                        </th>
                        <th
                            class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase"
                        >
                            {{ $t('system.credits.workshop') }}
                        </th>
                        <th
                            class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase"
                        >
                            {{ $t('system.credits.package') }}
                        </th>
                        <th
                            class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase"
                        >
                            {{ $t('system.credits.credits') }}
                        </th>
                        <th
                            class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase"
                        >
                            {{ $t('system.credits.amount') }}
                        </th>
                        <th
                            class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase"
                        >
                            {{ $t('system.credits.payment_method') }}
                        </th>
                        <th
                            class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase"
                        >
                            {{ $t('system.credits.status') }}
                        </th>
                        <th
                            class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase"
                        >
                            {{ $t('system.credits.date') }}
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    <tr v-for="item in purchases?.data || []" :key="item.id">
                        <td class="px-6 py-4 font-mono text-sm text-gray-900 dark:text-white">
                            {{ item.payment_reference || `#${item.id}` }}
                        </td>
                        <td class="px-6 py-4">
                            <div class="font-medium text-gray-900 dark:text-white">
                                {{
                                    item.tenant?.trade_name ||
                                    item.tenant?.name ||
                                    $t('system.credits.tenant_label', { id: item.tenant_id })
                                }}
                            </div>
                        </td>
                        <td class="px-6 py-4 text-gray-600 dark:text-gray-400">
                            {{ item.package?.name_ar || $t('system.credits.custom_package') }}
                        </td>
                        <td class="px-6 py-4 font-bold text-gray-900 dark:text-white">
                            +{{ item.credits.toLocaleString() }}
                        </td>
                        <td class="px-6 py-4 font-medium text-gray-900 dark:text-white">
                            {{ formatMoney(item.amount) }} {{ $t('common.currency_sar') }}
                        </td>
                        <td class="px-6 py-4 text-gray-600 dark:text-gray-400">
                            {{ getGatewayLabel(item.payment_gateway) }}
                        </td>
                        <td class="px-6 py-4">
                            <span
                                :class="[
                                    'px-2.5 py-1 rounded-full text-xs font-medium',
                                    getStatusClass(item.status),
                                ]"
                            >
                                {{ getStatusLabel(item.status) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-500">
                            {{ formatDate(item.created_at) }}
                        </td>
                    </tr>
                    <tr v-if="!purchases?.data?.length">
                        <td colspan="8" class="px-6 py-8 text-center text-gray-500">
                            {{ $t('system.credits.no_operations_found') }}
                        </td>
                    </tr>
                </tbody>
            </DataTable>
            <AppPagination
                :links="purchases?.links || []"
                :from="purchases?.from"
                :to="purchases?.to"
                :total="purchases?.total"
            />
        </div>
    </div>
</template>

<script setup>
import { computed, ref } from 'vue';
import { router } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import DataTable from '@/Components/Common/DataTable.vue';
import AppPagination from '@/Components/Common/AppPagination.vue';
import AppInput from '@/Components/App/AppInput.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import { useFormatters } from '@/Composables/useFormatters';

const { t } = useI18n();
const { formatDateTime: formatDate } = useFormatters();

const props = defineProps({
    channel: { type: String, required: true },
    purchases: { type: Object, default: () => ({}) },
    stats: { type: Object, default: () => ({}) },
    filters: { type: Object, default: () => ({}) },
});

const search = ref(props.filters?.search || '');
const status = ref(props.filters?.status || '');

const statusOptions = computed(() => [
    { value: '', label: t('system.credits.all_statuses') },
    { value: 'pending', label: t('common.pending') },
    { value: 'paid', label: t('common.status_paid') },
    { value: 'failed', label: t('system.credits.failed') },
    { value: 'refunded', label: t('system.credits.refunded') },
]);

const applyFilters = () => {
    router.get(
        `/system/${props.channel}`,
        { tab: 'purchases', search: search.value, status: status.value },
        { preserveState: true }
    );
};

const formatMoney = (amount) =>
    Number(amount || 0).toLocaleString('en-US', { minimumFractionDigits: 2 });

const getGatewayLabel = (gateway) =>
    ({
        manual: t('system.credits.manual'),
        moyasar: t('system.credits.moyasar'),
        tap: t('system.credits.tap'),
        stripe: 'Stripe',
        bank_transfer: t('system.credits.bank_transfer'),
    })[gateway] ||
    gateway ||
    '-';

const getStatusLabel = (s) =>
    ({
        pending: t('common.pending'),
        paid: t('common.status_paid'),
        failed: t('system.credits.failed'),
        refunded: t('system.credits.refunded'),
    })[s] || s;

const getStatusClass = (s) =>
    ({
        pending: 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300',
        paid: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300',
        failed: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300',
        refunded: 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-300',
    })[s] || 'bg-gray-100 text-gray-700';
</script>
