<script setup>
import { computed } from 'vue';

const props = defineProps({
    data: { type: Array, default: () => [] }, // [{ date, label, count }]
});

const max = computed(() => {
    if (!props.data.length) return 1;
    return Math.max(...props.data.map((d) => d.count || 0), 1);
});

const chartHeight = 100;

function barHeight(val) {
    return Math.max(((val || 0) / max.value) * chartHeight, val > 0 ? 1.5 : 0);
}
</script>

<template>
    <div>
        <div
            v-if="!data.length"
            class="py-8 text-center text-sm text-slate-500 dark:text-slate-400"
        >
            {{ $t('dashboard.no_data') }}
        </div>
        <div v-else class="space-y-3">
            <div class="flex h-32 items-end gap-1.5">
                <div
                    v-for="d in data"
                    :key="d.date"
                    class="group flex flex-1 flex-col items-center gap-1"
                >
                    <div class="flex h-full w-full items-end">
                        <div
                            :style="{ height: `${barHeight(d.count)}%` }"
                            class="w-full rounded-t bg-sky-500 transition-all group-hover:bg-sky-600 dark:bg-sky-400 dark:group-hover:bg-sky-300"
                        >
                            <div
                                class="invisible h-0 group-hover:visible group-hover:relative group-hover:h-6 group-hover:w-full group-hover:translate-y-[-110%] group-hover:rounded group-hover:bg-slate-900 group-hover:py-0.5 group-hover:text-center group-hover:text-[10px] group-hover:font-semibold group-hover:text-white group-hover:shadow-md dark:group-hover:bg-slate-700"
                            >
                                {{ d.count }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="flex gap-1.5 text-[10px] font-medium text-slate-500 dark:text-slate-400">
                <span
                    v-for="d in data"
                    :key="d.date"
                    class="flex-1 text-center uppercase tracking-wide"
                >
                    {{ d.label }}
                </span>
            </div>
        </div>
    </div>
</template>
