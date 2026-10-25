<script setup>
import { computed } from 'vue';

const props = defineProps({
    plan: { type: Object, required: true },
    isCurrent: { type: Boolean, default: false },
    hasPendingSubscription: { type: Boolean, default: false },
    canRenew: { type: Boolean, default: false },
    isExpired: { type: Boolean, default: false },
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

// Button state has three flavors:
// 1. Current active plan (not renewable yet)     → "Your current plan", disabled
// 2. Current expired / expiring / trial plan    → "Renew subscription", ENABLED
// 3. Pending in-flight subscription             → "Pending request", disabled
// 4. Other plan                                 → "Subscribe now", ENABLED
const isDisabled = computed(() => {
    if (props.hasPendingSubscription) return true;
    if (props.isCurrent && !props.canRenew && !props.isExpired) {
        return true;
    }
    return false;
});

const buttonLabel = computed(() => {
    if (props.hasPendingSubscription) {
        return locale.value === 'ar' ? 'طلب قيد المراجعة' : 'Pending request';
    }
    if (props.isCurrent) {
        if (props.isExpired || props.canRenew) {
            return locale.value === 'ar' ? 'تجديد الاشتراك' : 'Renew subscription';
        }
        return locale.value === 'ar' ? 'باقتك الحالية' : 'Your current plan';
    }
    return locale.value === 'ar' ? 'اشترك الآن' : 'Subscribe now';
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
                : hasPendingSubscription
                  ? 'border-2 border-amber-400/70 ring-1 ring-amber-300/50 dark:border-amber-500/50 dark:ring-amber-500/20'
                  : 'border-slate-200 hover:border-slate-300 hover:shadow-md dark:border-slate-700 dark:hover:border-slate-600',
            plan.is_featured && !isCurrent && !hasPendingSubscription
                ? 'ring-1 ring-amber-300 dark:ring-amber-500/30'
                : '',
        ]"
    >
        <!-- Current / Expired badge -->
        <span
            v-if="isCurrent && isExpired"
            class="absolute -top-3 start-6 rounded-full bg-rose-600 px-3 py-1 text-[10px] font-bold uppercase tracking-wider text-white shadow"
        >
            {{ locale === 'ar' ? 'باقتك المنتهية' : 'Expired plan' }}
        </span>
        <span
            v-else-if="isCurrent"
            class="absolute -top-3 start-6 rounded-full bg-indigo-600 px-3 py-1 text-[10px] font-bold uppercase tracking-wider text-white shadow"
        >
            {{ locale === 'ar' ? 'باقتك الحالية' : 'Current plan' }}
        </span>
        <span
            v-else-if="hasPendingSubscription"
            class="absolute -top-3 start-6 rounded-full bg-amber-500 px-3 py-1 text-[10px] font-bold uppercase tracking-wider text-white shadow"
        >
            {{ locale === 'ar' ? 'طلب قيد المراجعة' : 'Pending request' }}
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
                        {{ formatCurrency(plan.price_yearly) }}
                    </span>
                    <span class="text-xs font-semibold text-slate-500 dark:text-slate-400">
                        {{ locale === 'ar' ? 'ر.س / سنوياً' : 'SAR / yearly' }}
                    </span>
                </div>
                <div
                    class="mt-1 flex items-baseline gap-1 text-xs text-slate-500 dark:text-slate-400"
                >
                    <span v-if="Number(plan.price_yearly) > 0">
                        {{
                            locale === 'ar'
                                ? 'اشتراك سنوي شامل لكافة المزايا'
                                : 'Annual subscription with all features'
                        }}
                    </span>
                    <span v-else>
                        {{
                            locale === 'ar'
                                ? 'تجربة مجانية لفترة محدودة'
                                : 'Limited-time free trial'
                        }}
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
            :disabled="isDisabled"
            :title="
                hasPendingSubscription && !isCurrent
                    ? locale === 'ar'
                        ? 'لديك اشتراك قيد المراجعة، انتظر الموافقة قبل طلب جديد'
                        : 'You already have a pending request. Wait for admin approval.'
                    : ''
            "
            :class="[
                'mt-6 w-full rounded-xl px-4 py-2.5 text-xs font-bold transition-all',
                isDisabled
                    ? 'cursor-not-allowed bg-slate-100 text-slate-400 dark:bg-slate-700 dark:text-slate-500'
                    : isCurrent && (canRenew || isExpired)
                      ? 'bg-indigo-600 text-white hover:bg-indigo-700 shadow-md ring-2 ring-indigo-500/20'
                      : plan.is_featured
                        ? 'bg-amber-500 text-white hover:bg-amber-600'
                        : 'bg-indigo-50 text-indigo-700 hover:bg-indigo-100 dark:bg-indigo-900/30 dark:text-indigo-300 dark:hover:bg-indigo-900/50',
            ]"
        >
            {{ buttonLabel }}
        </button>
    </div>
</template>
