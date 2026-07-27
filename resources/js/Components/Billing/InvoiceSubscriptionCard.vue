<template>
    <div
        class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800"
    >
        <h3 class="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">
            {{ isAr ? 'الاشتراك' : 'Subscription' }}
        </h3>

        <div class="mt-4 flex items-center gap-3">
            <div
                class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-gradient-to-br from-indigo-500 to-blue-600 text-white"
            >
                <BoltIcon class="h-5 w-5" />
            </div>
            <div class="min-w-0 flex-1">
                <p class="truncate text-sm font-bold text-gray-900 dark:text-white">
                    {{ planName }}
                </p>
                <p class="truncate text-xs text-gray-500 dark:text-gray-400">
                    {{ billingCycleLabel }}
                </p>
            </div>
        </div>

        <dl class="mt-5 space-y-3 border-t border-gray-200 pt-4 text-sm dark:border-gray-700">
            <div class="flex items-center justify-between">
                <dt class="text-xs text-gray-500 dark:text-gray-400">
                    {{ isAr ? 'الحالة' : 'Status' }}
                </dt>
                <dd>
                    <span
                        :class="[
                            'inline-block rounded-full px-2 py-0.5 text-xs font-semibold',
                            statusClasses,
                        ]"
                    >
                        {{ statusLabel }}
                    </span>
                </dd>
            </div>

            <div v-if="subscription?.starts_at" class="flex items-center justify-between">
                <dt class="text-xs text-gray-500 dark:text-gray-400">
                    {{ isAr ? 'تاريخ البدء' : 'Start date' }}
                </dt>
                <dd class="font-mono text-xs text-gray-900 dark:text-white">
                    {{ formatDate(subscription.starts_at) }}
                </dd>
            </div>

            <div v-if="subscription?.ends_at" class="flex items-center justify-between">
                <dt class="text-xs text-gray-500 dark:text-gray-400">
                    {{ isAr ? 'تاريخ الانتهاء' : 'End date' }}
                </dt>
                <dd class="font-mono text-xs text-gray-900 dark:text-white">
                    {{ formatDate(subscription.ends_at) }}
                </dd>
            </div>

            <div v-if="subscription?.price" class="flex items-center justify-between">
                <dt class="text-xs text-gray-500 dark:text-gray-400">
                    {{ isAr ? 'سعر الباقة' : 'Plan price' }}
                </dt>
                <dd class="font-mono text-sm font-semibold text-gray-900 dark:text-white">
                    {{ formatMoney(subscription.price) }}
                </dd>
            </div>
        </dl>

        <Link
            v-if="subscription?.id"
            :href="`/system/subscriptions/${subscription.id}`"
            class="mt-5 flex w-full items-center justify-center gap-1.5 rounded-lg border border-gray-200 px-3 py-2 text-xs font-semibold text-gray-700 transition hover:bg-gray-50 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-gray-700/40"
        >
            {{ isAr ? 'عرض الاشتراك' : 'View subscription' }}
            <ArrowRightIcon class="h-3.5 w-3.5" />
        </Link>
    </div>
</template>

<script setup>
import { computed, h } from 'vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    subscription: { type: Object, default: null },
    plan: { type: Object, default: null },
});

const isAr = computed(() => (document?.documentElement?.lang || 'ar') === 'ar');

const planName = computed(() => {
    if (!props.plan) return '—';
    if (isAr.value) {
        return props.plan.name_ar || props.plan.name_en || props.plan.name;
    }
    return props.plan.name_en || props.plan.name_ar || props.plan.name;
});

const billingCycleLabel = computed(() => {
    const cycle = props.subscription?.billing_cycle;
    if (cycle === 'yearly') {
        return isAr.value ? 'سنوي' : 'Yearly';
    }
    if (cycle === 'monthly') {
        return isAr.value ? 'شهري' : 'Monthly';
    }
    return cycle || '—';
});

const statusLabel = computed(() => {
    const map = {
        active: isAr.value ? 'نشط' : 'Active',
        trial: isAr.value ? 'تجريبي' : 'Trial',
        expired: isAr.value ? 'منتهي' : 'Expired',
        cancelled: isAr.value ? 'ملغي' : 'Cancelled',
        pending: isAr.value ? 'بالانتظار' : 'Pending',
        past_due: isAr.value ? 'متأخر' : 'Past due',
        pending_payment: isAr.value ? 'بانتظار الدفع' : 'Pending payment',
    };
    return map[props.subscription?.status] || props.subscription?.status || '—';
});

const statusClasses = computed(() => {
    const map = {
        active: 'bg-emerald-50 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300',
        trial: 'bg-blue-50 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300',
        expired: 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300',
        cancelled: 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300',
        pending: 'bg-amber-50 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300',
        pending_payment: 'bg-amber-50 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300',
        past_due: 'bg-rose-50 text-rose-700 dark:bg-rose-900/30 dark:text-rose-300',
    };
    return (
        map[props.subscription?.status] ||
        'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300'
    );
});

function formatDate(d) {
    if (!d) return '—';
    return new Date(d).toLocaleDateString(isAr.value ? 'ar-SA' : 'en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
}

function formatMoney(n) {
    return new Intl.NumberFormat(isAr.value ? 'ar-SA-u-nu-latn' : 'en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }).format(n || 0);
}

const makeIcon = (path) =>
    h(
        'svg',
        {
            xmlns: 'http://www.w3.org/2000/svg',
            fill: 'none',
            viewBox: '0 0 24 24',
            strokeWidth: 1.8,
            stroke: 'currentColor',
        },
        [
            h('path', {
                strokeLinecap: 'round',
                strokeLinejoin: 'round',
                d: path,
            }),
        ]
    );

const BoltIcon = () => makeIcon('M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z');
const ArrowRightIcon = () => makeIcon('M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3');
</script>
