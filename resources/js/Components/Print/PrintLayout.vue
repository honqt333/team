<template>
    <div
        class="print-container bg-white min-h-screen p-8 print:p-0 print:m-0 flex flex-col items-center print:block"
        :class="[isRtl ? 'rtl' : 'ltr', isThermalTemplate ? 'print-thermal' : 'print-a4']"
        :dir="isRtl ? 'rtl' : 'ltr'"
    >
        <!-- Floating Controls Bar (Hidden during print) -->
        <StandardPrintControls
            v-model:scale="printScale"
            layout="floating"
            :print-label="printLabel"
            :close-label="backLabel"
            :primary-color="resolvedVisualSettings.primary_color"
            :on-print="handlePrint"
            :on-close="handleBack"
        >
            <template #extra-actions><slot name="controls-extra" /></template>
        </StandardPrintControls>

        <!-- Document Content: Slot or Standard PrintEngine -->
        <div class="w-full flex flex-col items-center print:block" :style="printScaleStyle">
            <slot>
                <PrintEngine
                    :documentType="documentType"
                    :data="data"
                    :centerData="resolvedCenterData"
                    :documentSettings="resolvedDocumentSettings"
                    :visualSettings="resolvedVisualSettings"
                    :previewMode="previewMode"
                />
            </slot>
        </div>
    </div>
</template>

<script setup>
import {
    printDocument,
    resolveDocumentSettings,
    resolveVisualSettings,
    usePrintScale,
} from '@/Composables/usePrint';

import { computed, onMounted, onBeforeUnmount, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import { usePage, router } from '@inertiajs/vue3';
import PrintEngine from '@/Components/Print/PrintEngine.vue';
import StandardPrintControls from '@/Components/Print/StandardPrintControls.vue';

const props = defineProps({
    /** Document type for PrintEngine (e.g., 'invoice', 'work_order', 'quotation') */
    documentType: {
        type: String,
        default: 'invoice',
    },
    /** Document payload data */
    data: {
        type: Object,
        default: () => ({}),
    },
    /** Center/Company data override */
    centerData: {
        type: Object,
        default: null,
    },
    /** Document-level settings override */
    documentSettings: {
        type: Object,
        default: null,
    },
    /** Visual settings override */
    visualSettings: {
        type: Object,
        default: null,
    },
    /** Custom back URL (e.g. route('work-orders.show', 10)) */
    backUrl: {
        type: String,
        default: '',
    },
    /** Custom back callback function */
    onBack: {
        type: Function,
        default: null,
    },
    /** Custom print callback function */
    onPrint: {
        type: Function,
        default: null,
    },
    /** Custom print button label */
    printLabel: {
        type: String,
        default: '',
    },
    /** Custom back button label */
    backLabel: {
        type: String,
        default: '',
    },
    /** Preview mode toggle */
    previewMode: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['print', 'back']);

const { locale } = useI18n();
const isRtl = computed(() => locale.value === 'ar');
const page = usePage();
const { printScale, printScaleStyle } = usePrintScale();

// Resolve Center & Company Data with safe defaults
const resolvedCenterData = computed(() => {
    if (props.centerData && Object.keys(props.centerData).length > 0) {
        return props.centerData;
    }

    const center = props.data?.center || page?.props?.center || page?.props?.currentCenter || {};
    const tenant = page?.props?.tenant || {};

    return {
        name: isRtl.value
            ? center.name_ar || center.name || tenant.name
            : center.name_en || center.name || tenant.name,
        tax_number: center.vat_number || tenant.vat_number || '',
        cr_number: tenant.cr_number || '',
        phone: center.phone || tenant.phone || '',
        logo: center.logo_invoice_url || center.logo_light_url || tenant.logo_url || '',
        iban: tenant.iban || '',
        address: center.address || tenant.address || '',
        stamp_url: center.stamp_url || '',
    };
});

// Resolve Visual Settings from tenant or props
const resolvedVisualSettings = computed(() => {
    if (props.visualSettings && Object.keys(props.visualSettings).length > 0) {
        return props.visualSettings;
    }

    return resolveVisualSettings({
        tenantSettings: page?.props?.tenant?.print_settings,
        center: props.data?.center || page?.props?.center || {},
    });
});

// Resolve Document-specific Settings
const resolvedDocumentSettings = computed(() => {
    if (props.documentSettings && Object.keys(props.documentSettings).length > 0) {
        return props.documentSettings;
    }

    const docType = props.documentType === 'quote' ? 'quotation' : props.documentType;
    return resolveDocumentSettings({
        tenantSettings: page?.props?.tenant?.print_settings,
        documentType: docType,
        customerSignatureUrl:
            props.data?.signature_url ||
            props.data?.customer_signature ||
            props.data?.reception_signature ||
            props.data?.delivery_signature ||
            null,
    });
});

const effectiveTemplateName = computed(
    () =>
        resolvedDocumentSettings.value?.template ||
        resolvedVisualSettings.value?.active_template ||
        'TemplateDefaultA4'
);

const isThermalTemplate = computed(() =>
    ['TemplateThermal80', 'TemplateSleekThermal'].includes(effectiveTemplateName.value)
);

// Sync dynamic @page size and margin so A4 gets proper top/side margins and Thermal80 gets 80mm roll margins
let dynamicPageStyleEl = null;
function applyDynamicPageStyle() {
    if (typeof document === 'undefined' || props.previewMode) return;
    if (!dynamicPageStyleEl) {
        dynamicPageStyleEl = document.createElement('style');
        dynamicPageStyleEl.setAttribute('data-print-layout-page', 'true');
        document.head.appendChild(dynamicPageStyleEl);
    }
    dynamicPageStyleEl.textContent = isThermalTemplate.value
        ? '@media print { @page { size: 80mm auto; margin: 4mm 3mm; } body { -webkit-print-color-adjust: exact; print-color-adjust: exact; background: white !important; } }'
        : '@media print { @page { size: A4; margin: 15mm 10mm 12mm 10mm; } body { -webkit-print-color-adjust: exact; print-color-adjust: exact; background: white !important; } }';
}

// Actions
function handlePrint() {
    if (!props.onPrint) printDocument();
    emit('print');
}

function handleBack() {
    if (props.onBack) {
        emit('back');
        return;
    }

    if (props.backUrl) {
        router.visit(props.backUrl);
        emit('back');
        return;
    }

    if (window.history.length > 1) {
        window.history.back();
    } else {
        window.close();
    }
    emit('back');
}

// Global Keyboard Shortcuts (Ctrl+P / Cmd+P to print, Esc to go back)
function handleKeyDown(event) {
    if (event.key === 'Escape') {
        handleBack();
    }
}

onMounted(() => {
    window.addEventListener('keydown', handleKeyDown);
    applyDynamicPageStyle();
});

watch(isThermalTemplate, () => {
    applyDynamicPageStyle();
});

onBeforeUnmount(() => {
    window.removeEventListener('keydown', handleKeyDown);
    if (dynamicPageStyleEl && dynamicPageStyleEl.parentNode) {
        dynamicPageStyleEl.parentNode.removeChild(dynamicPageStyleEl);
        dynamicPageStyleEl = null;
    }
});
</script>
