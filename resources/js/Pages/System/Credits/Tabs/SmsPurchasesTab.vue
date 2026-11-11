<template>
    <div class="space-y-6">
        <!-- Filters -->
        <div class="flex items-center gap-4">
            <div class="w-64">
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
                            #{{ item.id }}
                        </td>
                        <td class="px-6 py-4">
                            <div class="font-medium text-gray-900 dark:text-white">
                                {{
                                    item.tenant?.name ||
                                    $t('system.credits.tenant_label', { id: item.tenant_id })
                                }}
                            </div>
                        </td>
                        <td class="px-6 py-4 text-gray-700 dark:text-gray-300">
                            {{ item.package?.name_ar || $t('system.credits.custom_package') }}
                        </td>
                        <td class="px-6 py-4 font-bold text-indigo-600 dark:text-indigo-400">
                            +{{ item.credits?.toLocaleString() }}
                        </td>
                        <td class="px-6 py-4 font-medium text-gray-900 dark:text-white">
                            {{ formatCurrency(item.amount) }}
                        </td>
                        <td class="px-6 py-4 text-gray-500 text-sm">
                            {{ item.payment_gateway || '-' }}
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
    purchases: { type: Object, default: () => ({}) },
    filters: { type: Object, default: () => ({}) },
});

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
        '/system/sms',
        { tab: 'purchases', status: status.value || undefined },
        { preserveState: true }
    );
};

const formatCurrency = (amount) =>
    new Intl.NumberFormat('ar-SA-u-nu-latn', { style: 'currency', currency: 'SAR' }).format(
        amount || 0
    );

const formatDate = (date) => (date ? new Date(date).toLocaleString('ar-SA-u-nu-latn') : '-');

const getStatusLabel = (s) =>
    ({
        pending: t('common.pending'),
        paid: t('common.status_paid'),
        failed: t('system.credits.failed'),
        refunded: t('system.credits.refunded'),
    })[s] || s;

const getStatusClass = (s) =>
    ({
        pending: 'bg-amber-100 text-amber-700 dark:bg-amber-950/40 dark:text-amber-300',
        paid: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300',
        failed: 'bg-red-100 text-red-700 dark:bg-red-950/40 dark:text-red-300',
        refunded: 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300',
    })[s] || 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300';
</script>
