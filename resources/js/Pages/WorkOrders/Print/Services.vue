<template>
    <PrintLayout
        documentType="work_order"
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

const props = defineProps({
    workOrder: Object,
    itemsByDepartment: Object,
    departments: Object,
    printedDepartment: { type: Object, default: null },
    printedTechnician: { type: Object, default: null },
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

const calculateItemDueDate = (item, fallbackStart) => {
    if (item.due_date) return item.due_date;
    const start = item.started_at || fallbackStart;
    if (!start) return null;
    const startDate = new Date(start);
    if (isNaN(startDate.getTime())) return null;
    const val = parseInt(item.duration_value);
    if (isNaN(val) || val <= 0) return start;

    const unit = item.duration_unit || 'minutes';
    if (unit === 'days') startDate.setDate(startDate.getDate() + val);
    else if (unit === 'weeks') startDate.setDate(startDate.getDate() + val * 7);
    else if (unit === 'months') startDate.setMonth(startDate.getMonth() + val);
    else if (unit === 'years') startDate.setFullYear(startDate.getFullYear() + val);
    else {
        startDate.setMinutes(startDate.getMinutes() + val);
    }
    return startDate.toISOString();
};

const mappedData = computed(() => {
    const make =
        props.workOrder.vehicle?.make?.name_ar || props.workOrder.vehicle?.make?.name_en || '';
    const model =
        props.workOrder.vehicle?.model?.name_ar || props.workOrder.vehicle?.model?.name_en || '';
    const vehicleStr = `${make} ${model}`.trim() || '-';

    const items = (props.workOrder.items || []).map((item) => {
        let serviceName = '';
        const isStandard =
            item.service &&
            item.service.name_ar?.trim() !== 'أخرى' &&
            item.service.name_en?.trim().toLowerCase() !== 'other';

        if (isStandard) {
            serviceName =
                locale.value === 'ar'
                    ? item.service.name_ar || item.title
                    : item.service.name_en || item.service.name_ar || item.title;
        } else {
            serviceName = item.title || '';
        }

        const itemTechnicians = (item.technicians || [])
            .map((tech) => {
                if (tech.employee) {
                    return locale.value === 'ar'
                        ? tech.employee.name_ar || tech.employee.name_en || tech.name
                        : tech.employee.name_en || tech.employee.name_ar || tech.name;
                }
                return tech.name;
            })
            .filter(Boolean);

        return {
            service_name: serviceName,
            description: '',
            technicians: itemTechnicians,
            technician: itemTechnicians.join(' - '),
            status: item.status || null,
            started_at: item.started_at || null,
            due_date: calculateItemDueDate(
                item,
                props.workOrder.entry_date || props.workOrder.created_at
            ),
            is_part: false,
        };
    });

    return {
        code: props.workOrder.code,
        created_at: new Date().toISOString(),
        entry_date: props.workOrder.entry_date,
        expected_end_date: props.workOrder.expected_end_date,
        mileage: props.workOrder.mileage,
        odometer: props.workOrder.mileage ? formatNumber(props.workOrder.mileage) : '-',
        fuel_level: props.workOrder.fuel_level,
        customer_complaint: props.workOrder.customer_complaint,
        initial_assessment: props.workOrder.initial_assessment,
        reception_signature: props.workOrder.reception_signature,
        delivery_signature: props.workOrder.delivery_signature,
        printed_department: props.printedDepartment || null,
        printed_technician: props.printedTechnician || null,
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
        items: items,
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
    const docSettings = tenantSettings?.documents?.['work_order'] || {};

    let titleAr = docSettings.title_ar || t('work_orders.work_order_label');
    let titleEn = docSettings.title_en || 'Work Order';

    if (props.printedDepartment) {
        titleAr = `${titleAr} - ${props.printedDepartment.name_ar}`;
        titleEn = `${titleEn} - ${props.printedDepartment.name_en}`;
    }

    if (props.printedTechnician) {
        const techNameAr = props.printedTechnician.name_ar || props.printedTechnician.name_en || '';
        const techNameEn = props.printedTechnician.name_en || props.printedTechnician.name_ar || '';
        titleAr = `${titleAr} ${t('work_orders.technician_label')} ${techNameAr}`.trim();
        titleEn = `${titleEn} - Tech: ${techNameEn}`.trim();
    }

    return {
        title_ar: titleAr,
        title_en: titleEn,
        terms: docSettings.terms || [],
        print_terms: docSettings.print_terms !== false,
        show_stamp: docSettings.show_stamp !== false,
        show_customer_address: docSettings.show_customer_address !== false,
        signatures:
            docSettings.signatures && docSettings.signatures.length > 0
                ? docSettings.signatures
                : [
                      { name_ar: 'توقيع المدير', name_en: 'Manager Signature' },
                      { name_ar: 'توقيع الفني', name_en: 'Technician Signature' },
                      { name_ar: 'توقيع العميل', name_en: 'Customer Signature' },
                  ],
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
