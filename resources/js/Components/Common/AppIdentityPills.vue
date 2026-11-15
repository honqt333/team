<template>
    <div v-if="visibleItems.length > 0" class="flex flex-wrap items-center gap-2 mt-1.5 text-xs">
        <template v-for="(item, idx) in visibleItems" :key="item.key || idx">
            <Tooltip v-if="item.tooltip" :content="item.tooltip">
                <span :dir="item.dir || undefined" :class="pillClasses(item)">
                    <slot name="item" :item="item">
                        {{ item.value }}
                    </slot>
                </span>
            </Tooltip>
            <span v-else :dir="item.dir || undefined" :class="pillClasses(item)">
                <slot name="item" :item="item">
                    {{ item.value }}
                </slot>
            </span>
        </template>
    </div>
</template>

<script setup>
import { computed } from 'vue';
import Tooltip from '@/Components/Tooltip.vue';

/**
 * Unified identity/metadata pills for PageHeader #identity slot.
 * Automatically hides empty items and wraps items with Tooltip when `tooltip` is provided.
 */
const props = defineProps({
    items: {
        type: Array,
        default: () => [],
    },
});

const VARIANT_CLASSES = {
    default: 'text-gray-600 dark:text-gray-300 bg-gray-100/70 dark:bg-gray-700/50',
    strong: 'text-gray-800 dark:text-gray-100 bg-gray-100/90 dark:bg-gray-700/70',
    success: 'text-emerald-700 dark:text-emerald-300 bg-emerald-50/70 dark:bg-emerald-900/30',
    primary: 'text-blue-700 dark:text-blue-300 bg-blue-50/70 dark:bg-blue-900/30',
    warning: 'text-amber-700 dark:text-amber-300 bg-amber-50/70 dark:bg-amber-900/30',
    danger: 'text-red-700 dark:text-red-300 bg-red-50/70 dark:bg-red-900/30',
};

const visibleItems = computed(() =>
    (props.items || []).filter(
        (item) => item && item.value !== null && item.value !== undefined && String(item.value).trim() !== ''
    )
);

function pillClasses(item) {
    return [
        'inline-flex items-center gap-1.5 px-2.5 py-1 rounded-xl',
        VARIANT_CLASSES[item.variant] || VARIANT_CLASSES.default,
        item.bold ? 'font-bold' : item.semibold ? 'font-semibold' : '',
        item.mono ? 'font-mono' : '',
    ];
}
</script>
