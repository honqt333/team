<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    links: {
        type: Array,
        default: () => [],
    },
    from: {
        type: [Number, String],
        default: null,
    },
    to: {
        type: [Number, String],
        default: null,
    },
    total: {
        type: [Number, String],
        default: null,
    },
    preserveScroll: {
        type: Boolean,
        default: true,
    },
    preserveState: {
        type: Boolean,
        default: true,
    },
    showSummary: {
        type: Boolean,
        default: true,
    },
});

// Laravel returns 3 links for a single page (prev + page-1 + next).
// We show pagination only when there is more than one real page (> 3 links).
// Using >= 3 so at minimum [prev, 1, next] always renders navigation.
const shouldRender = computed(() => {
    return props.links && props.links.length >= 3;
});

// Format labels to remove ugly HTML entities if needed, while keeping numbers
const formatLabel = (label) => {
    if (!label) return '';
    return label
        .replace(/&laquo;\s*/i, '')
        .replace(/\s*&raquo;/i, '')
        .replace(/&lsaquo;\s*/i, '')
        .replace(/\s*&rsaquo;/i, '')
        .trim();
};

const isPrevious = (link, index) => {
    return index === 0 || link.label.includes('laquo') || link.label.toLowerCase().includes('prev');
};

const isNext = (link, index) => {
    return (
        index === props.links.length - 1 ||
        link.label.includes('raquo') ||
        link.label.toLowerCase().includes('next')
    );
};
</script>

<template>
    <div
        v-if="shouldRender"
        class="px-4 py-3 sm:px-6 flex flex-col sm:flex-row items-center justify-between gap-3 border-t border-border bg-surface text-text-secondary text-sm"
    >
        <!-- Summary Info -->
        <div v-if="showSummary && total !== null" class="text-xs sm:text-sm text-text-secondary">
            <span>{{ $t('common.showing', 'عرض') }}</span>
            <span class="font-semibold text-text-primary mx-1">{{ from ?? 0 }}</span>
            <span>-</span>
            <span class="font-semibold text-text-primary mx-1">{{ to ?? 0 }}</span>
            <span>{{ $t('common.of', 'من') }}</span>
            <span class="font-semibold text-text-primary mx-1">{{ total }}</span>
        </div>
        <div v-else></div>

        <!-- Pagination Controls -->
        <nav
            class="inline-flex items-center gap-1 -space-x-px rounded-xl shadow-xs"
            :aria-label="$t('common.pagination', 'التنقل بين الصفحات')"
        >
            <template v-for="(link, index) in links" :key="index">
                <!-- Clickable Link -->
                <Link
                    v-if="link.url"
                    :href="link.url"
                    :preserve-scroll="preserveScroll"
                    :preserve-state="preserveState"
                    :class="[
                        'relative inline-flex items-center justify-center min-w-[36px] h-9 px-3 text-xs sm:text-sm font-medium rounded-lg transition-all focus:outline-none focus:ring-2 focus:ring-focus-ring',
                        link.active
                            ? 'bg-action-primary text-action-on font-bold shadow-xs z-10'
                            : 'bg-surface text-text-primary hover:bg-surface-muted hover:text-text-primary border border-border',
                    ]"
                >
                    <!-- Previous Icon if it's the first button -->
                    <svg
                        v-if="isPrevious(link, index)"
                        class="w-4 h-4 rtl:rotate-180"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M15 19l-7-7 7-7"
                        />
                    </svg>

                    <!-- Next Icon if it's the last button -->
                    <svg
                        v-else-if="isNext(link, index)"
                        class="w-4 h-4 rtl:rotate-180"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M9 5l7 7-7 7"
                        />
                    </svg>

                    <!-- Page Number / Text -->
                    <span v-else>{{ formatLabel(link.label) }}</span>
                </Link>

                <!-- Disabled Link / Ellipsis -->
                <span
                    v-else
                    :class="[
                        'relative inline-flex items-center justify-center min-w-[36px] h-9 px-3 text-xs sm:text-sm font-medium rounded-lg text-text-muted/60 select-none cursor-not-allowed',
                        isPrevious(link, index) || isNext(link, index)
                            ? 'border border-border/40 bg-surface-muted/40'
                            : '',
                    ]"
                >
                    <svg
                        v-if="isPrevious(link, index)"
                        class="w-4 h-4 rtl:rotate-180 opacity-50"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M15 19l-7-7 7-7"
                        />
                    </svg>
                    <svg
                        v-else-if="isNext(link, index)"
                        class="w-4 h-4 rtl:rotate-180 opacity-50"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M9 5l7 7-7 7"
                        />
                    </svg>
                    <span v-else>{{ formatLabel(link.label) || '...' }}</span>
                </span>
            </template>
        </nav>
    </div>
</template>
