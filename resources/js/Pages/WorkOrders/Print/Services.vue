<template>
    <div class="print-container bg-white min-h-screen p-8" :dir="isRtl ? 'rtl' : 'ltr'">
        <!-- Reusable Print Header -->
        <PrintHeader 
            :title="isRtl ? 'أمر العمل' : 'Work Order'"
            :subtitle="workOrder.code"
            :work-order="workOrder"
        />

        <!-- Customer & Vehicle Info -->
        <div class="grid grid-cols-2 gap-6 mb-6 p-4 bg-gray-50 rounded-lg">
            <div>
                <p class="mb-1"><span class="text-gray-500">{{ isRtl ? 'العميل:' : 'Customer:' }}</span> <span class="font-bold mr-2">{{ workOrder.customer?.name }}</span></p>
                <p class="mb-1"><span class="text-gray-500">{{ isRtl ? 'الهاتف:' : 'Phone:' }}</span> <span class="font-bold mr-2" dir="ltr">{{ workOrder.customer?.phone }}</span></p>
                <p class="mb-1"><span class="text-gray-500">{{ isRtl ? 'تاريخ الدخول:' : 'Entry Date:' }}</span> <span class="font-bold mr-2">{{ formatDate(workOrder.entry_date) }}</span></p>
            </div>
            <div :class="isRtl ? 'text-left' : 'text-right'">
                <p class="mb-1"><span class="text-gray-500">{{ isRtl ? 'المركبة:' : 'Vehicle:' }}</span> <span class="font-bold mr-2">{{ vehicleName }}</span></p>
                <p class="mb-1"><span class="text-gray-500">{{ isRtl ? 'اللوحة:' : 'Plate:' }}</span> <span class="font-bold mr-2" dir="ltr">{{ workOrder.vehicle?.plate_number }}</span></p>
            </div>
        </div>

        <!-- Services by Department -->
        <div class="mb-6">
            <h3 class="text-lg font-bold text-gray-900 mb-3 border-b pb-2">{{ isRtl ? 'الخدمات المطلوبة' : 'Required Services' }}</h3>
            
            <div v-for="(items, deptId) in itemsByDepartment" :key="deptId" class="mb-4">
                <h4 class="font-semibold text-gray-700 bg-gray-100 px-3 py-2 rounded">
                    {{ getDepartmentName(deptId) }}
                </h4>
                <table class="w-full text-sm mt-2">
                    <thead>
                        <tr class="border-b">
                            <th class="py-2 text-right w-10">#</th>
                            <th class="py-2 text-right">{{ isRtl ? 'الخدمة' : 'Service' }}</th>
                            <th class="py-2 text-center w-32">{{ isRtl ? 'الفني' : 'Technician' }}</th>
                            <th class="py-2 text-center w-20">{{ isRtl ? 'الحالة' : 'Status' }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(item, idx) in items" :key="item.id" class="border-b border-gray-100">
                            <td class="py-2 text-right text-gray-500">{{ idx + 1 }}</td>
                            <td class="py-2 text-right">{{ item.title || item.service?.name_ar || item.service?.name_en }}</td>
                            <td class="py-2 text-center">{{ item.technicians?.[0]?.name || '-' }}</td>
                            <td class="py-2 text-center">
                                <span class="px-2 py-0.5 rounded text-xs" :class="getStatusClass(item.status)">
                                    {{ getStatusLabel(item.status) }}
                                </span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Customer Complaint -->
        <div v-if="workOrder.customer_complaint" class="mb-6 p-4 bg-orange-50 rounded-lg border border-orange-200">
            <h3 class="font-bold text-orange-700 mb-2">{{ isRtl ? 'شكوى العميل' : 'Customer Complaint' }}</h3>
            <p class="text-gray-700 whitespace-pre-wrap">{{ workOrder.customer_complaint }}</p>
        </div>

        <!-- Signatures -->
        <div class="mt-8 pt-4 border-t grid grid-cols-3 gap-4 text-center text-sm text-gray-600">
            <div>
                <p class="mb-8">{{ isRtl ? 'توقيع المدير' : 'Manager' }}</p>
                <p class="border-t pt-2">_________________</p>
            </div>
            <div>
                <p class="mb-8">{{ isRtl ? 'توقيع الفني' : 'Technician' }}</p>
                <p class="border-t pt-2">_________________</p>
            </div>
            <div>
                <p class="mb-8">{{ isRtl ? 'توقيع العميل' : 'Customer' }}</p>
                <p class="border-t pt-2">_________________</p>
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
    departments: Object,
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

const getDepartmentName = (deptId) => {
    const dept = props.departments?.[deptId];
    if (dept) return isRtl.value ? dept.name_ar : dept.name_en;
    return isRtl.value ? 'خدمات أخرى' : 'Other Services';
};

const getStatusClass = (status) => ({
    pending: 'bg-gray-100 text-gray-600',
    in_progress: 'bg-blue-100 text-blue-600',
    completed: 'bg-green-100 text-green-600',
    cancelled: 'bg-red-100 text-red-600',
}[status] || 'bg-gray-100 text-gray-600');

const getStatusLabel = (status) => {
    const labels = isRtl.value 
        ? { pending: 'معلق', in_progress: 'قيد العمل', completed: 'مكتمل', cancelled: 'ملغي' }
        : { pending: 'Pending', in_progress: 'In Progress', completed: 'Completed', cancelled: 'Cancelled' };
    return labels[status] || status;
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
