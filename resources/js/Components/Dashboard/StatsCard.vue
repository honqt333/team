<script setup>
import { computed, ref, onMounted } from 'vue';

const props = defineProps({
    label: { type: String, required: true },
    value: { type: [Number, String], required: true },
    previousValue: { type: [Number, String], default: null },
    prefix: { type: String, default: '' },
    suffix: { type: String, default: '' },
    icon: { type: String, required: true },
    color: { type: String, default: 'indigo' }, // indigo | emerald | amber | rose | blue | violet | teal
    format: { type: String, default: 'number' }, // number | currency | raw
    currency: { type: String, default: 'SAR' },
    loading: { type: Boolean, default: false },
    link: { type: String, default: null },
    subtitle: { type: String, default: null },
});

const animated = ref(0);
const displayValue = ref(0);

const colorMap = {
    indigo: {
        bg: 'from-indigo-500 to-indigo-700',
        light: 'bg-indigo-50 dark:bg-indigo-900/20',
        text: 'text-indigo-600 dark:text-indigo-400',
        ring: 'ring-indigo-500/20',
        badge: 'bg-indigo-100 dark:bg-indigo-900/40 text-indigo-700 dark:text-indigo-300',
        iconBg: 'bg-indigo-500/15',
        icon: 'text-indigo-600 dark:text-indigo-400',
        glow: 'shadow-indigo-500/20',
        bar: 'bg-indigo-500',
    },
    emerald: {
        bg: 'from-emerald-500 to-emerald-700',
        light: 'bg-emerald-50 dark:bg-emerald-900/20',
        text: 'text-emerald-600 dark:text-emerald-400',
        ring: 'ring-emerald-500/20',
        badge: 'bg-emerald-100 dark:bg-emerald-900/40 text-emerald-700 dark:text-emerald-300',
        iconBg: 'bg-emerald-500/15',
        icon: 'text-emerald-600 dark:text-emerald-400',
        glow: 'shadow-emerald-500/20',
        bar: 'bg-emerald-500',
    },
    amber: {
        bg: 'from-amber-500 to-orange-600',
        light: 'bg-amber-50 dark:bg-amber-900/20',
        text: 'text-amber-600 dark:text-amber-400',
        ring: 'ring-amber-500/20',
        badge: 'bg-amber-100 dark:bg-amber-900/40 text-amber-700 dark:text-amber-300',
        iconBg: 'bg-amber-500/15',
        icon: 'text-amber-600 dark:text-amber-400',
        glow: 'shadow-amber-500/20',
        bar: 'bg-amber-500',
    },
    rose: {
        bg: 'from-rose-500 to-rose-700',
        light: 'bg-rose-50 dark:bg-rose-900/20',
        text: 'text-rose-600 dark:text-rose-400',
        ring: 'ring-rose-500/20',
        badge: 'bg-rose-100 dark:bg-rose-900/40 text-rose-700 dark:text-rose-300',
        iconBg: 'bg-rose-500/15',
        icon: 'text-rose-600 dark:text-rose-400',
        glow: 'shadow-rose-500/20',
        bar: 'bg-rose-500',
    },
    blue: {
        bg: 'from-blue-500 to-blue-700',
        light: 'bg-blue-50 dark:bg-blue-900/20',
        text: 'text-blue-600 dark:text-blue-400',
        ring: 'ring-blue-500/20',
        badge: 'bg-blue-100 dark:bg-blue-900/40 text-blue-700 dark:text-blue-300',
        iconBg: 'bg-blue-500/15',
        icon: 'text-blue-600 dark:text-blue-400',
        glow: 'shadow-blue-500/20',
        bar: 'bg-blue-500',
    },
    violet: {
        bg: 'from-violet-500 to-purple-700',
        light: 'bg-violet-50 dark:bg-violet-900/20',
        text: 'text-violet-600 dark:text-violet-400',
        ring: 'ring-violet-500/20',
        badge: 'bg-violet-100 dark:bg-violet-900/40 text-violet-700 dark:text-violet-300',
        iconBg: 'bg-violet-500/15',
        icon: 'text-violet-600 dark:text-violet-400',
        glow: 'shadow-violet-500/20',
        bar: 'bg-violet-500',
    },
    teal: {
        bg: 'from-teal-500 to-cyan-600',
        light: 'bg-teal-50 dark:bg-teal-900/20',
        text: 'text-teal-600 dark:text-teal-400',
        ring: 'ring-teal-500/20',
        badge: 'bg-teal-100 dark:bg-teal-900/40 text-teal-700 dark:text-teal-300',
        iconBg: 'bg-teal-500/15',
        icon: 'text-teal-600 dark:text-teal-400',
        glow: 'shadow-teal-500/20',
        bar: 'bg-teal-500',
    },
};

const c = computed(() => colorMap[props.color] || colorMap.indigo);

const changePercent = computed(() => {
    if (props.previousValue === null || props.previousValue === 0) return null;
    const diff = Number(props.value) - Number(props.previousValue);
    return ((diff / Number(props.previousValue)) * 100).toFixed(1);
});

const isPositive = computed(() => changePercent.value !== null && Number(changePercent.value) >= 0);

function formatNumber(n) {
    if (props.format === 'currency') {
        return new Intl.NumberFormat('en-SA', { style: 'decimal', minimumFractionDigits: 0, maximumFractionDigits: 0 }).format(n);
    }
    return new Intl.NumberFormat('en-SA').format(Math.round(n));
}

const formattedValue = computed(() => {
    if (props.format === 'raw') return props.value;
    return formatNumber(props.value);
});

// Animate counter on mount
onMounted(() => {
    const target = Number(props.value);
    if (isNaN(target) || props.format === 'raw') return;
    const duration = 1200;
    const steps = 60;
    const stepDuration = duration / steps;
    let current = 0;
    const timer = setInterval(() => {
        current += target / steps;
        if (current >= target) {
            current = target;
            clearInterval(timer);
        }
        displayValue.value = current;
    }, stepDuration);
});

const animatedFormatted = computed(() => {
    if (props.format === 'raw') return props.value;
    return formatNumber(displayValue.value);
});
</script>

<template>
    <component
        :is="link ? 'a' : 'div'"
        :href="link"
        :class="[
            'group relative bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700/60',
            'shadow-sm hover:shadow-lg transition-all duration-300',
            link ? 'cursor-pointer hover:-translate-y-0.5' : '',
            'overflow-hidden p-5',
        ]"
    >
        <!-- Gradient accent bar at top -->
        <div :class="['absolute top-0 inset-x-0 h-1 bg-gradient-to-r', c.bg]" />

        <!-- Subtle background glow -->
        <div :class="['absolute inset-0 opacity-0 group-hover:opacity-100 transition-opacity duration-500', c.light]" />

        <div class="relative flex items-start justify-between gap-4">
            <!-- Icon -->
            <div :class="['w-12 h-12 rounded-xl flex items-center justify-center flex-shrink-0 shadow-sm ring-1', c.iconBg, c.ring]">
                <span v-safe-html="icon" :class="['w-6 h-6', c.icon]" />
            </div>

            <!-- Change badge -->
            <div v-if="changePercent !== null"
                :class="[
                    'flex items-center gap-1 text-xs font-semibold px-2 py-1 rounded-full',
                    isPositive
                        ? 'bg-emerald-50 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400'
                        : 'bg-rose-50 dark:bg-rose-900/30 text-rose-700 dark:text-rose-400'
                ]"
            >
                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                        :d="isPositive ? 'M5 10l7-7m0 0l7 7m-7-7v18' : 'M19 14l-7 7m0 0l-7-7m7 7V3'" />
                </svg>
                {{ Math.abs(changePercent) }}%
            </div>
        </div>

        <div class="relative mt-4">
            <!-- Loading skeleton -->
            <template v-if="loading">
                <div class="h-8 w-32 bg-gray-200 dark:bg-gray-700 rounded-lg animate-pulse mb-2" />
                <div class="h-4 w-20 bg-gray-100 dark:bg-gray-700/50 rounded animate-pulse" />
            </template>

            <template v-else>
                <!-- Value -->
                <div class="flex items-baseline gap-1.5">
                    <span v-if="prefix" class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ prefix }}</span>
                    <span class="text-3xl font-bold text-gray-900 dark:text-white tracking-tight">
                        {{ animatedFormatted }}
                    </span>
                    <span v-if="suffix" class="text-sm font-semibold text-gray-500 dark:text-gray-400">{{ suffix }}</span>
                </div>

                <!-- Label -->
                <p class="mt-1 text-sm font-medium text-gray-500 dark:text-gray-400">{{ label }}</p>

                <!-- Subtitle -->
                <p v-if="subtitle" class="mt-0.5 text-xs text-gray-400 dark:text-gray-500">{{ subtitle }}</p>
            </template>
        </div>

        <!-- Bottom comparison bar -->
        <div v-if="previousValue !== null && !loading" class="relative mt-4 pt-3 border-t border-gray-100 dark:border-gray-700/50">
            <div class="flex items-center justify-between text-xs text-gray-500 dark:text-gray-400">
                <span>{{ $t('dashboard.vs_last_month') }}</span>
                <span class="font-medium">{{ formatNumber(previousValue) }}</span>
            </div>
        </div>
    </component>
</template>
