<template>
    <div class="print-container bg-white min-h-screen p-8" :dir="isRtl ? 'rtl' : 'ltr'">
        <!-- Reusable Print Header -->
        <PrintHeader 
            :title="isRtl ? 'سند المدفوعات' : 'Payments Receipt'"
            :subtitle="workOrder.code"
            :work-order="workOrder"
        />

        <!-- Customer & Vehicle Info -->
        <div class="grid grid-cols-2 gap-6 mb-6 p-4 bg-gray-50 rounded-lg">
            <div>
                <p class="mb-1"><span class="text-gray-500">{{ isRtl ? 'العميل:' : 'Customer:' }}</span> <span class="font-bold mr-2">{{ workOrder.customer?.name }}</span></p>
                <p class="mb-1"><span class="text-gray-500">{{ isRtl ? 'الهاتف:' : 'Phone:' }}</span> <span class="font-bold mr-2" dir="ltr">{{ workOrder.customer?.phone }}</span></p>
            </div>
            <div :class="isRtl ? 'text-left' : 'text-right'">
                <p class="mb-1"><span class="text-gray-500">{{ isRtl ? 'المركبة:' : 'Vehicle:' }}</span> <span class="font-bold mr-2">{{ vehicleName }}</span></p>
                <p class="mb-1"><span class="text-gray-500">{{ isRtl ? 'اللوحة:' : 'Plate:' }}</span> <span class="font-bold mr-2" dir="ltr">{{ workOrder.vehicle?.plate_number }}</span></p>
            </div>
        </div>

        <!-- Payments Table -->
        <div class="mb-6">
            <h3 class="text-lg font-bold text-gray-900 mb-3 border-b pb-2">{{ isRtl ? 'المدفوعات' : 'Payments' }}</h3>
            
            <div v-if="payments.length > 0">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b-2 border-gray-300">
                            <th class="py-2 text-right">#</th>
                            <th class="py-2 text-right">{{ isRtl ? 'التاريخ' : 'Date' }}</th>
                            <th class="py-2 text-right">{{ isRtl ? 'طريقة الدفع' : 'Method' }}</th>
                            <th class="py-2 text-right">{{ isRtl ? 'رقم المرجع' : 'Reference' }}</th>
                            <th class="py-2 text-left">{{ isRtl ? 'المبلغ' : 'Amount' }}</th>
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
                {{ isRtl ? 'لا توجد مدفوعات مسجلة' : 'No payments recorded' }}
            </div>
        </div>

        <!-- Summary -->
        <div class="mb-6 flex justify-end">
            <table class="w-64 text-sm">
                <tbody>
                    <tr class="border-b">
                        <td class="py-2 text-gray-600">{{ isRtl ? 'إجمالي الفاتورة' : 'Invoice Total' }}</td>
                        <td class="py-2 text-left font-medium" dir="ltr">{{ formatPrice(grandTotal) }}</td>
                    </tr>
                    <tr class="border-b bg-green-50">
                        <td class="py-2 font-bold text-green-600">{{ isRtl ? 'إجمالي المدفوع' : 'Total Paid' }}</td>
                        <td class="py-2 text-left font-bold text-green-600" dir="ltr">{{ formatPrice(totalPaid) }}</td>
                    </tr>
                    <tr class="bg-gray-100">
                        <td class="py-2 font-bold" :class="balance > 0 ? 'text-red-600' : 'text-green-600'">{{ isRtl ? 'المتبقي' : 'Balance' }}</td>
                        <td class="py-2 text-left font-bold" :class="balance > 0 ? 'text-red-600' : 'text-green-600'" dir="ltr">{{ formatPrice(balance) }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Signatures -->
        <div class="mt-8 pt-4 border-t flex justify-between text-sm text-gray-600">
            <div>
                <p>{{ isRtl ? 'توقيع المحاسب' : 'Accountant' }}: _________________</p>
            </div>
            <div>
                <p>{{ isRtl ? 'توقيع العميل' : 'Customer' }}: _________________</p>
            </div>
        </div>

        <!-- Print Button -->
        <div class="fixed bottom-4 left-4 print:hidden">
            <button @click="printPage" class="px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-lg shadow-lg flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                </svg>
                {{ isRtl ? 'طباعة' : 'Print' }}
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
    const labels = isRtl.value
        ? { cash: 'نقداً', card: 'بطاقة', transfer: 'تحويل بنكي', credit: 'آجل' }
        : { cash: 'Cash', card: 'Card', transfer: 'Bank Transfer', credit: 'Credit' };
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
