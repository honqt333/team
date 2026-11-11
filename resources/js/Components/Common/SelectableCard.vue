<script setup>
import { computed } from 'vue';

const props = defineProps({
    modelValue: {
        type: [Boolean, String, Number, Array],
        default: undefined,
    },
    name: String,
    value: {
        type: [String, Number, Boolean],
        default: null,
    },
    title: {
        type: String,
        default: '',
    },
    description: {
        type: String,
        default: '',
    },
    disabled: {
        type: Boolean,
        default: false,
    },
    type: {
        type: String,
        default: 'checkbox',
        validator: (v) => ['checkbox', 'radio'].includes(v),
    },
});

const emit = defineEmits(['update:modelValue', 'change']);

const isSelected = computed(() => {
    if (Array.isArray(props.modelValue)) {
        return (
            props.value !== null &&
            props.value !== undefined &&
            props.modelValue.some((v) => v === props.value || String(v) === String(props.value))
        );
    }
    if (props.type === 'radio') {
        return (
            props.value !== null &&
            props.value !== undefined &&
            (props.modelValue === props.value || String(props.modelValue) === String(props.value))
        );
    }
    return props.modelValue === true;
});

const handleToggle = () => {
    if (props.disabled) return;

    let nextValue;
    if (Array.isArray(props.modelValue)) {
        const arr = [...props.modelValue];
        if (isSelected.value) {
            nextValue = arr.filter((v) => v !== props.value && String(v) !== String(props.value));
        } else {
            nextValue = [...arr, props.value];
        }
    } else if (props.type === 'radio') {
        nextValue = props.value;
    } else {
        nextValue = !props.modelValue;
    }

    emit('update:modelValue', nextValue);
    emit('change', nextValue);
};
</script>

<template>
    <component
        :is="type === 'radio' ? 'label' : 'div'"
        :role="type === 'radio' ? undefined : 'button'"
        :tabindex="type === 'radio' ? undefined : 0"
        :aria-pressed="type === 'radio' ? undefined : isSelected"
        :aria-disabled="disabled"
        class="relative flex items-start gap-4 p-4 rounded-xl border transition-all duration-200 select-none text-start cursor-pointer focus-within:ring-2 focus-within:ring-focus-ring"
        :class="[
            disabled ? 'opacity-50 cursor-not-allowed' : '',
            isSelected
                ? 'border-action-primary bg-selection-bg ring-1 ring-action-primary'
                : 'border-border bg-surface hover:border-action-primary/60',
        ]"
        @click="type !== 'radio' && handleToggle()"
        @keydown.space.self.prevent="handleToggle"
        @keydown.enter.self.prevent="handleToggle"
    >
        <input
            v-if="type === 'radio'"
            type="radio"
            :name="name"
            :value="value"
            :checked="isSelected"
            :disabled="disabled"
            class="sr-only"
            @change="handleToggle"
        />
        <!-- Indicator (Radio / Checkbox style) -->
        <div
            class="mt-0.5 shrink-0 flex items-center justify-center transition-colors duration-200"
            :class="[
                type === 'radio' ? 'w-5 h-5 rounded-full border-2' : 'w-5 h-5 rounded-md border-2',
                isSelected
                    ? 'border-action-primary bg-action-primary text-action-on'
                    : 'border-border-strong bg-surface',
            ]"
        >
            <svg
                v-if="isSelected && type === 'checkbox'"
                class="w-3.5 h-3.5 stroke-current stroke-2 fill-none"
                viewBox="0 0 24 24"
            >
                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
            </svg>
            <span
                v-else-if="isSelected && type === 'radio'"
                class="w-2 h-2 rounded-full bg-action-on"
            />
        </div>

        <!-- Content -->
        <div class="flex-1 min-w-0">
            <div class="flex items-center gap-2">
                <slot name="icon" />
                <h4 class="text-sm font-semibold text-text-primary leading-snug">
                    <slot name="title">{{ title }}</slot>
                </h4>
            </div>
            <p
                v-if="description || $slots.description"
                class="text-xs text-text-secondary mt-1 leading-normal"
            >
                <slot name="description">{{ description }}</slot>
            </p>
            <div v-if="$slots.default" class="mt-2">
                <slot />
            </div>
        </div>
    </component>
</template>
