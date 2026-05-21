<script setup>
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';

const { locale } = useI18n();
const isRtl = computed(() => locale.value === 'ar');

const props = defineProps({
    workOrders: { type: Array, default: () => [] },
    loading: { type: Boolean, default: false },
});

const statusConfig = {
    draft:        { color: 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400', dot: 'bg-gray-400' },
    open:         { color: 'bg-indigo-50 text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300', dot: 'bg-indigo-500' },
    in_progress:  { color: 'bg-blue-50 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300', dot: 'bg-blue-500 animate-pulse' },
    on_hold:      { color: 'bg-amber-50 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300', dot: 'bg-amber-500' },
    ready_for_qc: { color: 'bg-violet-50 text-violet-700 dark:bg-violet-900/40 dark:text-violet-300', dot: 'bg-violet-500' },
    done:         { color: 'bg-emerald-50 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300', dot: 'bg-emerald-500' },
    cancelled:    { color: 'bg-rose-50 text-rose-700 dark:bg-rose-900/40 dark:text-rose-300', dot: 'bg-rose-500' },
};

function formatDate(dateStr) {
    if (!dateStr) return '';
    return new Date(dateStr).toLocaleDateString(locale.value === 'ar' ? 'en-US' : 'en-SA', {
        month: 'short', day: 'numeric'
    });
}

function formatAmount(val) {
    return new Intl.NumberFormat('en-SA', { minimumFractionDigits: 0, maximumFractionDigits: 0 }).format(val || 0);
}
</script>

<template>
    <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700/60 shadow-sm">
        <!-- Header -->
        <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100 dark:border-gray-700/60">
            <div>
                <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300">{{ $t('dashboard.recent_work_orders') }}</h3>
                <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">{{ $t('dashboard.latest_8') }}</p>
            </div>
            <a href="/app/work-orders" class="text-xs font-medium text-indigo-600 dark:text-indigo-400 hover:text-indigo-700 dark:hover:text-indigo-300 flex items-center gap-1 transition-colors">
                {{ $t('common.view_all') }}
                <svg class="w-3.5 h-3.5" :class="isRtl ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
            </a>
        </div>

        <!-- Loading -->
        <div v-if="loading" class="divide-y divide-gray-100 dark:divide-gray-700/50">
            <div v-for="i in 5" :key="i" class="flex items-center gap-3 px-5 py-3.5">
                <div class="w-9 h-9 rounded-full bg-gray-100 dark:bg-gray-700 animate-pulse" />
                <div class="flex-1 space-y-1.5">
                    <div class="h-3.5 w-24 bg-gray-100 dark:bg-gray-700 rounded animate-pulse" />
                    <div class="h-3 w-16 bg-gray-100 dark:bg-gray-700/50 rounded animate-pulse" />
                </div>
                <div class="h-5 w-16 bg-gray-100 dark:bg-gray-700 rounded-full animate-pulse" />
            </div>
        </div>

        <!-- List -->
        <div v-else-if="workOrders.length" class="divide-y divide-gray-100 dark:divide-gray-700/50">
            <a
                v-for="wo in workOrders"
                :key="wo.id"
                :href="`/app/work-orders/${wo.id}`"
                class="flex items-center gap-3 px-5 py-3.5 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors group"
            >
                <!-- Avatar -->
                <div class="w-9 h-9 rounded-full bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center text-white text-xs font-bold flex-shrink-0">
                    {{ wo.customer?.name?.charAt(0) || '#' }}
                </div>

                <!-- Info -->
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2">
                        <span class="text-sm font-semibold text-gray-800 dark:text-gray-200 group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition-colors truncate">
                            {{ wo.customer?.name || '—' }}
                        </span>
                        <span class="text-xs text-gray-400 dark:text-gray-500 font-mono flex-shrink-0">{{ wo.code }}</span>
                    </div>
                    <div class="flex items-center gap-2 mt-0.5">
                        <svg class="w-3 h-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        <span class="text-xs text-gray-400 dark:text-gray-500">{{ formatDate(wo.created_at) }}</span>
                        <span v-if="wo.vehicle?.plate_number" class="text-xs font-mono text-gray-500 dark:text-gray-400">
                            · {{ wo.vehicle.plate_number }}
                        </span>
                    </div>
                </div>

                <!-- Right side -->
                <div class="flex flex-col items-end gap-1.5 flex-shrink-0">
                    <span :class="['inline-flex items-center gap-1 text-xs font-medium px-2 py-0.5 rounded-full', statusConfig[wo.status]?.color]">
                        <span :class="['w-1.5 h-1.5 rounded-full', statusConfig[wo.status]?.dot]" />
                        {{ $t('work_orders.status.' + wo.status) }}
                    </span>
                    <span class="text-xs font-semibold text-gray-700 dark:text-gray-300">
                        {{ formatAmount(wo.total_incl_tax) }}
                    </span>
                </div>
            </a>
        </div>

        <div v-else class="py-12 text-center text-gray-400 dark:text-gray-600">
            <svg class="w-12 h-12 mx-auto mb-3 opacity-40" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
            </svg>
            <p class="text-sm">{{ $t('dashboard.no_recent_work_orders') }}</p>
        </div>
    </div>
</template>
