<template>
    <div class="print-container bg-white min-h-screen p-8" :dir="isRtl ? 'rtl' : 'ltr'">
        <!-- Reusable Print Header -->
        <PrintHeader 
            :title="isRtl ? 'فاتورة أولية' : 'Proforma Invoice'"
            :subtitle="workOrder.code"
            :work-order="workOrder"
        />

        <!-- Work Order Info -->
        <div class="grid grid-cols-3 gap-4 mb-6 text-sm">
            <div>
                <span class="text-gray-500">{{ isRtl ? 'رقم كرت الصيانة:' : 'Work Order No:' }}</span>
                <span class="font-bold mr-2">{{ workOrder.code }}</span>
            </div>
            <div>
                <span class="text-gray-500">{{ isRtl ? 'تاريخ الفاتورة:' : 'Invoice Date:' }}</span>
                <span class="font-bold mr-2">{{ formatDate(new Date()) }}</span>
            </div>
            <div>
                <span class="text-gray-500">{{ isRtl ? 'تاريخ الدخول:' : 'Entry Date:' }}</span>
                <span class="font-bold mr-2">{{ formatDate(workOrder.entry_date) }}</span>
            </div>
        </div>

        <!-- Customer & Vehicle Info -->
        <div class="grid grid-cols-2 gap-6 mb-6 p-4 bg-gray-50 rounded-lg">
            <!-- Customer Info -->
            <div>
                <p class="mb-1">
                    <span class="text-gray-500">{{ isRtl ? 'اسم العميل:' : 'Customer:' }}</span>
                    <span class="font-bold mr-2">{{ workOrder.customer?.name }}</span>
                </p>
                <p v-if="workOrder.contact_name" class="mb-1">
                    <span class="text-gray-500">{{ isRtl ? 'اسم المسؤول:' : 'Contact:' }}</span>
                    <span class="font-bold mr-2">{{ workOrder.contact_name }}</span>
                </p>
                <p v-if="workOrder.customer?.phone" class="mb-1">
                    <span class="text-gray-500">{{ isRtl ? 'الهاتف:' : 'Phone:' }}</span>
                    <span class="font-bold mr-2" dir="ltr">{{ workOrder.customer?.phone }}</span>
                </p>
            </div>

            <!-- Vehicle Info -->
            <div :class="isRtl ? 'text-left' : 'text-right'">
                <p class="mb-1">
                    <span class="text-gray-500">{{ isRtl ? 'الماركة:' : 'Make:' }}</span>
                    <span class="font-bold mr-2">{{ vehicleName }}</span>
                </p>
                <p class="mb-1">
                    <span class="text-gray-500">{{ isRtl ? 'اللون:' : 'Color:' }}</span>
                    <span class="font-bold mr-2">{{ workOrder.vehicle?.color || (isRtl ? 'غير محدد' : 'N/A') }}</span>
                </p>
                <p class="mb-1">
                    <span class="text-gray-500">{{ isRtl ? 'رقم اللوحة:' : 'Plate:' }}</span>
                    <span class="font-bold mr-2" dir="ltr">{{ workOrder.vehicle?.plate_number }}</span>
                </p>
                <p v-if="workOrder.vehicle?.vin" class="mb-1">
                    <span class="text-gray-500">{{ isRtl ? 'رقم الهيكل:' : 'VIN:' }}</span>
                    <span class="font-bold mr-2" dir="ltr">{{ workOrder.vehicle?.vin }}</span>
                </p>
            </div>
        </div>

        <!-- Services Section -->
        <div class="mb-6">
            <h3 class="text-lg font-bold text-gray-900 mb-3 border-b pb-2">{{ isRtl ? 'الخدمات' : 'Services' }}</h3>
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b-2 border-gray-300">
                        <th class="py-2 text-right w-10">#</th>
                        <th class="py-2 text-right">{{ isRtl ? 'الوصف' : 'Description' }}</th>
                        <th class="py-2 text-center w-24">{{ isRtl ? 'السعر' : 'Price' }}</th>
                        <th class="py-2 text-center w-24 text-red-600">{{ isRtl ? 'الخصم' : 'Disc.' }}</th>
                        <th class="py-2 text-left w-24">{{ isRtl ? 'المبلغ' : 'Total' }}</th>
                    </tr>
                </thead>
                <tbody>
                    <template v-for="(items, deptId) in itemsByDepartment" :key="deptId">
                        <tr v-for="(item, idx) in items" :key="item.id" class="border-b border-gray-200">
                            <td class="py-2 text-right text-gray-500">{{ idx + 1 }}</td>
                            <td class="py-2 text-right">{{ item.title || item.service?.name_ar || item.service?.name_en }}</td>
                            <td class="py-2 text-center" dir="ltr">{{ formatPrice(item.unit_price * item.qty) }}</td>
                            <td class="py-2 text-center text-red-600" dir="ltr">{{ formatPrice(getDiscount(item)) }}</td>
                            <td class="py-2 text-left font-medium" dir="ltr">{{ formatPrice(item.line_total || (item.qty * item.unit_price)) }}</td>
                        </tr>
                    </template>
                </tbody>
                <tfoot>
                    <tr class="font-bold">
                        <td colspan="4" class="py-2 text-left">{{ isRtl ? 'مجموع الخدمات:' : 'Services Total:' }}</td>
                        <td class="py-2 text-left" dir="ltr">{{ formatPrice(servicesTotal) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <!-- Parts Section -->
        <div v-if="allParts.length > 0" class="mb-6">
            <h3 class="text-lg font-bold text-gray-900 mb-3 border-b pb-2">{{ isRtl ? 'قطع الغيار' : 'Parts' }}</h3>
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b-2 border-gray-300">
                        <th class="py-2 text-right w-10">#</th>
                        <th class="py-2 text-right">{{ isRtl ? 'الوصف' : 'Description' }}</th>
                        <th class="py-2 text-center w-24">{{ isRtl ? 'السعر' : 'Price' }}</th>
                        <th class="py-2 text-center w-20 text-red-600">{{ isRtl ? 'الخصم' : 'Disc.' }}</th>
                        <th class="py-2 text-center w-20">{{ isRtl ? 'الكمية' : 'Qty' }}</th>
                        <th class="py-2 text-left w-24">{{ isRtl ? 'المبلغ' : 'Total' }}</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="(part, idx) in allParts" :key="part.id" class="border-b border-gray-200">
                        <td class="py-2 text-right text-gray-500">{{ idx + 1 }}</td>
                        <td class="py-2 text-right">{{ part.name || part.part?.name_ar || part.part?.name_en }}</td>
                        <td class="py-2 text-center" dir="ltr">{{ formatPrice(part.unit_price) }}</td>
                        <td class="py-2 text-center text-red-600" dir="ltr">0.00</td>
                        <td class="py-2 text-center" dir="ltr">{{ part.qty }}</td>
                        <td class="py-2 text-left font-medium" dir="ltr">{{ formatPrice(part.qty * part.unit_price) }}</td>
                    </tr>
                </tbody>
                <tfoot>
                    <tr class="font-bold">
                        <td colspan="5" class="py-2 text-left">{{ isRtl ? 'مجموع قطع الغيار:' : 'Parts Total:' }}</td>
                        <td class="py-2 text-left" dir="ltr">{{ formatPrice(partsTotal) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <!-- Summary -->
        <div class="mb-6 flex justify-end">
            <table class="w-64 text-sm">
                <tbody>
                    <tr class="border-b">
                        <td class="py-2 text-gray-600">{{ isRtl ? 'الخدمات' : 'Services' }}</td>
                        <td class="py-2 text-left font-medium" dir="ltr">{{ formatPrice(servicesTotal) }}</td>
                    </tr>
                    <tr v-if="partsTotal > 0" class="border-b">
                        <td class="py-2 text-gray-600">{{ isRtl ? 'قطع الغيار' : 'Parts' }}</td>
                        <td class="py-2 text-left font-medium" dir="ltr">{{ formatPrice(partsTotal) }}</td>
                    </tr>
                    <tr class="border-b bg-gray-50">
                        <td class="py-2 font-bold">{{ isRtl ? 'المجموع' : 'Total' }}</td>
                        <td class="py-2 text-left font-bold" dir="ltr">{{ formatPrice(grandTotal) }}</td>
                    </tr>
                    <tr v-if="totalPaid > 0" class="border-b">
                        <td class="py-2 text-green-600">{{ isRtl ? 'المدفوع' : 'Paid' }}</td>
                        <td class="py-2 text-left font-medium text-green-600" dir="ltr">{{ formatPrice(totalPaid) }}</td>
                    </tr>
                    <tr class="bg-gray-100">
                        <td class="py-2 font-bold text-red-600">{{ isRtl ? 'الباقي' : 'Balance' }}</td>
                        <td class="py-2 text-left font-bold text-red-600" dir="ltr">{{ formatPrice(balance) }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Notes -->
        <div v-if="workOrder.notes" class="mb-6 p-4 bg-gray-50 rounded-lg">
            <h3 class="text-sm font-bold text-gray-700 mb-2">{{ isRtl ? 'ملاحظات' : 'Notes' }}</h3>
            <p class="text-sm text-gray-600 whitespace-pre-wrap">{{ workOrder.notes }}</p>
        </div>

        <!-- Footer Signatures -->
        <div class="mt-8 pt-4 border-t border-gray-200">
            <div class="flex justify-between items-start">
                <!-- Center Stamp -->
                <div class="text-center">
                    <div v-if="workOrder.center?.stamp_url" class="w-24 h-24 mx-auto mb-2">
                        <img :src="workOrder.center.stamp_url" alt="Stamp" class="w-full h-full object-contain" />
                    </div>
                    <p class="text-sm text-gray-600">{{ workOrder.center?.name }}</p>
                </div>

                <!-- Signatures -->
                <div :class="['space-y-4', isRtl ? 'text-left' : 'text-right']">
                    <div>
                        <p class="text-sm text-gray-600">{{ isRtl ? 'توقيع المدير' : 'Manager Signature' }}: _________________</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">{{ isRtl ? 'توقيع العميل' : 'Customer Signature' }}: _________________</p>
                    </div>
                </div>
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
    itemsByDepartment: Object,
    allParts: Array,
    departments: Object,
    servicesTotal: Number,
    partsTotal: Number,
    grandTotal: Number,
    totalPaid: Number,
    balance: Number,
    taxSettings: Object,
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

const getDiscount = (item) => {
    if (!item.discount_value || item.discount_type === 'none') return 0;
    if (item.discount_type === 'fixed') return item.discount_value;
    if (item.discount_type === 'percentage') {
        return (item.qty * item.unit_price) * (item.discount_value / 100);
    }
    return 0;
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
