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
            documentType="proforma_invoice"
            :data="mappedData"
            :centerData="mappedCenterData"
            :documentSettings="documentSettings"
            :visualSettings="visualSettings"
            :previewMode="false"
        />
    </div>
</template>

<script setup>
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { usePage } from '@inertiajs/vue3';
import PrintEngine from '@/Components/Print/PrintEngine.vue';

const props = defineProps({
    workOrder: Object,
    itemsByDepartment: Object,
    allParts: Array,
    departments: Object,
    servicesTotal: Number,
    partsTotal: Number,
    grandTotal: Number,
    totalPaid: Number,
    balance: Number,
    taxSettings: Object,
});

const page = usePage();
const { locale } = useI18n();
const isRtl = computed(() => locale.value === 'ar');

const goBack = () => window.history.back();
const printPage = () => window.print();

const formatNumber = (num) => new Intl.NumberFormat(isRtl.value ? 'ar-SA-u-nu-latn' : 'en-US').format(num || 0);

const getDiscount = (item) => {
    if (!item.discount_value || item.discount_type === 'none') return 0;
    if (item.discount_type === 'fixed') return item.discount_value;
    if (item.discount_type === 'percentage') {
        return (item.qty * item.unit_price) * (item.discount_value / 100);
    }
    return 0;
};

const mappedData = computed(() => {
    const make = props.workOrder.vehicle?.make?.name_ar || props.workOrder.vehicle?.make?.name_en || '';
    const model = props.workOrder.vehicle?.model?.name_ar || props.workOrder.vehicle?.model?.name_en || '';
    const vehicleStr = `${make} ${model}`.trim() || '-';

    // Map services
    const services = (props.workOrder.items || []).map(item => ({
        service_name: item.title || item.service?.name_ar || item.service?.name_en || '',
        description: item.description || '',
        qty: item.qty || 1,
        unit_price: item.unit_price || 0,
        discount: getDiscount(item),
        is_part: false
    }));

    // Map parts
    const parts = (props.allParts || []).map(part => ({
        service_name: part.name || part.part?.name_ar || part.part?.name_en || '',
        description: part.description || '',
        qty: part.qty || 1,
        unit_price: part.unit_price || 0,
        discount: 0,
        is_part: true
    }));

    return {
        code: props.workOrder.code,
        created_at: new Date().toISOString(),
        entry_date: props.workOrder.entry_date,
        mileage: props.workOrder.mileage,
        odometer: props.workOrder.mileage ? formatNumber(props.workOrder.mileage) : '-',
        fuel_level: props.workOrder.fuel_level,
        customer_complaint: props.workOrder.customer_complaint,
        reception_signature: props.workOrder.reception_signature,
        delivery_signature: props.workOrder.delivery_signature,
        customer: {
            name: props.workOrder.customer?.name,
            phone: props.workOrder.customer?.phone,
            address: props.workOrder.customer?.address_line,
            tax_number: props.workOrder.customer?.tax_number,
        },
        vehicle: {
            make: vehicleStr,
            plate: props.workOrder.vehicle?.plate_number,
            color: props.workOrder.vehicle?.color,
        },
        items: [...services, ...parts]
    };
});

const mappedCenterData = computed(() => {
    const center = props.workOrder.center || {};
    const tenant = props.workOrder.tenant || page.props.tenant || {};
    return {
        name: isRtl.value ? (center.name_ar || center.name || tenant.name) : (center.name_en || center.name || tenant.name),
        tax_number: center.vat_number || tenant.vat_number,
        cr_number: tenant.cr_number,
        phone: center.phone || tenant.phone,
        logo: center.logo_invoice_url || center.logo_light_url || tenant.logo_url || '',
        iban: tenant.iban || '',
        address: center.address || tenant.address || '',
        stamp_url: center.stamp_url || '',
    };
});

const documentSettings = computed(() => {
    const tenantSettings = page.props.tenant?.print_settings;
    const docSettings = tenantSettings?.documents?.['proforma_invoice'] || {};
    return {
        title_ar: docSettings.title_ar || 'فاتورة أولية',
        title_en: docSettings.title_en || 'Proforma Invoice',
        terms: docSettings.terms || [],
        print_terms: docSettings.print_terms !== false,
        show_stamp: docSettings.show_stamp !== false,
        show_customer_address: docSettings.show_customer_address !== false,
        signatures: docSettings.signatures && docSettings.signatures.length > 0 ? docSettings.signatures : [
            { name_ar: 'توقيع المدير', name_en: 'Manager Signature' },
            { name_ar: 'توقيع العميل', name_en: 'Customer Signature' }
        ]
    };
});

const visualSettings = computed(() => {
    const tenantSettings = page.props.tenant?.print_settings;
    const vis = tenantSettings?.visual || {};
    const center = props.workOrder.center || {};
    return {
        active_template: vis.active_template || 'TemplateDefaultA4',
        show_logo: vis.show_logo !== false,
        show_stamp: vis.show_stamp !== false,
        show_qr_code: vis.show_qr_code !== false,
        primary_color: vis.primary_color || '#fbbf24',
        footer_text: vis.footer_text || '',
        stamp_url: center.stamp_url || vis.stamp_url || ''
    };
});
</script>

<style>
@media print {
    @page { size: A4; margin: 1cm; }
    body { -webkit-print-color-adjust: exact; print-color-adjust: exact; background-color: white !important; }
    .print-container { padding: 0; }
}
</style>
