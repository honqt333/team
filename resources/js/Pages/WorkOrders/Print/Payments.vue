<template>
    <div class="print-container bg-white min-h-screen p-8" :dir="isRtl ? 'rtl' : 'ltr'">
        <!-- Reusable Print Header -->
        <PrintHeader 
            :title="$t('work_orders.print_view.payments')"
            :subtitle="workOrder.code"
            :work-order="workOrder"
        />

        <!-- Customer & Vehicle Info -->
        <div class="grid grid-cols-2 gap-6 mb-6 p-4 bg-gray-50 rounded-lg">
            <div>
                <p class="mb-1"><span class="text-gray-500">{{ $t('work_orders.print_view.customer') }}:</span> <span class="font-bold mr-2">{{ workOrder.customer?.name }}</span></p>
                <p class="mb-1"><span class="text-gray-500">{{ $t('work_orders.print_view.phone') }}:</span> <span class="font-bold mr-2" dir="ltr">{{ workOrder.customer?.phone }}</span></p>
            </div>
            <div :class="isRtl ? 'text-left' : 'text-right'">
                <p class="mb-1"><span class="text-gray-500">{{ $t('work_orders.print_view.vehicle') }}:</span> <span class="font-bold mr-2">{{ vehicleName }}</span></p>
                <p class="mb-1"><span class="text-gray-500">{{ $t('work_orders.print_view.plate') }}:</span> <span class="font-bold mr-2" dir="ltr">{{ workOrder.vehicle?.plate_number }}</span></p>
            </div>
        </div>

        <!-- Payments Table -->
        <div class="mb-6">
            <h3 class="text-lg font-bold text-gray-900 mb-3 border-b pb-2">{{ $t('work_orders.print_view.payments') }}</h3>
            
            <div v-if="payments.length > 0">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b-2 border-gray-300">
                            <th class="py-2 text-right">#</th>
                            <th class="py-2 text-right">{{ $t('work_orders.print_view.date') }}</th>
                            <th class="py-2 text-right">{{ $t('work_orders.print_view.method') }}</th>
                            <th class="py-2 text-right">{{ $t('work_orders.print_view.reference') }}</th>
                            <th class="py-2 text-left">{{ $t('work_orders.print_view.amount') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(payment, idx) in payments" :key="payment.id" class="border-b border-gray-200">
                            <td class="py-2 text-right text-gray-500">{{ idx + 1 }}</td>
                            <td class="py-2 text-right">{{ formatDate(payment.payment_date) }}</td>
                            <td class="py-2 text-right">{{ getMethodLabel(payment.payment_method) }}</td>
                            <td class="py-2 text-right" dir="ltr">{{ payment.reference || '-' }}</td>
                            <td class="py-2 text-left font-medium" dir="ltr">{{ formatPrice(payment.amount) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div v-else class="text-center py-8 text-gray-400">
                {{ $t('work_orders.print_view.no_payments') }}
            </div>
        </div>

        <!-- Summary -->
        <div class="mb-6 flex justify-end">
            <table class="w-64 text-sm">
                <tbody>
                    <tr class="border-b">
                        <td class="py-2 text-gray-600">{{ $t('work_orders.print_view.invoice_total') }}</td>
                        <td class="py-2 text-left font-medium" dir="ltr">{{ formatPrice(grandTotal) }}</td>
                    </tr>
                    <tr class="border-b bg-green-50">
                        <td class="py-2 font-bold text-green-600">{{ $t('work_orders.print_view.total_paid') }}</td>
                        <td class="py-2 text-left font-bold text-green-600" dir="ltr">{{ formatPrice(totalPaid) }}</td>
                    </tr>
                    <tr class="bg-gray-100">
                        <td class="py-2 font-bold" :class="balance > 0 ? 'text-red-600' : 'text-green-600'">{{ $t('work_orders.print_view.balance') }}</td>
                        <td class="py-2 text-left font-bold" :class="balance > 0 ? 'text-red-600' : 'text-green-600'" dir="ltr">{{ formatPrice(balance) }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Signatures -->
        <div class="mt-8 pt-4 border-t flex justify-between text-sm text-gray-600">
            <div>
                <p>{{ $t('work_orders.print_view.accountant_signature') }}: _________________</p>
            </div>
            <div>
                <p>{{ $t('work_orders.print_view.customer_signature') }}: _________________</p>
            </div>
        </div>

        <!-- Print Button -->
        <div class="fixed bottom-4 left-4 print:hidden">
            <button @click="printPage" class="px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-lg shadow-lg flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                </svg>
                {{ $t('work_orders.print_view.print_btn') }}
            </button>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import PrintHeader from '@/Components/Print/PrintHeader.vue';

const props = defineProps({
    workOrder: Object,
    payments: Array,
    grandTotal: Number,
    totalPaid: Number,
    balance: Number,
});

const { locale } = useI18n();
const isRtl = computed(() => locale.value === 'ar');

const vehicleName = computed(() => {
    const make = props.workOrder.vehicle?.make?.name_ar || props.workOrder.vehicle?.make?.name_en || '';
    const model = props.workOrder.vehicle?.model?.name_ar || props.workOrder.vehicle?.model?.name_en || '';
    return `${make} ${model}`.trim() || '-';
});

const formatDate = (date) => {
    if (!date) return '-';
    return new Date(date).toLocaleDateString(isRtl.value ? 'ar-SA' : 'en-US');
};

const formatPrice = (amount) => {
    return new Intl.NumberFormat(isRtl.value ? 'ar-SA' : 'en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }).format(amount || 0);
};

const getMethodLabel = (method) => {
    const labels = {
        cash: isRtl.value ? 'نقداً' : 'Cash',
        card: isRtl.value ? 'بطاقة' : 'Card',
        transfer: isRtl.value ? 'تحويل بنكي' : 'Bank Transfer',
        credit: isRtl.value ? 'آجل' : 'Credit'
    };
    return labels[method] || method;
};

const printPage = () => window.print();
</script>

<style>
@media print {
    @page { size: A4; margin: 1cm; }
    body { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    .print-container { padding: 0; }
}
</style>
