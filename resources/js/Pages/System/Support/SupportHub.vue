<template>
    <SystemLayout>
        <Head :title="$t('system.support.hub_title')" />

        <div class="space-y-6">
            <!-- Unified Page Header -->
            <PageHeader
                :title="$t('system.support.hub_title')"
                :subtitle="$t('system.support.hub_subtitle')"
                gradientFrom="from-purple-600"
                gradientTo="to-pink-600"
                glowFrom="from-purple-500"
                glowTo="to-pink-600"
                badgeBg="bg-purple-50/70 dark:bg-purple-950/40"
                badgeText="text-purple-600 dark:text-purple-400"
                badgeBorder="border-purple-200/50 dark:border-purple-800/40"
                badgeDot="bg-purple-500"
            >
                <template #icon>
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"
                        />
                    </svg>
                </template>
            </PageHeader>

            <!-- Standardized Approved Tabs Component -->
            <AppTabs
                v-model="currentTab"
                :tabs="tabs"
                variant="underline"
                @change="handleTabChange"
            />

            <!-- Tab Panels -->
            <div class="mt-4">
                <FeedbacksTab
                    v-if="currentTab === 'feedbacks'"
                    :feedbacks="feedbacks"
                    :stats="feedbackStats"
                    :filters="filters"
                />
                <ContactMessagesTab
                    v-else-if="currentTab === 'contact_messages'"
                    :messages="contactMessages"
                    :filters="filters"
                />
                <HelpCenterTab v-else-if="currentTab === 'help'" :settings="helpSettings" />
            </div>
        </div>
    </SystemLayout>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import SystemLayout from '@/Layouts/SystemLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import AppTabs from '@/Components/Common/AppTabs.vue';
import FeedbacksTab from './Tabs/FeedbacksTab.vue';
import ContactMessagesTab from './Tabs/ContactMessagesTab.vue';
import HelpCenterTab from './Tabs/HelpCenterTab.vue';

const { t } = useI18n();

const props = defineProps({
    tab: { type: String, default: 'feedbacks' },
    feedbacks: { type: Object, default: () => ({}) },
    feedbackStats: { type: Object, default: () => ({}) },
    contactMessages: { type: Object, default: () => ({}) },
    helpSettings: { type: Object, default: () => ({}) },
    unreadMessagesCount: { type: Number, default: 0 },
    pendingFeedbacksCount: { type: Number, default: 0 },
    filters: { type: Object, default: () => ({}) },
});

const currentTab = ref(props.tab || 'feedbacks');

watch(
    () => props.tab,
    (newTab) => {
        if (newTab && newTab !== currentTab.value) {
            currentTab.value = newTab;
        }
    }
);

const tabs = computed(() => [
    {
        id: 'feedbacks',
        label: t('system.support.tabs.feedbacks'),
        name: t('system.support.tabs.feedbacks'),
        count: props.pendingFeedbacksCount || null,
    },
    {
        id: 'contact_messages',
        label: t('system.support.tabs.contact_messages'),
        name: t('system.support.tabs.contact_messages'),
        count: props.unreadMessagesCount || null,
    },
    {
        id: 'help',
        label: t('system.support.tabs.help'),
        name: t('system.support.tabs.help'),
    },
]);

const handleTabChange = (tabId) => {
    currentTab.value = tabId;
    router.get(
        '/system/support',
        { tab: tabId },
        { preserveState: true, preserveScroll: true, replace: true }
    );
};
</script>
