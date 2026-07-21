<script setup>
const props = defineProps({
    customers: { type: Array, default: () => [] },
    currency: { type: String, default: 'SAR' },
});

function initials(name) {
    if (!name) return '?';
    return name.trim().slice(0, 2).toUpperCase();
}
</script>

<template>
    <div>
        <div
            v-if="!customers.length"
            class="py-8 text-center text-sm text-slate-500 dark:text-slate-400"
        >
            {{ $t('dashboard.no_data') }}
        </div>
        <ul v-else class="divide-y divide-slate-100 dark:divide-slate-700">
            <li
                v-for="(customer, idx) in customers"
                :key="customer.id"
                class="flex items-center gap-3 py-3 first:pt-0 last:pb-0"
            >
                <div
                    class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-full bg-slate-100 text-sm font-semibold text-slate-600 dark:bg-slate-700 dark:text-slate-200"
                >
                    {{ initials(customer.name) }}
                </div>
                <div class="min-w-0 flex-1">
                    <p class="truncate text-sm font-medium text-slate-900 dark:text-white">
                        {{ customer.name }}
                    </p>
                    <p class="text-xs text-slate-500 dark:text-slate-400">
                        {{ $t('dashboard.invoice_count', { n: customer.invoice_count }) }}
                    </p>
                </div>
                <div class="flex-shrink-0 text-left">
                    <p class="text-sm font-semibold tabular-nums text-slate-900 dark:text-white">
                        {{
                            new Intl.NumberFormat('en-US', { maximumFractionDigits: 0 }).format(
                                customer.total_revenue
                            )
                        }}
                    </p>
                    <p class="text-xs text-slate-500 dark:text-slate-400">{{ currency }}</p>
                </div>
            </li>
        </ul>
    </div>
</template>
