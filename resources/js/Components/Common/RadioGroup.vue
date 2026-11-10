<script setup>
const props = defineProps({
    modelValue: {
        type: [String, Number, Boolean, null],
        default: null,
    },
    options: {
        type: Array,
        default: () => [],
    },
    name: {
        type: String,
        default: () => `radio-group-${Math.random().toString(36).slice(2, 9)}`,
    },
    direction: {
        type: String,
        default: 'vertical',
        validator: (v) => ['vertical', 'horizontal'].includes(v),
    },
    disabled: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['update:modelValue', 'change']);

const selectOption = (value, optDisabled) => {
    if (props.disabled || optDisabled) return;
    emit('update:modelValue', value);
    emit('change', value);
};
</script>

<template>
    <div
        role="radiogroup"
        class="flex"
        :class="[
            direction === 'horizontal' ? 'flex-wrap gap-4 sm:gap-6' : 'flex-col gap-3',
            disabled ? 'opacity-60 cursor-not-allowed' : '',
        ]"
    >
        <label
            v-for="opt in options"
            :key="opt.value"
            class="inline-flex items-start gap-3 cursor-pointer select-none"
            :class="{ 'opacity-50 cursor-not-allowed': opt.disabled || disabled }"
            @click.prevent="selectOption(opt.value, opt.disabled)"
        >
            <div class="relative flex items-center justify-center pt-0.5">
                <input
                    type="radio"
                    :name="name"
                    :value="opt.value"
                    :checked="modelValue === opt.value"
                    :disabled="disabled || opt.disabled"
                    class="w-4 h-4 text-action-primary border-border-strong bg-surface focus:ring-focus-ring focus:ring-2 focus:ring-offset-2 focus:ring-offset-surface cursor-pointer"
                />
            </div>

            <div class="flex flex-col text-start">
                <span class="text-sm font-medium text-text-primary leading-snug">
                    {{ opt.label }}
                </span>
                <span v-if="opt.description" class="text-xs text-text-secondary mt-0.5">
                    {{ opt.description }}
                </span>
            </div>
        </label>
    </div>
</template>
