<template>
    <div
        class="rounded-xl border p-4 shadow-sm transition-shadow hover:shadow"
        :class="cardClasses"
    >
        <div class="flex items-start justify-between gap-2">
            <div class="min-w-0 flex-1">
                <p class="text-xs font-bold uppercase tracking-wider" :class="labelClasses">
                    {{ label }}
                </p>
                <p class="mt-2 truncate text-2xl font-bold" :class="valueClasses">
                    {{ value }}
                </p>
                <p v-if="sub" class="mt-1 text-xs" :class="subClasses">
                    {{ sub }}
                </p>
            </div>

            <!-- Trend arrow (only when a number is passed in) -->
            <div
                v-if="trend !== null && trend !== undefined"
                class="flex items-center gap-1 text-xs font-bold shrink-0"
                :class="trendColor"
            >
                <svg
                    v-if="trendUp"
                    class="h-4 w-4"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M5 10l7-7m0 0l7 7m-7-7v18"
                    />
                </svg>
                <svg v-else class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M19 14l-7 7m0 0l-7-7m7 7V3"
                    />
                </svg>
                <span>{{ trendAbs }}%</span>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    label: { type: String, required: true },
    value: { type: [String, Number], required: true },
    sub: { type: String, default: null },
    trend: { type: Number, default: null },
    // When true, an upward trend is bad (e.g. churn). Flips the color.
    trendInverse: { type: Boolean, default: false },
    // Visual emphasis: emerald (positive), rose (negative), amber (warning),
    // indigo/blue/slate (neutral/informational).
    color: {
        type: String,
        default: 'indigo',
        validator: (v) => ['emerald', 'rose', 'amber', 'indigo', 'blue', 'slate'].includes(v),
    },
});

// Vue 3 templates don't expose `Math` automatically the way the
// runtime does. Expose a small wrapper so the template can use it
// without resorting to a method call on every render.
const trendAbs = computed(() => Math.abs(props.trend || 0));

const trendUp = computed(() => props.trend > 0);
const trendDown = computed(() => props.trend < 0);

const trendColor = computed(() => {
    if (props.trend === null || props.trend === undefined || props.trend === 0) {
        return 'text-gray-400';
    }
    const positive = trendUp.value;
    const isGood = props.trendInverse ? !positive : positive;
    return isGood ? 'text-emerald-600' : 'text-rose-600';
});

const cardClasses = computed(() => {
    const colorMap = {
        emerald:
            'border-emerald-200 bg-emerald-50 dark:border-emerald-800/50 dark:bg-emerald-900/10',
        rose: 'border-rose-200 bg-rose-50 dark:border-rose-800/50 dark:bg-rose-900/10',
        amber: 'border-amber-200 bg-amber-50 dark:border-amber-800/50 dark:bg-amber-900/10',
        indigo: 'border-indigo-200 bg-indigo-50 dark:border-indigo-800/50 dark:bg-indigo-900/10',
        blue: 'border-blue-200 bg-blue-50 dark:border-blue-800/50 dark:bg-blue-900/10',
        slate: 'border-slate-200 bg-slate-50 dark:border-slate-700 dark:bg-slate-800/30',
    };
    return colorMap[props.color] || colorMap.indigo;
});

const labelClasses = computed(() => {
    const map = {
        emerald: 'text-emerald-700 dark:text-emerald-300',
        rose: 'text-rose-700 dark:text-rose-300',
        amber: 'text-amber-700 dark:text-amber-300',
        indigo: 'text-indigo-700 dark:text-indigo-300',
        blue: 'text-blue-700 dark:text-blue-300',
        slate: 'text-slate-700 dark:text-slate-300',
    };
    return map[props.color] || map.indigo;
});

const valueClasses = computed(() => {
    return 'text-gray-900 dark:text-white';
});

const subClasses = computed(() => {
    return 'text-gray-500 dark:text-gray-400';
});
</script>
