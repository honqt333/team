<template>
    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
        <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
            <!-- Entry Date -->
            <div class="bg-gray-50 dark:bg-gray-900/50 rounded-xl p-3">
                <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">
                    {{ $t('work_orders.form.entry_date') }}
                </p>
                <p class="font-medium text-gray-900 dark:text-white flex items-center gap-2">
                    <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    {{ formatDate(workOrder.entry_date) }}
                </p>
            </div>

            <!-- Expected End Date -->
            <div class="bg-gray-50 dark:bg-gray-900/50 rounded-xl p-3">
                <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">
                    {{ $t('work_orders.form.expected_end_date') }}
                </p>
                <p class="font-medium text-gray-900 dark:text-white flex items-center gap-2">
                    <svg class="w-4 h-4 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    {{ formatDate(workOrder.expected_end_date) }}
                </p>
            </div>

            <!-- Duration / Status (highlighted when overdue) -->
            <div class="bg-gray-50 dark:bg-gray-900/50 rounded-xl p-3">
                <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">
                    {{ $t('work_orders.duration') }}
                </p>
                <p class="font-medium flex items-center gap-2"
                    :class="durationInfo.isOverdue ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400'">
                    <svg v-if="durationInfo.isOverdue" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    {{ durationInfo.text }}
                </p>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { useFormatters } from '@/Composables/useFormatters';

const props = defineProps({
    workOrder: { type: Object, required: true },
    isReadOnly: { type: Boolean, default: false },
});

const { t } = useI18n();
const { formatDate } = useFormatters();

/**
 * Compute the duration string shown in the third info card.
 *
 * For closed work orders we show how long the work took (and warn if
 * it ran past the expected end date). For open work orders we show the
 * days remaining until the expected end date, or mark overdue.
 */
const durationInfo = computed(() => {
    const entryDate = props.workOrder.entry_date ? new Date(props.workOrder.entry_date) : null;
    const expectedEndDate = props.workOrder.expected_end_date ? new Date(props.workOrder.expected_end_date) : null;
    const today = new Date();
    today.setHours(0, 0, 0, 0);

    if (!entryDate || !expectedEndDate) {
        return { text: '-', isOverdue: false };
    }

    if (props.isReadOnly) {
        const exitDate = props.workOrder.exit_date ? new Date(props.workOrder.exit_date) : expectedEndDate;
        const daysUsed = Math.ceil((exitDate - entryDate) / (1000 * 60 * 60 * 24));
        const isOverdue = exitDate > expectedEndDate;
        const overdueDays = isOverdue ? Math.ceil((exitDate - expectedEndDate) / (1000 * 60 * 60 * 24)) : 0;

        if (isOverdue) {
            return { text: t('work_orders.overdue_days', { days: overdueDays }), isOverdue: true };
        }
        return { text: t('work_orders.completed_in_days', { days: daysUsed }), isOverdue: false };
    }

    const daysRemaining = Math.ceil((expectedEndDate - today) / (1000 * 60 * 60 * 24));

    if (daysRemaining < 0) {
        return { text: t('work_orders.overdue_days', { days: Math.abs(daysRemaining) }), isOverdue: true };
    }
    if (daysRemaining === 0) {
        return { text: t('work_orders.due_today'), isOverdue: false };
    }
    return { text: t('work_orders.days_remaining', { days: daysRemaining }), isOverdue: false };
});
</script>
