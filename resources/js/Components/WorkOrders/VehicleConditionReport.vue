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
import { ref } from 'vue';
import { useI18n } from 'vue-i18n';

const props = defineProps({
    readonly: Boolean,
});

const { t } = useI18n();

const damageMarks = defineModel('damageMarks', { default: () => [] });

const vehicleSvg = ref(null);
const selectedColor = ref('red');

const colorOptions = [
    { value: 'red', color: '#ef4444', border: '#dc2626', label: t('work_orders.condition_report.legend.damage') },
    { value: 'blue', color: '#3b82f6', border: '#2563eb', label: t('work_orders.condition_report.legend.scratch') },
    { value: 'gray', color: '#6b7280', border: '#4b5563', label: t('work_orders.condition_report.legend.note') },
];

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
