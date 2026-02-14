<template>
    <div class="inline-flex" @mouseenter="show" @mouseleave="hide" ref="trigger">
        <slot />

        <Teleport to="body">
            <Transition enter-active-class="transition ease-out duration-200" enter-from-class="opacity-0 translate-y-1"
                enter-to-class="opacity-100 translate-y-0" leave-active-class="transition ease-in duration-150"
                leave-from-class="opacity-100 translate-y-0" leave-to-class="opacity-0 translate-y-1">
                <div v-if="isVisible" ref="tooltip" :style="tooltipStyle"
                    class="fixed z-[9999] px-3 py-2 text-sm font-medium text-white bg-gray-900 rounded-lg shadow-xl whitespace-nowrap pointer-events-none">
                    {{ text }}
                    <!-- Arrow -->
                    <div :class="['absolute w-2 h-2 bg-gray-900 rotate-45', arrowClasses]"></div>
                </div>
            </Transition>
        </Teleport>
    </div>
</template>

<script setup>
import { ref, computed, nextTick } from 'vue';

const props = defineProps({
    text: {
        type: String,
        required: true
    },
    position: {
        type: String,
        default: 'top',
        validator: (value) => ['top', 'bottom', 'left', 'right'].includes(value)
    }
});

const isVisible = ref(false);
const trigger = ref(null);
const tooltip = ref(null);
const position = ref({ top: 0, left: 0 });

const show = async () => {
    isVisible.value = true;
    await nextTick();
    updatePosition();
};

const hide = () => {
    isVisible.value = false;
};

const updatePosition = () => {
    if (!trigger.value || !tooltip.value) return;

    const triggerRect = trigger.value.getBoundingClientRect();
    const tooltipRect = tooltip.value.getBoundingClientRect();
    const spacing = 8; // Space between trigger and tooltip

    let top = 0;
    let left = 0;

    switch (props.position) {
        case 'top':
            top = triggerRect.top - tooltipRect.height - spacing;
            left = triggerRect.left + (triggerRect.width - tooltipRect.width) / 2;
            break;
        case 'bottom':
            top = triggerRect.bottom + spacing;
            left = triggerRect.left + (triggerRect.width - tooltipRect.width) / 2;
            break;
        case 'left':
            top = triggerRect.top + (triggerRect.height - tooltipRect.height) / 2;
            left = triggerRect.left - tooltipRect.width - spacing;
            break;
        case 'right':
            top = triggerRect.top + (triggerRect.height - tooltipRect.height) / 2;
            left = triggerRect.right + spacing;
            break;
    }

    position.value = { top, left };
};

const tooltipStyle = computed(() => ({
    top: `${position.value.top}px`,
    left: `${position.value.left}px`
}));

const arrowClasses = computed(() => {
    switch (props.position) {
        case 'top': return 'top-full left-1/2 -translate-x-1/2 -mt-1';
        case 'bottom': return 'bottom-full left-1/2 -translate-x-1/2 -mb-1';
        case 'left': return 'left-full top-1/2 -translate-y-1/2 -ms-1';
        case 'right': return 'right-full top-1/2 -translate-y-1/2 -me-1';
        default: return 'top-full left-1/2 -translate-x-1/2 -mt-1';
    }
});
</script>
