<script setup>
import { Link } from '@inertiajs/vue3';
import StatusBadge from '@/Components/DashboardV2/common/StatusBadge.vue';

defineProps({
    items: { type: Array, default: () => [] },
});

function formatTime(dateStr) {
    if (!dateStr) return '—';
    const d = new Date(dateStr);
    return d.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', hour12: true });
}
</script>

<template>
    <div>
        <div
            v-if="!items.length"
            class="py-8 text-center text-sm text-slate-500 dark:text-slate-400"
        >
            {{ $t('dashboard.no_schedule_today') }}
        </div>
        <ul v-else class="divide-y divide-slate-100 dark:divide-slate-700">
            <li v-for="item in items" :key="item.id" class="py-3 first:pt-0 last:pb-0">
                <Link :href="`/app/work-orders/${item.id}`" class="group flex items-start gap-3">
                    <div class="flex w-14 flex-shrink-0 flex-col items-center">
                        <span
                            class="text-sm font-semibold tabular-nums text-slate-900 dark:text-white"
                        >
                            {{ formatTime(item.expected_end_date) }}
                        </span>
                        <span
                            class="text-[10px] uppercase tracking-wide text-slate-500 dark:text-slate-400"
                        >
                            {{ $t('dashboard.expected') }}
                        </span>
                    </div>
                    <div
                        class="min-w-0 flex-1 border-l border-slate-200 pl-3 dark:border-slate-700"
                    >
                        <div class="flex items-center justify-between gap-2">
                            <p
                                class="truncate text-sm font-semibold text-slate-900 group-hover:text-indigo-600 dark:text-white dark:group-hover:text-indigo-400"
                            >
                                {{ item.code }}
                            </p>
                            <StatusBadge :status="item.status" />
                        </div>
                        <p class="mt-0.5 truncate text-xs text-slate-500 dark:text-slate-400">
                            {{ item.customer?.name || '—' }}
                            <span
                                v-if="item.vehicle?.plate_number"
                                class="mx-1 text-slate-300 dark:text-slate-600"
                            >
                                •
                            </span>
                            <span v-if="item.vehicle?.plate_number">
                                {{ item.vehicle.plate_number }}
                            </span>
                        </p>
                    </div>
                </Link>
            </li>
        </ul>
    </div>
</template>
