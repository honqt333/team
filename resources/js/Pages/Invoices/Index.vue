<template>
    <AppLayout :title="$t('invoices.title')">
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                        {{ $t('invoices.title') }}
                    </h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                        {{ $t('invoices.invoices') }}
                    </p>
                </div>
            </div>

            <!-- Filters -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4">
                <div class="flex flex-wrap gap-4">
                    <input
                        v-model="localFilters.search"
                        type="text"
                        :placeholder="$t('common.search')"
                        class="flex-1 min-w-[200px] px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                        @input="debouncedSearch"
                    />
                    <div class="w-48">
                        <SearchableSelect
                            v-model="localFilters.payment_status"
                            :options="statusOptions"
                            option-label="label"
                            option-value="value"
                            :placeholder="$t('invoices.all_statuses')"
                            :label="''"
                            @change="applyFilters"
                        />
                    </div>
                    <input
                        v-model="localFilters.date_from"
                        type="date"
                        class="px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                        @change="applyFilters"
                    />
                    <input
                        v-model="localFilters.date_to"
                        type="date"
                        class="px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                        @change="applyFilters"
                    />
                </div>
            </div>

            <!-- Invoices Table -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th class="px-4 py-3 text-start text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">{{ $t('invoices.invoice_number') }}</th>
                                <th class="px-4 py-3 text-start text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">{{ $t('invoices.issue_date') }}</th>
                                <th class="px-4 py-3 text-start text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">{{ $t('common.customer') }}</th>
                                <th class="px-4 py-3 text-start text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">{{ $t('invoices.total') }}</th>
                                <th class="px-4 py-3 text-start text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">{{ $t('invoices.total_paid') }}</th>
                                <th class="px-4 py-3 text-start text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">{{ $t('invoices.balance') }}</th>
                                <th class="px-4 py-3 text-start text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">{{ $t('common.status') }}</th>
                                <th class="px-4 py-3 text-end text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">{{ $t('common.actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            <tr v-for="invoice in invoices.data" :key="invoice.id" class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                <td class="px-4 py-3">
                                    <Link :href="route('app.invoices.show', invoice.id)" class="font-medium text-blue-600 dark:text-blue-400 hover:underline">
                                        {{ invoice.invoice_number }}
                                    </Link>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300">
                                    {{ formatDate(invoice.issue_date) }}
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">
                                    {{ invoice.customer?.name }}
                                </td>
                                <td class="px-4 py-3 text-sm font-medium text-gray-900 dark:text-white" dir="ltr">
                                    {{ formatCurrency(invoice.total_incl_tax) }}
                                </td>
                                <td class="px-4 py-3 text-sm text-green-600 dark:text-green-400" dir="ltr">
                                    {{ formatCurrency(invoice.total_paid) }}
                                </td>
                                <td class="px-4 py-3 text-sm font-medium" dir="ltr" :class="invoice.balance > 0 ? 'text-red-600 dark:text-red-400' : 'text-gray-600 dark:text-gray-300'">
                                    {{ formatCurrency(invoice.balance) }}
                                </td>
                                <td class="px-4 py-3">
                                    <span :class="statusClass(invoice.payment_status)" class="inline-flex px-2 py-1 rounded-full text-xs font-medium">
                                        {{ $t(`invoices.status.${invoice.payment_status}`) }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-end">
                                    <div class="flex items-center justify-end gap-2">
                                        <Link :href="route('app.invoices.show', invoice.id)" class="p-2 text-gray-500 hover:text-blue-600 dark:text-gray-400 dark:hover:text-blue-400">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                            </svg>
                                        </Link>
                                        <Link :href="route('app.invoices.print', invoice.id)" target="_blank" class="p-2 text-gray-500 hover:text-green-600 dark:text-gray-400 dark:hover:text-green-400">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                                            </svg>
                                        </Link>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="!invoices.data.length">
                                <td colspan="8" class="px-4 py-12 text-center text-gray-500 dark:text-gray-400">
                                    {{ $t('common.no_data') }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="invoices.links?.length > 3" class="px-4 py-3 border-t border-gray-200 dark:border-gray-700">
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-500 dark:text-gray-400">
                            {{ $t('common.showing') }} {{ invoices.from }} - {{ invoices.to }} {{ $t('common.of') }} {{ invoices.total }}
                        </span>
                        <div class="flex gap-1">
                            <Link
                                v-for="link in invoices.links"
                                :key="link.label"
                                :href="link.url || '#'"
                                :class="[
                                    'px-3 py-1 rounded text-sm',
                                    link.active 
                                        ? 'bg-blue-600 text-white' 
                                        : link.url 
                                            ? 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600'
                                            : 'bg-gray-50 dark:bg-gray-800 text-gray-400 cursor-not-allowed'
                                ]"
                                v-html="link.label"
                            />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import AppLayout from '@/Layouts/AppLayout.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import { debounce } from 'lodash-es';

const props = defineProps({
    invoices: Object,
    filters: Object,
});

const { t } = useI18n();

const statusOptions = computed(() => [
    { value: '', label: t('invoices.all_statuses') },
    { value: 'unpaid', label: t('invoices.status.unpaid') },
    { value: 'partial', label: t('invoices.status.partial') },
    { value: 'paid', label: t('invoices.status.paid') },
]);

const localFilters = ref({
    search: props.filters?.search || '',
    payment_status: props.filters?.payment_status || '',
    date_from: props.filters?.date_from || '',
    date_to: props.filters?.date_to || '',
});

const applyFilters = () => {
    router.get(route('app.invoices.index'), {
        search: localFilters.value.search || undefined,
        payment_status: localFilters.value.payment_status || undefined,
        date_from: localFilters.value.date_from || undefined,
        date_to: localFilters.value.date_to || undefined,
    }, { preserveState: true, preserveScroll: true });
};

const debouncedSearch = debounce(applyFilters, 300);

const formatDate = (date) => {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('ar-SA');
};

const formatCurrency = (amount) => {
    return new Intl.NumberFormat('ar-SA', {
        style: 'currency',
        currency: 'SAR',
    }).format(amount || 0);
};

const statusClass = (status) => {
    switch (status) {
        case 'paid':
            return 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400';
        case 'partial':
            return 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400';
        case 'unpaid':
            return 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400';
        default:
            return 'bg-gray-100 text-gray-700 dark:bg-gray-600 dark:text-gray-300';
    }
};
</script>
