<template>
    <div
        class="print-container bg-white min-h-screen p-8 print:p-0 print:m-0 flex flex-col items-center"
        :class="isRtl ? 'rtl' : 'ltr'"
        :dir="isRtl ? 'rtl' : 'ltr'"
    >
        <!-- Floating Controls Bar (Hidden during print) -->
        <div class="fixed bottom-6 start-6 flex items-center gap-3 print:hidden z-50">
            <!-- Back Button -->
            <button
                type="button"
                @click="handleBack"
                class="px-4 py-2.5 bg-white hover:bg-gray-50 text-gray-700 rounded-xl text-sm font-semibold transition-all border border-gray-200 shadow-lg flex items-center gap-2 active:scale-95"
            >
                <svg
                    class="w-4 h-4 rtl:rotate-180"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M10 19l-7-7m0 0l7-7m-7 7h18"
                    />
                </svg>
                <span>{{ backLabel || $t('common.back') }}</span>
            </button>

            <!-- Print Button -->
            <button
                type="button"
                @click="handlePrint"
                class="px-5 py-2.5 text-white rounded-xl text-sm font-semibold transition-all shadow-lg flex items-center gap-2 active:scale-95 hover:opacity-95"
                :style="{ backgroundColor: resolvedVisualSettings.primary_color || '#3b82f6' }"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 022 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"
                    />
                </svg>
                <span>{{ printLabel || $t('common.print') }}</span>
            </button>

            <slot name="controls-extra" />
        </div>

        <!-- Document Content: Slot or Standard PrintEngine -->
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
</template>

<script setup>
import { computed, onMounted, onBeforeUnmount } from 'vue';
import { useI18n } from 'vue-i18n';
import { usePage, router } from '@inertiajs/vue3';
import PrintEngine from '@/Components/Print/PrintEngine.vue';

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

// Resolve Center & Company Data with safe defaults
const resolvedCenterData = computed(() => {
    if (props.centerData && Object.keys(props.centerData).length > 0) {
        return props.centerData;
    }

    const center = props.data?.center || page.props.currentCenter || {};
    const tenant = page.props.tenant || {};

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

    const tenantVisual = page.props.tenant?.print_settings?.visual || {};
    return {
        active_template: tenantVisual.active_template || 'TemplateDefaultA4',
        show_logo: tenantVisual.show_logo !== false,
        show_stamp: tenantVisual.show_stamp !== false,
        show_qr_code: tenantVisual.show_qr_code !== false,
        primary_color: tenantVisual.primary_color || '#3b82f6',
        footer_text: tenantVisual.footer_text || '',
    };
});

// Resolve Document-specific Settings
const resolvedDocumentSettings = computed(() => {
    if (props.documentSettings && Object.keys(props.documentSettings).length > 0) {
        return props.documentSettings;
    }

    const docType = props.documentType === 'quote' ? 'quotation' : props.documentType;
    const tenantDocs = page.props.tenant?.print_settings?.documents || {};
    return tenantDocs[docType] || {};
});

// Actions
function handlePrint() {
    if (props.onPrint) {
        props.onPrint();
    } else {
        window.print();
    }
    emit('print');
}

function handleBack() {
    if (props.onBack) {
        props.onBack();
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
});

onBeforeUnmount(() => {
    window.removeEventListener('keydown', handleKeyDown);
});
</script>
