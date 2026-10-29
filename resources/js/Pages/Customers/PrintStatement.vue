<template>
    <div
        class="print-container print-statement-page bg-white min-h-screen text-gray-900 p-8 print:p-0 print:m-0"
        dir="rtl"
    >
        <Head
            :title="
                ($t('customers.statement.title') || 'كشف حساب عميل') +
                ' - ' +
                (customer?.name || '')
            "
        />

        <!-- Floating Screen Controls (Hidden during print) -->
        <div
            class="print:hidden mb-6 flex justify-between items-center bg-gray-50 p-4 rounded-2xl border border-gray-200 shadow-sm"
        >
            <div class="flex items-center gap-3">
                <button
                    type="button"
                    @click="handlePrint"
                    class="px-5 py-2.5 bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 text-white rounded-xl font-bold flex items-center gap-2 shadow-md transition-all cursor-pointer"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"
                        />
                    </svg>
                    <span>{{ $t('common.print') || $t('common.print') }}</span>
                </button>
            </div>
            <button
                type="button"
                @click="handleClose"
                class="px-4 py-2 bg-white text-gray-700 border border-gray-200 rounded-xl hover:bg-gray-100 font-bold transition-all cursor-pointer"
            >
                {{ $t('common.close') || $t('common.close') }}
            </button>
        </div>

        <!-- Official 3-Column Header (AUDIT_STANDARDS.md Section 5) -->
        <div class="statement-sheet max-w-5xl mx-auto">
            <div class="border-b-2 border-gray-900 pb-4 mb-4">
                <div class="grid grid-cols-3 items-center text-xs">
                    <!-- Column 1: Workshop / Center Info -->
                    <div class="text-start space-y-1">
                        <h1 class="text-base font-black text-black">
                            {{ companyInfo.name }}
                        </h1>
                        <p v-if="companyInfo.branch" class="font-bold text-gray-700">
                            {{ $t('common.branch_label') }} {{ companyInfo.branch }}
                        </p>
                        <p v-if="companyInfo.phone" class="text-gray-600">
                            {{ $t('common.phone_label') }}
                            <span class="font-mono" dir="ltr">{{ companyInfo.phone }}</span>
                        </p>
                        <p v-if="companyInfo.vat_number" class="text-gray-600">
                            {{ $t('common.tax_number_label') }}
                            <span class="font-mono font-bold">{{ companyInfo.vat_number }}</span>
                        </p>
                        <p v-if="companyInfo.cr_number" class="text-gray-600">
                            {{ $t('common.commercial_registration_label') }}
                            <span class="font-mono">{{ companyInfo.cr_number }}</span>
                        </p>
                    </div>

                    <!-- Column 2 (Center): Logo & Statement Title -->
                    <div class="text-center flex flex-col items-center justify-center">
                        <div
                            v-if="companyInfo.logo_url"
                            class="h-16 w-32 mb-1 flex items-center justify-center"
                        >
                            <img
                                :src="companyInfo.logo_url"
                                alt="Logo"
                                class="max-h-16 max-w-full object-contain"
                            />
                        </div>
                        <div v-else class="text-center py-1">
                            <span class="text-base font-black tracking-wide text-black">
                                {{ companyInfo.name }}
                            </span>
                        </div>
                        <h2 class="text-sm font-black text-black uppercase tracking-wider">
                            {{ $t('customers.statement.title') }}
                        </h2>
                    </div>

                    <!-- Column 3: Customer Info & Print Date -->
                    <div class="text-end space-y-1">
                        <p class="font-black text-black text-sm">{{ customer?.name }}</p>
                        <p class="font-mono" dir="ltr">{{ customer?.phone }}</p>
                        <p v-if="customer?.tax_number" class="text-gray-600">
                            {{ $t('common.tax_number_label') }}
                            <span class="font-mono">{{ customer?.tax_number }}</span>
                        </p>
                        <p class="text-gray-500">
                            {{ $t('common.print_date_label') }}
                            <span class="font-mono">{{ printDateFormatted }}</span>
                        </p>
                    </div>
                </div>

                <!-- Financial Summary Banner -->
                <div
                    class="grid grid-cols-3 gap-2 mt-4 p-2.5 bg-gray-100 rounded border border-gray-300 text-center text-xs"
                >
                    <div>
                        <span class="text-gray-600 block">
                            {{ $t('customers.statement.total_invoices') }}
                        </span>
                        <strong class="font-mono text-sm text-gray-900">
                            {{ formatMoney(financialSummary?.total_invoiced) }}
                        </strong>
                    </div>
                    <div>
                        <span class="text-gray-600 block">
                            {{ $t('customers.statement.total_paid') }}
                        </span>
                        <strong class="font-mono text-sm text-emerald-700">
                            {{ formatMoney(financialSummary?.total_paid) }}
                        </strong>
                    </div>
                    <div>
                        <span class="text-gray-600 block">
                            {{ $t('customers.statement.remaining_balance') }}
                        </span>
                        <strong class="font-mono text-sm text-rose-700">
                            {{ formatMoney(financialSummary?.total_due) }}
                        </strong>
                    </div>
                </div>
            </div>

            <!-- Detailed Ledger Table -->
            <table class="w-full text-xs text-center border-collapse">
                <thead>
                    <tr class="bg-gray-100 border-b-2 border-gray-800 text-gray-800 font-bold">
                        <th class="py-2 px-2 text-center w-8">#</th>
                        <th class="py-2 px-3 text-center">{{ $t('common.date') }}</th>
                        <th class="py-2 px-3 text-center">{{ $t('customers.statement.type') }}</th>
                        <th class="py-2 px-3 text-center">
                            {{ $t('common.reference_document_number') }}
                        </th>
                        <th class="py-2 px-3 text-center">
                            {{ $t('customers.statement.work_order_vehicle') }}
                        </th>
                        <th class="py-2 px-3 text-center">
                            {{ $t('customers.statement.debtor_invoices') }}
                        </th>
                        <th class="py-2 px-3 text-center">
                            {{ $t('customers.statement.credit') }}
                        </th>
                        <th class="py-2 px-3 text-center">
                            {{ $t('customers.statement.balance') }}
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <tr v-for="(entry, idx) in ledger" :key="entry.id" class="text-center">
                        <td class="py-2 px-2 font-mono text-gray-500 text-[11px]">
                            {{ idx + 1 }}
                        </td>
                        <td class="py-2 px-3 font-mono text-gray-600 text-[11px]">
                            {{ entry.date }}
                        </td>
                        <td class="py-2 px-3">
                            <span
                                v-if="entry.type === 'invoice'"
                                class="inline-block px-2 py-0.5 rounded text-[10px] font-bold bg-blue-50 text-blue-700 border border-blue-200"
                            >
                                {{ $t('invoices.invoice') }}
                            </span>
                            <span
                                v-else-if="entry.type === 'refund'"
                                class="inline-block px-2 py-0.5 rounded text-[10px] font-bold bg-rose-50 text-rose-700 border border-rose-200"
                            >
                                {{ $t('payments.types.refund') }}
                            </span>
                            <span
                                v-else
                                class="inline-block px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200"
                            >
                                {{ $t('print_settings.receipt') }}
                            </span>
                        </td>
                        <td class="py-2 px-3 font-mono font-bold text-gray-900">
                            {{ entry.reference }}
                        </td>
                        <td class="py-2 px-3 text-xs text-gray-600">
                            <span v-if="entry.work_order_number" class="font-mono text-indigo-700">
                                {{ entry.work_order_number }}
                            </span>
                            <span v-if="entry.vehicle_plate" class="text-gray-500 ms-1 font-mono">
                                ({{ entry.vehicle_plate }})
                            </span>
                            <span v-if="!entry.work_order_number && !entry.vehicle_plate">—</span>
                        </td>
                        <td class="py-2 px-3 font-mono text-gray-900">
                            {{ entry.debit > 0 ? formatMoney(entry.debit) : '—' }}
                        </td>
                        <td class="py-2 px-3 font-mono text-emerald-700">
                            {{ entry.credit > 0 ? formatMoney(entry.credit) : '—' }}
                        </td>
                        <td
                            class="py-2 px-3 font-mono font-black"
                            :class="entry.balance > 0 ? 'text-rose-700' : 'text-gray-900'"
                        >
                            {{ formatMoney(entry.balance) }}
                        </td>
                    </tr>

                    <tr v-if="!ledger || ledger.length === 0">
                        <td colspan="8" class="py-8 text-center text-gray-400 text-xs">
                            {{ $t('customers.statement.no_transactions') }}
                        </td>
                    </tr>
                </tbody>
            </table>

            <!-- Print Footer -->
            <div class="pt-6 mt-8 border-t border-gray-300 text-xs text-gray-600">
                <div class="flex items-center justify-between">
                    <div>
                        <p>
                            {{ $t('common.prepared_by_label') }}
                            <span class="font-bold text-black">
                                {{ $page.props.auth?.user?.name || 'Administrator' }}
                            </span>
                        </p>
                        <p>
                            {{ $t('common.print_date_time_label') }}
                            <span class="font-mono">{{ printDateFormatted }}</span>
                        </p>
                    </div>
                    <div class="text-center w-52 border-t border-dashed border-gray-400 pt-1">
                        <span class="text-[11px] font-bold text-black block">
                            {{ $t('reports.payments_report.official_seal') }}
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed, onMounted } from 'vue';
import { Head } from '@inertiajs/vue3';

const props = defineProps({
    customer: {
        type: Object,
        default: null,
    },
    financialSummary: {
        type: Object,
        default: () => ({
            total_invoiced: 0,
            total_paid: 0,
            total_due: 0,
        }),
    },
    ledger: {
        type: Array,
        default: () => [],
    },
    companyInfo: {
        type: Object,
        default: () => ({
            name: '',
            branch: '',
            vat_number: '',
            cr_number: '',
            logo_url: '',
            phone: '',
        }),
    },
});

const formatMoney = (val) => {
    const num = Number(val) || 0;
    return (
        new Intl.NumberFormat('en-US', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        }).format(num) + ' SAR'
    );
};

const printDateFormatted = computed(() => {
    return new Date().toLocaleString('en-GB', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit',
    });
});

const handlePrint = () => {
    window.print();
};

const handleClose = () => {
    if (window.opener) {
        window.close();
    } else {
        window.history.back();
    }
};

onMounted(() => {
    // Automatically open print dialog after short render delay
    setTimeout(() => {
        window.print();
    }, 400);
});
</script>

<style>
@page {
    size: A4 portrait;
    margin: 10mm;
}

@media print {
    html,
    body {
        background-color: white !important;
        margin: 0 !important;
        padding: 0 !important;
        color: black !important;
        font-family: 'Noto Kufi Arabic', 'Tajawal', sans-serif !important;
        height: auto !important;
        overflow: visible !important;
        visibility: visible !important;
    }

    #app,
    .print-container,
    .print-container * {
        visibility: visible !important;
    }

    .print-statement-page {
        padding: 0 !important;
        margin: 0 !important;
        min-height: auto !important;
        background: white !important;
    }

    .print\:hidden {
        display: none !important;
        visibility: hidden !important;
    }

    table {
        width: 100% !important;
        border-collapse: collapse !important;
        font-size: 11px !important;
    }

    th,
    td {
        border: 1px solid #9ca3af !important;
        padding: 5px 6px !important;
        text-align: center !important;
    }

    th {
        background-color: #f3f4f6 !important;
        color: #111827 !important;
        font-weight: bold !important;
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
    }

    tr {
        page-break-inside: avoid !important;
        break-inside: avoid !important;
    }
}
</style>
