<template>
    <div class="print-page bg-white min-h-screen font-sans text-gray-900" dir="rtl">
        <!-- Watermark/Draft overlay could go here -->

        <!-- Header Section -->
        <div class="flex justify-between items-start mb-10 border-b-4 border-slate-900 pb-6">
            <!-- Company/Tenant Logo & Info -->
            <div class="flex gap-6 items-center">
                <div v-if="invoice.tenant?.logo_url" class="w-24 h-24 bg-gray-50 rounded-2xl p-2 flex items-center justify-center border border-gray-100">
                    <img :src="invoice.tenant.logo_url" alt="Logo" class="max-w-full max-h-full object-contain" />
                </div>
                <div class="space-y-1">
                    <h1 class="text-2xl font-black tracking-tight text-slate-900">{{ invoice.tenant?.name }}</h1>
                    <div class="text-sm text-slate-500 font-bold space-y-0.5">
                        <p v-if="invoice.tenant?.vat_number">{{ labels.vat_number }}: <span class="font-mono">{{ invoice.tenant?.vat_number }}</span></p>
                        <p v-if="invoice.center?.phone">{{ labels.phone || 'الهاتف' }}: <span class="font-mono">{{ invoice.center?.phone }}</span></p>
                        <p v-if="invoice.center?.email">{{ invoice.center?.email }}</p>
                    </div>
                </div>
            </div>

            <!-- Invoice Title & QR -->
            <div class="text-start space-y-4">
                <div class="bg-slate-900 text-white px-6 py-2 rounded-xl text-center">
                    <h2 class="text-xl font-black uppercase tracking-widest">{{ labels.document_title || 'فاتورة ضريبية' }}</h2>
                    <p class="text-sm font-mono opacity-80">{{ invoice.invoice_number }}</p>
                </div>
                <div v-if="invoice.zatca_qr_tlv" class="flex justify-start">
                    <img :src="generateQRCode(invoice.zatca_qr_tlv)" alt="QR" class="w-24 h-24 border border-gray-100 p-1 rounded-lg" />
                </div>
            </div>
        </div>

        <!-- Info Grid -->
        <div class="grid grid-cols-2 gap-12 mb-10">
            <!-- Customer Section -->
            <div class="space-y-4 p-6 bg-slate-50 rounded-3xl border border-slate-100">
                <h3 class="text-[10px] font-black text-slate-400 uppercase tracking-widest border-b border-slate-200 pb-2">{{ labels.customer }}</h3>
                <div class="space-y-1">
                    <p class="text-lg font-black text-slate-900">{{ invoice.customer_name_snapshot || invoice.customer?.name }}</p>
                    <p v-if="invoice.customer?.phone" class="text-sm text-slate-500 font-mono">{{ invoice.customer?.phone }}</p>
                    <p v-if="invoice.customer_vat_snapshot" class="text-xs text-slate-400 font-bold mt-2 uppercase">VAT: <span class="font-mono text-slate-600">{{ invoice.customer_vat_snapshot }}</span></p>
                </div>
            </div>

            <!-- Invoice Meta Section -->
            <div class="space-y-4 p-6 bg-slate-50 rounded-3xl border border-slate-100">
                <h3 class="text-[10px] font-black text-slate-400 uppercase tracking-widest border-b border-slate-200 pb-2">تفاصيل الفاتورة</h3>
                <div class="grid grid-cols-2 gap-y-3 text-sm font-bold">
                    <span class="text-slate-400">{{ labels.date }}:</span>
                    <span class="text-slate-900 text-end font-mono">{{ formatDate(invoice.issue_date) }}</span>
                    
                    <span class="text-slate-400">{{ labels.supply_date || 'تاريخ التوريد' }}:</span>
                    <span class="text-slate-900 text-end font-mono">{{ formatDate(invoice.supply_date) }}</span>
                    
                    <span class="text-slate-400">حالة الدفع:</span>
                    <span class="text-end" :class="invoice.payment_status === 'paid' ? 'text-emerald-600' : 'text-red-600'">{{ paymentStatusLabel }}</span>
                </div>
            </div>
        </div>

        <!-- Vehicle/Related Info -->
        <div v-if="invoice.work_order?.vehicle" class="mb-10 p-6 border-2 border-slate-100 rounded-3xl flex items-center justify-between">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 rounded-2xl bg-blue-50 text-blue-600 flex items-center justify-center">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0"/></svg>
                </div>
                <div>
                    <p class="text-xs text-slate-400 font-black uppercase tracking-widest mb-0.5">{{ labels.vehicle }}</p>
                    <p class="text-base font-black text-slate-900">{{ invoice.work_order.vehicle.make }} {{ invoice.work_order.vehicle.model }} ({{ invoice.work_order.vehicle.year }})</p>
                </div>
            </div>
            <div class="text-end">
                <p class="text-[10px] text-slate-400 font-black uppercase tracking-widest mb-0.5">{{ labels.plate }}</p>
                <p class="text-lg font-black text-slate-900 font-mono tracking-tighter">{{ invoice.work_order.vehicle.plate_number }}</p>
            </div>
        </div>

        <!-- Table Section -->
        <div class="mb-10">
            <table class="w-full border-collapse">
                <thead>
                    <tr class="bg-slate-900 text-white">
                        <th class="px-6 py-4 text-start text-xs font-black uppercase tracking-widest rounded-s-2xl">{{ labels.description }}</th>
                        <th class="px-6 py-4 text-center text-xs font-black uppercase tracking-widest w-24">{{ labels.qty }}</th>
                        <th class="px-6 py-4 text-end text-xs font-black uppercase tracking-widest w-32">{{ labels.unit_price }}</th>
                        <th class="px-6 py-4 text-end text-xs font-black uppercase tracking-widest w-32 rounded-e-2xl">{{ labels.total }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <tr v-for="line in invoice.lines" :key="line.id" class="text-slate-700">
                        <td class="px-6 py-5">
                            <p class="font-bold text-slate-900">{{ line.description }}</p>
                            <p v-if="line.part?.sku" class="text-[10px] text-slate-400 font-mono mt-0.5">SKU: {{ line.part.sku }}</p>
                        </td>
                        <td class="px-6 py-5 text-center font-black font-mono">{{ toEnglish(line.qty) }}</td>
                        <td class="px-6 py-5 text-end font-black font-mono">{{ formatCurrency(line.unit_price) }}</td>
                        <td class="px-6 py-5 text-end font-black font-mono text-slate-900">{{ formatCurrency(line.line_total_incl_tax) }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Totals & Payment Breakdown -->
        <div class="flex justify-between items-start gap-12">
            <!-- Left: Notes & Terms -->
            <div class="flex-1 space-y-6">
                <div v-if="invoice.notes" class="p-6 bg-slate-50 rounded-3xl border border-slate-100">
                    <h3 class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">{{ labels.notes || 'ملاحظات' }}</h3>
                    <p class="text-sm text-slate-600 leading-relaxed italic">{{ invoice.notes }}</p>
                </div>
                <div v-if="page.props.tenant?.invoice_terms" class="p-6 border border-slate-100 rounded-3xl">
                    <h3 class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">الشروط والأحكام</h3>
                    <p class="text-[10px] text-slate-500 leading-tight whitespace-pre-wrap">{{ page.props.tenant.invoice_terms }}</p>
                </div>
            </div>

            <!-- Right: Totals -->
            <div class="w-80 space-y-4">
                <div class="p-6 bg-slate-50 rounded-3xl border border-slate-100 space-y-3">
                    <div class="flex justify-between items-center text-sm font-bold">
                        <span class="text-slate-400">{{ labels.subtotal }}:</span>
                        <span class="text-slate-900 font-mono">{{ formatCurrency(invoice.total_excl_tax) }}</span>
                    </div>
                    <div class="flex justify-between items-center text-sm font-bold">
                        <span class="text-slate-400">{{ labels.tax }} ({{ invoice.tax_rate_snapshot }}%):</span>
                        <span class="text-slate-900 font-mono">{{ formatCurrency(invoice.total_tax) }}</span>
                    </div>
                    <div class="pt-3 border-t border-slate-200 flex justify-between items-center">
                        <span class="text-base font-black text-slate-900">{{ labels.grand_total }}:</span>
                        <span class="text-2xl font-black text-slate-900 font-mono">{{ formatCurrency(invoice.total_incl_tax) }}</span>
                    </div>
                </div>

                <!-- Payment Status Badge -->
                <div class="p-4 rounded-2xl border flex justify-between items-center px-6" :class="invoice.balance > 0 ? 'bg-red-50 border-red-100' : 'bg-emerald-50 border-emerald-100'">
                    <span class="text-xs font-black uppercase tracking-widest" :class="invoice.balance > 0 ? 'text-red-600' : 'text-emerald-600'">
                        {{ invoice.balance > 0 ? labels.balance || 'المتبقي' : labels.paid || 'مدفوع بالكامل' }}
                    </span>
                    <span class="text-lg font-black font-mono" :class="invoice.balance > 0 ? 'text-red-700' : 'text-emerald-700'">
                        {{ formatCurrency(invoice.balance) }}
                    </span>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="mt-auto pt-10 text-center border-t border-slate-100 mt-12 pb-6">
            <p class="text-sm font-black text-slate-900 mb-1">{{ page.props.tenant?.print_settings?.footer_text || template.footer_text || 'شكراً لتعاملكم معنا' }}</p>
            <p class="text-[10px] text-slate-400 tracking-[0.2em] uppercase">{{ page.props.tenant?.name }} - {{ labels.thank_you || 'Thank You' }}</p>
        </div>

        <!-- Print UI Overlay (Hidden during print) -->
        <div class="fixed bottom-8 left-8 flex items-center gap-4 print:hidden">
            <button @click="window.history.back()" class="px-6 py-3 bg-white text-slate-900 rounded-2xl shadow-xl font-black text-sm border border-slate-200 hover:bg-slate-50 transition-all flex items-center gap-2">
                <svg class="w-4 h-4 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                رجوع
            </button>
            <button @click="printPage" class="px-8 py-4 bg-slate-900 text-white rounded-2xl shadow-2xl font-black text-sm hover:scale-105 active:scale-95 transition-all flex items-center gap-3">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                </svg>
                {{ labels.print || 'طباعة الفاتورة' }}
            </button>
        </div>
    </div>
</template>
<script setup>
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

const page = usePage();

const props = defineProps({
    invoice: Object,
    template: Object,
    labels: Object,
});

const balance = computed(() => (props.invoice.total_incl_tax || 0) - (props.invoice.total_paid || 0));

const paymentStatusLabel = computed(() => {
    switch (props.invoice.payment_status) {
        case 'paid': return 'مدفوعة بالكامل';
        case 'partial': return 'مدفوعة جزئياً';
        case 'unpaid': return 'غير مدفوعة';
        default: return props.invoice.payment_status;
    }
});

const formatDate = (date) => {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('ar-SA', { numberingSystem: 'latn' });
};

const toEnglish = (val) => {
    return val; 
};

const formatCurrency = (amount) => {
    return new Intl.NumberFormat('en-GB', {
        style: 'currency',
        currency: 'SAR',
        numberingSystem: 'latn'
    }).format(amount || 0);
};

const generateQRCode = (base64Data) => {
    return `https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=${encodeURIComponent(base64Data)}`;
};

const printPage = () => {
    window.print();
};
</script>

<style>
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;700;900&display=swap');

@media print {
    @page {
        size: A4;
        margin: 0;
    }
    
    body {
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
        background: white;
    }
    
    .print-page {
        padding: 1.5cm !important;
        min-height: auto;
        box-shadow: none !important;
    }

    .print-hidden {
        display: none !important;
    }
}

.print-page {
    max-width: 21cm;
    margin: 0 auto;
    background: white;
    box-shadow: 0 0 50px rgba(0,0,0,0.05);
    padding: 1.5cm;
}

/* Ensure nice typography */
h1, h2, h3, p, span {
    letter-spacing: -0.01em;
}
</style>

