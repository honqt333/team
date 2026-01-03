<template>
    <BaseModal :show="show" @close="emit('close')" size="md">
        <template #header>
            <div class="flex items-center gap-2">
                <svg class="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                </svg>
                <span class="text-lg font-bold text-gray-900 dark:text-white">{{ $t('print.options_title') }}</span>
            </div>
        </template>

        <template #default>
            <div class="space-y-2">
                <!-- Vehicle Condition Report -->
                <button
                    @click="printOption('condition')"
                    class="w-full flex items-center gap-4 p-4 rounded-xl border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/50 hover:border-indigo-300 dark:hover:border-indigo-600 transition-all group"
                >
                    <div class="w-12 h-12 rounded-xl bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center flex-shrink-0">
                        <span class="text-2xl">🚗</span>
                    </div>
                    <div class="flex-1 text-start">
                        <p class="font-semibold text-gray-900 dark:text-white group-hover:text-indigo-600 dark:group-hover:text-indigo-400">
                            {{ $t('print.vehicle_report') }}
                        </p>
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            {{ $t('print.vehicle_report_desc') }}
                        </p>
                    </div>
                    <svg class="w-5 h-5 text-gray-400 group-hover:text-indigo-500 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </button>

                <!-- Work Order (Services) - Only if status is open/in_progress -->
                <button
                    v-if="canPrintWorkOrder"
                    @click="printOption('work_order')"
                    class="w-full flex items-center gap-4 p-4 rounded-xl border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/50 hover:border-indigo-300 dark:hover:border-indigo-600 transition-all group"
                >
                    <div class="w-12 h-12 rounded-xl bg-amber-100 dark:bg-amber-900/30 flex items-center justify-center flex-shrink-0">
                        <span class="text-2xl">📋</span>
                    </div>
                    <div class="flex-1 text-start">
                        <p class="font-semibold text-gray-900 dark:text-white group-hover:text-indigo-600 dark:group-hover:text-indigo-400">
                            {{ $t('print.work_order') }}
                        </p>
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            {{ $t('print.work_order_desc') }}
                        </p>
                    </div>
                    <svg class="w-5 h-5 text-gray-400 group-hover:text-indigo-500 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </button>

                <!-- Proforma Invoice -->
                <button
                    @click="printOption('proforma')"
                    class="w-full flex items-center gap-4 p-4 rounded-xl border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/50 hover:border-green-300 dark:hover:border-green-600 transition-all group"
                >
                    <div class="w-12 h-12 rounded-xl bg-green-100 dark:bg-green-900/30 flex items-center justify-center flex-shrink-0">
                        <span class="text-2xl">🧾</span>
                    </div>
                    <div class="flex-1 text-start">
                        <p class="font-semibold text-gray-900 dark:text-white group-hover:text-green-600 dark:group-hover:text-green-400">
                            {{ $t('print.proforma_invoice') }}
                        </p>
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            {{ $t('print.proforma_invoice_desc') }}
                        </p>
                    </div>
                    <svg class="w-5 h-5 text-gray-400 group-hover:text-green-500 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </button>

                <!-- Payments Receipt - Only if has payments -->
                <button
                    v-if="hasPayments"
                    @click="printOption('payments')"
                    class="w-full flex items-center gap-4 p-4 rounded-xl border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/50 hover:border-purple-300 dark:hover:border-purple-600 transition-all group"
                >
                    <div class="w-12 h-12 rounded-xl bg-purple-100 dark:bg-purple-900/30 flex items-center justify-center flex-shrink-0">
                        <span class="text-2xl">💰</span>
                    </div>
                    <div class="flex-1 text-start">
                        <p class="font-semibold text-gray-900 dark:text-white group-hover:text-purple-600 dark:group-hover:text-purple-400">
                            {{ $t('print.payments_receipt') }}
                        </p>
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            {{ $t('print.payments_receipt_desc') }}
                        </p>
                    </div>
                    <svg class="w-5 h-5 text-gray-400 group-hover:text-purple-500 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </button>
            </div>
        </template>

        <template #footer>
            <div class="flex justify-end">
                <button
                    @click="emit('close')"
                    class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors"
                >
                    {{ $t('common.close') }}
                </button>
            </div>
        </template>
    </BaseModal>
</template>

<script setup>
import { computed } from 'vue';
import BaseModal from '@/Components/BaseModal.vue';

const props = defineProps({
    show: { type: Boolean, default: false },
    workOrder: { type: Object, required: true },
});

const emit = defineEmits(['close', 'print']);

// Show work order option only if status is open or in_progress
const canPrintWorkOrder = computed(() => {
    return ['open', 'in_progress'].includes(props.workOrder.status);
});

// Show payments option only if there are payments
const hasPayments = computed(() => {
    return props.workOrder.total_paid > 0 || (props.workOrder.payments?.length > 0);
});

function printOption(type) {
    emit('print', type);
    emit('close');
}
</script>
