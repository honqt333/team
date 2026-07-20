<template>
    <div class="min-h-screen bg-slate-100 dark:bg-gray-900 pb-12 print:bg-white print:pb-0" dir="rtl">

        <!-- Flash Messages -->
        <div v-if="$page.props.flash?.success" class="fixed top-4 inset-x-4 z-50 flex justify-center print:hidden">
            <div class="bg-emerald-500 text-white px-6 py-3 rounded-2xl shadow-xl flex items-center gap-3 max-w-md">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                <span class="font-bold text-sm">{{ $page.props.flash.success }}</span>
            </div>
        </div>

        <!-- Header -->
        <header class="bg-white/80 dark:bg-gray-900/90 backdrop-blur-md border-b border-gray-100 dark:border-gray-800 sticky top-0 z-40 print:hidden">
            <div class="max-w-4xl mx-auto px-4 py-4 flex items-center justify-between gap-4">
                <!-- Center Logo & Name -->
                <div class="flex items-center gap-3">
                    <img v-if="quote.center?.logo_light_url" :src="quote.center.logo_light_url" alt="logo"
                        class="h-10 w-auto object-contain" />
                    <div v-else class="h-10 w-10 rounded-xl bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-2 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                    </div>
                    <div>
                        <h1 class="font-black text-gray-900 dark:text-white text-base leading-tight">{{ quote.tenant?.quote_title || centerName }}</h1>
                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ quote.tenant?.quote_title ? centerName : t('quotes.public.quote_offer') }}</p>
                    </div>
                </div>
                <!-- Quote Code Badge -->
                <div class="bg-indigo-50 dark:bg-indigo-900/30 border border-indigo-100 dark:border-indigo-800/50 px-3 py-1.5 rounded-xl">
                    <p class="font-black text-indigo-700 dark:text-indigo-300 text-sm tracking-widest font-mono">{{ quote.code }}</p>
                </div>
            </div>
        </header>

        <main class="max-w-4xl mx-auto px-4 py-8 space-y-6 print:p-0 print:m-0">

            <!-- Status Banner -->
            <div :class="statusBannerClass" class="rounded-2xl px-5 py-4 flex items-center gap-4 print:hidden">
                <div :class="statusIconClass" class="p-2.5 rounded-xl flex-shrink-0">
                    <!-- Draft/Sent -->
                    <svg v-if="['draft','sent'].includes(quote.status)" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <!-- Approved -->
                    <svg v-else-if="quote.status === 'approved'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <!-- Rejected -->
                    <svg v-else-if="quote.status === 'rejected'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <!-- Converted -->
                    <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                </div>
                <div class="flex-1">
                    <p class="font-black text-sm">{{ statusText }}</p>
                    <p v-if="quote.status === 'rejected' && quote.rejection_reason" class="text-xs opacity-75 mt-0.5">{{ t('quotes.public.rejection_reason') }}: {{ quote.rejection_reason }}</p>
                    <p v-else class="text-xs opacity-75 mt-0.5">{{ statusSubtext }}</p>
                </div>
            </div>

            <!-- PrintEngine Document Sheet -->
            <div class="print-sheet-wrapper print-container w-full overflow-x-auto bg-white dark:bg-gray-800 rounded-3xl border border-gray-200 dark:border-gray-700 shadow-sm p-4 flex justify-start md:justify-center min-w-0 print:border-none print:shadow-none print:p-0">
                <div class="print-sheet-content inline-block shrink-0 min-w-[210mm] print:min-w-0 print:w-full">
                    <PrintEngine 
                        :documentType="'quotation'"
                        :data="mappedPrintData"
                        :centerData="mappedCenterData"
                        :documentSettings="documentSettings"
                        :visualSettings="visualSettings"
                        :previewMode="false"
                    />
                </div>
            </div>

            <!-- Action Buttons (only for draft/sent) -->
            <div v-if="quote.can_be_actioned" class="space-y-4 print:hidden">
                <p class="text-center text-sm font-bold text-gray-500 dark:text-gray-400">{{ t('quotes.public.action_prompt') }}</p>
                <div class="grid grid-cols-2 gap-4">
                    <button @click="handleApprove"
                        :disabled="submitting"
                        class="group flex items-center justify-center gap-2 py-4 bg-emerald-500 hover:bg-emerald-600 disabled:opacity-50 text-white font-black text-sm uppercase tracking-widest rounded-2xl shadow-lg shadow-emerald-200 dark:shadow-none transition-all active:scale-95">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        {{ t('quotes.public.approve') }}
                    </button>
                    <button @click="showRejectModal = true"
                        :disabled="submitting"
                        class="group flex items-center justify-center gap-2 py-4 bg-red-500 hover:bg-red-600 disabled:opacity-50 text-white font-black text-sm uppercase tracking-widest rounded-2xl shadow-lg shadow-red-200 dark:shadow-none transition-all active:scale-95">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        {{ t('quotes.public.reject') }}
                    </button>
                </div>
            </div>

            <!-- Contact Hub -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700/50 overflow-hidden shadow-sm print:hidden">
                <div class="px-5 py-4 border-b border-gray-50 dark:border-gray-700/50">
                    <h2 class="font-black text-gray-900 dark:text-white text-sm uppercase tracking-wider">{{ t('quotes.public.contact_us') }}</h2>
                </div>
                <div class="p-4 grid grid-cols-3 gap-3">
                    <!-- WhatsApp -->
                    <a v-if="quote.center?.phone" :href="`https://wa.me/${cleanPhone(quote.center.phone)}`" target="_blank"
                        class="flex flex-col items-center gap-2 p-3 bg-emerald-50 dark:bg-emerald-900/10 hover:bg-emerald-100 dark:hover:bg-emerald-900/20 rounded-xl transition-all active:scale-95 border border-emerald-100 dark:border-emerald-800/50">
                        <div class="w-10 h-10 bg-emerald-500 rounded-xl flex items-center justify-center shadow-md shadow-emerald-200">
                            <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.888-9.885 9.888m8.328-18.382A11.772 11.772 0 0012.057 0C5.454 0 .07 5.385.067 11.99a11.75 11.75 0 001.58 5.877l-1.647 6.02L6.18 22.25a11.708 11.708 0 005.875 1.579h.005c6.603 0 11.988-5.386 11.99-11.99a11.714 11.714 0 00-3.418-8.318"/></svg>
                        </div>
                        <span class="text-xs font-black text-emerald-700 dark:text-emerald-300">واتساب</span>
                    </a>
                    <!-- Call -->
                    <a v-if="quote.center?.phone" :href="`tel:${quote.center.phone}`"
                        class="flex flex-col items-center gap-2 p-3 bg-blue-50 dark:bg-blue-900/10 hover:bg-blue-100 dark:hover:bg-blue-900/20 rounded-xl transition-all active:scale-95 border border-blue-100 dark:border-blue-800/50">
                        <div class="w-10 h-10 bg-blue-500 rounded-xl flex items-center justify-center shadow-md shadow-blue-200">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                        </div>
                        <span class="text-xs font-black text-blue-700 dark:text-blue-300">{{ t('quotes.public.call') }}</span>
                    </a>
                    <!-- Print -->
                    <button @click="printPage"
                        class="flex flex-col items-center gap-2 p-3 bg-gray-50 dark:bg-gray-700/50 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-xl transition-all active:scale-95 border border-gray-100 dark:border-gray-700">
                        <div class="w-10 h-10 bg-gray-700 dark:bg-gray-600 rounded-xl flex items-center justify-center shadow-md shadow-gray-200 dark:shadow-none">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                        </div>
                        <span class="text-xs font-black text-gray-700 dark:text-gray-300">{{ t('common.print') }}</span>
                    </button>
                </div>
            </div>

            <!-- Footer -->
            <div class="pb-8 text-center print:hidden">
                <p class="text-xs text-gray-400">{{ t('quotes.public.powered_by') }}</p>
            </div>
        </main>

        <!-- Rejection Modal -->
        <div v-if="showRejectModal" class="fixed inset-0 z-50 bg-black/50 backdrop-blur-sm flex items-end sm:items-center justify-center p-4 print:hidden" @click.self="showRejectModal = false">
            <div class="bg-white dark:bg-gray-800 rounded-2xl w-full max-w-md shadow-2xl overflow-hidden">
                <div class="px-6 py-5 border-b border-gray-100 dark:border-gray-700">
                    <h3 class="font-black text-gray-900 dark:text-white text-lg">{{ t('quotes.public.reject_title') }}</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">{{ t('quotes.public.reject_subtitle') }}</p>
                </div>
                <div class="p-6">
                    <textarea v-model="rejectionReason" rows="4"
                        :placeholder="t('quotes.public.rejection_reason_placeholder')"
                        class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-700/50 border border-gray-200 dark:border-gray-600 rounded-xl text-sm text-gray-900 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-red-500 resize-none"
                    ></textarea>
                    <div class="flex gap-3 mt-4">
                        <button @click="showRejectModal = false"
                            class="flex-1 py-3 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 font-black text-sm rounded-xl transition-all">
                            {{ t('common.cancel') }}
                        </button>
                        <button @click="handleReject"
                            :disabled="!rejectionReason.trim() || submitting"
                            class="flex-1 py-3 bg-red-500 hover:bg-red-600 disabled:opacity-50 text-white font-black text-sm rounded-xl transition-all">
                            {{ t('quotes.public.confirm_reject') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { router, usePage } from '@inertiajs/vue3';
import PrintEngine from '@/Components/Print/PrintEngine.vue';

const props = defineProps({
    quote: Object,
});

const page = usePage();
const { t } = useI18n();

const showRejectModal = ref(false);
const rejectionReason = ref('');
const submitting = ref(false);

const printPage = () => window.print();

// Center display name (Arabic first, then English)
const centerName = computed(() => {
    const c = props.quote?.center;
    if (!c) return '';
    return c.name_ar || c.name_en || c.name || '';
});

function cleanPhone(phone) {
    return phone ? phone.replace(/[\s\+]/g, '') : '';
}

// Status display
const statusBannerClass = computed(() => ({
    'bg-amber-50 dark:bg-amber-900/20 text-amber-800 dark:text-amber-300 border border-amber-100 dark:border-amber-800/50': ['draft', 'sent'].includes(props.quote.status),
    'bg-emerald-50 dark:bg-emerald-900/20 text-emerald-800 dark:text-emerald-300 border border-emerald-100 dark:border-emerald-800/50': props.quote.status === 'approved',
    'bg-red-50 dark:bg-red-900/20 text-red-800 dark:text-red-300 border border-red-100 dark:border-red-800/50': props.quote.status === 'rejected',
    'bg-indigo-50 dark:bg-indigo-900/20 text-indigo-800 dark:text-indigo-300 border border-indigo-100 dark:border-indigo-800/50': props.quote.status === 'converted',
}));

const statusIconClass = computed(() => ({
    'bg-amber-100 dark:bg-amber-800/50': ['draft', 'sent'].includes(props.quote.status),
    'bg-emerald-100 dark:bg-emerald-800/50': props.quote.status === 'approved',
    'bg-red-100 dark:bg-red-800/50': props.quote.status === 'rejected',
    'bg-indigo-100 dark:bg-indigo-800/50': props.quote.status === 'converted',
}));

const statusText = computed(() => t(`quotes.public.status_text.${props.quote.status}`));
const statusSubtext = computed(() => t(`quotes.public.status_subtext.${props.quote.status}`));

// Helper for Center Address
const getCenterAddress = (center) => {
    if (!center || !center.address) return '';
    const addr = center.address;
    const parts = [
        addr.building_number ? `${t('common.building') || 'مبنى'} ${addr.building_number}` : '',
        addr.street ? `${t('common.street') || 'شارع'} ${addr.street}` : '',
        addr.district ? `${t('common.district') || 'حي'} ${addr.district}` : '',
        addr.city ? addr.city : '',
        addr.postal_code ? `${t('common.postal_code') || 'الرمز البريدي'} ${addr.postal_code}` : '',
    ].filter(Boolean);
    return parts.join('، ');
};

const mappedPrintData = computed(() => {
    let vehicleStr = '-';
    if (props.quote.vehicle) {
        const make = props.quote.vehicle.make || '';
        const model = props.quote.vehicle.model || '';
        vehicleStr = `${make} ${model}`.trim() || '-';
    }

    const services = (props.quote.lines || []).map(line => ({
        service_name: line.title || line.description || '—',
        description: '',
        qty: Number(line.qty || 1),
        unit_price: Number(line.unit_price || 0),
        discount: Number(line.discount_amount || 0),
        is_part: false,
        is_taxable: line.is_taxable !== false,
        tax_rate_snapshot: Number(line.tax_rate_snapshot || 0),
        tax_amount: Number(line.tax_amount || 0),
        total: Number(line.line_total || 0)
    }));

    const parts = (props.quote.parts || [])
        .filter(part => !part.hide_on_print)
        .map(part => ({
            service_name: part.name || '—',
            description: part.part_number || 'NO-SKU',
            qty: Number(part.qty || 1),
            unit_price: Number(part.unit_price || 0),
            discount: Number(part.discount || 0),
            is_part: true,
            is_taxable: part.is_taxable !== false,
            tax_rate_snapshot: Number(part.tax_rate_snapshot || 0),
            tax_amount: Number(part.tax_amount || 0),
            total: Number(part.total_incl_tax || part.total || 0)
        }));

    return {
        code: props.quote.code,
        created_at: props.quote.created_at,
        entry_date: props.quote.created_at,
        mileage: props.quote.odometer,
        odometer: props.quote.odometer ? Number(props.quote.odometer).toLocaleString() : '-',
        customer_complaint: props.quote.customer_complaint,
        customer: {
            name: props.quote.customer?.name || '—',
            phone: props.quote.customer?.phone || '',
            address: props.quote.customer_address_snapshot || props.quote.customer?.address_line || '',
            tax_number: props.quote.customer_vat_snapshot || props.quote.customer?.tax_number || '',
        },
        vehicle: {
            make: vehicleStr,
            plate: props.quote.vehicle?.plate_number,
            color: props.quote.vehicle?.color,
        },
        tax_enabled_snapshot: Boolean(props.quote.tax_enabled_snapshot),
        pricing_mode_snapshot: props.quote.pricing_mode_snapshot || 'exclusive',
        total_excl_tax: Number(props.quote.total_excl_tax || 0),
        total_tax: props.quote.tax_enabled_snapshot ? Number(props.quote.total_tax || 0) : 0,
        total_incl_tax: props.quote.tax_enabled_snapshot ? Number(props.quote.total_incl_tax || 0) : Number(props.quote.total_excl_tax || 0),
        total_paid: 0,
        balance: props.quote.tax_enabled_snapshot ? Number(props.quote.total || 0) : Number(props.quote.total_excl_tax || 0),
        items: [...services, ...parts]
    };
});

const mappedCenterData = computed(() => {
    const center = props.quote.center || {};
    const tenant = props.quote.tenant || page.props?.tenant || {};
    return {
        name: localeName(center, tenant),
        tax_number: center.vat_number || tenant.vat_number,
        cr_number: tenant.cr_number,
        phone: center.phone || tenant.phone,
        logo: center.logo_invoice_url || center.logo_light_url || tenant.logo_url || '',
        iban: tenant.iban || '',
        address: getCenterAddress(center) || tenant.address || '',
        stamp_url: center.stamp_url || '',
    };
});

function localeName(center, tenant) {
    const isAr = page.props?.locale === 'ar' || true;
    if (isAr) {
        return center.name_ar || center.name || tenant.name;
    }
    return center.name_en || center.name || tenant.name;
}

const documentSettings = computed(() => {
    const tenantSettings = props.quote.tenant?.print_settings || page.props?.tenant?.print_settings;
    const docSettings = tenantSettings?.documents?.['quote'] || tenantSettings?.documents?.['quotation'] || {};
    return {
        title_ar: docSettings.title_ar || 'عرض سعر / تقييم',
        title_en: docSettings.title_en || 'Price Quotation / Assessment',
        terms: docSettings.terms || (props.quote.tenant?.quote_terms ? [props.quote.tenant.quote_terms] : []),
        print_terms: docSettings.print_terms !== false,
        show_stamp: docSettings.show_stamp !== false,
        show_customer_address: docSettings.show_customer_address !== false,
        signatures: docSettings.signatures && docSettings.signatures.length > 0 ? docSettings.signatures : [
            { name_ar: 'توقيع المستشار الفني', name_en: 'Service Advisor Signature' },
            { name_ar: 'توقيع العميل للاعتماد', name_en: 'Customer Signature' }
        ]
    };
});

const visualSettings = computed(() => {
    const tenantSettings = props.quote.tenant?.print_settings || page.props?.tenant?.print_settings;
    const vis = tenantSettings?.visual || {};
    const center = props.quote.center || {};
    return {
        active_template: vis.active_template || 'TemplateDefaultA4',
        show_logo: vis.show_logo !== false,
        show_stamp: vis.show_stamp !== false,
        show_qr_code: vis.show_qr_code !== false,
        primary_color: vis.primary_color || '#3b82f6',
        footer_text: vis.footer_text || props.quote.tenant?.print_settings?.footer_text || '',
        stamp_url: center.stamp_url || vis.stamp_url || ''
    };
});

// Actions
function handleApprove() {
    if (submitting.value) return;
    submitting.value = true;
    router.post(`/view/quote/${props.quote.uuid}/approve`, {}, {
        onFinish: () => { submitting.value = false; },
    });
}

function handleReject() {
    if (!rejectionReason.value.trim() || submitting.value) return;
    submitting.value = true;
    router.post(`/view/quote/${props.quote.uuid}/reject`, {
        rejection_reason: rejectionReason.value,
    }, {
        onFinish: () => {
            submitting.value = false;
            showRejectModal.value = false;
        },
    });
}
</script>

<style>
@media print {
    @page {
        size: A4;
        margin: 0.5cm 1cm;
    }
    html, body, #app {
        background: white !important;
        overflow: visible !important;
    }
    body {
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
        background: white !important;
    }
    /* Hide header, footer, actions, and custom print hidden classes */
    header, footer, .print\:hidden, .print-hidden, [class*="print:hidden"] {
        display: none !important;
    }
    /* Set page body structure for printing */
    .min-h-screen {
        min-height: 0 !important;
        background: white !important;
        padding-bottom: 0 !important;
        display: block !important;
        overflow: visible !important;
    }
    main {
        padding: 0 !important;
        margin: 0 !important;
        max-width: 100% !important;
        width: 100% !important;
        display: block !important;
        overflow: visible !important;
    }
    .print-sheet-wrapper {
        display: block !important;
        overflow: visible !important;
        border: none !important;
        box-shadow: none !important;
        padding: 0 !important;
        margin: 0 !important;
        width: 100% !important;
        max-width: 100% !important;
        background: white !important;
    }
    .print-sheet-content {
        display: block !important;
        overflow: visible !important;
        min-width: 0 !important;
        width: 100% !important;
    }
    .print-engine-wrapper {
        display: block !important;
        overflow: visible !important;
    }
}
</style>
