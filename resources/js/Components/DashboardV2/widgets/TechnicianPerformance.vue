<script setup>
const props = defineProps({
    technicians: { type: Array, default: () => [] },
});

function initials(name) {
    if (!name) return '?';
    const parts = name.trim().split(/\s+/);
    if (parts.length === 1) return parts[0].slice(0, 2).toUpperCase();
    return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase();
}

function colorForIndex(idx) {
    const colors = [
        'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300',
        'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300',
        'bg-sky-100 text-sky-700 dark:bg-sky-900/40 dark:text-sky-300',
        'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300',
        'bg-rose-100 text-rose-700 dark:bg-rose-900/40 dark:text-rose-300',
        'bg-violet-100 text-violet-700 dark:bg-violet-900/40 dark:text-violet-300',
        'bg-teal-100 text-teal-700 dark:bg-teal-900/40 dark:text-teal-300',
        'bg-orange-100 text-orange-700 dark:bg-orange-900/40 dark:text-orange-300',
    ];
    return colors[idx % colors.length];
}
</script>

<template>
    <div>
        <div
            v-if="!technicians.length"
            class="py-8 text-center text-sm text-slate-500 dark:text-slate-400"
        >
            {{ $t('dashboard.no_data') }}
        </div>
        <ul v-else class="space-y-3">
            <li v-for="(tech, idx) in technicians" :key="tech.id" class="flex items-center gap-3">
                <div
                    :class="[
                        'flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-full text-sm font-bold',
                        colorForIndex(idx),
                    ]"
                >
                    {{ initials(tech.name) }}
                </div>
                <div class="min-w-0 flex-1">
                    <div class="flex items-center justify-between gap-2">
                        <p class="truncate text-sm font-medium text-slate-900 dark:text-white">
                            {{ tech.name }}
                        </p>
                        <span
                            class="flex-shrink-0 text-xs font-semibold tabular-nums text-slate-700 dark:text-slate-200"
                        >
                            {{ tech.completion_rate }}%
                        </span>
                    </div>
                    <div
                        class="mt-1.5 h-1.5 overflow-hidden rounded-full bg-slate-100 dark:bg-slate-700"
                    >
                        <div
                            class="h-full rounded-full bg-emerald-500 transition-all dark:bg-emerald-400"
                            :style="{ width: `${Math.min(tech.completion_rate, 100)}%` }"
                        ></div>
                    </div>
                    <div
                        class="mt-1 flex items-center gap-3 text-xs text-slate-500 dark:text-slate-400"
                    >
                        <span>{{ $t('dashboard.assigned', { n: tech.assigned }) }}</span>
                        <span class="text-emerald-600 dark:text-emerald-400">
                            {{ $t('dashboard.completed_short', { n: tech.completed }) }}
                        </span>
                        <span v-if="tech.in_progress > 0" class="text-sky-600 dark:text-sky-400">
                            {{ $t('dashboard.in_progress_short', { n: tech.in_progress }) }}
                        </span>
                    </div>
                </div>
            </li>
        </ul>
    </div>
</template>
