<script setup>
import { computed, onMounted } from 'vue';
import { router } from '@inertiajs/vue3';

const props = defineProps({
    modelValue: {
        type: String,
        required: true,
    },
    tabs: {
        type: Array,
        required: true, // Array of { id, label, icon?, count?, badge?, disabled? }
    },
    variant: {
        type: String,
        default: 'underline', // 'underline' | 'pills'
    },
    syncUrl: {
        type: Boolean,
        default: false,
    },
    urlParam: {
        type: String,
        default: 'tab',
    },
    size: {
        type: String,
        default: 'md', // 'sm' | 'md' | 'lg'
    },
});

const emit = defineEmits(['update:modelValue', 'change']);

onMounted(() => {
    if (props.syncUrl && typeof window !== 'undefined') {
        const urlParams = new URLSearchParams(window.location.search);
        const tabFromUrl = urlParams.get(props.urlParam);
        if (
            tabFromUrl &&
            props.tabs.some((t) => t.id === tabFromUrl) &&
            tabFromUrl !== props.modelValue
        ) {
            emit('update:modelValue', tabFromUrl);
            emit('change', tabFromUrl);
        }
    }
});

const selectTab = (tab) => {
    if (tab.disabled || tab.id === props.modelValue) return;

    emit('update:modelValue', tab.id);
    emit('change', tab.id);

    if (props.syncUrl && typeof window !== 'undefined') {
        const url = new URL(window.location.href);
        url.searchParams.set(props.urlParam, tab.id);
        window.history.replaceState({}, '', url.toString());
    }
};
</script>

<template>
    <!-- Underline Variant -->
    <div v-if="variant === 'underline'" class="border-b border-border">
        <nav class="-mb-px flex gap-6 sm:gap-8 overflow-x-auto no-scrollbar" aria-label="Tabs">
            <button
                v-for="tab in tabs"
                :key="tab.id"
                type="button"
                @click="selectTab(tab)"
                :disabled="tab.disabled"
                :class="[
                    'group inline-flex items-center gap-2 py-4 px-1 border-b-2 font-medium text-sm sm:text-base whitespace-nowrap transition-all duration-200 focus:outline-none',
                    modelValue === tab.id
                        ? 'border-action-primary text-action-primary font-semibold'
                        : 'border-transparent text-text-secondary hover:text-text-primary hover:border-border',
                    tab.disabled ? 'opacity-40 cursor-not-allowed' : 'cursor-pointer',
                ]"
                :aria-current="modelValue === tab.id ? 'page' : undefined"
            >
                <!-- Optional Icon Slot or Prop -->
                <slot :name="`icon-${tab.id}`">
                    <span v-if="tab.icon" class="w-5 h-5 flex items-center justify-center shrink-0">
                        <component :is="tab.icon" class="w-4 h-4" />
                    </span>
                </slot>

                <span>{{ tab.label || tab.name || tab.title }}</span>

                <!-- Count Badge -->
                <span
                    v-if="tab.count !== undefined && tab.count !== null"
                    :class="[
                        'ms-2 py-0.5 px-2 rounded-full text-xs font-semibold transition-colors',
                        modelValue === tab.id
                            ? 'bg-selection-bg text-action-primary'
                            : 'bg-surface-muted text-text-secondary group-hover:bg-surface-raised',
                    ]"
                >
                    {{ tab.count }}
                </span>
            </button>
        </nav>
    </div>

    <!-- Pills / Segmented Variant -->
    <div
        v-else
        class="inline-flex p-1.5 bg-surface-muted/90 rounded-2xl border border-border max-w-full overflow-x-auto no-scrollbar gap-1"
        role="tablist"
    >
        <button
            v-for="tab in tabs"
            :key="tab.id"
            type="button"
            @click="selectTab(tab)"
            :disabled="tab.disabled"
            :class="[
                'inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-medium whitespace-nowrap transition-all duration-200 focus:outline-none',
                modelValue === tab.id
                    ? 'bg-surface text-action-primary shadow-xs font-semibold'
                    : 'text-text-secondary hover:text-text-primary hover:bg-surface/40',
                tab.disabled ? 'opacity-40 cursor-not-allowed' : 'cursor-pointer',
            ]"
            role="tab"
            :aria-selected="modelValue === tab.id"
        >
            <!-- Optional Icon Slot or Prop -->
            <slot :name="`icon-${tab.id}`">
                <span v-if="tab.icon" class="w-4 h-4 flex items-center justify-center shrink-0">
                    <component :is="tab.icon" class="w-4 h-4" />
                </span>
            </slot>

            <span>{{ tab.label || tab.name || tab.title }}</span>
            <span
                v-if="tab.count !== undefined && tab.count !== null"
                class="ms-1.5 py-0.5 px-2 rounded-full text-xs font-semibold"
                :class="
                    modelValue === tab.id
                        ? 'bg-selection-bg text-action-primary'
                        : 'bg-surface-muted text-text-secondary'
                "
            >
                {{ tab.count }}
            </span>
        </button>
    </div>
</template>

<style scoped>
.no-scrollbar::-webkit-scrollbar {
    display: none;
}
.no-scrollbar {
    -ms-overflow-style: none;
    scrollbar-width: none;
}
</style>
