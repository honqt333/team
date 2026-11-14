<template>
    <Head :title="$t('dealers.title')" />

    <StandardReportLayout
        :title="$t('dealers.title')"
        :total-count="toEnglish(dealers.length)"
        :total-count-label="$t('dealers.total_count')"
        :custom-company="companyInfo"
        :on-print="handlePrint"
        :on-close="handleBack"
    >
        <table class="w-full text-xs text-center border-collapse">
            <thead>
                <tr class="bg-gray-100 border-b-2 border-gray-900">
                    <th class="py-2.5 px-2 border border-gray-400 font-black">#</th>
                    <th class="py-2.5 px-2 border border-gray-400 font-black">
                        {{ $t('dealers.columns.name') }}
                    </th>
                    <th class="py-2.5 px-2 border border-gray-400 font-black">
                        {{ $t('dealers.columns.contact') }}
                    </th>
                    <th class="py-2.5 px-2 border border-gray-400 font-black">
                        {{ $t('dealers.columns.tax_number') }}
                    </th>
                    <th class="py-2.5 px-2 border border-gray-400 font-black">
                        {{ $t('dealers.columns.vehicles_count') }}
                    </th>
                    <th class="py-2.5 px-2 border border-gray-400 font-black">
                        {{ $t('dealers.columns.work_orders_count') }}
                    </th>
                    <th class="py-2.5 px-2 border border-gray-400 font-black">
                        {{ $t('dealers.columns.outstanding_balance') }}
                    </th>
                    <th class="py-2.5 px-2 border border-gray-400 font-black">
                        {{ $t('dealers.columns.status') }}
                    </th>
                </tr>
            </thead>
            <tbody>
                <tr v-if="dealers.length === 0">
                    <td
                        colspan="8"
                        class="py-8 text-center text-gray-500 font-bold border border-gray-300"
                    >
                        {{ $t('dealers.empty_title') }}
                    </td>
                </tr>
                <tr
                    v-for="(dealer, index) in dealers"
                    :key="dealer.id"
                    class="border-b border-gray-300 hover:bg-gray-50"
                >
                    <td class="py-2 px-2 border border-gray-300 font-mono text-gray-600">
                        {{ toEnglish(index + 1) }}
                    </td>
                    <td class="py-2 px-2 border border-gray-300 font-bold text-start">
                        <div>{{ dealer.name }}</div>
                        <div
                            v-if="dealer.name_en"
                            class="text-[11px] font-normal text-gray-500"
                            dir="ltr"
                        >
                            {{ dealer.name_en }}
                        </div>
                    </td>
                    <td class="py-2 px-2 border border-gray-300">
                        <div v-if="dealer.contact_name" class="font-semibold">
                            {{ dealer.contact_name }}
                        </div>
                        <div v-if="dealer.phone" class="font-mono text-gray-700" dir="ltr">
                            {{ dealer.phone }}
                        </div>
                        <span v-if="!dealer.contact_name && !dealer.phone">—</span>
                    </td>
                    <td class="py-2 px-2 border border-gray-300 font-mono text-gray-700" dir="ltr">
                        {{ dealer.tax_number || '—' }}
                    </td>
                    <td class="py-2 px-2 border border-gray-300 font-mono">
                        {{ toEnglish(dealer.vehicles_count ?? 0) }}
                    </td>
                    <td class="py-2 px-2 border border-gray-300 font-mono">
                        {{ toEnglish(dealer.work_orders_count ?? 0) }}
                    </td>
                    <td
                        class="py-2 px-2 border border-gray-300 font-mono font-bold"
                        :class="
                            Number(dealer.outstanding_balance) > 0
                                ? 'text-red-700'
                                : 'text-gray-900'
                        "
                        dir="ltr"
                    >
                        {{ formatMoney(dealer.outstanding_balance || 0) }}
                    </td>
                    <td class="py-2 px-2 border border-gray-300">
                        {{
                            dealer.is_active
                                ? $t('dealers.status_active')
                                : $t('dealers.status_inactive')
                        }}
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
    dealers: {
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
                router.visit(route('app.dealers.index'));
            }
        }, 150);
        return;
    }
    if (window.history.length > 1) {
        window.history.back();
    } else {
        window.close();
        setTimeout(() => {
            router.visit(route('app.dealers.index'));
        }, 150);
    }
}
</script>
