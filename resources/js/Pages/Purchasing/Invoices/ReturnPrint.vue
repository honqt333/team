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
            :documentType="'purchase_return'"
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
import { usePage, router } from '@inertiajs/vue3';
import PrintEngine from '@/Components/Print/PrintEngine.vue';

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
const printPage = () => window.print();

onMounted(() => {
    // Automatically trigger print dialog
    setTimeout(() => {
        window.print();
    }, 500);
});

// Helper for Supplier Address
const getSupplierAddress = (supplier) => {
    if (!supplier) return '';
    const parts = [
        supplier.building_number ? `${t('common.building') || 'مبنى'} ${supplier.building_number}` : '',
        supplier.street ? `${t('common.street') || 'شارع'} ${supplier.street}` : '',
        supplier.district ? `${t('common.district') || 'حي'} ${supplier.district}` : '',
        supplier.city ? supplier.city : '',
        supplier.postal_code ? `${t('common.postal_code') || 'الرمز البريدي'} ${supplier.postal_code}` : '',
        supplier.address ? supplier.address : '',
    ].filter(Boolean);
    return parts.join('، ');
};

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
    const lines = (props.returnInvoice.lines || []).map(line => ({
        service_name: line.part?.name || '—',
        description: line.part?.sku || 'NO-SKU',
        qty: Number(line.qty || 1),
        unit_price: Number(line.unit_cost || 0),
        discount: 0,
        is_part: true,
        is_taxable: true,
        tax_rate_snapshot: Number(line.tax_rate || 0),
        tax_amount: Number(line.tax_amount || 0),
        total: Number(line.total || 0)
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
        items: lines
    };
});

const mappedCenterData = computed(() => {
    const center = props.returnInvoice.purchase_invoice?.center || {};
    const tenant = props.returnInvoice.purchase_invoice?.tenant || page.props?.tenant || {};
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
    return {
        title_ar: 'فاتورة مرتجع مشتريات',
        title_en: 'Purchase Return Invoice',
        terms: [],
        print_terms: false,
        show_stamp: false,
        show_customer_address: true,
        signatures: [
            { name_ar: 'توقيع أمين المستودع', name_en: 'Storekeeper Signature' },
            { name_ar: 'توقيع المدير المالي', name_en: 'Financial Manager Signature' }
        ]
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
        footer_text: vis.footer_text || props.returnInvoice.purchase_invoice?.tenant?.print_settings?.footer_text || '',
        stamp_url: ''
    };
});
</script>

<style>
@media print {
    @page { size: A4; margin: 0 1cm; }
    body { -webkit-print-color-adjust: exact; print-color-adjust: exact; background: white; }
}
</style>
