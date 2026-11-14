<script setup>
import { onBeforeUnmount, onMounted, ref } from 'vue';
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

const desktopMode = ref(props.modelValue);
let mediaQuery = null;

const getSavedMode = () => {
    if (!props.storageKey || typeof localStorage === 'undefined') return null;
    const saved =
        localStorage.getItem(`view_mode_${props.storageKey}`) ||
        localStorage.getItem(props.storageKey);
    return saved === 'grid' || saved === 'list' ? saved : null;
};

const applyViewportMode = (isMobile) => {
    if (isMobile) {
        if (props.modelValue !== 'grid') {
            desktopMode.value = getSavedMode() || props.modelValue;
            emit('update:modelValue', 'grid');
            emit('change', 'grid');
        }
        return;
    }

    const target = getSavedMode() || desktopMode.value || props.modelValue;
    if (target && target !== props.modelValue) {
        emit('update:modelValue', target);
        emit('change', target);
    }
};

const onMediaChange = (event) => {
    applyViewportMode(event.matches);
};

onMounted(() => {
    const saved = getSavedMode();
    if (saved) {
        desktopMode.value = saved;
    }

    if (typeof window !== 'undefined' && typeof window.matchMedia === 'function') {
        mediaQuery = window.matchMedia('(max-width: 767px)');
        applyViewportMode(mediaQuery.matches);
        if (typeof mediaQuery.addEventListener === 'function') {
            mediaQuery.addEventListener('change', onMediaChange);
        } else if (typeof mediaQuery.addListener === 'function') {
            mediaQuery.addListener(onMediaChange);
        }
        return;
    }

    applyViewportMode(false);
});

onBeforeUnmount(() => {
    if (!mediaQuery) return;
    if (typeof mediaQuery.removeEventListener === 'function') {
        mediaQuery.removeEventListener('change', onMediaChange);
    } else if (typeof mediaQuery.removeListener === 'function') {
        mediaQuery.removeListener(onMediaChange);
    }
});

const setViewMode = (mode) => {
    if (mode === props.modelValue) return;
    desktopMode.value = mode;
    if (props.storageKey && typeof localStorage !== 'undefined') {
        localStorage.setItem(`view_mode_${props.storageKey}`, mode);
        localStorage.setItem(props.storageKey, mode);
    }
    emit('update:modelValue', mode);
    emit('change', mode);
};
</script>

<template>
    <div
        class="hidden md:inline-flex items-center gap-0.5 p-0.5 bg-surface-muted/80 rounded-xl border border-border shadow-2xs"
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
