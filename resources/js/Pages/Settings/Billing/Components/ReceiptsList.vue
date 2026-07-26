<script setup>
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

const props = defineProps({
    receipts: { type: Array, default: () => [] },
});

const locale = computed(() => document.documentElement.lang || 'ar');

const formatDate = (dateStr) => {
    if (!dateStr) return t('system.billing.page.em_dash');
    return new Date(dateStr).toLocaleDateString(
        locale.value === 'ar'
            ? t('system.billing.page.locale_ar')
            : t('system.billing.page.locale_en'),
        {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
        }
    );
};

const formatCurrency = (amount) => {
    return new Intl.NumberFormat(t('system.billing.page.locale_en'), {
        maximumFractionDigits: 2,
    }).format(amount || 0);
};

const noPlanLabel = () => t('system.billing.page.no_plan');

const statusConfig = {
    pending: {
        class: 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300',
    },
    approved: {
        class: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300',
    },
    rejected: {
        class: 'bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-300',
    },
};
</script>

<template>
    <div class="overflow-hidden rounded-xl border border-slate-200 dark:border-slate-700">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 dark:bg-slate-900/50">
                <tr>
                    <th
                        class="px-4 py-2.5 text-start text-xs font-bold uppercase tracking-wider text-slate-500"
                    >
                        {{ t('system.billing.page.receipts_table.date') }}
                    </th>
                    <th
                        class="px-4 py-2.5 text-start text-xs font-bold uppercase tracking-wider text-slate-500"
                    >
                        {{ t('system.billing.page.receipts_table.plan') }}
                    </th>
                    <th
                        class="px-4 py-2.5 text-start text-xs font-bold uppercase tracking-wider text-slate-500"
                    >
                        {{ t('system.billing.page.receipts_table.amount') }}
                    </th>
                    <th
                        class="px-4 py-2.5 text-start text-xs font-bold uppercase tracking-wider text-slate-500"
                    >
                        {{ t('system.billing.page.receipts_table.reference') }}
                    </th>
                    <th
                        class="px-4 py-2.5 text-start text-xs font-bold uppercase tracking-wider text-slate-500"
                    >
                        {{ t('system.billing.page.receipts_table.status') }}
                    </th>
                    <th
                        class="px-4 py-2.5 text-end text-xs font-bold uppercase tracking-wider text-slate-500"
                    >
                        {{ t('system.billing.page.receipts_table.actions') }}
                    </th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                <tr
                    v-for="r in receipts"
                    :key="r.id"
                    class="hover:bg-slate-50/50 dark:hover:bg-slate-800/30"
                >
                    <td class="px-4 py-2.5 font-mono text-xs text-slate-600 dark:text-slate-300">
                        {{ formatDate(r.created_at) }}
                    </td>
                    <td class="px-4 py-2.5 text-slate-700 dark:text-slate-200">
                        {{
                            locale === 'ar'
                                ? r.subscription?.plan?.name_ar || noPlanLabel()
                                : r.subscription?.plan?.name_en || noPlanLabel()
                        }}
                    </td>
                    <td class="px-4 py-2.5 font-mono font-semibold text-slate-900 dark:text-white">
                        {{ formatCurrency(r.transfer_amount) }}
                        {{ t('system.billing.page.currency') }}
                    </td>
                    <td class="px-4 py-2.5 font-mono text-xs text-slate-600 dark:text-slate-300">
                        {{ r.transfer_reference }}
                    </td>
                    <td class="px-4 py-2.5">
                        <span
                            :class="[
                                'inline-flex items-center gap-1 rounded px-2 py-0.5 text-xs font-semibold',
                                statusConfig[r.status]?.class ||
                                    'bg-slate-100 text-slate-700 dark:bg-slate-700 dark:text-slate-300',
                            ]"
                        >
                            <span class="h-1.5 w-1.5 rounded-full bg-current opacity-70"></span>
                            {{ t(`system.billing.page.receipts_status.${r.status}`, r.status) }}
                        </span>
                    </td>
                    <td class="px-4 py-2.5 text-end">
                        <a
                            v-if="r.receipt_path"
                            :href="`/storage/${r.receipt_path}`"
                            target="_blank"
                            rel="noopener"
                            class="text-xs font-semibold text-indigo-600 hover:text-indigo-700 dark:text-indigo-400 dark:hover:text-indigo-300"
                        >
                            {{ t('system.billing.page.receipts_view') }}
                        </a>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</template>
