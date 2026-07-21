<script setup>
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';

const { t, n } = useI18n();

const props = defineProps({
    label: { type: String, required: true },
    value: { type: [Number, String], required: true },
    previousValue: { type: [Number, String, null], default: null },
    format: { type: String, default: 'number' }, // 'number' | 'currency' | 'percent'
    suffix: { type: String, default: '' },
    icon: { type: String, default: null },
    iconColor: { type: String, default: 'slate' }, // slate, indigo, emerald, amber, rose, sky, violet
    href: { type: [String, null], default: null },
    subtitle: { type: String, default: null },
    isLoading: { type: Boolean, default: false },
});

const iconColorClasses = {
    slate: 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300',
    indigo: 'bg-indigo-50 text-indigo-600 dark:bg-indigo-900/30 dark:text-indigo-300',
    emerald: 'bg-emerald-50 text-emerald-600 dark:bg-emerald-900/30 dark:text-emerald-300',
    amber: 'bg-amber-50 text-amber-600 dark:bg-amber-900/30 dark:text-amber-300',
    rose: 'bg-rose-50 text-rose-600 dark:bg-rose-900/30 dark:text-rose-300',
    sky: 'bg-sky-50 text-sky-600 dark:bg-sky-900/30 dark:text-sky-300',
    violet: 'bg-violet-50 text-violet-600 dark:bg-violet-900/30 dark:text-violet-300',
};

const trend = computed(() => {
    if (props.previousValue === null || props.previousValue === undefined) return null;
    const curr = Number(props.value) || 0;
    const prev = Number(props.previousValue) || 0;
    if (prev === 0) {
        return curr > 0 ? { direction: 'up', percent: 100 } : { direction: 'flat', percent: 0 };
    }
    const diff = ((curr - prev) / prev) * 100;
    if (Math.abs(diff) < 0.5) return { direction: 'flat', percent: 0 };
    return {
        direction: diff > 0 ? 'up' : 'down',
        percent: Math.abs(Math.round(diff * 10) / 10),
    };
});

const formattedValue = computed(() => {
    const num = Number(props.value) || 0;
    if (props.format === 'currency') {
        return new Intl.NumberFormat('en-US', { maximumFractionDigits: 0 }).format(num);
    }
    if (props.format === 'percent') {
        return `${num}${props.suffix || '%'}`;
    }
    return new Intl.NumberFormat('en-US').format(num);
});

const formattedPrevious = computed(() => {
    if (props.previousValue === null) return null;
    const num = Number(props.previousValue) || 0;
    if (props.format === 'currency') {
        return new Intl.NumberFormat('en-US', { maximumFractionDigits: 0 }).format(num);
    }
    return new Intl.NumberFormat('en-US').format(num);
});
</script>

<template>
    <component
        :is="href ? 'a' : 'div'"
        :href="href || undefined"
        class="group block rounded-xl border border-slate-200 bg-white p-5 transition-all hover:border-slate-300 hover:shadow-sm dark:border-slate-700 dark:bg-slate-800 dark:hover:border-slate-600"
        :class="href ? 'cursor-pointer' : ''"
    >
        <div class="flex items-start justify-between gap-3">
            <div class="min-w-0 flex-1">
                <p
                    class="text-xs font-medium uppercase tracking-wide text-slate-500 dark:text-slate-400"
                >
                    {{ label }}
                </p>
                <p class="mt-2 text-2xl font-semibold text-slate-900 dark:text-white">
                    <span
                        v-if="isLoading"
                        class="inline-block h-7 w-20 animate-pulse rounded bg-slate-200 dark:bg-slate-700"
                    ></span>
                    <span v-else>
                        {{ formattedValue }}
                        <span
                            v-if="suffix && format !== 'percent'"
                            class="ml-1 text-sm font-normal text-slate-500 dark:text-slate-400"
                        >
                            {{ suffix }}
                        </span>
                    </span>
                </p>
                <div v-if="subtitle || trend" class="mt-2 flex items-center gap-2 text-xs">
                    <span
                        v-if="trend"
                        :class="[
                            'inline-flex items-center gap-0.5 rounded px-1.5 py-0.5 font-semibold',
                            trend.direction === 'up'
                                ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300'
                                : trend.direction === 'down'
                                  ? 'bg-rose-50 text-rose-700 dark:bg-rose-900/30 dark:text-rose-300'
                                  : 'bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-300',
                        ]"
                    >
                        <svg
                            v-if="trend.direction === 'up'"
                            class="h-3 w-3"
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
                        <svg
                            v-else-if="trend.direction === 'down'"
                            class="h-3 w-3"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M19 14l-7 7m0 0l-7-7m7 7V3"
                            />
                        </svg>
                        <svg
                            v-else
                            class="h-3 w-3"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M5 12h14"
                            />
                        </svg>
                        {{ trend.percent }}%
                    </span>
                    <span v-if="subtitle" class="truncate text-slate-500 dark:text-slate-400">
                        {{ subtitle }}
                    </span>
                    <span
                        v-else-if="formattedPrevious !== null"
                        class="truncate text-slate-500 dark:text-slate-400"
                    >
                        {{ $t('dashboard.vs_last_period', { value: formattedPrevious }) }}
                    </span>
                </div>
            </div>
            <div
                v-if="icon"
                :class="[
                    'flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-lg',
                    iconColorClasses[iconColor] || iconColorClasses.slate,
                ]"
                v-html="icon"
            />
        </div>
    </component>
</template>
