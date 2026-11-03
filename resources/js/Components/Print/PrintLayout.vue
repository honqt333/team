/** * Unified print layout component. * * Provides a consistent structure for all print pages: * -
Header with company info, logo, and document title * - Optional controls (print button, back button)
* - Slot for content * - Footer with signatures, QR code, and terms * * Usage: *
<PrintLayout
    *
    :document-type="'customers'"
    *
    :data="pageProps"
    *
    :center-data="companyInfo"
    *
    title-ar="قائمة العملاء"
    *
    title-en="Customers List"
    *
    :show-controls="true"
    *
    @print="handlePrint"
    *
>
 *     <MyTable :items="customers" />
 *   </PrintLayout>
*/
<script setup>
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { usePage } from '@inertiajs/vue3';
import PrintEngine from '@/Components/Print/PrintEngine.vue';
import { usePrint } from '@/Composables/usePrint';
import { buildPrintSignatures } from '@/Utils/printSignatures';

const props = defineProps({
    /** Document type for template selection */
    documentType: {
        type: String,
        default: 'invoice',
    },
    /** Page data for the print engine */
    data: {
        type: Object,
        default: () => ({}),
    },
    /** Center/company data (logo, name, tax_number, etc.) */
    centerData: {
        type: Object,
        default: () => ({}),
    },
    /** Document-level settings (qr_code, iban, terms, signatures) */
    documentSettings: {
        type: Object,
        default: () => ({}),
    },
    /** Visual settings (template, colors, footer_text) */
    visualSettings: {
        type: Object,
        default: () => ({
            active_template: 'TemplateDefaultA4',
            show_logo: true,
            show_stamp: true,
            show_qr_code: true,
            primary_color: '#3b82f6',
            footer_text: '',
        }),
    },
    /** Arabic title for the document */
    titleAr: {
        type: String,
        default: '',
    },
    /** English title for the document */
    titleEn: {
        type: String,
        default: '',
    },
    /** Show print/back controls (for direct-print pages) */
    showControls: {
        type: Boolean,
        default: false,
    },
    /** Back route (for direct-print pages) */
    backRoute: {
        type: String,
        default: '',
    },
    /** Custom title override (takes precedence over titleAr/titleEn) */
    title: {
        type: String,
        default: '',
    },
});

const emit = defineEmits(['print', 'back']);

const { locale } = useI18n();
const isRtl = computed(() => locale.value === 'ar');
const page = usePage();
const printHelpers = usePrint();

const title = computed(() => {
    if (props.title) return props.title;
    return isRtl.value ? props.titleAr : props.titleEn;
});

function handlePrint() {
    emit('print');
}

function handleBack() {
    if (props.backRoute) {
        if (typeof window !== 'undefined' && window.Inertia) {
            window.Inertia.visit(props.backRoute);
        } else {
            window.location.href = props.backRoute;
        }
    } else {
        emit('back');
    }
}

// Build signature rows from documentSettings
const signatures = computed(() => {
    const customerSignatureUrl = props.data?.signature_url || props.data?.customer_signature;
    return buildPrintSignatures(props.documentSettings, customerSignatureUrl);
});

defineExpose({ handlePrint, handleBack, isRtl });
</script>

<template>
    <div
        class="print-container bg-white min-h-screen text-gray-900"
        :class="isRtl ? 'rtl' : 'ltr'"
        dir="rtl"
    >
        <!-- Controls (hidden during print) -->
        <div
            v-if="showControls"
            class="print:hidden fixed top-4 right-4 z-50 flex gap-2"
            :class="isRtl ? 'left-4 right-auto' : 'left-4 right-auto'"
        >
            <button
                @click="handlePrint"
                class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg shadow-md flex items-center gap-2 text-sm font-semibold"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"
                    />
                </svg>
                {{ isRtl ? 'طباعة' : 'Print' }}
            </button>
            <button
                v-if="backRoute"
                @click="handleBack"
                class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg shadow-md text-sm font-semibold border border-gray-200"
            >
                {{ isRtl ? 'رجوع' : 'Back' }}
            </button>
        </div>

        <!-- Print Header (company info, logo, title) -->
        <div class="print-header px-8 pt-6 pb-4 border-b-2 border-gray-900">
            <div class="grid grid-cols-3 items-start gap-6">
                <!-- Column 1: Company Info -->
                <div class="space-y-1 text-xs" :class="isRtl ? 'text-right' : 'text-left'">
                    <h1 class="text-lg font-black text-gray-900">
                        {{ centerData.name || title }}
                    </h1>
                    <p v-if="centerData.center_name" class="font-bold text-gray-700">
                        {{ isRtl ? 'الفرع' : 'Branch' }}: {{ centerData.center_name }}
                    </p>
                    <p v-if="centerData.phone" class="text-gray-600">
                        {{ isRtl ? 'الهاتف' : 'Phone' }}:
                        <span dir="ltr" class="font-mono">{{ centerData.phone }}</span>
                    </p>
                    <p v-if="centerData.vat_number" class="text-gray-600">
                        {{ isRtl ? 'الضريبة' : 'VAT' }}:
                        <span class="font-mono font-bold">{{ centerData.vat_number }}</span>
                    </p>
                    <p v-if="centerData.cr_number" class="text-gray-600">
                        {{ isRtl ? 'السجل التجاري' : 'CR' }}:
                        <span class="font-mono">{{ centerData.cr_number }}</span>
                    </p>
                </div>

                <!-- Column 2: Logo & Title -->
                <div class="flex flex-col items-center justify-center">
                    <div v-if="centerData.logo_path || centerData.logo_url" class="w-16 h-16 mb-2">
                        <img
                            :src="
                                centerData.logo_path
                                    ? '/storage/' + centerData.logo_path
                                    : centerData.logo_url
                            "
                            alt="Logo"
                            class="w-full h-full object-contain"
                        />
                    </div>
                    <div
                        v-else
                        class="w-16 h-16 mb-2 bg-gray-100 rounded-lg flex items-center justify-center text-gray-400 text-xs font-bold"
                    >
                        [Logo]
                    </div>
                    <h2 class="text-base font-black uppercase tracking-wider text-gray-900">
                        {{ title }}
                    </h2>
                </div>

                <!-- Column 3: Metadata -->
                <div class="space-y-1 text-xs" :class="isRtl ? 'text-left' : 'text-right'">
                    <slot name="metadata" />
                </div>
            </div>
        </div>

        <!-- Content Area -->
        <div class="px-8 py-6">
            <slot />
        </div>

        <!-- Footer: Signatures, QR, Terms -->
        <div
            v-if="
                documentSettings.show_qr_code ||
                documentSettings.signatures ||
                visualSettings.footer_text
            "
            class="border-t-2 border-gray-300 pt-6 px-8 mt-4"
        >
            <div class="grid grid-cols-3 gap-8">
                <!-- Signatures -->
                <div
                    v-if="signatures.length > 0"
                    class="space-y-4"
                    :class="isRtl ? 'text-right' : 'text-left'"
                >
                    <div
                        v-for="(sig, index) in signatures"
                        v-show="sig.show !== false"
                        :key="index"
                        class="flex flex-col items-center"
                    >
                        <div
                            v-if="sig.url"
                            class="h-12 mb-1"
                            :class="isRtl ? 'self-end' : 'self-start'"
                        >
                            <img
                                :src="printHelpers.formatSignatureUrl(sig.url)"
                                :alt="
                                    isRtl ? sig.name_ar || sig.name_en : sig.name_en || sig.name_ar
                                "
                                class="max-h-12 max-w-[120px] object-contain"
                            />
                        </div>
                        <div v-else class="w-24 h-px bg-gray-400 mb-1"></div>
                        <span class="text-xs text-gray-600 font-semibold">
                            {{ isRtl ? sig.name_ar : sig.name_en || sig.name_ar }}
                        </span>
                    </div>
                </div>

                <!-- QR Code -->
                <div
                    v-if="documentSettings.show_qr_code && data.qr_code_url"
                    class="flex flex-col items-center"
                >
                    <img
                        :src="data.qr_code_url"
                        alt="QR"
                        class="w-20 h-20 object-contain border border-gray-200 p-1"
                    />
                    <span class="text-[10px] text-gray-500 mt-1">
                        {{ isRtl ? 'رمز الاستجابة السريعة' : 'QR Code' }}
                    </span>
                </div>
                <div v-else-if="documentSettings.show_qr_code" class="flex flex-col items-center">
                    <div
                        class="w-20 h-20 bg-gray-100 border border-gray-300 flex items-center justify-center text-gray-400 text-xs"
                    >
                        [QR]
                    </div>
                </div>

                <!-- Terms / Footer Text -->
                <div
                    v-if="documentSettings.terms?.length > 0 || visualSettings.footer_text"
                    :class="isRtl ? 'text-right' : 'text-left'"
                >
                    <template v-if="documentSettings.terms?.length > 0">
                        <p class="text-xs font-bold text-gray-700 mb-1">
                            {{ isRtl ? 'الشروط والأحكام' : 'Terms & Conditions' }}
                        </p>
                        <ul class="text-[10px] text-gray-500 space-y-0.5 list-disc list-inside">
                            <li v-for="(term, idx) in documentSettings.terms" :key="idx">
                                {{
                                    isRtl
                                        ? term.text_ar || term
                                        : term.text_en || term.text_ar || term
                                }}
                            </li>
                        </ul>
                    </template>
                    <p
                        v-if="visualSettings.footer_text"
                        class="text-[10px] text-gray-400 mt-2 leading-tight"
                    >
                        {{ visualSettings.footer_text }}
                    </p>
                </div>
            </div>
        </div>

        <!-- Print Styles -->
        <style scoped>
            @media print {
                .print-container {
                    background: white !important;
                }
                .print-header {
                    border-bottom: 2px solid #111827 !important;
                }
                .print\\:hidden {
                    display: none !important;
                }
            }
        </style>
    </div>
</template>
