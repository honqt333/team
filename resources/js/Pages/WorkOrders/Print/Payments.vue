<template>
    <PrintLayout
        documentType="payments"
        :data="mappedData"
        :centerData="mappedCenterData"
        :documentSettings="documentSettings"
        :visualSettings="visualSettings"
        :backUrl="workOrder?.id ? route('work-orders.show', workOrder.id) : null"
    />
</template>

<script setup>
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { usePage, router } from '@inertiajs/vue3';
import PrintLayout from '@/Components/Print/PrintLayout.vue';
import { resolveDocumentSettings, resolveVisualSettings } from '@/Composables/usePrint';

const { t } = useI18n();

const props = defineProps({
    workOrder: Object,
    payments: Array,
    grandTotal: Number,
    totalPaid: Number,
    badDebt: { type: Number, default: 0 },
    balance: Number,
});

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
        total_incl_tax: props.grandTotal,
        currency_code: props.workOrder.currency_code,
        total_tax: 0,
        total_excl_tax: props.grandTotal,
        bad_debt:
            props.badDebt ||
            (props.payments || []).reduce(
                (s, p) => (p.type === 'bad_debt' ? s + Number(p.amount || 0) : s),
                0
            ),
        total_paid: props.totalPaid,
        balance: props.balance,
        reception_signature: props.workOrder.reception_signature,
        delivery_signature: props.workOrder.delivery_signature,
        payments: props.payments || [],
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
    const tenantSettings =
        props.workOrder?.tenant?.print_settings || page?.props?.tenant?.print_settings;
    return resolveDocumentSettings({
        tenantSettings,
        documentType: 'payments',
        customerSignatureUrl:
            props.workOrder?.reception_signature || props.workOrder?.delivery_signature || null,
        defaults: {
            title_ar: t('work_orders.payments_and_receipts'),
            title_en: 'Payments Receipt',
            signatures: [
                { name_ar: 'توقيع المحاسب', name_en: 'Accountant Signature', show: true },
                { name_ar: 'توقيع العميل', name_en: 'Customer Signature', show: true },
            ],
        },
    });
});

const visualSettings = computed(() => {
    const tenantSettings =
        props.workOrder?.tenant?.print_settings || page?.props?.tenant?.print_settings;
    const center = props.workOrder?.center || {};
    return resolveVisualSettings({
        tenantSettings,
        center,
        overrides: {
            primary_color: tenantSettings?.visual?.primary_color || '#3b82f6',
        },
    });
});
</script>

<style>
@media print {
    @page {
        size: A4;
        margin: 15mm 10mm 12mm 10mm;
    }
    body {
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
        background: white;
    }
    .print-container,
    .print-container * {
        visibility: visible !important;
    }
}
</style>
