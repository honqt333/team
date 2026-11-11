<script setup>
import SaudiPlateDisplay from './SaudiPlateDisplay.vue';
import { useNumberFormat } from '@/Composables/useNumberFormat';
defineProps({ vehicle: { type: Object, required: true }, blocked: Boolean, customerName: String });
defineEmits(['select']);
const { toEnglish } = useNumberFormat();
</script>

<template>
    <div
        :class="[
            'w-full px-4 py-3 text-start flex items-center gap-3 border-b border-gray-100 dark:border-gray-700 last:border-0',
            blocked
                ? 'bg-red-50 dark:bg-red-900/20 cursor-not-allowed opacity-75'
                : 'hover:bg-gray-50 dark:hover:bg-gray-700 cursor-pointer',
        ]"
        @click="!blocked && $emit('select', vehicle)"
    >
        <div
            :class="[
                'w-10 h-10 rounded-lg flex items-center justify-center',
                blocked ? 'bg-red-200 dark:bg-red-800' : 'bg-gray-200 dark:bg-gray-600',
            ]"
        >
            <svg
                class="w-5 h-5"
                :class="blocked ? 'text-red-600' : 'text-gray-500'"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
            >
                <path
                    v-if="blocked"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"
                />
                <path
                    v-else
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0"
                />
            </svg>
        </div>
        <div class="flex-1">
            <div class="mb-1">
                <SaudiPlateDisplay :plate-number="vehicle.plate_number" size="sm" />
            </div>
            <p class="text-sm text-gray-500 dark:text-gray-400">
                {{ customerName }} -
                {{ toEnglish(vehicle.customer?.phone) }}
            </p>
            <slot />
        </div>
    </div>
</template>
