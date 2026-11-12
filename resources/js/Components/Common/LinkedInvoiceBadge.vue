<template>
    <Link
        :href="href"
        :title="resolvedMeta.tooltip"
        :class="[
            'inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-mono font-bold border transition-all duration-150 shadow-2xs hover:shadow-xs hover:scale-[1.02] cursor-pointer',
            resolvedMeta.badgeClass,
        ]"
    >
        <!-- Credit Note / Return Icon -->
        <svg
            v-if="resolvedMeta.icon === 'credit_note'"
            class="w-3.5 h-3.5 shrink-0"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
        >
            <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"
            />
        </svg>

        <!-- Deferred / Clock Icon -->
        <svg
            v-else-if="resolvedMeta.icon === 'clock'"
            class="w-3.5 h-3.5 shrink-0"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
        >
            <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"
            />
        </svg>

        <!-- Paid / Check Icon -->
        <svg
            v-else-if="resolvedMeta.icon === 'check'"
            class="w-3.5 h-3.5 shrink-0"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
        >
            <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
            />
        </svg>

        <!-- Work Order Icon -->
        <svg
            v-else-if="resolvedMeta.icon === 'work_order'"
            class="w-3.5 h-3.5 shrink-0"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
        >
            <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"
            />
        </svg>

        <!-- Standard Document Icon -->
        <svg
            v-else
            class="w-3.5 h-3.5 shrink-0"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
        >
            <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
            />
        </svg>

        <span dir="ltr">{{ resolvedMeta.number }}</span>
    </Link>
</template>

<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import { useFormatters } from '@/Composables/useFormatters';

const props = defineProps({
    href: { type: String, required: true },
    invoice: { type: Object, default: null },
    number: { type: String, default: '' },
    kind: { type: String, default: '' }, // 'work_order' | 'credit_note' | 'deferred' | 'paid' | 'standard'
    tooltip: { type: String, default: '' },
});

const { t } = useI18n();
const { formatDate } = useFormatters();

const BADGE_STYLES = {
    credit_note:
        'bg-rose-50 text-rose-700 border-rose-200/80 dark:bg-rose-950/40 dark:text-rose-300 dark:border-rose-800/60 hover:bg-rose-100 dark:hover:bg-rose-900/60 hover:border-rose-300',
    deferred:
        'bg-amber-50 text-amber-700 border-amber-200/80 dark:bg-amber-950/40 dark:text-amber-300 dark:border-amber-800/60 hover:bg-amber-100 dark:hover:bg-amber-900/60 hover:border-amber-300',
    paid: 'bg-emerald-50 text-emerald-700 border-emerald-200/80 dark:bg-emerald-950/40 dark:text-emerald-300 dark:border-emerald-800/60 hover:bg-emerald-100 dark:hover:bg-emerald-900/60 hover:border-emerald-300',
    standard:
        'bg-sky-50 text-sky-700 border-sky-200/80 dark:bg-sky-950/40 dark:text-sky-300 dark:border-sky-800/60 hover:bg-sky-100 dark:hover:bg-sky-900/60 hover:border-sky-300',
    work_order:
        'bg-indigo-50 text-indigo-700 border-indigo-200/80 dark:bg-indigo-950/40 dark:text-indigo-300 dark:border-indigo-800/60 hover:bg-indigo-100 dark:hover:bg-indigo-900/60 hover:border-indigo-300',
};

const resolvedMeta = computed(() => {
    const inv = props.invoice;
    const num = props.number || inv?.invoice_number || inv?.code || '';

    if (props.kind === 'work_order') {
        return {
            number: num,
            icon: 'work_order',
            badgeClass: BADGE_STYLES.work_order,
            tooltip: props.tooltip || `${t('work_orders.title_singular')} #${num}`,
        };
    }

    if (props.kind === 'credit_note' || inv?.type === 'credit_note') {
        return {
            number: num,
            icon: 'credit_note',
            badgeClass: BADGE_STYLES.credit_note,
            tooltip: props.tooltip || `${t('invoices.credit_note.title')} #${num}`,
        };
    }

    const isDeferred =
        props.kind === 'deferred' ||
        (inv &&
            ((inv.due_date && inv.payment_status !== 'paid') ||
                inv.payment_status === 'unpaid' ||
                inv.payment_status === 'partial'));

    if (isDeferred) {
        const dueText = inv?.due_date
            ? ` (${t('invoices.due_date')}: ${formatDate(inv.due_date)})`
            : '';
        return {
            number: num,
            icon: 'clock',
            badgeClass: BADGE_STYLES.deferred,
            tooltip: props.tooltip || `${t('invoices.deferred_invoice')} #${num}${dueText}`,
        };
    }

    const isPaid = props.kind === 'paid' || inv?.payment_status === 'paid';
    const statusText = isPaid ? t('invoices.status_paid') : t('invoices.types.invoice');

    return {
        number: num,
        icon: isPaid ? 'check' : 'document',
        badgeClass: isPaid ? BADGE_STYLES.paid : BADGE_STYLES.standard,
        tooltip: props.tooltip || `${t('invoices.types.invoice')} #${num} - ${statusText}`,
    };
});
</script>
