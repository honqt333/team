<template>
    <PrintLayout
        documentType="purchase_invoice"
        :data="mappedPrintData"
        :centerData="mappedCenterData"
        :documentSettings="documentSettings"
        :visualSettings="visualSettings"
        :backUrl="invoice?.id ? route('purchases.show', invoice.id) : null"
    />
</template>

<script setup>
import {
    printDocument,
    resolveDocumentSettings,
    resolveVisualSettings,
} from '@/Composables/usePrint';
import { computed, onMounted } from 'vue';
import { useI18n } from 'vue-i18n';
import { usePage, router } from '@inertiajs/vue3';
import PrintLayout from '@/Components/Print/PrintLayout.vue';

const props = defineProps({
    invoice: Object,
});

const page = usePage();
const { t, locale } = useI18n();
const isRtl = computed(() => locale.value === 'ar');

const goBack = () => {
    if (window.history.length <= 1) {
        window.close();
        setTimeout(() => {
            if (props.invoice?.id) {
                router.visit(route('app.invoices.purchases.show', props.invoice.id));
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

// Helper to format detailed address objects
const getFormattedAddress = (addr) => {
    if (!addr) return '';
    const parts = [
        addr.building_number ? `${t('common.building')} ${addr.building_number}` : '',
        addr.street ? `${t('common.street')} ${addr.street}` : '',
        addr.district ? `${t('common.district')} ${addr.district}` : '',
        addr.city ? addr.city : '',
        addr.postal_code ? `${t('common.postal_code')} ${addr.postal_code}` : '',
        addr.address_line ? addr.address_line : '',
    ].filter(Boolean);
    return parts.join('، ');
};

// Helper for Supplier Address
const getSupplierAddress = (supplier) => {
    if (!supplier) return '';
    const parts = [
        supplier.building_number ? `${t('common.building')} ${supplier.building_number}` : '',
        supplier.street ? `${t('common.street')} ${supplier.street}` : '',
        supplier.district ? `${t('common.district')} ${supplier.district}` : '',
        supplier.city ? supplier.city : '',
        supplier.postal_code ? `${t('common.postal_code')} ${supplier.postal_code}` : '',
        supplier.address ? supplier.address : supplier.address_line ? supplier.address_line : '',
    ].filter(Boolean);
    return parts.join('، ');
};

// Helper for Center Address
const getCenterAddress = (center) => {
    if (!center || !center.address) return '';
    return getFormattedAddress(center.address);
};

// Helper for Tenant Address
const getTenantAddress = (tenant) => {
    if (!tenant) return '';
    if (tenant.address && typeof tenant.address === 'object') {
        return getFormattedAddress(tenant.address);
    }
    return tenant.address || '';
};

// Subtotal calculation (cost excl. tax before line discounts)
const calculatedInvoiceSubtotal = computed(() => {
    return Number(props.invoice?.subtotal || 0);
});

const calculatedInvoiceTaxAmount = computed(() => {
    return Number(props.invoice?.tax_amount || 0);
});

const calculatedInvoiceTotal = computed(() => {
    return Number(props.invoice?.total || 0);
});

const mappedPrintData = computed(() => {
    const isCompany = !!props.invoice.company_transaction;
    const companyTitle = props.invoice.company_transaction?.title;

    const lines = (props.invoice.lines || []).map((line) => ({
        service_name: isCompany
            ? props.invoice.company_transaction?.income_category
                ? locale.value === 'ar'
                    ? props.invoice.company_transaction.income_category.name_ar
                    : props.invoice.company_transaction.income_category.name_en
                : companyTitle || '—'
            : line.part?.name || '—',
        description: isCompany
            ? props.invoice.company_transaction?.income_category
                ? companyTitle
                : ''
            : line.part?.sku || 'NO-SKU',
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
        code: props.invoice.code,
        created_at: props.invoice.issue_date,
        entry_date: props.invoice.supply_date || props.invoice.issue_date,
        tax_enabled_snapshot: true,
        pricing_mode_snapshot: 'exclusive',
        total_excl_tax:
            Number(calculatedInvoiceSubtotal.value || 0) -
            Number(props.invoice.discount_amount || 0),
        total_tax: Number(calculatedInvoiceTaxAmount.value || 0),
        total_incl_tax: Number(calculatedInvoiceTotal.value || 0),
        total_paid: Number(calculatedInvoiceTotal.value || 0) - Number(props.invoice.balance || 0),
        balance: Number(props.invoice.balance || 0),
        discount_amount: Number(props.invoice.discount_amount || 0),
        customer: {
            name:
                (isCompany
                    ? props.invoice.company_transaction?.contact?.name ||
                      props.invoice.supplier?.name
                    : props.invoice.supplier?.name) || '—',
            phone:
                (isCompany
                    ? props.invoice.company_transaction?.contact?.phone ||
                      props.invoice.supplier?.phone
                    : props.invoice.supplier?.phone) || '',
            address: getSupplierAddress(
                isCompany
                    ? props.invoice.company_transaction?.contact || props.invoice.supplier
                    : props.invoice.supplier
            ),
            tax_number:
                (isCompany
                    ? props.invoice.company_transaction?.contact?.tax_number ||
                      props.invoice.supplier?.tax_number
                    : props.invoice.supplier?.tax_number) || '',
        },
        is_company: isCompany,
        items: lines,
    };
});

const mappedCenterData = computed(() => {
    const isCompany = !!props.invoice.company_transaction;
    const center = isCompany ? {} : props.invoice.center || {};
    const tenant = props.invoice.tenant || page.props?.tenant || {};
    return {
        name: isRtl.value
            ? tenant.trade_name || tenant.legal_name || tenant.name || center.name_ar || center.name
            : tenant.trade_name ||
              tenant.legal_name ||
              tenant.name ||
              center.name_en ||
              center.name,
        tax_number: tenant.vat_number || center.vat_number,
        cr_number: tenant.cr_number,
        phone: tenant.phone || center.phone,
        logo: isCompany
            ? tenant.logo_url || ''
            : center.logo_invoice_url || center.logo_light_url || tenant.logo_url || '',
        iban: tenant.iban || '',
        address: isCompany
            ? getTenantAddress(tenant)
            : getTenantAddress(tenant) || getCenterAddress(center) || '',
        stamp_url: isCompany ? '' : center.stamp_url || '',
    };
});

const documentSettings = computed(() => {
    const tenantSettings =
        props.invoice.tenant?.print_settings || page?.props?.tenant?.print_settings;
    return resolveDocumentSettings({
        tenantSettings,
        documentType: 'purchase_invoice',
        defaults: {
            title_ar: 'فاتورة مشتريات',
            title_en: 'Purchase Invoice',
            print_terms: false,
            show_stamp: false,
            show_qr_code: false,
            signatures: [
                { name_ar: 'توقيع أمين المستودع', name_en: 'Storekeeper Signature', show: true },
                {
                    name_ar: 'توقيع المدير المالي',
                    name_en: 'Financial Manager Signature',
                    show: true,
                },
            ],
        },
    });
});

const visualSettings = computed(() => {
    const tenantSettings =
        props.invoice.tenant?.print_settings || page?.props?.tenant?.print_settings;
    const isCompany = !!props.invoice.company_transaction;
    const center = isCompany ? {} : props.invoice.center || {};
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
