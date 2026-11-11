<template>
    <PrintEngine
        document-type="report"
        class="standard-report-layout bg-white min-h-screen text-gray-900 p-8 print:p-0 print:m-0"
        :class="isRtl ? 'rtl' : 'ltr'"
        :dir="isRtl ? 'rtl' : 'ltr'"
    >
        <!-- Print Controls Bar (hidden during print) -->
        <StandardPrintControls
            v-if="showControls"
            :print-label="printLabel"
            :close-label="closeLabel"
            :on-print="onPrint"
            :on-close="onClose"
        >
            <template #extra-actions>
                <slot name="controls-extra" />
            </template>
        </StandardPrintControls>

        <!-- Official 3-Column Header -->
        <StandardReportHeader
            :title="title"
            :subtitle="subtitle"
            :total-count="totalCount"
            :total-count-label="totalCountLabel"
            :custom-company="customCompany"
            :show-time="showTime"
        >
            <template #company-extra>
                <slot name="header-company-extra" />
            </template>
            <template #title-extra>
                <slot name="header-title-extra" />
            </template>
            <template #metadata>
                <slot name="header-metadata" />
            </template>
        </StandardReportHeader>

        <!-- Main Report Content / Table (Flexible natural width & layout) -->
        <div class="report-content">
            <slot />
        </div>

        <!-- Official Footer (Prepared by & Date/Time, no stamp/signature) -->
        <StandardReportFooter
            v-if="showFooter"
            :prepared-by="preparedBy"
            :prepared-by-label="preparedByLabel"
        >
            <template #center-note>
                <slot name="footer-center-note" />
            </template>
        </StandardReportFooter>
    </PrintEngine>
</template>

<script setup>
import PrintEngine from '@/Components/Print/PrintEngine.vue';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import StandardPrintControls from './StandardPrintControls.vue';
import StandardReportHeader from './StandardReportHeader.vue';
import StandardReportFooter from './StandardReportFooter.vue';

const props = defineProps({
    title: {
        type: String,
        required: true,
    },
    subtitle: {
        type: String,
        default: '',
    },
    totalCount: {
        type: [Number, String],
        default: null,
    },
    totalCountLabel: {
        type: String,
        default: '',
    },
    showControls: {
        type: Boolean,
        default: true,
    },
    showFooter: {
        type: Boolean,
        default: true,
    },
    printLabel: {
        type: String,
        default: '',
    },
    closeLabel: {
        type: String,
        default: '',
    },
    preparedBy: {
        type: String,
        default: '',
    },
    preparedByLabel: {
        type: String,
        default: '',
    },
    customCompany: {
        type: Object,
        default: null,
    },
    showTime: {
        type: Boolean,
        default: true,
    },
    onPrint: {
        type: Function,
        default: null,
    },
    onClose: {
        type: Function,
        default: null,
    },
});

const { locale } = useI18n();
const isRtl = computed(() => locale.value === 'ar');
</script>

<style>
@media print {
    body {
        background: white !important;
        color: black !important;
    }
    .standard-report-layout {
        background: white !important;
        padding: 0 !important;
        margin: 0 !important;
        min-height: auto !important;
    }
    .print\:hidden {
        display: none !important;
    }
}
</style>
