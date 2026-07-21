<script setup>
import { computed } from 'vue';

const props = defineProps({
    services: { type: Array, default: () => [] }, // [{ id, name, times_used, total_revenue }]
    currency: { type: String, default: 'SAR' },
});

const maxUsage = computed(() => {
    if (!props.services.length) return 1;
    return Math.max(...props.services.map((s) => s.times_used || 0));
});
</script>

<template>
    <div>
        <div
            v-if="!services.length"
            class="py-8 text-center text-sm text-slate-500 dark:text-slate-400"
        >
            {{ $t('dashboard.no_data') }}
        </div>
        <ul v-else class="divide-y divide-slate-100 dark:divide-slate-700">
            <li
                v-for="(service, idx) in services"
                :key="service.id"
                class="flex items-center gap-3 py-3 first:pt-0 last:pb-0"
            >
                <span
                    :class="[
                        'flex h-7 w-7 flex-shrink-0 items-center justify-center rounded-md text-xs font-bold',
                        idx === 0
                            ? 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300'
                            : idx === 1
                              ? 'bg-slate-200 text-slate-700 dark:bg-slate-700 dark:text-slate-200'
                              : idx === 2
                                ? 'bg-orange-100 text-orange-700 dark:bg-orange-900/40 dark:text-orange-300'
                                : 'bg-slate-50 text-slate-500 dark:bg-slate-800 dark:text-slate-400',
                    ]"
                >
                    {{ idx + 1 }}
                </span>
                <div class="min-w-0 flex-1">
                    <p class="truncate text-sm font-medium text-slate-900 dark:text-white">
                        {{ service.name }}
                    </p>
                    <div class="mt-1.5 flex items-center gap-2">
                        <div
                            class="h-1.5 flex-1 overflow-hidden rounded-full bg-slate-100 dark:bg-slate-700"
                        >
                            <div
                                class="h-full rounded-full bg-indigo-500 transition-all dark:bg-indigo-400"
                                :style="{ width: `${(service.times_used / maxUsage) * 100}%` }"
                            ></div>
                        </div>
                        <span
                            class="w-16 flex-shrink-0 text-xs font-medium tabular-nums text-slate-600 dark:text-slate-300"
                        >
                            {{ service.times_used }} ×
                        </span>
                    </div>
                </div>
                <div class="flex-shrink-0 text-left">
                    <p class="text-sm font-semibold tabular-nums text-slate-900 dark:text-white">
                        {{
                            new Intl.NumberFormat('en-US', { maximumFractionDigits: 0 }).format(
                                service.total_revenue
                            )
                        }}
                    </p>
                    <p class="text-xs text-slate-500 dark:text-slate-400">{{ currency }}</p>
                </div>
            </li>
        </ul>
    </div>
</template>
