<template>
    <TransitionGroup name="banner" tag="div" class="space-y-0">
        <div v-for="announcement in visibleAnnouncements" :key="announcement.id"
            :class="[
                'relative flex items-center gap-3 px-4 py-2.5 text-sm border-b',
                typeStyles[announcement.type] || typeStyles.info
            ]">
            <!-- Icon -->
            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    :d="typeIcons[announcement.type] || typeIcons.info" />
            </svg>

            <!-- Content -->
            <div class="flex-1 min-w-0">
                <span class="font-semibold">{{ announcement.title }}</span>
                <span v-if="announcement.content" class="mx-1.5 opacity-70">—</span>
                <span v-if="announcement.content" class="opacity-90" v-html="truncate(announcement.content, 120)"></span>
            </div>

            <!-- Dismiss Button -->
            <button @click="dismiss(announcement)"
                class="flex-shrink-0 p-1 rounded-lg opacity-60 hover:opacity-100 transition-opacity"
                :title="$t('common.close')">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
    </TransitionGroup>
</template>

<script setup>
import { ref, computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

const page = usePage();
const dismissedIds = ref([]);

const visibleAnnouncements = computed(() => {
    const announcements = page.props.system_announcements || [];
    return announcements.filter(a => !dismissedIds.value.includes(a.id));
});

const typeStyles = {
    info: 'bg-blue-50 dark:bg-blue-900/20 text-blue-800 dark:text-blue-300 border-blue-200 dark:border-blue-800',
    warning: 'bg-amber-50 dark:bg-amber-900/20 text-amber-800 dark:text-amber-300 border-amber-200 dark:border-amber-800',
    important: 'bg-red-50 dark:bg-red-900/20 text-red-800 dark:text-red-300 border-red-200 dark:border-red-800',
    maintenance: 'bg-purple-50 dark:bg-purple-900/20 text-purple-800 dark:text-purple-300 border-purple-200 dark:border-purple-800',
};

const typeIcons = {
    info: 'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
    warning: 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z',
    important: 'M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
    maintenance: 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z',
};

async function dismiss(announcement) {
    dismissedIds.value.push(announcement.id);

    try {
        await fetch(`/app/announcements/${announcement.id}/dismiss`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
                'Accept': 'application/json',
            },
        });
    } catch (e) {
        console.error('Failed to dismiss announcement:', e);
    }
}

function truncate(text, maxLength) {
    const stripped = text.replace(/<[^>]+>/g, '');
    return stripped.length > maxLength ? stripped.substring(0, maxLength) + '...' : stripped;
}
</script>

<style scoped>
.banner-enter-active,
.banner-leave-active {
    transition: all 0.3s ease;
}

.banner-enter-from {
    opacity: 0;
    transform: translateY(-10px);
}

.banner-leave-to {
    opacity: 0;
    max-height: 0;
    padding-top: 0;
    padding-bottom: 0;
    overflow: hidden;
}
</style>
