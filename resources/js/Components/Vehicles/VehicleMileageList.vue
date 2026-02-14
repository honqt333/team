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

        <!-- History List -->
        <div v-else class="relative overflow-x-auto rounded-xl border border-gray-100 dark:border-gray-700">
            <table class="w-full text-sm text-start">
                <thead class="text-xs text-gray-500 uppercase bg-gray-50 dark:bg-gray-800 dark:text-gray-400">
                    <tr>
                        <th class="px-6 py-3 font-medium">{{ $t('common.date') }}</th>
                        <th class="px-6 py-3 font-medium text-center">{{ $t('vehicles.mileage.value') }}</th>
                        <th class="px-6 py-3 font-medium text-center">{{ $t('vehicles.mileage.difference') }}</th>
                        <th class="px-6 py-3 font-medium">{{ $t('common.source') }}</th>
                        <th class="px-6 py-3 font-medium">{{ $t('common.user') }}</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="log in logs" :key="log.id"
                        class="bg-white dark:bg-gray-900 border-b dark:border-gray-700 last:border-0 hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors">
                        <td class="px-6 py-4 whitespace-nowrap text-gray-700 dark:text-gray-300">
                            {{ formatDate(log.recorded_at) }}
                        </td>
                        <td class="px-6 py-4 text-center font-mono font-bold text-gray-900 dark:text-white">
                            {{ formatNumber(log.mileage) }}
                        </td>
                        <td class="px-6 py-4 text-center">
                            <span v-if="log.difference > 0"
                                class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400"
                                dir="ltr">
                                +{{ formatNumber(log.difference) }}
                            </span>
                            <span v-else-if="log.difference < 0"
                                class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400"
                                dir="ltr">
                                {{ formatNumber(log.difference) }}
                            </span>
                            <span v-else class="text-gray-400">-</span>
                        </td>
                        <td class="px-6 py-4">
                            <div v-if="log.reference_code" class="flex flex-col">
                                <span
                                    class="font-medium text-indigo-600 dark:text-indigo-400 break-words max-w-[150px]">
                                    {{ log.reference_code }}
                                </span>
                                <span class="text-xs text-gray-400">
                                    {{ formatRefType(log.reference_type) }}
                                </span>
                            </div>
                            <span v-else class="text-gray-400 text-xs">{{ $t('common.manual_update') }}</span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-gray-600 dark:text-gray-400 text-xs">
                            {{ log.creator?.name || '-' }}
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
import { useNumberFormat } from '@/Composables/useNumberFormat';

const props = defineProps({
    vehicleId: {
        type: [Number, String],
        required: true,
    },
});

const { formatNumber } = useNumberFormat();
const logs = ref([]);
const loading = ref(false);

const formatDate = (dateString) => {
    if (!dateString) return '';
    return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
};

const formatRefType = (type) => {
    if (!type) return '';
    if (type.includes('WorkOrder')) return 'Work Order';
    if (type.includes('Quote')) return 'Quote';
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
