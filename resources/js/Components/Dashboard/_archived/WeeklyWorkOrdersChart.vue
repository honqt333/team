<script setup>
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import VueApexCharts from 'vue3-apexcharts';

const { locale } = useI18n();
const isDark = computed(() => document.documentElement.classList.contains('dark'));
const isRtl = computed(() => locale.value === 'ar');

const props = defineProps({
    data: { type: Array, default: () => [] }, // [{ date, label, count }]
    loading: { type: Boolean, default: false },
});

const total = computed(() => props.data.reduce((s, d) => s + d.count, 0));
const max = computed(() => Math.max(...props.data.map((d) => d.count), 1));

const chartOptions = computed(() => ({
    chart: {
        type: 'bar',
        toolbar: { show: false },
        fontFamily: 'inherit',
        background: 'transparent',
        animations: { enabled: true, speed: 600 },
    },
    plotOptions: {
        bar: {
            borderRadius: 6,
            columnWidth: '55%',
            distributed: false,
        },
    },
    dataLabels: { enabled: false },
    stroke: { show: false },
    colors: ['#6366f1'],
    fill: {
        type: 'gradient',
        gradient: {
            shade: 'light',
            type: 'vertical',
            gradientToColors: ['#818cf8'],
            opacityFrom: 1,
            opacityTo: 0.6,
        },
    },
    grid: {
        borderColor: isDark.value ? '#374151' : '#e5e7eb',
        strokeDashArray: 4,
        xaxis: { lines: { show: false } },
        yaxis: { lines: { show: true } },
        padding: { top: 0, right: 0, bottom: 0, left: 0 },
    },
    xaxis: {
        categories: props.data.map((d) => d.label),
        axisBorder: { show: false },
        axisTicks: { show: false },
        labels: {
            style: { colors: isDark.value ? '#9ca3af' : '#6b7280', fontSize: '11px' },
        },
    },
    yaxis: {
        min: 0,
        tickAmount: 4,
        labels: {
            style: { colors: isDark.value ? '#9ca3af' : '#6b7280', fontSize: '11px' },
            formatter: (val) => Math.round(val),
        },
    },
    tooltip: {
        theme: isDark.value ? 'dark' : 'light',
        y: {
            formatter: (val) => `${val} ${isRtl.value ? 'أمر عمل' : 'Work Orders'}`,
        },
    },
}));

const series = computed(() => [
    { name: isRtl.value ? 'أوامر العمل' : 'Work Orders', data: props.data.map((d) => d.count) },
]);
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
                    {{ $t('dashboard.weekly_work_orders') }}
                </h3>
                <p class="mt-0.5 text-xs text-gray-400 dark:text-gray-500">
                    {{ $t('dashboard.last_7_days') }}
                </p>
            </div>
            <div class="flex items-center gap-2">
                <span class="text-2xl font-bold text-gray-900 dark:text-white">{{ total }}</span>
                <span class="text-xs text-gray-400 dark:text-gray-500">
                    {{ $t('dashboard.total') }}
                </span>
            </div>
        </div>

        <div v-if="loading" class="h-36 bg-gray-100 dark:bg-gray-700/50 rounded-xl animate-pulse" />
        <VueApexCharts
            v-else-if="data.length"
            type="bar"
            height="150"
            :options="chartOptions"
            :series="series"
            class="-mx-2"
        />
        <div v-else class="h-36 flex items-center justify-center text-gray-400 dark:text-gray-600">
            <p class="text-sm">{{ $t('dashboard.no_data') }}</p>
        </div>
    </div>
</template>
