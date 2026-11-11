<template>
    <CustomerPortalLayout
        :tenant="tenant"
        :locale="locale"
        :title="t('customer_portal.portal_quotes.title')"
        :subtitle="t('customer_portal.portal_quotes.subtitle')"
        show-logout
        show-bottom-nav
        current-route="public.portal.quotes.index"
        :language-base-path="`/${tenant.slug}/portal/quotes`"
    >
        <!--
            Quotes/Index.vue — the customer's quotes hub.
            Pending quotes (can_be_actioned) are highlighted so the
            customer immediately sees what is waiting for a decision.
        -->
        <div class="mb-5">
            <AppTabs v-model="activeFilter" :tabs="filterTabs" variant="pills" size="sm" />
        </div>

        <PortalEmptyState
            v-if="filteredQuotes.length === 0"
            :message="t('customer_portal.portal_quotes.empty')"
        />

        <div v-else class="space-y-4">
            <a
                v-for="q in filteredQuotes"
                :key="q.id"
                :href="`/${tenant.slug}/portal/quote/${q.id}`"
                class="group block rounded-2xl bg-white dark:bg-slate-900 border p-5 shadow-sm hover:shadow-md transition"
                :class="
                    q.can_be_actioned
                        ? 'border-amber-300 dark:border-amber-700/80 ring-1 ring-amber-200/60 dark:ring-amber-900/40'
                        : 'border-slate-200/80 dark:border-slate-800'
                "
            >
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div class="min-w-0 space-y-1.5">
                        <div class="flex flex-wrap items-center gap-2">
                            <span
                                class="text-sm font-mono font-extrabold text-slate-900 dark:text-white"
                                dir="ltr"
                            >
                                {{ q.code }}
                            </span>
                            <span
                                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-bold"
                                :class="quoteStatusClass(q)"
                            >
                                {{ quoteStatusLabel(q) }}
                            </span>
                        </div>
                        <p class="text-sm font-bold text-slate-700 dark:text-slate-200">
                            {{ q.vehicle_label || '—' }}
                        </p>
                        <div
                            class="flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-slate-400"
                        >
                            <span v-if="q.center_name">{{ q.center_name }}</span>
                            <span v-if="q.created_at">
                                {{ t('customer_portal.portal_quotes.created_at') }}:
                                <span dir="ltr">{{ formatDate(q.created_at) }}</span>
                            </span>
                            <span v-if="q.valid_until">
                                {{
                                    t('customer_portal.portal_quotes.valid_until', {
                                        date: formatDate(q.valid_until),
                                    })
                                }}
                            </span>
                            <span v-if="q.items_count">
                                {{
                                    t('customer_portal.portal_quotes.items_count', {
                                        count: q.items_count,
                                    })
                                }}
                            </span>
                        </div>
                    </div>

                    <div class="flex flex-col items-end gap-2">
                        <SaudiPlateDisplay
                            v-if="q.vehicle_plate"
                            :plate-number="q.vehicle_plate"
                            size="xs"
                        />
                        <p
                            class="text-base font-extrabold text-slate-900 dark:text-white"
                            dir="ltr"
                        >
                            {{ formatMoney(q.total_incl_tax) }}
                            <span class="text-xs font-semibold text-slate-500">
                                {{ q.currency_code }}
                            </span>
                        </p>
                        <span
                            class="inline-flex items-center gap-1 text-xs font-bold"
                            :class="
                                q.can_be_actioned
                                    ? 'text-amber-600 dark:text-amber-400'
                                    : 'text-slate-500 dark:text-slate-400'
                            "
                        >
                            {{ t('customer_portal.portal_quotes.view_details') }}
                            <svg
                                class="w-3.5 h-3.5 rtl:rotate-180 transition group-hover:translate-x-0.5"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M9 5l7 7-7 7"
                                />
                            </svg>
                        </span>
                    </div>
                </div>
            </a>
        </div>
    </CustomerPortalLayout>
</template>

<script setup>
import { computed, ref } from 'vue';
import CustomerPortalLayout from '@/Layouts/CustomerPortalLayout.vue';
import SaudiPlateDisplay from '@/Components/Vehicles/SaudiPlateDisplay.vue';
import AppTabs from '@/Components/Common/AppTabs.vue';
import PortalEmptyState from '@/Components/CustomerPortal/PortalEmptyState.vue';
import { usePortalFormat, quoteStatusClass as statusClass } from '@/Composables/usePortalFormat';

const props = defineProps({
    tenant: { type: Object, required: true },
    locale: { type: Object, default: () => ({ current: 'ar', is_rtl: true }) },
    quotes: { type: Array, default: () => [] },
});

const { t, formatDate, formatMoney } = usePortalFormat(props);

const isApproved = (q) => q.status === 'approved' || q.status === 'converted';

const activeFilter = ref('all');
const filterTabs = computed(() => [
    { id: 'all', label: t('customer_portal.portal_quotes.filter_all'), count: props.quotes.length },
    {
        id: 'pending',
        label: t('customer_portal.portal_quotes.filter_pending'),
        count: props.quotes.filter((q) => q.can_be_actioned).length,
    },
    {
        id: 'approved',
        label: t('customer_portal.portal_quotes.filter_approved'),
        count: props.quotes.filter(isApproved).length,
    },
]);

const filteredQuotes = computed(() => {
    if (activeFilter.value === 'pending') return props.quotes.filter((q) => q.can_be_actioned);
    if (activeFilter.value === 'approved') return props.quotes.filter(isApproved);
    return props.quotes;
});

function quoteStatusLabel(q) {
    const status = q.is_expired && !isApproved(q) && q.status !== 'rejected' ? 'expired' : q.status;
    return t(`customer_portal.portal_quotes.status_${status}`);
}

const quoteStatusClass = (q) => statusClass(q);
</script>
