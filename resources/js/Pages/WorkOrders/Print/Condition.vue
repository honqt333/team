<template>
    <PrintLayout
        documentType="condition_report"
        :data="mappedData"
        :centerData="mappedCenterData"
        :documentSettings="documentSettings"
        :visualSettings="visualSettings"
        :backUrl="workOrder?.id ? route('work-orders.show', workOrder.id) : null"
        :printLabel="$t('work_orders.print_view.print_btn')"
    />
</template>

<script setup>
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { usePage, router } from '@inertiajs/vue3';
import PrintLayout from '@/Components/Print/PrintLayout.vue';

const { t } = useI18n();

const props = defineProps({ workOrder: Object });

const page = usePage();
const { locale } = useI18n();
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

const formatNumber = (num) =>
    new Intl.NumberFormat(isRtl.value ? 'ar-SA-u-nu-latn' : 'en-US').format(num || 0);

const mappedData = computed(() => {
    const make =
        props.workOrder.vehicle?.make?.name_ar || props.workOrder.vehicle?.make?.name_en || '';
    const model =
        props.workOrder.vehicle?.model?.name_ar || props.workOrder.vehicle?.model?.name_en || '';
    const vehicleStr = `${make} ${model}`.trim() || '-';

    return {
        code: props.workOrder.code,
        created_at: new Date().toISOString(),
        entry_date: props.workOrder.entry_date,
        mileage: props.workOrder.mileage,
        odometer: props.workOrder.mileage ? formatNumber(props.workOrder.mileage) : '-',
        fuel_level: props.workOrder.fuel_level,
        customer_complaint: props.workOrder.customer_complaint,
        damage_marks: props.workOrder.damage_marks || [],
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
    };
});

const mappedCenterData = computed(() => {
    const center = props.workOrder.center || {};
    const tenant = props.workOrder.tenant || page.props.tenant || {};
    return {
        name: isRtl.value
            ? center.name_ar || center.name || tenant.name
            : center.name_en || center.name || tenant.name,
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
    const docSettings = tenantSettings?.documents?.['condition_report'] || {};
    return {
        title_ar: docSettings.title_ar || t('print.vehicle_report'),
        title_en: docSettings.title_en || 'Vehicle Condition Report',
        terms: docSettings.terms || [],
        print_terms: docSettings.print_terms !== false,
        show_stamp: docSettings.show_stamp !== false,
        show_customer_address: docSettings.show_customer_address !== false,
        signatures:
            docSettings.signatures && docSettings.signatures.length > 0
                ? docSettings.signatures
                : [{ name_ar: 'توقيع المستلم' }, { name_ar: 'توقيع العميل' }],
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
        stamp_url: center.stamp_url || vis.stamp_url || '',
    };
});
</script>

<style>
@media print {
    @page {
        size: A4;
        margin: 1cm;
    }
    body {
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
        background-color: white !important;
    }
    .print-container {
        padding: 0;
    }
    .print-container,
    .print-container * {
        visibility: visible !important;
    }
}
</style>
