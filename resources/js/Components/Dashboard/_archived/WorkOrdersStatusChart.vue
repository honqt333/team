<script setup>
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import VueApexCharts from 'vue3-apexcharts';

const { locale } = useI18n();

const props = defineProps({
    data: { type: Object, default: () => ({}) }, // { open: 5, in_progress: 3, ... }
    loading: { type: Boolean, default: false },
});

const isDark = computed(() => document.documentElement.classList.contains('dark'));

const statusConfig = {
    draft: { label_ar: 'مسودة', label_en: 'Draft', color: '#9ca3af' },
    open: { label_ar: 'مفتوح', label_en: 'Open', color: '#6366f1' },
    in_progress: { label_ar: 'قيد التنفيذ', label_en: 'In Progress', color: '#3b82f6' },
    on_hold: { label_ar: 'معلق', label_en: 'On Hold', color: '#f59e0b' },
    ready_for_qc: { label_ar: 'جاهز للفحص', label_en: 'Ready for QC', color: '#8b5cf6' },
    done: { label_ar: 'مكتمل', label_en: 'Done', color: '#10b981' },
    cancelled: { label_ar: 'ملغي', label_en: 'Cancelled', color: '#ef4444' },
};

const isRtl = computed(() => locale.value === 'ar');

const entries = computed(() =>
    Object.entries(props.data)
        .filter(([, count]) => count > 0)
        .map(([status, count]) => ({
            status,
            count,
            config: statusConfig[status] || {
                label_ar: status,
                label_en: status,
                color: '#9ca3af',
            },
        }))
        .sort((a, b) => b.count - a.count)
);

const total = computed(() => entries.value.reduce((s, e) => s + e.count, 0));

const labels = computed(() =>
    entries.value.map((e) => (isRtl.value ? e.config.label_ar : e.config.label_en))
);
const series = computed(() => entries.value.map((e) => e.count));
const colors = computed(() => entries.value.map((e) => e.config.color));

const chartOptions = computed(() => ({
    chart: {
        type: 'donut',
        fontFamily: 'inherit',
        background: 'transparent',
        animations: { enabled: true, speed: 800 },
    },
    labels: labels.value,
    colors: colors.value,
    dataLabels: { enabled: false },
    legend: { show: false },
    plotOptions: {
        pie: {
            donut: {
                size: '72%',
                labels: {
                    show: true,
                    name: {
                        show: true,
                        fontSize: '13px',
                        color: isDark.value ? '#9ca3af' : '#6b7280',
                        offsetY: 4,
                    },
                    value: {
                        show: true,
                        fontSize: '28px',
                        fontWeight: '700',
                        color: isDark.value ? '#f9fafb' : '#111827',
                        offsetY: -4,
                        formatter: (val) => val,
                    },
                    total: {
                        show: true,
                        showAlways: true,
                        label: isRtl.value ? 'الإجمالي' : 'Total',
                        fontSize: '13px',
                        color: isDark.value ? '#9ca3af' : '#6b7280',
                        formatter: () => total.value,
                    },
                },
            },
        },
    },
    stroke: { width: 2, colors: [isDark.value ? '#1f2937' : '#fff'] },
    tooltip: {
        theme: isDark.value ? 'dark' : 'light',
        y: { formatter: (val) => `${val} ${isRtl.value ? 'أمر' : 'Orders'}` },
    },
}));
</script>

<template>
    <div
        class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700/60 shadow-sm p-5"
    >
        <div class="flex items-center justify-between mb-4">
            <div>
                <h3
                    class="text-sm font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider"
                >
                    {{ $t('dashboard.work_orders_status') }}
                </h3>
                <p class="mt-0.5 text-xs text-gray-400 dark:text-gray-500">
                    {{ $t('dashboard.this_month') }}
                </p>
            </div>
            <div
                class="w-8 h-8 rounded-lg bg-indigo-50 dark:bg-indigo-900/30 flex items-center justify-center"
            >
                <svg
                    class="w-4 h-4 text-indigo-600 dark:text-indigo-400"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"
                    />
                </svg>
            </div>
        </div>

        <div v-if="loading" class="flex items-center justify-center h-44">
            <div class="w-32 h-32 rounded-full bg-gray-100 dark:bg-gray-700/50 animate-pulse" />
        </div>

        <template v-else-if="total > 0">
            <div class="flex items-center gap-4">
                <!-- Chart -->
                <div class="flex-shrink-0">
                    <VueApexCharts
                        type="donut"
                        height="180"
                        width="180"
                        :options="chartOptions"
                        :series="series"
                    />
                </div>

                <!-- Legend -->
                <div class="flex-1 space-y-2 min-w-0">
                    <div
                        v-for="entry in entries"
                        :key="entry.status"
                        class="flex items-center justify-between gap-2"
                    >
                        <div class="flex items-center gap-2 min-w-0">
                            <span
                                class="w-2.5 h-2.5 rounded-full flex-shrink-0"
                                :style="{ background: entry.config.color }"
                            />
                            <span class="text-xs text-gray-600 dark:text-gray-400 truncate">
                                {{
                                    $t('work_orders.status.' + entry.status) ||
                                    (locale === 'ar'
                                        ? entry.config.label_ar
                                        : entry.config.label_en)
                                }}
                            </span>
                        </div>
                        <div class="flex items-center gap-2 flex-shrink-0">
                            <span class="text-xs font-semibold text-gray-800 dark:text-gray-200">
                                {{ entry.count }}
                            </span>
                            <span class="text-xs text-gray-400 dark:text-gray-500">
                                ({{ total > 0 ? Math.round((entry.count / total) * 100) : 0 }}%)
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </template>

        <div v-else class="h-44 flex items-center justify-center text-gray-400 dark:text-gray-600">
            <p class="text-sm">{{ $t('dashboard.no_data') }}</p>
        </div>
    </div>
</template>
