<template>
    <AppLayout>
        <div class="space-y-6">
            <!-- Header Actions Bar -->
            <div class="flex items-center justify-between">
                <Link :href="route('app.purchasing.orders.index')"
                    class="inline-flex items-center gap-2 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 transition-colors">
                    <svg class="w-5 h-5 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    {{ $t('common.back') }}
                </Link>

                <!-- Action Buttons -->
                <div class="flex items-center gap-2">
                    <!-- Send PO (if draft) -->
                    <button v-if="order.status === 'draft' && order.items?.length > 0" @click="sendOrder"
                        class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-xl transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                        </svg>
                        <span class="hidden sm:inline">{{ $t('purchasing.orders.actions.send') }}</span>
                    </button>

                    <!-- Receive Goods -->
                    <Link v-if="canReceive" :href="route('app.purchasing.grn.create', { purchase_order: order.id })"
                        class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-white bg-green-600 hover:bg-green-700 rounded-xl transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span class="hidden sm:inline">{{ $t('purchasing.orders.actions.receive') }}</span>
                    </Link>

                    <!-- Print -->
                    <button @click="printOrder"
                        class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                        </svg>
                        <span class="hidden sm:inline">{{ $t('common.print') }}</span>
                    </button>

                    <!-- Cancel (if not received/cancelled) -->
                    <button v-if="canCancel" @click="cancelOrder"
                        class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-red-600 dark:text-red-400 bg-white dark:bg-gray-800 border border-red-200 dark:border-red-800 rounded-xl hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                        <span class="hidden sm:inline">{{ $t('common.cancel') }}</span>
                    </button>
                </div>
            </div>

            <!-- Top Section: Order Info & Supplier -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

                <!-- 1. Supplier & Warehouse Info -->
                <div
                    class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 p-6 relative overflow-hidden">
                    <!-- Status Badge (Top End) -->
                    <div class="absolute top-6 end-6">
                        <div class="flex items-center gap-2">
                            <span class="text-sm font-medium text-gray-500 font-mono">{{ order.code }}</span>
                            <span :class="statusBadgeClass">{{ $t(`purchasing.orders.statuses.${order.status}`) }}</span>
                        </div>
                    </div>

                    <div class="flex flex-col items-center text-center mt-2">
                        <!-- Supplier Icon -->
                        <div
                            class="w-16 h-16 rounded-2xl bg-blue-50 dark:bg-blue-900/30 flex items-center justify-center mb-4">
                            <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                            </svg>
                        </div>

                        <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-1">
                            {{ order.supplier?.name }}
                        </h3>
                        <p v-if="order.supplier?.code" class="text-sm text-gray-500 dark:text-gray-400 font-mono mb-4">
                            {{ order.supplier.code }}
                        </p>

                        <!-- Info Grid -->
                        <div class="w-full space-y-3 mt-4">
                            <!-- Warehouse -->
                            <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700/50 rounded-xl">
                                <div class="flex items-center gap-3">
                                    <div
                                        class="w-8 h-8 rounded-full bg-green-100 dark:bg-green-900/30 flex items-center justify-center text-green-600">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z" />
                                        </svg>
                                    </div>
                                    <span class="text-sm text-gray-500">{{ $t('purchasing.orders.warehouse') }}</span>
                                </div>
                                <span class="font-medium text-gray-900 dark:text-white">{{ order.warehouse?.name }}</span>
                            </div>

                            <!-- Order Date -->
                            <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700/50 rounded-xl">
                                <div class="flex items-center gap-3">
                                    <div
                                        class="w-8 h-8 rounded-full bg-purple-100 dark:bg-purple-900/30 flex items-center justify-center text-purple-600">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                        </svg>
                                    </div>
                                    <span class="text-sm text-gray-500">{{ $t('purchasing.orders.date') }}</span>
                                </div>
                                <span class="font-medium text-gray-900 dark:text-white">{{ formatDate(order.order_date) }}</span>
                            </div>

                            <!-- Expected Date -->
                            <div v-if="order.expected_date" class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700/50 rounded-xl">
                                <div class="flex items-center gap-3">
                                    <div
                                        class="w-8 h-8 rounded-full bg-orange-100 dark:bg-orange-900/30 flex items-center justify-center text-orange-600">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                    </div>
                                    <span class="text-sm text-gray-500">{{ $t('purchasing.orders.expected_date') }}</span>
                                </div>
                                <span class="font-medium text-gray-900 dark:text-white">{{ formatDate(order.expected_date) }}</span>
                            </div>

                            <!-- Sent Info -->
                            <div v-if="order.sent_at" class="flex items-center justify-between p-3 bg-blue-50 dark:bg-blue-900/20 rounded-xl">
                                <div class="flex items-center gap-3">
                                    <div
                                        class="w-8 h-8 rounded-full bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center text-blue-600">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                                        </svg>
                                    </div>
                                    <span class="text-sm text-gray-500">{{ $t('purchasing.orders.sent_at') }}</span>
                                </div>
                                <div class="text-end">
                                    <span class="font-medium text-gray-900 dark:text-white block">{{ formatDateTime(order.sent_at) }}</span>
                                    <span v-if="order.sent_by_user" class="text-xs text-gray-500">{{ order.sent_by_user.name }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 2. Financial Summary -->
                <div
                    class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">
                        {{ $t('purchasing.orders.financial_summary') }}
                    </h3>

                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="text-gray-500 dark:text-gray-400 border-b border-gray-100 dark:border-gray-700">
                                    <th class="pb-3 text-start">{{ $t('common.description') }}</th>
                                    <th class="pb-3 text-end">{{ $t('common.amount') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50 dark:divide-gray-700/50">
                                <!-- Subtotal -->
                                <tr>
                                    <td class="py-3 font-medium text-gray-700 dark:text-gray-300">
                                        {{ $t('invoices.subtotal') }}
                                    </td>
                                    <td class="py-3 text-end text-gray-600 dark:text-gray-400 font-mono">
                                        {{ formatCurrency(order.subtotal) }}
                                    </td>
                                </tr>

                                <!-- Tax -->
                                <tr v-if="parseFloat(order.tax_amount) > 0">
                                    <td class="py-3 font-medium text-gray-700 dark:text-gray-300">
                                        {{ $t('invoices.tax') }} (15%)
                                    </td>
                                    <td class="py-3 text-end text-gray-600 dark:text-gray-400 font-mono">
                                        {{ formatCurrency(order.tax_amount) }}
                                    </td>
                                </tr>

                                <!-- Total -->
                                <tr class="bg-gray-50 dark:bg-gray-900/50 font-bold border-t-2 border-gray-100 dark:border-gray-700">
                                    <td class="py-4 text-gray-900 dark:text-white">
                                        {{ $t('common.total') }}
                                    </td>
                                    <td class="py-4 text-end text-lg text-blue-600 dark:text-blue-400 font-mono">
                                        {{ formatCurrency(order.total) }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Items Count -->
                    <div class="mt-4 pt-4 border-t border-gray-100 dark:border-gray-700">
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-gray-500">{{ $t('purchasing.orders.items_count') }}</span>
                            <span class="font-bold text-gray-900 dark:text-white">{{ order.items?.length || 0 }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Notes Section -->
            <div v-if="order.notes || order.terms" class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Notes -->
                <div v-if="order.notes"
                    class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                    <div class="flex items-center gap-3 mb-4">
                        <div
                            class="w-10 h-10 rounded-xl bg-yellow-100 dark:bg-yellow-900/30 flex items-center justify-center text-yellow-600">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white">
                            {{ $t('common.notes') }}
                        </h3>
                    </div>
                    <p class="text-gray-700 dark:text-gray-300 leading-relaxed whitespace-pre-wrap">
                        {{ order.notes }}
                    </p>
                </div>

                <!-- Terms -->
                <div v-if="order.terms"
                    class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                    <div class="flex items-center gap-3 mb-4">
                        <div
                            class="w-10 h-10 rounded-xl bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center text-blue-600">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white">
                            {{ $t('purchasing.orders.terms') }}
                        </h3>
                    </div>
                    <p class="text-gray-700 dark:text-gray-300 leading-relaxed whitespace-pre-wrap">
                        {{ order.terms }}
                    </p>
                </div>
            </div>

            <!-- Items Table -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-900/50">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
                        <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                        </svg>
                        {{ $t('purchasing.orders.items') }}
                        <span class="px-2 py-0.5 text-xs rounded-full bg-gray-200 dark:bg-gray-600">
                            {{ order.items?.length || 0 }}
                        </span>
                    </h3>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th class="px-4 py-3 text-start text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                                    {{ $t('purchasing.orders.part_name') }}
                                </th>
                                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                                    {{ $t('purchasing.orders.qty_ordered') }}
                                </th>
                                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                                    {{ $t('purchasing.orders.qty_received') }}
                                </th>
                                <th class="px-4 py-3 text-end text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                                    {{ $t('purchasing.orders.unit_cost') }}
                                </th>
                                <th class="px-4 py-3 text-end text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                                    {{ $t('purchasing.orders.line_total') }}
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            <tr v-for="item in order.items" :key="item.id" class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-lg bg-gray-100 dark:bg-gray-700 flex items-center justify-center">
                                            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                                            </svg>
                                        </div>
                                        <div>
                                            <p class="font-medium text-gray-900 dark:text-white">
                                                {{ item.part?.name_ar || item.part?.name_en }}
                                            </p>
                                            <p v-if="item.part?.sku" class="text-xs text-gray-500 font-mono">
                                                {{ item.part.sku }}
                                            </p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-center font-mono text-gray-900 dark:text-white">
                                    {{ item.qty_ordered }}
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <span :class="getReceivedClass(item)">
                                        {{ item.qty_received || 0 }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-end font-mono text-gray-600 dark:text-gray-400">
                                    {{ formatCurrency(item.unit_cost) }}
                                </td>
                                <td class="px-4 py-3 text-end font-mono font-medium text-gray-900 dark:text-white">
                                    {{ formatCurrency(item.line_total) }}
                                </td>
                            </tr>

                            <tr v-if="!order.items?.length">
                                <td colspan="5" class="px-4 py-12 text-center text-gray-500 dark:text-gray-400">
                                    {{ $t('purchasing.orders.no_items') }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Goods Received Notes (if any) -->
            <div v-if="order.goods_received_notes?.length > 0"
                class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-900/50">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
                        <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        {{ $t('purchasing.grn.title') }}
                        <span class="px-2 py-0.5 text-xs rounded-full bg-green-100 dark:bg-green-900/30 text-green-600">
                            {{ order.goods_received_notes.length }}
                        </span>
                    </h3>
                </div>

                <div class="divide-y divide-gray-200 dark:divide-gray-700">
                    <div v-for="grn in order.goods_received_notes" :key="grn.id"
                        class="p-4 hover:bg-gray-50 dark:hover:bg-gray-700/50">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="font-medium text-gray-900 dark:text-white font-mono">{{ grn.code }}</p>
                                <p class="text-sm text-gray-500">{{ formatDate(grn.received_at) }}</p>
                            </div>
                            <Link :href="route('app.purchasing.grn.show', grn.id)"
                                class="text-blue-600 hover:text-blue-700 text-sm font-medium">
                                {{ $t('common.view') }}
                            </Link>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <ConfirmModal />
    </AppLayout>
</template>

<script setup>
import { computed } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import { useNumberFormat } from '@/Composables/useNumberFormat';
import { useConfirm } from '@/Composables/useConfirm';
import AppLayout from '@/Layouts/AppLayout.vue';
import ConfirmModal from '@/Components/ConfirmModal.vue';

const props = defineProps({
    order: Object,
});

const { t } = useI18n();
const { formatCurrency } = useNumberFormat();
const { confirm } = useConfirm();

// Date formatters
const formatDate = (date) => {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('ar-SA', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
    });
};

const formatDateTime = (date) => {
    if (!date) return '-';
    return new Date(date).toLocaleString('ar-SA', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit',
    });
};

// Computed properties
const canReceive = computed(() => {
    return ['sent', 'partial'].includes(props.order.status);
});

const canCancel = computed(() => {
    return !['received', 'cancelled'].includes(props.order.status);
});

const statusBadgeClass = computed(() => {
    const classes = {
        draft: 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
        sent: 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300',
        partial: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300',
        received: 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300',
        cancelled: 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300',
    };
    return `${classes[props.order.status] || classes.draft} px-3 py-1 rounded-full text-sm font-medium`;
});

// Helper for received qty styling
const getReceivedClass = (item) => {
    const ordered = parseFloat(item.qty_ordered || 0);
    const received = parseFloat(item.qty_received || 0);
    
    if (received >= ordered) {
        return 'font-mono text-green-600 dark:text-green-400 font-medium';
    } else if (received > 0) {
        return 'font-mono text-yellow-600 dark:text-yellow-400 font-medium';
    }
    return 'font-mono text-gray-400';
};

// Actions
async function sendOrder() {
    const confirmed = await confirm({
        title: t('purchasing.orders.confirm_send_title'),
        message: t('purchasing.orders.confirm_send'),
        confirmText: t('purchasing.orders.actions.send'),
        cancelText: t('common.cancel'),
        type: 'info'
    });

    if (confirmed) {
        router.post(route('app.purchasing.orders.send', props.order.id), {}, {
            preserveScroll: true,
        });
    }
}

async function cancelOrder() {
    const confirmed = await confirm({
        title: t('purchasing.orders.confirm_cancel_title'),
        message: t('purchasing.orders.confirm_cancel'),
        confirmText: t('common.cancel'),
        cancelText: t('common.back'),
        type: 'danger'
    });

    if (confirmed) {
        router.post(route('app.purchasing.orders.cancel', props.order.id), {}, {
            preserveScroll: true,
        });
    }
}

function printOrder() {
    window.print();
}
</script>
