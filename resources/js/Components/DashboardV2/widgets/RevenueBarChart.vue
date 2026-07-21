<script setup>
import { computed } from 'vue';

const props = defineProps({
    data: { type: Array, default: () => [] }, // [{ date, label, total }]
    currency: { type: String, default: 'SAR' },
});

const max = computed(() => {
    if (!props.data.length) return 1;
    return Math.max(...props.data.map((d) => d.total || 0), 1);
});

const chartHeight = 100; // svg viewBox height

function barHeight(val) {
    return Math.max(((val || 0) / max.value) * chartHeight, val > 0 ? 1.5 : 0);
}

// Pick every nth label so x-axis isn't crowded on 30 points
const labelStride = computed(() => {
    if (props.data.length <= 7) return 1;
    if (props.data.length <= 15) return 2;
    if (props.data.length <= 30) return 5;
    return 7;
});
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
            <!-- Chart -->
            <div class="relative">
                <svg viewBox="0 0 100 100" preserveAspectRatio="none" class="h-32 w-full">
                    <!-- Grid lines -->
                    <line
                        x1="0"
                        y1="25"
                        x2="100"
                        y2="25"
                        stroke="currentColor"
                        stroke-width="0.2"
                        class="text-slate-200 dark:text-slate-700"
                        stroke-dasharray="1,1"
                    />
                    <line
                        x1="0"
                        y1="50"
                        x2="100"
                        y2="50"
                        stroke="currentColor"
                        stroke-width="0.2"
                        class="text-slate-200 dark:text-slate-700"
                        stroke-dasharray="1,1"
                    />
                    <line
                        x1="0"
                        y1="75"
                        x2="100"
                        y2="75"
                        stroke="currentColor"
                        stroke-width="0.2"
                        class="text-slate-200 dark:text-slate-700"
                        stroke-dasharray="1,1"
                    />
                    <!-- Bars -->
                    <g v-for="(d, idx) in data" :key="d.date">
                        <rect
                            :x="(idx / data.length) * 100 + 0.5"
                            :y="chartHeight - barHeight(d.total)"
                            :width="100 / data.length - 1"
                            :height="barHeight(d.total)"
                            rx="0.5"
                            class="fill-indigo-500 hover:fill-indigo-600 dark:fill-indigo-400 dark:hover:fill-indigo-300 transition-colors"
                        >
                            <title>
                                {{ d.label }}: {{ new Intl.NumberFormat('en-US').format(d.total) }}
                                {{ currency }}
                            </title>
                        </rect>
                    </g>
                </svg>
            </div>
            <!-- X-axis labels -->
            <div
                class="flex justify-between text-[10px] font-medium text-slate-500 dark:text-slate-400"
            >
                <span
                    v-for="(d, idx) in data"
                    :key="d.date"
                    v-show="idx % labelStride === 0 || idx === data.length - 1"
                >
                    {{ d.label }}
                </span>
            </div>
        </div>
    </div>
</template>
