<template>
    <div class="print-container bg-white min-h-screen p-8">
        <!-- Header -->
        <div class="flex justify-between items-start mb-8">
            <!-- Company Info -->
            <div class="flex items-start gap-4">
                <div v-if="invoice.tenant?.logo_url" class="w-20 h-20">
                    <img :src="invoice.tenant.logo_url" alt="Logo" class="w-full h-full object-contain" />
                </div>
                <div>
                    <h1 class="text-xl font-bold text-gray-900">{{ invoice.tenant?.name }}</h1>
                    <p v-if="invoice.tenant?.tax_number" class="text-sm text-gray-600">
                        {{ labels.vat_number }}: {{ invoice.tenant?.tax_number }}
                    </p>
                    <p v-if="invoice.center?.phone" class="text-sm text-gray-600">{{ invoice.center?.phone }}</p>
                </div>
            </div>

            <!-- Invoice Title -->
            <div class="text-left">
                <h2 class="text-2xl font-bold text-gray-900">{{ labels.document_title }}</h2>
                <p class="text-lg font-medium text-gray-700">{{ invoice.invoice_number }}</p>
            </div>
        </div>

        <!-- Invoice Meta -->
        <div class="grid grid-cols-2 gap-8 mb-8 border-t border-b border-gray-200 py-4">
            <div>
                <h3 class="text-sm font-medium text-gray-500 mb-2">{{ labels.customer }}</h3>
                <p class="font-medium text-gray-900">{{ invoice.customer_name_snapshot || invoice.customer?.name }}</p>
                <p v-if="invoice.customer_vat_snapshot" class="text-sm text-gray-600">{{ labels.vat_number }}: {{ invoice.customer_vat_snapshot }}</p>
            </div>
            <div class="text-left">
                <div class="grid grid-cols-2 gap-2 text-sm">
                    <span class="text-gray-500">{{ labels.invoice_number }}:</span>
                    <span class="font-medium">{{ invoice.invoice_number }}</span>
                    <span class="text-gray-500">{{ labels.date }}:</span>
                    <span class="font-medium">{{ formatDate(invoice.issue_date) }}</span>
                    <span class="text-gray-500">{{ labels.supply_date }}:</span>
                    <span class="font-medium">{{ formatDate(invoice.supply_date) }}</span>
                </div>
            </div>
        </div>

        <!-- Vehicle Info (if work order) -->
        <div v-if="invoice.work_order?.vehicle" class="mb-6 bg-gray-50 p-4 rounded-lg">
            <h3 class="text-sm font-medium text-gray-500 mb-2">{{ labels.vehicle }}</h3>
            <p class="font-medium text-gray-900">
                {{ invoice.work_order.vehicle.make }} {{ invoice.work_order.vehicle.model }} ({{ invoice.work_order.vehicle.year }})
            </p>
            <p class="text-sm text-gray-600">{{ labels.plate }}: {{ invoice.work_order.vehicle.plate_number }}</p>
        </div>

        <!-- Invoice Lines -->
        <table class="w-full mb-8">
            <thead>
                <tr class="border-b-2 border-gray-300">
                    <th class="py-2 text-right text-sm font-medium text-gray-500">{{ labels.description }}</th>
                    <th class="py-2 text-center text-sm font-medium text-gray-500 w-20">{{ labels.qty }}</th>
                    <th class="py-2 text-left text-sm font-medium text-gray-500 w-28">{{ labels.unit_price }}</th>
                    <th class="py-2 text-left text-sm font-medium text-gray-500 w-28">{{ labels.total }}</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="line in invoice.lines" :key="line.id" class="border-b border-gray-200">
                    <td class="py-2 text-sm text-gray-900">{{ line.description }}</td>
                    <td class="py-2 text-sm text-center text-gray-600" dir="ltr">{{ line.qty }}</td>
                    <td class="py-2 text-sm text-left text-gray-600" dir="ltr">{{ formatCurrency(line.unit_price) }}</td>
                    <td class="py-2 text-sm text-left font-medium text-gray-900" dir="ltr">{{ formatCurrency(line.line_total_incl_tax) }}</td>
                </tr>
            </tbody>
        </table>

        <!-- Totals -->
        <div class="flex justify-end mb-8">
            <div class="w-64">
                <div class="flex justify-between py-2 border-b border-gray-200">
                    <span class="text-sm text-gray-600">{{ labels.subtotal }}</span>
                    <span class="text-sm font-medium text-gray-900" dir="ltr">{{ formatCurrency(invoice.total_excl_tax) }}</span>
                </div>
                <div class="flex justify-between py-2 border-b border-gray-200">
                    <span class="text-sm text-gray-600">{{ labels.tax }} ({{ invoice.tax_rate_snapshot }}%)</span>
                    <span class="text-sm font-medium text-gray-900" dir="ltr">{{ formatCurrency(invoice.total_tax) }}</span>
                </div>
                <div class="flex justify-between py-2 bg-gray-100 px-2 rounded">
                    <span class="font-bold text-gray-900">{{ labels.grand_total }}</span>
                    <span class="font-bold text-gray-900" dir="ltr">{{ formatCurrency(invoice.total_incl_tax) }}</span>
                </div>
            </div>
        </div>

        <!-- Payment Status -->
        <div class="border-t border-gray-200 pt-4 mb-8">
            <div class="flex justify-between items-center">
                <div>
                    <span class="text-sm text-gray-500">{{ labels.payment_status }}:</span>
                    <span class="ml-2 font-medium" :class="invoice.payment_status === 'paid' ? 'text-green-600' : 'text-red-600'">
                        {{ paymentStatusLabel }}
                    </span>
                </div>
                <div v-if="invoice.total_paid > 0" class="text-left">
                    <div class="text-sm text-gray-600">{{ labels.paid }}: <span dir="ltr">{{ formatCurrency(invoice.total_paid) }}</span></div>
                    <div v-if="balance > 0" class="text-sm text-red-600 font-medium">{{ labels.balance }}: <span dir="ltr">{{ formatCurrency(balance) }}</span></div>
                </div>
            </div>
        </div>

        <!-- QR Code -->
        <div v-if="invoice.zatca_qr_tlv" class="flex justify-center mb-8">
            <div class="text-center">
                <img :src="generateQRCode(invoice.zatca_qr_tlv)" alt="ZATCA QR" class="w-32 h-32 mx-auto" />
                <p class="text-xs text-gray-500 mt-2">ZATCA Compliant</p>
            </div>
        </div>

        <!-- Terms & Conditions (Print Only) -->
        <div v-if="page.props.tenant?.invoice_terms" class="mt-8 pt-6 border-t border-gray-200">
            <h3 class="text-xs font-bold text-gray-900 mb-2">الشروط والأحكام</h3>
            <p class="text-[10px] text-gray-600 whitespace-pre-wrap leading-tight">{{ page.props.tenant.invoice_terms }}</p>
        </div>

        <!-- Footer -->
        <div v-if="template?.footer_text || page.props.tenant?.print_settings?.footer_text" class="text-center text-sm text-gray-500 border-t border-gray-200 pt-4 mt-4">
            {{ page.props.tenant?.print_settings?.footer_text || template.footer_text }}
        </div>
        <div class="text-center text-sm text-gray-400 mt-4">
            {{ labels.thank_you }}
        </div>

        <!-- Print Button (hidden in print) -->
        <div class="fixed bottom-4 left-4 print:hidden">
            <button
                @click="printPage"
                class="px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-lg shadow-lg flex items-center gap-2"
            >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                </svg>
                طباعة
            </button>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { usePage } from '@inertiajs/vue3';

const page = usePage();

const { t } = useI18n();

const props = defineProps({
    invoice: Object,
    template: Object,
    labels: Object,
});

const balance = computed(() => (props.invoice.total_incl_tax || 0) - (props.invoice.total_paid || 0));

const paymentStatusLabel = computed(() => {
    switch (props.invoice.payment_status) {
        case 'paid': return 'مدفوعة';
        case 'partial': return 'مدفوعة جزئياً';
        case 'unpaid': return 'غير مدفوعة';
        default: return props.invoice.payment_status;
    }
});

const formatDate = (date) => {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('ar-SA');
};

const formatCurrency = (amount) => {
    return new Intl.NumberFormat('ar-SA', {
        style: 'currency',
        currency: props.invoice.currency_code || 'SAR',
    }).format(amount || 0);
};

const generateQRCode = (base64Data) => {
    // Using a simple QR code generator - in production use a library
    return `https://api.qrserver.com/v1/create-qr-code/?size=128x128&data=${encodeURIComponent(base64Data)}`;
};

const printPage = () => {
    window.print();
};
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
    }
    
    .print-container {
        padding: 0;
    }
}
</style>
