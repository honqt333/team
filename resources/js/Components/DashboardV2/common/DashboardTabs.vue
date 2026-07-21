<script setup>
import { computed } from 'vue';

const props = defineProps({
    modelValue: { type: String, required: true },
    tabs: { type: Array, required: true }, // [{ id, label, icon, badge? }]
});

const emit = defineEmits(['update:modelValue']);

const activeIndex = computed(() => props.tabs.findIndex((t) => t.id === props.modelValue));
</script>

<template>
    <div class="border-b border-slate-200 bg-white dark:border-slate-700 dark:bg-slate-800">
        <nav class="flex gap-0 overflow-x-auto px-4" aria-label="Tabs">
            <button
                v-for="(tab, idx) in tabs"
                :key="tab.id"
                @click="emit('update:modelValue', tab.id)"
                :class="[
                    'group relative flex flex-shrink-0 items-center gap-2 border-b-2 px-4 py-3 text-sm font-medium transition-colors',
                    modelValue === tab.id
                        ? 'border-indigo-600 text-indigo-600 dark:border-indigo-400 dark:text-indigo-300'
                        : 'border-transparent text-slate-500 hover:border-slate-300 hover:text-slate-700 dark:text-slate-400 dark:hover:border-slate-600 dark:hover:text-slate-200',
                ]"
            >
                <span v-if="tab.icon" v-html="tab.icon" class="h-4 w-4"></span>
                <span>{{ tab.label }}</span>
                <span
                    v-if="tab.badge !== undefined && tab.badge !== null && tab.badge > 0"
                    :class="[
                        'ml-1 inline-flex h-5 min-w-[20px] items-center justify-center rounded-full px-1.5 text-xs font-semibold',
                        modelValue === tab.id
                            ? 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/50 dark:text-indigo-200'
                            : 'bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-300',
                    ]"
                >
                    {{ tab.badge > 99 ? '99+' : tab.badge }}
                </span>
            </button>
        </nav>
    </div>
</template>
