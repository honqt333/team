<script setup>
import {
    computed,
    getCurrentInstance,
    nextTick,
    onBeforeUnmount,
    onMounted,
    ref,
    useAttrs,
    watch,
} from 'vue';
import { registerModal, unregisterModal, isTopModal } from '@/Support/modalStack';

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    maxWidth: {
        type: String,
        default: '2xl',
    },
    // Keep custom form shells while sharing native dialog behavior.
    unstyled: { type: Boolean, default: false },
    closeable: {
        type: Boolean,
        default: true,
    },
});

const emit = defineEmits(['close']);
const attrs = useAttrs();
const dialog = ref();
const showSlot = ref(props.show);

let closeTimer;
const instanceId = getCurrentInstance().uid;
const generatedLabel = ref(null);

function close() {
    if (props.closeable) emit('close');
}

function finishClose() {
    dialog.value?.close();
    unregisterModal(dialog.value);
    showSlot.value = false;
}

async function openDialog() {
    clearTimeout(closeTimer);
    const opener = document.activeElement;
    showSlot.value = true;
    await nextTick();
    if (!props.show || !dialog.value) return;
    const element = dialog.value;
    // Vue mounts children first. Let a simultaneously opening ancestor reach
    // the top layer before opening its nested dialog above it.
    const ancestor = element.parentElement?.closest('dialog');
    if (ancestor && !ancestor.open) {
        await nextTick();
        if (!props.show || !dialog.value || !ancestor.open) return;
    }
    registerModal(element, opener);
    if (!element.open) element.showModal();
    if (!attrs['aria-label'] && !attrs['aria-labelledby']) {
        const heading = element.querySelector('h1, h2, h3, [data-modal-title]');
        if (heading) {
            heading.id = `dialog-title-${instanceId}`;
            generatedLabel.value = heading.id;
        }
    }
    // The browser traps focus for showModal(); explicitly choose initial focus
    // so dialogs mounted already open behave consistently as well.
    const focusable = focusableElements();
    const target =
        focusable.find((control) => control.hasAttribute('autofocus')) || focusable[0] || element;
    target.focus();
}

function focusableElements() {
    return [
        ...(dialog.value?.querySelectorAll(
            'a[href], button, input:not([type="hidden"]), select, textarea, [tabindex]'
        ) || []),
    ].filter(
        (element) =>
            element.tabIndex >= 0 &&
            !element.matches(':disabled') &&
            !element.closest('[hidden], [inert], [aria-hidden="true"]') &&
            isVisible(element)
    );
}

function isVisible(element) {
    for (
        let current = element;
        current && current !== dialog.value;
        current = current.parentElement
    ) {
        const style = getComputedStyle(current);
        if (style.display === 'none' || style.visibility === 'hidden') return false;
    }
    return true;
}

function onKeydown(event) {
    if (!props.show || !isTopModal(dialog.value) || event.defaultPrevented || event.isComposing)
        return;
    if (event.key === 'Escape') {
        event.preventDefault();
        close();
    } else if (event.key === 'Tab') {
        const elements = focusableElements();
        const first = elements[0];
        const last = elements.at(-1);
        if (!first) {
            event.preventDefault();
            dialog.value.focus();
        } else if (
            event.shiftKey &&
            (document.activeElement === first || !elements.includes(document.activeElement))
        ) {
            event.preventDefault();
            last.focus();
        } else if (
            !event.shiftKey &&
            (document.activeElement === last || !elements.includes(document.activeElement))
        ) {
            event.preventDefault();
            first.focus();
        }
    }
}

function onCancel(event) {
    event.preventDefault();
    if (props.show && isTopModal(dialog.value)) close();
}

watch(
    () => props.show,
    (value) => {
        clearTimeout(closeTimer);
        if (value) openDialog();
        else closeTimer = setTimeout(finishClose, 200);
    }
);

onMounted(() => {
    document.addEventListener('keydown', onKeydown);
    if (props.show) openDialog();
});

onBeforeUnmount(() => {
    clearTimeout(closeTimer);
    document.removeEventListener('keydown', onKeydown);
    finishClose();
});

const maxWidthClass = computed(() => {
    return {
        sm: 'sm:max-w-sm',
        md: 'sm:max-w-md',
        lg: 'sm:max-w-lg',
        xl: 'sm:max-w-xl',
        '2xl': 'sm:max-w-2xl',
        '3xl': 'sm:max-w-3xl',
        '4xl': 'sm:max-w-4xl',
    }[props.maxWidth];
});
</script>

<template>
    <dialog
        class="z-50 m-0 min-h-full min-w-full overflow-y-auto border-0 p-0 bg-transparent text-inherit backdrop:bg-transparent"
        ref="dialog"
        tabindex="-1"
        aria-modal="true"
        :aria-labelledby="attrs['aria-labelledby'] || generatedLabel || undefined"
        @cancel="onCancel"
    >
        <slot v-if="unstyled && showSlot" />
        <div
            v-else-if="!unstyled"
            class="fixed inset-0 z-50 overflow-y-auto px-4 py-6 sm:px-0"
            scroll-region
        >
            <Transition
                enter-active-class="ease-out duration-300"
                enter-from-class="opacity-0"
                enter-to-class="opacity-100"
                leave-active-class="ease-in duration-200"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div v-show="show" class="fixed inset-0 transform transition-all" @click="close">
                    <div
                        class="absolute inset-0 bg-gray-500 dark:bg-gray-900 opacity-75 dark:opacity-80"
                    />
                </div>
            </Transition>

            <Transition
                enter-active-class="ease-out duration-300"
                enter-from-class="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                enter-to-class="opacity-100 translate-y-0 sm:scale-100"
                leave-active-class="ease-in duration-200"
                leave-from-class="opacity-100 translate-y-0 sm:scale-100"
                leave-to-class="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            >
                <div
                    v-show="show"
                    class="mb-6 transform rounded-lg bg-white dark:bg-gray-800 shadow-xl transition-all sm:mx-auto sm:w-full"
                    :class="maxWidthClass"
                >
                    <slot v-if="showSlot" />
                </div>
            </Transition>
        </div>
    </dialog>
</template>
