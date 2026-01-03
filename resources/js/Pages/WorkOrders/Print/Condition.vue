<template>
    <div class="print-container bg-white min-h-screen p-8" :dir="isRtl ? 'rtl' : 'ltr'">
        <!-- Reusable Print Header -->
        <PrintHeader 
            :title="isRtl ? 'تقرير حالة المركبة' : 'Vehicle Condition Report'"
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
                <p class="mb-1"><span class="text-gray-500">{{ isRtl ? 'اللون:' : 'Color:' }}</span> <span class="font-bold mr-2">{{ workOrder.vehicle?.color || (isRtl ? 'غير محدد' : 'N/A') }}</span></p>
            </div>
        </div>

        <!-- Entry Info -->
        <div class="grid grid-cols-3 gap-4 mb-6 text-sm">
            <div class="bg-gray-50 p-3 rounded">
                <span class="text-gray-500 block">{{ isRtl ? 'تاريخ الدخول' : 'Entry Date' }}</span>
                <span class="font-bold">{{ formatDate(workOrder.entry_date) }}</span>
            </div>
            <div class="bg-gray-50 p-3 rounded">
                <span class="text-gray-500 block">{{ isRtl ? 'عداد الكيلومترات' : 'Odometer' }}</span>
                <span class="font-bold" dir="ltr">{{ workOrder.mileage ? formatNumber(workOrder.mileage) + ' km' : '-' }}</span>
            </div>
            <div class="bg-gray-50 p-3 rounded">
                <span class="text-gray-500 block">{{ isRtl ? 'مستوى الوقود' : 'Fuel Level' }}</span>
                <span class="font-bold">{{ workOrder.fuel_level || '-' }}%</span>
            </div>
        </div>

        <!-- Vehicle Diagram with Damage Marks -->
        <div class="mb-6">
            <h3 class="text-lg font-bold text-gray-900 mb-3 border-b pb-2">{{ isRtl ? 'حالة المركبة' : 'Vehicle Condition' }}</h3>
            <div class="bg-white rounded-xl p-4 border border-gray-200">
                <svg viewBox="0 0 400 300" class="w-full h-auto max-w-lg mx-auto">
                    <image href="/images/vehicle-diagram.png" x="0" y="0" width="400" height="300" preserveAspectRatio="xMidYMid meet" />
                    <g v-for="(mark, index) in damageMarks" :key="index">
                        <circle :cx="mark.x" :cy="mark.y" r="12" :fill="getColorValue(mark.color)" />
                        <text :x="mark.x" :y="mark.y + 4" text-anchor="middle" class="text-xs font-bold fill-white">{{ index + 1 }}</text>
                    </g>
                </svg>

                <!-- Legend -->
                <div class="flex items-center justify-center gap-6 mt-4 text-sm border-t pt-4">
                    <div class="flex items-center gap-2">
                        <span class="w-4 h-4 rounded-full bg-red-500"></span>
                        <span class="text-gray-600">{{ isRtl ? 'ضرر' : 'Damage' }}</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="w-4 h-4 rounded-full bg-blue-500"></span>
                        <span class="text-gray-600">{{ isRtl ? 'خدش' : 'Scratch' }}</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="w-4 h-4 rounded-full bg-gray-500"></span>
                        <span class="text-gray-600">{{ isRtl ? 'ملاحظة' : 'Note' }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Damage Marks List -->
        <div v-if="damageMarks.length > 0" class="mb-6">
            <h3 class="text-lg font-bold text-gray-900 mb-3 border-b pb-2">{{ isRtl ? 'تفاصيل الحالات' : 'Condition Details' }}</h3>
            <div class="grid grid-cols-2 gap-2 text-sm">
                <div v-for="(mark, index) in damageMarks" :key="index" class="flex items-center gap-3 p-2 bg-gray-50 rounded">
                    <span 
                        class="w-6 h-6 rounded-full flex items-center justify-center text-xs font-bold text-white flex-shrink-0"
                        :style="{ backgroundColor: getColorValue(mark.color) }"
                    >{{ index + 1 }}</span>
                    <span class="text-gray-700">{{ mark.description || (isRtl ? 'بدون وصف' : 'No description') }}</span>
                </div>
            </div>
        </div>

        <!-- Customer Complaint -->
        <div v-if="workOrder.customer_complaint" class="mb-6 p-4 bg-orange-50 rounded-lg border border-orange-200">
            <h3 class="font-bold text-orange-700 mb-2">{{ isRtl ? 'شكوى العميل' : 'Customer Complaint' }}</h3>
            <p class="text-gray-700 whitespace-pre-wrap">{{ workOrder.customer_complaint }}</p>
        </div>

        <!-- Signatures -->
        <div class="mt-8 pt-4 border-t flex justify-between">
            <div><p class="text-sm text-gray-600">{{ isRtl ? 'توقيع الاستلام' : 'Receiver Signature' }}: _________________</p></div>
            <div><p class="text-sm text-gray-600">{{ isRtl ? 'توقيع العميل' : 'Customer Signature' }}: _________________</p></div>
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

const props = defineProps({ workOrder: Object });

const { locale } = useI18n();
const isRtl = computed(() => locale.value === 'ar');

const damageMarks = computed(() => props.workOrder.damage_marks || []);

const vehicleName = computed(() => {
    const make = props.workOrder.vehicle?.make?.name_ar || props.workOrder.vehicle?.make?.name_en || '';
    const model = props.workOrder.vehicle?.model?.name_ar || props.workOrder.vehicle?.model?.name_en || '';
    return `${make} ${model}`.trim() || '-';
});

const formatDate = (date) => {
    if (!date) return '-';
    return new Date(date).toLocaleDateString(isRtl.value ? 'ar-SA' : 'en-US');
};

const formatNumber = (num) => new Intl.NumberFormat(isRtl.value ? 'ar-SA' : 'en-US').format(num || 0);

const getColorValue = (color) => ({ red: '#ef4444', blue: '#3b82f6', gray: '#6b7280' }[color] || '#ef4444');

const printPage = () => window.print();
</script>

<style>
@media print {
    @page { size: A4; margin: 1cm; }
    body { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    .print-container { padding: 0; }
}
</style>
