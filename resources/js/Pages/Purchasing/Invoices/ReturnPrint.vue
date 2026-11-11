<template>
    <PrintLayout
        documentType="purchase_return"
        :data="mappedPrintData"
        :centerData="mappedCenterData"
        :documentSettings="documentSettings"
        :visualSettings="visualSettings"
        :backUrl="returnInvoice?.id ? route('purchases.returns.show', returnInvoice.id) : null"
    />
</template>

<script setup>
import { printDocument } from '@/Composables/usePrint';
import { computed, onMounted } from 'vue';
import { useI18n } from 'vue-i18n';
import { usePage, router } from '@inertiajs/vue3';
import PrintLayout from '@/Components/Print/PrintLayout.vue';

const props = defineProps({
    returnInvoice: Object,
});

const page = usePage();
const { t, locale } = useI18n();
const isRtl = computed(() => locale.value === 'ar');

const goBack = () => {
    if (window.history.length <= 1) {
        window.close();
        setTimeout(() => {
            if (props.returnInvoice?.id) {
                router.visit(route('app.invoices.purchases.returns.show', props.returnInvoice.id));
            }
        }, 100);
    } else {
        window.history.back();
    }
};

onMounted(() => {
    // Automatically trigger print dialog
    setTimeout(() => {
        printDocument();
    }, 500);
});

// Helper for Supplier Address
const getSupplierAddress = (supplier) => {
    if (!supplier) return '';
    const parts = [
        supplier.building_number ? `${t('common.building')} ${supplier.building_number}` : '',
        supplier.street ? `${t('common.street')} ${supplier.street}` : '',
        supplier.district ? `${t('common.district')} ${supplier.district}` : '',
        supplier.city ? supplier.city : '',
        supplier.postal_code ? `${t('common.postal_code')} ${supplier.postal_code}` : '',
        supplier.address ? supplier.address : '',
    ].filter(Boolean);
    return parts.join('، ');
};

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

// Helper for Tenant Address
const getTenantAddress = (tenant) => {
    if (!tenant) return '';
    const addr = tenant.address;
    if (!addr) return '';
    if (typeof addr === 'object') {
        if (addr.address_line) return addr.address_line;
        const parts = [
            addr.building_number ? t('common.building') : '',
            addr.street ? t('common.street') : '',
            addr.district ? t('common.district') : '',
            addr.city ? addr.city : '',
            addr.postal_code ? t('center_settings.address.postal_code') : '',
        ].filter(Boolean);
        return parts.join('، ');
    }
    if (typeof addr === 'string' && addr.startsWith('{')) {
        try {
            const parsed = JSON.parse(addr);
            if (parsed.address_line) return parsed.address_line;
            const parts = [
                parsed.building_number ? t('common.building') : '',
                parsed.street ? t('common.street') : '',
                parsed.district ? t('common.district') : '',
                parsed.city ? parsed.city : '',
                parsed.postal_code ? t('center_settings.address.postal_code') : '',
            ].filter(Boolean);
            return parts.join('، ');
        } catch (e) {
            return addr;
        }
    }
    return addr || '';
};

const mappedPrintData = computed(() => {
    const lines = (props.returnInvoice.lines || []).map((line) => ({
        service_name: line.part?.name || '—',
        description: line.part?.sku || 'NO-SKU',
        qty: Number(line.qty || 1),
        unit_price: Number(line.unit_cost || 0),
        discount: 0,
        is_part: true,
        is_taxable: true,
        tax_rate_snapshot: Number(line.tax_rate || 0),
        tax_amount: Number(line.tax_amount || 0),
        total: Number(line.total || 0),
    }));

    return {
        code: props.returnInvoice.code,
        created_at: props.returnInvoice.return_date,
        entry_date: props.returnInvoice.return_date,
        tax_enabled_snapshot: true,
        pricing_mode_snapshot: 'exclusive',
        total_excl_tax: Number(props.returnInvoice.subtotal || 0),
        total_tax: Number(props.returnInvoice.tax_amount || 0),
        total_incl_tax: Number(props.returnInvoice.total || 0),
        total_paid: Number(props.returnInvoice.total || 0),
        balance: 0,
        discount_amount: 0,
        customer: {
            name: props.returnInvoice.purchase_invoice?.supplier?.name || '—',
            phone: props.returnInvoice.purchase_invoice?.supplier?.phone || '',
            address: getSupplierAddress(props.returnInvoice.purchase_invoice?.supplier),
            tax_number: props.returnInvoice.purchase_invoice?.supplier?.tax_number || '',
        },
        items: lines,
    };
});

const mappedCenterData = computed(() => {
    const center = props.returnInvoice.purchase_invoice?.center || {};
    const tenant = props.returnInvoice.purchase_invoice?.tenant || page.props?.tenant || {};
    return {
        name: isRtl.value
            ? center.name_ar || center.name || tenant.name
            : center.name_en || center.name || tenant.name,
        tax_number: center.vat_number || tenant.vat_number,
        cr_number: tenant.cr_number,
        phone: center.phone || tenant.phone,
        logo: center.logo_invoice_url || center.logo_light_url || tenant.logo_url || '',
        iban: tenant.iban || '',
        address: getCenterAddress(center) || getTenantAddress(tenant) || '',
        stamp_url: center.stamp_url || '',
    };
});

const documentSettings = computed(() => {
    return {
        title_ar: 'فاتورة مرتجع مشتريات',
        title_en: 'Purchase Return Invoice',
        terms: [],
        print_terms: false,
        show_stamp: false,
        show_customer_address: true,
        signatures: [
            { name_ar: 'توقيع أمين المستودع', name_en: 'Storekeeper Signature' },
            { name_ar: 'توقيع المدير المالي', name_en: 'Financial Manager Signature' },
        ],
    };
});

const visualSettings = computed(() => {
    const tenantSettings = page.props?.tenant?.print_settings;
    const vis = tenantSettings?.visual || {};
    return {
        active_template: vis.active_template || 'TemplateDefaultA4',
        show_logo: vis.show_logo !== false,
        show_stamp: false,
        show_qr_code: false,
        primary_color: vis.primary_color || '#3b82f6',
        footer_text:
            vis.footer_text ||
            props.returnInvoice.purchase_invoice?.tenant?.print_settings?.footer_text ||
            '',
        stamp_url: '',
    };
});
</script>

<style>
@media print {
    @page {
        size: A4;
        margin: 0 1cm;
    }
    body {
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
        background: white;
    }
}
</style>
