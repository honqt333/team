<script setup>
import { computed } from 'vue';

const props = defineProps({
    data: { type: Object, default: () => ({}) }, // { status: count }
});

const statusColors = {
    open: { color: '#64748b', label: 'Open' },
    in_progress: { color: '#0ea5e9', label: 'In Progress' },
    on_hold: { color: '#f59e0b', label: 'On Hold' },
    ready_for_qc: { color: '#8b5cf6', label: 'Ready for QC' },
    done: { color: '#10b981', label: 'Done' },
    completed: { color: '#10b981', label: 'Completed' },
    cancelled: { color: '#f43f5e', label: 'Cancelled' },
    pending: { color: '#64748b', label: 'Pending' },
};

const segments = computed(() => {
    const entries = Object.entries(props.data).filter(([k, v]) => v > 0);
    const total = entries.reduce((sum, [, v]) => sum + v, 0);
    if (total === 0) return [];

    let offset = 0;
    return entries.map(([status, count]) => {
        const pct = (count / total) * 100;
        const seg = {
            status,
            label: statusColors[status]?.label || status,
            color: statusColors[status]?.color || '#94a3b8',
            count,
            percent: Math.round(pct * 10) / 10,
            offset,
            length: pct,
        };
        offset += pct;
        return seg;
    });
});

const total = computed(() => {
    return Object.values(props.data).reduce((s, v) => s + v, 0);
});
</script>

<template>
    <div>
        <div v-if="total === 0" class="py-8 text-center text-sm text-slate-500 dark:text-slate-400">
            {{ $t('dashboard.no_data') }}
        </div>
        <div v-else class="flex items-center gap-6">
            <!-- Donut chart (SVG) -->
            <div class="relative h-28 w-28 flex-shrink-0">
                <svg viewBox="0 0 36 36" class="h-full w-full -rotate-90">
                    <circle
                        cx="18"
                        cy="18"
                        r="15.915"
                        fill="transparent"
                        stroke="currentColor"
                        stroke-width="3"
                        class="text-slate-100 dark:text-slate-700"
                    />
                    <circle
                        v-for="seg in segments"
                        :key="seg.status"
                        cx="18"
                        cy="18"
                        r="15.915"
                        fill="transparent"
                        :stroke="seg.color"
                        stroke-width="3"
                        :stroke-dasharray="`${seg.length} ${100 - seg.length}`"
                        :stroke-dashoffset="-seg.offset"
                        class="transition-all"
                    />
                </svg>
                <div class="absolute inset-0 flex flex-col items-center justify-center">
                    <span class="text-lg font-bold text-slate-900 dark:text-white">
                        {{ total }}
                    </span>
                    <span
                        class="text-[10px] uppercase tracking-wide text-slate-500 dark:text-slate-400"
                    >
                        {{ $t('dashboard.total') }}
                    </span>
                </div>
            </div>
            <!-- Legend -->
            <ul class="flex-1 space-y-1.5">
                <li
                    v-for="seg in segments"
                    :key="seg.status"
                    class="flex items-center justify-between gap-2 text-xs"
                >
                    <span class="flex items-center gap-2 min-w-0">
                        <span
                            class="h-2 w-2 flex-shrink-0 rounded-full"
                            :style="{ backgroundColor: seg.color }"
                        ></span>
                        <span class="truncate text-slate-600 dark:text-slate-300">
                            {{ seg.label }}
                        </span>
                    </span>
                    <span
                        class="flex-shrink-0 font-semibold tabular-nums text-slate-900 dark:text-white"
                    >
                        {{ seg.count }}
                        <span class="ml-1 font-normal text-slate-500 dark:text-slate-400">
                            ({{ seg.percent }}%)
                        </span>
                    </span>
                </li>
            </ul>
        </div>
    </div>
</template>
