<template>
    <Head
        :title="tab === 'returns' ? $t('invoices.sales.tab_returns') : $t('invoices.sales.title')"
    />

    <StandardReportLayout
        :title="tab === 'returns' ? $t('invoices.sales.tab_returns') : $t('invoices.sales.title')"
        :total-count="toEnglish(rows.length)"
        :total-count-label="
            tab === 'returns' ? $t('invoices.sales.tab_returns') : $t('invoices.sales.tab_invoices')
        "
        :custom-company="companyInfo"
        :on-print="handlePrint"
        :on-close="handleBack"
    >
        <table class="w-full text-xs text-center border-collapse">
            <thead>
                <tr class="bg-gray-100 border-b-2 border-gray-900">
                    <th class="py-2.5 px-2 border border-gray-400 font-black">#</th>
                    <th class="py-2.5 px-2 border border-gray-400 font-black">
                        {{
                            tab === 'returns'
                                ? $t('invoices.return_code')
                                : $t('invoices.invoice_number')
                        }}
                    </th>
                    <th
                        v-if="tab === 'returns'"
                        class="py-2.5 px-2 border border-gray-400 font-black"
                    >
                        {{ $t('invoices.original_invoice') }}
                    </th>
                    <th class="py-2.5 px-2 border border-gray-400 font-black">
                        {{ $t('invoices.issue_date') }}
                    </th>
                    <th class="py-2.5 px-2 border border-gray-400 font-black">
                        {{ $t('common.customer') }}
                    </th>
                    <th class="py-2.5 px-2 border border-gray-400 font-black">
                        {{ $t('invoices.subtotal') }}
                    </th>
                    <th class="py-2.5 px-2 border border-gray-400 font-black">
                        {{ $t('common.vat') }}
                    </th>
                    <th class="py-2.5 px-2 border border-gray-400 font-black">
                        {{
                            tab === 'returns'
                                ? $t('invoices.credit_note.total_credit_note')
                                : $t('invoices.grand_total')
                        }}
                    </th>
                    <th class="py-2.5 px-2 border border-gray-400 font-black">
                        {{ $t('invoices.balance') }}
                    </th>
                    <th class="py-2.5 px-2 border border-gray-400 font-black">
                        {{ $t('common.status') }}
                    </th>
                </tr>
            </thead>
            <tbody>
                <tr v-if="rows.length === 0">
                    <td
                        :colspan="tab === 'returns' ? 10 : 9"
                        class="py-8 text-center text-gray-500 font-bold border border-gray-300"
                    >
                        {{ $t('invoices.empty') }}
                    </td>
                </tr>
                <tr
                    v-for="(row, index) in rows"
                    :key="row.id"
                    class="border-b border-gray-300 hover:bg-gray-50"
                >
                    <td class="py-2 px-2 border border-gray-300 font-mono text-gray-600">
                        {{ toEnglish(index + 1) }}
                    </td>
                    <td class="py-2 px-2 border border-gray-300 font-mono font-bold" dir="ltr">
                        #{{ row.invoice_number }}
                    </td>
                    <td
                        v-if="tab === 'returns'"
                        class="py-2 px-2 border border-gray-300 font-mono"
                        dir="ltr"
                    >
                        {{ row.original_invoice ? `#${row.original_invoice.invoice_number}` : '—' }}
                    </td>
                    <td class="py-2 px-2 border border-gray-300 font-mono" dir="ltr">
                        {{ formatDate(row.issue_date) }}
                    </td>
                    <td class="py-2 px-2 border border-gray-300 font-bold text-start">
                        {{ row.customer_name_snapshot || row.customer?.name || '—' }}
                    </td>
                    <td class="py-2 px-2 border border-gray-300 font-mono" dir="ltr">
                        {{ formatMoney(row.total_excl_tax || 0) }}
                    </td>
                    <td class="py-2 px-2 border border-gray-300 font-mono" dir="ltr">
                        {{ formatMoney(row.total_tax || 0) }}
                    </td>
                    <td class="py-2 px-2 border border-gray-300 font-mono font-bold" dir="ltr">
                        {{ formatMoney(row.total_incl_tax || 0) }}
                    </td>
                    <td
                        class="py-2 px-2 border border-gray-300 font-mono font-bold"
                        :class="Number(row.balance) > 0 ? 'text-red-700' : 'text-gray-900'"
                        dir="ltr"
                    >
                        {{ formatMoney(row.balance || 0) }}
                    </td>
                    <td class="py-2 px-2 border border-gray-300">
                        {{ row.payment_status ? $t(`invoices.status.${row.payment_status}`) : '—' }}
                    </td>
                </tr>
            </tbody>
        </table>
    </StandardReportLayout>
</template>

<script setup>
import { Head, router } from '@inertiajs/vue3';
import { printDocument, usePrint } from '@/Composables/usePrint';
import { useNumberFormat } from '@/Composables/useNumberFormat';
import StandardReportLayout from '@/Components/Print/StandardReportLayout.vue';

const { formatCurrency: formatMoney } = usePrint();
const { toEnglish } = useNumberFormat();

defineProps({
    rows: {
        type: Array,
        default: () => [],
    },
    tab: {
        type: String,
        default: 'invoices',
    },
    companyInfo: {
        type: Object,
        default: () => ({
            name: '',
            vat_number: '',
            cr_number: '',
            logo_path: '',
            center_name: '',
            phone: '',
        }),
    },
});

function formatDate(date) {
    if (!date) return '—';
    return new Date(date).toLocaleDateString('en-GB', { numberingSystem: 'latn' });
}

function handlePrint() {
    printDocument();
}

function handleBack() {
    if (window.opener) {
        window.close();
        setTimeout(() => {
            if (!window.closed) {
                router.visit(route('app.invoices.sales.index'));
            }
        }, 150);
        return;
    }
    if (window.history.length > 1) {
        window.history.back();
    } else {
        window.close();
        setTimeout(() => {
            router.visit(route('app.invoices.sales.index'));
        }, 150);
    }
}
</script>
