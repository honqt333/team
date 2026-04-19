<template>
    <div class="space-y-4">
        <!-- Loading State -->
        <div v-if="loading" class="flex justify-center py-8">
            <svg class="animate-spin w-8 h-8 text-indigo-600" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
            </svg>
        </div>

        <!-- Empty State -->
        <div v-else-if="logs.length === 0" class="text-center py-8 text-gray-500 dark:text-gray-400">
            {{ $t('vehicles.mileage.no_history') }}
        </div>

        <div v-else class="relative overflow-hidden rounded-2xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 shadow-sm">
            <table class="w-full text-sm text-start">
                <thead class="text-xs text-gray-500 uppercase bg-gray-50/50 dark:bg-gray-800/50 dark:text-gray-400 border-b border-gray-200 dark:border-gray-700">
                    <tr>
                        <th class="px-6 py-4 font-semibold text-start">{{ $t('common.date') }}</th>
                        <th class="px-6 py-4 font-semibold text-center">{{ $t('vehicles.mileage.value') }}</th>
                        <th class="px-6 py-4 font-semibold text-center">{{ $t('vehicles.mileage.difference') }}</th>
                        <th class="px-6 py-4 font-semibold text-start">{{ $t('common.source') }}</th>
                        <th class="px-6 py-4 font-semibold text-start">{{ $t('common.user') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    <tr v-for="log in logs" :key="log.id"
                        class="hover:bg-gray-50/50 dark:hover:bg-gray-800/30 transition-colors">
                        <!-- Date -->
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex flex-col">
                                <span class="text-gray-900 dark:text-white font-medium">{{ formatDate(log.recorded_at) }}</span>
                                <span class="text-[10px] text-gray-400 mt-0.5">{{ formatTime(log.recorded_at) }}</span>
                            </div>
                        </td>
                        <!-- Value -->
                        <td class="px-6 py-4 text-center">
                            <span class="font-mono font-bold text-gray-900 dark:text-white text-base">
                                {{ formatNumber(log.mileage) }}
                            </span>
                        </td>
                        <!-- Difference -->
                        <td class="px-6 py-4 text-center">
                            <div class="flex justify-center">
                                <span v-if="log.difference > 0"
                                    class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-bold bg-emerald-50 text-emerald-600 dark:bg-emerald-900/20 dark:text-emerald-400 border border-emerald-100 dark:border-emerald-800/50"
                                    dir="ltr">
                                    +{{ formatNumber(log.difference) }}
                                </span>
                                <span v-else-if="log.difference < 0"
                                    class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-bold bg-red-50 text-red-600 dark:bg-red-900/20 dark:text-red-400 border border-red-100 dark:border-red-800/50"
                                    dir="ltr">
                                    {{ formatNumber(log.difference) }}
                                </span>
                                <span v-else class="text-gray-300">—</span>
                            </div>
                        </td>
                        <!-- Source -->
                        <td class="px-6 py-4">
                            <div v-if="log.reference_code" class="flex flex-col gap-0.5">
                                <span class="font-bold text-indigo-600 dark:text-indigo-400 text-xs">
                                    {{ log.reference_code }}
                                </span>
                                <span class="text-[10px] text-gray-500 bg-gray-100 dark:bg-gray-800 px-1.5 py-0.5 rounded-md self-start">
                                    {{ formatRefType(log.reference_type) }}
                                </span>
                            </div>
                            <div v-else class="flex flex-col">
                                <span v-if="log.previous_mileage === 0" class="inline-flex items-center gap-1.5 text-amber-600 dark:text-amber-400 font-bold text-xs uppercase tracking-wider">
                                    <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                                    {{ $t('vehicles.mileage.initial_log') }}
                                </span>
                                <span v-else class="text-gray-400 text-xs italic">{{ $t('common.manual_update') }}</span>
                            </div>
                        </td>
                        <!-- User -->
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center gap-2">
                                <div class="w-6 h-6 rounded-full bg-gray-100 dark:bg-gray-800 flex items-center justify-center text-[10px] font-bold text-gray-500">
                                    {{ log.creator?.name?.charAt(0).toUpperCase() }}
                                </div>
                                <span class="text-gray-600 dark:text-gray-400 text-xs">{{ log.creator?.name || '-' }}</span>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>

<script setup>
import { ref, watch, onMounted } from 'vue';
import axios from 'axios';
import { useI18n } from 'vue-i18n';
import { useNumberFormat } from '@/Composables/useNumberFormat';

const props = defineProps({
    vehicleId: {
        type: [Number, String],
        required: true,
    },
});

const { t } = useI18n();
const { formatNumber } = useNumberFormat();
const logs = ref([]);
const loading = ref(false);

const formatDate = (dateString) => {
    if (!dateString) return '';
    return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
};

const formatTime = (dateString) => {
    if (!dateString) return '';
    return new Date(dateString).toLocaleTimeString('en-US', {
        hour: '2-digit',
        minute: '2-digit'
    });
};

const formatRefType = (type) => {
    if (!type) return '';
    if (type.includes('WorkOrder')) return t('work_orders.title');
    if (type.includes('Quote')) return t('nav.quotes');
    return type.split('\\').pop();
};

const fetchLogs = async () => {
    if (!props.vehicleId) return;

    loading.value = true;
    try {
        const response = await axios.get(route('vehicles.mileage-logs.index', props.vehicleId));
        logs.value = response.data.data;
    } catch (error) {
        console.error('Failed to fetch mileage logs', error);
    } finally {
        loading.value = false;
    }
};

watch(() => props.vehicleId, (newId) => {
    if (newId) fetchLogs();
}, { immediate: true });
</script>
