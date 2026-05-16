<template>
    <AppLayout :title="`${$t('purchasing.grn.title')}: ${grn.code}`">
        <template #header>
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div class="flex items-center gap-4">
                    <Link :href="route('app.purchasing.orders.show', grn.purchase_order_id)"
                        class="p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 transition-colors bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                        </svg>
                    </Link>
                    <div>
                        <div class="flex items-center gap-3">
                            <h2 class="text-2xl font-bold text-gray-900 dark:text-white">{{ grn.code }}</h2>
                            <span :class="statusBadgeClass">{{ $t(`purchasing.grn.statuses.${grn.status}`) }}</span>
                        </div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            {{ $t('purchasing.grn.for_po') }}: {{ grn.purchase_order?.code }}
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <!-- Post GRN -->
                    <button v-if="props.grn.status === 'draft'" @click="postGrn"
                        class="inline-flex items-center gap-2 px-6 py-2.5 text-sm font-bold text-white bg-green-600 hover:bg-green-700 rounded-xl transition-all shadow-lg shadow-green-500/30">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        {{ $t('purchasing.grn.post') }}
                    </button>

                    <!-- Cancel GRN -->
                    <button v-if="props.grn.status === 'draft'" @click="cancelGrn"
                        class="inline-flex items-center gap-2 px-4 py-2.5 text-sm font-bold text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-xl transition-all border border-red-200 dark:border-red-800">
                        {{ $t('common.cancel') }}
                    </button>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    <!-- Left Column: Items -->
                    <div class="lg:col-span-2 space-y-6">
                        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
                            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-900/50">
                                <h3 class="text-lg font-bold text-gray-900 dark:text-white">{{ $t('purchasing.orders.items') }}</h3>
                            </div>
                            <div class="overflow-x-auto">
                                <table class="w-full text-sm">
                                    <thead class="bg-gray-50 dark:bg-gray-900/30 text-gray-500 dark:text-gray-400 border-b border-gray-200 dark:border-gray-700">
                                        <tr>
                                            <th class="px-6 py-4 text-start font-semibold">{{ $t('inventory.parts.title') }}</th>
                                            <th class="px-6 py-4 text-center font-semibold">{{ $t('common.quantity') }}</th>
                                            <th class="px-6 py-4 text-end font-semibold">{{ $t('inventory.parts.unit_price') }}</th>
                                            <th class="px-6 py-4 text-end font-semibold">{{ $t('common.total') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                        <tr v-for="item in grn.items" :key="item.id" class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                                            <td class="px-6 py-4">
                                                <div class="font-bold text-gray-900 dark:text-white">{{ item.part?.name_ar || item.part?.name_en }}</div>
                                                <div class="text-xs text-gray-500 mt-0.5 font-mono">{{ item.part?.sku }}</div>
                                            </td>
                                            <td class="px-6 py-4 text-center">
                                                <span class="px-2 py-1 rounded-lg bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 font-mono font-bold">
                                                    {{ toEnglish(item.qty_received) }}
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 text-end font-mono">{{ formatCurrency(item.unit_cost) }}</td>
                                            <td class="px-6 py-4 text-end font-bold text-gray-900 dark:text-white font-mono">
                                                {{ formatCurrency(item.qty_received * item.unit_cost) }}
                                            </td>
                                        </tr>
                                    </tbody>
                                    <tfoot class="bg-gray-50/50 dark:bg-gray-900/50">
                                        <tr>
                                            <td colspan="3" class="px-6 py-4 text-end font-bold text-gray-900 dark:text-white">{{ $t('common.total') }}</td>
                                            <td class="px-6 py-4 text-end font-bold text-blue-600 dark:text-blue-400 font-mono text-lg">
                                                {{ formatCurrency(totalAmount) }}
                                            </td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                        <!-- Notes -->
                        <div v-if="grn.notes" class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                            <h4 class="text-sm font-bold text-gray-400 uppercase tracking-wider mb-3">{{ $t('common.notes') }}</h4>
                            <p class="text-gray-700 dark:text-gray-300 whitespace-pre-wrap">{{ grn.notes }}</p>
                        </div>
                    </div>

                    <!-- Right Column: Details Card -->
                    <div class="space-y-6">
                        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
                            <div class="p-6 space-y-6">
                                <!-- Details Grid -->
                                <div class="space-y-4">
                                    <div class="flex items-center justify-between py-2 border-b border-gray-50 dark:border-gray-700/50">
                                        <span class="text-sm text-gray-500">{{ $t('purchasing.grn.received_date') }}</span>
                                        <span class="text-sm font-bold text-gray-900 dark:text-white">{{ formatDate(grn.received_date) }}</span>
                                    </div>
                                    <div class="flex items-center justify-between py-2 border-b border-gray-50 dark:border-gray-700/50">
                                        <span class="text-sm text-gray-500">{{ $t('inventory.warehouses.title') }}</span>
                                        <span class="text-sm font-bold text-gray-900 dark:text-white">{{ grn.warehouse?.name }}</span>
                                    </div>
                                    <div v-if="grn.delivery_note" class="flex items-center justify-between py-2 border-b border-gray-50 dark:border-gray-700/50">
                                        <span class="text-sm text-gray-500">{{ $t('purchasing.grn.delivery_note') }}</span>
                                        <span class="text-sm font-bold text-gray-900 dark:text-white">{{ grn.delivery_note }}</span>
                                    </div>
                                    <div class="flex items-center justify-between py-2">
                                        <span class="text-sm text-gray-500">{{ $t('purchasing.orders.supplier') }}</span>
                                        <span class="text-sm font-bold text-gray-900 dark:text-white">{{ grn.purchase_order?.supplier?.name }}</span>
                                    </div>
                                </div>

                                <!-- Post Info -->
                                <div v-if="grn.status === 'posted'" class="mt-6 p-4 bg-green-50 dark:bg-green-900/20 rounded-xl border border-green-100 dark:border-green-800">
                                    <div class="flex items-start gap-3">
                                        <div class="p-2 bg-green-100 dark:bg-green-900/40 rounded-lg text-green-600">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                        </div>
                                        <div>
                                            <p class="text-xs text-green-800 dark:text-green-300 font-bold">{{ $t('purchasing.grn.posted_at') }}</p>
                                            <p class="text-sm text-green-700 dark:text-green-400 mt-0.5">{{ formatDateTime(grn.posted_at) }}</p>
                                            <p class="text-xs text-green-600/70 dark:text-green-500/70 mt-1">{{ $t('common.by') }}: {{ grn.posted_by_user?.name }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { computed } from 'vue';
import { router, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { useNumberFormat } from '@/Composables/useNumberFormat';

const props = defineProps({
    grn: Object,
});

const { formatCurrency, toEnglish } = useNumberFormat();

const statusBadgeClass = computed(() => {
    const classes = {
        draft: 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
        posted: 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300',
        cancelled: 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300',
    };
    return `${classes[props.grn.status] || classes.draft} px-3 py-1 rounded-full text-xs font-medium`;
});

const totalAmount = computed(() => {
    return props.grn.items.reduce((sum, item) => sum + (item.qty_received * item.unit_cost), 0);
});

const formatDate = (date) => {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('en-US', { year: 'numeric', month: '2-digit', day: '2-digit' });
};

const formatDateTime = (date) => {
    if (!date) return '-';
    return new Date(date).toLocaleString('en-US', {
        year: 'numeric', month: '2-digit', day: '2-digit',
        hour: '2-digit', minute: '2-digit'
    });
};

const postGrn = () => {
    if (confirm('هل أنت متأكد من ترحيل سند الاستلام؟ سيتم تحديث المخزون فوراً.')) {
        router.post(route('app.purchasing.grn.post', props.grn.id));
    }
};

const cancelGrn = () => {
    if (confirm('هل أنت متأكد من إلغاء هذا السند؟')) {
        router.post(route('app.purchasing.grn.cancel', props.grn.id));
    }
};
</script>
