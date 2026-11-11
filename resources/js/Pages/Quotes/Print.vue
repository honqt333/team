<template>
    <PrintLayout
        documentType="quotation"
        :data="mappedPrintData"
        :centerData="mappedCenterData"
        :documentSettings="documentSettings"
        :visualSettings="visualSettings"
        :backUrl="quote?.id ? route('app.quotes.show', quote.id) : null"
    />
</template>

<script setup>
import { computed, onMounted } from 'vue';
import { useI18n } from 'vue-i18n';
import { usePage, router } from '@inertiajs/vue3';
import PrintLayout from '@/Components/Print/PrintLayout.vue';
import { usePrint, resolveDocumentSettings, resolveVisualSettings } from '@/Composables/usePrint';

const props = defineProps({
    quote: Object,
});

const page = usePage();
const { t, locale } = useI18n();
const { print } = usePrint();
const isRtl = computed(() => locale.value === 'ar');

const goBack = () => {
    if (window.history.length <= 1) {
        window.close();
        setTimeout(() => {
            if (props.quote?.id) {
                router.visit(route('app.quotes.show', props.quote.id));
            }
        }, 100);
    } else {
        window.history.back();
    }
};
const printPage = () => print();

onMounted(() => {
    // Automatically trigger print dialog
    setTimeout(() => {
        print();
    }, 500);
});

// Helper for Center Address
const getCenterAddress = (center) => {
    if (!center || !center.address) return '';
    const addr = center.address;
    const parts = [
        addr.building_number ? `${t('common.building')} ${addr.building_number}` : '',
        addr.street ? `${t('common.street')} ${addr.street}` : '',
        addr.district ? `${t('common.district')} ${addr.district}` : '',
        addr.city ? addr.city : '',
        addr.postal_code ? `${t('common.postal_code')} ${addr.postal_code}` : '',
    ].filter(Boolean);
    return parts.join('، ');
};

const mappedPrintData = computed(() => {
    let vehicleStr = '-';
    if (props.quote.vehicle) {
        const make =
            props.quote.vehicle.make?.name_ar ||
            props.quote.vehicle.make?.name_en ||
            props.quote.vehicle.make ||
            '';
        const model =
            props.quote.vehicle.model?.name_ar ||
            props.quote.vehicle.model?.name_en ||
            props.quote.vehicle.model ||
            '';
        vehicleStr = `${make} ${model}`.trim() || '-';
    }

    const services = (props.quote.lines || []).map((line) => {
        let serviceName = '';
        const isStandard =
            line.service &&
            line.service.name_ar?.trim() !== 'أخرى' &&
            line.service.name_en?.trim().toLowerCase() !== 'other';

        if (isStandard) {
            serviceName =
                locale.value === 'ar'
                    ? line.service.name_ar || line.description
                    : line.service.name_en || line.service.name_ar || line.description;
        } else {
            serviceName = line.description || '';
        }

        return {
            service_name: serviceName,
            description: '',
            qty: Number(line.qty || 1),
            unit_price: Number(line.unit_price || 0),
            discount: Number(line.discount_amount || 0),
            is_part: false,
            is_taxable: line.is_taxable !== false,
            tax_rate_snapshot: Number(line.tax_rate_snapshot || 0),
            tax_amount: Number(line.tax_amount || 0),
            total: Number(line.line_total || 0),
            line_total_excl_tax: Number(line.line_total_excl_tax || 0),
            line_total_incl_tax: Number(line.line_total_incl_tax || 0),
        };
    });

    const parts = (props.quote.parts || [])
        .filter((part) => !part.hide_on_print)
        .map((part) => ({
            service_name: part.name || '—',
            description: part.part_number || part.part?.sku || 'NO-SKU',
            qty: Number(part.qty || 1),
            unit_price: Number(part.unit_price || 0),
            discount: Number(part.discount || 0),
            is_part: true,
            is_taxable: part.is_taxable !== false,
            tax_rate_snapshot: Number(part.tax_rate_snapshot || 0),
            tax_amount: Number(part.tax_amount || 0),
            total: Number(part.total_incl_tax || part.total || 0),
            line_total_excl_tax: Number(part.total_excl_tax || 0),
            line_total_incl_tax: Number(part.total_incl_tax || 0),
        }));

    return {
        code: props.quote.code,
        created_at: props.quote.created_at,
        entry_date: props.quote.created_at,
        valid_until: props.quote.valid_until,
        mileage: props.quote.odometer,
        odometer: props.quote.odometer ? Number(props.quote.odometer).toLocaleString() : '-',
        customer_complaint: props.quote.customer_complaint,
        customer: {
            name: props.quote.customer?.name || '—',
            phone: props.quote.customer?.phone || '',
            address:
                props.quote.customer_address_snapshot || props.quote.customer?.address_line || '',
            tax_number: props.quote.customer_vat_snapshot || props.quote.customer?.tax_number || '',
        },
        vehicle: {
            make: vehicleStr,
            plate: props.quote.vehicle?.plate_number,
            color: props.quote.vehicle?.color,
        },
        tax_enabled_snapshot: Boolean(props.quote.tax_enabled_snapshot),
        currency_code: props.quote.currency_code,
        pricing_mode_snapshot: props.quote.pricing_mode_snapshot || 'exclusive',
        total_excl_tax: Number(props.quote.total_excl_tax || 0),
        total_tax: props.quote.tax_enabled_snapshot ? Number(props.quote.total_tax || 0) : 0,
        total_incl_tax: props.quote.tax_enabled_snapshot
            ? Number(props.quote.total_incl_tax || 0)
            : Number(props.quote.total_excl_tax || 0),
        total_paid: 0,
        balance: props.quote.tax_enabled_snapshot
            ? Number(props.quote.total || 0)
            : Number(props.quote.total_excl_tax || 0),
        signature_url: props.quote.signature_url,
        customer_signature: props.quote.signature_url,
        items: [...services, ...parts],
    };
});

const mappedCenterData = computed(() => {
    const center = props.quote.center || {};
    const tenant = props.quote.tenant || page.props?.tenant || {};
    return {
        name: isRtl.value
            ? center.name_ar || center.name || tenant.name
            : center.name_en || center.name || tenant.name,
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
    const tenantSettings = props.quote.tenant?.print_settings || page.props?.tenant?.print_settings;
    return resolveDocumentSettings({
        tenantSettings,
        documentType: 'quotation',
        customerSignatureUrl: props.quote.signature_url,
        legacyTerms: props.quote.tenant?.quote_terms || page.props?.tenant?.quote_terms,
        defaults: {
            title_ar: t('quotes.quotation_or_estimate'),
            title_en: 'Price Quotation / Assessment',
        },
    });
});

const visualSettings = computed(() => {
    const tenantSettings = props.quote.tenant?.print_settings || page.props?.tenant?.print_settings;
    const center = props.quote.center || {};
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
}
</style>
