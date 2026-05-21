<script setup>
import { ref, onMounted, computed, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import VueApexCharts from 'vue3-apexcharts';

const { locale } = useI18n();

const props = defineProps({
    data: { type: Array, default: () => [] }, // [{ date, label, total }]
    currency: { type: String, default: 'SAR' },
    loading: { type: Boolean, default: false },
});

const isDark = computed(() => document.documentElement.classList.contains('dark'));
const isRtl = computed(() => locale.value === 'ar');

const totalRevenue = computed(() => props.data.reduce((s, d) => s + d.total, 0));
const avgDaily = computed(() => props.data.length ? totalRevenue.value / props.data.length : 0);
const maxDay = computed(() => props.data.reduce((m, d) => d.total > m.total ? d : m, { total: 0, label: '' }));

const chartOptions = computed(() => ({
    chart: {
        type: 'area',
        toolbar: { show: false },
        zoom: { enabled: false },
        fontFamily: 'inherit',
        background: 'transparent',
        animations: {
            enabled: true,
            easing: 'easeinout',
            speed: 800,
            animateGradually: { enabled: true, delay: 150 },
            dynamicAnimation: { enabled: true, speed: 350 },
        },
    },
    dataLabels: { enabled: false },
    stroke: {
        curve: 'smooth',
        width: 3,
        colors: ['#6366f1'],
    },
    fill: {
        type: 'gradient',
        gradient: {
            shadeIntensity: 1,
            opacityFrom: 0.45,
            opacityTo: 0.0,
            stops: [0, 100],
            colorStops: [
                { offset: 0, color: '#6366f1', opacity: 0.4 },
                { offset: 100, color: '#6366f1', opacity: 0 },
            ],
        },
    },
    markers: {
        size: 0,
        hover: { size: 5, sizeOffset: 3 },
        strokeColors: '#6366f1',
        strokeWidth: 2,
        fillColor: '#fff',
    },
    tooltip: {
        theme: isDark.value ? 'dark' : 'light',
        y: {
            formatter: (val) => `${val.toLocaleString('en-SA', { minimumFractionDigits: 0, maximumFractionDigits: 0 })} ${props.currency}`,
        },
        x: { show: true },
    },
    grid: {
        borderColor: isDark.value ? '#374151' : '#e5e7eb',
        strokeDashArray: 4,
        padding: { top: 0, right: 10, bottom: 0, left: 0 },
        xaxis: { lines: { show: false } },
        yaxis: { lines: { show: true } },
    },
    xaxis: {
        type: 'category',
        categories: props.data.map(d => d.label),
        axisBorder: { show: false },
        axisTicks: { show: false },
        labels: {
            style: {
                colors: isDark.value ? '#9ca3af' : '#6b7280',
                fontSize: '11px',
            },
            rotate: 0,
            hideOverlappingLabels: true,
            showDuplicates: false,
        },
        tickAmount: 7,
    },
    yaxis: {
        labels: {
            style: {
                colors: isDark.value ? '#9ca3af' : '#6b7280',
                fontSize: '11px',
            },
            formatter: (val) => {
                if (val >= 1000000) return `${(val / 1000000).toFixed(1)}M`;
                if (val >= 1000) return `${(val / 1000).toFixed(0)}K`;
                return val.toFixed(0);
            },
        },
    },
    colors: ['#6366f1'],
}));

const series = computed(() => [{
    name: 'الإيرادات',
    data: props.data.map(d => parseFloat(d.total.toFixed(2))),
}]);

function formatCurrency(val) {
    return new Intl.NumberFormat('en-SA', { style: 'decimal', minimumFractionDigits: 0, maximumFractionDigits: 0 }).format(val);
}
</script>

<template>
    <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700/60 shadow-sm overflow-hidden">
        <!-- Header -->
        <div class="px-5 pt-5 pb-4">
            <div class="flex items-start justify-between">
                <div>
                    <h3 class="text-sm font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                        {{ $t('dashboard.revenue_chart_title') }}
                    </h3>
                    <div class="mt-1 flex items-baseline gap-2">
                        <span class="text-3xl font-bold text-gray-900 dark:text-white">
                            {{ formatCurrency(totalRevenue) }}
                        </span>
                        <span class="text-sm text-gray-500 dark:text-gray-400">{{ currency }}</span>
                    </div>
                    <p class="mt-1 text-xs text-gray-400 dark:text-gray-500">{{ $t('dashboard.last_30_days') }}</p>
                </div>

                <!-- Mini stats -->
                <div class="flex gap-4 text-right">
                    <div class="text-center">
                        <p class="text-xs text-gray-400 dark:text-gray-500">{{ $t('dashboard.daily_avg') }}</p>
                        <p class="text-sm font-semibold text-gray-700 dark:text-gray-300">{{ formatCurrency(avgDaily) }}</p>
                    </div>
                    <div class="text-center">
                        <p class="text-xs text-gray-400 dark:text-gray-500">{{ $t('dashboard.best_day') }}</p>
                        <p class="text-sm font-semibold text-indigo-600 dark:text-indigo-400">{{ maxDay.label }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Chart -->
        <div v-if="loading" class="h-48 mx-4 mb-4 bg-gray-100 dark:bg-gray-700/50 rounded-xl animate-pulse" />
        <template v-else-if="data.length">
            <VueApexCharts
                type="area"
                height="200"
                :options="chartOptions"
                :series="series"
                class="px-2"
            />
        </template>
        <div v-else class="h-48 flex items-center justify-center text-gray-400 dark:text-gray-600">
            <div class="text-center">
                <svg class="w-12 h-12 mx-auto mb-2 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                </svg>
                <p class="text-sm">{{ $t('dashboard.no_data') }}</p>
            </div>
        </div>
    </div>
</template>
