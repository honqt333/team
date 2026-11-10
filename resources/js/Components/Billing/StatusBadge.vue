<script setup>
import Badge from '@/Components/Common/StatusBadge.vue';
import { useI18n } from 'vue-i18n';
const { t } = useI18n();

import { computed } from 'vue';

const props = defineProps({
    status: {
        type: String,
        required: true,
    },
});

const STATUS_MAP = {
    paid: {
        key: 'hr.payroll.paid',
        classes:
            'bg-emerald-50 text-emerald-700 ring-1 ring-inset ring-emerald-600/20 dark:bg-emerald-900/20 dark:text-emerald-300 dark:ring-emerald-500/30',
        dotClasses: 'bg-emerald-500',
    },
    pending: {
        key: 'hr.leaves.statuses.pending',
        classes:
            'bg-amber-50 text-amber-700 ring-1 ring-inset ring-amber-600/20 dark:bg-amber-900/20 dark:text-amber-300 dark:ring-amber-500/30',
        dotClasses: 'bg-amber-500',
    },
    overdue: {
        key: 'work_orders.filters.overdue',
        classes:
            'bg-rose-50 text-rose-700 ring-1 ring-inset ring-rose-600/20 dark:bg-rose-900/20 dark:text-rose-300 dark:ring-rose-500/30',
        dotClasses: 'bg-rose-500',
    },
    cancelled: {
        key: 'inventory.transfers.statuses.cancelled',
        classes:
            'bg-gray-100 text-gray-600 ring-1 ring-inset ring-gray-500/20 dark:bg-gray-700/40 dark:text-gray-300 dark:ring-gray-500/30',
        dotClasses: 'bg-gray-400',
    },
    failed: {
        key: 'system.invoices.status.failed',
        classes:
            'bg-rose-50 text-rose-700 ring-1 ring-inset ring-rose-600/20 dark:bg-rose-900/20 dark:text-rose-300 dark:ring-rose-500/30',
        dotClasses: 'bg-rose-500',
    },
};

const config = computed(() => {
    const entry = STATUS_MAP[props.status] || STATUS_MAP.pending;
    return { ...entry, label: t(entry.key) };
});
</script>

<template>
    <Badge
        :label="config.label"
        :dot-class="config.dotClasses"
        :class="['gap-1.5 rounded-full px-3 py-1 text-xs font-semibold', config.classes]"
    />
</template>
