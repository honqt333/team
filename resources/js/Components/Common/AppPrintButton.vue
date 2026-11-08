<script setup>
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import Tooltip from '@/Components/Tooltip.vue';

const { t } = useI18n();

const props = defineProps({
    label: {
        type: String,
        default: '',
    },
    tooltip: {
        type: String,
        default: '',
    },
    loading: {
        type: Boolean,
        default: false,
    },
    disabled: {
        type: Boolean,
        default: false,
    },
    variant: {
        type: String,
        default: 'secondary', // 'primary' | 'secondary' | 'ghost' | 'icon'
    },
    size: {
        type: String,
        default: 'md', // 'sm' | 'md' | 'lg'
    },
});

const emit = defineEmits(['click']);

const resolvedTooltip = computed(() => {
    if (props.tooltip) return props.tooltip;
    if (props.variant === 'icon' || !props.label) return t('common.print');
    return '';
});

const buttonClasses = computed(() => {
    const base =
        'inline-flex items-center justify-center font-medium transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2';

    // Size classes
    const sizeClasses = {
        sm:
            props.variant === 'icon'
                ? 'p-1.5 rounded-lg'
                : 'px-3 py-1.5 text-xs rounded-lg gap-1.5',
        md: props.variant === 'icon' ? 'p-2.5 rounded-xl' : 'px-4 py-2.5 text-sm rounded-xl gap-2',
        lg:
            props.variant === 'icon'
                ? 'p-3 rounded-2xl'
                : 'px-5 py-3 text-base rounded-2xl gap-2.5',
    }[props.size];

    // Variant classes
    const variantClasses = {
        primary:
            'bg-indigo-600 hover:bg-indigo-700 text-white shadow-sm hover:shadow focus:ring-indigo-500',
        secondary:
            'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-200 border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-750 shadow-sm focus:ring-indigo-500',
        ghost: 'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-800 focus:ring-gray-500',
        icon: 'text-gray-500 hover:text-indigo-600 dark:text-gray-400 dark:hover:text-indigo-400 hover:bg-white dark:hover:bg-gray-800 rounded-xl shadow-sm hover:shadow focus:ring-indigo-500 border border-transparent hover:border-gray-200 dark:hover:border-gray-700',
    }[props.variant];

    return [
        base,
        sizeClasses,
        variantClasses,
        props.disabled || props.loading ? 'opacity-60 cursor-not-allowed' : '',
    ];
});
</script>

<template>
    <Tooltip :text="resolvedTooltip" :disabled="!resolvedTooltip">
        <button
            type="button"
            :class="buttonClasses"
            :disabled="disabled || loading"
            @click="emit('click', $event)"
        >
            <!-- Loading Spinner -->
            <svg
                v-if="loading"
                class="animate-spin h-4 w-4 text-current"
                fill="none"
                viewBox="0 0 24 24"
            >
                <circle
                    class="opacity-25"
                    cx="12"
                    cy="12"
                    r="10"
                    stroke="currentColor"
                    stroke-width="4"
                ></circle>
                <path
                    class="opacity-75"
                    fill="currentColor"
                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                ></path>
            </svg>

            <!-- Print Icon -->
            <svg
                v-else
                class="w-4 h-4 shrink-0"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
            >
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"
                />
            </svg>

            <!-- Button Label (if not icon-only) -->
            <span v-if="variant !== 'icon' && label" class="truncate">{{ label }}</span>
        </button>
    </Tooltip>
</template>
