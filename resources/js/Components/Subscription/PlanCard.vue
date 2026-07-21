<script setup>
import { computed } from 'vue';

const props = defineProps({
    plan: { type: Object, required: true },
    isCurrent: { type: Boolean, default: false },
});

const emit = defineEmits(['subscribe']);

const locale = computed(() => document.documentElement.lang || 'ar');

const name = computed(() => (locale.value === 'ar' ? props.plan.name_ar : props.plan.name_en));
const description = computed(() =>
    locale.value === 'ar' ? props.plan.description_ar : props.plan.description_en
);
const features = computed(() => {
    if (locale.value === 'ar') return props.plan.features_ar || [];
    return props.plan.features_en || props.plan.features_ar || [];
});

const formatCurrency = (amount) => {
    return new Intl.NumberFormat('en-US', { maximumFractionDigits: 0 }).format(amount || 0);
};
</script>

<template>
    <div
        :class="[
            'relative flex flex-col justify-between rounded-2xl border bg-white p-6 transition-all dark:bg-slate-800',
            isCurrent
                ? 'border-2 border-indigo-600 ring-2 ring-indigo-600/20 dark:border-indigo-400 dark:ring-indigo-400/20'
                : 'border-slate-200 hover:border-slate-300 hover:shadow-md dark:border-slate-700 dark:hover:border-slate-600',
            plan.is_featured && !isCurrent ? 'ring-1 ring-amber-300 dark:ring-amber-500/30' : '',
        ]"
    >
        <!-- Current badge -->
        <span
            v-if="isCurrent"
            class="absolute -top-3 start-6 rounded-full bg-indigo-600 px-3 py-1 text-[10px] font-bold uppercase tracking-wider text-white shadow"
        >
            {{ locale === 'ar' ? 'باقتك الحالية' : 'Current plan' }}
        </span>
        <span
            v-else-if="plan.is_featured"
            class="absolute -top-3 start-6 rounded-full bg-amber-500 px-3 py-1 text-[10px] font-bold uppercase tracking-wider text-white shadow"
        >
            {{ locale === 'ar' ? 'الأكثر شعبية' : 'Most popular' }}
        </span>

        <!-- Header -->
        <div>
            <h3 class="text-lg font-bold text-slate-900 dark:text-white">{{ name }}</h3>
            <p v-if="description" class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                {{ description }}
            </p>

            <!-- Pricing -->
            <div class="mt-4">
                <div class="flex items-baseline gap-1">
                    <span class="text-3xl font-black text-slate-900 dark:text-white">
                        {{ formatCurrency(plan.price_monthly) }}
                    </span>
                    <span class="text-xs text-slate-500 dark:text-slate-400">
                        {{ locale === 'ar' ? 'ر.س / شهرياً' : 'SAR / month' }}
                    </span>
                </div>
                <div class="mt-1 flex items-baseline gap-1 text-sm">
                    <span class="text-slate-600 dark:text-slate-300">
                        {{ formatCurrency(plan.price_yearly) }}
                        {{ locale === 'ar' ? 'ر.س' : 'SAR' }}
                    </span>
                    <span class="text-xs text-slate-500">
                        / {{ locale === 'ar' ? 'سنوياً' : 'yearly' }}
                    </span>
                </div>
            </div>

            <!-- Features -->
            <ul
                v-if="features.length"
                class="mt-5 space-y-2 border-t border-slate-100 pt-4 dark:border-slate-700"
            >
                <li
                    v-for="(feat, idx) in features"
                    :key="idx"
                    class="flex items-start gap-2 text-xs text-slate-600 dark:text-slate-300"
                >
                    <svg
                        class="mt-0.5 h-4 w-4 flex-shrink-0 text-emerald-500"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2.5"
                            d="M5 13l4 4L19 7"
                        />
                    </svg>
                    <span>{{ feat }}</span>
                </li>
            </ul>
            <p v-else class="mt-5 text-xs italic text-slate-400">
                {{ locale === 'ar' ? 'لا توجد ميزات مسجلة' : 'No features listed' }}
            </p>
        </div>

        <!-- Action -->
        <button
            type="button"
            @click="emit('subscribe', plan)"
            :disabled="isCurrent"
            :class="[
                'mt-6 w-full rounded-xl px-4 py-2.5 text-xs font-bold transition-all',
                isCurrent
                    ? 'cursor-not-allowed bg-slate-100 text-slate-400 dark:bg-slate-700 dark:text-slate-500'
                    : plan.is_featured
                      ? 'bg-amber-500 text-white hover:bg-amber-600'
                      : 'bg-indigo-50 text-indigo-700 hover:bg-indigo-100 dark:bg-indigo-900/30 dark:text-indigo-300 dark:hover:bg-indigo-900/50',
            ]"
        >
            <span v-if="isCurrent">
                {{ locale === 'ar' ? 'باقتك الحالية' : 'Your current plan' }}
            </span>
            <span v-else>
                {{ locale === 'ar' ? 'اشترك الآن' : 'Subscribe now' }}
            </span>
        </button>
    </div>
</template>
