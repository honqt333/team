<template>
    <BaseModal :show="show" @close="$emit('close')" size="4xl" scroll-entire>
        <template #title>
            <div class="flex items-center justify-between w-full pe-4">
                <div class="flex items-center gap-3">
                    <div
                        class="w-10 h-10 rounded-xl bg-gradient-to-br from-indigo-600 to-purple-600 text-white flex items-center justify-center font-bold shadow-md shadow-indigo-500/20"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                            />
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-base font-black text-gray-900 dark:text-white">
                            {{ $t('customers.statement.title') || 'كشف حساب مالي للعميل' }}
                        </h2>
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            {{ customer?.name }} ({{ customer?.phone }})
                        </p>
                    </div>
                </div>

                <button
                    type="button"
                    @click="handlePrint"
                    class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-gradient-to-r from-indigo-600 to-purple-600 text-white text-xs font-bold shadow-md shadow-indigo-500/20 hover:from-indigo-700 hover:to-purple-700 transition-all hover:-translate-y-0.5"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"
                        />
                    </svg>
                    <span>{{ $t('common.print') }}</span>
                </button>
            </div>
        </template>

        <div class="space-y-6 statement-content">
            <!-- Screen Summary Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 print:hidden">
                <div
                    class="p-4 rounded-2xl bg-blue-50/50 dark:bg-blue-900/20 border border-blue-100 dark:border-blue-800/40"
                >
                    <span
                        class="text-[11px] font-bold text-blue-600 dark:text-blue-400 uppercase tracking-wider block"
                    >
                        {{
                            $t('customers.statement.total_invoiced') || 'إجمالي الفواتير والمبيعات'
                        }}
                    </span>
                    <span
                        class="text-xl font-black text-gray-900 dark:text-white font-mono mt-1 block"
                    >
                        {{ formatMoney(financialSummary?.total_invoiced) }}
                    </span>
                </div>

                <div
                    class="p-4 rounded-2xl bg-emerald-50/50 dark:bg-emerald-900/20 border border-emerald-100 dark:border-emerald-800/40"
                >
                    <span
                        class="text-[11px] font-bold text-emerald-600 dark:text-emerald-400 uppercase tracking-wider block"
                    >
                        {{ $t('customers.statement.total_paid') || 'إجمالي المدفوعات المسددة' }}
                    </span>
                    <span
                        class="text-xl font-black text-emerald-600 dark:text-emerald-400 font-mono mt-1 block"
                    >
                        {{ formatMoney(financialSummary?.total_paid) }}
                    </span>
                </div>

                <div
                    class="p-4 rounded-2xl border"
                    :class="
                        financialSummary?.total_due > 0
                            ? 'bg-rose-50/50 dark:bg-rose-900/20 border-rose-100 dark:border-rose-800/40'
                            : 'bg-gray-50/50 dark:bg-gray-900/20 border-gray-100 dark:border-gray-800'
                    "
                >
                    <span
                        class="text-[11px] font-bold uppercase tracking-wider block"
                        :class="
                            financialSummary?.total_due > 0
                                ? 'text-rose-600 dark:text-rose-400'
                                : 'text-gray-500'
                        "
                    >
                        {{ $t('customers.statement.total_due') || 'الرصيد المتبقي المستحق' }}
                    </span>
                    <span
                        class="text-xl font-black font-mono mt-1 block"
                        :class="
                            financialSummary?.total_due > 0
                                ? 'text-rose-600 dark:text-rose-400'
                                : 'text-gray-900 dark:text-white'
                        "
                    >
                        {{ formatMoney(financialSummary?.total_due) }}
                    </span>
                </div>
            </div>

            <!-- AUDIT_STANDARDS.md Section 5: Standard Official Print Header -->
            <div class="print-header-area hidden print:block border-b-2 border-gray-800 pb-4 mb-4">
                <div class="grid grid-cols-3 items-center text-xs">
                    <!-- Column 1: Workshop / Center Info -->
                    <div class="text-start space-y-1">
                        <h2 class="text-base font-black text-black">
                            {{
                                $page.props.tenant?.trade_name ||
                                $page.props.tenant?.name ||
                                'Carag'
                            }}
                        </h2>
                        <p v-if="$page.props.center?.name" class="font-bold text-gray-700">
                            الفرع: {{ $page.props.center?.name }}
                        </p>
                        <p v-if="$page.props.tenant?.tax_number" class="text-gray-600">
                            الرقم الضريبي:
                            <span class="font-mono font-bold">
                                {{ $page.props.tenant?.tax_number }}
                            </span>
                        </p>
                        <p v-if="$page.props.tenant?.cr_number" class="text-gray-600">
                            السجل التجاري:
                            <span class="font-mono">{{ $page.props.tenant?.cr_number }}</span>
                        </p>
                    </div>

                    <!-- Column 2 (Center): Logo & Statement Title -->
                    <div class="text-center flex flex-col items-center justify-center">
                        <img
                            v-if="$page.props.tenant?.logo_path"
                            :src="'/storage/' + $page.props.tenant?.logo_path"
                            alt="Logo"
                            class="h-14 w-auto object-contain mx-auto mb-1"
                        />
                        <div v-else class="text-center">
                            <span class="text-lg font-black tracking-wider text-black">CARAG</span>
                        </div>
                        <span class="text-sm font-black text-black uppercase tracking-wider">
                            كشف حساب عميل (Statement of Account)
                        </span>
                    </div>

                    <!-- Column 3: Customer Info & Print Date -->
                    <div class="text-end space-y-1">
                        <p class="font-black text-black text-sm">{{ customer?.name }}</p>
                        <p class="font-mono" dir="ltr">{{ customer?.phone }}</p>
                        <p v-if="customer?.tax_number" class="text-gray-600">
                            الرقم الضريبي:
                            <span class="font-mono">{{ customer?.tax_number }}</span>
                        </p>
                        <p class="text-gray-500">
                            تاريخ الطباعة:
                            <span class="font-mono">{{ printDateFormatted }}</span>
                        </p>
                    </div>
                </div>

                <!-- Print Financial Banner -->
                <div
                    class="grid grid-cols-3 gap-2 mt-4 p-2.5 bg-gray-100 rounded border border-gray-300 text-center text-xs"
                >
                    <div>
                        <span class="text-gray-600 block">إجمالي الفواتير:</span>
                        <strong class="font-mono">
                            {{ formatMoney(financialSummary?.total_invoiced) }}
                        </strong>
                    </div>
                    <div>
                        <span class="text-gray-600 block">إجمالي المسدد:</span>
                        <strong class="font-mono text-emerald-700">
                            {{ formatMoney(financialSummary?.total_paid) }}
                        </strong>
                    </div>
                    <div>
                        <span class="text-gray-600 block">الرصيد المتبقي المستحق:</span>
                        <strong class="font-mono text-red-700">
                            {{ formatMoney(financialSummary?.total_due) }}
                        </strong>
                    </div>
                </div>
            </div>

            <!-- Ledger Table -->
            <div class="rounded-2xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-xs text-gray-700 dark:text-gray-300">
                        <thead
                            class="bg-gray-50 dark:bg-gray-800 text-gray-500 dark:text-gray-400 font-bold border-b border-gray-200 dark:border-gray-700"
                        >
                            <tr>
                                <th class="px-3 py-3 text-center">#</th>
                                <th class="px-3 py-3 text-center">التاريخ</th>
                                <th class="px-3 py-3 text-center">نوع الحركة</th>
                                <th class="px-3 py-3 text-center">رقم المرجع / المستند</th>
                                <th class="px-3 py-3 text-center">أمر العمل / المركبة</th>
                                <th class="px-3 py-3 text-center">مدين (فواتير)</th>
                                <th class="px-3 py-3 text-center">دائن (سداد)</th>
                                <th class="px-3 py-3 text-center">الرصيد التراكمي</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800 font-medium">
                            <tr
                                v-for="(entry, idx) in ledger"
                                :key="entry.id"
                                class="hover:bg-gray-50/60 dark:hover:bg-gray-800/60 text-center"
                            >
                                <td class="px-3 py-2.5 font-mono text-gray-400 text-[11px]">
                                    {{ idx + 1 }}
                                </td>
                                <td class="px-3 py-2.5 font-mono text-gray-500 text-[11px]">
                                    {{ entry.date }}
                                </td>
                                <td class="px-3 py-2.5">
                                    <span
                                        v-if="entry.type === 'invoice'"
                                        class="inline-block px-2 py-0.5 rounded-md text-[10px] font-bold bg-blue-50 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300"
                                    >
                                        فاتورة
                                    </span>
                                    <span
                                        v-else-if="entry.type === 'refund'"
                                        class="inline-block px-2 py-0.5 rounded-md text-[10px] font-bold bg-rose-50 text-rose-700 dark:bg-rose-900/30 dark:text-rose-300"
                                    >
                                        استرجاع
                                    </span>
                                    <span
                                        v-else
                                        class="inline-block px-2 py-0.5 rounded-md text-[10px] font-bold bg-emerald-50 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300"
                                    >
                                        سند قبض
                                    </span>
                                </td>
                                <td
                                    class="px-3 py-2.5 font-mono font-bold text-gray-900 dark:text-white"
                                >
                                    {{ entry.reference }}
                                </td>
                                <td class="px-3 py-2.5 text-xs text-gray-500">
                                    <span
                                        v-if="entry.work_order_number"
                                        class="font-mono text-indigo-600 dark:text-indigo-400"
                                    >
                                        {{ entry.work_order_number }}
                                    </span>
                                    <span
                                        v-if="entry.vehicle_plate"
                                        class="text-gray-400 ms-1 font-mono"
                                    >
                                        ({{ entry.vehicle_plate }})
                                    </span>
                                    <span v-if="!entry.work_order_number && !entry.vehicle_plate">
                                        —
                                    </span>
                                </td>
                                <td class="px-3 py-2.5 font-mono text-gray-900 dark:text-white">
                                    {{ entry.debit > 0 ? formatMoney(entry.debit) : '—' }}
                                </td>
                                <td
                                    class="px-3 py-2.5 font-mono text-emerald-600 dark:text-emerald-400"
                                >
                                    {{ entry.credit > 0 ? formatMoney(entry.credit) : '—' }}
                                </td>
                                <td
                                    class="px-3 py-2.5 font-mono font-black"
                                    :class="
                                        entry.balance > 0
                                            ? 'text-rose-600 dark:text-rose-400'
                                            : 'text-gray-900 dark:text-white'
                                    "
                                >
                                    {{ formatMoney(entry.balance) }}
                                </td>
                            </tr>

                            <tr v-if="!ledger || ledger.length === 0">
                                <td colspan="8" class="px-4 py-8 text-center text-gray-400 text-xs">
                                    لا توجد حركات مالية مسجلة للعميل حتى الآن
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Print Footer -->
            <div
                class="print-footer-area hidden print:block pt-6 mt-8 border-t border-gray-300 text-xs text-gray-600"
            >
                <div class="flex items-center justify-between">
                    <div>
                        <p>
                            المُعد:
                            <span class="font-bold text-black">
                                {{ $page.props.auth?.user?.name || 'Administrator' }}
                            </span>
                        </p>
                        <p>
                            تاريخ ووقت الطباعة:
                            <span class="font-mono">{{ printDateFormatted }}</span>
                        </p>
                    </div>
                    <div class="text-center w-48 border-t border-dashed border-gray-400 pt-1">
                        <span class="text-[11px] font-bold text-black block">
                            الختم والتوقيع الرسمي
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </BaseModal>
</template>

<script setup>
import { computed } from 'vue';
import BaseModal from '@/Components/BaseModal.vue';

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
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
});

defineEmits(['close']);

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
</script>

<style scoped>
@media print {
    body * {
        visibility: hidden;
    }
    .statement-content,
    .statement-content * {
        visibility: visible;
    }
    .statement-content {
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
        padding: 20px;
    }
    .print-header-area {
        display: block !important;
    }
    .print-footer-area {
        display: block !important;
    }
    table {
        width: 100% !important;
        border-collapse: collapse !important;
        font-size: 11px !important;
    }
    th,
    td {
        border: 1px solid #d1d5db !important;
        padding: 5px !important;
        text-align: center !important;
    }
    th {
        background-color: #f3f4f6 !important;
        color: #111827 !important;
    }
}
</style>
