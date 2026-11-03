/** * Generic report table template for unified print system. * * Provides a consistent table layout
for all report-type prints: * - Customers list, employees, payroll, attendance, etc. * - Supports
custom columns via slot * - Automatically formats currency, dates, and status values * * Usage: *
<TemplateReportTable
    *
    :title="isRtl ? 'قائمة العملاء' : 'Customers'"
    *
    :data="customers"
    *
    :columns="columns"
    *
    :footer-items="customers.length"
    *
>
 *     <template #actions="{ row }">
 *       <button @click="view(row)">View</button>
 *     </template>
 *   </TemplateReportTable>
*/
<script setup>
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { usePrint } from '@/Composables/usePrint';

const props = defineProps({
    /** Table title (Arabic) */
    titleAr: {
        type: String,
        default: '',
    },
    /** Table title (English) */
    titleEn: {
        type: String,
        default: '',
    },
    /** Data array to display */
    data: {
        type: Array,
        default: () => [],
    },
    /** Column definitions: [{ key, label, format? }] */
    columns: {
        type: Array,
        default: () => [],
    },
    /** Total items count (for footer) */
    totalItems: {
        type: Number,
        default: 0,
    },
    /** Report date */
    reportDate: {
        type: String,
        default: '',
    },
    /** Print date format */
    dateFormat: {
        type: String,
        default: 'iso',
    },
    /** Custom footer content */
    showFooter: {
        type: Boolean,
        default: true,
    },
    /** Footer label */
    footerLabel: {
        type: String,
        default: '',
    },
    /** Footer value */
    footerValue: {
        type: [String, Number],
        default: '',
    },
    /** Show print button in header */
    showPrintBtn: {
        type: Boolean,
        default: false,
    },
    /** Print callback */
    onPrint: {
        type: Function,
        default: null,
    },
});

const emit = defineEmits(['print']);

const { locale } = useI18n();
const isRtl = computed(() => locale.value === 'ar');
const printHelpers = usePrint();

const title = computed(() => {
    return isRtl.value ? props.titleAr || props.titleEn : props.titleEn || props.titleAr;
});

const reportDate = computed(() => {
    if (!props.reportDate) return printHelpers.formatDate(new Date().toISOString());
    return props.dateFormat === 'iso'
        ? printHelpers.formatDateISO(props.reportDate)
        : printHelpers.formatDateSlash(props.reportDate);
});

function formatCellValue(cell, column) {
    if (!column?.format) {
        if (typeof cell === 'number' && Math.abs(cell) >= 0.01) {
            return printHelpers.formatRawPrice(cell);
        }
        return cell ?? '-';
    }
    return column.format(cell);
}

function handlePrint() {
    if (props.onPrint) {
        props.onPrint();
    } else {
        window.print();
    }
    emit('print');
}
</script>

<template>
    <div class="print-table-container bg-white">
        <!-- Header -->
        <div class="flex items-center justify-between mb-4 pb-3 border-b border-gray-300">
            <h3 class="text-base font-bold text-gray-900">
                {{ title }}
            </h3>
            <div class="flex items-center gap-4 text-xs text-gray-500">
                <span v-if="reportDate">
                    {{ isRtl ? 'تاريخ التقرير' : 'Report Date' }}: {{ reportDate }}
                </span>
                <span v-if="showPrintBtn" class="print:hidden">
                    <button
                        @click="handlePrint"
                        class="px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded text-xs font-semibold flex items-center gap-1"
                    >
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"
                            />
                        </svg>
                        {{ isRtl ? 'طباعة' : 'Print' }}
                    </button>
                </span>
            </div>
        </div>

        <!-- Table -->
        <table class="w-full text-xs border-collapse">
            <thead>
                <tr class="bg-gray-100 border-b-2 border-gray-800">
                    <th
                        v-for="(col, index) in columns"
                        :key="index"
                        class="py-2 px-2 font-bold text-gray-700"
                        :class="{
                            'text-center': col.align === 'center' || !isRtl,
                            'text-right': col.align === 'right' && isRtl,
                            'text-left': col.align === 'left' && !isRtl,
                        }"
                    >
                        {{ isRtl ? col.labelAr || col.label : col.labelEn || col.label }}
                    </th>
                    <slot name="header" />
                </tr>
            </thead>
            <tbody>
                <tr v-if="data.length === 0" class="border-b border-gray-200">
                    <td :colspan="columns.length + 1" class="py-8 text-center text-gray-500">
                        {{ isRtl ? 'لا توجد بيانات' : 'No data available' }}
                    </td>
                </tr>
                <tr
                    v-for="(row, rowIndex) in data"
                    :key="rowIndex"
                    class="border-b border-gray-100 hover:bg-gray-50/50"
                >
                    <td
                        v-for="(col, colIndex) in columns"
                        :key="colIndex"
                        class="py-2 px-2 text-gray-700"
                        :class="{
                            'text-center': col.align === 'center' || !isRtl,
                            'text-right': col.align === 'right' && isRtl,
                            'text-left': col.align === 'left' && !isRtl,
                            'font-semibold': col.bold,
                            'font-mono': col.mono,
                        }"
                    >
                        {{ formatCellValue(row[col.key], col) }}
                    </td>
                    <slot :name="`row-${rowIndex}`" :row="row" />
                </tr>
            </tbody>
            <tfoot v-if="showFooter && totalItems > 0">
                <tr class="font-bold bg-gray-50 border-t-2 border-gray-300">
                    <td :colspan="columns.length - 1" class="py-2 px-2 text-center">
                        {{ footerLabel || (isRtl ? 'الإجمالي' : 'Total') }}
                    </td>
                    <td class="py-2 px-2 text-center font-mono">
                        {{ footerValue ?? totalItems }}
                    </td>
                </tr>
            </tfoot>
        </table>

        <!-- Items count -->
        <div v-if="data.length > 0" class="mt-2 text-xs text-gray-500 text-center">
            {{ isRtl ? `إجمالي ${data.length} عنصر` : `Total: ${data.length} items` }}
        </div>
    </div>
</template>

<style scoped>
@media print {
    .print-table-container {
        background: white !important;
    }
    .print\\:hidden {
        display: none !important;
    }
    table {
        page-break-inside: auto;
    }
    tr {
        page-break-inside: avoid;
        page-break-after: avoid;
    }
}
</style>
