<script setup>
import { ref, watch, computed } from 'vue';
import { useI18n } from 'vue-i18n';
import draggable from 'vuedraggable';

const { locale } = useI18n();
const isRtl = computed(() => locale.value === 'ar');

const props = defineProps({
    widgets: { type: Array, required: true },
    modelValue: { type: Boolean, default: false },
});

const emit = defineEmits(['update:modelValue', 'update:widgets', 'reset-defaults']);

const localWidgets = ref([]);
const draggedIndex = ref(null);

watch(() => props.widgets, (val) => {
    localWidgets.value = val.map(w => ({ ...w }));
}, { immediate: true, deep: true });

function onDragStart(event, index) {
    draggedIndex.value = index;
    event.dataTransfer.effectAllowed = 'move';
    // Required for Firefox
    if (event.dataTransfer.setData) {
        event.dataTransfer.setData('text/plain', index);
    }
}

function onDragEnter(event, index) {
    if (draggedIndex.value !== null && draggedIndex.value !== index) {
        const item = localWidgets.value.splice(draggedIndex.value, 1)[0];
        localWidgets.value.splice(index, 0, item);
        draggedIndex.value = index;
    }
}

function onDragEnd() {
    draggedIndex.value = null;
}

function toggle(widget) {
    widget.visible = !widget.visible;
}

function save() {
    emit('update:widgets', localWidgets.value.map(w => ({ ...w })));
    emit('update:modelValue', false);
}

function reset() {
    emit('reset-defaults');
}

function close() {
    localWidgets.value = props.widgets.map(w => ({ ...w }));
    emit('update:modelValue', false);
}
</script>

<template>
    <Teleport to="body">
        <Transition name="fade-backdrop">
            <div
                v-if="modelValue"
                class="fixed inset-0 z-[9999] flex"
                :dir="isRtl ? 'rtl' : 'ltr'"
            >
                <!-- Backdrop -->
                <div
                    class="absolute inset-0 bg-black/50 backdrop-blur-sm"
                    @click="close"
                />

                <!-- Panel — always slides from the END of reading direction -->
                <Transition :name="isRtl ? 'slide-rtl' : 'slide-ltr'">
                    <div
                        v-if="modelValue"
                        :class="[
                            'absolute top-0 bottom-0 w-80 bg-white dark:bg-gray-900 shadow-2xl flex flex-col z-10',
                            isRtl
                                ? 'left-0 border-r border-gray-200 dark:border-gray-700'
                                : 'right-0 border-l border-gray-200 dark:border-gray-700'
                        ]"
                    >
                        <!-- Header -->
                        <div class="flex items-center justify-between px-5 py-4 border-b border-gray-200 dark:border-gray-700">
                            <div>
                                <h2 class="text-base font-bold text-gray-900 dark:text-white">
                                    {{ $t('dashboard.customize') }}
                                </h2>
                                <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">
                                    {{ $t('dashboard.customize_hint') }}
                                </p>
                            </div>
                            <button
                                @click="close"
                                class="w-8 h-8 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800 flex items-center justify-center text-gray-500 transition-colors flex-shrink-0"
                            >
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>

                        <!-- Draggable list -->
                        <div class="flex-1 overflow-y-auto p-4">
                            <p class="text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-3">
                                {{ $t('dashboard.drag_to_reorder') }}
                            </p>
                            <TransitionGroup name="list" tag="div" class="space-y-2 relative">
                                <div
                                    v-for="(element, index) in localWidgets"
                                    :key="element.id"
                                    draggable="true"
                                    @dragstart="onDragStart($event, index)"
                                    @dragover.prevent
                                    @dragenter.prevent="onDragEnter($event, index)"
                                    @dragend="onDragEnd"
                                    @drop.prevent="onDragEnd"
                                    :class="[
                                        'flex items-center gap-3 p-3 rounded-xl mb-2 select-none cursor-grab active:cursor-grabbing',
                                        draggedIndex === index ? 'opacity-30 ring-2 ring-indigo-400 shadow-lg scale-[1.02] bg-white dark:bg-gray-800' : 'transition-colors duration-200',
                                        element.visible && draggedIndex !== index
                                            ? 'bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 shadow-sm'
                                            : (draggedIndex !== index ? 'bg-gray-50 dark:bg-gray-800/40 border border-dashed border-gray-200 dark:border-gray-700/50 opacity-50' : '')
                                    ]"
                                >
                                    <!-- Drag handle icon -->
                                    <div class="flex-shrink-0 text-gray-300 dark:text-gray-600 p-1 pointer-events-none">
                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                            <circle cx="7" cy="4" r="1.5"/>
                                            <circle cx="13" cy="4" r="1.5"/>
                                            <circle cx="7" cy="10" r="1.5"/>
                                            <circle cx="13" cy="10" r="1.5"/>
                                            <circle cx="7" cy="16" r="1.5"/>
                                            <circle cx="13" cy="16" r="1.5"/>
                                        </svg>
                                    </div>

                                    <!-- Icon -->
                                    <div class="w-7 h-7 rounded-lg bg-gray-100 dark:bg-gray-700 flex items-center justify-center flex-shrink-0 pointer-events-none">
                                        <span v-safe-html="element.icon" class="text-gray-500 dark:text-gray-400 [&>svg]:w-3.5 [&>svg]:h-3.5" />
                                    </div>

                                    <!-- Label -->
                                    <span class="flex-1 text-sm font-medium text-gray-700 dark:text-gray-300 truncate pointer-events-none">
                                        {{ element.label }}
                                    </span>

                                    <!-- Toggle switch -->
                                    <button
                                        type="button"
                                        @click.stop="toggle(element)"
                                        :class="[
                                            'relative inline-flex h-5 w-9 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent',
                                            'transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-1',
                                            element.visible ? 'bg-indigo-600' : 'bg-gray-200 dark:bg-gray-600'
                                        ]"
                                    >
                                        <span
                                            :class="[
                                                'pointer-events-none inline-block h-4 w-4 rounded-full bg-white shadow ring-0 transition-transform duration-200 ease-in-out',
                                                element.visible ? 'translate-x-4 rtl:-translate-x-4' : 'translate-x-0'
                                            ]"
                                        />
                                    </button>
                                </div>
                            </TransitionGroup>
                        </div>

                        <!-- Footer -->
                        <div class="px-4 py-4 border-t border-gray-200 dark:border-gray-700 flex gap-2">
                            <button
                                @click="reset"
                                class="flex-1 py-2.5 text-sm font-medium text-gray-600 dark:text-gray-400 rounded-xl border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors"
                            >
                                {{ $t('dashboard.reset') }}
                            </button>
                            <button
                                @click="save"
                                class="flex-1 py-2.5 text-sm font-medium text-white rounded-xl bg-gradient-to-r from-indigo-600 to-indigo-700 hover:from-indigo-700 hover:to-indigo-800 shadow-sm shadow-indigo-500/30 transition-all"
                            >
                                {{ $t('dashboard.save_layout') }}
                            </button>
                        </div>
                    </div>
                </Transition>
            </div>
        </Transition>
    </Teleport>
</template>

<style scoped>
/* Backdrop fade */
.fade-backdrop-enter-active,
.fade-backdrop-leave-active {
    transition: opacity 0.25s ease;
}
.fade-backdrop-enter-from,
.fade-backdrop-leave-to {
    opacity: 0;
}

/* LTR — slides from right */
.slide-ltr-enter-active,
.slide-ltr-leave-active {
    transition: transform 0.3s cubic-bezier(0.32, 0.72, 0, 1);
}
.slide-ltr-enter-from,
.slide-ltr-leave-to {
    transform: translateX(100%);
}

/* RTL — slides from left */
.slide-rtl-enter-active,
.slide-rtl-leave-active {
    transition: transform 0.3s cubic-bezier(0.32, 0.72, 0, 1);
}
.slide-rtl-enter-from,
.slide-rtl-leave-to {
    transform: translateX(-100%);
}

/* List drag reorder animation */
.list-move,
.list-enter-active,
.list-leave-active {
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}
.list-enter-from,
.list-leave-to {
    opacity: 0;
    transform: scale(0.9);
}
.list-leave-active {
    position: absolute;
}
</style>
