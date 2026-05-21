<script setup>
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';

const { locale } = useI18n();
const isRtl = computed(() => locale.value === 'ar');

const props = defineProps({
    invoices: { type: Array, default: () => [] },
    currency: { type: String, default: 'SAR' },
    loading: { type: Boolean, default: false },
});

const statusConfig = {
    unpaid:  { label_ar: 'غير مدفوع', label_en: 'Unpaid',   color: 'bg-rose-50 text-rose-700 dark:bg-rose-900/40 dark:text-rose-300' },
    partial: { label_ar: 'جزئي',      label_en: 'Partial',  color: 'bg-amber-50 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300' },
    paid:    { label_ar: 'مدفوع',     label_en: 'Paid',     color: 'bg-emerald-50 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300' },
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
        <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100 dark:border-gray-700/60">
            <div>
                <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300">{{ $t('dashboard.recent_invoices') }}</h3>
                <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">{{ $t('dashboard.latest_8') }}</p>
            </div>
            <a href="/app/invoices" class="text-xs font-medium text-emerald-600 dark:text-emerald-400 hover:text-emerald-700 dark:hover:text-emerald-300 flex items-center gap-1 transition-colors">
                {{ $t('common.view_all') }}
                <svg class="w-3.5 h-3.5" :class="isRtl ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
            </a>
        </div>

        <div v-if="loading" class="divide-y divide-gray-100 dark:divide-gray-700/50">
            <div v-for="i in 5" :key="i" class="flex items-center gap-3 px-5 py-3.5">
                <div class="w-9 h-9 rounded-full bg-gray-100 dark:bg-gray-700 animate-pulse" />
                <div class="flex-1 space-y-1.5">
                    <div class="h-3.5 w-24 bg-gray-100 dark:bg-gray-700 rounded animate-pulse" />
                    <div class="h-3 w-16 bg-gray-100 dark:bg-gray-700/50 rounded animate-pulse" />
                </div>
                <div class="h-5 w-14 bg-gray-100 dark:bg-gray-700 rounded-full animate-pulse" />
            </div>
        </div>

        <div v-else-if="invoices.length" class="divide-y divide-gray-100 dark:divide-gray-700/50">
            <a
                v-for="inv in invoices"
                :key="inv.id"
                :href="`/app/invoices`"
                class="flex items-center gap-3 px-5 py-3.5 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors group"
            >
                <!-- Icon -->
                <div class="w-9 h-9 rounded-full bg-gradient-to-br from-emerald-400 to-teal-600 flex items-center justify-center flex-shrink-0">
                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                </div>

                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2">
                        <span class="text-sm font-semibold text-gray-800 dark:text-gray-200 group-hover:text-emerald-600 dark:group-hover:text-emerald-400 transition-colors truncate">
                            {{ inv.customer?.name || '—' }}
                        </span>
                        <span class="text-xs text-gray-400 font-mono flex-shrink-0">{{ inv.invoice_number }}</span>
                    </div>
                    <span class="text-xs text-gray-400 dark:text-gray-500">{{ formatDate(inv.issue_date) }}</span>
                </div>

                <div class="flex flex-col items-end gap-1.5 flex-shrink-0">
                    <span :class="['text-xs font-medium px-2 py-0.5 rounded-full', statusConfig[inv.payment_status]?.color]">
                        {{ $t('invoices.payment_status.' + inv.payment_status) || (isRtl ? statusConfig[inv.payment_status]?.label_ar : statusConfig[inv.payment_status]?.label_en) }}
                    </span>
                    <span class="text-xs font-semibold text-gray-700 dark:text-gray-300">
                        {{ formatAmount(inv.total_incl_tax) }}
                        <span class="text-gray-400 font-normal">{{ currency }}</span>
                    </span>
                </div>
            </a>
        </div>

        <div v-else class="py-12 text-center text-gray-400 dark:text-gray-600">
            <svg class="w-12 h-12 mx-auto mb-3 opacity-40" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            <p class="text-sm">{{ $t('dashboard.no_recent_invoices') }}</p>
        </div>
    </div>
</template>
