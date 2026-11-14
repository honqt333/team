<template>
    <PrintEngine
        document-type="report"
        class="standard-report-layout print-container bg-white min-h-screen text-gray-900 p-8 print:p-0 print:m-0"
        :class="isRtl ? 'rtl' : 'ltr'"
        :dir="isRtl ? 'rtl' : 'ltr'"
    >
        <!-- Print Controls Bar (hidden during print) -->
        <StandardPrintControls
            v-if="showControls"
            v-model:scale="printScale"
            :print-label="printLabel"
            :close-label="closeLabel"
            :on-print="onPrint"
            :on-close="resolvedOnClose"
        >
            <template #extra-actions>
                <button
                    v-if="showOrientationToggle"
                    type="button"
                    @click="toggleOrientation"
                    class="inline-flex items-center gap-1.5 px-3 py-2 bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-200 border border-gray-200 dark:border-gray-600 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-600 font-semibold transition-all text-xs"
                >
                    <svg
                        class="w-4 h-4 shrink-0 transition-transform duration-200"
                        :class="orientation === 'landscape' ? 'rotate-90' : ''"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"
                        />
                    </svg>
                    <span>
                        {{
                            orientation === 'landscape'
                                ? $t('common.landscape')
                                : $t('common.portrait')
                        }}
                    </span>
                </button>
                <slot name="controls-extra" />
            </template>
        </StandardPrintControls>

        <div :style="printScaleStyle">
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
        </div>
    </PrintEngine>
</template>

<script setup>
import PrintEngine from '@/Components/Print/PrintEngine.vue';
import { usePrintScale } from '@/Composables/usePrint';
import { computed, ref, watch, onMounted, onBeforeUnmount } from 'vue';
import { useI18n } from 'vue-i18n';
import { router } from '@inertiajs/vue3';
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
    backRoute: {
        type: String,
        default: '',
    },
    backRouteParams: {
        type: [Array, Object, String, Number],
        default: () => [],
    },
    defaultOrientation: {
        type: String,
        default: 'portrait',
        validator: (val) => ['portrait', 'landscape'].includes(val),
    },
    showOrientationToggle: {
        type: Boolean,
        default: true,
    },
});

const { locale } = useI18n();
const isRtl = computed(() => locale.value === 'ar');
const { printScale, printScaleStyle } = usePrintScale();

const orientation = ref(props.defaultOrientation);
function toggleOrientation() {
    orientation.value = orientation.value === 'portrait' ? 'landscape' : 'portrait';
}

let dynamicPageStyleEl = null;
function applyReportPageStyle() {
    if (typeof document === 'undefined') return;
    if (!dynamicPageStyleEl) {
        dynamicPageStyleEl = document.createElement('style');
        dynamicPageStyleEl.setAttribute('data-report-layout-page', 'true');
        document.head.appendChild(dynamicPageStyleEl);
    }
    dynamicPageStyleEl.textContent = `@media print { @page { size: A4 ${orientation.value}; margin: 15mm 10mm 12mm 10mm; } }`;
}

const resolvedOnClose = computed(() => {
    if (props.onClose) return props.onClose;
    if (!props.backRoute) return null;
    return () => {
        if (window.opener) {
            window.close();
            setTimeout(() => {
                if (!window.closed) {
                    router.visit(route(props.backRoute, props.backRouteParams));
                }
            }, 150);
            return;
        }
        if (window.history.length > 1) {
            window.history.back();
        } else {
            router.visit(route(props.backRoute, props.backRouteParams));
        }
    };
});

function handleKeyDown(event) {
    if (event.key === 'Escape' && props.showControls) {
        if (resolvedOnClose.value) {
            resolvedOnClose.value();
        } else if (window.opener) {
            window.close();
        } else if (window.history.length > 1) {
            window.history.back();
        } else {
            window.close();
        }
    }
}

onMounted(() => {
    window.addEventListener('keydown', handleKeyDown);
    applyReportPageStyle();
});

watch(orientation, () => {
    applyReportPageStyle();
});

onBeforeUnmount(() => {
    window.removeEventListener('keydown', handleKeyDown);
    if (dynamicPageStyleEl && dynamicPageStyleEl.parentNode) {
        dynamicPageStyleEl.parentNode.removeChild(dynamicPageStyleEl);
        dynamicPageStyleEl = null;
    }
});
</script>

<style>
@media print {
    body {
        background: white !important;
        color: black !important;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }
    .standard-report-layout {
        background: white !important;
        padding: 0 !important;
        margin: 0 !important;
        min-height: auto !important;
    }
    .standard-report-layout table thead {
        display: table-header-group;
    }
    .standard-report-layout table tr {
        break-inside: avoid;
        page-break-inside: avoid;
    }
    .print\:hidden {
        display: none !important;
    }
}
</style>
