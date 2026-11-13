<template>
    <Head :title="$t('customers.print_title')" />

    <StandardReportLayout
        :title="$t('customers.print.title')"
        :total-count="customers.length"
        :total-count-label="$t('customers.print.total_customers')"
        :custom-company="companyInfo"
        :on-print="handlePrint"
        :on-close="handleBack"
    >
        <!-- Table -->
        <table class="w-full text-xs text-center border-collapse">
            <thead>
                <tr class="bg-gray-100 border-b-2 border-gray-900">
                    <th class="py-2.5 px-2 border border-gray-400 font-black">#</th>
                    <th class="py-2.5 px-2 border border-gray-400 font-black">
                        {{ $t('customers.print.customer_code') }}
                    </th>
                    <th class="py-2.5 px-2 border border-gray-400 font-black">
                        {{ $t('customers.print.name') }}
                    </th>
                    <th class="py-2.5 px-2 border border-gray-400 font-black">
                        {{ $t('customers.print.phone') }}
                    </th>
                    <th class="py-2.5 px-2 border border-gray-400 font-black">
                        {{ $t('customers.print.type') }}
                    </th>
                    <th class="py-2.5 px-2 border border-gray-400 font-black">
                        {{ $t('customers.print.vehicles_count') }}
                    </th>
                    <th class="py-2.5 px-2 border border-gray-400 font-black">
                        {{ $t('customers.print.invoices_count') }}
                    </th>
                    <th class="py-2.5 px-2 border border-gray-400 font-black">
                        {{ $t('customers.print.balance') }}
                    </th>
                </tr>
            </thead>
            <tbody>
                <tr v-if="customers.length === 0">
                    <td
                        colspan="8"
                        class="py-8 text-center text-gray-500 font-bold border border-gray-300"
                    >
                        {{ $t('customers.print.no_customers') }}
                    </td>
                </tr>
                <tr v-if="customers.length === 0">
                    <td colspan="8" class="py-6 px-4 border border-gray-300 text-gray-500">
                        {{ $t('common.no_data') }}
                    </td>
                </tr>
                <tr
                    v-for="(customer, index) in customers"
                    :key="customer.id"
                    class="border-b border-gray-300 hover:bg-gray-50"
                >
                    <td class="py-2 px-2 border border-gray-300 font-mono text-gray-600">
                        {{ index + 1 }}
                    </td>
                    <td class="py-2 px-2 border border-gray-300 font-mono font-bold">
                        {{ customer.code || '—' }}
                    </td>
                    <td class="py-2 px-2 border border-gray-300 font-bold text-start">
                        {{ customer.name }}
                    </td>
                    <td class="py-2 px-2 border border-gray-300 font-mono text-gray-700" dir="ltr">
                        {{ customer.phone || '—' }}
                    </td>
                    <td class="py-2 px-2 border border-gray-300">
                        {{
                            customer.customer_type
                                ? $t(`customers.type.${customer.customer_type}`)
                                : '—'
                        }}
                    </td>
                    <td class="py-2 px-2 border border-gray-300 font-mono">
                        {{ customer.vehicles_count || 0 }}
                    </td>
                    <td class="py-2 px-2 border border-gray-300 font-mono">
                        {{ customer.invoices_count || 0 }}
                    </td>
                    <td
                        class="py-2 px-2 border border-gray-300 font-mono font-bold"
                        :class="customer.balance > 0 ? 'text-red-700' : 'text-gray-900'"
                    >
                        {{ formatMoney(customer.balance || 0) }}
                    </td>
                </tr>
            </tbody>
        </table>
    </StandardReportLayout>
</template>

<script setup>
import { printDocument, usePrint } from '@/Composables/usePrint';
import { onMounted } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import StandardReportLayout from '@/Components/Print/StandardReportLayout.vue';

const { formatCurrency: formatMoney } = usePrint();

const props = defineProps({
    customers: {
        type: Array,
        default: () => [],
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

function handlePrint() {
    printDocument();
}

function handleBack() {
    if (window.opener) {
        window.close();
        setTimeout(() => {
            if (!window.closed) {
                router.visit(route('customers.index'));
            }
        }, 150);
        return;
    }
    if (window.history.length > 1) {
        window.history.back();
    } else {
        window.close();
        setTimeout(() => {
            router.visit(route('customers.index'));
        }, 150);
    }
}

// formatMoney provided by usePrint()

onMounted(() => {
    // Optional print trigger after render if requested
});
</script>
