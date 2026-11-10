<script setup>
import { computed } from 'vue';
import Badge from '@/Components/Common/StatusBadge.vue';
import { useI18n } from 'vue-i18n';

const { t, te } = useI18n();

const props = defineProps({
    status: { type: String, required: true },
});

const statusConfig = {
    // work order statuses
    open: {
        key: 'work_orders.work_order_statuses.open',
        fallback: 'مفتوح',
        class: 'bg-slate-100 text-slate-700 dark:bg-slate-700 dark:text-slate-200',
    },
    in_progress: {
        key: 'work_orders.work_order_statuses.in_progress',
        fallback: 'قيد التنفيذ',
        class: 'bg-sky-100 text-sky-700 dark:bg-sky-900/40 dark:text-sky-300',
    },
    on_hold: {
        key: 'work_orders.work_order_statuses.on_hold',
        fallback: 'معلق',
        class: 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300',
    },
    ready_for_qc: {
        key: 'work_orders.work_order_statuses.ready_for_qc',
        fallback: 'جاهز للفحص',
        class: 'bg-violet-100 text-violet-700 dark:bg-violet-900/40 dark:text-violet-300',
    },
    done: {
        key: 'work_orders.work_order_statuses.done',
        fallback: 'جاهز للتسليم',
        class: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300',
    },
    completed: {
        key: 'work_orders.work_order_statuses.completed',
        fallback: 'مكتمل',
        class: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300',
    },
    cancelled: {
        key: 'work_orders.work_order_statuses.cancelled',
        fallback: 'ملغي',
        class: 'bg-rose-100 text-rose-700 dark:bg-rose-900/40 dark:text-rose-300',
    },
    pending: {
        key: 'common.pending',
        fallback: 'قيد الانتظار',
        class: 'bg-slate-100 text-slate-700 dark:bg-slate-700 dark:text-slate-200',
    },
    // invoice payment statuses
    paid: {
        key: 'invoices.status.paid',
        fallback: 'مدفوعة',
        class: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300',
    },
    unpaid: {
        key: 'invoices.status.unpaid',
        fallback: 'غير مدفوعة',
        class: 'bg-rose-100 text-rose-700 dark:bg-rose-900/40 dark:text-rose-300',
    },
    partial: {
        key: 'invoices.status.partial',
        fallback: 'مدفوعة جزئياً',
        class: 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300',
    },
    overdue: {
        key: 'invoices.status.overdue',
        fallback: 'متأخرة',
        class: 'bg-rose-100 text-rose-700 dark:bg-rose-900/40 dark:text-rose-300',
    },
};

const meta = computed(() => {
    const config = statusConfig[props.status];
    if (config) {
        return {
            label: te(config.key) ? t(config.key) : config.fallback,
            class: config.class,
        };
    }
    return {
        label: props.status,
        class: 'bg-slate-100 text-slate-700 dark:bg-slate-700 dark:text-slate-200',
    };
});
</script>

<template>
    <Badge
        :label="meta.label"
        dot-class="bg-current opacity-70"
        :class="['gap-1 rounded px-2 py-0.5 text-xs font-medium', meta.class]"
    />
</template>
