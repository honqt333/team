<template>
    <SystemLayout>
        <Head :title="$t('system.nav_items.reports')" />

        <div class="space-y-6">
            <!-- Header -->
            <PageHeader
                :title="$t('system.nav_items.reports')"
                :subtitle="$t('system.analytics.reports_subtitle')"
            >
                <template #icon>
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                        />
                    </svg>
                </template>
            </PageHeader>

            <!-- Report cards -->
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
                <div
                    v-for="report in localizedReports"
                    :key="report.routeName"
                    class="flex flex-col rounded-xl border border-gray-200 bg-white p-5 transition-shadow hover:shadow-sm dark:border-gray-700 dark:bg-gray-800"
                >
                    <div
                        class="mb-3 flex h-10 w-10 items-center justify-center rounded-lg"
                        :class="report.iconBg"
                    >
                        <svg
                            class="h-5 w-5"
                            :class="report.iconColor"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                :d="report.iconPath"
                            />
                        </svg>
                    </div>

                    <h2 class="text-base font-semibold text-gray-900 dark:text-white">
                        {{ report.title }}
                    </h2>
                    <p class="mt-1 flex-1 text-sm text-gray-500 dark:text-gray-400">
                        {{ report.description }}
                    </p>

                    <button
                        type="button"
                        class="mt-4 inline-flex items-center justify-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800"
                        @click="download(report)"
                    >
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M12 10v6m0 0l-3-3m3 3l3-3M4 7v10a2 2 0 002 2h12a2 2 0 002-2V9a2 2 0 00-2-2h-1M8 5h8a2 2 0 012 2v1"
                            />
                        </svg>
                        {{ $t('common.export') }}
                    </button>
                </div>
            </div>
        </div>
    </SystemLayout>
</template>

<script setup>
const { t } = useI18n();

import { computed } from 'vue';
import { Head } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import SystemLayout from '@/Layouts/SystemLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';

// The reactive i18n locale is the source of truth. Reading
// `document.documentElement.lang` is unreliable: that attribute is
// only rewritten when setLocale() runs, so it can disagree with the
// locale the UI is actually rendering in.
const { locale } = useI18n();

const reports = [
    {
        routeName: 'system.reports.receipts',
        title: t('system.nav_items.bank_receipts'),
        titleEn: 'Bank transfer receipts',
        description: t('system.analytics.bank_transfer_receipts_desc'),
        descriptionEn: 'Full log of bank transfer receipts with amounts and statuses.',
        iconBg: 'bg-emerald-100 dark:bg-emerald-900/40',
        iconColor: 'text-emerald-600 dark:text-emerald-400',
        iconPath:
            'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
    },
    {
        routeName: 'system.reports.tenants',
        title: t('system.analytics.tenants_report'),
        titleEn: 'Tenant report',
        description: t('system.analytics.tenants_report_desc'),
        descriptionEn: 'List of tenants with their plans, subscriptions and subscription status.',
        iconBg: 'bg-indigo-100 dark:bg-indigo-900/40',
        iconColor: 'text-indigo-600 dark:text-indigo-400',
        iconPath:
            'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z',
    },
    {
        routeName: 'system.reports.aging',
        title: t('system.analytics.aging_report'),
        titleEn: 'Aging report',
        description: t('system.analytics.aging_report_desc'),
        descriptionEn: 'Outstanding receivables broken down by days overdue.',
        iconBg: 'bg-amber-100 dark:bg-amber-900/40',
        iconColor: 'text-amber-600 dark:text-amber-400',
        iconPath: 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z',
    },
];

// The report titles are plain data (not user-facing copy that
// changes per tenant), so they are held as a bilingual pair and
// resolved against the current locale at render time.
const localizedReports = computed(() =>
    reports.map((report) => ({
        ...report,
        title: locale.value === 'ar' ? report.title : report.titleEn,
        description: locale.value === 'ar' ? report.description : report.descriptionEn,
    }))
);

// The export endpoints stream a file directly (Excel::download), so
// the browser has to navigate to the URL rather than fetch it via
// XHR — this matches the pattern used by the other export buttons
// in the app (e.g. Customers/Index.vue and Quotes/Index.vue).
function download(report) {
    window.location.href = route(report.routeName);
}
</script>
