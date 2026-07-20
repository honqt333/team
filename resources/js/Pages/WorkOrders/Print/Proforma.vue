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
import { usePage, router } from '@inertiajs/vue3';
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
const { t, locale } = useI18n();
const isRtl = computed(() => locale.value === 'ar');

const goBack = () => {
    if (window.history.length <= 1) {
        window.close();
        setTimeout(() => {
            if (props.workOrder?.id) {
                router.visit(route('work-orders.show', props.workOrder.id));
            }
        }, 100);
    } else {
        window.history.back();
    }
};
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

const isTaxEnabled = computed(() => {
    if (props.taxSettings && (props.taxSettings.vat_enabled === false || props.taxSettings.vat_enabled === 0 || props.taxSettings.vat_enabled === '0')) {
        return false;
    }
    const woTax = props.workOrder?.tax_enabled_snapshot;
    if (woTax === false || woTax === 0 || woTax === '0') {
        return false;
    }
    if (woTax === true || woTax === 1 || woTax === '1') {
        return true;
    }
    return Boolean(props.taxSettings?.vat_enabled);
});

const mappedData = computed(() => {
    const wo = props.workOrder || {};
    const make = wo.vehicle?.make?.name_ar || wo.vehicle?.make?.name_en || '';
    const model = wo.vehicle?.model?.name_ar || wo.vehicle?.model?.name_en || '';
    const vehicleStr = `${make} ${model}`.trim() || '-';

    // Map services
    const services = (wo.items || []).map(item => {
        let serviceName = '';
        const isStandard = item.service && 
            item.service.name_ar?.trim() !== 'أخرى' && 
            item.service.name_en?.trim().toLowerCase() !== 'other';
            
        if (isStandard) {
            serviceName = isRtl.value 
                ? (item.service.name_ar || item.title) 
                : (item.service.name_en || item.service.name_ar || item.title);
        } else {
            serviceName = item.title || '';
        }

        if (item.warranty_value_snapshot && item.warranty_unit_snapshot) {
            const warrantyLabel = t('services_management.columns.warranty');
            let unitLabel = t(`services_management.warranty_units.${item.warranty_unit_snapshot}`);
            const val = item.warranty_value_snapshot;
            if (isRtl.value) {
                if (val >= 3 && val <= 10) {
                    if (item.warranty_unit_snapshot === 'days') unitLabel = 'أيام';
                    if (item.warranty_unit_snapshot === 'weeks') unitLabel = 'أسابيع';
                    if (item.warranty_unit_snapshot === 'months') unitLabel = 'أشهر';
                    if (item.warranty_unit_snapshot === 'years') unitLabel = 'سنوات';
                }
            } else {
                if (val > 1) {
                    if (item.warranty_unit_snapshot === 'days') unitLabel = 'days';
                    if (item.warranty_unit_snapshot === 'weeks') unitLabel = 'weeks';
                    if (item.warranty_unit_snapshot === 'months') unitLabel = 'months';
                    if (item.warranty_unit_snapshot === 'years') unitLabel = 'years';
                }
            }
            serviceName += ` - ${warrantyLabel} ${item.warranty_value_snapshot} ${unitLabel}`;
        }
        return {
            service_name: serviceName,
            description: '',
            qty: item.qty || 1,
            unit_price: item.unit_price || 0,
            discount: getDiscount(item),
            is_part: false,
            is_taxable: isTaxEnabled.value
        };
    });

    // Map parts
    const parts = (props.allParts || [])
        .filter(part => !part.hide_on_print)
        .map(part => ({
            service_name: part.name || part.part?.name_ar || part.part?.name_en || '',
            description: part.description || '',
            qty: part.qty || 1,
            unit_price: part.unit_price || 0,
            discount: Number(part.discount || 0),
            is_part: true,
            is_taxable: isTaxEnabled.value
        }));

    return {
        code: wo.code,
        created_at: new Date().toISOString(),
        entry_date: wo.entry_date,
        mileage: wo.mileage,
        odometer: wo.mileage ? formatNumber(wo.mileage) : '-',
        fuel_level: wo.fuel_level,
        customer_complaint: wo.customer_complaint,
        reception_signature: wo.reception_signature,
        delivery_signature: wo.delivery_signature,
        customer: {
            name: wo.customer?.name,
            phone: wo.customer?.phone,
            address: wo.customer?.address_line,
            tax_number: wo.customer?.tax_number,
        },
        vehicle: {
            make: vehicleStr,
            plate: wo.vehicle?.plate_number,
            color: wo.vehicle?.color,
        },
        tax_enabled_snapshot: isTaxEnabled.value,
        pricing_mode_snapshot: wo.pricing_mode_snapshot || 'exclusive',
        total_excl_tax: Number(wo.total_excl_tax || props.grandTotal || 0),
        total_tax: isTaxEnabled.value ? Number(wo.total_tax || 0) : 0,
        total_incl_tax: isTaxEnabled.value ? Number(wo.total_incl_tax || 0) : Number(wo.total_excl_tax || props.grandTotal || 0),
        discount_amount: Number(wo.total_discount || 0),
        total_paid: props.totalPaid ?? 0,
        balance: (isTaxEnabled.value ? Number(wo.total || 0) : Number(props.grandTotal || 0)) - Number(props.totalPaid || 0),
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
    @page { size: A4; margin: 0 1cm; }
    body { -webkit-print-color-adjust: exact; print-color-adjust: exact; background-color: white !important; }
    .print-container { padding: 0; }
    .print-container,
    .print-container * {
        visibility: visible !important;
    }
}
</style>
