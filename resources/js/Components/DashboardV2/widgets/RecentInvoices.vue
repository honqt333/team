<script setup>
import { Link } from '@inertiajs/vue3';
import StatusBadge from '@/Components/DashboardV2/common/StatusBadge.vue';

defineProps({
    items: { type: Array, default: () => [] },
    currency: { type: String, default: 'SAR' },
    limit: { type: Number, default: 5 },
});

function formatDate(dateStr) {
    if (!dateStr) return '';
    return new Date(dateStr).toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
}
</script>

<template>
    <div>
        <div
            v-if="!items.length"
            class="py-8 text-center text-sm text-slate-500 dark:text-slate-400"
        >
            {{ $t('dashboard.no_data') }}
        </div>
        <ul v-else class="divide-y divide-slate-100 dark:divide-slate-700">
            <li
                v-for="item in items.slice(0, limit)"
                :key="item.id"
                class="py-2.5 first:pt-0 last:pb-0"
            >
                <Link :href="`/app/invoices/${item.id}`" class="group flex items-center gap-3">
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center gap-2">
                            <p
                                class="truncate text-sm font-semibold text-slate-900 group-hover:text-indigo-600 dark:text-white dark:group-hover:text-indigo-400"
                            >
                                {{ item.invoice_number }}
                            </p>
                            <StatusBadge :status="item.payment_status" />
                        </div>
                        <p class="mt-0.5 truncate text-xs text-slate-500 dark:text-slate-400">
                            {{ item.customer?.name || '—' }}
                        </p>
                    </div>
                    <div class="flex-shrink-0 text-right">
                        <p
                            class="text-sm font-semibold tabular-nums text-slate-900 dark:text-white"
                        >
                            {{
                                new Intl.NumberFormat('en-US', { maximumFractionDigits: 0 }).format(
                                    item.total_incl_tax || 0
                                )
                            }}
                        </p>
                        <p class="text-xs text-slate-500 dark:text-slate-400">
                            {{ formatDate(item.issue_date) }}
                        </p>
                    </div>
                </Link>
            </li>
        </ul>
    </div>
</template>
