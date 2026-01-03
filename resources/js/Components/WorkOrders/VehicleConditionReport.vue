<template>
    <div class="bg-gray-50 dark:bg-gray-900/50 rounded-xl p-4 border border-gray-200 dark:border-gray-700">
        <!-- Color Selector -->
        <div v-if="!readonly" class="flex items-center justify-center gap-3 mb-4">
            <span class="text-sm text-gray-600 dark:text-gray-400">{{ $t('work_orders.condition_report.select_color') }}:</span>
            <button
                v-for="colorOption in colorOptions"
                :key="colorOption.value"
                type="button"
                @click="selectedColor = colorOption.value"
                :class="[
                    'w-8 h-8 rounded-full border-2 transition-all',
                    selectedColor === colorOption.value 
                        ? 'ring-2 ring-offset-2 ring-gray-400 dark:ring-offset-gray-900' 
                        : 'hover:scale-110'
                ]"
                :style="{ backgroundColor: colorOption.color, borderColor: colorOption.border }"
                :title="colorOption.label"
            ></button>
        </div>

        <!-- Fuel Level Gauge (Moved to top) -->
        <div class="mb-4 bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-600">
            <div class="flex items-center justify-between mb-3">
                <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 flex items-center gap-2">
                    <svg class="w-5 h-5 text-amber-500" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M19.77,7.23l.01-.01-3.72-3.72L15,4.56l2.11,2.11c-.94.36-1.61,1.26-1.61,2.33,0,1.38,1.12,2.5,2.5,2.5.36,0,.69-.08,1-.21v7.21c0,.55-.45,1-1,1s-1-.45-1-1V14c0-1.1-.9-2-2-2h-1V5c0-1.1-.9-2-2-2H6C4.9,3,4,3.9,4,5v16h10V13h2.16c.57,0,1.03.46,1.03,1.03v4.19c0,1.84,1.5,3.34,3.34,3.34,1.73,0,3.07-1.45,3.07-3.19V11c0-1.42-.71-2.77-1.83-3.77zM12,10H6V5h6v5zm6,.5c-.55,0-1-.45-1-1s.45-1,1-1,1,.45,1,1-.45,1-1,1z"/>
                    </svg>
                    {{ $t('work_orders.condition_report.fuel_level') }}
                </h4>
                <span class="text-sm font-medium" :class="fuelLevelColor">{{ fuelLevelText }}</span>
            </div>
            
            <!-- Fuel Gauge Visual -->
            <div class="relative">
                <!-- Background Track -->
                <div class="h-10 rounded-full bg-gray-100 dark:bg-gray-700 flex items-center overflow-hidden relative">
                    <!-- Fuel Level Fill -->
                    <div 
                        class="absolute inset-y-0 start-0 rounded-full transition-all duration-500 ease-out"
                        :style="{ width: `${fuelLevel}%` }"
                        :class="fuelLevelBgClass"
                    ></div>
                    
                    <!-- Segment Dividers -->
                    <div class="absolute inset-0 flex">
                        <div v-for="i in 7" :key="i" class="flex-1 border-e border-white/30 dark:border-gray-600/30 last:border-e-0"></div>
                    </div>
                    
                    <!-- Labels on gauge -->
                    <div class="absolute inset-0 flex items-center justify-between px-4 text-xs font-bold">
                        <span class="text-red-600 dark:text-red-400 drop-shadow-sm">E</span>
                        <span class="text-gray-400">1/4</span>
                        <span class="text-gray-400">1/2</span>
                        <span class="text-gray-400">3/4</span>
                        <span class="text-green-600 dark:text-green-400 drop-shadow-sm">F</span>
                    </div>
                </div>
                
                <!-- Level Selector Buttons -->
                <div v-if="!readonly" class="flex justify-between mt-2">
                    <button
                        v-for="level in fuelLevels"
                        :key="level.value"
                        type="button"
                        @click="fuelLevel = level.value"
                        :class="[
                            'px-3 py-1.5 text-xs font-medium rounded-lg transition-all',
                            fuelLevel === level.value
                                ? 'bg-indigo-600 text-white shadow-lg scale-105'
                                : 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-600'
                        ]"
                    >
                        {{ level.label }}
                    </button>
                </div>
            </div>
        </div>

        <!-- Vehicle Diagram -->
        <div class="relative bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-600">
            <svg
                ref="vehicleSvg"
                viewBox="0 0 400 300"
                :class="['w-full h-auto', readonly ? 'cursor-default' : 'cursor-crosshair']"
                @click="!readonly && addMark($event)"
            >
                <!-- Background Image -->
                <image
                    href="/images/vehicle-diagram.png?v=5"
                    x="0"
                    y="0"
                    width="400"
                    height="300"
                    preserveAspectRatio="xMidYMid meet"
                    class="transition-all mix-blend-multiply dark:mix-blend-screen dark:filter dark:invert dark:opacity-90"
                />
                
                <!-- Damage Marks -->
                <g v-for="(mark, index) in damageMarks" :key="index">
                    <circle
                        :cx="mark.x"
                        :cy="mark.y"
                        r="12"
                        :fill="getColorValue(mark.color)"
                        class="cursor-pointer hover:opacity-80 transition-opacity"
                        @click.stop
                    />
                    <text
                        :x="mark.x"
                        :y="mark.y + 4"
                        text-anchor="middle"
                        class="text-xs font-bold fill-white pointer-events-none"
                    >{{ index + 1 }}</text>
                </g>
            </svg>

            <p v-if="!readonly" class="text-center text-sm text-gray-500 dark:text-gray-400 mt-2">
                {{ $t('work_orders.condition_report.click_to_mark') }}
            </p>
        </div>

        <!-- Legend -->
        <div class="flex items-center justify-center gap-6 mt-4 text-sm">
            <div class="flex items-center gap-2">
                <span class="w-4 h-4 rounded-full bg-red-500"></span>
                <span class="text-gray-600 dark:text-gray-400">{{ $t('work_orders.condition_report.legend.damage') }}</span>
            </div>
            <div class="flex items-center gap-2">
                <span class="w-4 h-4 rounded-full bg-blue-500"></span>
                <span class="text-gray-600 dark:text-gray-400">{{ $t('work_orders.condition_report.legend.scratch') }}</span>
            </div>
            <div class="flex items-center gap-2">
                <span class="w-4 h-4 rounded-full bg-gray-500"></span>
                <span class="text-gray-600 dark:text-gray-400">{{ $t('work_orders.condition_report.legend.note') }}</span>
            </div>
        </div>

        <!-- Marks Description List -->
        <div v-if="damageMarks.length > 0" class="mt-4 space-y-2">
            <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                {{ $t('work_orders.condition_report.marks_list') }}
            </h4>
            <div
                v-for="(mark, index) in damageMarks"
                :key="index"
                class="flex items-center gap-3 bg-white dark:bg-gray-800 rounded-lg p-3 border border-gray-200 dark:border-gray-600"
            >
                <span
                    class="w-7 h-7 rounded-full flex items-center justify-center text-sm font-bold text-white flex-shrink-0"
                    :style="{ backgroundColor: getColorValue(mark.color) }"
                >{{ index + 1 }}</span>
                <input
                    v-model="mark.description"
                    type="text"
                    :disabled="readonly"
                    :placeholder="$t('work_orders.condition_report.description_placeholder')"
                    class="flex-1 px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 focus:ring-1 focus:ring-indigo-500 disabled:opacity-60 disabled:bg-gray-50 dark:disabled:bg-gray-900"
                />
                <button
                    v-if="!readonly"
                    type="button"
                    @click="removeMark(index)"
                    class="p-2 text-red-500 hover:bg-red-100 dark:hover:bg-red-900/30 rounded-lg transition-colors"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                    </svg>
                </button>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import { useI18n } from 'vue-i18n';

const props = defineProps({
    readonly: Boolean,
});

const { t } = useI18n();

const damageMarks = defineModel('damageMarks', { default: () => [] });
const fuelLevel = defineModel('fuelLevel', { default: 50 });

const vehicleSvg = ref(null);
const selectedColor = ref('red');

const colorOptions = [
    { value: 'red', color: '#ef4444', border: '#dc2626', label: t('work_orders.condition_report.legend.damage') },
    { value: 'blue', color: '#3b82f6', border: '#2563eb', label: t('work_orders.condition_report.legend.scratch') },
    { value: 'gray', color: '#6b7280', border: '#4b5563', label: t('work_orders.condition_report.legend.note') },
];

// Fuel level options
const fuelLevels = [
    { value: 0, label: 'E' },
    { value: 12.5, label: '1/8' },
    { value: 25, label: '1/4' },
    { value: 37.5, label: '3/8' },
    { value: 50, label: '1/2' },
    { value: 62.5, label: '5/8' },
    { value: 75, label: '3/4' },
    { value: 87.5, label: '7/8' },
    { value: 100, label: 'F' },
];

// Fuel level computed properties
const fuelLevelText = computed(() => {
    const level = fuelLevels.find(l => l.value === fuelLevel.value);
    return level?.label || '1/2';
});

const fuelLevelColor = computed(() => {
    if (fuelLevel.value <= 12.5) return 'text-red-600 dark:text-red-400';
    if (fuelLevel.value <= 25) return 'text-orange-500 dark:text-orange-400';
    if (fuelLevel.value <= 50) return 'text-yellow-500 dark:text-yellow-400';
    return 'text-green-600 dark:text-green-400';
});

const fuelLevelBgClass = computed(() => {
    if (fuelLevel.value <= 12.5) return 'bg-gradient-to-r from-red-500 to-red-400';
    if (fuelLevel.value <= 25) return 'bg-gradient-to-r from-red-500 via-orange-500 to-orange-400';
    if (fuelLevel.value <= 50) return 'bg-gradient-to-r from-red-500 via-orange-500 to-yellow-400';
    if (fuelLevel.value <= 75) return 'bg-gradient-to-r from-red-500 via-yellow-500 to-lime-400';
    return 'bg-gradient-to-r from-red-500 via-yellow-500 to-green-400';
});

function getColorValue(color) {
    const option = colorOptions.find(c => c.value === color);
    return option?.color || '#ef4444';
}

function addMark(event) {
    if (!vehicleSvg.value) return;
    
    const svg = vehicleSvg.value;
    const rect = svg.getBoundingClientRect();
    const viewBox = svg.viewBox.baseVal;
    
    // Calculate the position relative to viewBox
    const x = ((event.clientX - rect.left) / rect.width) * viewBox.width;
    const y = ((event.clientY - rect.top) / rect.height) * viewBox.height;
    
    damageMarks.value.push({
        x: Math.round(x),
        y: Math.round(y),
        color: selectedColor.value,
        description: '',
    });
}

function removeMark(index) {
    damageMarks.value.splice(index, 1);
}
</script>
