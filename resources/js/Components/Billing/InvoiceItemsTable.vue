<template>
    <div
        class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800"
    >
        <div
            class="flex items-center justify-between border-b border-gray-200 px-5 py-4 dark:border-gray-700"
        >
            <h3 class="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                {{ isAr ? 'عناصر الفاتورة' : 'Invoice Items' }}
            </h3>
            <span
                v-if="items.length > 0"
                class="rounded-full bg-gray-100 px-2 py-0.5 text-xs font-semibold text-gray-600 dark:bg-gray-700 dark:text-gray-300"
            >
                {{ items.length }} {{ isAr ? 'عنصر' : 'item(s)' }}
            </span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead
                    class="border-b border-gray-200 bg-gray-50 text-xs uppercase text-gray-500 dark:border-gray-700 dark:bg-gray-900/40 dark:text-gray-400"
                >
                    <tr>
                        <th class="px-5 py-3 text-start font-semibold">
                            {{ isAr ? 'الوصف' : 'Description' }}
                        </th>
                        <th class="px-5 py-3 text-start font-semibold">
                            {{ isAr ? 'الفترة' : 'Billing Period' }}
                        </th>
                        <th class="px-5 py-3 text-end font-semibold">
                            {{ isAr ? 'السعر' : 'Price' }}
                        </th>
                        <th class="px-5 py-3 text-end font-semibold">
                            {{ isAr ? 'المجموع' : 'Total' }}
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    <tr
                        v-for="(item, idx) in items"
                        :key="idx"
                        class="hover:bg-gray-50/50 dark:hover:bg-gray-700/30"
                    >
                        <td class="px-5 py-4">
                            <div class="font-semibold text-gray-900 dark:text-white">
                                {{ item.description }}
                            </div>
                            <div
                                v-if="item.subDescription"
                                class="mt-0.5 text-xs text-gray-500 dark:text-gray-400"
                            >
                                {{ item.subDescription }}
                            </div>
                        </td>
                        <td class="px-5 py-4 text-gray-600 dark:text-gray-300">
                            <div class="text-sm">
                                {{ item.period }}
                            </div>
                        </td>
                        <td
                            class="px-5 py-4 text-end font-mono text-gray-700 tabular-nums dark:text-gray-300"
                        >
                            {{ formatMoney(item.price) }}
                        </td>
                        <td
                            class="px-5 py-4 text-end font-mono font-semibold text-gray-900 tabular-nums dark:text-white"
                        >
                            {{ formatMoney(item.total) }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    invoice: { type: Object, required: true },
    subscription: { type: Object, default: null },
    plan: { type: Object, default: null },
});

const isAr = computed(() => (document?.documentElement?.lang || 'ar') === 'ar');

const billingLabel = computed(() => {
    const cycle = props.subscription?.billing_cycle;
    if (cycle === 'yearly') {
        return isAr.value ? 'اشتراك سنوي' : 'Yearly subscription';
    }
    return isAr.value ? 'اشتراك شهري' : 'Monthly subscription';
});

const items = computed(() => {
    const out = [];

    // Single primary line: the plan subscription
    if (props.plan) {
        out.push({
            description:
                props.plan.name_ar && isAr.value
                    ? props.plan.name_ar
                    : props.plan.name_en || props.plan.name,
            subDescription: billingLabel.value,
            period: formatPeriod(props.subscription?.starts_at, props.subscription?.ends_at),
            price: Number(props.invoice.subtotal),
            total: Number(props.invoice.subtotal),
        });
    } else {
        out.push({
            description: isAr.value ? 'اشتراك' : 'Subscription',
            subDescription: billingLabel.value,
            period: formatPeriod(props.subscription?.starts_at, props.subscription?.ends_at),
            price: Number(props.invoice.subtotal),
            total: Number(props.invoice.subtotal),
        });
    }

    return out;
});

function formatPeriod(start, end) {
    if (!start || !end) return '—';
    const fmt = (d) =>
        new Date(d).toLocaleDateString(isAr.value ? 'ar-SA' : 'en-US', {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
        });
    return `${fmt(start)} → ${fmt(end)}`;
}

function formatMoney(n) {
    return new Intl.NumberFormat(isAr.value ? 'ar-SA-u-nu-latn' : 'en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }).format(n || 0);
}
</script>
