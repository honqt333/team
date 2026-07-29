<template>
    <div class="rounded-xl border p-4 shadow-sm" :class="cardClasses">
        <div class="flex items-center justify-between gap-4 flex-wrap">
            <div>
                <p class="text-xs font-bold uppercase tracking-wider opacity-80">
                    {{ $t('system.tenants.health.title') }}
                </p>
                <p class="mt-1 text-3xl font-bold" :class="scoreColor">
                    {{ score }}
                    <span class="text-base font-medium opacity-60">/100</span>
                </p>
                <p class="mt-1 text-xs opacity-80">
                    {{ scoreLabel }}
                </p>
            </div>

            <!-- Visual ring -->
            <div class="relative h-16 w-16 shrink-0">
                <svg class="h-16 w-16 -rotate-90" viewBox="0 0 36 36">
                    <path
                        class="text-gray-200 dark:text-gray-700"
                        d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="3"
                    />
                    <path
                        :class="ringColor"
                        d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="3"
                        :stroke-dasharray="`${score}, 100`"
                        stroke-linecap="round"
                    />
                </svg>
                <div
                    class="absolute inset-0 flex items-center justify-center text-sm font-bold"
                    :class="scoreColor"
                >
                    {{ score }}
                </div>
            </div>
        </div>

        <!-- Flags: explanation chips for each signal that moved the score.
             Wrapped in a tooltip-like container — admin can see at a glance
             why the score is what it is and what action to take. -->
        <div v-if="flags && flags.length" class="mt-3 flex flex-wrap gap-1.5">
            <span
                v-for="flag in flags"
                :key="flag.key"
                class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[11px] font-medium"
                :class="flagBadge(flag.type)"
                :title="flagTooltip(flag.key)"
            >
                <span aria-hidden="true">{{ flagIcon(flag.type) }}</span>
                {{ flagLabel(flag.key) }}
            </span>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';

const props = defineProps({
    score: { type: Number, required: true },
    flags: { type: Array, default: () => [] },
});

const { t } = useI18n();

// Map score → color + label. 80+ green (healthy), 60-79 amber (watch),
// <60 rose (at risk). Mirrors the visual ring fill so the score is
// readable at a glance without hovering.
const scoreColor = computed(() => {
    if (props.score >= 80) return 'text-emerald-600 dark:text-emerald-400';
    if (props.score >= 60) return 'text-amber-600 dark:text-amber-400';
    return 'text-rose-600 dark:text-rose-400';
});

const ringColor = computed(() => {
    if (props.score >= 80) return 'text-emerald-500';
    if (props.score >= 60) return 'text-amber-500';
    return 'text-rose-500';
});

const cardClasses = computed(() => {
    if (props.score >= 80)
        return 'border-emerald-200 bg-emerald-50 dark:border-emerald-800/50 dark:bg-emerald-900/10';
    if (props.score >= 60)
        return 'border-amber-200 bg-amber-50 dark:border-amber-800/50 dark:bg-amber-900/10';
    return 'border-rose-200 bg-rose-50 dark:border-rose-800/50 dark:bg-rose-900/10';
});

const scoreLabel = computed(() => {
    if (props.score >= 80) return t('system.tenants.health.healthy');
    if (props.score >= 60) return t('system.tenants.health.needs_watch');
    return t('system.tenants.health.needs_intervention');
});

const flagBadge = (type) => {
    if (type === 'danger')
        return 'bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-300';
    if (type === 'warning')
        return 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300';
    return 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300';
};

const flagIcon = (type) => {
    if (type === 'danger') return '⚠';
    if (type === 'warning') return '◐';
    return '✓';
};

const flagLabel = (key) => {
    const translationKey = `system.tenants.health.flags.${key}`;
    return t(translationKey) !== translationKey ? t(translationKey) : key;
};

const flagTooltip = (key) => {
    const translationKey = `system.tenants.health.tooltips.${key}`;
    return t(translationKey) !== translationKey ? t(translationKey) : key;
};
</script>
