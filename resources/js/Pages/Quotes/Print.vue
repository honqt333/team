<template>
    <div class="print-container bg-white min-h-screen p-8 print:p-0 print:m-0 flex flex-col items-center" :dir="isRtl ? 'rtl' : 'ltr'">
        <!-- Print / Back Controls (hidden during print) -->
        <div class="fixed bottom-6 left-6 flex items-center gap-3 print:hidden z-50">
            <!-- Back Button -->
            <button 
                @click="goBack" 
                class="px-4 py-2.5 bg-white hover:bg-gray-50 text-gray-700 rounded-xl text-sm font-semibold transition-all border border-gray-200 shadow-lg flex items-center gap-2"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                {{ $t('common.back') }}
            </button>

            <!-- Print Button -->
            <button 
                @click="printPage" 
                class="px-5 py-2.5 text-white rounded-xl text-sm font-semibold transition-all shadow-lg flex items-center gap-2"
                :style="{ backgroundColor: visualSettings.primary_color || '#3b82f6' }"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 022 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                </svg>
                {{ $t('common.print') }}
            </button>
        </div>

        <PrintEngine 
            :documentType="'quotation'"
            :data="mappedPrintData"
            :centerData="mappedCenterData"
            :documentSettings="documentSettings"
            :visualSettings="visualSettings"
            :previewMode="false"
        />
    </div>
</template>

<script setup>
import { computed, onMounted } from 'vue';
import { useI18n } from 'vue-i18n';
import { usePage } from '@inertiajs/vue3';
import PrintEngine from '@/Components/Print/PrintEngine.vue';

const props = defineProps({
    quote: Object,
});

const page = usePage();
const { t, locale } = useI18n();
const isRtl = computed(() => locale.value === 'ar');

const goBack = () => window.history.back();
const printPage = () => window.print();

onMounted(() => {
    // Automatically trigger print dialog
    setTimeout(() => {
        window.print();
    }, 500);
});

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
        const make = props.quote.vehicle.make?.name_ar || props.quote.vehicle.make?.name_en || props.quote.vehicle.make || '';
        const model = props.quote.vehicle.model?.name_ar || props.quote.vehicle.model?.name_en || props.quote.vehicle.model || '';
        vehicleStr = `${make} ${model}`.trim() || '-';
    }

    const services = (props.quote.lines || []).map(line => ({
        service_name: line.service?.name || line.description || '—',
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

    const parts = (props.quote.parts || []).map(part => ({
        service_name: part.name || '—',
        description: part.part_number || part.part?.sku || 'NO-SKU',
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
        tax_enabled_snapshot: props.quote.tax_enabled_snapshot !== false,
        pricing_mode_snapshot: props.quote.pricing_mode_snapshot || 'exclusive',
        total_excl_tax: Number(props.quote.total_excl_tax || 0),
        total_tax: Number(props.quote.total_tax || 0),
        total_incl_tax: Number(props.quote.total_incl_tax || 0),
        total_paid: 0,
        balance: Number(props.quote.total || 0),
        items: [...services, ...parts]
    };
});

const mappedCenterData = computed(() => {
    const center = props.quote.center || {};
    const tenant = props.quote.tenant || page.props?.tenant || {};
    return {
        name: isRtl.value ? (center.name_ar || center.name || tenant.name) : (center.name_en || center.name || tenant.name),
        tax_number: center.vat_number || tenant.vat_number,
        cr_number: tenant.cr_number,
        phone: center.phone || tenant.phone,
        logo: center.logo_invoice_url || center.logo_light_url || tenant.logo_url || '',
        iban: tenant.iban || '',
        address: getCenterAddress(center) || tenant.address || '',
        stamp_url: center.stamp_url || '',
    };
});

const documentSettings = computed(() => {
    const tenantSettings = page.props?.tenant?.print_settings;
    const docSettings = tenantSettings?.documents?.['quote'] || tenantSettings?.documents?.['quotation'] || {};
    return {
        title_ar: docSettings.title_ar || 'عرض سعر / تقييم',
        title_en: docSettings.title_en || 'Price Quotation / Assessment',
        terms: docSettings.terms || (page.props?.tenant?.invoice_terms ? [page.props.tenant.invoice_terms] : []),
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
    const tenantSettings = page.props?.tenant?.print_settings;
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
</script>

<style>
@media print {
    @page { size: A4; margin: 0; }
    body { -webkit-print-color-adjust: exact; print-color-adjust: exact; background: white; }
}
</style>
