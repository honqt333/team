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
                {{ labels.print || $t('common.print') }}
            </button>
        </div>

        <PrintEngine 
            :documentType="invoice.work_order_id || invoice.company_transaction ? 'invoice' : 'parts_invoice'"
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
    invoice: Object,
    template: Object,
    labels: Object,
});

const page = usePage();
const { locale } = useI18n();
const isRtl = computed(() => locale.value === 'ar');

const goBack = () => {
    if (window.history.length <= 1) {
        // Try closing the tab first if it was opened in target="_blank"
        window.close();
        // Fallback: if closing is blocked, redirect using Inertia
        setTimeout(() => {
            if (props.invoice?.id) {
                router.visit(route('app.invoices.show', props.invoice.id));
            }
        }, 100);
    } else {
        window.history.back();
    }
};
const printPage = () => window.print();

const formatNumber = (num) => new Intl.NumberFormat(isRtl.value ? 'ar-SA-u-nu-latn' : 'en-US').format(num || 0);

const mappedData = computed(() => {
    // Map vehicle details
    let vehicleStr = '-';
    if (props.invoice.work_order?.vehicle) {
        const make = props.invoice.work_order.vehicle.make?.name_ar || props.invoice.work_order.vehicle.make?.name_en || props.invoice.work_order.vehicle.make || '';
        const model = props.invoice.work_order.vehicle.model?.name_ar || props.invoice.work_order.vehicle.model?.name_en || props.invoice.work_order.vehicle.model || '';
        vehicleStr = `${make} ${model}`.trim() || '-';
    }

    // Map line items
    const lines = (props.invoice.lines || []).map(line => ({
        service_name: line.description || '',
        description: line.notes || '',
        qty: Number(line.qty || 1),
        unit_price: Number(line.unit_price || 0),
        discount: Number(line.discount_amount || 0),
        is_part: line.is_part || false,
        is_taxable: line.is_taxable !== false,
        tax_rate_snapshot: line.tax_rate_snapshot,
        line_total_excl_tax: Number(line.line_total_excl_tax || 0),
        line_total_incl_tax: Number(line.line_total_incl_tax || 0),
        tax_amount: Number(line.tax_amount || 0)
    }));


    const qrUrl = props.invoice.zatca_qr_tlv 
        ? `https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=${encodeURIComponent(props.invoice.zatca_qr_tlv)}` 
        : '';

    const tenant = props.invoice.tenant || page.props.tenant || {};
    const isVatEnabled = tenant.tax_settings?.vat_enabled ?? tenant.taxSettings?.vat_enabled ?? true;

    return {
        code: props.invoice.invoice_number,
        created_at: props.invoice.issue_date,
        entry_date: props.invoice.supply_date || props.invoice.issue_date,
        mileage: props.invoice.work_order?.mileage,
        odometer: props.invoice.work_order?.mileage ? formatNumber(props.invoice.work_order.mileage) : '-',
        fuel_level: props.invoice.work_order?.fuel_level,
        customer_complaint: props.invoice.work_order?.customer_complaint,
        qr_code_url: qrUrl,
        customer: {
            name: props.invoice.customer_name_snapshot || props.invoice.customer?.name,
            phone: props.invoice.customer?.phone,
            address: props.invoice.customer_address_snapshot || props.invoice.customer?.address_line,
            tax_number: props.invoice.customer_vat_snapshot || props.invoice.customer?.tax_number,
        },
        vehicle: {
            make: vehicleStr,
            plate: props.invoice.work_order?.vehicle?.plate_number,
            color: props.invoice.work_order?.vehicle?.color,
        },
        tax_enabled_snapshot: props.invoice.tax_enabled_snapshot && isVatEnabled,
        pricing_mode_snapshot: props.invoice.pricing_mode_snapshot || props.invoice.work_order?.pricing_mode_snapshot,
        total_excl_tax: Number(props.invoice.total_excl_tax || 0),
        total_tax: Number(props.invoice.total_tax || 0),
        total_incl_tax: Number(props.invoice.total_incl_tax || 0),
        bad_debt: (props.invoice.payments || []).reduce((s, p) => p.type === 'bad_debt' ? s + Number(p.amount || 0) : s, 0),
        total_paid: Number(props.invoice.total_paid || 0),
        balance: Number(props.invoice.balance !== undefined ? props.invoice.balance : (Number(props.invoice.total_incl_tax || 0) - Number(props.invoice.total_paid || 0))),
        is_company: !!props.invoice.company_transaction,
        items: lines
    };
});

const formatAddress = (addr) => {
    if (!addr) return '';
    if (typeof addr === 'object') {
        if (addr.address_line) return addr.address_line;
        const parts = [
            addr.building_number ? `مبنى ${addr.building_number}` : '',
            addr.street ? `شارع ${addr.street}` : '',
            addr.district ? `حي ${addr.district}` : '',
            addr.city ? addr.city : '',
            addr.postal_code ? `الرمز البريدي ${addr.postal_code}` : '',
        ].filter(Boolean);
        return parts.join('، ');
    }
    if (typeof addr === 'string' && addr.startsWith('{')) {
        try {
            const parsed = JSON.parse(addr);
            if (parsed.address_line) return parsed.address_line;
            const parts = [
                parsed.building_number ? `مبنى ${parsed.building_number}` : '',
                parsed.street ? `شارع ${parsed.street}` : '',
                parsed.district ? `حي ${parsed.district}` : '',
                parsed.city ? parsed.city : '',
                parsed.postal_code ? `الرمز البريدي ${parsed.postal_code}` : '',
            ].filter(Boolean);
            return parts.join('، ');
        } catch (e) {
            return addr;
        }
    }
    return addr || '';
};

const mappedCenterData = computed(() => {
    const isCompany = !!props.invoice.company_transaction;
    const center = isCompany ? {} : (props.invoice.center || {});
    const tenant = props.invoice.tenant || page.props.tenant || {};
    return {
        name: isRtl.value ? (tenant.trade_name || tenant.legal_name || tenant.name || center.name_ar || center.name) : (tenant.trade_name || tenant.legal_name || tenant.name || center.name_en || center.name),
        tax_number: tenant.vat_number || center.vat_number,
        cr_number: tenant.cr_number,
        phone: tenant.phone || center.phone,
        logo: tenant.logo_url || center.logo_invoice_url || center.logo_light_url || '',
        iban: tenant.iban || '',
        address: formatAddress(tenant.address) || formatAddress(center.address) || '',
        stamp_url: isCompany ? '' : (center.stamp_url || ''),
    };
});

const documentSettings = computed(() => {
    const tenantSettings = page.props.tenant?.print_settings;
    const docSettings = tenantSettings?.documents?.['invoice'] || {};
    return {
        title_ar: docSettings.title_ar || props.labels?.document_title || 'فاتورة ضريبية مبسطة',
        title_en: docSettings.title_en || props.labels?.document_title || 'Simplified Tax Invoice',
        terms: docSettings.terms || (page.props.tenant?.invoice_terms ? [page.props.tenant.invoice_terms] : []),
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
    const center = props.invoice.center || {};
    return {
        active_template: vis.active_template || 'TemplateDefaultA4',
        show_logo: vis.show_logo !== false,
        show_stamp: vis.show_stamp !== false,
        show_qr_code: vis.show_qr_code !== false,
        primary_color: vis.primary_color || '#3b82f6',
        footer_text: vis.footer_text || props.invoice.tenant?.print_settings?.footer_text || '',
        stamp_url: center.stamp_url || vis.stamp_url || ''
    };
});
</script>

<style>
@media print {
    @page { size: A4; margin: 0 1cm; }
    body { -webkit-print-color-adjust: exact; print-color-adjust: exact; background: white; }
}
</style>
