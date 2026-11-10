<script setup>
import { computed } from 'vue';

const props = defineProps({
    modelValue: {
        type: [Boolean, String, Number, Array],
        default: undefined,
    },
    checked: {
        type: [Boolean, String, Number, Array],
        default: undefined,
    },
    value: {
        type: [String, Number, Boolean],
        default: null,
    },
    trueValue: {
        type: [Boolean, String, Number],
        default: true,
    },
    falseValue: {
        type: [Boolean, String, Number],
        default: false,
    },
    disabled: {
        type: Boolean,
        default: false,
    },
    size: {
        type: String,
        default: 'md',
        validator: (v) => ['sm', 'md', 'lg'].includes(v),
    },
    color: {
        type: String,
        default: 'primary',
        validator: (v) =>
            [
                'primary',
                'success',
                'danger',
                'warning',
                'violet',
                'blue',
                'info',
                'emerald',
                'red',
                'amber',
                'green',
            ].includes(v),
    },
    label: {
        type: String,
        default: '',
    },
    description: {
        type: String,
        default: '',
    },
    labelPosition: {
        type: String,
        default: 'end',
        validator: (v) => ['start', 'end'].includes(v),
    },
    name: {
        type: String,
        default: '',
    },
    id: {
        type: String,
        default: '',
    },
});

const emit = defineEmits(['update:modelValue', 'update:checked', 'change']);

const currentValue = computed(() => {
    if (props.modelValue !== undefined) {
        return props.modelValue;
    }
    if (props.checked !== undefined) {
        return props.checked;
    }
    return false;
});

const isChecked = computed(() => {
    if (Array.isArray(currentValue.value)) {
        return props.value !== null && currentValue.value.includes(props.value);
    }
    return (
        currentValue.value === props.trueValue ||
        currentValue.value === true ||
        currentValue.value === '1' ||
        currentValue.value === 1
    );
});

const handleChange = () => {
    if (props.disabled) return;

    let nextValue;
    if (Array.isArray(currentValue.value)) {
        const arr = [...currentValue.value];
        if (isChecked.value) {
            nextValue = arr.filter((v) => v !== props.value);
        } else {
            nextValue = [...arr, props.value];
        }
    } else {
        nextValue = isChecked.value ? props.falseValue : props.trueValue;
    }

    emit('update:modelValue', nextValue);
    emit('update:checked', nextValue);
    emit('change', nextValue);
};

// Size configurations with pixel-perfect vertical centering
const sizeClasses = computed(() => {
    switch (props.size) {
        case 'sm':
            return {
                track: 'h-5 w-9 p-0.5',
                knob: 'h-4 w-4',
                translate: 'translate-x-4',
            };
        case 'lg':
            return {
                track: 'h-7 w-14 p-0.5',
                knob: 'h-6 w-6',
                translate: 'translate-x-7',
            };
        case 'md':
        default:
            return {
                track: 'h-6 w-11 p-0.5',
                knob: 'h-5 w-5',
                translate: 'translate-x-5',
            };
    }
});

// Color configurations
const colorClasses = computed(() => {
    switch (props.color) {
        case 'emerald':
        case 'success':
        case 'green':
            return {
                active: 'bg-success',
                ring: 'focus:ring-success',
            };
        case 'danger':
        case 'red':
            return {
                active: 'bg-danger',
                ring: 'focus:ring-danger',
            };
        case 'warning':
        case 'amber':
            return {
                active: 'bg-warning',
                ring: 'focus:ring-warning',
            };
        case 'info':
        case 'blue':
            return {
                active: 'bg-info',
                ring: 'focus:ring-info',
            };
        case 'violet':
        case 'indigo':
        case 'primary':
        default:
            return {
                active: 'bg-action-primary',
                ring: 'focus:ring-focus-ring',
            };
    }
});
</script>

<template>
    <div
        class="inline-flex align-middle select-none"
        :class="[
            disabled ? 'opacity-60 cursor-not-allowed' : 'cursor-pointer',
            description || $slots.description ? 'items-start pt-0.5' : 'items-center',
            label || $slots.default || description || $slots.description ? 'gap-3' : '',
            labelPosition === 'start' ? 'flex-row-reverse justify-end' : '',
        ]"
        @click="handleChange"
    >
        <!-- Switch Button (Locked in deterministic LTR coordinate space for clean sliding) -->
        <button
            :id="id || undefined"
            type="button"
            role="switch"
            dir="ltr"
            :name="name || undefined"
            :aria-checked="isChecked"
            :aria-label="label || undefined"
            :disabled="disabled"
            class="relative inline-flex items-center shrink-0 cursor-pointer rounded-full transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-surface"
            :class="[
                sizeClasses.track,
                isChecked ? colorClasses.active : 'bg-toggle-off-bg',
                colorClasses.ring,
                disabled ? 'cursor-not-allowed' : '',
            ]"
            @click.stop="handleChange"
        >
            <span
                aria-hidden="true"
                class="pointer-events-none inline-block rounded-full bg-toggle-thumb shadow-md ring-0 transform transition-transform duration-200 ease-in-out"
                :class="[sizeClasses.knob, isChecked ? sizeClasses.translate : 'translate-x-0']"
            />
        </button>

        <!-- Optional Label & Description -->
        <div
            v-if="label || $slots.default || description || $slots.description"
            class="flex flex-col"
        >
            <span
                v-if="label || $slots.default"
                class="font-medium text-text-primary leading-tight"
                :class="[size === 'sm' ? 'text-xs' : 'text-sm']"
            >
                <slot>{{ label }}</slot>
            </span>
            <span
                v-if="description || $slots.description"
                class="text-xs text-text-secondary mt-1 leading-normal"
            >
                <slot name="description">{{ description }}</slot>
            </span>
        </div>
    </div>
</template>
