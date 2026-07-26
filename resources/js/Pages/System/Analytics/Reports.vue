<template>
    <SystemLayout>
        <Head :title="isAr ? 'التقارير' : 'Reports'" />

        <div class="space-y-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                    {{ isAr ? 'التقارير' : 'Reports' }}
                </h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    {{
                        isAr
                            ? 'تصدير البيانات للمراجعة المالية أو الامتثال'
                            : 'Export data for finance review or compliance'
                    }}
                </p>
            </div>

            <!-- Three report cards -->
            <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                <ReportCard
                    :title="isAr ? 'إيصالات التحويل البنكي' : 'Bank transfer receipts'"
                    :description="
                        isAr
                            ? 'كل الإيصالات مع تفاصيل المطابقة والمبالغ'
                            : 'All receipts with matching details and amounts'
                    "
                    :icon="iconDocument"
                    :href="'/system/reports/receipts'"
                />
                <ReportCard
                    :title="isAr ? 'قائمة المستأجرين' : 'Tenant roster'"
                    :description="
                        isAr
                            ? 'كل المستأجرين مع حالة الاشتراك والـ MRR'
                            : 'All tenants with subscription status and MRR'
                    "
                    :icon="iconUsers"
                    :href="'/system/reports/tenants'"
                />
                <ReportCard
                    :title="isAr ? 'تقرير أعمار الديون' : 'Aging report'"
                    :description="
                        isAr
                            ? 'الفواتير المتأخرة مجمّعة (30/60/90 يوم)'
                            : 'Overdue invoices bucketed (30/60/90 days)'
                    "
                    :icon="iconClock"
                    :href="'/system/reports/aging'"
                />
            </div>

            <!-- Filters preview (for visual feedback) -->
            <div
                class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800"
            >
                <h2 class="text-sm font-bold uppercase tracking-wider text-gray-500">
                    {{ isAr ? 'حالة التصدير' : 'Export status' }}
                </h2>
                <p class="mt-2 text-sm text-gray-600 dark:text-gray-300">
                    {{
                        isAr
                            ? 'كل التقارير تُصدَّر بصيغة Excel. الفلاتر تُطبَّق تلقائياً من الـ index (status, search) — مثلاً لو كنت في /system/bank-transfer-receipts?status=pending وفتحت التقرير، ستحصل على إيصالات معلقة فقط.'
                            : 'All reports export to Excel. Filters are auto-applied from the index (status, search) — for example, if you were on /system/bank-transfer-receipts?status=pending and clicked the report, you would receive only pending receipts.'
                    }}
                </p>
            </div>
        </div>
    </SystemLayout>
</template>

<script setup>
import { computed } from 'vue';
import { Head } from '@inertiajs/vue3';
import SystemLayout from '@/Layouts/SystemLayout.vue';
import ReportCard from '@/Components/System/ReportCard.vue';

// `isAr` is a `computed` (boolean) — matches the pattern used in
// every other page in the project (TenantTabs,
// BankTransferReceipts/Index, Settings/Billing/Index, etc).
// Reading `document.documentElement.lang` lazily via a computed
// arrow function avoids the "Cannot read properties of undefined"
// crash that happens in setup when the DOM isn't fully ready yet.
// We use `isAr` (not `locale`) because Vue 3 templates auto-unwrap
// refs in direct bindings, but NOT inside `===` expressions — using
// a boolean computed keeps the template clean.
const isAr = computed(() => (document?.documentElement?.lang || 'ar') === 'ar');

// SVG paths inline so the cards can be themed by color.
const iconDocument =
    'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z';
const iconUsers =
    'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z';
const iconClock = 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z';
</script>
