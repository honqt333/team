<script setup>
import { onMounted } from 'vue';
import { useI18n } from 'vue-i18n';
import Tooltip from '@/Components/Tooltip.vue';

const { t } = useI18n();

const props = defineProps({
    modelValue: {
        type: String,
        default: 'grid', // 'grid' | 'list'
    },
    storageKey: {
        type: String,
        default: '',
    },
    size: {
        type: String,
        default: 'md', // 'sm' | 'md'
    },
});

const emit = defineEmits(['update:modelValue', 'change']);

onMounted(() => {
    if (props.storageKey) {
        const saved =
            localStorage.getItem(`view_mode_${props.storageKey}`) ||
            localStorage.getItem(props.storageKey);
        if (saved && (saved === 'grid' || saved === 'list') && saved !== props.modelValue) {
            emit('update:modelValue', saved);
            emit('change', saved);
        }
    }
});

const setViewMode = (mode) => {
    if (mode === props.modelValue) return;
    if (props.storageKey) {
        localStorage.setItem(`view_mode_${props.storageKey}`, mode);
        localStorage.setItem(props.storageKey, mode);
    }
    emit('update:modelValue', mode);
    emit('change', mode);
};
</script>

<template>
    <div
        class="inline-flex items-center gap-0.5 p-0.5 bg-surface-muted/80 rounded-xl border border-border shadow-2xs"
        role="group"
        :aria-label="t('common.view_mode')"
    >
        <!-- Grid View Button -->
        <Tooltip :text="t('common.grid_view')">
            <button
                type="button"
                @click="setViewMode('grid')"
                :class="[
                    size === 'sm' ? 'p-1.5' : 'p-2',
                    'rounded-lg transition-all duration-200 flex items-center justify-center cursor-pointer',
                    modelValue === 'grid'
                        ? 'bg-surface text-action-primary shadow-xs font-semibold'
                        : 'text-text-muted hover:text-text-primary hover:bg-surface/50',
                ]"
                :aria-pressed="modelValue === 'grid'"
            >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"
                    />
                </svg>
            </button>
        </Tooltip>

        <!-- List View Button -->
        <Tooltip :text="t('common.list_view')">
            <button
                type="button"
                @click="setViewMode('list')"
                :class="[
                    size === 'sm' ? 'p-1.5' : 'p-2',
                    'rounded-lg transition-all duration-200 flex items-center justify-center cursor-pointer',
                    modelValue === 'list'
                        ? 'bg-surface text-action-primary shadow-xs font-semibold'
                        : 'text-text-muted hover:text-text-primary hover:bg-surface/50',
                ]"
                :aria-pressed="modelValue === 'list'"
            >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M4 6h16M4 12h16M4 18h16"
                    />
                </svg>
            </button>
        </Tooltip>
    </div>
</template>
